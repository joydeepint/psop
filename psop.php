<?php
/**
 * psop - PHP Script Optimizer
 * 
 * @author     Rutger <rutger@maaksite.nl>
 * @package    MaakSite.nl
 * @copyright  Copyright (c) 2008 MaakSite.nl Productions (http://productions.maaksite.nl)
 * @license    http://www.opensource.org/licenses/bsd-license.php     New BSD License
 * @version    $Id$
 */

if (E_STRICT & E_ALL) {
    $errorReporting = E_ALL;
} else {
    $errorReporting = E_ALL|E_STRICT;
}

error_reporting($errorReporting);

// check if script is run in cli
if (PHP_SAPI != 'cli') {
    echo 'You may only run this script from the command line';
    exit();
}

/**
 * Strip the slashes in an array or string
 * 
 * @param   mixed   $var
 * 
 * @return  mixed
 */
function stripSlashesDeep($var)
{
    // check for an array
    return (is_array($var)) ?
    // return array mapped version
    array_map('stripSlashesDeep', $var) :
    // return slashed version
    stripslashes($var);
}

if (get_magic_quotes_gpc()) {
    // strip the slashes deep in get post and cookie
    $_GET = array_map('stripSlashesDeep', $_GET);
    $_POST = array_map('stripSlashesDeep', $_POST);
    $_COOKIE = array_map('stripSlashesDeep', $_COOKIE);
}
// always put magic quotes runtime off
set_magic_quotes_runtime(0);

/**
 * The document root, this variable says where the files are placed
 */
define('ZEND_ROOT', realpath('./'));
/**
 * The place of the library
 * 
 * Here are the Zend Framework and the Zend Extension stored
 */
define('ZEND_LIB', ZEND_ROOT . '/library/');
/**
 * The application root, here is the application stored
 */
define('ZEND_APP', ZEND_ROOT . '/app/');
/**
 * The data dir, here are all the data files stored
 * 
 * Like cache files, search indexes, config files and other types of data
 */
define('ZEND_CACHE', ZEND_ROOT . '/cache/');

$path = array(
ZEND_LIB,
get_include_path()
);

// set the include path
set_include_path(implode(PATH_SEPARATOR, $path));

/**
 * @see Zend_Db_Table_Abstract
 */
//require_once ZEND_ROOT . '/incubator/library/Zend/Db/Table/Abstract.php';
require_once 'Zend/Db/Table/Abstract.php';

/**
 * @see Zend_Debug
 */
require_once 'Zend/Debug.php';

if (extension_loaded('xdebug')) {
    Zend_Debug::setSapi('cli');
}

/**
 * @see Zend_Debug
 */
require_once 'Zend/Debug.php';

/**
 * @see Zend_Console_Getopt
 */
require_once 'Zend/Console/Getopt.php';

echo 'Psop version 1' . "\n";

// set opts
try {
    $opts = new Zend_Console_Getopt(array(
    'dir|d=s' => 'Location of project'
    ), null, array('ruleMode' => Zend_Console_Getopt::MODE_ZEND));

    $opts->parse();

    $dir = $opts->dir;
    
    if ($dir == '' OR $dir === null) {
        throw new Zend_Console_Getopt_Exception('Error: Provide a project location', $opts->getUsageMessage());
    }
} catch (Zend_Console_Getopt_Exception $e) {
    echo $e->getMessage() . "\n";
    echo $e->getUsageMessage();
    exit;
}

/**
 * @see Psop_Psop
 */
require_once 'Psop/Psop.php';

try {
    $psop = new Psop_Psop($dir);
} catch (Zend_Exception $e) {
    echo $e->getMessage() . "\n";
    exit;
}


