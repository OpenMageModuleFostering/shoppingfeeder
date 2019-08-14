<?php
/**
 * Admin grid container
 *
 * @author ShoppingFeeder
 */
class ShoppingFeeder_Fbtrack_Block_Adminhtml_Service extends
    Mage_adminhtml_Block_Widget_Grid_Container
{
    /**
     * Block constructor
     */
    public function __construct()
    {
        $this->_blockGroup = 'shoppingfeeder_fbtrack';
        $this->_controller = 'adminhtml_service';
        $this->_headerText = Mage::helper('shoppingfeeder_fbtrack')->__('Manage Service');
    }
}