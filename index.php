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

if (file_exists('config.php')) {
    include 'config.php';
} else {
    header('HTTP/1.0 500 Internal Server Error');
    die('config.php missing');
}


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

// check access by session variable
if (!empty($gstbrowserConf[$config]['sess_variable']) && !empty($gstbrowserConf[$config]['sess_value'])) {
    if (!isset($_SESSION)) {
        session_start();
    }
    if (!isset($gstbrowserConf[$config]['sess_variable'])
            || $_SESSION[$gstbrowserConf[$config]['sess_variable']] !== $gstbrowserConf[$config]['sess_value']) {
        header('HTTP/1.0 403 Forbidden');
        die('You are not allowed');
    }
}

// check access by IP
if (!empty($gstbrowserConf[$config]['allow_ip'])
        && !in_array($_SERVER['REMOTE_ADDR'], $gstbrowserConf[$config]['allow_ip'])) {
    header('HTTP/1.0 403 Forbidden');
    die('Your IP addres is not allowed');
}

require_once 'connector.php';

$connectorConfig = new Config($gstbrowserConf['default']['root_dir']);

if (isset($gstbrowserConf[$config]['root_dir'])) {
    $connectorConfig->baseDir($gstbrowserConf[$config]['root_dir']);
}
if (isset($gstbrowserConf[$config]['mode_dir'])) {
    $connectorConfig->modeDir($gstbrowserConf[$config]['mode_dir']);
}
if (isset($gstbrowserConf[$config]['mode_file'])) {
    $connectorConfig->modeFile($gstbrowserConf[$config]['mode_file']);
}
if (isset($gstbrowserConf[$config]['thumb_width'])) {
    $connectorConfig->thumbWidth($gstbrowserConf[$config]['thumb_width']);
}
if (isset($gstbrowserConf[$config]['thumb_height'])) {
    $connectorConfig->thumbWidth($gstbrowserConf[$config]['thumb_height']);
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