<!DOCTYPE html>
<html>
	<head>
		<?php if ($layoutContext->hasTitle()) : ?>
		<title><?php echo $layoutContext->getTitle(); ?></title>
		<?php endif; ?>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<?php if ($layoutContext->hasDescription()) : ?>
		<meta name="description" content="<?php echo $layoutContext->getDescription(); ?>" />
		<?php endif; ?>
		<meta name="generator" content="<?php echo $layoutContext->getConfig()->getCmsFullname(); ?>" />
		<script type="text/javascript" src="<?php echo $layoutContext->getRoot(); ?>/js/main.js"></script>
		<script type="text/javascript" src="<?php echo $layoutContext->getRoot(); ?>/custom/scripts.js"></script>
		<link rel="stylesheet" href="<?php echo $layoutContext->getRoot(); ?>/css/main.css" type="text/css" media="all" />
		<link rel="stylesheet" href="<?php echo $layoutContext->getRoot(); ?>/custom/style.css" type="text/css" media="all" />
		<?php if ($layoutContext->hasCustomHeader()) : ?>
		<?php echo $layoutContext->getCustomHeader(); ?>
		<?php endif; ?>
	</head>
	<body>
		<header>
			<?php if ($layoutContext->hasLogo()) : ?>
			<div class="logo">
				<?php echo $layoutContext->getLogo(); ?>
			</div>
			<?php endif; ?>
			<?php if ($layoutContext->hasMenuItems()) : ?>
			<nav class="main-menu">
				<ul>
				<?php foreach ($layoutContext->getMenuItems() as $item) : ?>
					<li>
						<a href="<?php echo $item->getUrl(); ?>"<?php if ($item->hasHoverTitle()) : ?> title="<?php echo $item->getHoverTitle(); ?>"<?php endif; ?><?php if ($item->isCurrent()) : ?> class="current"<?php endif; ?>><?php echo $item->getTitle(); ?></a>
						<?php if ($item->hasChild()) : ?>
						<ul class="sub-menu">
							<?php foreach ($item->getChildren() as $child) : ?>
								<li><a href="<?php echo $child->getUrl(); ?>"<?php if ($child->hasHoverTitle()) : ?> title="<?php echo $child->getHoverTitle(); ?>"<?php endif; ?><?php if ($child->isCurrent()) : ?> class="current"<?php endif; ?>><?php echo $child->getTitle(); ?></a><li>
							<?php endforeach; ?>
						</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
				</ul>
			</nav>
			<?php if ($layoutContext->hasCurrentSubMenuItems()) : ?>
			<nav class="current-sub-menu">
				<ul>
					<?php foreach ($item->getCurrentSubMenuItems() as $currentSubItem) : ?>
						<li><a href="<?php echo $currentSubItem->getUrl(); ?>"<?php if ($currentSubItem->hasHoverTitle()) : ?> title="<?php echo $currentSubItem->getHoverTitle(); ?>"<?php endif; ?><?php if ($currentSubItem->isCurrent()) : ?> class="current"<?php endif; ?>><?php echo $currentSubItem->getTitle(); ?></a><li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</nav>
			<?php endif; ?>
		</header>
		<?php if ($layoutContext->hasBeforeContentModules()) : ?>
		<section class="before-content">
			<?php foreach ($layoutContext->getBeforeContentModules() as $module) : ?>
			<div class="module <?php echo $module->getName(); ?> order<?php echo $module->getOrder(); ?>" id="bc-<?php echo $module->getName()."-".$module->getOrder(); ?>">
					<?php echo $module->getContent(); ?>
			</div>
			<?php endforeach; ?>
		</section>
		<?php endif; ?>
		<?php if ($layoutContext->hasContentModules()) : ?>
		<section class="content">
			<?php foreach ($layoutContext->getContentModules() as $module) : ?>
			<div class="module <?php echo $module->getName(); ?> order<?php echo $module->getOrder(); ?>" id="c-<?php echo $module->getName()."-".$module->getOrder(); ?>">
					<?php echo $module->getContent(); ?>
			</div>
			<?php endforeach; ?>
		</section>
		<?php endif; ?>
		<?php if ($layoutContext->hasAfterContentModules()) : ?>
		<section class="after-content">
			<?php foreach ($layoutContext->getAfterContentModules() as $module) : ?>
			<div class="module <?php echo $module->getName(); ?> order<?php echo $module->getOrder(); ?>" id="ac-<?php echo $module->getName()."-".$module->getOrder(); ?>">
					<?php echo $module->getContent(); ?>
			</div>
			<?php endforeach; ?>
		</section>
		<?php endif; ?>
		<footer>
			<?php if ($layoutContext->hasFooter()) : ?>
				<?php echo $layoutContext->getFooter(); ?>
			<?php else : ?>
				&copy; <?php echo date('Y'); ?> Copyright - Managed and generated by <a href="<?php echo $layoutContext->getConfig()->getCmsUrl(); ?>"><?php echo $layoutContext->getConfig()->getCmsFullname(); ?></a>.
			<?php endif; ?>
		</footer>
	</body>
</html>