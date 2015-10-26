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

	public function __construct($key, $name, $namePlural, $fieldInfos, $minNumberOfGroups = 1,
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
		// TODO check that title present if one pager and private
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

	// --------------------------------------------------------------------------------------------
	// Visualize field group for administration
	// --------------------------------------------------------------------------------------------

	public function printFieldsWithLabel() {
		
		
	}
}

?>