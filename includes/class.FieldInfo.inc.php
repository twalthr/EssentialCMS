<?php

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
	const TYPE_LOCALE = 32768;

	private $key;
	private $allowedContentTypes;
	private $name;
	private $array;
	private $required;
	private $largeContentField;
	private $minContentLength;
	private $maxContentLength;
	private $additionalNames; // e.g. for type boolean = checkbox string, for type enum = values
	private $defaultContentType;
	private $defaultContentValue;

	public function __construct(
		$key,
		$allowedContentTypes,
		$name,
		$array = false,
		$required = false,
		$largeContentField = false,
		$minContentLength = null,
		$maxContentLength = null,
		$additionalNames = null,
		$defaultContentType = null,
		$defaultContentValue = null) {
		
		$this->key = $key;
		$this->allowedContentTypes = $allowedContentTypes;
		$this->name = $name;
		$this->array = $array;
		$this->required = $required;
		$this->largeContentField = $largeContentField;
		$this->minContentLength = $minContentLength;
		$this->maxContentLength = $maxContentLength;
		$this->additionalNames = $additionalNames;
		$this->defaultContentType = $defaultContentType;
		$this->defaultContentValue = $defaultContentValue;
	}

	public function getKey() {
		return $this->key;
	}

	public function getAllowedContentTypes() {
		return $this->allowedContentTypes;
	}

	public function getName() {
		return $this->name;
	}

	public function isArray() {
		return $this->array;
	}

	public function isRequired() {
		return $this->required;
	}

	public function isLargeContentField() {
		return $this->largeContentField;
	}

	public function getMinContentLength() {
		return $this->minContentLength;
	}

	public function getMaxContentLength() {
		return $this->maxContentLength;
	}

	public function getAdditionalNames() {
		return $this->additionalNames;
	}

	public function getDefaultContentType() {
		return $this->defaultContentType;
	}

	public function getDefaultContentValue() {
		return $this->defaultContentValue;
	}

	public function getAllowedContentTypesArray() {
		$type = $this->allowedContentTypes;
		$types = [];
		foreach (FieldInfo::getTypeStringMapping() as $key => $value) {
			if ($type & $key) {
				 $types[] = $key;
			}
		}
		return $types;
	}

	public function isMultiTypeContent() {
		return count($this->getAllowedContentTypesArray()) > 1;
	}

	// --------------------------------------------------------------------------------------------
	// Validate field for administration
	// --------------------------------------------------------------------------------------------

	private function isValidContentInputValue($type, $inputValue) {
		$value = '';
		if (isset($inputValue) && is_string($inputValue)) {
			$value = $inputValue;
		}
		// check value
		$trimmed = trim($value);
		$length = strlen($trimmed);
		switch ($type) {
			case FieldInfo::TYPE_PLAIN:
			case FieldInfo::TYPE_HTML:
			case FieldInfo::TYPE_MARKDOWN:
				// check required
				if ($this->required === true && $length === 0) {
					return 'FIELD_IS_REQUIRED';
				}
				// check min length
				if ($this->minContentLength !== null && $length < $this->minContentLength) {
					return 'FIELD_TOO_SHORT';
				}
				// check max length
				if ($this->maxContentLength !== null && $length > $this->maxContentLength) {
					return 'FIELD_TOO_LONG';
				}
				// check largeness
				if ($this->largeContentField !== true 
					&& !(strpos($trimmed, "\r") === false && strpos($trimmed, "\n") === false)) {
					return 'FIELD_CONTAINS_LINEBREAKS';
				}
				break;
			case FieldInfo::TYPE_TAGS:
				return 'NOT_YET_IMPLEMENTED';
				break;
			case FieldInfo::TYPE_INT:
			case FieldInfo::TYPE_PAGE:
			case FieldInfo::TYPE_IMAGE:
			case FieldInfo::TYPE_FILE:
				// check for valid int
				$validInt = true;
				if (filter_var($trimmed, FILTER_VALIDATE_INT) === false) {
					$validInt = false;
				}
				// check required
				if ($this->required === true && $validInt === false) {
					return 'FIELD_IS_REQUIRED';
				}
				else if ($validInt === false && $length > 0) {
					return 'FIELD_INVALID_TYPE';
				}
				// check min length
				if ($this->minContentLength !== null && ((int) $trimmed) < $this->minContentLength) {
					return 'FIELD_TOO_SMALL';
				}
				// check max length
				if ($this->maxContentLength !== null && ((int) $trimmed) > $this->maxContentLength) {
					return 'FIELD_TOO_LARGE';
				}
				break;
			case FieldInfo::TYPE_BOOLEAN:
				return 'NOT_YET_IMPLEMENTED';
				break;
			case FieldInfo::TYPE_ENUM:
				return 'NOT_YET_IMPLEMENTED';
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
				return 'NOT_YET_IMPLEMENTED';
				break;
			}
			return true;
	}

	public function isValidContentInput($typeField, $valueField) {
		// check type
		$typeString = Utils::getUnmodifiedStringOrEmpty($typeField);
		$type = FieldInfo::translateStringToType($typeString);
		if ($type === false) {
			return 'FIELD_INVALID_TYPE';
		}
		if (!in_array($type, $this->getAllowedContentTypesArray(), true)) {
			return 'FIELD_INVALID_TYPE';
		}

		// for field arrays
		if ($this->array === true && Utils::isValidFieldArray()) {
			foreach (Utils::getValidFieldArray($valueField) as $value) {
				$result = $this->isValidContentInputValue($type, $value);
				if ($result !== true) {
					return $result;
				}
			}
		}
		// array missing
		else if ($this->array === true && !Utils::isValidFieldArray()) {
			return 'FIELD_INVALID_TYPE';
		}
		// non array
		else {
			return $this->isValidContentInputValue($type, Utils::getUnmodifiedStringOrEmpty($valueField));
		}
	}

	// --------------------------------------------------------------------------------------------
	// Visualize field for administration
	// --------------------------------------------------------------------------------------------

	public function printField($type, $typePostField, $postField, $value, $disabled) {
		UiUtils::printHiddenTypeInput($typePostField, $type, $disabled);
		switch ($type) {
			case FieldInfo::TYPE_PLAIN:
			case FieldInfo::TYPE_HTML:
			case FieldInfo::TYPE_MARKDOWN:
				UiUtils::printTextInput(
					$type,
					$postField,
					$value,
					$disabled,
					$this);
				break;
			case FieldInfo::TYPE_IMAGE:
				break;
			case FieldInfo::TYPE_FILE:
				break;
			case FieldInfo::TYPE_TAGS:
				break;
			case FieldInfo::TYPE_INT:
				break;
			case FieldInfo::TYPE_BOOLEAN:
				break;
			case FieldInfo::TYPE_ENUM:
				break;
			case FieldInfo::TYPE_DATE:
				break;
			case FieldInfo::TYPE_COLOR:
				break;
			case FieldInfo::TYPE_LINK:
				break;
			case FieldInfo::TYPE_PAGE:
				break;
			case FieldInfo::TYPE_ID:
				break;
			case FieldInfo::TYPE_EMAIL:
				break;
			case FieldInfo::TYPE_LOCALE:
				break;
		}
	}

	public function printFieldWithLabel($moduleDefinition, $valueType, $valueContent) {
		echo '<div class="richField">';
		echo '	<label for="' . 'valueof_' . $this->key . '">';
		echo $moduleDefinition->text($this->name);
		if ($this->required) {
			echo '*';
		}
		echo '	</label>';

		$types = $this->getAllowedContentTypesArray();
		$currentType = 0;
		// type is already set
		if (isset($valueType)) {
			$currentType = array_search($valueType, $types, true);
		}
		// default is set
		else if (isset($this->defaultContentType)) {
			$index = array_search($this->defaultContentType, $types, true);
			$currentType = ($index !== false) ? $index : 0;
		}
		// print type selection
		echo '	<div class="tabBox">';
		// print types
		echo '		<ul class="tabs">';
		foreach ($types as $i => $type) {
			if ($i === $currentType) {
				echo '		<li class="current">';
			}
			else {
				echo '		<li>';
			}
			echo '				<a>';
			echo $moduleDefinition->text(FieldInfo::translateTypeToString($type));
			echo '				</a>';
			echo '			</li>';
		}
		echo '		</ul>';
		// print values for types
		echo '		<div class="tabContent">';
		foreach ($types as $i => $type) {
			if ($i === $currentType) {
				echo '			<div class="tab">';
				$currentValue = null;
				// value is already set
				if (isset($valueContent)) {
					$currentValue = $valueContent;
				}
				// default is set
				else if (isset($this->defaultContentValue)) {
					$currentValue = $this->defaultContentValue;
				}
				$this->printField($type, 'typeof_' . $this->key, 'valueof_' . $this->key, $currentValue, false);
			}
			else {
				echo '			<div class="tab hidden">';
				$this->printField($type, 'typeof_' . $this->key, 'valueof_' . $this->key, null, true);
			}
			echo '				</div>';
		}
		echo '		</div>';
		echo '	</div>';
		echo '</div>';
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
			FieldInfo::TYPE_LOCALE =>'TYPE_LOCALE'
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