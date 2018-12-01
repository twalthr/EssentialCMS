<?php

final class PageOperations {

	const PAGES_OPTION_PRIVATE = 1;

	private $db;
	private $moduleOperations;
	private $changelogOperations;

	public function __construct($db, $moduleOperations, $changelogOperations) {
		$this->db = $db;
		$this->moduleOperations = $moduleOperations;
		$this->changelogOperations = $changelogOperations;
	}

	public function makePagePublic($pid) {
		$result = $this->db->successQuery('
			UPDATE `Pages`
			SET `options` = `options` & ~' . PageOperations::PAGES_OPTION_PRIVATE . '
			WHERE `pid`=?',
			'i',
			$pid);

		// publish page update
		$result = $result && $this->changelogOperations->addChange(
			ChangelogOperations::CHANGELOG_TYPE_PAGE,
			ChangelogOperations::CHANGELOG_OPERATION_UPDATED,
			$pid,
			null);

		return $result;
	}

	public function makePagePrivate($pid) {
		$result = $this->db->successQuery('
			UPDATE `Pages`
			SET `options` = `options` | ' . PageOperations::PAGES_OPTION_PRIVATE . '
			WHERE `pid`=?',
			'i',
			$pid);

		// publish page update
		$result = $result && $this->changelogOperations->addChange(
			ChangelogOperations::CHANGELOG_TYPE_PAGE,
			ChangelogOperations::CHANGELOG_OPERATION_UPDATED,
			$pid,
			null);

		return $result;
	}

	public function getPages() {
		return $this->db->valuesQuery('
			SELECT `pid`, `title`, `hoverTitle`, `options`, `externalId`
			FROM `Pages`
			ORDER BY `pid` ASC');
	}

	public function addPage($title, $hoverTitle, $externalId, $options, $externalLastChanged) {
		$pid = $this->db->impactQueryWithId('
			INSERT INTO `Pages`
			(`title`, `hoverTitle`, `externalId`, `options`, `lastChanged`, `externalLastChanged`)
			VALUES
			(?,?,?,?,NOW(),?)',
			'sssis',
			$title, $hoverTitle, $externalId, $options, $externalLastChanged);
		if ($pid === false) {
			return false;
		}

		// publish page
		$result = $this->changelogOperations->addChange(
			ChangelogOperations::CHANGELOG_TYPE_PAGE,
			ChangelogOperations::CHANGELOG_OPERATION_UPDATED,
			$pid,
			$title);
		if ($result === false) {
			return false;
		}

		return $pid;
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
			$values[] = $value;
		}
		$query = rtrim($query, ', ');
		$query .= ' WHERE `pid`=?';
		$types .= 'i';
		$values[] = $pid;

		$result = $this->db->impactQuery($query, $types, ...$values);

		if ($result === false) {
			return false;
		}

		$title = $this->getPageTitle($pid);
		if ($title === false) {
			return false;
		}

		// publish page update
		$result = $this->changelogOperations->addChange(
			ChangelogOperations::CHANGELOG_TYPE_PAGE,
			ChangelogOperations::CHANGELOG_OPERATION_UPDATED,
			$pid,
			$title);

		return $result;
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

	public function getPageTitle($pid) {
		$result = $this->db->valueQuery('
			SELECT `title`
			FROM `Pages`
			WHERE `pid`=?',
			'i',
			$pid);
		if ($result === false) {
			return false;
		}
		return $result['title'];
	}

	public function copyPage($pid) {
		$page = $this->getPage($pid);
		if ($page === false) {
			return false;
		}

		// if externalId already exists -> try externalId with suffix
		$externalId = $page['externalId'];
		if ($externalId !== null) {
			$externalId = $externalId . '_' . uniqid();
			$duplicates = $this->db->valueQuery('
				SELECT COUNT(*) AS `value`
				FROM `Pages`
				WHERE `externalId`=?',
				's',
				$externalId);
			// also not possible -> copy failed
			if ($duplicates === false || $duplicates['value'] > 0) {
				return false;
			}
		}

		// add page
		$newPid = $this->addPage($page['title'], $page['hoverTitle'], $externalId, $page['options'],
			$page['externalLastChanged']);
		if ($newPid === false) {
			return false;
		}

		// publish page
		$result = $this->changelogOperations->addChange(
			ChangelogOperations::CHANGELOG_TYPE_PAGE,
			ChangelogOperations::CHANGELOG_OPERATION_UPDATED,
			$newPid,
			$page['title']);
		if ($result === false) {
			return false;
		}

		// copy modules
		return $this->moduleOperations->copyModules($pid, $newPid);
	}

	public function deletePage($pid) {
		// delete modules then page
		$result = $this->moduleOperations->deleteModules($pid)
			&& $this->db->successQuery('
				DELETE FROM `Pages`
				WHERE `pid`=?',
				'i',
				$pid);

		// publish page delete
		$result = $result && $this->changelogOperations->addChange(
			ChangelogOperations::CHANGELOG_TYPE_PAGE,
			ChangelogOperations::CHANGELOG_OPERATION_DELETED,
			$pid,
			null);

		return $result;
	}

}

?>