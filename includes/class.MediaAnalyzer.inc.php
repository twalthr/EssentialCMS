<?php

abstract class MediaAnalyzer {

	protected $cmsVersion;
	protected $name;
	protected $config;

	public function __construct($cmsVersion, $name, $config) {
		$this->cmsVersion = $cmsVersion;
		$this->name = $name;
		$this->config = $config;
	}

	public function nameMatches($name) {
		return false;
	}

	public function extensionMatches($extension) {
		return false;
	}

	public function magicNumberMatches($hexMagicNumber) {
		return false;
	}

	public function mimeMatches($mime) {
		return false;
	}

	public function textContentMatches($text) {
		return false;
	}

	// full indicates if thumbnail can be cropped 
	public function generateThumbnail($full, $width, $height, $src, $dst) {
		return false;
	}

	public function extractProperties($src, $ext) {
		return [];
	}

	// --------------------------------------------------------------------------------------------
	// Utilities
	// --------------------------------------------------------------------------------------------

	protected function addDateTime(&$values, $key, $propsKey, &$props) {
		if (isset($values[$key])) {
			$value = Utils::findLongestString($values[$key]);
			if (Utils::hasStringContent($value)) {
				$parsed = date_parse($value);
				if ($parsed !== false &&
						$parsed['warning_count'] === 0 &&
						$parsed['error_count'] === 0 &&
						$parsed['year'] !== false &&
						$parsed['month'] !== false &&
						$parsed['day'] !== false &&
						$parsed['hour'] !== false &&
						$parsed['minute'] !== false &&
						$parsed['second'] !== false) {
					$props[] = [$propsKey, $parsed['year'] . '-' . $parsed['month'] . '-' . $parsed['day'] . ' ' .
						$parsed['hour'] . ':' . $parsed['minute'] . ':' . $parsed['second']];
					return true;
				}
			}
		}
		return false;
	}

	protected function addOtherProperty(&$values, $key, &$props) {
		if (isset($values[$key])) {
			$value = $this->stringifyArray($values[$key]);
			if (Utils::hasStringContent($value)) {
				$props[] = [MediaProperties::KEY_OTHER, $key . '=' . $value];
			}
		}
	}

	protected function stringifyArray($array) {
		$result = '';
		if (is_array($array)) {
			$result .= '[';
			foreach ($array as $key => $value) {
				$result .= $key . '=' . $this->stringifyArray($value) . ', ';
			}
			$result = rtrim($result, ', ');
			$result .= ']';
		} else if (is_bool($array)) {
			$result .= ($array) ? 'true' : 'false';
		} else {
			// remove illegal characters
			// source: http://stackoverflow.com/a/1176923
			$result .= preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $array);
		}
		return $result;
	}
}