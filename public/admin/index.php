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
		// uninitialized
		if (!$adminController->isInstalled()) {
			$adminController->layoutContent(new ModuleAdminInstall());
			break;
		}

		$loggedIn = $adminController->isLoggedIn();
		// logged in
		if ($loggedIn === true) {
			$adminController->layoutContent(new ModuleAdminOverview());
		}
		// not logged in
		else if ($loggedIn === false) {
			$adminController->layoutContent(new ModuleAdminLogin());
		}
		// error during login
		else {
			$module = new ModuleAdminLogin();
			$module->setState($result);
			$adminController->layoutContent($module);
		}
		break;
	case 'install':
		$result = $adminController->install();
		$module = new ModuleAdminInstall();
		$module->setState($result);
		$adminController->layoutContent($module);
		break;
	default:
		echo "Invalid command.";
}


?>
