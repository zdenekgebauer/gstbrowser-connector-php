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
class UploadTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        rmDirRecursive(FILEBROWSER_DATA_DIR);
        mkDirRecursive(FILEBROWSER_DATA_DIR, 0777);
        date_default_timezone_set('UTC');
        $config = new \GstBrowser\Config(FILEBROWSER_DATA_DIR);
        $config->setMode(0777, 0666);
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

    public function testUploadFile()
    {
        $testfile = sys_get_temp_dir().'/phpunit.jpg';
        copy (dirname(__DIR__).'/test/phpunit.jpg', $testfile);
        $uploadfiles[] = array(
            'name' => basename($testfile),
            'type' => 'image/jpeg',
            'tmp_name' => $testfile,
            'error' => 0,
            'size' => filesize($testfile)
        );
        $testfile = sys_get_temp_dir().'/phpunit2.jpg';
        copy (dirname(__DIR__).'/test/phpunit.jpg', $testfile);
        $uploadfiles[] = array(
            'name' => basename($testfile),
            'type' => 'image/jpeg',
            'tmp_name' => $testfile,
            'error' => 0,
            'size' => filesize($testfile)
        );

        $output = $this->_obj->upload('', $uploadfiles);
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'phpunit.jpg');
        chmod(FILEBROWSER_DATA_DIR.'phpunit.jpg', 0777); // remove read-only attribute on windows
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'phpunit2.jpg');
        chmod(FILEBROWSER_DATA_DIR.'phpunit2.jpg', 0777);

        // thumbnails are generated from resized image, ignore diffs
        for ($index = 0; $index < count($output['files']); $index++) {
            if (!is_null($output['files'][$index]['thumbnail'])) {
                $output['files'][$index]['thumbnail'] = explode(',', $output['files'][$index]['thumbnail'])[0];
            }
        }
        $files = array(
            array('name' => 'phpunit.jpg',
                'type' => 'file',
                'size' => filesize(FILEBROWSER_DATA_DIR.'phpunit.jpg'),
                'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'phpunit.jpg')),
                'imgsize' => array(94, 80),
                'thumbnail' => 'data:image/jpg;base64'),
            array('name' => 'phpunit2.jpg',
                'type' => 'file',
                'size' => filesize(FILEBROWSER_DATA_DIR.'phpunit2.jpg'),
                'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'phpunit2.jpg')),
                'imgsize' => array(94, 80),
                'thumbnail' => 'data:image/jpg;base64')
            );
        $expect = array(
            'status'=>'OK',
            'files'=>$files
        );
        $this->assertEquals($expect, $output);

        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(2, count($cache));
    }

    public function testUploadSecondFile()
    {
        copy (dirname(__DIR__).'/test/phpunit.jpg', FILEBROWSER_DATA_DIR.'phpunit.jpg');
        // get files to create cache file
        $output = $this->_obj->getFiles('');
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(1, count($cache));

        $testfile = sys_get_temp_dir().'/phpunit2.jpg';
        copy (dirname(__DIR__).'/test/phpunit.jpg', $testfile);
        $uploadfiles[] = array(
            'name' => basename($testfile),
            'type' => 'image/jpeg',
            'tmp_name' => $testfile,
            'error' => 0,
            'size' => filesize($testfile)
        );

        $output = $this->_obj->upload('', $uploadfiles);
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'phpunit.jpg');
        chmod(FILEBROWSER_DATA_DIR.'phpunit.jpg', 0777); // remove read-only attribute on windows
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'phpunit2.jpg');
        chmod(FILEBROWSER_DATA_DIR.'phpunit2.jpg', 0777);

        // thumbnails are generated from resized image, ignore diffs
        for ($index = 0; $index < count($output['files']); $index++) {
            if (!is_null($output['files'][$index]['thumbnail'])) {
                $output['files'][$index]['thumbnail'] = explode(',', $output['files'][$index]['thumbnail'])[0];
            }
        }

        $files = array(
            array('name' => 'phpunit.jpg',
                'type' => 'file',
                'size' => filesize(FILEBROWSER_DATA_DIR.'phpunit.jpg'),
                'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'phpunit.jpg')),
                'imgsize' => array(94, 80),
                'thumbnail' => 'data:image/jpg;base64'),
            array('name' => 'phpunit2.jpg',
                'type' => 'file',
                'size' => filesize(FILEBROWSER_DATA_DIR.'phpunit2.jpg'),
                'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'phpunit2.jpg')),
                'imgsize' => array(94, 80),
                'thumbnail' => 'data:image/jpg;base64')
            );
        $expect = array(
            'status'=>'OK',
            'files'=>$files
        );
        $this->assertEquals($expect, $output);

        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = (array) json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(2, count($cache));
    }

    public function testUploadOverwrite()
    {
        $testfile = sys_get_temp_dir().'/phpunit.jpg';
        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'phpunit.jpg'); // use different file than jpg
        copy (dirname(__DIR__).'/test/phpunit.jpg', $testfile);
        $uploadfiles[] = array(
            'name' => basename($testfile),
            'type' => 'image/jpeg',
            'tmp_name' => $testfile,
            'error' => 0,
            'size' => filesize($testfile)
        );

        //$this->_obj->setOverwrite(true);
        $output = $this->_obj->upload('', $uploadfiles);
        chmod(FILEBROWSER_DATA_DIR.'phpunit.jpg', 0777); // remove read-only attribute on windows
        // thumbnails are generated from resized image, ignore diffs
        for ($index = 0; $index < count($output['files']); $index++) {
            if (!is_null($output['files'][$index]['thumbnail'])) {
                $output['files'][$index]['thumbnail'] = explode(',', $output['files'][$index]['thumbnail'])[0];
            }
        }
        $files = array(array(
            'name' => 'phpunit.jpg',
            'type' => 'file',
            'size' => filesize(FILEBROWSER_DATA_DIR.'phpunit.jpg'),
            'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'phpunit.jpg')),
            'imgsize' => array(94, 80),
            'thumbnail' => 'data:image/jpg;base64'));
        $expect = array(
            'status'=>'OK',
            'files'=>$files
        );
        $this->assertEquals($expect, $output);
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'phpunit.jpg');
        chmod(FILEBROWSER_DATA_DIR.'phpunit.jpg', 0777); // remove read-only attribute on windows
        $this->assertNotContains(file_get_contents(dirname(__DIR__).'/test/phpunit.txt'), file_get_contents(FILEBROWSER_DATA_DIR.'phpunit.jpg'));

        // thumbnails are generated from resized image, ignore diffs
        for ($index = 0; $index < count($output['files']); $index++) {
            if (!is_null($output['files'][$index]['thumbnail'])) {
                $output['files'][$index]['thumbnail'] = explode(',', $output['files'][$index]['thumbnail'])[0];
            }
        }

        $files = array(
            array('name' => 'phpunit.jpg',
                'type' => 'file',
                'size' => filesize(dirname(__DIR__).'/test/phpunit.jpg'),
                'date' => date('c', filemtime(FILEBROWSER_DATA_DIR.'phpunit.jpg')),
                'imgsize' => array(94, 80),
                'thumbnail' => 'data:image/jpg;base64'));
        $expect = array(
            'status'=>'OK',
            'files'=>$files
        );
        $this->assertEquals($expect, $output);

        $this->assertFileExists(FILEBROWSER_DATA_DIR.'.htdircache');
        $cache = json_decode(file_get_contents(FILEBROWSER_DATA_DIR.'.htdircache'));
        $this->assertEquals(1, count($cache));
    }

    public function testUploadDoNotOverwrite()
    {
        $testfile = sys_get_temp_dir().'/phpunit.txt';
        //copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'phpunit.jpg'); // use different file than jpg
        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'phpunit.txt');
        copy (dirname(__DIR__).'/test/phpunit.txt', $testfile);
        $uploadfiles[] = array(
            'name' => basename($testfile),
            'type' => 'text/plain',
            'tmp_name' => $testfile,
            'error' => 0,
            'size' => filesize($testfile)
        );

        $config = new \GstBrowser\Config(FILEBROWSER_DATA_DIR);
        $config->overwrite(FALSE);
        $obj = new \GstBrowser\Connector($config);

        $output = $obj->upload('', $uploadfiles);
        $expect = array(
            'status'=>'ERR',
            'err'=>  \GstBrowser\Connector::ERR_UPLOAD_FILE_EXISTS
        );
        $this->assertEquals($expect, $output);
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'phpunit.txt');
        chmod(FILEBROWSER_DATA_DIR.'phpunit.txt', 0777); // remove read-only attribute on windows
        //$this->assertContains(file_get_contents(dirname(__DIR__).'/test/phpunit.txt'), file_get_contents(FILEBROWSER_DATA_DIR.'phpunit.jpg'));
    }

    public function testUploadCannotOverwrite()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $this->markTestSkipped('test only on windows');
        }

        copy (dirname(__DIR__).'/test/phpunit.jpg', FILEBROWSER_DATA_DIR.'phpunit.jpg');
        chmod(FILEBROWSER_DATA_DIR.'phpunit.jpg', 0400);
        $testfile = sys_get_temp_dir().'/phpunit.jpg';
        copy (dirname(__DIR__).'/test/phpunit.jpg', $testfile);
        $uploadfiles[] = array(
            'name' => basename($testfile),
            'type' => 'image/jpeg',
            'tmp_name' => $testfile,
            'error' => 0,
            'size' => filesize($testfile)
        );

//        $this->_obj->setOverwrite(true);

        $output = $this->_obj->upload('', $uploadfiles);
        $expect = array(
            'status'=>'ERR',
            'err'=>  \GstBrowser\Connector::ERR_UPLOAD
        );
        $this->assertEquals($expect, $output);
        chmod(FILEBROWSER_DATA_DIR.'phpunit.jpg', 0700);
    }

    public function testUploadError()
    {
        $testfile = sys_get_temp_dir().'/phpunit.jpg';
        copy (dirname(__DIR__).'/test/phpunit.jpg', $testfile);
        $uploadfiles[] = array(
            'name' => basename($testfile),
            'type' => 'image/jpeg',
            'tmp_name' => $testfile,
            'error' => 1,
            'size' => 0
        );

        $output = $this->_obj->upload('', $uploadfiles);
        $expect = array(
            'status'=>'ERR',
            'err'=>\GstBrowser\Connector::ERR_UPLOAD_FILESIZE
        );
        $this->assertEquals($expect, $output);
    }

    public function testUploadToInvalidDirectory()
    {
        $testfile = sys_get_temp_dir().'/phpunit.jpg';
        copy (dirname(__DIR__).'/test/phpunit.jpg', $testfile);
        $uploadfiles[] = array(
            'name' => basename($testfile),
            'type' => 'image/jpeg',
            'tmp_name' => $testfile,
            'error' => 0,
            'size' => 0
        );

        $output = $this->_obj->upload('notexists', $uploadfiles);
        $expect = array(
            'status'=>'ERR',
            'err'=>\GstBrowser\Connector::ERR_DIRECTORY_NOT_FOUND
        );
        $this->assertEquals($expect, $output);
    }

}