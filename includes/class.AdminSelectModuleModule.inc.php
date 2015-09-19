<?php

class AdminSelectModuleModule extends BasicModule {

	private $modules;

	public function __construct(&$controller) {
		global $CMS_VERSION;
		parent::__construct($CMS_VERSION, 'admin-select-module');

		$this->loadModules();
	}

	public function printContent($config) {
		?>
		<div class="dialog-window">
			<h1><?php $this->text('MODULE_SELECTION'); ?></h1>
			<button class="lightbox-close" id="cancel-selection"><?php $this->text('CANCEL'); ?></button>
			<p><?php $this->text('SELECT_MODULE'); ?></p>
			<ul class="tableLike">
				<?php foreach ($this->modules as $module) : ?>
					<li class="rowLike">
						<h2><?php echo Utils::escapeString($module['name']); ?></h2>
						<?php if (isset($module['description'])) : ?>
							<p><?php echo $module['description']; ?></p>
						<?php endif; ?>
						<span class="rowAdditionalInfo">
							<button class="select-module"
								value="<?php echo Utils::escapeString($module['id']); ?>">
								<?php $this->text('ADD'); ?>
							</button>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	// --------------------------------------------------------------------------------------------

	private function loadModules() {
		$this->modules = RichModule::getLocalizedModulesList();
		if ($this->modules === false) {
			$this->modules = [];
		}
	}
}

?>