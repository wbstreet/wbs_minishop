DROP TABLE IF EXISTS `{TABLE_PREFIX}mod_wbs_minishop_settings`;
CREATE TABLE  `{TABLE_PREFIX}mod_wbs_minishop_settings` (
    `section_id` INT(11) NOT NULL,
    `page_id` INT(11) NOT NULL,
    `admin_email` VARCHAR(255) NOT NULL,
    `admin_login` VARCHAR(255) NOT NULL,
    `block_html` TEXT,
    `block_css` TEXT,
    `address_self_delivery` TEXT,
    `has_self_delivery` INT(11),
    `has_delivery` INT(11),
    `window_html` TEXT,
    `window_css` TEXT,
    `is_general_settings` INT(11) NOT NULL DEFAULT '0'
){TABLE_ENGINE=MyISAM};
        
DROP TABLE IF EXISTS `{TABLE_PREFIX}mod_wbs_minishop_products`;
CREATE TABLE `{TABLE_PREFIX}mod_wbs_minishop_products` (
    `section_id` INT(11) NOT NULL DEFAULT '0',
    `prod_id` INT(11) NOT NULL AUTO_INCREMENT,
    `prod_category_id` INT(11),
    `page_id` INT(11) NOT NULL DEFAULT '0',
    `prod_title` VARCHAR(255) NOT NULL,
    `prod_shortdesc` VARCHAR(255),
    `prod_desc` TEXT(500),
    `prod_price` INT(11) NOT NULL,
    `prod_is_active` INT(11) NOT NULL DEFAULT '0',
    `prod_count` INT(11) NOT NULL DEFAULT '0',
    `prod_image_name` VARCHAR(255) NOT NULL,
    `prop_value_ids` JSON,
    `prod_is_hit` INT(11),
    PRIMARY KEY (prod_id)
){TABLE_ENGINE=MyISAM};

DROP TABLE IF EXISTS `{TABLE_PREFIX}mod_wbs_minishop_photos`;
CREATE TABLE `{TABLE_PREFIX}mod_wbs_minishop_photos` (
    `photo_id` INT(11) NOT NULL AUTO_INCREMENT,
    `storage_image_id` INT(11) NOT NULL,
    `prod_id` INT(11) NOT NULL,
    `photo_name` varchar(255) NOT NULL,
    `photo_position` INT(11) NOT NULL DEFAULT '0',
    `photo_is_main` INT(11) NOT NULL,
     PRIMARY KEY (`photo_id`)
){TABLE_ENGINE=MyISAM};
        
DROP TABLE IF EXISTS `{TABLE_PREFIX}mod_wbs_minishop_categories`;
CREATE TABLE `{TABLE_PREFIX}mod_wbs_minishop_categories` (
    `section_id` INT(11) NOT NULL DEFAULT '0',
    `category_id` INT(11) NOT NULL AUTO_INCREMENT,
    `page_id` INT(11) NOT NULL DEFAULT '0',
    `category_name` VARCHAR(255) NOT NULL,
    PRIMARY KEY (category_id)
){TABLE_ENGINE=MyISAM};
        
DROP TABLE IF EXISTS `{TABLE_PREFIX}mod_wbs_minishop_users`;
CREATE TABLE `{TABLE_PREFIX}mod_wbs_minishop_users` (
    `section_id` INT(11) NOT NULL DEFAULT '0',
    `user_id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id_in_engine` INT(11) NOT NULL DEFAULT '0' ,
    `page_id` INT(11) NOT NULL DEFAULT '0',
    `user_name` VARCHAR(255) NOT NULL,
    `user_address` VARCHAR(255) NOT NULL,
    `user_phone` VARCHAR(255) NOT NULL,
    PRIMARY KEY (user_id)
){TABLE_ENGINE=MyISAM};
        


DROP TABLE IF EXISTS `{TABLE_PREFIX}mod_wbs_minishop_prop`;
CREATE TABLE `{TABLE_PREFIX}mod_wbs_minishop_prop` (
    `prop_id` INT(11) NOT NULL AUTO_INCREMENT,
    `page_id` INT(11) NOT NULL,
    `section_id` INT(11) NOT NULL,
    `prop_name` varchar(255) NOT NULL,
    `prop_type` varchar(10) NOT NULL,
    PRIMARY KEY (prop_id)
){TABLE_ENGINE=MyISAM};
        

DROP TABLE IF EXISTS `{TABLE_PREFIX}mod_wbs_minishop_prop_values`;
CREATE TABLE `{TABLE_PREFIX}mod_wbs_minishop_prop_values` (
    `prop_value_id` INT(11) NOT NULL AUTO_INCREMENT,
    `page_id` INT(11) NOT NULL,
    `section_id` INT(11) NOT NULL,
    `prop_id` INT(11) NOT NULL,
    `value` varchar(255) NOT NULL,
    PRIMARY KEY (prop_value_id)
){TABLE_ENGINE=MyISAM};