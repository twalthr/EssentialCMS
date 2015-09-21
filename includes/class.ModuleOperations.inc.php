<?php

final class ModuleOperations {

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function addModule($page, $section, $moduleId) {
		$nextOrder = $this->db->valueQuery('
			SELECT COALESCE(MAX(`order`), -1) + 1 AS `value`
			FROM `Modules`
			WHERE `page`=? AND `section`=?',
			'ii',
			$page, $section);
		if ($nextOrder === false) {
			return false;
		}
		return $this->db->impactQuery('
			INSERT INTO `Modules`
			(`page`, `section`, `order`, `module`)
			VALUES
			(?, ?, ?, ?)',
			'iiis',
			$page, $section, $nextOrder['value'], $moduleId);
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

	public function moveModuleWithinSection($module, $newOrder = -1) {
		$oldPosition = $this->db->valueQuery('
			SELECT `page`, `section`, `order`
			FROM `Modules`
			WHERE `mid`=?',
			'i',
			$module);
		if ($oldPosition === false) {
			return false;
		}
		// move to same position can be skipped
		if ($oldPosition['order'] === $newOrder) {
			return true;
		}
		// move to the end
		else if ($newOrder < 0) {
			$count = $this->db->valueQuery('
				SELECT COUNT(*) AS `value`
				FROM `Modules`
				WHERE `page`=? AND `section`=?',
				'ii',
				$oldPosition['page'], $oldPosition['section']);
			if ($count === false) {
				return false;
			}
			$newOrder = $count['value'];
		}

		// move all modules below new position downwards
		// make a gap
		$result = $this->db->successQuery('
				UPDATE `Modules`
				SET `order` = `order` + 1
				WHERE `page`=? AND `section`=? AND `order`>=?
				ORDER BY `order` DESC',
				'iii',
				$oldPosition['page'], $oldPosition['section'], $newOrder);

		// move module to gap/new position
		// gap at old position accrues
		$result =  $result && $this->db->impactQuery('
				UPDATE `Modules`
				SET `order`=?
				WHERE `mid`=?',
				'ii',
				$newOrder, $module);
		// update all modules below the gap because of movement until new postion 
		// and below new position
		$result =  $result && $this->db->successQuery('
				UPDATE `Modules`
				SET `order` = `order` - 1
				WHERE `page`=? AND `section`=? AND ((`order`>? AND `order`<=?) OR `order`>?)
				ORDER BY `order` ASC',
				'iiiii',
				$oldPosition['page'], $oldPosition['section'], $oldPosition['order'], $newOrder, $newOrder);
		return $result;
	}
}

?>