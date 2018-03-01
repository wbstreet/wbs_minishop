<?php
/**
 *
 * @category        module
 * @package         wbs_minishop
 * @author          Konstantin Polyakov
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WebsiteBaker 2.10.0
 * @requirements    PHP 5.2.2 and higher
 *
 */


// prevent this file from being accessed directly
if(!defined('WB_PATH')) die(header('Location: index.php'));

// include core functions of WB 2.7 to edit the optional module CSS files (frontend.css, backend.css)
@include_once(WB_PATH .'/framework/module.functions.php');

// check if module language file exists for the language set by the user (e.g. DE, EN)
if(!file_exists(WB_PATH .'/modules/wbs_minishop/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/wbs_minishop/languages/EN.php');
} else {
		require_once(WB_PATH .'/modules/wbs_minishop/languages/' .LANGUAGE .'.php');
}

include(WB_PATH.'/modules/wbs_minishop/lib.class.minishop.php');
$clsMinishop = new ModMinishop($page_id, $section_id);

$mod_path = MEDIA_DIRECTORY."/mod_minishop";
$section_path = $clsMinishop->pathMedia;
$section_url = $clsMinishop->urlMedia;

$PAGE_SECTION_FIELDS = "<input type='hidden' name='page_id' value='{$page_id}'>
<input type='hidden' name='section_id' value='{$section_id}'>
";

include('common.php');

if(function_exists('wbs_core_include')) wbs_core_include(['functions.js', 'windows.js', 'windows.css']);
?>

<style>
    .prop_name {
        background: #ddd;
        padding: 5px 3px 5px 3px;
        margin-top: 10px;
    }
    .prop_values {
        display: none;
    }
    
    .windowTitle {
        display: initial;
    }
</style>

<script src='<?=$clsMinishop->urlMod ?>frontend.js'></script>

<script>
    "use strict"
    
    let section_id = <?=$section_id?>;
    let page_id = <?=$page_id?>;

    let mod_minishop = new mod_minishop_Main(section_id, page_id);

    function toggle_edit(el) {
        if (!el.dataset.status) el.dataset.status = 'view';
        //if (el.dataset.status == 'view') {
            el.dataset.prev_value = el.textContent;
            el.innerHTML = '<input type="text" value="'+el.textContent+'">';
            el.dataset.status = 'edit';
            el.children[0].focus();
            el.children[0].addEventListener('blur', function(e) {
                var prev_value = e.target.parentElement.dataset.prev_value;
                var new_value = e.target.value;
                if (new_value == '') {e.target.parentElement.innerHTML = prev_value; return;}
                e.target.parentElement.innerHTML = new_value;
                if (new_value != prev_value) {
                    mod_minishop_Request2('edit_prop', 'prop_id='+el.parentElement.dataset.prop_id+'&value='+new_value);
                }
            });
        //} else if ()
    }
    
    function show_pic(file, func, ...args) {
    	var reader = new FileReader();
   		reader.onload = function(event) {
            func(event.target.result, ...args);
        };
        reader.readAsDataURL(file);
    }
    
    function show_product(prod_id) {
        content_by_api('content_form_product', $('#product'+prod_id)[0], {
            data:{section_id:<?=$section_id?>, page_id:<?=$page_id?>, prod_id:prod_id},
            func_after_insert: function() {
                if (window.product_current == '#product'+prod_id) {
                    $(window.product_current).toggle(500);
                    $(window.product_current)[0].innerHTML = "";
                    window.product_current = undefined;
                    return;
                }
                
                if (window.product_current) {$(window.product_current).toggle(500); $(window.product_current)[0].innerHTML="";}
                $('#product'+prod_id).toggle(500);
                window.product_current = '#product'+prod_id;
            },
            url:mod_minishop.url_api
        })
    }
</script>

<script>
    function open_window_photos(prod_id) {
        W.open_by_api('window_product_photos_edit', {
            data: {
                prod_id: prod_id,
                page_id: '<?=$page_id ?>',
                section_id: '<?=$section_id ?>'
            },
            url:mod_minishop.url_api,
            add_sheet:true
        });
    }
</script>

<!-- ---------------
----- Тела окон ----
---------------- -->

<form id="<?php echo "{$page_id}_{$section_id}_default_pic"; ?>" class='windowBody' enctype='multipart/form-data' method="post" action="<? echo $clsMinishop->getUrlAction('upload_product_default_image'); ?>">
    <?php echo $admin->getFTAN();?>
    Картинка по умолчанию для товаров:
    <?php echoImageLoader("product_default_image", $section_url."product_default_image.jpg", "100px", "100px"); ?>
    <input type="submit" value="Загрузить">
</form>

<form id="<?php echo "{$page_id}_{$section_id}_create_product"; ?>"  method="post" class="minishop_create_product_form windowBody" action="<? echo $clsMinishop->getUrlAction('create_product'); ?>">
    <?php echo $admin->getFTAN();?>
       <div>* Краткое описание: <br><textarea name="prod_shortdesc" required></textarea></div>
       * Название: <input name="prod_title" type="text" required><br>
       * Стоимость: <input name="prod_price" type="text" required><br>
       <?php echoCategorySelect(
            $name="prod_category_id", $firstValue="0", $firstTextContent="Выберите категорию"); ?>
    <input type="submit" value="Добавить новый товар"><br>
</form>

<!-- Кнопки -->
<input type="button" value="Добавить товар" onclick="W.open('<?php echo "{$page_id}_{$section_id}_create_product"; ?>', {text_title:'Добавление товара', add_sheet:true})">
<input type="button" value="Настройки"      onclick="W.open_by_api('window_settings_edit', {data:{section_id:<?=$section_id?>, page_id:<?=$page_id?>}, url:mod_minishop.url_api, add_sheet:true})">
<input type="button" value="Категории"      onclick="W.open_by_api('window_category_edit', {data:{section_id:<?=$section_id?>, page_id:<?=$page_id?>}, url:mod_minishop.url_api, add_sheet:true})">
<input type="button" value="Характеристики" onclick="W.open_by_api('window_property_edit', {data:{section_id:<?=$section_id?>, page_id:<?=$page_id?>}, url:mod_minishop.url_api, add_sheet:true})">
<input type="button" value="Картинка по умолчанию" onclick="W.open('<?php echo "{$page_id}_{$section_id}_default_pic"; ?>', {text_title:'Загрузить картинку по умолчанию', add_sheet:true})">

<!-- Основное -->

<br><span class="minishop_title">Список товаров. Нажмите на имя для редактирования</span>

<div class="modify_products">
<?php
$products = $database->query('SELECT * FROM `'.TABLE_PREFIX.'mod_minishop_products` WHERE `section_id`='.$section_id.' ORDER BY `prod_category_id`');
$current_category_id = '';
while($product = $products->fetchRow()) {
    if ($product['prod_category_id'] != $current_category_id) {
        echo "<h2>".$category_array[$product['prod_category_id']]."</h2>";
        $current_category_id = $product['prod_category_id'];
    }
    ?>
    <!--<input type='checkbox' class='mass_select' value='<?=$product['prod_id']?>'>-->
    <span class="minishop_product_title" onclick="show_product(<?=$product['prod_id']?>)">Товар:  <?=$product['prod_title']?></span><br>
    <form class="minishop_update_product_form block" id="product<?=$product['prod_id']?>" class="product">
    </form>
    <?php
} ?>
</div>

<br>

<!--<select>
	<option selected disabled>Что сделать с выбранными товарами?</option>
	<option value="">Удаление</option>
	<option value="">Активность</option>
	<option value="">Перенести</option>
	<option value="">Экспорт</option>
</select>-->

<script src="/modules/wbs_minishop/backend_after.js" defer></script>