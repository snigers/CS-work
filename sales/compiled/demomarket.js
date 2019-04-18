/**
 * Название этого файла должно быть первым в лексикографическом порядке
 * среди всех файлов sites/demomarket/src/files/templates/demomarket/js/*.js
 *
 * Это нужно для того, чтобы gulp добавил этот файл первым в compiled/demomarket.js
 */

var site = {};

/**
 * Основной модуль шаблона
 * @type {Object}
 */
site.common = {

	/** Стилизует результаты голосования модуля "Опросы" */
	stylizeVoteResults: function() {
		var $interviewRange = $('.result_range');

		$.each($interviewRange, function(key, value) {
			var rangeWidth = $(value).next().text();
			rangeWidth = parseInt(rangeWidth, 10);
			$(this).css('width', rangeWidth + '%');
		});
	},

	/** Инициализирует стили и поведение на всех страницах сайта */
	init: function() {
		initInputmask();
		initFancybox();
		initSlick();
		initVerge();
		initMenu($('#headerMobileMenu'), $('#topMenuToggle'), $('#closeMenuToggle'), 'active');
		initCaptcha();
		initToolTips();
		initFaqQuestions();
		stylizeSelects();
		site.common.stylizeVoteResults();

		registerPrintPageCallback();
		registerFilterFieldArrowCallback();
		registerMobileFilterBlockCallback();
		registerProductPreviewCallback();
		registerProductSortingCallback();
		registerSubmitPaymentCallback();
		registerCategoryMenuCallback();
		registerSubscribeFormCallback();

		/** Добавляет маску формата телефонного номера в поля с номером телефона */
		function initInputmask() {
			$('input[type="tel"]').inputmask("+9-999-999-99-99");
		}

		/** Стилизует селекты с помощью jQuery-UI */
		function stylizeSelects() {
			$('select').selectmenu();
		}

		/** Регистрирует обработчик нажатия на кнопку "Распечатать" на странице товара. */
		function registerPrintPageCallback() {
			$('a.for_print').on('click', function(e) {
				e.preventDefault();
				window.print();
			});
		}

		/** Инициализирует slick-карусели */
		function initSlick() {
			$('.slider-for').slick({
				slidesToShow: 1,
				slidesToScroll: 1,
				arrows: false,
				fade: true,
				asNavFor: '.slider-nav'
			});

			$('.slider-nav').slick({
				slidesToShow: 3,
				slidesToScroll: 1,
				asNavFor: '.slider-for',
				autoplay: true,
				autoplaySpeed: 3000,
				centerMode: true,
				focusOnSelect: true,
				responsive: [{
					breakpoint: 1500,
					settings: {
						arrows: false
					}
				}]
			});

			$('.subCarousel').slick({
				slidesToShow: 5,
				slidesToScroll: 1,
				responsive: [
					{
						breakpoint: 1500,
						settings: {
							slidesToShow: 3,
							slidesToScroll: 1,
						}
					},
					{
						breakpoint: 991,
						settings: {
							slidesToShow: 2,
							slidesToScroll: 1
						}
					},
					{
						breakpoint: 570,
						settings: {
							slidesToShow: 1,
							slidesToScroll: 1
						}
					}
				]
			});
		}

		function initMenu(menu, openToggle, closeToggle, newClass) {
			function initCollapse() {
				if (menu.hasClass(newClass)) {
					menu.removeClass(newClass);
				} else {
					menu.addClass(newClass);
				}
			}

			openToggle.click(function(event) {
				initCollapse();
				event.preventDefault();
			});

			openToggle.on('tap', function(event) {
				initCollapse();
				event.preventDefault();
			});

			if (closeToggle != false) {
				closeToggle.click(function(event) {
					initCollapse();
					event.preventDefault(event);
				});

				closeToggle.on('tap', function(event) {
					initCollapse();
					event.preventDefault();
				});
			}
		}

		/**
		 * Регистрирует обработчик нажатия на стрелочку в заголовке поля умных фильтров.
		 * Обработчик скрывает или показывает это поле.
		 */
		function registerFilterFieldArrowCallback() {
			$('.arrow_dropdown').on('click', function() {
				if ($(this).hasClass('fa-angle-up')) {
					$(this).removeClass('fa-angle-up');
					$(this).addClass('fa-angle-down');
				} else {
					$(this).removeClass('fa-angle-down');
					$(this).addClass('fa-angle-up');
				}

				var parentArrow = $(this).closest('.arrow_product');
				var animationSpeed = 400;
				parentArrow.next('.product_form').slideToggle(animationSpeed);
			});
		}

		/**
		 * Регистрирует обработчик нажатия на иконку "фильтр" в мобильной версии.
		 * Обработчик скрывает или показывает блок умных фильтров в мобильной версии.
		 */
		function registerMobileFilterBlockCallback() {
			$('.section_capt .sort_mobile').on('click', function() {
				$(this).toggleClass('sort_close');
				var animationSpeed = 400;
				$('.filter_mobile').slideToggle(animationSpeed);
			});
		}

		/**
		 * Регистрирует обработчик отправки формы выбора способа оплаты, либо формы заказа в один шаг.
		 *
		 * Если был выбран способ оплаты "Платежная квитанция", заказ оформляется особым образом:
		 *   - Отправка формы блокируется
		 *   - Создается всплывающее окно, в котором делается редирект на action формы (оформляется заказ)
		 *   - Во всплывающем окне распечатывается платежная квитанция
		 *   - Скрипт всплывающего окна инициализирует редирект основного окна на страницу успешного оформления заказа
		 */
		function registerSubmitPaymentCallback() {
			$('form#payment_choose, form.without-steps').on('submit', function() {
				var $form = $(this);
				var $payment = $("input[name='payment-id']:checked", $form);

				if ($payment.attr('class') !== 'receipt') {
					return true;
				}

				var params = $form.serialize();
				var url = $form.attr('action');

				var win = window.open("", "_blank", "width=710,height=620,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=no");
				var html = "<html><head><" + "script" + ">location.href = '" + url + "?" + params + "'</" + "script" + "></head><body></body></html>";

				win.document.write(html);
				win.focus();

				return false;
			});
		}

		/** Регистрирует обработчик нажатия на иконки вида превью товаров на странице категории. */
		function registerProductPreviewCallback() {
			$('.sort_btn a').on('click', function() {
				var $button = $(this);
				var className = $button.data('class');
				$.cookie('catalog_class', className);

				$('#catalog_category')
					.removeClass('goods catalog_list catalog_inline')
					.addClass(className);
			});
		}

		/** Регистрирует обработчик нажатия на кнопки в блоке "Сортировать по" на странице категории. */
		function registerProductSortingCallback() {
			$('.sort_list a').on('click', function() {
				var $this = $(this);
				var fieldName = $this.data('field');
				$.cookie('sort_field', fieldName);

				var isAscending = !$this.parent().hasClass('up_arrow');
				var flag = isAscending ? '1' : '0';
				$.cookie('sort_direction_is_ascending', flag);

				window.location.reload(true);
			});
		}

		/** Инициализирует fancybox */
		function initFancybox() {
			$("a[rel=fancybox_group]").fancybox();
		}

		/**
		 * Регистрирует обработчик нажатия на категорию товаров первого уровня в мобильном меню.
		 * Обработчик выводит дочерние категории для этой категории.
		 */
		function registerCategoryMenuCallback() {
			$('.product_header').on('click', function() {
				var $this = $(this);
				$this.toggleClass('open');
				var animationSpeed = 400;
				$this.next('li').find('.mobile_sub_menu').slideToggle(animationSpeed);
			});
		}


		/**
		* Регистрирует обработчик нажатия на категорию товаров второго уровня в мобильном меню.
		* Обработчик выводит дочерние категории для этой категории.
		 */
		$(document).on("click", ".second_level_menu > a", function(evt){
			evt.preventDefault();
			var $this = $(this);
			$this.toggleClass('open');
			var animationSpeed = 400;
			$this.next('.mobile_sub_sub_menu').slideToggle(animationSpeed);
		});

		/** Регистрирует обработчик нажатия на кнопку "Подписаться" в футере. */
		function registerSubscribeFormCallback() {
			$('a.footer_subscription_button').on('click', function(e) {
				e.preventDefault();
				$(this).closest('form').submit();
			});
		}

		/**
		 * Инициализирует классическую капчу.
		 * Регистрирует обработчик нажатия на кнопку "Перезагрузить код".
		 */
		function initCaptcha() {
			$('.captcha_reset').on('click', function() {
				var now = new Date();
				$('.captcha_img').attr('src', '/captcha.php?reset&' + now.getTime());
			});
		}

		/** Инициализирует всплывающие окна через кастомную jQuery-функцию `customTooltip`. */
		function initToolTips() {
			$.fn.customTooltip = function() {
				var $tooltipBlock = $(this.attr('data-content'));

				this.tooltip({
					'html': true,
					'trigger': 'click',
					'placement': 'bottom',
					'title': $tooltipBlock.html()
				});
			};

			$('#basketTooltipToggle').customTooltip();
		}

		/** Инициализирует сворачиваемые блоки вопросов в модуле FAQ. */
		function initFaqQuestions() {
			$.fn.hideText = function(options) {

				var settings = $.extend({
					'speed': undefined,
					'dataName': 'element'
				}, options);

				this.click(function(e) {
					var block = $(e.target).data(settings['dataName']);
					var row = $(block).parent();
					var rowHeight = row.css('height');
					if (rowHeight > '95px') {
						row.css('height', '67px');
					} else {
						row.css('height', 'auto');

					}
					if (block === undefined) {
						return;
					}
					$(block).slideToggle(settings['speed'], function() {

						if ($(e.target).hasClass('sprite-hide_up')) {
							if ($(e.target).hasClass('title_link')) {
								return;
							} else {
								$(e.target).removeClass('sprite-hide_up');
							}
						} else {
							if ($(e.target).hasClass('title_link')) {
								return;
							} else {
								$(e.target).addClass('sprite-hide_up');

							}
						}
					});
				});

				return this;
			};

			$('.sprite-hide').hideText({
				speed: 'slow'
			});

			$('.title_link').hideText({
				speed: 'slow'
			});
		}

		/** Инициализирует библиотеку verge.js */
		function initVerge() {
			$.extend(verge);
		}
	},
};

/**
 * Модуль вспомогательных функций
 * @type {Object}
 */
site.helpers = {

	/**
	 * Форматирует строку цены
	 * @link https://stackoverflow.com/a/34141813
	 *
	 * @param {Number|String} price цена
	 * @param {String|null} prefix префикс цены
	 * @param {String|null} suffix суффикс цены
	 */
	formatPrice: function(price, prefix, suffix) {
		var number_format = function(number, decimals, dec_point, thousands_sep) {
			// Strip all characters but numerical ones.
			number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
			var n = !isFinite(+number) ? 0 : +number,
				prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
				sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
				dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
				s,
				toFixedFix = function (n, prec) {
					var k = Math.pow(10, prec);
					return '' + Math.round(n * k) / k;
				};
			// Fix for IE parseFloat(0.55).toFixed(0) = 0;
			s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
			if (s[0].length > 3) {
				s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
			}
			if ((s[1] || '').length < prec) {
				s[1] = s[1] || '';
				s[1] += new Array(prec - s[1].length + 1).join('0');
			}
			return s.join(dec);
		};

		price = number_format(price, 0, ',', ' ');
		prefix = prefix || '';
		suffix = suffix || '';

		return prefix + ' ' + price + ' ' + suffix;
	}
};

$(function() {
	site.common.init();
});

var basket = {
	get : function(callback) {
		basket.__request("/udata/emarket/basket.json", {}, callback);
	},	
	putElement : function(id, options, callback) {
		basket.__request("/udata/emarket/basket/put/element/" + id + ".json", options, callback);
	},
	modifyItem : function(id, options, callback) {
		if(options.amount == 0) {
			this.removeItem(id, callback);
			return;
		}
		basket.__request("/udata/emarket/basket/put/item/" + id + ".json", options, callback);
	},
	removeElement : function(id, callback) {
		basket.__request("/udata/emarket/basket/remove/element/" + id + ".json", {}, callback);
	},
	removeItem    : function(id, callback) {
		basket.__request("/udata/emarket/basket/remove/item/" + id + ".json", {}, callback);
	},
	removeAll     : function(callback) {
		basket.__request("/udata/emarket/basket/remove_all.json", {}, callback);
	},
	__cleanupHash : function(input) {
		return {
			customer : input.customer.object.id,
			items    : input.items,
			summary  : input.summary,
			id	:input.id
		};
	},
	__transformOptions : function(options) {
		var o = {};
		for(var i in options) {
			var k;
			if(i.toLowerCase() != "amount") k = "options[" + i + "]";
			else k = i;
			o[k] = options[i];
		}
		return o;
	},
	__request : function(url, options, callback) {
		jQuery.ajax({
			url      : url,
			type     : 'POST',
			dataType : 'json',
			data     : basket.__transformOptions(options),
			success  : function(data) {
				callback(basket.__cleanupHash(data));
			}
		});
	}
};

(function(w, $, _, getLabel) {

/**
 * Модуль корзины товаров
 * @type {Object}
 */
site.Cart = {
	/** @type {Boolean} Статус готовности корзины */
	ready: true,

	/** @type {Boolean} Является ли текущая страница корзиной */
	isCartPage: _.isArray(w.document.location.pathname.match(/emarket\/cart/)),

	/** Инициализация модуля */
	init: function() {

		/** @type {string} Селектор всплывающего окна для кнопки "Купить" */
		var buyModalSelector = '#buy_modal';

		/** @type {string} Селектор всплывающего окна для кнопки "Купить в один клик" */
		var oneClickModalSelector = '#oneclick_modal';

		/**
		 * Нажатие на кнопку "Купить" ("Добавить" для связанных товаров в корзине).
		 * Если у товара есть опционные свойства - появляется всплывающее окно с выбором свойств.
		 * Если опционных свойств нет - товар добавляется в корзину.
		 */
		$('a.add_to_cart_button').on('click', function(e) {
			e.preventDefault();
			var $this = $(this);

			var unavailable = $this.hasClass('not_buy');
			if (unavailable) {
				return;
			}

			var $optionedPropertiesBlock = $this.parents('.add_to_cart_block').find('.hidden_optioned_properties');

			if ($optionedPropertiesBlock.length === 0) {
				w.location.href = this.href;
				return;
			}

			var $buyModal = $(buyModalSelector);
			$buyModal.find('main').html($optionedPropertiesBlock.html());
			var $form = $buyModal.find('form');
			$form.attr('action', this.href);

			$buyModal.modal('show');
		});

		/** Нажатие на кнопку "Купить в один клик". */
		$('a.buy_one_click_button').on('click', function(e) {
			e.preventDefault();
			var $this = $(this);

			var unavailable = $this.hasClass('not_buy');
			if (unavailable) {
				return;
			}

			var $addToCartBlock = $this.parents('.add_to_cart_block');
			var $oneClickModal = $(oneClickModalSelector);

			var elementId = $addToCartBlock.data('element_id');
			$oneClickModal.data('element_id', elementId);

			var $optionedPropertiesBlock = $addToCartBlock.find('.hidden_optioned_properties');

			if ($optionedPropertiesBlock.length > 0) {
				$('#one_click_order_optioned_properties')
					.html($optionedPropertiesBlock.html())
					.find('input[type="submit"]')
					.remove(); // Удаляем ненужную кнопку 'Добавить в корзину'
			}

			$oneClickModal.modal('show');
		});

		/** Нажатие на кнопку "Оформить заказ" внутри всплывающего окна для заказа в один клик. */
		$(oneClickModalSelector).find('form').on('submit', function(e) {
			e.preventDefault();
			var elementId = $(oneClickModalSelector).data('element_id');
			var $form = $(this);

			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: '/emarket/getOneClickOrder/element/' + elementId + '.json',
				data: $form.serialize(),

				success: function(data) {
					if (data && data.data.error) {
						site.forms.showErrorMessage($form, data.data.error);
						return;
					}

					var message = getLabel('js-one_click_order_fail');

					var orderNumber = (data && data.data.orderId) ? data.data.orderId : null;
					if (orderNumber) {
						message = getLabel('js-one_click_order_success');
						message = message.replace('%s', orderNumber);
					}

					$form.parent().html(message);
				}
			});
		});

		/** Кнопка-крестик удаления товара из корзины */
		$('.order_delete').on('click', function(e) {
			e.preventDefault();
			site.Cart.remove(this.id.match(/\d+/).pop());
		});

		/** Изменение количества товара через кнопки "плюс/минус" */
		$('.change_product_quantity a').on('click', function(e) {
			e.preventDefault();

			if (!site.Cart.ready) {
				return;
			}

			site.Cart.ready = false;

			var $this = $(this);
			var orderItemId = $this.parent().data('id');
			var orderItemBlock = $('#order_item_' + orderItemId);

			var quantityNode = $('.quantity', orderItemBlock).contents()[0];
			var oldQuantity = parseInt(quantityNode.textContent, 10);
			var newQuantity = $this.hasClass('increment_product_quantity') ? oldQuantity + 1 : oldQuantity - 1;

			quantityNode.textContent = newQuantity;
			site.Cart.modify(orderItemId, newQuantity, oldQuantity);
		});
	},

	/**
	 * Возвращает функцию, которая вызывается после обновления корзины в бекенде,
	 * @see js/client/basket.js, basket.__request()
	 * Функция отвечает за обновление фронтенда корзины - пересчет количества товаров, цен, скидок и т.д.
	 *
	 * @param {Number|String} id Идентификатор измененного товарного наименования
	 * @returns {Function}
	 */
	redraw: function(id) {
		return function(data) {
			var orderItemCount = data.summary.amount || 0;

			if (orderItemCount > 0) {
				site.Cart.drawPopulatedCart(id, data);
			} else {
				site.Cart.drawEmptyCart();
			}

			site.Cart.updateOrderItemCount(orderItemCount);
			site.Cart.ready = true;
		};
	},

	/**
	 * Обновляет UI непустой корзины
	 * @param {Number|String} id Идентификатор измененного товарного наименования
	 * @param {Array} data данные корзины из бекенда
	 */
	drawPopulatedCart: function(id, data) {
		var formatPrice = site.helpers.formatPrice;
		var prefix = data.summary.price.prefix;
		var suffix = data.summary.price.suffix;

		var orderDiscount = data.summary.price.discount || 0;
		$('#order_discount').text(formatPrice(orderDiscount, prefix, suffix));

		var orderPrice = data.summary.price.original || data.summary.price.actual;
		$('#order_price').text(formatPrice(orderPrice, prefix, suffix));

		var orderItemBlock = $('#order_item_' + id);
		var orderItemWasRemoved = true;

		_.each(data.items.item, function(orderItem) {
			if (orderItem.id != id) {
				return;
			}

			orderItemWasRemoved = false;
			var orderItemTotalPrice = formatPrice(orderItem["total-price"].actual, prefix, suffix);
			var discount = orderItem.discount ? orderItem.discount.amount : 0;
			var orderItemDiscount = formatPrice(discount, prefix, suffix);

			$('.order_sum span', orderItemBlock).text(orderItemTotalPrice);
			$('.order_sale span', orderItemBlock).text(orderItemDiscount);
		});

		if (orderItemWasRemoved) {
			orderItemBlock.remove();
		}
	},

	/** Выводит на фронтенд пустую корзину */
	drawEmptyCart: function() {
		$.get('/templates/demomarket/js/cart/empty.html', function (data) {
			var emptyTemplate = _.template(data);
			var params = {
				'cart_empty': getLabel('js-cart_empty'),
				'return_to_catalog': getLabel('js-return_to_catalog')
			};

			$('.cart')
				.removeClass('cart')
				.addClass('cart-empty')
				.html(emptyTemplate(params));
		});
	},

	/**
	 * Обновляет информацию о количестве товаров в корзине в шапке сайта
	 * @param {Number} count новое количество товаров в корзине
	 */
	updateOrderItemCount: function (count) {
		$('.order_item_count').text(count);
		var cartHeader = getLabel('js-cart_header') + count;
		$('#basketTooltipToggle').text(cartHeader);
	},

	/**
	 * Отправляет аякс-запрос на изменение количества товарного наименования.
	 * Обновляет UI корзины.
	 *
	 * @param {number|string} id Идентификатор товарного наименования
	 * @param {number} newQuantity новое количество
	 * @param {number} oldQuantity предыдущее количество
	 */
	modify: function(id, newQuantity, oldQuantity) {
		if (newQuantity !== oldQuantity) {
			basket.modifyItem(id, {amount: newQuantity}, this.redraw(id));
		} else {
			this.ready = true;
		}
	},

	/**
	 * Отправляет аякс-запрос на удаление товарного наименования из корзины.
	 * Обновляет UI корзины.
	 *
	 * @param {number|string} id Идентификатор товарного наименования - числовое значение или ключевая строка `all`
	 */
	remove: function(id) {
		if (id === 'all') {
			basket.removeAll(this.redraw(id));
		} else {
			basket.removeItem(id, this.redraw(id));
		}
	}
};

$(function() {
	site.Cart.init();
});

})(window, jQuery, _, getLabel);

/**
 * Модуль filters.
 * Используется для управления адаптивной фильтрацией.
 * 
 * @param {jQuery} $
 * @link http://dev.docs.umi-cms.ru/spravochnik_makrosov_umicms/katalog/catalog_getsmartfilters/
 */
site.filters = (function($) {

	'use strict';

	/**
	 * @type {Number} минимальная ширина экрана в пикселях,
	 * при которой будет использоваться десктопная форма фильтров, а не мобильная.
	 */
	var MINIMUM_DESKTOP_WIDTH = 992;

	/** @type {jQuery} Форма с фильтрами */
	var _$form;

	/** @type {String} Название get-параметра, в котором передаются данные фильтрации */
	var _filterParamName = 'filter';

	/** @type {String} Префикс запроса на получение данных фильтрации */
	var _baseUrl = '/udata://catalog/getSmartFilters//';

	/** @type {String} ID категории, по которой выполняется фильтрация */
	var _categoryId;

	/** @type {Object} get-параметры фильтрации для всех полей */
	var _params = {};

	/**
	 * Кнопка отображения результатов фильтрации "Показать"
	 * @type {{element: jQuery, name: String}}
	 */
	var _resultButton = {
		$element: null,
		name: ''
	};

	/** @type {Boolean} Флаг инициализации полей типов "Чекбокс" и "Радио-кнопка" при первой загрузке фильтров */
	var _checkboxAndRadioFieldsAreInitialized = false;

	/** Инициализирует фильтрацию на странице */
	$(function() {
		init();

		if (hasFilterParam()) {
			_params = getFilterParams();
			getFilters();
		}

		var fieldList = getAllFields();
		forEachField(fieldList, bindValueChangeHandler);
	});

	/** Инициализирует умные фильтры на текущей странице */
	function init() {
		initFilterData();
		initResetButton();
		initSliderFields();
		initDateFields();

		/** Инициализирует основные параметры фильтрации */
		function initFilterData() {
			var formSelector = canShowMobileFilters() ? '.mobile_filters' : '.desktop_filters';
			_$form = $(formSelector);
			_categoryId = _$form.data('category');
			_resultButton.$element = $('.show_result', _$form);
			_resultButton.name = _resultButton.$element.val();
		}

		/** Инициализирует кнопку сброса фильтров */
		function initResetButton() {
			$('.reset', _$form).click(function(e) {
				e.preventDefault();
				location.href = location.pathname;
			});
		}

		/** Инициализирует поля со слайдером */
		function initSliderFields() {
			$('.slider_field', _$form).each(function() {
				var $field = $(this);

				var rangeBlock = $field.find('.delta_price');
				var from = rangeBlock.children('input.min');
				var to = rangeBlock.children('input.max');

				var startValue = parseFloat(from.data('minimum'));
				var selectedStartValue = parseFloat(from.val());

				var endValue = parseFloat(to.data('maximum'));
				var selectedEndValue = parseFloat(to.val());

				var $slider = $field.find('.price_progress .range');

				$slider.siblings('.min_val').text(startValue);
				$slider.siblings('.max_val').text(endValue);
				$slider.slider({
					range: true,
					min: startValue,
					max: endValue,
					values: [selectedStartValue, selectedEndValue],

					slide: function(event, ui) {
						from.val(ui.values[0]);
						to.val(ui.values[1]);
					},

					change: function(event, ui) {
						if (from.val() == ui.value) {
							onChange(from.get(0));
						}

						if (to.val() == ui.value) {
							onChange(to.get(0));
						}
					}
				});
			});
		}

		/** Инициализирует поля с типом "дата" */
		function initDateFields() {
			$('.date_field input[type=text]', _$form).each(function() {
				var $field = $(this);

				var minDate = new Date(Date.parse($field.data('minimum')));
				var maxDate = new Date(Date.parse($field.data('maximum')));
				var selectedDate = new Date(Date.parse($field.val()));
				var formattedDate = formatDate(selectedDate);

				$field.val(formattedDate);
				$field.datepicker({
					dateFormat: 'd.m.yy',
					minDate: minDate,
					maxDate: maxDate
				});
			});
		}
	}

	/** Выполняет ajax запрос для получения данных фильтрации */
	function getFilters() {
		var url = _baseUrl + _categoryId + '.json';

		$.ajax({
			url: url,
			data: _params,
			dataType: 'json',
			type: 'get',
			success: onGetFilters
		});
	}

	/**
	 * Отображает результат фильтрации (количество найденных товаров)
	 * @param {Object} data ответ от сервера с данными о фильтрации
	 */
	function showResult(data) {
		_resultButton.$element.val(_resultButton.name + ' (' + data.total + ')');
	}

	/**
	 * Определяет, нужно ли выводить фильтры в мобильной форме.
	 * Если сайт открыт в маленьком окне, для фильтрации будет использоваться
	 * мобильная форма, а если в большом - десктопная.
	 * @returns {Boolean}
	 */
	function canShowMobileFilters() {
		return $.viewportW() < MINIMUM_DESKTOP_WIDTH;
	}
	
	/**
	 * Преобразует Date в строковую дату
	 * @param {Date} date
	 * @returns {String}
	 */
	function formatDate(date) {
		if (!date instanceof Date) {
			return 'Date expected';
		}
		return date.getDate() + '.' + (date.getMonth() + 1) + '.' + date.getFullYear();
	}

	/**
	 * Обработчик ответа сервера с данными фильтрации
	 * @param {Object} response данные фильтрации
	 */
	function onGetFilters(response) {
		var $fieldList = getAllFields();

		forEachField($fieldList, resetField, function($field) {
			return (getFieldType($field).type !== 'text' && !isFiltered($field));
		});
		forEachField($fieldList, setField, null, response);
		enableCheckboxAndRadioFields();
		showResult(response);

		/** Включает поля типов "Чекбокс" и "Радио-кнопка" при первой загрузке фильтров */
		function enableCheckboxAndRadioFields() {
			if (_checkboxAndRadioFieldsAreInitialized) {
				return;
			}

			_checkboxAndRadioFieldsAreInitialized = true;

			forEachField($fieldList, function() {
				this.prop('checked', true);
			}, function($field) {
				var name = $field.attr('name');
				var type = getFieldType($field).type;
				var checkboxOrRadio = (type === 'checkbox' || type === 'radio');
				var isEnabled = (_params[name] && $field.val() == _params[name].replace(/\+/ig, ' '));
				return checkboxOrRadio && isEnabled;
			});
		}
	}

	/**
	 * Определяет, присутствует ли поле в параметрах фильтрации.
	 *
	 * Для полей типа "Чекбокс" учитывается совокупность всех чекбоксов
	 * в рамках одного свойства, например если хотя бы один чекбокс нажат:
	 *   filter[cvet][0]
	 *   filter[cvet][1]
	 *   filter[cvet][2]
	 *
	 *   все поля filter[cvet][*] участвуют в фильтрации.
	 *
	 * @param {jQuery} $field поле
	 * @returns {Boolean}
	 */
	function isFiltered($field) {
		var fieldName = getFieldNameByParam($field.attr('name'));
		var isFound = false;

		$.each(_params, function(param) {
			var name = getFieldNameByParam(param);
			if (fieldName === name) {
				isFound = true;
				return false;
			}
		});

		return isFound;
	}

	/** Выполняет визуальную "деактивацию" поля */
	function resetField() {
		var $field = this;

		if ($field.parent().length > 0) {
			disableField($field);
		}
	}

	/**
	 * Делает поле неактивным
	 * @param {jQuery} $field поле
	 */
	function disableField($field) {
		$field.parent().addClass('filter_disabled');
		$field.attr('disabled', '');
	}

	/**
	 * Обработчик события изменения значения поля
	 * @param {Object} event событие
	 */
	function onChange(event) {
		var $field = $(event.target || event);
		var newFieldParam = getFieldParam($field);
		$.extend(_params, newFieldParam);

		if (getFieldType($field).type === 'checkbox' && !$field.prop('checked')) {
			deleteFieldParam($field);
		}

		var rangeParams = getRangeParams();
		$.extend(_params, rangeParams);
		getFilters();

		/**
		 * Возвращает параметр с его значением поля
		 * @param {jQuery} $field поле
		 * @returns {Object}
		 */
		function getFieldParam($field) {
			var name = $field.attr('name');
			var value = $field.val();
			var param = {};
			param[name] = value;
			return param;
		}

		/**
		 * Удаляет параметр поля $field из объекта параметров фильтрации
		 * @param {jQuery} $field поле фильтрации
		 */
		function deleteFieldParam($field) {
			var name = $field.attr('name');
			delete _params[name];
		}

		/**
		 * Возвращает параметры полей с интервалом значений
		 * @returns {Object} {paramName1: paramValue1, paramName2: paramValue2, ...}
		 */
		function getRangeParams() {
			var $fieldList = getAllFields();
			var params = {};

			$fieldList.each(function (i, field) {
				var $field = $(field);
				var boundary = getBoundary($field);

				if (boundary === 'from' || boundary === 'to') {
					params[$field.attr('name')] = $field.val();
				}
			});

			return params;
		}
	}

	/**
	 * Возвращает есть ли в адресе параметры фильтрации
	 * @returns {Boolean}
	 */
	function hasFilterParam() {
		var params = getAllParams();
		for (var name in params) {
			if (params.hasOwnProperty(name)) {
				if (getArrayParamName(name) === _filterParamName) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Возвращает все параметры и их значения из строки запроса в виде объекта
	 * вида {paramName1: paramValue1, paramName2: paramValue2, ...}
	 * @returns {Object}
	 */
	function getAllParams() {
		var query = location.search;
		var params = {};

		if (query === '') {
			return params;
		}

		var decodedQuery = decodeURIComponent(query);
		decodedQuery = decodedQuery.replace(/^\?/, '');

		var groupList = decodedQuery.split('&');
		$.each(groupList, function(i, group) {
			group = group.split('=');
			var name = group[0];
			var value = group[1];
			params[name] = value;
		});

		return params;
	}

	/**
	 * Возвращает имя массива параметров по имени параметра
	 * @param {String} paramName имя параметра, @example "filter[price][from]"
	 * @returns {String} имя массива, @example "filter"
	 */
	function getArrayParamName(paramName) {
		var bracketPos = paramName.indexOf('[');
		if (bracketPos === -1) {
			return paramName;
		}
		return paramName.slice(0, bracketPos);
	}

	/**
	 * Возвращает объект только с данными о параметрах фильтрации
	 * вида вида {paramName1: paramValue1, paramName2: paramValue2 ...}
	 * @returns {Object}
	 */
	function getFilterParams() {
		var filterParams = {};
		$.each(getAllParams(), function(name, value) {
			if (getArrayParamName(name) === _filterParamName) {
				filterParams[name] = value;
			}
		});
		return filterParams;
	}

	/**
	 * Возвращает имя поля по имени параметра
	 * @param {String} paramName имя параметра, @example 'filter[price][from]'
	 * @returns {String} имя поля или пустую строку, @example 'price'
	 */
	function getFieldNameByParam(paramName) {
		var matches = /^(\w+)?\[(\w+)/.exec(paramName);
		if (typeof matches[2] !== 'undefined') {
			return matches[2];
		}
		return '';
	}

	/** Назначает обработчик событий выбора значения в фильтре для полей разных типов */
	function bindValueChangeHandler() {
		var $field = this;
		var fieldType = getFieldType($field);

		switch (true) {
			case fieldType.tag === 'input' && (fieldType.type === 'radio' || fieldType.type === 'checkbox'): {
				$field.click(onChange);
				break;
			}

			case $field.parents('.date_field').length > 0: {
				$field.change(onChange);
				break;
			}

			// Поле со слайдером
			default: {
				$field.bind('focus', function() {
					$(this).data('originValue', $(this).val());
				});

				$field.focusout(function(e) {
					var originValue = $(this).data('originValue');

					if ($(this).val() !== originValue) {
						onChange(e);
					}
				});
			}
		}
	}

	/**
	 * Возвращает данные, пришедшие от сервера по полю с именем `name`
	 * @param {JSON} data данные от сервера
	 * @param {String} name имя поля
	 * @returns {JSON}
	 */
	function getFieldDataByName(data, name) {
		var groupList = data['group'] || {};
		var fieldList = getFieldList(groupList);
		var fieldData = null;

		$.each(fieldList, function(i, field) {
			if (field.name === name) {
				fieldData = field;
				return false;
			}
		});

		return fieldData;

		/**
		 * Возвращает данные о дочерних элементах с именем 'field'
		 * @param {JSON} data
		 * @returns {Array}
		 */
		function getFieldList(data) {
			var allFieldList = [];
			$.each(data, function(name, value) {
				var fieldList = value['field'];
				$.each(fieldList, function(i, field) {
					allFieldList.push(field);
				});
			});

			return allFieldList;
		}
	}

	/**
	 * Выполняет функцию callback для всех полей fieldList, которые соответствуют критерию filter
	 * @param {jQuery} fieldList объект содержащий данные о полях формы фильтрации
	 * @param {Function} callback функция, которая будет выполнена для каждого поля
	 * @param {Function} [filter] функция, которая выступает в качестве критерия для отбора полей
	 */
	function forEachField(fieldList, callback, filter) {
		filter = (typeof filter === 'function') ? filter : function() {
			return true;
		};

		var extraArgs = Array.prototype.slice.call(arguments, 3);

		fieldList.each(function(i, field) {
			var $field = $(field);
			if (filter($field)) {
				callback.apply($field, extraArgs);
			}
		});
	}

	/**
	 * Возвращает все поля формы
	 * @returns {jQuery}
	 */
	function getAllFields() {
		return $('input[name]', _$form);
	}

	/**
	 * Возвращает объект с именем тега и типом (атрибут type) поля
	 * @param {jQuery} $field поле
	 */
	function getFieldType($field) {
		var tag = $field.prop('tagName').toLowerCase();
		var type = $field.attr('type') || null;

		return {
			tag: tag,
			type: type
		};
	}

	/**
	 * Изменяет состояние поля в зависимости от переданных данных
	 * @param {JSON} data
	 */
	function setField(data) {
		var $field = this;
		var paramName = $field.attr('name');
		var fieldName = getFieldNameByParam(paramName);
		var fieldData = getFieldDataByName(data, fieldName);

		var fieldType = getFieldType($field);
		if (fieldType.type === 'checkbox') {
			setCheckBoxField($field, fieldData);
		} else if (fieldType.type === 'radio') {
			setRadioField($field, fieldData);
		} else {
			setRangeField($field, fieldData);
		}
	}

	/**
	 * Изменяет состояние поля типа "Checkbox" в зависимости от переданных данных
	 * @param {jQuery} $field поле
	 * @param {JSON} data
	 */
	function setCheckBoxField($field, data) {
		if (!data) {
			return;
		}

		var fieldValue = $field.val();
		var itemList = data.item || [];

		$.each(itemList, function(i, item) {
			if (item.value === fieldValue) {
				enableField($field);
			}
		});
	}

	/**
	 * Делает поле активным
	 * @param {jQuery} $field поле
	 */
	function enableField($field) {
		$field.parent().removeClass('filter_disabled');
		$field.removeAttr('disabled');
	}

	/**
	 * Изменяет состояние поля типа "Radio" в зависимости от переданных данных
	 * @param {jQuery} $field поле
	 * @param {JSON} data
	 */
	function setRadioField($field, data) {
		var value = $field.val();
		if (value === '') {
			enableField($field);
		}
		setCheckBoxField($field, data);
	}

	/**
	 * Изменяет состояние поля с интервалом значений в зависимости от переданных данных
	 * @param {jQuery} $field поле
	 * @param {JSON} data
	 */
	function setRangeField($field, data) {
		if (!data) {
			return;
		}

		if (data.minimum && data.minimum.value && getBoundary($field) === 'from') {
			if (data['data-type'] != 'date') {
				$field.val(data.minimum.value);
				return;
			}
			var minDate = convertTimestampToDate(data.minimum.value);
			$field.val(formatDate(minDate));
		}

		if (data.maximum && data.maximum.value && getBoundary($field) === 'to') {
			if (data['data-type'] != 'date') {
				$field.val(data.maximum.value);
				return;
			}
			var maxDate = convertTimestampToDate(data.maximum.value);
			$field.val(formatDate(maxDate));
		}

		/**
		 * Преобразует timestamp в Date
		 * @param {Integer} timestamp
		 * @returns {Date}
		 */
		function convertTimestampToDate(timestamp) {
			return new Date(timestamp * 1000);
		}
	}

	/**
	 * Возвращает имя границы (from или to) поля с интервалом значений
	 * @param {jQuery} $field
	 * @returns {String}
	 */
	function getBoundary($field) {
		var name = $field.attr('name');
		var matches = /\[(\w+)\]$/.exec(name);

		if (matches && matches[1]) {
			return matches[1];
		}

		return '';
	}

})(jQuery);

/**
 * Модуль форм на сайте
 * @type {Object}
 */
site.forms = {

	/** Инициализация форм */
	init: function() {
		site.forms.showFieldErrorMessages();

		$('#post_vote').on('submit', this.submitVoteForm);

		if (location.href.indexOf('forget') !== -1) {
			var $forgetPasswordForm = $('#forget');

			$forgetPasswordForm.find('input:radio').click(function() {
				$forgetPasswordForm.find('input:text').attr('name', $(this).attr('id'));
			});
		}

		if (location.href.indexOf('purchasing_one_step') !== -1) {
			this.emarket.purchasingOneStep.init();
		}

		if (location.href.indexOf('purchase') !== -1) {
			this.emarket.purchase.init();
		}

		this.initLoginAjaxForm();
		this.initCallbackAjaxForm();
	},

	/** Инициализирует форму обратной связи "Заказать звонок" */
	initCallbackAjaxForm: function() {
		var $container = $('#callbackContent');
		var $form = $container.find('form');

		$form.on('submit', function(e) {
			e.preventDefault();
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: '/webforms/send.json?redirect_disallow=1',
				data: $form.serialize(),

				success: function(data) {
					var sendSuccess = !data.data;
					if (sendSuccess) {
						$container.html(getLabel('js-callback_success'));
						return;
					}

					var sendFailure = data.data && data.data.error;
					if (sendFailure) {
						site.forms.showErrorMessage($form, data.data.error);
					}
				}
			});
		});
	},

	/** Инициализирует форму авторизации */
	initLoginAjaxForm: function() {
		var $form = $('#login_form');
		$form.on('submit', function(e) {
			e.preventDefault();
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: '/users/login_do.json?redirect_disallow=1',
				data: $form.serialize(),

				success: function(data) {
					var loginSuccess = !data.data;
					if (loginSuccess) {
						location.reload(true);
					}

					var loginFailed = (data.data && data.data.from_page);
					if (loginFailed) {
						site.forms.showErrorMessage($form, getLabel('js-login_do_try_again'));
					}
				}
			});
		});
	},

	/**
	 * Выводит в форме сообщение об ошибке
	 * @param {jQuery} $form форма
	 * @param {String} message сообщение
	 */
	showErrorMessage: function($form, message) {
		$('.field_error_message', $form).remove();
		$('<div>', {
			'class': 'field_error_message',
			html: message
		}).appendTo($form);
	},

	/** Отправляет выбранный вариант опроса и выводит результаты опроса */
	submitVoteForm: function(e) {
		e.preventDefault();
		var $voteForm = $('#post_vote');
		var voteId = $voteForm.data('vote_id');
		var selected = $('input[name=answer]:checked', $voteForm).val();

		$.post('/vote/post/' + selected, function() {
			$.getJSON('/udata://vote/results/' + voteId + '.json', function(data) {
				var optionList = data.items.item;

				var $templateElement = $('#vote_result_template');
				var template = _.template($templateElement.html());
				var resultHtml = template({optionList: optionList});

				$('.interview').replaceWith(resultHtml);
				site.common.stylizeVoteResults();
			});
		});
	},

	/** Выводит сообщения об ошибках для полей форм */
	showFieldErrorMessages: function() {
		var matches = /_err=([^&]+)/.exec(location.search);
		if (!matches) {
			return;
		}

		var errorId = matches[1];
		$.getJSON('/udata/system/listErrorMessages.json?_err=' + errorId, function(data) {
			var errorList = (data.items && data.items.item) || [];
			$.each(errorList, function(i, error) {
				var fieldName = error['str-code'] || '';
				if (!fieldName) {
					return;
				}

				var $container;
				if (fieldName === 'captcha') {
					$container = $('main .captcha_field');
				} else {
					$container = $('main input[name="' + fieldName + '"]').parent();
				}

				$('<div>', {
					'class': 'field_error_message',
					html: error.message
				}).appendTo($container);
			});
		});
	},

	/**
	 * Контейнер для модулей оформления заказа
	 * @type {Object}
	 */
	emarket: {
		toggleNewObjectForm: function(container, newObjectBlock) {
			var block = $(newObjectBlock);

			if (block.length === 0) {
				return;
			}

			var $form = $('form#deliveryForm');

			if ($('input[type=radio][value!=new]', container).length > 0) {
				if ($('input[type=radio]:checked', container).val() !== 'new') {
					block.hide();
					$form.attr('novalidate', 'novalidate');
				}
			}

			$('input[type=radio]', container).click(function() {
				if ($(this).val() !== 'new') {
					block.hide();
					$form.attr('novalidate', 'novalidate');
				} else {
					block.show();
					$form.removeAttr('novalidate');
				}
			});
		},

		/**
		 * Модуль оформления заказа
		 * @type {Object}
		 */
		purchase: {
			init: function() {
				this.initDeliveryAddressPage();
				this.initInvoicePaymentPage();
			},

			// Показать/скрыть форму создания нового адреса доставки
			initDeliveryAddressPage: function() {
				var	addressBlock = '.delivery_address';
				site.forms.emarket.toggleNewObjectForm(addressBlock, '#new-address');

			},

			// Показать/скрыть форму создания нового юридического лица
			initInvoicePaymentPage: function() {
				var invoicePaymentBlock = '#invoice';
				var newLegalPersonBlock = '#new-legal-person';
				var $form = $('form#invoice');

				if ($('input[type=radio][value!=new]', invoicePaymentBlock).length > 0) {
					if ($('input[type=radio]:checked', invoicePaymentBlock).val() !== 'new') {
						$(newLegalPersonBlock).hide();
						$form.attr('novalidate', 'novalidate');
					}
				}

				$('input[type=radio]', invoicePaymentBlock).click(function() {
					if ($(this).val() !== 'new') {
						$(newLegalPersonBlock).hide();
						$form.attr('novalidate', 'novalidate');
					} else {
						$(newLegalPersonBlock).show();
						$form.removeAttr('novalidate');
					}
				});
			}
		},

		/**
		 * Модуль оформления заказа в один шаг
		 * @type {Object}
		 */
		purchasingOneStep: {
			init: function() {
				var customerSelector = '.customer.onestep';
				var	addressSelector = '.delivery_address.onestep';
				var	deliverySelector = '.delivery_choose.onestep';
				var paymentSelector = '.payment.onestep';
				var cartSelector = '.cart.onestep';

				var selectorList = [
					customerSelector,
					addressSelector,
					deliverySelector,
					paymentSelector,
					cartSelector
				];
				var actualBlocks = [];

				// Записываем только существующие блоки
				for (var i = 0; i < selectorList.length; i++) {
					if ($(selectorList[i]).length) {
						actualBlocks.push(selectorList[i]);
					}
				}

				var form = $('.without-steps');

				// Скрываем все блоки, кроме первого
				for (i = 1; i < actualBlocks.length; i++) {
					$(actualBlocks[i], form).hide();
				}

				// Показать/скрыть форму создания нового адреса доставки
				site.forms.emarket.toggleNewObjectForm(addressSelector, '#new-address');

				var animationSpeed = 300;
				var $paymentBlock = $(paymentSelector, form);
				var $deliveryBlock = $(deliverySelector, form);
				var $cartBlock = $(cartSelector, form);
				var $addressBlock = $(addressSelector, form);

				// Обработчик нажатия на кнопку "Продолжить" в форме личных данных покупателя
				$('a', customerSelector).click(function(event) {
					event.preventDefault();
					var formIsValid = true;

					if (typeof HTMLFormElement.prototype.reportValidity === 'function') {
						$('div.customer input', form).each(function(index, input) {
							if (!input.reportValidity()) {
								formIsValid = false;
							}
						});
					}

					if (formIsValid) {
						$(this).hide();
						$addressBlock.show(animationSpeed);
					}
				});

				// Обработчик события выбора адреса доставки или Самовывоза
				$('input[type=radio]', addressSelector).click(function() {
					if ($(this).data('type') === 'self') {
						$deliveryBlock.hide(animationSpeed);
						$paymentBlock.show(animationSpeed);
					} else {
						$deliveryBlock.show(animationSpeed);
					}
				});

				// Обработчик события выбора способа доставки
				$('input[type=radio]', deliverySelector).click(function() {
					if ($paymentBlock.length > 0) {
						$paymentBlock.show(animationSpeed);
					} else {
						$cartBlock.show(animationSpeed);
					}
				});

				// Обработчик события выбора способа оплаты
				$('input[type=radio]', paymentSelector).click(function() {
					$cartBlock.show(animationSpeed);
				});
			}
		}
	}
};

$(function() {
	site.forms.init();
});

/**
 * Поиск страниц на сайте.
 * Форма поиска находится в шапке сайта, файл `layout/header/bottom/search_form.phtml`
 */
(function($, _) {

	'use strict';

	var url = '/udata://search/search_do/.json';
	var searchStringParam = 'search_string';
	var perPageParam = 'per_page';
	var searchResults = {};
	var $element = null;
	var $form = null;
	var $templateElement = null;
	var $resultElement = null;
	var allResultButtonSelector = '.all_result';
	var maxResultsCount = 15;
	var template = null;
	var beforeCloseInterval = 200;

	var canShowMobileSearch = function() {
		return $.viewportW() < 992;
	};

	$(function() {
		var formSelector = canShowMobileSearch() ? '#searchFormMobile' : '#searchForm';
		$form = $(formSelector);
		$element = $('input[name=search_string]', $form).first();
		$templateElement = $('#search_result_template');
		$resultElement = $('.search_content', $form.parent());

		$resultElement.hide();

		if (!$form.length || !$element.length || !$templateElement.length) {
			return;
		}

		template = _.template($templateElement.html());

		$element.on('input', function() {
			var searchString = $element.val();
			search(searchString, function(response) {
				if (!isValidResult(response)) {
					return;
				}
				onSearchResult(searchString, response['items']);
			});
		});

		$element.on('blur', function() {
			setTimeout(function() {
				$resultElement.html('');
				$resultElement.hide();
			}, beforeCloseInterval);
		});
	});

	/**
	 * Обработчик события получения результатов поиска
	 * @param {String} searchString поисковый запрос
	 * @param result
	 */
	function onSearchResult(searchString, result) {
		saveResult(searchString, result);
		$resultElement.html('');

		var data = processResult(result);

		if (!data.length) {
			$resultElement.hide();
			return;
		}

		var resultHtml = template({typesList: data});
		$resultElement.html(resultHtml);
		$resultElement.show();

		$(allResultButtonSelector, $resultElement).on('click', function() {
			$form.submit();
		});
	}

	/**
	 * Обрабатывает результаты поиска для шаблонизатора
	 * @param {JSON} result данные найденных элементов
	 * @returns {*}
	 */
	function processResult(result) {
		var items = result['item'];

		if (!items) {
			return [];
		}

		return getDataByCategories(items);

		/**
		 * Группирует результаты найденные элементы по категориям
		 * @param {JSON} items данные найденных элементов
		 * @returns {Array}
		 */
		function getDataByCategories(items) {
			var data = $.extend(true, {}, items);
			var typesList = [];
			var type = null;

			_.each(data, function(element) {
				type = element['type'];

				if (!type) {
					type = {id: 0, name: ''};
				}

				if (type.module === 'Структура') {
					type.module = 'Страницы';
				}

				var addedType = _.findWhere(typesList, {id: type.id});

				if (!addedType) {
					typesList.push(type);
				}

				type = addedType || type;
				type['elements'] = type['elements'] || [];
				type['elements'].push(element);
			});

			return typesList;
		}
	}

	/**
	 * Проверяет вернулись ли от сервера корректные результаты поиска
	 * @param {JSON} data результаты поиска
	 * @returns {boolean}
	 */
	function isValidResult(data) {
		data = data || {};
		return !!data['items'];
	}

	/**
	 * Возвращает числовое значение (хэш) строки
	 * @param {String} str исходная строка
	 * @returns {number}
	 */
	function getHash(str) {
		var hash = 0;

		if (str.length === 0) {
			return hash;
		}

		var char = '';

		for (var i = 0; i < str.length; i++) {
			char = str.charCodeAt(i);
			hash = ((hash << 5) - hash) + char;
			hash = hash & hash;
		}

		return hash;
	}

	/**
	 * Сохраняет результаты поиска
	 * @param {String} searchString поисковый запрос
	 * @param {JSON} result результаты поиска
	 */
	function saveResult(searchString, result) {
		if (searchString) {
			searchResults[getHash(searchString)] = result;
		}
	}

	/**
	 * Выполняет поиск вхождения строки
	 * @param {String} string поисковый запрос
	 * @param {Function} callback вызывается при успешном получении результатов поиска
	 * @returns {*}
	 */
	function search(string, callback) {
		if (!string) {
			return;
		}

		callback = typeof callback === 'function' ? callback : function() {
		};

		var request = {};
		request[searchStringParam] = string;
		request[perPageParam] = maxResultsCount;

		var possibleResult = searchResults[getHash(string)];
		if (typeof possibleResult !== 'undefined') {
			return onSearchResult(string, possibleResult);
		}

		$.ajax({
			url: url,
			type: 'get',
			dataType: 'json',
			data: request,
			success: callback
		});
	}
})(jQuery, _);
