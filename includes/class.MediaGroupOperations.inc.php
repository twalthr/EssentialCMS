<?php

final class MediaGroupOperations {

	const GLOBAL_GROUP_OPTION = 1;
	const LOCKED_OPTION = 2;

	private $db;

	public function __construct($db) {
		$this->db = $db;
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

}

?>