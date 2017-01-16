<?php

abstract class Utils {

	public static function readFirstLine($str) {
		return strtok($str, "\n");
	}

	public static function deleteFirstLine($str) {
		return substr($str, strpos($str, "\n") + 1);
	}

	public static function redirect($url, $statusCode = 303) {
		header('Location: ' . $url, true, $statusCode);
		die();
	}

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

	public static function isValidFieldLink($str, $maxlength) {
		if (isset($_POST[$str]) && is_string($_POST[$str])) {
			if (preg_match("/^[a-zA-Z]+:/", $_POST[$str])
					&& strlen($_POST[$str]) <= $maxlength) {
				return true;
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

	public static function isValidField($str) {
		if (isset($_POST[$str]) && is_string($_POST[$str])) {
			return true;
		}
		return false;
	}

	public static function isValidFieldWithMaxLength($str, $maxlength) {
		if (isset($_POST[$str]) && is_string($_POST[$str])) {
			$trimmed = trim($_POST[$str]);
			$strLength = strlen($trimmed);
			if ($strLength <= $maxlength) {
				return true;
			}
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

	public static function isValidFieldInt($str) {
		$notNullStr = Utils::getUnmodifiedStringOrEmpty($str);
		return filter_var($notNullStr, FILTER_VALIDATE_INT) !== false;
	}

	public static function isValidFieldIntArray($str) {
		if (!Utils::isValidFieldArray($str)) {
			return false;
		}
		foreach ($_POST[$str] as $value) {
			if (filter_var($value, FILTER_VALIDATE_INT) === false) {
				return false;
			}
		}
		return true;
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

	public static function isValidFieldWithTags($str, $maxlength) {
		if (isset($_POST[$str]) && is_string($_POST[$str])) {
			$trimmed = trim($_POST[$str]);
			$strLength = strlen($trimmed);
			if ($strLength <= $maxlength && preg_match('/^[\w ,_#]*$/', $trimmed) === 1) {
				return true;
			}
		}
		return false;
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

	public static function getValueFieldOrVariableFlag($field, $value, $var) {
		if (Utils::getUnmodifiedStringOrEmpty() === $value) {
			return true;
		}
		else if (isset($var) && $var === $value) {
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

	public static function isValidInt($str) {
		return filter_var($str, FILTER_VALIDATE_INT) !== false;
	}

	public static function escapeString($str) {
		if (!isset($str)) {
			return '';
		}
		return htmlspecialchars($str);
	}

	public static function internalHtmlToText($html) {
		return preg_replace('/\s\s+/', ' ', html_entity_decode(strip_tags($html)));
	}

	public static function getColumnWithValue(&$array, $column, $value) {
		$key = array_search($value, array_column($array, $column), true);
		if ($key === false) {
			return false;
		}
		return $array[$key];
	}

	public static function getColumnWithValues(&$array, $column, $value) {
		$keys = array_keys(array_column($array, $column), $value, true);
		$values = [];
		foreach ($keys as $key) {
			$values[] = &$array[$key];
		}
		if (count($values) === 0) {
			return false;
		}
		return $values;
	}

	public static function project(&$array, ...$keys) {
		foreach ($array as $key => $value) {
			if (!in_array($key, $keys)) {
				unset($array[$key]);
			}
		}
	}

	public static function sortArray(&$array, $fields) {
		usort($array,
			function ($a, $b) use (&$fields) {
				foreach ($fields as $field) {
					$diff = strcmp($a[$field], $b[$field]);
					if($diff != 0) {
						return $diff;
					}
				}
				return 0;
			});
	}

	public static function arrayEqual(&$array1, &$array2, ...$keys) {
		if (!is_array($array1) || !is_array($array2) ) {
			return $array1 == $array2;
		}
		// build new array1 with keys
		$projectedArray1 = [];
		foreach ($array1 as $value) {
			Utils::project($value, ...$keys);
			$projectedArray1[] = $value;
		}
		// build new array2 with keys
		$projectedArray2 = [];
		foreach ($array2 as $value) {
			Utils::project($value, ...$keys);
			$projectedArray2[] = $value;
		}
		Utils::sortArray($projectedArray1, $keys);
		Utils::sortArray($projectedArray2, $keys);
		return $projectedArray1 == $projectedArray2;
	}

	public static function isFlagged($var, $flag) {
		return ($var & $flag) === $flag;
	}

	public static function setFlag($var, $flag) {
		return $var | $flag;
	}

	public static function unsetFlag($var, $flag) {
		return $var & ~$flag;
	}
}

?>