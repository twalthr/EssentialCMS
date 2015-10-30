<?php

class AdminEditFieldGroupModule extends BasicModule {

	// database operations
	private $moduleOperations;
	private $fieldGroupOperations;
	private $fieldOperations;

	// UI state
	private $state;
	private $message;

	// member variables

	public function __construct(
			$moduleOperations, $fieldGroupOperations, $fieldOperations, $parameters = null) {
		parent::__construct(1, 'admin-edit-field-group');
		$this->moduleOperations = $moduleOperations;
		$this->fieldGroupOperations = $fieldGroupOperations;
		$this->fieldOperations = $fieldOperations;

		// fieldGroup is present
		if (isset($parameters)
				&& count($parameters) == 1) {
			$this->loadFieldGroup($parameters[0]);
		}
		// new fieldGroup
		else if (isset($parameters)
				&& count($parameters) > 1) {
			$this->loadModuleWithFieldGroupInfo($parameters[0], $parameters[1]);
		}
	}

	public function printContent($config) {
		?>
		<?php if (!empty($this->state) && isset($this->createdPageId)) : ?>
			<div class="dialog-box">
				<div class="dialog-success-message">
				<?php $this->text('PAGE_CREATED'); ?>
			</div>
			<a href="<?php echo $config->getPublicRoot(); ?>/admin/page/<?php echo $this->createdPageId; ?>"
				class="goto"><?php $this->text('GOTO_PAGE'); ?></a>
			</div>
		<?php return; ?>
		<?php else: ?>
			<script type="text/javascript">
				$(document).ready(function() {
					$('#editPageCancel').click(function() {
						window.open('<?php echo $config->getPublicRoot(); ?>/admin/pages', '_self');
					});
					$('#pageDirectAccess').change(function() {
						var externalId = $('#externalId');
						externalId.prop('disabled', !$(this).prop('checked'));
						if (!externalId.prop('disabled') && externalId.val().length == 0)  {
							externalId.val(generateIdentifierFromString($('#title').val()));
						}
					});
					$('#pageDirectAccess').trigger('change');
					$('#pageCustomLastChange').change(function() {
						var externalLastChanged = $('#externalLastChanged');
						externalLastChanged.prop('disabled', !$(this).prop('checked'));
						if (!externalLastChanged.prop('disabled') && externalLastChanged.val().length == 0) {
							externalLastChanged.val(generateDate());
						}
					});
					$('#pageCustomLastChange').trigger('change');

					<?php if (isset($this->page)) : ?>
					$('#editPage').click(function() {
						$('.showInEditMode').removeClass('hidden');
						$('.hiddenInEditMode').remove();
					});
					$('.addButton').click(function(e) {
						var form = $(this).parents('form');
						var lightboxOpened = function() {
							$('.dialog-window .selectModule').click(function() {
								form.find('[name="operation"]').val('add');
								form.find('[name="operationParameter1"]').val($(this).val());
								form.submit();
							});
						};
						openLightboxWithUrl(
							'<?php echo $config->getPublicRoot(); ?>/admin/select-module-dialog',
							true,
							lightboxOpened);
					});				
					$('.moveModule').click(function() {
						var form = $(this).parents('form');
						form.find('[name="operation"]').val('move');
						openButtonSetDialog($(this),
							'<?php $this->text('SELECT_MOVE_TARGET'); ?>',
							'.moduleTarget, .moveConfirm');
					});
					$('.copyModule').click(function() {
						var form = $(this).parents('form');
						form.find('[name="operation"]').val('copy');
						openButtonSetDialog($(this),
							'<?php $this->text('SELECT_COPY_TARGET'); ?>',
							'.moduleTarget, .copyConfirm');
					});
					$('.exportModule').click(function() {
						var form = $(this).parents('form');
						var lightboxOpened = function() {
							$('.dialog-box #exportConfirm').click(function() {
								form.find('[name="operation"]').val('export');
								form.find('[name="operationParameter1"]')
									.val($('.dialog-box #exportTargetSection').val());
								form.find('[name="operationParameter2"]')
									.val($('.dialog-box #exportTargetPage').val());
								form.submit();
							});
						};
						openLightboxWithUrl(
							'<?php echo $config->getPublicRoot(); ?>/admin/export-module-dialog',
							true,
							lightboxOpened);
					});
					$('.deleteModule').click(function() {
						var form = $(this).parents('form');
						form.find('[name="operation"]').val('delete');
						openButtonSetDialog($(this),
							'<?php $this->text('DELETE_QUESTION'); ?>',
							'.deleteConfirm');
					});				
					$('.moveConfirm').click(function() {
						var form = $(this).parents('form');
						enableList($(this));
						form.submit();
					});
					$('.copyConfirm').click(function() {
						var form = $(this).parents('form');
						enableList($(this));
						form.submit();
					});
					$('.deleteConfirm').click(function() {
						var form = $(this).parents('form');
						enableList($(this));
						form.submit();
					});
					<?php endif; ?>

					<?php if (isset($this->page) && isset($this->state)) : ?>
						$('#editPage').trigger('click');
					<?php endif; ?>
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
		
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// Printing methods
	// --------------------------------------------------------------------------------------------

	

	// --------------------------------------------------------------------------------------------
	// User input handling methods
	// --------------------------------------------------------------------------------------------

	

	// --------------------------------------------------------------------------------------------
	// Database loading methods
	// --------------------------------------------------------------------------------------------

	
}

?>