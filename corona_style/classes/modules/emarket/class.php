<?php
	class emarket_custom extends def_module {


        public function sendManagerNotificationCustom() {
        	$order = order::get(10120);
            $umiRegistry = regedit::getInstance();
            $cmsController = cmsController::getInstance();
            $umiDomains = domainsCollection::getInstance();
            $umiObjects = umiObjectsCollection::getInstance();

            $currentDomain = $cmsController->getCurrentDomain();
            $defaultDomain = $umiDomains->getDefaultDomain();
            $currentDomainId = ($currentDomain instanceof domain) ? $currentDomain->getId() : null;
            $defaultDomainId = ($defaultDomain instanceof domain) ? $defaultDomain->getId() : null;

            if ($umiRegistry->get("//modules/emarket/manager-email/{$currentDomainId}")) {
                $emails = $umiRegistry->get("//modules/emarket/manager-email/{$currentDomainId}");
                $fromMail = $umiRegistry->get("//modules/emarket/from-email/{$currentDomainId}");
                $fromName = $umiRegistry->get("//modules/emarket/from-name/{$currentDomainId}");
            } elseif ($umiRegistry->get("//modules/emarket/manager-email/{$defaultDomainId}")) {
                $emails = $umiRegistry->get("//modules/emarket/manager-email/{$defaultDomainId}");
                $fromMail = $umiRegistry->get("//modules/emarket/from-email/{$defaultDomainId}");
                $fromName = $umiRegistry->get("//modules/emarket/from-name/{$defaultDomainId}");
            } else {
                $emails = $umiRegistry->get('//modules/emarket/manager-email');
                $fromMail = $umiRegistry->get("//modules/emarket/from-email");
                $fromName = $umiRegistry->get("//modules/emarket/from-name");
            }

            $mail = new umiMail();
            $recipientsCount = 0;

            foreach (explode(',', $emails) as $recipient) {
                $recipient = trim($recipient);

                if (mb_strlen($recipient) && umiMail::checkEmail($recipient)) {
                    $mail->addRecipient($recipient);
                    $recipientsCount++;
                }
            }

            if ($recipientsCount == 0) {
                return false;
            }

            try {
                /** @var payment $payment */
                $payment = payment::get($order->payment_id, $order);
                $paymentName = $payment ? $payment->name : '';
                $paymentStatus = order::getCodeByStatus($order->getPaymentStatus());
            } catch (coreException $e) {
                $paymentName = "";
                $paymentStatus = "";
            }

            $customer = $umiObjects->getObject($order->getCustomerId());
            $module = $this->module;
            $currency = $module->getCurrentCurrency();
            $suffix = $currency->getValue('suffix');
            $items = $module->getOrderItems($order, $suffix);
            $delivery = $module->getDeliveryName($order);
            $address = $module->getDeliveryAddress($order);

            $variables = [
                'order_id' => $order->id,
                'order_name' => $order->name,
                'order_number' => $order->number,
                'payment_type' => $paymentName,
                'payment_status' => $paymentStatus,
                'price' => $order->getActualPrice(),
                'total_price' => $order->getActualPrice(),
                'total_amount' => $order->getTotalAmount(),
                'domain' => ($currentDomain instanceof domain) ? $currentDomain->getCurrentHostName() : getServer('HTTP_HOST'),
                'suffix' => $suffix,
                '+items' => $items,
                'first_name' => $customer->getValue('fname'),
                'last_name' => $customer->getValue('lname'),
                'email' => $module->getCustomerEmail($order, $customer),
                'phone' => $customer->getValue('phone'),
                'delivery' => $delivery,
                'address' => $address,
            ];

            $labels = $cmsController->langs;
            $header = $labels['notification-neworder-subject'] . " (#{$order->number})";

            $subject = null;
            $content = null;

            if ($module->isUsingUmiNotifications()) {
                $mailNotifications = UmiCms\Service::MailNotifications();
                $notification = $mailNotifications->getCurrentByName('notification-emarket-new-order');

                if ($notification instanceof MailNotification) {
                    $subjectTemplate = $notification->getTemplateByName('emarket-neworder-notification-subject');
                    $contentTemplate = $notification->getTemplateByName('emarket-neworder-notification-content');

                    if ($subjectTemplate instanceof MailTemplate) {
                        $subject = $subjectTemplate->getProcessedContent(['header' => $header]);
                    }

                    if ($contentTemplate instanceof MailTemplate) {
                        $content = $contentTemplate->getProcessedContent($variables);
                    }
                }
            } else {
                try {
                    list($contentTemplate) = emarket::loadTemplatesForMail(
                        "emarket/mail/default",
                        "neworder_notification"
                    );
                    $subject = $header;
                    $content = emarket::parseTemplateForMail($contentTemplate, $variables);
                } catch (Exception $e) {
                    // nothing
                }
            }

            if ($subject === null || $content === null) {
                return false;
            }

            $mail->setFrom($fromMail, $fromName);
            $mail->setSubject($subject);
            $mail->setContent($content);
            $mail->commit();
            $mail->send();
            var_dump('ok'); exit;
            return true;
        }

		public function setOrderPayment(){
			$paymentId = getRequest("pid");
			if($paymentId){
                $emarket = cmsController::getInstance()->getModule("emarket");
                $order = $emarket->getBasketOrder(false);
                $order->payment_id = $paymentId;
			}
			return true;
		}

		public function getDeliveryPrice1(){
            $customer = customer::get();

            $delivery = delivery::get("delivery-id");
		}

		public function getCustomerId(){
			$customer = customer::get();
			if($customer){
				return $customer->id;
			}
		}

		public function getProductsForDD(){
			$emarket = cmsController::getInstance()->getModule("emarket");
			$catalog = cmsController::getInstance()->getModule("catalog");
			$order = $emarket->getBasketOrder(false);
			$items = $order->getItems();
			$result = array();
			foreach($items as $item){
				$element = $item->getItemElement();

				$price = $catalog->getPrice($element->id);
				$price = $price['price_not_format'];

				/*
				id: '122', // ID товара в CMS
					 name: 'Some piece of products', // Наименование товаров
					 price: 524, // Цена товара
					 width: 10, // ширина
					 height: 10, высота
					 length: 0, // длина
					 weight: 1, // вес
					 quantity: 2, // количество единиц товара
					 sku: 'SKU PRODUCT' // артикул товара
				*/

				$result[] = array(

					'id' => $element->id,
					'name' => $element->name,
					'price' => $price,
					'width' => 10,
					'height' => 10,
					'length' => 10,
					'weight' => 1,
					/*
					'width' => $element->width,
					'height' => $element->height,
					'length' => $element->length,
					'weight' => $element->weight,
					*/

					'quantity' => $item->getAmount(),
					'sku' => $element->sku,

				);

			}
			return def_module::parseTemplate('',array(
								  'subnodes:items' => $result,
								  'total' => sizeof($result),
								  ));

		}

		public function addToCompareNew($element_id) {


			$customer = customer::get();
			$wishlist = $customer->compare_products;
			$wishlist_id = array();
			foreach($wishlist as $page){
				$wishlist_id[] = $page->id;
			}

			$wishlist_id[] = $element_id;
			array_unique($wishlist_id);

			$customer->compare_products = $wishlist_id;

			$result['status'] = 'ok';
			return $result;
		}

		public function removeFromCompareCustom($element_id) {

			$customer = customer::get();
			$wishlist = $customer->compare_products;
			$wishlist_id = array();

			foreach($wishlist as $page){
				if($page->id !== (int)$element_id){
					$wishlist_id[] = $page->id;
				}
			}

			$customer->compare_products = $wishlist_id;
			$customer->commit();


			$result = array();
			$result['status'] = 'ok';
			return $result;

			$buffer = outputBuffer::current();
			$buffer->charset('utf-8');
			$buffer->contentType('application/jsonp');
			$buffer->clear();
			$buffer->push(json_encode($result));
			$buffer->end();

		}

		public function getCompareElementsCustom() {
			$customer = customer::get();
			$compare_element = $customer->compare_products;



			$result = array();
			$parents = array();

			$umiHierarchy = umiHierarchy::getInstance();

			foreach($compare_element as $page){

				$parents[] = array(
								    '@id' => $page->id,
								    '@name' => $page->name
								    );
			}

			return def_module::parseTemplate('',array(
								  'subnodes:items' => $parents,
								  'total' => sizeof($parents),
								  ));
		}



		public function clear_compare() {
			$customer = customer::get();
			$customer->compare_products = array();
			$customer->commit();

			$result = array();
			$result['status'] = 'ok';
			return $result;

		}

		public function getCompareElementsCategory() {
			$customer = customer::get();
			$compare_element = $customer->compare_products;

			$result = array();
			$parents = array();

			$umiHierarchy = umiHierarchy::getInstance();

			foreach($compare_element as $page){

				$parentId = $page->getParentId();
				if(array_key_exists($parentId,$parents) === false){
					$parent = $umiHierarchy->getElement($parentId);
					$parents[$parentId] = array(
								    '@id' => $parentId,
								    '@name' => $parent->name,
										'@count' => 1,
								    );
				}else{
					$parents[$parentId]['@count']++;
				}
			}

			return def_module::parseTemplate('',array(
								  'subnodes:items' => $parents,
								  'total' => sizeof($parents)
								  ));
		}

		public function getCategoryCompare($categoryId){
			$customer = customer::get();
			$compare_element = $customer->compare_products;

			$result = array();
			$parents = array();

			$umiHierarchy = umiHierarchy::getInstance();
			$pages = array();

			foreach($compare_element as $page){
				$parentId = $page->getParentId();
				if($parentId == $categoryId){
					$pages[] = $page->id;
				}
			}
			$result = array();
			$catalog = cmsController::getInstance()->getModule('catalog');
			$umiHierarchy = umiHierarchy::getInstance();
			$umiObjectsCollection = umiObjectsCollection::getInstance();
			$result = array();



			$result = self::compareCustom("default","tech_properties",$pages);
			//var_dump(111); exit;
			return def_module::parseTemplate('',$result);


		}

		public function getAllProductsCompare(){
			$customer = customer::get();
			$compare_element = $customer->compare_products;

			$result = array();
			$parents = array();

			$umiHierarchy = umiHierarchy::getInstance();
			$pages = array();

			foreach($compare_element as $page){
					$parentId = $page->getParentId();
					$pages[] = $page->id;
			}
			$result = array();
			$catalog = cmsController::getInstance()->getModule('catalog');
			$umiHierarchy = umiHierarchy::getInstance();
			$umiObjectsCollection = umiObjectsCollection::getInstance();
			$result = array();



			$result = self::compareCustom("default","item_properties",$pages);
			//var_dump(111); exit;
			return def_module::parseTemplate('',$result);


		}

		/**
		 * Возвращает список товаров (объектов каталога), добавленных
		 * к сравнению со значениями полей заданных групп
		 * @param string $template имя шаблона (для tpl)
		 * @param string $groups_names строковые идентификатор групп полей,
		 * разделенные пробелом
		 * @return mixed
		 */
		private function compareCustom($template = "default", $groups_names = '', $elements = array()) {
			$catalog = cmsController::getInstance()->getModule('catalog');
			$umiHierarchy = umiHierarchy::getInstance();
			$umiObjectsCollection = umiObjectsCollection::getInstance();
			if (!$template) {
				$template = "default";
			}
			list(
				$template_block,
				$template_block_empty,
				$template_block_header,
				$template_block_header_item,
				$template_block_line,
				$template_block_line_item,
				$template_list_block,
				$template_list_block_line
				) = emarket::loadTemplates(
				"emarket/compare/{$template}",
				"compare_block",
				"compare_block_empty",
				"compare_block_header",
				"compare_block_header_item",
				"compare_block_line",
				"compare_block_line_item",
				"compare_list_block",
				"compare_list_block_line"
			);

			//$elements = $this->getCompareElements();

			if (sizeof($elements) == 0) {
				return $template_block_empty;
			}

			$hierarchy = umiHierarchy::getInstance();
			$hierarchy->loadElements($elements);
			$umiLinksHelper = umiLinksHelper::getInstance();
			$umiLinksHelper->loadLinkPartForPages($elements);

			$block_arr = array();
			$items = array();
			$headers_arr = array();

			foreach ($elements as $element_id) {
				$element = $hierarchy->getElement($element_id);

				if (!$element instanceof iUmiHierarchyElement) {
					continue;
				}

				$item_arr = array(
					'attribute:id' => $element_id,
					'attribute:link' => $umiLinksHelper->getLinkByParts($element),
					'node:title' => $element->getName(),
					'product' => $catalog->makeProductLine($element,$umiObjectsCollection,$umiHierarchy,"compare")
				);

				$items[] = emarket::parseTemplate($template_block_header_item, $item_arr, $element_id);
			}

			$headers_arr['subnodes:items'] = $items;
			$headers = emarket::parseTemplate($template_block_header, $headers_arr);

			$fields = array();
			foreach ($elements as $element_id) {
				$fields = array_merge($fields, $this->module->getComparableFields($element_id, $groups_names));
			}

			$lines = array();
			$iCnt = 0;

			/**
			 * @var iUmiField $field
			 */
			foreach ($fields as $field_name => $field) {
				$field_title = $field->getTitle();
				$items = array();
				$is_void = true;

				foreach ($elements as $element_id) {
					$element = $hierarchy->getElement($element_id);
                    $object = $element->getObject();
                    $property = $object->getPropByName($field->getName());
                    $data = $this->getPropertyData($property);
					$item_arr = array(
						'attribute:id'		=> $element_id,
						'void:name'			=> $field_name,
						'void:field_name'	=> $field_name,
						'value'				=> $element->getObject()->getPropByName($field_name)
					);
                    $item_arr['data'] = $data;

					if ($is_void && $element->getValue($field_name)) {
						$is_void = false;
					}

					$items[] = emarket::parseTemplate($template_block_line_item, $item_arr, $element_id);
				}

				if ($is_void) {
					continue;
				}

				$iCnt++;



				$line_arr = array(
					'attribute:title'	=> $field_title,
					'attribute:name'	=> $field_name,
					'attribute:type'	=> $field->getDataType(),
					'attribute:par'		=> intval($iCnt / 2 == ceil($iCnt / 2)),
					'subnodes:values'	=> $line_arr['void:items'] = $items
				);


				$lines[] = emarket::parseTemplate($template_block_line, $line_arr);
			}


			$block_arr['headers'] = $headers;
			$block_arr['void:lines'] = $block_arr['void:fields'] = $lines;
			$block_arr['fields'] = array();
			$block_arr['fields']['nodes:field'] = $lines;

			return emarket::parseTemplate($template_block, $block_arr);
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
        private function getPropertyData($property) {
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



        public function getDate(){
			$date = date("d.m.Y").", ".self::getDayRus();
			return $date;

		}

		private function getDayRus(){
			// массив с названиями дней недели
			 $days = array(
			 'Воскресенье' , 'Понедельник' ,
			'Вторник' , 'Среда' ,
			 'Четверг' , 'Пятница' , 'Суббота'
			 );
			// номер дня недели
			// с 0 до 6, 0 - воскресенье, 6 - суббота
			$num_day = (date('w'));
			// получаем название дня из массива
			$name_day = $days[$num_day];
			// вернем название дня
			 return $name_day;
		}

		public function testMail(){
			$order = order::get(1796);

			$changedStatus = "status_id";
			$codeName = 666;
			$customer = umiObjectsCollection::getInstance()->getObject($order->getCustomerId());
			$buyerOneClick = umiObjectsCollection::getInstance()->getObject($order->getValue('purchaser_one_click'));
			$emailOneClick = false;
			if ($buyerOneClick instanceof umiObject) {
				$emailOneClick = $buyerOneClick->email ? $buyerOneClick->email : $buyerOneClick->getValue("e-mail");
			}
			if ($emailOneClick) {
				$email = $emailOneClick;
			} else {
				$email    = $customer->email ? $customer->email : $customer->getValue("e-mail");
			}
			if($email) {
				$name  = $customer->lname . " " .$customer->fname . " " . $customer->father_name;
				$langs = cmsController::getInstance()->langs;
				$statusString = "";
				$subjectString = $langs['notification-status-subject'];
				$regedit = regedit::getInstance();
				switch($changedStatus) {
					case 'status_id' : {
						if ($regedit->getVal('//modules/emarket/no-order-status-notification')) return;
						if($codeName == 'waiting') {
							$paymentStatusCodeName = order::getCodeByStatus($order->getPaymentStatus());
							$pkey = 'notification-status-payment-' . $paymentStatusCodeName;
							$okey = 'notification-status-' . $codeName;
							$statusString = ($paymentStatusCodeName == 'initialized') ?
												 ( (isset($langs[$okey]) ? ($langs[$okey] . " " . $langs['notification-and']) : "") . (isset($langs[$pkey]) ? (" ".$langs[$pkey]) : "" ) ) :
											( (isset($langs[$pkey]) ? ($langs[$pkey] . " " . $langs['notification-and']) : "") . (isset($langs[$okey]) ? (" ".$langs[$okey]) : "" ) );
							$subjectString = $langs['notification-client-neworder-subject'];
						} else {
							$key = 'notification-status-' . $codeName;
							$statusString = isset($langs[$key]) ? $langs[$key] : "_";
						}
						break;
					}
					case 'payment_status_id': {
						if ($regedit->getVal('//modules/emarket/no-payment-status-notification')) return;
						$key = 'notification-status-payment-' . $codeName;
						$statusString = isset($langs[$key]) ? $langs[$key] : "_";
						break;
					}
					case 'delivery_status_id': {
						if ($regedit->getVal('//modules/emarket/no-delivery-status-notification')) return;
						$key = 'notification-status-delivery-' . $codeName;
						$statusString = isset($langs[$key]) ? $langs[$key] : "_";
						break;
					}
				}
				$collection = umiObjectsCollection::getInstance();
				$paymentObject = $collection->getObject($order->payment_id);
				if($paymentObject) {
					$paymentType   = $collection->getObject($paymentObject->payment_type_id);
					$paymentClassName = $paymentType->class_name;
				} else {
					$paymentClassName = null;
				}
				$templateName  = ($paymentClassName == "receipt") ? "status_notification_receipt" : "status_notification";
				list($template) = def_module::loadTemplatesForMail("emarket/mail/default", $templateName);

				$param = array();
				$param["order_id"]        = $order->id;
				$param["order_name"]      = $order->name;
				$param["order_number"]    = $order->number;
				$param["status"]          = $statusString;
				$param["personal_params"] = $this->getPersonalLinkParams($customer->getId());

				$domain = cmsController::getInstance()->getCurrentDomain();

				$currentHost = getServer('HTTP_HOST');
				$param["domain"] = $domain->getCurrentHostName();

				if($paymentClassName == "receipt") {
					$param["receipt_signature"] = sha1("{$customer->getId()}:{$customer->email}:{$order->order_date}");
				}
				$content = def_module::parseTemplateForMail($template, $param);
				$regedit  = regedit::getInstance();
				$letter   = new umiMail();
				$letter->addRecipient("rodogor@gmail.com", $name);

				$cmsController = cmsController::getInstance();
				$domains = domainsCollection::getInstance();
				$domainId = $cmsController->getCurrentDomain()->getId();
				$defaultDomainId = $domains->getDefaultDomain()->getId();

				if ($regedit->getVal("//modules/emarket/from-email/{$domainId}")) {
					$fromMail = $regedit->getVal("//modules/emarket/from-email/{$domainId}");
					$fromName = $regedit->getVal("//modules/emarket/from-name/{$domainId}");

				} elseif ($regedit->getVal("//modules/emarket/from-email/{$defaultDomainId}")) {
					$fromMail = $regedit->getVal("//modules/emarket/from-email/{$defaultDomainId}");
					$fromName = $regedit->getVal("//modules/emarket/from-name/{$defaultDomainId}");

				} else {
					$fromMail = $regedit->getVal("//modules/emarket/from-email");
					$fromName = $regedit->getVal("//modules/emarket/from-name");
				}

				$letter->setFrom($fromMail, $fromName);
				$letter->setSubject($subjectString);
				$letter->setContent($content);
				$letter->commit();
				//var_dump($letter->content); exit;
				$letter->send();
echo $content; exit;


			}
		}

		public function testOrder(){
			$order = order::get(137621);
			$order->order();
			var_dump(11); exit;
		}

		public function fixOrder(iUmiEventPoint $oEventPoint){
            if ($oEventPoint->getMode() === "after") {
                $newStatusId = $oEventPoint->getParam("new-status-id");
                $status = umiObjectsCollection::getInstance()->getObject($newStatusId);
				if($status->codename == 'waiting'){
					$order = $oEventPoint->getRef("order");
					if($order->domain_id  == '16karat.ru'){
						$order->setValue('need_export',NULL);
						$order->commit();
					}

				}
			}
		}

		public function fixDiscount(iUmiEventPoint $oEventPoint){
			//if ($oEventPoint->getMode() === "after") {
				$ids = array(1623,1659);
				$object = $oEventPoint->getParam("object");
				var_dump($object->id); exit;

				$umiObjectsCollection = umiObjectsCollection::getInstance();
				$discount1 = $umiObjectsCollection->getObject(1623);
				$discount2 = $umiObjectsCollection->getObject(1659);
				$discount3 = $umiObjectsCollection->getObject(1663);

				$discount1_val = (int) $discount1->proc;
				$discount2_val = (int) $discount2->proc;
				$discount3->proc = $discount1_val + $discount2_val;
			//}

			//var_dump(111); exit;
		}

		public function resetPromo(){
            $order = emarket::getBasketOrder();
            $order->bonus_code = null;
            $order->commit();
            $order->refresh();
            $result['status'] = 'ok';
            $buffer = outputBuffer::current();
            $buffer->charset('utf-8');
            $buffer->contentType('application/jsonp');
            $buffer->clear();
            $buffer->push(json_encode($result));
            $buffer->end();

		}

		public function setPromo(){
			$promo = getRequest("promo");
			$objects = new selector('objects');
			$objects->types('object-type')->id(155);
			$objects->where("name")->equals($promo);
            $objects->where("used")->isnull(true);
			$result = array();
			if($objects->length){
				$result['status'] = 'ok';
				$promo = $objects->first;

				$order = emarket::getBasketOrder();

				$order->bonus_code = $promo->id;
				//var_dump($order->promokod); exit;
				$order->refresh();
				//var_dump($order); exit;

			}else{
				$result['status'] = 'error';
			}
			$buffer = outputBuffer::current();
			$buffer->charset('utf-8');
			$buffer->contentType('application/jsonp');
			$buffer->clear();
			$buffer->push(json_encode($result));
			$buffer->end();

		}

		/**
		 * Получить стоимость товара $elementId (со скидкой и без одновременно)
		 *
		 * @param null|int $elementId
		 * @param string $template
		 * @param bool $showAllCurrency
		 *
		 * @return mixed
		 * @throws publicException если данный элемент не найден
		 */
		public function priceCustom($elementId = null, $template = 'default', $showAllCurrency = false) {
			if(!$elementId) return null;
			$hierarchy = umiHierarchy::getInstance();
			//$elementId = $this->analyzeRequiredPath($elementId);

			if($elementId == false) {
				throw new publicException("Wrong element id given");
			}

			$element = $hierarchy->getElement($elementId);
			if($element instanceof iUmiHierarchyElement == false) {
				throw new publicException("Wrong element id given");
			}



			list($tpl_block) = def_module::loadTemplates("emarket/".$template, 'price_block');

			$originalPrice = $element->price;
			//Discounts
			$result = array(
				'attribute:element-id' => $elementId
			);

			$discount = itemDiscount::search($element);
			if($discount instanceof discount) {
				$result['discount'] = array(
					'attribute:id'		=> $discount->id,
					'attribute:name'	=> $discount->getName(),
					'description'		=> $discount->getValue('description')
				);

				$result['void:discount_id'] = $discount->id;
			}


			//Currency
			$price = self::formatPrice($element->price, $discount,false);
			$cmsController = cmsController::getInstance();
			$emarket = $cmsController->getModule("emarket");
			if ($currencyPrice = self::formatCurrencyPriceCustom($price)) {
				$result['price'] = $currencyPrice;
			} else {
				$result['price'] = $price;
			}


			$result['price'] = $emarket->parsePriceTpl($template, $result['price']);
			$result['void:price-original'] = getArrayKey($result['price'], 'original');
			$result['void:price-actual'] = getArrayKey($result['price'], 'actual');


			if($showAllCurrency) {
				$result['currencies'] = self::formatCurrencyPriceCustoms($price);
				$result['currency-prices'] = $emarket->parseCurrencyPricesTpl($template, $price);
			}


			return def_module::parseTemplate($tpl_block, $result);
		}


		/**
			*
		*/
		private static function formatPrice($originalPrice, itemDiscount $discount = null,$format = true) {
			$actualPrice = ($discount instanceof itemDiscount) ? $discount->recalcPrice($originalPrice) : $originalPrice;
			if($originalPrice == $actualPrice) {
				$originalPrice = null;
			}


			$result = array();
			if($format){
				$result = array(
					'original'	=> $originalPrice ? number_format($originalPrice, 0, '', ' ') : false,
					'actual'	=> number_format($actualPrice, 0, '', ' ')
				);
			}else{
				$result = array(
					'original'	=> $originalPrice,
					'actual'	=> $actualPrice
				);
			}

			return $result;
		}





		public function addWish($pageId){
			$customer = customer::get();
			$wishlist = $customer->wishlist;
			$wishlist_id = array();
			foreach($wishlist as $page){
				$wishlist_id[] = $page->id;
			}

			$wishlist_id[] = $pageId;
			array_unique($wishlist_id);
			$customer->wishlist = $wishlist_id;

			return 'ok';

		}


		public function getWishList(){
			$customer = customer::get();
			$wishlist = $customer->wishlist;
			$wishlist_id = array();
			foreach($wishlist as $page){
				$wishlist_id[] = $page->id;
			}
			$emarket = cmsController::getInstance()->getModule("emarket");
			$umiHierarchy = umiHierarchy::getInstance();
			$catalog = cmsController::getInstance()->getModule("catalog");
			$content = cmsController::getInstance()->getModule("content");
			$umiObjectsCollection = umiObjectsCollection::getInstance();
			$result = array();

			foreach($wishlist_id as $pageId){

				$page = $umiHierarchy->getElement($pageId);
				$result[] = $catalog->makeProductLine($page,$umiObjectsCollection,$umiHierarchy,"wishlist");;

			}
			$total = sizeof($result);
			$result = def_module::parseTemplate("",array(
							     "subnodes:items" => $result,
							     "total" => $total,
							     'word' => $total." ".self::getNumEnding($total,array('товар', 'товара', 'товаров'))
							     ));
			return $result;

		}

		public function getNumEnding($number, $endingArray){
			$number = $number % 100;
			if ($number>=11 && $number<=19) {
			    $ending=$endingArray[2];
			}
			else {
			    $i = $number % 10;
			    switch ($i)
			    {
				case (1): $ending = $endingArray[0]; break;
				case (2):
				case (3):
				case (4): $ending = $endingArray[1]; break;
				default: $ending=$endingArray[2];
			    }
			}
			return $ending;
		}

		public function delWish($pageId){
			$customer = customer::get();
			$wishlist = $customer->wishlist;
			$wishlist_id = array();
			foreach($wishlist as $page){
				if($page->id !== (int)$pageId){
					$wishlist_id[] = $page->id;
				}
			}

			$customer->wishlist = $wishlist_id;
			return 'ok';
		}

		/**
		 * Вывести список покупок (содержимое корзины)
		 *
		 * @param string $template
		 *
		 * @return mixed
		 */
		public function shopcart($template = 'default') {
			$customer_id = (int) getCookie('customer-id');

			if (!permissionsCollection::getInstance()->isAuth() && !$customer_id){
				list($tpl_block_empty) = def_module::loadTemplates("emarket/".$template, 'order_block_empty');
				$result = array(
					'attribute:id' => 'dummy',
					'summary' => array('amount' => 0),
					'steps' => $this->getPurchaseSteps($template, null)
				);

				return def_module::parseTemplate($tpl_block_empty, $result);
			}

			$order = $this->getBasketOrder();
			$order->refresh();
			return $this->orderCustom($order->getId(), $template);
		}


		public function test(){
			$item = orderItem::get(1688);
			//var_dump($item->getItemPrice()); exit;
			$item->refresh();
			echo "test() (".$item->getItemPrice().")";
			exit;

		}

		/**
		 * Вывести информацию о заказе $orderId
		 *
		 * @param bool $orderId Номер заказа
		 * @param string $template Шаблон(для tpl)
		 *
		 * @return mixed
		 * @throws publicException если не указан номер заказа или заказ не существует
		 * @throws publicException недостаточно прав
		 */
		public function orderCustom($orderId = false, $template = 'default') {
			if(!$template) $template = 'default';
			$permissions = permissionsCollection::getInstance();

			$cmsController = cmsController::getInstance();

			$emarket = $cmsController->getModule("emarket");

			$orderId = (int) ($orderId ? $orderId : getRequest('param0'));
			if(!$orderId) {
				throw new publicException("You should specify order id");
			}

			$order = order::get($orderId);
			if($order instanceof order == false) {
				throw new publicException("Order #{$orderId} doesn't exist");
			}

			if(!$permissions->isSv() && ($order->getName() !== 'dummy') &&
			   (customer::get()->getId() != $order->customer_id) &&
			   !$permissions->isAllowedMethod($permissions->getUserId(), "emarket", "control")) {
				throw new publicException(getLabel('error-require-more-permissions'));
			}

			list($tpl_block, $tpl_block_empty) = def_module::loadTemplates("emarket/".$template,
				'order_block', 'order_block_empty');

			$discount = $order->getDiscount();

			$totalAmount = $order->getTotalAmount();
			$originalPrice = $order->getOriginalPrice();
			$actualPrice = $order->getActualPrice();
			$deliveryPrice = $order->getDeliveryPrice();
			$bonusDiscount = $order->getBonusDiscount();

			if($originalPrice == $actualPrice) {
				$originalPrice = null;
			}

			$discountAmount = ($originalPrice) ? $originalPrice + $deliveryPrice - $actualPrice - $bonusDiscount : 0;
			$items = $order->getItems();
			$umiObjectsCollection = umiObjectsCollection::getInstance();
			$payment = $umiObjectsCollection->getObject($order->payment_id);
			$payment_status = $umiObjectsCollection->getObject($order->payment_status_id);


			$date = $order->order_date;
			if($date){
				$date = $date->getFormattedDate("d.m.Y");
			}
			$promo = $order->bonus_code;
			//var_dump($promo); exit;
			if($promo){
				$promo = umiObjectsCollection::getInstance()->getObject($promo);
				$promo = $promo->name;
			}

			$result = array(
				'attribute:id'	=> ($orderId),
				'xlink:href'	=> ('uobject://' . $orderId),
				'customer'		=> ($order->getName() == 'dummy') ? null : $emarket->renderOrderCustomer($order, $template),
				'subnodes:items'=> ($order->getName() == 'dummy') ? null : $this->renderOrderItemsCustom($order, $template),
				'delivery'		=> $emarket->renderOrderDeliveryCustom($order, $template),
				'payment' => array(
						      "method" => $payment,
						      "status" => $payment_status,
						      ),
				'summary'		=> array(
					'amount'		=> $totalAmount,
					'word' => "(".$totalAmount." ".self::getNumEnding($totalAmount,array('товар', 'товара', 'товаров')).")",
					'total_amount' => sizeof($items),
					'price'			=> self::formatCurrencyPriceCustom(array(
						'original'		=> $originalPrice,
						'delivery'		=> $deliveryPrice,
						'actual'		=> $actualPrice,
						'discount'		=> $discountAmount,
						'bonus'			=> $bonusDiscount
					)),
					'price-actual' => $actualPrice,
					'promo' => $promo,

				),
				'data_create' => $date,

				'steps' => $emarket->getPurchaseSteps($template, null)
			);


			if ($order->number) {
				$result['number'] = $order->number;
				$result['status'] = selector::get('object')->id($order->status_id);
			}

			if(sizeof($result['subnodes:items']) == 0) {
				$tpl_block = $tpl_block_empty;
			}

			$result['void:total-price'] = $emarket->parsePriceTpl($template, $result['summary']['price']);
			$result['void:delivery-price'] = $emarket->parsePriceTpl($template, self::formatCurrencyPriceCustom(array('actual' => $deliveryPrice)));
			$result['void:bonus'] = $emarket->parsePriceTpl($template, self::formatCurrencyPriceCustom(array('actual' => $bonusDiscount)));
			$result['void:total-amount'] = $totalAmount;

			$result['void:discount_id'] = false;
			if($discount instanceof discount) {
				$result['discount'] = array(
					'attribute:id'		=> $discount->id,
					'attribute:name'	=> $discount->getName(),
					'description'		=> $discount->getValue('description')
				);
				$result['void:discount_id'] = $discount->id;
			}
			return def_module::parseTemplate($tpl_block, $result, false, $order->id);
		}


		public function renderOrderDeliveryCustom(order $order, $template = 'default') {
			$objectsCollection = umiObjectsCollection::getInstance();

			list($tpl, $tplMethod, $tplAddress, $tplPrice) = emarket::loadTemplates(
				'emarket/' . $template,
				'order_delivery',
				'delivery_method',
				'delivery_address',
				'delivery_price'
			);

			$result = array();
			$method = $objectsCollection->getObject($order->delivery_id);

			if ($method instanceof iUmiObject == false) {
				return emarket::parseTemplate($tpl, $result);
			}

			$deliveryMethod = array(
				'attribute:id' => $method->getId(),
				'attribute:name' => $method->getName(),
				'xlink:href' => ('uobject://' . $method->getId()),
			);

			$result['method'] = emarket::parseTemplate($tplMethod, $deliveryMethod);

			/**
			 * @var umiObject $address
			 */
			$address = $objectsCollection->getObject($order->getValue('delivery_address'));

			if ($address instanceof iUmiObject) {
				$country = $objectsCollection->getObject($address->getValue('country'));
				$countryName = $country instanceof iUmiObject ? $country->getName() : '';
				$deliveryAddress = array(
					'attribute:id' => $address->getId(),
					'attribute:name' => $address->getName(),
					'xlink:href' => ('uobject://' . $address->getId()),
					'country' => $countryName,
					'index' => $address->getValue('index'),
					'region' => $address->getValue('region'),
					'city' => $address->getValue('city'),
					'street' => $address->getValue('street'),
					'korpus' => $address->getValue('korpus'),
					'stroenie' => $address->getValue('stroenie'),
					'house' => $address->getValue('house'),
					'flat' => $address->getValue('flat'),
					'comment' => $address->getValue('order_comments'),
				);
				$result['address'] = emarket::parseTemplate($tplAddress, $deliveryAddress);
			}

			$result['price'] = emarket::parseTemplate($tplPrice, $this->formatCurrencyPriceCustom(array(
				'delivery' => $order->getValue('delivery_price')
			)));

			return emarket::parseTemplate($tpl, $result);
		}


		/**
			* Отрисовать наименование в заказе
			* @param order $order
			* @return Array
		*/
		public function renderOrderItemsCustom(order $order, $template = 'default') {
			$items_arr = array();
			$umiHierarchy = umiHierarchy::getInstance();
			$objects = umiObjectsCollection::getInstance();
			$cmsController = cmsController::getInstance();
			$umiFieldsCollection = umiFieldsCollection::getInstance();
			$emarket = $cmsController->getModule("emarket");
			$content = $cmsController->getModule("content");
			$catalog = $cmsController->getModule("catalog");
			list($tpl_item, $tpl_options_block, $tpl_options_block_empty, $tpl_options_item) = def_module::loadTemplates("emarket/".$template,
				'order_item', 'options_block', 'options_block_empty', 'options_item');

			$orderItems = $order->getItems();
            $sample_amount = 0;
            $max_sample = 10;
			foreach($orderItems as $orderItem) {
				/**
				 * @var orderItem $orderItem
				 */

				$orderItemId = $orderItem->getId();

				$item_arr = array(
					'attribute:id'		=> $orderItemId,
					'attribute:name'	=> htmlspecialchars($orderItem->getName()),
					'@sample' => $orderItem->sample,
					'xlink:href'		=> ('uobject://' . $orderItemId),
					'amount'			=> $orderItem->getAmount(),
					'options'			=> null
				);

				$itemDiscount = $orderItem->getDiscount();

				$plainPriceOriginal = $orderItem->getItemPrice();




				$plainPriceActual = $itemDiscount ? $itemDiscount->recalcPrice($plainPriceOriginal) : $plainPriceOriginal;

				$totalPriceOriginal = $orderItem->getTotalOriginalPrice();
				$totalPriceActual = $orderItem->getTotalActualPrice();

				if($orderItem->sample){
                    $thisAmount = $orderItem->getAmount();
                    if((($sample_amount + $thisAmount) <= $max_sample)){
                        //$originalPrice += 0;
                        $totalPriceOriginal = 0;
                        $totalPriceActual = 0;
                        $plainPriceOriginal = 0;
                        $plainPriceActual = 0;
                    }else{
                        $price_elements = $thisAmount - ($max_sample - $sample_amount);
                        $prices = 20*$price_elements;
                        $totalPriceOriginal = $prices;
                        $totalPriceActual = $prices;
                        $plainPriceOriginal = 20;
                        $plainPriceActual = 20;
                    }
                    $sample_amount+=$thisAmount;
				}

				if($plainPriceOriginal == $plainPriceActual) {
					$plainPriceOriginal = null;
				}

				if($totalPriceOriginal == $totalPriceActual) {
					$totalPriceOriginal = null;
				}

				$item_arr['price'] = self::formatCurrencyPriceCustom(array(
					'original'	=> $plainPriceOriginal,
					'actual'	=> $plainPriceActual
				));

				$item_arr['total-price'] = self::formatCurrencyPriceCustom(array(
					'original'	=> $totalPriceOriginal,
					'actual'	=> $totalPriceActual
				));



				$item_arr['price'] = $emarket->parsePriceTpl($template, $item_arr['price']);
				$item_arr['total-price'] = $emarket->parsePriceTpl($template, $item_arr['total-price']);

				$element = false;
				$status = order::getCodeByStatus($order->getOrderStatus());
				if (!$status || $status == 'basket') {
					$element = $orderItem->getItemElement();
				} else {
					$symlink = $orderItem->getObject()->item_link;
					if(is_array($symlink) && sizeof($symlink)) {
						list($item) = $symlink;
						$element = $item;
					} else {
						$element = null;
					}
				}
				if($element instanceof iUmiHierarchyElement) {
					$item_arr['page'] = $element;

					$item_arr['void:element_id'] = $element->id;
					$item_arr['void:link'] = $element->link;
					$item_arr['product'] = $catalog->makeProductLine($element,$objects,$umiHierarchy);;

					$sostav = $element->sostav;
					if($sostav){
						$sostav = $objects->getObject($sostav);
						if($sostav){
                            $item_arr['@sostav'] = $sostav->name;
						}


					}
                    $item_arr['@common_quantity'] = $element->common_quantity;

					//$item_arr['linked_prod'] = $catalog->getLinkedProd($element->id,1);
					//$result[] = self::makeProductLine($page,$umiObjectsCollection,$umiHierarchy,"");

				}

				$discountAmount = $totalPriceOriginal ? $totalPriceOriginal - $totalPriceActual : 0;

				$discount = $orderItem->getDiscount();
				if($discount instanceof itemDiscount) {
					$item_arr['discount'] = array(
						'attribute:id' => $discount->id,
						'attribute:name' => $discount->getName(),
						'description' => $discount->getValue('description'),
						'amount' => $discountAmount
					);
					$item_arr['void:discount_id'] = $discount->id;
				}

				$elementId = ($element instanceof iUmiHierarchyElement) ? $element->getId() : null;

				if($orderItem instanceof optionedOrderItem) {
					$options = $orderItem->getOptions(); $options_arr = array();


					foreach($options as $optionInfo) {


						$optionId = $optionInfo['option-id'];
						$price = $optionInfo['price'];
						$fieldName = $optionInfo['field-name'];
						/*
						if($orderItemId === 1417){
							var_dump($optionInfo); exit;
						}*/

						$option = $objects->getObject($optionId);
						if($option instanceof iUmiObject) {
							$fieldId = $element->getFieldId($fieldName);
							$title = "";
							if($fieldId){
								$field = $umiFieldsCollection->getField($fieldId);
								$title = $field->getTitle();
							}



							$option_arr = array(
								'attribute:id'			=> $optionId,
								'attribute:name'		=> $option->getName(),
								'attribute:price'		=> $price,
								'attribute:field-name'	=> $fieldName,
								'attribute:element_id' 	=> $elementId,
								'attribute:title' 	=> $title,

								'xlink:href'			=> ('uobject://' . $optionId)
							);

							$options_arr[] = def_module::parseTemplate($tpl_options_item, $option_arr, false, $optionId);
						}


						/*

						$optionId = $optionInfo['option-id'];
						$price = $optionInfo['price'];
						$fieldName = preg_replace('/[0-9]/', '', $optionInfo['field-name']);
						$amount = $optionInfo['amount'];
						//var_dump($optionInfo); exit;

						$all_price = $price * $amount;


						//var_dump($optionInfo); exit;
						$fieldId = $element->getFieldId($fieldName);
						//	var_dump($fieldName); exit;
						if($fieldId){
							$field = $umiFieldsCollection->getField($fieldId);
							$title = $field->getTitle();
						}



						$option = $objects->getObject($optionId);
						if($option instanceof iUmiObject) {
							$option_arr = array(
								'attribute:id'			=> $optionId,
								'attribute:name'		=> $option->getName(),
								'attribute:price'		=> $price,
								'attribute:price_format'		=> number_format(round($price, 2), 0, '.', ' '),
								'attribute:summary_price'		=> number_format(round($all_price, 2), 0, '.', ' '),
								'attribute:title'		=> $title,
								'attribute:amount'		=> $amount,
								'attribute:field-name'	=> $fieldName,
								'attribute:element_id' 	=> $elementId,
								'xlink:href'			=> ('uobject://' . $optionId)
							);

							$options_arr[] = def_module::parseTemplate($tpl_options_item, $option_arr, false, $optionId);
						}
						*/

					}

					$item_arr['options'] = def_module::parseTemplate($tpl_options_block, array(
						'nodes:option' => $options_arr,
						'void:items' => $options_arr
					));
				}

				$items_arr[] = def_module::parseTemplate($tpl_item, $item_arr);
			}
			return $items_arr;
		}


		/**
		 * Пересчитать цены в массиве $prices в валюту $currency
		 * @param array $prices
		 * @param iUmiObject $currency = null
		 * @param iUmiObject $defaultCurrency = null
		 * @return array
		 */
		public function formatCurrencyPriceCustom($prices, iUmiObject $currency = null, iUmiObject $defaultCurrency = null) {
			$cmsController = cmsController::getInstance();
			$emarket = $cmsController->getModule("emarket");
			if(is_null($defaultCurrency)) {
				$defaultCurrency = $emarket->getDefaultCurrency();
			}
			$currentCurrency = $emarket->getCurrentCurrency();


			if(is_null($currency)) {
				$currency = $currentCurrency;
			} else {
				if(($currency->getId() == $currentCurrency->id) && ($defaultCurrency == $emarket->getDefaultCurrency())) {
					return $prices;
				}
			}

			$result = array(
				'attribute:name'		=> $currency->name,
				'attribute:code'		=> $currency->codename,
				'attribute:rate'		=> $currency->rate,
				'attribute:nominal'		=> $currency->nominal,
				'void:currency_name'	=> $currency->name
			);

			if($currency->prefix) $result['attribute:prefix'] = $currency->prefix; else $result['void:prefix'] = false;
			if($currency->suffix) $result['attribute:suffix'] = $currency->suffix; else $result['void:suffix'] = false;

			foreach($prices as $key => $price) {
				if($price == null) {
					$result[$key] = null;
					continue;
				}
				//var_dump((float)$price); exit;

				$price = $price * $defaultCurrency->nominal * $defaultCurrency->rate;
				$price = $price  / $currency->rate / $currency->nominal;

				$result[$key] = number_format(round($price, 2), 0, '', ' ');
			}

			return $result;
		}


		/**
		 * TODO: Write documentation
		 *
		 * All these cases renders full basket order:
		 * /udata/emarket/basket/ - do nothing
		 * /udata/emarket/basket/add/element/9 - add element 9 into the basket
		 * /udata/emarket/basket/add/element/9?amount=5 - add element 9 into the basket + amount
		 * /udata/emarket/basket/add/element/9?option[option_name_1]=1&option=2&option[option_name_2]=3 - add element 9 using options
		 * /udata/emarket/basket/modify/element/9?option[option_name_1]=1&option=2&option[option_name_2]=3 - add element 9 using options
		 * /udata/emarket/basket/modify/item/9?option[option_name_1]=1&option=2&option[option_name_2]=3 - add element 9 using options
		 * /udata/emarket/basket/remove/element/9 - remove element 9 from the basket
		 * /udata/emarket/basket/remove/item/111 - remove orderItem 111 from the basket
		 * /udata/emarket/basket/remove_all/ - remove all orderItems from basket
		 */
		public function basketCustom($mode = false, $itemType = false, $itemId = false) {
			$mode = $mode ? $mode : getRequest('param0');
			$cmsController = cmsController::getInstance();
			$emarket = $cmsController->getModule("emarket");
			$order = $emarket->getBasketOrder(!in_array($mode, array('put', 'remove')));

			$itemType = $itemType ? $itemType : getRequest('param1');
			$itemId = (int) ($itemId ? $itemId : getRequest('param2'));
			$amount = (float) getRequest('amount');
			$options = getRequest('options');

			if($mode == 'put') {
				$newElement = false;
				if ($itemType == 'element') {
					$orderItem = $this->getBasketItemCustom($itemId, false);
					if (!$orderItem) {
						$orderItem = $this->getBasketItemCustom($itemId);
						$newElement = true;
					}
				} elseif($itemType == 'sample'){
                    $orderItem = $this->getBasketItemCustom($itemId, false, true);
                    if (!$orderItem) {
                        $orderItem = $this->getBasketItemCustom($itemId,true,true);
                        $newElement = true;
                    }


				} else {
					$orderItem = $order->getItem($itemId);
				}


				if (!$orderItem) {
					throw new publicException("Order item is not defined");
				}

				if(is_array($options)) {
					if($itemType != 'element') {
						throw new publicException("Put basket method required element id of optionedOrderItem");
					}

					// Get all orderItems
					$orderItems = $order->getItems();

					foreach($orderItems as $tOrderItem) {
						if (!$tOrderItem instanceOf optionedOrderItem) {
							$itemOptions = null;
							$tOrderItem = null;
							continue;
						}

						$itemOptions = $tOrderItem->getOptions();

						if(sizeof($itemOptions) != sizeof($options)) {
							$itemOptions = null;
							$tOrderItem = null;
							continue;
						}

						if($tOrderItem->getItemElement()->id != $orderItem->getItemElement()->id) {
							$itemOptions = null;
							$tOrderItem = null;
							continue;
						}

						// Compare each tOrderItem with options list
						foreach($options as $optionName => $optionId) {
							$itemOption = getArrayKey($itemOptions, $optionName);

							if(getArrayKey($itemOption, 'option-id') != $optionId) {
								$tOrderItem = null;
								continue 2;		// If does not match, create new item using options specified
							}
						}

						break;	// If matches, stop loop and continue to amount change
					}

					if(!isset($tOrderItem) || is_null($tOrderItem)) {
						$tOrderItem = orderItem::create($itemId);
						$order->appendItem($tOrderItem);
						if ($newElement) {
							$orderItem->remove();
						}
					}


					if($tOrderItem instanceof optionedOrderItem) {
						foreach($options as $optionName => $optionId) {
							if($optionId) {
								$tOrderItem->appendOption($optionName, $optionId);
							} else {
								$tOrderItem->removeOption($optionName);
							}
						}
					}
					//var_dump($options); exit;
					/*
					if($tOrderItem instanceof optionedOrderItem) {
						//foreach($options as $optionName => $optionId) {

						foreach($options as $key => $option) {

							$optionName = $option['name'];
							$optionId = $option['id'];
							$optionAmount = 1 ;
							if(array_key_exists('amount',$option) !== false){
								$optionAmount = $option['amount'];
							}


							if($optionId) {
								$tOrderItem->appendOption($optionName."".$optionId, $optionId,false,$optionAmount);
								//$tOrderItem->appendOption($optionName, $optionId,false,$optionAmount);

							} else {
								$tOrderItem->removeOption($optionName);
							}
						}

					}
					*/

					if($tOrderItem) {
						$orderItem = $tOrderItem;
					}
				}

				$amount = $amount ? $amount : ($orderItem->getAmount() + 1);
				$orderItem->setAmount($amount ? $amount : 1);
				$orderItem->refresh();

				if(($itemType == 'element') || ($itemType == 'sample')) {
					$order->appendItem($orderItem);

				}
			}

			if($mode == 'remove') {
				$orderItem = ($itemType == 'element') ? $emarket->getBasketItem($itemId, false) : orderItem::get($itemId);
				if($orderItem instanceof orderItem) {
					$order->removeItem($orderItem);
                }
			}

			if ($mode == 'remove_all') {
				foreach ($order->getItems() as $orderItem) {
					$order->removeItem($orderItem);
				}
			}

			$order->refresh();

			$referer = getServer('HTTP_REFERER');
			$noRedirect = getRequest('no-redirect');
			/*
			if($redirectUri = getRequest('redirect-uri')) {
				$this->redirect($redirectUri);
			} else if (!defined('VIA_HTTP_SCHEME') && !$noRedirect && $referer) {
				$current = $_SERVER['REQUEST_URI'];
				if(substr($referer, -strlen($current)) == $current) {
					if($itemType == 'element') {
						$referer = umiHierarchy::getInstance()->getPathById($itemId);
					} else {
						$referer = "/";
					}
				}
				$this->redirect($referer);
			}*/

			return $this->orderCustom($order->getId());
		}



        /**
         * Возвращает товарное наименование из текущей корзины,
         * соответстоющее товару (объекту каталога)
         * @param int $elementId идентификатор товара (объекта каталога)
         * @param bool $autoCreate автоматически создать товарное наименование,
         * если оно не было задано
         * @return null|orderItem
         */
        private function getBasketItemCustom($elementId, $autoCreate = true, $sample = false) {
        	$emarket = cmsController::getInstance()->getModule('emarket');
            $order = $emarket->getBasketOrder();

            $orderItems = $order->getItems();

            /**
             * @var orderItem $orderItem
             */
            foreach ($orderItems as $orderItem) {
            	if($sample){
                    $element = $orderItem->getItemElement();
                    if($orderItem->sample){
                        if ($element instanceof umiHierarchyElement) {
                            if ($element->getId() == $elementId) {
                                return $orderItem;
                            }
                        }
					}
            	}else{
                    $element = $orderItem->getItemElement();
                    if ($element instanceof umiHierarchyElement) {
                        if ($element->getId() == $elementId) {
                            return $orderItem;
                        }
                    }
				}

            }

            return $autoCreate ? (orderItem::create($elementId,false,$sample)) : null;
        }

		public function getCityList(){
			$cacheName = 'getCityList';
			$cache = cacheFrontend::getInstance();
			$result = $cache->loadData($cacheName);
			if($result){
				return $result;
			}
			$objects = new selector('objects');
			$objects->types('object-type')->id(11);
			$result = array();
			foreach($objects as $object){
				$result[] = array(
						  '@id' => $object->id,
						  '@name' => $object->name
						  );

			}
			$result = def_module::parseTemplate("",array("subnodes:items" => $result));
			$cache->saveData($cacheName,$result,36000);

			return $result;
		}


		public function getDeliveryPrice(){
			$cmsController = cmsController::getInstance();
			$emarket = $cmsController->getModule('emarket');
			$order = $emarket->getBasketOrder(false);
			$id = getRequest("id");
			$id = $id ? $id : 1930;
			$umiObjectsCollection = umiObjectsCollection::getInstance();

			$delivery = delivery::get($id);
			return $delivery->getDeliveryPriceNew($order);
			//return (float) $delivery->getDeliveryPriceWithoutOrder($order);

		}



		public function saveInfoCustom() {

			//сохранение регистрационных данных
			$cmsController = cmsController::getInstance();
			$permissionsCollection = permissionsCollection::getInstance();
			$emarket = $cmsController->getModule('emarket');
			$order = $emarket->getBasketOrder(false);
			$data = $cmsController->getModule('data');
			$guestId = $permissionsCollection->getGuestId();
			$guest = false;
			$userId = $permissionsCollection->getUserId();

			if($userId == $guestId){
				$guest = true;
			}


			$data->saveEditedObject(customer::get()->getId(), false, true);

			//сохранение адреса доставки
			$addressId = getRequest('delivery-address');
            $addressId = $addressId ? $addressId : "new";
			$customer   = customer::get();
			$dataModule = $cmsController->getModule("data");
			$address = false;
			$deliveryId = getRequest('delivery-id');
			if($addressId == 'new') {
				$collection = umiObjectsCollection::getInstance();
				$types      = umiObjectTypesCollection::getInstance();
				$typeId     = $types->getTypeIdByHierarchyTypeName("emarket", "delivery_address");

				$addressId  = $collection->addObject("Address for customer #" . $customer->getId(), $typeId);

				if($dataModule) {
					if(!$dataModule->saveEditedObject($addressId, true, true))
						$dataModule->saveEditedObjectWithIgnorePermissions($addressId, true, true); // начиная с версии 2.9.5
				}
				$customer->delivery_addresses = array_merge( $customer->delivery_addresses, array($addressId) );
				$address = $collection->getObject($addressId);
			}

			$order->delivery_address = $addressId;




			//сохранение способа доставки

			$ddOrder = false;
			if($deliveryId){

				$delivery = delivery::get($deliveryId);
				$deliveryPrice = (float) $delivery->getDeliveryPrice($order);
				$order->setValue('delivery_id', $deliveryId);
				$order->setValue('delivery_price', $deliveryPrice);

			}

			//сохранение способа оплаты и редирект на итоговую страницу
			$order->setValue('payment_id', getRequest('payment-id'));

			$order->refresh();

			$bonus_code = $order->bonus_code;
			if($bonus_code){
                $bonus_code = umiObjectsCollection::getInstance()->getObject($bonus_code);
                if($bonus_code){
                	if($bonus_code->one_off){
                		$bonus_code->used = 1;
                        $bonus_code->commit();
					}
				}
			}

			$paymentId = getRequest('payment-id');

			//if(!$paymentId) {
			//	$_REQUEST['payment-id'] = 1518;
			//	//$this->errorNewMessage(getLabel('error-emarket-choose-payment'));
			//	//$this->errorPanic();
			//	$paymentId = getRequest('payment-id');
			//}
			$payment = payment::get($paymentId, $order);

			if($payment instanceof payment) {
				$paymentName = $payment->getCodeName();
				$url = "{$this->pre_lang}/" . cmsController::getInstance()->getUrlPrefix()."emarket/purchase/payment/{$paymentName}/";
			} else {
				$url = "{$this->pre_lang}/" . cmsController::getInstance()->getUrlPrefix()."emarket/cart/";
			}

			$data = getRequest('data');
			$data = $data['new'];

			//$order->comments = $data['comment'];
			//var_dump($ddOrder); exit;

			$this->redirect($url);
		}

	};
?>
