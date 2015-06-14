<?php
	/*
	 * Find a better way to check if it's AJAX, current method can be spoofed
	 *
	 * Try to implement: http://abhinavsingh.com/web-security-using-crumbs-to-protect-your-php-api-ajax-call-from-cross-site-request-forgery-csrfxsrf-and-other-vulnerabilities/
	 */
	class Razorphyn_Country_AjaxController extends Mage_Core_Controller_Front_Action {

		public function updateCountryAction(){//to check, is this complete?
			if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
				
				$country = $this->getRequest()->getParam('country');
				$category_id = $this->getRequest()->getParam('category_id');
				$product_id = $this->getRequest()->getParam('product_id');
				
				
				$countryList = Mage::getResourceModel('directory/country_collection')->loadData()->toOptionArray(false);

				foreach($_countries as $_country){
					if($_country['label']== $country ){
						$jsonData = json_encode(array(0=>'e',1=>Mage::helper('country')->__("Invalid Country Code")));
						$this->getResponse()->setHeader('Content-type', 'application/json');
						$this->getResponse()->setBody($jsonData);
						die();
					}
				}

				Mage::getSingleton('core/session')->setRazorphynCountry($country);
				
				$current=Mage::app()->getFrontController()->getRequest()->getControllerName();
				$stored = json_decode(Mage::getStoreConfig('razorphyn/country/buttons'));
				$theme=trim(Mage::getSingleton('core/design_package')->getPackageName());
				
				if($current=='cms'){
					$jsonData = json_encode(array(0=>'refresh'));
					$this->getResponse()->setHeader('Content-type', 'application/json');
					$this->getResponse()->setBody($jsonData);
					die();
				}
				else if($current=='category' && is_numeric($category_id)){
					
					$category = Mage::getModel('catalog/category')->load($category_id);
					$collection = $category->getProductCollection();
					Mage::getModel('catalog/layer')->prepareProductCollection($collection);
					
					$productsId= array();
					foreach ($collection as $res) {
						$productsId[]=$res->getId();
					}

					$collection = Mage::getMode('country/product')->getCollection()
																   ->addFieldToFilter('product_id',array('in' => $productsId) )
																   ->addFieldToFilter('active',1);

					$removeProduct= array();
					foreach( $collection as $res){
						if(($res->allowed==0 && strpos($res->country, $country) !== false) || ($res->allowed==1 && strpos($res->country, $country) === false)){
							$removeProduct[]=$res->product_id;
						}
					}
					
					$response=array(
									 0 => (count($removeProduct)>0)? true:false,
									 1 => false,
									 2 => $stored[$theme]['isOnClick'],
									 3 => $removeProduct,
									 4 => $stored[$theme]['class'],
									 5 => Mage::helper('country')->__("This product isn't available in your country.")
									);

					$jsonData = json_encode($response);
					$this->getResponse()->setHeader('Content-type', 'application/json');
					$this->getResponse()->setBody($jsonData);
				}
				else if($current=='product' && $product_id){
					$collection = Mage::getMode('country/product')->getCollection()
																   ->addFieldToFilter('product_id',array('in' => $productsId) )
																   ->addFieldToFilter('active',1);
					$countries= $res->country;
					$removeProduct= array();
					foreach( $collection as $res){
						if(($res->allowed==0 && strpos($countries, $country) !== false) || ($res->allowed==1 && strpos($countries, $country) === false)){
							$removeProduct[]=$res->product_id;
						}
					}

					$response=array(
									 0 =>(count($removeProduct)>0)? true:false,
									 1 => true,
									 2 => $stored[$theme]['isOnClick'],
									 3 => $removeProduct,
									 4 => $stored[$theme]['class'],
									 5 => Mage::helper('country')->__("This product isn't available in your country.")
									);
					$jsonData = json_encode($response);
					$this->getResponse()->setHeader('Content-type', 'application/json');
					$this->getResponse()->setBody($jsonData);
				}
				else{
					$response=array(
									 0 => false
									);
					$jsonData = json_encode($response);
					$this->getResponse()->setHeader('Content-type', 'application/json');
					$this->getResponse()->setBody($jsonData);
				}
			}
			else{
				echo Mage::helper('country')->__("Unauthorized access");
				exit();
			}
		}

		public function saveButtonAction(){//seems ok
			if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && Mage::helper('country/data')->isAdmin()) {
				if(Mage::helper('country/data')->isAdmin()){
					$stored = json_decode(Mage::getStoreConfig('razorphyn/country/buttons'));

					$theme 		= trim($this->getRequest()->getParam('theme'));
					$btClass 	= '.'.str_replace(' ','.',trim($this->getRequest()->getParam('btClass')));
					$isOnClick  = ($this->getRequest()->getParam('isOnClick')==true)? true:false;
					$isProdView  = ($this->getRequest()->getParam('productView')==1)? true:false;
					$nodeName 	= trim($this->getRequest()->getParam('nodeName'));
					
					if(!preg_match('/^[a-zA-Z0-9]{1,}$/',$nodeName)){
						$jsonData = json_encode(array(Mage::helper('country')->__("Invalid Node Name")));
						$this->getResponse()->setHeader('Content-type', 'application/json');
						$this->getResponse()->setBody($jsonData);
						die();
					}
					if($isProdView===true){
						$stored->$theme->product->nodeName	= $nodeName;
						$stored->$theme->product->eClass	= $btClass;
						$stored->$theme->product->isOnClick	= $isOnClick;
					}
					else{
						$stored->$theme->category->nodeName	 = $nodeName;
						$stored->$theme->category->eClass	 = $btClass;
						$stored->$theme->category->isOnClick = $isOnClick;
					}
					Mage::getModel('core/config')->saveConfig('razorphyn/country/buttons', json_encode($stored));
					$jsonData = json_encode(array(true,Mage::helper('country')->__("Saved")));
					$this->getResponse()->setHeader('Content-type', 'application/json');
					$this->getResponse()->setBody($jsonData);
				}
			}
		}
	}
?>