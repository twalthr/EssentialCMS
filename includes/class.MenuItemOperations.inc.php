<?php

final class MenuItemOperations {

	const MENU_ITEMS_OPTION_PRIVATE = 1;
	const MENU_ITEMS_OPTION_BLANK = 2;
	const MENU_ITEMS_OPTION_HIDDEN = 4;

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function isValidExternalId($externalId) {
		return $this->db->resultQuery('
			SELECT `miid`
			FROM `MenuItems`
			WHERE `externalId`=?',
			's',
			$externalId);
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
			$result = $result && $this->db->impactQuery('
				UPDATE `MenuItems`
				SET `order` = `order` + 1
				WHERE `parent` IS NULL AND `order`>=?
				ORDER BY `order` DESC',
				'i',
				$oldPosition['order']);
		}
		else {
			$result = $result && $this->db->impactQuery('
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
		return $this->db->impactQueryWithId('
			INSERT INTO `MenuItems`
			(`parent`, `order`, `title`, `hoverTitle`, `externalId`, `destPage`, `destLink`, `options`)
			VALUES
			(?,0,?,?,?,?,?,?)',
			'isssisi',
			$parent, $title, $hoverTitle, $externalId, $destPage, $destLink, $options);
	}
}

?>