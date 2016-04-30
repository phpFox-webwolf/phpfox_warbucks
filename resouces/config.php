<?php
/*
 * config sets various constants that will be used throughout the program
 * This should work with phpfox v3 or v4
 * pieced together  by webwolf
 */
$bIsV4=FALSE;
define("SECURE", FALSE);    // FOR DEVELOPMENT ONLY!!!!
defined('PHPFOX') 
    or define('PHPFOX', true);
defined('PHPFOX_DS') 
    or define('PHPFOX_DS', DIRECTORY_SEPARATOR);

if(file_exists(dirname(dirname(dirname(__FILE__))) . PHPFOX_DS . 'PF.Base'))
{
    $bIsV4=TRUE;
    defined('V4') 
        or define('V4', TRUE);
    defined('PHPFOX_WEB_BASE') 
        or define('PHPFOX_WEB_BASE', 'PF.Base' . PHPFOX_DS);
}


defined('PHPFOX_FULL_DIR') 
    or define('PHPFOX_FULL_DIR', ($bIsV4)?dirname(dirname(dirname(__FILE__))) . PHPFOX_DS . 'PF.Base' . PHPFOX_DS:dirname(dirname(dirname(__FILE__))) . PHPFOX_DS);

defined("PHPFOX_SETTINGS")
    or define("PHPFOX_SETTINGS", ($bIsV4)?PHPFOX_FULL_DIR."file/settings/":PHPFOX_FULL_DIR."include/setting/");

defined("LIBRARY_PATH")
    or define("LIBRARY_PATH", dirname(__FILE__) . '/library/'); 
