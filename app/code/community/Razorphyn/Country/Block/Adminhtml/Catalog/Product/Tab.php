<?php
 
class Razorphyn_Country_Block_Adminhtml_Catalog_Product_Tab extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface {

    public function _construct(){
		parent::_construct();
		$this->setTemplate('country/catalog/product/tab.phtml');
	}

    public function getTabLabel(){
		return $this->__('Country Limiter');
	}
     
    public function getTabTitle(){
		return $this->__('Click here to view your custom tab content');
	}

	public function canShowTab(){
		return true;
	}

    public function isHidden(){
		return false;
	}
}