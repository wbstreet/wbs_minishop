/*

Created by Konstantin Polyakov for WebsiteBaker.
License: public domain :)

*/

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
                this.cart_show_list = this.cart_show_list.bind(this);
                this.cart_recount_list = this.cart_recount_list.bind(this);
                this.order_confirm = this.order_confirm.bind(this);
                this.order_do = this.order_do.bind(this);
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

    cart_add(btn) {
        let prod = this.get_prod_data(btn);
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
    }

    cart_show_list() {
        var w = W.open_by_api('get_cart_list', {
        	add_sheet:  true,
        	max_count: 1,
                data:       {products: JSON.stringify(this.cart.get_all()), section_id:section_id, page_id:page_id},
        	url:        this.url_api
        });
    }
    
    cart_recount_list() {
            let pcl = document.getElementById('product_cart_list2').children;
            for (let i=0; i< pcl.length; i++) {
                let prod_tag = pcl[i];
                let prod_count = prod_tag.querySelector('.prod_count_cart_list').children[0].value;
                if (prod_count === 0) {prod_tag.remove();  i-=1;}
                this.cart.add(prod_tag.dataset.prod_id, prod_count);
            }
            mod_minishop_set_count_products();
            this.cart_show_list();
        }

    order_show_list() {
        var w = W.open_by_api('get_order_list', {
        	add_sheet:  true,
        	max_count: 1,
        	url:       this.url_api
        });
    }

    order_do(btn, order_action) {
    	
    	var action = 'order_'+order_action
    	
    	if (order_action == "cancel") {
    	
        	if (!confirm("Вы действительно хотите отменить заказ?")) return;

    	    sendform(null, action, {
        		url:this.url_api,
        		data:{order_id:btn.closest('tr').dataset.order_id},
    	    	arg_func_success: btn,
    		    func_success: function(res, btn) {
    		    	btn.closest('tr').querySelector('.orders_text_cancelled').style.display = "inline-block";
    		    	btn.closest('tr').querySelector('.orders_btn_pay').remove();
    		    	btn.remove();
        		}
        	});

    	} else if (order_action == "pay") {
    		
    	    sendform(null, action, {
        		url:this.url_api,
        		data:{order_id:btn.closest('tr').dataset.order_id},
    	    	arg_func_success: btn,
    		    func_success: function(res, btn) {
    		    	btn.closest('tr').querySelector('.orders_text_payed').style.display = "inline-block";
    		    	btn.closest('tr').querySelector('.orders_btn_cancel').remove();
    		    	btn.remove();
        		}
        	});
    		
    	} else if (order_action == "ship") {

        	if (!confirm("Вы действительно хотите подтвердить доставку заказа?")) return;

    	    sendform(null, action, {
        		url:this.url_api,
        		data:{order_id:btn.closest('tr').dataset.order_id},
    	    	arg_func_success: btn,
    		    func_success: function(res, btn) {
    		    	btn.closest('tr').querySelector('.orders_text_shipped').style.display = "inline-block";
    		    	btn.remove();
        		}
        	});

    	} else if (order_action == "send_w") {

            var w = W.open_by_api('get_order_mark_sended', {
            	add_sheet:  true,
            	max_count: 1,
        	    url:       this.url_api,
        	    data:{order_id:btn.closest('tr').dataset.order_id}
            });

    	} else if (order_action == "send") {

    	    sendform(btn, action, {url:this.url_api});

    	}
    }
    
    order_confirm(btn) {
                let prod_objs = this.cart.get_all();

                sendform(btn, 'content_confirm_order', {
                        data:{
                            products: JSON.stringify(prod_objs),
                         section_id:section_id, page_id:page_id
                        },
                        arg_func_success: [prod_objs, this],
                        func_success: function(res, arg) {
                                for (let prod_id in arg[0]) { arg[1].cart.remove(prod_id); }
                    mod_minishop_set_count_products();
                        },
                        wb_captcha_img: btn.parentElement.querySelector('img'),
                        url:this.url_api,
                });
        }
    
}