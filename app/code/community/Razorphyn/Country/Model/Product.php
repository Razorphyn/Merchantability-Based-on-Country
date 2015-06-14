<?php
 
class Razorphyn_Country_Model_Product extends Mage_Core_Model_Abstract{
    protected function _construct(){
		parent::_construct();
		$this->_isPkAutoIncrement=false;
        $this->_init('country/product');
    }
}