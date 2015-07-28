<?php

class Translator {
	private $currentLocale; // e.g. "en_US"
	private $dict;

	public function __construct($defaultLanguage, $defaultCountry, $languageSwitching) {
		if ($languageSwitching === false) {
			$this->currentLocale = $defaultLanguage . '_' . $defaultCountry;
		}
		else {
			$this->setLocaleAutomatically($defaultLanguage, $defaultCountry);
		}
	}

	private function setLocaleAutomatically($defaultLanguage, $defaultCountry) {
		if (isset($_COOKIE["locale"]) && $this->isLocaleSupported($_COOKIE["locale"])) {
			$this->currentLocale = $_COOKIE["locale"];
		}
		else if ($this->isLocaleSupported(Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']))) {
			$this->currentLocale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
		}
		else if ($this->isLocaleSupported($defaultLanguage . '_' . $defaultCountry)) {
			$this->currentLocale = $defaultLanguage . '_' . $defaultCountry;
		}
		else {
			$this->currentLocale = "en_US";
		}
	}

	public function isLocaleSupported($locale) {
		global $ROOT_DIRECTORY;
		$fileList = Utils::getFileList($ROOT_DIRECTORY . '/locales', '.locale');
		return in_array($locale, $fileList, true);
	}

}

?>