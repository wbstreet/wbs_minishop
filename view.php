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

global $prod_id, $TEXT, $MESSAGE;

if(!file_exists(WB_PATH .'/modules/wbs_minishop/languages/' .LANGUAGE .'.php')) {
        require_once(WB_PATH .'/modules/wbs_minishop/languages/EN.php');
} else {
                require_once(WB_PATH .'/modules/wbs_minishop/languages/' .LANGUAGE .'.php');
}

if (!class_exists("ModMinishop")) include(WB_PATH.'/modules/wbs_minishop/lib.class.minishop.php');
include('common.php');

$clsMinishop = new ModMinishop($page_id, $section_id);
$minishop_settings = $clsMinishop->get_settings();

if (function_exists('wbs_core_include')) {
        ob_start(); 
        wbs_core_include(['functions.js', 'windows.js', 'windows.css']);
        $incl = ob_get_contents();
    ob_end_clean();
} else {$incl = "";}

$common_array = [
        "includes"=>$incl,
        "section_id"=>$section_id,
        "page_id"=>PAGE_ID,
        "is_common_cart"=>json_encode($clsMinishop->is_common_cart),
        "settings"=>$minishop_settings,
        "TEXT"=>$TEXT,
        "WB_URL"=>WB_URL,
];

if (isset($prod_id)) {
        
        $r = $clsMinishop->get_obj([
            'prod_id'=>$prod_id,
            'is_copy_for'=>'0',
        ]);

        $current_category_id = '';
        if ($r !== null) {
                $prod = $r->fetchRow(MYSQL_ASSOC);

            $category_id = $prod['prod_category_id'];
            $count = $product['prod_count'];
        
        
                $clsMinishop->render('frontend_product.twig', array_merge(
                        $clsMinishop->get_product_vars($prod),
                        $common_array
                ));
        }
        
} else {

        $clsMinishop->add_loader('array', [
                'block_html' => $minishop_settings['block_html']
        ]);
        
        $order_by = isset($_GET['sorted_by']) ? $_GET['sorted_by'] : 'prod_title';
        if ($order_by == 'name') $order_by = 'prod_title';
        else if ($order_by == 'price') $order_by = 'prod_price';
        else $order_by = 'prod_title';
        
        // Вынимаем товары
        
        $r = $clsMinishop->get_obj([
            'section_id'=>$section_id,
            'is_copy_for'=>'0',
            'prod_is_active'=>'1',
            'order_by'=>['prod_category_id', $order_by]
        ]);

        $prods = [];
        while($r !== null && $product = $r->fetchRow()) {
            $prods[] = $clsMinishop->get_product_vars($product);
        }
        
        $clsMinishop->render('frontend_product_list.twig', array_merge([
                "order_by"=>$order_by,
                "prods"=>$prods,
                "categories"=>$category_array,
        ], $common_array));
}
?>
