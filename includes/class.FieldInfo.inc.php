<?php

// Defines a field. Each field can have one or an array of a type and a content.
class FieldInfo {

	const TYPE_PLAIN = 1;
	const TYPE_HTML = 2;
	const TYPE_MARKDOWN = 4;
	const TYPE_IMAGE = 8;
	const TYPE_FILE = 16;
	const TYPE_TAGS = 32;
	const TYPE_INT = 64;
	const TYPE_BOOLEAN = 128;
	const TYPE_ENUM = 256;
	const TYPE_DATE = 512;
	const TYPE_COLOR = 1024;
	const TYPE_LINK = 2048;
	const TYPE_PAGE = 4096;
	const TYPE_ID = 8192;
	const TYPE_EMAIL = 16384;
	const TYPE_LOCALE = 32768; // e.g. de-DE
	const TYPE_DATE_TIME = 65536;
	const TYPE_FLOAT = 131072;
	const TYPE_DURATION = 262144; // e.g. 2017-12-12 13:20:11.001
	const TYPE_RANGE = 524288;
	const TYPE_ENCRYPTED = 1048576;

	private $key;
	private $allowedTypes;
	private $name;
	private $array;
	private $required;
	private $largeContent;
	private $minContentLength; // multibyte character length or maximum value/'2017-12-12 13:20:11'
	private $maxContentLength; // multibyte character length or minimum value/'2017-12-12 13:20:11'
	// e.g. for type boolean = checkbox string, for type enum = values, for type range = step size
	private $auxiliaryInfo;
	private $defaultType;
	private $defaultContent;

	public static function create($array) {
		return new FieldInfo(
			array_key_exists('key', $array) ? $array['key'] : null,
			array_key_exists('types', $array) ? $array['types'] : null,
			array_key_exists('name', $array) ? $array['name'] : null,
			array_key_exists('array', $array) ? $array['array'] : null,
			array_key_exists('required', $array) ? $array['required'] : null,
			array_key_exists('large', $array) ? $array['large'] : null,
			array_key_exists('min', $array) ? $array['min'] : null,
			array_key_exists('max', $array) ? $array['max'] : null,
			array_key_exists('auxiliaryInfo', $array) ? $array['auxiliaryInfo'] : null,
			array_key_exists('defaultType', $array) ? $array['defaultType'] : null,
			array_key_exists('defaultContent', $array) ? $array['defaultContent'] : null
			);
	}

	public function __construct(
		$key,
		$allowedTypes,
		$name,
		$array = null,
		$required = null,
		$largeContent = null,
		$minContentLength = null,
		$maxContentLength = null,
		$auxiliaryInfo = null,
		$defaultType = null,
		$defaultContent = null) {

		$this->key = $key;
		$this->allowedTypes = $allowedTypes;
		$this->name = $name;
		$this->array = $array;
		$this->required = $required;
		$this->largeContent = $largeContent;
		$this->minContentLength = $minContentLength;
		$this->maxContentLength = $maxContentLength;
		$this->auxiliaryInfo = $auxiliaryInfo;
		$this->defaultType = $defaultType;
		$this->defaultContent = $defaultContent;

		// validate parameters
		if (!isset($this->key) && !preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $this->key)) {
			throw new Exception("Key must not be null and only consist of [A-Za-z][A-Za-z0-9]*.");
		}
		if (!isset($this->allowedTypes) || !is_int($this->allowedTypes) || $this->allowedTypes < 1) {
			throw new Exception("Allowed types must be set.");
		}
		if (!isset($this->name)) {
			throw new Exception("Name must not be null.");
		}
		if (isset($this->array) && !is_bool($this->array)) {
			throw new Exception("Array can only be boolean or null.");
		}
		if (isset($this->required) && !is_bool($this->required)) {
			throw new Exception("Required can only be boolean or null.");
		}
		if (isset($this->largeContent) && !is_bool($this->largeContent)) {
			throw new Exception("Large content can only be boolean or null.");
		}
		if ($this->allowedTypes & FieldInfo::TYPE_DATE_TIME) {
			// format is '2017-12-12 13:20:11'
			if (isset($this->minContentLength) && (!is_string($this->minContentLength) ||
					strtotime($this->minContentLength) === false)) {
				throw new Exception("Minimum content length can only be string or null.");
			}
			if (isset($this->maxContentLength) && (!is_string($this->maxContentLength) ||
					strtotime($this->minContentLength) === false)) {
				throw new Exception("Maximum content length can only be string or null.");
			}
		} else {
			if (isset($this->minContentLength) && !is_int($this->minContentLength)) {
				throw new Exception("Minimum content length can only be int or null.");
			}
			if (isset($this->maxContentLength) && !is_int($this->maxContentLength)) {
				throw new Exception("Maximum content length can only be int or null.");
			}
		}
		if (isset($this->defaultType) && !($this->allowedTypes & $this->defaultType)) {
			throw new Exception("Default type must be in allowed types.");
		}
		if ($this->allowedTypes & FieldInfo::TYPE_ENUM) {
			if (!isset($this->auxiliaryInfo) || !is_array($this->auxiliaryInfo) ||
					empty($this->auxiliaryInfo)) {
				throw new Exception("Enum must declare possible values.");
			}
			if (!$this->isArray() && isset($this->defaultContent) &&
					!array_key_exists($this->defaultContent, $this->auxiliaryInfo)) {
				throw new Exception("Invalid enum default value.");
			}
		}
		if ($this->isArray()) {
			if ((isset($this->defaultType) && !is_array($this->defaultType)) ||
					(isset($this->defaultContent) && !is_array($this->defaultContent))) {
				throw new Exception("Default type and content needs to be an array for array types.");
			}
		}
	}

	public function getKey() {
		return $this->key;
	}

	public function getAllowedTypes() {
		return $this->allowedTypes;
	}

	public function getName() {
		return $this->name;
	}

	public function isArray() {
		return $this->array === true;
	}

	public function isRequired() {
		return $this->required === true;
	}

	public function isLargeContent() {
		return $this->largeContent === true;
	}

	public function getMinContentLength() {
		return $this->minContentLength;
	}

	public function getMaxContentLength() {
		return $this->maxContentLength;
	}

	public function getAuxiliaryInfo() {
		return $this->auxiliaryInfo;
	}

	public function getDefaultType() {
		return $this->defaultType;
	}

	public function getDefaultContent() {
		return $this->defaultContent;
	}

	public function generateTypeName($uniqueId = 'empty') {
		return 'typeof_' . $uniqueId . '_' . $this->getKey();
	}

	public function generateContentName($uniqueId = 'empty') {
		return 'contentof_' . $uniqueId . '_' . $this->getKey();
	}

	public function getAllowedTypesArray() {
		$type = $this->allowedTypes;
		$types = [];
		foreach (FieldInfo::getTypeStringMapping() as $key => $value) {
			if ($type & $key) {
				 $types[] = $key;
			}
		}
		return $types;
	}

	public function isMultiTypeField() {
		return count($this->getAllowedTypesArray()) > 1;
	}

	public function getDefaultTypeAndContent() {
		if (!isset($this->defaultType)) {
			return [['type' => $this->getAllowedTypesArray()[0], 'content' => null]];
		}
		else if ($this->isArray() && !isset($this->defaultContent)) {
			return [['type' => $this->defaultType[0], 'content' => null]];
		}
		else if (!$this->isArray() && !isset($this->defaultContent)) {
			return [['type' => $this->defaultType, 'content' => null]];
		}

		// if field is array
		if ($this->isArray()) {
			$defaultTypeAndContent = [];
			foreach ($this->defaultType as $key => $type) {
				$value['type'] = $type;
				$value['content'] = $this->defaultContent[$key];
				$defaultTypeAndContent[] = $value;
			}
			return $defaultTypeAndContent;
		}
		// non-array
		else {
			return [
				['type' => $this->getDefaultType(),
				'content' => $this->getDefaultContent()]
				];
		}
	}

	// --------------------------------------------------------------------------------------------
	// Normalization for generated field content
	// --------------------------------------------------------------------------------------------

	// tries to make content valid e.g. for generated content
	public function normalize($typeAndContent, $unique = false) {
		// array
		if ($this->isArray()) {
			// convert elements of array
			$newTypeAndContent = [];
			foreach ($typeAndContent as $element) {
				$normalized = $this->normalizeElement($element);
				if (isset($normalized)) {
					$newTypeAndContent[] = $normalized;
				}
			}
			// skip empty arrays
			if (count($newTypeAndContent) === 0) {
				return null;
			}
			// make array elements unique
			if ($unique === true) {
				$newTypeAndContent = Utils::arrayNestedUnique($newTypeAndContent);
			}

			return $newTypeAndContent;
		}
		// element
		return $this->normalizeElement($typeAndContent);
	}

	private function normalizeElement($typeAndContent) {
		$type = $typeAndContent['type'];
		$content = (string) $typeAndContent['content'];
		$content = mb_convert_encoding($content, 'UTF-8');
		// check for correct type
		if (!in_array($type, $this->getAllowedTypesArray(), true)) {
			return null;
		}
		// normalize string
		if ($this->isLargeContent()) {
			$content = str_replace(array("\r", "\n"), '', $content);
		}
		$content = preg_replace('/[[:space:][:cntrl:]]+/u', ' ', $content);
		// clear strings that only contain punctuation characters
		$content = preg_replace('/^[[:punct:]]+$/u', '', $content);
		$content = trim($content);

		// perform type specific normalization
		switch ($type) {
			case FieldInfo::TYPE_PLAIN: // markdown or html cannot be reduced
				$contentLength = mb_strlen($content);
				// enfore maximum length
				if (isset($this->maxContentLength) && $contentLength > $this->maxContentLength) {
					$content = mb_substr($content, 0, $this->maxContentLength - 1);
					$content = $content . '…'; // indicate substring
				}
				break;
			case FieldInfo::TYPE_TAGS:
				$content = Utils::normalizeTags($content);
				// enfore maximum length
				if (isset($this->maxContentLength)) {
					$split = explode(', ', $content);
					$content = '';
					$currentLength = 0;
					foreach ($split as $tag) {
						// normalize tag
						// clear strings that only contain punctuation characters
						$tag = preg_replace('/^[[:punct:]]+$/u', '', $tag);
						$tag = trim($tag);

						$tagLength = mb_strlen($tag);
						// skip empty tags
						if ($tagLength === 0) {
							continue;
						}
						// reconstruct content
						if ($currentLength === 0 && $tagLength <= $this->maxContentLength) {
							$content = $tag;
							$currentLength += $tagLength;
						} else if ($currentLength + 2 + $tagLength <= $this->maxContentLength) {
							$content = $content . ', ' . $tag;
							$currentLength += 2 + $tagLength;
						} else {
							break; // max length reached
						}
					}
				}
				break;
			case FieldInfo::TYPE_LOCALE:
				$locale = Locale::parseLocale($content);
				if (isset($locale)) {
					if (isset($locale['language']) && isset($locale['region'])) {
						$content = $locale['language'] . '_' . $locale['region'];
					} else if (isset($locale['language'])) {
						$content = $locale['language'];
					}
				}
				break;
			case FieldInfo::TYPE_DURATION:
				// check for a float (seconds with decimal part)
				if (is_numeric($content)) {
					$converted = (float) $content;
					$seconds = floor($converted);
					$millis = (int) (($converted - $seconds) * 1000);
					$content = Utils::normalizeDuration(0, 0, 0, 0, 0, $seconds, $millis);
				}
				break;
			case FieldInfo::TYPE_RANGE:
				// normalize e.g. "2/20"
				$split = explode('/', $content);
				if (count($split) === 2 && is_numeric($split[0]) && is_numeric($split[1])) {
					$content = ((float) $split[0]) / ((float) $split[1]);
				}
				// normalize percentages e.g. "0.2"
				else if (is_numeric($content) && isset($this->auxiliaryInfo) && $this->auxiliaryInfo >= 1) {
					$value = (float) $content;
					if ($value < 0 && isset($this->minContentLength) && isset($this->maxContentLength)) {
						$range = $this->maxContentLength - $this->minContentLength;
						$content = $this->minContentLength + $value * $range;
					}
				}
				break;
			case FieldInfo::TYPE_BOOLEAN:
				$content = strtolower($content);
				if ($content === 'false' || $content === '0' || $content === 'f') {
					$content = '';
				} else if ($content === 'true' || $content === '1' || $content === 't') {
					$content = '1';
				} else {
					return null;
				}
				break;
			case FieldInfo::TYPE_ENCRYPTED:
				return null; // encrypted content cannot be normalized
			default:
				break; // do nothing
		}

		$content = (string) $content;

		// skip empty content (except for boolean)
		if ($content === '' && $type !== FieldInfo::TYPE_BOOLEAN) {
			return null;
		}

		// perform final validation
		if ($this->isValidContentForType($type, $content) === true) {
			return $content;
		}
		return null;
	}

	// --------------------------------------------------------------------------------------------
	// Validate field for administration
	// --------------------------------------------------------------------------------------------

	private function isValidType($typeString) {
		if (!isset($typeString)) {
			return false;
		}
		$type = FieldInfo::translateStringToType($typeString);
		if ($type === false) {
			return false;
		}
		if (!in_array($type, $this->getAllowedTypesArray(), true)) {
			return false;
		}
		return true;
	}

	private function convertPostArraysToTypeAndContent($uniqueId) {
		$typeArray = Utils::getValidFieldArray($this->generateTypeName($uniqueId));
		$contentArray = Utils::getValidFieldArray($this->generateContentName($uniqueId));

		$typeAndContent = [];

		foreach ($typeArray as $key => $type) {
			// boolean content does not have a content at all
			$content = '';
			if (array_key_exists($key, $contentArray)) {
				$content = $contentArray[$key];
			}
			$newTypeAndContent = $this->convertToTypeAndContent($type, $content);
			if ($newTypeAndContent !== false) {
				$typeAndContent[] = $newTypeAndContent;
			}
		}
		return $typeAndContent;
	}

	private function convertToTypeAndContent($typeString, $content) {
		if (!$this->isValidType($typeString)) {
			return false;
		}
		$type = FieldInfo::translateStringToType($typeString);
		return ['type' => $type, 'content' => trim($content)];
	}

	public function isValidTypeAndContentInput($uniqueId) {
		$uniqueTypeName = $this->generateTypeName($uniqueId);
		$uniqueContentName = $this->generateContentName($uniqueId);
		// for arrays
		if ($this->isArray() && Utils::isValidFieldArray($uniqueTypeName)) {
			$postTypeAndContent = $this->convertPostArraysToTypeAndContent($uniqueId);
			// print an error if one of the types was invalid
			if (count($postTypeAndContent) !== count(Utils::getValidFieldArray($uniqueTypeName))) {
				return 'FIELD_INVALID_TYPE';
			}
			// validate the content
			foreach ($postTypeAndContent as $element) {
				$result = $this->isValidContentForType($element['type'], $element['content']);
				if ($result !== true) {
					return $result;
				}
			}
		}
		// for empty array
		else if ($this->isArray() && !Utils::isValidFieldArray($uniqueTypeName)) {
			return true;
		}
		// use post content for non-arrays
		else if (!$this->isArray() && Utils::isValidField($uniqueTypeName)) {
			$postTypeAndContent = $this->convertToTypeAndContent(
				Utils::getUnmodifiedStringOrEmpty($uniqueTypeName),
				Utils::getUnmodifiedStringOrEmpty($uniqueContentName));
			// check if conversion was successful
			if ($postTypeAndContent === false) {
				return 'FIELD_INVALID_TYPE';
			}
			$result = $this->isValidContentForType(
				$postTypeAndContent['type'],
				$postTypeAndContent['content']);
			if ($result !== true) {
				return $result;
			}
		}
		return true;
	}

	private function isValidContentForType($type, $trimmedContent) {
		// check value
		$length = mb_strlen($trimmedContent);
		switch ($type) {
			case FieldInfo::TYPE_PLAIN:
			case FieldInfo::TYPE_HTML:
			case FieldInfo::TYPE_MARKDOWN:
			case FieldInfo::TYPE_TAGS:
				// check required
				if ($this->required === true && $length === 0) {
					return 'FIELD_IS_REQUIRED';
				}
				// check min length
				if (isset($this->minContentLength) && $length < $this->minContentLength) {
					return 'FIELD_TOO_SHORT';
				}
				// check max length
				if (isset($this->maxContentLength) && $length > $this->maxContentLength) {
					return 'FIELD_TOO_LONG';
				}
				// check largeness
				if ($this->largeContent !== true 
						&& !(strpos($trimmedContent, "\r") === false
								&& strpos($trimmedContent, "\n") === false)) {
					return 'FIELD_CONTAINS_LINEBREAKS';
				}
				break;
			case FieldInfo::TYPE_INT:
			case FieldInfo::TYPE_PAGE:
			case FieldInfo::TYPE_IMAGE:
			case FieldInfo::TYPE_FILE:
				// check required
				if ($this->required === true && $length === 0) {
					return 'FIELD_IS_REQUIRED';
				} else if ($length > 0) {
					if (filter_var($trimmedContent, FILTER_VALIDATE_INT) === false) {
						return 'FIELD_INVALID_TYPE';
					}
					$value = (int) $trimmedContent;
					if (isset($this->minContentLength) && $value < $this->minContentLength) {
						return 'FIELD_TOO_SMALL';
					}
					if (isset($this->maxContentLength) && $value > $this->maxContentLength) {
						return 'FIELD_TOO_LARGE';
					}
				}
				break;
			case FieldInfo::TYPE_BOOLEAN:
				// check required
				if ($this->required === true && $length === 0) {
					return 'FIELD_IS_REQUIRED';
				} else if ($length > 0) {
					if ($trimmedContent !== '1') {
						return 'FIELD_INVALID_TYPE';
					}
				}
				break;
			case FieldInfo::TYPE_ENUM:
				// check required
				if ($this->required === true && $length === 0) {
					return 'FIELD_IS_REQUIRED';
				} else if ($length > 0 && !array_key_exists($trimmedContent, $this->auxiliaryInfo)) {
					return 'FIELD_INVALID_TYPE';
				}
				break;
			case FieldInfo::TYPE_DATE:
				return 'NOT_YET_IMPLEMENTED';
				break;
			case FieldInfo::TYPE_COLOR:
				return 'NOT_YET_IMPLEMENTED';
				break;
			case FieldInfo::TYPE_LINK:
				return 'NOT_YET_IMPLEMENTED';
				break;
			case FieldInfo::TYPE_ID:
				return 'NOT_YET_IMPLEMENTED';
				break;
			case FieldInfo::TYPE_EMAIL:
				return 'NOT_YET_IMPLEMENTED';
				break;
			case FieldInfo::TYPE_LOCALE:
				if ($this->required === true && $length === 0) {
					return 'FIELD_IS_REQUIRED';
				} else if ($length > 0) {
					$locales = Translator::get()->translateLocaleList(true);
					if (!array_key_exists($trimmedContent, $locales)) {
						return 'FIELD_INVALID_TYPE';
					}
				}
				break;
			case FieldInfo::TYPE_DATE_TIME:
				$converted = str_replace('T', ' ', $trimmedContent);
				// check required
				if ($this->required === true && $length === 0) {
					return 'FIELD_IS_REQUIRED';
				} else if ($length > 0) {
					if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) '.
							'(2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]$/', $converted)) {
						return 'FIELD_INVALID_TYPE';
					}
					$timestamp = strtotime($converted);
					if (isset($this->minContentLength) && $timestamp < strtotime($this->minContentLength)) {
						return 'FIELD_TOO_SMALL';
					}
					if (isset($this->maxContentLength) && $timestamp > strtotime($this->maxContentLength)) {
						return 'FIELD_TOO_LARGE';
					}
				}
				break;
			case FieldInfo::TYPE_FLOAT:
				// check required
				if ($this->required === true && $length === 0) {
					return 'FIELD_IS_REQUIRED';
				} else if ($length > 0) {
					if (filter_var($trimmedContent, FILTER_VALIDATE_FLOAT) === false) {
						return 'FIELD_INVALID_TYPE';
					}
					$value = (float) $trimmedContent;
					if (isset($this->minContentLength) && $value < $this->minContentLength) {
						return 'FIELD_TOO_SMALL';
					}
					if (isset($this->maxContentLength) && $value > $this->maxContentLength) {
						return 'FIELD_TOO_LARGE';
					}
				}
				break;
			case FieldInfo::TYPE_DURATION:
				// check required
				if ($this->required === true && $length === 0) {
					return 'FIELD_IS_REQUIRED';
				} else if ($length > 0) {
					if (!preg_match('/^([0-9]{1,10})-([0-9]{1,10})-([0-9]{1,10}) '.
							'([0-9]{1,10}):([0-9]{1,10}):([0-9]{1,10})([\.,][0-9]{1,3})?$/',
							$trimmedContent)) {
						return 'FIELD_INVALID_TYPE';
					}
				}
				break;
			case FieldInfo::TYPE_RANGE:
				// check required
				if ($this->required === true && $length === 0) {
					return 'FIELD_IS_REQUIRED';
				} else if ($length > 0) {
					if (filter_var($trimmedContent, FILTER_VALIDATE_FLOAT) === false) {
						return 'FIELD_INVALID_TYPE';
					}
					$value = (float) $trimmedContent;
					if (isset($this->minContentLength) && $value < $this->minContentLength) {
						return 'FIELD_TOO_SMALL';
					}
					if (isset($this->maxContentLength) && $value > $this->maxContentLength) {
						return 'FIELD_TOO_LARGE';
					}
					if (isset($this->auxiliaryInfo) && fmod($value, $this->auxiliaryInfo) != 0) {
						return 'FIELD_INVALID_TYPE';
					}
				}
				break;
			case FieldInfo::TYPE_ENCRYPTED:
				break;
		}
		return true;
	}

	public function getValidTypeAndContentInput($uniqueId) {
		$uniqueTypeName = $this->generateTypeName($uniqueId);
		$uniqueContentName = $this->generateContentName($uniqueId);
		// for arrays
		if ($this->isArray() && Utils::isValidFieldArray($uniqueTypeName)) {
			$postTypeAndContent = $this->convertPostArraysToTypeAndContent($uniqueId);
			// transform the content
			foreach ($postTypeAndContent as &$element) {
				$element['content'] = $this->transformContentForType($element['type'], $element['content']);
			}
			return $postTypeAndContent;
		}
		// for empty array
		else if ($this->isArray() && !Utils::isValidFieldArray($uniqueTypeName)) {
			return [];
		}
		// use post content for non-arrays
		else if (!$this->isArray() && Utils::isValidField($uniqueTypeName)) {
			$postTypeAndContent = $this->convertToTypeAndContent(
				Utils::getUnmodifiedStringOrEmpty($uniqueTypeName),
				Utils::getUnmodifiedStringOrEmpty($uniqueContentName));
			// transform the content
			$postTypeAndContent['content'] = $this->transformContentForType(
				$postTypeAndContent['type'],
				$postTypeAndContent['content']);
			return [$postTypeAndContent];
		}
	}

	private function transformContentForType($type, $trimmedContent) {
		// a boolean field uses the empty string for indicating'false'
		if ($trimmedContent === '' && $type !== FieldInfo::TYPE_BOOLEAN) {
			return '';
		}
		switch ($type) {
			case FieldInfo::TYPE_PLAIN:
			case FieldInfo::TYPE_HTML:
			case FieldInfo::TYPE_MARKDOWN:
			case FieldInfo::TYPE_LOCALE:
			case FieldInfo::TYPE_ENCRYPTED:
				return $trimmedContent;
			case FieldInfo::TYPE_TAGS:
				return Utils::normalizeTags($trimmedContent);
			case FieldInfo::TYPE_INT:
			case FieldInfo::TYPE_PAGE:
			case FieldInfo::TYPE_IMAGE:
			case FieldInfo::TYPE_FILE:
				return (int) $trimmedContent;
			case FieldInfo::TYPE_BOOLEAN:
				if ($trimmedContent === '1') {
					return true;
				}
				return false;
			case FieldInfo::TYPE_ENUM:
				break;
			case FieldInfo::TYPE_DATE:
				break;
			case FieldInfo::TYPE_COLOR:
				break;
			case FieldInfo::TYPE_LINK:
				break;
			case FieldInfo::TYPE_ID:
				break;
			case FieldInfo::TYPE_EMAIL:
				break;
			case FieldInfo::TYPE_DATE_TIME:
				return str_replace('T', ' ', $trimmedContent);
			case FieldInfo::TYPE_FLOAT:
			case FieldInfo::TYPE_RANGE:
				return (float) $trimmedContent;
			case FieldInfo::TYPE_DURATION:
				// extract parts
				$split = preg_split('/[-: ,\.]/', $trimmedContent);
				$yearPart = (int) $split[0];
				$monthPart = (int) $split[1];
				$dayPart = (int) $split[2];
				$hourPart = (int) $split[3];
				$minutePart = (int) $split[4];
				$secondPart = (int) $split[5];
				$milliPart = (isset($split[6]))? (int) $split[6] : 0;
				// normalize
				return Utils::normalizeDuration($yearPart, $monthPart, $dayPart,
					$hourPart, $minutePart, $secondPart, $milliPart);
		}
		return $trimmedContent;
	}

	// --------------------------------------------------------------------------------------------
	// Visualize field for administration
	// --------------------------------------------------------------------------------------------

	public function printFieldWithLabel($databaseTypeAndContent, $uniqueId) {
		echo '<div class="richField">';
		// print label (arrays have no for since a textfield might not be present)
		if ($this->isArray()) {
			echo '	<label>';
		}
		else {
			echo '	<label for="' . $this->generateContentName($uniqueId) . '">';
		}
		echo Translator::get()->translate($this->name);
		if ($this->required) {
			echo '*';
		}
		echo ':	</label>';

		// print content
		$types = $this->getAllowedTypesArray();

		// find source of current content
		$currentTypeAndContent = null;
		// use post type and content for arrays
		if ($this->isArray() && Utils::isValidFieldArray($this->generateTypeName($uniqueId))) {
			$postTypeAndContent = $this->convertPostArraysToTypeAndContent($uniqueId);
			$currentTypeAndContent = $postTypeAndContent;
		}
		// use post content for non-arrays
		else if (!$this->isArray() && Utils::isValidField($this->generateTypeName($uniqueId))) {
			$postTypeAndContent = $this->convertToTypeAndContent(
				Utils::getUnmodifiedStringOrEmpty($this->generateTypeName($uniqueId)),
				Utils::getUnmodifiedStringOrEmpty($this->generateContentName($uniqueId)));
			if ($postTypeAndContent !== false) {
				$currentTypeAndContent = [$postTypeAndContent];
			}
		}
		// use database content
		else if (isset($databaseTypeAndContent) && count($databaseTypeAndContent) > 0) {
			$currentTypeAndContent = $databaseTypeAndContent;
		}
		// use default content as fallback
		if ($currentTypeAndContent === null
			// fill required fields with default content if they have empty string
			|| ($this->required && !$this->isArray()
					&& $currentTypeAndContent[0]['content'] === '')
			|| ($this->required && $this->isArray() 
					&& count($currentTypeAndContent) === 1 && $currentTypeAndContent[0]['content'] === '')) {
			$currentTypeAndContent = $this->getDefaultTypeAndContent();
		}

		if ($this->isArray()) {
			echo '<div class="array">';
		}

		$this->printFieldTypeAndContent($types, $currentTypeAndContent, false, $uniqueId);

		// add an 'add' button
		if ($this->isArray()) {
			echo '	<div class="arrayOptions">';
			echo '		<button class="add">';
			echo Translator::get()->translate('ADD');
			echo '</button>';
			// store a template for a new array element
			echo '		<div class="template hidden">';
			$this->printFieldTypeAndContent($types, $this->getDefaultTypeAndContent(), true, $uniqueId);
			echo '		</div>'; // class="template hidden"
			echo '	</div>'; // class="arrayOptions"
			echo '</div>'; // class="array"
		}

		echo '</div>'; // class="richField"
	}

	private function printFieldTypeAndContent($types, $typeAndContent, $template, $uniqueId) {
		// print special HTML if only one type is supported
		if (count($types) === 1) {
			// do not print an array element if content is empty except for a template
			if (!($this->isArray()
						&& count($typeAndContent) === 1
						&& $typeAndContent[0]['content'] === null)
					|| $template) {
				if ($this->isArray()) {
					echo '<div class="arrayElement">';
				}
				$this->printPostField(
					$types[0],
					$typeAndContent[0]['content'],
					$template,
					$uniqueId);
				echo '<span class="hint">';
				echo Translator::get()->translate(FieldInfo::translateTypeToString($types[0]));
				echo '</span>';
				if ($this->isArray()) {
					echo '	<div class="arrayElementOptions">';
					echo '		<button class="remove">';
					echo Translator::get()->translate('REMOVE');
					echo '</button>';
					echo '	</div>'; // class="arrayElementOptions"
					echo '</div>'; // class="arrayElement"
				}
			}
		}
		else {
			// for each content value
			foreach ($typeAndContent as $element) {
				// do not print an array element if content is empty except for a template
				if ($this->isArray() && $element['content'] === null && !$template) {
					continue;
				}

				// split up array elements with an additional div
				if ($this->isArray()) {
					echo '<div class="arrayElement">';
				}

				// print type selection
				echo '	<div class="tabBox">';
				// print types
				echo '		<ul class="tabs">';
				foreach ($types as $type) {
					if ($type === $element['type']) {
						echo '		<li class="current">';
					}
					else {
						echo '		<li>';
					}
					echo '				<a>';
					echo Translator::get()->translate(FieldInfo::translateTypeToString($type));
					echo '				</a>';
					echo '			</li>';
				}
				echo '		</ul>';

				// print content for types
				echo '		<div class="tabContent">';
				foreach ($types as $key => $type) {
					if ($type === $element['type']) {
						echo '			<div class="tab">';
						$this->printPostField(
							$type,
							$element['content'],
							$template,
							$uniqueId);
					}
					else {
						echo '			<div class="tab hidden">';
						$this->printPostField(
							$type,
							null,
							true,
							$uniqueId);
					}
					echo '				</div>'; // class="tab hidden" or class="tab"
				}
				echo '		</div>'; // class="tabContent"
				echo '	</div>'; // class="tabBox"

				if ($this->isArray()) {
					echo '	<div class="arrayElementOptions">';
					echo '		<button class="remove">';
					echo Translator::get()->translate('REMOVE');
					echo '</button>';
					echo '	</div>'; // class="arrayElementOptions"
					echo '</div>'; // class="arrayElement"
				}
			}
		}
	}

	private function printPostField($type, $value, $disabled, $uniqueId) {
		UiUtils::printHiddenTypeInput(
			$this->generateTypeName($uniqueId) . ($this->isArray() ? '[]' : ''),
			$type,
			$disabled);
		switch ($type) {
			case FieldInfo::TYPE_PLAIN:
			case FieldInfo::TYPE_HTML:
			case FieldInfo::TYPE_MARKDOWN:
			case FieldInfo::TYPE_TAGS:
				UiUtils::printTextInput(
					$this,
					$type,
					$value,
					$disabled,
					$uniqueId);
				break;
			case FieldInfo::TYPE_IMAGE:
				break;
			case FieldInfo::TYPE_FILE:
				break;
			case FieldInfo::TYPE_INT:
				UiUtils::printIntInput(
					$this,
					$value,
					$disabled,
					$uniqueId);
				break;
			case FieldInfo::TYPE_BOOLEAN:
				UiUtils::printCheckbox(
					$this,
					$value,
					$disabled,
					$uniqueId);
				break;
			case FieldInfo::TYPE_ENUM:
				UiUtils::printEnumSelection(
					$this,
					$value,
					$disabled,
					$uniqueId);
				break;
			case FieldInfo::TYPE_DATE:
				break;
			case FieldInfo::TYPE_COLOR:
				break;
			case FieldInfo::TYPE_LINK:
				break;
			case FieldInfo::TYPE_PAGE:
				UiUtils::printPageSelection(
					$this,
					$value,
					$uniqueId);
				break;
			case FieldInfo::TYPE_ID:
				break;
			case FieldInfo::TYPE_EMAIL:
				break;
			case FieldInfo::TYPE_LOCALE:
				UiUtils::printLocaleSelection(
					$this,
					$value,
					$disabled,
					$uniqueId);
				break;
			case FieldInfo::TYPE_DATE_TIME:
				UiUtils::printDateTimeInput(
					$this,
					$value,
					$disabled,
					$uniqueId);
				break;
			case FieldInfo::TYPE_FLOAT:
				UiUtils::printFloatInput(
					$this,
					$value,
					$disabled,
					$uniqueId);
				break;
			case FieldInfo::TYPE_DURATION:
				UiUtils::printDurationInput(
					$this,
					$value,
					$disabled,
					$uniqueId);
				break;
			case FieldInfo::TYPE_RANGE:
				UiUtils::printRangeInput(
					$this,
					$value,
					$disabled,
					$uniqueId);
				break;
			case FieldInfo::TYPE_ENCRYPTED:
				UiUtils::printEncryptedInput(
					$this,
					$value,
					$uniqueId);
				break;
		}
	}

	// --------------------------------------------------------------------------------------------
	// Compile field
	// --------------------------------------------------------------------------------------------

	// --------------------------------------------------------------------------------------------
	// Helper methods
	// --------------------------------------------------------------------------------------------

	public static function getTypeStringMapping() {
		return [
			FieldInfo::TYPE_PLAIN =>'TYPE_PLAIN',
			FieldInfo::TYPE_HTML =>'TYPE_HTML',
			FieldInfo::TYPE_MARKDOWN =>'TYPE_MARKDOWN',
			FieldInfo::TYPE_IMAGE =>'TYPE_IMAGE',
			FieldInfo::TYPE_FILE =>'TYPE_FILE',
			FieldInfo::TYPE_TAGS =>'TYPE_TAGS',
			FieldInfo::TYPE_INT =>'TYPE_INT',
			FieldInfo::TYPE_BOOLEAN =>'TYPE_BOOLEAN',
			FieldInfo::TYPE_ENUM =>'TYPE_ENUM',
			FieldInfo::TYPE_DATE =>'TYPE_DATE',
			FieldInfo::TYPE_COLOR =>'TYPE_COLOR',
			FieldInfo::TYPE_LINK =>'TYPE_LINK',
			FieldInfo::TYPE_PAGE =>'TYPE_PAGE',
			FieldInfo::TYPE_ID =>'TYPE_ID',
			FieldInfo::TYPE_EMAIL =>'TYPE_EMAIL',
			FieldInfo::TYPE_LOCALE =>'TYPE_LOCALE',
			FieldInfo::TYPE_DATE_TIME =>'TYPE_DATE_TIME',
			FieldInfo::TYPE_FLOAT =>'TYPE_FLOAT',
			FieldInfo::TYPE_DURATION =>'TYPE_DURATION',
			FieldInfo::TYPE_RANGE =>'TYPE_RANGE',
			FieldInfo::TYPE_ENCRYPTED =>'TYPE_ENCRYPTED'
		];
	}

	public static function translateStringToType($type) {
		return array_search($type, FieldInfo::getTypeStringMapping());
	}

	public static function translateTypeToString($type) {
		return FieldInfo::getTypeStringMapping()[$type];
	}
}

?>