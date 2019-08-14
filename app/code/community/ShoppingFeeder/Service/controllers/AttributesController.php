<?php

//IMPORTANT - "Controller" directory is not autoloaded
require_once(Mage::getModuleDir('Controller','ShoppingFeeder_Service').DS.'Controller'.DS.'FrontAuth.php');

class ShoppingFeeder_Service_AttributesController extends ShoppingFeeder_Service_Controller_FrontAuth
{
    public function indexAction()
    {
        set_time_limit(0);

        $internalAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->getItems();

        $attributes = array();
        foreach ($internalAttributes as $attribute){
            $attributes[$attribute->getAttributecode()] = $attribute->getFrontendLabel();
        }

        $responseData = array(
            'status' => 'success',
            'data' => array(
                'attributes' => $attributes
            )
        );

        header('Content-type: application/json; charset=UTF-8');
        echo json_encode($responseData);
        exit();
    }
}