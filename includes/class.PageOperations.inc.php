<?php

final class PageOperations {

	const PAGES_OPTION_PRIVATE = 1;

	private $db;
	private $moduleOperations;

	public function __construct($db, $moduleOperations) {
		$this->db = $db;
		$this->moduleOperations = $moduleOperations;
	}

	public function getPages() {
		return $this->db->valuesQuery('
			SELECT `pid`, `title`, `hoverTitle`, `options`, `externalId`
			FROM `Pages`
			ORDER BY `pid` ASC');
	}

	public function addPage($title, $hoverTitle, $externalId, $options, $externalLastChanged) {
		return $this->db->impactQueryWithId('
			INSERT INTO `Pages`
			(`title`, `hoverTitle`, `externalId`, `options`, `lastChanged`, `externalLastChanged`)
			VALUES
			(?,?,?,?,NOW(),?)',
			'sssis',
			$title, $hoverTitle, $externalId, $options, $externalLastChanged);
	}

	public function updatePage($pid, $updateColumns) {
		// no update
		if (count($updateColumns) === 0) {
			return true;
		}
		$query = '
			UPDATE `Pages`
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
			$values[] = &$value;
		}
		$query = rtrim($query, ', ');
		$query .= ' WHERE `pid`=?';
		$types .= 'i';
		$values[] = &$pid;
		return $this->db->impactQuery($query, $types, ...$values);
	}

	public function getPage($pid) {
		return $this->db->valueQuery('
				SELECT `pid`, `title`, `hoverTitle`, `externalId`, 
					`options`, `lastChanged`, `externalLastChanged`
				FROM `Pages`
				WHERE `pid`=?',
				'i',
				$pid);
	}

	public function getPageNames() {
		return $this->db->valuesQuery('
			SELECT `pid`, `title`, `hoverTitle`
			FROM `Pages`');
	}

	public function isValidPageId($pid) {
		return $this->db->resultQuery('
			SELECT `pid` FROM `Pages`
			WHERE `pid`=?',
			'i',
			$pid);
	}

	public function isValidExternalId($externalId) {
		return $this->db->resultQuery('
			SELECT `pid`
			FROM `Pages`
			WHERE `externalId`=?',
			's',
			$externalId);
	}

}

?>