<?php
/**
 * @package GstBrowser
 * @author  Zdenek Gebauer <zdenek.gebauer@gmail.com>
 */

namespace GstBrowser;

/**
 * holds configuration of connector
 * @package GstBrowser
 */
class Config
{
    /** @var string base directory */
    private $_baseDir;
    /** @var octal mode for new directories */
    private $_modeDir;
    /** @var octal mode for new files*/
    private $_modeFile;
    /** @var bool allow overwite existing files  */
    private $_overwrite;
    /** @var int max width of thumbnail */
    private $_thumbWidth = 90;
    /** @var int max height of thumbnail */
    private $_thumbHeight = 90;

    /**
     * constructor
     * @param string $baseDir path to root directory with user files
     */
    public function __construct($baseDir)
    {
        $this->_baseDir = trim($baseDir);
        $this->_modeDir = 0755;
        $this->_modeFile = 0644;
        $this->_overwrite = TRUE;
        $this->_thumbMaxWidth = 90;
        $this->_thumbMaxHeight = 90;
    }

    /**
     * returns path to root directory with user files
     * @return string
     */
    public function baseDir()
    {
        return $this->_baseDir;
    }

    /**
     * sets mode for new directories and files
     * @param octal $modeDir
     * @param octal $modeFile
     */
    public function setMode($modeDir, $modeFile)
    {
        $this->_modeDir = $modeDir;
        $this->_modeFile = $modeFile;
    }

    /**
     * allow/forbid ovewrite existing file by uploaded file with the same name
     * @param bool $mode
     * @return bool current overwrite mode
     */
    public function overwrite($mode = NULL)
    {
        if (!is_null($mode)) {
            $this->_overwrite = (bool) $mode;
        }
        return $this->_overwrite;
    }

    /**
     * sets max allowed size of thumbnail from 20x20 to 400x400, default 90x90
     * @param int $width
     * @param int $height
     */
    public function thumbMaxSize($width, $height)
    {
        $width = (int) $width;
        if ($width >= 20 && $width <= 400) {
            $this->_thumbWidth = $width;
        }

        $height = (int) $height;
        if ($height >= 20 && $height <= 400) {
            $this->_thumbHeight = $height;
        }
    }

    /**
     * return allowed thumbnail width
     * @return int
     */
    public function thumbWidth()
    {
        return $this->_thumbWidth;
    }

    /**
     * return allowed thumbnail height
     * @return int
     */
    public function thumbHeight()
    {
        return $this->_thumbHeight;
    }

    /**
     * returns mode for new files
     * @return octal
     */
    public function modeFile()
    {
        return $this->_modeFile;
    }

    /**
     * returns mode for new folders
     * @return octal
     */
    public function modeDir()
    {
        return $this->_modeDir;
    }

}

/**
 * manipulate with files on server
 * @package GstBrowser
 */
class Connector
{
    /** @var error code - missing section in configuration */
    const ERR_MISSING_CONFIG_SECTION = 2;
    /** @var error code - missing action parameter */
    const ERR_MISSING_ACTION = 3;
    /** @var error code - not found directory (given path) */
    const ERR_DIRECTORY_NOT_FOUND = 4;
    /** @var error code - file or directory not exist (when delete on rename */
    const ERR_FILE_NOT_FOUND = 6;
    /** @var error code - given name of file or folder is empty or invalid */
    const ERR_INVALID_PARAMETER = 7;

    /** @var error code - unspecified error when create new directory */
    const ERR_MKDIR = 10;
    /** @var error code - directory to create already exists */
    const ERR_MKDIR_EXISTS = 11;

    /** @var error code - unspecified error when rename file or folder */
    const ERR_RENAME = 20;

    /** @var error code - unspecified error on upload file */
    const ERR_UPLOAD = 30;
    /** @var error code - uploaded file too large */
    const ERR_UPLOAD_FILESIZE = 31;
    /** @var error code - file already exists, if overwrite is disabled */
    const ERR_UPLOAD_FILE_EXISTS = 32;

    /** @var error code - unspecified error on delete file or folder */
    const ERR_DELETE = 40;
    /** @var error code - deleted directory is not empty */
    const ERR_DELETE_NOT_EMPTY_DIR = 41;

    /** @var error code - unspecified error on copy/move file */
    const ERR_COPY = 50;
    /** @var error code - target file exists on copy/move file */
    const ERR_COPY_FILE_EXISTS = 51;
    /** @var error code - target dir not exists on copy/move file */
    const ERR_COPY_DIR_NOT_FOUND = 52;

    /** @var \GstBrowser\Config current configuration */
    private $_config;

    /**
     * constructor
     * @param \GstBrowser\Config $config current configuration
     */
    public function  __construct(Config $config)
    {
        $this->_config = $config;
    }

    /**
     * returns path to directory
     * @param string $path
     * @return string
     */
    private function _targetDir($path)
    {
        return $this->_config->baseDir().trim($path, '/').($path === '' ? '' : '/');
    }

    /**
     * returns list of files and folders in path
     * @param string $path
     * @return array
     * @see \GstBrowser\Connector::_output
     */
    public function getFiles($path)
    {
        $targetDir = $this->_targetDir($path);
        if (!is_dir($targetDir)) {
            return $this->_output(self::ERR_DIRECTORY_NOT_FOUND);
        }
        return $this->_output(0, $this->_getFolderContent($targetDir));
    }

    /**
     * returns list of files and folders in given directory
     * @param string $targetDir
     * @return array
     */
    private function _getFolderContent($targetDir)
    {
        $cache = new CacheDir($targetDir, $this->_config);
        return $cache->getFiles();
    }

    /**
     * returns result from processed action as assoc array with indices:
     * 'status' => [OK|ERR]
     * 'err' => error code if status=ERR
     * 'files' => optional array of files, if action change content of folder in current path
     * 'tree' => optional tree of foldes, if action change any folder
     *
     * @param int $err error code, 0=success
     * @param array $files
     * @param array $tree
     * @return array
     */
    private function _output($err, $files = NULL, $tree = NULL)
    {
        $ret = array('status' => ($err === 0 ? 'OK' : 'ERR'));
        if ($err > 0) {
            $ret['err'] = $err;
        }
        if (!is_null($files)) {
            $ret['files'] = $files;
        }
        if (!is_null($tree)) {
            $ret['tree'] = $tree;
        }
        return $ret;
    }

    /**
     * create new directory
     * @param string $path
     * @param string $newdir name of new directory
     * @return array
     * @see \GstBrowser\Connector::_output
     */
    public function mkDir($path, $newdir)
    {
        $targetDir = $this->_targetDir($path);
        if (!is_dir($targetDir)) {
            return $this->_output(self::ERR_DIRECTORY_NOT_FOUND);
        }
        if ($newdir === '' || !preg_match('/^[a-z0-9-_\.]+$/i', $newdir)) {
            return $this->_output(self::ERR_INVALID_PARAMETER);
        }
        $fullpath = $targetDir.$newdir;
        if (is_dir($fullpath)) {
            return $this->_output(self::ERR_MKDIR_EXISTS);
        }
        $oldumask = umask(0);
        if (@mkdir($fullpath, $this->_config->modeDir())) {
            umask($oldumask);
            $cache = new CacheDir($targetDir, $this->_config);
            $cache->updateFile($newdir);
        } else {
            umask($oldumask);
            return $this->_output(self::ERR_MKDIR);
        }
        return $this->_output(0, $this->_getFolderContent($targetDir), $this->_getTree());
    }

    /**
     * upload files to specified path.
     * Name of uploaded file will be converted to ASCII witch spaces replaced by hyphen
     * @param string $path
     * @param array $files same as $_FILES
     * @return array
     * @see \GstBrowser\Connector::_output
     */
    public function upload($path, $files)
    {
        $targetDir = $this->_targetDir($path);
        if (!is_dir($targetDir)) {
            return $this->_output(self::ERR_DIRECTORY_NOT_FOUND);
        }

        $cache = new CacheDir($targetDir, $this->_config);

        foreach ($files as $field) {
            if ($field['error'] !== 0) {
                return $this->_output(self::ERR_UPLOAD_FILESIZE);
            }
            $filename = basename($field['name']);
            $filename = @iconv('UTF-8', 'ASCII//TRANSLIT', $filename);
            $filename = str_replace(' ', '-', $filename);

            $targetFullpath = $targetDir.$filename;
            if (!$this->_config->overwrite() && file_exists($targetFullpath)) {
                return $this->_output(self::ERR_UPLOAD_FILE_EXISTS);
            }

            if (!isset($_SERVER['HTTP_HOST'])) {
                // script was run from command line (phpunit?)
                if (!@rename($field['tmp_name'], $targetFullpath)) {
                    return $this->_output(self::ERR_UPLOAD);
                }
            } else {
                if (!move_uploaded_file($field['tmp_name'], $targetFullpath)) {
                    return $this->_output(self::ERR_UPLOAD);
                }
            }
            $oldumask = umask(0);
            @chmod($targetFullpath, $this->_config->modeFile());
            umask($oldumask);

            clearstatcache();
            $cache->refresh();
        }
        return $this->_output(0, $this->_getFolderContent($targetDir));
    }

    /**
     * rename folder or file
     * @param string $path
     * @param string $old
     * @param string $new
     * @return array
     * @see \GstBrowser\Connector::_output
     */
    public function rename($path, $old, $new)
    {
        $targetDir = $this->_targetDir($path);
        if ($old === '' || $new === '' || !preg_match('/^[a-z0-9-_\.]+$/i', $new)) {
            return $this->_output(self::ERR_INVALID_PARAMETER);
        }

        $src = $targetDir.$old;
        $isDir = is_dir($src);
        if (!file_exists($src)) {
            return $this->_output(self::ERR_FILE_NOT_FOUND);
        }
        if (@rename($src, $targetDir.$new)) {
            $cache = new CacheDir($targetDir, $this->_config);
            $cache->deleteFile($old);
            $cache->updateFile($new);
        } else {
            return $this->_output(self::ERR_RENAME);
        }
        if ($isDir) {
            return $this->_output(0, $this->_getFolderContent($targetDir), $this->_getTree());
        }
        return $this->_output(0, $this->_getFolderContent($targetDir));
    }

    /**
     * delete folder or file
     * @param string $path
     * @param string $name
     * @return array
     * @see \GstBrowser\Connector::_output
     */
    public function delete($path, $name)
    {
        $targetDir = $this->_targetDir($path);
        $target = $targetDir.$name;
        if (!is_dir($target) && !is_file($target)) {
            return $this->_output(self::ERR_FILE_NOT_FOUND);
        }
        if (is_dir($target)) {
            $files = @scandir($target);
            $files = array_diff($files, array(CacheDir::CACHE_FILENAME));
            if (count($files) > 2) { // dirs . and ..
                return $this->_output(self::ERR_DELETE_NOT_EMPTY_DIR);
            }
            $cacheFile = $target.'/'.CacheDir::CACHE_FILENAME;
            if (is_file($cacheFile)) {
                unlink($cacheFile);
            }
            if (rmdir($target)) {
                $cache = new CacheDir($targetDir, $this->_config);
                $cache->deleteFile($name);
                return $this->_output(0, $this->_getFolderContent($targetDir), $this->_getTree());
            } else {
                return $this->_output(self::ERR_DELETE);
            }
        } else {
            if (@unlink($target)) {
                $cache = new CacheDir($targetDir, $this->_config);
                $cache->deleteFile($name);
                return $this->_output(0, $this->_getFolderContent($targetDir));
            } else {
                return $this->_output(self::ERR_DELETE);
            }
        }
    }

    /**
     * copy file to another folder
     * @param string $path relative path of copied filed
     * @param string $name name of copied filed
     * @param string $newtarget path (optionaly including filename) of target file, relative to root directory
     * @return array
     * @see \GstBrowser\Connector::_output
     */
    public function copy($path, $name, $newtarget)
    {
        $targetDir = $this->_targetDir($path);
        $target = $targetDir.$name;
        if (is_dir($target)) {
            return $this->_output(self::ERR_INVALID_PARAMETER);
        }
        if (!is_file($target)) {
            return $this->_output(self::ERR_FILE_NOT_FOUND);
        }

        $copyTargetDir = $this->_targetDir('').$newtarget;

        //copy file
        if (is_dir($copyTargetDir)) {
            if (copy($target, $copyTargetDir.'/'.$name)) {
                $cache = new CacheDir($copyTargetDir, $this->_config);
                $cache->updateFile($name);
                return $this->_output(0);
            } else {
                return $this->_output(self::ERR_COPY);
            }
        } else {
            // copy with new filename
            if (is_file($copyTargetDir)) {
                return $this->_output(self::ERR_COPY_FILE_EXISTS);
            }
            if (!is_dir(dirname($copyTargetDir))) {
                return $this->_output(self::ERR_COPY_DIR_NOT_FOUND);
            }
            if (!preg_match('/^[a-z0-9-_\.]+$/i', basename($copyTargetDir))) {
                return $this->_output(self::ERR_INVALID_PARAMETER);
            }
            if (copy($target, $copyTargetDir)) {
                $cache = new CacheDir(dirname($copyTargetDir), $this->_config);
                $cache->updateFile(basename($copyTargetDir));
                return $this->_output(0);
            } else {
                return $this->_output(self::ERR_COPY);
            }
        }
    }


    /**
     * move file to another folder
     * @param string $path relative path of moved filed
     * @param string $name name of moved filed
     * @param string $newtarget path (optionaly including filename) of target file, relative to root directory
     * @return array
     * @see \GstBrowser\Connector::_output
     */
    public function move($path, $name, $newtarget)
    {
        $targetDir = $this->_targetDir($path);
        $target = $targetDir.$name;
        if (is_dir($target)) {
            return $this->_output(self::ERR_INVALID_PARAMETER);
        }
        if (!is_file($target)) {
            return $this->_output(self::ERR_FILE_NOT_FOUND);
        }

        $copyTargetDir = $this->_targetDir('').$newtarget;

        //copy file
        if (is_dir($copyTargetDir)) {
            if (rename($target, $copyTargetDir.'/'.$name)) {
                $cache = new CacheDir($copyTargetDir, $this->_config);
                $cache->updateFile($name);
            } else {
                return $this->_output(self::ERR_COPY);
            }
        } else {
            // copy with new filename
            if (is_file($copyTargetDir)) {
                return $this->_output(self::ERR_COPY_FILE_EXISTS);
            }
            if (!is_dir(dirname($copyTargetDir))) {
                return $this->_output(self::ERR_COPY_DIR_NOT_FOUND);
            }
            if (!preg_match('/^[a-z0-9-_\.]+$/i', basename($copyTargetDir))) {
                return $this->_output(self::ERR_INVALID_PARAMETER);
            }
            if (rename($target, $copyTargetDir)) {
                $cache = new CacheDir(dirname($copyTargetDir), $this->_config);
                $cache->updateFile(basename($copyTargetDir));
            } else {
                return $this->_output(self::ERR_COPY);
            }
        }
        $cache = new CacheDir($targetDir, $this->_config);
        $cache->deleteFile($name);
        return $this->_output(0, $this->_getFolderContent($targetDir));
    }

    /**
     * returns tree of all folders
     * @return array
     * @see \GstBrowser\Connector::_output
     */
    public function getFoldersTree()
    {
        if (!is_dir($this->_config->baseDir())) {
            return $this->_output(self::ERR_DIRECTORY_NOT_FOUND);
        }
        return $this->_output(0, NULL, $this->_getTree());
    }

    /**
     * returns tree of all folders
     * @return array
     */
    private function _getTree()
    {
        $subTree = $this->_getSubTree($this->_config->baseDir());

        $return = array('name' => basename($this->_config->baseDir()));
        if (isset($subTree[0])) {
            $return['children'] = $subTree;
        }
        return array($return);
    }

    /**
     * recursive function, returns folders in given directory
     * @param string $dir
     * @return array
     */
    private function _getSubTree($dir)
    {
        $result = array();
        $dirs = glob($dir.'*', GLOB_ONLYDIR);
        if (is_array($dirs)) {
            foreach ($dirs as $subDir) {
                $tmp = array('name'=>basename($subDir));
                $children = $this->_getSubTree($subDir.'/');
                if (isset($children[0])) {
                    $tmp['children'] = $children;
                }
                $result[] = $tmp;
            }
        }
        return $result;
    }
}

/**
 * represent file or folder
 * @package GstBrowser
 */
class File
{
    /** @var string absolute path to file or folder */
    private $_file;
    /** @var \GstBrowser\Config current configuration */
    private $_config;

    /**
     * constructor
     * @param string $file absolute path to file or folder
     * @param \GstBrowser\Config $config current configuration
     */
    public function __construct($file, \GstBrowser\Config $config)
    {
        $this->_file = $file;
        $this->_config = $config;
    }

    /**
     * return info about file or folder as assoc array with indices:
     * 'name' => name of file or folder
     * 'type' => type, see http://php.net/manual/en/function.filetype.php
     * 'size' => filesize in bytes, NULL for directory
     * 'date' => date of file as unix timestamp
     * 'imgsize' => array with width and height if file is image
     * 'thumbnail' => thumbnail as base64 data uri
     * @return array
     */
    public function getParams()
    {
        return array(
            'name' => basename($this->_file),
            'type' => filetype($this->_file),
            'size' => (filetype($this->_file) === 'file' ? filesize($this->_file) : NULL),
            'date' => $this->date(),
            'imgsize' => $this->imageSize(),
            'thumbnail' => $this->thumbnail()
        );
    }

    /**
     * returns last modified date of file
     * @return string
     */
    public function date()
    {
        $date = new \DateTime(NULL, new \DateTimeZone('UTC'));
        $date->setTimestamp(filemtime($this->_file));
        return $date->format('c');
    }

    /**
     * returns width and height of image
     * @return array or NULL if file is not jpg/gif/png image
     */
    public function imageSize()
    {
        if (filetype($this->_file) !== 'file') {
            return NULL;
        }
        $ext = explode('.', $this->_file);
        $ext = strtolower(end($ext));
        if (!in_array($ext, array('jpg', 'jpeg', 'gif', 'png'))) {
            return NULL;
        }
        $size = @getimagesize($this->_file);
        return (is_array($size) ? array_splice($size, 0, 2) : NULL);
    }

    /**
     * returns thumbnail as base64 data uri
     * @return string
     */
    public function thumbnail()
    {
        if (filetype($this->_file) !== 'file') {
            return '';
        }
        $ext = explode('.', $this->_file);
        $ext = strtolower(end($ext));
        $ext = str_replace('jpeg', 'jpg', $ext);
        if (!in_array($ext, array('jpg', 'gif', 'png'))) {
            return '';
        }

        $imgOrig = $this->_openImage($this->_file, $ext);
        if ($imgOrig === FALSE) {
            return '';
        }

        $origTop = $origLeft = 0;
        $origWidth = imagesx($imgOrig);
        $origHeight = imagesy($imgOrig);

        $thumbWidth = $this->_config->thumbWidth();
        $thumbHeight = $this->_config->thumbHeight();
        $thumbTop = $thumbLeft = 0;

        $ratio = $origWidth / $origHeight;
        if ($ratio < 1) {
            $thumbWidth = min($thumbWidth, (int) ($thumbHeight * $ratio));
        } else {
            $thumbHeight = min($thumbHeight, (int) ($thumbWidth / $ratio));
        }

        $imgThumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
        if (!$imgThumb) {
            return '';
        }

        if ($ext === 'gif') {
            $transparentIndex = imagecolortransparent($imgOrig);
            if ($transparentIndex >= 0) {
                // get original image's transparent color's RGB values
                $transparentColor = imagecolorsforindex($imgOrig, $transparentIndex);
                // allocate the same color in the new image
                $transparentIndex = imagecolorallocate(
                    $imgThumb, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']
                );
                // fill the background of the new image with allocated color
                imagefill($imgThumb, 0, 0, $transparentIndex);
                // set the background color to transparent
                imagecolortransparent($imgThumb, $transparentIndex);
            }
        }
        if ($ext == 'png') {
            // temporarily turn off transparency blending
            imagealphablending($imgThumb, FALSE);
            imagesavealpha($imgThumb, TRUE);
            // create a new transparent color for image
            $transparent = imagecolorallocatealpha($imgThumb, 0, 0, 0, 127);
            // fill the background of the new image with allocated color
            imagefilledrectangle($imgThumb, 0, 0, $thumbWidth, $thumbHeight, $transparent);
            // restore transparency blending
            imagesavealpha($imgThumb, TRUE);
        }

        imagecopyresampled(
            $imgThumb, $imgOrig, $origTop, $origLeft, $thumbTop, $thumbLeft,
            $thumbWidth, $thumbHeight, $origWidth, $origHeight
        );

        $imageData = $this->_imageToData($imgThumb, $ext);
        imagedestroy($imgOrig);
        imagedestroy($imgThumb);
        return 'data:image/'.$ext.';base64,'.base64_encode($imageData);
    }

    private function _openImage($file, $ext)
    {
        switch ($ext) {
        case 'png':
            return @imagecreatefrompng($file);
        case 'gif':
            return @imagecreatefromgif($file);
        }
        return @imagecreatefromjpeg($file);
    }

    private function _imageToData($image, $ext)
    {
        ob_start();
        switch ($ext) {
        case 'jpg':
            imagejpeg($image);
            break;
        case 'gif':
            imagegif($image);
            break;
        case 'png':
            imagepng($image);
            break;
        }
        $imageData = ob_get_contents();
        ob_end_clean();
        return $imageData;
    }

}


/**
 * manipulate with cached content of directory
 * @package GstBrowser
 */
class CacheDir
{
    /** @var name of file with cached content  */
    const CACHE_FILENAME = '.htdircache';
    /** @var string directory */
    private $_dir;
    /** @var string cache file in current directory */
    private $_cachefile;
    /** @var array items in cache */
    private $_items;
    /** @var \GstBrowser\Config current configuration */
    private $_config;

    /**
     * constructor
     * @param string $dir absolute path to directory
     * @param \GstBrowser\Config current configuration
     */
    public function __construct($dir, \GstBrowser\Config $config)
    {
        $this->_dir = rtrim($dir, '/').'/';
        $this->_config = $config;
        $this->_cachefile = $this->_dir.self::CACHE_FILENAME;
        if (is_file($this->_cachefile) && filemtime($this->_cachefile) > time()-7200) {
            $this->_items = @json_decode(file_get_contents($this->_cachefile));
            if (!is_array($this->_items)) {
                $this->refresh();
            }
        } else {
            $this->refresh();
        }
    }

    /**
     * returns array of folders and files in cache
     * @return array or NULL
     */
    public function getFiles()
    {
        return is_null($this->_items) ? NULL : array_values($this->_items);
    }

    /**
     * refresh cache content
     */
    public function refresh()
    {
        $result = array();
        $files = glob($this->_dir.'*');
        $files = (is_array($files) ? $files : array());
        foreach ($files as $file) {
            if (substr(basename($file), 0, 3) !== '.ht') {
                $fileInfo = new File($file, $this->_config);
                $result[basename($file)] = $fileInfo->getParams();
            }
        }
        ksort($result);
        $this->_items = $result;
        $this->_save();
    }

    /**
     * add or update file in cache
     * @param string $file absolute path
     */
    public function updateFile($file)
    {
        $fileInfo = new File($this->_dir.$file, $this->_config);
        $this->_items[$file] = $fileInfo->getParams();
        $this->_save();
    }

    /**
     * delete file from cache
     * @param string $file absolute path
     */
    public function deleteFile($file)
    {
        if (isset($this->_items[$file])) {
            unset($this->_items[$file]);
            $this->_save();
        }
    }

    /**
     * save cache to file
     */
    private function _save()
    {
        file_put_contents($this->_cachefile, json_encode($this->_items));
    }

}