<?php

/**
 * Расширение php шаблонизатора для шаблона demomarket
 */
class DemomarketPhpExtension extends ViewPhpExtension {

	/** int максимальное количество товаров в блоке "Лучшие предложения" */
	const MAX_BEST_OFFERS_COUNT = 12;

	/** int максимальное количество товаров в карусели */
	const MAX_CAROUSEL_PRODUCT_COUNT = 12;

	/** int максимальное количество товаров для сравнения */
	const MAX_PRODUCT_COUNT_FOR_COMPARISON = 3;

	/** @var string путь до картинки "фото временно отсутствует" */
	private $noPhotoPath = '/templates/demomarket/img/no_photo.jpg';

	/**
	 * Инициализирует общие переменные для шаблонов.
	 * @param array $variables глобальные переменные запроса
	 */
	public function initializeCommonVariables($variables) {
		$templateEngine = $this->getTemplateEngine();
		$templateEngine->setCommonVar('domain', $variables['domain']);
        $templateEngine->setCommonVar('domain-id', $variables['domain-id']);
		$templateEngine->setCommonVar('lang', $variables['lang']);
        $templateEngine->setCommonVar('lang_id', cmsController::getInstance()->getCurrentLang()->getId());
		$templateEngine->setCommonVar('pre_lang', $variables['pre-lang']);
		$templateEngine->setCommonVar('header', isset($variables['header']) ? $variables['header'] : '');
		$templateEngine->setCommonVar('request_uri', $variables['request-uri']);
		$templateEngine->setCommonVar('user', $variables['user']);
        /*
		$cart = $this->macros('emarket', 'cart');
		$templateEngine->setCommonVar('cart', $cart);
		$templateEngine->setCommonVar('order_id', isset($cart['id']) ? $cart['id'] : '');
        */



        $getCategoryMenuNew = $this->macros('catalog','getCategoryMenuNew',[0]);
        $templateEngine->setCommonVar('getCategoryMenuNew', $getCategoryMenuNew);

        $settingsContainer = $this->getSettingsContainer();
        $templateEngine->setCommonVar('settingsContainer', $settingsContainer);

        $politics_page = $settingsContainer->politics;

        if(sizeof($politics_page)){
            $politics_page = $politics_page[0];
            $templateEngine->setCommonVar('politics_page', $politics_page->link);
        }else{
            $templateEngine->setCommonVar('politics_page', '');
        }
	}

	public function domainId() {
        $templateEngine = $this->getTemplateEngine();
        return $templateEngine->getCommonVar('domain-id');
    }

	public function renderCache($variables, $template,$key){
        $cacheName = 'renderCache_'.$key.'_'.cmsController::getInstance()->getCurrentLang()->getId();
        $cache = cacheFrontend::getInstance();
        $result = $cache->loadData($cacheName);
        if($result){
            return $result;
        }
        $templateEngine = $this->getTemplateEngine();
        $result = $templateEngine->render($variables,$template);
        $cache->saveData($cacheName,$result,86400);
        return $result;
    }

    public function macrosCache($module,$method,$params,$key){
        $cacheName = 'macrosCache_'.$key.'_'.cmsController::getInstance()->getCurrentLang()->getId();;
        $cache = cacheFrontend::getInstance();
        $result = $cache->loadData($cacheName);
        if($result){
            return $result;
        }
        $result = $this->macros($module,$method,$params);
        $cache->saveData($cacheName,$result,86400);
        return $result;
    }

	/**
	 * Возвращает ID формы "заказать звонок"
	 * @return int|bool
	 */
	public function getCallbackId() {
		return umiObjectTypesCollection::getInstance()
			->getTypeIdByGUID('call-order-form');
	}

	/**
	 * Возвращает товары для вкладки "Новинки"
	 * @return iUmiHierarchyElement[]
	 */
	public function getNewProducts() {
		return $this->getProductsByFlag('new');
	}

	/**
	 * Возвращает товары для вкладки "Лучшие предложения"
	 * @return iUmiHierarchyElement[]
	 */
	public function getBestProducts() {
		return $this->getProductsByFlag('best_offers');
	}

	/**
	 * Возвращает список товаров, у которых отмечено булевое поле с заданным строковым идентификатором
	 * @param string $flag строковой идентификатор булевого поля
	 * @return iUmiHierarchyElement[]
	 */
	public function getProductsByFlag($flag) {
		$products = new selector('pages');
		$products->types('object-type')->name('catalog', 'object');
		$products->where($flag)->equals(true);
		$products->limit(0, self::MAX_BEST_OFFERS_COUNT);
		$products->order('id')->rand();
		return $products->result();
	}

	/**
	 * Возвращает форматированную цену
	 * @param float $price цена
	 * @return string
	 */
	public function formatPrice($price) {
		$price = (float) $price;
		return number_format($price, 0, ',', ' ');
	}

	/**
	 * Возвращает ссылку на отзывы товара
	 * @param iUmiHierarchyElement $product страница (товар)
	 * @return string
	 */
	public function getCommentLink(iUmiHierarchyElement $product) {
		$fragment = '#add_comment';

		if ($this->getCommentCount($product) > 0) {
			$fragment = '#comments';
		}

		return $this->getPath($product) . $fragment;
	}

	/**
	 * Возвращает количество комментариев к странице
	 * @param iUmiHierarchyElement $page страница
	 * @return int
	 */
	public function getCommentCount(iUmiHierarchyElement $page) {
		$result = $this->macros('comments', 'countComments', [$page->getId()]);
		return isset($result['total']) ? $result['total'] : 0;
	}

	/**
	 * Возвращает сообщение "Отзывы (#)"
	 * @param iUmiHierarchyElement $product товар (страница)
	 * @return string
	 */
	public function getCommentMessage(iUmiHierarchyElement $product) {
		$message = $this->getTemplateEngine()->translate('comments');
		$count = $this->getCommentCount($product);
		return "{$message} ({$count})";
	}

	/**
	 * Возвращает адрес фотографии товара
	 * @param iUmiHierarchyElement $product товар (страница)
	 * @return string
	 */
	public function getPhotoPath(iUmiHierarchyElement $product) {
		/** @var iUmiImageFile $photo */
		$photo = $product->getValue('photo');

		if ($photo instanceof iUmiImageFile) {
			return $photo->getFilePath(true);
		}

		return $this->noPhotoPath;
	}

	/**
	 * Возвращает цену товара с учетом скидок
	 * @param iUmiHierarchyElement $product страница (товар)
	 * @return string
	 */
	public function getPrice(iUmiHierarchyElement $product) {
		$result = $this->macros('emarket', 'price', [$product->getId()]);
		$prefix = isset($result['price']['prefix']) ? $result['price']['prefix'] : '';
		$price = isset($result['price']['actual']) ? $result['price']['actual'] : 0;
		$suffix = isset($result['price']['suffix']) ? $result['price']['suffix'] : '';

		return implode(' ', [
			$prefix,
			$this->formatPrice($price),
			$suffix,
		]);
	}

	/**
	 * Возвращает день публикации страницы
	 * @param iUmiHierarchyElement $page страница
	 * @return string
	 */
	public function getPublishDay(iUmiHierarchyElement $page) {
		return $this->getPublishDateParts($page)[0];
	}

	/**
	 * Возвращает месяц публикации страницы
	 * @param iUmiHierarchyElement $page страница
	 * @return string
	 */
	public function getPublishMonth(iUmiHierarchyElement $page) {
		return $this->getPublishDateParts($page)[1];
	}

	/**
	 * Возвращает год публикации страницы
	 * @param iUmiHierarchyElement $page страница
	 * @return string
	 */
	public function getPublishYear(iUmiHierarchyElement $page) {
		return $this->getPublishDateParts($page)[2];
	}

	/**
	 * Возвращает дату публикации страницы в формате [
	 *   0 => <day>,
	 *   1 => <month>,
	 *   2 => <year>,
	 * ]
	 * В текущей реализации работает только с русскоязычными датами, @see dateToString()
	 *
	 * @param iUmiHierarchyElement $page страница
	 * @return array
	 */
	public function getPublishDateParts(iUmiHierarchyElement $page) {
		$date = $page->getValue('publish_time');
		$timeStamp = ($date instanceof umiDate) ? $date->getDateTimeStamp() : $date;
		return explode(' ', dateToString($timeStamp));
	}

	/**
	 * Определяет, нужно ли выводить форму опроса
	 * @param array $variables результат работы макроса vote:poll()
	 * @return bool
	 */
	public function canShowVoteForm(array $variables) {
		return isset($variables['items'][0]['id']);
	}

	/**
	 * Определяет, нужно ли вывести результаты опроса
	 * @param array $variables результат работы макроса vote:poll()
	 * @return bool
	 */
	public function canShowVoteResults(array $variables) {
		return isset($variables['items'][0]['score']);
	}

	/**
	 * Выводит текущий год
	 * @return string
	 */
	public function getCurrentYear() {
		return date('Y');
	}

	/**
	 * Возвращает список разделов каталога, разбитый на две части
	 * @param array $variables результат работы макроса catalog::getCategoryList()
	 * @return array
	 */
	public function getCategoryGroups(array $variables) {
		return array_chunk($variables, 2);
	}

	/**
	 * Возвращает список основных разделов каталога
	 * @return array результат работы макроса catalog::getCategoryList()
	 */
	public function getMainCategories() {
		$categoryId = 'shop';
		return $this->getChildCategories(['id' => $categoryId]);
	}

	/**
	 * Возвращает дочерние категории родительской категории
	 * @param array $parent данные раздела каталога
	 *
	 * [
	 *     'id' => идентификатор раздела каталога
	 * ]
	 *
	 * @return array catalog::getCategoryList()
	 */
	public function getChildCategories(array $parent) {
		$template = null;
		$categoryId = isset($parent['id']) ? $parent['id'] : '';
		$limit = 10;
		$ignorePaging = true;

		$data = $this->macros('catalog', 'getCategoryList', [$template, $categoryId, $limit, $ignorePaging]);
		$data = is_array($data) ? $data : [];
		$data['category_id'] = isset($data['category_id']) ? $data['category_id'] : '';
		$data['items'] = isset($data['items']) ? $data['items'] : [];

		foreach ($data['items'] as &$category) {
			$category = is_array($category) ? $category : [];
			$category['id'] = isset($category['id']) ? $category['id'] : '';
			$category['link'] = isset($category['link']) ? $category['link'] : '';
			$category['text'] = isset($category['text']) ? $category['text'] : '';
		}

		return $data;
	}

	/**
	 * Возвращает данные карты сайта
	 * @return array результат работы макроса content/sitemap:
	 *
	 * [
	 *     'items' => [
	 *         # => [
	 *             'id' => int идентификатор страницы
	 *             'link' => string ссылка на страницу
	 *             'name' => название страницы
	 *         ]
	 *     ]
	 * ]
	 */
	public function getSiteMap() {
		$data = $this->macros('content', 'sitemap');
		$data = is_array($data) ? $data : [];
		$data['items'] = isset($data['items']) ? $data['items'] : [];

		foreach ($data['items'] as &$page) {
			$page = is_array($page) ? $page : [];
			$page['id'] = isset($page['id']) ? $page['id'] : '';
			$page['link'] = isset($page['link']) ? $page['link'] : '';
			$page['name'] = isset($page['name']) ? $page['name'] : '';
		}

		return $data;
	}

	/**
	 * Возвращает данные меню сайта
	 * @param string $menuId строковой идентификатор меню
	 * @return array результат работы макроса menu/draw:
	 *
	 * [
	 *     'lines' => [
	 *         # => [
	 *             'link' => string ссылка на страницу
	 *             'name' => название страницы
	 *         ]
	 *     ]
	 * ]
	 */
	public function getMenu($menuId) {
		$data = $this->macros('menu', 'draw', [$menuId]);
		$data = is_array($data) ? $data : [];
		$data['lines'] = isset($data['lines']) ? $data['lines'] : [];

		foreach ($data['lines'] as &$page) {
			$page = is_array($page) ? $page : [];
			$page['link'] = isset($page['link']) ? $page['link'] : $this->getHomePageUrl();
			$page['name'] = isset($page['name']) ? $page['name'] : '';
		}

		return $data;
	}

	/**
	 * Возвращает данные миниатюры
	 * @param array $variables
	 *
	 * [
	 *      'src' =>  string,       // путь до оригинального изображения
	 *      'width' => int|string,  // ширина миниатюры или ключевое слово auto
	 *      'height' => int|string, // высота миниатюры или ключевое слово auto
	 *      'id' => int,            // идентификатор сущности
	 *      'field_name' => string, // строковой идентификатор поля сущности
	 * ]
	 *
	 * @return array
	 *
	 * Результат работы макроса system::makeThumbnailFull()
	 *
	 * +
	 *
	 * [
	 *      'id' => int,            // идентификатор сущности
	 *      'field_name' => string, // строковой идентификатор поля сущности
	 * ]
	 */
	public function getThumbnail(array $variables) {
		$variables['id'] = isset($variables['id']) ? $variables['id'] : '';
		$variables['field_name'] = isset($variables['field_name']) ? $variables['field_name'] : '';
		$variables['src'] = isset($variables['src']) ? $variables['src'] : '';
		$variables['width'] = isset($variables['width']) ? $variables['width'] : 'auto';
		$variables['height'] = isset($variables['height']) ? $variables['height'] : 'auto';

		$thumbnail = $this->macros(
			'system',
			'makeThumbnailFull',
			[
				'path' => '.' . $variables['src'],
				'width' => $variables['width'],
				'height' => $variables['height'],
			]
		);

		$thumbnail['alt'] = isset($variables['alt']) ? $variables['alt'] : '';
		$thumbnail['src'] = isset($thumbnail['src']) ? $thumbnail['src'] : '';
		$thumbnail['width'] = isset($thumbnail['width']) ? $thumbnail['width'] : '';
		$thumbnail['height'] = isset($thumbnail['height']) ? $thumbnail['height'] : '';
		$thumbnail['id'] = $variables['id'];
		$thumbnail['field_name'] = $variables['field_name'];
		return $thumbnail;
	}

	/**
	 * Определяет пуста ли корзина
	 * @param array $variables результат работы макроса emarket::cart()
	 *
	 * [
	 *      'data' => [
	 *          'items' => array // список товаров в корзине
	 *      ]
	 * ]
	 *
	 * @return bool
	 */
	public function isCartEmpty(array $variables) {
		$variables = isset($variables['data']) ? $variables['data'] : $variables;
		return !isset($variables['items'][0]);
	}

	/**
	 * Возвращает заголовок цены в корзине
	 * @param array $variables часть результата работы макроса emarket::cart()
	 *
	 * [
	 *      'data' => [
	 *          'summary' => [
	 *              'price' => [
	 *                  'prefix' =>  string,  // префикс валюты
	 *                  'suffix' =>  string,  // суффикс валюты
	 *              ]
	 *          ]
	 *      ]
	 * ]
	 *
	 * @return string
	 */
	public function getPriceHeader(array $variables) {
		return implode(' ', [
			$this->getTemplateEngine()->translate('price') . ',',
			$this->getPriceSymbol($variables),
			'x',
			$this->getTemplateEngine()->translate('amount'),
		]);
	}

	/**
	 * Возвращает символ валюты
	 * @param array $variables данные цены
	 *
	 * [
	 *      'data' => [
	 *          'summary' => [
	 *              'price' => [
	 *                  'prefix' =>  string,  // префикс валюты
	 *                  'suffix' =>  string,  // суффикс валюты
	 *              ]
	 *          ]
	 *      ]
	 * ]
	 *
	 * @return string
	 */
	public function getPriceSymbol(array $variables) {
		$variables = isset($variables['data']) ? $variables['data'] : $variables;
		$prefix = isset($variables['summary']['price']['prefix']) ? $variables['summary']['price']['prefix'] : '';
		$suffix = isset($variables['summary']['price']['suffix']) ? $variables['summary']['price']['suffix'] : '';
		return $prefix ?: $suffix;
	}

	/**
	 * Возвращает заголовок скидки в корзине
	 * @param array $variables часть результата работы макроса emarket::cart()
	 *
	 * [
	 *      'data' => [
	 *          'summary' => [
	 *              'price' => [
	 *                  'prefix' =>  string,  // префикс валюты
	 *                  'suffix' =>  string,  // суффикс валюты
	 *              ]
	 *          ]
	 *      ]
	 * ]
	 *
	 * @return string
	 */
	public function getDiscountHeader(array $variables) {
		return implode(' ', [
			$this->getTemplateEngine()->translate('item-discount') . ',',
			$this->getPriceSymbol($variables),
		]);
	}

	/**
	 * Возвращает заголовок "Сумма" для корзины
	 * @param array $variables часть результата работы макроса emarket::cart()
	 *
	 * [
	 *      'data' => [
	 *          'summary' => [
	 *              'price' => [
	 *                  'prefix' =>  string,  // префикс валюты
	 *                  'suffix' =>  string,  // суффикс валюты
	 *              ]
	 *          ]
	 *      ]
	 * ]
	 *
	 * @return string
	 */
	public function getSumHeader(array $variables) {
		return implode(' ', [
			$this->getTemplateEngine()->translate('sum') . ',',
			$this->getPriceSymbol($variables),
		]);
	}

	/**
	 * Возвращает css-класс для товара в корзине
	 * @param int $index порядковый номер товара в корзине
	 * @return string
	 */
	public function getBorderClass($index) {
		return ($index === 0) ? 'no_border' : '';
	}

	/**
	 * Возвращает цену одной единицы товара в корзине без скидки
	 * @param array $product данные товара
	 *
	 * [
	 *      'price' => [
	 *          'original' => float, // цена товара без скидки
	 *          'actual' => float    // текущая цена товара
	 *      ]
	 * ]
	 *
	 * @return  string
	 */
	public function getOriginalPrice(array $product) {
		$originalPrice = isset($product['price']['original']) ? $product['price']['original'] : 0;
		$actualPrice = isset($product['price']['actual']) ? $product['price']['actual'] : 0;
		return $this->formatPrice($originalPrice ?: $actualPrice);
	}

	/**
	 * Возвращает общую цену товара в корзине с учетом количества
	 * @param array $product данные товара
	 *
	 * [
	 *      'price' => [
	 *          'actual' => float  // текущая цена товара
	 *      ],
	 *      'amount' => int        // количество товара
	 * ]
	 *
	 * @return string
	 */
	public function getTotalPrice(array $product) {
		$price = isset($product['price']['actual']) ? $product['price']['actual'] : 0;
		$amount = isset($product['amount']) ? $product['amount'] : 1;
		return $this->formatPrice($price * $amount);
	}

	/**
	 * Возвращает общую цену с префиксом/суффиксом валюты
	 * @param array $product данные товара
	 *
	 * [
	 *      'price' => [
	 *          'actual' => float,  // текущая цена товара
	 *          'prefix' => string, // префикс валюты
	 *          'suffix' => string  // суффикс валюты
	 *      ],
	 *      'amount' => int         // количество товара
	 * ]
	 *
	 * @return string
	 */
	public function getTotalPriceWithSymbol(array $product) {
		$price = $this->getTotalPrice($product);
		$prefix = isset($product['price']['prefix']) ? $product['price']['prefix'] : '';
		$suffix = isset($product['price']['suffix']) ? $product['price']['suffix'] : '';

		return implode(' ', [
			$prefix,
			$price,
			$suffix,
		]);
	}

	/**
	 * Возвращает количество товара в корзине
	 * @param array $product данные товара
	 *
	 * [
	 *      'amount' => int // количество товара в корзине
	 * ]
	 *
	 * @return int
	 */
	public function getAmount(array $product) {
		return (int) isset($product['amount']) ? $product['amount'] : 1;
	}

	/**
	 * Возвращает абсолютное значение скидки на товар
	 * @param array $product данные товара
	 *
	 * [
	 *      'discount' => [
	 *          'amount' => int // абсолютное значение скидки на товар
	 *      ]
	 * ]
	 *
	 * @return int
	 */
	public function getDiscount(array $product) {
		return isset($product['discount']['amount']) ? $product['discount']['amount'] : 0;
	}

	/**
	 * Возвращает скидку на товар с префиксом/суффиксом валюты
	 * @param array $product данные товара
	 *
	 * [
	 *      'discount' => [
	 *          'amount' => int // абсолютное значение скидки на товар
	 *      ],
	 *     'price' => [
	 *          'prefix' => string, // префикс валюты
	 *          'suffix' => string  // суффикс валюты
	 *      ],
	 * ]
	 *
	 * @return string
	 */
	public function getDiscountWithSymbol(array $product) {
		$discount = $this->getDiscount($product);
		$prefix = isset($product['price']['prefix']) ? $product['price']['prefix'] : '';
		$suffix = isset($product['price']['suffix']) ? $product['price']['suffix'] : '';

		return implode(' ', [
			$prefix,
			$discount,
			$suffix,
		]);
	}

	/**
	 * Возвращает абсолютное значение скидки на заказ с префиксом/суффиксом валюты
	 * @param array $variables данные заказа
	 *
	 * [
	 *      'data' => [
	 *          'summary' => [
	 *              'price' => [
	 *                  'prefix' => string  // префикс валюты
	 *                  'discount' => int   // абсолютное значение скидки
	 *                  'suffix' => string  // суффикс валюты
	 *              ]
	 *          ]
	 *      ]
	 * ]
	 *
	 * @return string
	 */
	public function getOrderDiscount(array $variables) {
		$variables = isset($variables['data']) ? $variables['data'] : $variables;
		$prefix = isset($variables['summary']['price']['prefix']) ? $variables['summary']['price']['prefix'] : '';
		$suffix = isset($variables['summary']['price']['suffix']) ? $variables['summary']['price']['suffix'] : '';
		$discount = isset($variables['summary']['price']['discount']) ? $variables['summary']['price']['discount'] : 0;

		return implode(' ', [
			$prefix,
			$this->formatPrice($discount),
			$suffix,
		]);
	}

	/**
	 * Возвращает цену заказа префиксом/суффиксом валюты
	 * @param array $variables данные заказа
	 *
	 * [
	 *      'data' => [
	 *          'summary' => [
	 *              'price' => [
	 *                  'prefix' => string  // префикс валюты
	 *                  'actual' => float   // текущая цена заказа
	 *                  'suffix' => string  // суффикс валюты
	 *              ]
	 *          ]
	 *      ]
	 * ]
	 *
	 * @return string
	 */
	public function getOrderPrice(array $variables) {
		$variables = isset($variables['data']) ? $variables['data'] : $variables;
		$prefix = isset($variables['summary']['price']['prefix']) ? $variables['summary']['price']['prefix'] : '';
		$suffix = isset($variables['summary']['price']['suffix']) ? $variables['summary']['price']['suffix'] : '';
		$price = isset($variables['summary']['price']['actual']) ? $variables['summary']['price']['actual'] : 0;

		return implode(' ', [
			$prefix,
			$this->formatPrice($price),
			$suffix,
		]);
	}

	/**
	 * Возвращает ссылку на страницу с корзиной товаров
	 * @return string
	 */
	public function getCartLink() {
		$langPrefix = cmsController::getInstance()->getPreLang();
		return $langPrefix . '/emarket/cart';
	}

	/**
	 * Возвращает ссылку, чтобы положить товар в корзину
	 * @param iUmiHierarchyElement $product страница (товар)
	 * @return string
	 */
	public function getAddToCartLink(iUmiHierarchyElement $product) {
		$langPrefix = cmsController::getInstance()->getPreLang();
		return $langPrefix . '/emarket/basket/put/element/' . $product->getId();
	}

	/**
	 * Возвращает ссылку, чтобы убрать товар из корзины
	 * @param iUmiHierarchyElement $product страница (товар)
	 * @return string
	 */
	public function getRemoveFromCartLink(iUmiHierarchyElement $product) {
		$langPrefix = cmsController::getInstance()->getPreLang();
		return $langPrefix . '/emarket/basket/remove/element/' . $product->getId();
	}

	/**
	 * Возвращает список товаров, рекомендуемых к приобретению с заданным товаром
	 * @param iUmiHierarchyElement $product страница (товар)
	 * @return iUmiHierarchyElement[]
	 */
	public function getSuggestedProducts(iUmiHierarchyElement $product) {
		$suggestedList = $product->getValue('udachno_sochetaetsya_s');

		if (!is_array($suggestedList)) {
			$suggestedList = [];
		}

		$filteredList = [];

		foreach ($suggestedList as $suggestedProduct) {
			if (!$suggestedProduct instanceof iUmiHierarchyElement) {
				continue;
			}

			if (!$this->isInStock($suggestedProduct)) {
				continue;
			}

			$filteredList[] = $suggestedProduct;
		}

		return array_slice($filteredList, 0, self::MAX_CAROUSEL_PRODUCT_COUNT);
	}

	/**
	 * Возвращает товары, находящиеся в одной категории с заданным товаром
     * @param iUmiHierarchyElement $product страница (товар)
	 * @return iUmiHierarchyElement[]
	 */
	public function getRelatedProducts(iUmiHierarchyElement $product) {
		$products = new selector('pages');
		$products->types('hierarchy-type')->name('catalog', 'object');
		$products->where('hierarchy')->page($product->getParentId());
		$products->where('id')->notequals($product->getId());
		$products->limit(0, self::MAX_CAROUSEL_PRODUCT_COUNT);
		$products->order('id')->rand();
		return $products->result();
	}

	/**
	 * Возвращает недавно просмотренные товары
	 * @return iUmiHierarchyElement[]
	 */
	public function getRecentlyVisitedProducts() {
		$template = null;
		$scope = null;
		$showCurrentElement = false;
		$limit = self::MAX_CAROUSEL_PRODUCT_COUNT;

		$data = $this->macros(
			'content',
			'getRecentPages',
			[$template, $scope, $showCurrentElement, $limit]
		);

		$data = is_array($data) ? $data : [];
		$productDataList = isset($data['items']) ? $data['items'] : [];

		$umiHierarchy = umiHierarchy::getInstance();
		$productList = [];

		/** @var array $productDataList */
		foreach ($productDataList as $productData) {
			$productList[] = $umiHierarchy->getElement($productData['id'], true);
		}

		return $productList;
	}

	/**
	 * Возвращает сообщение с количеством голосов
	 * @param array $variables результат работы макроса vote::getElementRating()
	 *
	 * [
	 *      'rate_voters' => int // количество голосов
	 * ]
	 *
	 * @return string
	 */
	public function getVoteCountMessage(array $variables) {
		$message = $this->getTemplateEngine()->translate('total_votes');
		$count = isset($variables['rate_voters']) ? $variables['rate_voters'] : 0;
		return "{$message} ({$count})";
	}

	/**
	 * Возвращает звездочки голосования
	 * @param array $variables результат работы макроса vote::getElementRating()
	 *
	 * [
	 *      'ceil_rate' => int,  // средний рейтинг
	 *      'element_id' => int  // идентификатор страницы
	 * ]
	 *
	 * @return array
	 *
	 * [
	 *     'css_color_class' => css-класс звездочки рейтинга
	 *     'link' => ссылка для голосования за страницу
	 * ]
	 */
	public function getVoteStars(array $variables) {
		$averagedRating = isset($variables['ceil_rate']) ? $variables['ceil_rate'] : 0;
		$elementId = isset($variables['element_id']) ? $variables['element_id'] : '';
		$minRating = 1;
		$maxRating = 5;
		$stars = [];

		for ($rating = $minRating; $rating <= $maxRating; $rating++) {
			$cssColorClass = ($rating > $averagedRating) ? 'gray_text' : 'red_text';

			$stars[] = [
				'css_color_class' => $cssColorClass,
				'link' => "/vote/setElementRating/default/{$elementId}/{$rating}/",
			];
		}

		return $stars;
	}

	/**
	 * Возвращает родителя страницы
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *      'parents' => array // список страниц, родительских текущей
	 * ]
	 *
	 * @return iUmiHierarchyElement
	 */
	public function getImmediateParent(array $variables) {
		if (isset($variables['parents']) && is_array($variables['parents']) && count($variables['parents']) > 0) {
			return array_pop($variables['parents']);
		}

		return $this->getDefaultPage();
	}

	/**
	 * Возвращает все изображения товара
	 * @param iUmiHierarchyElement $product страница (товар)
	 * @return string[]
	 */
	public function getProductImagePaths(iUmiHierarchyElement $product) {
		$mainPhotoPath = $this->getPhotoPath($product);
		$paths = [$mainPhotoPath];

		if ($mainPhotoPath === $this->noPhotoPath) {
			return $paths;
		}

		$additionalPhotos = is_array($product->getValue('photos')) ? $product->getValue('photos') : [];

		foreach ($additionalPhotos as $photo) {
			if ($photo instanceof iUmiImageFile) {
				$paths[] = $photo->getFilePath(true);
			}
		}

		return $paths;
	}

	/**
	 * Определяет в наличии ли товар
	 * @param iUmiHierarchyElement $product страница (товар)
	 * @return bool
	 */
	public function isInStock(iUmiHierarchyElement $product) {
		return $product->getValue('common_quantity') > 0;
	}

	/**
	 * Возвращает ссылку для добавления или удаления товара из сравнения
	 * @param iUmiHierarchyElement $product страница (товар)
	 * @return string
	 */
	public function getProductComparisonLink(iUmiHierarchyElement $product) {
		$data = $this->macros('emarket', 'getCompareLink', [$product->getId()]);
		$data = is_array($data) ? $data : [];
		$addLink = isset($data['add-link']) ? $data['add-link'] : '';
		$removeLink = isset($data['del-link']) ? $data['del-link'] : '';
		return $addLink ?: $removeLink;
	}

	/**
	 * Возвращает сообщение для добавления или удаления товара
	 * @param iUmiHierarchyElement $product страница (товар)
	 * @return string
	 */
	public function getComparisonMessage(iUmiHierarchyElement $product) {
		$data = $this->macros('emarket', 'getCompareLink', [$product->getId()]);
		$data = is_array($data) ? $data : [];

		if (isset($data['add-link'])) {
			return $this->getTemplateEngine()->translate('add_to_comparison');
		}

		return $this->getTemplateEngine()->translate('remove_from_comparison');
	}

	/**
	 * Возвращает список полей товара, разделенных на две равные части
	 * @param iUmiHierarchyElement $product страница (товар)
	 * @return array
	 *
	 * [
	 *    0 => [                    // первая часть полей
	 *       0 => [
	 *          'title' => string,  // наименование поля
	 *          'name' => string,   // строковой идентификатор поля
	 *		    'value' => mixed    // значение поля
	 *       ]
	 *    ],
	 *    1 => [                    // вторая часть полей
	 *       0 => [
	 *          'title' => string,  // наименование поля
	 *          'name' => string,   // строковой идентификатор поля
	 *		    'value' => mixed    // значение поля
	 *       ]
	 *    ]
	 * ]
	 */
	public function getProductPropertyChunks(iUmiHierarchyElement $product) {
		$object = $product->getObject();

		/** @var iUmiFieldsGroup $propertiesGroup */
		$propertiesGroup = $object->getType()->getFieldsGroupByName('item_properties');
        //$propertiesGroup = $object->getType()->getFieldsGroupByName('special');
		$fieldList = [];
		if ($propertiesGroup instanceof iUmiFieldsGroup) {
			$fieldList = $propertiesGroup->getFields();
		}

		$propertyList = [];

		foreach ($fieldList as $field) {
			$propertyList[] = $object->getPropByName($field->getName());
		}

        //$propertiesGroup = $object->getType()->getFieldsGroupByName('item_properties');
        $propertiesGroup = $object->getType()->getFieldsGroupByName('special');
        $fieldList = [];
        if ($propertiesGroup instanceof iUmiFieldsGroup) {
            $fieldList = $propertiesGroup->getFields();
        }

        foreach ($fieldList as $field) {
            $propertyList[] = $object->getPropByName($field->getName());
        }



		$propertyDataList = $this->getPropertyDataList($propertyList);
		/*
		$length = count($propertyDataList);
		$firstHalf = array_slice($propertyDataList, 0, $length / 2);
		$secondHalf = array_slice($propertyDataList, $length / 2);
		*/
		return $propertyDataList;
	}

	/**
	 * Возвращает список данных свойств для вывода в шаблоне товара
	 * @param iUmiObjectProperty[] $propertyList список свойств
	 * @return array [
	 *   [
	 *     'title' => %title%,
	 *     'name' => %name%,
	 *     'value' => %value%
	 *   ],
	 * ]
	 */
	public function getPropertyDataList(array $propertyList) {
		$result = [];

		foreach ($propertyList as $property) {
			if (!$this->isAllowedDataType($property->getDataType())) {
				continue;
			}

			$result[] = $this->getPropertyData($property);
		}

		return $result;
	}

	/**
	 * Поддерживается ли вывод поля заданного типа
	 * @param string $dataType тип поля
	 * @return bool
	 */
	public function isAllowedDataType($dataType) {
		$allowedDataTypes = [
			'int',
			'string',
			'text',
			'relation',
			'date',
			'boolean',
			'symlink',
			'price',
			'float',
			'counter',
			'optioned',
			'color',
		];

		return in_array($dataType, $allowedDataTypes);
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
	public function getPropertyData($property) {
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
	public function getSymlinkValue(iUmiObjectProperty $property) {
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
	public function getRelationValue(iUmiObjectProperty $property) {
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
	public function getOptionedValue(iUmiObjectProperty $property) {
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

	/**
	 * Возвращает список товаров
	 * @param array $variables результат работы метода Demomarket::getCatalog()
	 *
	 * [
	 *     'category_id' => идентификатор категории товаров,
	 *     'lines' => [
	 *         # => [
	 *             'id' => идентификатор товара
	 *         ]
	 *     ]
	 * ]
	 *
	 * @return iUmiHierarchyElement[]
	 */
	public function getProducts(array $variables) {
		$isRootCatalog = !isset($variables['category_id']);

		if ($isRootCatalog) {
			return $variables;
		}

		$umiHierarchy = umiHierarchy::getInstance();
		$productDataList = isset($variables['lines']) ? $variables['lines'] : [];
		$productList = [];

		/** @var array $productDataList */
		foreach ($productDataList as $productData) {
			$product = $umiHierarchy->getElement($productData['id'], true);

			if ($product instanceof iUmiHierarchyElement) {
				$productList[] = $product;
			}
		}

		return $productList;
	}

	/**
	 * Определяет находится ли страница на первом уровне вложенности от корня
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *     'parents' => array  // список страниц, родительских для текущей
	 * ]
	 *
	 * @return bool
	 */
	public function isRootCatalog(array $variables) {
		return !isset($variables['parents']) || count($variables['parents']) === 0;
	}

	/**
	 * Возвращает строковой идентификатор поля, по которому сортируются товары
	 * @return string
	 */
	public function getSortField() {
		$cookieJar = \UmiCms\Service::CookieJar();
		return $cookieJar->get('sort_field') ?: 'common_quantity';
	}

	/**
	 * Определяет сортируются ли товары в восходящем порядке
	 * @return bool
	 */
	public function isSortDirectionAscending() {
		$cookieJar = \UmiCms\Service::CookieJar();
		return (bool) $cookieJar->get('sort_direction_is_ascending');
	}

	/**
	 * Возвращает css-класс для пункта сортировки
	 * @param string $sortField строковой идентификатор сортируемого поля
	 * @return string
	 */
	public function getSortClass($sortField) {
		if ($sortField !== $this->getSortField()) {
			return '';
		}

		$direction = $this->isSortDirectionAscending() ? 'up_arrow' : 'down_arrow';
		return "active {$direction}";
	}

	/**
	 * Возвращает данные для построения пагинации в списке товаров
	 * @param array $variables результат работы метода Demomarket::getCatalog()
	 *
	 * [
	 *     'category_id'  => int,  // идентификатор раздела каталога
	 *     'numpages'  =>  array,  // данные пагинации
	 *     'total'  =>  int,       // общее количество товаров
	 *     'per_page'  =>  int,    // количество выводимых товаров на одной странице
	 * ]
	 *
	 * @return array
	 *
	 * $variables['numpages]
	 *
	 * +
	 *
	 * [
	 *     'total' => int,    // общее количество товаров
	 *     'per_page' => int, // количество выводимых товаров на одной странице
	 * ]
	 */
	public function getPagination(array $variables) {
		$isRootCatalog = !isset($variables['category_id']);

		if ($isRootCatalog) {
			return [];
		}

		$pagination = isset($variables['numpages']) ? $variables['numpages'] : [];
		$pagination['total'] = isset($variables['total']) ? $variables['total'] : 0;
		$pagination['per_page'] = isset($variables['per_page']) ? $variables['per_page'] : 0;
		return $pagination;
	}

	/**
	 * Возвращает ссылку на следующую страницу пагинации
	 * @param array $pagination параметры пагинации
	 *
	 * [
	 *     'tonext_link'  => [
	 *          'value' => string,  // ссылка на следующую страницу пагинации
	 *      ]
	 * ]
	 *
	 * @return string
	 */
	public function getNextPageLink(array $pagination) {
		return $this->getCurrentPath() . $pagination['tonext_link']['value'];
	}

	/**
	 * Возвращает текущую строку запроса без get-параметров
	 * @return string
	 */
	public function getCurrentPath() {
		$currentUrl = $this->getTemplateEngine()->getCommonVar('request_uri');
		return strtok($currentUrl, '?');
	}

	/**
	 * Возвращает поля для вывода умных фильтров
	 * @param array|null $filtersData результат работы макроса catalog::getSmartFilter()
	 *
	 * [
	 *     'group' => [                         // список групп полей
	 *          0 => [                          // группа полей
	 *              0 => [                      // список полей
	 *                  0 => [
	 *                      'name' => string,   // строковой идентификатор поля
	 *                      'title' => string,  // наименования поля
	 *                      mixed array         // варианты значения поля
	 *                  ]
	 *              ]
	 *          ]
	 *     ]
	 * ]
	 *
	 * @return array
	 *
	 * [
	 *      0 => [
	 *          'name' => string,   // строковой идентификатор поля
	 *          'title' => string,  // наименования поля
	 *          mixed array         // варианты значения поля
	 *      ]
	 * ]
	 */
	public function getFilterFields($filtersData) {
		$filtersData = is_array($filtersData) ? $filtersData : [];
		$groupList = isset($filtersData['group']) ? $filtersData['group'] : [];
		$fieldList = [];

		/** @var array $groupList */
		foreach ($groupList as $group) {
			$groupFields = isset($group['field']) ? $group['field'] : [];

			foreach ($groupFields as $field) {
				$fieldList[] = $field;
			}
		}

		return $fieldList;
	}

	/**
	 * Возвращает варианты значения булевого поля, выводимого с помощью radio-кнопок
	 * @param array $data описание поля и его значений, часть результата макроса catalog::getSmartFilters()
	 *
	 * [
	 *      'item' => [
	 *          0 => [
	 *              'value' => 0,           // вариант значения поля
	 *              'is-selected' => bool   // вариант был выбран
	 *          ],
	 *          0 => [
	 *              'value' => 1,           // вариант значения поля
	 *              'is-selected' => bool   // вариант был выбран
	 *          ]
	 *      ]
	 * ]
	 *
	 * @return array
	 *
	 * [
	 *     0 => string, // применен фильтр по значению поля
	 *     1 => string, // применен фильтр по отсутствию значения поля
	 *     2 => string  // фильтр не применен
	 * ]
	 */
	public function getChecked(array $data) {
		$result = ['', '', ''];
		$i = 0;
		$checked = false;
		$items = (isset($data['item'])) ? $data['item'] : [];

		foreach ($items as $item) {
			if (isset($item['is-selected']) && $item['is-selected']) {
				$result[$i] = 'checked';
				$checked = true;
			}

			$i++;
		}

		if (!$checked) {
			$result['2'] = 'checked';
		}

		return $result;
	}

	/**
	 * Возвращает список фотографий фотоальбома
	 * @param array $albumData результат работы макроса photoalbum::album()
	 *
	 * [
	 *     'lines' => [
	 *          0 => [
	 *              'id' => int // идентификатор страницы (фотографии)
	 *          ]
	 *      ]
	 * ]
	 *
	 * @return iUmiHierarchyElement[]
	 */
	public function getPhotoalbumPhotos(array $albumData) {
		$umiHierarchy = umiHierarchy::getInstance();
		$photoDataList = isset($albumData['lines']) ? $albumData['lines'] : [];
		$photoList = [];

		/** @var array $photoDataList */
		foreach ($photoDataList as $photoData) {
			$photo = $umiHierarchy->getElement($photoData['id'], true);

			if ($photo instanceof iUmiHierarchyElement) {
				$photoList[] = $umiHierarchy->getElement($photoData['id'], true);
			}
		}

		return $photoList;
	}

	/**
	 * Возвращает сообщение о количестве товаров в корзине
	 * @param array $cart результат работы макроса emarket::cart()
	 *
	 * [
	 *     'total-amount' => float, // общее количество товаров в корзине
	 * ]
	 *
	 * @return string
	 */
	public function getCartProductCountMessage(array $cart) {
		$count = isset($cart['total-amount']) ? $cart['total-amount'] : 0;

		if ($count === 0) {
			return $this->getTemplateEngine()->translate('cart_empty');
		}

		$message = $this->getTemplateEngine()->translate('cart_items_count');
		return implode(' ', [$message, $count]);
	}

	/**
	 * Возвращает данные товаров в корзине
	 * @param array $cart результат работы макроса emarket::cart()
	 *
	 * [
	 *     'items' => [
	 *         # => [
	 *             'page' => iUmiHierarchyElement страница товара,
	 *             'name' => имя товара
	 *         ]
	 *     ]
	 * ]
	 *
	 * @return array результат работы макроса emarket::cart(), ключ 'items'
	 */
	public function getCartProducts(array $cart) {
		$productList = isset($cart['items']) ? $cart['items'] : [];
		$filteredProductList = [];

		foreach ($productList as $product) {
			$page = isset($product['page']) ? $product['page'] : null;

			if ($page instanceof iUmiHierarchyElement) {
				$filteredProductList[] = $product;
			}
		}

		foreach ($filteredProductList as &$filteredProduct) {
			$filteredProduct['name'] = isset($filteredProduct['name']) ? $filteredProduct['name'] : '';
		}

		return $filteredProductList;
	}

	/**
	 * Возвращает владельца блога или null, если его не удалось определить
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *      'pageId' => int, // идентификатор блога
	 * ]
	 *
	 * @return int|null
	 */
	public function getBlogOwnerId(array $variables) {
		$data = $this->macros('blogs20', 'viewBlogAuthors', [$variables['pageId']]);
		$data = is_array($data) ? $data : [];
		$authorList = isset($data['users']) ? $data['users'] : [];

		/** @var array $authorList */
		foreach ($authorList as $author) {
			$isOwner = isset($author['is_owner']) ? (bool) $author['is_owner'] : false;

			if ($isOwner) {
				return $author['user_id'];
			}
		}

		return null;
	}

	/**
	 * Возвращает css-класс для товаров-новинок
	 * @param iUmiHierarchyElement $product страница (товар)
	 * @return string
	 */
	public function getNewLabelClass(iUmiHierarchyElement $product) {
		return $product->getValue('new') ? 'sticker_item' : '';
	}

	/**
	 * Возвращает css-класс для отображения каталога
	 * @return string
	 */
	public function getCatalogClass() {
		$cookieJar = \UmiCms\Service::CookieJar();
		return $cookieJar->get('catalog_class') ?: 'goods';
	}

	/**
	 * Возвращает список товаров.
	 * Возвращает результат работы макроса catalog::getSmartCatalog(), либо DemomarketPhpExtension::getBestProducts(),
	 * если страница на первом уровне вложенности.
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *     'pageId' => int,   // идентификатор корневого раздела каталога
	 *     'parent' => array, // список страниц, родительских текущей странице
	 * ]
	 *
	 * @return array
	 */
	public function getCatalog(array $variables) {
		if ($this->isRootCatalog($variables)) {
			return $this->getBestProducts();
		}

		$template = null;
		$categoryId = $variables['pageId'];
		$limit = null;
		$ignorePaging = false;
		$level = 4;
		$fieldName = $this->getSortField();
		$isAscending = $this->isSortDirectionAscending();

		return $this->macros(
			'catalog',
			'getSmartCatalogCustom',
			[
				$categoryId,
				$limit,
				$ignorePaging,
				$level,
				$fieldName,
				$isAscending,
			]
		);
	}

	/**
	 * Возвращает имя класса для оформления поля товара
	 * @param int $index порядковый связки (набора) полей
	 * @return string
	 */
	public function getPropertyChunkClass($index) {
		return ($index === 0) ? 'pr20' : 'pl20';
	}

	/**
	 * Определяет нужно ли выводить блок "опционные свойства" для товара
	 * @param iUmiHierarchyElement $product страницы (товар)
	 * @param iUmiField[] $fieldList список полей из группы "catalog_option_props"
	 * @return bool
	 */
	public function canShowOptionedProperties(iUmiHierarchyElement $product, array $fieldList) {
		foreach ($fieldList as $field) {
			$valueList = (array) $product->getValue($field->getName());

			if (count($valueList) > 0) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Возвращает атрибут "checked" для radio-поля
	 * @param int $index порядковый номер варианта значения radio-поля
	 * @return string
	 */
	public function getRadioStatusByPosition($index) {
		return ($index === 0) ? 'checked' : '';
	}

	/**
	 * Возвращает результат работы макроса catalog::getSmartFilters()
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *    'pageId' => идентификатор раздела каталога
	 * ]
	 *
	 * @return array|null
	 */
	public function getSmartFilters(array $variables,$level = 2) {
		$template = null;
		$categoryId = $variables['pageId'];
		$isAdaptive = false;
        //$level = 2;
		try {
			$data = $this->macros('catalog', 'getSmartFilters', [$template, $categoryId, $isAdaptive, $level]);
		} catch (Exception $e) {
			return null;
		}

		$data = is_array($data) ? $data : [];
		$data['category-id'] = isset($data['category-id']) ? $data['category-id'] : 'undefined';
		return $data;
	}

	/**
	 * Определяет является ли текущая страница корзиной (emarket/cart)
	 * @return bool
	 */
	public function isCartPage() {
		$path = $this->getTemplateEngine()->getCommonVar('request_uri');
		return preg_match('/emarket\/cart/', $path);
	}

	/**
	 * Возвращает строку адреса
	 * @param int $id идентификатор адреса
	 * @return string
	 */
	public function getAddressLine($id) {
		$address = $this->getObjectById($id);
		$fieldList = $address->getType()->getAllFields(true);
		$result = [];

		foreach ($fieldList as $field) {
		    $value = $address->getValue($field->getName());
		    if($value){
                if ($field->getName() == 'country') {
                    $result[] = $this->getObjectById($address->getValue($field->getName()))->getName();
                } else {
                    $result[] = $address->getValue($field->getName());
                }
            }

		}

		return implode(', ', $result);
	}

	/**
	 * Определяет нужно ли рисовать опцию создания нового адреса доставки при оформлении заказа
	 * @param array $variables часть результата работы макроса emarket::purchase()
	 *
	 * [
	 *      'only_self_delivery' => bool,  // в системе присутствуют только способы доставки типа "Самовывоз"
	 *      'self_delivery_exist' => bool, // в системе присутствуют хотя бы один способ доставки типа "Самовывоз"
	 *      'items' => array,              // список способов доставки
	 * ]
	 *
	 * @return bool
	 */
	public function canShowNewDeliveryAddressOption(array $variables) {
		if (isset($variables['only_self_delivery']) && $variables['only_self_delivery']) {
			return false;
		}

		$realDeliveryList = isset($variables['items']) ? $variables['items'] : [];
		$selfDeliveryExists = isset($variables['self_delivery_exist']) ? $variables['self_delivery_exist'] : false;
		return count($realDeliveryList) > 0 || $selfDeliveryExists;
	}

	/**
	 * Определяет нужно ли рисовать форму создания нового адреса доставки при оформлении заказа
	 * @param array $variables часть результата работы макроса emarket::purchase()
	 *
	 * [
	 *      'only_self_delivery' => bool, // в системе присутствуют только способы доставки типа "Самовывоз"
	 *      'type-id' => int,            // идентификатор типа данных способа доставки
	 * ]
	 *
	 * @return bool
	 */
	public function canShowNewDeliveryAddressForm(array $variables) {
		$onlySelfDelivery = isset($variables['only_self_delivery']) ? (bool) $variables['only_self_delivery'] : false;
		return !$onlySelfDelivery && isset($variables['type-id']);
	}

	/**
	 * Возвращает css-класс для шага оформления заказа
	 * @param array $step часть результата работы макроса emarket::purchase()
	 *
	 * [
	 *    'status' => string // статус шага оформления заказа
	 * ]
	 *
	 * @return string
	 */
	public function getPurchaseStepClass($step) {
		return in_array($step['status'], ['active', 'complete']) ? 'active' : '';
	}

	/**
	 * Возвращает результаты поиска страниц на сайте
	 * @return array результаты макроса search/search_do:
	 *
	 * [
	 *     'per_page' => int количество результатов на одной странице
	 *     'lines' => [
	 *         # => [
	 *             'id' => int идентификатор страницы
	 *             'link' => string ссылка на страницу
	 *             'name' => название страницы
	 *             'context' => string найденная строка в контексте контента поля страницы
	 *             'number' => int порядковый номер найденной страницы
	 *         ]
	 *     ]
	 * ]
	 */
	public function getSearchResult() {
		$data = $this->macros('search', 'search_do', [null, getRequest('search_string'), 52]);
		$data = is_array($data) ? $data : [];
		$data['lines'] = isset($data['lines']) ? $data['lines'] : [];
		$data['per_page'] = isset($data['per_page']) ? $data['per_page'] : 0;
		$currentPage = (int) getRequest('p');

		foreach ($data['lines'] as $index => &$item) {
			$item['id'] = isset($item['id']) ? $item['id'] : '';
			$item['name'] = isset($item['name']) ? $item['name'] : '';
			$item['link'] = isset($item['link']) ? $item['link'] : $this->getHomePageUrl();
			$item['context'] = isset($item['context']) ? $item['context'] : '';
			$item['number'] = $currentPage * $data['per_page'] + ($index + 1);
		}

		return $data;
	}

	/**
	 * Возвращает заголовок для результатов поиска
	 * @param array $variables результат работы макроса search::search_do()
	 *
	 * [
	 *    'last_search_string' => string, // поисковый запрос
	 *    'total' => int                  // количество найденных страниц
	 * ]
	 *
	 * @return string
	 */
	public function getSearchResultHeader(array $variables) {
		$total = isset($variables['total']) ? $variables['total'] : 0;

		if ($total > 0) {
			$message = $this->getTemplateEngine()->translate('search_result');
			$query = isset($variables['last_search_string']) ? $variables['last_search_string'] : '';
			return sprintf($message, htmlspecialchars($query), $total);
		}

		return $this->getTemplateEngine()->translate('search_empty_result');
	}

	/**
	 * Возвращает путь картинки для публикации новости
	 * @param iUmiHierarchyElement $newsItem новость
	 * @return string|null
	 */
	public function getNewsItemPhotoPath(iUmiHierarchyElement $newsItem) {
		/** @var umiImageFile $photo */
		$photo = $newsItem->getValue('publish_pic');

		if ($photo instanceof iUmiImageFile) {
			return $photo->getFilePath(true);
		}

		return null;
	}

	/**
	 * Делает редирект на указанный адрес
	 * @param string $url
	 * @return void
	 */
	public function redirect($url) {
		/** @var HTTPOutputBuffer $buffer */
		$buffer = outputBuffer::current('HTTPOutputBuffer');
		$buffer->redirect($url);
	}

	/**
	 * Возвращает сообщение с датой заказа для вкладки "Личный кабинет->Заказы"
	 * @param array $order данные заказа
	 *
	 * [
	 *     'number' => int,          // номер заказа
	 *     'creation_date' => string // дата создания заказа
	 * ]
	 *
	 * @return string
	 * @example № 8 от 19.01.2016
	 */
	public function getOrderDateMessage(array $order) {
		if (!isset($order['number'], $order['creation_date'])) {
			return '';
		}

		return implode(' ', [
			$this->getTemplateEngine()->translate('number_sign'),
			$order['number'],
			$this->getTemplateEngine()->translate('order_date_from'),
			$order['creation_date'],
		]);
	}

	/**
	 * Возвращает сообщение со статусом заказа для вкладки "Личный кабинет->Заказы"
	 * @param array $order данные заказа
	 *
	 * [
	 *     'status_name' => string,       // название статуса
	 *     'status_change_date' => string // дата изменения статуса
	 * ]
	 *
	 * @return string
	 * @example Ожидает проверки с 19.01.2016
	 */
	public function getOrderStatusMessage(array $order) {
		if (!isset($order['status_name'], $order['status_change_date'])) {
			return '';
		}

		return implode(' ', [
			$order['status_name'],
			$this->getTemplateEngine()->translate('order_status_date_from'),
			$order['status_change_date'],
		]);
	}

	/**
	 * Возвращает ссылку на товар товарного наименования для вкладки "Личный кабинет->Заказы"
	 * @param array $orderItem данные товарного наименования
	 *
	 * [
	 *     'element_id' => int // идентификатор страницы (товара)
	 * ]
	 *
	 * @return string
	 */
	public function getOrderItemProductUrl(array $orderItem) {
		$preLang = $this->getTemplateEngine()->getCommonVar('pre_lang');
		$umiHierarchy = umiHierarchy::getInstance();
		return $preLang . $umiHierarchy->getPathById($orderItem['element_id']);
	}

	/**
	 * Возвращает информацию о заказе для вкладки "Личный кабинет->Заказы"
	 * @param array $variables данные заказа
	 *
	 * [
	 *     'id' => int,           // идентификатор заказа
	 *     'name' => string,      // название заказа (Заказ #1)
	 *     'type-id' => int,      // идентификатор типа данных заказа
	 *     'type-guid' => string, // гуид типа данных заказа
	 *     'ownerId' => int,      // идентификатор владельца заказа
	 * ]
	 *
	 * @return array
	 *
	 * Результат работы макроса emarket::order()
	 *
	 * +
	 *
	 * [
	 *     'creation_date' => string,      // дата создания заказа
	 *     'status_change_date' => string, // дата изменения статуса заказа
	 *     'payment_name' => string,       // название способа оплаты
	 *     'status_name' => string,        // название статус заказа
	 *     'number' => string,             // номер заказа
	 * ]
	 */
	public function getOrderInfo(array $variables) {
		$order = $this->getObjectById($variables['id']);
		$orderInfo = $this->macros('emarket', 'order', [$variables['id']]);

		$orderInfo = array_merge($orderInfo, [
			'creation_date' => '',
			'status_change_date' => '',
			'payment_name' => '',
			'status_name' => '',
		]);

		$orderDate = $order->getValue('order_date');

		if ($orderDate instanceof umiDate) {
			$orderInfo['creation_date'] = $orderDate->getFormattedDate('d.m.Y');
		}

		$statusDate = $order->getValue('status_change_date');

		if ($statusDate instanceof umiDate) {
			$orderInfo['status_change_date'] = $statusDate->getFormattedDate('d.m.Y');
		}

		$payment = $this->getObjectById($order->getValue('payment_id'));

		if ($payment instanceof iUmiObject) {
			$orderInfo['payment_name'] = $payment->getName();
		}

		$status = isset($orderInfo['status']) ? $orderInfo['status'] : null;

		if ($status instanceof iUmiObject) {
			$orderInfo['status_name'] = $status->getName();
		}

		$orderInfo['number'] = isset($orderInfo['number']) ? $orderInfo['number'] : '';

		return $orderInfo;
	}

	/**
	 * Возвращает css-класс для поля формы, в зависимости от того, является ли оно обязательным
	 * @param array $field данные поля
	 *
	 * [
	 *    'required' => bool // является ли поле обязательным для заполнения
	 * ]
	 *
	 * @return string
	 */
	public function getFormFieldClass($field) {
		return isset($field['required']) ? 'important' : '';
	}

	/**
	 * Возвращает данные для шаблона входа на сайт
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *    'demo' => mixed  // работает ли система в режиме демо центра
	 * ]
	 *
	 * @return array
	 *
	 * [
	 *    'login' => string,         // логин
	 *    'password' => string,      // пароль
	 *    'action' => string,        // action для формы логина пользователя
	 *    'register_link' => string, // адрес страницы с формой регистрации пользователя
	 * ]
	 *
	 */
	public function getLoginParams(array $variables) {
		$langPrefix = $this->getTemplateEngine()->getCommonVar('pre_lang');
		$login = '';
		$password = '';

		if (isset($variables['demo'])) {
			$login = 'demo';
			$password = 'demo';
		}

		return [
			'login' => $login,
			'password' => $password,
			'action' => "{$langPrefix}/users/login_do/",
			'register_link' => "{$langPrefix}/users/registrate/",
		];
	}

	/**
	 * Перенаправляет в личный кабинет, если пользователь авторизован
	 * @return void
	 */
	public function redirectLoggedInUserToUserSettings() {
		// Побочный эффект от вызова
		$this->macros('users', 'registrate');
	}

	/**
	 * Возвращает ссылку на страницу регистрации
	 * @return string
	 */
	public function getRegistrationLink() {
		return $this->getTemplateEngine()->getCommonVar('pre_lang') . '/users/registrate/';
	}

	/**
	 * Возвращает ссылку на страницу восстановления пароля
	 * @return string
	 */
	public function getPasswordRestoreLink() {
		return $this->getTemplateEngine()->getCommonVar('pre_lang') . '/users/forget/';
	}

	/**
	 * Возвращает полное имя автора по его ID
	 * @param int $id идентификатор автора
	 * @return string
	 */
	public function getFullAuthorName($id) {
		$author = $this->macros('users', 'viewAuthor', [$id]);
		$author = is_array($author) ? $author : [];
		$firstName = isset($author['fname']) ? $author['fname'] : '';
		$lastName = isset($author['lname']) ? $author['lname'] : '';

		if ($firstName || $lastName) {
			return trim(implode(' ', [$firstName, $lastName]));
		}

		return isset($author['nickname']) ? $author['nickname'] : '';
	}

	/**
	 * Возвращает ссылку на страницу с сообщением об успешно отправленной форме
	 * @param int $formId идентификатор отправленной формы
	 * @return string
	 */
	public function getWebformSuccessUrl($formId) {
		$langPrefix = $this->getTemplateEngine()->getCommonVar('pre_lang');
		return "{$langPrefix}/webforms/posted/{$formId}";
	}

	/**
	 * Возвращает путь до главной страницы сайта
	 * @return string
	 */
	public function getHomePageUrl() {
		return $this->getTemplateEngine()->getCommonVar('pre_lang') . '/';
	}

	/**
	 * Возвращает сообщение "Добрый день %Username%"
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *   'user' => [
	 *        'login' => string // логин текущего пользователя
	 *    ]
	 * ]
	 *
	 * @return string
	 */
	public function getWelcomeMessage(array $variables) {
		$welcome = $this->getTemplateEngine()->translate('welcome');
		return implode(' ', [
			$welcome,
			$variables['user']['login'],
		]);
	}

	/**
	 * Можно ли показать текущему пользователю ссылку в админку
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *   'user' => [
	 *        'type' => string // строковой идентификатор типа текущей пользователя
	 *    ]
	 * ]
	 *
	 * @return bool
	 */
	public function canShowAdminLink(array $variables) {
		return in_array($variables['user']['type'], ['admin', 'sv']);
	}

	/**
	 * Возвращает ссылку на административную панель
	 * @return string
	 */
	public function getAdminLink() {
		return $this->getTemplateEngine()->getCommonVar('pre_lang') . '/admin/';
	}

	/**
	 * Возвращает ссылку в личный кабинет пользователя
	 * @return string
	 */
	public function getPersonalLink() {
		return $this->getTemplateEngine()->getCommonVar('pre_lang') . '/emarket/personal/';
	}

	/**
	 * Возвращает ссылку на деавторизацию
	 * @return string
	 */
	public function getLogoutLink() {
		return $this->getTemplateEngine()->getCommonVar('pre_lang') . '/users/logout/';
	}

	/**
	 * Возвращает ссылку страницу сравнения товаров
	 * @return string
	 */
	public function getComparisonLink() {
		return $this->getTemplateEngine()->getCommonVar('pre_lang') . '/emarket/compare/';
	}

	/**
	 * Возвращает список товаров для сравнения
	 * @param array $variables результат работы макроса emarket::compare().
	 *
	 * [
	 *    'headers' => [            // список данных для формирования заголовка таблицы сравнения
	 *        0 => [
	 *            'id' => int       // id страницы
	 *            'link' => string  // адрес (ссылка) страницы
	 *            'title' => string // имя страницы
	 *        ]
	 *    ]
	 * ]
	 *
	 * @return iUmiHierarchyElement[]
	 */
	public function getProductsForComparison(array $variables) {
		$headerList = isset($variables['headers']) ? $variables['headers'] : [];
		$productList = [];

		/** @var array $headerList */
		foreach ($headerList as $header) {
			$productId = isset($header['id']) ? $header['id'] : '';
			$product = $this->getPageById($productId);

			if ($product instanceof iUmiHierarchyElement) {
				$productList[] = $product;
			}
		}

		return array_slice($productList, 0, self::MAX_PRODUCT_COUNT_FOR_COMPARISON);
	}

	/**
	 * Определяет есть ли товары для сравнения
	 * @param array $result результат работы макроса emarket::compare().
	 *
	 * [
	 *    'fields' => [
	 *        0 => []
	 *    ]
	 * ]
	 *
	 * @return bool
	 */
	public function canCompare($result) {
		return is_array($result) && isset($result['fields'][0]);
	}

	/**
	 * Возвращает список значений полей для формирования таблицы сравнения товаров
	 * @param array $fieldDataList список полей
	 *
	 * [
	 *    0 => [
	 *        'title' => string // наименование поля
	 *        'name' => string  // строковый идентификатор поля
	 *        'values' => [
	 *            0 => [
	 *                'value' => iUmiObjectProperty // значение поля одного сравниваемого товара
	 *            ]
	 *            1 => [
	 *                'value' => iUmiObjectProperty // значение поля другого сравниваемого товара
	 *            ]
	 *        ]
	 *    ]
	 * ]
	 *
	 * @return array
	 *
	 * [
	 *    0 => [
	 *        'title' => string  // наименование поля
	 *        'name' => string   // строковый идентификатор поля
	 *        'value' => mixed   // значение поля одного сравниваемого товара
	 *    ],
	 *    1 => [
	 *        'title' => string  // наименование поля
	 *        'name' => string   // строковый идентификатор поля
	 *        'value' => mixed   // значение поля другого сравниваемого товара
	 *    ]
	 * ]
	 */
	public function getColumnList(array $fieldDataList) {
		$productList = isset($fieldDataList[0]['values']) ? $fieldDataList[0]['values'] : [];
		$result = [];

		foreach (array_keys($productList) as $index) {
			$column = [];

			foreach ($fieldDataList as $fieldData) {
				/** @var iUmiObjectProperty|null $property */
				$property = isset($fieldData['values'][$index]['value']) ? $fieldData['values'][$index]['value'] : null;
				$column[] = $this->getPropertyData($property);
			}

			$result[] = $column;
		}

		return array_slice($result, 0, self::MAX_PRODUCT_COUNT_FOR_COMPARISON);
	}

	/**
	 * Фильтрует список полей по поддерживаемым типам полей.
     * @see DemomarketPhpExtension::isAllowedDataType().
	 * @param array $fieldDataList список полей
	 *
	 * [
	 *    'type' => string // строковой идентификатор типа поля
	 * ]
	 *
	 * @return array Возвращает список полей, тип которых поддерживается.
	 * Структура данных сохраняется.
	 */
	public function filterAllowedFieldData(array $fieldDataList) {
		$result = [];

		foreach ($fieldDataList as $fieldData) {
			$type = isset($fieldData['type']) ? $fieldData['type'] : '';

			if ($this->isAllowedDataType($type)) {
				$result[] = $fieldData;
			}
		}

		return $result;
	}

	/**
	 * Определяет может ли текущий пользователь добавить новый пост в блог
	 * @param array $variables данные страницы
	 *
	 * [
	 *    'pageId' => int // id страницы
	 * ]
	 *
	 * @return bool
	 */
	public function canAddNewBlogPost(array $variables) {
		$user = $this->getTemplateEngine()->getCommonVar('user');

		if ($user['type'] === 'sv') {
			return true;
		}

		$ownerId = $this->getBlogOwnerId($variables);
		return $user['id'] == $ownerId;
	}

	/**
	 * Выводит поле с капчей
	 * @return string
	 */
	public function renderCaptcha() {
		return $this->getTemplateEngine()->render(
			$this->macros('system', 'captcha'),
			'library/captcha'
		);
	}

	/**
	 * Выводит action для формы добавления нового поста блога
	 * @param array $variables данные страницы
	 *
	 * [
	 *    'pageId' => int // id страницы
	 * ]
	 *
	 * @return string
	 */
	public function getNewBlogPostFormAction(array $variables) {
		$pageId = isset($variables['pageId']) ? $variables['pageId'] : '';
		return $this->getTemplateEngine()->getCommonVar('pre_lang') . "/blogs20/postAdd/{$pageId}/";
	}

	/**
	 * Возвращает контент поста блога
	 * @param array $variables данные страницы
	 *
	 * [
	 *    'pageId' => int // id страницы
	 * ]
	 *
	 * @return string
	 */
	public function getPostContent(array $variables) {
		$pageId = isset($variables['pageId']) ? $variables['pageId'] : '';
		$postData = $this->macros('blogs20', 'postView', [$pageId]);

		if (isset($postData['content'])) {
			return $postData['content'];
		}

		$page = $this->getPageById($pageId);

		if ($page instanceof iUmiHierarchyElement) {
			return $page->getValue('content');
		}

		return '';
	}

	/**
	 * Выводит блок с комментариями
	 * @param array $variables данные страницы
	 *
	 * [
	 *    'pageId' => int // id страницы
	 * ]
	 *
	 * @return string
	 */
	public function renderComments(array $variables) {
		$pageId = isset($variables['pageId']) ? $variables['pageId'] : '';
		return $this->getTemplateEngine()->render(
			$this->macros('comments', 'insert', [$pageId]),
			'comments/insert'
		);
	}

	/**
	 * Возвращает форматированную дату публикации страницы
	 * @param iUmiHierarchyElement|array $page страница или ее данные
	 *
	 * [
	 *    'publish_time' => int // дата публикации
	 * ]
	 *
	 * @return string
	 */
	public function getFormattedPublishTime($page) {
		if ($page instanceof iUmiHierarchyElement) {
			$date = $page->getValue('publish_time');
		} else {
			$date = isset($page['publish_time']) ? $page['publish_time'] : 0;
		}

		$timeStamp = ($date instanceof umiDate) ? $date->getDateTimeStamp() : $date;
		return date('d.m.Y H:i', $timeStamp);
	}

	/**
	 * Возвращает css-класс для кнопок покупки товара в зависимости от его наличия
	 * @param iUmiHierarchyElement $product товар
	 * @return string
	 */
	public function getBuyButtonClass(iUmiHierarchyElement $product) {
		return $this->isInStock($product) ? '' : 'not_buy';
	}

	/**
	 * Определяет нужно ли показывать пагинацию на странице
	 * @param array $variables данные шаблона пагинации
	 *
	 * [
	 *    'total' => int,     // количество элементов
	 *    'per_page' => int,  // количество элементов на одну страницу
	 * ]
	 *
	 * @return bool
	 */
	public function canShowPagination(array $variables) {
		return
			isset($variables['total']) &&
			isset($variables['per_page']) &&
			$variables['total'] > $variables['per_page'];
	}

	/**
	 * Возвращает атрибут action для формы опроса
	 * @return string
	 */
	public function getVoteFormAction() {
		$langPrefix = $this->getTemplateEngine()->getCommonVar('pre_lang');
		return "{$langPrefix}/vote/post/";
	}

	/**
	 * Делает редирект на предыдущую страницу
	 * @return void
	 */
	public function redirectBack() {
		/** @var HTTPOutputBuffer $buffer */
		$buffer = outputBuffer::current('HTTPOutputBuffer');
		$buffer->redirect(getServer('HTTP_REFERER'));
	}

	/**
	 * Возвращает объект настроек сайта
	 * @return iUmiObject|bool
	 */
	public function getSettingsContainer() {
		/** @var umiSettings|UmiSettingsMacros $settings */
		$settings = cmsController::getInstance()
			->getModule('umiSettings');

		$settingsContainerId = $settings->getIdByCustomId('settings');

		return umiObjectsCollection::getInstance()
			->getObject($settingsContainerId);
	}

	/**
	 * Возвращает action для формы оплаты заказа бонусами
	 * @return string
	 */
	public function getBonusPaymentAction() {
		return $this->getCurrentPath() . '/do/';
	}

	/**
	 * Определяет оформился ли заказ
	 * @param array $variables данные макроса оформления заказа
	 *
	 * [
	 *    'purchasing' => [
	 *        'order_id' => int // идентификатор заказа
	 *    ]
	 * ]
	 * @return bool
	 */
	public function orderWasPlaced(array $variables) {
		return isset($variables['purchasing']['order_id']);
	}

	/**
	 * Возвращает объект оформленного заказа
	 * @param array $variables данные макроса оформления заказа
	 *
	 * [
	 *    'purchasing' => [
	 *        'order_id' => int // идентификатор заказа
	 *    ]
	 * ]
	 *
	 * @return iUmiObject|bool
	 */
	public function getPlacedOrder(array $variables) {
		$orderId = isset($variables['purchasing']['order_id']) ? $variables['purchasing']['order_id'] : '';
		return $this->getObjectById($orderId);
	}

	/**
	 * Включена ли на сайте Google ReCaptcha
	 * @return bool
	 */
	public function isRecaptchaEnabled() {
		return (bool) regedit::getInstance()->getVal('//settings/enable-recaptcha');
	}

	/**
	 * Возвращает url скрипта recaptcha
	 * @param array $variables описание страницы
	 *
	 * [
	 *    'lang' => string // код языка
	 * ]
	 *
	 * @return string
	 */
	public function getRecaptchaUrl(array $variables) {
		$lang = isset($variables['lang']) ? $variables['lang'] : 'ru';
		return "https://www.google.com/recaptcha/api.js?onload=CaptchaCallback&render=explicit&hl={$lang}";
	}

	/**
	 * Возвращает параметр sitekey для recaptcha
	 * @return string
	 */
	public function getRecaptchaSiteKey() {
		return (string) regedit::getInstance()->getVal('//settings/recaptcha-sitekey');
	}

	/**
	 * Возвращает redirect url для скрипта ulogin.js
	 * @return string
	 */
	public function getULoginRedirectUrl() {
		$cmsController = cmsController::getInstance();
		$host = $cmsController->getCurrentDomain()->getHost();
		$url = getSelectedServerProtocol() . '://' . $host . '/users/ulogin/';
		return urlencode($url);
	}

	/**
	 * Возвращает url для формы оформления заказа в один клик
	 * @return string
	 */
	public function getOneClickOrderUrl() {
		return $this->getTemplateEngine()->getCommonVar('pre_lang') . '/emarket/getOneClickOrder/';
	}

	/**
	 * Возвращает сообщение в случае успешного оформления заказа в один клик
	 * @param int|string $orderNumber номер заказа
	 * @return string
	 */
	public function getOneClickOrderSuccessMessage($orderNumber) {
		$message = $this->getTemplateEngine()->translate('one_click_order_success');
		return sprintf($message, $orderNumber);
	}

	/**
	 * Возвращает данные баннера на главной странице
	 * @return array
	 *
	 * [
	 *    'id' => int,                  // id
	 *    'name' => string,             // имя
	 *    'width' => int,               // ширина изображения
	 *    'height' => int,              // высота изображения
	 *    'alt' => string,              // alt изображения
	 *    'image' => string,            // путь до изображения
	 *    'url' => string,              // ссылка
	 *    'target' => string,           // target для ссылки
	 *    'show_till_date' => string,   // дата окончания показа баннера
	 * ]
	 */
	public function getBanner() {
		$placeName = 'homepage';
		$data = $this->macros('banners', 'fastInsert', [$placeName]);
		$data = is_array($data) ? $data : [];

		$bannerId = isset($data['id']) ? $data['id'] : '';
		$banner = $this->getObjectById($bannerId);

		$result = [
			'id' => $bannerId,
			'name' => '',
			'width' => '',
			'height' => '',
			'alt' => '',
			'image' => '',
			'url' => '',
			'target' => '',
			'show_till_date' => '',
		];

		$timeStamp = 0;

		if ($banner instanceof iUmiObject) {
			$result['name'] = $banner->getName();
			$date = $banner->getValue('show_till_date');
			$timeStamp = ($date instanceof umiDate) ? $date->getDateTimeStamp() : $date;
		}

		$result['show_till_date'] = date('d.m.Y', $timeStamp);
		$result['width'] = isset($data['banner']['width']) ? $data['banner']['width'] : '';
		$result['height'] = isset($data['banner']['height']) ? $data['banner']['height'] : '';
		$result['alt'] = isset($data['banner']['alt']) ? $data['banner']['alt'] : '';
		$result['image'] = isset($data['banner']['source']) ? $data['banner']['source'] : '';
		$result['url'] = isset($data['banner']['href']) ? $data['banner']['href'] : '';
		$result['target'] = isset($data['banner']['target']) ? $data['banner']['target'] : '';
		return $result;
	}

	/**
	 * Возвращает строку с ценой опционного свойства
	 * @param array $option значение опционного свойства
	 *
	 * [
	 *    'float' => float // значения поля "число с точкой" опции
	 * ]
	 *
	 * @return string
	 */
	public function getFormattedOptionPrice(array $option) {
		$cart = $this->getTemplateEngine()->getCommonVar('cart');
		$prefix = isset($cart['summary']['price']['prefix']) ? $cart['summary']['price']['prefix'] : '';
		$price = isset($option['float']) ? $option['float'] : '';
		$suffix = isset($cart['summary']['price']['suffix']) ? $cart['summary']['price']['suffix'] : '';

		return implode(' ', [
			'+',
			$prefix,
			$price,
			$suffix,
		]);
	}

	/**
	 * Возвращает action для формы выбора способа доставки
	 * @return string
	 */
	public function getDeliveryChooseUrl() {
		return $this->getTemplateEngine()->getCommonVar('pre_lang') . '/emarket/purchase/delivery/choose/do/';
	}

	/**
	 * Определяет является ли способ переданной доставки способом ApiShip
	 * @param array $delivery описание доставки
	 *
	 * [
	 *    'type-class-name' => string // строковой идентификатор типа доставки
	 * ]
	 *
	 * @return bool
	 */
	public function isApiShipDelivery(array $delivery) {
		return isset($delivery['type-class-name']) && $delivery['type-class-name'] === 'ApiShip';
	}

	/**
	 * Возвращает строку с ценой способа доставки
	 * @param array $delivery описание доставки
	 *
	 * [
	 *    'price' => float // стоимость доставки
	 * ]
	 *
	 * @return string
	 */
	public function getFormattedDeliveryPrice(array $delivery) {
		$price = isset($delivery['price']) ? $delivery['price'] : 0;
		return $this->getTemplateEngine()->render(
			$this->macros('emarket', 'applyPriceCurrency', [$price]),
			'emarket/price'
		);
	}

	/**
	 * Возвращает статус радиокнопки по активности элемента
	 * @param array $item описание кнопки
	 *
	 * [
	 *    'active' => string // активна (выбрана) ли кнопка
	 * ]
	 *
	 * @return string
	 */
	public function getRadioStatusByActivity(array $item) {
		if (cmsController::getInstance()->getCurrentMethod() == 'purchasing_one_step') {
			return '';
		}

		if (array_key_exists('active', $item)) {
			return (isset($item['active']) && $item['active']  === 'active') ? 'checked' : '';
		}

		if (array_key_exists('checked', $item)) {
			return (isset($item['checked']) && $item['checked']  === 'checked') ? 'checked' : '';
		}

		return '';
	}

	/**
	 * Возвращает action для формы оформления заказа в один шаг
	 * @return string
	 */
	public function getPurchasingOneStepUrl() {
		return $this->getTemplateEngine()->getCommonVar('pre_lang') . '/emarket/saveInfo/';
	}

	/**
	 * Возвращает путь до шаблона текущего шага оформления заказа
	 * @param array $purchase данные макроса оформления заказа
	 *
	 * [
	 *    'purchasing' => [
	 *          'stage' => string, // название этапа оформления заказа
	 *          'step' => string,  // название шага оформления заказа
	 *     ]
	 * ]
	 *
	 * @return string
	 */
	public function getPurchaseTemplate(array $purchase) {
		return implode('/', [
			'emarket',
			$purchase['purchasing']['stage'],
			$purchase['purchasing']['step']
		]);
	}

	/**
	 * Возвращает action для шага оформления заказа "Личная информация"
	 * @return string
	 */
	public function getPersonalFormUrl() {
		return $this->getTemplateEngine()->getCommonVar('pre_lang') . '/emarket/purchase/required/personal/do/';
	}

	/**
	 * Возвращает action для шага оформления заказа "Адрес доставки"
	 * @return string
	 */
	public function getDeliveryAddressFormUrl() {
		return $this->getTemplateEngine()->getCommonVar('pre_lang') . '/emarket/purchase/delivery/address/do/';
	}

	/**
	 * Возвращает сообщение об ошибке
	 * @param mixed $error
	 * @return string
	 */
	public function getErrorMessage($error) {
		if (is_array($error)) {
			return isset($error['message']) ? (string) $error['message'] : '';
		}

		return (string) $error;
	}

	/**
	 * Возвращает action для формы добавления комментария к посту блога
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *    'pageId' => идентификатор блога
	 * ]
	 *
	 * @return string
	 */
	public function getNewBlogCommentUrl(array $variables) {
		$pageId = isset($variables['pageId']) ? $variables['pageId'] : '';
		return "/blogs20/commentAdd/{$pageId}/";
	}

	/**
	 * Возвращает атрибут поля для фильтрации
	 * @param array $data данные поля
	 * @param string $attribute имя атрибута
	 * @return string
	 */
	public function getFilterFieldAttribute(array $data, $attribute) {
		return isset($data[$attribute]) ? $data[$attribute] : 'undefined';
	}

	/**
	 * Определяет нужно ли для поля фильтрации выводить чекбокс
	 * @param array $variables описание поля
	 *
	 * [
	 *    'data-type' => string // строковый идентификатор типа поля
	 * ]
	 *
	 * @return bool
	 */
	public function isCheckboxFilterField(array $variables) {
		$dataType = $this->getFilterFieldAttribute($variables, 'data-type');
		return in_array($dataType, [
			'string',
			'color',
			'password',
			'optioned',
			'tags',
			'symlink',
			'text',
			'wysiwyg',
			'relation',
		]);
	}

	/**
	 * Определяет нужно ли для поля фильтрации выводить радиокнопку
	 * @param array $variables описание поля
	 *
	 * [
	 *    'data-type' => string // строковый идентификатор типа поля
	 * ]
	 *
	 * @return bool
	 */
	public function isRadioFilterField(array $variables) {
		$dataType = $this->getFilterFieldAttribute($variables, 'data-type');
		return in_array($dataType, [
			'boolean',
			'file',
			'img_file',
			'swf_file',
			'video_file',
		]);
	}

	/**
	 * Определяет нужно ли для поля фильтрации выводить интервал значений
	 * @param array $variables описание поля
	 *
	 * [
	 *    'data-type' => string // строковый идентификатор типа поля
	 * ]
	 *
	 * @return bool
	 */
	public function isIntervalFilterField(array $variables) {
		$dataType = $this->getFilterFieldAttribute($variables, 'data-type');
		return in_array($dataType, [
			'date',
			'int',
			'price',
			'float',
			'counter',
		]);
	}

	/**
	 * Возвращает данные для отдельной опции поля фильтрации с чекбоксом
	 * @param array $variables описание значений булевого поля
	 *
	 * [
	 *    0 => string, // имя поля
	 *    1 => string, // индекс
	 *    2 => string, // значения поля
	 * ]
	 *
	 * @return array
	 *
	 * [
	 *    'id' => string    // идентификатор поля для шаблона
	 *    'name' => string  // пример вызова фильтра
	 *    'value' => string // значение поля
	 * ]
	 */
	public function getCheckboxFilterFieldData(array $variables) {
		list($fieldName, $index, $option) = $variables;

		return [
			'id' => "{$fieldName}_{$index}",
			'name' => "filter[{$fieldName}][{$index}]",
			'value' => isset($option['value']) ? $option['value'] : 'undefined',
            'is-selected' => isset($option['is-selected']) ? $option['is-selected'] : false,
		];
	}

	/**
	 * Возвращает данные для поля фильтрации со слайдером
	 * @param array $variables описание значений числового поля
	 *
	 * [
	 *   'title' => string,     // наименование поля
	 *   'name' => string,      // строковой идентификатор поля
	 *   'minimum' => [
	 *       'value' => int     // минимальное значение поля
	 *       'selected' => int  // минимальное выбранное значение поля
	 *    ]
	 *   'maximum' => [
	 *       'value' => int     // максимальное значение поля
	 *       'selected' => int  // максимальное выбранное значение поля
	 *    ]
	 *    'item' => [
	 *       'value' => int     // единственное значение
	 *    ]
	 * ]
	 *
	 * @return array
	 *
	 * [
	 *   'title' => string,                      // имя поля
	 *   'name_from' => "filter[@title][from]",  // пример вызова фильтра для фильтрации "от"
	 *   'name_to' => "filter[@title]][to]",     // пример вызова фильтра для фильтрации "до"
	 *   'selected_min' => int,                  // выбранная минимальная дата
	 *   'selected_max' => int,                  // выбранная максимальная дата
	 *   'min' => int,                           // минимальная дата
	 *   'max' => int,                           // максимальная дата
	 * ]
	 */
	public function getSliderFilterFieldData(array $variables) {
		$fieldTitle = $this->getFilterFieldAttribute($variables, 'title');
		$fieldName = $this->getFilterFieldAttribute($variables, 'name');
		$min = $max = $selectedMin = $selectedMax = 0;

		switch (true) {
			case (isset($variables['minimum']) && isset($variables['maximum'])): {
				$minData = $variables['minimum'];
				$maxData = $variables['maximum'];

				$min = isset($minData['value']) ? $minData['value'] : 0;
				$max = isset($maxData['value']) ? $maxData['value'] : 0;
				$selectedMin = isset($minData['selected']) ? $minData['selected'] : $min;
				$selectedMax = isset($maxData['selected']) ? $maxData['selected'] : $max;
				break;
			}

			case (isset($variables['item'])): {
				$item = $variables['item'];
				$oneValue = (isset($item['value'])) ? $item['value'] : 0;
				$min = $max = $selectedMin = $selectedMax = $oneValue;
				break;
			}
		}

		return [
			'title' => $fieldTitle,
			'name_from' => "filter[{$fieldName}][from]",
			'name_to' => "filter[{$fieldName}][to]",
			'selected_min' => $selectedMin,
			'selected_max' => $selectedMax,
			'min' => $min,
			'max' => $max,
		];
	}

	/**
	 * Возвращает данные для поля фильтрации типа "Дата".
	 * Меняет таймстампы на дату в формате m.d.y.
	 * @param array $variables описание значений поля типа "дата"
	 *
	 * [
	 *   'title' => string,     // наименование поля
	 *   'name' => string,      // строковой идентификатор поля
	 *   'minimum' => [
	 *       'value' => int     // минимальное значение поля
	 *       'selected' => int  // минимальное выбранное значение поля
	 *    ]
	 *   'maximum' => [
	 *       'value' => int     // максимальное значение поля
	 *       'selected' => int  // максимальное выбранное значение поля
	 *    ]
	 *    'item' => [
	 *       'value' => int     // единственное значение
	 *    ]
	 * ]
	 *
	 * @return array
	 *
	 * [
	 *   'selected_min' => string, // выбранная минимальная дата
	 *   'min' => string,          // минимальная дата
	 *   'selected_max' => string, // выбранная максимальная дата
	 *   'max' => string,          // максимальная дата
	 * ]
	 */
	public function getDateFilterFieldData(array $variables) {
		$data = $this->getSliderFilterFieldData($variables);
		$format = 'm.d.y';

		$selectedMinDate = new umiDate($data['selected_min']);
		$data['selected_min'] = $selectedMinDate->getFormattedDate($format);

		$minDate = new umiDate($data['min']);
		$data['min'] = $minDate->getFormattedDate($format);

		$selectedMaxDate = new umiDate($data['selected_max']);
		$data['selected_max'] = $selectedMaxDate->getFormattedDate($format);

		$maxDate = new umiDate($data['max']);
		$data['max'] = $maxDate->getFormattedDate($format);

		return $data;
	}

	/** Отправляет статус 404 */
	public function send404Status() {
		/** @var HTTPOutputBuffer $buffer */
		$buffer = outputBuffer::current('HTTPOutputBuffer');
		$buffer->status(404);
	}

	/**
	 * Возвращает список полей товара типа "опционное"
	 * @param iUmiHierarchyElement $product товар
	 * @return iUmiField[]
	 */
	public function getOptionedFieldList(iUmiHierarchyElement $product) {
		$object = $product->getObject();

		if (!$object instanceof iUmiObject) {
			return [];
		}

		$type = $object->getType();

		if (!$type instanceof iUmiObjectType) {
			return [];
		}

		$group = $type->getFieldsGroupByName('catalog_option_props');
		return ($group instanceof iUmiFieldsGroup) ? $group->getFields() : [];
	}

	/**
	 * Выводит рейтинг товара (пять звездочек).
	 * Для переключения режима рейтингования на 5-балльный нужно включить опцию в Настройках модуля "Опросы".
	 *
	 * @param int $productId ID товара
	 * @return mixed
	 */
	public function renderProductRating($productId) {
		$template = null;
		return $this->getTemplateEngine()->render(
			$this->macros('vote', 'getElementRating', [$template, $productId]),
			'vote/element_rating'
		);
	}

	/**
	 * Возвращает путь до картинки "фото временно отсутствует"
	 * @return string
	 */
	public function getNoPhotoPath() {
		return $this->noPhotoPath;
	}

	/**
	 * Является ли текущий пользователь гостем
	 * @return bool
	 */
	public function isGuest() {
		$user = $this->getTemplateEngine()->getCommonVar('user');
		return $user['type'] === 'guest';
	}

	/**
	 * Может ли текущий пользователь добавить комментарий на сайт
	 * @param array $variables результат работы макроса `comment/insert`
	 * @return bool
	 */
	public function canComment(array $variables) {
		return isset($variables['add_form']['action']);
	}

	/**
	 * Возвращает action для формы добавления нового комментария
	 * @param array $variables результат работы макроса `comment/insert`
	 * @return string
	 */
	public function getNewCommentUrl(array $variables) {
		return isset($variables['add_form']['action']) ? $variables['add_form']['action'] : '';
	}

	/**
	 * Возвращает url для отрисовки классической капчи
	 * @param array $variables результат работы макроса `system/captcha`
	 * @return string
	 */
	public function getClassicCaptchaUrl(array $variables) {
		$value = isset($variables['url']['value']) ? $variables['url']['value'] : '';
		$random = isset($variables['url']['random-string']) ? $variables['url']['random-string'] : '';
		return $value . $random;
	}

	/**
	 * Может ли текущий пользователь добавить новый топик форума
	 * @param array $variables результат работы макроса `forum/topic_post`
	 * @return bool
	 */
	public function canAddNewForumTopic(array $variables) {
		return isset($variables['action']);
	}

	/**
	 * Возвращает action для формы добавления нового топика форума
	 * @param array $variables результат работы макроса `forum/topic_post`
	 * @return string
	 */
	public function getNewForumTopicUrl(array $variables) {
		return isset($variables['action']) ? $variables['action'] : '';
	}

	/**
	 * Может ли текущий пользователь добавить новое сообщение в топик форума
	 * @param array $variables результат работы макроса `forum/message_post`
	 * @return bool
	 */
	public function canAddNewForumMessage(array $variables) {
		return isset($variables['action']);
	}

	/**
	 * Возвращает action для формы добавления нового сообщения в топик форума
	 * @param array $variables результат работы макроса `forum/message_post`
	 * @return string
	 */
	public function getNewForumMessageUrl(array $variables) {
		return isset($variables['action']) ? $variables['action'] : '';
	}

	/**
	 * Может ли текущий пользователь добавить новый комментарий в пост блога
	 * @return bool
	 */
	public function canAddNewBlogComment() {
		return (bool) $this->macros('blogs20', 'checkAllowComments');
	}

	/**
	 * Возвращает url карты сайта
	 * @return string
	 */
	public function getSiteMapUrl() {
		$langPrefix = $this->getTemplateEngine()->getCommonVar('pre_lang');
		return "{$langPrefix}/content/sitemap/";
	}

	/**
	 * Возвращает страницу 404
	 * @return bool|iUmiHierarchyElement
	 */
	public function get404Page() {
		$langPrefix = $this->getTemplateEngine()->getCommonVar('pre_lang');
		return $this->getPageByPath("{$langPrefix}/notfound/");
	}

	/**
	 * Является ли текущая страница страницей по умолчанию
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *     'is-default' => bool флаг страницы по умолчанию
	 * ]
	 *
	 * @return bool
	 */
	public function isHomePage(array $variables) {
		return (bool) (isset($variables['is-default']) ? $variables['is-default'] : false);
	}

	/**
	 * Является ли текущая страница страницей 404
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *     'page' => iUmiHierarchyElement текущая страница
	 * ]
	 *
	 * @return bool
	 */
	public function is404Page(array $variables) {
		$page = isset($variables['page']) ? $variables['page'] : null;

		if ($page instanceof iUmiHierarchyElement) {
			return $page->getAltName() === 'notfound';
		}

		return false;
	}

	/**
	 * Возвращает список новостей на главной странице (максимум 2 новости)
	 * @return array результат работы макроса news::lastlist()
	 */
	public function getLastNews() {
		$path = 'news1';
		$template = null;
		$perPage = 2;
		$ignorePaging = true;

		$data = $this->macros('news', 'lastlist', [$path, $template, $perPage, $ignorePaging]);
		$data = is_array($data) ? $data : [];
		$data['items'] = isset($data['items']) ? $data['items'] : [];
		return $data;
	}

	/**
	 * Возвращает данные для отрисовки слайдера на главной странице
	 * @return array результат работы макроса umiSliders::getSlidesBySliderName()
	 *
	 * [
	 *     'slider_id' => int идентификатор слайдера
	 *     'slides' => [
	 *         'link' => string ссылка в слайде
	 *         'target' => атрибут target для ссылки
	 *         'image' => string картинка слайда
	 *         'text' => string текст слайда
	 *     ]
	 * ]
	 */
	public function getSlides() {
		$template = null;
		$sliderCustomId = 'main';
		$data = $this->macros('umiSliders', 'getSlideListBySliderCustomId', [$template, $sliderCustomId]);
		$data = is_array($data) ? $data : [];

		$sliderId = isset($data['id']) ? $data['id'] : '';
		$slideList = isset($data['slides']) ? $data['slides'] : [];

		/** @var array $slideList */
		foreach ($slideList as &$slide) {
			$slide['link'] = isset($slide['link']) ? $slide['link'] : '';
			$openLinkInNewTab = isset($slide['open_in_new_tab']) ? (bool) $slide['open_in_new_tab'] : false;
			$slide['target'] = $openLinkInNewTab ? 'target="_blank"' : '';
			$slide['image'] = isset($slide['image']) ? $slide['image'] : '';
			$slide['text'] = isset($slide['text']) ? $slide['text'] : '';
		}

		return [
			'slider_id' => $sliderId,
			'slides' => $slideList,
		];
	}

	/**
	 * Возвращает css-класс для слайда по его позиции
	 * @param int $index позиция слайда
	 * @return string
	 */
	public function getSlideClassByPosition($index) {
		return ($index === 0) ? 'active' : '';
	}

	/**
	 * Возвращает список топиков в текущей конференции форума
	 * @return array результат работы макроса forum::conf()
	 */
	public function getTopics() {
		$data = $this->macros('forum', 'conf');
		$data = is_array($data) ? $data : [];
		$data['lines'] = isset($data['lines']) ? $data['lines'] : [];
		return $data;
	}

	/**
	 * Возвращает список комментариев к текущему посту блога
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *     'pageId' => идентификатор текущего поста блога
	 * ]
	 *
	 * @return array результат работы макроса blogs20::commentsList()
	 */
	public function getBlogPostComments(array $variables) {
		$postId = $variables['pageId'];
		$data = $this->macros('blogs20', 'commentsList', [$postId]);
		$data = is_array($data) ? $data : [];
		$data['items'] = isset($data['items']) ? $data['items'] : [];

		foreach ($data['items'] as &$comment) {
			$comment['cid'] = isset($comment['cid']) ? $comment['cid'] : '';
			$comment['author_id'] = isset($comment['author_id']) ? $comment['author_id'] : '';
			$comment['content'] = isset($comment['content']) ? $comment['content'] : '';
			$comment['publish_time'] = isset($comment['publish_time']) ? $comment['publish_time'] : '';
		}

		return $data;
	}

	/**
	 * Возвращает список постов текущего блога
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *     'pageId' => идентификатор текущего блога
	 * ]
	 *
	 * @return array результат работы макроса blogs20::getPostsList()
	 */
	public function getBlogPosts(array $variables) {
		$blogId = $variables['pageId'];
		$data = $this->macros('blogs20', 'getPostsList', [$blogId]);
		$data = is_array($data) ? $data : [];
		$data['lines'] = isset($data['lines']) ? $data['lines'] : [];

		foreach ($data['lines'] as &$post) {
			$post['id'] = isset($post['id']) ? $post['id'] : '';
			$post['name'] = isset($post['name']) ? $post['name'] : '';
			$post['cut'] = isset($post['cut']) ? $post['cut'] : '';
			$post['post_link'] = isset($post['post_link']) ? $post['post_link'] : $this->getHomePageUrl();
			$post['comments_count'] = isset($post['comments_count']) ? $post['comments_count'] : 0;
		}

		return $data;
	}

	/**
	 * Возвращает данные последнего сообщения в топике форума
	 * @param array $topic данные топика
	 *
	 * [
	 *     'id' => идентификатор топика
	 * ]
	 *
	 * @return array результат работы макроса forum::topic_last_message()
	 */
	public function getLastTopicMessage($topic) {
		$topicId = isset($topic['id']) ? $topic['id'] : '';
		$data = $this->macros('forum', 'topic_last_message', [$topicId]);
		$data = is_array($data) ? $data : [];
		$data['id'] = isset($data['id']) ? $data['id'] : '';
		$data['name'] = isset($data['name']) ? $data['name'] : '';
		$data['link'] = isset($data['link']) ? $data['link'] : '';
		return $data;
	}

	/**
	 * Возвращает сообщение о результате восстановления пароля по ссылке из письма
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *     'data' => [
	 *         'status' => string статус результата восстановления пароля
	 *     ]
	 * ]
	 *
	 * @return string
	 */
	public function getPasswordRestoreMessage(array $variables) {
		$status = isset($variables['data']['status']) ? $variables['data']['status'] : '';
		$messageLabel = ($status === 'success') ? 'password_was_sent' : 'wrong_activation_code';
		return $this->getTemplateEngine()->translate($messageLabel);
	}

	/**
	 * Возвращает сообщение о результате отправки запроса на восстановление пароля
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *     'data' => string статус результата отправки запроса на восстановление пароля
	 * ]
	 *
	 * @return string
	 */
	public function getPasswordForgetDoMessage(array $variables) {
		$data = isset($variables['data']) ? $variables['data'] : '';
		$messageLabel = ($data === 'success') ? 'forget_do_success' : 'forget_do_fail';
		return $this->getTemplateEngine()->translate($messageLabel);
	}

	/**
	 * Возвращает данные для способа оплаты "КупиВКредит"
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *   'purchasing' => [
	 *       'totalPrice' => float Стоимость заказа
	 *       'order' => mixed Элементы заказа
	 *       'sig' => string Подпись запроса
	 *    ]
	 * ]
	 *
	 * @return array
	 *
	 * [
	 *   'totalPrice' => float Стоимость заказа
	 *   'order' => mixed Элементы заказа
	 *   'sig' => string Подпись запроса
	 * ]
	 */
	public function getKupiVKreditParams(array $variables) {
		$price = isset($variables['purchasing']['totalPrice']) ? $variables['purchasing']['totalPrice'] : 0;
		$order = isset($variables['purchasing']['order']) ? $variables['purchasing']['order'] : '';
		$signature = isset($variables['purchasing']['sig']) ? $variables['purchasing']['sig'] : '';

		return [
			'totalPrice' => $price,
			'order' => $order,
			'sig' => $signature,
		];
	}

	/**
	 * Возвращает url js-виджета "КупиВКредит"
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *   'purchasing' => [
	 *       'test-mode' => bool Работает ли способ оплаты в тестовом режиме
	 *    ]
	 * ]
	 *
	 * @return string
	 */
	public function getKupiVKreditWidgetUrl(array $variables) {
		$testMode = isset($variables['purchasing']['test-mode']) ? (bool) $variables['purchasing']['test-mode'] : false;

		if ($testMode) {
			return kupivkreditPayment::getTestWidgetUrl();
		}

		return kupivkreditPayment::getProductionWidgetUrl();
	}

	/**
	 * Возвращает список категорий проекта в модуле FAQ
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *     'pageId' => идентификатор проекта в модуле FAQ
	 * ]
	 *
	 * @return array результат работы макроса faq::project()
	 */
	public function getFaqCategories(array $variables) {
		$template = null;
		$projectId = isset($variables['pageId']) ? $variables['pageId'] : '';

		$data = $this->macros('faq', 'project', [$template, $projectId]);
		$data = is_array($data) ? $data : [];
		$data['lines'] = isset($data['lines']) ? $data['lines'] : [];

		foreach ($data['lines'] as &$category) {
			$category['id'] = isset($category['id']) ? $category['id'] : '';
			$category['name'] = isset($category['name']) ? $category['name'] : '';
			$category['link'] = isset($category['link']) ? $category['link'] : $this->getHomePageUrl();
			$category['content'] = isset($category['content']) ? $category['content'] : '';
		}

		return $data;
	}

	/**
	 * Возвращает список вопросов категории в модуле "FAQ".
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *     'pageId' => идентификатор категории в модуле FAQ
	 * ]
	 *
	 * @return array результат работы макроса faq::category()
	 */
	public function getFaqQuestions(array $variables) {
		$template = null;
		$categoryId = isset($variables['pageId']) ? $variables['pageId'] : '';

		$data = $this->macros('faq', 'category', [$template, $categoryId]);
		$data = is_array($data) ? $data : [];
		$data['lines'] = isset($data['lines']) ? $data['lines'] : [];

		foreach ($data['lines'] as &$question) {
			$question['question'] = isset($question['question']) ? $question['question'] : '';
			$question['answer'] = isset($question['answer']) ? $question['answer'] : '';
		}

		return $data;
	}

	/**
	 * Возвращает атрибут action для формы добавления нового вопроса в модуле FAQ
	 * @param array $variables глобальные переменные запроса
	 *
	 * [
	 *     'pageId' => идентификатор категории в модуле FAQ
	 * ]
	 *
	 * @return string
	 */
	public function getNewFaqQuestionUrl(array $variables) {
		$template = null;
		$categoryId = isset($variables['pageId']) ? $variables['pageId'] : '';
		$data = $this->macros('faq', 'addQuestionForm', [$template, $categoryId]);
		$data = is_array($data) ? $data : [];
		return isset($data['action']) ? $data['action'] : '';
	}

	/**
	 * Возвращает последний опрос на сайте (результат работы макроса vote::insertlast())
	 * @return array
	 */
	public function getLastVote() {
		$data = [];

		try {
			$data = $this->macros('vote', 'insertlast');
		} catch (RuntimeException $e) {
			// модуль vote не установлен
		}

		return $data;
	}

	/**
	 * Возвращает параметры способа оплаты "PayAnyWay"
	 *
	 * @param array $variables результат работы макроса emarket::purchase()
	 *
	 * [
	 *     'purchasing' => [
	 *         'formAction' => string action формы оплаты,
	 *         'mntId' => string ID магазина,
	 *         'mnTransactionId' => string ID транзакции,
	 *         'mntCurrencyCode' => string код валюты,
	 *         'mntAmount' => string стоимость заказа,
	 *         'mntTestMode' => int флаг тестового режима оплаты,
	 *         'mntSignature' => string md5-хеш транзакции,
	 *         'mntSuccessUrl' => string адрес редиректа для успешной оплаты,
	 *         'mntFailUrl' => string адрес редиректа для неуспешной оплаты,
	 *     ]
	 * ]
	 *
	 * @return array
	 *
	 * [
	 *         'formAction' => string action формы оплаты,
	 *         'mntId' => string ID магазина,
	 *         'mnTransactionId' => string ID транзакции,
	 *         'mntCurrencyCode' => string код валюты,
	 *         'mntAmount' => string стоимость заказа,
	 *         'mntTestMode' => int флаг тестового режима оплаты,
	 *         'mntSignature' => string md5-хеш транзакции,
	 *         'mntSuccessUrl' => string адрес редиректа для успешной оплаты,
	 *         'mntFailUrl' => string адрес редиректа для неуспешной оплаты,
	 * ]
	 */
	public function getPayAnyWayParams(array $variables) {
		$variables = isset($variables['purchasing']) ? $variables['purchasing'] : [];
		$variables['formAction'] = isset($variables['formAction']) ? $variables['formAction'] : '';
		$variables['mntId'] = isset($variables['mntId']) ? $variables['mntId'] : '';
		$variables['mnTransactionId'] = isset($variables['mnTransactionId']) ? $variables['mnTransactionId'] : '';
		$variables['mntCurrencyCode'] = isset($variables['mntCurrencyCode']) ? $variables['mntCurrencyCode'] : '';
		$variables['mntAmount'] = isset($variables['mntAmount']) ? $variables['mntAmount'] : '';
		$variables['mntTestMode'] = isset($variables['mntTestMode']) ? $variables['mntTestMode'] : '';
		$variables['mntSignature'] = isset($variables['mntSignature']) ? $variables['mntSignature'] : '';
		$variables['mntSuccessUrl'] = isset($variables['mntSuccessUrl']) ? $variables['mntSuccessUrl'] : '';
		$variables['mntFailUrl'] = isset($variables['mntFailUrl']) ? $variables['mntFailUrl'] : '';
		return $variables;
	}

	/**
	 * Возвращает параметры способа оплаты "Деньги Online"
	 *
	 * @param array $variables результат работы макроса emarket::purchase()
	 *
	 * [
	 *     'purchasing' => [
	 *         'formAction' => string action формы оплаты,
	 *         'project' => string ID проекта,
	 *         'amount' => string стоимость заказа,
	 *         'nickname' => string ID заказа,
	 *         'source' => string поле "Source",
	 *         'order_id' => string ID заказа,
	 *         'comment' => string Комментарий,
	 *         'paymentCurrency' => string код валюты,
	 *
	 *         'items' => [
	 *             # => [
	 *                 'id' => int ID варианта оплаты,
	 *                 'label' => string название варианта оплаты,
	 *             ]
	 *         ]
	 *     ]
	 * ]
	 *
	 * @return array
	 *
	 * [
	 *         'formAction' => string action формы оплаты,
	 *         'project' => string ID проекта,
	 *         'amount' => string стоимость заказа,
	 *         'nickname' => string ID заказа,
	 *         'source' => string поле "Source",
	 *         'order_id' => string ID заказа,
	 *         'comment' => string Комментарий,
	 *         'paymentCurrency' => string код валюты,
	 *
	 *         'items' => [
	 *             # => [
	 *                 'id' => int ID варианта оплаты,
	 *                 'label' => string название варианта оплаты,
	 *             ]
	 *         ]
	 * ]
	 */
	public function getDengiOnlineParams(array $variables) {
		$variables = isset($variables['purchasing']) ? $variables['purchasing'] : [];
		$variables['formAction'] = isset($variables['formAction']) ? $variables['formAction'] : '';
		$variables['project'] = isset($variables['project']) ? $variables['project'] : '';
		$variables['amount'] = isset($variables['amount']) ? $variables['amount'] : '';
		$variables['nickname'] = isset($variables['nickname']) ? $variables['nickname'] : '';
		$variables['source'] = isset($variables['source']) ? $variables['source'] : '';
		$variables['order_id'] = isset($variables['order_id']) ? $variables['order_id'] : '';
		$variables['comment'] = isset($variables['comment']) ? $variables['comment'] : '';
		$variables['paymentCurrency'] = isset($variables['paymentCurrency']) ? $variables['paymentCurrency'] : '';

		$variables['items'] = isset($variables['items']) ? $variables['items'] : [];

		foreach ($variables['items'] as &$item) {
			$item['id'] = isset($item['id']) ? $item['id'] : '';
			$item['label'] = isset($item['label']) ? $item['label'] : '';
		}

		return $variables;
	}

	/**
	 * Возвращает параметры способа оплаты "AcquiroPay"
	 *
	 * @param array $variables результат работы макроса emarket::purchase()
	 *
	 * [
	 *     'purchasing' => [
	 *         'formAction' => string формы оплаты,
	 *         'product_id' => string Идентификатор продукта магазина,
	 *         'amount' => string стоимость заказа,
	 *         'language' => string языковой префикс сайта,
	 *         'order_id' => string ID заказа,
	 *         'ok_url' => string адрес редиректа для успешной оплаты,
	 *         'cb_url' => string callback-адрес для обработки ответа от платежной системы,
	 *         'ko_url' => string адрес редиректа для неуспешной оплаты,
	 *         'token' => string md5-хеш транзакции,
	 *     ]
	 * ]
	 *
	 * @return array
	 *
	 * [
	 *         'formAction' => string формы оплаты,
	 *         'product_id' => string Идентификатор продукта магазина,
	 *         'amount' => string стоимость заказа,
	 *         'language' => string языковой префикс сайта,
	 *         'order_id' => string ID заказа,
	 *         'ok_url' => string адрес редиректа для успешной оплаты,
	 *         'cb_url' => string callback-адрес для обработки ответа от платежной системы,
	 *         'ko_url' => string адрес редиректа для неуспешной оплаты,
	 *         'token' => string md5-хеш транзакции,
	 * ]
	 */
	public function getAcquiroPayParams(array $variables) {
		$variables = isset($variables['purchasing']) ? $variables['purchasing'] : [];

		$variables['formAction'] = isset($variables['formAction']) ? $variables['formAction'] : '';
		$variables['product_id'] = isset($variables['product_id']) ? $variables['product_id'] : '';
		$variables['amount'] = isset($variables['amount']) ? $variables['amount'] : '';
		$variables['language'] = isset($variables['language']) ? $variables['language'] : '';
		$variables['order_id'] = isset($variables['order_id']) ? $variables['order_id'] : '';
		$variables['ok_url'] = isset($variables['ok_url']) ? $variables['ok_url'] : '';
		$variables['cb_url'] = isset($variables['cb_url']) ? $variables['cb_url'] : '';
		$variables['ko_url'] = isset($variables['ko_url']) ? $variables['ko_url'] : '';
		$variables['token'] = isset($variables['token']) ? $variables['token'] : '';

		return $variables;
	}



    public function getAsideLinkClass($itemId, $pageId, $categoryList = array())
    {
        $activeClass = 'class="active"';
        $uH = umiHierarchy::getInstance();
        $page = $uH->getElement($pageId);

        if ($pageId == $itemId) {
            return $activeClass;
        }

        if (!isset($categoryList['items'])) return '';
        foreach ($categoryList['items'] as $item) {
            if ($item['id'] == $pageId or $item['id'] === $page->getParentId()) {
                return $activeClass;
            }
        }
	}



    public function getObjectsCount($id)
    {
        $count = 0;
        $pages = new selector('pages');
        $pages->where('hierarchy')->page($id)->childs(100500);
        $pages->types('hierarchy-type')->name('catalog', 'object');
        foreach ($pages as $page) {
            if ($page->this_parent) $count++;
        }
        return $count;
	}



    public function getCommonQuantity($id)
    {
        $commonQuantity = [];
        $pages = new selector('pages');
        $pages->where('hierarchy')->page($id)->childs(100500);
        $pages->types('hierarchy-type')->name('catalog', 'object');
        foreach ($pages as $page) {
            $commonQuantity[] = $page->common_quantity ? $page->common_quantity : 0;
        }
        return $commonQuantity;
    }

		public function getBestCategory() {
		$category = new selector('pages');
$category->types('hierarchy-type')->name('catalog', 'object');
$category->where('flag')->equals('Sale');

		$category->limit(0, self::MAX_BEST_OFFERS_COUNT);
		$category->order('id')->rand();
		$category->group('obj_id');
		$category->option('load-all-props')->value(true);
		$category->option('no-length', true);
		return $category->result();
	}
	
	
}
