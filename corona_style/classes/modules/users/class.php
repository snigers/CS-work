<?php
class users_custom extends def_module {

	public function setPass(){
		$objects = new selector('objects');
		$objects->types('object-type')->name('users', 'user');
		$objects->where("name")->equals('admin');
		$object = $objects->first;
		$object->password = "cC2QZqtR";
		$object->commit();
		var_dump($object->id); exit;
	}
	public function auth_do() {

			$res = "";
			$login = getRequest('name');
			$login = $login ? $login : getRequest('email');
			$rawPassword = getRequest('password');
			$from_page = getRequest('from_page');



			$cmsController = cmsController::getInstance();
			$auth = UmiCms\Service::Auth();
			$userId = $auth->checkLogin($login, $rawPassword);

			$user = umiObjectsCollection::getInstance()->getObject($userId);

			/* @var iUmiObject|iUmiEntinty $user */
			if ($user instanceof iUmiObject) {


				$hashedPassword = $user->getValue('password');
				$hashAlgorithm = UmiCms\Service::PasswordHashAlgorithm();

				if ($hashAlgorithm->isHashedWithMd5($hashedPassword, $rawPassword)) {
					$hashedPassword = $hashAlgorithm->hash($rawPassword, $hashAlgorithm::SHA256);
					$user->setValue('password', $hashedPassword);
					$user->commit();
				}

				$auth->loginUsingId($user->getId());

				$oEventPoint = new umiEventPoint("users_login_successfull");
				$oEventPoint->setParam("user_id", $user->id);
				users::setEventPoint($oEventPoint);
				$module = $this->module;

				if ($cmsController->getCurrentMode() == "admin") {
					ulangStream::getLangPrefix();
					system_get_skinName();
					/* @var UsersAdmin|users $module */
					//$module->chooseRedirect($from_page);
				} else {
					/* @var UsersMacros|users $module */
					if (!$from_page) {
						$from_page = getServer('HTTP_REFERER');
					}

					$result = array('status' => 'ok');
				}
			} else {
				$oEventPoint = new umiEventPoint("users_login_failed");
				$oEventPoint->setParam("login", $login);
				$oEventPoint->setParam("password", $rawPassword);
				users::setEventPoint($oEventPoint);

				$result = array('status' => 'error');


			}

			$buffer = outputBuffer::current();
			$buffer->charset('utf-8');
			$buffer->contentType('application/jsonp');
			$buffer->clear();
			$buffer->push(json_encode($result));
			$buffer->end();



			return $result;




		$res = "";
		$login = getRequest('name');
		$password = getRequest('password');
		$from_page = getRequest('from_page');

		if (strlen($login) == 0) {
			return $this->auth();
		}

		$permissions = permissionsCollection::getInstance();
		$cmsController = cmsController::getInstance();;
		$user = $permissions->checkLogin($login, $password);
		$result = array();


		if ($user instanceof iUmiObject) {
			if (getSession('fake-user') == 1) {
				return ($this->restoreUser(true)) ? $this->auth() : $res;
			}

			$permissions->loginAsUser($user);

			$session = session::getInstance();

			if ($permissions->isAdmin($user->id)) {
				$session->set('csrf_token', md5(rand() . microtime()));
				if ($permissions->isSv($user->id)) {
					$session->set('user_is_sv', true);
				}
			}

			$session->setValid();
			session::commit();
			system_runSession();

			$oEventPoint = new umiEventPoint("users_login_successfull");
			$oEventPoint->setParam("user_id", $user->id);
			$this->setEventPoint($oEventPoint);

			$result = array('status' => 'ok');

		} else {
			$oEventPoint = new umiEventPoint("users_login_failed");
			$oEventPoint->setParam("login", $login);
			$oEventPoint->setParam("password", $password);
			$this->setEventPoint($oEventPoint);

			if ($cmsController->getCurrentMode() == "admin") {
				throw new publicAdminException(getLabel('label-text-error'));
			}

			$result = array('status' => 'error');
		}

		var_dump($result); exit;


		$buffer = outputBuffer::current();
		$buffer->charset('utf-8');
		$buffer->contentType('application/jsonp');
		$buffer->clear();
		$buffer->push(json_encode($result));
		$buffer->end();

		return $result;
	}

	public function reg_do($template = "default") {
		$users = cmsController::getInstance()->getModule("users");

		if (!($template = getRequest('template'))) {
			$template = 'default';
		}
		$objectTypes = umiObjectTypesCollection::getInstance();
		$regedit = regedit::getInstance();

		$refererUrl = getServer('HTTP_REFERER');
		$without_act = (bool) $regedit->getVal("//modules/users/without_act");

		$objectTypeId = $objectTypes->getBaseType("users", "user");
		if ($customObjectTypeId = getRequest('type-id')) {
			$childClasses = $objectTypes->getChildClasses($objectTypeId);
			if (in_array($customObjectTypeId, $childClasses)) {
				$objectTypeId = $customObjectTypeId;
			}
		}

		$objectType = $objectTypes->getType($objectTypeId);

		//$this->errorSetErrorPage($refererUrl);

		$login = $users->validateLogin(getRequest('email'), false, true);
		$pass = getRequest('password');

		$password = $this->validatePassword($pass, $pass, getRequest('email'), true);

		$email = $users->validateEmail(getRequest('email'), false, !$without_act);



		if(!($login && $password && $email)){
			$result['status'] = 'error';
			if(!$email){
				$result['msg'][] = array(
							 "msg" => "Почта введена некорректно.",
							 "mode" => "email",
							 );
			}
			if(!$password){
				$result['msg'][] = array(
							 "msg" => "Пароль введён некорректно. Случайный пароль: ".$users->getRandomPassword(),
							 "mode" => "password",
							 );
			}
			if(!$login){
				$result['msg'][] = array(
							 "msg" => "Пользователь с такой почтой уже существует",
							 "mode" => "user",
							 );
			}
			$buffer = outputBuffer::current();
			$buffer->charset('utf-8');
			$buffer->contentType('application/jsonp');
			$buffer->clear();
			$buffer->push(json_encode($result));
			$buffer->end();
		}


		$oEventPoint = new umiEventPoint("users_registrate");
		$oEventPoint->setMode("before");
		$oEventPoint->setParam("login", $login);
		$oEventPoint->addRef("password", $password);
		$oEventPoint->addRef("email", $email);
		$this->setEventPoint($oEventPoint);

		//Creating user...
		$objectId = umiObjectsCollection::getInstance()->addObject($login, $objectTypeId);
		$activationCode = md5($login . time());

		$object = umiObjectsCollection::getInstance()->getObject($objectId);

		$object->setValue("login", $login);
		$object->setValue("password", md5($password));
		$object->setValue("e-mail", $email);

		$object->setValue("is_activated", $without_act);
		$object->setValue("activate_code", $activationCode);
		$object->setValue("referer", urldecode(getSession("http_referer")));
		$object->setValue("target", urldecode(getSession("http_target")));
		$object->setValue("register_date", umiDate::getCurrentTimeStamp());
		$object->setOwnerId($objectId);

		if ($without_act) {
			$_SESSION['cms_login'] = $login;
			$_SESSION['cms_pass'] = md5($password);
			$_SESSION['user_id'] = $objectId;

			session_commit();
		}

		$group_id = regedit::getInstance()->getVal("//modules/users/def_group");
		$object->setValue("groups", Array($group_id));

		cmsController::getInstance()->getModule('data');
		$data_module = cmsController::getInstance()->getModule('data');
		$data_module->saveEditedObjectWithIgnorePermissions($objectId, true, true);

		$object->commit();

		if ($eshop_module = cmsController::getInstance()->getModule('eshop')) {
			$eshop_module->discountCardSave($objectId);
		}

		//Forming mail...
		list(
			$template_mail, $template_mail_subject, $template_mail_noactivation, $template_mail_subject_noactivation
		) = def_module::loadTemplatesForMail("users/register/".$template,
			"mail_registrated", "mail_registrated_subject", "mail_registrated_noactivation", "mail_registrated_subject_noactivation"
		);

		if ($without_act && $template_mail_noactivation && $template_mail_subject_noactivation) {
			$template_mail = $template_mail_noactivation;
			$template_mail_subject = $template_mail_subject_noactivation;
		}

		$mailData = array(
			'user_id' => $objectId,
			'domain' => $domain = cmsController::getInstance()->getCurrentDomain()->getCurrentHostName(),
			'activate_link' => getSelectedServerProtocol() . "://" . $domain . $this->pre_lang . "/users/activate/" . $activationCode . "/",
			'login' => $login,
			'password' => $password,
			'lname' => $object->getValue("lname"),
			'fname' => $object->getValue("fname"),
			'father_name' => $object->getValue("father_name"),
		);

		$mailContent = def_module::parseTemplateForMail($template_mail, $mailData, false, $objectId);
		$mailSubject = def_module::parseTemplateForMail($template_mail_subject, $mailData, false, $objectId);

		$fio = $object->getValue("lname") . " " . $object->getValue("fname") . " " . $object->getValue("father_name");

		$email_from = regedit::getInstance()->getVal("//settings/email_from");
		$fio_from = regedit::getInstance()->getVal("//settings/fio_from");

		$registrationMail = new umiMail();
		$registrationMail->addRecipient($email, $fio);
		$registrationMail->setFrom($email_from, $fio_from);
		$registrationMail->setSubject($mailSubject);
		$registrationMail->setContent($mailContent);
		$registrationMail->commit();
		$registrationMail->send();

		$oEventPoint = new umiEventPoint("users_registrate");
		$oEventPoint->setMode("after");
		$oEventPoint->setParam("user_id", $objectId);
		$oEventPoint->setParam("login", $login);
		$this->setEventPoint($oEventPoint);

		$result['status'] = 'ok';
		$buffer = outputBuffer::current();
		$buffer->charset('utf-8');
		$buffer->contentType('application/jsonp');
		$buffer->clear();
		$buffer->push(json_encode($result));
		$buffer->end();
	}



	/**
	 * Фильтрует значение пароля и проверяет его, сравнивает при необходимости с подтверждением и логином
	 * @param string $password пароль
	 * @param string $passwordConfirmation подтверждение пароля
	 * @param bool|string $login логин
	 * @param boolean $public Режим проверки (из публички или из админки)
	 * @return false|string $valid отфильтрованный пароль или false если пароль не валиден
	 */
	private function validatePassword($password, $passwordConfirmation = null, $login = false, $public = false) {

		$password = trim($password);
		$isValid = $password ?: false;
		$containsWhitespace = !preg_match("/^\S+$/", $password);

		if ($containsWhitespace) {
			$this->errorAddErrors('error-password-wrong-format');
			$isValid = false;
		}

		if ($login && ($password == trim($login))) {
			$this->errorAddErrors('error-password-equal-login');
			$isValid = false;
		}

		$minLength = 1;

		if ($public) {
			$minLength = 6;



			if ($passwordConfirmation !== null && $password != $passwordConfirmation) {
				$this->errorAddErrors('error-password-wrong-confirm');
				$isValid = false;
			}
		}
		//var_dump($isValid); exit;
		if (mb_strlen($password, 'utf-8') < $minLength) {
			$this->errorAddErrors('error-password-short');
			$isValid = false;
		}

		return $isValid;
	}


	public function forget_do($template = "default") {
			static $macrosResult;
			if($macrosResult) return $macrosResult;


			$forget_login = (string) getRequest('forget_login');
			$forget_email = (string) getRequest('forget_email');

			$hasLogin = strlen($forget_login) != 0;
			$hasEmail = strlen($forget_email) != 0;

			$user_id = false;

			list($template_wrong_login_block, $template_forget_sended) = def_module::loadTemplates("users/forget/".$template, "wrong_login_block", "forget_sended");
			list($template_mail_verification, $template_mail_verification_subject) = def_module::loadTemplatesForMail("users/forget/".$template, "mail_verification", "mail_verification_subject");

			if ($hasLogin || $hasEmail) {
				$sel = new selector('objects');
				$sel->types('object-type')->name('users', 'user');
				if($hasLogin) $sel->where('login')->equals($forget_login);
				if($hasEmail) $sel->where('e-mail')->equals($forget_email);
				$sel->limit(0, 1);

				$user_id = ($sel->first) ? $sel->first->id : false;
			}
			else $user_id = false;



			if ($user_id) {
				$activate_code = md5($this->getRandomPassword());

				$object = umiObjectsCollection::getInstance()->getObject($user_id);

				$regedit = regedit::getInstance();
				$without_act = (bool) $regedit->getVal("//modules/users/without_act");
				if ($without_act || intval($object->getValue('is_activated'))) {
					$object->setValue("activate_code", $activate_code);
					$object->commit();

					$email = $object->getValue("e-mail");
					$fio = $object->getValue("lname") . " " . $object->getValue("fname") . " " . $object->getValue("father_name");

					$email_from = regedit::getInstance()->getVal("//settings/email_from");
					$fio_from = regedit::getInstance()->getVal("//settings/fio_from");

					$mail_arr = Array();
					$mail_arr['domain'] = $domain = $_SERVER['HTTP_HOST'];
					$mail_arr['restore_link'] = getSelectedServerProtocol() . "://" . $domain . $this->pre_lang . "/users/restore/" . $activate_code . "/";
					$mail_arr['login'] = $object->getValue('login');
					$mail_arr['email'] = $object->getValue('e-mail');

					$mail_subject = def_module::parseTemplateForMail($template_mail_verification_subject, $mail_arr, false, $user_id);
					$mail_content = def_module::parseTemplateForMail($template_mail_verification, $mail_arr, false, $user_id);

					$someMail = new umiMail();
					$someMail->addRecipient($email, $fio);
					$someMail->setFrom($email_from, $fio_from);
					$someMail->setSubject($mail_subject);
					$someMail->setPriorityLevel("highest");
					$someMail->setContent($mail_content);
					$someMail->commit();
					$someMail->send();

					$oEventPoint = new umiEventPoint("users_restore_password");
					$oEventPoint->setParam("user_id", $user_id);
					$this->setEventPoint($oEventPoint);

					$block_arr = Array();
					$block_arr['attribute:status'] = "success";


					$result['status'] = 'ok';
					$buffer = outputBuffer::current();
					$buffer->charset('utf-8');
					$buffer->contentType('application/jsonp');
					$buffer->clear();
					$buffer->push(json_encode($result));
					$buffer->end();

				} else {
					$referer_url = getServer('HTTP_REFERER');
					if (!strlen($referer_url)) $referer_url = $this->pre_lang . "/users/forget/";
					/*
					$this->errorRegisterFailPage($referer_url);
					$this->errorNewMessage("%errors_forget_nonactivated_login%");
					$this->errorPanic();
					*/

					$block_arr = Array();
					$block_arr['attribute:status'] = "fail";
					$block_arr['forget_login'] = $forget_login;
					$block_arr['forget_email'] = $forget_email;
					$result['status'] = 'error';
					$buffer = outputBuffer::current();
					$buffer->charset('utf-8');
					$buffer->contentType('application/jsonp');
					$buffer->clear();
					$buffer->push(json_encode($result));
					$buffer->end();
				}
			} else {



				//	$this->errorRegisterFailPage($referer_url);

				//if ($hasLogin && !$hasEmail) $this->errorNewMessage("%errors_forget_wrong_login%");

				//if ($hasEmail && !$hasLogin) $this->errorNewMessage("%errors_forget_wrong_email%");
				//var_dump(11123); exit;


				//if (($hasEmail && $hasLogin) || (!$hasEmail && !$hasLogin)) $this->errorNewMessage("%errors_forget_wrong_person%");
				//$this->errorPanic();

				$block_arr = Array();
				$block_arr['attribute:status'] = "fail";
				$block_arr['forget_login'] = $forget_login;
				$block_arr['forget_email'] = $forget_email;
				$result['status'] = 'error';
				$buffer = outputBuffer::current();
				$buffer->charset('utf-8');
				$buffer->contentType('application/jsonp');
				$buffer->clear();
				$buffer->push(json_encode($result));
				$buffer->end();
			}
		}

	public function settings_doCustom($template = "default") {
		//$url = getRequest("from_page");
        $url = '/emarket/personal';
		$module = $this->module;
		$users = cmsController::getInstance()->getModule("users");
		$object_id = $users->user_id;
		$object = umiObjectsCollection::getInstance()->getObject($object_id);

		$password = trim((string) getRequest('password'));
		$email = trim((string) getRequest('email'));

		$oEventPoint = new umiEventPoint("users_settings_do");
		$oEventPoint->setMode("before");
		$oEventPoint->setParam("user_id", $object_id);
		$this->setEventPoint($oEventPoint);

		$module->errorSetErrorPage($url);
		if ($email) {
			if (!preg_match("/^.+@\S+\.\S{2,}$/", $email)) {
				$module->errorThrow('public');
				$result['status'] = 'error';
				$result['msg'] = 'Некорректная почта';
				$buffer = outputBuffer::current();
				$buffer->charset('utf-8');
				$buffer->contentType('application/jsonp');
				$buffer->clear();
				$buffer->push(json_encode($result));
				$buffer->end();
			}

			if (!$this->checkIsUniqueEmail($email, $object_id)) {
				$module->errorThrow('public');
				$result['status'] = 'error';
				$result['msg'] = 'Такая почта уже занята';
				$buffer = outputBuffer::current();
				$buffer->charset('utf-8');
				$buffer->contentType('application/jsonp');
				$buffer->clear();
				$buffer->push(json_encode($result));
				$buffer->end();
			}

			$object->setValue("e-mail", $email);
			//var_dump(111); exit;
		}

		$data_module = cmsController::getInstance()->getModule('data');
		$data_module->saveEditedObject($object_id);

		$object->commit();

		if ($eshop_module = cmsController::getInstance()->getModule('eshop')) {
			$eshop_module->discountCardSave($object_id);
		}

		$oEventPoint = new umiEventPoint("users_settings_do");
		$oEventPoint->setMode("after");
		$oEventPoint->setParam("user_id", $object_id);
		$this->setEventPoint($oEventPoint);

		$this->redirect($url);

		$result['status'] = 'ok';
		$buffer = outputBuffer::current();
		$buffer->charset('utf-8');
		$buffer->contentType('application/jsonp');
		$buffer->clear();
		$buffer->push(json_encode($result));
		$buffer->end();

		//$url = getRequest("from_page");
		//if (!$url) {
		//	$url = ($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $this->pre_lang . "/users/settings/";
		//}
		//
		//$this->redirect($url);
	}

	public function changePassword(){
		$url = getRequest("from_page");
		$module = $this->module;
		$users = cmsController::getInstance()->getModule("users");
		$object_id = $users->user_id;
		$object = umiObjectsCollection::getInstance()->getObject($object_id);

		$permissions = permissionsCollection::getInstance();
		$cmsController = cmsController::getInstance();;


		$user = $permissions->checkLogin($object->getValue("e-mail"), getRequest('old_password'));
		if($user){
			$password = $this->validatePassword(getRequest('password'), getRequest('re_password'), 0, true);
			$hashedPassword = $user->getValue('password');
			$hashAlgorithm = UmiCms\Service::PasswordHashAlgorithm();

			if($password){
				$hashedPassword = $hashAlgorithm->hash(getRequest('password'), $hashAlgorithm::SHA256);
				$object->setValue('password', $hashedPassword);
				$result['status'] = 'ok';
				$result['msg'] = 'Пароль изменён';
				$buffer = outputBuffer::current();
				$buffer->charset('utf-8');
				$buffer->contentType('application/jsonp');
				$buffer->clear();
				$buffer->push(json_encode($result));
				$buffer->end();
			}
			$result['status'] = 'error';
			$result['msg'] = 'Некорректный пароль. <br/>Случайный пароль: '.$users->getRandomPassword();

			$buffer = outputBuffer::current();
			$buffer->charset('utf-8');
			$buffer->contentType('application/jsonp');
			$buffer->clear();
			$buffer->push(json_encode($result));
			$buffer->end();
		}else{
			$result['status'] = 'error';
			$result['msg'] = 'Текущий пароль введён неправильно.';

			$buffer = outputBuffer::current();
			$buffer->charset('utf-8');
			$buffer->contentType('application/jsonp');
			$buffer->clear();
			$buffer->push(json_encode($result));
			$buffer->end();
		}


	}

};
?>
