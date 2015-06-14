<?php
  
class Razorphyn_Country_Model_Observer{

    /*
     * Flag to stop observer executing more than once
     *
     * @var static bool
     */
    static protected $_singletonFlag = false;
	
	/*
	 * Check if product is merchantable in that country, otherwise remove add to cart button
	 * Tested: simple product
	 * Working on: configurable product
	 * catalog_product_load_after
	 * @return boolean
	 */

	public function checkMerchantable(Varien_Event_Observer $observer){//seems ok - little fox has to be done to avoid config page
		$enConfig = Mage::getSingleton('core/session')->getRazorphynCountryConfig();
		//To do: don't go further if config page
		if(!self::isModuleEnabled() || $enConfig==1){
			if($enConfig==1)
				Mage::getSingleton('core/session')->unsRazorphynCountryConfig();
			return;
		}

		$block = $observer->getBlock();
		$visitorCountry = Mage::getSingleton('core/session')->getRazorphynCountry();
		
		if ($visitorCountry && $block instanceof Mage_Catalog_Block_Product_View) {//check
			$transport = $observer->getTransport();
			$html = $transport->getHtml();

			$stored = json_decode(Mage::getStoreConfig('razorphyn/country/buttons'));
			$theme=trim(Mage::getSingleton('core/design_package')->getPackageName());

			if(!isset($stored->$theme->product))
				return;

			$dom = new DOMDocument();
			$dom->loadHTML($html);

			$productId  = Mage::registry('current_product')->getId();
			$collection = Mage::getModel('country/product')->getCollection()
														   ->addFieldToFilter('product_id', $productId)
														   ->setPageSize(1)
														   ->setCurPage(1);
														   
			$res = $collection->getFirstItem();
			$countries= $res->country;
			
			if($res->active==1 && !empty($visitorCountry) && (($res->allowed==0 && strpos($countries, $visitorCountry) !== false) || ($res->allowed==1 && strpos($countries, $visitorCountry) === false))){
				$queryDom=($stored->$theme->product->isOnClick)?'//button[@class="'.trim(str_replace('.',' ',$stored->$theme->product->eClass)).'" and contains(@onclick,"/checkout/cart/add/")]':'//button[@class="'.trim(str_replace('.',' ',$stored->$theme->product->eClass)).'"]';
				
				$xpath  = new DOMXPath($dom);
				$results  = $xpath->query($queryDom);
				
				$newNode = $dom->createElement("p", Mage::helper('country')->__("This product isn't available in your country."));

				foreach ($results as $result) {
					while(strtolower($result->nodeName)!='div')
						$result=$result->parentNode;

					$result->parentNode->replaceChild($newNode, $result);
				}
				$html = $dom->saveHTML();
			}

			if( Mage::registry('current_product')->getTypeId()=='configurable'){ //to be checked
				//Retrieve all options ids
				$childIds = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($productId);
				
				//Retrieve rules to remove  for all options
				$collection = Mage::getModel('country/product')->getCollection()
															   ->addFieldToFilter('active', 1)
															   ->addFieldToFilter('product_id', $childIds)
															   ->limit(count($childIds));

				if($collection->length >0){
					//Create an array with the items that will be removed
					$removable=array();
					foreach($collection as $res){
						if(($res->allowed==0 && strpos($countries, $visitorCountry) !== false) || ($res->allowed==1 && strpos($countries, $visitorCountry) === false)){
							$removable[$res->product_id]=true;
						}
					}
					
					//Retrieve json with the product options (will be printed in the frontend
					$json = Mage::createBlock('Mage_Catalog_Block_Product_View')->setProduct($productId)->getJsonConfig();
					$arr=json_decode($json);
					//I would like to print all the removed options
					//each line is combination of removed options for the same product id
					//label1: option - label2: option - label3:option
					$removedOpt= array();
					//Loop through options
					foreach($arr['attribute'] as $element){
						foreach($element['options'] as &$option){
							foreach($option['products'] as &$productId){
								if(in_array($productId,$removable)){
									$removedOpt[$productId][]=$element['label'].': '.$option['label'];
									unset($productId);
								}
							}
							//Remove options if there is no associated product
							if(count($option) == 0)
								unset($option);
						}
					}
					
					//Create final string with removed options
					foreach($removedOpt as $o){
						$o=implode(' - ',$o);
					}
					$removedOpt=implode("\n",$removedOpt);
					
					//Ugh...ehm...replace the already printed json with the new one...
					preg_replace('/new\s*Product\.Config\((.*)\)/',$json,$html);
					Mage::getSingleton('core/session')->addNotice(Mage::helper('country')->__("The following options are not available in your country:")."\n".$removedOpt);
				}
			}

			$transport->setHtml($html);
		}
		else if ($block instanceof Mage_Catalog_Block_Category_View){//check
		
			$transport = $observer->getTransport();
			$html = $transport->getHtml();
			$stored = json_decode(Mage::getStoreConfig('razorphyn/country/buttons'));
			$theme=trim(Mage::getSingleton('core/design_package')->getPackageName());

			if(!isset($stored->$theme->category))
				return;
			
			$dom = new DOMDocument();
			$dom->loadHTML($html);
			$xpath  = new DOMXPath($dom);

			$newNode = $dom->createElement("p", Mage::helper('country')->__("This product isn't available in your country."));

			$elArray=array();
			$productsIds= array();
			
			if($stored->$theme->category->isOnClick == true){
				$queryDom='//button[@class="'.trim(str_replace('.',' ',$stored->$theme->category->eClass)).'" and contains(@onclick,"/checkout/cart/add/")]';

				$results  = $xpath->query($queryDom);
				
				
				
				foreach ($results as $result) {
					preg_match("/checkout\/cart\/add.+\/([0-9]+)\//",$result->getAttribute('onclick'),$currentProdId);
					$productsIds[]=$currentProdId[1];
					$elArray[$currentProdId[1]]=$result;
				}
			}
			else{
				$queryDom='//form[contains(@action,"/checkout/cart/add/")]//button[@class="'.trim(str_replace('.',' ',$stored->$theme->category->eClass)).'"]';
				
				$results  = $xpath->query($queryDom);
				
				foreach ($results as $result) {
					$form=$result->parentNode;
					while(strtolower($form->nodeName)!='form')
						$form=$form->parentNode;

					preg_match("/checkout\/cart\/add.+\/([0-9]+)\//",$form->getAttribute('action'),$currentProdId);
					if($currentProdId[0] && is_numeric($currentProdId[0])){
						$productsIds[]=$currentProdId[1];
						$elArray[$currentProdId[1]]=$result;
					}
				}
			}
			if(count($productsIds)>0){
				
				$collection = Mage::getModel('country/product')->getCollection()
															   ->addFieldToFilter('active', 1)
															   ->addFieldToFilter('product_id', array('in' => $productsIds));

				$country = Mage::getSingleton('core/session')->getRazorphynCountry;

				foreach($collection as $res){
					if($res->allowed && !empty($visitorCountry) && (($res->allowed==0 && strpos($res->country, $country) !== false) || ($res->allowed==1 && strpos($res->country, $country) === false))){
						$el=$elArray[$res->product_id];
						$el->parentNode->replaceChild($newNode, $el);
					}
				}
				$html = $dom->saveHTML();
			}
			$transport->setHtml($html);
		}
	}
		
	/**
     * Update cart on Address change
     * customer_address_save_after
     * @return boolean
     */

	public function updateCartAddress(Varien_Event_Observer $observer){//to do
		if(!self::isModuleEnabled()){
			return;
		}
		
		$customerAddressId =Mage::getSingleton('customer/session')->getCustomer()->getDefaultShipping();
		$customerCountryId= Mage::getSingleton('core/session')->setRazorphynCountry(Mage::getModel('customer/address')->load($customerAddressId)->getData('country_id'));
		
		$cart = Mage::getModel('checkout/cart');
		$quote = Mage::getModel('checkout/cart')->getQuote();
		$productsId= array();
		foreach ($quote->getAllItems() as $item) {
			$productsId[] = $item->getProduct()->getId();
		}
		if(count($productsId)==0)
			return;

		$collection = Mage::getModel('country/product')->getCollection()
														->addAttributeToFilter('active', 1)
														->addAttributeToFilter('product_id', array('in' => $productsId));
		
		foreach($collection as $res){
			if(($res->allowed==0 && strpos($countries, $country) !== false) || ($res->allowed==1 && strpos($countries, $country) === false)){
				$cart->getCart()->removeItem($res->getProductId())->save();
				//to do:Collect all the removed product name and send back
				$error=true;
			}
		}
		if($error){
			//To do: Add removed products name
			Mage::getSingleton('core/session')->addError(Mage::helper('country')->__("Sorry, some of the products you have added to the cart aren't available in your country"));
			exit();
		}
	}
	
	/**
     * Prevent adding prohibited products to cart
     * controller_action_predispatch_checkout_cart_add
     * @return boolean
     */

	public function checkProductOnAdd(Varien_Event_Observer $observer){//seems ok
		if(!self::isModuleEnabled()){
			return;
		}
		
		if($observer->getEvent()->getControllerAction()->getFullActionName() == "checkout_cart_add"){
			
			$productId  = Mage::app()->getRequest()->getParam('product');
			$collection = Mage::getModel('country/product')->getCollection()
														   ->addFieldToFilter('product_id', $productId);

			$country = Mage::getSingleton('core/session')->getRazorphynCountry;

			foreach($collection as $res){
				if(($res->allowed==0 && strpos($res->country, $country) !== false) || ($res->allowed==1 && strpos($res->country, $country) === false)){
					Mage::getSingleton('core/session')->addError(Mage::helper('country')->__("Sorry, this product isn't available in your country"));
					//NOW?
					$request = Mage::app()->getRequest();
					$refererUrl = $request->getServer('HTTP_REFERER');
					if ($url = $request->getParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_REFERER_URL)) {
						$refererUrl = $url;
					}
					if ($url = $request->getParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_BASE64_URL)) {
						$refererUrl = Mage::helper('core')->urlDecode($url);
					}
					if ($url = $request->getParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_URL_ENCODED)) {
						$refererUrl = Mage::helper('core')->urlDecode($url);
					}
					$refererUrl = Mage::helper('core')->escapeUrl($refererUrl);
					if (!self::_isUrlInternal($refererUrl)) {
						$refererUrl = Mage::app()->getStore()->getBaseUrl();
					}
					Mage::app()->getRequest()->setParam('return_url',$refererUrl);
					Mage::app()->getResponse()->setRedirect($refererUrl);
					Mage::app()->getResponse()->sendResponse();
					die();
				}
			}
		}
	}
	
	
	/**
     * Retrieve Customer Country
     * controller_front_init_before 
     * @return boolean
     */

	public function retrieveCountry(Varien_Event_Observer $observer){//to be cjecked
		if(!self::isModuleEnabled()){
			return;
		}
		$customerCountry=Mage::getSingleton('core/session')->getRazorphynCountry();
		if(empty($customerCountry)){
			
			//Check if it is an engine bot
			if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT'])) {
				Mage::getSingleton('core/session')->setRazorphynCountry(false);
				return;
			}

			$customerAddressId =Mage::getSingleton('customer/session')->getCustomer()->getDefaultShipping();
			if($customerAddressId){
				Mage::getSingleton('core/session')->setRazorphynCountry(Mage::getModel('customer/address')->load($customerAddressId)->getData('country_id'));
			}
			else{
				$ip=Mage::helper('core/http')->getRemoteAddr();
				$url='freegeoip.net/json/'.$ip;
				if(function_exists('curl_version')){//seems ok
					$ch = curl_init($url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_VERBOSE, 1);
					curl_setopt($ch, CURLOPT_HEADER, 1);
					$response = curl_exec($ch);
					list($header, $body) = explode("\r\n\r\n", $response, 2);

					$header=str_replace(PHP_EOL,"",$header);
					
					if(strpos($header,'HTTP/1.1 200 OK')!==false){
						$body=json_decode($body);
						Mage::getSingleton('core/session')->setRazorphynCountry($body->country_code);
					}
					else{
						Mage::getSingleton('core/session')->setRazorphynCountry(false);
					}
				}
				else{//to be checked
					$hedaers=get_headers ($url);
					if($hedaers[0]=='HTTP/1.1 200 OK'){
						$body=json_decode(file_get_contents($url));
						Mage::getSingleton('core/session')->setRazorphynCountry($body->country_code);
					}
					else{
						Mage::getSingleton('core/session')->setRazorphynCountry(false);
					}
				}
			}
		}
    }

	/*
	 * This method will run when a product is saved
	 *
	 * @param Varien_Event_Observer $observer
	 */
    public function saveProductAllowedCountry(Varien_Event_Observer $observer){//seems ok
		$product = $observer->getProduct();
		$productId = $product->getId();

		try {
			$active =  ($this->_getRequest()->getPost('limit_active')==1)? 1:0;
			$allowed =  ($this->_getRequest()->getPost('allowed')==1)? 1:0;
			$allowedCountry =  $this->_getRequest()->getPost('country');
			if(is_array($allowedCountry)){
				$allowedCountry=implode(',',$allowedCountry);
			}

			if(!empty($allowedCountry) && !preg_match('/[a-zA-Z]{0,4}+[,]{0,1}/',$allowedCountry)){
				Mage::log('Product id: '.$productId.'; Invalid Country Value: '.$allowedCountry, null, 'razorphyn_error.log');
				die();
			}
			else if(empty($allowedCountry)){
				$allowedCountry=null;
			}

			$data = array('product_id'=>$productId,'active'=>$active,'allowed'=>$allowed,'country'=>$allowedCountry);
			Mage::getModel('country/product')->load($productId, 'product_id')->setData($data)->save();
		}
		catch (Exception $e) {
			Mage::log('Product id: '.$productId.'; '.$e->getMessage(), null, 'razorphyn_error.log');
		}
    }
	
	/*
	 * Haven't developed this solution yet
	 * This method will run when a category is saved
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function saveCategoryAllowedCountry(Varien_Event_Observer $observer){//to do - useless
		$categoryId = $observer->getCategory()->getId();

		try {
			$active =  ($this->_getRequest()->getPost('limit_active')==1)? 1:0;
			$allowed =  ($this->_getRequest()->getPost('allowed')==0)? 0:1;
               $allowedCountry =  $this->_getRequest()->getPost('country');
			if(is_array($allowedCountry)){
				$allowedCountry=implode(',',$allowedCountry);
			}

			if(!empty($allowedCountry) && !preg_match('/[a-zA-Z]{0,4}+[,]{0,1}/',$allowedCountry)){
				Mage::log('Category id: '.$categoryId.'; Invalid Country Value', null, 'razorphyn_error.log');
				die();
			}
			else if(empty($allowedCountry)){
				$allowedCountry=null;
			}
				
			$data = array('category_id'=>$categoryId, 'active'=>$active,'allowed'=>$allowed,'country'=>$allowedCountry);
			Mage::getModel('country/category')->load($categoryId, 'category_id')->setData($data)->save();
		}
		catch (Exception $e) {
			Mage::log($e->getMessage(), null, 'razorphyn_error.log');
		}
	}

	/**
     * Prepare page to config the module: identify add to cart button for each theme
     * @return controller_action_layout_load_before
     */
    public function addConfigThemeJs(Varien_Event_Observer $observer){//seems ok
		$theme = Mage::app()->getRequest()->getParam('razorphyn_theme');

		if(!$theme || !self::isModuleEnabled() || !Mage::getStoreConfig('razorphyn/razorphyn_country_group/razorphyn_country_config_active',Mage::app()->getStore())){
			return;
		}
		
		Mage::getSingleton('core/session')->setRazorphynCountryConfig(true); 
		
		if(!Mage::helper('country/data')->isAdmin()){
			echo Mage::helper('country')->__("Please, login as Administrator and retry");
			die();
		}
		
		if(Mage::app()->getFrontController()->getRequest()->getControllerName()=='product' || Mage::app()->getFrontController()->getRequest()->getControllerName()=='category'){
			Mage::getDesign()->setPackageName($theme);
			Mage::app()->getLayout()->getUpdate()->addHandle('razorphyn_country_module_button_identifier');
			return $this;
		}
    }
	
	/**
     * Check if module is enabled
     */
	
	 public function isModuleEnabled(){
		return Mage::getStoreConfig('razorphyn/razorphyn_country_group/razorphyn_country_active',Mage::app()->getStore());
	}

	/**
     * Retrieve the product model
     * @return Mage_Catalog_Model_Product $product
     */
    public function getProduct(){
        return Mage::registry('product');
    }

    /**
     * Shortcut to getRequest
     *
     */
    protected function _getRequest(){
        return Mage::app()->getRequest();
    }
	
	protected function _isUrlInternal($url){
		if (strpos($url, 'http') !== false) {
			/**
			 * Url must start from base secure or base unsecure url
			 */
			if ((strpos($url, Mage::app()->getStore()->getBaseUrl()) === 0) || (strpos($url, Mage::app()->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true)) === 0)){
				return true;
			}
		}
		return false;
	}
}

