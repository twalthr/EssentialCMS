<?php

final class ModuleOperations {

	const MODULES_SECTION_GLOBAL_PRE_CONTENT = 1;
	const MODULES_SECTION_GLOBAL_CONTENT = 2;
	const MODULES_SECTION_GLOBAL_ASIDE_CONTENT = 4;
	const MODULES_SECTION_GLOBAL_POST_CONTENT = 8;
	const MODULES_SECTION_GLOBAL_LOGO = 16;
	const MODULES_SECTION_GLOBAL_ASIDE_HEADER = 32;
	const MODULES_SECTION_GLOBAL_FOOTER = 64;
	const MODULES_SECTION_PRE_CONTENT = 128;
	const MODULES_SECTION_CONTENT = 256;
	const MODULES_SECTION_ASIDE_CONTENT = 512;
	const MODULES_SECTION_POST_CONTENT = 1024;

	const MODULES_SECTIONS = [
		ModuleOperations::MODULES_SECTION_GLOBAL_PRE_CONTENT,
		ModuleOperations::MODULES_SECTION_GLOBAL_CONTENT,
		ModuleOperations::MODULES_SECTION_GLOBAL_ASIDE_CONTENT,
		ModuleOperations::MODULES_SECTION_GLOBAL_POST_CONTENT,
		ModuleOperations::MODULES_SECTION_GLOBAL_LOGO,
		ModuleOperations::MODULES_SECTION_GLOBAL_ASIDE_HEADER,
		ModuleOperations::MODULES_SECTION_GLOBAL_FOOTER,
		ModuleOperations::MODULES_SECTION_PRE_CONTENT,
		ModuleOperations::MODULES_SECTION_CONTENT,
		ModuleOperations::MODULES_SECTION_ASIDE_CONTENT,
		ModuleOperations::MODULES_SECTION_POST_CONTENT];

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

	public function moveModuleBetweenSections($mid, $section, $pid = null) {
		$oldPosition = $this->db->valueQuery('
			SELECT `page`, `section`, `order`
			FROM `Modules`
			WHERE `mid`=?',
			'i',
			$mid);
		if ($oldPosition === false) {
			return false;
		}

		$nextOrder = null;
		if ($pid === null) {
			$nextOrder = $this->db->valueQuery('
				SELECT COUNT(*) as `value`
				FROM `Modules`
				WHERE `page` IS NULL AND `section`=?',
				'i',
				$section);
		}
		else {
			$nextOrder = $this->db->valueQuery('
				SELECT COUNT(*) as `value`
				FROM `Modules`
				WHERE `page`=? AND `section`=?',
				'ii',
				$pid, $section);
		}
		if ($nextOrder === false) {
			return false;
		}
		$nextOrder = $nextOrder['value'];

		$result = $this->db->impactQuery('
				UPDATE `Modules`
				SET `page`=?, `section`=?, `order`=?
				WHERE `mid`=?',
				'iiii',
				$pid, $section, $nextOrder, $mid);

		return $result
			&& $this->db->successQuery('
				UPDATE `Modules`
				SET `order` = `order` - 1
				WHERE `page`=? AND `section`=? AND `order`>?',
				'iii',
				$oldPosition['page'], $oldPosition['section'], $oldPosition['order']);
	}

	// --------------------------------------------------------------------------------------------
	// Static helper methods
	// --------------------------------------------------------------------------------------------

	public static function translateSectionToLocale($section) {
		switch ($section) {
			case ModuleOperations::MODULES_SECTION_GLOBAL_PRE_CONTENT:
				return 'MODULES_SECTION_GLOBAL_PRE_CONTENT';
			case ModuleOperations::MODULES_SECTION_GLOBAL_CONTENT:
				return 'MODULES_SECTION_GLOBAL_CONTENT';
			case ModuleOperations::MODULES_SECTION_GLOBAL_ASIDE_CONTENT:
				return 'MODULES_SECTION_GLOBAL_ASIDE_CONTENT';
			case ModuleOperations::MODULES_SECTION_GLOBAL_POST_CONTENT:
				return 'MODULES_SECTION_GLOBAL_POST_CONTENT';
			case ModuleOperations::MODULES_SECTION_GLOBAL_LOGO:
				return 'MODULES_SECTION_GLOBAL_LOGO';
			case ModuleOperations::MODULES_SECTION_GLOBAL_ASIDE_HEADER:
				return 'MODULES_SECTION_GLOBAL_ASIDE_HEADER';
			case ModuleOperations::MODULES_SECTION_GLOBAL_FOOTER:
				return 'MODULES_SECTION_GLOBAL_FOOTER';
			case ModuleOperations::MODULES_SECTION_PRE_CONTENT:
				return 'MODULES_SECTION_PRE_CONTENT';
			case ModuleOperations::MODULES_SECTION_CONTENT:
				return 'MODULES_SECTION_CONTENT';
			case ModuleOperations::MODULES_SECTION_ASIDE_CONTENT:
				return 'MODULES_SECTION_ASIDE_CONTENT';
			case ModuleOperations::MODULES_SECTION_POST_CONTENT:
				return 'MODULES_SECTION_POST_CONTENT';
			default:
				throw new Exception('Unknown modules section.');
		}
	}

	public static function translateSectionString($section) {
		switch ($section) {
			case 'globalPreContent': return ModuleOperations::MODULES_SECTION_GLOBAL_PRE_CONTENT;
			case 'globalContent': return ModuleOperations::MODULES_SECTION_GLOBAL_CONTENT;
			case 'globalAsideContent': return ModuleOperations::MODULES_SECTION_GLOBAL_ASIDE_CONTENT;
			case 'globalPostContent': return ModuleOperations::MODULES_SECTION_GLOBAL_POST_CONTENT;
			case 'globalLogo': return ModuleOperations::MODULES_SECTION_GLOBAL_LOGO;
			case 'globalAsideHeader': return MODULES_SECTION_GLOBAL_ASIDE_HEADER;
			case 'globalFooter': return ModuleOperations::MODULES_SECTION_GLOBAL_FOOTER;
			case 'preContent': return ModuleOperations::MODULES_SECTION_PRE_CONTENT;
			case 'content': return ModuleOperations::MODULES_SECTION_CONTENT;
			case 'asideContent': return ModuleOperations::MODULES_SECTION_ASIDE_CONTENT;
			case 'postContent': return ModuleOperations::MODULES_SECTION_POST_CONTENT;
			default: return false;
		}
	}

	public static function translateToSectionString($section) {
		switch ($section) {
			case ModuleOperations::MODULES_SECTION_GLOBAL_PRE_CONTENT: return 'globalPreContent';
			case ModuleOperations::MODULES_SECTION_GLOBAL_CONTENT: return 'globalContent';
			case ModuleOperations::MODULES_SECTION_GLOBAL_ASIDE_CONTENT: return 'globalAsideContent';
			case ModuleOperations::MODULES_SECTION_GLOBAL_POST_CONTENT: return 'globalPostContent';
			case ModuleOperations::MODULES_SECTION_GLOBAL_LOGO: return 'globalLogo';
			case ModuleOperations::MODULES_SECTION_GLOBAL_ASIDE_HEADER: return 'globalAsideHeader';
			case ModuleOperations::MODULES_SECTION_GLOBAL_FOOTER: return 'globalFooter';
			case ModuleOperations::MODULES_SECTION_PRE_CONTENT: return 'preContent';
			case ModuleOperations::MODULES_SECTION_CONTENT: return 'content';
			case ModuleOperations::MODULES_SECTION_ASIDE_CONTENT: return 'asideContent';
			case ModuleOperations::MODULES_SECTION_POST_CONTENT: return 'postContent';
			default: return false;
		}
	}

	public static function isStringValidPageSection($sectionString) {
		$section = ModuleOperations::translateSectionString($sectionString);
		return $section !== false && $section > ModuleOperations::MODULES_SECTION_GLOBAL_FOOTER;
	}

	public static function isStringValidSection($sectionString) {
		$section = ModuleOperations::translateSectionString($sectionString);
		return $section !== false;
	}

	public static function isGlobalSection($section) {
		return $section <= ModuleOperations::MODULES_SECTION_GLOBAL_FOOTER;
	}
}

?>