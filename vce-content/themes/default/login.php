<?php
/*
Template Name: login
*/
?>
<!DOCTYPE html>
<html lang="en">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title><?php $site->site_title(); ?> : <?php $page->title(); ?></title>
<link rel="shortcut icon" href="<?php echo $site->theme_path; ?>/favicon.png" type="image/x-icon">	
<?php $content->javascript(); ?>
<?php $content->stylesheet(); ?>
</head>
<body>
<div class="wrapper">
	<div class="content">
		<div class="header-content" role="banner">
			<div class="header-main">
				<div class="inner">
					<div class="site-logo">
						<div class="site-title">
							<div class="site-title-primary"><?php $site->site_title(); ?></div>
							<div class="site-title-secondary"><?php $site->site_description(); ?></div>
						</div>
					</div>
					<div class="site-user">
					</div>
				</div>
			</div>
		</div>
		
		<div class="inner">
			<br><br>
		</div>
		<div class="inner">
			<?php if (isset($page->message)) { ?>
				<div class="form-message form-success"><?php echo $page->message; ?><div class="close-message">x</div></div>
			<?php } ?>
			<?php $content->output(array('admin', 'premain', 'main', 'postmain')); ?>
		</div>
		<div class="inner">
			<?php $content->menu('login-page'); ?>
		</div>
	</div>
	<div class="footer" role="contentinfo">
		<div class="inner">
			<div class="footer-links"><?php $content->menu('footer'); ?></div>
			<?php include dirname(__DIR__) . "/logo/index.php"; ?>
		</div>

		<div class="footer-copyright-bar">
		</div>
	</div>
</div>
</body>
</html>