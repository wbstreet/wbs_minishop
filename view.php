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


/**
  This module is free software. You can redistribute it and/or modify it 
  under the terms of the GNU General Public License  - version 2 or later, 
  as published by the Free Software Foundation: http://www.gnu.org/licenses/gpl.html.

  This module is distributed in the hope that it will be useful, 
  but WITHOUT ANY WARRANTY; without even the implied warranty of 
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
  GNU General Public License for more details.
**/
// prevent this file from being accessed directly
if(!defined('WB_PATH')) die(header('Location: index.php'));  

if(!file_exists(WB_PATH .'/modules/wbs_minishop/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/wbs_minishop/languages/EN.php');
} else {
		require_once(WB_PATH .'/modules/wbs_minishop/languages/' .LANGUAGE .'.php');
}

if (!class_exists("ModMinishop")) include(WB_PATH.'/modules/wbs_minishop/lib.class.minishop.php');
include('common.php');

$clsMinishop = new ModMinishop($page_id, $section_id);

$minishop_settings = $clsMinishop->get_settings();

$loader = new Twig_Loader_Array(array(
    'block_html' => $minishop_settings['block_html'],
));
$twig = new Twig_Environment($loader);

$order_by = isset($_GET['sorted_by']) ? $_GET['sorted_by'] : 'prod_title';
if ($order_by == 'name') $order_by = 'prod_title';
else if ($order_by == 'price') $order_by = 'prod_price';
else $order_by = 'prod_title';
?>


<script src="<?=WB_URL?>/modules/wbs_minishop/frontend2.js"></script> <!-- Только для одностраничника!!! -->
<link href="<?=WB_URL?>/modules/wbs_minishop/frontend.css" rel="stylesheet"> <!-- Только для одностраничника!!! -->

<style> <?=$minishop_settings['block_css']?> </style>
<style>
    <?php echo $minishop_settings['window_css']; ?>
</style>

<?php if(function_exists('wbs_core_include')) wbs_core_include(['functions.js', 'windows.js', 'windows.css']); ?>

<script>
    var section_id = <?php echo $section_id; ?>;
    var page_id = <?=PAGE_ID?>;
    var is_common_cart = <? echo json_encode($clsMinishop->is_common_cart); ?>;

    "use strict";
    
    mod_minishop = new mod_minishop_Main(section_id, page_id);
</script>

<?php $clsMinishop->print_cart_btn(); ?>
<?php $clsMinishop->print_cart(); ?>

<div style='float:right;'>
    Сортировать по
    <select onchange='window.location.search = "?sorted_by=" + this.value;'>
        <option value="name"  <?php if($order_by=='prod_name') echo 'selected'; ?>>Алфавиту</option>
        <option value="price" <?php if($order_by=='prod_price') echo 'selected'; ?>>Цене</option>
    </select>
</div><br>

<div class="view_products"> <?php

$sql = "SELECT * FROM `".TABLE_PREFIX."mod_wbs_minishop_products` WHERE ";
$sql .= "`section_id`=$section_id AND ";
//$sql .= '`page_id`='.$page_id.' AND ';
$sql .= "`prod_is_active`='1' ORDER BY `prod_category_id`, `$order_by`";
$products = $database->query($sql);
$current_category_id = '';
while($product = $products->fetchRow()) {

    $category_id = $product['prod_category_id'];
    $count = $product['prod_count'];

    if ($category_id != $current_category_id) {
        if (isset($category_array[$category_id])) echo "<h2>".$category_array[$category_id]."</h2>";
        $current_category_id = $category_id;
    }

    $tile = $twig->render('block_html', $clsMinishop->get_product_vars($product));
    echo $clsMinishop->wrap_product_tile($tile, $product);
} ?>
</div>

<script src="<?=WB_URL?>/modules/wbs_minishop/frontend_after.js" defer></script>