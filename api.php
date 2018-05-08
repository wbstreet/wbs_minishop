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
	    if ($delivery=='deliv') $delivery_address = $clsFilter->f('delivery_address', [['1', "Не указан адрес доставки"]], 'append', '');
    	else $delivery_address = "";

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
	
	    $body = $clsMinishop->render('letter_order.twig', [
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
   	
    $sql = "SELECT * FROM ".$clsMinishop->tbl_order." WHERE `order_id`=".process_value($order_id)." AND `user_id`=".process_value($admin->get_user_id());
	$r = $database->query($sql);
	if ($database->is_error()) print_error($database->get_error());
	if ($r->numRows() === 0) print_error("Заказ не найден!");
	$order = $r->fetchRow(MYSQL_ASSOC);
	
	// Проверяем заказ
	
	if ($order['is_payed'] === '1' || $order['is_shipped'] === '1' || $order['is_sended'] === '1') {
		print_error('Заказ уже нельзя отменить!');
	}
	
	
	if ($order['is_cancelled'] === '1') {
		print_error('Заказ был отменён ранее!');
	}
	
	// Отменяем заказ
	
	$sql = "UPDATE {$clsMinishop->tbl_order} SET `is_cancelled`=1 WHERE `order_id`=".process_value($order_id);
	$r = $database->query($sql);
	if ($database->is_error()) print_error($database->get_error());

    print_success("Заказ успешно отменён");

} else if ($action == 'edit_prop') {

    require(WB_PATH.'/modules/admin.php');

    $prop_id = preg_replace('/[^0-9]/', '', $_POST['prop_id']);
    $value = $database->escapeString($_POST['value']);
    
    $res['e'] = $prop_id.'-'.$value;
    
    $sql = "UPDATE `".TABLE_PREFIX."mod_wbs_minishop_prop` SET `prop_name`='$value' WHERE `prop_id`='$prop_id'";
    if ($database->query($sql)) $res['success'] = true;
    else { $res['success']= false; $res['message'] = $database->get_error();}

} else if ($action == 'window_product_info') {

	$product_id = $clsFilter->f('product_id', [['integer']], 'fatal');
	
	// извлекаем данные о товаре
    
    $products = $clsMinishop->get_product(['product_id'=>$product_id]);
    if ($products === null) print_error('Товар не найден!');
    
    $product = $products->fetchRow(MYSQLI_ASSOC);

    // подключаем шаблон
    
    $minishop_settings = $clsMinishop->get_settings();
    
    $loader = new Twig_Loader_Array(array(
        'window_html' => $clsMinishop->wrap_product_tile($minishop_settings['window_html'], $product),
	));
	$twig = new Twig_Environment($loader);

    $array_vars = $clsMinishop->get_product_vars($product);
    
    print_success($twig->render('window_html', $array_vars), ['title'=>$product['prod_title']]);

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
    $sql = 'UPDATE `'.TABLE_PREFIX.'mod_wbs_minishop_settings` SET ';
    $sql .= '`admin_email`="'          .$database->escapeString($_POST['admin_email'])          .'", ';
    $sql .= '`admin_login`="'          .$database->escapeString($_POST['admin_login'])          .'", ';
    $sql .= '`block_html`="'           .$database->escapeString($_POST['block_html'])           .'", ';
    $sql .= '`block_css`="'            .$database->escapeString($_POST['block_css'])            .'", ';
    $sql .= '`address_self_delivery`="'.$database->escapeString($_POST['address_self_delivery']).'", ';
    $sql .= '`has_self_delivery`="'    .$database->escapeString($has_self_delivery)             .'", ';
    $sql .= '`has_delivery`="'         .$database->escapeString($has_delivery)                  .'", ';
    $sql .= '`window_html`="'          .$database->escapeString($_POST['window_html'])          .'", ';
    $sql .= '`need_registration`="'          .$database->escapeString($_POST['need_registration'])  .'", ';
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
	
    require(WB_PATH.'/modules/admin.php');
	
    $prod_id = $admin->get_post('prod_id');

    if ($admin->get_post('prod_is_active')==='true') {$is_active = '1';} else {$is_active = '0';}
    
    $fields = [
        'prod_category_id' => $admin->get_post('prod_category_id'),
        'prod_title' => $admin->get_post('prod_title'),
        'prod_shortdesc' => $admin->get_post('prod_shortdesc'),
        'prod_desc' => $admin->get_post('prod_desc'),
        'prod_price' => (float)$admin->get_post('prod_price') * 100,
        'prod_is_active' => $is_active,
        'prod_count' => $admin->get_post('prod_count'),
        'prop_value_ids'=>json_encode($_POST['prop_value']),
        'prod_is_hit' => $admin->get_post('prod_is_hit'),
        ];

    $forupdate = glue_fields($fields, ',');

    $sql = 'UPDATE `'.TABLE_PREFIX.'mod_wbs_minishop_products` SET '.$forupdate.'  WHERE `section_id`="'.$database->escapeString($section_id).'" AND `prod_id`="'.$database->escapeString($prod_id).'"';
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
        "{$clsMinishop->tbl_photos}.`storage_image_id` = {$clsStorageImg->tbl_img}.`img_id` AND `prod_id`='{$product_id}' ORDER BY photo_is_main, photo_id DESC"
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

    list($ids, $errors) = $clsStorageImg->save_many($_FILES['photos']['tmp_name']);

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

} else {
	print_error('Невверный action!');
}

echo json_encode($res);

?>