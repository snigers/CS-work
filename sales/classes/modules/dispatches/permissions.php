<?php
	/**
	 * Группы прав на функционал модуля
	 */
	$permissions = [
		/**
		 * Права на администрирование модуля
		 */
		'dispatches_list' => [
			'add',
			'edit',
			'activity',
			'del',
			'messages',
			'subscribers',
			'releasees',
			'fill_release',
			'release_send',
			'lists',
			'add_message',
			'releases'
		],
		/**
		 * Права на подписку и отписку
		 */
		'subscribe' => [
			'subscribe_doCustom',
			'subscribe',
			'subscribe_do',
			'unsubscribe',
			'parseDispatches'
		]
	];
?>
