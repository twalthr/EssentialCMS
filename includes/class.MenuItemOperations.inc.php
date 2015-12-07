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

	public function addMenuItem($parent, $order, $title, $hoverTitle, $externalId, $destPage, $destLink,
			$options) {

	}
}

?>