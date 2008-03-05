<?php

/**
 * Psop
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.opensource.org/licenses/bsd-license.php
 *
 * @category   Psop
 * @package    Psop_Psop
 * @copyright  Copyright (c) 2008 MaakSite.nl Productions. (http://productions.maaksite.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php     New BSD License
 * @version    $Id$
 */

/**
 * @see Zend_Cache
 */
require_once 'Zend/Cache.php';
/**
 * @see Psop_Extension_Abstract
 */
require_once 'Psop/Extension/Abstract.php';

/**
 * @category   Psop
 * @package    Psop_Psop
 * @copyright  Copyright (c) 2008 MaakSite.nl Productions. (http://productions.maaksite.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php     New BSD License
 */
class Psop_Psop
{
    private $_start;
    private $_end;

    // cache
    private $_dir;
    private $_cacheDir;

    // stats
    private $_files = 0;
    private $_filesOptimized = 0;
    private $_dirs = 0;

    private $_size;
    private $_sizeOptimized;
    
    // config
    private $_config = array();

    /**
     * Psop
     *
     * @param   string  $dir    Location of project
     * @return  void
     */
    public function __construct($dir, $config = array())
    {
        // start timer
        $this->_start  = time();

        // check if dir exists
        $handle = @opendir($dir);

        if (!$handle) {
            throw new Zend_Exception('psop.php: Project location invalid: \'' . $dir . '\'');
        }

        // close handle
        closedir($handle);

        // make cache dir
        $this->_dir = $dir;
        $this->_cacheDir = ZEND_ROOT . '/cache/' . str_replace('/', '_', $dir);

        if (is_dir($this->_cacheDir)) {
            $this->_cacheDir = ZEND_ROOT . '/cache/' . str_replace('/', '_', $dir) . time();
        }
        $mkdir = @mkdir($this->_cacheDir);
        if (!$mkdir) {
            throw new Zend_Exception('Could not make dir \'' . $this->_cacheDir . '\'');
        }
        
        // config is array
        if (!is_array($config)) {
            throw new Psop_Psop_Exception('Config must be an array');
        }
        
        // set config
        $this->_config = $config;
        
        // check config for extensions
        if (!isset($this->_config['extensions']) || !is_array($this->_config['extensions'])) {
            /**
             * @see Psop_Extension_Css
             */
            require_once 'Psop/Extension/Css.php';
            /**
             * @see Psop_Extension_Js
             */
            require_once 'Psop/Extension/Js.php';
            /**
             * @see Psop_Extension_Php
             */
            require_once 'Psop/Extension/Php.php';
            /**
             * @see Psop_Extension_Xml
             */
            require_once 'Psop/Extension/Xml.php';
                        // add default extensions
            $this->_config['extensions'] = array(
            'css'       => new Psop_Psop_Extension_Css(),
            'js'        => new Psop_Psop_Extension_Js(),
            'php'       => new Psop_Psop_Extension_Php(),
            'xml'       => new Psop_Psop_Extension_Xml(),
            );
        } else {
            // check user defined extensions
            foreach ($this->_config['extensions'] as $$extension => $instance) {
                if (!($instance instanceof Psop_Extension_Abstract)) {
                    throw new Psop_Psop_Exception('Extension "' . $extension . '" is not an instance of Psop_Extension_abstract');
                }
            }
        }
        
        // debug options
        if (!isset($this->_options['debug'])) {
            $this->_config['debug'] = false;
        } elseif (!is_bool($this->_options['debug'])) {
            throw new Psop_Psop_Exception('Debug option must be a boolean');
        }

        // process
        $this->_process($dir);

        // stop timer
        $this->_end  = time();

        echo "\n";
        echo '--- psop statitics for \'' . $dir . '\' ---' . "\n";
        echo 'Time: ' . round($this->_end - $this->_start, 2) . 's' . "\n";
        echo 'Optimized: ' . $this->_filesOptimized . ' out of ' . $this->_files . ' files (' . round($this->_filesOptimized/$this->_files, 3)*100 . '%)' . "\n";
        echo 'Compression ratio: ' . $this->_sizeOptimized . ' out of ' . $this->_size . ' bytes (' . round($this->_sizeOptimized/$this->_size, 3) * 100 . '%)' . "\n";
    }

    private function _process ($dir)
    {
        // check if dir exists
        $handle = @opendir($dir);

        if (!$handle) {
            throw new Zend_Exception('psop.php: Project location invalid: \'' . $dir . '\'');
        }

        // open dir
        while(false !== ($read = readdir($handle))) {
            // check if it is not current dir or parent dir
            if($read <> '.' && $read <> '..') {
                // check if it is a file or a dir
                if(is_file($dir . $read)) {
                    // if it is a file, optimize it
                    $this->_files++;
                    $this->_optimize($dir . $read);
                } elseif(is_dir($dir . $read)) {
                    // do not include hidden dirs
                    if (substr($read, 0, 1) != '.') {
                        // if it is a dir, open it and run this function again
                        $this->_dirs++;
                        mkdir($this->_cacheDir . '/' . substr($dir, strlen($this->_dir)) . '/' . $read . '/');

                        $this->_process($dir . $read . '/');
                    }
                }
            }
        }

        // close handle
        closedir($handle);
    }

    private function _optimize ($file)
    {
        // get content of file
        $handle = @fopen($file, 'r');

        if (!$handle) {
            throw new Zend_Exception('Could not open \'' . $file . '\'');
        }

        $contents = file_get_contents($file);
        $size  = $sizeOptimized = filesize($file);

        $pathinfo = pathinfo($file);
        if (!isset($pathinfo['extension'])) {
            $pathinfo['extension'] = '';
        }

        if (isset($this->_config['extensions'][$pathinfo['extension']])) {
            $this->_filesOptimized++;
            $this->_log('Optimizing file \'' . $file . '\'' . '(' . $size . ')');

            $this->_size += $size;

            // optimize
            $newContents = $this->_config['extensions'][$pathinfo['extension']]->parse($contents);

            $cacheFile = $this->_cacheDir . '/' . substr($file, strlen($this->_dir));
            $newHandle = fopen ($cacheFile, 'w');
            fwrite($newHandle, $newContents);

            $sizeOptimized = filesize($cacheFile);
            $this->_sizeOptimized += $sizeOptimized;
        } else {
            $this->_log('NOT Optimizing file \'' . $file . '\'' . '(' . $size . ')');
            copy($file, $this->_cacheDir . '/' . substr($file, strlen($this->_dir)));
        }
    }
    
    private function _log ($content)
    {
        if ($this->_config['debug']) {
            echo $content . "\n";
            ob_flush();
        }
    }

}
