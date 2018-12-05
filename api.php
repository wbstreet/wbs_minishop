<?php
function get_number($string){
    return preg_replace("/[^0-9]+/", '', $string);
}

// settings
$product_image_width = 500;
$product_image_height = 550;
$is_use_product_image_height = false;


$action = isset($_GET['action']) ? $_GET['action'] : $_POST['action'];
$section_id = get_number(isset($_GET['section_id']) ? $_GET['section_id'] : $_POST['section_id']);
$page_id = get_number(isset($_GET['page_id']) ? $_GET['page_id'] : $_POST['page_id']);

$PAGE_SECTION_FIELDS = "<input type='hidden' name='page_id' value='{$page_id}'>
<input type='hidden' name='section_id' value='{$section_id}'>
";

require('../../config.php');
require_once(WB_PATH.'/framework/functions.php');
$admin_header = false;
$update_when_modified = false;

//require_once(WB_PATH.'/framework/wb.php');

include(WB_PATH.'/modules/wbs_minishop/lib.class.minishop.php');
$clsMinishop = new ModMinishop($page_id, $section_id);

function check_cart() {
	global $clsFilter;
	
    $cart_prods = json_decode($clsFilter->f('products', [['1', "Не добавлены товары в корзину!"]], "fatal"), true);
	
    $prod_ids = [];
    foreach ($cart_prods as $i => $data) {
    	$prod_ids[] = $clsFilter->f2($data, 'prod_id', [['integer', "Неправильный идентификатор товара!"]], 'fatal');
    	$clsFilter->f2($data, 'count', [['integer', "Неправильное количество товара!"]], 'fatal');
    }
    $prod_ids = implode(',', $prod_ids);
    
    return [$cart_prods, $prod_ids];
}

function get_order($sets, $only_count=false) {
	global $clsMinishop;

	$r = $clsMinishop->get_order($sets, $only_count);
	if (gettype($r) === "string") print_error($r);
	if ($r === null) print_error("Заказ не найден!");
	return $r->fetchRow(MYSQL_ASSOC);
}

function update_order($order_id, $name, $value) {
	global $clsMinishop, $database;
	
	$sql = "UPDATE {$clsMinishop->tbl_order} SET ".process_key($name)."=".process_value($value)." WHERE `order_id`=".process_value($order_id);
	$r = $database->query($sql);
	if ($database->is_error()) print_error($database->get_error());
}


if ($action == 'content_confirm_order') {
	
	$page_id = 1;
    require(WB_PATH.'/modules/admin.php');

    $minishop_settings = $clsMinishop->get_settings();

	$i_agree = $clsFilter->f('i_agree', [['variants', "Вы должны согласитиься с пользовательским соглашением!", ['true']]], 'fatal', '');
	$captcha = $clsFilter->f('captcha', [['1', "Введите Защитный код!"], ['variants', "Введите Защитный код!", [$_SESSION['captcha']]]], 'fatal', '');

    // принять данные корзины

    list($products, $prod_ids) = check_cart();
    if (count($products) == 0) print_error("Не выбраны товары!");

    if ($minishop_settings['need_registration'] == '0') {

    	$fio_first = $clsFilter->f('fio_first', [['1', "Вы не указали Ваше имя!"]], 'append', '');
    	$fio_second = $clsFilter->f('fio_second', [['1', "Вы не указали Вашу фамилию!"]], 'append', '');
    	$fio_third = $clsFilter->f('fio_third', [['1', "Вы не указали Ваше отчество!"]], 'append', '');
        $fio = $fio_first .' '. $fio_second .' '. $fio_third ;

    	$phone = $clsFilter->f('phone', [['1', "Вы не указали Ваш номер телефона!"]], 'append', '');
	    $delivery = $clsFilter->f('delivery', [['variants', "Не указан способ доставки!", ["self", "deliv"]]], 'append', '');
            if ($delivery=='deliv') {
                if ($minishop_settings['has_delivery'] == '0') print_error("Доставка недоступна!");
                $delivery_address = [
                        'country'    => 'Россия',/*$clsFilter->f('delivery_addr_country', [['1', "Не указана страна"]],              'append', ''),*/
                        'region'     => $clsFilter->f('delivery_addr_region', [['1', "Не указан регион"]], '               append', ''),
                        'settlement' => $clsFilter->f('delivery_addr_settleement', [['1', "Не указан населённый пункт"]], 'append', ''),
                        'street'     => $clsFilter->f('delivery_addr_street', [['1', "Не указана улица"]],                'append', ''),
                        'building'   => $clsFilter->f('delivery_addr_building', [['1', "Не указан номер дома"]],          'append', ''),
                        'sector'     => $clsFilter->f('delivery_addr_sector', [['1', "Не указан корпус"]],                'default', ''),
                        'flat'       => $clsFilter->f('delivery_addr_flat', [['1', "Не указана квартира"]],               'default', ''),
                ];
            } else {
                if ($minishop_settings['has_self_delivery'] == '0') print_error("Самовывоз недоступен!");
                $delivery_address = "";
            }

		if ($clsFilter->is_error()) $clsFilter->print_error();
	
		$comment = $clsFilter->f('comment', [['1', ""]], 'default', '');
		
	    if ($delivery == 'self') $delivery = 'самовывоз';
	    else if ($delivery == 'deliv') $delivery = 'доставка';
	
	    // Определяем сайт
	    list($url, $is_true) = idn_decode(WB_URL);
	
	    // формирование тела письма
	
	    $prods = [];
	    $sql = "SELECT `prod_id`, `prod_category_id`, `prod_title`, `prod_shortdesc`, `prod_price`, `prod_is_active`, `prod_count` FROM ".$clsMinishop->tbl_products." WHERE `prod_id` IN ({$prod_ids})";
	    //print_error($sql);
	    $r = $database->query($sql);
	    if ($database->is_error(0)) print_error($database->get_error());
	    while($row = $r->fetchRow(MYSQLI_ASSOC)) {
	    	$row['prod_price'] /= 100;
	    	$row['count_to_order'] = (int)($products[$row['prod_id']]['count']);
	    	$prods[$row['prod_id']] = $row;
	    }
	
	    // отправка письма
	
	    /*$body = $clsMinishop->render('letter_order.twig', [
	    	'fio'=>$fio,
	    	'phone'=>$phone,
	    	'prods'=>$prods,
	    	'comment'=>$comment,
	    	'delivery'=>$delivery,
	    	'delivery_address'=>$delivery_address,
	    ], true);
	
	    $r = $clsEmail->send(
	
	        $minishop_settings['admin_email'],
	
	        $body,
	
	        "Заказ из магазина $url",
	
	        0, false
	
	    );*/
	    
            $vars = [
                'fio'=>$fio,
                'phone'=>$phone,
                'prods'=>$prods,
                'comment'=>$comment,
                'delivery'=>$delivery,
                'delivery_address'=>$delivery_address,
                'url'=>$url
            ];

        
            $r = $clsEmail->send_template(
                $minishop_settings['admin_email'],
                $clsMinishop->name."_order",
                $vars

            );
	
	    if ($r[0] !== true) print_error('Письмо не отправлено! ');
	    
	    print_success('Заказ успешно отправлен Администратору магазина');
	
    } else if ($minishop_settings['need_registration'] == '1') {

        // проверить авторизованность. Если не авторизхован, то предложить форму авторизации.
        //if (!$admin->is_authoritated()) print_error('Для совершения заказа необходимо авторизоваться!');

        // получить данные текущего пользователя

        $sql = "SELECT * FROM `".TABLE_PREFIX."users` WHERE `user_id`=".process_value($admin->get_user_id());
        $r = $database->query($sql);
        if ($database->is_error()) print_error($database->get_error());
        if ($r->numRows() == 0) print_error('Пользователь не найден!');
        $user = $r->fetchRow();
        
        // добавить запись заказа
        
        $row = ['user_id'=>$user['user_id']];
        $r = insert_row($clsMinishop->tbl_order, $row);
        if ($r === false) print_error('Ошибка создания заказа');
        if (gettype($r) === 'string') print_error($r);
        $order_id = $database->getLastInsertId();

        // сделать копии товаров

        $prods = [];
        $sql = "SELECT * FROM ".$clsMinishop->tbl_products." WHERE `prod_id` IN (".$prod_ids.")";
        $r = $database->query($sql);
        if ($database->is_error()) print_error($database->get_error());
        if ($r->numRows() == 0) print_error('Товары не найдены!');
        while ($row = $r->fetchRow(MYSQL_ASSOC)) {
        	$row['is_copy_for'] = $row['prod_id'];
        	unset($row['prod_id']);
            $prods[] = $row;
        }
        
        $copy_prods = [];
        foreach ($prods as $i=>$prod) {
            $r = insert_row($clsMinishop->tbl_products, $prod);
            if ($r === false) print_error('Ошибка создания заказа');
            if (gettype($r) === 'string') print_error($r);
            $copy_prods[$prod['is_copy_for']] = $database->getLastInsertId();
        }
        // сохранить данные корзины

        $fields = ['order_id', 'copy_prod_id', 'cart_count'];
        $rows_values = [];
        
        foreach($products as $prod_id => $cart) {
            $rows_values[] = [$order_id, $copy_prods[$prod_id], $cart['count']];
        }
        $r = insert_row($clsMinishop->tbl_order_prods, $fields, $rows_values);
        if ($r === false) print_error('Ошибка сохранения заказа');
        if (gettype($r) === 'string') print_error($r);

        // отправить по письму о заказе (пользователю и администратору)

	    print_success('Заказ успешно оформлен. Необходимо совершить оплату заказа.');

    }

} else if ($action == 'get_product_data') {

    list($products, $prod_ids) = check_cart();
    if (count($products) == 0) print_success("Успешно", ['data'=>[]]);

    $products = [];
    $sql = "SELECT `prod_id`, `prod_category_id`, `prod_title`, `prod_shortdesc`, `prod_price`, `prod_is_active`, `prod_count` FROM ".$clsMinishop->tbl_products." WHERE `prod_id` IN ({$prod_ids})";
    //print_error($sql);
    $r = $database->query($sql);
    if ($database->is_error(0)) print_error($database->get_error());
    while($row = $r->fetchRow(MYSQLI_ASSOC)) {
    	$row['prod_price'] /= 100;
    	$products[$row['prod_id']] = $row;
    }

    print_success("Успешно", ['data'=>$products]);

} else if ($action == 'get_cart_list') {

    $opts = ['title'=>'Корзина'];

    // проверяем корзину

    list($cart_prods, $prod_ids) = check_cart();
    if (count($cart_prods) == 0) print_success("Корзина пуста. Начните добавлять товары!", $opts);

    // получаем товары

    $prods = [];
    $sql = "SELECT `prod_id`, `prod_category_id`, `prod_title`, `prod_shortdesc`, `prod_price`, `prod_is_active`, `prod_count` FROM ".$clsMinishop->tbl_products." WHERE `prod_id` IN ({$prod_ids})";
    $r = $database->query($sql);
    if ($database->is_error()) print_error($database->get_error());
    while($row = $r->fetchRow()) {
    	$row['prod_price'] /= 100;
    	$row['cart_count'] = $cart_prods[$row['prod_id']]['count'];
    	$prods[$row['prod_id']] = $row;
    }
    
    // получаем капчу
    
    require_once(WB_PATH.'/include/captcha/captcha.php');
    
    ob_start();
    call_captcha('image_iframe'); echo ' = '; call_captcha('input');
    $captcha = ob_get_contents();
    ob_end_clean();
    
    // отображаем

    print_success($clsMinishop->render('frontend_cart.twig', [
    	'cart_prods'=>$cart_prods,
    	'prods'=>$prods,
    	'captcha'=>$captcha,
    	'settings'=> $clsMinishop->get_settings(),
    ], true), $opts);

} else if ($action == 'get_order_list') {

	$page_id = 1;
    require(WB_PATH.'/modules/admin.php');
    $is_admin = $admin->get_user_id() == 1 ? true : false;

    $opts = ['title'=>'Мои заказы'];
    
    // Извлекаем заказы
    
    $orders = [];
    $sql = "SELECT * FROM ".$clsMinishop->tbl_order;
    if (!$is_admin) $sql .= " WHERE `user_id`=".process_value($admin->get_user_id());
    $r = $database->query($sql);
    if ($database->is_error()) print_error($database->get_error());
    if ($r->numRows() == 0) print_error('Вы не сделали ни одного заказа.');
    while ($order = $r->fetchRow(MYSQL_ASSOC)) {

    	$order['prods'] = [];
    	
	    $sql = "SELECT * FROM ".$clsMinishop->tbl_products.", ".$clsMinishop->tbl_order_prods." WHERE ".$clsMinishop->tbl_products.".`prod_id`=".$clsMinishop->tbl_order_prods.".`copy_prod_id` AND ".$clsMinishop->tbl_order_prods.".`order_id`=".process_value($order['order_id']);
	    $r2 = $database->query($sql);
	    if ($database->is_error()) print_error($database->get_error());
	    while ($prod = $r2->fetchRow(MYSQL_ASSOC)) {
	    	$order['prods'][] = $prod;
	        //print_error(json_encode($prod));
	    }
    	
        $orders[] = $order;
    }

    print_success($clsMinishop->render('frontend_orders.twig', [
    	'orders'=>$orders,
    	'is_admin'=>$is_admin
    ], true), $opts);

} else if ($action == 'order_cancel') {

	$page_id = 1;
    require(WB_PATH.'/modules/admin.php');
    
   	$order_id = $clsFilter->f('order_id', [['integer', "Вы не указали код заказа!"]], 'fatal');

   	// вынимаем данные заказа
   	
	$sets = ['order_id'=>$order_id, 'user_id'=>$admin->get_user_id()];
	$order = get_order($sets);
	
	// Проверяем заказ
	
	if ($order['is_payed'] === '1' || $order['is_shipped'] === '1' || $order['is_sended'] === '1') {
		print_error('Заказ уже нельзя отменить!');
	}
	
	
	if ($order['is_cancelled'] === '1') {
		print_error('Заказ был отменён ранее!');
	}
	
	// Отменяем заказ
	update_order($order_id, 'is_cancelled', 1);

    print_success("Заказ успешно отменён");

} else if ($action == 'order_pay') {

	$page_id = 1;
    require(WB_PATH.'/modules/admin.php');
    
   	$order_id = $clsFilter->f('order_id', [['integer', "Вы не указали код заказа!"]], 'fatal');

   	// вынимаем данные заказа
   	
	$sets = ['order_id'=>$order_id, 'user_id'=>$admin->get_user_id()];
	$order = get_order($sets);
	
	// обновляем 
	update_order($order_id, 'is_payed', 1);

    print_success("Заказ успешно оплачен");

} else if ($action == 'order_send') {

	$page_id = 1;
    require(WB_PATH.'/modules/admin.php');
    $is_admin = $admin->get_user_id() == 1 ? true : false;
    
    if (!$is_admin) print_error('Функция недоступна!');
    
   	$order_id = $clsFilter->f('order_id', [['integer', "Вы не указали код заказа!"]], 'fatal');
   	$track_code = $clsFilter->f('post_track_code', [['1', "Вы не указали номер отслеживания посывлки!"]], 'fatal');

   	// вынимаем данные заказа
   	
	$sets = ['order_id'=>$order_id];
	$order = get_order($sets);
	
	// обновляем 
	update_order($order_id, 'is_sended', 1);
	update_order($order_id, 'post_track_code', $track_code);

    print_success("Заказ успешно помечен как отправлен");

} else if ($action == 'order_ship') {

	$page_id = 1;
    require(WB_PATH.'/modules/admin.php');
    
   	$order_id = $clsFilter->f('order_id', [['integer', "Вы не указали код заказа!"]], 'fatal');

   	// вынимаем данные заказа
   	
	$sets = ['order_id'=>$order_id, 'user_id'=>$admin->get_user_id()];
	$order = get_order($sets);
	
	// обновляем 
	update_order($order_id, 'is_shipped', 1);
	update_order($order_id, 'date_shipped', curtime());

    print_success("Заказ успешно помечен как доставленным");

} else if ($action == 'get_order_mark_sended') {

    $opts = ['title'=>'Пометить заказ как отправленный'];

	$page_id = 1;
    require(WB_PATH.'/modules/admin.php');
    $is_admin = $admin->get_user_id() == 1 ? true : false;
    
    if (!$is_admin) print_error('Функция недоступна!');
    
   	$order_id = $clsFilter->f('order_id', [['integer', "Вы не указали код заказа!"]], 'fatal');

	$order = get_order(['order_id'=>$order_id]);

    print_success($clsMinishop->render('frontend_orders_mark_sended.twig', [
    	'order'=>$order,
    	'is_admin'=>$is_admin
    ], true), $opts);

} else if ($action == 'edit_prop') {

    require(WB_PATH.'/modules/admin.php');

    $prop_id = preg_replace('/[^0-9]/', '', $_POST['prop_id']);
    $value = $database->escapeString($_POST['value']);
    
    $res['e'] = $prop_id.'-'.$value;
    
    $sql = "UPDATE `".TABLE_PREFIX."mod_wbs_minishop_prop` SET `prop_name`='$value' WHERE `prop_id`='$prop_id'";
    if ($database->query($sql)) $res['success'] = true;
    else { $res['success']= false; $res['message'] = $database->get_error();}

} else if ($action == 'window_product_info') {

        
        $product_id = $clsFilter->f('product_id', [['integer', 'Не указан идентификатор товара!']], 'fatal');
        
        // извлекаем данные о товаре
    
    $products = $clsMinishop->get_product(['product_id'=>$product_id]);
    if ($products === null) print_error('Товар не найден!');
    
    $product = $products->fetchRow(MYSQLI_ASSOC);

    // подключаем шаблон
    
    $minishop_settings = $clsMinishop->get_settings();
    
    $clsMinishop->add_loader('array', [
        'block_html' => $minishop_settings['window_html'],
    ]);

    $array_vars = array_merge($clsMinishop->get_product_vars($product), ['section_id'=>$section_id,'page_id'=>$page_id, 'settings'=>$minishop_settings]);
    print_success($clsMinishop->render('frontend_product_wrap.twig', $array_vars, true), ['title'=>$product['prod_title']]);



} else if ($action == 'window_property_edit') {

    require(WB_PATH.'/modules/admin.php');

    $props = [];
    $r = $clsMinishop->get_prop();
    while($r && $prop = $r->fetchRow(MYSQLI_ASSOC)) {

        $_prop_values = [];
        $prop_values = $clsMinishop->get_prop_values($prop['prop_id']);
        while ($prop_values !== null && $prop_value = $prop_values->fetchRow(MYSQLI_ASSOC)) {
        	$_prop_values[] = [
            	'value'=>$prop_value['value'],
        	];
        }

    	$props[] = [
    		'prop_id' => $prop['prop_id'],
    		'prop_name' => $prop['prop_name'],
    		'prop_values'=> $_prop_values,
    	];
    	
    }
	
    print_success(
   	  $clsMinishop->render('properties_edit.twig', [
		'FTAN'=>$admin->getFTAN(),
		'props'=> $props,
		'PAGE_SECTION_FIELDS' => $PAGE_SECTION_FIELDS,
      ], true),
   	  ['title'=>'Управление характеристиками товаров']
   );	

} else if ($action == 'window_category_edit') {

    require(WB_PATH.'/modules/admin.php');

   include('common.php');

    print_success(
   	  $clsMinishop->render('category_edit.twig', [
		'FTAN'=>$admin->getFTAN(),
		'PAGE_SECTION_FIELDS' => $PAGE_SECTION_FIELDS,
		'section_id'=>$section_id,
		'page_id'=>$page_id,
		'categories' => $category_array,
      ], true),
   	  ['title'=>'Управление категориями']
   );	

} else if ($action == 'save_category_edit') {
	
    require(WB_PATH.'/modules/admin.php');
	
    $category_id = $admin->get_post('category_id');
    $category_name = $admin->get_post('category_name');

    if ($category_id==0) {
        $fields = ['category_name'=>$category_name, 'section_id'=>$section_id, 'page_id'=>$page_id];
        $sql = 'INSERT INTO `'.TABLE_PREFIX.'mod_wbs_minishop_categories` ('.glue_keys(array_keys($fields)).') VALUES ('.glue_values(array_values($fields)).')';
        if ($database->query($sql)) print_success("Категория создана!", ['data'=>['id'=>$database->getLastInsertId(), 'name'=>$category_name]]);
        else print_error($database->get_error());
    } else {
        $fields = ['category_name'=>$category_name];
        $forupdate = glue_fields($fields, ',');
        $sql = 'UPDATE `'.TABLE_PREFIX.'mod_wbs_minishop_categories` SET '.$forupdate.'  WHERE `section_id`="'.$database->escapeString($section_id).'" AND `category_id`="'.$database->escapeString($category_id).'"';
        if ($database->query($sql)) print_success("Категория переименована!");
        else print_error($database->get_error());
    }

} else if ($action == 'create_prop') {

    require(WB_PATH.'/modules/admin.php');

    $prop_name = $database->escapeString($_POST['prop_name']);
    $sql = "INSERT INTO `".TABLE_PREFIX."mod_wbs_minishop_prop` (`prop_name`, `section_id`, `page_id`) VALUES ('$prop_name', '$section_id', '$page_id')";
    if ($database->query($sql)) print_success("Создано! Переоткройте окно.");
    else print_error($database->get_error());

} else if ($action == 'create_prop_value') {

    require(WB_PATH.'/modules/admin.php');

    $prop_value = $database->escapeString($_POST['prop_value']);
    $prop_id = $database->escapeString($_POST['prop_id']);
    $sql = "INSERT INTO `".TABLE_PREFIX."mod_wbs_minishop_prop_values` (`prop_id`, `value`, `section_id`, `page_id`) VALUES ('$prop_id', '$prop_value', '$section_id', '$page_id')";
    if ($database->query($sql)) print_success("Добавлено! Переоткройте окно.");
    else print_error($database->get_error());

} else if ($action == 'window_settings_edit') {

    require(WB_PATH.'/modules/admin.php');

    $minishop_settings = $clsMinishop->get_settings();

    print_success(
   	  $clsMinishop->render('settings_edit.twig', [
		'FTAN'=>$admin->getFTAN(),
		'settings'=>$minishop_settings,
		'section_id'=>$section_id,
		'page_id'=>$page_id,
		'PAGE_SECTION_FIELDS' => $PAGE_SECTION_FIELDS,
      ], true),
   	  ['title'=>'Настройки магазина']
   );

} else if ($action == 'save_settings_edit') {
    
    require(WB_PATH.'/modules/admin.php');
	
    $minishop_settings = $clsMinishop->get_settings();
    if ($minishop_settings['is_general_settings'] == 2) {
        $_section_id = 0; $_page_id = 0;
    } else {
        $_section_id = $section_id; $_page_id = $page_id;
    }

    if ($_POST['has_delivery']=='true') {$has_delivery = '1';} else {$has_delivery = '0';}
    if ($_POST['has_self_delivery']=='true') {$has_self_delivery = '1';} else {$has_self_delivery = '0';}

    if ($_POST['show_code_id']=='true') {$show_code_id = '1';} else {$show_code_id = '0';}
    if ($_POST['show_code_vendor']=='true') {$show_code_vendor = '1';} else {$show_code_vendor = '0';}

    if ($_POST['shop_name_use_website_title']=='true') {$shop_name_use_website_title = '1';} else {$shop_name_use_website_title = '0';}
    
    $sql = 'UPDATE `'.TABLE_PREFIX.'mod_wbs_minishop_settings` SET ';
    $sql .= '`admin_email`="'          .$database->escapeString($_POST['admin_email'])          .'", ';
    $sql .= '`admin_login`="'          .$database->escapeString($_POST['admin_login'])          .'", ';
    $sql .= '`shop_name`="'            .$database->escapeString($_POST['shop_name'])            .'", ';
    $sql .= '`shop_org_name`="'        .$database->escapeString($_POST['shop_org_name'])        .'", ';
    $sql .= '`shop_name_use_website_title`="'         .$database->escapeString($shop_name_use_website_title)                  .'", ';
    $sql .= '`block_html`="'           .$database->escapeString($_POST['block_html'])           .'", ';
    $sql .= '`block_css`="'            .$database->escapeString($_POST['block_css'])            .'", ';
    $sql .= '`address_self_delivery`="'.$database->escapeString($_POST['address_self_delivery']).'", ';
    $sql .= '`has_self_delivery`="'    .$database->escapeString($has_self_delivery)             .'", ';
    $sql .= '`has_delivery`="'         .$database->escapeString($has_delivery)                  .'", ';
    $sql .= '`window_html`="'          .$database->escapeString($_POST['window_html'])          .'", ';
    $sql .= '`need_registration`="'          .$database->escapeString($_POST['need_registration'])  .'", ';
    $sql .= '`show_code_id`="'         .$show_code_id  .'", ';
    $sql .= '`show_code_vendor`="'     .$show_code_vendor  .'", ';
    
    if ($minishop_settings['is_general_settings'] == 0) { $sql .= '`is_general_settings`="'  .$database->escapeString($_POST['is_general_settings'])  .'",  '; }
    $sql .= '`window_css`="'           .$database->escapeString($_POST['window_css'])           .'" ';
    $sql .= ' WHERE `section_id`='.$_section_id.' AND `page_id`='.$_page_id;
    if (!$database->query($sql)) $admin->print_error($database->get_error(), $clsMinishop->urlRet);

    if ($minishop_settings['is_general_settings'] != 0) {
        $sql = 'UPDATE `'.TABLE_PREFIX.'mod_wbs_minishop_settings` SET ';
        $sql .= '`is_general_settings`="'  .$database->escapeString($_POST['is_general_settings'])  .'"  ';
        $sql .= ' WHERE `section_id`='.$section_id.' AND `page_id`='.$page_id;
        if (!$database->query($sql)) $admin->print_error($database->get_error(), $clsMinishop->urlRet);
    }

    print_success("Сохранено!");

} else if ($action == 'update_product') {
	
        /* `is_copy_for`=0 необходимо, чтобы не позволить изменить данные копии товара, заказанного пользователем.
        */

    require(WB_PATH.'/modules/admin.php');

    $prod_id = $admin->get_post('prod_id');
    if ($admin->get_post('prod_is_active')==='true') {$is_active = '1';} else {$is_active = '0';}
    $prod_title = $admin->get_post('prod_title');

    // вынимаем товар из базы

    $r = select_row('`'.TABLE_PREFIX.'mod_wbs_minishop_products`', '`prod_title`', "`prod_id`=".process_value($prod_id)." AND `is_copy_for`=0");
    if (gettype($r) === 'string') print_error($r);
    if ($r === null) print_error('Товар не найден!');
    $prod = $r->fetchRow();

    // формируем ссылку

    list($is_error, $prod_link) = createAccessFile($prod_id, $prod_title, $prod['prod_title'], $page_id, $section_id, $clsMinishop->name);
    if ($is_error) {
         print_error($prod_link);
    }

    // обновляем

    $fields = [
        'prod_category_id' => $admin->get_post('prod_category_id'),
        'prod_title' => $prod_title,
        'prod_shortdesc' => $admin->get_post('prod_shortdesc'),
        'prod_desc' => $admin->get_post('prod_desc'),
        'prod_price' => (float)$admin->get_post('prod_price') * 100,
        'prod_is_active' => $is_active,
        'prod_count' => $admin->get_post('prod_count'),
        'prop_value_ids'=>json_encode($_POST['prop_value']),
        'prod_is_hit' => $admin->get_post('prod_is_hit'),
        'prod_link'=> $prod_link,
        'prod_vendor_code' => $admin->get_post('prod_vendor_code'),
        ];

    $forupdate = glue_fields($fields, ',');

    $sql = 'UPDATE `'.TABLE_PREFIX.'mod_wbs_minishop_products` SET '.$forupdate.'  WHERE `section_id`="'.$database->escapeString($section_id).'" AND `prod_id`="'.$database->escapeString($prod_id).' AND `is_copy_for`=0"';
    if ($database->query($sql)) { print_success("Товар обновлён!"); }
    else print_error($database->get_error());

} else if ($action == 'content_form_product') {

    require(WB_PATH.'/modules/admin.php');
    include('common.php');

	$prod_id = $admin->get_post('prod_id');

    $products = $database->query('SELECT * FROM `'.TABLE_PREFIX.'mod_wbs_minishop_products` WHERE `section_id`='.$section_id.' AND `prod_id`="'.$prod_id.'"');
    $product = $products->fetchRow();

    $product['prod_price'] = $product['prod_price'] / 100;

    $prop_value_ids = $product['prop_value_ids'] !== null ? json_decode($product['prop_value_ids']) : [];

    $props = [];
    $r = $clsMinishop->get_prop();
    while($r && $prop = $r->fetchRow(MYSQLI_ASSOC)) {
        $prop_values = $clsMinishop->get_prop_values($prop['prop_id']);
        $prop['values'] = [];
        while ($prop_values && $prop_value = $prop_values->fetchRow(MYSQLI_ASSOC)) {
            $prop_value['checked'] = in_array($prop_value['prop_value_id'], $prop_value_ids) ? 'checked' : '';
            $prop['values'][] = $prop_value;
        }
    	$props[] = $prop;
    }

	print_success($clsMinishop->render('product_edit.twig', [
		'FTAN'=>$admin->getFTAN(),
		'PAGE_SECTION_FIELDS' => $PAGE_SECTION_FIELDS,
		'product' => $product,
		'page_id' => $page_id,
		'section_id' => $section_id,
		'props' => $props,
		'categories' => $category_array,
    ], true));

} else if ($action == 'window_product_photos_edit') {

    require(WB_PATH.'/modules/admin.php');

    $product_id = $clsFilter->f('prod_id', [['integer']], 'fatal');

    $r = select_row(
        [$clsMinishop->tbl_photos, $clsStorageImg->tbl_img],
        "*",
        "{$clsMinishop->tbl_photos}.`storage_image_id` = {$clsStorageImg->tbl_img}.`img_id` AND `prod_id`='{$product_id}' ORDER BY {$clsMinishop->tbl_photos}.`photo_is_main` DESC"
        );
    if (gettype($r) === 'string') print_error($r);

    $photos = [];    
    while ($r !== null && $row = $r->fetchRow()) {
        $row['photo_url'] = $clsStorageImg->get_without_db($row['md5'], $row['ext'], 'origin');
        $photos[] = $row;
    }

        print_error($clsMinishop->render('product_photos_edit.twig', [
                'FTAN'=>$admin->getFTAN(),
                'PAGE_SECTION_FIELDS' => $PAGE_SECTION_FIELDS,
                'section_id' => $section_id,
                'page_id' => $page_id,
                'prod_id' => $product_id,
                'photos'=>$photos
    ], true),
    ['title'=>'Редактирование фотографий']
    );

} else if ($action == 'photo_load') {

    require(WB_PATH.'/modules/admin.php');

    $product_id = $clsFilter->f('prod_id', [['integer']], 'fatal');

    if (!isset($_FILES['photos']['tmp_name'])) print_error('Не выбраны фотографии');
    if (!$_FILES['photos']['tmp_name']) print_error('Не выбраны фотографии');

    list($ids, $errors) = $clsStorageImg->save_many($_FILES['photos']['tmp_name'], ['maxsize'=>4*1024]);

    $img_errs = '<br>';
    if ($errors) {
        $img_errs .= count($errors)." из ".count($_FILES['photos']['tmp_name'])." изображений не загружены! <br>";
        foreach ($errors as $i => $error) $img_errs .= $error."<br>";
    }

    $loaded_photos = [];
    if ($ids) {
        foreach($ids as $i => $image_id) {
                $fields = ['`prod_id`'=>$product_id, '`storage_image_id`'=>$image_id, '`photo_position`'=>0, '`photo_is_main`'=>0];
            $r = insert_row($clsMinishop->tbl_photos, $fields);
                $loaded_photos[] = [
                'photo_id'=>$database->getLastInsertId(),
                'phot_url'=>$clsStorageImg->get($image_id),
            ];
        }
        
    }

    print_success($img_errs, ['timeout'=>10000, 'data'=>['loaded_photos'=>$loaded_photos]]);

} else if ($action == 'photo_delete') {
    
    require(WB_PATH.'/modules/admin.php');

	$photo_id = $clsFilter->f('photo_id', [['integer']], 'fatal');

    $r = $database->query("SELECT * FROM {$clsMinishop->tbl_photos} WHERE `photo_id`='{$photo_id}'");
    if ($database->is_error()) print_error($database->get_error());

    // удаляем файл    
    $row = $r->fetchRow(MYSQLI_ASSOC);
    unlink($clsMinishop->pathMedia.'/'.$row['prod_id'].'/'.$row['photo_name']);

    // удаляем из базы
    $r = $database->query("DELETE FROM {$clsMinishop->tbl_photos} WHERE `photo_id`='{$photo_id}'");
    if ($database->is_error()) print_error($database->get_error());

    print_success("Удалено успешно!");

} else if ($action == 'photo_change_main') {

	$photo_id = $clsFilter->f('photo_id', [['integer']], 'fatal');
	$prod_id = $clsFilter->f('prod_id', [['integer']], 'fatal');

    $r = $database->query("UPDATE {$clsMinishop->tbl_photos} SET `photo_is_main`=0 WHERE `photo_is_main`=1 AND `prod_id`='{$prod_id}'");
    if ($database->is_error()) print_error($database->get_error());

    $r = $database->query("UPDATE {$clsMinishop->tbl_photos} SET `photo_is_main`=1 WHERE `photo_id`='{$photo_id}' AND `prod_id`='{$prod_id}'");
    if ($database->is_error()) print_error($database->get_error());

    print_success("Успешно!");

} else if ($action == 'export_yml') {
    
    /* ALTER TABLE `vs_mod_minishop_settings` ADD `shop_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `admin_login`, ADD `shop_org_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `shop_name`, ADD `shop_name_use_website_title` INT(11) NOT NULL DEFAULT '0' AFTER `shop_org_name`;
       ALTER TABLE `vs_mod_minishop_products` ADD `prod_link` VARCHAR(255) NULL DEFAULT NULL AFTER `prod_is_hit`;
       ALTER TABLE `vs_mod_minishop_products` ADD `is_copy_for` INT(11) NOT NULL DEFAULT '0' AFTER `prod_link`;
    */

    // modules/wbs_minishop/api.php?action=export_yml&section_id=0&page_id=0

    header('Content-type: text/xml');
    header('Content-Disposition: attachment; filename="yml.xml"'); // https://developer.mozilla.org/ru/docs/Web/HTTP/%D0%97%D0%B0%D0%B3%D0%BE%D0%BB%D0%BE%D0%B2%D0%BA%D0%B8/Content-Disposition

    $minishop_settings = $clsMinishop->get_settings();
    
    $cats = [];
    $r = $database->query("SELECT * FROM ".$clsMinishop->tbl_categories." WHERE 1=1");//section_id` = '$section_id'");
    if ($database->is_error()) print_error($database->get_error());
    while ($r !== null && $category = $r->fetchRow()) {
        $cat = [];
        $cat['id'] = $category['category_id'];
        $cat['name'] = $category['category_name'];
        $cats[] = $cat;
    }
        
    $clsYml = new WbsYML('test.xml');
    $clsYml->startShop(
        $minishop_settings['shop_name_use_website_title'] === '1' ? WEBSITE_TITLE : $minishop_settings['shop_name'],
        $minishop_settings['shop_org_name'],
        idn_decode(WB_URL)[0],
        [['id'=>'RUB', 'rate'=>'CB']],
        $cats
    );

    $r = $clsMinishop->get_product();
    if (gettype($r) === 'string') print_error($r);
    while($r !== null && $row = $r->fetchRow(MYSQLI_ASSOC)) {
        $row['prod_price'] /= 100;
        
        $photos = $clsMinishop->get_product_photos($row['prod_id'], '350x250');
        $photo_main = count($photos) == 0 ? $clsMinishop->urlMedia.'product_default_image.jpg' : $photos[0]['preview_image'];
        $prod_url = WB_URL.PAGES_DIRECTORY.$row['prod_link'].PAGE_EXTENSION;

        $clsYml->startOfferMarket(
            $row['prod_id'],
            $row['prod_is_active']==='1' ? 'true' : 'false',
            $row['prod_title'],
            $prod_url,
            $photo_main,
            $row['prod_price'],
            'RUB',
            $row['prod_category_id'], 
            ['description'=>$row['prod_shortdesc']]
        );
        $clsYml->endOffer();
    }

    $clsYml->endShop();
    $clsYml->write();

    
    exit();

} else if ($action == 'window_export') {

    require(WB_PATH.'/modules/admin.php');

    print_success(
        $clsMinishop->render('window_export.twig', [
        'FTAN'=>$admin->getFTAN(),
         'section_id'=>$section_id,
         'page_id'=>$page_id,
         'PAGE_SECTION_FIELDS' => $PAGE_SECTION_FIELDS,
         'wb_url'=>WB_URL,
      ], true),
      ['title'=>'Экспорт']
   );
    
} else {
	print_error('Невверный action!');
}

echo json_encode($res);

?>