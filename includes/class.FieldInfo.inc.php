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
		$this->additionalName = $additionalName;
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
}

?>