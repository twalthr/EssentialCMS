<?php

class FieldGroupInfo {

	private $key;
	private $name;
	private $namePlural;
	private $fieldInfos;
	private $minNumberOfGroups;
	private $maxNumberOfGroups;
	private $onePagePerGroup;
	private $hasOrder;

	public function __construct($key, $name, $namePlural, $fieldInfos, $minNumberOfGroups = 0,
			$maxNumberOfGroups = null, $onePagePerGroup = false, $hasOrder = false) {
		$this->key = $key;
		$this->name = $name;
		$this->namePlural = $namePlural;
		$this->fieldInfos = $fieldInfos;
		$this->minNumberOfGroups = $minNumberOfGroups;
		$this->maxNumberOfGroups = $maxNumberOfGroups;
		$this->onePagePerGroup = $onePagePerGroup;
		$this->hasOrder = $hasOrder;

		// validate parameters
		if (!isset($this->key) && !preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $this->key)) {
			throw new Exception("Key must not be null and only consist of [A-Za-z][A-Za-z0-9]*.");
		}
		if (!isset($this->name)) {
			throw new Exception("Name must not be null.");
		}
		if (!isset($this->namePlural)) {
			throw new Exception("Name must not be null.");
		}
		if (!isset($this->fieldInfos) || !is_array($this->fieldInfos) || count($this->fieldInfos) === 0) {
			throw new Exception("Field group must specify fields.");
		}
		if (!isset($this->minNumberOfGroups) || !is_int($this->minNumberOfGroups)) {
			throw new Exception("Field group must specify MinNumberOfGroups.");
		}
		if (isset($this->maxNumberOfGroups) && !is_int($this->maxNumberOfGroups)) {
			throw new Exception("MaxNumberOfGroups can only be int or null.");
		}
		if (isset($this->onePagePerGroup) && !is_bool($this->onePagePerGroup)) {
			throw new Exception("OnePagePerGroup can only be boolean or null.");
		}		
		if ($this->onePagePerGroup === true && $this->minNumberOfGroups !== 0) {
			throw new Exception("MinNumberOfGroups must be 0 if OnePagePerGroup is true.");
		}
		if (isset($this->hasOrder) && !is_bool($this->hasOrder)) {
			throw new Exception("HasOrder can only be boolean or null.");
		}
		// a one pager field group must have a title
		if ($this->isOnePagePerGroup()) {
			$titleFound = false;
			foreach ($this->fieldInfos as $fieldInfo) {
				if ($fieldInfo->getKey() === 'title'
					&& $fieldInfo->getAllowedTypes() === FieldInfo::TYPE_PLAIN
					&& $fieldInfo->isRequired() === true) {
					$titleFound = true;
				}
			}
			if (!$titleFound) {
				throw new Exception('A one page field group must have a required plain text "title" field.');
			}
		}
	}

	public function getKey() {
		return $this->key;
	}

	public function getName() {
		return $this->name;
	}

	public function getNamePlural() {
		return $this->namePlural;
	}

	public function getFieldInfos() {
		return $this->fieldInfos;
	}

	public function getMinNumberOfGroups() {
		return $this->minNumberOfGroups;
	}

	public function getMaxNumberOfGroups() {
		return $this->maxNumberOfGroups;
	}

	public function isOnePagePerGroup() {
		return $this->onePagePerGroup === true;
	}

	public function hasOrder() {
		return $this->hasOrder === true;
	}

}

?>