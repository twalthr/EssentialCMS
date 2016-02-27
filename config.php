<?php

error_reporting(E_ALL);

// parameters
$DEBUG = true;

$DEFAULT_LANGUAGE = 'en';
$DEFAULT_COUNTRY = 'US';
$LANGUAGE_SWITCHING = true;

$DB_HOST = 'localhost';
$DB_NAME = 'ecms';
$DB_USERNAME = 'root';
$DB_PASSWORD = '';

$PUBLIC_ROOT = 'http://localhost/EssentialCMS/public';

$MAX_RUNTIME = 30;
$MAX_RUNTIME_STOP_FACTOR = 0.1;

// set class autoloader
$ROOT_DIRECTORY = dirname(__FILE__);
spl_autoload_register(function ($class) {
	global $ROOT_DIRECTORY;
	require_once $ROOT_DIRECTORY . '/includes/class.' . $class . '.inc.php';
});
$INCLUDE_DIRECTORY = $ROOT_DIRECTORY . '/includes';

// constants
$TR = new Translator($DEFAULT_LANGUAGE, $DEFAULT_COUNTRY, $LANGUAGE_SWITCHING);
$CMS_VERSION = 1;
$CMS_FULLNAME = 'EssentialCMS v' . $CMS_VERSION;
$CMS_URL = 'https://github.com/twalthr/EssentialCMS';

// set default error message
function handleError($fatal, $message) {
	global $DEBUG, $TR;
	if ($DEBUG && $fatal) {
		die("Fatal error: " . $message);
	}
	else if ($DEBUG && !$fatal) {
		echo "Warning: " . $message . '<br/>';
	}
	else if (!$DEBUG && $fatal) {
		die($TR->translate('INTERNAL_SERVER_ERROR'));
	}
	else if (!$DEBUG && !$fatal) {
		echo $TR->translate('INTERNAL_SERVER_ERROR') . '<br/>';
	}
}
function globalErrorHandler($errno, $errstr, $errfile, $errline) {
	handleError($errno == E_ERROR || $errno == E_USER_ERROR,
		'Code: ' . $errno . '<br/>' .
		'Message: ' . $errstr . '<br/>' .
		'File: ' . $errfile . '<br/>' .
		'Line: ' . $errline
		);
}
set_error_handler('globalErrorHandler');

$DB = new Database($DB_HOST, $DB_NAME, $DB_USERNAME, $DB_PASSWORD);

$success = $DB->connect();
if (!$success) {
	handleError(true, "Could not connect to database: " . $DB->getLastError());
}

?>