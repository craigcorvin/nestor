<?php
/*
Template Name: homepage
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
			<a href="#main_content" class="skip-to-main">Skip to main content</a>
			<div class="header-main">
				<div class="inner">
					<div class="site-logo">
						<div class="site-title">
							<div class="site-title-primary"><?php $site->site_title(); ?></div>
							<div class="site-title-secondary"><?php $site->site_description(); ?></div>
						</div>
					</div>
					<div class="site-user">
						<div class="site-user-info">
							<div class="site-user-name"><?php echo $user->first_name; ?> <?php echo $user->last_name; ?></div>
							<div class="site-user-items"><?php $content->menu('user'); ?></div>
						</div>

						<div class="site-user-image">
							<img class="profile_picture" src="<?php echo $site->site_url . '/vce-application/images/user_' . ($user->user_id % 5) . '.png'; ?>">
						</div>
					</div>
				</div>
			</div>

			<div class="header-nav">
				<div class="inner">
					<div class="site-menu site-menu-home" role="navigation" aria-label="Main Menu">
					<?php $content->menu('main'); ?>
					</div>
					<div class="responsive-menu-icon"><img src="<?php echo $site->theme_path; ?>/images/appbar.lines.horizontal.4.png"></div>
				</div>
			</div>
		</div>

		<div class="responsive-menu" role="navigation" aria-label="Main Menu">
			<?php $content->menu('responsive'); ?>
		</div>

		<div class="home-background-image"></div>

		<div class="inner" id="main_content" role="main">
			<div class="cover-image">
				<div class="left-cover-image-link"><?php $content->new_coaching_partnership_link(); ?></div>
				<div class="right-cover-image-link"><?php $content->upload_new_resource_link(); ?></div>
			</div>
		
			<div class="main-content">
				<div class="coaching-partnerships-link"><?php $content->coaching_partnership_list(); ?></div>
				<div class="notifications-link"><?php $content->notifications_list(); ?></div
			</div>
			
			<?php $content->output(array('admin', 'premain', 'main', 'postmain')); ?>
			
			<div class=""><?php $content->output('links_component_content'); ?></div>
		</div>
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