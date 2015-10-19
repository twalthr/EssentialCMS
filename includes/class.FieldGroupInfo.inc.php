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
		// TODO check that title present if one pager
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

	public function getHasOrder() {
		return $this->hasOrder;
	}

	// --------------------------------------------------------------------------------------------
	// Visualize field group for administration
	// --------------------------------------------------------------------------------------------

	public function printFieldGroupSection($moduleDefinition, $fieldGroups, $fieldOperations) {
		echo '<form method="post">';
		echo '<section>';
		echo '<h1>';
		if ($this->maxNumberOfGroups === 1) {
			$moduleDefinition->text($this->name);
		}
		else {
			$moduleDefinition->text($this->namePlural);
		}
		echo '</h1>';
		
		if ($this->isOnePagePerGroup()) {

		}
		else {

		}
		echo '</section>';
		echo '</form>';
	}
}

?>