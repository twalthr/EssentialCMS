<?php

error_reporting(E_ALL);

// parameters
$DEBUG = true;

$DEFAULT_LANGUAGE = 'en';
$DEFAULT_COUNTRY = 'US';
$LANGUAGE_SWITCHING = true;

// Database recommendations:
// use "utf8mb4_bin" -> case sensitive, unnormalized, supports emojis
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
	if ($class === 'Locale') {
		die('Could not find class "Locale". Make sure to enable the "intl" PHP extension.');
	}
	require_once $ROOT_DIRECTORY . '/includes/class.' . $class . '.inc.php';
});
$INCLUDE_DIRECTORY = $ROOT_DIRECTORY . '/includes';

// constants
$TR = new Translator($DEFAULT_LANGUAGE, $DEFAULT_COUNTRY, $LANGUAGE_SWITCHING);
$CMS_VERSION = 1;
$CMS_FULLNAME = 'EssentialCMS v' . $CMS_VERSION;
$CMS_URL = 'https://github.com/twalthr/EssentialCMS';

// set default error message
function logInfo($message, $e = null) {
	logEvent(false, $message, $e);
}
function logWarning($message, $e = null) {
	logEvent(true, $message, $e);
}
function logEvent($warning, $message, $e = null) {
	global $DEBUG, $TR;
	if ($DEBUG && $warning) {
		die("Warning: " . $message . "\n" . $e);
	}
	else if ($DEBUG && !$warning) {
		die("Info: " . $message . "\n" . $e);
	}
	// in the future this might be written to a file
	else {
		echo $TR->translate('INTERNAL_SERVER_ERROR');
	}
}

// convert errors into exceptions
function globalErrorHandler($errno, $errstr, $errfile, $errline) {
	global $TR;
	$message = $TR->translate('INTERNAL_SERVER_ERROR') . "\n" .
		'Code: ' . $errno . "\n" .
		'Message: ' . $errstr . "\n" .
		'File: ' . $errfile . "\n" .
		'Line: ' . $errline;
	throw new ErrorException($message, 0, $errno, $errfile, $errline);
}
set_error_handler('globalErrorHandler');

$DB = new Database($DB_HOST, $DB_NAME, $DB_USERNAME, $DB_PASSWORD);

$success = $DB->connect();
if (!$success) {
	throw new Exception("Could not connect to database: " . $DB->getLastError());
}

?>