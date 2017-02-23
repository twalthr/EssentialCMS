<?php

final class MediaOperations {

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function getMediaSummary($mgid) {
		return $this->db->valuesQuery('
			SELECT `mid`, `parent`,`internalName`, `originalModified`, `description`, `tags`, `options`,
				`lastChanged`, `size`
			FROM `Media`
			WHERE `group`=? AND `originalName` IS NOT NULL AND `internalName` IS NOT NULL
			ORDER BY `parent` DESC, `internalName` ASC',
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
			SELECT `mid`, `group`, `parent`,`originalName`, `originalModified`, `internalName`, `description`,
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

	public function deleteMedia($mid) {
		return $this->db->impactQuery('
			DELETE FROM `Media`
			WHERE `mid`=?',
			'i',
			$mid);
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

	public function moveMedia($mid, $internalName) {
		return $this->db->impactQuery('
			UPDATE `Media`
			SET `internalName`=?
			WHERE `mid`=?',
			'si',
			$internalName, $mid);
	}

	public function attachMedia($targetMid, $attachmentMid, $attachmentPath) {
		return $this->db->impactQuery('
			UPDATE `Media`
			SET `parent`=?, `internalName`=?
			WHERE `mid`=?',
			'isi',
			$targetMid, $attachmentPath, $attachmentMid);
	}

	public function detachMedia($mid, $detachmentPath) {
		return $this->db->impactQuery('
			UPDATE `Media`
			SET `parent`=NULL, `internalName`=?
			WHERE `mid`=?',
			'si',
			$detachmentPath, $mid);
	}

	public function copyMedia($mid, $copiedpath) {
		return $this->db->impactQueryWithId('
			INSERT INTO `Media`
			(`group`, `parent`,`originalName`, `originalModified`, `internalName`, `description`,
				`tags`, `checksum`, `size`, `externalId`, `options`, `lastChanged`, `externalLastChanged`)
			SELECT `group`, `parent`,`originalName`, `originalModified`, ?, `description`,
				`tags`, `checksum`, `size`, `externalId`, `options`, `lastChanged`, `externalLastChanged`
			FROM `Media`
			WHERE `mid`=?',
			'si',
			$copiedpath, $mid);
	}

	public function copyAttachment($mid, $parent) {
		return $this->db->impactQueryWithId('
			INSERT INTO `Media`
			(`group`, `parent`,`originalName`, `originalModified`, `internalName`, `description`,
				`tags`, `checksum`, `size`, `externalId`, `options`, `lastChanged`, `externalLastChanged`)
			SELECT `group`, ?,`originalName`, `originalModified`, `internalName`, `description`,
				`tags`, `checksum`, `size`, `externalId`, `options`, `lastChanged`, `externalLastChanged`
			FROM `Media`
			WHERE `mid`=?',
			'ii',
			$parent, $mid);
	}

	public function getAttachements($parent) {
		return $this->db->valuesQuery('
			SELECT `mid` AS `value`
			FROM `Media`
			WHERE `parent`=?',
			'i',
			$parent);
	}

}

?>