<?php

final class FieldOperations {

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function copyFields($fromFgid, $toFgid) {
		return $this->db->successQuery('
			INSERT INTO `Fields`
			(`group`, `key`, `type`, `content`)
			SELECT ?, `key`, `type`, `content`
			FROM `Fields` WHERE `group`=?',
			'ii',
			$toFgid, $fromFgid);
	}

	public function removeFields($fgid) {
		return $this->db->successQuery('
			DELETE FROM `Fields`
			WHERE `group`=?',
			'i',
			$fgid);
	}

	public function getFields($fgid) {
		$fields = $this->db->valuesQuery('
			SELECT *
			FROM `Fields`
			WHERE `group`=?',
			'i',
			$fgid);
		if ($fields === false) {
			return false;
		}
		return $fields;
	}
}

?>