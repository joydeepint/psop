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
 * @see Psop_Psop
 */
require_once 'Psop/Psop.php';
/**
 * Psop_Psop_Extension_Abstract
 */
require_once 'Psop/Psop/Extension/Abstract.php';

/**
 * Class for an extension interface.
 *
 * @category   Psop
 * @package    Psop_Extension
 * @subpackage Css
 * @copyright  Copyright (c) 2008 MaakSite.nl Productions. (http://productions.maaksite.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php     New BSD License
 */
class Psop_Psop_Extension_Css extends Psop_Extension_Abstract
{

    /**
	 * Parse content of file
	 * 
	 * @param string $input
	 * @return string
	 */
    public function parse($contents)
    {
        $newContents = '';
        
        $len = strlen($contents);
        $this->cssStatus = 'default';
        $this->cssCdataBs = 0;
        $this->cssSelectorBuffer = array();
        $this->cssSelectorBufferValues = array();
        $this->cssCurrentProperty = '';
        
        for ($i = 0; $i < $len; $i++) {
            if ($this->cssStatus == 'default' AND ($contents{$i} == "\r" OR $contents{$i} == "\n" OR $contents{$i} == "\t")) {
                continue;
            }
            switch ($this->cssStatus) {
                case 'comment':
                    if ($contents{$i} == '*') {
                        $this->cssPreviousChar = '*';
                    } elseif ($contents{$i} == '/' && $this->cssPreviousChar == '*') {
                        $this->cssStatus = 'default';
                        $this->cssPreviousChar = '';
                    }
                    break;
                case 'selector':
                    if ($contents{$i} == ' ' OR $contents{$i} == "\r" OR $contents{$i} == "\n" OR $contents{$i} == "\t") {
                        break;
                    } elseif ($contents{$i} == '}') {
                        $this->cssStatus = 'default';
                        $had = array();
                        foreach ($this->cssSelectorBuffer as $key => $prop) {
                            if (substr($prop, 0, 6) == 'border') {
                                if (!in_array('border', $had)) {
                                    $borderL = array_search('border-left', $this->cssSelectorBuffer);
                                    $borderR = array_search('border-right', $this->cssSelectorBuffer);
                                    $borderT = array_search('border-top', $this->cssSelectorBuffer);
                                    $borderB = array_search('border-bottom', $this->cssSelectorBuffer);
                                    
                                    $borderValueL = '';
                                    $borderValueR = '';
                                    $borderValueT = '';
                                    $borderValueB = '';
                                    
                                    if ($borderL !== false && $borderL !== null) {
                                        $borderValueL = $this->cssSelectorBufferValues[$borderL];
                                    }
                                    if ($borderR !== false && $borderR !== null) {
                                        $borderValueR = $this->cssSelectorBufferValues[$borderR];
                                    }
                                    if ($borderT !== false && $borderT !== null) {
                                        $borderValueT = $this->cssSelectorBufferValues[$borderT];
                                    }
                                    if ($borderB !== false && $borderB !== null) {
                                        $borderValueB = $this->cssSelectorBufferValues[$borderB];
                                    }
                                    
                                    if ($borderValueL == $borderValueR && $borderValueL == $borderValueT && $borderValueB == $borderValueL && $borderValueB != '') {
                                        $newContents .= 'border:' . $this->cssSelectorBufferValues[$key];
                                        $had[] = 'border';
                                    } else {
                                        $newContents .= $prop . ':' . $this->cssSelectorBufferValues[$key];
                                        if (count($this->cssSelectorBuffer) != $key+1) {
                                            $newContents .= ';';
                                        }
                                        $had[] = 'border';
                                    }
                                } else {
                                    $newContents .= $prop . ':' . $this->cssSelectorBufferValues[$key];
                                    if (count($this->cssSelectorBuffer) != $key+1) {
                                        $newContents .= ';';
                                    }
                                }
                            } else {
                                $newContents .= $prop . ':' . $this->cssSelectorBufferValues[$key];
                                if (count($this->cssSelectorBuffer) != $key+1) {
                                    $newContents .= ';';
                                }
                            }
                        }
                        $this->cssSelectorBuffer = array();
                        $this->cssSelectorBufferValues = array();
                        $newContents .= $contents{$i};
                    } else {
                        #$this->cssSelectorBuffer = array();
                        #$this->cssSelectorBufferValues = array();
                        $this->cssStatus = 'property';
                        $this->cssCurrentProperty = $contents{$i};
                        #$newContents .= $contents{$i};
                    }
                    break;
                case 'property':
                    if ($contents{$i} == ' ' OR $contents{$i} == "\r" OR $contents{$i} == "\n" OR $contents{$i} == "\t") {
                        break;
                    } elseif ($contents{$i} == ':') {
                        $this->cssStatus = 'value';
                        $this->cssPreviousChar = ':';
                        $this->cssCurrentValue = '';
                        $this->cssSelectorBuffer[] = $this->cssCurrentProperty;
                    } else {
                        $this->cssCurrentProperty .= $contents{$i};
                    }
                    #$newContents .= $contents{$i};
                    
                    break;
                case 'value':
                    if ($this->cssPreviousChar == ':' && ($contents{$i} == ' ' OR $contents{$i} == "\r" OR $contents{$i} == "\n" OR $contents{$i} == "\t")) {
                        $this->cssPreviousChar = '';
                        break;
                    } elseif ($contents{$i} == '(' OR $contents{$i} == '\'' OR $contents{$i} == '"') {
                        $this->cssStatus = 'cdata';
                        $this->cssCdataBs = 0;
                        if ($contents{$i} == '(') {
                            $prev = ')';
                        } elseif ($contents{$i} == '\'') {
                            $prev = '\'';
                        }  elseif ($contents{$i} == '"') {
                            $prev = '"';
                        }
                        $this->cssPreviousChar = $prev;
                    } elseif ($contents{$i} == '}') {
                        // not implemented yet
                    } elseif ($contents{$i} == ';') {
                        $this->cssSelectorBufferValues[] = $this->cssCurrentValue;
                        $this->cssStatus = 'selector';
                        #$newContents .= $this->cssCurrentProperty . ':' . $this->cssCurrentValue . ';';
                        break;
                    }
                    #$newContents .= $contents{$i};
                    $this->cssCurrentValue .= $contents{$i};
                    
                    break;
                case 'cdata':
                    if ($contents{$i} == $this->cssPreviousChar && ($this->cssCdataBs/2 == round($this->cssCdataBs/2))) {
                        $this->cssStatus = 'value';
                        $this->cssCdataBs = 0;
                    } elseif ($contents{$i} == '\\') {
                        $this->cssCdataBs++;
                    } else {
                        $this->cssCdataBs = 0;
                    }

                    #$newContents .= $contents{$i};
                    $this->cssCurrentValue .= $contents{$i};
                    break;
                case 'default':
                    if ($contents{$i} == '{') {
                        $this->cssStatus = 'selector';
                        $this->cssCurrentProperty = '';
                        $this->cssSelectorBuffer = array();
                        $this->cssSelectorBufferValues = array();
                    } elseif ($contents{$i} == '/') {
                        $this->cssPreviousChar = '/';
                        break;
                    } elseif ($contents{$i} == '*' && $this->cssPreviousChar == '/') {
                        $this->cssStatus = 'comment';
                        $this->cssPreviousChar = '';
                        break;
                    }
                    $newContents .= $contents{$i};
                    break;
            }
        }
        
        #echo $newContents;
        
        return $newContents;
    }
}