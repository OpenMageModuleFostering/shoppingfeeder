<?php

//IMPORTANT - "Controller" directory is not autoloaded
require_once(Mage::getModuleDir('Controller','ShoppingFeeder_Service').DS.'Controller'.DS.'FrontAuth.php');

class ShoppingFeeder_Service_StockController extends ShoppingFeeder_Service_Controller_FrontAuth
{
    public function indexAction()
    {
        set_time_limit(0);

        $offerId = $this->getRequest()->getParam('offer_id', null);
        $store = $this->getRequest()->getParam('store', null);

        /** @var ShoppingFeeder_Service_Model_Offers $offersModel */
        $offersModel = Mage::getSingleton('shoppingfeeder_service/offers');

        $stockQuantity = $offersModel->getStockQuantity($offerId, $store);

        $responseData = array(
            'status' => 'success',
            'data' => array(
                'stock_quantity' => (int)$stockQuantity
            )
        );

        header('Content-type: application/json; charset=UTF-8');
        echo json_encode($responseData);
        exit();
    }
}