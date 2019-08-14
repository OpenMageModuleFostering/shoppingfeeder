<?php

class ShoppingFeeder_Service_Controller_FrontAuth extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        parent::preDispatch();

//DEBUG
//        return $this;

        //check Auth
        if (!function_exists('getallheaders'))
        {
            function getallheaders()
            {
                $headers = '';
                foreach ($_SERVER as $name => $value)
                {
                    if (substr($name, 0, 5) == 'HTTP_')
                    {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }
                return $headers;
            }
        }

        $headers = getallheaders();

        /** @var ShoppingFeeder_Service_Model_Auth $authModel */
        $authModel = Mage::getModel('shoppingfeeder_service/auth');

        $authResult = $authModel->auth(
            $headers,
            $this->getRequest()->getScheme(),
            $this->getRequest()->getMethod()
        );

        if ($authResult !== true)
        {
            $responseData = array(
                'status' => 'fail',
                'data' => array (
                    'message' => $authResult
                )
            );

            header('Content-type: application/json; charset=UTF-8');
            echo json_encode($responseData);
            exit();
        }

        return $this;
    }
}