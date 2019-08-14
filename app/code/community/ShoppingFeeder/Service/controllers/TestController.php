<?php

//IMPORTANT - "Controller" directory is not autoloaded
require_once(Mage::getModuleDir('Controller','ShoppingFeeder_Service').DS.'Controller'.DS.'FrontAuth.php');

class ShoppingFeeder_Service_TestController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
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

        $requiresSsl = false;

        try
        {
            /** @var ShoppingFeeder_Service_Model_Auth $authModel */
            $authModel = Mage::getModel('shoppingfeeder_service/auth');
            //check if this setup requires SSL
            $sslInFront = Mage::getStoreConfig('web/secure/use_in_frontend');
            $requiresSsl = ($sslInFront == null) ? false : $sslInFront;

            $apiKeys = $authModel->getApiKeys();

            if (!isset($apiKeys['api_key']) || empty($apiKeys['api_key']))
            {
                throw new Exception('API key not setup.');
            }

            if (!isset($apiKeys['api_secret']) || empty($apiKeys['api_secret']))
            {
                throw new Exception('API secret not setup.');
            }

            $headers = getallheaders();

            $authResult = $authModel->auth(
                $headers,
                $this->getRequest()->getScheme(),
                $this->getRequest()->getMethod()
            );

            if ($authResult === true)
            {
                set_time_limit(0);

                $responseData = array(
                    'status' => 'success',
                    'data' => array(
                        'message' => 'Authorization OK',
                        'requires_ssl' => $requiresSsl
                    )
                );
            }
            else
            {
                $responseData = array(
                    'status' => 'fail',
                    'data' => array (
                        'message' => 'Authorization failed: ['.$authResult.']',
                        'requires_ssl' => $requiresSsl
                    )
                );
            }
        }
        catch (Exception $e)
        {
            $responseData = array(
                'status' => 'fail',
                'data' => array (
                    'message' => $e->getMessage(),
                    'requires_ssl' => $requiresSsl
                )
            );
        }

        header('Content-type: application/json; charset=UTF-8');
        echo json_encode($responseData);
        exit();
    }

    public function debugAction()
    {
        if (function_exists('getallheaders'))
        {
            echo 'Function <b>getallheaders</b> <span style="color:green;">exists</span>'."<br>\n";
            try
            {
                $headers = getallheaders();
                echo 'Get headers succeeded: '.print_r($headers,true)."<br>\n";
            }
            catch (Exception $e)
            {
                echo 'Get headers failed: ['.$e->getMessage().']'."<br>\n";
            }
        }
        else
        {
            try
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
                echo 'Function <b>getallheaders</b> created'."<br>\n";

                try
                {
                    $headers = getallheaders();
                    echo 'Get headers succeeded: '.print_r($headers,true)."<br>\n";
                }
                catch (Exception $e)
                {
                    echo 'Get headers failed: ['.$e->getMessage().']'."<br>\n";
                }
            }
            catch (Exception $e)
            {
                echo 'Function <b>getallheaders</b> could not be created'."<br>\n";
            }
        }

        if (function_exists('hash_hmac'))
        {
            echo 'Function <b>hash_hmac</b> <span style="color:green;">exists</span>'."<br>\n";
        }
        else
        {
            echo 'Function <b>hash_hmac</b> <span style="color:red;">does not exist</span>'."<br>\n";
        }

        if (function_exists('mhash'))
        {
            echo 'Function <b>mhash</b> <span style="color:green;">exists</span>'."<br>\n";
        }
        else
        {
            echo 'Function <b>mhash</b> <span style="color:red;">does not exist</span>'."<br>\n";
        }

        try
        {
            /** @var ShoppingFeeder_Service_Model_Auth $authModel */
            $authModel = Mage::getModel('shoppingfeeder_service/auth');
            echo '$authModel successfully instantiated'."<br>\n";
        }
        catch (Exception $e)
        {
            echo '$authModel could not be instantiated: ['.$e->getMessage().']'."<br>\n";
        }


        if (isset($authModel))
        {
            try
            {
                $apiKeys = $authModel->getApiKeys();
                //hide from view
                unset($apiKeys['api_secret']);
                echo 'API Keys successfully fetched: '.print_r($apiKeys, true)."<br>\n";

                try
                {
                    $headers = getallheaders();
                    $authResult = $authModel->auth(
                        $headers,
                        $this->getRequest()->getScheme(),
                        $this->getRequest()->getMethod()
                    );
                    echo '$authresult successfully called: '.print_r($authResult,true)."<br>\n";
                }
                catch (Exception $e)
                {
                    echo '$authresult could not be called: ['.$e->getMessage().']'."<br>\n";
                }
            }
            catch (Exception $e)
            {
                echo 'API Keys could not be fetched: ['.$e->getMessage().']'."<br>\n";
            }
        }

        $offersModel = false;
        try {
            $offersModel = Mage::getSingleton('shoppingfeeder_service/offers');
            echo '$offersModel <span style="color:green;">successfully instantiated</span>'."<br>\n";
        }
        catch (Exception $e)
        {
            echo '$offersModel <span style="color:red;">could not</span> be instantiated: ['.$e->getMessage().']'."<br>\n";

        }

        $page = 1;
        $numPerPage = 1;
        $offers = false;
        if ($offersModel)
        {
            try {
                $page = 1;
                $numPerPage = 1;

                $offers = $offersModel->getItems($page, $numPerPage);
                echo 'Fetch 1 product successful'."<br>\n";
            }
            catch (Exception $e)
            {
                echo 'Fetch 1 product <span style="color:red;">NOT</span> successful: ['.$e->getMessage().']'."<br>\n";

                try {
                    echo 'Trying to get $collection'."<br>\n";
                    $collection = Mage::getModel('catalog/product')->getCollection()
                        ->addAttributeToSelect('*')
                        ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                        ->addAttributeToFilter('visibility', 4);
                    echo '$collection <span style="color:green;">successfully instantiated</span>'."<br>\n";
                }
                catch (Exception $e)
                {
                    echo '$collection <span style="color:red;">NOT</span> successful: ['.$e->getMessage().']'."<br>\n";
                }
            }
        }

        if ($offers)
        {
            try {
                $responseData = array(
                    'status' => 'success',
                    'data' => array(
                        'page' => $page,
                        'num_per_page' => $numPerPage,
                        'offers' => $offers
                    )
                );

                echo json_encode($responseData);
            }
            catch (Exception $e)
            {
                echo 'Could not output JSON: ['.$e->getMessage().']'."<br>\n";
            }
        }

        exit();
    }

    public function debug2Action()
    {
        echo("DEBUG-DEBUG");
        exit();
    }
}