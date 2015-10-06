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
	const TYPE_LOCALE = 32768;

	private $key;
	private $allowedTypes;
	private $name;
	private $array;
	private $required;
	private $largeContent;
	private $minContentLength;
	private $maxContentLength;
	private $additionalNames; // e.g. for type boolean = checkbox string, for type enum = values
	private $defaultType;
	private $defaultContent;

	private $typePostFieldName;
	private $contentPostFieldName;

	public function __construct(
		$key,
		$allowedTypes,
		$name,
		$array = null,
		$required = null,
		$largeContent = null,
		$minContentLength = null,
		$maxContentLength = null,
		$additionalNames = null,
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
		$this->additionalNames = $additionalNames;
		$this->defaultType = $defaultType;
		$this->defaultContent = $defaultContent;

		// validate parameters
		// TODO

		$this->typePostFieldName = 'typeof_' . $this->key;
		$this->contentPostFieldName = 'contentof_' . $this->key;
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
		return $this->required;
	}

	public function isLargeContent() {
		return $this->largeContent;
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

	public function getDefaultType() {
		return $this->defaultType;
	}

	public function getDefaultContent() {
		return $this->defaultContent;
	}

	public function getTypePostFieldName() {
		return $this->typePostFieldName;
	}

	public function getContentPostFieldName() {
		return $this->contentPostFieldName;
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

	private function convertPostArraysToTypeAndContent() {
		$typeArray = Utils::getValidFieldArray($this->typePostFieldName);
		$contentArray = Utils::getValidFieldArray($this->contentPostFieldName);

		$typeAndContent = [];

		foreach ($typeArray as $key => $type) {
			// each type must have a corresponding content
			if (array_key_exists($key, $contentArray)) {
				$newTypeAndContent = $this->convertToTypeAndContent($type, $contentArray[$key]);
				if ($newTypeAndContent !== false) {
					$typeAndContent[] = $newTypeAndContent;
				}
			}
		}
		return $typeAndContent;
	}

	private function convertToTypeAndContent($typeString, $content) {
		if (!$this->isValidType($typeString)) {
			return false;
		}
		$type = FieldInfo::translateStringToType($typeString);
		return ['type' => $type, 'content' => trim($value)];
	}





















// TODO
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
				if ($this->largeContent !== true 
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

// TODO
	public function isValidContentInput() {
		$typeField = $this->typePostFieldName;
		$valueField = $this->contentPostFieldName;

		// check type
		$typeString = Utils::getUnmodifiedStringOrEmpty($typeField); // TODO array support
		if ($this->isValidType($typeString) === false) {
			return 'FIELD_INVALID_TYPE';
		}
		$type = FieldInfo::translateStringToType($typeString);

		// for field arrays
		if ($this->isArray() && Utils::isValidFieldArray($valueField)) {
			foreach (Utils::getValidFieldArray($valueField) as $value) {
				$result = $this->isValidContentInputValue($type, $value);
				if ($result !== true) {
					return $result;
				}
			}
		}
		// array missing
		else if ($this->isArray() && !Utils::isValidFieldArray($valueField)) {
			return 'FIELD_INVALID_TYPE';
		}
		// non array
		else {
			return $this->isValidContentInputValue($type, Utils::getUnmodifiedStringOrEmpty($valueField));
		}
	}

// TODO
	private function getValidContentInputValue($type, $inputValue) {
		$value = '';
		if (isset($inputValue) && is_string($inputValue)) {
			$value = $inputValue;
		}
		// convert value
		$trimmed = trim($value);

		$content = [];
		$content['type'] = $type;
		$content['content'] = '';

		switch ($type) {
			case FieldInfo::TYPE_PLAIN:
			case FieldInfo::TYPE_HTML:
			case FieldInfo::TYPE_MARKDOWN:
				$content['content'] = $trimmed;
				break;
			case FieldInfo::TYPE_TAGS:
				break;
			case FieldInfo::TYPE_INT:
			case FieldInfo::TYPE_PAGE:
			case FieldInfo::TYPE_IMAGE:
			case FieldInfo::TYPE_FILE:
				$content['content'] = (int) $trimmed;
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
			case FieldInfo::TYPE_ID:
				break;
			case FieldInfo::TYPE_EMAIL:
				break;
			case FieldInfo::TYPE_LOCALE:
				break;
		}
		return $content;
	}

// TODO
	public function getValidContentInput() {
		$typeField = 'typeof_' . $this->key;
		$valueField = 'valueof_' . $this->key;
		$typeString = Utils::getUnmodifiedStringOrEmpty($typeField);
		$type = FieldInfo::translateStringToType($typeString); // TODO array support

		$content = [];

		// for field arrays
		if ($this->isArray()) {
			foreach (Utils::getValidFieldArray($valueField) as $value) {
				$content[] = $this->getValidContentInputValue($type, $value);
			}
			return $content;
		}
		// non array
		return [$this->getValidContentInputValue($type, Utils::getUnmodifiedStringOrEmpty($valueField))];
	}

	// --------------------------------------------------------------------------------------------
	// Visualize field for administration
	// --------------------------------------------------------------------------------------------

	public function printFieldWithLabel($moduleDefinition, $databaseTypeAndContent) {
		echo '<div class="richField">';
		// print label (arrays have no for since a textfield might not be present)
		if ($this->isArray()) {
			echo '	<label>';
		}
		else {
			echo '	<label for="' . $this->contentPostFieldName . '">';
		}
		$moduleDefinition->text($this->name);
		if ($this->required) {
			echo '*';
		}
		echo ':	</label>';

		// print content
		$types = $this->getAllowedTypesArray();

		// find source of current content
		$currentTypeAndContent = null;
		// use post type and content for arrays
		if ($this->isArray()
				&& Utils::isValidFieldArray($this->typePostFieldName)
				&& Utils::isValidFieldArray($this->contentPostFieldName)) {
			$postTypeAndContent = $this->convertPostArraysToTypeAndContent();
			$currentTypeAndContent = $postContent;
		}
		// use post content for non-arrays
		else if (!$this->isArray()
				&& Utils::isValidField($this->typePostFieldName)
				&& Utils::isValidField($this->contentPostFieldName)) {
			$postTypeAndContent = $this->convertToTypeAndContent(
				Utils::getUnmodifiedStringOrEmpty($typeField),
				Utils::getUnmodifiedStringOrEmpty($valueField));
			if ($postTypeAndContent !== false) {
				$currentTypeAndContent = [$postTypeAndContent];
			}
		}
		// use database content
		else if (isset($databaseTypeAndContent) && count($databaseTypeAndContent) > 0) {
			$currentTypeAndContent = $databaseTypeAndContent;
		}
		// use default content as fallback
		if ($currentTypeAndContent === null) {
			$currentTypeAndContent = $this->getDefaultTypeAndContent();
		}

		if ($this->isArray()) {
			echo '<div class="array">';
		}

		$this->printFieldTypeAndContent($types, $currentTypeAndContent, $moduleDefinition);

		// add an 'add' button
		if ($this->isArray()) {
			echo '	<div class="arrayOptions">';
			echo '		<button class="add">ADD</button>';
			// store a template for a new array element
			echo '		<div class="template hidden">';
			$this->printFieldTypeAndContent($types, $this->getDefaultTypeAndContent(), $moduleDefinition);
			echo '		</div>'; // class="template hidden"
			echo '	</div>'; // class="arrayOptions"
			echo '</div>'; // class="array"
		}

		echo '</div>'; // class="richField"
	}

	private function printFieldTypeAndContent($types, $typeAndContent, $moduleDefinition) {
		if (count($types) === 1) {
			if ($this->isArray()) {
				echo '<div class="arrayElement">';
			}
			$this->printField(
				$types[0],
				$typeAndContent[0]['content'],
				false,
				true);
			echo '<span class="hint">';
			$moduleDefinition->text(FieldInfo::translateTypeToString($types[0]));
			echo '</span>';
			if ($this->isArray()) {
				echo '	<div class="arrayElementOptions">';
				echo '		<button class="remove">REMOVE</button>';
				echo '	</div>'; // class="arrayElementOptions"
				echo '</div>'; // class="arrayElement"
			}
		}
		else {
			// for each content value
			foreach ($typeAndContent as $element) {
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
					$moduleDefinition->text(FieldInfo::translateTypeToString($type));
					echo '				</a>';
					echo '			</li>';
				}
				echo '		</ul>';

				// print content for types
				echo '		<div class="tabContent">';
				foreach ($types as $key => $type) {
					if ($type === $element['type']) {
						echo '			<div class="tab">';
						$this->printField(
							$type,
							$element['content'],
							false,
							$this->isArray());
					}
					else {
						echo '			<div class="tab hidden">';
						$this->printField(
							$type,
							null,
							true,
							$this->isArray());
					}
					echo '				</div>'; // class="tab hidden" or class="tab"
				}
				echo '		</div>'; // class="tabContent"
				echo '	</div>'; // class="tabBox"

				if ($this->isArray()) {
					echo '	<div class="arrayElementOptions">';
					echo '		<button class="remove">REMOVE</button>';
					echo '	</div>'; // class="arrayElementOptions"
					echo '</div>'; // class="arrayElement"
				}
			}
		}
	}

	private function printField($type, $value, $disabled) {
		UiUtils::printHiddenTypeInput($this->typePostFieldName . ($this->isArray() ? '[]' : ''),
			$type, $disabled);
		switch ($type) {
			case FieldInfo::TYPE_PLAIN:
			case FieldInfo::TYPE_HTML:
			case FieldInfo::TYPE_MARKDOWN:
				UiUtils::printTextInput(
					$this,
					$type,
					$value,
					$disabled);
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