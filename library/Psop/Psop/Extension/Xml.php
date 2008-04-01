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
 * @subpackage Xml
 * @copyright  Copyright (c) 2008 MaakSite.nl Productions. (http://productions.maaksite.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php     New BSD License
 */
class Psop_Psop_Extension_Xml extends Psop_Extension_Abstract
{

    /**
	 * Parse content of file
	 * 
	 * @param string $input
	 * @return string
	 */
    public function parse($contents)
    {
        $newContents = $this->xmlNewContents = '';
        
        $this->pointer =& $this->dom;

        $this->xmlParser = xml_parser_create();
        
        xml_set_object($this->xmlParser, $this);
        xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, false);
        xml_set_element_handler($this->xmlParser, "xmlStartTag", "xmlEndTag");
        xml_set_character_data_handler($this->xmlParser, "xmlContents");
        
        xml_parse($this->xmlParser, $contents);
        
        unset($contents);
        
        return $this->xmlNewContents;
    }

    public function xmlMakeChildNode() {
        if (!isset($this->pointer['childNodes'])){
            $this->pointer['childNodes'] = array();
        }
        return count($this->pointer['childNodes']);
    }

    public function xmlStartTag($parser, $tag, $attributes) {
        $idx = $this->xmlMakeChildNode();
        $this->pointer['childNodes'][$idx] = Array(
            '_idx' => $idx,
            'tagName' => $tag,
            'parentNode' => &$this->pointer,
            'attributes' => $attributes,
        );
        $this->pointer =& $this->pointer['childNodes'][$idx];
        
        $newAttributes = '';
        foreach ($attributes as $key => $value) {
            $newAttributes .= ' ' . /*strtolower($key)*/ $key . '="' . $value . '"';
        }
        
        $this->xmlNewContents .= '<' . /*strtolower($tag)*/ $tag . '' . $newAttributes . '>';
        
        $this->xmlInTag = true;
    }

    public function xmlContents($parser, $cdata) {
        $idx = $this->xmlMakeChildNode();
        $this->pointer['childNodes'][$idx] = $cdata;
        //text node -- has no other attributes than the content
        
        if ($this->xmlInTag === true && trim($cdata) != '') {
            $this->xmlNewContents .= htmlspecialchars($cdata);
        }
    }

    public function xmlEndTag($parser, $tag) {
        $idx =& $this->pointer['_idx'];
        $this->pointer =& $this->pointer['_parent'];
        unset($this->pointer['childNodes'][$idx]['_idx']);
        
        $this->xmlNewContents .= '</' . /*strtolower($tag)*/ $tag . '>';
    }
}