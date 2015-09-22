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
				<div class="field">
					<label for="exportTargetPage"><?php $this->text('TARGET_PAGE'); ?></label>
					<?php $this->printPageListAsSelect(); ?>
				</div>
			</div>
			<div class="options">
				<button id="exportConfirm"><?php $this->text('EXPORT'); ?></button>
				<button class="lightbox-close" id="cancelExport"><?php $this->text('CANCEL'); ?></button>
			</div>
		</div>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function printSectionListAsSelect() {
		echo '<select id="exportTargetSection">';
		echo '	<optgroup label="' . $this->text('PAGE_SECTIONS') . '">';
		echo '	</optgroup>';
		echo '	<optgroup label="' . $this->text('GLOBAL_SECTIONS') . '">';
		echo '	</optgroup>';
		echo '</select>';
	}

	private function printPageListAsSelect() {

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