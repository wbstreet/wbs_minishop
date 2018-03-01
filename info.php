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

if(!defined('WB_PATH')) {
	require_once(dirname(dirname(__FILE__)).'/framework/globalExceptionHandler.php');
	throw new IllegalFileException();
}
/* -------------------------------------------------------- */
$module_directory = 'wbs_minishop';
$module_name = 'WBS Minishop v 2.0';
$module_function = 'page';
$module_version = '3.0';
$module_platform = '2.8.3';
$module_author = 'Konstantin Polyakov';
$module_license = 'GNU General Public License';
$module_description = 'Minishop is page module for different web-portals - some shops on one portal. Users can do orders without registration. You can visit the author\'s website http://dosmth.ru to know about his another project.';

$links = ['windows', 'media_efects']; // зависимости

?>