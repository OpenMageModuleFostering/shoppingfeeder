<?php

class ShoppingFeeder_Service_Model_Orders extends Mage_Core_Model_Abstract
{
    public function __construct()
    {
        $this->_init('shoppingfeeder_service/orders');
    }

    private function getOrderInfo(Mage_Sales_Model_Order $order)
    {
        $data = array();

        $orderData = $order->getData();

        //normalise order data for ShoppingFeeder
        $data['order_id'] = $orderData['entity_id'];
        $data['order_date'] = $orderData['created_at'];
        $data['store_order_number'] = $orderData['increment_id'];
        $data['store_order_user_id'] = $orderData['customer_id'];
        $data['store_order_currency'] = $orderData['order_currency_code'];
        $data['store_total_price'] = $orderData['grand_total'];
        $data['store_total_line_items_price'] = $orderData['subtotal_incl_tax'];
        $data['store_total_tax'] = $orderData['tax_amount'];
        $data['store_order_total_discount'] = $orderData['discount_amount'];
        $data['store_shipping_price'] = $orderData['shipping_incl_tax'];

        $lineItems = array();
        foreach($order->getAllItems() as $item)
        {
            $lineItems[] = $item->toArray();
        }
        $data['line_items'] = $lineItems;

        return $data;
    }

    public function getItems($page = null, $numPerPage = 1000)
    {
        /* @var Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection */
        $collection = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status', Mage_Sales_Model_Order::STATE_COMPLETE);

        if (!is_null($page))
        {
            $offset = ($page * $numPerPage) - $numPerPage;
            $orderIds = $collection->getAllIds($numPerPage, $offset);
        }
        else
        {
            $orderIds = $collection->getAllIds();
        }

        $orders = array();
        /* @var Mage_Sales_Model_Order $order */
        foreach ($orderIds as $orderId)
        {
            $order = Mage::getModel('sales/order')->load($orderId);

            $orders[$order->getId()] = $this->getOrderInfo($order);
        }
        return $orders;
    }

    public function getItem($itemId)
    {
        $orders = array();

        $order = Mage::getModel('sales/order')->load($itemId);
        $orders[$order->getId()] = $this->getOrderInfo($order);

        return $orders;
    }
}