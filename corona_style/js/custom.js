$(function(){
    $("#robox").trigger("submit");
	
	$('.filter > .form > .mobile_filter').on('click', function(){
		$(this).slideUp(500);
		$('.filter > .form > .filter').slideDown(500);
	})
    /*
    $(".property_el").change(function(){
        var size_el = $("input[name='size']:checked"),
            color_el = $("input[name='color']:checked");
        if($(size_el).length && $(color_el).length){
            var size = $(size_el).val(),
                color = $(color_el).val();
            for(var i in pages){
                var item = pages[i];
                if((item['sizeId'] == size) && (item['colorId'] == color)){
                    window.location.href = item['link'];
                    return true;
                }
            }
        }

        if($(size_el).length){
            var size = $(size_el).val(),
            colors = [];
            $("input[name='size']:not(:checked)").prop("disabled","disabled");
            $("input[name='color']").prop("disabled","disabled");
            for(var i in pages){
                var item = pages[i];
                if((item['sizeId'] == size)){
                    $("#product_size_check"+item['colorId']).prop("disabled",false).removeProp("disabled");
                }
            }
        }

        if($(color_el).length){
            var color = $(color_el).val(),
                sizes = [];
            $("input[name='color']:not(:checked)").prop("disabled","disabled");
            $("input[name='size']").prop("disabled","disabled");
            for(var i in pages){
                var item = pages[i];
                if((item['colorId'] == color)){
                    $("#product_size_check"+item['sizeId']).prop("disabled",false).removeProp("disabled");
                }
            }
        }

        $(".product_info .reset").stop().show();

    });

    $(".product_info .reset").click(function(){
        $(this).stop().hide();
        $("input[name='size'],input[name='color']").prop("checked",false).prop("disabled",false).removeProp("checked").removeProp("disabled");
        return false;
    });
    */

    $("input[name=delivery-id]").change(function(){
        val = $(this).val();
        switch(val){
            case "2125":{
                $(".delivery_form").stop().hide();
                $(".delivery_form").find("input").prop("disabled","disabled");
            }; break;
            case "2126":
            case "2127":{
                $(".delivery_form").stop().show();
                $(".delivery_form").find("input").prop("disabled",false).removeProp("disabled");
            }; break;
        }
    });
    $("input[name=delivery-id]:checked").trigger("change");

    $(".sorting select").change(function(){
        $(this).parents("form").trigger("submit");
    });

    $(".quike_buy_link").click(function(){
        $("#form_product_link_607").val(window.location.href);
        $("#form_product_name_608").val($("h1.product_name").text().trim());
        $("#form_product_sku_609").val($(".product_info").find(".articul").text().trim());
        var size = $('input[name=product_size]:checked');
        if($(size).length){
            $("#form_product_size_610").val($(size).next('label').text().trim());
        }
    });

    $(".subscribe_doCustom").submit(function(){
        var form = $(this);
        valid = checkForm(form);
        if (!valid) {
            return false;
        }
        $(this).next().fadeIn()

        clearTimeout( timer )
        var timer = setTimeout(function(){
            $('.subscribe .success').fadeOut(300)
        }, 2000)
        return false;
    });

    $("body").on("click",".reset_promo",function(e){
        $.ajax({
            url: "/emarket/resetPromo",
            dataType: 'json',
            success: function(data){
                basket.get(site.basket.replace(666));
            }
        });
        return false;
    });


    $("form.promo").submit(function(){
        var input = $("input.promo_val"),
            val = $(input).val();
        if (val == "") {
            $(input).addClass("error");
        }else{
            $.ajax({
                url: "/emarket/setPromo",
                dataType: 'json',
                data: {
                    promo: val
                },
                success: function(data){
                    if (data.status === "ok") {
                        $(input).removeClass("error");
                        basket.get(site.basket.replace(666));
                    }else{
                        $(input).addClass("error");
                    }
                }
            });
        }

        return false;
    });
/*
    $("input[name='search_string']").autocomplete({
        source: function( request, response ) {
            $.ajax( {
                url: "/udata/catalog/searchProduct.json",
                dataType: "json",
                data: {
                    term: request.term
                },
                success: function( data ) {
                    var data_array = new Array();
                    if(typeof data.names !== "undefined"){
                        for(var i in data.names.item){
                            var item = data.names.item[i];
                            data_array.push({
                                'id': item.name,
                                'label': item.name,
                                'value': item.name,
                            })
                        }
                    }

                    response( data_array );
                }
            } );
        },
        minLength: 4,
        select: function( event, ui ) {
            //  log( "Selected: " + ui.item.value + " aka " + ui.item.id );
        }
    } );
    */

	$("input[name='delivery-address']").change(function(){
		if($(this).val() == "new"){
			var a =$(".delivery_address input");
			$(".delivery_address input").removeAttr('disabled');
		}else{
            $(".delivery_address input").prop('disabled','disabled');
		}
	});
    $("input[name='delivery-address']:checked").trigger("change");

	$("select[name=fields]").change(function(){
		$(this).parents("form").trigger("submit");
	});

	$(".product_info .amount input").change(function(){
		var val = $(this).val(),
		val = val.replace(/\D+/g,""),
		price = $(this).data("price"),
		summary = val*price;
		$('.summary_price').text(number_format(summary, 0, ' ', ' '));
	});


    $(".personal_form").submit(function(e) {
        var form = $(this);
        checkForm(form);
        if ($(form).find(".error").length !== 0) {
            e.preventDefault();
        }
    });

    $(".forget_do").submit(function(e){
        var form = $(this);
        checkForm(form);
        $(form).find(".error_msg").html("");
        if ($(form).find(".error").length === 0) {
            $.ajax({
                url: $(form).attr("action"),
                dataType: 'json',
                data: $(form).serialize(),
                success: function(data){
                    if (data.status === "ok") {
                        //location.reload();


                        $.fancybox.close();
                        $.fancybox.open({
                            src  : '#forgot_form_success',
                            type : 'inline',
                            opts : {
                                speed: 300,focus : false,
                                margin: [20,0],
                                slideShow : false,
                                fullScreen : false,
                                thumbs : false
                            }
                        })

                    }else{
                       // $(form).find(".required").addClass("error");
                        $(form).find(".error_msg").html("Пользователь с такой почтой не найден.");
                    }
                }
            });
        }

        return false;
    })

    $(".reg_do").submit(function(e){
        var form = $(this);
        $(form).find(".error").removeClass("error");
        $(form).find(".error_msg").html('');
        checkForm(form);
        if ($(form).find(".error").length === 0) {
            $.ajax({
                url: $(form).attr("action"),
                dataType: 'json',
                data: $(form).serialize(),
                success: function(data){
                    if (data.status === "ok") {
                        //location.reload();


                        $.fancybox.close();
                        $.fancybox.open({
                            src  : '#register_form_success',
                            type : 'inline',
                            opts : {
                                speed: 300,focus : false,
                                margin: [20,0],
                                slideShow : false,
                                fullScreen : false,
                                thumbs : false
                            }
                        })

                    }else{
                        //$(form).find("input.required,textarea.required").addClass("error");
                        for(var i in data.msg){
                            var item = data.msg[i];
                            $(form).find(".error_msg").append('<p>'+item.msg+'</p>');
                        }

                    }
                }
            });
        }

        return false;
    })

    $(".auth_do").submit(function(e){
        var form = $(this);
        $(form).find(".error").removeClass("error");
        $(form).find(".error_msg").html("");
        checkForm(form);
        if ($(form).find(".error").length === 0) {
            $.ajax({
                url: $(form).attr("action"),
                dataType: 'json',
                data: $(form).serialize(),
                success: function(data){
                    if (data.status === "ok") {
                        location.reload();
                    }else{
                        $(form).find(".error_msg").html("<p>Ошибка авторизации</p>");
                        $(form).find(".required").parents(".line").addClass("error");
                    }
                }
            });
        }

        return false;
    })


    $(".password_form").submit(function(e){
        var form = $(this);
        checkForm(form);
        $(form).find(".error_msg").html('');
        if ($(form).find(".error").length === 0) {
            $.ajax({
                url: $(form).attr("action"),
                dataType: 'json',
                data: $(form).serialize(),
                success: function(data){
                    if (data.status === "ok") {
                        location.reload();
                    }else{
                        $(form).find(".msg").html(data.msg);


                        e.preventDefault()

                        $.fancybox.close()

                        $.fancybox.open({
                            src  : '#password_form',
                            type : 'inline',
                            opts : {
                                speed: 300,
                                margin: [20,0],
                                slideShow : false,
                                fullScreen : false,
                                thumbs : false
                            }
                        })


                    }
                }
            });
        }

        return false;
    })

	$('.webforms').submit(function(e){
		e.preventDefault();

		var form = $(this);
		checkForm(form);
		if ($(form).find(".error").length === 0) {
		    $.ajax({
			url: $(form).attr("action"),
			dataType: 'json',
			data: $(form).serialize(),
			success: function(data){
			    if (data.status === "ok") {
						//location.reload();

						var success = "#success_modal";
						if($(form).data('success')){
							success = $(form).data('success');
						}
						$.fancybox.close()

						$.fancybox.open({
							src  : success,
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
						$(form).find(".required").addClass("error");
			    }
			}
		  });
		}

		return false;
	});

});


function checkForm(form){
	var valid = true;
	$(form).find(".error_text").remove();
	$.each($(form).find("input.required:visible:enabled,textarea.required:visible:enabled,input[type=checkbox].required"),function(i,required){
		valid = checkInput(this)
	});
	return valid;
}

function checkInput(element){
	valid = true;
	var input = $(element), parent = $(input).parent();
	if ($(input).attr("type") == "checkbox") {
		if (!$(input).is(":checked")) {
			$(parent).addClass("error").append('<div class="error_text">Это поле необходимо заполнить</div>');
			valid = false;
		}else{
			$(parent).removeClass("error");
			$(parent).find(".error_text").remove();
		}
	}else{
		if ($(input).val() === "") {
				$(parent).addClass("error").append('<div class="error_text">Это поле необходимо заполнить</div>');
			valid = false;
		}else{
			$(parent).removeClass("error");
			$(parent).find(".error_text").remove();
		}
	}
	return valid;
}
