<?php
	class Razorphyn_Country_Model_Mysql4_Category_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
		public function _construct(){
			$this->_isPkAutoIncrement=false;
			$this->_init('country/category');
		}
	}