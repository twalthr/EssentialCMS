<?php

final class MediaOperations {

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function getMediaSummary($mgid) {
		return $this->db->valuesQuery('
			SELECT `mid`, `internalName`, `description`, `tags`, `options`, `lastChanged`
			FROM `Media`
			WHERE `group`=?',
			'i',
			$mgid);
	}

	public function addTempMedia($mgid, $checksum, $size) {
		return $this->db->impactQueryWithId('
			INSERT INTO `Media`
			(`group`, `checksum`, `size`, `options`, `lastChanged`)
			VALUES
			(?,?,?,0,NOW())',
			'isi',
			$mgid, $checksum, $size);
	}

	public function getTempMedia($mid) {
		return $this->db->valueQuery('
			SELECT `mid`, `group`, `checksum`, `size`
			FROM `Media`
			WHERE `mid`=? AND `originalName` IS NULL AND `internalName` IS NULL',
			'i',
			$mid);
	}

	public function getMedia($mid) {
		return $this->db->valueQuery('
			SELECT `mid`, `group`, `originalName`, `internalName`, `description`, `tags`, `checksum`,
				`size`, `externalId`, `options`, `lastChanged`, `externalLastChanged`
			FROM `Media`
			WHERE `mid`=?',
			'i',
			$mid);
	}

	public function commitTempMedia($mid, $path) {
		return $this->db->impactQuery('
			UPDATE `Media`
			SET `originalName`=?, `internalName`=?
			WHERE `mid`=?',
			'ssi',
			$path, $path, $mid);
	}

}

?>