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
class IndexMoveTest extends \PHPUnit_Framework_TestCase
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

    public function testMove()
    {
        $expectFolder = FILEBROWSER_DATA_DIR.'folder';
        mkDirRecursive($expectFolder);

        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'txt-file.txt');

        $data = array('config' => 'test', 'action' => 'move', 'old' => 'txt-file.txt', 'new' => 'folder');
        $options = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ));
        $context = stream_context_create($options);
        $output = json_decode(file_get_contents(FILEBROWSER_URL_CONNECTOR, FALSE, $context));
        clearstatcache();
        $this->assertFileNotExists(FILEBROWSER_DATA_DIR.'txt-file.txt');
        $this->assertFileExists($expectFolder.'/txt-file.txt');
        $this->assertEquals('OK', $output->status);
        $files = array((object) array('name' => 'folder',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime($expectFolder)),
            'imgsize' => NULL,
            'thumbnail' => NULL));
        $this->assertEquals($files, $output->files);
    }

    public function testMoveNewName()
    {
        $expectFolder = FILEBROWSER_DATA_DIR.'folder';
        mkDirRecursive($expectFolder);

        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'txt-file.txt');

        $data = array('config' => 'test', 'action' => 'move', 'old' => 'txt-file.txt', 'new' => 'folder/new.txt');
        $options = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ));
        $context = stream_context_create($options);
        $output = json_decode(file_get_contents(FILEBROWSER_URL_CONNECTOR, FALSE, $context));
        clearstatcache();
        $this->assertFileNotExists(FILEBROWSER_DATA_DIR.'txt-file.txt');
        $this->assertFileNotExists($expectFolder.'/txt-file.txt');
        $this->assertFileExists($expectFolder.'/new.txt');
        $this->assertEquals('OK', $output->status);
        $this->assertEquals('OK', $output->status);
        $files = array((object) array('name' => 'folder',
            'type' => 'dir',
            'size' => NULL,
            'date' => date('c', filemtime($expectFolder)),
            'imgsize' => NULL,
            'thumbnail' => NULL));
        $this->assertEquals($files, $output->files);

    }

}