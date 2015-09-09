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
			header('Location: ' . $PUBLIC_ROOT . '/admin/install');
			exit;
		}

		$loggedIn = $adminController->login();
		// logged in
		if ($loggedIn === true) {
			header('Location: ' . $PUBLIC_ROOT . '/admin/overview');
			exit;
		}
		// not logged in
		else if ($loggedIn === false) {
			$adminController->layoutContent(new ModuleAdminLogin());
		}
		// error during login
		else {
			$module = new ModuleAdminLogin();
			$module->setState($loggedIn);
			$adminController->layoutContent($module);
		}
		break;
	case 'overview':
		checkLogin($adminController);
		$adminController->layoutContent(new ModuleAdminOverview());
		break;
	case 'install':
		$installed = $adminController->install();
		// not installed
		if ($installed === false) {
			$adminController->layoutContent(new ModuleAdminInstall());
		}
		// success or error during installation
		else {
			$module = new ModuleAdminInstall();
			$module->setState($installed);
			$adminController->layoutContent($module);
		}
		break;
	default:
		echo "Invalid command.";
}

// ------------------------------------------------------------------------------------------------

function checkLogin($adminController) {
	global $PUBLIC_ROOT;
	if ($adminController->login() !== true) {
		header('Location: ' . $PUBLIC_ROOT . '/admin');
		exit;
	}
}

?>
