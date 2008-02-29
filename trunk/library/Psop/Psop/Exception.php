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
 * Zend_Exception
 */
require_once 'Zend/Exception.php';

/**
 * @category   Psop
 * @package    Psop_Psop
 * @copyright  Copyright (c) 2008 MaakSite.nl Productions. (http://productions.maaksite.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php     New BSD License
 */
class Psop_Psop_Exception extends Zend_Exception
{
    public function __construct($message = null, Exception $e = null)
    {
        parent::__construct($message);
    }

}
