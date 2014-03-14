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
class BrowserTest extends \PHPUnit_Framework_TestCase
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
        $config = new \GstBrowser\Config(FILEBROWSER_DATA_DIR);
        $config->modeDir(0777);
        $config->modeFile(0666);
        $this->_obj = new \GstBrowser\Connector($config);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        rmDirRecursive(FILEBROWSER_DATA_DIR);
    }

    public function testTree()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a/aa');
        mkDirRecursive(FILEBROWSER_DATA_DIR.'b');

        $data = array(
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

        $expect = array(
            'status'=>'OK',
            'tree'=>$data
        );
        $output = $this->_obj->getFoldersTree();
        $this->assertEquals($expect, $output);
    }

    public function testTreeWithInvalidFolder()
    {
        $obj = new \GstBrowser\Connector(new \GstBrowser\Config('notexists'));

        $expect = array(
            'status' => 'ERR',
            'err' => \GstBrowser\Connector::ERR_DIRECTORY_NOT_FOUND
        );
        $output = $obj->getFoldersTree();
        $this->assertEquals($expect, $output);
    }

    public function testFilesInRoot()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a/aa');
        mkDirRecursive(FILEBROWSER_DATA_DIR.'b');
        copy (dirname(__DIR__).'/test/phpunit.jpg', FILEBROWSER_DATA_DIR.'jpeg-image.jpg');
        copy (dirname(__DIR__).'/test/phpunit.gif', FILEBROWSER_DATA_DIR.'gif-image.gif');
        copy (dirname(__DIR__).'/test/phpunit.png', FILEBROWSER_DATA_DIR.'png-image.png');
        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'txt-file.txt');

        $output = $this->_obj->getFiles('');
        // thumbnails are generated from resized image, ignore diffs
        for ($index = 0; $index < count($output['files']); $index++) {
            if (!is_null($output['files'][$index]['thumbnail'])) {
                $tmp = explode(',', $output['files'][$index]['thumbnail']);
                $output['files'][$index]['thumbnail'] = reset($tmp);
            }
        }
        $this->assertEquals('OK', $output['status']);
        $this->assertEquals (6, count($output['files']));

        //cache file
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        // thumbnails are generated from resized image, ignore diffs
        foreach ($cache as $key=>$value) {
            if (!is_null($value->thumbnail)) {
                $tmp = explode(',', $value->thumbnail);
                $cache[$key]->thumbnail = reset($tmp);
            }
        }

        $file = (object) array('name' => 'a',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'a')),
            'imgsize' => NULL,
            'thumbnail' => '');
        $this->assertEquals($file, $cache['a']);

        $file = (object) array('name' => 'b',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'b')),
            'imgsize' => NULL,
            'thumbnail' => '');
        $this->assertEquals($file, $cache['b']);

        $file = (object) array('name' => 'jpeg-image.jpg',
                'type' => 'file',
                'size' => filesize(FILEBROWSER_DATA_DIR.'jpeg-image.jpg'),
                'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'jpeg-image.jpg')),
                'imgsize' => array (94, 80),
                'thumbnail' => 'data:image/jpg;base64');
        $this->assertEquals($file, $cache['jpeg-image.jpg']);

        $file = (object) array('name' => 'gif-image.gif',
                'type' => 'file',
                'size' => filesize(FILEBROWSER_DATA_DIR.'gif-image.gif'),
                'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'gif-image.gif')),
                'imgsize' => array (94, 80),
                'thumbnail' => 'data:image/gif;base64');
        $this->assertEquals($file, $cache['gif-image.gif']);

        $file = (object) array('name' => 'png-image.png',
                'type' => 'file',
                'size' => filesize(FILEBROWSER_DATA_DIR.'png-image.png'),
                'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'png-image.png')),
                'imgsize' => array (94, 80),
                'thumbnail' => 'data:image/png;base64');
        $this->assertEquals($file, $cache['png-image.png']);

        $file = (object) array('name' => 'txt-file.txt',
                'type' => 'file',
                'size' => filesize(FILEBROWSER_DATA_DIR.'txt-file.txt'),
                'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'txt-file.txt')),
                'imgsize' => NULL,
                'thumbnail' => NULL);
        $this->assertEquals($file, $cache['txt-file.txt']);
    }

    public function testFilesInRootWithInvalidCache()
    {
        copy (dirname(__DIR__).'/test/phpunit.jpg', FILEBROWSER_DATA_DIR.'jpeg-image.jpg');
        file_put_contents(FILEBROWSER_DATA_DIR.'.htdircache', '{invalid json');

        $output = $this->_obj->getFiles('');
        // thumbnails are generated from resized image, ignore diffs
        for ($index = 0; $index < count($output['files']); $index++) {
            if (!is_null($output['files'][$index]['thumbnail'])) {
                $tmp = explode(',', $output['files'][$index]['thumbnail']);
                $output['files'][$index]['thumbnail'] = reset($tmp);
            }
        }
        $this->assertEquals('OK', $output['status']);
        $this->assertEquals (1, count($output['files']));

        //cache file
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        // thumbnails are generated from resized image, ignore diffs
        foreach ($cache as $key=>$value) {
            if (!is_null($value->thumbnail)) {
                $tmp = explode(',', $value->thumbnail);
                $cache[$key]->thumbnail = reset($tmp);
            }
        }

        $file = (object) array('name' => 'jpeg-image.jpg',
                'type' => 'file',
                'size' => filesize(FILEBROWSER_DATA_DIR.'jpeg-image.jpg'),
                'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'jpeg-image.jpg')),
                'imgsize' => array (94, 80),
                'thumbnail' => 'data:image/jpg;base64');
        $this->assertEquals($file, $cache['jpeg-image.jpg']);
    }

    public function testFilesInRootWithInvalidImage()
    {
        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'jpeg-image.jpg');

        $output = $this->_obj->getFiles('');
        // thumbnails are generated from resized image, ignore diffs
        for ($index = 0; $index < count($output['files']); $index++) {
            if (!is_null($output['files'][$index]['thumbnail'])) {
                $tmp = explode(',', $output['files'][$index]['thumbnail']);
                $output['files'][$index]['thumbnail'] = reset($tmp);
            }
        }
        $this->assertEquals('OK', $output['status']);
        $this->assertEquals (1, count($output['files']));

        //cache file
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        // thumbnails are generated from resized image, ignore diffs
        foreach ($cache as $key=>$value) {
            if (!is_null($value->thumbnail)) {
                $tmp = explode(',', $value->thumbnail);
                $cache[$key]->thumbnail = reset($tmp);
            }
        }

        $file = (object) array('name' => 'jpeg-image.jpg',
                'type' => 'file',
                'size' => filesize(FILEBROWSER_DATA_DIR.'jpeg-image.jpg'),
                'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'jpeg-image.jpg')),
                'imgsize' => NULL,
                'thumbnail' => '');
        $this->assertEquals($file, $cache['jpeg-image.jpg']);
    }

    public function testFilesInSubfolder()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a/aa');
        mkDirRecursive(FILEBROWSER_DATA_DIR.'b');
        copy (dirname(__DIR__).'/test/phpunit.jpg', FILEBROWSER_DATA_DIR.'a/aa/jpeg-image.jpg');
        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'b/txt-file.txt');

        // folder a
        $output = $this->_obj->getFiles('a');
        // thumbnails are generated from resized image, ignore diffs
        for ($index = 0; $index < count($output['files']); $index++) {
            if (!is_null($output['files'][$index]['thumbnail'])) {
                //$output['files'][$index]['thumbnail'] = reset(explode(',', $output['files'][$index]['thumbnail']));
                $output['files'][$index]['thumbnail'] = explode(',', $output['files'][$index]['thumbnail'])[0];
            }
        }
        $this->assertEquals('OK', $output['status']);
        $this->assertEquals (1, count($output['files']));
        $file = array('name' => 'aa',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'a/aa')),
            'imgsize' => NULL,
            'thumbnail' => NULL);
        $this->assertContains($file, $output['files']);

        // folder b
        $output = $this->_obj->getFiles('b');
        // thumbnails are generated from resized image, ignore diffs
        for ($index = 0; $index < count($output['files']); $index++) {
            if (!is_null($output['files'][$index]['thumbnail'])) {
                $output['files'][$index]['thumbnail'] = explode(',', $output['files'][$index]['thumbnail'])[0];
            }
        }
        $this->assertEquals('OK', $output['status']);
        $this->assertEquals (1, count($output['files']));
        $file = array('name' => 'txt-file.txt',
                'type' => 'file',
                'size' => filesize(FILEBROWSER_DATA_DIR.'b/txt-file.txt'),
                'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'b/txt-file.txt')),
                'imgsize' => NULL,
                'thumbnail' => NULL);
        $this->assertContains($file, $output['files']);


        // folder a/aa
        $output = $this->_obj->getFiles('a/aa');
        // thumbnails are generated from resized image, ignore diffs
        for ($index = 0; $index < count($output['files']); $index++) {
            if (!is_null($output['files'][$index]['thumbnail'])) {
                $output['files'][$index]['thumbnail'] = explode(',', $output['files'][$index]['thumbnail'])[0];
            }
        }
        $this->assertEquals('OK', $output['status']);
        $this->assertEquals (1, count($output['files']));
        $file = array('name' => 'jpeg-image.jpg',
                'type' => 'file',
                'size' => filesize(FILEBROWSER_DATA_DIR.'a/aa/jpeg-image.jpg'),
                'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'a/aa/jpeg-image.jpg')),
                'imgsize' => array (94, 80),
                'thumbnail' => 'data:image/jpg;base64');
    }

    public function testFilesWithInvalidFolder()
    {
        $expect = array(
            'status' => 'ERR',
            'err' => \GstBrowser\Connector::ERR_DIRECTORY_NOT_FOUND
        );
        $output = $this->_obj->getFiles('notexists');
        $this->assertEquals($expect, $output);
    }

    public function testDifferentThumbnailSize()
    {
        copy (dirname(__DIR__).'/test/phpunit.jpg', FILEBROWSER_DATA_DIR.'jpeg-image.jpg');

        $config = new \GstBrowser\Config(FILEBROWSER_DATA_DIR);
        $config->thumbMaxSize(60, 50);
        $obj = new \GstBrowser\Connector($config);

        $output = $obj->getFiles('');
        $this->assertEquals('OK', $output['status']);
        $this->assertEquals (1, count($output['files']));
        $thumbDataUri = $output['files'][0]['thumbnail'];
        $tmp = explode(',', $thumbDataUri);
        $image = imagecreatefromstring(base64_decode($tmp[1]));

        $this->assertEquals(60, imagesx($image));
        $this->assertLessThanOrEqual(50, imagesy($image));

        //cache file
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals($thumbDataUri, $cache["jpeg-image.jpg"]->thumbnail);
    }

    public function testDifferentThumbnailSizePortrait()
    {
        copy (dirname(__DIR__).'/test/phpunit-portrait.jpg', FILEBROWSER_DATA_DIR.'jpeg-image.jpg');

        $config = new \GstBrowser\Config(FILEBROWSER_DATA_DIR);
        $config->thumbMaxSize(50, 50);
        $obj = new \GstBrowser\Connector($config);

        $output = $obj->getFiles('');
        $this->assertEquals('OK', $output['status']);
        $this->assertEquals (1, count($output['files']));
        $thumbDataUri = $output['files'][0]['thumbnail'];
        $tmp = explode(',', $thumbDataUri);
        $image = imagecreatefromstring(base64_decode($tmp[1]));

        $this->assertLessThanOrEqual(50, imagesx($image));
        $this->assertEquals(50, imagesy($image));

        //cache file
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals($thumbDataUri, $cache["jpeg-image.jpg"]->thumbnail);
    }

}