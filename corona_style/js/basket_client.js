var basket = {
	get : function(callback) {
		basket.__request("/udata/emarket/basketCustom.json", {}, callback);
	},
	addCompare : function(id) {
		basket.__request("/udata/emarket/addToCompareNew/"+id+".json");
	},
	delCompare : function(id) {
		basket.__request("/udata/emarket/removeFromCompareCustom/"+id+".json");
	},
	delAllCompare : function(callback) {
		basket.__request("/udata/emarket/clear_compare/.json",{},callback);
	},

	getCompareList : function(callback) {
		basket.__request("/udata/emarket/getCompareElementsCustom.json", {}, callback);
	},

	getCompareCategory : function(callback) {
		basket.__request("/udata/emarket/getCompareElementsCategory.json", {}, callback);
	},

	addWish : function(id) {
		basket.__request("/udata/emarket/addWish/"+id+".json");
	},
	delWish : function(id) {
		basket.__request("/udata/emarket/delWish/"+id+".json");
	},
	getWishList : function(callback) {
		basket.__request("/udata/emarket/getWishList.json", {}, callback);
	},
	putElement : function(id, options, callback) {
		basket.__request("/udata/emarket/basketCustom/put/element/" + id + ".json", options, callback);
	},
    putSample : function(id, options, callback) {
        basket.__request("/udata/emarket/basketCustom/put/sample/" + id + ".json", options, callback);
    },
	modifyItem : function(id, options, callback) {
		if(options.amount == 0) {
			this.removeItem(id, callback);
			return;
		}
		basket.__request("/udata/emarket/basketCustom/put/item/" + id + ".json", options, callback);
	},
	removeElement : function(id, callback) {
		basket.__request("/udata/emarket/basketCustom/remove/element/" + id + ".json", {}, callback);
	},
	removeItem    : function(id, callback) {
		basket.__request("/udata/emarket/basketCustom/remove/item/" + id + ".json", {}, callback);
	},
	removeAll     : function(callback) {
		basket.__request("/udata/emarket/basketCustom/remove_all.json", {}, callback);
	},
	__cleanupHash : function(input) {
		var output = {
			//customer : input.customer.object.id,
			items    : input.items,
			summary  : input.summary,
			id	:input.id
		};

		if (typeof input.total) {
			output['total'] = input.total;
		}
		if (typeof input.discount) {
			output['discount'] = input.discount;
		}
		return output;
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
			async: site.basket.async,
			data     : basket.__transformOptions(options),
			success  : function(data) {
				if(typeof callback !== "undefined"){
						callback(basket.__cleanupHash(data));
				}

			}
		});
	}
};
