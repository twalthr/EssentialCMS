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
}

?>