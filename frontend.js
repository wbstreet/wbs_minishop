/*

Created by Konstantin Polyakov for WebsiteBaker.
License: public domain :)

*/

mod_minishop_current_prodform = null;

function mod_minishop_isHasFavourite(org_id) {
	return localStorage.hasOwnProperty(org_id);
}

/* -------   Корзина ------------- */

function mod_minishop_get_product_from_list(prod_id) {
    var prod_div = document.getElementById('product'+prod_id);
    return {
        'count': prod_div.getElementsByClassName('product_count2cart')[0].value,
        'short_description': prod_div.getElementsByClassName('product_short_description')[0].textContent,
        'price': prod_div.getElementsByClassName('product_price')[0].textContent,
        'title': prod_div.getElementsByClassName('product_title')[0].textContent
    };
}
function mod_minishop_collect_data_from_localStorage(res, name) {
    var obj_regexp = new RegExp(name+"_[0-9]+" );

    if(res=='assoc') var objs = {};
    else if (res == 'array') var objs = [];
    else if (res == 'count') var objs = 0;

    for (var key in localStorage) {
        if (key.search(obj_regexp) == -1) continue;
        var obj_id = key.split('_'); obj_id = Number(obj_id[obj_id.length-1]);
        if(res=='assoc') objs[obj_id] = JSON.parse(localStorage.getItem(key))
        else if (res == 'array') objs.push(obj_id);
        else if (res == 'count') objs += 1;
    }
    return objs;
}

function mod_minishop_collect_products_from_cart(res='assoc') {
    return mod_minishop_collect_data_from_localStorage(res, 'cart_product');
}

function mod_minishop_collect_orders(res='assoc') {
    return mod_minishop_collect_data_from_localStorage(res, 'order');
}

function mod_minishop_get_count_products_in_cart() {
    return mod_minishop_collect_data_from_localStorage('count', 'cart_product');
}

function mod_minishop_get_product_from_cart(prod_id) {
    
	return JSON.parse(localStorage.getItem('cart_product_'+prod_id));
}


/* -------   Корзина HTML ------------- */

function mod_minishop_set_count_products() {
	"use strict"
	let count = mod_minishop_get_count_products_in_cart();
    let els = document.querySelectorAll('.mod_minishop_count_in_cart');
    for (let el of els) { el.textContent = count; }
}

function mod_minishop_hide_form_product2cart(prod_id) {
    var prod_div = document.getElementById("product"+prod_id);
    if(prod_div !== null) prod_div.getElementsByClassName("count2cart_form")[0].style.display='none';
}

function mod_minishop_create_order(prod_objs, user_data) {
    if (localStorage.hasOwnProperty('current_order_id')) var order_id = Number(localStorage.getItem('current_order_id'))+1
    else var order_id = 1

    var order_obj = {
        "products": prod_objs,
        "user_data": user_data, // информация о пользователе на момент заказа
        "time": new Date().getTime()
    }

    localStorage.setItem('current_order_id', order_id)
    localStorage.setItem('order'+'_'+order_id, JSON.stringify(order_obj))
}


"use strict";

class mod_minishop_Cart {
	constructor() {
	}
	
	add(prod_id, prod_count, show_note=true) {
	    let name = 'cart_product_';
	    let prod_obj = {};
        prod_obj['count'] = prod_count;
        prod_obj['prod_id'] = prod_id;
        localStorage.setItem(name+prod_id, JSON.stringify(prod_obj));
	}

	remove(prod_id) {
	    let name = 'cart_product_';
        localStorage.removeItem(name+prod_id);
	}
	
	get() {
		
	}
	
	get_all() {
		 return mod_minishop_collect_products_from_cart();
	}
}

class mod_minishop_Main {
	
	constructor() {
		this.cart = new mod_minishop_Cart();
		this.url_mod = WB_URL + '/modules/wbs_minishop/'
		this.url_api = this.url_mod + 'api.php';
		
		this.cart_add = this.cart_add.bind(this);
		this.cart_show_form = this.cart_show_form.bind(this);
		this.cart_show_list = this.cart_show_list.bind(this);
		this.cart_recount_list = this.cart_recount_list.bind(this);
		this.order_confirm = this.order_confirm.bind(this);
	}
	
    /*request(api_name, data, sets) {
    	data['action'] = api_name;
    	sets['data'] = data;
    	sets['type'] = 'POST';
        $.ajax(this.mod_url + 'api.php', sets);
    }*/
    
    /*sendform(btn, action, data) {
        data['url'] = this.url_api;
    	sendform(btn, action, data);
    }*/
    
    get_prod_data(el) {
        let prod = [];

        // из html-элемента товара
        let prod_el = el.closest('.product');
        //prod['page_id'] = prod_el.dataset.page_id;
        //prod['section_id'] = prod_el.dataset.section_id;
        prod['prod_id'] = prod_el.dataset.prod_id;
        prod['count_to_cart'] = prod_el.querySelector('.product_count2cart').value;
        prod['tile'] = prod_el;

        // из корзины
        let prod_cart = mod_minishop_get_product_from_cart(prod.prod_id);
        prod['count_in_cart'] = prod_cart !== null ? prod_cart.count : 0;

        // из html-элемента корзины
        
        // из сервера (ajax)

        return prod;
    }

    cart_show_form(btn) {
        let prod = this.get_prod_data(btn);

        let span =  prod.tile.querySelector(".count2cart_form");
        if (mod_minishop_current_prodform != null) mod_minishop_hide_form_product2cart(mod_minishop_current_prodform);
        if (mod_minishop_current_prodform == prod.prod_id) {mod_minishop_current_prodform = null; return;}

        if (getComputedStyle(span).display=='none') {
            console.log(prod);
            span.style.display = 'block';
            span.querySelector('.product_count2cart').value = prod.count_in_cart;
        }

        mod_minishop_current_prodform = prod.prod_id;
    }
    
    cart_add(btn) {
        console.log(btn);
        let prod = this.get_prod_data(btn);
        console.log(prod);

        prod.count_to_cart = Number(prod.count_to_cart);
	    if (prod.count_to_cart <= 0) {
	        this.cart.remove(prod.prod_id);
	        showNotification('Удалено из корзины', 'note', 1000);
	    } else {
	        this.cart.add(prod.prod_id, prod.count_to_cart);
	        showNotification('Добавлено в корзину', 'note', 1000);
	    }

        //mod_minishop_show_menu_cart(); 
        mod_minishop_set_count_products();
        mod_minishop_hide_form_product2cart(prod.prod_id);
    }
    
    cart_show_list() {
    	//if (getComputedStyle(el_for_insert).display == "none") {el_for_insert.style.display = "block";}
    	//else {el_for_insert.style.display = "none"; return;}

        let el_for_insert = document.getElementById('product_cart_list2');
	    el_for_insert.innerHTML = "";

	    let prod_objs = this.cart.get_all();

        sendform(document.createElement('p'), 'get_product_data', {
        	data: {products: JSON.stringify(prod_objs)},
        	answer_type: 'Notification',
            url:this.url_api,
            arg_func_success: prod_objs,
            func_success: function(res, prod_objs) {
			    let prod_html = '';
			    let total_price = 0
			    let i = 0;
			    for (let prod_id in res['data']) {
			    	let prod = res['data'][prod_id];
			    	let prod_cart = prod_objs[prod.prod_id];

			        prod_html += `<tr id="cartlist${prod.prod_id}" data-prod_id="${prod.prod_id}">
			        <td>${i+1}. </td>
			        <td>${prod.prod_title}</td>
			        <td>${prod.prod_price}</td>
			        <td class="prod_count_cart_list"><input onchange="mod_minishop.cart.add(${prod.prod_id}, this.value); mod_minishop.cart_show_list()" type="number" value="${prod_cart.count}"></td>
			        <td class="button_delete_prod_from_cart" onclick="mod_minishop.cart.remove(${prod.prod_id}); mod_minishop_set_count_products(); mod_minishop.cart_show_list(); ">X</td>
			        </tr>`;
		
			        total_price += prod_cart.count * prod.prod_price
			        i += 1;
			    }
			    document.getElementById('total_price').innerHTML = total_price
			    el_for_insert.innerHTML = prod_html
			    //mod_minishop_show_menu_user_data(2);            	
            }
        });
    }
    
    cart_recount_list() {
	    let pcl = document.getElementById('product_cart_list2').children;
	    for (let i=0; i< pcl.length; i++) {
	        let prod_tag = pcl[i];
	        let prod_count = prod_tag.querySelector('.prod_count_cart_list').children[0].value;
	        if (prod_count == 0) {prod_tag.remove();  i-=1;}
	        this.cart.add(prod_tag.dataset.prod_id, prod_count);
	    }
	    mod_minishop_set_count_products();
	    this.cart_show_list();
	}
	
	order_confirm(btn) {
		let prod_objs = this.cart.get_all();

		sendform(btn, 'content_confirm_order', {
			data:{
				products: JSON.stringify(prod_objs)
			},
			arg_func_success: [prod_objs, this],
			func_success: function(res, arg) {
				for (let prod_id in arg[0]) { arg[1].cart.remove(prod_id); }
        	    mod_minishop_set_count_products();
			},
			url:this.url_api,
		});
	}
    
}