<?php

class FieldGroupInfo {

	private $key;
	private $name;
	private $initNumberOfGroups;
	private $maxNumberOfGroups;
	private $onePagePerGroup;
	private $hasOrder;
	private $fieldInfos;

	public function __construct($key, $name, $initNumberOfGroups, $maxNumberOfGroups, $onePagePerGroup,
		$hasOrder, $fieldInfos) {
		$this->key = $key;
		$this->name = $name;
		$this->initNumberOfGroups = $initNumberOfGroups;
		$this->maxNumberOfGroups = $maxNumberOfGroups;
		$this->onePagePerGroup = $onePagePerGroup;
		$this->hasOrder = $hasOrder;
		$this->fieldInfos = $fieldInfos;
	}

	public function getKey() {
		return $this->key;
	}

	public function getName() {
		return $this->name;
	}

	public function getInitNumberOfGroups() {
		return $this->initNumberOfGroups;
	}

	public function getMaxNumberOfGroups() {
		return $this->maxNumberOfGroups;
	}

	public function getOnePagePerGroup() {
		return $this->onePagePerGroup;
	}

	public function getHasOrder() {
		return $this->hasOrder;
	}

	public function getFieldInfos() {
		return $this->fieldInfos;
	}
}

?>