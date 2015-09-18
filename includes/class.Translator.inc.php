<?php

class Translator {
	private $currentLocale; // e.g. "en_US"
	private $localeEnglishName;
	private $localeLocaleName;
	private $dict = array();

	public function __construct($defaultLanguage, $defaultCountry, $languageSwitching) {
		if ($languageSwitching === false) {
			$this->currentLocale = $defaultLanguage . '_' . $defaultCountry;
		}
		else {
			$this->setLocaleAutomatically($defaultLanguage, $defaultCountry);
		}
	}

	public function isLocaleSupported($locale) {
		global $ROOT_DIRECTORY;
		$fileList = Utils::getFileList($ROOT_DIRECTORY . '/locales', '.locale');
		return in_array($locale, $fileList, true);
	}

	public function translate($id, ...$args) {
		$this->checkAndLoadDict();
		if (array_key_exists($id, $this->dict)) {
			return sprintf(trim($this->dict[$id]), ...$args);
		}
		return $id;
	}

	public function getSupportedLocaleFromDirectory($dir) {
		$fileList = Utils::getFileList($dir, '.locale');
		// no locales found
		if (count($fileList) === 0) {
			return false;
		}
		// check if locale exists
		if (in_array($this->currentLocale, $fileList, true)) {
			return $this->currentLocale;
		}
		// fallback to englisch
		if (in_array('en_US', $fileList, true)) {
			return 'en_US';
		}
		// fallback to any supported locale
		return $fileList[0];
	}

	public function translateFromLocaleFile($filePath, $id, ...$args) {
		// TODO chaching of dict load/unload
		// 2 levels
	}

	// --------------------------------------------------------------------------------------------

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

	private function checkAndLoadDict() {
		if (count($this->dict) == 0) {
			global $ROOT_DIRECTORY;
			$localeFile = fopen($ROOT_DIRECTORY . '/locales/' . $this->currentLocale . '.locale', 'r');
			$i = 0;
			while(!feof($localeFile)) {
				$row = fgets($localeFile);
				if ($i == 0) {
					$i++;
					$this->localeEnglishName = $row;
				}
				else if ($i == 1) {
					$i++;
					$this->localeLocaleName = $row;
				}
				else {
					$rowSplitted = explode('=', $row, 2);
					$this->dict[$rowSplitted[0]] = $rowSplitted[1];
				}
			}
			fclose($localeFile);
		}
	}

	// --------------------------------------------------------------------------------------------

	public static function readHeaderFromLocaleFile($filePath) {
		$header = [];
		$localeFile = fopen($filePath, 'r');
		$i = 0;
		while(!feof($localeFile) && $i < 2) {
			$row = fgets($localeFile);
			if ($row !== false) {
				$header[] = $row;
			}
			$i++;
		}
		fclose($localeFile);
		return $header;
	}
}

?>