<?php

class catalog_custom {

	/*
	 * Исключение товаров "наследник" из поиска
	 * */
	public function excludeObjectsFromSearch() {
        $pages = new selector('pages');
        $pages->types('hierarchy-type')->name('catalog', 'object');
        $pages->where('domain')->equals(1);
        $pages->where('this_child')->isnotnull();
        $pages->where('is_unindexed')->isnull();

        foreach ($pages->result as $page) {
        	$page->setValue('is_unindexed', true);
        	$page->commit();
		}

        return count($pages->result);
	}

    public function getColors($currentElementId = false){
        $umiHierarchy = umiHierarchy::getInstance();
        $umiObjectsCollection = umiObjectsCollection::getInstance();
        $page = $umiHierarchy->getElement($currentElementId);
        $sku = $page->artikul;
        $objectTypeId = $page->getObjectTypeId();
        $pages = new selector('pages');
        $pages->types('object-type')->id($objectTypeId);
        $pages->types('hierarchy-type')->name('catalog', 'object');
        $pages->where('artikul')->equals($sku);
        $pages->where('id')->notequals($currentElementId);
        $result = array();
        $pages_list = array_merge(array($page),$pages->result);
        foreach($pages_list as $page){
        	$line_arr = array(
        		'@id' => $page->id,
				'@name' => $page->name,
				'@link' => $page->link,
                '@selected' => ($page->id == $currentElementId) ? true : false,
			);
        	$colorId = $page->cvet;
        	if($colorId){
        		$color = $umiObjectsCollection->getObject($colorId);
        		$line_arr['@colorId'] = $colorId;
        		$line_arr['@color'] = $color->name;
        		$result[] = $line_arr;
			}
		}
		return def_module::parseTemplate('',array(
			'subnodes:items' => $result,
			'total' => $pages->length
		));
    }

	public function getProperties($currentElementId = false){
        $currentElementId = $currentElementId ? $currentElementId :cmsController::getInstance()->getCurrentElementId();
        $umiHierarchy = umiHierarchy::getInstance();
        $umiObjectsCollection = umiObjectsCollection::getInstance();
        $page = $umiHierarchy->getElement($currentElementId);
        $result = array();
		$properties = array();
        //if($parent_product = $page->parent_product){
		if(!$page->this_child && !$page->this_parent){
			return array();
		}

		$parentId = ($page->this_child) ? $page->getParentId() : $currentElementId;

        //$parent = $umiHierarchy->getElement($parent_product);
        $parent = $umiHierarchy->getElement($parentId);
        //$childs_product = $parent->childs_product;
        $pages = new selector('pages');
        $pages->types('hierarchy-type')->name('catalog', 'object');
        $pages->where('hierarchy')->page($parentId)->childs(1);
        $childs_product = $pages->result;

        foreach($childs_product as $child){



            $sizeId = $child->razmer;
            $colorId = $child->cvet;
            $size = $umiObjectsCollection->getObject($sizeId);
            $color = $umiObjectsCollection->getObject($colorId);
            if($size){
                $properties['subnodes:sizes'][$sizeId] = array(
                    '@id' => $sizeId,
                    '@name' => $size->name
                );
            }
            if($color){
                $properties['subnodes:colors'][$colorId] = array(
                    '@id' => $colorId,
                    '@name' => $color->name
                );
            }

            if($size){
                $line_arr = array(
                    '@id' => $child->id,
                    '@link' => $child->link,
                    '@name' => $child->name,
                    '@size' => $size->name,
                    '@sizeId' => $sizeId,
					'@selected' => ($child->id == $currentElementId) ? true : false,
                );
                $result['subnodes:pages'][] = $line_arr;
            }
        }

        $result['properties'] = $properties;

        return def_module::parseTemplate('',$result);
	}

	public function testLang(){
		var_dump(getLabel("field-phone")); exit;
	}

	public function searchProduct(){
		$search_string = getRequest('term');
        $pages = new selector('pages');
        $pages->types('hierarchy-type')->name('catalog', 'object');
        $pages->where("name")->ilike("%".$search_string."%");
        $result = array();

		$names = array();

        foreach($pages as $page){
            $result['subnodes:names'][md5($page->name)] = array(
                '@name' => $page->name,

            );
        	$result['subnodes:items'][] = array(
        		'@id' => $page->id,
				'@name' => $page->name,
				'@link' => $page->link,
			);
		}



		$result['total'] = sizeof($result);

		return def_module::parseTemplate('',$result);

        $buffer = outputBuffer::current();
        $buffer->charset('utf-8');
        $buffer->contentType('application/jsonp');
        $buffer->clear();
        $buffer->push(json_encode($result));
        $buffer->end();


	}


	public function getRecentPagesCustom($template = "default", $scope = "default", $showCurrentElement = false, $limit = null) {
		if (!$scope) {
			$scope = "default";
		}

		$hierarchy = umiHierarchy::getInstance();
		$currentElementId = cmsController::getInstance()->getCurrentElementId();
		list($itemsTemplate, $itemTemplate) = def_module::loadTemplates("content/" . $template, "items", "item");
		$recentPages = \UmiCms\Service::Session()->get('content:recent_pages');
		$recentPages = (is_array($recentPages)) ? $recentPages : [];
		$items = [];

		if (!isset($recentPages[$scope])) {
			return def_module::parseTemplate($itemsTemplate, ["subnodes:items" => []]);
		}

		$pageIdList = [];

		foreach ($recentPages[$scope] as $pageId => $time) {
			$pageIdList[] = $pageId;
		}

		$hierarchy->loadElements($pageIdList);
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		foreach ($recentPages[$scope] as $pageId => $time) {
			$element = $hierarchy->getElement($pageId, true);

			if (!($element instanceOf umiHierarchyElement)) {
				continue;
			}

			if (!$showCurrentElement && $element->getId() == $currentElementId) {
				continue;
			}

			if (!is_null($limit) && $limit <= 0) {
				break;
			}

			if (!is_null($limit)) {
				$limit--;
			}

			$items[] = def_module::parseTemplate($itemTemplate, self::makeProductLine($element,$umiObjectsCollection,$hierarchy,"slide_features"), $element->getId());
		}

		return def_module::parseTemplate($itemsTemplate, [
			"subnodes:items" => $items,
			'total' => sizeof($items),
		]);
	}

	public function fixType(){
		$typeId = 184;
		$prefix = "ch";
		$umiObjectTypesCollection = umiObjectTypesCollection::getInstance();
		$type = $umiObjectTypesCollection->getType($typeId);
		$group = $type->getFieldsGroupByName('properties');
		$fields = $group->getFields();
		foreach($fields as $field){
			$name = $field->getName();
			$new_name = $name."_".$prefix;
			$field->setName($new_name);
		}

		var_dump('ok'); exit;
	}

	public function download($pageId, $fieldName){
		$umiHierarchy = umiHierarchy::getInstance();
		$page = $umiHierarchy->getElement($pageId);
		$value = $page->getValue($fieldName);
		if($value){
			$value->download();
		}
	}

	public function getActionProducts(){
		$pages = new selector('pages');
		$pages->types('object-type')->id(150);
		$pages->types('hierarchy-type')->name('content', 'page');
		$pages->where('hierarchy')->page(3592)->childs(1);
		$result = array();
		foreach($pages as $page){
			$product = $page->product;
			$product = $product[0];


			$line_arr = array(
				'@id' => $page->id,
				'@link' => $product->link,
				'@name' => $page->name,
				'@img' => $page->img,
			);

			$result[] = $line_arr;
		}
		return def_module::parseTemplate('',array('subnodes:items' => $result));

	}

	public function fixField(){
		$umiFieldsCollection = umiFieldsCollection::getInstance();
		$fieldId = 617;

		$field = $umiFieldsCollection->getField($fieldId);
		$field->setIsLocked(false);
		$field->setIsSystem(false);
		$field->commit(true);
		/*
		$fieldId = 306;
		$field = $umiFieldsCollection->getField($fieldId);
		$field->setIsLocked(false);
		$field->setIsSystem(false);
		$field->commit(true);
		*/
		var_dump(11); exit;

	}

		public function objectImages($pageId = false){
		$pageId = $pageId ? $pageId : cmsController::getInstance()->getCurrentElementId();
		$umiHierarchy = umiHierarchy::getInstance();

		$page = $umiHierarchy->getElement($pageId);
		$local_files = array();

		if($images = $page->kartinki_galerei){
			$images = split(",",$images);

			foreach($images as $image){
				$image = trim($image);
				$image_name = split("/",$image);
				$image_name = $image_name[sizeof($image_name)-1];
				$file_name = CURRENT_WORKING_DIR."/images/product_imgs/".$image_name;
				if(file_exists($file_name)){
				}else{

					copy($image, $file_name);

				}
				$file = new umiImageFile("./images/product_imgs/".$image_name);
				$local_files[] = $file;
			}



		}
		//var_dump($local_files); exit;

		if($size = sizeof($local_files)){
			$page->photo = $local_files[0];

			if($size >= 2){
				$page->photo_2 = $local_files[1];
			}else{
				$page->photo = $local_files[0];
			}
			if($size>2){
				$page->photos = array_slice($local_files,2);
			}
			$page->commit();

		}



	}


	public function updateObjects(iUmiEventPoint $event){
		if ($event->getMode() !== 'after') {
			return false;
		}
		$element = $event->getRef('element');
		if($element->getMethod() == 'object'){
				self::objectImages($element->id);
		}

	}


	public function fixImages(){
		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('catalog', 'object');
		$pages->order('id')->asc(true);
		//$pages->where("kartinki_galerei")->isnotnull(true);
		$p = 0;
		$limit = 20000;
		$pages->limit($limit*$p,$limit);
		$i = 0;
		$result = array();
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		foreach($pages as $page){

				self::objectImages($page->id);
				echo $i.". ".$page->name."#".$page->id."  <br/>".PHP_EOL;
				$i++;
		}
			echo "end <br/>".PHP_EOL;;
		exit;
	}

	public function getLinks(){
		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('catalog', 'object');
		//$pages->where("kartinki_galerei")->isnotnull(true);
		$p = 1;
		$pages->limit(10000*$p,10000);

		$result = array();
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		foreach($pages as $page){
				$images = $page->kartinki_galerei;
				if($images){

					$images = explode(", ",$images);
					$result = array_merge($result,$images);
				}

		}
		echo implode("<br/>",$result);
		exit;
	}




	public function getOptions($pageId = false){
		$pageId = $pageId ? $pageId : cmsController::getInstance()->getCurrentElementId();
		$umiHierarchy = umiHierarchy::getInstance();
		$umiObjectTypesCollection = umiObjectTypesCollection::getInstance();
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		$page = $umiHierarchy->getElement($pageId);
		$objectTypeId = $page->getObjectTypeId();
		$type = $umiObjectTypesCollection->getType($objectTypeId);
		$group = $type->getFieldsGroupByName("catalog_option_props");
		$fields = $group->getFields();
		$result = array();
		foreach($fields as $field){
			$field_value = $page->getValue($field->getName());
			$line_arr = array();
			foreach($field_value as $rel){
				$object = $umiObjectsCollection->getObject($rel['rel']);
				$price = $rel['float'];
				$price_format = number_format($price, 0, '.', ' ');
				$line_arr[] = array(
								    '@id' => $rel['rel'],
								    '@name' => $object->name,
								    '@price' => $price,
								    '@price_format' => $price_format
								    );
			}

			if($field_value){
				$result[] = array(
					  '@name' => $field->getName(),
					  '@title' => $field->getTitle(),
					  'subnodes:items' => $line_arr,
					  );
			}

		}
		return def_module::parseTemplate('',array('subnodes:items' => $result));



	}

	public function getBrandsLink($pageId = false){
		$pageId = $pageId ? $pageId : cmsController::getInstance()->getCurrentElementId();
		$umiHierarchy = umiHierarchy::getInstance();
		$element = $umiHierarchy->getElement($pageId);

		$brandId = $element->brand;

		if($brandId){
			$pages = new selector('pages');
			$pages->types('hierarchy-type')->name('content', 'page');
			$pages->types('object-type')->id(147);
			$pages->where("brand")->equals($brandId);
			if($pages->length){
				$page = $pages->first;
				return array(
					     'link' => $page->link,
					     'brand' => $page->name

					    );
			}
		}

		return false;
	}

	public function test(){
		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('catalog', 'object');
		$pages->where("is_visible")->equals(array(0,1));
		$pages->where('brand')->equals(46709);
		var_dump($pages->length); exit;
	}

	public function searchCategory(){
		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('catalog', 'category');
		$pages->where('hierarchy')->page(0)->childs(1);
		//$pages->where('hierarchy')->page(0)->childs(1);
		$pages->where("is_visible")->equals(0);
		$limit = 5;
		$p = (int) getRequest('p');
		$pages->limit($limit*$p,$limit);
		$result = array();
		$country = "";
		foreach($pages as $page){

			$name = $page->name;

			$array = explode("(",$name);
			if(sizeof($array) > 1){
				$name = trim($array[0]);
				$country = $array[1];
				$country = str_replace(')','',$country);

				$objects = new selector('pages');
				$objects->types('hierarchy-type')->name('catalog', 'object');
				$objects->where('hierarchy')->page($page->id)->childs(6);
				foreach($objects as $object){
					self::saveObjectValue($object,"brand",$name,140);
					self::saveObjectValue($object,"country_relation",$country,10);
				}

			}else{
				$name = false;
			}


			$result[] = array(
					  '@id' => $page->id,
					  '@name' => $page->name,
					  '@link' => $page->link,
					  '@brand_name' => $name,
					  '@country' => $country
					  );
		}

		return def_module::parseTemplate("",array("subnodes:items" => $result,
							  'total' => $pages->length));
	}

	private function saveObjectValue($page,$fieldName,$value,$typeId){
		$objects = new selector('objects');
		$objects->types('object-type')->id($typeId);
		if($fieldName != "country_relation"){
			$objects->where("name")->ilike("%.".$value."%");
		}else{
			$objects->where("country_iso_code")->equals(md5($value));
		}

		$objectId = false;
		//var_dump($objects->length); exit;
		if($objects->length){
			$object = $objects->first;
			$objectId = $object->id;

		}else{

			$umiObjectsCollection = umiObjectsCollection::getInstance();
			$objectId = $umiObjectsCollection->addObject($value,$typeId);
			if($fieldName == "country_relation"){
				$object = $umiObjectsCollection->getObject($objectId);
				$object->country_iso_code = md5($value);
				$object->commit();
			}
		}

		$page->setValue($fieldName, $objectId);

	}

	public function getProductsLine($pageId = false){
		$pageId = $pageId ? $pageId : cmsController::getInstance()->getCurrentElementId();
		$cacheName = 'getProductsLine_'.$pageId;
		$cache = cacheFrontend::getInstance();
		$result = $cache->loadData($cacheName);
		if($result) return $result;
		$result = array();
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		$umiHierarchy = umiHierarchy::getInstance();
		$page = umiHierarchy::getInstance()->getElement($pageId);
		$brand_line = $page->brand_line;
		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('catalog', 'object');
		$pages->where("brand_line")->equals($brand_line);
		//$pages->order("rand");
		foreach($pages as $page){

			//$objectId = $page->getObjectId();
			//$pages = $umiHierarchy->getObjectInstances($objectId);
			//if(sizeof($pages) > 1){
			//	$pageId = $pages[0];
			//	if($pageId == $page->id){
			//		$result[] = self::makeProductLine($page,$umiObjectsCollection,$umiHierarchy);
			//	}
			//}else{
			//	$result[] = self::makeProductLine($page,$umiObjectsCollection,$umiHierarchy);
			//}
			$result[] = self::makeProductLine($page,$umiObjectsCollection,$umiHierarchy);


		}
		$result = def_module::parseTemplate("",array(
							     "subnodes:items" => $result,
							     "total" => sizeof($result),
							     ));

		$cache->saveData($cacheName,$result,36000);
		return $result;
	}

	public function getBrandsProduct($pageId = false){
		$pageId = $pageId ? $pageId : cmsController::getInstance()->getCurrentElementId();
		$cacheName = 'getBrandsProduct_'.$pageId;
		$cache = cacheFrontend::getInstance();
		$result = $cache->loadData($cacheName);
		if($result) return $result;

		$umiHierarchy = umiHierarchy::getInstance();
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		$page = umiHierarchy::getInstance()->getElement($pageId);
		$brandId = $page->brand;
		$brand = $umiObjectsCollection->getObject($brandId);
		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('catalog', 'category');
		$pages->where('hierarchy')->page(0)->childs(1);
		$result = array();
		foreach($pages as $page){
			$line_arr = array();

			$line_arr = array(
					  '@id' => $page->id,
					  '@name' => $page->name,
					  '@link' => $page->link
					  );
			$pages2 = new selector('pages');
			$pages2->types('hierarchy-type')->name('catalog', 'category');
			$pages2->where('hierarchy')->page($page->id)->childs(1);

			foreach($pages2 as $page2){
				$line_arr2 = array(
					  '@id' => $page2->id,
					  '@name' => $page2->name,
					  '@link' => $page2->link."?filter[brand][0]=".$brand->name,
					  );


				$pages3 = new selector('pages');
				$pages3->types('hierarchy-type')->name('catalog', 'object');
				$pages3->where('hierarchy')->page($page2->id)->childs(5);
				$pages3->where("brand")->equals($brandId);
				$pages3->limit(0,5);
				$pages3->order("rand");

				if($pages3->length){

					foreach($pages3 as $page3){
						$line_arr2['subnodes:items'][] = self::makeProductLine($page3,$umiObjectsCollection,$umiHierarchy);
					}
					$line_arr['subnodes:items'][] = $line_arr2;
				}


			}

			if(array_key_exists('subnodes:items',$line_arr) !== false){
				$result[] = $line_arr;
			}

		}

		$result = def_module::parseTemplate("",array("subnodes:items" => $result));

		$cache->saveData($cacheName,$result,36000);
		return $result;
	}


	public function getMainCategory($pageId = false){
		$cacheName = 'getMainCategory';
		$cache = cacheFrontend::getInstance();
		$result = $cache->loadData($cacheName);
		if($result) return $result;
		$pageId = $pageId ? $pageId : cmsController::getInstance()->getCurrentElementId();
		$pageId = $pageId ? $pageId : 0;
		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('catalog', 'category');
		$pages->where('hierarchy')->page($pageId)->childs(1);
		//$pages->where("is_visible")->equals(1);
		//$pages->where("main_page_img")->isnotnull(true);
		//$pages->where("name_main")->isnotnull(true);
		$result = array();
		foreach($pages as $page){
			$result[] = array(
					  '@id' => $page->id,
					  '@link' => $page->link,
					  '@name' => $page->name,
					  '@header_pic' => $page->header_pic
					  );
		}

		$result = def_module::parseTemplate("",array("subnodes:items" => $result));

		$cache->saveData($cacheName,$result,36000);
		return $result;

	}

	public function getSpecialsCategory(){
		$cacheName = 'getSpecialsCategory';
		$cache = cacheFrontend::getInstance();
		$result = $cache->loadData($cacheName);
		if($result) return $result;

		$umiHierarchy = umiHierarchy::getInstance();
		$mainPageId = 3;
		$mainPage = $umiHierarchy->getElement($mainPageId);
		$categories = $mainPage->category;
		$result = array();
		foreach($categories as $page){
			if($page->menu_pic_a){
				$result['subnodes:items'][] = array(
							'@id' => $page->id,
							'@link' => $page->link,
							'@name' => $page->name,
							'@header_pic' => $page->menu_pic_a
							);
			}

		}


		$result = def_module::parseTemplate("",$result);

		$cache->saveData($cacheName,$result,36000);
		return $result;
	}
	public function getMainProductsOld($limit = false){
		//var_dump($popular); exit;
		$cacheName = 'getMainProducts';
		$cache = cacheFrontend::getInstance();
		$result = $cache->loadData($cacheName);
		//if($result) return $result;

		$umiHierarchy = umiHierarchy::getInstance();



		$result = array();
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		$limit = $limit ? $limit : 15;


		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('catalog', 'object');
		$pages->where("popular")->equals(1);

		$pages_temp = array();
		foreach($pages as $page){
			$pages_temp[$page->getParentId()][] = $page;
		}


		$result = array();
		foreach($pages_temp as $parentId => $pages){
			$parent = $umiHierarchy->getElement($parentId);
			$line_arr = array(
				'@id' => $parentId,
				'@link' => $parent->link,
				'@name' => $parent->name
			);

			foreach($pages as $page){
				$class = "slide";
				$line_arr['subnodes:items'][] = self::makeProductLine($page,$umiObjectsCollection,$umiHierarchy,$class);
			}
			$result[] = $line_arr;
		}

		$result = def_module::parseTemplate("",array(
																									'subnodes:items' => $result,
																									'total' => sizeof($result),
		));

		$cache->saveData($cacheName,$result,36000);
		return $result;
	}


	public function getMainProducts(){
        $limit = false;
        //var_dump($popular); exit;
        $langId = cmsController::getInstance()->getCurrentLang()->getId();
        $cacheName = 'getMainProducts_'.$langId;
        $cache = cacheFrontend::getInstance();
        $result = $cache->loadData($cacheName);
        if($result) return $result;

        $umiHierarchy = umiHierarchy::getInstance();
        $mainPage = $umiHierarchy->getDefaultElement();


        $result = array();
        $umiObjectsCollection = umiObjectsCollection::getInstance();
        $limit = $limit ? $limit : 15;


        $class = "slide";
        for($i = 1; $i<5; $i++){
            $items = $mainPage->getValue('products_'.$i);

            if(sizeof($items)){
                $name = $mainPage->getValue('nazvanie_'.$i);
                $line_arr = array(
                    '@name' => $name,
                );
                shuffle($items);
                if(!$limit){

                    $popular = array_slice($items,0,15);
                }else{
                    $popular = array_slice($items,0,$limit);
                }
                foreach($items as $j => $page){


                    $line_arr['subnodes:items'][] = self::makeProductLine($page,$umiObjectsCollection,$umiHierarchy,$class);

                }
                $result['products_'.$i] = $line_arr;
            }
        }

        $result = def_module::parseTemplate("",$result);

        $cache->saveData($cacheName,$result,36000);
        return $result;
	}


	public function getMyFiltr(){
		$array = array(
			       "fields" => array(
							"price_desc" => getLabel('sort_down'),
							"price_asc" => getLabel('sort_up'),							
							"popular_asc" => getLabel('sort_up'),
							// "Сначала популярные",
							//"popular_desc" => "Сначала непопулярные",
							"name_asc" => getLabel('name_up'),
							"name_desc" => getLabel('name_down'),
						 //"weight_asc" => "Имя по возрастанию",
						 //"weight_desc" => "Имя по убывания",
						 ),
						 'sort' => array(
							 'desc' => 'desc',
							 'asc' => 'asc'
						 ),
			       "per_page" => array(
						   "15" => "15",
						   "30" => "30",
						   "45" => "45",
						   "all" => "Все",
						   ),
			       );

		$result = array();
		$active = getRequest('fields');
		$active = $active ? $active : "desc";
		foreach($array['fields'] as $name=>$title){
			$is_active = ($name == $active) ? 'active' : false;
			$result['subnodes:fields'][] = array(
							     '@name' => $name,
							     '@title' => $title,
							     '@is_active' => $is_active
							     );
		}
		$active = getRequest('sort');
		$active = $active ? $active : "desc";
		foreach($array['sort'] as $name=>$title){
			$is_active = ($name == $active) ? 'active' : false;
			$result['subnodes:sort'][] = array(
							     '@name' => $name,
							     '@title' => $title,
							     '@is_active' => $is_active
							     );
		}
		$active = getRequest('per_page');
		$active = $active ? $active : "15";

		if(isset($_COOKIE['view'])){
			if($_COOKIE['view'] == '#table'){
				if(isset($_COOKIE['per_page'])){
					$active = $_COOKIE['per_page'];
				}
			}
		}
		foreach($array['per_page'] as $name=>$title){
			$is_active = ($name == $active) ? 'active' : false;
			$result['subnodes:per_page'][] = array(
							     '@name' => $name,
							     '@title' => $title,
							     '@is_active' => $is_active
							     );
		}


		return def_module::parseTemplate('',$result);
    }


	public function getSalesProd(){
		$umiHierarchy = umiHierarchy::getInstance();
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		$current_page_id = cmsController::getInstance()->getCurrentElementId();
		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('catalog', 'object');
		$pages->where("id")->notequals($current_page_id);
		$pages->where("sales")->equals(1);
		$pages->order("rand");
		$pages->limit(0,4);
		foreach($pages as $page){
			$result[] = self::makeProductLine($page,$umiObjectsCollection,$umiHierarchy,"");
		}
		$result = def_module::parseTemplate("",array(
							     "subnodes:items" => $result,
							     "total" => sizeof($result)));
		return $result;
	}

	public function getLinkedProd($current_page_id = false, $mode = false){
		$current_page_id = $current_page_id ? $current_page_id : cmsController::getInstance()->getCurrentElementId();
		$umiHierarchy = umiHierarchy::getInstance();
		$page = $umiHierarchy->getElement($current_page_id);
		$linkedProducts = array();
		$result = array();
		$limit = 12;
        $linkedProducts = $page->recommended_items;

		$content = cmsController::getInstance()->getModule("content");
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		$result = array();

		if(!sizeof($linkedProducts)){
			$parentId = $page->getParentId();

			$pages = new selector('pages');
			$pages->types('hierarchy-type')->name('catalog', 'object');
			$pages->where('hierarchy')->page($parentId)->childs(1);
			$pages->where("id")->notequals($current_page_id);
			$pages->order("rand");
			$pages->limit(0,$limit);
			$linkedProducts = $pages->result;

		}

		shuffle($linkedProducts);
		$linkedProducts = array_slice($linkedProducts,0,$limit);



		foreach($linkedProducts as $page){
			if($mode){
				$result['subnodes:items'][] = self::makeProductLine($page,$umiObjectsCollection,$umiHierarchy,"");
			}else{
				$result['subnodes:items'][] = self::makeProductLine($page,$umiObjectsCollection,$umiHierarchy,"slide");
			}
		}

/* Обнуление переменных, чтобы второй слайдер работал отдельно от первого */
				$current_page_id = $current_page_id ? $current_page_id : cmsController::getInstance()->getCurrentElementId();
				$umiHierarchy = umiHierarchy::getInstance();
				$page = $umiHierarchy->getElement($current_page_id);
				$linkedProducts = array();
				// $result = array();

        //$limit = 16;
        $linkedProducts = $page->likes;

        $content = cmsController::getInstance()->getModule("content");
        $umiObjectsCollection = umiObjectsCollection::getInstance();


        if(!sizeof($linkedProducts)){
            $parentId = $page->getParentId();

            $pages = new selector('pages');
            $pages->types('hierarchy-type')->name('catalog', 'object');
            $pages->where('hierarchy')->page($parentId)->childs(1);
            $pages->where("id")->notequals($current_page_id);
            $pages->order("rand");
            $pages->limit(0,$limit);
            $linkedProducts = $pages->result;

        }

        shuffle($linkedProducts);
        $linkedProducts = array_slice($linkedProducts,0,$limit);



        foreach($linkedProducts as $page){
            if($mode){
                $result['subnodes:likes'][] = self::makeProductLine($page,$umiObjectsCollection,$umiHierarchy,"");
            }else{
                $result['subnodes:likes'][] = self::makeProductLine($page,$umiObjectsCollection,$umiHierarchy,"slide");
            }
        }

		$result = def_module::parseTemplate("",$result);
		return $result;

	}




	/**
	 * Получение списка последних просмотренных страниц
	 *
	 * @param string $template Шаблон для вывода
	 *
	 * @param string $scope Тэг(группировка страниц), без пробелов и запятых
	 * @param bool $showCurrentElement Если false - текущая страница не будет включена в результат
	 * @param int|null $limit Количество выводимых элементов
	 *
	 * @return mixed
	 */
	public function getRecentPages($template = "default", $scope = "default", $showCurrentElement = false, $limit = null) {
		if (!$scope) {
			$scope = "default";
		}

		$hierarchy = umiHierarchy::getInstance();
		$currentElementId = cmsController::getInstance()->getCurrentElementId();


		$session = session::getInstance();
		$recentPages = $session->get('content:recent_pages');
		$recentPages = (is_array($recentPages)) ? $recentPages : [];
		$session->commit();
		$items = [];
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		$umiHierarchy = $hierarchy;
		$limit = 15;

		if (isset($recentPages[$scope])) {
			$pagesIds = [];
			foreach ($recentPages[$scope] as $recentPage) {
				$pagesIds[] = $recentPage;
			}
			$hierarchy->loadElements($pagesIds);
			foreach ($recentPages[$scope] as $recentPage => $time) {
				$element = $hierarchy->getElement($recentPage, true);

				if (!($element instanceOf umiHierarchyElement)) {
					continue;
				}

				if (!$showCurrentElement && $element->getId() == $currentElementId) {
					continue;
				} elseif (!is_null($limit) && $limit <= 0) {
					break;
				} elseif (!is_null($limit)) {
					$limit--;
				}

				$items[] = self::makeProductLine($element,$umiObjectsCollection,$umiHierarchy,"recent_page");

			}
		}


		return def_module::parseTemplate('', [
			"subnodes:items" => $items,
			'total' => sizeof($items)
		]);
	}

	public function getSetProduct($current_page_id = false){
		$current_page_id = $current_page_id ? $current_page_id : cmsController::getInstance()->getCurrentElementId();
		$umiHierarchy = umiHierarchy::getInstance();
		$page = $umiHierarchy->getElement($current_page_id);
		$linkedProducts = array();
		$linkedProducts = $page->products_set;


		if(!sizeof($linkedProducts)){
			$parentId = $page->getParentId();

			$pages = new selector('pages');
			$pages->types('hierarchy-type')->name('catalog', 'object');
			$pages->where('hierarchy')->page($parentId)->childs(1);
			$pages->where("id")->notequals($current_page_id);
			$pages->order("rand");
			$pages->limit(0,12);
			$linkedProducts = $pages->result;

		}

		//shuffle($linkedProducts);
		//$linkedProducts = array_slice($linkedProducts,0,12);
		$result = array();

		$content = cmsController::getInstance()->getModule("content");
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		foreach($linkedProducts as $page){
			$result[] = self::makeProductLine($page,$umiObjectsCollection,$umiHierarchy,"");

		}
		$result = def_module::parseTemplate("",array(
							     "subnodes:items" => $result,
							     "total" => sizeof($result)));
		return $result;

	}

	public function getPriceOld($current_page_id = false, $umiHierarchy = false,$umiObjectsCollection = false){
		$current_page_id = $current_page_id ? $current_page_id : cmsController::getInstance()->getCurrentElementId();
		$umiHierarchy = $umiHierarchy ? $umiHierarchy : umiHierarchy::getInstance();
		$umiObjectsCollection = $umiObjectsCollection ? $umiObjectsCollection : umiObjectsCollection::getInstance();
		$page = $umiHierarchy->getElement($current_page_id);
		if(!$page){
			//if(!$page) return false;
			$pages = $umiHierarchy->getObjectInstances($current_page_id);

			$current_page_id = $pages[0];
			$page = $umiHierarchy->getElement($current_page_id);
		}

		$price = $old_price = 0;

		if($currencyId = $page->currency){
			$currency = $umiObjectsCollection->getObject($currencyId);
			$rate = $currency->rate;
			$price = round($page->price*$rate);
			$old_price = round($page->old_price*$rate);
		}else{
			$price = $page->price;
			$old_price = $page->old_price;
		}

		$line_arr = array();

		$page->price_rur = $price;

		if($price){
			$line_arr['price'] = number_format($price, 0, '.', ' ');
		}
		if($old_price){
			$line_arr['old_price'] = number_format($old_price, 0, '.', ' ');
		}

		return $line_arr;
	}


	public function getPrice($current_page_id = false, $umiHierarchy = false,$umiObjectsCollection = false){
		$current_page_id = $current_page_id ? $current_page_id : cmsController::getInstance()->getCurrentElementId();
		$umiHierarchy = $umiHierarchy ? $umiHierarchy : umiHierarchy::getInstance();
		$umiObjectsCollection = $umiObjectsCollection ? $umiObjectsCollection : umiObjectsCollection::getInstance();
		$page = $umiHierarchy->getElement($current_page_id);
		if(!$page){
			//if(!$page) return false;
			$pages = $umiHierarchy->getObjectInstances($current_page_id);

			$current_page_id = $pages[0];
			$page = $umiHierarchy->getElement($current_page_id);
		}

		$emarket = cmsController::getInstance()->getModule("emarket");

		$price_new = $emarket->priceCustom($current_page_id);
		//var_dump($price_new); exit;
		$price = $old_price = 0;

		//
		//$sets_price = preg_replace('/[^0-9]/', '', $line_arr['price']['price']);
		//$sets_price = $sets_price*8;
		//$sets_price = number_format($sets_price, 0, '.', ' ');
		//
		/*
		$sets_count = $page->kolichestvo_v_korobke;
		if($sets_count){
			$sets_count = $umiObjectsCollection->getObject($sets_count);
			$sets_count = (int)$sets_count->name;
		}else{
			$sets_count = 8;
		}
		if(array_key_exists('original',$price_new['price']) !== false){
			$line_arr['price'] = $price_new['price']['actual'];
			$line_arr['old_price'] = $price_new['price']['original'];
			$sets_price = $page->cena_za_paru;
			if(!$sets_price){
				$sets_price = preg_replace('/[^0-9]/', '', $price_new['price']['actual']);
				$sets_price = $sets_price*8;
			}


			$line_arr['sets_price'] = number_format($sets_price, 0, '.', ' ');;


		}else{
			$price = $old_price = 0;

			if($currencyId = $page->currency){
				$currency = $umiObjectsCollection->getObject($currencyId);
				$rate = $currency->rate;
				$price = round($page->price*$rate);
				$old_price = round($page->old_price*$rate);
			}else{
				$price = $page->price;
				$old_price = $page->old_price;
			}

			$line_arr = array();

			$page->price_rur = $price;
			if($price){
				$line_arr['price'] = number_format($price, 0, '.', ' ');
				$sets_price = $page->cena_za_paru;
				if(!$sets_price){
					$sets_price = preg_replace('/[^0-9]/', '', $price_new['price']['actual']);
					$sets_price = $sets_price*8;
				}
				$line_arr['sets_price'] = number_format($sets_price, 0, '.', ' ');;
			}
			if($old_price){
				$line_arr['old_price'] = number_format($old_price, 0, '.', ' ');
			}

		}
		*/

		if(array_key_exists('original',$price_new['price']) !== false){
			$line_arr['price_not_format'] = preg_replace('/\s/', '', $price_new['price']['actual']);
			$line_arr['price'] = $price_new['price']['actual'];
			$line_arr['old_price'] = $price_new['price']['original'];



			$sets_price = $page->cena_za_paru;


			$line_arr['sets_price'] = number_format($sets_price, 0, '.', ' ');;


		}else{
			$price = $old_price = 0;

			if($currencyId = $page->currency){
				$currency = $umiObjectsCollection->getObject($currencyId);
				$rate = $currency->rate;
				$price = round($page->price*$rate);
				$old_price = round($page->old_price*$rate);
			}else{
				$price = $page->price;
				$old_price = $page->old_price;
			}

			$line_arr = array();

			$page->price_rur = $price;
				$line_arr['price_not_format'] = $price;
				$line_arr['price'] = number_format($price, 0, '.', ' ');
				$sets_price = $page->cena_za_paru;
				$line_arr['sets_price'] = number_format($sets_price, 0, '.', ' ');;

			if($old_price){
				$line_arr['old_price'] = number_format($old_price, 0, '.', ' ');
			}

		}

		//var_dump($line_arr); exit;

		if($nds = $page->nds_rub){
				$line_arr['nds'] = number_format($nds, 0, '.', ' ');
		}
		if($not_nds = $page->not_nds){
				$line_arr['not_nds'] = number_format($not_nds, 0, '.', ' ');
		}



		return $line_arr;
	}

	public function getProductPhoto($current_page_id = false){
		$current_page_id = $current_page_id ? $current_page_id : cmsController::getInstance()->getCurrentElementId();
		$page = umiHierarchy::getInstance()->getElement($current_page_id);
        $result = array();
		if($page){

			$photo = $page->photo;

			if(!$photo){
				//var_dump($page->image); exit;
				$photo = $page->image;
			}

			$photos = array();
			//$photo = $photo ? $photo : new umiImageFile("./images/nofoto.jpg");
            $parent = $page->this_child ? umiHierarchy::getInstance()->getElement($page->getParentId()) : false;
			if($photo){
				$photos = array($photo);
				$photos = array_merge($photos,$page->photos);
			}else{
				$photos = $page->photos;
				if(sizeof($photos) == 0){
                    if($parent){
                        $photo = $parent->photo;
                        if($photo){
                            $photos = array($photo);
                            $photos = array_merge($photos,$parent->photos);
                        }else{
                            $photos = $parent->photos;
                            if(sizeof($photos) == 0){
                                $photo = new umiImageFile("./images/nofoto.jpg");
                                $photos = array($photo);
                            }
                        }
					}else{
                        $photo = new umiImageFile("./images/nofoto.jpg");
                        $photos = array($photo);
					}
				}
			}

			$colors_photo = $page->colors_photo;
			if($colors_photo){
				$photos = array_merge($photos,$colors_photo);
			}else{
				if($parent){
                    if($colors_photo = $parent->colors_photo){
                        $photos = array_merge($photos,$colors_photo);
                    }
				}

			}


			foreach($photos as $photo){
                if($photo){
                	if(!$photo->getIsBroken()){
                        $result[] = array(
                            'alt' => $photo->getAlt(),
                            'img' => $photo->getFilePath(true),

                            //'big' => self::makeThumbnailNew($photo->getFilePath(),598,538),
                            /*
                            'small' => self::makeThumbnailNew($photo->getFilePath(),68,60)
                            */
                        );
					}

				}


			}

		}

		$result = def_module::parseTemplate("",array(
							     "subnodes:items" => $result,
							     "total" => sizeof($result)));
		return $result;
	}

	public function getProductOtherPhoto($current_page_id = false){
		$current_page_id = $current_page_id ? $current_page_id : cmsController::getInstance()->getCurrentElementId();
		$page = umiHierarchy::getInstance()->getElement($current_page_id);
		if($page){

			$result = array();
			$photos = $page->dop_photo;
			foreach($photos as $photo){
				$result[] = array(
						'big' => self::makeThumbnailNew($photo->getFilePath(),464,358),
						//'small' => self::makeThumbnailNew($photo->getFilePath(),42,27)
						);

			}


		}
		$result = def_module::parseTemplate("",array(
							     "subnodes:items" => $result,
							     "total" => sizeof($result)));
		return $result;
	}


	public function fixCategoryImg(){

		$file = new umiFile("./images/kravd/item_1.jpg");

		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('catalog', 'category');
		foreach($pages as $page){
			$page->header_pic = $file;
		}
	}

	public function fixDescr(){



		define("CRON", (isset($_SERVER['HTTP_HOST'])?"HTTP":"CLI"));
		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('catalog', 'object');
		$umiHierarchy = umiHierarchy::getInstance();

		foreach($pages as $page){
			$page->description = "<p>Значимость этих проблем настолько очевидна, что укрепление и развитие структуры требуют от нас анализа модели развития. Повседневная практика показывает, что сложившаяся структура организации позволяет выполнять важные задания по разработке дальнейших направлений развития. Не следует, однако забывать, что постоянное информационно-пропагандистское обеспечение нашей деятельности позволяет оценить значение форм развития. С другой стороны сложившаяся структура организации требуют от нас анализа существенных финансовых и административных условий. Разнообразный и богатый опыт укрепление и развитие</p>";
		}

	}


	public function fixPrice(){


		$objects = new selector('objects');
		$objects->types('object-type')->id(191);
		$flags = $objects->result;
        /*

        $objects2 = new selector('objects');
        $objects2->types('object-type')->id(148);
        $naznachenie = $objects2->result;

        $objects = new selector('objects');
        $objects->types('object-type')->id(153);
        $dizajn = $objects->result;
        */





		/*

		$objects = new selector('objects');
		$objects->types('object-type')->id(124);
		$colors = $objects->result;

		$objects = new selector('objects');
		$objects->types('object-type')->id(144);
		$material = $objects->result;

		$objects = new selector('objects');
		$objects->types('object-type')->id(147);
		$themes = $objects->result;

		$objects = new selector('objects');
		$objects->types('object-type')->id(148);
		$flags = $objects->result;
		*/




		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('catalog', 'object');
		$pages->where('is_active')->equals(array(1,0));
		$pages->order('id')->asc(true);
		//$pages->limit(0,10000);
		//var_dump($pages->length); exit;
		//$pages->types('object-type')->id(144);
		$umiHierarchy = umiHierarchy::getInstance();
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		foreach($pages as $page){
            $price = rand(3000,20000);
            $price_old = rand($price+rand(1,$price),$price*3);
            $page->price = $price;
            $page->old_price = $price_old;

			$page->flag = $flags[rand(0,sizeof($flags)-1)];
			/*
            $page->sostav = $sostav[rand(0,sizeof($sostav)-1)];
            $page->naznachenie = $naznachenie[rand(0,sizeof($naznachenie)-1)];
            $page->dizajn = $dizajn[rand(0,sizeof($dizajn)-1)];

			$page->new = rand(0,1);
            $page->sale = rand(0,1);

            $page->commit();
			*/
			/*
			$price = rand(0,1);
			if($price){
				$price = rand(50000,500000);
				$price_old = rand($price+rand(1,$price),$price*3);
				$page->price = $price;
				$page->old_price = $price_old;
			}else{
				$price = 0;
				$page->old_price = 0;
			}
			*/


			/*
			$page->flag = $objects[rand(0,sizeof($objects)-1)];
			$page->commit();
			*/




			//$page->oblast_primeneniya = $objects[rand(0,sizeof($objects)-1)];
			//$page->term = rand(10,1000)." дн.";
			//echo $page->id."<br/>";
			//$page->setIsActive(true);
			//$page->commit();
			/*
			if($page->hasVirtualCopy()){
				if(!$page->isOriginal()){
					$umiHierarchy->delElement($page->id);
					$umiHierarchy->removeDeletedAll();
				}
			}
			*/
			/*
			$page->price = rand(1000,100000);
			$page->nds_rub = $page->price*0.18;
			*/
			/*
			$price = $page->price;
			$nds = $page->nds_rub;
			$page->not_nds = $price - $nds;
			$page->commit();
			*/


			//$page->nds_rub = $page->price*0.18;

			//$page->cvet_2 = $colors;
			/*
			$page->weight = rand(1,10);


			$page->width = rand(1,30);
			$page->height = rand(1,30);
			$page->length = rand(1,30);
			*/


			/*
			$price = rand(1000,100000);
			$price_old = rand($price+rand(1,$price),$price*3);
			$page->price = $price;
			$page->price_old = $price_old;

			$page->brand = $brands[rand(0,sizeof($brands)-1)];
			$page->color = $colors[rand(0,sizeof($colors)-1)];
			$page->material = $material[rand(0,sizeof($material)-1)];
			$page->ves = rand(1,10);
			*/
			/*
			$page->brand = $brands[rand(0,sizeof($brands)-1)];

			$themes_count = rand(0,sizeof($themes)-1);
			shuffle($themes);
			$this_themes = array_slice($themes,0,$themes_count);
			$page->themes = $this_themes;

			$page->flag = $flags[rand(0,sizeof($flags)-1)];
			*/


			//$price = $page->price;
			//$cena_za_upakovku = $page->cena_za_paru;
			//
			//$page->price = $cena_za_upakovku;
			//$page->cena_za_paru = $price;
		}
		var_dump($pages->length); exit;

	}

	public function downloadPDF(){
		//var_dump(111); exit;

		$pageId = 253;
		$page = umiHierarchy::getInstance()->getElement($pageId);
		$alt_name = $page->getAltName();

		//var_dump("http://".$_SERVER['HTTP_HOST'].$page->link); exit;
		$html = file_get_contents("http://".$_SERVER['HTTP_HOST'].$page->link);

		include("mpdf/mpdf.php");

		$mpdf = new mPDF('utf-8', 'A4', '8', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
		$mpdf->charset_in = 'cp1251'; /*не забываем про русский*/

		$stylesheet = file_get_contents(CURRENT_WORKING_DIR.'/templates/king/css/styles.css'); /*подключаем css*/
		$mpdf->WriteHTML($stylesheet, 1);
		//$stylesheet = file_get_contents(CURRENT_WORKING_DIR.'/templates/king/css/new_styles.css'); /*подключаем css*/
		//$mpdf->WriteHTML($stylesheet, 1);

		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($html, 2); /*формируем pdf*/
		$mpdf->Output('mpdf.pdf', 'I');


	}


	public function fixObjectPriceUnit(){

		$objects = new selector('objects');
		$objects->types('object-type')->id(153);
		$objects = $objects->result;


		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('catalog', 'object');
		foreach($pages as $page){
			$this_unit = $objects[rand(0,1)];
			$page->unit = $this_unit;
		}

	}

	public function fixObjectImg(){
		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('catalog', 'object');

		$photo = new umiFile("./images/king/product_img.jpg");
		foreach($pages as $page){
			$page->photo = $photo;
		}
	}

	/**
	* Выводит данные для формирования списка объектов каталога, с учетом параметров фильтрации
	* @param string $template имя шаблона отображения (только для tpl)
	* @param int $categoryId ид раздела каталога, объекты которого требуется вывести
	* @param int $limit ограничение количества выводимых объектов каталога
	* @param bool $ignorePaging игнорировать постраничную навигацию (то есть GET параметр 'p')
	* @param int $level уровень вложенности раздела каталога $categoryId, на котором размещены необходимые объекты каталога
	* @param bool $fieldName поле объекта каталога, по которому необходимо произвести сортировку
	* @param bool $isAsc порядок сортировки
	* @return mixed
	* @throws publicException если не удалось получить объект страницы по id = $categoryId
	*/
       public function getSmartCatalogCustom($categoryId, $limit = false, $ignorePaging = false, $level = 4, $fieldName = false, $isAsc = true) {
		$template = 'default';
	       /* @var catalog|__filter_catalog $this*/
				 $cmsController = cmsController::getInstance();
	       $catalog = $cmsController->getModule('catalog');

	       if (!is_string($template)) {
		       $template = 'default';
	       }

	       list(
		       $itemsTemplate,
		       $emptyItemsTemplate,
		       $emptySearchTemplates,
		       $itemTemplate
	       ) = def_module::loadTemplates(
		       'catalog/' . $template,
		       'objects_block',
		       'objects_block_empty',
		       'objects_block_search_empty',
		       'objects_block_line'
	       );

	       $umiHierarchy = umiHierarchy::getInstance();
	       /* @var iUmiHierarchyElement $category */
	       $category = $umiHierarchy->getElement($categoryId);

	       if (!$category instanceof iUmiHierarchyElement) {
		       throw new publicException(__METHOD__ . ': cant get page by id = '. $categoryId);
	       }

	       $limit = ((int) $limit) ? $limit : $catalog->per_page;
	       $limit_new = getRequest("per_page");

	       $limit = ($limit_new) ? $limit_new : $limit;

	       $currentPage = ($ignorePaging) ? 0 : (int) getRequest('p');
	       $offset = $currentPage * $limit;

	       if (!is_numeric($level)) {
		       $level = 1;
	       }

	       $filteredProductsIds = null;
	       $queriesMaker = null;
	       if (is_array(getRequest('filter'))) {
		       $emptyItemsTemplate = $emptySearchTemplates;
		       $queriesMaker = $catalog->getCatalogQueriesMaker($category, $level);

		       if (!$queriesMaker instanceof FilterQueriesMaker) {
			       return $catalog->makeEmptyCatalogResponse($emptyItemsTemplate, $categoryId);
		       }

		       $filteredProductsIds = $queriesMaker->getFilteredEntitiesIds();

		       if (count($filteredProductsIds) == 0) {
			       return $catalog->makeEmptyCatalogResponse($emptyItemsTemplate, $categoryId);
		       }
	       }

	       $products = new selector('pages');
	       $products->types('hierarchy-type')->name('catalog', 'object');
				 if($category->getObjectTypeId() === 155){
			 			$products->where("filtr")->equals($category->filtr);
			 		}else{
						if($categoryId == 3601){
		 					 $products->where("flag")->equals(867);
		 				 }else{
		 					 $sales = getRequest("sales");
		 					 if(!$sales){
		 						 if (is_null($filteredProductsIds)) {
		 							 $products->where('hierarchy')->page($categoryId)->childs($level);
		 						 } else {
		 							 $products->where('id')->equals($filteredProductsIds);
		 						 }
		 					 }
		 				 }
					}




	//       if ($fieldName) {
	//	       if ($isAsc) {
	//		       $products->order($fieldName)->asc();
	//	       } else {
	//		       $products->order($fieldName)->desc();
	//	       }
	//       } else {
	//	       $products->order('ord')->asc();
	//       }

	//       if($sort_field = getRequest("sort")){
	//		$field = getRequest("fields");
	//		switch($sort_field){
	//			case "asc": {$products->order($field)->asc();}; break;
	//			case "desc": {$products->order($field)->desc();}; break;
	//
	//		}
	//
	//       }else{
	//		$products->order("price")->asc(true);
	//       }
		/*
		"price_asc" => "Цена по убыванию",
		"price_desc" => "Цена по возрастанию",
		"name_asc" => "Имя по убыванию",
		"name_desc" => "Имя по возрастанию",
		*/
		$field = getRequest('fields');


		switch($field){
				case "price_desc": {$products->order("price")->desc(true);}; break;
				case "price_asc": {$products->order("price")->asc(true);}; break;
				case "name_asc": {$products->order("name")->asc(true);}; break;
				case "name_desc": {$products->order("name")->desc(true);}; break;
				case "popular_asc": {$products->order("seens_count")->desc(true);}; break;
				case "popular_desc": {$products->order("seens_count")->asc(true);}; break;
				default: $products->order("price")->desc(true); break;
			}
		//var_dump(); exit;

		if($sales){
			$products->where("sales")->equals(1);
		}
		$flag = getRequest("flag");
		if($flag){
			switch($flag){
				case "new": {$products->where("flag")->equals(818);}; break;
                case "sale": {$products->where("flag")->equals(819);}; break;
			}

		}


	       if ($queriesMaker instanceof FilterQueriesMaker) {
		       if (!$queriesMaker->isPermissionsIgnored()) {
			       $products->option('no-permissions')->value(true);
		       }
	       }
	       $products->where("this_parent")->equals(1);
	       $products->option('load-all-props')->value(true);

		if($limit){
			if($limit !== "all"){
				$products->limit($offset, $limit);
			}

		}
	       $pages = $products->result();
	       $total = $products->length();

	       if ($total == 0) {
		       return $catalog->makeEmptyCatalogResponse($emptyItemsTemplate, $categoryId);
	       }

	       $result = array();
	       $items = array();
	       $umiLinksHelper = umiLinksHelper::getInstance();
	       /* @var iUmiHierarchyElement|umiEntinty $page */
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		$emarket = cmsController::getInstance()->getModule("emarket");
	       foreach ($pages as $i=>$page) {
		       $items[] = $this->makeProductLine($page, $umiObjectsCollection, $umiHierarchy, "");
		       $umiHierarchy->unloadElement($page->id);
	       }

				 $content = $cmsController->getModule("content");
				 $pageNum = $content->generateNumPage($total,$limit,'default',"p",false,$category->link);


	       $result['subnodes:lines'] = $items;
	       $result['numpages'] = $pageNum;
	       $result['total'] = $total;
	       $result['per_page'] = $limit;
				 $result['curr_page'] = (int)getRequest("p");
	       $result['category_id'] = $categoryId;

	       return def_module::parseTemplate($itemsTemplate, $result, $categoryId);
       }

       public function makeProductLine($page = false,$umiObjectsCollection = false, $umiHierarchy = false,$className = ""){
		if(is_string($page)){
			$umiHierarchy = $umiHierarchy ? $umiHierarchy : umiHierarchy::getInstance();
			$page = $umiHierarchy->getElement($page);
		}
		$cacheName = 'makeProductLine_'.$className."_".$page->id;
		$cache = cacheFrontend::getInstance();
		$result = $cache->loadData($cacheName);
		//if($result) return $result;
		$umiObjectsCollection = $umiObjectsCollection ? $umiObjectsCollection : umiObjectsCollection::getInstance();
		$object = $page->getObject();
		$line_arr = array(
				  '@id' => $page->id,
				  '@link' => $page->link,
				  '@name' => strip_tags(htmlspecialchars_decode($page->name)),
				  '@sku' => $page->sku,
				  '@common_quantity' => $page->common_quantity,
				  //'@price' => $page->price,
				  //'@old_price' => $page->old_price,
				  );
           $line_arr['@common_quantity'] = $line_arr['@common_quantity'] ? $line_arr['@common_quantity'] : 1;
					$parent = $umiHierarchy->getElement($page->getParentId());


		$line_arr['price'] = self::getPrice($page->id,$umiHierarchy,$umiObjectsCollection);
		$price = "";
		if(array_key_exists("old_price",$line_arr['price']) !== false){
			$line_arr['@old_price'] = $line_arr['price']['old_price'];
            $price = '<div class="old">'.$line_arr['price']['old_price'].' <div class="currency">ц</div></div>'.$line_arr['price']['price'].' <div class="currency">ц</div>';
		}else{
            $price = $line_arr['price']['price'].' <div class="currency">ц</div>';
		}

		$img = $page->thumb;

		if($img){
			//$thumb = $img->getFilePath(true);
		}else{
			$img = $page->photo;
			if(!$img){
				$img = $page->image;
				if(!$img){
                    $parent = self::get1CParent($page);
                    if($parent){
                    	$img = $parent->thumb;
                    	if(!$img){
                            $img = $parent->photo;
                            if(!$img){
                                $img = new umiFile("./images/nofoto.jpg");
							}
						}else{
                            $img = new umiFile("./images/nofoto.jpg");
						}
					}else{
                        $img = new umiFile("./images/nofoto.jpg");
					}
				}
			}
		}



		if($img->getIsBroken()){
			$img = new umiFile("./images/nofoto.jpg");
		}
		$thumb = self::makeThumbnailNew($img->getFilePath(),241,316);
		//$small_thumb = self::makeThumbnailNew($img->getFilePath(),116,136);
		$line_arr['img'] = $thumb;
		$img_html = "";
		$img_html  = '<img  src="'.$line_arr['img']['src'].'" alt="'.$line_arr['@name'].'">';
		$sticker = self::getSticker($page->flag,$umiObjectsCollection);

		$info = array();


		if($sizes = $page->sizes){
			$objects = array();
			foreach($sizes as $size){
				//$object = $umiObjectsCollection->getObject($size['rel']);
                $object = $umiObjectsCollection->getObject($size);
				if($object){
                    $objects[] = $object->name;
				}
			}
			if(sizeof($objects)){
				$info[] = '<div class="sizes">'.getLabel("sizes").' '.implode(" ",$objects).'</div>';
			}
		}

		if($line_arr['@sku']){
            $info[] = '<div class="articul">'.getLabel("sku").' '.$line_arr['@sku'].'</div>';
		}
		if(sizeof($info)){
            $info = '<div class="info">'.implode("",$info).'</div>';
		}else{
            $info = '';
		}



           $line_arr['html'] = '<a href="'.$line_arr['@link'].'" class="product id_'.$line_arr['@id'].'">
									'.$sticker.'
									<div class="thumb">'.$img_html.$info.'</div>
									<div class="name">'.$line_arr['@name'].'</div>
									<div class="price">'.$price.'</div>
								</a>';

			switch($className){
				case "slide":
				case "slide_features": {$line_arr['html'] = '<div class="slide">'.$line_arr['html'].'</div>';}; break;
				case "compare": {
					$line_arr['html'] = '<a href="#" class="del_compare" data-id="'.$line_arr['@id'].'"></a>
										<a href="'.$line_arr['@link'].'" class="product id_'.$line_arr['@id'].'">
											<div class="thumb">
												'.$img_html.'
											</div>
											<div class="price">'.$price.'</div>
											<div class="name">'.$line_arr['@name'].'</div>
											<div class="product_link">'.getLabel('go_to_product').'</div>
										</a>';
				}; break;
				default: {$line_arr['html'] = $line_arr['html'];}; break;

			}




		//var_dump($line_arr); exit;
		$cache->saveData($cacheName,$line_arr,36000);
		return $line_arr;

	}

	public function get1CParent($page){
		if($parent = $page->getValue('1c_parent_id')){
			$pages = new selector('pages');
			$pages->types('hierarchy-type')->name('catalog', 'object');
            $pages->types('object-type')->id($page->getObjectTypeId());
			//$pages->where('hierarchy')->page($page->id)->childs(1);
            $pages->where("is_visible")->equals(array(0,1));
            $pages->where("is_active")->equals(array(0,1));
			$pages->where("1c_product_id")->equals($parent);

			$pages->order("id")->asc(true);
			if($pages->length){
				$parent = $pages->first;
			}else{
				$parent = false;
			}

		}

		return $parent;

	}


	public function getSticker($stickerId = false,$umiObjectsCollection = false){
		if(!$stickerId) return "";
		$umiObjectsCollection = $umiObjectsCollection ? $umiObjectsCollection : umiObjectsCollection::getInstance();
		$sticker = "";
		switch($stickerId){
            case 1331: {
                $object = $umiObjectsCollection->getObject($stickerId);
                $sticker = '<div class="sticker stock">'.$object->name.'</div>';
            }; break;
            case 1332: {
                $object = $umiObjectsCollection->getObject($stickerId);
                $sticker = '<div class="sticker new">'.$object->name.'</div>';
            }; break;
            case 1333: {
                $object = $umiObjectsCollection->getObject($stickerId);
                $sticker = '<div class="sticker hit">'.$object->name.'</div>';
            }; break;

			default: {
									$object = $umiObjectsCollection->getObject($stickerId);
									$sticker = '<div class="sticker new">'.$object->name.'</div>';
			}; break;
		}
		return $sticker;
	}

	public function getProductProperties($pageId = false){

		$pageId = $pageId ? $pageId : cmsController::getInstance()->getCurrentElementId();
		$umiHierarchy = umiHierarchy::getInstance();

		$umiObjectType = umiObjectTypesCollection::getInstance();

		$page = $umiHierarchy->getElement($pageId);
		$object = $page->getObject();

		$type = $umiObjectType->getType($page->getObjectTypeId());
		$group = $type->getFieldsGroupByName('properties');
		$fields = $group->getFields();

		$product_fields = array();

		foreach($fields as $field){
			if(self::isProductField($field)){
				$product_fields[] = $object->getPropByName($field->getName());
				continue(1);
			}
		}

		$features = "";
		foreach($product_fields as $property){
			$value = $page->getValue($property->getName());
			if($value){
				$data = self::getPropertyData($property);
				$features.='<div class="item"><div class="name">'.$data['title'].'</div><div class="val"> '.$data['value'].'</div></div>';
			}
		}


		return $features;

	}



	/**
	 * Возвращает данные свойства для вывода в шаблоне товара
	 * @param iUmiObjectProperty|null $property свойство
	 * @return array [
	 *   'title' => %title%,
	 *   'name' => %name%,
	 *   'value' => %value%
	 * ]
	 */
	public function getPropertyData($property) {
		if (!$property instanceof iUmiObjectProperty) {
			return [
				'title' => '',
				'name' => '',
				'value' => '',
			];
		}

		$dataType = $property->getDataType();
		$value = $property->getValue();

		switch ($dataType) {
			case 'date': {
				$preparedValue = '';

				if ($value instanceof umiDate) {
					$preparedValue = $value->getFormattedDate('d M Y');
				}

				break;
			}

			case 'symlink': {
				$preparedValue = $this->getSymlinkValue($property);
				break;
			}

			case 'relation': {
				$preparedValue = $this->getRelationValue($property);
				break;
			}

			case 'optioned': {
				$preparedValue = $this->getOptionedValue($property);
				break;
			}

			case 'boolean': {
				$isTrue = $value;
				$label = $isTrue ? 'yes' : 'no';
				$preparedValue = $this->translate($label);
				break;
			}

			default: {
				$preparedValue = (string) $value;
			}
		}

		return [
			'title' => $property->getTitle(),
			'name' => $property->getName(),
			'value' => $preparedValue,
		];
	}

	/**
	 * Возвращает значение свойства типа `symlink`
	 * @param iUmiObjectProperty $property
	 * @return string
	 */
	private function getSymlinkValue(iUmiObjectProperty $property) {
		$templateEngine = $this->getTemplateEngine();
		$linkedPages = (array) $property->getValue();
		$value = '';

		/** @var iUmiHierarchyElement $page */
		foreach ($linkedPages as $page) {
			$variables = [
				'path' => $this->getPath($page),
				'name' => $page->getName()
			];

			$value .= $templateEngine->render($variables, 'catalog/product/main/properties/symlink');
		}

		return $value;
	}

	/**
	 * Возвращает значение свойства типа `relation`
	 * @param iUmiObjectProperty $property
	 * @return string
	 */
	private function getRelationValue(iUmiObjectProperty $property) {
		$umiObjects = umiObjectsCollection::getInstance();
		$relationList = (array) $property->getValue();
		$value = [];

		foreach ($relationList as $relationId) {
			$relation = $umiObjects->getObject($relationId);

			if ($relation instanceof iUmiObject) {
				$value[] = $relation->getName();
			}
		}

		return implode(', ', $value);
	}

	/**
	 * Возвращает значение свойства типа `optioned`
	 * @param iUmiObjectProperty $property
	 * @return string
	 */
	private function getOptionedValue(iUmiObjectProperty $property) {
		$umiObjects = umiObjectsCollection::getInstance();
		$optionList = (array) $property->getValue();
		$value = [];

		foreach ($optionList as $optionData) {
			$relationId = isset($optionData['rel']) ? $optionData['rel'] : '';
			$option = $umiObjects->getObject($relationId);

			if ($option instanceof iUmiObject) {
				$value[] = $option->getName();
			}
		}

		return implode(', ', $value);
	}


	public function getCategoryMenuNew($pageId = false,$current_page_id = false){
		$result = array();
		$current_page_id = $current_page_id ? $current_page_id : cmsController::getInstance()->getCurrentElementId();


		//$current_page_id = 1;
		$umiHierarchy = umiHierarchy::getInstance();
		/*
		$cacheName = 'getAllParents_'.$current_page_id;
		$cache = cacheFrontend::getInstance();
		$parents = $cache->loadData($cacheName);

		if(!$parents){
			$parents = $umiHierarchy->getAllParents($current_page_id,true);
			$cache->saveData($cacheName,$parents,36000);
		}
		*/
		$parents = $umiHierarchy->getAllParents($current_page_id,true);
        $langId = cmsController::getInstance()->getCurrentLang()->getId();
		$cacheName = 'getChildsMenuNew_'.$langId;
		$cache = cacheFrontend::getInstance();
		$childs = $cache->loadData($cacheName);

		if(!$childs){
				$umiTypesHelper = umiTypesHelper::getInstance();
				$hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName('catalog', 'category');
				$childs = $umiHierarchy->getChildrenTree($pageId,false,true,5,$hierarchyTypeId);
				$cache->saveData($cacheName,$childs,36000);
		}

		//var_dump($childs);exit;
		$result = array();


		$result = self::getChildsMenuNew($childs,$parents,0);


		$result = array('subnodes:items' => $result);
		foreach($parents as $parentId){
			$result['subnodes:parents'][] = array(
				'@id' => $parentId
			);
		}


		$result = def_module::parseTemplate("",$result);
		/*
		$cache->saveData($cacheName,$result,36000);
		*/
		return $result;
	}

	private function getChildsMenuNew($childs, $parents = false, $deph = 1,$umiHierarchy = false){
		$umiHierarchy = $umiHierarchy ? $umiHierarchy : umiHierarchy::getInstance();
		$result = array();
		$deph++;
		$cache = cacheFrontend::getInstance();
		foreach($childs as $parentId => $this_childs){
			$page = $umiHierarchy->getElement($parentId);
			if($page){
                $typeId = $page->getObjectTypeId();
                if($typeId == "178"){
                    $cacheName = 'getChilds_'.$parentId;
                    $this_childs = $cache->loadData($cacheName);
                    if(!$this_childs){
                        $umiTypesHelper = umiTypesHelper::getInstance();
                        $hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName('catalog', 'category');
                        $this_childs = $umiHierarchy->getChildrenTree(0,false,true,2,$hierarchyTypeId);

                        $cache->saveData($cacheName,$line_arr,36000);
                    }



                }

                $cacheName = 'getChildsMenuNew_'.$parentId;
                $line_arr = $cache->loadData($cacheName);
                if($line_arr){
                    $line_arr['@cache'] = 'ok';
                }else{
                    $line_arr['@cache'] = 'no';
                }

                if((sizeof($line_arr) == 1)){

                    //if(true){
//			var_dump(11);exit;

                    $img = $page->menu_pic_a;
                    $img = $img ? $img : new umiFile("./images/nofoto.jpg");
                    $img = $img ? $img->getFilePath(true) : false;

                    $line_arr = array(
                        '@id' => $parentId,
                        '@name' => $page->name,
                        '@link' => $page->link,
                        '@img' => $img,
                        '@cache' => "no",
                        '@typeId' => $typeId,
                        '@is-default' => $page->getIsDefault()
                    );


                    $this_childs = self::getChildsMenuNew($this_childs,$parents,$deph,$umiHierarchy);
                    if(sizeof($this_childs)){
                        $line_arr['subnodes:items'] = $this_childs;
                    }

                    $cache->saveData($cacheName,$line_arr,36000);
                    //$line_arr['@active'] = $is_active;

                }else{

                }
                /*
                $pageParents = $umiHierarchy->getAllParents($parentId,true);
                $common_parents = array_intersect($parents,$pageParents);
                $is_active = ($common_parents == $pageParents) ? "active" : false;
                $line_arr['@active'] = $is_active;
                */


                $result[] = $line_arr;
			}

		}

		return $result;

	}






	public function getCategoryMenu($catalogId = 4){
		$result = array();
		$current_page_id = cmsController::getInstance()->getCurrentElementId();
		//$current_page_id = 147;
		$parents = umiHierarchy::getInstance()->getAllParents($current_page_id,true);
		$cacheName = 'getCategoryMenu'.$current_page_id;
		$cache = cacheFrontend::getInstance();
		$result = $cache->loadData($cacheName);

		if($result){
			$result['cache'] = 'ok';
			return $result;
		}

		$result = array();

		$result = self::getChildsMenu($catalogId,$parents,0);


		$result = def_module::parseTemplate("",array(
							     "subnodes:items" => $result,
							     'total' => sizeof($result),
									 'cache' => "no",
							     ));
		$cache->saveData($cacheName,$result,36000);
		return $result;
	}

	private function getChildsMenu($pageId = 0, $parents = false, $deph = 1){
		if(!$parents){
			$current_page_id = cmsController::getInstance()->getCurrentElementId();
			$parents = umiHierarchy::getInstance()->getAllParents($current_page_id,true);
		}
		$cacheName = 'getChildsMenu_'.$pageId.$deph;
		$cache = cacheFrontend::getInstance();
		$pages = $cache->loadData($cacheName);
		//var_dump($result); exit;
		if(!$pages){
			$pages = new selector('pages');

			$pages->types('hierarchy-type')->name('catalog', 'category');
			if($pageId == 100){
				$pages->where("is_visible")->equals(1);
			}

			$pages->where('hierarchy')->page($pageId)->childs(1);
			$cache->saveData($cacheName,$pages->result,36000);
		}
		$result = array();
		foreach($pages as $page){
			$img = $page->menu_pic_a;
			//$img = $img ? $img : new umiFile("./images/nofoto.jpg");
			$img = $img ? $img->getFilePath(true) : false;


			//$cat_img = $page->cat_img;
			//$cat_img = $cat_img ? $cat_img : new umiFile("./images/nofoto.jpg");
			//$img = self::makeThumbnailNew($img->getFilePath(),305,191);
			$line_arr = array(
					  '@id' => $page->id,
					  '@name' => $page->name,
					  '@link' => $page->link,
					  '@img' => $img,
						'@typeId' => $page->getObjectTypeId(),
					//  '@cat_img' => $cat_img->getFilePath(true),
					  );

			$childs = array();
			if($page->show_submenu){
				$childs = self::getChildsMenu($page->id,$parents,($deph+1));
			}
			$is_active = (array_search($page->id,$parents) !== false) ? "active" : false;
			$line_arr['@active'] = $is_active;
			if(sizeof($childs)){
				$line_arr['subnodes:items'] = $childs;
			}
			$result[] = $line_arr;
		}
		return $result;
	}



	/*
	 *	Превью, которое ставит картинку по ценру, а фон заливает белым
	 */
	public function makeThumbnailNew($path, $width = 'auto', $height = 'auto', $template = "default", $returnArrayOnly = false, $flags = 0, $quality = 100, $logo = false) {

		if(!$template) $template = "default";

		$flags = (int)$flags;

		$image = new umiImageFile($path);



		$file_name = $image->getFileName();
		$file_ext = strtolower($image->getExt());
		$file_ext = ($file_ext=='bmp'?'jpg':$file_ext);

		$thumbPath = sha1($image->getDirName());


		if (!is_dir($_SERVER['DOCUMENT_ROOT'].'/images/thumbs/'.$thumbPath)) {
			mkdir($_SERVER['DOCUMENT_ROOT'].'/images/thumbs/'.$thumbPath, 0755, true);
		}


		$allowedExts = Array('gif', 'jpeg', 'jpg', 'png', 'bmp');
		//var_dump($path); exit;
		if(!in_array($file_ext, $allowedExts)) return "";

		$file_name = substr($file_name, 0, (strlen($file_name) - (strlen($file_ext) + 1)) );
		$file_name_new = $file_name . "_" . $width . "_" . $height . "_" .$image->getExt(). "." . $file_ext;
		$path_new = $_SERVER['DOCUMENT_ROOT'].'/images/thumbs/' .$thumbPath."/". $file_name_new;

		if(!file_exists($path_new) || filemtime($path_new) < filemtime($path)) {
		//if(true) {
			if(file_exists($path_new)) {
				unlink($path_new);
			}
			$width_src = $image->getWidth();
			$height_src = $image->getHeight();

			if(!($width_src && $height_src)) {
				throw new coreException(getLabel('error-image-corrupted', null, $path));
			}

			if(!$width_src) return false;

			$rgb=0xffffff; //цвет заливки несоответствия
			$size = getimagesize($path);//узнаем размеры картинки (дает нам масив size)

			if($width == 'auto'){
			    $width = $size[0];
			}

			if($height == 'auto'){
			    $height = $size[1];
			}
			$format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1)); //определяем тип файла
			$icfunc = "imagecreatefrom" . $format;   //определение функции соответственно типу файла
			if($format == "x-ms-bmp"){
				$icfunc = "imagecreatefromxbm";
			}
			//var_dump($icfunc); exit;
			if (!function_exists($icfunc)) return false;  //если нет такой функции прекращаем работу скрипта
			$x_ratio = $width / $size[0]; //пропорция ширины будущего превью
			$y_ratio = $height / $size[1]; //пропорция высоты будущего превью
			$ratio       = min($x_ratio, $y_ratio);
			$use_x_ratio = ($x_ratio == $ratio); //соотношения ширины к высоте
			$new_width   = $use_x_ratio  ? $width  : floor($size[0] * $ratio); //ширина превью
			$new_height  = !$use_x_ratio ? $height : floor($size[1] * $ratio); //высота превью
			$new_left    = $use_x_ratio  ? 0 : floor(($width - $new_width) / 2); //расхождение с заданными параметрами по ширине
			$new_top     = !$use_x_ratio ? 0 : floor(($height - $new_height) / 2); //расхождение с заданными параметрами по высоте
			$img = imagecreatetruecolor($width,$height); //создаем вспомогательное изображение пропорциональное превью
			imagefill($img, 0, 0, $rgb); //заливаем его…
			$photo = $icfunc($path); //достаем наш исходник
			imagecopyresampled($img, $photo, $new_left, $new_top, 0, 0, $new_width, $new_height, $size[0], $size[1]); //копируем на него нашу превью с учетом расхождений
			switch($format){
				case 'gif':
					$res = imagegif($img, $path_new);
					break;
				case 'png':
					$res = imagepng($img, $path_new);
					break;
				default:
					$res = imagejpeg($img, $path_new, $quality);
			}
			//imagejpeg($img); //выводим результат (превью картинки)
			// Очищаем память после выполнения скрипта
			imagedestroy($img);
			imagedestroy($photo);

		}

		//Parsing
		$value = new umiImageFile($path_new);
		if($logo){
			umiImageFile::addWatermark($path_new);
		}
		$arr = Array();
		$arr['size'] = $value->getSize();
		$arr['filename'] = $value->getFileName();
		$arr['filepath'] = $value->getFilePath();
		$arr['src'] = $value->getFilePath(true);
		$arr['ext'] = $value->getExt();
		$arr['original'] = $image->getFilePath(true);
		$arr['width'] = $value->getWidth();
		$arr['height'] = $value->getHeight();

		$arr['void:template'] = $template;

		if(cmsController::getInstance()->getCurrentMode() == "admin") {
			$arr['src'] = str_replace("&", "&amp;", $arr['src']);
		}

		if($returnArrayOnly) {
			return $arr;
		} else {
			list($tpl) = def_module::loadTemplates("thumbs/".$template, "image");
			return def_module::parseTemplate($tpl, $arr);
		}
	}





};


function imagecreatefrombmp( $filename ){
	    $file = fopen( $filename, "rb" );
	    $read = fread( $file, 10 );
	    while( !feof( $file ) && $read != "" )
	    {
		$read .= fread( $file, 1024 );
	    }
	    $temp = unpack( "H*", $read );
	    $hex = $temp[1];
	    $header = substr( $hex, 0, 104 );
	    $body = str_split( substr( $hex, 108 ), 6 );
	    if( substr( $header, 0, 4 ) == "424d" )
	    {
		$header = substr( $header, 4 );
		// Remove some stuff?
		$header = substr( $header, 32 );
		// Get the width
		$width = hexdec( substr( $header, 0, 2 ) );
		// Remove some stuff?
		$header = substr( $header, 8 );
		// Get the height
		$height = hexdec( substr( $header, 0, 2 ) );
		unset( $header );
	    }
	    $x = 0;
	    $y = 1;
	    $image = imagecreatetruecolor( $width, $height );
	    foreach( $body as $rgb )
	    {
		$r = hexdec( substr( $rgb, 4, 2 ) );
		$g = hexdec( substr( $rgb, 2, 2 ) );
		$b = hexdec( substr( $rgb, 0, 2 ) );
		$color = imagecolorallocate( $image, $r, $g, $b );
		imagesetpixel( $image, $x, $height-$y, $color );
		$x++;
		if( $x >= $width )
		{
		    $x = 0;
		    $y++;
		}
	    }
	    return $image;
	}

?>
