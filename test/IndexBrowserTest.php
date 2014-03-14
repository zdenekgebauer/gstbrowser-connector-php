<?php
/**
 * @package    GstLib
 * @subpackage FileBrowserTest
 */

require_once 'bootstrap.php';
require_once dirname(__DIR__).'/connector.php';

/**
 * @package    GstLib
 * @subpackage FileBrowserTest
 */
class IndexBrowserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        rmDirRecursive(FILEBROWSER_DATA_DIR);
        mkDirRecursive(FILEBROWSER_DATA_DIR);
        copy(dirname(__FILE__).'/config.php', dirname(__DIR__).'/config.php');
        date_default_timezone_set('UTC');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        rmDirRecursive(FILEBROWSER_DATA_DIR);
        unlink(dirname(__DIR__).'/config.php');
    }

    public function testWithoutParams()
    {
        $expect = (object) array(
            'status' => 'ERR',
            'err' => \GstBrowser\Connector::ERR_MISSING_ACTION
        );

        $output = json_decode(file_get_contents(FILEBROWSER_URL_CONNECTOR));
        $this->assertEquals($expect, $output);

        $headers = get_headers(FILEBROWSER_URL_CONNECTOR, 1);
        $this->assertEquals($headers['Content-Type'], 'application/json; charset=utf-8');
    }

    public function testTree()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a/aa');
        mkDirRecursive(FILEBROWSER_DATA_DIR.'b');
        $data = array(
            (object) array('name' => basename(FILEBROWSER_DATA_DIR),
                'children' => array(
                    (object) array('name' => 'a',
                        'children' => array(
                            (object) array('name' => 'aa')
                        )
                    ),
                    (object) array('name' => 'b')
                )
            )
        );

        $expect = (object) array(
            'status'=>'OK',
            'tree'=>$data
        );

        $output = json_decode(file_get_contents(FILEBROWSER_URL_CONNECTOR.'?config=test&action=tree'));
        $this->assertEquals($expect, $output);
    }

    public function testFilesInRoot()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a');
        mkDirRecursive(FILEBROWSER_DATA_DIR.'b');
        copy (dirname(__DIR__).'/test/phpunit.jpg', FILEBROWSER_DATA_DIR.'jpeg-image.jpg');
        copy (dirname(__DIR__).'/test/phpunit.gif', FILEBROWSER_DATA_DIR.'gif-image.gif');

        $url = FILEBROWSER_URL_CONNECTOR.'?config=test&action=files';
        $output = json_decode(file_get_contents($url));
        $this->assertEquals('OK', $output->status);
        $this->assertEquals(4, count($output->files));
    }

    public function testFilesInSubfolder()
    {
        mkDirRecursive(FILEBROWSER_DATA_DIR.'a');
        mkDirRecursive(FILEBROWSER_DATA_DIR.'b');
        copy (dirname(__DIR__).'/test/phpunit.jpg', FILEBROWSER_DATA_DIR.'a/jpeg-image.jpg');

        $url = FILEBROWSER_URL_CONNECTOR.'?config=test&action=files';
        $output = json_decode(file_get_contents($url));
        $files = $output->files;
        $this->assertEquals('OK', $output->status);
        $this->assertEquals(2, count($output->files));

        $url = FILEBROWSER_URL_CONNECTOR.'?config=test&action=files&path=a';
        $output = json_decode(file_get_contents($url));
        $files = $output->files;
        $this->assertEquals('OK', $output->status);
        $this->assertEquals(1, count($output->files));

        $url = FILEBROWSER_URL_CONNECTOR.'?config=test&action=files&path=b';
        $output = json_decode(file_get_contents($url));
        $files = $output->files;
        $this->assertEquals('OK', $output->status);
        $this->assertEquals(0, count($output->files));
   }

    public function testFilesInvalidPath()
    {
        $url = FILEBROWSER_URL_CONNECTOR.'?config=test&action=files&path=notexists';
        $output = json_decode(file_get_contents($url));
        $expect = (object) array(
            'status'=>'ERR',
            'err'=>  \GstBrowser\Connector::ERR_DIRECTORY_NOT_FOUND
        );
        $this->assertEquals($expect, $output);
    }

}