<?php
	//Populate table country/product
	$collection = Mage::getModel('catalog/product')->getCollection()->getAllIds();
	foreach($collection as $id){
		$data = array('product_id'=>$id,'active'=>0,'allowed'=>0,'country'=>null);
		Mage::getModel('country/product')->setData($data)->save();
	}

	//Populate table country/catalog
	$collection = Mage::getModel('catalog/category');
	$catTree = $collection->getTreeModel()->load();
	$catIds = $catTree->getCollection()->getAllIds();

	foreach($catIds as $id){
		$data = array('category_id'=>$id,'active'=>0,'allowed'=>0,'country'=>null);
		Mage::getModel('country/category')->setData($data)->save();	
	}
?>