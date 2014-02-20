<?php
/**
 * @package GstBrowser
 * @author  Zdenek Gebauer <zdenek.gebauer@gmail.com>
 */

require_once 'BrowserTest.php';
require_once 'MkDirTest.php';
require_once 'DeleteTest.php';
require_once 'RenameTest.php';
require_once 'UploadTest.php';
require_once 'CopyTest.php';
require_once 'MoveTest.php';
require_once 'IndexBrowserTest.php';
require_once 'IndexMkDirTest.php';
require_once 'IndexRenameTest.php';
require_once 'IndexUploadTest.php';
require_once 'IndexCopyTest.php';
require_once 'IndexMoveTest.php';

/**
 * run all tests
 */
class FileBrowserTest_AllTests extends \PHPUnit_Framework_TestSuite
{
    /**
     * run all tests
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite()
    {
        $suite = new \PHPUnit_Framework_TestSuite('GstBrowser');
        $suite->addTestSuite('BrowserTest');
        $suite->addTestSuite('MkDirTest');
        $suite->addTestSuite('DeleteTest');
        $suite->addTestSuite('RenameTest');
        $suite->addTestSuite('UploadTest');
        $suite->addTestSuite('CopyTest');
        $suite->addTestSuite('MoveTest');
        $suite->addTestSuite('IndexBrowserTest');
        $suite->addTestSuite('IndexMkDirTest');
        $suite->addTestSuite('IndexRenameTest');
        $suite->addTestSuite('IndexUploadTest');
        $suite->addTestSuite('IndexCopyTest');
        $suite->addTestSuite('IndexMoveTest');
        return $suite;
    }
}