<?php
class Razorphyn_Country_IndexController extends Mage_Adminhtml_Controller_Action{
    public function indexAction(){ //seems ok
        $this->loadLayout();

		//Maybe don't retrieve a limited product 

		//get first product id
		$collection = Mage::getModel('catalog/product')->getCollection()
														->addAttributeToFilter('status',array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));

		foreach($collection as $res){
			$productId=$res->getId();
			break;
		}

		//get first category of the product
		$product = Mage::getModel('catalog/product')->load($productId);
		$categoryCollection = $product->getCategoryCollection();
		$categoryId=array();
		foreach($categoryCollection as $res){
			$categoryId[]=$res->getId();
		}

		//Create links
		$productUrl = Mage::getBaseUrl().'catalog/product/view/category/'.$categoryId[count($categoryId)-1].'/id/'.$productId.'/razorphyn_theme/';
		$categoryUrl = Mage::getBaseUrl().'catalog/category/view/id/'.$categoryId[count($categoryId)-1].'/razorphyn_theme/';
		

		$theme=Mage::getStoreConfig('design/package/name');
		$ob=unserialize(Mage::getStoreConfig('design/package/ua_regexp'));
		
		$stored = json_decode(Mage::getStoreConfig('razorphyn/country/buttons'));
		
		
		$links=array(array(
						$theme,
						$productUrl.$theme,
						$categoryUrl.$theme,
						isset($stored->$theme->product)? 'Y':'N',
						isset($stored->$theme->category)? 'Y':'N'
					));
		
		foreach($ob as $key){
			if(empty($key['value']))
				continue;

			$completeProd = isset($stored->$key['value']->product) ? 'Y':'N';
			$completeCat  = isset($stored->$key['value']->category)? 'Y':'N';
			$links[]= array(
								$key['value'],
								$productUrl.$key['value'],
								$categoryUrl.$key['value'],
								$completeProd,
								$completeCat
							);
		}
		
		$body='<table style="width:50%; position:relative; margin: 0 auto">
					<thead>
						<tr>
							<th style="width:20%">'.Mage::helper('country')->__("Theme").'</th>
							<th style="width:30%">'.Mage::helper('country')->__("Product").'</th>
							<th style="width:30%">'.Mage::helper('country')->__("Category").'</th>
							<th style="width:20%">'.Mage::helper('country')->__("Complete").'</th>
						</tr>
					</thead>
					<tbody>
			  ';
		foreach($links as $link){
			
			$body.='
						<tr>
							<td>'.$link[0].'</td>
							<td><a target="_blank" href="'.$link[1].'">'.Mage::helper('country')->__("Product Configuration Url").'</a></td>
							<td><a target="_blank" href="'.$link[2].'">'.Mage::helper('country')->__("Category Configuration Url").'</a></td>
							<td>'.$link[3].'/'.$link[4].'</td>
						</tr>
					';
		}
		$body.='</tbody></table>';

        $block = $this	->getLayout()
						->createBlock('core/text', 'razorphyn.country.themelink')
						->setText($body);

        $this->_addContent($block);

        $this->renderLayout();
    }
}