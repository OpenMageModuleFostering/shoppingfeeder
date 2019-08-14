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

                //Mage::log('salesOrderPlaceAfter Order ID: '.$order->getRealOrderId());

                //set the order for JS tracking code
                $orderItems = array();
                foreach ($order->getAllItems() as $item)
                {
                    $orderItems[] = '\''.$item->getProductId().'\'';
                }
                $orderInfo = array(
                    'items' => $orderItems,
                    'value' => $order->getGrandTotal()
                );
                Mage::getModel('core/session')->setOrderForJsTracking($orderInfo);

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

    /**
     * @param Varien_Event_Observer $observer
     */
    public function generateBlocksAfter($observer)
    {
        try{
            $actionName = $observer->getEvent()->getAction()->getFullActionName();
//            var_dump($actionName);
//            exit();
            $block = Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('shoppingfeeder_service_tracking_fb');

            if ($actionName == 'catalog_product_view' || $actionName == 'checkout_cart_index')
            {
                $product = Mage::registry('current_product');
                if (is_null($product))
                {
                    $product = Mage::getModel('core/session')->getProductToShoppingCart();
                }

                //if we still don't have a product, don't do anything
                if (is_null($product))
                {
                    return;
                }

                $category = Mage::registry('current_category');
                $currentCategoryPath = null;
                if (!is_null($category))
                {
                    $currentCategoryPath = $category->getPath();
                }

                $startTime = microtime(true);

                $storeRootCategoryId = Mage::app()->getStore()->getRootCategoryId();
                $storeRootCategoryName = Mage::getModel('catalog/category')->load($storeRootCategoryId)->getName();
                if (is_null($currentCategoryPath))
                {
                    /** @var Mage_Catalog_Model_Resource_Category_Collection $categoryCollection */
                    $categoryCollection = $product->getCategoryCollection()->addAttributeToSelect('name');

                    $depth = 0;
                    foreach($categoryCollection as $cat1){
                        $pathIds = explode('/', $cat1->getPath());
                        unset($pathIds[0]);

                        $collection = Mage::getModel('catalog/category')->getCollection()
                            ->setStoreId(Mage::app()->getStore()->getId())
                            ->addAttributeToSelect('name')
                            ->addAttributeToSelect('is_active')
                            ->addFieldToFilter('entity_id', array('in' => $pathIds));

                        $pathByName = array();
                        /** @var Mage_Catalog_Model_Category $cat */
                        foreach($collection as $cat){
                            if ($cat->getName() != $storeRootCategoryName)
                            {
                                $pathByName[] = $cat->getName();
                            }
                        }

                        //take the longest (generally more detailed) path
                        $thisDepth = count($pathByName);
                        if ($thisDepth > $depth)
                        {
                            $depth = $thisDepth;
                            $pathToUse = implode(' > ', $pathByName);
                        }
                    }
                }
                else
                {
                    $pathIds = explode('/', $currentCategoryPath);
                    unset($pathIds[0]);

                    $collection = Mage::getModel('catalog/category')->getCollection()
                        ->setStoreId(Mage::app()->getStore()->getId())
                        ->addAttributeToSelect('name')
                        ->addAttributeToSelect('is_active')
                        ->addFieldToFilter('entity_id', array('in' => $pathIds));

                    $pathByName = array();
                    /** @var Mage_Catalog_Model_Category $cat */
                    foreach($collection as $cat){
                        if ($cat->getName() != $storeRootCategoryName)
                        {
                            $pathByName[] = $cat->getName();
                        }
                    }
                    $pathToUse = implode(' > ', $pathByName);
                }

                if ($product)
                {
                    $block->setData('product', $product);
                    $block->setData('category', $pathToUse);

                    if ($actionName == 'catalog_product_view')
                    {
                        $block->setData('action_type', 'ViewContent');
                    }
                    elseif ($actionName == 'checkout_cart_index')
                    {
                        $block->setData('action_type', 'AddToCart');
                        //reset the cart product
                        Mage::getModel('core/session')->setProductToShoppingCart(null);
                    }
                }
            }


            elseif ($actionName == 'checkout_onepage_success' || $actionName == 'checkout_multishipping_success')
            {
                Mage::log('After template action: '.$actionName);
                $orderInfo = Mage::getModel('core/session')->getOrderForJsTracking();
                if (!is_null($orderInfo) && $block)
                {
                    $block->setData('order', $orderInfo);
                    $block->setData('action_type', 'Purchase');
                    Mage::getModel('core/session')->setOrderForJsTracking(null);
                }
            }
        }
        catch (Exception $e)
        {

        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function setAddToCartProduct($observer)
    {
        try{

            //add the cart product to the session
            $product = Mage::getModel('catalog/product')
                ->load(Mage::app()->getRequest()->getParam('product', 0));

            Mage::getModel('core/session')->setProductToShoppingCart($product);
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