<?php

require_once '../../config.php';

$query = '';
if (isset($_GET['q'])) {
	$query = $_GET['q'];
}

$querySplitted = explode('/', $query);

$action = $querySplitted[0];
$parameters = array_slice($querySplitted, 1);

$controller = new AdminController();

switch ($action) {
	case '':
		// uninitialized
		if (!$controller->isInstalled()) {
			header('Location: ' . $PUBLIC_ROOT . '/admin/install');
			exit;
		}

		$loggedIn = $controller->login();
		// logged in
		if ($loggedIn === true) {
			header('Location: ' . $PUBLIC_ROOT . '/admin/overview');
			exit;
		}
		// not logged in
		else if ($loggedIn === false) {
			$controller->layoutContent(new AdminLoginModule());
		}
		// error during login
		else {
			$module = new AdminLoginModule();
			$module->setState($loggedIn);
			$controller->layoutContent($module);
		}
		break;
	case 'install':
		$installed = $controller->install();
		// not installed
		if ($installed === false) {
			$controller->layoutContent(new AdminInstallModule());
		}
		// success or error during installation
		else {
			$module = new AdminInstallModule();
			$module->setState($installed);
			$controller->layoutContent($module);
		}
		break;
	case 'overview':
		$controller->verifyLogin();
		$controller->layoutLoggedInContent(0, null, null, new AdminOverviewModule($controller));
		break;
	case 'pages':
		$controller->verifyLogin();
		$controller->layoutLoggedInContent(1, null, null, new AdminPagesModule($controller));
		break;
	case 'new-page':
		$controller->verifyLogin();
		$module = new AdminEditPageModule(
			$controller->getPageOperations(),
			null,
			$controller->getMenuItemOperations());
		$controller->layoutLoggedInContent(1, null, null, $module);
		break;
	case 'page':
		$controller->verifyLogin();
		$module = new AdminEditPageModule(
			$controller->getPageOperations(),
			$controller->getModuleOperations(),
			$controller->getMenuItemOperations(), $parameters);
		$controller->layoutLoggedInContent(1, null, null, $module);
		break;
	case 'select-module-dialog':
		$controller->verifyLogin();
		$controller->layoutDialog(new AdminSelectModuleModule($controller));
		break;
	case 'export-module-dialog':
		$controller->verifyLogin();
		$module = new AdminExportModuleModule(
			$controller->getPageOperations(), $parameters);
		$controller->layoutDialog($module);
		break;
	case 'module':
		$controller->verifyLogin();
		$module = new AdminEditModuleModule(
			$controller->getModuleOperations(),
			$controller->getFieldGroupOperations(),
			$controller->getFieldOperations(), $parameters);
		$controller->layoutLoggedInContent(1, null, null, $module);
		break;
	case 'module-options':
		$controller->verifyLogin();
		$module = new AdminModuleConfigModule(
			$controller->getModuleOperations(),
			$controller->getFieldGroupOperations(),
			$controller->getFieldOperations(), $parameters);
		$controller->layoutLoggedInContent(1, null, null, $module);
		break;
	default:
		echo "Invalid command.";
}

?>
