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
	case 'overview':
		$adminController->layoutLoggedInContent(0, null, null, new ModuleAdminOverview($adminController));
		break;
	case 'pages':
		$adminController->layoutLoggedInContent(1, null, null, new ModuleAdminPages($adminController));
		break;
	case 'new-page':
		$adminController->layoutLoggedInContent(1, null, null, new ModuleAdminEditPage($adminController));
		break;
	case 'edit-page':
		$pageId = null;
		if (count($querySplitted) > 1) {
			$pageId = $querySplitted[1];
		}
		$adminController->layoutLoggedInContent(1, null, null, new ModuleAdminEditPage($adminController, $pageId));
		break;
	
	default:
		echo "Invalid command.";
}

?>
