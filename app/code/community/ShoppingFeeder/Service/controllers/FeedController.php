<?php

//IMPORTANT - "Controller" directory is not autoloaded
require_once(Mage::getModuleDir('Controller','ShoppingFeeder_Service').DS.'Controller'.DS.'FrontAuth.php');

class ShoppingFeeder_Service_FeedController extends ShoppingFeeder_Service_Controller_FrontAuth
{
    public function indexAction()
    {
        set_time_limit(0);

        /** @var ShoppingFeeder_Service_Model_Offers $offersModel */
        $offersModel = Mage::getSingleton('shoppingfeeder_service/offers');

        $page = $this->getRequest()->getParam('page', null);
        $numPerPage = $this->getRequest()->getParam('num_per_page', 1000);
        $offerId = $this->getRequest()->getParam('offer_id', null);
        $lastUpdate = $this->getRequest()->getParam('last_update', null);
        $store = $this->getRequest()->getParam('store', null);

        /**
         * For per-store system
         */
        if (!is_null($store))
        {
            Mage::app()->setCurrentStore($store);
        }
        else
        {
            $mageApp = Mage::app();
            $mageApp->setCurrentStore($mageApp::DISTRO_STORE_CODE);
        }

        if (is_null($offerId))
        {
            $offers = $offersModel->getItems($page, $numPerPage, $lastUpdate, $store);
        }
        else
        {
            $offers = $offersModel->getItem($offerId, $store);
        }

        $responseData = array(
            'status' => 'success',
            'data' => array(
                'page' => $page,
                'num_per_page' => $numPerPage,
                'offers' => $offers,
                'store' => $store
            )
        );

        header('Content-type: application/json; charset=UTF-8');
        echo json_encode($responseData);
        exit();
    }

    /** Old XML version */
    /*
    public function indexAction()
    {
        set_time_limit(0);

        $offersModel = Mage::getSingleton('shoppingfeeder_service/offers');

        $page = $this->getRequest()->getParam('page', null);
        $numPerPage = $this->getRequest()->getParam('num_per_page', 1000);

        $offers = $offersModel->getItems($page, $numPerPage);
        header('Content-type: text/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo "\n".'<offers>';
        foreach ($offers as $offer) {
            echo "\n".'<offer>';
            echo "\n".'<category><![CDATA['.$offer['category'].']]></category>';
            echo "\n".'<title><![CDATA['.$offer['title'].']]></title>';
            echo "\n".'<brand><![CDATA['.$offer['manufacturer'].']]></brand>';
            echo "\n".'<manufacturer><![CDATA['.$offer['manufacturer'].']]></manufacturer>';
            echo "\n".'<mpn><![CDATA['.$offer['mpn'].']]></mpn>';
            echo "\n".'<sku><![CDATA['.$offer['sku'].']]></sku>';
            echo "\n".'<gtin>'.$offer['gtin'].'</gtin>';
            echo "\n".'<weight>'.$offer['weight'].'</weight>';
            echo "\n".'<internal_id><![CDATA['.$offer['internal_id'].']]></internal_id>';
            echo "\n".'<internal_variant_id><![CDATA['.$offer['internal_variant_id'].']]></internal_variant_id>';
            echo "\n".'<description><![CDATA['.$offer['description'].']]></description>';
            echo "\n".'<price>'.$offer['price'].'</price>';
            echo "\n".'<sale_price>'.$offer['sale_price'].'</sale_price>';
            echo "\n".'<sale_price_effective_date>'.$offer['sale_price_effective_date'].'</sale_price_effective_date>';
            echo "\n".'<delivery_cost>'.$offer['delivery_cost'].'</delivery_cost>';
            echo "\n".'<tax>'.$offer['tax'].'</tax>';
            echo "\n".'<url><![CDATA['.$offer['url'].']]></url>';
            echo "\n".'<image_url><![CDATA['.$offer['image_url'].']]></image_url>';
            echo "\n".'<quantity>'.$offer['quantity'].'</quantity>';
            echo "\n".'<condition>'.$offer['condition'].'</condition>';
            echo "\n".'<availability>'.$offer['availability'].'</availability>';
            echo "\n".'<availability_date>'.$offer['availability_date'].'</availability_date>';
            foreach ($offer['attributes'] as $attributeName => $attributeValue) {
                echo "\n".'                <attribute name="'.htmlspecialchars($attributeName).'"><![CDATA['.$attributeValue.']]></attribute>';
            }
            foreach ($offer['extra_images'] as $imageUrl){
                echo "\n".'                <additional_image_link><![CDATA['.$imageUrl.']]></additional_image_link>';
            }
            echo "\n".'</offer>';

            flush();
            ob_flush();
        }
        echo "\n".'</offers>';

        exit();
    }
    */
}