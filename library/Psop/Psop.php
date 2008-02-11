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

    /**
     * Psop
     *
     * @param   string  $dir    Location of project
     * @return  void
     */
    public function __construct($dir)
    {
        // start timer
        $start = explode (' ', microtime());
        $this->_start  = $start[1] + $start[0];

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

        // process
        $this->_process($dir);

        // stop timer
        $end = explode (' ', microtime());
        $this->_end  = $end[1] + $end[0];

        echo "\n";
        echo '--- psop statitics for \'' . $dir . '\' ---' . "\n";
        echo 'Time: ' . round($this->_end - $this->_start, 2) . 'ms' . "\n";
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
                        echo 'DIR ' . $dir . $read . '/' . "\n";
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

        if ($pathinfo['extension'] == 'php') {
            $this->_filesOptimized++;
            echo 'Optimizing file \'' . $file . '\'' . '(' . $size . ')' . "\n";

            $this->_size += $size;

            if ($pathinfo['extension'] == 'php') {
                $newContents = $this->_optimizePhp($contents);
            } else {
                throw new Zend_Exception('Internal error');
            }

            $cacheFile = $this->_cacheDir . '/' . substr($file, strlen($this->_dir));
            $newHandle = fopen ($cacheFile, 'w');
            fwrite($newHandle, $newContents);
            
            $sizeOptimized = filesize($cacheFile);
            $this->_sizeOptimized += $sizeOptimized;
        } else {
            echo 'NOT Optimizing file \'' . $file . '\'' . '(' . $size . ')' . "\n";
            copy($file, $this->_cacheDir . '/' . substr($file, strlen($this->_dir)));
        }
    }

    private function _optimizePhp ($contents) {
        $tokens = token_get_all($contents);
        $newContents = '';
        
        $whitespace = false;

        foreach ($tokens as $token) {
            if (is_string($token)) {
                $newContents .= $token;
            } else {
                list ($id, $text) = $token;
                switch ($id) {
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                    case T_WHITESPACE:
                        
                    break;
                    case T_OPEN_TAG:
                        if (substr($text, 0, 5) == '<?php') {
                            $newContents .= '<?php ';
                        } else if (substr($text, 0, 2) == '<?') {
                            $newContents .= '<? ';
                        } else {
                            $newContents .= $text;
                        }
                        break;
                    case T_AS:
                    case T_INSTANCEOF:
                         $newContents .= ' ' . $text . ' ';
                         break;
                    case T_PUBLIC:
                    case T_PRIVATE:
                    case T_PROTECTED:
                    case T_CONST:
                    case T_NEW:
                    case T_RETURN:
                    case T_CLASS:
                    case T_THROW:
                    case T_TRY:
                    case T_CASE:
                    case T_SWITCH:
                    case T_CATCH:
                    case T_INTERFACE:
                    case T_FINAL:
                    case T_ABSTRACT:
                    case T_FUNCTION:
                    case T_STRING:
                    case T_IMPLEMENTS:
                    case T_EXTENDS:
                    case T_STATIC:
                    case T_REQUIRE_ONCE:
                    case T_REQUIRE:
                    case T_INCLUDE:
                    case T_INCLUDE_ONCE:
                    case T_CLONE:
                    case T_ECHO:
                        #$whitespace = true;
                        $newContents .= $text . ' ';
                        break;
                    case T_LOGICAL_AND:
                        $newContents .= '&&';
                        break;
                    case T_LOGICAL_OR:
                        $newContents .= '||';
                        break;
                    case T_END_HEREDOC:
                        $newContents .= $text . "\n";
                        break;
                    default:
                        // anything else -> output "as is"
                        $newContents .= $text;
                        break;
                }
            }
        }
        
        return $newContents;
    }
}
