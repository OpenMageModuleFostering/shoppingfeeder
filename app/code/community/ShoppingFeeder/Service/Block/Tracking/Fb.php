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

class ShoppingFeeder_Service_Block_Tracking_Fb extends Mage_Core_Block_Template
{
    protected function _toHtml() {
        $trackFb = Mage::getStoreConfig('shoppingfeeder/fb_track_config/fb_track');

        if (!$trackFb) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * return the price of the product
     *
     * @return string
     */
    public function getProductPrice()
    {
        $price = $this->getData('product')->getPrice();
        $specialPrice = $this->getData('product')->getSpecialPrice();

        return number_format(((!is_null($specialPrice) && $specialPrice < $price) ? $specialPrice : $price), 2);
    }

    /**
     * Return the current currency code
     *
     * @return string
     */
    public function getCurrency()
    {
        return Mage::app()->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Return the FB pixel ID
     *
     * @return string
     */
    public function getPixelId()
    {
        return Mage::getStoreConfig('shoppingfeeder/fb_track_config/fb_track_code');
    }
}