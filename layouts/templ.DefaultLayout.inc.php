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
		<meta name="generator" content="<?php echo $layoutContext->getCmsFullname(); ?>" />
		<script type="text/javascript" src="<?php echo $layoutContext->getRoot(); ?>/js/main.js"></script>
		<script type="text/javascript" src="<?php echo $layoutContext->getRoot(); ?>/custom/scripts.js"></script>
		<link rel="stylesheet" href="<?php echo $layoutContext->getRoot(); ?>/css/main.css" type="text/css" media="all" />
		<link rel="stylesheet" href="<?php echo $layoutContext->getRoot(); ?>/custom/style.css" type="text/css" media="all" />
		<?php if ($layoutContext->hasCustomHeader()) : ?>
		<?php echo $layoutContext->getCustomHeader(); ?>
		<?php endif; ?>
	</head>
	<body>
		<div class="page">
			<header>
				<?php if ($layoutContext->hasLogoModules()) : ?>
				<div class="logo">
					<?php foreach ($layoutContext->getLogoModules() as $order => $module) : ?>
					<div class="module <?php echo $module['name']; ?> order<?php echo $order; ?>" id="l-<?php echo $module['name'] . '-' .$order; ?>">
						<?php echo $module['content']; ?>
					</div>
					<?php endforeach; ?>
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
				<?php if ($layoutContext->hasAsideHeaderModules()) : ?>
				<aside>
					<?php foreach ($layoutContext->getAsideHeaderModules() as $order => $module) : ?>
					<div class="module <?php echo $module['name']; ?> order<?php echo $order; ?>" id="ah-<?php echo $module['name'] . '-' .$order; ?>">
						<?php echo $module['content']; ?>
					</div>
					<?php endforeach; ?>
				</aside>
				<?php endif; ?>
			</header>
			<?php if ($layoutContext->hasPreContentModules()) : ?>
			<section class="pre-content">
				<?php foreach ($layoutContext->getPreContentModules() as $order => $module) : ?>
				<div class="module <?php echo $module['name']; ?> order<?php echo $order; ?>" id="prc-<?php echo $module['name'] . '-' .$order; ?>">
					<?php echo $module['content']; ?>
				</div>
				<?php endforeach; ?>
			</section>
			<?php endif; ?>
			<main>
				<?php if ($layoutContext->hasContentModules()) : ?>
				<section class="content">
					<?php foreach ($layoutContext->getContentModules() as $order => $module) : ?>
					<div class="module <?php echo $module['name']; ?> order<?php echo $order; ?>" id="c-<?php echo $module['name'] . '-' .$order; ?>">
						<?php echo $module['content']; ?>
					</div>
					<?php endforeach; ?>
				</section>
				<?php endif; ?>
				<?php if ($layoutContext->hasAsideContentModules()) : ?>
				<aside class="aside-content">
					<?php foreach ($layoutContext->getAsideContentModules() as $order => $module) : ?>
					<div class="module <?php echo $module['name']; ?> order<?php echo $order; ?>" id="ac-<?php echo $module['name'] . '-' .$order; ?>">
						<?php echo $module['content']; ?>
					</div>
					<?php endforeach; ?>
				</aside>
				<?php endif; ?>
			</main>
			<?php if ($layoutContext->hasPostContentModules()) : ?>
			<section class="post-content">
				<?php foreach ($layoutContext->getPostContentModules() as $order => $module) : ?>
				<div class="module <?php echo $module['name']; ?> order<?php echo $order; ?>" id="poc-<?php echo $module['name'] . '-' .$order; ?>">
					<?php echo $module['content']; ?>
				</div>
				<?php endforeach; ?>
			</section>
			<?php endif; ?>
			<footer>
				<?php if ($layoutContext->hasFooterModules()) : ?>
					<?php foreach ($layoutContext->getFooterModules() as $order => $module) : ?>
					<div class="module <?php echo $module['name']; ?> order<?php echo $order; ?>" id="f-<?php echo $module['name'] . '-' .$order; ?>">
						<?php echo $module['content']; ?>
					</div>
					<?php endforeach; ?>
				<?php endif; ?>
					&copy; <?php echo date('Y'); ?> Copyright - Managed and generated by <a href="<?php echo $layoutContext->getCmsUrl(); ?>"><?php echo $layoutContext->getCmsFullname(); ?></a>.
			</footer>
		</div>
	</body>
</html>