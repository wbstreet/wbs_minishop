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

$_section_id = mysql_escape_string($section_id);

$sql = "SELECT * FROM `" .TABLE_PREFIX ."mod_wbs_minishop_products` WHERE `section_id` = '".$_section_id."'";
if ($database->query($sql)->numRows() > 0 ) {
    $admin->print_header();
    $admin->print_error('Секция не удалена: в магазине есть товары!', ADMIN_URL.'/pages/sections.php?page_id='.$page_id);
    die();
}

//	delete the row of the module table which contains the actual page
$database->query("DELETE FROM `" .TABLE_PREFIX ."mod_wbs_minishop_settings` WHERE `section_id` = '$_section_id'");
$database->query("DELETE FROM `" .TABLE_PREFIX ."mod_wbs_minishop_users` WHERE `section_id` = '$_section_id'");
$database->query("DELETE FROM `" .TABLE_PREFIX ."mod_wbs_minishop_categories` WHERE `section_id` = '$_section_id'");

?>