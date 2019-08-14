<?php

//IMPORTANT - "Controller" directory is not autoloaded
require_once(Mage::getModuleDir('Controller','ShoppingFeeder_Service').DS.'Controller'.DS.'FrontAuth.php');

class ShoppingFeeder_Service_VersionController extends Mage_Core_Controller_Front_Action
{
    protected static $_version = '1.3.7';

    public function indexAction()
    {
        $responseData = array(
            'status' => 'success',
            'data' => array(
                'version' => self::$_version
            )
        );

        header('Content-type: application/json; charset=UTF-8');
        echo json_encode($responseData);
        exit();
    }
}