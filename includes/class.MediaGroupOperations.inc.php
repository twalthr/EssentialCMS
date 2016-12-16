<?php

final class MediaGroupOperations {

	const GLOBAL_GROUP = 1;

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function getGlobalMediaGroups() {
		return $this->db->valuesQuery('
			SELECT `mgid`, `title`, `options`
			FROM `MediaGroups`
			WHERE `options` & ' . MediaGroupOperations::GLOBAL_GROUP . ' = ' .
				MediaGroupOperations::GLOBAL_GROUP. '
			ORDER BY `title` ASC');
	}

	public function getLocalMediaGroups() {
		return $this->db->valuesQuery('
			SELECT `mgid`, `title`, `options`
			FROM `MediaGroups`
			WHERE `options` & ' . MediaGroupOperations::GLOBAL_GROUP . ' = 0
			ORDER BY `title` ASC');
	}

}

?>