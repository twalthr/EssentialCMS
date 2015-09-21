<?php

final class ModuleOperations {

	private $db;
	private $fieldGroupOperations;

	public function __construct($db, $fieldGroupOperations) {
		$this->db = $db;
		$this->fieldGroupOperations = $fieldGroupOperations;
	}

	public function addModule($page, $section, $moduleId) {
		$nextOrder = $this->db->valueQuery('
			SELECT COUNT(*) AS `value`
			FROM `Modules`
			WHERE `page`=? AND `section`=?',
			'ii',
			$page, $section);
		if ($nextOrder === false) {
			return false;
		}
		$nextOrder = $nextOrder['value'];
		return $this->db->impactQueryWithId('
			INSERT INTO `Modules`
			(`page`, `section`, `order`, `module`)
			VALUES
			(?, ?, ?, ?)',
			'iiis',
			$page, $section, $nextOrder, $moduleId);
	}

	public function getModule($mid) {
		$module = $this->db->valueQuery('
			SELECT *
			FROM `Modules`
			WHERE `mid`=?',
			'i',
			$mid);
		if ($module === false) {
			return false;
		}
		return $module;
	}

	public function getModuleSections($page) {
		$sections = $this->db->valuesQuery('
			SELECT `section`
			FROM `Modules`
			WHERE `page`=?
			GROUP BY `section`',
			'i',
			$page);
		if ($sections === false || empty($sections)) {
			return false;
		}
		return $sections;
	}

	public function getModules($page, $section) {
		$modules = $this->db->valuesQuery('
			SELECT *
			FROM `Modules`
			WHERE `page`=? AND `section`=?
			ORDER BY `order` ASC',
			'ii',
			$page, $section);
		if ($modules === false || empty($modules)) {
			return false;
		}
		return $modules;
	}

	public function moveModuleWithinSection($mid, $newOrder) {
		$oldPosition = $this->db->valueQuery('
			SELECT `page`, `section`, `order`
			FROM `Modules`
			WHERE `mid`=?',
			'i',
			$mid);
		if ($oldPosition === false) {
			return false;
		}
		$page = $oldPosition['page'];
		$section = $oldPosition['section'];
		$oldOrder = $oldPosition['order'];

		// move to same position can be skipped
		if ($oldOrder === $newOrder) {
			return true;
		}

		$count = $this->db->valueQuery('
				SELECT COUNT(*) AS `value`
				FROM `Modules`
				WHERE `page`=? AND `section`=?',
				'ii',
				$page, $section);
		if ($count === false) {
			return false;
		}
		$count = $count['value'];

		$result = $this->db->impactQuery('
				UPDATE `Modules`
				SET `order`=?
				WHERE `mid`=?',
				'ii',
				$count, $mid);

		// based on
		// http://stackoverflow.com/questions/8607998/using-a-sort-order-column-in-a-database-table
		if ($newOrder < $oldOrder) {
			$result = $result && $this->db->impactQuery('
				UPDATE `Modules`
				SET `order` = `order` + 1
				WHERE `page`=? AND `section`=? AND `order` BETWEEN ? AND ?
				ORDER BY `order` DESC',
				'iiii',
				$page, $section, $newOrder, $oldOrder);
		}

		if ($newOrder > $oldOrder) {
			$result = $result && $this->db->impactQuery('
				UPDATE `Modules`
				SET `order` = `order` - 1
				WHERE `page`=? AND `section`=? AND `order` BETWEEN ? AND ?
				ORDER BY `order` ASC',
				'iiii',
				$page, $section, $oldOrder, $newOrder);
		}

		$result = $result && $this->db->impactQuery('
				UPDATE `Modules`
				SET `order`=?
				WHERE `mid`=?',
				'ii',
				$newOrder, $mid);

		return $result;
	}

	public function copyModuleWithinSection($mid, $newOrder) {
		$module = $this->getModule($mid);
		if ($module === false) {
			return false;
		}
		$newMid = $this->addModule($module['page'], $module['section'], $module['module']);
		if ($newMid === false) {
			return false;
		}
		$result = $this->moveModuleWithinSection($newMid, $newOrder);
		return $result && $this->fieldGroupOperations->copyFieldGroups($mid, $newMid);
	}

	public function deleteModule($mid) {
		$module = $this->db->valueQuery('
			SELECT `page`, `section`, `order`
			FROM `Modules`
			WHERE `mid`=?',
			'i',
			$mid);
		if ($module === false) {
			return false;
		}
		$result = $this->fieldGroupOperations->deleteFieldGroups($mid);

		$result = $result && $this->db->impactQuery('
			DELETE FROM `Modules`
			WHERE `mid`=?',
			'i',
			$mid);

		return $result && $this->db->successQuery('
			UPDATE `Modules`
				SET `order` = `order` - 1
				WHERE `page`=? AND `section`=? AND `order`>?
				ORDER BY `order` ASC',
				'iii',
				$module['page'], $module['section'], $module['order']);
	}
}

?>