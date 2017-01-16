<?php

final class MediaGroupOperations {

	const GLOBAL_GROUP_OPTION = 1;
	const LOCKED_OPTION = 2;

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function addMediaGroup($title, $description, $tags, $options) {
		$mgid = $this->db->impactQueryWithId('
			INSERT INTO `MediaGroups`
			(`title`, `description`, `tags`, `checksum`, `options`)
			VALUES
			(?,?,?,NULL,?)',
			'sssi',
			$title, $description, $tags, $options);

		return $mgid;
	}

	public function updateMediaGroup($mgid, $updateColumns) {
		// no update
		if (count($updateColumns) === 0) {
			return true;
		}
		$query = '
			UPDATE `MediaGroups`
			SET ';
		$types = '';
		$values = [];
		foreach ($updateColumns as $key => $value) {
			$query .= '`' . $key. '`=?, ';
			if ($key === 'options') {
				$types .= 'i';
			}
			else {
				$types .= 's';
			}
			$values[] = $value;
		}
		$query = rtrim($query, ', ');
		$query .= ' WHERE `mgid`=?';
		$types .= 'i';
		$values[] = $mgid;

		return $this->db->impactQuery($query, $types, ...$values);
	}

	public function getGlobalMediaGroups() {
		return $this->db->valuesQuery('
			SELECT `mgid`, `title`, `description`, `tags`, `checksum`, `options`
			FROM `MediaGroups`
			WHERE `options` & ' . MediaGroupOperations::GLOBAL_GROUP_OPTION . ' = ' .
				MediaGroupOperations::GLOBAL_GROUP_OPTION. '
			ORDER BY `title` ASC');
	}

	public function getLocalMediaGroups() {
		return $this->db->valuesQuery('
			SELECT `mgid`, `title`, `description`, `tags`, `checksum`, `options`
			FROM `MediaGroups`
			WHERE `options` & ' . MediaGroupOperations::GLOBAL_GROUP_OPTION . ' = 0
			ORDER BY `title` ASC');
	}

	public function getMediaGroup($mgid) {
		return $this->db->valueQuery('
			SELECT `mgid`, `title`, `description`, `tags`, `checksum`, `options`
			FROM `MediaGroups`
			WHERE `mgid`=?',
			'i',
			$mgid);
	}

	public function deleteMediaGroup($mgid) {
		// TODO DELETE SUB DATA STRUCTURES
		return $this->db->successQuery('
			DELETE FROM `MediaGroups`
			WHERE `mgid`=?',
			'i',
			$mgid);
	}

	public function lockMediaGroup($mgid) {
		return $this->db->successQuery('
			UPDATE `MediaGroups`
			SET `options` = `options` | ' . MediaGroupOperations::LOCKED_OPTION . '
			WHERE `mgid`=?',
			'i',
			$mgid);
	}

	public function unlockMediaGroup($mgid) {
		return $this->db->successQuery('
			UPDATE `MediaGroups`
			SET `options` = `options` & ~' . MediaGroupOperations::LOCKED_OPTION . '
			WHERE `mgid`=?',
			'i',
			$mgid);
	}

	public function moveMediaGroup($mgid) {
		return $this->db->successQuery('
			UPDATE `MediaGroups`
			SET `options` = `options` ^ ' . MediaGroupOperations::GLOBAL_GROUP_OPTION . '
			WHERE `mgid`=?',
			'i',
			$mgid);
	}

	public function copyMediaGroup($mgid) {
		$mediaGroup = $this->getMediaGroup($mgid);
		if ($mediaGroup === false) {
			return false;
		}
		// add media group
		$newMgid = $this->addMediaGroup(
			$mediaGroup['title'],
			$mediaGroup['description'],
			$mediaGroup['tags'],
			$mediaGroup['options']);
		if ($newMgid === false) {
			return false;
		}
		return true; // TODO COPY SUB DATA STRUCTURES
	}

}

?>