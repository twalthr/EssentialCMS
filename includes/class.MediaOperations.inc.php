<?php

final class MediaOperations {

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function getMediaSummary($mgid) {
		return $this->db->valuesQuery('
			SELECT `mid`, `internalName`, `description`, `tags`, `checksum`, `options`, `lastChanged`
			FROM `Media`
			WHERE `group`=?',
			'i',
			$mgid);
	}

}

?>