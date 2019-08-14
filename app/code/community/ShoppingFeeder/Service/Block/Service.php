<?php
/**
 *
 * ShoppingFeeder XML generation code
 *
 * @category   ShoppingFeeder
 * @package    ShoppingFeeder_Service
 * @copyright  Copyright (c) 2014 Kevin Tucker (http://www.shoppingfeeder.com)
 *
 */

class ShoppingFeeder_Service_Block_Service extends Mage_Core_Block_Template
{

    /**
     * Block constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the offers
     *
     * @return collection|null
     */
    public function getOffers()
    {
        return $this->_getData('offers');
    }

}