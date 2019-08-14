<?php
class ShoppingFeeder_Fbtrack_Model_Observer extends Varien_Object
{
    public function salesOrderPlaceAfter($observer)
    {
        try{
            $fbTracking = Mage::getStoreConfig('shoppingfeeder_fbtrack/fb_track_config/fb_track');
            if (!is_null($fbTracking) && $fbTracking)
            {
                /* @var Mage_Sales_Model_Order $order */
                $order = $observer->getEvent()->getOrder();

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
                Mage::getModel('core/session')->setOrderForJsTrackingShoppingFeederFbtrack($orderInfo);
            }
        }
        catch (Exception $e)
        {
            Mage::log('_notifyShoppingFeeder Order ID: '.$order->getRealOrderId().' FAILED! Message: '.$e->getMessage());
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function generateBlocksAfter($observer)
    {
        try{
            $fbTracking = Mage::getStoreConfig('shoppingfeeder_fbtrack/fb_track_config/fb_track');
            if (!is_null($fbTracking) && $fbTracking)
            {
                $actionName = $observer->getEvent()->getAction()->getFullActionName();
    //            var_dump($actionName);
    //            exit();
                $block = Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('shoppingfeeder_fbtrack_tracking_fb');

                if ($actionName == 'catalog_product_view' || $actionName == 'checkout_cart_index')
                {
                    $product = Mage::registry('current_product');
                    if (is_null($product))
                    {
                        $productId = Mage::getSingleton('core/session')->getProductToShoppingCartShoppingFeederFbtrack();
                        $product = Mage::getModel('catalog/product')->load($productId);
                    }

                    //if we still don't have a product, don't do anything
                    if (is_null($product))
                    {
                        return;
                    }

                    $pathToUse = '';

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
                        if (!is_null($product) && is_object($product))
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
                            Mage::getModel('core/session')->unsProductToShoppingCartShoppingFeederFbtrack();
                        }
                    }
                }


                elseif ($actionName == 'checkout_onepage_success' || $actionName == 'checkout_multishipping_success')
                {
                    Mage::log('After template action: '.$actionName);
                    $orderInfo = Mage::getModel('core/session')->getOrderForJsTrackingShoppingFeederFbtrack();
                    if (!is_null($orderInfo) && $block)
                    {
                        $block->setData('order', $orderInfo);
                        $block->setData('action_type', 'Purchase');
                        Mage::getModel('core/session')->unsOrderForJsTrackingShoppingFeederFbtrack();
                    }
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
            Mage::getModel('core/session')->setProductToShoppingCartShoppingFeederFbtrack(Mage::app()->getRequest()->getParam('product', 0));
        }
        catch (Exception $e) {

        }
    }
}