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
 * @subpackage Php
 * @copyright  Copyright (c) 2008 MaakSite.nl Productions. (http://productions.maaksite.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php     New BSD License
 */
class Psop_Psop_Extension_Php extends Psop_Extension_Abstract
{

    /**
	 * Parse content of file
	 * 
	 * @param string $input
	 * @return string
	 */
    public function parse($contents)
    {
        // tokenize with the built-in php tokenizer
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
                    case T_VAR:
                        #$whitespace = true;
                        $newContents .= $text . ' ';
                        break;
                    /*case T_LOGICAL_AND:
                        $newContents .= '&&';
                        break;
                    case T_LOGICAL_OR:
                        $newContents .= '||';
                        break;*/
                    case T_LOGICAL_OR:
                    case T_LOGICAL_AND:
                        $newContents .= $text . ' ';
                        break;
                    case T_END_HEREDOC:
                        $newContents .= $text . "\n";
                        break;
                    default:
                        // anything else
                        $newContents .= $text;
                        break;
                }
            }
        }

        // returns optimized file
        return $newContents;
    }
}