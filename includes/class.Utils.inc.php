<?php

class Utils {
	public static function hasStringContent($str) {
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
				if ($entry !== '.'
					&& $entry != '..'
					&& !is_dir($dir . '/' . $entry)
					&& Utils::stringEndsWith($entry, $ending)) {
					$fileList[] = substr($entry, 0, -strlen($ending));
				}
			}
			closedir($handle);
		}
		return $fileList;
	}

	public static function isValidFieldWithContentNoLinebreak($str, $maxlength) {
		if (isset($_POST[$str]) && is_string($_POST[$str])) {
			$trimmed = trim($_POST[$str]);
			$strLength = strlen($trimmed);
			if ($strLength > 0
				&& $strLength <= $maxlength
				&& strpos($trimmed, "\r") === false
				&& strpos($trimmed, "\n") === false) {
				return true;
			}
		}
		return false;
	}

	public static function isValidFieldNoLinebreak($str, $maxlength) {
		if (isset($_POST[$str]) && is_string($_POST[$str])) {
			$trimmed = trim($_POST[$str]);
			$strLength = strlen($trimmed);
			if ($strLength <= $maxlength
				&& strpos($trimmed, "\r") === false
				&& strpos($trimmed, "\n") === false) {
				return true;
			}
		}
		return false;
	}

	public static function isValidFieldIdentifier($str, $maxlength) {
		if (isset($_POST[$str]) && is_string($_POST[$str])) {
			if (preg_match("/^[.:0-9a-zA-Z+_-]+$/", $_POST[$str])
				&& strlen($_POST[$str]) <= $maxlength) {
				return true;
			}
		}
		return false;
	}

	public static function isValidFieldDate($str) {
		if (isset($_POST[$str]) && is_string($_POST[$str])) {
			if (preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/",
					$_POST[$str], $date)) {
				if (checkdate($date[2], $date[3], $date[1])) {
					return true;
				}
			}
		}
		return false;
	}

	public static function isChecked($str) {
		if (isset($_POST[$str]) && !empty($_POST[$str])) {
			return true;
		}
		return false;
	}

	public static function isValidFieldNotEmpty($str) {
		if (isset($_POST[$str]) && is_string($_POST[$str])) {
			$trimmed = trim($_POST[$str]);
			return strlen($trimmed) > 0;
		}
		return false;
	}

	public static function isValidFieldArray($str) {
		return isset($_POST[$str]) && is_array($_POST[$str]);
	}

	public static function isValidFieldArrayWithContent($str) {
		return isset($_POST[$str]) && is_array($_POST[$str]) && count($_POST[$str]) > 0;
	}

	public static function getValidFieldString($str) {
		return trim($_POST[$str]);
	}

	public static function getUnmodifiedStringOrEmpty($str) {
		if (isset($_POST[$str]) && is_string($_POST[$str])) {
			return $_POST[$str];
		}
		return '';
	}

	public static function getValidFieldStringOrNull($str) {
		$trimmed = trim($_POST[$str]);
		if (strlen($trimmed) === 0) {
			return null;
		}
		return $trimmed;
	}

	public static function getValidFieldArray($str) {
		return $_POST[$str];
	}

	public static function hasFields() {
		return isset($_POST) && count($_POST) > 0;
	}

	public static function getEscapedFieldOrVariable($field, $var) {
		if (Utils::isValidFieldNotEmpty($field)) {
			return Utils::escapeString($_POST[$field]);
		}
		else if (isset($var)) {
			return Utils::escapeString($var);
		}
		return '';
	}

	public static function getCheckedFieldOrVariable($field, $var) {
		if (Utils::isChecked($field)) {
			return true;
		}
		else if (isset($var)) {
			return true;
		}
		return false;
	}

	public static function getCheckedFieldOrVariableFlag($field, $var, $flag) {
		if (Utils::isChecked($field)) {
			return true;
		}
		else if (isset($var) && ($var & $flag)) {
			return true;
		}
		return false;
	}

	public static function escapeString($str) {
		return htmlspecialchars($str);
	}

	public static function internalHtmlToText($html) {
		return preg_replace('/\s\s+/', ' ', html_entity_decode(strip_tags($html)));
	}
}

?>