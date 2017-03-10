<?php

// v1: FEATURE COMPLETE

class AdminSelectMediaGroupModule extends BasicModule {

	// member variables
	private $globalMediaGroups;
	private $localMediaGroups;

	public function __construct($mediaGroupOperations) {
		parent::__construct(1, 'admin-select-media-group');

		$this->globalMediaGroups = $mediaGroupOperations->getGlobalMediaGroups();
		$this->localMediaGroups = $mediaGroupOperations->getLocalMediaGroups();
	}

	public function printContent($config) {
		?>
		<div class="dialog-window">
			<h1><?php $this->text('MEDIA_GROUP_SELECTION'); ?></h1>
			<p><?php $this->text('SELECT_MEDIA_GROUP'); ?></p>

			<section>
				<h2><?php $this->text('GLOBAL_MEDIA_GROUPS'); ?></h2>
				<?php $this->printMediaGroups($config, $this->globalMediaGroups); ?>
			</section>
			<section>
				<h2><?php $this->text('LOCAL_MEDIA_GROUPS'); ?></h2>
				<?php $this->printMediaGroups($config, $this->localMediaGroups); ?>
			</section>
		</div>
		<?php
	}

	public function printMediaGroups($config, $mediaGroups) {
		if ($mediaGroups === false || count($mediaGroups) === 0) : ?>
			<p class="empty"><?php $this->text('NO_MEDIA_GROUP'); ?></p>
			<div class="buttonSet">
				<button class="lightbox-close">
					<?php $this->text('CANCEL'); ?>
				</button>
			</div>
		<?php else : ?>
			<ul class="tableLike enableButtonsIfChecked">
				<?php foreach ($mediaGroups as $mediaGroup) : ?>
					<li class="rowLike">
						<input type="radio" name="mediaGroup"
							id="mediaGroup<?php echo $mediaGroup['mgid']; ?>"
							value="<?php echo $mediaGroup['mgid']; ?>" />
						<label for="mediaGroup<?php echo $mediaGroup['mgid']; ?>" class="checkbox">
							<?php echo Utils::escapeString($mediaGroup['title']); ?>
						</label>
						<a class="componentLink" target="_blank"
							href="<?php echo $config->getPublicRoot(); ?>/admin/media-group/<?php 
								echo $mediaGroup['mgid']; ?>"
							<?php if (Utils::hasStringContent($mediaGroup['description'])) : ?>
								title="<?php echo Utils::escapeString($mediaGroup['description']); ?>"
							<?php endif; ?>>
							<?php echo Utils::escapeString($mediaGroup['title']); ?>
						</a>
						<span class="rowAdditionalInfo">
							<?php if (Utils::hasStringContent($mediaGroup['tags'])) : ?>
								<?php echo Utils::escapeString($mediaGroup['tags']); ?> | 
							<?php endif; ?>
							<?php echo $mediaGroup['mgid']; ?> 
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="buttonSet">
					<button class="selectMediaGroup" disabled><?php $this->text('SELECT'); ?></button>
					<button class="lightbox-close">
						<?php $this->text('CANCEL'); ?>
					</button>
				</div>
		<?php endif;
	}
}

?>