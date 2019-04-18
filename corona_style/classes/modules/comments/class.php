<?php
	class comments_custom  {
		public function postCustom($parent_element_id = false) {
			$bNeedFinalPanic = false;

			$parent_element_id = (int) $parent_element_id;
			if(!isset($parent_element_id) || !$parent_element_id) {
				$parent_element_id = (int) getRequest('param0');
			}

			$title = trim(getRequest('title'));
			$content = trim(getRequest('comment'));
			$nick = htmlspecialchars(getRequest('author_nick'));
			$email = htmlspecialchars(getRequest('author_email'));

			$referer_url = getServer('HTTP_REFERER');
			$posttime = time();
			$ip = getServer('REMOTE_ADDR');



			if (!(strlen($title) || strlen($content))) {
				$this->errorNewMessage('%comments_empty%', false);
				$this->errorPanic();
			}

			if (!umiCaptcha::checkCaptcha() || !$parent_element_id) {
				$this->errorNewMessage("%errors_wrong_captcha%");
				$this->errorPanic();
			}

			$user_id = permissionsCollection::getInstance()->getUserId();

			if(!$nick) {
				$nick = getRequest('nick');
			}

			if(!$email) {
				$email = getRequest('email');
			}


			if($nick) {
				$nick = htmlspecialchars($nick);
			}

			if($email) {
				$email = htmlspecialchars($email);
			}

			$is_sv = false;
			if($users_inst = cmsController::getInstance()->getModule("users")) {
				if($users_inst->is_auth()) {
					$author_id = $users_inst->createAuthorUser($user_id);
					$is_sv = permissionsCollection::getInstance()->isSv($user_id);
				} else {
					if(!(regedit::getInstance()->getVal("//modules/comments/allow_guest"))) {
						$this->errorNewMessage('%comments_not_allowed_post%', true);
					}

					$author_id = $users_inst->createAuthorGuest($nick, $email, $ip);
				}
			}

			$is_active = ($this->moderated && !$is_sv) ? 0 : 1;

			if($is_active) {
				$is_active = antiSpamHelper::checkContent($content.$title.$nick.$email) ? 1 : 0;
			}

			if (!$is_active) {
				$this->errorNewMessage('%comments_posted_moderating%', false);
				$bNeedFinalPanic = true;
			}

			$object_type_id = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName("comments", "comment");
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("comments", "comment")->getId();

			$parentElement = umiHierarchy::getInstance()->getElement($parent_element_id);
			$tpl_id		= $parentElement->getTplId();
			$domain_id	= $parentElement->getDomainId();
			$lang_id	= $parentElement->getLangId();

			if (!strlen(trim($title)) && ($parentElement instanceof umiHierarchyElement)) {
				$title = "Re: ".$parentElement->getName();
			}

			$element_id = umiHierarchy::getInstance()->addElement($parent_element_id, $hierarchy_type_id, $title, $title, $object_type_id, $domain_id, $lang_id, $tpl_id);
			permissionsCollection::getInstance()->setDefaultPermissions($element_id);

			$element = umiHierarchy::getInstance()->getElement($element_id, true);

			$element->setIsActive($is_active);
			$element->setIsVisible(false);

			$element->setValue("message", $content);
			$element->setValue("publish_time", $posttime);

			$element->getObject()->setName($title);
			$element->setValue("h1", $title);

			$element->setValue("author_id", $author_id);

			$object_id = $element->getObject()->getId();
			$data_module = cmsController::getInstance()->getModule('data');
			$data_module->saveEditedObject($object_id, true);

			// moderate
			$element->commit();
			$parentElement->commit();


//var_dump($element_id); exit;
			// redirect with or without error messages
			$result = array('status' => 'ok');
			if ($bNeedFinalPanic) {
				$result['status'] = "error";
			} else {
				// validate url
			}

			self::checkEval($parent_element_id);
			$buffer = outputBuffer::current();
			$buffer->charset('utf-8');
			$buffer->contentType('application/jsonp');
			$buffer->clear();
			$buffer->push(json_encode($result));
			$buffer->end();

		}

		public function getEvals($pageId = false){
			$cmsController = cmsController::getInstance();
			$pageId = $pageId ? $pageId : $cmsController->getCurrentElementId();
			$page = umiHierarchy::getInstance()->getElement($pageId);
			$val = $page->eval;
			$result = array();

			$emarket = $cmsController->getModule("emarket");

			$makeEvalLine = self::makeEvalLine($val);
			$result['subnodes:items'] = $makeEvalLine['items'];

			$result['feedacks'] = (int)$page->comments_count." ".$emarket->getNumEnding($page->comments_count,array('отзыв', 'отзыва', 'отзывов'));
			//var_dump($result); exit;
			return def_module::parseTemplate('',$result);
		}


		private function makeEvalLine($val){
			$result = array();
			$result['html'] = '<ul>';
			for($i=0;$i<5;$i++){
				$class ="";
				if($i<$val){
					$class = "active";
				}
				$result['items'][] = array('@class' => $class);
				$result['html'].= ($class == 'active') ? '<li class="active"></li>' : '<li></li>';
			}
			$result['html'].= "</ul>";

			return $result;
		}

	private function checkEval($parentId = false){
		$parentId = $parentId ? $parentId : cmsController::getInstance()->getCurrentElementId();
		$pages = new selector('pages');
		$pages->types('object-type')->name('comments', 'comment');
		$pages->where('hierarchy')->page($parentId);
		$eval = 0;
		$umiObjectsCollection = umiObjectsCollection::getInstance();

		foreach($pages as $page){
			$this_eval = $umiObjectsCollection->getObject($page->ocenka);
			$this_eval = (int) $this_eval->name;
			if($this_eval){
				$eval+= $this_eval;
			}
		}

		$val = round($eval/$pages->length);

		$page = umiHierarchy::getInstance()->getElement($parentId);
		$page->eval = $val;
		$page->comments_count = $pages->length;

	}


	public function getRating($pageId = false){
		$pageId = $pageId ? $pageId : cmsController::getInstance()->getCurrentElementId();
		$pages = new selector('pages');
		$pages->types('object-type')->name('comments', 'comment');
		$pages->where('hierarchy')->page($pageId);
		$pages->where("rating_value")->isnotnull(true);
		$eval = 0;
		foreach($pages as $page){
			$eval = $page->rating_value;
		}
		$val = 0;
		if($pages->length){
				$val = round($eval/$pages->length);
		}

		$result = "";
		for($i=1; $i<=5; $i++){
			if($i <= $val){
				$result.= '<li class="active"></li>';
			}else{
				$result.= '<li></li>';
			}
		}


		return def_module::parseTemplate('',array("html" => $result));

	}


	private function dateru($time) {
		$day = date('d', $time);
		$month = date('n', $time);
		$year = date('Y', $time);

		// Проверка существования месяца
		if (!checkdate($month, 1, $year)){
		    throw new publicException("Проверьте порядок ввода даты.");
		}

		$months_ru = array(1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
		$date_ru = $day . ' ' . $months_ru[$month] . ' ' . $year.' в '.date('H', $time).":".date('i', $time);
		return $date_ru;
	}

	/**
	 * Возвращает список комментариев, дочерних заданной странице
	 * и action для формы добавления комментария
	 * @param bool|string|int $parent_element_id адрес или идентификатор страницы
	 * @param string $template имя шаблона (для tpl)
	 * @param bool|int $order порядок сортировки по дате публикации комментария
	 * В качестве значения можно передать "1" (прямой порядок) или "0" (обратный порядок).
	 * @return mixed
	 * @throws selectorException
	 */
	public function insertCustom($parent_element_id = false, $template = "default", $order = false) {
		$umiRegistry = regedit::getInstance();
		$default = $umiRegistry->getVal('//modules/comments/default_comments');
		$block_arr = Array();

		if ($default == "0") {
			return comments::parseTemplate(false, $block_arr, false);
		}

		if (!$template) {
			$template = "default";
		}

		$parent_element_id = $this->module->analyzeRequiredPath($parent_element_id);

		list(
			$template_block, $template_line, $template_add_user, $template_add_guest, $template_smiles
			) = comments::loadTemplates(
			"comments/" . $template,
			"comments_block",
			"comments_block_line",
			"comments_block_add_user",
			"comments_block_add_guest",
			"smiles"
		);

		$isAuth = permissionsCollection::getInstance()->isAuth();

		if ($isAuth) {
			$template_add = $template_add_user;
		} else {
			$template_add = ($umiRegistry->getVal("//modules/comments/allow_guest")) ? $template_add_guest : "";
		}

		$oHierarchy = umiHierarchy::getInstance();
		$oParent = $oHierarchy->getElement($parent_element_id);
		$per_page = $this->module->per_page;
		$curr_page = (int) getRequest('p');

		$comments = new selector('pages');
		$comments->types('object-type')->name('comments', 'comment');
		$comments->where('hierarchy')->page($parent_element_id);

		if ($order) {
			$comments->order('publish_time')->asc();
		} else {
			$comments->order('publish_time')->desc();
		}

		$comments->option('load-all-props')->value(true);
		$comments->limit($curr_page * $per_page, $per_page);

		$result = $comments->result();
		$total = $comments->length();

		$lines = Array();
		$i = 0;

		$users = cmsController::getInstance()->getModule('users');

		foreach($result as $element) {
			$line_arr = Array();

			if (!$element instanceof umiHierarchyElement) {
				continue;
			}

			$element_id = $element->getId();
			$line_arr['attribute:id'] = $element_id;
			$line_arr['attribute:title'] = $element->getName();
			$line_arr['attribute:author_id'] = $author_id = $element->getValue("author_id");
			$line_arr['attribute:num'] = ($per_page * $curr_page) + (++$i);
			$line_arr['xlink:href'] = "upage://" . $element_id;
			$line_arr['xlink:author-href'] = "udata://users/viewAuthor/" . $author_id;
			$line_arr['message'] = $this->module->formatMessage($element->getValue("message"));
			$line_arr['author'] = $users->viewAuthor($author_id);
			$publish_time = $element->getValue('publish_time');

			if ($publish_time instanceof umiDate) {
				$line_arr['attribute:publish_time'] = $publish_time->getFormattedDate("d.m.Y")." в ".$publish_time->getFormattedDate("H:i");
			}

			$eval = $element->rating_value;
			$eval = $eval ? $eval : 0;


			$eval = self::makeEvalLine($eval);
			$eval = $eval['html'];



			$plus = $element->good;
			if($plus){
				$plus = '<div class="plus">'.$plus.'</div>';
			}else{
				$plus = '';
			}



			$minus = $element->bad;
			if($minus){
				$minus = '<div class="minus">'.$minus.'</div>';
			}else{
				$minus = '';
			}

			$name = $line_arr['author']['nickname'];
			if(array_key_exists('fname',$line_arr['author']) !== false){
				$name = $line_arr['author']['fname'];
			}


			$line_arr['html'] = '<div class="review">
				<div class="author left"><b>'.$name.'</b> <span class="date">'.self::dateru($element->publish_time->getDateTimeStamp() ).'</span></div>
				<div class="rating right">'.$eval.'</div>
				<div class="clear"></div>
				<div class="text">'.$element->message.'</div>
				<div class="pluses"><b>Достоинства:</b> '.$element->dostoinstva.'</div>
				<div class="minuses"><b>Недостатки:</b> '.$element->nedostatki.'</div>
			</div>';

			comments::pushEditable("comments", "comment", $element_id);
			$lines[] = comments::parseTemplate($template_line, $line_arr, $element_id);
		}

		$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;

		$block_arr['per_page'] = $per_page;
		$block_arr['total'] = $total;
		$catalog = cmsController::getInstance()->getModule("emarket");
		$block_arr['word'] = $total." ".$catalog->getNumEnding($total,array('отзыв', 'отзыва', 'отзывов'));

		$add_arr = Array();


		$template_add = comments::parseTemplate($template_add, $add_arr, $parent_element_id);

			$block_arr['pagenum'] = cmsController::getInstance()->getModule('content')->generateNumPage($total,$per_page) ;

		$block_arr['action'] = $this->module->pre_lang . "/comments/post/" . $parent_element_id . "/";

		if (comments::isXSLTResultMode()) {
			$isGuestAllowed = $umiRegistry->getVal("//modules/comments/allow_guest");

			if (!$isAuth && !$isGuestAllowed) {
				unset($block_arr['action']);
				unset($block_arr['add_form']);
			}
		}
		return comments::parseTemplate($template_block, $block_arr, $parent_element_id);
	}
	};
?>
