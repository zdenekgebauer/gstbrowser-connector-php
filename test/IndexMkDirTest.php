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
class IndexMkDirTest extends \PHPUnit_Framework_TestCase
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

    public function testMkDir()
    {
        $data = array('config' => 'test', 'action' => 'mkdir', 'dir' => 'newfolder');
        $options = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ));
        $context = stream_context_create($options);
        $output = json_decode(file_get_contents(FILEBROWSER_URL_CONNECTOR, FALSE, $context));

        $expectFolder = FILEBROWSER_DATA_DIR.'newfolder';
        $this->assertTrue(is_dir($expectFolder));

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


         // delete folder here vua url - because phpunit cannot delete folder on windows

        $data = array('config' => 'test', 'action' => 'delete', 'name' => 'newfolder');
        $options = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ));
        $context = stream_context_create($options);
        $output = json_decode(file_get_contents(FILEBROWSER_URL_CONNECTOR, FALSE, $context));
        clearstatcache();
        $this->assertFalse(is_dir($expectFolder));

        $tree = array(
            (object) array('name' => 'phpunitdata')
        );
        $this->assertEquals($tree, $output->tree);
        $this->assertEquals(array(), $output->files);
    }

}