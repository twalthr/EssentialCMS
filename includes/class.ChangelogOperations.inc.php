<?php

final class ChangelogOperations {

	const CHANGELOG_INTERNAL_FALSE = 0;
	const CHANGELOG_INTERNAL_TRUE = 1;

	const CHANGELOG_TYPE_GLOBAL = 0;
	const CHANGELOG_TYPE_PAGE = 1;
	const CHANGELOG_TYPE_MODULE = 2;
	const CHANGELOG_TYPE_FIELD_GROUP = 3;
	const CHANGELOG_TYPE_MEDIA_REFERENCE = 4;

	const CHANGELOG_OPERATION_INSERTED = 0;
	const CHANGELOG_OPERATION_UPDATED = 1;
	const CHANGELOG_OPERATION_DELETED = 2;

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function getNumberOfChanges() {
		$number = $this->db->valueQuery('
			SELECT COUNT(*) AS `value`
			FROM `Changelog`
			WHERE `internal` = ' . ChangelogOperations::CHANGELOG_INTERNAL_FALSE);
		if ($number === false) {
			return false;
		}
		return $number['value'];
	}

	public function getChanges() {
		return $this->db->valuesQuery('
			SELECT *
			FROM `Changelog`
			WHERE `internal` = ' . ChangelogOperations::CHANGELOG_INTERNAL_FALSE . '
			ORDER BY `time` ASC');
	}

	public function getInternalChanges() {
		return $this->db->valuesQuery('
			SELECT *
			FROM `Changelog`
			WHERE `internal` = ' . ChangelogOperations::CHANGELOG_INTERNAL_TRUE . '
			ORDER BY `time` ASC');
	}

	public function addInternalChange($type, $operation, $recordId) {
		return $this->db->impactQuery('
			INSERT INTO `Changelog`
			(`internal`, `type`, `operation`, `recordId`, `time`)
			VALUES
			(?,?,?,?,NOW())',
			'iiii',
			ChangelogOperations::CHANGELOG_INTERNAL_TRUE, $type, $operation, $recordId);
	}

}

?>