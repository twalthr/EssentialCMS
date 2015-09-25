<?php

final class MenuItemOperations {

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function isValidExternalId($externalId) {
		return $this->db->resultQuery('
			SELECT `mpid`
			FROM `MenuPaths`
			WHERE `externalId`=?',
			's',
			$externalId);
	}
}

?>