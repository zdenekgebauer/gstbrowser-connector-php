<?php
/**
 * @package    GstLib
 * @subpackage FileBrowserTest
 */

require_once 'bootstrap.php';
require_once dirname(__DIR__).'/connector.php';

/**
 *
 * @author     Zdenek Gebauer <zdenek.gebauer@gmail.com>
 * @package    GstLib
 * @subpackage FileBrowserTest
 */
class IndexRenameTest extends \PHPUnit_Framework_TestCase
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
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        rmDirRecursive(FILEBROWSER_DATA_DIR);
    }

    public function testRenameDir()
    {
        // create new folder via url
        $data = array('config' => 'test', 'action' => 'mkdir', 'dir' => 'folder');
        $options = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ));
        $context = stream_context_create($options);
        $output = json_decode(file_get_contents(FILEBROWSER_URL_CONNECTOR, FALSE, $context));
        $expectFolder = FILEBROWSER_DATA_DIR.'folder';
        $this->assertTrue(is_dir($expectFolder));
        $this->assertEquals('OK', $output->status);

        // rename
        $data = array('config' => 'test', 'action' => 'rename', 'old' => 'folder', 'new' => 'newfolder');
        $options = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ));
        $context = stream_context_create($options);
        $output = json_decode(file_get_contents(FILEBROWSER_URL_CONNECTOR, FALSE, $context));
        clearstatcache();
        $this->assertFalse(is_dir(FILEBROWSER_DATA_DIR.'folder'));
        $this->assertTrue(is_dir(FILEBROWSER_DATA_DIR.'newfolder'));
        $this->assertEquals('OK', $output->status);
        $tree = array(
            (object) array('name' => 'phpunitdata',
                'children' => array(
                    (object) array('name' => 'newfolder')
                )
            )
        );
        $this->assertEquals($tree, $output->tree);
        $files = array((object) array(
            "name" => "newfolder",
            "type" => "dir",
            "size" => NULL,
            "date" => date('c', filemtime(FILEBROWSER_DATA_DIR.'newfolder')),
            "imgsize" =>  NULL,
            "thumbnail" => NULL));
        $this->assertEquals($files, $output->files);

        // delete folder here via url, because phpunit cannot delete folder on windows

        $data = array('config' => 'test', 'action' => 'delete', 'name' => 'newfolder');
        $options = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ));
        $context = stream_context_create($options);
        $output = json_decode(file_get_contents(FILEBROWSER_URL_CONNECTOR, FALSE, $context));
        clearstatcache();
        $this->assertEquals('OK', $output->status);
        $this->assertFalse(is_dir(FILEBROWSER_DATA_DIR.'newfolder'));

        $tree = array(
            (object) array('name' => 'phpunitdata')
        );
        $this->assertEquals($tree, $output->tree);
        $this->assertEquals(array(), $output->files);
    }

}