<?php

final class FieldOperations {

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function copyFields($fromFgid, $toFgid) {
		return $this->db->successQuery('
			INSERT INTO `Fields`
			(`group`, `key`, `type`, `content`, `metaType`, `metaContent`)
			SELECT ?, `key`, `type`, `content`, `metaType`, `metaContent`
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
		if (count($fields) === 0) {
			return null;
		}
		return $fields;
	}
}

?>