<?php
/**
 * configuration file for phpunit tests
 *
 * @package GstBrowser
 * @author  Zdenek Gebauer <zdenek.gebauer@gmail.com>
 */

define('FILEBROWSER_URL_CONNECTOR', 'http://localhost/filebrowser-php/index.php');

define('FILEBROWSER_DATA_DIR', __DIR__.'/phpunitdata/');


/**
 * recursively create directory (directories) specified by path
 * @param string $path
 * @param int $mode unix rights
 * @return bool FALSE on error
 */
function mkDirRecursive($path, $mode = 0777)
{
    $current = '';
    foreach (explode('/', $path) as $item) {
        $current .= $item.'/';
        if (@is_dir($current)) {
            continue;
        } else {
            if (!@is_readable(dirname($current))) {
                continue; // parent directory outside open_basedir
            }
        }
        umask(000);
        if (!mkdir($current, $mode)) {
            return FALSE;
        }
    }
    return true;
}

/**
 * recursively delete directory with content
 * @param string $dir $directory
 * @return bool FALSE on error
 */
function rmDirRecursive($dir)
{
    $dir = rtrim($dir, '/');
    if (is_dir($dir)) {
        $dirHandler = opendir($dir);
        if ($dirHandler !== FALSE) {
            while (($item = readdir($dirHandler)) !== FALSE) {
                if ($item == '.' || $item == '..') {
                    continue;
                }
                $fullpath = $dir.'/'.$item;
                if (is_file($fullpath)) {
                    unlink($fullpath);
                }
                if (is_dir($fullpath)) {
                    rmDirRecursive($fullpath);
                }
            }
            closedir($dirHandler);
        }
        rmdir($dir);
    }
    return TRUE;
}