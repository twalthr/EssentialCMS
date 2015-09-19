<?php

final class ModulesOperations {

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function addModule($page, $section, $moduleId) {
		$nextOrder = $this->db->valueQuery('
			SELECT COALESCE(MAX(`order`), -1) + 1 AS `value`
			FROM `Modules`
			WHERE `page`=? AND `section`=?',
			'ii',
			$page, $section);
		if ($nextOrder === false) {
			return false;
		}
		return $this->db->impactQuery('
			INSERT INTO `Modules`
			(`page`, `section`, `order`, `module`)
			VALUES
			(?, ?, ?, ?)',
			'iiis',
			$page, $section, $nextOrder['value'], $moduleId);
	}
}

?>