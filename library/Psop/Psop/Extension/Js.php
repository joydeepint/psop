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
 * @subpackage Js
 * @copyright  2002 Douglas Crockford <douglas@crockford.com>
 * @copyright  Copyright (c) 2008 MaakSite.nl Productions. (http://productions.maaksite.nl/)
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Psop_Psop_Extension_Js extends Psop_Extension_Abstract
{

    const ORD_LF    = 10;
    const ORD_SPACE = 32;

    protected $a           = '';
    protected $b           = '';
    protected $input       = '';
    protected $inputIndex  = 0;
    protected $inputLength = 0;
    protected $lookAhead   = null;
    protected $output      = array();

    /**
	 * Parse content of file
	 * 
	 * @param string $input
	 * @return string
	 */
    public function parse($contents)
    {
        $this->input       = str_replace("\r\n", "\n", $contents);
        $this->inputLength = strlen($this->input);
        
        $this->a = "\n";
        $this->_action(3);

        while ($this->a !== null) {
            switch ($this->a) {
                case ' ':
                    if ($this->_isAlphaNum($this->b)) {
                        $this->_action(1);
                    }
                    else {
                        $this->_action(2);
                    }
                    break;

                case "\n":
                    switch ($this->b) {
                        case '{':
                        case '[':
                        case '(':
                        case '+':
                        case '-':
                            $this->_action(1);
                            break;

                        case ' ':
                            $this->_action(3);
                            break;

                        default:
                            if ($this->_isAlphaNum($this->b)) {
                                $this->_action(1);
                            }
                            else {
                                $this->_action(2);
                            }
                    }
                    break;

                default:
                    switch ($this->b) {
                        case ' ':
                            if ($this->_isAlphaNum($this->a)) {
                                $this->_action(1);
                                break;
                            }

                            $this->_action(3);
                            break;

                        case "\n":
                            switch ($this->a) {
                                case '}':
                                case ']':
                                case ')':
                                case '+':
                                case '-':
                                case '"':
                                case "'":
                                    $this->_action(1);
                                    break;

                                default:
                                    if ($this->_isAlphaNum($this->a)) {
                                        $this->_action(1);
                                    }
                                    else {
                                        $this->_action(3);
                                    }
                            }
                            break;

                        default:
                            $this->_action(1);
                            break;
                    }
            }
        }

        return implode('', $this->output);
    }
    
    private function _action($d) {
        switch($d) {
            case 1:
                $this->output[] = $this->a;

            case 2:
                $this->a = $this->b;

                if ($this->a === "'" || $this->a === '"') {
                    for (;;) {
                        $this->output[] = $this->a;
                        $this->a        = $this->_get();

                        if ($this->a === $this->b) {
                            break;
                        }

                        if (ord($this->a) <= self::ORD_LF) {
                            throw new Psop_Psop_Exception('Unterminated string literal in js file');
                        }

                        if ($this->a === '\\') {
                            $this->output[] = $this->a;
                            $this->a        = $this->_get();
                        }
                    }
                }

            case 3:
                $this->b = $this->_next();

                if ($this->b === '/' && (
                $this->a === '(' || $this->a === ',' || $this->a === '=' ||
                $this->a === ':' || $this->a === '[' || $this->a === '!' ||
                $this->a === '&' || $this->a === '|' || $this->a === '?')) {

                    $this->output[] = $this->a;
                    $this->output[] = $this->b;

                    for (;;) {
                        $this->a = $this->_get();

                        if ($this->a === '/') {
                            break;
                        }
                        elseif ($this->a === '\\') {
                            $this->output[] = $this->a;
                            $this->a        = $this->_get();
                        }
                        elseif (ord($this->a) <= self::ORD_LF) {
                            throw new Psop_Psop_Exception('Unterminated regular expression '.
                            'literal in js file');
                        }

                        $this->output[] = $this->a;
                    }

                    $this->b = $this->_next();
                }
        }
    }

    private function _get() {
        $c = $this->lookAhead;
        $this->lookAhead = null;

        if ($c === null) {
            if ($this->inputIndex < $this->inputLength) {
                $c = $this->input[$this->inputIndex];
                $this->inputIndex += 1;
            }
            else {
                $c = null;
            }
        }

        if ($c === "\r") {
            return "\n";
        }

        if ($c === null || $c === "\n" || ord($c) >= self::ORD_SPACE) {
            return $c;
        }

        return ' ';
    }

    private function _isAlphaNum($c) {
        return ord($c) > 126 || $c === '\\' || preg_match('/^[\w\$]$/', $c) === 1;
    }

    private function _next() {
        $c = $this->_get();

        if ($c === '/') {
            switch($this->_peek()) {
                case '/':
                    for (;;) {
                        $c = $this->_get();

                        if (ord($c) <= self::ORD_LF) {
                            return $c;
                        }
                    }

                case '*':
                    $this->_get();

                    for (;;) {
                        switch($this->_get()) {
                            case '*':
                                if ($this->_peek() === '/') {
                                    $this->_get();
                                    return ' ';
                                }
                                break;

                            case null:
                                throw new Psop_Psop_Exception('Unterminated comment in js file');
                        }
                    }

                default:
                    return $c;
            }
        }

        return $c;
    }

    private function _peek() {
        $this->lookAhead = $this->_get();
        return $this->lookAhead;
    }
}