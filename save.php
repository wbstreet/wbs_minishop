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

$action = $_GET['action'];
$section_id = $_GET['section_id'];
$page_id = $_GET['page_id'];

require('../../config.php');
$admin_header = false;
$update_when_modified = true;
require(WB_PATH.'/modules/admin.php');

if (!$admin->checkFTAN()) {
	$admin->print_header();
	$admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS'], $clsMinishop->urlRet);
}
$admin->print_header();

include(WB_PATH.'/modules/wbs_minishop/lib.class.minishop.php');
$clsMinishop = new ModMinishop($page_id, $section_id);
//$admin->print_error(json_encode($_POST['prop_value']), $clsMinishop->urlRet);

if ($action=='create_product') {

    $fields = [
        'section_id' => $section_id,
        'page_id' => $page_id,
        'prod_category_id' => $admin->get_post('prod_category_id'),
        'prod_title' => $admin->get_post('prod_title'),
        'prod_shortdesc' => $admin->get_post('prod_shortdesc'),
        'prod_desc' => $admin->get_post('prod_desc'),
        'prod_price' => $admin->get_post('prod_price') * 100,
        'prod_is_active' => 1,
        'prod_count' => 10,
        'prod_image_name' => 'default'
    ];

    $sql = 'INSERT INTO `'.TABLE_PREFIX.'mod_minishop_products` ('.glue_keys(array_keys($fields)).') VALUES ('.glue_values(array_values($fields)).') ';
    //echo $sql;
    if ($database->query($sql)) $admin->print_success("Товар создан!", $clsMinishop->urlRet);
    else $admin->print_error($database->get_error(), $clsMinishop->urlRet);

} else if ($action=='upload_product_default_image') {
    $image = $_FILES['product_default_image'];
    if ($image['size'] != 0 and $image['tmp_name'] != '') {
        $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
        if ($ext != 'jpg') $admin->print_error("Неразрешённое расширение файла! Только .jpg !", $clsMinishop->urlRet);
        $new_name = WB_PATH.'/media/mod_minishop/product_default_image.jpg';
        move_uploaded_file($image['tmp_name'], $new_name);
        if ($is_use_product_image_height) convertImage($new_name, $product_image_width, $product_image_height);
    } else {$admin->print_error("Пустой файл или имя!", $clsMinishop->urlRet);}
    $admin->print_success("Картинка загружена!", $clsMinishop->urlRet);
}
?>