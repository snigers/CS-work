<?php

	namespace UmiCms\Manifest\Demomarket;

	/**
	 * Команда изменения идентификатора формы обратной связи,
	 * с которой связана страница "Контакты".
	 */
	class ChangeWebformIdAction extends \Action {

		/** @inheritdoc */
		public function execute() {
			$umiHierarchy = \umiHierarchy::getInstance();

			$form = \umiObjectTypesCollection::getInstance()
				->getTypeByGUID('contacts-form');
			$pageId = $umiHierarchy->getIdByPath('contacts');
			$page = $umiHierarchy->getElement($pageId);
			$page->setValue('form_id', $form->getId());
			$page->commit();
		}

		/**
		 * @inheritdoc
		 * @return $this
		 */
		public function rollback() {
			return $this;
		}
	}
