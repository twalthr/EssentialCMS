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
	const TYPE_LOCALE = 32768; // e.g. de-DE, de, deu, ger
	const TYPE_DATE_TIME = 65536;
	const TYPE_FLOAT = 131072;
	const TYPE_DURATION = 262144; // in seconds with microseconds (double)

	private $key;
	private $allowedTypes;
	private $name;
	private $array;
	private $required;
	private $largeContent;
	private $minContentLength; // multibyte character length or maximum value
	private $maxContentLength; // multibyte character length or minimum value
	private $additionalNames; // e.g. for type boolean = checkbox string, for type enum = values
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
			array_key_exists('values', $array) ? $array['values'] : null,
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
			throw new Exception("LargeContent can only be boolean or null.");
		}
		if (isset($this->minContentLength) && !is_int($this->minContentLength)) {
			throw new Exception("MinContentLength can only be int or null.");
		}
		if (isset($this->maxContentLength) && !is_int($this->maxContentLength)) {
			throw new Exception("MaxContentLength can only be int or null.");
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

	public function getAdditionalNames() {
		return $this->additionalNames;
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
		return $this->normalizeElement($element);
	}

	private function normalizeElement($typeAndContent) {
		$type = $typeAndContent['type'];
		$content = (string) $typeAndContent['content'];
		// check for correct type
		if (!in_array($type, $this->getAllowedTypesArray(), true)) {
			return null;
		}
		// normalize string
		if ($this->isLargeContent()) {
			$content = str_replace(array("\r", "\n"), '', $buffer);
		}
		$content = preg_replace('/[[:space:][:cntrl:]]+/', ' ', $content);
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
						$tagLength = mb_strlen($tag);
						if ($currentLength + 2 + $tagLength > $this->maxContentLength) {
							break;
						}
						$content = $content . ', ' . $tag;
						$currentLength += 2 + $tagLength;
					}
				}
				break;
			default:
				break; // do nothing
		}

		// skip empty content
		if ($content === '') {
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
		return ['type' => $type, 'content' => trim($content)];
	}

	public function isValidTypeAndContentInput($uniqueId) {
		$uniqueTypeName = $this->generateTypeName($uniqueId);
		$uniqueContentName = $this->generateContentName($uniqueId);
		// for arrays
		if ($this->isArray()
				&& Utils::isValidFieldArray($uniqueTypeName)
				&& Utils::isValidFieldArray($uniqueContentName)) {
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
		else if ($this->isArray()
				&& !Utils::isValidFieldArray($uniqueTypeName)
				&& !Utils::isValidFieldArray($uniqueContentName)) {
			return true;
		}
		// use post content for non-arrays
		else if (!$this->isArray()
				&& Utils::isValidField($uniqueTypeName)
				&& Utils::isValidField($uniqueContentName)) {
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
						&& !(strpos($trimmedContent, "\r") === false
								&& strpos($trimmedContent, "\n") === false)) {
					return 'FIELD_CONTAINS_LINEBREAKS';
				}
				break;
			case FieldInfo::TYPE_TAGS:
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
				// TODO more validation
				break;
			case FieldInfo::TYPE_INT:
			case FieldInfo::TYPE_PAGE:
			case FieldInfo::TYPE_IMAGE:
			case FieldInfo::TYPE_FILE:
				// check for valid int
				$validInt = true;
				if (filter_var($trimmedContent, FILTER_VALIDATE_INT) === false) {
					$validInt = false;
				}
				if ($validInt === false && $length > 0) {
					return 'FIELD_INVALID_TYPE';
				}
				// check required
				if ($this->required === true && $validInt === false) {
					return 'FIELD_IS_REQUIRED';
				}
				// check min length
				if ($this->minContentLength !== null && ((int) $trimmedContent) < $this->minContentLength) {
					return 'FIELD_TOO_SMALL';
				}
				// check max length
				if ($this->maxContentLength !== null && ((int) $trimmedContent) > $this->maxContentLength) {
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
			case FieldInfo::TYPE_DATE_TIME:
				return 'NOT_YET_IMPLEMENTED';
				break;
			case FieldInfo::TYPE_FLOAT:
				return 'NOT_YET_IMPLEMENTED';
				break;
			case FieldInfo::TYPE_DURATION:
				return 'NOT_YET_IMPLEMENTED'; // e.g. never negative
				break;
		}
		return true;
	}

	public function getValidTypeAndContentInput($uniqueId) {
		$uniqueTypeName = $this->generateTypeName($uniqueId);
		$uniqueContentName = $this->generateContentName($uniqueId);
		// for arrays
		if ($this->isArray()
				&& Utils::isValidFieldArray($uniqueTypeName)
				&& Utils::isValidFieldArray($uniqueContentName)) {
			$postTypeAndContent = $this->convertPostArraysToTypeAndContent($uniqueId);
			// transform the content
			foreach ($postTypeAndContent as &$element) {
				$element['content'] = $this->transformContentForType($element['type'], $element['content']);
			}
			return $postTypeAndContent;
		}
		// for empty array
		else if ($this->isArray()
				&& !Utils::isValidFieldArray($uniqueTypeName)
				&& !Utils::isValidFieldArray($uniqueContentName)) {
			return [];
		}
		// use post content for non-arrays
		else if (!$this->isArray()
				&& Utils::isValidField($uniqueTypeName)
				&& Utils::isValidField($uniqueContentName)) {
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
		if ($trimmedContent === '') {
			return '';
		}
		switch ($type) {
			case FieldInfo::TYPE_PLAIN:
			case FieldInfo::TYPE_HTML:
			case FieldInfo::TYPE_MARKDOWN:
				return $trimmedContent;
			case FieldInfo::TYPE_TAGS:
				break;
			case FieldInfo::TYPE_INT:
			case FieldInfo::TYPE_PAGE:
			case FieldInfo::TYPE_IMAGE:
			case FieldInfo::TYPE_FILE:
				return (int) $trimmedContent;
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
			case FieldInfo::TYPE_DATE_TIME:
				break;
			case FieldInfo::TYPE_FLOAT:
				break;
			case FieldInfo::TYPE_DURATION:
				break;
		}
		return $trimmedContent;
	}

	// --------------------------------------------------------------------------------------------
	// Visualize field for administration
	// --------------------------------------------------------------------------------------------

	public function printFieldWithLabel($moduleDefinition, $databaseTypeAndContent, $uniqueId) {
		echo '<div class="richField">';
		// print label (arrays have no for since a textfield might not be present)
		if ($this->isArray()) {
			echo '	<label>';
		}
		else {
			echo '	<label for="' . $this->generateContentName($uniqueId) . '">';
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
				&& Utils::isValidFieldArray($this->generateTypeName($uniqueId))
				&& Utils::isValidFieldArray($this->generateContentName($uniqueId))) {
			$postTypeAndContent = $this->convertPostArraysToTypeAndContent($uniqueId);
			$currentTypeAndContent = $postTypeAndContent;
		}
		// use post content for non-arrays
		else if (!$this->isArray()
				&& Utils::isValidField($this->generateTypeName($uniqueId))
				&& Utils::isValidField($this->generateContentName($uniqueId))) {
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

		$this->printFieldTypeAndContent($types, $currentTypeAndContent, $moduleDefinition, false, $uniqueId);

		// add an 'add' button
		if ($this->isArray()) {
			echo '	<div class="arrayOptions">';
			echo '		<button class="add">';
			$moduleDefinition->text('ADD');
			echo '</button>';
			// store a template for a new array element
			echo '		<div class="template hidden">';
			$this->printFieldTypeAndContent($types, $this->getDefaultTypeAndContent(),
				$moduleDefinition, true, $uniqueId);
			echo '		</div>'; // class="template hidden"
			echo '	</div>'; // class="arrayOptions"
			echo '</div>'; // class="array"
		}

		echo '</div>'; // class="richField"
	}

	private function printFieldTypeAndContent($types, $typeAndContent, $moduleDefinition, $template,
			$uniqueId) {
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
					$moduleDefinition,
					$types[0],
					$typeAndContent[0]['content'],
					$template,
					$uniqueId);
				echo '<span class="hint">';
				$moduleDefinition->text(FieldInfo::translateTypeToString($types[0]));
				echo '</span>';
				if ($this->isArray()) {
					echo '	<div class="arrayElementOptions">';
					echo '		<button class="remove">';
					$moduleDefinition->text('REMOVE');
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
						$this->printPostField(
							$moduleDefinition,
							$type,
							$element['content'],
							$template,
							$uniqueId);
					}
					else {
						echo '			<div class="tab hidden">';
						$this->printPostField(
							$moduleDefinition,
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
					$moduleDefinition->text('REMOVE');
					echo '</button>';
					echo '	</div>'; // class="arrayElementOptions"
					echo '</div>'; // class="arrayElement"
				}
			}
		}
	}

	private function printPostField($moduleDefinition, $type, $value, $disabled, $uniqueId) {
		UiUtils::printHiddenTypeInput($this->generateTypeName($uniqueId) . ($this->isArray() ? '[]' : ''),
			$type, $disabled);
		switch ($type) {
			case FieldInfo::TYPE_PLAIN:
			case FieldInfo::TYPE_HTML:
			case FieldInfo::TYPE_MARKDOWN:
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
				UiUtils::printPageSelection(
					$moduleDefinition,
					$this,
					$value,
					$disabled,
					$uniqueId);
				break;
			case FieldInfo::TYPE_ID:
				break;
			case FieldInfo::TYPE_EMAIL:
				break;
			case FieldInfo::TYPE_LOCALE:
				break;
			case FieldInfo::TYPE_DATE_TIME:
				break;
			case FieldInfo::TYPE_FLOAT:
				break;
			case FieldInfo::TYPE_DURATION:
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
			FieldInfo::TYPE_DURATION =>'TYPE_DURATION'
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