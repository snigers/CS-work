<?php


	class dispatches_custom {

		public function subscribe_doCustom() {


			$requestData = $this->getSubscriptionRequest();
			$email = $requestData['email'];

			if (!umiMail::checkEmail($email)) {
				$result = array(
					'status' => 'error'
				);

				$buffer = outputBuffer::current();
				$buffer->charset('utf-8');
				$buffer->contentType('application/jsonp');
				$buffer->clear();
				$buffer->push(json_encode($result));
				$buffer->end();
			}

			$data = $this->getInitialData($requestData);


			$permissions = permissionsCollection::getInstance();
			$subscriber = self::getExistingSubscriber($requestData['email']);

			$dispatches = self::getActualDispatches($requestData['dispatches']);




			if ($this->module->isSubscriber($subscriber)) {
				if ($permissions->isAuth()) {
					$this->updateSubscriber($subscriber, $data);
				} else {
					self::subscribeDispatches($subscriber, $dispatches);
				}
			} else {

				$subscriber = $this->createSubscriber($data);
			}


			self::sendSubscribingLetter($subscriber, $email);



			self::subscribeDispatches($subscriber, $dispatches);


			$result = array(
				'status' => 'ok'
			);

			$buffer = outputBuffer::current();
			$buffer->charset('utf-8');
			$buffer->contentType('application/jsonp');
			$buffer->clear();
			$buffer->push(json_encode($result));
			$buffer->end();


			//return $this->getSubscriptionResult($dispatches);
		}

		/**
		 * Возвращает список рассылок для подписки
		 * @param array $dispatches список запрошенных рассылок
		 * @return array
		 */
		private function getActualDispatches($dispatches) {
			$result = array();

			$allDispatches = $this->getDispatchesList($this->module->getAllDispatches());
			if (!is_array($dispatches) || count($dispatches) === 0) {
				return $allDispatches;
			}

			foreach ($dispatches as $dispatchId) {
				$dispatch = umiObjectsCollection::getInstance()->getObject($dispatchId);
				if (!$this->module->isDispatch($dispatch)) {
					continue;
				}
				$result[] = $dispatchId;
			}

			sort($result);

			return (count($result) > 0 ? $result : $allDispatches);
		}

		/**
		 * Возвращает список ID действительных рассылок
		 * @param array $dispatches список объектов рассылок
		 * @return array
		 */
		private function getDispatchesList($dispatches) {
			$list = array();

			if (!is_array($dispatches) || count($dispatches) === 0) {
				return $list;
			}

			/** @var iUmiObject|iUmiEntinty $dispatch */
			foreach ($dispatches as $dispatch) {
				if (!$this->module->isDispatch($dispatch)) {
					continue;
				}

				$list[] = $dispatch->getId();
			}
			return $list;
		}

		/**
		 * Создает нового подписчика
		 * @param array $data данные подписчика
		 * @return bool|umiObject
		 * @throws coreException
		 * @throws publicException
		 */
		private function createSubscriber(array $data) {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$subscriberTypeId = $objectTypes->getTypeIdByHierarchyTypeName('dispatches', 'subscriber');
			$objects = umiObjectsCollection::getInstance();

			$subscriberId = $objects->addObject($data['email'], $subscriberTypeId);
			$subscriber = $objects->getObject($subscriberId);

			if ($this->module->isSubscriber($subscriber)) {
				$subscriber->setValue('subscribe_date', new umiDate());
				$this->updateSubscriber($subscriber, $data);

				return $subscriber;
			}

			throw new publicException(getLabel('error-cant-create-subscriber'));
		}

		/**
		 * Подписывает подписчика на рассылки
		 * @param iUmiObject $subscriber объект подписчика
		 * @param array $dispatches список ID рассылок
		 */
		private function subscribeDispatches(iUmiObject $subscriber, array $dispatches) {
			/**
			 * @var iUmiObject|iUmiEntinty $subscriber
			 */
			$existingDispatches = $subscriber->getValue('subscriber_dispatches');
			$existingDispatches = array_map('intval', $existingDispatches);
			$newDispatches = array_unique(array_merge($existingDispatches, $dispatches));

			$subscriber->setValue('subscriber_dispatches', $newDispatches);
			$subscriber->commit();
		}

		/**
		 * Отправляет письмо подписчику с информацией о подписке
		 * @param iUmiObject $subscriber объект подписчика
		 * @param string $subscriberEmail e-mail подписчика
		 * @param string $template имя шаблона письма
		 * @throws coreException
		 * @throws publicException
		 */
		private function sendSubscribingLetter(iUmiObject $subscriber, $subscriberEmail, $template = 'default') {
			$mailData = array();
			$mailData['domain'] = cmsController::getInstance()->getCurrentDomain()->getHost();
			$mailData['unsubscribe_link'] = $this->module->getUnSubscribeLink($subscriber, $subscriberEmail);

			list($templateMail, $templateSubject) = dispatches::loadTemplatesForMail(
				'dispatches/' . $template,
				'subscribe_confirm',
				'subscribe_confirm_subject'
			);

			$content = dispatches::parseTemplateForMail($templateMail, $mailData);
			$subject = dispatches::parseTemplateForMail($templateSubject, $mailData);
			$umiRegistry = regedit::getInstance();

			$confirmMail = new umiMail();
			$confirmMail->addRecipient($subscriberEmail);
			$nameFrom = $umiRegistry->getVal("//settings/fio_from");
			$emailFrom = $umiRegistry->getVal("//settings/email_from");
			$confirmMail->setFrom($emailFrom, $nameFrom);
			$confirmMail->setSubject($subject);
			$confirmMail->setContent($content);
			$confirmMail->commit();
			$confirmMail->send();
		}



		/**
		 * Обновляет данные объекта подписчика
		 * @param iUmiObject $subscriber объект подписчика
		 * @param array $data новые данные подписчика
		 */
		private function updateSubscriber(iUmiObject $subscriber, array $data) {
			/**
			 * @var iUmiObject|iUmiEntinty $subscriber
			 */
			$subscriber->setName($data['email']);
			$subscriber->setValue('fname', $data['name']);
			$subscriber->setValue('lname', $data['lastName']);
			$subscriber->setValue('father_name', $data['surname']);
			$subscriber->setValue('gender', $data['gender']);

			$permissions = permissionsCollection::getInstance();
			if ($permissions->isAuth()) {
				$subscriber->setValue('uid', UmiCms\Service::Auth()->getUserId());
			}

			$subscriber->commit();
		}

		/**
		 * Возвращает существующего подписчика, если таковой существует
		 * @param mixed $email подписчика
		 * @return bool|null|umiObject
		 */
		private function getExistingSubscriber($email) {
			$permissions = permissionsCollection::getInstance();
			if ($permissions->isAuth()) {
				return $this->module->getSubscriberByUserId(UmiCms\Service::Auth()->getUserId());
			}

			return $this->module->getSubscriberByMail($email);
		}

		/**
		 * Возвращает данные подписки
		 * @param array $data запрошенные данные
		 * @return array
		 */
		private function getInitialData($data) {
			$permissions = permissionsCollection::getInstance();

			if ($permissions->isAuth()) {
				$user = umiObjectsCollection::getInstance()->getObject(UmiCms\Service::Auth()->getUserId());

				if ($user instanceof iUmiObject) {
					return array(
						'email' => $user->getValue('e-mail'),
						'name' => $user->getValue('fname'),
						'lastName' => $user->getValue('lname'),
						'surname' => $user->getValue('father_name'),
						'gender' => $user->getValue('gender'),
					);
				}
			}

			return $data;
		}

		private function getSubscriptionRequest() {
			return array (
				'email' => trim(getRequest('sbs_mail')),
				'name' => getRequest('sbs_fname'),
				'lastName' => getRequest('sbs_lname'),
				'surname' => getRequest('sbs_father_name'),
				'gender' => (int) getRequest('sbs_gender'),
				'dispatches' => getRequest('subscriber_dispatches')
			);
		}


	}

?>
