$(window).load(function(){
	compareInit()
})


$(window).resize(function(){
	$('.compare_carousel .compare_wrap').sly(false)

	$('.compare_carousel .features li .box').height('auto')
	$('.compare_features ul li .box').height('auto')

	compareInit()
})


function compareInit(){
	// Сравнение
	var products = $('.compare_carousel .carousel .product')
	var sizes = new Object()


	if(products.size() > 4){
		$('.compare_carousel .carousel > li').width( Math.ceil($('.compare_carousel').width()/4) )
	}

	if(products.size() > 3 && $(window).width() < 1270 && $(window).width() > 1023){
		$('.compare_carousel .carousel > li').width( Math.ceil($('.compare_carousel').width()/3) )
	}

	if(products.size() > 2 && $(window).width() < 1024){
		$('.compare_carousel .carousel > li').width( Math.ceil($('.compare_carousel').width()/2) )
	}

	productHeight( products, products.size())


	products.each(function(){
		$(this).next().find('li').each(function(){
			if(sizes[$(this).index()]){
				if($(this).find('.box').outerHeight() > sizes[$(this).index()]){
					sizes[$(this).index()] = $(this).find('.box').outerHeight()
				}
			}else{
				sizes[$(this).index()] = $(this).find('.box').outerHeight()
			}
		})
	})

	$('.compare_features li').each(function(){
		if(sizes[$(this).index()]){
			if($(this).find('.box').outerHeight() > sizes[$(this).index()]){
				sizes[$(this).index()] = $(this).find('.box').outerHeight()
			}
		}else{
			sizes[$(this).index()] = $(this).find('.box').outerHeight()
		}
	})

	$.each(sizes, function(key, data){
		products.each(function(){
			$(this).next().find('ul li:eq('+ key +') .box').innerHeight(data)
		})

		$('.compare_features ul li:eq('+ key +') .box').innerHeight(data)
	})


	if(
		products.size() > 4
		|| ( products.size() > 3 && $(window).width() < 1270 && $(window).width() > 1023 )
		|| ( products.size() > 2 && $(window).width() < 1024 )
	){
		$('.compare_carousel').addClass('active')

		$frame = $('.compare_carousel .compare_wrap')
		$wrap  = $frame.parent()

		$SLY = $frame.sly({
			horizontal: 1,
			itemNav : 'basic',
			activateMiddle: 1,
			smart: 1,
			activateOn: 'click',
			mouseDragging: 1,
			touchDragging: 1,
			releaseSwing: 1,
			startAt: 0,
			scrollBar : $wrap.find('.scrollbar'),
			scrollBy: 0,
			speed : 500,
			elasticBounds: 1,
			dragHandle: 1,
			dynamicHandle: 1,
			clickBar: 1
		})
	}

	$('.compare_features').css('top', $('.compare_carousel .features').position().top-37)
    $("input[name=compare_settings]").change(function(){
        var value = $(this).val();
        var features = $(".compare_features li"), features_count = $(features).length,
            products = $(".compare_wrap .carousel > li"), products_count = $(products).length;
        if (value == "diff") {


            for (i=0; i<features_count; i++) {
                var values = [];
                for(j=0; j<products_count; j++){
                    text = $(".compare_wrap .carousel > li:eq("+j+") .features li:eq("+i+")").text();
                    values.push(text.trim());
                }
                values = array_unique(values);
                //if (values.length < products_count) {
                if (values.length == 1) {
                    $(".compare_features li:eq("+i+")").stop().hide();
                    $(products).find(".features li:eq("+i+")").stop().hide();
                }
            }
        }else{

            $(features).stop().show();
            $(products).find("li:hidden").stop().show();

        }
    });
}

function array_unique(arr) {
    var tmp_arr = new Array();
    for (var i = 0; i < arr.length; i++) {
        if (tmp_arr.indexOf(arr[i]) == "-1") {
            tmp_arr.push(arr[i]);
        }
    }
    return tmp_arr;
}