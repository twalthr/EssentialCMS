<?php

class AdminLoginModule extends BasicModule {

	private $state;

	public function __construct() {
		global $CMS_VERSION;
		parent::__construct($CMS_VERSION, 'admin-login');
	}

	public function setState($state) {
		$this->state = $state;
	}

	public function printContent($config) {
		?>
<div class="dialog-box">
<?php if (!empty($this->state)) : ?>
	<div class="dialog-error-message">
		<?php $this->text($this->state); ?>
	</div>
<?php endif; ?>
	<div class="dialog-message">
		<?php $this->text('ENTER_PASSWORD'); ?>
	</div>
	<form method="post">
		<div class="fields">
			<div class="field">
				<label for="password"><?php $this->text('PASSWORD'); ?></label>
				<input type="password" name="password" id="password" pattern=".{8,64}" required />
			</div>
		</div>
		<input type="submit" value="<?php $this->text('LOGIN'); ?>" />
	</form>
</div>
		<?php
	}

}

?>