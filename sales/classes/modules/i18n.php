<?php

/**
 * Языковые константы для фронтенда русской версии шаблона Demomarket
 */
$i18n = [
	'js-basket_items_total' => ' шт товаров на сумму ',
	'js-basket_empty' => 'В корзине нет ни одного товара.',
	'js-basket_empty_html' => '<h4 class="empty-content">В корзине нет ни одного товара.</h4><p>Вернитесь в <a href="/">каталог</a> и добавьте товары в корзину.</p>',
	'js-basket_options' => 'Выбор опций',
	'js-basket_add_button' => 'Добавить в корзину',
	'js-basket_add_short' => 'Добавить',
	'js-vote_no_element' => 'Не выбран ни один вариант',
	'js-vote_already_voted' => 'Вы уже голосовали',
	'js-vote_total_votes' => 'Всего голосов:',
	'js-vote_rating' => 'Рейтинг=> ',
	'js-forms_empty_field' => "Поле обязательно для заполнения.",
	'js-forms_short_login' => "Слишком короткий логин. Логин должен состоять не менее, чем из 3х символов.",
	'js-forms_long_login' => "Слишком большой логин. Логин должен состоять не более, чем из 40 символов.",
	'js-forms_short_pass' => "Слишком короткий пароль. Пароль должен состоять не менее, чем из шести символов.",
	'js-forms_same_pass' => "Пароль не должен совпадать с логином.",
	'js-forms_confirm_pass' => "Пароли должны совпадать.",
	'js-forms_invalid_email' => "Некорректный e-mail.",
	'js-checkout' => "Оформить",
	'js-oneclick_checkout' => "Быстрый заказ",
	'js-finish_message_prefix' => "Заказ ",
	'js-finish_message_postfix' => " успешно оформлен!<br/>Ожидайте звонка от менеджера магазина.",
	'js-continue' => "Продолжить",
	'js-max_file_size' => "Максимальный размер загружаемого файла",
	'js-enter_captcha' => "Введите код с картинки",
	'js-reset_captcha' => "перезагрузить код",

	'js-cart_header' => 'В корзине товаров: ',
	'js-cart_empty' => 'В корзине ничего нет',
	'js-return_to_catalog' => 'Вернитесь в <a href="' . cmsController::getInstance()->getPreLang() . '/">каталог</a> и добавьте товары в корзину.',
	"dizzy_usually_buy" => "С этим товаром покупают",
	"dizzy_order_comment" => "Комментарий к адресу",
	"specify_delivery_address" => "Укажите адрес доставки",
	"one-step-order" => "Оформление заказа в один шаг",
	"sort-stock" => "Наличию",
	'js-callback_success' => 'Спасибо за интерес, наш менеджер позвонит вам в ближайшее время.',

	'js-one_click_order_fail' => 'К сожалению, при оформлении заказа в один клик произошла ошибка.',
	'js-one_click_order_success' => 'Заказ #%s успешно оформлен! Ожидайте звонка от менеджера магазина.',
	'js-login_do_try_again' => 'Вы ввели неверный логин или пароль. Проверьте раскладку клавиатуры, не нажата ли клавиша «Caps Lock» и попробуйте ввести логин и пароль еще раз.',


    'personal' => 'Личный кабинет',
    'entry' => 'Вход/Регистрация',
    'exist' => 'Выход',
    'registration' => 'Регистрация',
    'compare' => 'Сравнение',
    'subscribes' => 'Подпишитесь на новинки',
    'your_mail' => 'Введите свой E-mail',
    'subscribe_success' => 'Вы успешно подписались на рассылку.',
    'politics' => 'Политика конфиденциальности',
    'catalog' => 'Каталог',
    'helpful_information' => 'Покупателям',
    'about' => 'CORONA STYLE',
    'have_quest' => 'Возник вопрос?',
    'sort' => 'Сортировка',
    'output' => 'Выводить',
    'empty_category' => 'Категория пуста.',
    'pick_up' => 'Подобрать',
    'reset' => 'Сбросить',
    'sku' => 'Артикул',
    'add_to_compare' => 'Добавить в сравнение',
    'del_form_compare' => 'Удалить из сравнения',
    'add_to_compare_success' => 'успешно добавлен в сравнение',
    'del_from_compare_success' => 'успешно удалён из сравнения',
    'size' => 'Размер',
    'add_to_cart' => 'ДОБАВИТЬ В КОРЗИНУ',
    'buy_one_click' => 'КУПИТЬ В 1 КЛИК',
    'descr' => 'Описание',
    'products_in_collections' => 'в состав коллекции входит',
    'will_like' => 'Вам может понравиться',
    'product_in_cart' => 'Товар добавлен в корзину',
    'go_cart' => 'Перейти в корзину',
    'go_sales' => 'Продолжить покупки',
    'thanks' => 'Спасибо!',
    'manager_will_concact' => 'Наш менеджер свяжется с Вами в ближайшее время.',
    'login_in_account' => 'Вход в личный кабинет',
    'password' => 'Пароль',
    'forget_password' => 'Забыли свой пароль?',
    'login' => 'Войти',
    'forget_password' => "Восстановление пароля",
    'forget_mail' => 'Для получения инструкций по восстановлению пароля введите e-mail, указанный при регистрации',
    'send_password' => 'Выслать',
    'forget_instruction' => 'Инструкции отправлены на почты.',
    'register_instruction' => 'Регистрация успешно пройдена. Проверьте почту.',
    'you_name' => 'Ваше имя',
    'mobile_phone' => 'Мобильный телефон',
    'password' => 'Пароль',
    'i_confirm' => 'Я даю свое согласие на обработку и хранение персональных данных *',
    'required_fields' => '- поля, обязательные для заполнения',
    'do_registration' => 'Зарегистрироваться',
    'else' => 'или',
    'more_info' => 'подробнее',
    'blog' => 'Блог',
    'send' => 'Отправить',
    'field-sizes' => 'Размер',
    'field-sostav' => 'Состав',
    'field-recommend2' => 'Рекомендации по стирке',
    'field-dopolnitelno' => 'Дополнительно',
    'go_to_product' => 'Перейти к товару',
    'sku' => 'Арт.',
    'sizes' => 'Размеры:',
    'field-stock' => 'Акция',
    'field-trend' => 'Тренд сезона',
    'field-news' => 'Новинка',
    'reset' => 'Сбросить',
    'colors' => 'Цвета',

    'sort_up' => 'цена по возрастанию',
    'sort_down' => 'цена по убыванию',
    'name_up' => 'по алфавиту А-Я',
    'name_down' => 'по алфавиту Я-А',
    'empty_cart' => 'Корзина пуста',
    'select_size' => 'Для добавления товара в корзину выберите размер.',
    'select_size_header' => 'Выберите размер',
    'product' => 'Продукт',
    'price' => 'Цена',
    'qty' => 'Кол-во',
    'discount' => 'Скидка',
    'total' => 'Сумма',
    'remove' => 'Удалить',
    'clear_cart' => 'Очистить корзину',
    'step' => 'Шаг',
    'shipping_method_and_address' => 'Cпособ и адрес доставки',
    'field-courier_delivery' => 'Курьером',
    'field-russian_post' => 'Почта России',
    'customer_information' => 'Данные покупателя',
    'payment_methods' => 'Способ оплаты',
    'field-online_payment' => 'Online-оплата',
    'field-cash' => "Наличными",
    'purchase_order' => 'Оформить заказ',
    'order_comment' => 'Комментарии к заказу',




























];
