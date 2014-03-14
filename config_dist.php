<?php
/**
 * configuration for gstbrowser
 *
 * @package GstBrowser
 */

// default configuration
$gstbrowserConf['default']['root_dir'] = ''; // abolute path to root directory
$gstbrowserConf['default']['mode_dir'] = 0755; // permissions for new folders
$gstbrowserConf['default']['mode_file'] = 0644; // permissions for new files
$gstbrowserConf['default']['thumb_width'] = 90; // max width of image thumbnails
$gstbrowserConf['default']['thumb_hight'] = 90; // max height of image thumbnails
$gstbrowserConf['default']['sess_variable'] = ''; // session variable and value to check
$gstbrowserConf['default']['sess_value'] = '';
$gstbrowserConf['default']['allow_ip'] = array(); // array of allowed IP adresses

# optionally uncomment and override default configuration with named config
//$gstbrowserConf['custom']['root_dir'] = 'd:/temp/';
//$gstbrowserConf['custom']['thumb_width'] = 60;
//$gstbrowserConf['custom']['thumb_width'] = 60;
//$gstbrowserConf['custom']['sess_variable'] = 'some_sesion_variable';
//$gstbrowserConf['custom']['sess_value'] = 'some_secret_value';
