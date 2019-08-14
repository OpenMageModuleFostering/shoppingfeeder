<?php

//IMPORTANT - "Controller" directory is not autoloaded
require_once(Mage::getModuleDir('Controller','ShoppingFeeder_Service').DS.'Controller'.DS.'FrontAuth.php');

class ShoppingFeeder_Service_OrdersController extends ShoppingFeeder_Service_Controller_FrontAuth
{
    public function indexAction()
    {
        set_time_limit(0);

        /** @var ShoppingFeeder_Service_Model_Orders $ordersModel */
        $ordersModel = Mage::getSingleton('shoppingfeeder_service/orders');

        $page = $this->getRequest()->getParam('page', null);
        $numPerPage = $this->getRequest()->getParam('num_per_page', 1000);
        $orderId = $this->getRequest()->getParam('order_id', null);
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
            $defaultStoreCode = Mage::app()
                ->getWebsite(true)
                ->getDefaultGroup()
                ->getDefaultStore()
                ->getCode();

            Mage::app()->setCurrentStore($defaultStoreCode);
        }

        if (is_null($orderId))
        {
            $orders = $ordersModel->getItems($page, $numPerPage, $store);
        }
        else
        {
            $orders = $ordersModel->getItem($orderId, $store);
        }

        $responseData = array(
            'status' => 'success',
            'data' => array(
                'page' => $page,
                'num_per_page' => $numPerPage,
                'orders' => $orders,
                'store' => $store
            )
        );

        header('Content-type: application/json; charset=UTF-8');
        echo json_encode($responseData);
        exit();
    }

//    public function createAction()
//    {
//        /** @var ShoppingFeeder_Service_Model_Orders $ordersModel */
//        $ordersModel = Mage::getSingleton('shoppingfeeder_service/orders');
//
//        if ($this->getRequest()->getMethod() == Zend_Http_Client::POST)
//        {
//            //create the order
//            $order = $ordersModel->create();
//
//
//            $responseData = array(
//                'status' => 'success',
//                'data' => array(
//                    'order' => $order
//                )
//            );
//        }
//        else
//        {
//            $responseData = array(
//                'status' => 'fail',
//                'data' => array(
//                    'message' => 'HTTP method incorrect, must be POST'
//                )
//            );
//        }
//
//        header('Content-type: application/json; charset=UTF-8');
//        echo json_encode($responseData);
//        exit();
//    }
}