<?php

class Utils {
	public static function hasStringContents($str) {
		return isset($str) && !(trim($str) === '');
	}

	public static function stringStartsWith($haystack, $needle) {
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}

	public static function stringEndsWith($haystack, $needle) {
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}

	public static function getFileList($dir, $ending) {
		$fileList = array();
		if ($handle = opendir($dir)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != ".." && !is_dir($entry) && Utils::stringEndsWith($entry, $ending)) {
					$fileList[] = substr($entry, 0, -strlen($ending));
				}
			}
			closedir($handle);
		}
		return $fileList;
	}
}

?>