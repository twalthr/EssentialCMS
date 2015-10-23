<?php

final class FieldGroupOperations {

	private $db;
	private $fieldOperations;

	public function __construct($db, $fieldOperations) {
		$this->db = $db;
		$this->fieldOperations = $fieldOperations;
	}

	public function addFieldGroup($mid, $key) {
		$nextOrder = false;
		if ($key === null) {
			$nextOrder = null;
		}
		else {
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
		}
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

	public function getNumberOfFieldGroups($mid, $key) {
		$count = false;
		if ($key === null) {
			$count = $this->db->valueQuery('
				SELECT COUNT(*) AS `value`
				FROM `FieldGroups`
				WHERE `module`=? AND `key` IS NULL',
				'i',
				$mid);
		}
		else {
			$count = $this->db->valueQuery('
				SELECT COUNT(*) AS `count`
				FROM `FieldGroups`
				WHERE `module`=? AND `key`=?',
				'is',
				$mid, $key);
		}
		if ($count === false) {
			return false;
		}
		return intval($count['value']);
	}

	public function getConfigFieldGroupId($mid) {
		$fieldGroupId = $this->db->valueQuery('
			SELECT `fgid`
			FROM `FieldGroups`
			WHERE `module`=? AND `key` IS NULL AND `order` IS NULL',
			'i',
			$mid);
		if ($fieldGroupId === false) {
			return false;
		}
		return $fieldGroupId['fgid'];
	}

	public function getFieldGroups($mid, $key) {
		$fieldGroups = $this->db->valuesQuery('
			SELECT `fgid`, `order`
			FROM `FieldGroups`
			WHERE `module`=? AND `key`=? ORDER BY `order` ASC',
			'is',
			$mid, $key);
		return $fieldGroups;
	}

	public function getFieldGroupsWithTitle($mid, $key) {
		$fieldGroups = $this->db->valuesQuery('
			SELECT `fgid`, `order`, `f1`.`content` AS `title`, `f2`.`content` AS `private`
			FROM `FieldGroups` `fg`
			LEFT JOIN `Fields` `f1` ON `f1`.`group` = `fg`.`fgid` AND `f1`.`key` = "title"
			LEFT JOIN `Fields` `f2` ON `f2`.`group` = `fg`.`fgid` AND `f2`.`key` = "private"
			WHERE `fg`.`module`=? AND `fg`.`key`=? ORDER BY `fg`.`order` ASC',
			'is',
			$mid, $key);
		return $fieldGroups;
	}
}

?>