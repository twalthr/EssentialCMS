<?php

class AdminEditModuleModule extends BasicModule {

	// database operations
	private $moduleOperations;
	private $fieldGroupOperations;
	private $fieldOperations;

	// UI state
	private $state;
	private $message;

	// member variables
	private $module;
	private $moduleInfo; // translated name and description
	private $moduleDefinition; // instance of RichModule

	public function __construct(
			$moduleOperations, $fieldGroupOperations, $fieldOperations, $parameters = null) {
		parent::__construct(1, 'admin-edit-module');
		$this->moduleOperations = $moduleOperations;
		$this->fieldGroupOperations = $fieldGroupOperations;
		$this->fieldOperations = $fieldOperations;

		// module id is present
		if (isset($parameters)
			&& count($parameters) > 0) {
			$this->loadModule($parameters[0]);
		}
		// if module is present, load module info
		if (isset($this->module)) {
			$this->loadModuleInfo();
		}
		// if module info is present, load module definition
		if (isset($this->moduleInfo)) {
			$this->loadModuleDefinition();
		}
	}

	public function printContent($config) {
		?>
		<?php if (isset($this->moduleDefinition)) : ?>
			<script type="text/javascript">
				$(document).ready(function() {
					$('#editModuleOptions').click(function() {
						window.open('<?php echo $config->getPublicRoot(); ?>/admin/module-options/<?php
							echo $this->module['mid']; ?>', '_self');
					});
					$('#editModuleCancel').click(function() {
						window.open('<?php echo $config->getPublicRoot(); ?>/admin/page/<?php
							echo $this->module['page']; ?>', '_self');
					});
				});
			</script>
		<?php endif; ?>
		<?php if (isset($this->state)) : ?>
			<?php if ($this->state === true) : ?>
				<div class="dialog-success-message">
					<?php $this->text($this->message); ?>
				</div>
			<?php else: ?>
				<div class="dialog-error-message">
					<?php $this->text($this->message); ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<?php if (isset($this->moduleDefinition)) : ?>
			<section>
					<h1>
						<?php $this->text('MODULE'); ?>
						<?php echo Utils::escapeString($this->moduleInfo['name']); ?>
					</h1>
					<div class="buttonSet general">
						<button id="editModuleOptions">
							<?php $this->text('EDIT_MODULE_CONFIG'); ?>
						</button>
						<button id="editModuleCancel">
							<?php $this->text('CANCEL'); ?>
						</button>
					</div>
			</section>
			<?php $this->printFieldGroups($config); ?>
		<?php endif; ?>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	private function printFieldGroups($config) {
		$fieldGroups = $this->moduleDefinition->getFieldGroupInfo();
		echo '<div class="fieldGroups">';
		foreach ($fieldGroups as &$fieldGroup) {
			$fieldGroupContent = false;
			if ($fieldGroup->isOnePagePerGroup()) {
				$fieldGroupContent = $this->fieldGroupOperations->getFieldGroupsWithTitle(
					$this->module['mid'], $fieldGroup->getKey());
			}
			else {
				$fieldGroupContent = $this->fieldGroupOperations->getFieldGroups(
					$this->module['mid'], $fieldGroup->getKey());
			}
			if ($fieldGroupContent === false) {
				continue;
			}
			$this->printFieldGroupSection($fieldGroup, $fieldGroupContent, $config);
		}
		echo '</div>';
	}

	private function printFieldGroupSection($fieldGroup, $fieldGroupContent, $config) {
		?>
		<form method="post">
			<section>
				<h1>
					<?php if ($fieldGroup->getMaxNumberOfGroups() === 1) : ?>
						<?php $this->moduleDefinition->text($fieldGroup->getName()); ?>
					<?php else : ?>
						<?php $this->moduleDefinition->text($fieldGroup->getNamePlural()); ?>
					<?php endif; ?>
				</h1>
				<?php if ($fieldGroup->getMaxNumberOfGroups() === null
						|| count($fieldGroupContent) < $fieldGroup->getMaxNumberOfGroups()) : ?>
					<div class="buttonSet general">
						<button id="editModuleOptions">
							<?php $this->text('ADD_FIELDGROUP',
									Utils::escapeString($fieldGroup->getName())); ?>
						</button>
					</div>
				<?php endif; ?>
				<?php if (count($fieldGroupContent) === 0) : ?>
					<p class="empty">
						<?php $this->text('NO_FIELDGROUP',
								Utils::escapeString($fieldGroup->getNamePlural())) ?>
					</p>;
				<?php endif; ?>
				<?php if ($fieldGroup->isOnePagePerGroup()) : ?>
					<ul class="tableLike enableButtonsIfChecked">
						<?php foreach ($fieldGroupContent as &$content) : ?>
							<li class="rowLike">
								<input type="checkbox" id="fieldGroup<?php echo $content['fgid']; ?>"
										name="fieldGroup[]"
										value="<?php echo $content['fgid']; ?>" />
								<label for="fieldGroup<?php echo $content['fgid']; ?>"
										class="checkbox">
									<?php echo Utils::escapeString($content['title']); ?>
								</label>
								<a href="<?php echo $config->getPublicRoot(); ?>/admin/field-group/<?php 
										echo $content['fgid']; ?>"
										<?php if (isset($content['private']) 
												&& $content['private'] === '1') : ?>
											class="private componentLink"
										<?php else : ?>
											class="componentLink"
										<?php endif; ?>
										>
									<?php echo Utils::escapeString($content['title']); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
					<div class="buttonSet">
						<?php if ($fieldGroup->hasOrder()) : ?>
							<button class="moveModule disableListIfClicked"
								value="<?php echo $fieldGroup->getKey(); ?>" disabled>
								<?php $this->text('MOVE'); ?>
							</button>
						<?php endif; ?>
						<button class="copyModule disableListIfClicked"
							value="<?php echo $fieldGroup->getKey(); ?>" disabled>
							<?php $this->text('COPY'); ?>
						</button>
						<button class="exportModule"
							value="<?php echo $fieldGroup->getKey(); ?>" disabled>
							<?php $this->text('EXPORT'); ?>
						</button>
						<button class="deleteModule disableListIfClicked"
							value="<?php echo $fieldGroup->getKey(); ?>" disabled>
							<?php $this->text('DELETE'); ?>
						</button>
					</div>
					<div class="dialog-box hidden">
						<div class="dialog-message"></div>
						<div class="fields">
							
						</div>
						<div class="options">
							<button class="hidden copyConfirm"><?php $this->text('COPY'); ?></button>
							<button class="hidden moveConfirm"><?php $this->text('MOVE'); ?></button>
							<button class="hidden deleteConfirm"><?php $this->text('DELETE'); ?></button>
							<button class="hidden cancel"><?php $this->text('CANCEL'); ?></button>
						</div>
					</div>
				<?php else : ?>
					<?php $fieldGroup->printFieldsWithLabel(); ?>
				<?php endif; ?>
			</section>
		</form>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	

	// --------------------------------------------------------------------------------------------
	// Database loading methods
	// --------------------------------------------------------------------------------------------

	private function loadModule($moduleId) {
		if (!Utils::isValidInt($moduleId)) {
			$this->state = false;
			$this->message = 'MODULE_NOT_FOUND';
			return;
		}
		$module = $this->moduleOperations->getModule($moduleId);
		if ($module === false) {
			$this->state = false;
			$this->message = 'MODULE_NOT_FOUND';
			return;
		}
		$this->module = $module;
	}

	private function loadModuleInfo() {
		if (!RichModule::isValidModuleDefinitionId($this->module['definitionId'])) {
			$this->state = false;
			$this->message = 'MODULE_DEFINITION_INVALID';
			return;
		}
		$this->moduleInfo = RichModule::getLocalizedModuleInfo($this->module['definitionId']);
	}

	private function loadModuleDefinition() {
		$moduleDefinition = RichModule::loadModuleDefinition($this->module['definitionId']);
		if ($moduleDefinition === false) {
			$this->state = false;
			$this->message = 'MODULE_DEFINITION_INVALID';
			return;
		}
		$this->moduleDefinition = $moduleDefinition;
	}
}

?>