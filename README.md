# PHP server connector for GST Browser

Server side part of gstbrowser (filemanager for TinymCE, CKEditor and web apps,
https://github.com/zdenekgebauer/gstbrowser)

## Instalation

## Manual instalation
1. Make a clean directory, this directory must be accessible via url.
2. Download zip with latest version from github file and unpack its contents to the same directory.

Or you can clone repository to the same directory.

## Instalation via composer
Add to composer.json
<pre><code>
"require": {
    "zdenekgebauer/gstbrowser-connector-php": "dev-master"
},
</code></pre>

then
``composer install``

## Configuration
Rename config_dist.php to config.php and adjust the settings according to your needs. You have to set at least
$gstbrowserConf['default']['root_dir'].
You can create more sections with different configurations







