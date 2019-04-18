<?php

class news_custom{

	public function lastlistCustom($path = "", $template = "default", $per_page = false, $ignore_paging = false, $sDaysInterval = '', $bSkipOrderByTime = false) {

		if(!$per_page) $per_page = $this->module->per_page;

		$per_page = intval($per_page);



		if (strlen($sDaysInterval)) {
			$sStartDaysOffset = ''; $iStartDaysOffset = 0;
			$sFinishDaysOffset = ''; $iFinishDaysOffset = 0;
			$arrDaysInterval = preg_split("/\s+/is", $sDaysInterval);
			if (isset($arrDaysInterval[0])) $sStartDaysOffset = $arrDaysInterval[0];
			if (isset($arrDaysInterval[1])) $sFinishDaysOffset = $arrDaysInterval[1];

			$iNowTime = time();
			if ($sStartDaysOffset === '+') {
				$iStartDaysOffset = (PHP_INT_MAX - $iNowTime);
			} elseif ($sStartDaysOffset === '-') {
				$iStartDaysOffset = (0 - PHP_INT_MAX + $iNowTime);
			} else {
				$iStartDaysOffset = intval($sStartDaysOffset);
				$sPostfix = substr($sStartDaysOffset, -1);
				if ($sPostfix === 'm') { // minutes
					$iStartDaysOffset *= (60);
				} elseif ($sPostfix === 'h' || $sPostfix === 'H') { // hours
					$iStartDaysOffset *= (60*60);
				} else { // days
					$iStartDaysOffset *= (60*60*24);
				}
			}
			if ($sFinishDaysOffset === '+') {
				$iFinishDaysOffset = (PHP_INT_MAX - $iNowTime);
			} elseif ($sFinishDaysOffset === '-') {
				$iFinishDaysOffset = (0 - PHP_INT_MAX + $iNowTime);
			} else {
				$iFinishDaysOffset = intval($sFinishDaysOffset);
				$sPostfix = substr($sFinishDaysOffset, -1);
				if ($sPostfix === 'm') { // minutes
					$iFinishDaysOffset *= (60);
				} elseif ($sPostfix === 'h' || $sPostfix === 'H') { // hours
					$iFinishDaysOffset *= (60*60);
				} else { // days
					$iFinishDaysOffset *= (60*60*24);
				}
			}
			$iPeriodStart = $iNowTime + $iStartDaysOffset;
			$iPeriodFinish = $iNowTime + $iFinishDaysOffset;
			$bPeriodOrder = ($iPeriodStart >= $iPeriodFinish ? false : true);
		} else {
			$iPeriodStart = false;
			$iPeriodFinish = false;
			$bPeriodOrder = false;
		}

		//
		list($template_block, $template_block_empty, $template_line, $template_archive) = def_module::loadTemplates("news/".$template, "lastlist_block", "lastlist_block_empty", "lastlist_item", "lastlist_archive");
		$curr_page = (int) getRequest('p');
		if($ignore_paging) $curr_page = 0;


		//$parent_id = $this->analyzeRequiredPath($path);
		$parent_id = $path;

		$cacheName = 'lastlistCustom_'.$path."_".$curr_page."_".$per_page;
		$cache = cacheFrontend::getInstance();
		$result = $cache->loadData($cacheName);
		if($result){
			return $result;
		}


		if ($parent_id === false && $path != KEYWORD_GRAB_ALL) {
			throw new publicException(getLabel('error-page-does-not-exist', null, $path));
		}

		//$this->loadElements($parent_id);
		$month = (int) getRequest('month');
		$year = (int) getRequest('year');
		$day = (int) getRequest('day');

		$news = new selector('pages');
		$news->types('hierarchy-type')->name('news', 'item');
		if ($path != KEYWORD_GRAB_ALL) {
			if (is_array($parent_id)) {
				foreach ($parent_id as $parent) {
					$news->where('hierarchy')->page($parent)->childs(1);
				}
			} else {
				$news->where('hierarchy')->page($parent_id)->childs(1);
			}
		}
		if (!empty($month) && !empty($year) && !empty($day)) {
			$date1 = mktime(0, 0, 0, $month, $day, $year);
			$date2 = mktime(23, 59, 59, $month, $day, $year);
			$news->where('publish_time')->between($date1, $date2);
		} elseif (!empty($month) && !empty($year)) {
			$date1 = mktime(0, 0, 0, $month, 1, $year);
			$date2 = mktime(23, 59, 59, $month+1, 0, $year);
			$news->where('publish_time')->between($date1, $date2);
		} elseif( !empty($year)) {
			$date1 = mktime(0, 0, 0, 1, 1, $year);
			$date2 = mktime(23, 59, 59, 12, 31, $year);
			$news->where('publish_time')->between($date1, $date2);
		} elseif ($iPeriodStart !== $iPeriodFinish) {
			if($iPeriodStart != false && $iPeriodFinish != false) {
				if($sDaysInterval && $sDaysInterval != '+ -') {
					if ($iPeriodStart < $iPeriodFinish) {
						$news->where('publish_time')->between($iPeriodStart, $iPeriodFinish);
					} else {
						$news->where('publish_time')->between($iPeriodFinish, $iPeriodStart);
					}
				}
			}
		}
		if (!$bSkipOrderByTime) {
			//if ($bPeriodOrder === true) {
			//	$news->order('publish_time')->asc();
			//} else {
			//	$news->order('publish_time')->desc();
			//}
		}

		$news->order('ord');

		selectorHelper::detectFilters($news);
		$news->option('load-all-props')->value(true);
		$news->limit($curr_page * $per_page, $per_page);

		$result = $news->result();
		$total = $news->length();

		//$umiLinksHelper = $this->umiLinksHelper;
		$umiHierarchy = umiHierarchy::getInstance();
		$catalog = cmsController::getInstance()->getModule("catalog");
		$html = '';

		if(($sz = sizeof($result)) > 0) {
			$block_arr = Array();
			$lines = Array();

			foreach ($result as $j => $element) {
				if (!$element instanceof umiHierarchyElement) {
					continue;
				}
				$line_arr = self::makeNewsLine($element,$umiHierarchy,$catalog);
                $html.=$line_arr['html'];



                $element_id = $element->id;

				$lines[] = def_module::parseTemplate($template_line, $line_arr, $element_id);

				//$this->pushEditable("news", "item", $element_id);
				umiHierarchy::getInstance()->unloadElement($element_id);

			}




			if (is_array($parent_id)) {
				list($parent_id) = $parent_id;
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['archive'] = ($total > 0) ? $template_archive : "";
			$parent = $umiHierarchy->getElement($parent_id);
			if ($parent instanceof umiHierarchyElement) {
				$block_arr['archive_link'] = $parent->link;
			}

			$block_arr['html'] = $html;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			$block_arr['category_id'] = $parent_id;

			$result = def_module::parseTemplate($template_block, $block_arr, $parent_id);
			$cache->saveData($cacheName,$result,36000);
			return $result;
		} else {
			return $template_block_empty;
		}
	}

	public function getMainNews(){
        $langId = cmsController::getInstance()->getCurrentLang()->getId();
        $umiHierarchy = umiHierarchy::getInstance();
        $defaultPageId = $umiHierarchy->getDefaultElement();
        $defaultPageId = $defaultPageId->id;
        $cacheName = 'getMainNews_'.$defaultPageId;
        $cache = cacheFrontend::getInstance();
        $result = $cache->loadData($cacheName);
        if($result){
            return $result;
        }

        $page = $umiHierarchy->getElement($defaultPageId);
        $main_pages = $page->news_main;
        $result = array();
        $umiHierarchy = $umiHierarchy ? $umiHierarchy : umiHierarchy::getInstance();
        $catalog = cmsController::getInstance()->getModule("catalog");
        $html = '';
        $main_pages = array_slice($main_pages,0,2);
        foreach($main_pages as $element){
        	$makeNewsLine = self::makeNewsLine($element,$umiHierarchy,$catalog);
        	$html.=$makeNewsLine['html'];
			$result[] = $makeNewsLine;
		}

		$result = array(
			'subnodes:items' => $result,
			'html' => $html,
			'total' => sizeof($result),
			'pageId' => $defaultPageId
		);
        $result = def_module::parseTemplate('', $result);
        $cache->saveData($cacheName,$result,36000);
        return $result;

	}

	private function makeNewsLine($element,$umiHierarchy = false,$catalog){
        $cacheName = 'makeNewsLine_'.$element->id;
        $cache = cacheFrontend::getInstance();
        $result = $cache->loadData($cacheName);
        if($result){
            return $result;
        }
        $umiHierarchy = $umiHierarchy ? $umiHierarchy : umiHierarchy::getInstance();
        $catalog = $catalog ? $catalog : cmsController::getInstance()->getModule("catalog");
        $line_arr = Array();

        $element_id = $element->getId();

        $line_arr['attribute:id'] = $element_id;
        $line_arr['@name'] = $element->getName();
        $line_arr['attribute:link'] = $element->link;
        $line_arr['xlink:href'] = "upage://" . $element_id;
        $line_arr['void:header'] = $lines_arr['name'] = $element->getName();
        $line_arr['anons'] = $element->anons;

        $anons_pic = $element->anons_pic;

        if($anons_pic){
            $thumb = $catalog->makeThumbnailNew($anons_pic->getFilePath(),294,266);
            $line_arr['@img'] = $thumb['src'];
        }else{
            $line_arr['@img'] = 'images/article_thumb1.jpg';
        }


        if ($publish_time = $element->getValue('publish_time')) {
            //$line_arr['attribute:publish_time'] = self::dateru($publish_time->getDateTimeStamp());
            $line_arr['attribute:publish_time'] = $publish_time->getFormattedDate("d.m.Y");
        }

        $lent_name = "";
        $lent_link = "";
        $lent_id = $element->getParentId();

        if ($lent_element = $umiHierarchy->getElement($lent_id)) {
            $lent_name = $lent_element->getName();
            $lent_link = $lent_element->link;
        }

        $line_arr['attribute:lent_id'] = $lent_id;
        $line_arr['attribute:lent_name'] = $lent_name;
        $line_arr['attribute:lent_link'] = $lent_link;

        $line_arr['html'] = '<div class="item">
                <div class="thumb left">
                    <a href="'.$line_arr['attribute:link'].'"><img src="'.$line_arr['@img'].'" alt="'.$line_arr['@name'].'" /></a>
                </div>
                <div class="info right">
                    <div class="name">
                        <a href="'.$line_arr['attribute:link'].'">'.$line_arr['@name'].'</a>
                    </div>
                    <div class="date">'.$line_arr['attribute:publish_time'].'</div>
                    <div class="desc">'.$line_arr['anons'].'</div>
                    <a href="'.$line_arr['attribute:link'].'" class="details"><span>'.getLabel('more_info').'</span></a>
                </div>
            </div>';
        $cache->saveData($cacheName,$line_arr,36000);
        return $line_arr;
	}

	public function dateru($time) {
		$day = date('d', $time);
		$month = date('n', $time);
		$year = date('Y', $time);

		// Проверка существования месяца
		if (!checkdate($month, 1, $year)){
		    throw new publicException("Проверьте порядок ввода даты.");
		}

		$months_ru = array(1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
		$date_ru = $day . ' ' . $months_ru[$month] . ' ' . $year;
		return $date_ru;
	}

};
?>
