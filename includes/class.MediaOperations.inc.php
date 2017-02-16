<?php

final class MediaOperations {

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function getMediaSummary($mgid) {
		return $this->db->valuesQuery('
			SELECT `mid`, `internalName`, `originalModified`, `description`, `tags`, `options`,
				`lastChanged`, `size`
			FROM `Media`
			WHERE `group`=? AND `originalName` IS NOT NULL AND `internalName` IS NOT NULL
			ORDER BY `internalName` ASC',
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
			SELECT `mid`, `group`, `originalName`, `originalModified`, `internalName`, `description`,
				`tags`, `checksum`, `size`, `externalId`, `options`, `lastChanged`, `externalLastChanged`
			FROM `Media`
			WHERE `mid`=?',
			'i',
			$mid);
	}

	public function commitTempMedia($mid, $path, $modified) {
		return $this->db->impactQuery('
			UPDATE `Media`
			SET `originalName`=?, `originalModified`=?, `internalName`=?
			WHERE `mid`=?',
			'sssi',
			$path, $modified, $path, $mid);
	}

	public function deleteMedia($mgid, $mid) {
		return $this->db->impactQuery('
			DELETE FROM `Media`
			WHERE `group`=? AND `mid`=?',
			'ii',
			$mgid, $mid);
	}

	public function getAllTempMedia() {
		return $this->db->valuesQuery('
			SELECT `mid` AS `value`
			FROM `Media`
			WHERE `originalName` IS NULL AND `internalName` IS NULL');
	}

	public function deleteAllTempMedia($mgid) {
		return $this->db->impactQuery('
			DELETE FROM `Media`
			WHERE `originalName` IS NULL AND `internalName` IS NULL AND `group`=?',
			'i',
			$mgid);
	}

}

?>