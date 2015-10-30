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
			$result = $this->fieldOperations->deleteFields($fieldGroup['fgid']);
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
			WHERE `module`=? AND `key`=? ORDER BY `order` DESC',
			'is',
			$mid, $key);
		return $fieldGroups;
	}

	public function getFieldGroup($fgid) {
		$fieldGroup = $this->db->valueQuery('
			SELECT *
			FROM `FieldGroups`
			WHERE `fgid`=?',
			'i',
			$fgid);
		if ($fieldGroup === false) {
			return false;
		}
		return $fieldGroup;
	}

	public function getFieldGroupsWithTitle($mid, $key) {
		$fieldGroups = $this->db->valuesQuery('
			SELECT `fgid`, `order`, `f1`.`content` AS `title`, `f2`.`content` AS `private`
			FROM `FieldGroups` `fg`
			LEFT JOIN `Fields` `f1` ON `f1`.`group` = `fg`.`fgid` AND `f1`.`key` = "title"
			LEFT JOIN `Fields` `f2` ON `f2`.`group` = `fg`.`fgid` AND `f2`.`key` = "private"
			WHERE `fg`.`module`=? AND `fg`.`key`=? ORDER BY `fg`.`order` DESC',
			'is',
			$mid, $key);
		return $fieldGroups;
	}

	public function moveFieldGroupWithinSameKey($fgid, $newOrder) {
		$oldPosition = $this->db->valueQuery('
			SELECT `module`, `key`, `order`
			FROM `FieldGroups`
			WHERE `fgid`=?',
			'i',
			$fgid);
		if ($oldPosition === false) {
			return false;
		}
		$module = $oldPosition['module'];
		$key = $oldPosition['key'];
		$oldOrder = $oldPosition['order'];

		// move to same position can be skipped
		if ($oldOrder === $newOrder) {
			return true;
		}

		$count = $this->db->valueQuery('
				SELECT COUNT(*) AS `value`
				FROM `FieldGroups`
				WHERE `module`=? AND `key`=?',
				'is',
				$module, $key);
		if ($count === false) {
			return false;
		}
		$count = $count['value'];

		$result = $this->db->impactQuery('
				UPDATE `FieldGroups`
				SET `order`=?
				WHERE `fgid`=?',
				'ii',
				$count, $fgid);

		// based on
		// http://stackoverflow.com/questions/8607998/using-a-sort-order-column-in-a-database-table
		if ($newOrder < $oldOrder) {
			$result = $result && $this->db->impactQuery('
				UPDATE `FieldGroups`
				SET `order` = `order` + 1
				WHERE `module`=? AND `key`=? AND `order` BETWEEN ? AND ?
				ORDER BY `order` DESC',
				'isii',
				$module, $key, $newOrder, $oldOrder);
		}

		if ($newOrder > $oldOrder) {
			$result = $result && $this->db->impactQuery('
				UPDATE `FieldGroups`
				SET `order` = `order` - 1
				WHERE `module`=? AND `key`=? AND `order` BETWEEN ? AND ?
				ORDER BY `order` ASC',
				'isii',
				$module, $key, $oldOrder, $newOrder);
		}

		$result = $result && $this->db->impactQuery('
				UPDATE `FieldGroups`
				SET `order`=?
				WHERE `fgid`=?',
				'ii',
				$newOrder, $fgid);

		return $result;
	}

	public function copyFieldGroupWithinSameKey($fgid, $newOrder) {
		$fieldGroup = $this->getFieldGroup($fgid);
		if ($fieldGroup === false) {
			return false;
		}
		$newFgid = $this->addFieldGroup($fieldGroup['module'], $fieldGroup['key']);
		if ($newFgid === false) {
			return false;
		}
		$result = $this->moveFieldGroupWithinSameKey($newFgid, $newOrder);
		return $result && $this->fieldOperations->copyFields($fgid, $newFgid);
	}

	public function deleteFieldGroup($fgid) {
		$fieldGroup = $this->db->valueQuery('
			SELECT `module`, `key`, `order`
			FROM `FieldGroups`
			WHERE `fgid`=?',
			'i',
			$fgid);
		if ($fieldGroup === false) {
			return false;
		}
		$result = $this->fieldOperations->deleteFields($fgid);

		$result = $result && $this->db->impactQuery('
			DELETE FROM `FieldGroups`
			WHERE `fgid`=?',
			'i',
			$fgid);

		return $result && $this->db->successQuery('
			UPDATE `FieldGroups`
				SET `order` = `order` - 1
				WHERE `module`=? AND `key`=? AND `order`>?
				ORDER BY `order` ASC',
				'isi',
				$fieldGroup['module'], $fieldGroup['key'], $fieldGroup['order']);
	}
}

?>