<?php
defined("ABSPATH") or die("");
require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/classes/entities/class.secure.global.entity.php');



$section = isset($_GET['section']) ? $_GET['section'] : 'general';
$txt_gen = DUP_PRO_U::__("General Settings");
$txt_migrate = DUP_PRO_U::__("Migrate Settings");
$url = 'admin.php?page=duplicator-pro-settings';

switch ($section) {
	case 'general':
			echo "<div class='dpro-sub-tabs'><b>{$txt_gen}</b> &nbsp;|&nbsp; <a href='{$url}&section=migrate'>{$txt_migrate}</a></div>";
			include ('general.php');
		break;
		case 'migrate':
			echo "<div class='dpro-sub-tabs'><a href='{$url}&section=general'>{$txt_gen}</a> &nbsp;|&nbsp; <b>{$txt_migrate}</b></div>";
			include ('migrate.php');
		break;
}
?>


