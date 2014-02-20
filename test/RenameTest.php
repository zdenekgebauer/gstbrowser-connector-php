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
class RenameTest extends \PHPUnit_Framework_TestCase
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

    public function testRenameFile()
    {
        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'txt-file.txt');

        // get files to create cache file
        $output = $this->_obj->getFiles('');
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = unserialize(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(1, count($cache));

        $output = $this->_obj->rename('', 'txt-file.txt', 'newname.txt');
        $files = array(array('name' => 'newname.txt',
            'type' => 'file',
            'size' => filesize(FILEBROWSER_DATA_DIR.'newname.txt'),
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'newname.txt')),
            'imgsize' => NULL,
            'thumbnail' => NULL));
        $expect = array(
            'status'=>'OK',
            'files'=>$files
        );
        $this->assertEquals($expect, $output);

        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = unserialize(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(1, count($cache));
    }

    public function testRenameFolder()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a');

        // get files to create cache file
        $output = $this->_obj->getFiles('a');
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'a/.htdircache');
        $cache = unserialize(file_get_contents(FILEBROWSER_DATA_DIR.'a/.htdircache'));
        $this->assertEquals(0, count($cache));

        $output = $this->_obj->rename('', 'a', 'b');

        $this->assertTrue(is_dir(FILEBROWSER_DATA_DIR.'b'));

        $files = array(
            array('name' => 'b',
                'type' => 'dir',
                'size' => NULL,
                'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'b')),
                'imgsize' => NULL,
                'thumbnail' => NULL)
        );
        $folders = array(
            array('name' => 'phpunitdata',
                'children' => array(
                    array('name' => 'b')
                )
            )
        );
        $expect = array(
            'status'=>'OK',
            'files'=>$files,
            'tree'=>$folders
        );
        $this->assertEquals($expect, $output);

        $this->assertFileExists(FILEBROWSER_DATA_DIR.'b/.htdircache');
    }

    public function testRenameEmptySource()
    {
        $output = $this->_obj->rename('', '', 'file.txt');
        $expect = array(
            'status'=>'ERR',
            'err'=>  \GstBrowser\Connector::ERR_INVALID_PARAMETER
        );
        $this->assertEquals($expect, $output);
    }

    public function testRenameEmptyTarget()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a');
        $output = $this->_obj->rename('', 'a', '');

        $this->assertTrue(is_dir(FILEBROWSER_DATA_DIR.'a'));

        $expect = array(
            'status'=>'ERR',
            'err'=>  \GstBrowser\Connector::ERR_INVALID_PARAMETER
        );
        $this->assertEquals($expect, $output);
    }

    public function testRenameInvalidSource()
    {
        $output = $this->_obj->rename('', 'notexists', 'file.txt');
        $expect = array(
            'status' => 'ERR',
            'err' => \GstBrowser\Connector::ERR_FILE_NOT_FOUND
        );
        $this->assertEquals($expect, $output);
    }


    public function testRenameInvalidTarget()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a');
        $output = $this->_obj->rename('', 'a', 'in:valid');
        $expect = array(
            'status' => 'ERR',
            'err' => \GstBrowser\Connector::ERR_INVALID_PARAMETER
        );
        $this->assertEquals($expect, $output);
    }

}