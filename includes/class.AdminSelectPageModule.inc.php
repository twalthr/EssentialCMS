<?php

// v1: FEATURE COMPLETE

class AdminSelectPageModule extends BasicModule {

	// member variables
	private $pages;
	private $selectedPage;

	public function __construct($pageOperations, $parameters) {
		parent::__construct(1, 'admin-select-page');

		$this->pages = $pageOperations->getPages();

		if (count($parameters) > 0 && Utils::isValidInt($parameters[0])) {
			$this->selectedPage = (int) $parameters[0];
		}
	}

	public function printContent($config) {
		?>
		<div class="dialog-window">
			<h1><?php $this->text('PAGE_SELECTION'); ?></h1>
			<p><?php $this->text('SELECT_PAGE'); ?></p>
			<?php if ($this->pages === false || count($this->pages) === 0) : ?>
				<p class="empty"><?php $this->text('NO_PAGES'); ?></p>
				<div class="buttonSet">
				<button class="lightbox-close">
					<?php $this->text('CANCEL'); ?>
				</button>
				</div>
			<?php else : ?>
				<ul class="tableLike enableButtonsIfChecked">
					<?php foreach ($this->pages as $page) : ?>
						<li class="rowLike">
							<input type="radio" name="page" id="page<?php echo $page['pid']; ?>"
								value="<?php echo $page['pid']; ?>" 
								<?php if ($page['pid'] === $this->selectedPage) : ?>
								checked
								<?php endif; ?>
								/>
							<label for="page<?php echo $page['pid']; ?>" class="checkbox">
								<?php echo Utils::escapeString($page['title']); ?>
							</label>
							<a class="componentLink" target="_blank"
								href="<?php echo $config->getPublicRoot(); ?>/admin/page/<?php 
									echo $page['pid']; ?>"
								<?php if (Utils::hasStringContent($page['hoverTitle'])) : ?>
									title="<?php echo Utils::escapeString($page['hoverTitle']); ?>"
								<?php endif; ?>>
								<?php echo Utils::escapeString($page['title']); ?>
							</a>
							<span class="rowAdditionalInfo">
								<?php if (Utils::hasStringContent($page['externalId'])) : ?>
									<?php echo Utils::escapeString($page['externalId']); ?> | 
								<?php endif; ?>
								<?php echo $page['pid']; ?> 
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
				<div class="buttonSet">
					<button class="selectPage" disabled><?php $this->text('SELECT'); ?></button>
					<button class="lightbox-close">
						<?php $this->text('CANCEL'); ?>
					</button>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

?>