<?php
class ShoppingFeeder_Service_Model_Observer extends Varien_Object
{
    const SF_URL = 'https://www.shoppingfeeder.com/webhook/magento-orders/';
    //const SF_URL = 'http://dev.shoppingfeeder.com/webhook/magento-orders/';

    public function salesOrderPlaceAfter($observer)
    {
        try{
            //if order tracking is set
            //$sfEnabled = Mage::getStoreConfig('shoppingfeeder/service/enable');
            $sfEnabled = true;
            $sfTracking = Mage::getStoreConfig('shoppingfeeder/service/tracking');
            if ($sfEnabled && $sfTracking)
            {
                /* @var Mage_Sales_Model_Order $order */
                $order = $observer->getEvent()->getOrder();

                Mage::log('salesOrderPlaceAfter Order ID: '.$order->getRealOrderId());

                $this->_notifyShoppingFeeder($order);
            }
        }
        catch (Exception $e)
        {
            Mage::log('_notifyShoppingFeeder Order ID: '.$order->getRealOrderId().' FAILED! Message: '.$e->getMessage());
        }
    }

    public function productView($observer)
    {
        try{
            //here we're going to create the referral cookie for the visitor if they came from ShoppingFeeder
            if (isset($_GET['SFDRREF']))
            {
                setcookie('SFDRREF', $_GET['SFDRREF'], time() + (60*60*24*30), '/');
                $_COOKIE['SFDRREF']= $_GET['SFDRREF'];
            }
        }
        catch (Exception $e)
        {

        }
    }

    protected function _notifyShoppingFeeder(Mage_Sales_Model_Order $order)
    {
        Mage::log('_notifyShoppingFeeder Order ID: '.$order->getRealOrderId());

        //get API key value from admin settings
        $apiKey = $sfTracking = Mage::getStoreConfig('shoppingfeeder/service/apikey');

        $http = new Zend_Http_Client(self::SF_URL);

        $http->setHeaders('X-SFApiKey', $apiKey);

        $data = $order->toArray();
        foreach ($order->getAllItems() as $lineItem)
        {
            $data['line_items'][] = $lineItem->toArray();
        }
        $data['landing_site_ref'] = isset($_COOKIE['SFDRREF']) ? $_COOKIE['SFDRREF'] : '';

        $http->setRawData(Mage::helper('core')->jsonEncode($data));
        $http->request(Zend_Http_Client::POST);
    }
}