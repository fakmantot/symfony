<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\HttpFoundation\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedFileTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!ini_get('file_uploads')) {
            $this->markTestSkipped('file_uploads is disabled in php.ini');
        }
    }

    public function testConstructWhenFileNotExists()
    {
        $this->setExpectedException('Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException');

        new UploadedFile(
            __DIR__.'/Fixtures/not_here',
            'original.gif',
            null
        );
    }

    public function testFileUploadsWithNoMimeType()
    {
        $file = new UploadedFile(
            __DIR__.'/Fixtures/test.gif',
            'original.gif',
            null,
            filesize(__DIR__.'/Fixtures/test.gif'),
            UPLOAD_ERR_OK
        );

        $this->assertEquals('application/octet-stream', $file->getClientMimeType());

        if (extension_loaded('fileinfo')) {
            $this->assertEquals('image/gif', $file->getMimeType());
        }
    }

    public function testFileUploadsWithUnknownMimeType()
    {
        $file = new UploadedFile(
            __DIR__.'/Fixtures/.unknownextension',
            'original.gif',
            null,
            filesize(__DIR__.'/Fixtures/.unknownextension'),
            UPLOAD_ERR_OK
        );

        $this->assertEquals('application/octet-stream', $file->getClientMimeType());
    }

    public function testErrorIsOkByDefault()
    {
        $file = new UploadedFile(
            __DIR__.'/Fixtures/test.gif',
            'original.gif',
            'image/gif',
            filesize(__DIR__.'/Fixtures/test.gif'),
            null
        );

        $this->assertEquals(UPLOAD_ERR_OK, $file->getError());
    }

    /**
     * @expectedException Symfony\Component\HttpFoundation\File\Exception\FileException
     */
    public function testMoveLocalFileIsNotAllowed()
    {
        $file = new UploadedFile(
            __DIR__.'/Fixtures/test.gif',
            'original.gif',
            'image/gif',
            filesize(__DIR__.'/Fixtures/test.gif'),
            UPLOAD_ERR_OK
        );

        $movedFile = $file->move(__DIR__.'/Fixtures/directory');
    }

    public function testMoveLocalFileIsAllowedInTestMode()
    {
        $path = __DIR__.'/Fixtures/test.copy.gif';
        $targetDir = __DIR__.'/Fixtures/directory';
        $targetPath = $targetDir.'/test.copy.gif';
        @unlink($path);
        @unlink($targetPath);
        copy(__DIR__.'/Fixtures/test.gif', $path);

        $file = new UploadedFile(
            $path,
            'original.gif',
            'image/gif',
            filesize($path),
            UPLOAD_ERR_OK,
            true
        );

        $movedFile = $file->move(__DIR__.'/Fixtures/directory');

        $this->assertTrue(file_exists($targetPath));
        $this->assertFalse(file_exists($path));
        $this->assertEquals(realpath($targetPath), $movedFile->getRealPath());

        @unlink($targetPath);
    }

    /**
     * @dataProvider getClientFilenameFixtures
     */
    public function testGetClientOriginalNameSanitizeFilename($filename, $sanitizedFilename)
    {
        $file = new UploadedFile(
            __DIR__.'/Fixtures/test.gif',
            $filename,
            'image/gif',
            filesize(__DIR__.'/Fixtures/test.gif'),
            null
        );

        $this->assertEquals($sanitizedFilename, $file->getClientOriginalName());
    }

    public function getClientFilenameFixtures()
    {
        return array(
            array('original.gif', 'original.gif'),
            array('..\\..\\original.gif', 'original.gif'),
            array('../../original.gif', 'original.gif'),
            array('????????file.gif', '????????file.gif'),
            array('..\\..\\????????file.gif', '????????file.gif'),
            array('../../????????file.gif', '????????file.gif'),
        );
    }

    public function testGetSize()
    {
        $file = new UploadedFile(
            __DIR__.'/Fixtures/test.gif',
            'original.gif',
            'image/gif',
            filesize(__DIR__.'/Fixtures/test.gif'),
            null
        );

        $this->assertEquals(filesize(__DIR__.'/Fixtures/test.gif'), $file->getSize());

        $file = new UploadedFile(
            __DIR__.'/Fixtures/test',
            'original.gif',
            'image/gif'
        );

        $this->assertEquals(filesize(__DIR__.'/Fixtures/test'), $file->getSize());
    }

    public function testGetExtension()
    {
        $file = new UploadedFile(
            __DIR__.'/Fixtures/test.gif',
            'original.gif',
            null
        );

        $this->assertEquals('gif', $file->getExtension());
    }

    public function testIsValid()
    {
        $file = new UploadedFile(
            __DIR__.'/Fixtures/test.gif',
            'original.gif',
            null,
            filesize(__DIR__.'/Fixtures/test.gif'),
            UPLOAD_ERR_OK
        );

        $this->assertTrue($file->isValid());
    }

    /**
     * @dataProvider uploadedFileErrorProvider
     */
    public function testIsInvalidOnUploadError($error)
    {
        $file = new UploadedFile(
            __DIR__.'/Fixtures/test.gif',
            'original.gif',
            null,
            filesize(__DIR__.'/Fixtures/test.gif'),
            $error
        );

        $this->assertFalse($file->isValid());
    }

    public function uploadedFileErrorProvider()
    {
        return array(
            array(UPLOAD_ERR_INI_SIZE),
            array(UPLOAD_ERR_FORM_SIZE),
            array(UPLOAD_ERR_PARTIAL),
            array(UPLOAD_ERR_NO_TMP_DIR),
            array(UPLOAD_ERR_EXTENSION),
        );
    }
}
