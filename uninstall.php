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

include(__DIR__.'/lib.class.minisho.php');
$clsModMinishop = new ModMinishop(null, null);
$r = $clsModMinishop->uninstall();
if (gettype($r) === 'string') {
    $admin->print_error($r);
}
?>