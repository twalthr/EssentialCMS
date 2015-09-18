<?php

class FieldGroup {

	private $controller;
	private $fieldGroupId;

	public function __construct($controller, $fieldGroupId) {
		$this->controller = $controller;
		$this->fieldGroupId = $fieldGroupId;
	}
}

?>