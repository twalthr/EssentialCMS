<?php

final class FieldGroupOperations {

	private $db;
	private $fieldOperations;

	public function __construct($db, $fieldOperations) {
		$this->db = $db;
		$this->fieldOperations = $fieldOperations;
	}

	public function addFieldGroup($mid, $key) {
		$nextOrder = $this->db->valueQuery('
			SELECT COUNT(*) AS `value`
			FROM `FieldGroups`
			WHERE `module`=? AND `key`=?',
			'is',
			$mid, $key);
		if ($nextOrder === false) {
			return false;
		}
		$nextOrder = $nextOrder['value'];
		return $this->db->impactQueryWithId('
			INSERT INTO `FieldGroups`
			(`module`, `key`, `order`)
			VALUES
			(?, ?, ?)',
			'isi',
			$mid, $key, $nextOrder);
	}

	public function copyFieldGroups($fromMid, $toMid) {
		$fieldGroups = $this->db->valuesQuery('
			SELECT `fgid`, `key`
			FROM `FieldGroups`
			WHERE `module`=?
			ORDER BY `order` ASC',
			'i',
			$fromMid);
		if ($fieldGroups === false) {
			return false;
		}
		foreach ($fieldGroups as $fieldGroup) {
			$newFgid = $this->addFieldGroup($toMid, $fieldGroup['key']);
			if ($newFgid === false) {
				return false;
			}
			$result = $this->fieldOperations->copyFields($fieldGroup['fgid'], $newFgid);
			if ($result === false) {
				return false;
			}
		}
		return true;
	}

	public function deleteFieldGroups($mid) {
		$fieldGroups = $this->db->valuesQuery('
			SELECT `fgid`
			FROM `FieldGroups`
			WHERE `module`=?
			ORDER BY `key` ASC, `order` DESC',
			'i',
			$mid);
		if ($fieldGroups === false) {
			return false;
		}
		foreach ($fieldGroups as $fieldGroup) {
			$result = $this->fieldOperations->removeFields($fieldGroup['fgid']);
			if ($result === false) {
				return false;
			}
		}
		return $this->db->successQuery('
			DELETE FROM `FieldGroups`
			WHERE `module`=?
			ORDER BY `key` ASC, `order` DESC',
			'i',
			$mid);
	}
}

?>