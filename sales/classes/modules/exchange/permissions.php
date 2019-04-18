<?php
	/**
	 * Группы прав на функционал модуля
	 */
	$permissions = [
		/**
		 * Права на интеграция с 1С
		 */
		'auto' => [
			'getcurrencycodebyalias',
			'export1c'
		],
		/**
		 * Права на ручной импорт и экспорт
		 */
		'exchange' => [
			'import',
			'export',
			'add',
			'edit',
			'del',
			'import_do',
			'prepareelementstoexport',
            'fixRelaton'
		],
		/**
		 * Права на доступ к экспорту данных по http
		 */
		'get_export' => [
			'get_export'
		]
	];
?>