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
 * Class for an extension interface.
 *
 * @category   Psop
 * @package    Psop_Extension
 * @subpackage Abstract
 * @copyright  Copyright (c) 2008 MaakSite.nl Productions. (http://productions.maaksite.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php     New BSD License
 */
abstract class Psop_Extension_Abstract
{
	
	/**
     * User-provided configuration
     *
     * @var array
     */
    protected $_config = array();
    
    /**
     * Constructor.
     *
     * $config is an array of key/value pairs or an instance of Zend_Config
     * containing configuration options.
     *
     * @param  array|Zend_Config $config An array or instance of Zend_Config having configuration data
     * @throws Psop_Psop_Exception
     */
    public function __construct($config = array())
    {
        /*
         * Verify that adapter parameters are in an array.
         */
        if (!is_array($config)) {
            /*
             * Convert Zend_Config argument to a plain array.
             */
            if ($config instanceof Zend_Config) {
                $config = $config->toArray();
            } else {
                /**
                 * @see Psop_Psop_Exception
                 */
                require_once 'Psop/Psop/Exception.php';
                throw new Psop_Psop_Exception('Extension parameters must be in an array or a Zend_Config object');
            }
        }

        $this->_config  = array_merge($this->_config, $config);
    }
	
	/**
	 * Parse content of file
	 * 
	 * @param string $input
	 * @return string
	 */
	abstract public function parse($input);
}
