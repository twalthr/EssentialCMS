<?php

abstract class Utils {

	public static function requireLibrary($name, $file) {
		global $ROOT_DIRECTORY;
		$oldDir = getcwd();
		$newDir = $ROOT_DIRECTORY . '/libs/' . $name . '/dist';
		chdir($newDir);
		require_once $newDir . '/' . $file;
		chdir($oldDir);
	}

	public static function loadFact($name) {
		global $ROOT_DIRECTORY;
		return file_get_contents($ROOT_DIRECTORY . '/facts/' . $name);
	}

	public static function configOrDefault($config, $key, $default) {
		if (isset($config[$key])) {
			$value = $config[$key];
			settype($value, gettype($default));
			return $value;
		}
		return $default;
	}

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

	public static function hasStringContent(&$str) { // & => forward for isset
		return isset($str) && !(trim((string) $str) === '');
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

	public static function getJsonFieldOrNull($str, $depth) {
		if (!Utils::isValidField($str)) {
			return null;
		}
		$fieldContent = Utils::getValidFieldStringOrNull($str);
		if ($fieldContent === null) {
			return null;
		}
		return json_decode($fieldContent, true, $depth);
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

	// modifies keys!
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

	public static function isValidPath($path) {
		if (strlen($path) > 512 ||
				strlen($path) < 1 ||
				substr($path, 0, 1) !== '/' ||
				substr($path, strlen($path) -1) === '/') {
			return false;
		}
		$split = explode('/', substr($path, 1));
		foreach ($split as $component) {
			if (strlen($component) === 0) {
				return false;
			}
		}
		return true;
	}

	public static function getFileExtension($fileName) {
		if (!isset($fileName) || strlen($fileName) === 0) {
			return '';
		}
		$split = explode('.', $fileName);
		return strtolower($split[sizeof($split) - 1]);
	}

	public static function normalizeTags($str) {
		$split = preg_split("/[,#]+/", $str);
		$normalized = [];
		foreach ($split as $value) {
			$trimmed = trim($value);
			if (strlen($trimmed) > 0) {
				$normalized[] = $trimmed;
			}
		}
		return implode(', ', array_unique($normalized));
	}

	public static function normalizeDuration($yearPart, $monthPart, $dayPart,
			$hourPart, $minutePart, $secondPart, $milliPart) {
		$milliPart = substr((string) $milliPart, 0, 3); // extract first 3 digits
		// convert overflows to next part (day part cannot overflow)
		$overflow = (int) ($secondPart / 60);
		$secondPart = $secondPart % 60;

		$minutePart = $minutePart + $overflow;
		$overflow = (int) ($minutePart / 60);
		$minutePart = $minutePart % 60;

		$hourPart = $hourPart + $overflow;
		$overflow = (int) ($hourPart / 24);
		$hourPart = $hourPart % 24;

		$dayPart = $dayPart + $overflow;
		return str_pad($yearPart, 4, '0', STR_PAD_LEFT) . '-' .
			str_pad($monthPart, 2, '0', STR_PAD_LEFT) . '-' .
			str_pad($dayPart, 2, '0', STR_PAD_LEFT) . ' ' .
			str_pad($hourPart, 2, '0', STR_PAD_LEFT) . ':' .
			str_pad($minutePart, 2, '0', STR_PAD_LEFT) . ':' .
			str_pad($secondPart, 2, '0', STR_PAD_LEFT) . '.' .
			str_pad($milliPart, 3, '0', STR_PAD_RIGHT);
	}

	public static function findLongestString($array) {
		if (!is_array($array)) {
			return $array;
		}
		if (count($array) == 1) {
			return $array[0];
		}
		usort($array, function($a, $b) {
			return strlen((string) $b) - strlen((string) $a);
		});
		return $array[0];
	}

	public static function arrayNestedUnique($array) {
		$serialized = array_map('serialize', $array);
		$unique = array_unique($serialized);
		return array_intersect_key($array, $unique);
	}

	public static function normalizeColor($name) {
		// source: https://stackoverflow.com/a/5925612
		$colors = array(
			'aliceblue'=>'f0f8ff',
			'antiquewhite'=>'faebd7',
			'aqua'=>'00ffff',
			'aquamarine'=>'7fffd4',
			'azure'=>'f0ffff',
			'beige'=>'f5f5dc',
			'bisque'=>'ffe4c4',
			'black'=>'000000',
			'blanchedalmond '=>'ffebcd',
			'blue'=>'0000ff',
			'blueviolet'=>'8a2be2',
			'brown'=>'a52a2a',
			'burlywood'=>'deb887',
			'cadetblue'=>'5f9ea0',
			'chartreuse'=>'7fff00',
			'chocolate'=>'d2691e',
			'coral'=>'ff7f50',
			'cornflowerblue'=>'6495ed',
			'cornsilk'=>'fff8dc',
			'crimson'=>'dc143c',
			'cyan'=>'00ffff',
			'darkblue'=>'00008b',
			'darkcyan'=>'008b8b',
			'darkgoldenrod'=>'b8860b',
			'darkgray'=>'a9a9a9',
			'darkgreen'=>'006400',
			'darkgrey'=>'a9a9a9',
			'darkkhaki'=>'bdb76b',
			'darkmagenta'=>'8b008b',
			'darkolivegreen'=>'556b2f',
			'darkorange'=>'ff8c00',
			'darkorchid'=>'9932cc',
			'darkred'=>'8b0000',
			'darksalmon'=>'e9967a',
			'darkseagreen'=>'8fbc8f',
			'darkslateblue'=>'483d8b',
			'darkslategray'=>'2f4f4f',
			'darkslategrey'=>'2f4f4f',
			'darkturquoise'=>'00ced1',
			'darkviolet'=>'9400d3',
			'deeppink'=>'ff1493',
			'deepskyblue'=>'00bfff',
			'dimgray'=>'696969',
			'dimgrey'=>'696969',
			'dodgerblue'=>'1e90ff',
			'firebrick'=>'b22222',
			'floralwhite'=>'fffaf0',
			'forestgreen'=>'228b22',
			'fuchsia'=>'ff00ff',
			'gainsboro'=>'dcdcdc',
			'ghostwhite'=>'f8f8ff',
			'gold'=>'ffd700',
			'goldenrod'=>'daa520',
			'gray'=>'808080',
			'green'=>'008000',
			'greenyellow'=>'adff2f',
			'grey'=>'808080',
			'honeydew'=>'f0fff0',
			'hotpink'=>'ff69b4',
			'indianred'=>'cd5c5c',
			'indigo'=>'4b0082',
			'ivory'=>'fffff0',
			'khaki'=>'f0e68c',
			'lavender'=>'e6e6fa',
			'lavenderblush'=>'fff0f5',
			'lawngreen'=>'7cfc00',
			'lemonchiffon'=>'fffacd',
			'lightblue'=>'add8e6',
			'lightcoral'=>'f08080',
			'lightcyan'=>'e0ffff',
			'lightgoldenrodyellow'=>'fafad2',
			'lightgray'=>'d3d3d3',
			'lightgreen'=>'90ee90',
			'lightgrey'=>'d3d3d3',
			'lightpink'=>'ffb6c1',
			'lightsalmon'=>'ffa07a',
			'lightseagreen'=>'20b2aa',
			'lightskyblue'=>'87cefa',
			'lightslategray'=>'778899',
			'lightslategrey'=>'778899',
			'lightsteelblue'=>'b0c4de',
			'lightyellow'=>'ffffe0',
			'lime'=>'00ff00',
			'limegreen'=>'32cd32',
			'linen'=>'faf0e6',
			'magenta'=>'ff00ff',
			'maroon'=>'800000',
			'mediumaquamarine'=>'66cdaa',
			'mediumblue'=>'0000cd',
			'mediumorchid'=>'ba55d3',
			'mediumpurple'=>'9370d0',
			'mediumseagreen'=>'3cb371',
			'mediumslateblue'=>'7b68ee',
			'mediumspringgreen'=>'00fa9a',
			'mediumturquoise'=>'48d1cc',
			'mediumvioletred'=>'c71585',
			'midnightblue'=>'191970',
			'mintcream'=>'f5fffa',
			'mistyrose'=>'ffe4e1',
			'moccasin'=>'ffe4b5',
			'navajowhite'=>'ffdead',
			'navy'=>'000080',
			'oldlace'=>'fdf5e6',
			'olive'=>'808000',
			'olivedrab'=>'6b8e23',
			'orange'=>'ffa500',
			'orangered'=>'ff4500',
			'orchid'=>'da70d6',
			'palegoldenrod'=>'eee8aa',
			'palegreen'=>'98fb98',
			'paleturquoise'=>'afeeee',
			'palevioletred'=>'db7093',
			'papayawhip'=>'ffefd5',
			'peachpuff'=>'ffdab9',
			'peru'=>'cd853f',
			'pink'=>'ffc0cb',
			'plum'=>'dda0dd',
			'powderblue'=>'b0e0e6',
			'purple'=>'800080',
			'red'=>'ff0000',
			'rosybrown'=>'bc8f8f',
			'royalblue'=>'4169e1',
			'saddlebrown'=>'8b4513',
			'salmon'=>'fa8072',
			'sandybrown'=>'f4a460',
			'seagreen'=>'2e8b57',
			'seashell'=>'fff5ee',
			'sienna'=>'a0522d',
			'silver'=>'c0c0c0',
			'skyblue'=>'87ceeb',
			'slateblue'=>'6a5acd',
			'slategray'=>'708090',
			'slategrey'=>'708090',
			'snow'=>'fffafa',
			'springgreen'=>'00ff7f',
			'steelblue'=>'4682b4',
			'tan'=>'d2b48c',
			'teal'=>'008080',
			'thistle'=>'d8bfd8',
			'tomato'=>'ff6347',
			'turquoise'=>'40e0d0',
			'violet'=>'ee82ee',
			'wheat'=>'f5deb3',
			'white'=>'ffffff',
			'whitesmoke'=>'f5f5f5',
			'yellow'=>'ffff00',
			'yellowgreen'=>'9acd32');

		$name = strtolower($name);
		if (isset($colors[$name])) {
			return '#' . $colors[$name];
		}
		return $name;
	}
}

?>