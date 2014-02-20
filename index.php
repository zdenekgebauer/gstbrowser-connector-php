<?php
/**
 * @package GstBrowser
 */

namespace GstBrowser;

/**
 * entry point of server part of connector
 *
 * always expects:
 * $_GET['config'] or $_POST['config'] - predfined configuration. If empty or missing, use 'default'
 * $_GET['action'] or $_POST['action'] - requested action. Some actions requires additional parameters - see bellow
 * $_GET['path'] or $_POST['path'] - current directory path relative to base dir, without starting and leading slash
 *
 * returns JSON with properties:
 * status - info about proccesing request [OK|ERR]
 * err - optional error number if processing request failed. See GstBrowserConnector::ERR_* constants
 * tree - tree of folders. Returned if action change folders
 * files - list of folders and files in given path. Returned if action change files in current path
 *
 * action "tree"
 * - returns property "tree" with all directories as array of objects. Each object represents one folder
 *   and contains array "children" with nested folders and files
 *
 * action "files"
 * - returns property "files" with folders and files in current path
 *
 * action "mkdir" create new folder
 * - require $_POST['dir'] with name of new folder
 * - returns property "tree" with all directories
 *
 * action "delete" delete file or folder in current path
 * - require $_POST['name'] with name of file or folder
 * - if deleted item is file, returns property "files"
 * - if deleted item is folder, returns property "tree"
 *
 * action "rename" rename file or folder in current path
 * - require $_POST['old'] with name of file or folder
 * - require $_POST['new'] with new name of file or folder
 * - if renamed item is file, returns property "files"
 * - if renamed item is folder, returns property "tree"
 *
 * action "upload" upload files
 * - require standard $_FILES array
 * - returns property "files"
 *
 * action "copy" copy file in current path to another folder
 * - require $_POST['old'] with name of file
 * - require $_POST['new'] with target folder, or target folder/name

 * action "move" moves file in current path to another folder
 * - require $_POST['old'] with name of file
 * - require $_POST['new'] with target folder, or target folder/name
 * - returns property "files"
 */

// uncomment for debuging
//error_reporting(E_ALL);
//ini_set('display_errors', 'on');

// example of check access by session - uncomment and modify if need
//if (!isset($_SESSION)) {
//    session_start();
//}
//if (!isset($_SESSION['somevariable']) || $_SESSION['somevariable'] !== 'somevalue') {
//    header('HTTP/1.0 403 Forbidden');
//    die('You are not allowed');
//}

// example of check access by IP address - uncomment and modify if need
//if (!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1'))) {
//    header('HTTP/1.0 403 Forbidden');
//    die('Your IP addres is not allowed');
//}

require_once 'connector.php';

$config = (isset($_GET['config']) ? trim(strip_tags($_GET['config'])) : NULL);
if (is_null($config)) {
    $config = isset($_POST['config']) && $_POST['config'] !== '' ? trim(strip_tags($_POST['config'])) : 'default';
}
/** @var string $action requested action: upload, delete, ... */
$action = isset($_GET['action']) ? $_GET['action'] : NULL;
if (is_null($action)) {
    $action = isset($_POST['action']) ? $_POST['action'] : NULL;
}
/** @var string $path current directory path relative to base dir, without starting and leading slash */
$path = isset($_GET['path']) ?  trim($_GET['path']) : '';
if ($path === '') {
    $path = isset($_POST['path']) ? trim($_POST['path']) : '';
}
$path = trim($path, '/');

switch ($config) {
    case 'test':
        require 'test/bootstrap.php';
        $connectorConfig = new \GstBrowser\Config(FILEBROWSER_DATA_DIR);
        $connectorConfig->setMode(0777, 0666);
        break;
    case 'demo':
        $connectorConfig = new \GstBrowser\Config('d:/temp/');
        break;
    default:
        $connectorConfig = new \GstBrowser\Config(__DIR__);
        $connectorConfig->setMode(0755, 0644);
        $connectorConfig->overwrite(TRUE);
        $connectorConfig->thumbMaxSize(90, 90);
}

$con = new \GstBrowser\Connector($connectorConfig);

switch ($action) {
case 'tree':
    $ret = $con->getFoldersTree();
    break;
case 'files':
    $ret = $con->getFiles($path);
    break;
case 'mkdir':
    $name = (isset($_POST['dir']) ? trim($_POST['dir']) : '');
    $ret = $con->mkDir($path, $name);
    break;
case 'upload':
    $ret = $con->upload($path, $_FILES);
    break;
case 'rename':
    $old = (isset($_POST['old']) ? trim($_POST['old']) : '');
    $new = (isset($_POST['new']) ? trim($_POST['new']) : '');
    $ret = $con->rename($path, $old, $new);
    break;
case 'delete':
    $name = (isset($_POST['name']) ? trim($_POST['name']) : '');
    $ret = $con->delete($path, $name);
    break;
case 'copy':
    $old = (isset($_POST['old']) ? trim($_POST['old']) : '');
    $new = (isset($_POST['new']) ? trim($_POST['new']) : '');
    $ret = $con->copy($path, $old, $new);
    break;
case 'move':
    $old = (isset($_POST['old']) ? trim($_POST['old']) : '');
    $new = (isset($_POST['new']) ? trim($_POST['new']) : '');
    $ret = $con->move($path, $old, $new);
    break;
default:
    $ret = array(
        'status' => 'ERR',
        'err' => \GstBrowser\Connector::ERR_MISSING_ACTION
    );
}

header('Content-type: application/json; charset=utf-8');
die(json_encode($ret));