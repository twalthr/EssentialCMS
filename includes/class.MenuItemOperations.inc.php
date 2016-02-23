<?php

final class MenuItemOperations {

	const MENU_ITEMS_OPTION_PRIVATE = 1;
	const MENU_ITEMS_OPTION_BLANK = 2;
	const MENU_ITEMS_OPTION_HIDDEN = 4;

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function makeMenuItemPublic($miid) {
		return $this->db->impactQuery('
			UPDATE `MenuItems`
			SET `options` = `options` & ~' . MenuItemOperations::MENU_ITEMS_OPTION_PRIVATE . '
			WHERE `miid`=?',
			'i',
			$miid);
	}

	public function makeMenuItemPrivate($miid) {
		return $this->db->impactQuery('
			UPDATE `MenuItems`
			SET `options` = `options` | ' . MenuItemOperations::MENU_ITEMS_OPTION_PRIVATE . '
			WHERE `miid`=?',
			'i',
			$miid);
	}

	public function updateMenuItem($miid, $updateColumns) {
		// no update
		if (count($updateColumns) === 0) {
			return true;
		}
		$query = '
			UPDATE `MenuItems`
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
		$query .= ' WHERE `miid`=?';
		$types .= 'i';
		$values[] = $miid;
		return $this->db->impactQuery($query, $types, ...$values);
	}

	public function isValidExternalId($parent, $externalId) {
		if ($parent === null) {
			return $this->db->resultQuery('
				SELECT `miid`
				FROM `MenuItems`
				WHERE `parent` IS NULL AND `externalId`=?',
				's',
				$externalId);
		}
		// existing sibling with common parent
		else {
			return $this->db->resultQuery('
				SELECT `miid`
				FROM `MenuItems`
				WHERE `parent`=? AND `externalId`=?',
				'is',
				$parent, $externalId);
		}
	}

	public function isValidSiblingExternalId($sibling, $externalId) {
		// sibling is not present (top-level)
		if ($sibling === null) {
			return $this->db->resultQuery('
				SELECT `miid`
				FROM `MenuItems`
				WHERE `parent` IS NULL AND `externalId`=?',
				's',
				$externalId);
		}
		// existing sibling with common parent
		else {
			$commonParent = $this->db->valueQuery('
				SELECT `parent`
				FROM `MenuItems`
				WHERE `miid`=?',
				'i',
				$sibling);
			if ($commonParent === false) {
				return false;
			}
			$commonParent = $commonParent['parent'];
			if ($commonParent === null) {
				return $this->db->resultQuery('
					SELECT `miid`
					FROM `MenuItems`
					WHERE `parent` IS NULL AND `externalId`=?',
					's',
					$externalId);
			}
			else {
				return $this->db->resultQuery('
					SELECT `miid`
					FROM `MenuItems`
					WHERE `parent`=? AND `externalId`=?',
					'is',
					$commonParent,
					$externalId);
			}
		}
	}

	public function getParentMenuItems() {
		return $this->db->valuesQuery('
			SELECT `miid`, `title`, `hoverTitle`, `options`
			FROM `MenuItems`
			WHERE `parent` IS NULL
			ORDER BY `order` ASC');
	}

	public function getSubmenuItems($parent) {
		return $this->db->valuesQuery('
			SELECT *
			FROM `MenuItems`
			WHERE `parent`=?
			ORDER BY `order` ASC', 'i', $parent);
	}

	public function getMenuItem($miid) {
		return $this->db->valueQuery('
			SELECT *
			FROM `MenuItems`
			WHERE `miid`=?', 'i', $miid);
	}

	public function addMenuItemAtEnd($parent, $title, $hoverTitle, $externalId, $destPage, $destLink,
			$options) {
		$nextOrder = false;
		if ($parent === null) {
			$nextOrder = $this->db->valueQuery('
				SELECT COUNT(*) AS `value`
				FROM `MenuItems`
				WHERE `parent` IS NULL');
		}
		else {
			$nextOrder = $this->db->valueQuery('
				SELECT COUNT(*) AS `value`
				FROM `MenuItems`
				WHERE `parent`=?',
				'i',
				$parent);
		}
		if ($nextOrder === false) {
			return false;
		}
		$nextOrder = $nextOrder['value'];
		return $this->db->impactQueryWithId('
			INSERT INTO `MenuItems`
			(`parent`, `order`, `title`, `hoverTitle`, `externalId`, `destPage`, `destLink`, `options`)
			VALUES
			(?,?,?,?,?,?,?,?)',
			'iisssisi',
			$parent, $nextOrder, $title, $hoverTitle, $externalId, $destPage, $destLink, $options
			);
	}

	public function addMenuItemAt($position, $title, $hoverTitle, $externalId, $destPage, $destLink,
			$options) {
		$oldPosition = $this->db->valueQuery('
			SELECT `parent`, `order`
			FROM `MenuItems`
			WHERE `miid`=?',
			'i',
			$position);
		if ($oldPosition === false) {
			return false;
		}
		// shift all items at position down
		$result = true;
		if ($oldPosition['parent'] === null) {
			$result = $result && $this->db->successQuery('
				UPDATE `MenuItems`
				SET `order` = `order` + 1
				WHERE `parent` IS NULL AND `order`>=?
				ORDER BY `order` DESC',
				'i',
				$oldPosition['order']);
		}
		else {
			$result = $result && $this->db->successQuery('
				UPDATE `MenuItems`
				SET `order` = `order` + 1
				WHERE `parent`=? AND `order`>=?
				ORDER BY `order` DESC',
				'ii',
				$oldPosition['parent'], $oldPosition['order']);
		}
		if ($result === false) {
			return false;
		}

		// insert item
		return $this->db->impactQueryWithId('
			INSERT INTO `MenuItems`
			(`parent`, `order`, `title`, `hoverTitle`, `externalId`, `destPage`, `destLink`, `options`)
			VALUES
			(?,?,?,?,?,?,?,?)',
			'iisssisi',
			$oldPosition['parent'], $oldPosition['order'], $title, $hoverTitle, $externalId, $destPage,
			$destLink, $options);
	}

	public function addMenuItemSubmenu($parent, $title, $hoverTitle, $externalId, $destPage, $destLink,
			$options) {
		// shift all items at position 0 of parent down
		$result = $this->db->successQuery('
			UPDATE `MenuItems`
			SET `order` = `order` + 1
			WHERE `parent`=? AND `order`>=0
			ORDER BY `order` DESC',
			'i',
			$parent);
		if ($result === false) {
			return false;
		}
		return $this->db->impactQueryWithId('
			INSERT INTO `MenuItems`
			(`parent`, `order`, `title`, `hoverTitle`, `externalId`, `destPage`, `destLink`, `options`)
			VALUES
			(?,0,?,?,?,?,?,?)',
			'isssisi',
			$parent, $title, $hoverTitle, $externalId, $destPage, $destLink, $options);
	}

	public function copyMenuItemAt($position, $miid) {
		$oldPosition = $this->db->valueQuery('
			SELECT `parent`
			FROM `MenuItems`
			WHERE `miid`=?',
			'i',
			$position);
		if ($oldPosition === false) {
			return false;
		}

		// get item to copy
		$copyItem = $this->db->valueQuery('
			SELECT `title`, `hoverTitle`, `externalId`, `destPage`, `destLink`, `options`
			FROM `MenuItems`
			WHERE `miid`=?',
			'i',
			$miid);
		if ($copyItem === false) {
			return false;
		}

		// check if externalId already exists
		$externalId = $copyItem['externalId'];
		$duplicates = false;
		if ($oldPosition['parent'] === null) {
			$duplicates = $this->db->valueQuery('
				SELECT COUNT(*) AS `value`
				FROM `MenuItems`
				WHERE `parent` IS NULL AND `externalId`=?',
				's',
				$externalId);
		}
		else {
			$duplicates = $this->db->valueQuery('
				SELECT COUNT(*) AS `value`
				FROM `MenuItems`
				WHERE `parent`=? AND `externalId`=?',
				'is',
				$oldPosition['parent'], $externalId);
		}
		if ($duplicates === false) {
			return false;
		}
		// externalId already exists -> try externalId with suffix
		else if ($duplicates['value'] > 0) {
			$externalId = $externalId . '_' . uniqid();
			// check if externalId with suffix is unique
			if ($oldPosition['parent'] === null) {
				$duplicates = $this->db->valueQuery('
					SELECT COUNT(*) AS `value`
					FROM `MenuItems`
					WHERE `parent` IS NULL AND `externalId`=?',
					's',
					$externalId);
			}
			else {
				$duplicates = $this->db->valueQuery('
					SELECT COUNT(*) AS `value`
					FROM `MenuItems`
					WHERE `parent`=? AND `externalId`=?',
					'is',
					$oldPosition['parent'], $externalId);
			}
			// also not possible -> copy failed
			if ($duplicates === false || $duplicates['value'] > 0) {
				return false;
			}
		}

		// add it again
		return $this->addMenuItemAt($position, $copyItem['title'], $copyItem['hoverTitle'],
			$externalId, $copyItem['destPage'], $copyItem['destLink'],
			$copyItem['options']);
	}

	public function copyMenuItemSubmenu($parent, $miid) {
		// get item to copy
		$copyItem = $this->db->valueQuery('
			SELECT `parent`, `title`, `hoverTitle`, `externalId`, `destPage`, `destLink`, `options`
			FROM `MenuItems`
			WHERE `miid`=?',
			'i',
			$miid);
		if ($copyItem === false) {
			return false;
		}

		// check if externalId already exists
		$externalId = $copyItem['externalId'];
		$duplicates = $this->db->valueQuery('
				SELECT COUNT(*) AS `value`
				FROM `MenuItems`
				WHERE `parent`=? AND `externalId`=?',
				'is',
				$parent, $externalId);
		if ($duplicates === false) {
			return false;
		}
		// externalId already exists -> try externalId with suffix
		else if ($duplicates['value'] > 0) {
			$externalId = $externalId . '_' . uniqid();
			// check if externalId with suffix is unique
			$duplicates = $this->db->valueQuery('
				SELECT COUNT(*) AS `value`
				FROM `MenuItems`
				WHERE `parent`=? AND `externalId`=?',
				'is',
				$parent, $externalId);
			// also not possible -> copy failed
			if ($duplicates === false || $duplicates['value'] > 0) {
				return false;
			}
		}

		// add it again
		return $this->addMenuItemSubmenu($parent, $copyItem['title'], $copyItem['hoverTitle'],
			$externalId, $copyItem['destPage'], $copyItem['destLink'],
			$copyItem['options']);
	}

	public function deleteMenuItem($miid) {
		$oldPosition = $this->db->valueQuery('
			SELECT `parent`, `order`
			FROM `MenuItems`
			WHERE `miid`=?',
			'i',
			$miid);
		if ($oldPosition === false) {
			return false;
		}
		// delete item
		$result = $this->db->impactQuery('
			DELETE FROM `MenuItems`
			WHERE `miid`=?',
			'i',
			$miid);
		// shift all items at position up
		if ($oldPosition['parent'] === null) {
			$result = $result && $this->db->successQuery('
				UPDATE `MenuItems`
				SET `order` = `order` - 1
				WHERE `parent` IS NULL AND `order`>=?
				ORDER BY `order` ASC',
				'i',
				$oldPosition['order']);
		}
		else {
			$result = $result && $this->db->successQuery('
				UPDATE `MenuItems`
				SET `order` = `order` - 1
				WHERE `parent`=? AND `order`>=?
				ORDER BY `order` ASC',
				'ii',
				$oldPosition['parent'], $oldPosition['order']);
		}
		return $result;
	}
}

?>