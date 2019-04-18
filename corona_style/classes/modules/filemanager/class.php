<?php

	class filemanager_custom extends def_module {
		public function list_filesCustom($element_id = false, $template = "default", $per_page = false, $ignore_paging = false, $depth = 1) {
			$cacheName = 'list_filesCustom_'.$element_id;
			$cache = cacheFrontend::getInstance();
			$result = $cache->loadData($cacheName);
			if($result){
				return $result;
			}
			if(!$template) $template = "default";

			$depth = (int) $depth;
			if ( !$depth ) $depth = 1;

			list($template_block, $template_line) = def_module::loadTemplates("filemanager/".$template, "list_files", "list_files_row");

			$block_arr = Array();

			$element_id = $this->analyzeRequiredPath($element_id);

			if(!$per_page) $per_page = $this->per_page;
			$curr_page = (int) getRequest('p');
			if($ignore_paging) $curr_page = 0;

			$sel = new selector('pages');
			$sel->types('object-type')->name('filemanager', 'shared_file');
			$sel->where('hierarchy')->page($element_id)->childs($depth);
			$sel->option('load-all-props')->value(true);
			$sel->limit($curr_page * $per_page, $per_page);

			$result = $sel->result();
			$total = $sel->length();

			$umiLinksHelper = umiLinksHelper::getInstance();

			$lines = Array();
			foreach($result as $element) {
				$line_arr = Array();

				$next_element_id = $element->getId();

				$line_arr['attribute:id'] = $element->getId();
				$line_arr['attribute:name'] = $element->getName();
				$line_arr['@img'] = $element->menu_pic_a;
				$line_arr['attribute:link'] = $umiLinksHelper->getLinkByParts($element);
				$line_arr['attribute:downloads-count'] = $element->getValue('downloads_counter');
				$line_arr['@download-link'] = $this->pre_lang . "/filemanager/download/" . $next_element_id;
				$line_arr['@href'] = "upage://" . $next_element_id;
				$line_arr['@fs_file'] = $element->fs_file;
				$line_arr['node:desc'] = $element->getValue("content");

				$this->pushEditable("filemanager", "shared_file", $next_element_id);

				$lines[] = def_module::parseTemplate($template_line, $line_arr, $next_element_id);
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['per_page'] = $per_page;
			$block_arr['total'] = $total;

			$result = def_module::parseTemplate($template_block, $block_arr);
			$cache->saveData($cacheName,$result,36000);
			return $result;

			return def_module::parseTemplate($template_block, $block_arr);
		}

	};
?>
