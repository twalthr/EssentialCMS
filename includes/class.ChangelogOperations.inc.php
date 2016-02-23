<?php

final class ChangelogOperations {

	const CHANGELOG_TYPE_GLOBAL = 0;
	const CHANGELOG_TYPE_PAGE = 1;
	const CHANGELOG_TYPE_FIELD_GROUP = 2;
	const CHANGELOG_TYPE_MEDIA_REFERENCE = 3;

	const CHANGELOG_OPERATION_INSERTED = 0;
	const CHANGELOG_OPERATION_UPDATED = 1;
	const CHANGELOG_OPERATION_DELETED = 2;

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

}

?>