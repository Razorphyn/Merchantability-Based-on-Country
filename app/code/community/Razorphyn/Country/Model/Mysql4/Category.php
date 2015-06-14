<?php
 
class Razorphyn_Country_Model_Mysql4_Category extends Mage_Core_Model_Mysql4_Abstract{
    protected function _construct(){
		$this->_isPkAutoIncrement=false;
        $this->_init('country/category', 'category_id');
    }
}