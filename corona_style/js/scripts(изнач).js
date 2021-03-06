$(function(){

    $('.js-asite-cats').on('click', function() {
        $().add($(this).prev()).add($(this).next()).add($(this)).toggleClass('active');
    });

    /*
    * Цели на счетчики
    * */
    $(window).load(function () {
        if (typeof(Ya) !== "undefined" && domainId === 2) {
            var id = 49048508;

            function addEvent(elem, target) {
                var event = "yaCounter" + id + ".reachGoal('" + target + "'); return true;"
                $(elem).attr('onclick', event);
            }

            addEvent('.buy_link', 'dobavit-v-korzinu');
            addEvent('.quike_buy_link', 'kupit-v-1-klik');
            addEvent('.cart_link', 'perejti-v-korzinu');
            addEvent('#saveInfoCustom .submit_btn', 'oformit-zakaz');
            addEvent('.feedback_link', 'voznik-vopros');
            addEvent('header .cart_link', 'klik-po-korzine');
        }
    });

	/*
	* Достаем из баннеров ссылки и меняем div на a
	* */
	if (window.domainId == 1) {
        $('.promo_banners.right .item').each(function() {
            var href = $(this).find('a').attr('href');
            if (!href) return;

            var html = $(this).html();
            var $a = $('<a></a>');

            $.each(this.attributes, function() {
                if(this.specified) {
                    $a.attr(this.name, this.value);
                }
            });

            $a.attr('href', href).attr('rel', 'noopener').attr('target', '_blank');
            $a.append(html);

            $(this).parent().append($a);
            $(this).remove();
        });
	}

	// Основной слайдер на главной
	$('.main_slider .slider').owlCarousel({
		loop: true,
	    margin: 19,
	    dots: false,
	    nav: true,
	    navSpeed: 750,
	    dotsSpeed: 750,
	    smartSpeed: 750,
	    autoplaySpeed: 750,
	    items: 1,
	    autoplay: true,
		autoplayTimeout: 5000
	})


	// Товары
	$('.products .slider.carousel1').owlCarousel({
		loop: false,
	    dots: false,
	    nav: true,
	    navSpeed: 500,
	    dotsSpeed: 500,
	    smartSpeed: 500,
	    responsive : {
		    1024 : {
		        items: 4,
		        margin: 20
		    },
		    768 : {
		        items: 3,
		        margin: 15
		    },
		    480 : {
		        items: 2,
		        margin: 15
		    },
		    0 : {
		        items: 1,
		        margin: 15
		    }
		}
	})

	$('.products .slider.carousel2').owlCarousel({
		loop: false,
	    dots: false,
	    nav: true,
	    navSpeed: 500,
	    dotsSpeed: 500,
	    smartSpeed: 500,
	    responsive : {
	    	1270 : {
		        items: 5,
		        margin: 10
		    },
		    1024 : {
		        items: 4,
		        margin: 10
		    },
		    768 : {
		        items: 3,
		        margin: 15
		    },
		    480 : {
		        items: 2,
		        margin: 15
		    },
		    0 : {
		        items: 1,
		        margin: 15
		    }
		}
	})


	// Маска ввода
	$('input[type=tel]').mask('+7 (999) 999-99-99')


	// Кастомный select
 	$('.sorting select').niceSelect()


 	// Фильтр
	if( $(window).width() < 768 ){
		$('aside .filter .item .name').click(function(e){
			e.preventDefault()

			if( $(this).hasClass('active') ){
				$(this).removeClass('active').next().slideUp()
			} else{
				$(this).addClass('active').next().slideDown()
			}
		})
	}


    var min_el = $(".input.ot"),
        max_el = $(".input.do"),
        min = $(min_el).data("minimum"),
        max = $(max_el).data("maximum"),
        from = $(min_el).val(),
        to = $(max_el).val();

    $("#price_range").ionRangeSlider({
        type     : 'double',
        min      : min,
        max      : max,
        from     : from,
        to       : to,
        step     : 1,
        onChange : function (data) {
            $('.filter .price input.ot').val( number_format(data.from, 0, ',', ' ') )
            $('.filter .price input.do').val( number_format(data.to, 0, ',', ' ') )
            $("#from").val(data.from);
            $("#to").val(data.to);
        }
    })

    $('.filter .price .input').keyup(function() {
        var slider = $("#price_range").data("ionRangeSlider")

        slider.update({
            type     : 'double',
            min      : min,
            max      : max,
            from     : parseInt( $('.filter .price input.ot').val().replace(/\s+/g, '') ),
            to       : parseInt( $('.filter .price input.do').val().replace(/\s+/g, '') ),
            step     : 1,
            onChange : function (data) {
                $('.filter .price input.ot').val( number_format(data.from, 0, ',', ' ') )
                $('.filter .price input.do').val( number_format(data.to, 0, ',', ' ') )
                $("#from").val(data.from);
                $("#to").val(data.to);
            }
        })
    })


    $('.filter .reset_btn').click(function(){
        var slider = $("#price_range").data("ionRangeSlider")

        slider.update({
            type     : 'double',
            min      : min,
            max      : max,
            from     : from,
            to       : to,
            step     : 1,
            onChange : function (data) {
                $('.filter .price input.ot').val( number_format(data.from, 0, ',', ' ') )
                $('.filter .price input.do').val( number_format(data.to, 0, ',', ' ') )
                $("#from").val(data.from);
                $("#to").val(data.to);
            }
        })

    })

/*
    $('.filter .reset_btn').click(function(){
		var slider = $("#price_range").data("ionRangeSlider")

		slider.reset()
	})
	*/


	// Всплывающие окна
	$('.modal_link').click(function(e){
		e.preventDefault()

		$.fancybox.close()

		$.fancybox.open({
			src  : $(this).attr('href'),
			type : 'inline',
			opts : {
				speed: 300,
				autoFocus : false,
				i18n : {
					'en' : {
						CLOSE : 'Закрыть'
					}
				}
			}
		})
	})


	// Отправка форм
	/*
	$('.form.custom_submit').submit(function(e){
		e.preventDefault()

		$.fancybox.close()

		$.fancybox.open({
			src  : '#success_modal',
			type : 'inline',
			opts : {
				speed: 300,
				autoFocus : false,
				i18n : {
					'en' : {
						CLOSE : 'Закрыть'
					}
				}
			}
		})
	})
	*/


	// Увеличение картинки
	$('.fancy_img').fancybox({
		transitionEffect : 'slide',
		animationEffect : 'fade',
		i18n : {
			'en' : {
				CLOSE : 'Закрыть'
			}
		}
	})


	$('.modal .close').click(function(e){
		e.preventDefault()

		$.fancybox.close()
	})


	// Подписка
	/*
	$('.subscribe form').submit(function(e){
		e.preventDefault()

		$(this).next().fadeIn()

		clearTimeout( timer )
		var timer = setTimeout(function(){
			$('.subscribe .success').fadeOut(300)
		}, 2000)
	})
	*/


	// Мини всплывающие окна
	firstClick = false
	$('.mini_modal_link').click(function(e){
	    e.preventDefault()

	    var modalId = $(this).attr('data-modal-id')

	    if($(this).hasClass('active')){
	        $(this).removeClass('active')
	        $('.mini_modal').fadeOut(200)
	        firstClick = false

            if( $(window).width() < 1024 ){
                $('body').css('cursor', 'default')
            }
	    }else{
	        $('.mini_modal_link').removeClass('active')
	        $(this).addClass('active')

	        $('.mini_modal').fadeOut(200)
	        $(modalId).fadeIn(300)
	        firstClick = true

            if( $(window).width() < 1024 ){
                $('body').css('cursor', 'pointer')
            }
	    }
	})

	//Закрываем всплывашку при клике вне неё
	$(document).click(function(e){
	    if (!firstClick && $(e.target).closest('.mini_modal').length == 0){
	        $('.mini_modal').fadeOut(300)
	        $('.mini_modal_link').removeClass('active')

            if( $(window).width() < 1024 ){
                $('body').css('cursor', 'default')
            }
	    }
	    firstClick = false
	})


	// Изменение количества товара
	$('.amount .minus').click(function(e){
	    e.preventDefault()

	    var input = $(this).parents('.amount').find('input')
	    var inputVal = parseInt(input.val())
	    var minimum = parseInt(input.attr('data-minimum'))

	    if(inputVal > minimum){
	    	input.val( inputVal-1 )
	    }
        $(input).trigger("change");
	    if( $(this).hasClass('update_price') ){
	    	updateCartPrice( $(this).parents('tr') )
	    }
	})

	$('.amount .plus').click(function(e){
	    e.preventDefault()

	    var input = $(this).parents('.amount').find('input')
	    var inputVal = parseInt(input.val())
	    var maximum = parseInt(input.attr('data-maximum'))

	    if(inputVal < maximum){
	    	input.val( inputVal-(-1) )
	    }
	    $(input).trigger("change");

	    if( $(this).hasClass('update_price') ){
	    	updateCartPrice( $(this).parents('tr') )
	    }
	})

	$('.amount .input').keydown(function(){
		var _self = $(this)

		setTimeout(function(){
			updateCartPrice( _self.parents('tr') )
		}, 10)
	})


	// Личный кабинет
	$('.lk .edit_personal').click(function(e){
	    e.preventDefault()
	    var parent = $(this).parents('.personal')

	    parent.find('.personal_info').hide()
	    parent.find('.password_form').hide()
	    parent.find('.personal_form').fadeIn()
	})

	$('.lk .edit_password').click(function(e){
	    e.preventDefault()
	    var parent = $(this).parents('.personal')

	    parent.find('.personal_info').hide()
	    parent.find('.personal_form').hide()
	    parent.find('.password_form').fadeIn()
	})

	$('.lk .personal form .cancel_link').click(function(e){
	    e.preventDefault()
	    var parent = $(this).parents('.personal')

	    $(this).parents('form').hide()
	    parent.find('.personal_info').fadeIn()
	})

	$('.lk .history .head').click(function(e){
	    e.preventDefault()

	    if( $(this).parents('.item').hasClass('active') ){
	    	$(this).parents('.item').removeClass('active').find('.data').slideUp()
	    }else{
	    	$('.lk .history .data').slideUp()
	    	$('.lk .history .item').removeClass('active')

	    	$(this).parents('.item').addClass('active').find('.data').slideDown()
	    }
	})


	// Товар в сравнение
	$('.product_info .compare a').click(function(e){
		e.preventDefault()

		var self = $(this)
		var parent = self.parents('.compare')

		if( self.hasClass('active') ){
			self.removeClass('active').find('span').text('Добавить в сравнение')
			parent.find('.success').hide()
			parent.find('.success_del').fadeIn(300)
		}else{
			self.addClass('active').find('span').text('Удалить из сравнения')
			parent.find('.success').hide()
			parent.find('.success_add').fadeIn(300)
		}

		clearTimeout( timer )
		var timer = setTimeout(function(){
			parent.find('.success').fadeOut(200)
		}, 2000)
	})


	// Товар в корзину
	$('.buy_link').click(function(e){
		e.preventDefault()

		if( $(this).hasClass('once') ){
			$.fancybox.close()

			$.fancybox.open({
				src  : $(this).attr('href'),
				type : 'inline',
				opts : {
					speed: 300,
					autoFocus : false,
					i18n : {
						'en' : {
							CLOSE : 'Закрыть'
						}
					}
				}
			})
		}else{
			if( $(this).hasClass('active') ){
				$(this).removeClass('active').find('span').text('Добавить в корзину')
			}else{
				$.fancybox.close()

				$.fancybox.open({
					src  : $(this).attr('href'),
					type : 'inline',
					opts : {
						speed: 300,
						autoFocus : false,
						i18n : {
							'en' : {
								CLOSE : 'Закрыть'
							}
						}
					}
				})

				$(this).addClass('active').find('span').text('Уже в корзине')
			}
		}
	})


	// Моб. меню
    $('header .mob_menu_link').click(function(e){
    	e.preventDefault()

		if( $(this).hasClass('active') ){
			$(this).removeClass('active')
        	$('header .cats').slideUp(200)
		} else{
			$(this).addClass('active')
			$('header .cats').slideDown(300)
		}
    })

    if( $(window).width() < 1024 ){
 		$('header .cats .cats_item > a.sub_link').click(function(e){
			e.preventDefault()

			if( $(this).hasClass('active') ){
				$(this).removeClass('active').next().slideUp()
			} else{
				$('header .cats .cats_item > a.sub_link').removeClass('active')
				$('header .cats .sub_cats').slideUp()
				$(this).addClass('active').next().slideDown()
			}
		})
 	}


 	// Моб. поиск
    $('header .mob_search_link').click(function(e){
    	e.preventDefault()

		if( $(this).hasClass('active') ){
			$(this).removeClass('active')
        	$('#search_form').slideUp(200)
		} else{
			$(this).addClass('active')
			$('#search_form').slideDown(300)
		}
    })


 	// Зум картинки
 	if( $(window).width() > 767 ){
 		$('.zoomImg').elevateZoom({
	 		zoomType:"inner",
	 		cursor:"crosshair"
	 	})
 	} else {
 		$('.product_info .images .grid').addClass('owl-carousel')
 		$('.product_info .images .grid').owlCarousel({
			loop: true,
		    margin: 15,
		    dots: false,
		    nav: true,
		    navSpeed: 750,
		    dotsSpeed: 750,
		    smartSpeed: 750,
		    autoplaySpeed: 750,
		    items: 1
		})
 	}
})


function updateCartPrice(context){
	var price = parseInt(context.find('td.price:not(.total)').data('price'))
	var amount = parseInt(context.find('td.amount .input').val())
	var discount = parseInt(context.find('td.discount').text())/100
	var totalPrice = (price*amount)-((price*amount)*discount)
	var totalCartPrice = 0

	context.find('td.total.price .val').text( number_format(totalPrice, 0, ',', ' ') )

	$('.cart_info table tbody td.total.price .val').each(function(){
		totalCartPrice = (totalCartPrice + parseInt( $(this).text().replace(/\s+/g, '') ))
	})

	$('.cart_info table tfoot .total_price .val').text( number_format(totalCartPrice, 0, ',', ' ') )
}


function productHeight(selector, step){
	var start = 0
	var finish = parseInt(step)

	var products = selector

	for( var i = 0; i < products.length; i++ ){
		var obj = products.slice(start, finish).find('.name')

		setHeight( obj )

		start = start+step
		finish = finish+step
	}
}


function setHeight(className){
    var maxheight = 0
    $(className).each(function() {
        if($(this).innerHeight() > maxheight) {
        	maxheight = $(this).innerHeight()
        }
    })
    $(className).innerHeight(maxheight)
}


// Форматирование чисел
function number_format(number, decimals, dec_point, thousands_sep) {
  number = (number + '').replace(/[^0-9+\-Ee.]/g, '');

  var n          = !isFinite(+number) ? 0 : +number,
  	  prec       = !isFinite(+decimals) ? 0 : Math.abs(decimals),
  	  sep        = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
  	  dec        = (typeof dec_point === 'undefined') ? '.' : dec_point,
  	  s          = '',
  	  toFixedFix = function(n, prec) {
      	var k = Math.pow(10, prec);
      	return '' + (Math.round(n * k) / k).toFixed(prec);
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
}