<?php
/**
 * Admin grid container
 *
 * @author ShoppingFeeder
 */
class ShoppingFeeder_Service_Block_Adminhtml_Service extends
    Mage_adminhtml_Block_Widget_Grid_Container
{
    /**
     * Block constructor
     */
    public function __construct()
    {
        $this->_blockGroup = 'shoppingfeeder_service';
        $this->_controller = 'adminhtml_service';
        $this->_headerText = Mage::helper('shoppingfeeder_service')->__('Manage Service');
        parent::__construct();
//        if (Mage::helper('shoppingfeeder_service/Admin')->isActionAllowed('save'))
//        {
//            $this->_updateButton('add', 'label',
//                Mage::helper('shoppingfeeder_service')->__('Add New News'));
//        } else {
//            $this->_removeButton('add');
//        }
//        $this->addButton(
//            'news_flush_images_cache',
//            array(
//                'label' => Mage::helper('shoppingfeeder_service')->__('Flush Images Cache'),
//                'onclick' => 'setLocation(\'' . $this->getUrl('*/*/flush') . '\')',
//            )
//        );
    }
}