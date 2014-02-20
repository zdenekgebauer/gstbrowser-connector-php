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
class IndexUploadTest extends \PHPUnit_Framework_TestCase
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

    public function testUpload()
    {
        $boundary = '--'.microtime(TRUE);
        $filename = dirname(__DIR__).'/test/phpunit.txt';

        $content = '--'.$boundary."\r\n".
            'Content-Disposition: form-data; name="file"; filename="'.basename($filename).'"'."\r\n".
            'Content-Type: text/plain'."\r\n\r\n".
            file_get_contents($filename)."\r\n";
        // add POST fields
        $content .= '--'.$boundary."\r\n".
            'Content-Disposition: form-data; name="config"'."\r\n\r\n".
            'test'."\r\n";
        $content .= '--'.$boundary."\r\n".
            'Content-Disposition: form-data; name="action"'."\r\n\r\n".
            'upload'."\r\n";

        // signal end of request (note the trailing "--")
        $content .= '--'.$boundary."--\r\n";

        $options = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-Type: multipart/form-data; boundary='.$boundary,
            'content' => $content
        ));
        $context = stream_context_create($options);
        $output = json_decode(file_get_contents(FILEBROWSER_URL_CONNECTOR, FALSE, $context));
        clearstatcache();
        $this->assertFileExists(FILEBROWSER_DATA_DIR.'phpunit.txt');
        $this->assertEquals('OK', $output->status);
    }

}