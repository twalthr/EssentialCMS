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
	private $hasMetaContent;
	private $allowedContentTypes;
	private $allowedMetaContentTypes;
	private $name;
	private $metaName;
	private $largeContentField;
	private $largeMetaContentField;
	private $minContentLength;
	private $minMetaContentLength;
	private $maxContentLength;
	private $maxMetaContentLength;
	private $additionalNames; // e.g. for type boolean = checkbox string, for type enum = values

	public function __construct(
		$key,
		$hasMetaContent,
		$allowedContentTypes,
		$allowedMetaContentTypes,
		$name,
		$metaName,
		$largeContentField,
		$largeMetaContentField,
		$minContentLength,
		$minMetaContentLength,
		$maxContentLength,
		$maxMetaContentLength,
		$additionalNames) {
		
		$this->key = $key;
		$this->hasMetaContent = $hasMetaContent;
		$this->allowedContentTypes = $allowedContentTypes;
		$this->allowedMetaContentTypes = $allowedMetaContentTypes;
		$this->name = $name;
		$this->metaName = $metaName;
		$this->largeContentField = $largeContentField;
		$this->largeMetaContentField = $largeMetaContentField;
		$this->minContentLength = $minContentLength;
		$this->minMetaContentLength = $minMetaContentLength;
		$this->maxContentLength = $maxContentLength;
		$this->maxMetaContentLength = $maxMetaContentLength;
		$this->additionalNames = $additionalNames;
	}

	public function getKey(){
		return $this->key;
	}

	public function getHasMetaContent(){
		return $this->hasMetaContent;
	}

	public function getAllowedContentTypes(){
		return $this->allowedContentTypes;
	}

	public function getAllowedMetaContentTypes(){
		return $this->allowedMetaContentTypes;
	}

	public function getName(){
		return $this->name;
	}

	public function getMetaName(){
		return $this->metaName;
	}

	public function getLargeContentField(){
		return $this->largeContentField;
	}

	public function getLargeMetaContentField(){
		return $this->largeMetaContentField;
	}

	public function getMinContentLength(){
		return $this->minContentLength;
	}

	public function getMinMetaContentLength(){
		return $this->minMetaContentLength;
	}

	public function getMaxContentLength(){
		return $this->maxContentLength;
	}

	public function getMaxMetaContentLength(){
		return $this->maxMetaContentLength;
	}

	public function getAdditionalNames(){
		return $this->additionalNames;
	}

	// --------------------------------------------------------------------------------------------
	// Visualize field for administration
	// --------------------------------------------------------------------------------------------
	public static function isMultiTypeField($type) {
		return count(FieldInfo::getTypesOfField($type)) > 1;
	}

	public static function getTypesOfField($type) {
		$types = [];
		if ($type & FieldInfo::TYPE_PLAIN) $types[] = FieldInfo::TYPE_PLAIN;
		if ($type & FieldInfo::TYPE_HTML) $types[] = FieldInfo::TYPE_HTML;
		if ($type & FieldInfo::TYPE_MARKDOWN) $types[] = FieldInfo::TYPE_MARKDOWN;
		if ($type & FieldInfo::TYPE_IMAGE) $types[] = FieldInfo::TYPE_IMAGE;
		if ($type & FieldInfo::TYPE_FILE) $types[] = FieldInfo::TYPE_FILE;
		if ($type & FieldInfo::TYPE_TAGS) $types[] = FieldInfo::TYPE_TAGS;
		if ($type & FieldInfo::TYPE_INT) $types[] = FieldInfo::TYPE_INT;
		if ($type & FieldInfo::TYPE_BOOLEAN) $types[] = FieldInfo::TYPE_BOOLEAN;
		if ($type & FieldInfo::TYPE_ENUM) $types[] = FieldInfo::TYPE_ENUM;
		if ($type & FieldInfo::TYPE_DATE) $types[] = FieldInfo::TYPE_DATE;
		if ($type & FieldInfo::TYPE_COLOR) $types[] = FieldInfo::TYPE_COLOR;
		if ($type & FieldInfo::TYPE_LINK) $types[] = FieldInfo::TYPE_LINK;
		if ($type & FieldInfo::TYPE_PAGE) $types[] = FieldInfo::TYPE_PAGE;
		if ($type & FieldInfo::TYPE_ID) $types[] = FieldInfo::TYPE_ID;
		if ($type & FieldInfo::TYPE_EMAIL) $types[] = FieldInfo::TYPE_EMAIL;
		if ($type & FieldInfo::TYPE_LOCALE) $types[] = FieldInfo::TYPE_LOCALE;
		return $types;
	}

	public static function translateTypeToString($type) {
		switch ($type) {
			case FieldInfo::TYPE_PLAIN: return 'TYPE_PLAIN';
			case FieldInfo::TYPE_HTML: return 'TYPE_HTML';
			case FieldInfo::TYPE_MARKDOWN: return 'TYPE_MARKDOWN';
			case FieldInfo::TYPE_IMAGE: return 'TYPE_IMAGE';
			case FieldInfo::TYPE_FILE: return 'TYPE_FILE';
			case FieldInfo::TYPE_TAGS: return 'TYPE_TAGS';
			case FieldInfo::TYPE_INT: return 'TYPE_INT';
			case FieldInfo::TYPE_BOOLEAN: return 'TYPE_BOOLEAN';
			case FieldInfo::TYPE_ENUM: return 'TYPE_ENUM';
			case FieldInfo::TYPE_DATE: return 'TYPE_DATE';
			case FieldInfo::TYPE_COLOR: return 'TYPE_COLOR';
			case FieldInfo::TYPE_LINK: return 'TYPE_LINK';
			case FieldInfo::TYPE_PAGE: return 'TYPE_PAGE';
			case FieldInfo::TYPE_ID: return 'TYPE_ID';
			case FieldInfo::TYPE_EMAIL: return 'TYPE_EMAIL';
			case FieldInfo::TYPE_LOCALE: return 'TYPE_LOCALE';
		}
	}

	public static function printField($postField, $defaultValue, $large, $minLength, $maxLength) {
		switch ($type) {
			case FieldInfo::TYPE_PLAIN:
			case FieldInfo::TYPE_HTML:
			case FieldInfo::TYPE_MARKDOWN:
				UiUtils::printTextInput($postField, $defaultValue, $large, $minLength, $maxLength);
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
	// Validate field for administration
	// --------------------------------------------------------------------------------------------

	// --------------------------------------------------------------------------------------------
	// Compile field
	// --------------------------------------------------------------------------------------------
}

?>