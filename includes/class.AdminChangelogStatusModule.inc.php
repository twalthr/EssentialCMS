<?php

class AdminChangelogStatusModule extends BasicModule {

	// member variables
	private $numberOfChanges;

	public function __construct($numberOfChanges) {
		parent::__construct(1, 'admin-changelog-status');
		$this->numberOfChanges = $numberOfChanges;
	}

	public function printContent($config) {
		?>
		<div class="dialog-notice-message">
			<?php $this->text("CHANGES_PENDING", $this->numberOfChanges); ?>
			<a class="goto" href="<?php echo $config->getPublicRoot(); ?>/admin/changes">
				<?php $this->text("PUBLISH_CHANGES"); ?>
			</a>
		</div>
		<?php
	}
}

?>