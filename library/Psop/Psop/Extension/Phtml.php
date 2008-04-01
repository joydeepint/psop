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
 * @subpackage Phtml
 * @copyright  Copyright (c) 2008 MaakSite.nl Productions. (http://productions.maaksite.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php     New BSD License
 */
class Psop_Psop_Extension_Phtml extends Psop_Extension_Abstract
{

    const STATE_DATA = 'data';
    const STATE_END = 'end';
    const STATE_TAG_OPEN = 'tag_open';
    const STATE_TAG_ATTR = 'tag_attr';
    const STATE_TAG_ATTR_VALUE = 'tag_attr_value';
    const STATE_ENTITY = 'entity';

    const T_TEXT = 1;
    const T_COMMENT = 2;
    const T_ERROR = 3;
    const T_TAG_OPEN = 4;
    const T_TAG_NAME = 5;
    const T_WHITESPACE = 6;
    const T_ATTR_NAME = 7;
    const T_EQUAL = 8;
    const T_ATTR_VALUE = 9;
    const T_TAG_END = 10;
    const T_DOCTYPE = 11;
    const T_CDATA = 12;
    const T_ENTITY = 13;


    /**
     * Tokenizer source
     * 
     * @var string
     */
    protected $_source;

    /**
     * The source len
     * 
     * @var int
     */
    protected $_sourceLen;

    /**
     * The pointer
     * 
     * @var int
     */
    protected $_pointer;

    /**
     * The source buffer
     * 
     * @var string
     */
    protected $_buffer;

    /**
     * Tokenizer state
     * 
     * @var string
     */
    protected $_state;

    /**
     * Tokenizer tokens
     * 
     * @var array
     */
    protected $_tokens;

    /**
     * Some temp date is stored here
     * 
     * @var array
     */
    protected $_temp = array();


    /**
     * Parser
     * 
     * @param string $contents
     * 
     * @return array
     * 
     */
    public function parse($contents)
    {
        $this->_source = $contents;

        // initialize some variables
        $this->_state     = self::STATE_DATA;
        $this->_pointer   = 0;
        $this->_tokens    = array();
        $this->_sourceLen = strlen($this->_source);
        $this->_buffer    = '';
        $this->_temp      = array();

        $this->_newContent = '';

        // and now tokenize
        do {
            switch ($this->_state) {
                case self::STATE_DATA:
                    $this->_parseData();
                    break;
                case self::STATE_TAG_OPEN:
                    $this->_parseTagOpen();
                    break;
                case self::STATE_TAG_ATTR:
                    $this->_parseTagAttr();
                    break;
                case self::STATE_TAG_ATTR_VALUE:
                    $this->_parseTagAttrValue();
                    break;
                case self::STATE_ENTITY:
                    $this->_parseEntity();
                    break;
                default:
                    $this->_state = self::STATE_END;
                    break;
            }
        } while ($this->_state != self::STATE_END);

        if (strlen($this->_buffer)) {
            $this->_newContent .= $this->_buffer;

            $this->_addToken($this->_buffer, self::T_TEXT);

            $this->_buffer = '';
        }

        #var_dump($this->_analyzed);die();
        #die();
        //var_dump($this->_tokens);die();
        $this->_newContent = '';
        foreach($this->_tokens as $key => $token) {
            if (!isset($token[1])) {
                $this->_newContent .= $token[0];
            } else {
                switch ($token[0]) {
                    case self::T_TEXT:
                        $this->_newContent .= str_replace(array("\r\n", "\n", "\n", "\t", '    '), '',  $token[1]);
                        break;
                    case self::T_COMMENT:
                        break;
                    default:
                        $this->_newContent .= $token[1];
                        break;
                }
            }
        }

        return $this->_newContent;
    }

    /**
     * Get the tokenizer output
     * 
     * @return array
     */
    public function getTokens()
    {
        return $this->_tokens;
    }

    /**
     * Add a token
     * 
     * @param string $value
     * @param int $token
     * 
     * @return void
     */
    protected function _addToken($value, $token)
    {
        $this->_tokens[] = array($token, $value);
    }

    /**
     * Parse an entity
     * 
     * @return void
     */
    protected function _parseEntity()
    {
        // match everything until the entity

        $matches = '';

        $name = substr($this->_source, $this->_pointer);

        preg_match('/^([a-zA-Z0-9]+?|#[0-9]+?);/i', $name, $matches);

        if (isset($matches[1])) {
            $name = '&' . $matches[1];
        } else {
            $name = '&';
        }

        $this->_pointer += strlen($name) - 1;

        $this->_newContent .= $name;

        $this->_addToken($name, self::T_ENTITY);

        $this->_state = self::STATE_DATA;
    }

    /**
     * Parse tag attr value
     * 
     * @return void
     */
    protected function _parseTagAttrValue()
    {
        $equal = false;

        // try to get the value
        while ($char = $this->_nextChar()) {

            if (($char == '"') || ($char == "'") && $equal) {
                // the attribute value starts, try to get the end of the value

                // count the backslashes
                $backSlashes = 0;

                $this->_buffer .= $char;

                while ($nchar = $this->_nextChar()) {
                    switch ($nchar) {
                        case '\\':
                            $backSlashes++;

                            $this->_buffer .= $nchar;
                            break;
                        case $char:
                            // we have a value
                            $this->_buffer .= $nchar;
                            if (($backSlashes % 2) == 0) {
                                // end of the value
                                $this->_temp['tag'][] = array(self::T_ATTR_VALUE, $this->_buffer);

                                $this->_temp['tag_content'] .= $this->_buffer;

                                $this->_state = self::STATE_TAG_ATTR;

                                $this->_buffer = '';
                                $tmpBuffer = '';
                                $this->_openAttr = '';

                                return;
                            }
                            break;
                        default:
                            $backSlashes = 0;

                            // hack for speed
                            $len = strcspn($this->_source, $char . '\\', $this->_pointer);

                            if ($len != 0) {
                                $this->_buffer .= substr($this->_source, $this->_pointer - 1, $len + 1);
                                $this->_pointer += $len - 1;
                            } elseif (strlen($this->_buffer) == 1) {
                                $this->_buffer .= substr($this->_source, $this->_pointer - 1, $len + 1);
                            }
                            unset($len);
                            break;
                    }
                }

                // there is no " or ' found, add an error

                $this->_newContent .= $this->_temp['tag_content'];

                $this->_addToken($this->_temp['tag_content'], self::T_ERROR);

                $this->_temp['tag']         = array();
                $this->_temp['tag_content'] = '';

                $this->_state = self::STATE_DATA;

                return;
            } elseif (($char == '=') && !$equal) {
                $equal = true;

                $this->_temp['tag'][] = array(self::T_EQUAL, $char);

                $this->_temp['tag_content'] .= $char;
            } elseif (ctype_space($char)) {
                // whitespace
                $last = count($this->_temp['tag']) - 1;

                if ($this->_temp['tag'][$last][0] == self::T_WHITESPACE) {
                    $this->_temp['tag'][$last][1] .= $char;
                } else {
                    $this->_temp['tag'][]        = array(self::T_WHITESPACE, $char);
                }

                $this->_temp['tag_content'] .= $char;
            } elseif ($equal == true) {
                $this->_buffer .= $this->_source[$this->_pointer-1];
                while ($nchar = $this->_nextChar()) {
                    if ($nchar != ' ' AND $nchar != '>') {
                        $this->_buffer .= $nchar;
                        //$this->_pointer++;
                    } else {

                        $this->_temp['tag'][] = array(self::T_ATTR_VALUE, $this->_buffer);
                        $this->_state = self::STATE_TAG_ATTR;
                        $this->_temp['tag_content'] .= $this->_buffer;
                        $this->_buffer = '';
                        $this->_openAttr = '';
                        $this->_pointer--;
                        return;
                    }
                }
            } else {
                // error
                $this->_newContent .= $this->_temp['tag_content'];

                $this->_addToken($this->_temp['tag_content'], self::T_ERROR);

                $this->_temp['tag']         = array();
                $this->_temp['tag_content'] = '';

                $this->_state = self::STATE_DATA;

                return;
            }
        }
    }

    /**
     * Parse the tag's attributes
     * 
     * @return void
     */
    protected function _parseTagAttr()
    {
        // get the next char
        while ($char = $this->_nextChar()) {
            if (ctype_alpha($char)) {
                // attribute

                $name = substr($this->_source, $this->_pointer);

                // we get the name first

                $matches = array();

                preg_match('/^([a-zA-Z:-]+?)[^a-zA-Z:-]+?/i', $name, $matches);

                $name = $char . $matches[1];

                $this->_pointer += strlen($name) - 1;

                $this->_temp['tag'][] = array(self::T_ATTR_NAME, $name);

                $this->_temp['tag_content'] .= $name;

                $this->_state = self::STATE_TAG_ATTR_VALUE;

                $this->_openAttr = strtolower($name);

                return;
            } elseif (ctype_space($char)) {
                // whitespace
                $last = count($this->_temp['tag']) - 1;

                if ($this->_temp['tag'][$last][0] == self::T_WHITESPACE) {
                    $this->_temp['tag'][$last][1] .= $char;
                } else {
                    $this->_temp['tag'][]        = array(self::T_WHITESPACE, $char);
                }

                $this->_temp['tag_content'] .= $char;
            } elseif ($char == '>') {
                // tag end
                $this->_newContent .= $char;
                $this->_temp['tag'][] = array(self::T_TAG_END, $char);

                $this->_openTag = '';
                $this->_openAttr = '';

                $this->_tokens = array_merge($this->_tokens, $this->_temp['tag']);

                $this->_temp['tag']         = array();
                $this->_temp['tag_content'] = '';

                $this->_state = self::STATE_DATA;
                return;
            } elseif ($char == '/') {
                $this->_temp['tag'][] = '/';

                return;
            } elseif ($char == '<') {
                $pos = strpos($this->_source, '?>', $this->_pointer);

                if (strlen($this->_buffer)) {
                    $this->_newContent .= $this->_buffer;

                    $this->_addToken($this->_buffer, self::T_TEXT);

                    $this->_buffer = '';
                }

                if ($pos !== false) {
                    $pos += 1;

                    // collect the text
                    $text = '<' . substr($this->_source, $this->_pointer, $pos - $this->_pointer) . '>';

                    $this->_pointer = $pos+1;

                    $php = new Psop_Psop_Extension_Php();
                    $text = $php->parse($text);
                    
                    // add the text
                    $this->_newContent .= $text;
                    
                    $this->_temp['tag'][] = array(self::T_ERROR, $text);
                    $this->_temp['tag_content'] .= $text;

                    #$this->_addToken($text, self::T_DOCTYPE);
                } else {
                    $this->_newContent .= '<?php';

                    #$this->_addToken('<?php', self::T_ERROR);
                }
            } else {
                // error
                $this->_newContent .= $this->_temp['tag_content'];

                $this->_addToken($this->_temp['tag_content'], self::T_ERROR); // unknown self closing tag
                $this->_temp['tag']         = array();
                $this->_temp['tag_content'] = '';

                $this->_state = self::STATE_DATA;

                return;
            }
        }
    }

    /**
     * Parse an tag
     * 
     * @return void
     */
    public function _parseTagOpen()
    {
        // get the next char
        $char = $this->_nextChar();

        switch ($char) {
            case '!':
                $nchar = $this->_nextChar();
                if ($nchar == '-') {
                    $nchar2 = $this->_nextChar();
                    // probably a comment
                    if ($nchar2 == '-') {
                        // it is a comment, check for '-->'
                        $pos = strpos($this->_source, '-->', $this->_pointer);

                        if (strlen($this->_buffer)) {
                            $this->_newContent .= $this->_buffer;

                            $this->_addToken($this->_buffer, self::T_TEXT);

                            $this->_buffer = '';
                        }

                        if ($pos !== false) {
                            $pos += 3;

                            // collect the text
                            $text = '<!--' . substr($this->_source, $this->_pointer, $pos - $this->_pointer);

                            $this->_pointer = $pos;

                            // add the text

                            // DO NOT ADD TO NEW CONTENT
                            $this->_addToken($text, self::T_COMMENT);
                        } else {
                            $this->_newContent .= '<!--';

                            $this->_addToken('<!--', self::T_ERROR);
                        }
                        $this->_state = self::STATE_DATA;
                    } else {
                        // some error, execute a backChar
                        $this->_backChar();

                        // and assign two tokens (one text and one error)
                        if (strlen($this->_buffer)) {
                            $this->_newContent .= $this->_buffer;

                            $this->_addToken($this->_buffer, self::T_TEXT);

                            $this->_buffer = '';
                        }

                        $this->_newContent .= '<!-';

                        $this->_addToken('<!-', self::T_ERROR);

                        $this->_state = self::STATE_DATA;
                    }
                } elseif ($nchar == '[') {
                    // probably something like CDATA

                    if (substr($this->_source, $this->_pointer, 6) == 'CDATA[') {
                        // it is CDATA
                        $pos = strpos($this->_source, ']]>', $this->_pointer);

                        if (strlen($this->_buffer)) {
                            $this->_newContent .= $this->_buffer;

                            $this->_addToken($this->_buffer, self::T_TEXT);

                            $this->_buffer = '';
                        }

                        if ($pos !== false) {
                            $pos += 3;

                            // collect the text
                            $text = '<![' . substr($this->_source, $this->_pointer, $pos - $this->_pointer);

                            $this->_pointer = $pos;

                            // add the text
                            $this->_newContent .= $text;

                            $this->_addToken($text, self::T_CDATA);
                        } else {
                            $this->_newContent .= '<![';

                            $this->_addToken('<![', self::T_ERROR);
                        }
                        $this->_state = self::STATE_DATA;
                    } else {
                        // add an error
                        if (strlen($this->_buffer)) {
                            $this->_newContent .= $this->_buffer;

                            $this->_addToken($this->_buffer, self::T_TEXT);

                            $this->_buffer = '';
                        }

                        $this->_newContent .= '<![';

                        $this->_addToken('<![', self::T_ERROR);
                    }
                } elseif ($nchar == 'D') {
                    // parse a DOCTYPE

                    // just find the >
                    $pos = strpos($this->_source, '>', $this->_pointer);

                    if (strlen($this->_buffer)) {
                        $this->_newContent .= $this->_buffer;

                        $this->_addToken($this->_buffer, self::T_TEXT);

                        $this->_buffer = '';
                    }

                    if ($pos !== false) {
                        $pos += 1;

                        // collect the text
                        $text = '<!D' . substr($this->_source, $this->_pointer, $pos - $this->_pointer);

                        $this->_pointer = $pos;

                        // add the text
                        $this->_newContent .= $text;

                        $this->_addToken($text, self::T_DOCTYPE);
                    } else {
                        $this->_newContent .= '<!D';

                        $this->_addToken('<!D', self::T_ERROR);
                    }

                    $this->_state = self::STATE_DATA;
                } else {
                    // some error
                }
                break;
            case '?':
                // just find the >
                $pos = strpos($this->_source, '?>', $this->_pointer);

                if (strlen($this->_buffer)) {
                    $this->_newContent .= $this->_buffer;

                    $this->_addToken($this->_buffer, self::T_TEXT);

                    $this->_buffer = '';
                }

                if ($pos !== false) {
                    $pos += 1;

                    // collect the text
                    $text = '<?' . substr($this->_source, $this->_pointer, $pos - $this->_pointer);

                    $this->_pointer = $pos;
                    
                    $php = new Psop_Psop_Extension_Php();
                    $text2 = $php->parse($text);

                    if ($text2 == '' OR strlen($text2) > strlen($text)) {
                        $text2 = $text;
                        unset($text);
                    }
                    unset($text);
                    
                    // add the text
                    $this->_newContent .= $text2;

                    $this->_addToken($text2, self::T_DOCTYPE);
                } else {
                    $this->_newContent .= '<?php';

                    $this->_addToken('<?php', self::T_ERROR);
                }

                $this->_state = self::STATE_DATA;
                break;
            default:
                if (!ctype_alpha($char) && ($char != '/')) {
                    if (strlen($this->_buffer)) {
                        $this->_newContent .= $this->_buffer;

                        $this->_addToken($this->_buffer, self::T_TEXT);

                        $this->_buffer = '';
                    }

                    // add an error
                    $this->_newContent .= '<' . $char;

                    $this->_addToken('<' . $char, self::T_ERROR);

                    $this->_state = self::STATE_DATA;

                    return;
                }

                // search for '>' or ' '
                $len = strcspn($this->_source, '> ', $this->_pointer);

                $name = $char;

                if ($len != 0) {
                    $name .= substr($this->_source, $this->_pointer, $len);
                    $this->_pointer += $len;
                }
                unset($len);

                if (strlen($this->_buffer)) {
                    $this->_newContent .= $this->_buffer;

                    $this->_addToken($this->_buffer, self::T_TEXT);

                    $this->_buffer = '';
                }

                $this->_temp['tag'] = array();
                $this->_temp['tag'][] = array(self::T_TAG_OPEN, '<');
                $this->_temp['tag'][] = array(self::T_TAG_NAME, $name);

                $this->_openTag = strtolower($name);

                $this->_temp['tag_content'] = '<' . $name;

                $this->_state = self::STATE_TAG_ATTR;
                break;
        }
    }

    /**
     * Parse data
     * 
     * @return void
     */
    protected function _parseData()
    {
        while ($char = $this->_nextChar()) {
            //echo $char;
            switch ($char) {
                case '<':
                    // tag start
                    $this->_state = self::STATE_TAG_OPEN;

                    return;
                    break;
                /*case '&':
                    // entity start
                    $this->_state = self::STATE_ENTITY;

                    return;
                    break;*/
                default:
                    // hack for speed
                    $len = strcspn($this->_source, '<&', $this->_pointer);

                    if ($len != 0) {
                        $this->_buffer .= substr($this->_source, $this->_pointer - 1, $len);
                        $this->_pointer += $len - 1;
                    } else {
                        $this->_buffer .= $char;
                    }
                    unset($len);
            }
        }
        $this->_state = self::STATE_END;
    }

    /**
     * Get the next char
     * 
     * @return string
     */
    protected function _nextChar()
    {
        return ($this->_pointer < $this->_sourceLen) ? $this->_source[$this->_pointer++] : false;
    }

    /**
     * Reverse one char
     * 
     * @return string
     */
    protected function _backChar()
    {
        return $this->_source[$this->_pointer--];
    }
}