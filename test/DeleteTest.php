<?php
/**
 * @package GstBrowser
 * @author  Zdenek Gebauer <zdenek.gebauer@gmail.com>
 */

require_once 'bootstrap.php';
require_once dirname(__DIR__).'/connector.php';

/**
 * @package    GstLib
 * @subpackage FileBrowserTest
 */
class DeleteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        rmDirRecursive(FILEBROWSER_DATA_DIR);
        mkDirRecursive(FILEBROWSER_DATA_DIR);
        date_default_timezone_set('UTC');
        $this->_obj = new \GstBrowser\Connector(new \GstBrowser\Config(FILEBROWSER_DATA_DIR));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        rmDirRecursive(FILEBROWSER_DATA_DIR);
    }

    public function testDeleteFile()
    {
        copy (dirname(__DIR__).'/test/phpunit.jpg', FILEBROWSER_DATA_DIR.'jpeg-image.jpg');
        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'txt-file.txt');

        // get files to create cache file
        $output = $this->_obj->getFiles('');
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(2, count($cache));

        $output = $this->_obj->delete('', 'jpeg-image.jpg');
        $files = array((object) array('name' => 'txt-file.txt',
            'type' => 'file',
            'size' => filesize(FILEBROWSER_DATA_DIR.'txt-file.txt'),
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'txt-file.txt')),
            'imgsize' => NULL,
            'thumbnail' => NULL));
        $expect = array(
            'status'=>'OK',
            'files'=>$files
        );
        $this->assertEquals($expect, $output);

        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(1, count($cache));
    }

    public function testDeleteFolder()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a');

        // get files to create cache file
        $output = $this->_obj->getFiles('');
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(1, count($cache));

        $output = $this->_obj->delete('', 'a');
        $folders = array(
            array('name' => 'phpunitdata')
        );
        $expect = array(
            'status'=>'OK',
            'tree'=>$folders,
            'files'=>array()
        );
        $this->assertEquals($expect, $output);

        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(0, count($cache));
    }

    public function testDeleteFileNotExists()
    {
        $output = $this->_obj->delete('', 'notexists.jpg');
        $expect = array(
            'status' => 'ERR',
            'err' => \GstBrowser\Connector::ERR_FILE_NOT_FOUND
        );
        $this->assertEquals($expect, $output);
    }

    public function testDeleteFileReadOnly()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $this->markTestSkipped('test only on windows');
        }

        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'txt-file.txt');
        chmod(FILEBROWSER_DATA_DIR.'txt-file.txt', 0400); // should set read-only
        $output = $this->_obj->delete('', 'txt-file.txt');
        $expect = array(
            'status' => 'ERR',
            'err' => \GstBrowser\Connector::ERR_DELETE
        );
        $this->assertEquals($expect, $output);
        chmod(FILEBROWSER_DATA_DIR.'txt-file.txt', 0700); // should set read-only
    }

    public function testDeleteFolderNotEmpty()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a');
        copy (dirname(__DIR__).'/test/phpunit.jpg', FILEBROWSER_DATA_DIR.'a/jpeg-image.jpg');

        // get files to create cache file
        $output = $this->_obj->getFiles('');
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(1, count($cache));
        $output = $this->_obj->getFiles('a');
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'a/.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'a/.htdircache'));
        $this->assertEquals(1, count($cache));

        $output = $this->_obj->delete('', 'a');
        $expect = array(
            'status' => 'ERR',
            'err' => \GstBrowser\Connector::ERR_DELETE_NOT_EMPTY_DIR
        );
        $this->assertEquals($expect, $output);

        $this->assertFileExists(FILEBROWSER_DATA_DIR.'a/jpeg-image.jpg');
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'a/.htdircache');
    }

    public function testDeleteFolderWithCache()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a');
        copy (dirname(__DIR__).'/test/phpunit.jpg', FILEBROWSER_DATA_DIR.'a/jpeg-image.jpg');

        // get files to create cache file
        $output = $this->_obj->getFiles('a');
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'a/.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'a/.htdircache'));
        $this->assertEquals(1, count($cache));
        unlink(FILEBROWSER_DATA_DIR.'a/jpeg-image.jpg');

        $output = $this->_obj->delete('', 'a');
        $folders = array(
            array('name' => 'phpunitdata')
        );
        $expect = array(
            'status'=>'OK',
            'tree'=>$folders,
            'files'=>array()
        );
        $this->assertEquals($expect, $output);

        $this->assertFileNotExists(FILEBROWSER_DATA_DIR.'a/.htdircache');
    }

}