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
class MoveTest extends \PHPUnit_Framework_TestCase
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

    public function testMoveFile()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a');
        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'txt-file.txt');

        // get files to create cache file
        $output = $this->_obj->getFiles('');
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(2, count($cache));

        $output = $this->_obj->move('', 'txt-file.txt', 'a');
        $files = array((object) array(
            'name' => 'a',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'a')),
            'imgsize' => NULL,
            'thumbnail' => ''));
        $expect = array(
            'status'=>'OK',
            'files'=>$files
        );
        $this->assertEquals($expect, $output);

        $this->assertFileNotExists(FILEBROWSER_DATA_DIR.'txt-file.txt');
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(1, count($cache));

        $this->assertFileExists(FILEBROWSER_DATA_DIR.'a/.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'a/.htdircache'));
        $this->assertEquals(1, count($cache));
    }

    public function testMoveFileNewName()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a');
        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'txt-file.txt');

        // get files to create cache file
        $output = $this->_obj->getFiles('');
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(2, count($cache));

        $output = $this->_obj->move('', 'txt-file.txt', 'a/new.txt');
        $files = array((object) array(
            'name' => 'a',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'a')),
            'imgsize' => NULL,
            'thumbnail' => ''));
        $expect = array(
            'status'=>'OK',
            'files'=>$files
        );
        $this->assertEquals($expect, $output);

        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(1, count($cache));

        $this->assertFileNotExists(FILEBROWSER_DATA_DIR.'/txt-file.txt');
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'a/new.txt');
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'a/.htdircache');
        $cache = json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'a/.htdircache'));
        $this->assertEquals(1, count($cache));

        $expect = (object) array('new.txt'=> (object) array(
            'name' => 'new.txt',
            'type' => 'file',
            'size' => filesize(FILEBROWSER_DATA_DIR.'a/new.txt'),
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'a/new.txt')),
            'imgsize' => NULL,
            'thumbnail' => ''));
        $this->assertEquals($expect, $cache);
    }

    public function testMoveDir()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a');
        mkDirRecursive(FILEBROWSER_DATA_DIR.'b');
        $output = $this->_obj->move('', 'a', 'b');
        $expect = array(
            'status' => 'ERR',
            'err' => \GstBrowser\Connector::ERR_INVALID_PARAMETER
        );
        $this->assertEquals($expect, $output);
    }

    public function testMoveFileNotExists()
    {
        $output = $this->_obj->move('', 'notexists.jpg', 'a');
        $expect = array(
            'status' => 'ERR',
            'err' => \GstBrowser\Connector::ERR_FILE_NOT_FOUND
        );
        $this->assertEquals($expect, $output);
    }

    public function testMoveTargetFileAlreadyExists()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a');
        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'txt-file.txt');
        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'a/new.txt');
        $output = $this->_obj->move('', 'txt-file.txt', 'a/new.txt');
        $expect = array(
            'status' => 'ERR',
            'err' => \GstBrowser\Connector::ERR_COPY_FILE_EXISTS
        );
        $this->assertEquals($expect, $output);
    }

    public function testMoveTargetDirNotExists()
    {
        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'txt-file.txt');
        $output = $this->_obj->move('', 'txt-file.txt', 'a/new.txt');
        $expect = array(
            'status' => 'ERR',
            'err' => \GstBrowser\Connector::ERR_COPY_DIR_NOT_FOUND
        );
        $this->assertEquals($expect, $output);
    }

    public function testMoveInvalidFilename()
    {
        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'txt-file.txt');
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a');
        $output = $this->_obj->move('', 'txt-file.txt', 'a/in:valid.txt');
        $expect = array(
            'status' => 'ERR',
            'err' => \GstBrowser\Connector::ERR_INVALID_PARAMETER
        );
        $this->assertEquals($expect, $output);
    }

}