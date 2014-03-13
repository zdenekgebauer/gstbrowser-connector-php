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
class MkDirTest extends \PHPUnit_Framework_TestCase
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

    public function testMkDir()
    {
        $output = $this->_obj->mkDir('', 'a');
        $folders = array(
            array('name' => 'phpunitdata',
                'children' => array(
                    array('name' => 'a')
                )
            )
        );
        $files = array(
            array('name' => 'a',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'a')),
            'imgsize' => NULL,
            'thumbnail' => NULL)
        );
        $expect = array(
            'status'=>'OK',
            'tree'=>$folders,
            'files'=>$files
        );
        $this->assertEquals($expect, $output);
        //cache file
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $expect = array('a' => (object) array('name' => 'a',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'a')),
            'imgsize' => NULL,
            'thumbnail' => NULL));
        $this->assertEquals($expect, $cache);

        // create
        $output = $this->_obj->mkDir('', 'b');
        $folders = array(
            array('name' => 'phpunitdata',
                'children' => array(
                    array('name' => 'a'),
                    array('name' => 'b')
                )
            )
        );
        $files = array(
            array('name' => 'a',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'a')),
            'imgsize' => NULL,
            'thumbnail' => NULL),
            array('name' => 'b',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'b')),
            'imgsize' => NULL,
            'thumbnail' => NULL)
        );
        $expect = array(
            'status'=>'OK',
            'tree'=>$folders,
            'files'=>$files
        );
        $this->assertEquals($expect, $output);
        //cache file
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $expect = (object) array(
            'a' => (object) array('name' => 'a',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'a')),
            'imgsize' => NULL,
            'thumbnail' => NULL),
            'b' => (object) array('name' => 'b',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'b')),
            'imgsize' => NULL,
            'thumbnail' => NULL)
        );
        $this->assertEquals($expect, $cache);


        $output = $this->_obj->mkDir('a', 'aa');
        $folders = array(
            array('name' => 'phpunitdata',
                'children' => array(
                    array('name' => 'a',
                        'children' => array(
                            array('name' => 'aa')
                        )
                    ),
                    array('name' => 'b')
                )
            )
        );
        $files = array(
            array('name' => 'aa',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'a/aa')),
            'imgsize' => NULL,
            'thumbnail' => NULL)
        );
        $expect = array(
            'status'=>'OK',
            'tree'=>$folders,
            'files'=>$files
        );
        $this->assertEquals($expect, $output);
        //cache file
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'a/.htdircache');
        $cache = json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'a/.htdircache'));
        $expect = (object) array(
            'aa' => (object) array('name' => 'aa',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'a/aa')),
            'imgsize' => NULL,
            'thumbnail' => NULL)
        );
        $this->assertEquals($expect, $cache);
    }

    public function testMkDirExists()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a');

        $output = $this->_obj->mkDir('', 'a');
        $expect = array(
            'status'=>'ERR',
            'err'=>\GstBrowser\Connector::ERR_MKDIR_EXISTS
        );
        $this->assertEquals($expect, $output);

        $output = $this->_obj->mkDir('notexists', 'a');
        $expect = array(
            'status'=>'ERR',
            'err'=>\GstBrowser\Connector::ERR_DIRECTORY_NOT_FOUND
        );
        $this->assertEquals($expect, $output);
    }

    public function testMkDirEmpty()
    {
        $output = $this->_obj->mkDir('', '');
        $expect = array(
            'status'=>'ERR',
            'err'=>\GstBrowser\Connector::ERR_INVALID_PARAMETER
        );
        $this->assertEquals($expect, $output);
    }

    public function testMkDirInvalidDir()
    {
        $output = $this->_obj->mkDir('', 'in:valid');
        $expect = array(
            'status'=>'ERR',
            'err'=>\GstBrowser\Connector::ERR_INVALID_PARAMETER
        );
        $this->assertEquals($expect, $output);
    }

}