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
  Module developed for the Open Source Content Management System Website Baker (http://websitebaker.org)
  Copyright (C) 2016, Konstantin Polyakov
  Contact me: shyzik93@mail.ru, http://just-idea.ru

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

// delete all database search table entries made by this module
$database->query("DELETE FROM `" .TABLE_PREFIX ."search` WHERE `name` = 'module' AND `value` = 'minishop'");
$database->query("DELETE FROM `" .TABLE_PREFIX ."search` WHERE `extra` = 'minishop'");

// delete the module database table
$database->query("DROP TABLE `" .TABLE_PREFIX ."mod_minishop_settings`");
$database->query("DROP TABLE `" .TABLE_PREFIX ."mod_minishop_products`");
$database->query("DROP TABLE `" .TABLE_PREFIX ."mod_minishop_categories`");
$database->query("DROP TABLE `" .TABLE_PREFIX ."mod_minishop_users`");
?>