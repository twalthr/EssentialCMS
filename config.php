<?php

error_reporting(E_ALL);

// parameters
$DEFAULT_LANGUAGE = 'en';
$DEFAULT_COUNTRY = "US";
$LANGUAGE_SWITCHING = true;

// set class autoloader
$ROOT_DIRECTORY = dirname(__FILE__);
spl_autoload_register(function ($class) {
	global $ROOT_DIRECTORY;
	require_once $ROOT_DIRECTORY . '/includes/class.' . $class . '.inc.php';
});

// constants
$TRANSLATOR = new Translator($DEFAULT_LANGUAGE, $DEFAULT_COUNTRY, $LANGUAGE_SWITCHING);

?>