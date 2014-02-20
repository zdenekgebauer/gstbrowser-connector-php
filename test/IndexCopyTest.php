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
class IndexCopyTest extends \PHPUnit_Framework_TestCase
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

    public function testCopy()
    {
        $expectFolder = FILEBROWSER_DATA_DIR.'folder';
        mkDirRecursive($expectFolder);

        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'txt-file.txt');

        $data = array('config' => 'test', 'action' => 'copy', 'old' => 'txt-file.txt', 'new' => 'folder');
        $options = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ));
        $context = stream_context_create($options);
        $output = json_decode(file_get_contents(FILEBROWSER_URL_CONNECTOR, FALSE, $context));
        clearstatcache();
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'txt-file.txt');
        $this->assertFileExists($expectFolder.'/txt-file.txt');
        $this->assertEquals('OK', $output->status);
    }

    public function testCopyNewName()
    {
        $expectFolder = FILEBROWSER_DATA_DIR.'folder';
        mkDirRecursive($expectFolder);

        copy (dirname(__DIR__).'/test/phpunit.txt', FILEBROWSER_DATA_DIR.'txt-file.txt');

        $data = array('config' => 'test', 'action' => 'copy', 'old' => 'txt-file.txt', 'new' => 'folder/new.txt');
        $options = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ));
        $context = stream_context_create($options);
        $output = json_decode(file_get_contents(FILEBROWSER_URL_CONNECTOR, FALSE, $context));
        clearstatcache();
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'txt-file.txt');
        $this->assertFileNotExists($expectFolder.'/txt-file.txt');
        $this->assertFileExists($expectFolder.'/new.txt');
        $this->assertEquals('OK', $output->status);
    }

}