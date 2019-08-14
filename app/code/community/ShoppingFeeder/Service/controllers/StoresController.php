<?php

//IMPORTANT - "Controller" directory is not autoloaded
require_once(Mage::getModuleDir('Controller','ShoppingFeeder_Service').DS.'Controller'.DS.'FrontAuth.php');

class ShoppingFeeder_Service_StoresController extends ShoppingFeeder_Service_Controller_FrontAuth
{
    public function indexAction()
    {
        /** @var $websiteCollection Mage_Core_Model_Store */
        $storeCollection = Mage::getModel('core/store')->getCollection();

        $stores = array();
        foreach ($storeCollection as $store) {
            /** @var $store Mage_Core_Model_Store */
            $store->initConfigCache();

            $stores[$store->getCode()] = $store->getName();
        }

        $responseData = array(
            'status' => 'success',
            'data' => array(
                'stores' => $stores
            )
        );

        header('Content-type: application/json; charset=UTF-8');
        echo json_encode($responseData);
        exit();
    }
}