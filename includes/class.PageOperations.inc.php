<?php

final class PageOperations {

	private $db;
	private $moduleOperations;

	public function __construct($db, $moduleOperations) {
		$this->db = $db;
		$this->moduleOperations = $moduleOperations;
	}

	public function getPageNames() {
		return $this->db->valuesQuery('
			SELECT `pid`, `title`
			FROM `Pages`');
	}
}

?>