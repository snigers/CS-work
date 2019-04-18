site.basket = {};
site.basket.async = true;
site.basket.empty = function(){
	return function(e) {

	}
}

site.basket.wishList = function(){
	return function(e) {
		$(".favorite_total").text(e.total);
		if (e.total > 0) {
			for(var i in e.items.item){
				var item = e.items.item[i];
                $("a.favorite_link[data-id="+item.id+"]").addClass("active").find("span").text('в избранном');

			}
		}
	}
}



site.basket.getCompareList = function(){
	return function(e) {
		$(".compare_total").text(e.total);
		if (e.total > 0) {
			for(var i in e.items.item){
				var item = e.items.item[i];
				$("a.compare_link[data-id="+item.id+"]").addClass("active").find("span").text('Удалить из сравнения');

			}
		}else{
			$(".compare_info").addClass("text_block").html('<p class="empty">Нет товаров для сравнения.</p>');
		}
	}
}

site.basket.replace = function(id) {
	if (id == 'all') {
		jQuery('a[id^="add_basket_"]').each(function() {
			$(this).text(i18n.basket_add_button)
			jQuery('.basket_info_summary').text(i18n.basket_empty);
		});
	}
	return function(e) {
		var text, discount, goods_discount_price, goods_discount, item_total_price, item_discount, cart_item, basket, i, item, related_goods,
			cart_summary = jQuery('.cart_summary'),
			cart_discount = jQuery('.cart_discount'),
			goods_discount_price = jQuery('.cart_goods_discount'),
			rem_item = true,
			detect_options = {};

		if (e.summary.amount > 0) {
			text = e.summary.price.actual;
			goods_discount = ((typeof e.summary.price.original == 'undefined') ? e.summary.price.actual : e.summary.price.original);
			discount = ((typeof e.summary.price.discount != 'undefined') ? e.summary.price.discount : 0);
			if(discount){
                $('.discount_item').stop().show();
                $(".order_discount").text(discount);
			}else{
				$('.discount_item').stop().hide();
			}
			if(typeof e.summary.promo !== "undefined"){
				$(".promo_val").val(e.summary.promo);
			}else{
                $(".promo_val").val('');
			}

			$(".cart_all_summary_original").text(goods_discount);
			$(".cart_summary_count").text(e.summary.total_amount);
			var products_cart = $("section.cart_info");
			for (i in e.items.item) {
				item = e.items.item[i];
				if (item.id == id) {
					rem_item = false;
					item_total_price = item["total-price"].actual;
					item_discount = ((typeof item.discount != 'undefined') ? item.discount.amount : '0');
				}
				if (item.page.id == id) {
					if (detect_options.amount) {
						detect_options.amount = detect_options.amount + item.amount;
					}
					else detect_options = {'id':id, 'amount':item.amount};
				}

				$(".buy_link[data-id="+item.page.id+"]").not(".once").addClass('active').find("span").text("В корзине");


				var cart_tr = $(products_cart).find("tr.id_"+item.id),
                    input = $(cart_tr).find("input")


				$(input).val(item.amount);
				var total_price = (typeof item['total-price'].actual == "undefined") ? 0 : item['total-price'].actual,
					price = (typeof item['price'].actual == "undefined") ? 0 : item['price'].actual;
				$(cart_tr).find(".total.price").html(total_price + ' <span class="currency">Ц</span>');


				if(typeof item.discount != "undefined"){
                    $(cart_tr).find(".discount").text(item['discount']['amount']);

				}


			}

			for (i in e.items.item) {
				item = e.items.item[i];
				$(".productid_"+item.page.id).attr("checked","checked").data('id',item.id);
			}

			if (rem_item) {
				$("tr.id_"+id).remove();
			}
			else {

			}

			var delivery_price = parseFloat($("select[name='delivery-id'] option:selected").data("price")),
			cart_all_summary = parseFloat(e.summary.price.actual.replace(/\s/g,""));
			delivery_price = delivery_price || 0;
			var all_price = cart_all_summary + delivery_price;
			$(".cart_all_summary").text(e.summary.price.actual + " Р");
			$(".delivery_price").text(number_format(delivery_price, 0, '', ' '));
			$(".cart_all_summary_with_delivery").text(number_format(all_price, 0, '', ' '));

		}
		else {
			$(".cart_all_summary").text(0);
			$(".cart_count").text(0);

			$(".checkout").remove();
			$("section.cart_info").html('<p class="empty_cart">Корзина пуста.</p>');
		}
		site.basket.modify.complete = true;
	};
};



site.basket.modify = function(id, amount_new, amount_old) {
	if (amount_new.replace(/[\d]+/) == 'undefined' && amount_new != amount_old) {
		basket.modifyItem(id, {amount:amount_new}, this.replace(id));
	}
	else this.modify.complete = true;
};

site.basket.modify.complete = true;

site.basket.remove = function(id) {
	if (id == 'all') basket.removeAll(this.replace(id));
	else basket.removeItem(id, this.replace(id));
};


site.basket.init = function() {
	this.is_cart = (!!jQuery('.basket table').length);

	basket.get(site.basket.replace(666));
	basket.getWishList(site.basket.wishList(666));
	basket.getCompareList(site.basket.getCompareList(666));

	//$(".product a.favorite_link,.product_info a.favorite_link").click(function(){

	$("input[name='delivery-address']").change(function(){
		if($(this).val() !== "new"){
            var select = $("select[name=delivery-id"),
			option = $(select).find("option:selected"),
            city = $(this).data("city"),
			index = $(this).data("index");
            if((city != "") && (index != "")){
                $.ajax({
                    url: "/emarket/getDeliveryPrice.json",
                    data: {
                        city: city,
                        index: index,
                        id: $(select).val()
                    },
                    dataType: 'json',
                    success: function(data){
                        var data = parseFloat(data.data);
						/*
						 if(data){
						 $(option).data('price',data).attr("data-price",data);
						 }else{

						 }*/
                        $(option).data('price',data).attr("data-price",data);
                        $(select).trigger("change");
                    }
                });
            }
		}
	});

    $(".delivery_address").on("change","input",function(){
    	var select = $("select[name=delivery-id"),
		option = $(select).find("option:selected");
		if($(option).data("type") == "russianpost"){
			var city = $(".delivery_address").find("input[name='data[new][city]']").val(),
				index = $(".delivery_address").find("input[name='data[new][index]']").val();
			if((city != "") && (index != "")){
                $.ajax({
                    url: "/emarket/getDeliveryPrice.json",
					data: {
                    	city: city,
						index: index,
						id: $(select).val()
					},
                    dataType: 'json',
                    success: function(data){
                        var data = parseFloat(data.data);
                        /*
                        if(data){
                        	$(option).data('price',data).attr("data-price",data);
						}else{

						}*/
                        $(option).data('price',data).attr("data-price",data);
                        $(select).trigger("change");
                    }
                });
			}
		}
    });


	$("body").on("change",".related_product",function(){
		var id = $(this).val(),
		options = [];
		if($(this).is(":checked")){
			basket.putElement(id, options, site.basket.replace(id));
		}else{
			basket.removeElement(id, site.basket.replace($(this).data('id')));
			$.each($(".productid_"+id),function(i,input){
				$(input).prop('checked',false)
			});


		}
	});

	$("body").on("click","a.favorite_link",function(){
		var id = $(this).data("id"),
		total = parseInt($(".favorite_total").text());

		if ($(this).hasClass("active")) {
			basket.addWish(id);
			total++;
		}else{

			basket.delWish(id);
			total--;
			if ($(".product-list_fav").length !== 0) {
				$(this).parents(".item_wrap").remove();
			}

		}

		if (total == 0) {
			$(".product-list_fav").html('<p class="empty">Товаров нет.</p>');
		}

		$(".favorite_total").text(total);
		return false;
	});


	$("body").on("click",".remove-fav",function(){
		var id = $(this).data("id"),
		total = parseInt($(".favorite_total").text());
		total--;
		if ($(".product-list_fav").length !== 0) {
			$(this).parents(".item_wrap").remove();
		}
		basket.delWish(id);
		if (total == 0) {
			$(".product-list_fav").html('<p class="empty">Товаров нет.</p>');
		}

		$(".favorite_total").text(total);
		return false;
	});


    $(".product-list_fav").on("click",".del_wishlist",function(){
        var id = $(this).data("id"),
            total = parseInt($(".favorite_total").text());
        $(this).parents(".item_wrap").remove();
        basket.delWish(id);
        total--;
        $(".favorite_total").text(total);
        if (total == 0) {
            $(".product-list_fav").html('<p class="empty">Товаров нет.</p>');
        }
        return false;
    });



	//$(".product a.compare_link").click(function(){
	//$(".product a.compare_link, .product_info a.compare_link").click(function(){
	$("body").on("click",".product a.compare_link, .product_info a.compare_link",function(){
		var id = $(this).data("id"),
		total = parseInt($(".compare_total").text());
		if ($(this).hasClass("active")) {
			basket.addCompare(id);
			total++;
		}else{
			basket.delCompare(id);
			total--;
		}

		$(".compare_total").text(total);
	});






	$(".compare_carousel").on("click",".del_compare",function(){
		var id = $(this).data("id"),
		total = parseInt($(".compare_total").text());
		$(this).parents("li").remove();
		basket.delCompare(id);


		$(".compare_total").text(total);
		basket.getCompareList(site.basket.getCompareList(666));
		return false;
	});


	$(".clear_compare").click(function(){
		basket.delAllCompare(site.basket.getCompareList(666));
		return false;
	});


	//$(".products").on("click",".js-add-basket-btn",function(){
	$(".credit_link").click(function(){
		$(".js-add-basket-btn").trigger("click");
	});

	$("body").on("click",'.buy_link',function(e){
		var id = $(this).data("id"), size_el = $("input[name='product_size']"),size = $("input[name='product_size']:checked");
		options = [];
        options['amount'] = 1;
        if($(".properties .size").length && ($(".properties .size .active").length == 0)){
            $.fancybox.close();
            $.fancybox.open({
                src  : "#sizes_fail",
                type : 'inline',
                opts : {
                    speed: 300,focus : false,
                    margin: [20,0],
                    slideShow : false,
                    fullScreen : false,
                    thumbs : false
                }
            })
		}
        if($(size).length){
        	options['sizes'] = $(size).val();
		}
		if($(this).hasClass('once')){
            basket.putElement(id, options, site.basket.replace(id));
		}else{
            if($(this).hasClass("active")){
                basket.putElement(id, options, site.basket.replace(id));
            }else{
                basket.removeElement(id, site.basket.replace(id));
            }
		}



		return false;
	});

    $("body").on("click",'.sample_buy_link',function(e){
        var id = $(this).data("id");
        options = [];
        var parent = $(this).parents(".product");
        options['amount'] = 1;

        basket.putSample(id, options, site.basket.replace(id));

        return false;
    });





/*
	$(".product").on("click",".js-add-basket-btn",function(){
		var id = $(this).data("id"),
		options = [];
		basket.putElement(id, options, site.basket.replace(id));
	});
*/


	$(".products").on("click",".js-add-basket-btn",function(){
		var id = $(this).data("id"),
		options = [],
		parent = $(this).parents(".product");
		options['amount'] = 1;

		select = $(parent).find("select");
		if($(select).length){
			if($(select).val() !== "null"){
				options['sizes'] = $(select).val();
			}else{
				$.fancybox.close();
				$.fancybox.open({
						src  : "#sizes_fail",
						type : 'inline',
						opts : {
							speed: 300,focus : false,
							margin: [20,0],
							slideShow : false,
							fullScreen : false,
							thumbs : false
						}
					})
				$(this).removeClass("active").text("В корзину");
					return false;
			}
		}


		basket.putElement(id, options, site.basket.replace(id));
		js-add-basket-btn($(this));
	});



	$(".clear_link").click(function(){
		basket.removeAll(site.basket.replace(666));
		return false;
	});


	$(".cart_info").on("click",".delete a",function(){
		var id = $(this).data("id");
		basket.removeItem(id, site.basket.replace(id));
		return false;
	});


	$(".cart_info").on("change",".amount input",function(e){
		e.preventDefault();
		var input = $(this),
		amount = $(input).val(),
		amount = parseFloat(amount.replace(/.,\D+/g,"").trim()),
		id = $(input).data("id");
		basket.modifyItem(id, {amount:amount}, site.basket.replace(id));
	});

	$("section.cart_info").on("click",".b-basket__remove",function(){
		var id = $(this).data("id");
		basket.removeItem(id, site.basket.replace(id));
		return false;
	})

	$("select[name=delivery-id]").change(function(){
		var delivery_price = parseFloat($(this).find("option:selected").data("price")),
		cart_all_summary = $(".cart_all_summary_original:eq(0)").text();
		cart_all_summary = parseFloat(cart_all_summary.replace(/\s/g,""));
		if(!delivery_price){
			delivery_price = 0;
		}
		var all_price = cart_all_summary + delivery_price;
		$(".delivery_price").text(number_format(delivery_price, 0, '', ' '));
		$(".cart_all_summary_with_delivery").text(number_format(all_price, 0, '', ' '));

		var type = $(this).find("option:selected").data("type");
		if(type == "self"){
			$(".address_form input,.address_form textarea").prop("disabled","disabled");
			$(".address_form").stop().hide();
		}else{
            $(".address_form input,.address_form textarea").prop("disabled",false);
            $(".address_form").stop().show();
		}

		var payments = $(this).find("option:selected").data("payments"),
		payments = payments.split(',');
		if(payments.length){
            $("select[name='payment-id'] option").prop("disabled","disabled")
			for(var i in payments){
            	var a = $("select[name='payment-id'] option[value='"+payments[i]+"']");
                $("select[name='payment-id'] option[value='"+payments[i]+"']").prop("disabled",false);

			}
			if($("select[name='payment-id'] option:disabled:selected").length){
                $("select[name='payment-id'] option:disabled:selected").prop("selected",false)
                $("select[name='payment-id'] option:visible").prop("selected","selected")
			}

		}else{
			$("select[name='payment-id'] option").prop("disabled",false)
		}
        $("select[name='payment-id']").niceSelect('update');

	});

	$("select[name='payment-id']").change(function(){
        $.ajax({
            url: "/emarket/setOrderPayment.json",
            data: {
                pid: $(this).val(),
            },
            dataType: 'json',
            success: function(data){
                basket.get(site.basket.replace(666));
            }
        });
	});


    $("select[name=delivery-id],select[name='payment-id']").trigger("change");


	$("#saveInfoCustom").submit(function(){
		var form = $(this);



		checkForm(form);
		var valid = false;







		if ($(form).find(".error").length == 0) {
			valid = true;
		}




		return valid;
	});


};

jQuery(document).ready(function(){site.basket.init()});


function declOfNum(number, titles)
{
    cases = [2, 0, 1, 1, 1, 2];
    return titles[ (number%100>4 && number%100<20)? 2 : cases[(number%10<5)?number%10:5] ];
}
function number_format( number, decimals, dec_point, thousands_sep ) {	// Format a number with grouped thousands
	//
	// +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +	 bugfix by: Michael White (http://crestidg.com)

	var i, j, kw, kd, km;

	// input sanitation & defaults
	if( isNaN(decimals = Math.abs(decimals)) ){
		decimals = 2;
	}
	if( dec_point == undefined ){
		dec_point = ",";
	}
	if( thousands_sep == undefined ){
		thousands_sep = ".";
	}

	i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

	if( (j = i.length) > 3 ){
		j = j % 3;
	} else{
		j = 0;
	}

	km = (j ? i.substr(0, j) + thousands_sep : "");
	kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
	//kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
	kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


	return km + kw + kd;
}
