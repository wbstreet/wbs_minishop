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

if(!defined('WB_PATH')) die(header('Location: index.php'));  

include(WB_PATH.'/modules/wbs_minishop/lib.class.minishop.php');
$clsMinishop = new ModMinishop($page_id, $section_id);

$clsMinishop->add();

?>