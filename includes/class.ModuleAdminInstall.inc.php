<?php

class ModuleAdminInstall extends BasicModule {

	private $state;

	public function __construct() {
		global $CMS_VERSION;
		parent::__construct($CMS_VERSION, "admin-install");
	}

	public function setState($state) {
		$this->state = $state;
	}

	public function getContent($config) {
		?>
<div class="dialog-box">
<?php if ($this->state === true) : ?>
	<div class="dialog-success-message">
		<?php $this->text('INSTALLATION_SUCCESS'); ?>
	</div>
<?php else : ?>
<?php if (!empty($this->state)) : ?>
	<div class="dialog-error-message">
		<?php $this->text($this->state); ?>
	</div>
<?php endif; ?>
	<div class="dialog-message">
		<?php $this->text('DATABASE_NOT_INITIALIZED'); ?>
	</div>
	<form action="<?php echo $config->getPublicRoot()?>/admin/install" method="post">
		<label for="password"><?php $this->text('NEW_PASSWORD'); ?></label>
		<input type="password" name="password" id="password" pattern=".{8,64}" required />
		<label for="password2"><?php $this->text('RETYPE_PASSWORD'); ?></label>
		<input type="password" name="password2" id="password2" pattern=".{8,64}" required />
		<input type="submit" value="<?php $this->text('CREATE_DATABASE'); ?>" />
	</form>
<?php endif; ?>
</div>
<?php
	}

}

?>