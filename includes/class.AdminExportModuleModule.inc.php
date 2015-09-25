<?php

class AdminExportModuleModule extends BasicModule {

	// database operations
	private $pageOperations;

	// member variables
	private $pages;

	public function __construct($pageOperations, $parameters = null) {
		global $CMS_VERSION;
		parent::__construct($CMS_VERSION, 'admin-export-module');
		$this->pageOperations = $pageOperations;

		$this->loadPages();
	}

	public function printContent($config) {
		?>
		<script type="text/javascript">
			$(document).ready(function() {
				$('#exportTargetSection').change(function() {
					var select = $(this).val();
					if (select === 'empty' || select.indexOf('global') === 0) {
						$('#exportTargetSectionField').addClass('hidden');
					}
					else {
						$('#exportTargetSectionField').removeClass('hidden');
					}
				});
			});
		</script>
		<div class="dialog-box">
			<h1><?php $this->text('MODULE_EXPORT'); ?></h1>
			<div class="dialog-message">
				<?php $this->text('EXPORT_MODULE'); ?>
			</div>
			<div class="fields">
				<div class="field">
					<label for="exportTargetSection"><?php $this->text('TARGET_SECTION'); ?></label>
					<?php $this->printSectionListAsSelect(); ?>
				</div>
				<div class="field hidden" id="exportTargetSectionField">
					<label for="exportTargetPage"><?php $this->text('TARGET_PAGE'); ?></label>
					<?php $this->printPageListAsSelect(); ?>
				</div>
			</div>
			<div class="options">
				<button id="exportConfirm"><?php $this->text('EXPORT'); ?></button>
				<button class="lightbox-close"><?php $this->text('CANCEL'); ?></button>
			</div>
		</div>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function printSectionListAsSelect() {
		echo '<select id="exportTargetSection">';
		echo '	<option value="empty" selected>';
		$this->text('PLEASE_SELECT');
		echo '	</option>';
		echo '	<optgroup label="';
		$this->text('PAGE_SECTIONS');
		echo '">';
		foreach (ModuleOperations::MODULES_SECTIONS as $section) {
			if (!ModuleOperations::isGlobalSection($section)) {
				echo '<option value="' . ModuleOperations::translateToSectionString($section) . '">';
				$this->text(ModuleOperations::translateSectionToLocale($section));
				echo '</option>';
			}
		}
		echo '	</optgroup>';
		echo '	<optgroup label="';
		$this->text('GLOBAL_SECTIONS');
		echo '">';
		foreach (ModuleOperations::MODULES_SECTIONS as $section) {
			if (ModuleOperations::isGlobalSection($section)) {
				echo '<option value="' . ModuleOperations::translateToSectionString($section) . '">';
				$this->text(ModuleOperations::translateSectionToLocale($section));
				echo '</option>';
			}
		}
		echo '	</optgroup>';
		echo '</select>';
	}

	private function printPageListAsSelect() {
		echo '<select id="exportTargetPage">';
		echo '	<option value="empty" selected>';
		$this->text('PLEASE_SELECT');
		echo '	</option>';
		if (isset($this->pages)) {
			foreach ($this->pages as $page) {
				echo '<option value="' . $page['pid'] . '"';
				if (Utils::hasStringContent($page['hoverTitle'])) {
					echo ' title="' . Utils::escapeString($page['hoverTitle']) . '"';
				}
				echo '>';
				echo Utils::escapeString($page['title']);
				echo '</option>';
			}
		}
		echo '</select>';
	}

	// --------------------------------------------------------------------------------------------
	// Database loading methods
	// --------------------------------------------------------------------------------------------

	private function loadPages() {
		$pages = $this->pageOperations->getPageNames();
		if ($pages !== false) {
			$this->pages = $pages;
		}
	}
}

?>