<?php
	$permissions = array(
		'purchasing' => array(
			'getNumEnding',
			'priceCustom',
			'addWish',
			'getWishList',
			'delWish',
			'shopcart',
			'orderCustom',
			'renderOrderItemsCustom',
			'formatCurrencyPriceCustom',
			'basketCustom',
			'getCityList',
			'saveInfoCustom',
			'setPromo',
            'resetPromo',
			'fixDiscount',
			'getDate',
			'clear_compare',
			'addToCompareNew',
			'removeFromCompareCustom',
			'getCompareElementsCustom',
			'clear_compare',
			'getCompareElementsCategory',
			'getCategoryCompare',
			'compareCustom',
			'getDeliveryPrice',
			'getPurchaseLink', 'countInStores', 'getInvoiceLink', 'getInvoice',
			'getAllProductsCompare',
			'getProductsForDD',
			'getCustomerId',
			'renderOrderDelivery',
			'setOrderPayment',
			'qxplus_orders',
			'fixOrder'
		),

		'personal' => array(
			'ordersList', 'customerDeliveryList'
		),

		'compare' => array(
			'getCompareList', 'getCompareLink',
			'addToCompare', 'removeFromCompare', 'resetCompareList',
			'jsonAddToCompareList', 'jsonRemoveFromCompare', 'jsonResetCompareList',
			'getPurchaseLink', 'countInStores'
		),

		'control' => array(
			'orders', 'ordersList', 'del', 'order_edit', 'order_printable',  'order.edit',
			'currency', 'currency_add', 'currency_edit', 'currency.edit',
			'delivery', 'delivery_add', 'delivery_edit', 'delivery_address_edit', 'delivery.edit',
			'discounts', 'discount_add', 'discount_edit', 'getModificators', 'getRules', 'discount', 'discount.edit',
			'payment', 'payment_add', 'payment_edit', 'payment.edit',
			'stores', 'store_add', 'store_edit', 'store', 'store.edit',
			'stats', 'realpayments', 'setDateRange', 'getDateRange', 'getMostPopularProduct', 'statRun'
		),

		'order_editing' => array(
			'editOrderAsUser', 'actAsUser'
		),
		'mobile_application_get_data' => array (
			'getOrderStatuses', 'getOrdersByStatus', 'getOrder', 'setOrder', 'addToken', 'removeToken'
		)
	);
?>
