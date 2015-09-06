<?php

require_once '../../config.php';

$query = '';
if (isset($_GET['q'])) {
	$query = $_GET['q'];
}

$querySplitted = explode('/', $query);

$adminController = new AdminController();

switch ($querySplitted[0]) {
	case '':
		$adminController->layoutContent(new ModuleAdminLogin());
		break;
	case 'install':
		$adminController->install();
		break;
	default:
		echo "Invalid command.";
}


?>
