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

$categories = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_minishop_categories` WHERE `section_id` = '$section_id'");
$category_array = [];
while ($category = $categories->fetchRow()) {
    $category_array[$category['category_id']] = $category['category_name'];
}

function echoCategorySelect($name, $firstValue, $firstTextContent, $selectedValue=null) {
    global $category_array;
    echo "<select name=\"".$name."\">\n";
    echo "<option value=\"".$firstValue."\">".$firstTextContent."</option>\n";
    foreach ($category_array as $k => $v) {
        $c = ($selectedValue !== null) && ($selectedValue == $k) ? 'selected' : '';
        echo "<option value=\"".$k."\" ".$c.">".$v."</option>\n";
    }
    echo "</select>";
}
?>