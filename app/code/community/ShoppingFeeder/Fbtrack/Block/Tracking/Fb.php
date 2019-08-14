<?php
/**
 *
 * ShoppingFeeder XML generation code
 *
 * @category   ShoppingFeeder
 * @package    ShoppingFeeder_Fbtrack
 * @copyright  Copyright (c) 2014 Kevin Tucker (http://www.shoppingfeeder.com)
 *
 */

class ShoppingFeeder_Fbtrack_Block_Tracking_Fb extends Mage_Core_Block_Template
{
    protected function _toHtml() {
        $trackFb = Mage::getStoreConfig('shoppingfeeder_fbtrack/fb_track_config/fb_track');

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
        $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();

        $finalPrice = $this->getData('product')->getFinalPrice();
        if ($currentCurrencyCode && ($currentCurrencyCode != $baseCurrencyCode)) {
            $finalPrice = Mage::helper('directory')->currencyConvert($finalPrice, $baseCurrencyCode, $currentCurrencyCode);
        }

        return number_format($finalPrice, 2);
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
        return Mage::getStoreConfig('shoppingfeeder_fbtrack/fb_track_config/fb_track_code');
    }
}