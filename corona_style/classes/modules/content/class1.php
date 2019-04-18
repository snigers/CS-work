<?php
	class content_custom{

			public function getInst(){

            $_GET['view'] = 8;
            $_GET['inline'] = 8;
            $_GET['width'] = 1770;

                include CURRENT_WORKING_DIR.'/inwidget/classes/InstagramScraper.php';
                include CURRENT_WORKING_DIR.'/inwidget/classes/Unirest.php';
                include CURRENT_WORKING_DIR.'/inwidget/classes/InWidget.php';
                $config = array(
                    'LOGIN' => 'coronastyle.ru',
                    'HASHTAG' => '',
                    'ACCESS_TOKEN' => '',
                    'tagsBannedLogins' => '',
                    'tagsFromAccountOnly' => false,
                    'imgRandom' => false,
                    'imgCount' => 6,
                    'view' => 6,

                    'cacheExpiration' => 6,
                    'cacheSkip' => false,
                    'cachePath' =>  CURRENT_WORKING_DIR.'/inwidget/cache/',
                    'skinDefault' => 'default',
                    'skinPath'=> 'skins/',
                    'langDefault' => 'ru',
                    'langAuto' => false,
                    'langPath' => CURRENT_WORKING_DIR.'/inwidget/langs/',
                );



			$inWidget = new \inWidget\Core($config);

			$inWidget->inline = 6;
			$inWidget->view = 6;


			$inWidget->setOptions();
                //$inWidget->config['imgRandom'] = true;
            $inWidget->getData();


            $result = array();
            $result['html'] = '';




//			if ($_SERVER['REMOTE_ADDR']=='83.149.44.240') print_r($inWidget);
            if (isset($inWidget->data->images))
                $count = $inWidget->countAvailableImages($inWidget->data->images);
            else $count = 0;
            $i=1;
            if($count>0) {
                if($inWidget->config['imgRandom'] === true) shuffle($inWidget->data->images);
                //$inWidget->data->images = array_slice($inWidget->data->images,0,$inWidget->view);

                foreach ($inWidget->data->images as $key=>$item){
                    if($inWidget->isBannedUserId($item->authorId) == true) continue;

                    switch ($inWidget->preview){
                        case 'large':
                            $thumbnail = $item->large;
                            break;
                        case 'fullsize':
                            $thumbnail = $item->fullsize;
                            break;
                        default:
                            $thumbnail = $item->small;
                    }


                    //echo '<a href="'.$item->link.'" class="image" target="_blank"><span style="background-image:url('.$thumbnail.');">&nbsp;</span></a>';


                    $result['html'].='<a href="'.$item->link.'" target="_blank" rel="noopener"><img src="'.$thumbnail.'" alt="" /></a>';

                    $i++;
                    if($i >= $inWidget->view) break;
                }

            }
            return $result;


            return 1;


        }

		public function getRegions(){
			$pageId = 201;
			$current_page_id = cmsController::getInstance()->getCurrentElementId();
			$typeId = 148;
			$pages = new selector('pages');
			$pages->types('object-type')->id($typeId);
			$pages->types('hierarchy-type')->name('content', 'page');
			$result = array();
			foreach($pages as $page){
				$is_active = ($page->id == $current_page_id) ? "active" : false;
				$result[] = array(
					'@id' => $page->id,
					'@name' => $page->name,
					'@link' => $page->link,
					'@coords' => $page->coords,
					'@id_map' => $page->id_map,
					'@active' => $is_active,
				);
			}
			$result = def_module::parseTemplate("",array('subnodes:items' => $result));
			return $result;

		}

		public function getMenuNew($pageId = false,$current_page_id = false,$deph = 5){
		$result = array();
		$current_page_id = $current_page_id ? $current_page_id : cmsController::getInstance()->getCurrentElementId();


		//$current_page_id = 187;
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



		$args = func_get_args ();
		$args['current_page_id'] = $current_page_id;
		$args = array_merge($args,$_POST,$_GET);
		unset($args["path"]);
		unset($args["umi_authorization"]);

		$cacheName = 'getMenuNew_'.md5(serialize($args));

		$cache = cacheFrontend::getInstance();
		$childs = $cache->loadData($cacheName);

		if(!$childs){
				$umiTypesHelper = umiTypesHelper::getInstance();
				//$hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName('content', 'page');
				$hierarchyTypeId = false;
				$childs = $umiHierarchy->getChildrenTree($pageId,false,false,$deph,$hierarchyTypeId,$umiHierarchy);
				$cache->saveData($cacheName,$childs,36000);
		}


		//var_dump($childs);exit;
		$result = array();


		$result = self::getChildsMenuNew($childs,$parents,0,$umiHierarchy,$deph);


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

	private function getChildsMenuNew($childs, $parents = false, $deph = 1,$umiHierarchy = false,$maxDeph = 5){
		$umiHierarchy = $umiHierarchy ? $umiHierarchy : umiHierarchy::getInstance();
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		$result = array();
		$deph++;
		$cache = cacheFrontend::getInstance();
		foreach($childs as $parentId => $this_childs){
			$page = $umiHierarchy->getElement($parentId);

			$cacheName = 'getChildsMenuNew_'.$parentId."_".$maxDeph;
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
					'@name2' => $page->right_menu_name,
					'@link' => $page->link,
					'@img' => $img,
					'@typeId' => $page->getObjectTypeId()
				//	'page' => $page
				);
				$icon_number = $page->icon_number;
				$icon_number = $icon_number ? $umiObjectsCollection->getObject($icon_number)->name : false;
				$line_arr['@icon_number'] = $icon_number;

				if($deph <= $maxDeph){
						$this_childs = self::getChildsMenuNew($this_childs,$parents,$deph,$umiHierarchy);
				}

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

		return $result;

	}


		public function fixField(){
			$fieldId = 237;
			$umiFieldsCollection = umiFieldsCollection::getInstance();
			$field = $umiFieldsCollection->getField($fieldId);
			$field->setIsLocked(false);
			$field->setIsSystem(false);
			var_dump("ok"); exit;
		}

		public function getCapabilities(){
			$typeId = 143;
			$pages = new selector('pages');
			$pages->types('object-type')->id($typeId);
			$pages->types('hierarchy-type')->name('content', 'page');
			$pages->limit(0,1);
			foreach($pages as $page){
				$result['page'] = array(
					'@id' => $page->id,
					'@name' => $page->name,
					'@link' => $page->link,
					'@link_name' => $page->link_name
				);
				$pages = new selector('pages');
				$pages->types('object-type')->id(142);
				$pages->types('hierarchy-type')->name('content', 'page');
				$pages->where('hierarchy')->page($page->id)->childs(1);
				foreach($pages as $page){
					$img = $page->img;
					$img = $img ? $img->getFilePath(true) : false;
					$name = $page->nazvanie_html;
					$name = $name ? $name : $page->name;
					$result['subnodes:items'][] = array(
						'@id' => $page->id,
						'@name' => $name,
						'@page_name' => $page->name,
						'@link' => $page->link,
						'@img' => $img,
						'@descr' => $page->descr,
					);
				}
				return $result;

			}

		}

		public function setGallery(){
			$path = CURRENT_WORKING_DIR."/images/hr/temp/gallery/*.*";
			$files = glob($path);
			$umiHierarchy = umiHierarchy::getInstance();
			$thisPageId = 190;
			$page = $umiHierarchy->getElement($thisPageId);
			$thisParentId = $page->getParentId();
			$i = 1;
			foreach($files as $file){
				$newPageId = $umiHierarchy->cloneElement($thisPageId,$thisParentId);
				$newPage = $umiHierarchy->getElement($newPageId);
				$name = "Хрустальные люстры в интерьере ".$i;
				$newPage->name = $name;
				$newPage->h1 = $name;

				$i++;
				$umiFile = new umiFile($file);
				$newFile = new umiFile(".".$umiFile->getFilePath(true));
				$newPage->fs_file = $newFile;
				$newPage->commit(true);
			}
			var_dump('ok'); exit;

		}

		public function testMail(){
			$oMail = new umiMail();
			$oMail->addRecipient( 'rodogor@gmail.com', 'rodogor@gmail.com' );
			/*
			$oMail->addRecipient( 'posthuman.d@gmail.com', 'posthuman.d@gmail.com' );
			$oMail->addRecipient( 'dn@tochka-ru.ru', 'dn@tochka-ru.ru' );
			*/


			$oMail->setFrom('site@shoesexpert.ru', 'site@shoesexpert.ru');
			$oMail->setSubject("test2");
			$oMail->setContent("test2");
			$oMail->commit();
			$oMail->send();

			var_dump('ok'); exit;
			//----------------------------
		}




		public function getMenu($catalogId = 0){
			$result = array();
			$current_page_id = cmsController::getInstance()->getCurrentElementId();
			//$current_page_id = 147;
			$parents = umiHierarchy::getInstance()->getAllParents($current_page_id,true);
			$cacheName = 'getMenu'.$current_page_id;
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

				//$pages->types('hierarchy-type')->name('catalog', 'category');
				if($pageId == 0){
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


		public function getGuidObject($typeId = 146){
			$objects = new selector('objects');
			$objects->types('object-type')->id($typeId);
			$result = array();
			foreach($objects as $object){
				$result[] = array(
					'@id' => $object->id,
					'@name' => $object->name,
					'@img' => $object->img,
				);
			}
			return def_module::parseTemplate("",array(
				'subnodes:items' => $result,
				'total' => $objects->length
			));
		}

		public function generateNumPage($total, $per_page, $template = "default", $varName = "p", $max_pages = false,$page_link = "") {

			$per_page = intval($per_page);
			if($per_page == 0) $per_page = $total;
			if(!$template) $template = "default";
			if(!$varName) $varName = "p";
			list(
				$template_block, $template_block_empty, $template_item, $template_item_a, $template_quant,
				$template_tobegin, $template_tobegin_a, $template_toend, $template_toend_a, $template_toprev,
				$template_toprev_a, $template_tonext, $template_tonext_a
			) = def_module::loadTemplates("numpages/".$template,
				"pages_block", "pages_block_empty", "pages_item", "pages_item_a", "pages_quant", "pages_tobegin",
				"pages_tobegin_a", "pages_toend", "pages_toend_a", "pages_toprev", "pages_toprev_a", "pages_tonext", "pages_tonext_a"
			);

			$isXslt = def_module::isXSLTResultMode();
			if(($total <= 0) || ($total <= $per_page)) {
				return ($isXslt) ? "" : $template_block_empty;
			}


			$key = $varName;
			$page_current = (int) getRequest($key);

			$params = $_GET;
			if(array_key_exists($key, $params)) {
				unset($params[$key]);
			}
			unset($params['path'], $params['umi_authorization']);

			if(array_key_exists('scheme', $params)) {
				unset($params['scheme']);
			}

			if($max_pages === false) {
				$max_pages = 5;
			}

			$block_arr = Array();

			$pages = Array();
			$pages_count = ceil($total / $per_page);
			if(!$pages_count) $pages_count = 1;

			$params = self::protectParams($params);

			$q = (sizeof($params)) ? "&" . http_build_query($params, '', '&') : "";

			if ($isXslt == false) {
				$q = str_replace("%", "&#37;", $q);
			}

			$q = str_replace(array("<", ">", "%3C", "%3E"),
							 array("&lt;", "&gt;", "&lt;", "&gt;"), $q);

			$items = "";

			for($i = 0; $i < $pages_count; $i++) {
				$line_arr = Array();

				$n = $i + 1;
				if(($page_current - $max_pages) >= $i) continue;
				if(($page_current + $max_pages) <= $i) break;

				if($page_current != "all") {
					$tpl = ($i == $page_current) ? $template_item_a : $template_item;
				} else {
					$tpl = $template_item;
				}

				$link = "?{$key}={$i}" . $q;

				$line_arr['attribute:link'] = $link;
				$line_arr['attribute:page-num'] = $i;

				if($page_current == $i) {
					$line_arr['attribute:is-active'] = true;
				}

				$line_arr['node:num'] = $n;
				//Bugfix #0002780
				//$line_arr['void:quant'] = ($i < ($pages_count - 1)) ? $template_quant : "";
				$line_arr['void:quant'] = (($i < (($page_current + $max_pages)-1)) and ($i < ($pages_count - 1))) ? $template_quant : "";
				if($page_current == $i) {
					$items.='<a href="'.$page_link.$link.'" class="active" data-num="'.$i.'">'.$n.'</a>';
				}else{
						$items.='<a href="'.$page_link.$link.'" data-num="'.$i.'">'.$n.'</a>';
				}


				$pages[] = def_module::parseTemplate($tpl, $line_arr);
			}

			$block_arr['subnodes:items'] = $block_arr['void:pages'] = $pages;
			if (!$isXslt) {
				$block_arr['tobegin'] = ($page_current == 0 || $pages_count <= 1) ? $template_tobegin_a : $template_tobegin;
				$block_arr['toprev']  = ($page_current == 0 || $pages_count <= 1) ? $template_toprev_a  : $template_toprev;
				$block_arr['toend'] =  ($page_current == ($pages_count - 1) || $pages_count <= 1) ? $template_toend_a : $template_toend;
				$block_arr['tonext'] = ($page_current == ($pages_count - 1) || $pages_count <= 1) ? $template_tonext_a : $template_tonext;
			}



			if ($page_current != 0) {
				$tobegin_link = "?{$key}=0" . $q;
				if($isXslt) {
					$block_arr['tobegin_link'] = array(
						'attribute:page-num' => 0,
						'node:value' => $tobegin_link
					);
				} else {
					$block_arr['tobegin_link'] = $tobegin_link;
				}
			}

			if ($page_current < $pages_count - 1) {
				$toend_link = "?{$key}=" . ($pages_count - 1) . $q;
				if($isXslt) {
					$block_arr['toend_link'] = array(
						'attribute:page-num' => $pages_count - 1,
						'node:value' => $toend_link
					);
				} else {
					$block_arr['toend_link'] = $toend_link;
				}
			}
			$toprev_link = "";
			if($page_current - 1 >= 0) {
				$toprev_link = "?{$key}=" . ($page_current -1)  . $q;
				if($isXslt) {
					$block_arr['toprev_link'] = array(
						'attribute:page-num' => $page_current -1,
						'node:value' => $toprev_link
					);
				} else {
					$block_arr['toprev_link'] = $toprev_link;
				}
				$toprev_link = '<a href="'.$page_link.$toprev_link.'" data-num="'.($page_current - 1).'" class="prev"></a>';
			}
			$tonext_link = "";
			if($page_current < $pages_count - 1) {
				$tonext_link = "?{$key}=" . ($page_current + 1) . $q;

				if($isXslt) {
					$block_arr['tonext_link'] = array(
						'attribute:page-num' => $page_current + 1,
						'node:value' => $tonext_link
					);
				} else {
					$block_arr['tonext_link'] = $tonext_link;
				}
				$tonext_link = '<a href="'.$page_link.$tonext_link.'" data-num="'.($page_current + 1).'" class="next"></a>';
			}

			$html = '<div class="pagination">
								'.$toprev_link.'
								'.$items.'
								'.$tonext_link.'
							</div>';

			$block_arr['html'] = $html;


			$block_arr['current-page'] = (int) $page_current;
			return def_module::parseTemplate($template_block, $block_arr);
		}


		private function protectParams($params) {
			foreach($params as $i => $v) {
				if(is_array($v)) {
					$params[$i] = self::protectParams($v);
				} else {
					$v = htmlspecialchars($v);
					$params[$i] = str_replace(array("%", "<", ">", "%3C", "%3E"),
											  array("&#037;", "&lt;", "&gt;", "&lt;", "&gt;"), $v);
				}
			}
			return $params;
		}



		public function getOurWorks($pageId = false){
			$pageId = $pageId ? $pageId : cmsController::getInstance()->getCurrentElementId();
			$langId = cmsController::getInstance()->getCurrentLang()->getId();
			$cacheName = 'getOurWorks_'.$pageId."_".$langId;
			$cache = cacheFrontend::getInstance();
			$result = $cache->loadData($cacheName);
			if($result){
				return $result;
			}
			$umiHierarchy = umiHierarchy::getInstance();
			$page = $umiHierarchy->getElement($pageId);
			$works = $page->works;
			$result = array();
			foreach($works as $work){
				$line_arr = array(
					'@id' => $work->id,
					'@name' => $work->name,
					'@link' => $work->link,
				);
				$images = $work->photos;
				$images_html = "";
				foreach($images as $image){
					$images_html.='<div class="slide"><img src="'.$image->getFilePath(true).'" alt=""></div>';
				}
				$descript = $work->descript;
				$descript = $descript ? '<div class="item"><div class="title">Описание проекта:</div><div>'.$descript.'</div></div>' : '';
				$trebovaniya = $work->trebovaniya;
				$trebovaniya = $trebovaniya ? '<div class="item"><div class="title">Требования:</div><div>'.$trebovaniya.'</div></div>' : '';
				$slozhnosti = $work->slozhnosti;
				$slozhnosti = $slozhnosti ? '<div class="item"><div class="title">Сложности:</div><div>'.$slozhnosti.'</div></div>' : '';


				$line_arr['html'] = '<div class="slide">
		      <div class="cont">
		        <div class="project">
		          <div class="images">
		            <div class="slider owl-carousel">
		              '.$images_html.'
		            </div>
		          </div>
		          <div class="info">
		            <div class="name">'.$line_arr['@name'].'</div>
		            '.$descript.'
								'.$trebovaniya.'
								'.$slozhnosti.'
		            <a href="'.$line_arr['@link'].'" class="details">Подробнее о проекте</a>
		          </div>
		        </div>
		      </div>
		    </div>';
				$result[] = $line_arr;
			}
			$result = def_module::parseTemplate("",array(
				"subnodes:items"=>$result,
			));
			$cache->saveData($cacheName,$result,36000);
			return $result;


		}

		public function getApplicationArea($pageId = false){
			$pageId = $pageId ? $pageId : cmsController::getInstance()->getCurrentElementId();

			$cacheName = 'getApplicationArea_'.$pageId;
			$cache = cacheFrontend::getInstance();
			$result = $cache->loadData($cacheName);
			if($result){
			//	return $result;
			}
			$umiHierarchy = umiHierarchy::getInstance();
			$page = $umiHierarchy->getElement($pageId);
			$umiObjectsCollection = umiObjectsCollection::getInstance();

			$areaes = $page->application_area;
			$catalog = $umiHierarchy->getElement(4);
			$result = array();
			$link = $catalog->link;

			foreach($areaes as $areaId){
				$area = $umiObjectsCollection->getObject($areaId);
				$result[] = array(
					'@name' => $area->name,
					'@img' => $area->img,
					'@link' => $link."?filter=".$areaId,
				);

			}

			$result = def_module::parseTemplate('',array(
				'subnodes:items' => $result,
				'total' => sizeof($result),
			));
			$cache->saveData($cacheName,$result,36000);
			return $result;
		}

		public function getAdvantages($pageId = false){
			$pageId = $pageId ? $pageId : cmsController::getInstance()->getCurrentElementId();
            $pageId = $pageId ? $pageId : 227;

			$cacheName = 'getAdvantages_'.$pageId;
			$cache = cacheFrontend::getInstance();
			$result = $cache->loadData($cacheName);
			if($result){
				return $result;
			}
			$umiHierarchy = umiHierarchy::getInstance();
			$page = $umiHierarchy->getElement($pageId);
			$umiObjectsCollection = umiObjectsCollection::getInstance();
			$advantages = $page->advantages;
			$result = array();
			$html = "";
			foreach($advantages as $i=>$advantageId){
				$advantage = $umiObjectsCollection->getObject($advantageId);
				$img = $advantage->img;
				$img = $img ? $img->getFilePath(true) : "images/ic_advantage".++$i.".png";
				$line_arr = array(
					'@id' => $advantageId,
                    '@name' => $advantage->name,
                    '@img' => $advantage->img,
					'descr' => $advantage->descr

                );
				$line_arr['html'] = '<div class="item">
										<div class="icon"><img src="'.$img.'" alt="'.$line_arr['@name'].'" /></div>					
										<div class="name">'.$line_arr['@name'].'</div>				
										<div class="desc">'.$line_arr['descr'].'</div>
									</div>';
                $html.=$line_arr['html'];
				$result[] = $line_arr;
			}

			$result = def_module::parseTemplate('',array(
				'subnodes:items' => $result,
				'html' => $html,
				'total' => sizeof($result),
			));
			$cache->saveData($cacheName,$result,36000);
			return $result;


		}

		public function getInstancePage($pageId = false){
			$pageId = $pageId ? $pageId : cmsController::getInstance()->getCurrentElementId();
			$umiHierarchy = umiHierarchy::getInstance();
			$page = $umiHierarchy->getElement($pageId);
			if($page){
				$objectId = $page->getObjectId();

				$pages = $umiHierarchy->getObjectInstances($objectId);
				if(sizeof($pages)){
					$pageId = $pages[0];
					$page = $umiHierarchy->getElement($pageId);
					return array("link" => $page->link);
				}
			}

			return false;
		}

		public function getBrandLine($pageId = false){
			$pageId = $pageId ? $pageId : cmsController::getInstance()->getCurrentElementId();
			$cacheName = 'getBrandLine_'.$pageId;
			$cache = cacheFrontend::getInstance();
			$result = $cache->loadData($cacheName);
			if($result){
				return $result;
			}
			$pages = new selector('pages');
			$pages->types('object-type')->id(149);
			$pages->types('hierarchy-type')->name('content', 'page');
			$pages->where('hierarchy')->page($pageId)->childs(1);
			$result = array();
			foreach($pages as $page){
				$result[] = array(
						'@id' => $page->id,
						'@name' => $page->name,
						'@img' => $page->img,
						'@link' => $page->link
						  );
			}
			$result = def_module::parseTemplate('',array('subnodes:items' => $result));
			$cache->saveData($cacheName,$result,36000);
			return $result;

		}

	public function getAlphabetBrandsTest(){
		$rus = array('А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я');
		$eng = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');

		//$objects = new selector('objects');
		//$objects->types('object-type')->id(141);
		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('content', 'page');
		$pages->types('object-type')->id(147);
		$pages->where('hierarchy')->page(244)->childs(1);

		$result = array();

		$rus_pages = array();
		$eng_pages = array();
		$catalog = umiHierarchy::getInstance()->getElement(4);
		foreach($pages as $page){
			$name = $page->name;
			$letter = mb_substr($name,0,1);
			$letter = mb_strtoupper($letter);
			if(array_search($letter,$eng) === false ){
				$rus_pages[$letter][] = array(
							      '@id' => $page->id,
							      '@name' => $page->name,
							      '@link' => $page->link,
							      '@img' => $page->img
							      );

			}else{
				$eng_pages[$letter][] = array(
							      '@id' => $page->id,
							      '@name' => $page->name,
							      '@link' => $page->link,
							      '@img' => $page->img
							      );
			}
			$result['subnodes:all'][] = array(
							      '@id' => $page->id,
							      '@name' => $page->name,
							      '@link' => $page->link,
							      '@img' => $page->img
							      );
		}
		ksort($rus_pages);
		ksort($eng_pages);


		$subresult = array();

		foreach($rus_pages as $letter => $array){
			$line_arr = array(
					  '@letter' => $letter
					  );
			foreach($array as $page){
				$line_arr['subnodes:items'][] = $page;
			}
			$subresult['subnodes:items'][] = $line_arr;
		}
		$result['rus'] = $subresult;
		$subresult = array();
		foreach($eng_pages as $letter => $array){
			$line_arr = array(
					  '@letter' => $letter
					  );
			foreach($array as $page){
				$line_arr['subnodes:items'][] = $page;
			}
			$subresult['subnodes:items'][] = $line_arr;
		}
		$result['eng'] = $subresult;
		$result['total'] = $pages->length;
		//var_dump($result); exit;
		//return def_module::parseTemplates('',array('eng' => 'test'));
		return def_module::parseTemplate('',$result);

	}

	public function getAlphabetBrands(){
		$rus = array('А' => array(),'Б' => array(),'В' => array(),'Г' => array(),'Д' => array(),'Е' => array(),'Ё' => array(),'Ж' => array(),'З' => array(),'И' => array(),'Й' => array(),'К' => array(),'Л' => array(),'М' => array(),'Н' => array(),'О' => array(),'П' => array(),'Р' => array(),'С' => array(),'Т' => array(),'У' => array(),'Ф' => array(),'Х' => array(),'Ц' => array(),'Ч' => array(),'Ш' => array(),'Щ' => array(),'Ъ' => array(),'Ы' => array(),'Ь' => array(),'Э' => array(),'Ю' => array(),'Я' => array());
		$eng = array('A' => array(),'B' => array(),'C' => array(),'D' => array(),'E' => array(),'F' => array(),'G' => array(),'H' => array(),'I' => array(),'J' => array(),'K' => array(),'L' => array(),'M' => array(),'N' => array(),'O' => array(),'P' => array(),'Q' => array(),'R' => array(),'S' => array(),'T' => array(),'U' => array(),'V' => array(),'W' => array(),'X' => array(),'Y' => array(),'Z' => array());

		//$objects = new selector('objects');
		//$objects->types('object-type')->id(141);
		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('content', 'page');
		$pages->types('object-type')->id(147);
		$pages->where('hierarchy')->page(244)->childs(1);

		$result = array();


		//$array_eng = array_flip($eng);
		//$array_rus = array_flip($rus);
		//var_dump($array_eng); exit;

		$rus_total = 0;
		$eng_total = 0;
		$catalog = umiHierarchy::getInstance()->getElement(4);
		foreach($pages as $page){
			$name = $page->name;
			$letter = mb_substr($name,0,1);
			$letter = mb_strtoupper($letter);
			if(array_key_exists($letter,$eng) === false ){
				$rus[$letter][] = array(
							      '@id' => $page->id,
							      '@name' => $page->name,
							      '@link' => $page->link,
							      '@img' => $page->img
							      );
				$rus_total++;
			}else{
				$eng[$letter][] = array(
							      '@id' => $page->id,
							      '@name' => $page->name,
							      '@link' => $page->link,
							      '@img' => $page->img
							      );
				$eng_total++;
			}
			$result['subnodes:all'][] = array(
							      '@id' => $page->id,
							      '@name' => $page->name,
							      '@link' => $page->link,
							      '@img' => $page->img
							      );
		}
		//ksort($rus_pages);
		//ksort($eng_pages);


		$subresult = array();

		foreach($rus as $letter => $array){
			$line_arr = array(
					  '@letter' => $letter
					  );
			foreach($array as $page){
				$line_arr['subnodes:items'][] = $page;
			}
			$subresult['subnodes:items'][] = $line_arr;
		}
		$result['rus'] = $subresult;
		$subresult = array();
		foreach($eng as $letter => $array){
			$line_arr = array(
					  '@letter' => $letter
					  );
			foreach($array as $page){
				$line_arr['subnodes:items'][] = $page;
			}
			$subresult['subnodes:items'][] = $line_arr;
		}
		$result['eng'] = $subresult;
		$result['total'] = $pages->length;
		$result['rus_total'] = $rus_total;
		$result['eng_total'] = $eng_total;
		//var_dump($result); exit;
		//return def_module::parseTemplates('',array('eng' => 'test'));
		return def_module::parseTemplate('',$result);

	}

	public function do_subscribe(){
		$typeId = 83;
		$mail = getRequest('subscribe');
		$umiObjectsCollection = umiObjectsCollection::getInstance();

		if(umiMail::checkEmail($mail)){
			$objects = new selector('objects');
			$objects->types('object-type')->id($typeId);
			$objects->where("name")->equals($mail);
			if($objects->length == 0){
				$objectId = umiObjectsCollection::getInstance()->addObject($mail,$typeId);
				$object = $umiObjectsCollection->getObject($objectId);
				$subscriber_dispatches = $object->subscriber_dispatches;
				$subscriber_dispatches = array(getRequest('id'));
				$object->subscriber_dispatches = $subscriber_dispatches;

				$result['status'] = 'ok';
				$result['msg'] = 'Подписка выполнена.';
			}else{
				$result['status'] = 'ok';
				$result['msg'] = 'Вы уже подписались.';
			}
		}else{
			$result['status'] = 'error';
			$result['msg'] = 'Некорректный e-mail.';

		}
		//var_dump($result); exit;
		$buffer = outputBuffer::current();
		$buffer->charset('utf-8');
		$buffer->contentType('application/jsonp');
		$buffer->clear();
		$buffer->push(json_encode($result));
		$buffer->end();


	}


	public function sendCustom(){
		/*
		if (!umiCaptcha::checkCaptcha()) {
			$result['status'] = 'error';
			$result['msg'] = 'captcha';
			$buffer = outputBuffer::current();
			$buffer->charset('utf-8');
			$buffer->contentType('application/jsonp');
			$buffer->clear();
			$buffer->push(json_encode($result));
			$buffer->end();
		}
		*/
		//-------------------------------------------------------------------
		// Get necessary data
		$oTypes      = umiObjectTypesCollection::getInstance();
		$iBaseTypeId = $oTypes->getBaseType("webforms", "form");
		$iFormTypeId = getRequest('system_form_id');
		$sSenderIP   = getServer('REMOTE_ADDR');
		$iTime       = new umiDate( time() );
		$aAddresses  = getRequest('system_email_to');
		if(!is_array($aAddresses)) $aAddresses = array($aAddresses);

		$type = $oTypes->getType($iFormTypeId);
		$aRecipients = array();
		$email = regedit::getInstance()->getVal("//settings/admin_email");
		$fio_from = regedit::getInstance()->getVal("//settings/fio_from");
		$email_from = regedit::getInstance()->getVal("//settings/email_from");

		$aRecipients[] = array('email'=>$email, 'name'=>$fio_from);

		//-------------------------------------------------------------------
		// Saving message and preparing it for sending
		$_REQUEST['data']['new']['sender_ip'] = $sSenderIP;  // Hack for saving files-only-forms
		$oObjectsCollection = umiObjectsCollection::getInstance();
		$iObjectId          = $oObjectsCollection->addObject($email, $iFormTypeId);
		$oObjectsCollection->getObject($iObjectId)->setOwnerId(permissionsCollection::getInstance()->getUserId());
		cmsController::getInstance()->getModule('data')->saveEditedObject($iObjectId, true);
		$oObject            = $oObjectsCollection->getObject($iObjectId);
		//$oObject->setValue('destination_address', $sEmailTo);
		$oObject->setValue('sender_ip', $sSenderIP);
		$oObject->setValue('sending_time', $iTime);
		$message = "";

		//--------------------------------------------------------------------
		// Make an e-mail
		$oMail = new umiMail();
		//--------------------------------------------------------------------
		// Determine file fields
		$aFTypes     = array('file', 'img_file', 'swf_file');
		$aFields     = $oTypes->getType($oObject->getTypeId())->getAllFields();
		foreach($aFields as $oField) {
			$value = $oObject->getValue($oField->getName());
			$oType   = $oField->getFieldType();
			$data_type  = $oType->getDataType();
			if(in_array($oType->getDataType(), $aFTypes)) {
				$oFile = $oObject->getValue($oField->getName());

				if($oFile instanceof umiFile) {
					$oMail->attachFile($oFile);
				} /*else {
					$this->errorNewMessage("%errors_wrong_file_type%");
					$this->errorPanic();
				}*/
				//var_dump($oFile); exit;
				if($value){
					$value = $_SERVER['HTTP_HOST'].$oFile->getFilePath(true);
				}


			}

			if($value){
				if($data_type == "relation"){
					$value = $oObjectsCollection->getObject($value);
						$message.="<p>".$oField->getTitle().": ".$value->name	."</p>";
				}else{
					if($value !== ""){
						$message.="<p>".$oField->getTitle().": ".$value."</p>";
					}
				}

			}



		}

		$recpCount = 0;
		foreach($aRecipients as $recipient) {
			foreach(explode(',', $recipient['email']) as $sAddress) {
				if(strlen(trim($sAddress))) {
					$oMail->addRecipient( trim($sAddress), $recipient['name'] );
					$recpCount++;
				}
			}
		}
		//if(!$recpCount) {
		//	$this->errorNewMessage(getLabel('error-no_recipients'));
		//}

		$oMail->setFrom($fio_from, $email_from);
		$oMail->setSubject("На сайте ".$_SERVER['HTTP_HOST']." заполнена форма \"".$type->getName()."\"");
		$oMail->setContent($message);
		$oMail->commit();
		$oMail->send();
		//--------------------------------------------------------------------

		//var_dump(111); exit;
		//--------------------------------------------------------------------
		// Process events

		//--------------------------------------------------------------------

		$result['status'] = 'ok';
		$result['msg'] = 'Ваша заявка отправлена. Мы свяжемся с Вами в ближайшее время.';
		$buffer = outputBuffer::current();
		$buffer->charset('utf-8');
		$buffer->contentType('application/jsonp');
		$buffer->clear();
		$buffer->push(json_encode($result));
		$buffer->end();
	}

	public function getReviews($pageId = false){
		$pageId = $pageId ? $pageId : 245;

		$cacheName = 'getReviews_'.$pageId;
		$cache = cacheFrontend::getInstance();
		$result = $cache->loadData($cacheName);
		if($result){
			//return $result;
		}

		$pages = new selector('pages');
		$pages->types('object-type')->id(141);
		$pages->types('hierarchy-type')->name('content', 'page');
		$pages->where('hierarchy')->page($pageId)->childs(1);
		//$pages->limit(0,4);
		$result = array();
		foreach($pages as $page){
			$img = $page->img;
			$img = $img ? $img->getFilePath(true) : false;
			if ($publish_time = $page->data) {
				$news = cmsController::getInstance()->getModule("news");
				$publish_time = $news->dateru($publish_time->getDateTimeStamp());
				//$line_arr['attribute:publish_time'] = $publish_time->getFormattedDate("m.d.Y");
			}
			$result[] = array(
						'@id' => $page->id,
						'@name' => $page->name,
						'@img' => $img,
						'@link' => $page->link,
						'@author' => $page->author,
						'@data' => $publish_time,
						'content' => $page->content,
						);
		}

		$result = def_module::parseTemplate('',array('subnodes:items' => $result,
									 'total' => $pages->length));
		$cache->saveData($cacheName,$result,36000);
		return $result;
	}

		public function getBrands(){
			$cacheName = 'getBrands_';
			$cache = cacheFrontend::getInstance();
			$result = $cache->loadData($cacheName);
			if($result){
				//return $result;
			}

			$objects = new selector('objects');
			$objects->types('object-type')->id(144);
			$objects->where("img")->isnotnull(true);



			$umiHierarchy = umiHierarchy::getInstance();
			$page = $umiHierarchy->getElement(4);

			$link = $page->link."?filter[brand][0]=";



			foreach($objects as $object){
				$img = $object->img;
				$img = $img ? $img->getFilePath(true) : false;
				$result[] = array(
						  '@id' => $object->id,
						  '@name' => $object->name,
						  '@img' => $img,
						  '@link' => $link.$object->name,
						  );
			}


			$result = def_module::parseTemplate('',array('subnodes:items' => $result,
								     'total' => sizeof($result)));
			$cache->saveData($cacheName,$result,36000);
			return $result;


			//$pageId = $pageId ? $pageId : 250;
			/*
			$cacheName = 'getBrands_'.;
			$cache = cacheFrontend::getInstance();
			$result = $cache->loadData($cacheName);
			if($result){
				//return $result;
			}

			$pages = new selector('pages');
			$pages->types('object-type')->id(142);
			$pages->types('hierarchy-type')->name('content', 'page');
			$pages->where('hierarchy')->page($pageId)->childs(1);
			$pages->limit(0,7);
			$pages->order('rand');
			$pages->where("outer_link")->isnotnull(true);
			$pages->where("img")->isnotnull(true);
			$result = array();
			foreach($pages as $page){
				$img = $page->img;
				$img = $img ? $img->getFilePath(true) : false;
				$link = $page->outer_link;
				$result[] = array(
						  '@id' => $page->id,
						  '@name' => $page->name,
						  '@img' => $img,
						  '@link' => $link,
						  );
			}

			$result = def_module::parseTemplate('',array('subnodes:items' => $result,
								     'total' => $pages->length));
			$cache->saveData($cacheName,$result,36000);
			return $result;
			*/
		}
		/*
		 *	слайдер
		 */

		 public function getGallery($pageId = false){
 			$pageId = $pageId ? $pageId : 191;

 			$cacheName = 'getGallery_'.$pageId;
 			$cache = cacheFrontend::getInstance();
 			$result = $cache->loadData($cacheName);
 			if($result){
 				return $result;
 			}

 			$pages = new selector('pages');
 			$pages->types('object-type')->id(119);
 			$pages->types('hierarchy-type')->name('filemanager', 'shared_file');
 			$pages->where('hierarchy')->page($pageId)->childs(1);
 			$result = array();
 			foreach($pages as $page){
 				$file = $page->fs_file;
 				$file = $file ? $file->getFilePath(true) : false;

 				$link = $page->category;
 				if(sizeof($link)){
 					$link = $link[0];
 					$link = $link->link;
 				}else{
 					$link = false;
 				}




 				$line_arr = array(
 						  '@id' => $page->id,
 						  '@name' => $page->name,
 							'@header' => $page->header,
 						  '@file' => $file,
 						  '@link' => $link,
 						  );

 				if($link){
 					$link = 'data-link="'.$link.'"';
 				}else{
 					$link = '';
 				}


				$line_arr['html'] = '<div class="b-gallery__main-item" data-title="'.$line_arr['@name'].'" '.$link.'>
						<div class="b-gallery__main-item-inner">
								<div class="b-gallery__main-img-wrapper">
										<img src="'.$line_arr['@file'].'" alt="'.$line_arr['@name'].'" class="b-gallery__main-img">
								</div>
						</div>
				</div>';

 				$result[] = $line_arr;
 			}

 			$result = def_module::parseTemplate('',array('subnodes:items' => $result,
 								     'total' => $pages->length));
 			$cache->saveData($cacheName,$result,36000);
 			return $result;
 		}

		public function getClients($pageId = false,$all = false){
			$pageId = $pageId ? $pageId : cmsController::getInstance()->getCurrentElementId();
			$langId = cmsController::getInstance()->getCurrentLang()->getId();

			//$all = (int) getRequest('all');
			$cacheName = 'getClients_'.$pageId."_".$langId."_".$all;
			$cache = cacheFrontend::getInstance();
			$result = $cache->loadData($cacheName);
			if($result){
				return $result;
			}
			$limit = 16;
			$thisPage = umiHierarchy::getInstance()->getElement($pageId);
			$pages = new selector('pages');
			$pages->types('object-type')->id(145);
			$pages->types('hierarchy-type')->name('content', 'page');
			$pages->where('hierarchy')->page($pageId)->childs(1);
			if(!$all){
					$pages->limit(0,$limit);
			}

			$result = array();
			foreach($pages as $page){
				$img = $page->img;
				$img = $img ? $img->getFilePath(true) : false;
				$line_arr = array(
					'@id' => $page->id,
					'@name' => $page->name,
					'@link' => $page->outer_link,
					'@img' => $img,
					'@outer_link' => $page->outer_link,
				);
				$line_arr['html'] = '<img src="'.$line_arr['@img'].'" alt="'.$line_arr['@name'].'">';
				if($line_arr['@link']){
					$line_arr['html'] = '<a href="'.$line_arr['@link'].'">'.$line_arr['html'].'</a>';
				}

				$result[] = $line_arr;

			}

			$result = def_module::parseTemplate('',array('subnodes:items' => $result,
								     'total' => $pages->length,
									 'link' => $thisPage->link));
			$cache->saveData($cacheName,$result,36000);
			return $result;


		}

		public function getSlider($pageId = false){
            $langId = cmsController::getInstance()->getCurrentLang()->getId();

			//$pageId = $pageId ? $pageId : ($langId == 1) ? 321 : 1095;
            //var_dump($pageId); exit;

			$cacheName = 'getSlider_'.$pageId."_".$langId;
			$cache = cacheFrontend::getInstance();
			$result = $cache->loadData($cacheName);
			if($result){
				$result['pageId'] = $pageId;
				$result['cache'] = 'ok';
				return $result;
			}

			$pages = new selector('pages');
			$pages->types('object-type')->id(186);
			$pages->types('hierarchy-type')->name('content', 'page');
			//$pages->limit(0,3);
			$pages->where('hierarchy')->page($pageId)->childs(1);
			$result = array();
			$html = "";
			foreach($pages as $i=>$page){
				$img = $page->img;
				$img = $img ? $img->getFilePath(true) : false;

				$link = $page->outer_link ? $page->outer_link : false;

				$inner_link = $page->inner_link;
				if(sizeof($inner_link )){
                    $inner_link  = $inner_link [0];
					$link = $inner_link->link;
				}

				$line_arr = array(
						  '@id' => $page->id,
						  '@name' => $page->name,
						  '@img' => $img,
							'@link' => $link,
							'content' => $page->content,
						  );

				$title = $page->not_descript ? "" : '<div class="title">'.$line_arr['@name'].'</div>';

				$link = $link ? $link : false;
				if($link){
                    $line_arr['html'] = '<div class="slide">
							<a class="info" href="'.$link.'">
								<img src="'.$line_arr['@img'].'" alt="">
                    		</a>
						</div>';
				}else{
                    $line_arr['html'] = '<div class="slide">
							<img src="'.$line_arr['@img'].'" alt="">
						</div>';
                }
                $html.=$line_arr['html'];
				$result[] = $line_arr;
			}

			$result = def_module::parseTemplate('',array('subnodes:items' => $result,
								     'html' => $html,
								     'total' => $pages->length,
				'pageId' => $pageId));
			$cache->saveData($cacheName,$result,36000);
			return $result;
		}


		public function getBanners($pageId = false){
			$pageId = $pageId ? $pageId : 259;

			$cacheName = 'getBanners_'.$pageId;
			$cache = cacheFrontend::getInstance();
			$result = $cache->loadData($cacheName);
			if($result){
				//return $result;
			}

			$pages = new selector('pages');
			$pages->types('object-type')->id(143);
			$pages->types('hierarchy-type')->name('content', 'page');
			$pages->where('hierarchy')->page($pageId)->childs(1);
			$pages->limit(0,4);
			$result = array();
			foreach($pages as $page){
				$img = $page->img;
				$img = $img ? $img->getFilePath(true) : false;

				$link = $page->inner_link;
				if(sizeof($link)){
					$link = $link[0];
					$link = $link->link;
				}else{
					$link = $page->outer_link;
				}



				$result[] = array(
						  '@id' => $page->id,
						  '@name' => $page->name,
							'@header' => $page->header,
						  '@img' => $img,
						  'content' => $page->content,
						  '@link' => $link,



						  );
			}

			$result = def_module::parseTemplate('',array('subnodes:items' => $result,
								     'total' => $pages->length));
			$cache->saveData($cacheName,$result,36000);
			return $result;
		}

		public function getInstagram($pageId = false){
			$pageId = $pageId ? $pageId : 250;

			$cacheName = 'getInstagram_'.$pageId;
			$cache = cacheFrontend::getInstance();
			$result = $cache->loadData($cacheName);
			if($result){
				//return $result;
			}

			$pages = new selector('pages');
			$pages->types('object-type')->id(142);
			$pages->types('hierarchy-type')->name('content', 'page');
			$pages->where('hierarchy')->page($pageId)->childs(1);
			$pages->limit(0,7);
			$pages->order('rand');
			$pages->where("outer_link")->isnotnull(true);
			$pages->where("img")->isnotnull(true);
			$result = array();
			foreach($pages as $page){
				$img = $page->img;
				$img = $img ? $img->getFilePath(true) : false;
				$link = $page->outer_link;
				$result[] = array(
						  '@id' => $page->id,
						  '@name' => $page->name,
						  '@img' => $img,
						  '@link' => $link,
						  );
			}

			$result = def_module::parseTemplate('',array('subnodes:items' => $result,
								     'total' => $pages->length));
			$cache->saveData($cacheName,$result,36000);
			return $result;
		}

		/*
		 *	слайдер
		 */

		public function getProducers($pageId = false){
			$pageId = $pageId ? $pageId : 244;

			$cacheName = 'getProducers_'.$pageId;
			$cache = cacheFrontend::getInstance();
			$result = $cache->loadData($cacheName);
			if($result){
				return $result;
			}

			$pages = new selector('pages');
			$pages->types('object-type')->id(139);
			$pages->types('hierarchy-type')->name('content', 'page');
			$result = array();
			foreach($pages as $page){
				$img = $page->img;
				$img = $img ? $img->getFilePath(true) : false;
				$result[] = array(
						  '@id' => $page->id,
						  '@name' => $page->name,
						  '@link' => $page->link,
						  '@img' => $img,

						  );
			}

			$result = def_module::parseTemplate('',array('subnodes:items' => $result));
			$cache->saveData($cacheName,$result,36000);
			return $result;
		}

		/*
		 *	технологии
		 */

		public function getTehnologies($pageId = false){
			$pageId = $pageId ? $pageId : 0;

			$cacheName = 'getTehnologies'.$pageId;
			$cache = cacheFrontend::getInstance();
			$result = $cache->loadData($cacheName);
			if($result){
				return $result;
			}

			$pages = new selector('pages');
			$pages->types('object-type')->id(147);
			$pages->types('hierarchy-type')->name('content', 'page');
			$result = array();
			//var_dump($pages->length); exit;
			foreach($pages as $page){
				$img = $page->img;
				$img = $img ? $img->getFilePath(true) : false;
				$result[] = array(
						  '@id' => $page->id,
						  '@name' => $page->name,
						  '@link' => $page->link,
						  '@img' => $img,


						  );
			}

			$result = def_module::parseTemplate('',array('subnodes:items' => $result));
			$cache->saveData($cacheName,$result,36000);
			return $result;
		}



		/*
		 *	хлебные крошки
		 */
		public function getBreadCrumbs($current_page_id = false){
			$current_page_id = $current_page_id ? $current_page_id : cmsController::getInstance()->getCurrentElementId();
            $umiHierarchy = umiHierarchy::getInstance();
            $default = $umiHierarchy->getElement($umiHierarchy->getDefaultElementId());
			if($current_page_id){
				$parents = $umiHierarchy->getAllParents($current_page_id, false);
				$element = $umiHierarchy->getElement($current_page_id);
				if ($element->getIsDefault()){
					return false;
				}
				$result = array();

				foreach($parents as $parentId){
				    if ($parentId === 0){
					$result[] = array('@link'=>'/','@name'=>$default->name);
				    }
				    else{
					$page = $umiHierarchy->getElement($parentId);
					$result[] = array('@link'=>$page->link,'@name'=>strip_tags(htmlspecialchars_decode($page->name)));
				    }

				}
				return def_module::parseTemplate('',array('subnodes:items'=>$result));
			}else{
				$result = array();
				$result[] = array('@link'=>'/','@name'=>$default->name);

				return def_module::parseTemplate('',array('subnodes:items'=>$result));
			}

		}
	};
?>
