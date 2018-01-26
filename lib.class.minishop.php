<?php

$path_core = WB_PATH.'/modules/wbs_core/include_all.php';
if (file_exists($path_core )) include($path_core );
else echo "<script>console.log('Модуль минимаркета требует модуль wbs_core')</script>";
//if (!defined('FUNCTIONS_FILE_LOADED')) require_once(WB_PATH.'/framework/functions.php');

class Minishop extends Addon {
    public $is_common_cart = true;
    public $is_common_props = true;

    function __construct($page_id, $section_id) {
        parent::__construct('wbs_minishop', $page_id, $section_id);
        $this->tbl_settings = "`".TABLE_PREFIX."mod_wbs_minishop_settings`";
        $this->tbl_products = "`".TABLE_PREFIX."mod_wbs_minishop_products`";
        $this->tbl_categories = "`".TABLE_PREFIX."mod_wbs_minishop_categories`";
        $this->tbl_users = "`".TABLE_PREFIX."mod_wbs_minishop_users`";
        $this->tbl_photos = "`".TABLE_PREFIX."mod_wbs_minishop_photos`";
        $this->tbl_prop = "`".TABLE_PREFIX."mod_wbs_minishop_prop`";
        $this->tbl_prop_values = "`".TABLE_PREFIX."mod_wbs_minishop_prop_values`";
        
        $this->process_error = 'echo';
    }
    
    function install() {
        global $database;
        $database->query("DROP TABLE IF EXISTS {$this->tbl_settings}");
        $mod_create_table = 'CREATE TABLE '.$this->tbl_settings.' ( '
        	. '`section_id` INT NOT NULL,'
        	. '`page_id` INT NOT NULL,'
        	. '`admin_email` VARCHAR(255) NOT NULL,'
        	. '`admin_login` VARCHAR(255) NOT NULL,'
        	. '`block_html` TEXT,'
        	. '`block_css` TEXT,'
        	. '`address_self_delivery` TEXT,'
        	. '`has_self_delivery` INT,'
        	. '`has_delivery` INT,'
        	. '`window_html` TEXT,'
        	. '`window_css` TEXT,'
        	. '`is_general_settings` INT NOT NULL DEFAULT \'0\''
        	. ' )';
        $database->query($mod_create_table);
        
        $database->query("DROP TABLE IF EXISTS {$this->tbl_products}");
        $mod_create_table = 'CREATE TABLE '.$this->tbl_products.' ( '
        	. '`section_id` INT NOT NULL DEFAULT \'0\','
        	. '`prod_id` INT NOT NULL AUTO_INCREMENT,'
        	. '`prod_category_id` INT,'
        	. '`page_id` INT NOT NULL DEFAULT \'0\','
        	. '`prod_title` VARCHAR(255) NOT NULL,'
        	. '`prod_shortdesc` VARCHAR(255),'
        	. '`prod_desc` TEXT(500),'
        	. '`prod_price` INT NOT NULL,'
        	. '`prod_is_active` INT NOT NULL DEFAULT \'0\','
        	. '`prod_count` INT NOT NULL DEFAULT \'0\','
        	. '`prod_image_name` VARCHAR(255) NOT NULL,'
        	. '`prop_value_ids` JSON,'
                . '`prod_is_hit` INT,'
        	. 'PRIMARY KEY (prod_id)'
        	. ' )';
        $database->query($mod_create_table);
        
        $database->query("DROP TABLE IF EXISTS {$this->tbl_photos}");
        $mod_create_table = "CREATE TABLE {$this->tbl_photos} (
          `photo_id` int(11) NOT NULL AUTO_INCREMENT,
          `prod_id` int(11) NOT NULL,
          `photo_name` varchar(255) NOT NULL,
          `photo_position` int(11) NOT NULL DEFAULT '0',
          `photo_is_main` int(11) NOT NULL,
           PRIMARY KEY (`photo_id`)
        )";
        $database->query($mod_create_table);
        
        $database->query("DROP TABLE IF EXISTS {$this->tbl_categories}");
        $mod_create_table = 'CREATE TABLE '.$this->tbl_categories.' ( '
        	. '`section_id` INT NOT NULL DEFAULT \'0\','
        	. '`category_id` INT NOT NULL AUTO_INCREMENT,'
        	. '`page_id` INT NOT NULL DEFAULT \'0\','
        	. '`category_name` VARCHAR(255) NOT NULL,'
        	. 'PRIMARY KEY (category_id)'
        	. ' )';
        $database->query($mod_create_table);
        
        $database->query("DROP TABLE IF EXISTS {$this->tbl_users}");
        $mod_create_table = 'CREATE TABLE '.$this->tbl_users.' ( '
        	. '`section_id` INT NOT NULL DEFAULT \'0\','
        	. '`user_id` INT NOT NULL AUTO_INCREMENT,'
        	. '`user_id_in_engine` INT NOT NULL DEFAULT \'0\' ,'
        	. '`page_id` INT NOT NULL DEFAULT \'0\','
        	. '`user_name` VARCHAR(255) NOT NULL,'
        	. '`user_address` VARCHAR(255) NOT NULL,'
        	. '`user_phone` VARCHAR(255) NOT NULL,'
        	. 'PRIMARY KEY (user_id)'
        	. ' )';
        $database->query($mod_create_table);


        $database->query("DROP TABLE IF EXISTS {$this->tbl_prop};");
        $mod_create_table = "CREATE TABLE {$this->tbl_prop} (
		  `prop_id` int(11) NOT NULL AUTO_INCREMENT,
		  `page_id` int(11) NOT NULL,
		  `section_id` int(11) NOT NULL,
		  `prop_name` varchar(255) NOT NULL,
		  `prop_type` varchar(10) NOT NULL,
		  PRIMARY KEY (prop_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
        $database->query($mod_create_table);

        $database->query("DROP TABLE IF EXISTS {$this->tbl_prop_values};");
        $mod_create_table = "CREATE TABLE {$this->tbl_prop_values} (
		  `prop_value_id` int(11) NOT NULL AUTO_INCREMENT,
		  `page_id` int(11) NOT NULL,
		  `section_id` int(11) NOT NULL,
		  `prop_id` int(11) NOT NULL,
		  `value` varchar(255) NOT NULL,
		  PRIMARY KEY (prop_value_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
        $database->query($mod_create_table);

    }
    
    function add() {
        global $admin, $database;
        
        $block_html = "
        <span class='product_title'>{{PROD_TITLE}}</span><br>
        <span class='product_full_price'><span class='product_price'>{{PROD_PRICE}}</span> <span class='currency'>{{CURRENCY}}</span></span><br>
        <img class='product_main_pic' src='{{PROD_MAIN_IMG_SRC}}' {{ONCLICK_OPEN_WINDOW|raw}}><br>
        <span class='product_short_description'>{{PROD_SHORT_DESCR}}</span>
        <span class='button_add2cart' onclick='mod_minishop.cart_show_form(this)'>В корзину</span>
        <span class='count2cart_form'>
            <input class='product_count2cart' type='number' value=''> <br>
            <input class='button_add2cart2' type='button' value='Добавить' onclick='mod_minishop.cart_add(this)'>
        </span>

        {% if  PROD_IS_HIT == 1 %}
            <span class='product_hit'>Хит<br>продаж</span>
        {% endif %}
        ";
        $block_css = "
        .view_products {text-align: center;}
        .view_products .product {position:relative;display: inline-block; width:150px; vertical-align: top; border: 1px solid #B0B0B0; border-radius:10px; background:#ECECEC; text-align:center; padding: 5px 0 10px 0; margin: 15px 10px 15px 10px;}
        .view_products .product .product_title {color: red;  font-weight: bold;}
        .view_products .product .product_price {color: #000;  font-weight: bold;}
        .view_products .product .currency {color: #000;  font-weight: bold;}
        .view_products .product .product_main_pic {width: 90%; height: auto; cursor:pointer;}
        .view_products .product .product_full_price {font-size: 120%;}
        .view_products .product .product_short_description {color: #000; display:block;}
        .view_products .product .count2cart_form {display: none;}
        .view_products .product .product_hit {position:absolute;top:-10px;right:-10px;transform: rotate(-30deg);text-align:center; color:#dd2222;}
        
        .windowBody {font-size:10pt;}
        .windowBody .product_main_pic {margin:0 15px 15px 0 ;  width:50%}
        .windowBody {background: #dfe8ff;}
        .windowWindow {background: #dfe8ff;}
        .windowTitle {background: #6073a9;}
        ";
        $window_html = "
        <div class='block_photos' style='float:left;text-align:center;width:50%;'>
            <img class='product_main_pic fm' src='{{PROD_MAIN_IMG_SRC}}'>
            <br>
            <div class='photos' style='display:inline-block;width: 100%;overflow-x: auto;'>
                <nobr>
                {% for photo in PROD_PHOTOS %}
                      <img src='{{ photo.photo_url }}' style='height:40px;width:auto;cursor:pointer;'>
                {% endfor %}
                </nobr>
            </div>
        </div>

        <span class='product_description'>{{PROD_DESCR|raw }}</span>
        <br><br>Цена: <span class='product_full_price'><span class='product_price'>{{PROD_PRICE}}</span> s<span class='currency'>{{CURRENCY}}</span></span><br>
        <span class='count2cart_form'>

            {% if PROD_PROPS|length > 0 %} <br> {% endif %}

            {% for prod_prop in PROD_PROPS %}
                {{ prod_prop|raw }}
            {% endfor %}

            {% if PROD_PROPS|length > 0 %} <br><br> {% endif %}

            В корзину: <input class='product_count2cart' type='number' value='1'>
            <input class='button_add2cart2' type='button' value='Добавить' onclick='mod_minishop.cart_add(this)'>
        </span>

       <script>
function show_photo(e) {
        e.target.closest('.block_photos').querySelector('.product_main_pic').src=e.target.src
}

var photos = Array.from(document.querySelector('.block_photos>.photos').children);
for (photo of photos) {
    photo.addEventListener('click',show_photo);
    photo.addEventListener('mouseover',show_photo);
}
       </script>
        
        ";
        $window_css = "";
        $admin_login = "admin";
        $address_self_delivery = "адрес самовывоза";
        $has_self_delivery = "0";
        $has_delivery = "0";
        //	add a new row to the module table which contains the actual page_id and section_id 
        $r = $database->query("INSERT INTO {$this->tbl_settings} (`page_id`, `section_id`, `admin_email`, `admin_login`, `block_html`, `block_css`, `address_self_delivery`, `has_self_delivery`, `has_delivery`, `window_html`, `window_css`) VALUES ('{$this->page_id}', '{$this->section_id}', \"mail@example.com\", \"".$admin_login."\", \"".mysql_escape_string($block_html)."\", \"".mysql_escape_string($block_css)."\", \"".$address_self_delivery."\", \"".$has_self_delivery."\", \"".$has_delivery."\", \"".mysql_escape_string($window_html)."\", \"".mysql_escape_string($window_css)."\")");
        if (!$r) $admin->print_error($database->get_error());
    }
    
    function get_settings() {
        global $database;

        $sql_result = $database->query("SELECT * FROM {$this->tbl_settings} WHERE `section_id` = '{$this->section_id}'");
        if ($database->is_error()) {echo $database->get_error(); die();}
        if($sql_result->numRows() == 0) {
            echo "<script>console.log('Настройки секции {$this->section_id} магазина не найдены');</script>";
            return null;
        }
        $minishop_settings = $sql_result->fetchRow();
        //echo "<script>console.log('".json_encode(((int)$minishop_settings['is_general_settings'] === 2))."');</script>";
        if ((int)$minishop_settings['is_general_settings'] === 2) {
            $_sql_result = $database->query("SELECT * FROM {$this->tbl_settings} WHERE `section_id`=0 AND `page_id`=0");
            if ($database->is_error()) {echo $database->get_error(); die();}
            if($sql_result->numRows() == 0) {
                echo "<script>console.log('Настройки секции магазина для всего сайта не найдены');</script>";
                return null;
            }
            $_minishop_settings = $_sql_result->fetchRow();
            $_minishop_settings['is_general_settings'] = $minishop_settings['is_general_settings'];
            $_minishop_settings['page_id'] = 0;
            $_minishop_settings['section_id'] = 0;
            $minishop_settings = $_minishop_settings;
        }
        return $minishop_settings;
    }
    
    function get_prop_values($prop_id) {
        global $database;
        
        $where = ["1=1"];

        $where[] = glue_fields(["prop_id" => $prop_id], "=");

        if ($this->is_common_props === false) {
            $where[] = "`page_id`={$this->page_id}";
            $where[] = "`section_id`={$this->section_id}";
        }
        $sql = build_select($this->tbl_prop_values, "*", implode(' AND ', $where));
        
        //$sql = "SELECT * FROM {$this->tbl_prop_values} WHERE `page_id`='{$this->page_id}' AND `section_id`='{$this->section_id}' AND `prop_id`='{$prop_id}'";
        $r = $database->query($sql);
        if ($this->process_error == 'return') {
            if ($database->is_error()) return $database->get_error();
            if ($r->numRows() == 0) return null;
            return $r;
        } else if ($this->process_error == 'echo') {
            if ($database->is_error()) {echo $database->get_error(); return null;}
            else if ($r->numRows() == 0) {/*echo "Вариантов значений нет";*/ return null;}
            return $r;
        } else if ($this->process_error == 'fatal') {
        }
    }
    
    function get_prop() {
        global $database;
        
        $where = ["1=1"];
        
        if ($this->is_common_props === false) {
            $where[] = "`page_id`={$this->page_id}";
            $where[] = "`section_id`={$this->section_id}";
        }
        $sql = build_select($this->tbl_prop, "*", implode(' AND ', $where));

        $r = $database->query($sql);
        if ($this->process_error == 'return') {
            if ($database->is_error()) return $database->get_error();
            if ($r->numRows() == 0) return null;
            return $r;
        } else if ($this->process_error == 'echo') {
            if ($database->is_error()) {echo $database->get_error(); return null;}
            else if ($r->numRows() == 0) {/*echo "Нет ни одной характеристики.";*/ return null;}
            return $r;
        } else if ($this->process_error == 'fatal') {
        }
    }
    
    function get_product($sets) {
        global $database;
        
        $where = ["1=1"];
        
        if ($this->is_common_props === false) {
            $where[] = "`page_id`={$this->page_id}";
            $where[] = "`section_id`={$this->section_id}";
        }
        
        if (isset($sets['product_id'])) $where[] = "`prod_id`='".$database->escapeString($sets['product_id'])."'";
        
        $sql = build_select($this->tbl_products, "*", implode(' AND ', $where));

        $r = $database->query($sql);
        if ($this->process_error == 'return') {
            if ($database->is_error()) return $database->get_error();
            if ($r->numRows() == 0) return null;
            return $r;
        } else if ($this->process_error == 'echo') {
            if ($database->is_error()) {echo $database->get_error(); return null;}
            else if ($r->numRows() == 0) {echo "Нет Товара."; return null;}
            return $r;
        } else if ($this->process_error == 'fatal') {
        }
    }
    
    function get_product_vars($arrProduct) {
    	global $database;

        // характеристики

   	    $prop_value_ids = $arrProduct['prop_value_ids'] !== null ? json_decode($arrProduct['prop_value_ids']) : [];

    	$props = [];
	    if (count($prop_value_ids) > 0) {
	        $sql = "SELECT * FROM `".TABLE_PREFIX."mod_wbs_minishop_prop_values`, `".TABLE_PREFIX."mod_wbs_minishop_prop` WHERE `".TABLE_PREFIX."mod_minishop_prop_values`.`prop_id` = `".TABLE_PREFIX."mod_minishop_prop`.`prop_id` AND `prop_value_id` IN (".implode(',', $prop_value_ids).") ORDER BY `".TABLE_PREFIX."mod_minishop_prop_values`.`prop_id`";
	        $r = $database->query($sql);
	        if ($database->is_error()) {
	            echo $database->get_error();
	        } else {
	            $prev = null;
	            $sProp = '';
	            while($prop = $r->fetchRow(MYSQLI_ASSOC)) {
	                if ($prev != $prop['prop_id']) {
	                	if ($prev !== null) { $props[] = $sProp.'</select>'; $sProp = '';}
	                    $sProp .= "<select name='props[]'><option selected disabled>{$prop['prop_name']}</option>";
	                }
	                $sProp .= "<option value='{$prop['prop_value_id']}'>{$prop['value']}</option>";
	    
	                $prev = $prop['prop_id'];
	            }
	           if ($sProp !== null) $props[] = $sProp.'</select>';
	        }
	    }

        // фотографии
	    
        $r = $database->query("SELECT * FROM {$this->tbl_photos} WHERE `prod_id`='{$arrProduct['prod_id']}' ORDER BY photo_is_main DESC");
        if ($database->is_error()) print_error($database->get_error());
    
        $photos;
        while ($row = $r->fetchRow(MYSQLI_ASSOC)) {
            $row['photo_url'] = $this->urlMedia.$arrProduct['prod_id'].'/'.$row['photo_name'];
            $photos[] = $row;
        }
    	
    	$photo_main = count($photos) == 0 ? $this->urlMedia.'product_default_image.jpg' : $photos[0]['photo_url'];
    	
    	return [
            "PROD_ID"             =>  $arrProduct['prod_id'],
            "PROD_COUNT2CART"     =>  0,
            "PROD_TITLE"          =>  $arrProduct['prod_title'],
            "PROD_PRICE"          =>  $arrProduct['prod_price'] / 100,
            "CURRENCY"            =>  "руб",
            "PROD_SHORT_DESCR"    =>  $arrProduct['prod_shortdesc'],
            "PROD_DESCR"          =>  preg_replace("/\n/", "<br>", $arrProduct['prod_desc']),
            "PROD_MAIN_IMG_SRC"   =>  $photo_main,//$arrProduct['prod_image_name'] == 'default' ? $this->urlMedia.'product_default_image.jpg' : $this->urlMedia.$arrProduct['prod_id']."/".$arrProduct['prod_image_name'],
            "ONCLICK_OPEN_WINDOW" =>  " onclick=\"W.open_by_api('window_product_info', {data: {product_id:'{$arrProduct['prod_id']}', page_id:'{$this->page_id}', section_id:'{$this->section_id}'}, url:mod_minishop.url_api, add_sheet:true})\"",//" onclick=\"minishop_show_window(this)\"",
            "PROD_PROPS"          =>  $props,
            'PROD_PHOTOS'         =>  $photos,
            'PROD_IS_HIT'         =>  $arrProduct['prod_is_hit'],
            ];
    }
    
    function photo_generate_name($original_image_name) {
        $aName = pathinfo($original_image_name);
        return generate_image_name($len=15, $registr='both').".".$aName['extension'];
    }

    /*
     * Данная функция должна работать без переменных section_id и page_id
    */
    function photo_old2new() {
        global $database;
        
        $count = 0;

        $_r = $database->query("SELECT * FROM {$this->tbl_products}");
        if ($database->is_error()) {
        } else {

            while ($arrProduct = $_r->fetchRow(MYSQLI_ASSOC)) {

                $image_old = $this->pathMedia.$arrProduct['prod_id']."/".$arrProduct['prod_image_name'];
        
                $pathProduct = $this->pathMedia.'/'.$arrProduct['prod_id'].'/';
                if (!file_exists($pathProduct)) mkdir($pathProduct);
        
                if (file_exists($image_old)) {
                    $photo_name = $this->photo_generate_name($image_old);

                    $r = $database->query("INSERT INTO {$this->tbl_photos} (`prod_id`, `photo_name`, `photo_position`, `photo_is_main`) VALUES ('{$arrProduct['prod_id']}', '{$photo_name}', '0', '0')");
                    if (!$database->is_error()) rename($image_old, $pathProduct.$photo_name);

                    $count += 1;
                }
            }
        }
        
        return $count;
    }
    
    function wrap_product_tile($html, $arrProduct) {
        return "<div class='product' id='product{$arrProduct['prod_id']}' data-prod_id='{$arrProduct['prod_id']}' data-section_id='{$this->section_id}' data-page_id='{$this->page_id}'>".$html."</div>";
    }
    
    function print_template($name, $vars) {
	    $loader = new Twig_Loader_Filesystem($this->pathTemplates);
	    $twig = new Twig_Environment($loader);    	
	    
	    $vars["WB_URL"] = WB_URL;

   	    echo $twig->render($name, $vars);
    }

    function print_cart_btn() {
        $this->print_template("frontend_cart_btn.twig", []);
    }
    
    function print_cart() {
        $this->print_template("frontend_cart.twig", []);
    }
    
    function is_hit($prod_id) {
        $r = $database->query("SELECT `prod_is_hit` FROM {$this->tbl_products} WHERE `prod_id`='".process_value($prod_id)."'");
        if ($database->is_error()) return '0';
        if ($r->numRows() === 0) return '0';
        $row = $r->fettchRow();
        return $row['prod_is_hit'] === '1' ? '1' : '0';
    }
}

?>