<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php bloginfo('text_direction'); ?>" xml:lang="<?php bloginfo('language'); ?>">
    <head>
        <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
        <title><?php wp_title ( '|', true,'right' ); ?></title>
        <meta http-equiv="Content-language" content="<?php bloginfo('language'); ?>" />
		
		<?php  if (!is_page(array('store','checkout','transaction-results','your-account')) && ( is_single() || is_page() ) ) : if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
		<meta name="description" content="<?php echo strip_tags(get_the_excerpt()); ?>" />
		<?php endwhile; endif; 
		elseif (is_category()) : ?>
		<meta name="description" content="<?php bloginfo('name'); echo " "; single_cat_title(); ?>" />
		<?php elseif(is_home()) : ?>
		<meta name="description" content="<?php bloginfo('description'); ?>" />
		<?php else : ?>
		<meta name="description" content="<?php wp_title ( '|', true,'right' ); ?>" />
		<?php endif;?>
		
		<!-- For third-generation iPad with high-resolution Retina display: -->
		<link rel="apple-touch-icon-precomposed" sizes="144x144" href="apple-touch-icon-144x144-precomposed.png">
		<!-- For iPhone with high-resolution Retina display: -->
		<link rel="apple-touch-icon-precomposed" sizes="114x114" href="apple-touch-icon-114x114-precomposed.png">
		<!-- For first- and second-generation iPad: -->
		<link rel="apple-touch-icon-precomposed" sizes="72x72" href="apple-touch-icon-72x72-precomposed.png">
		<!-- For non-Retina iPhone, iPod Touch, and Android 2.1+ devices: -->
		<link rel="apple-touch-icon-precomposed" href="apple-touch-icon-precomposed.png">
		

		<link rel="profile" href="http://gmpg.org/xfn/11" />
        <link rel="shortcut icon" href="<?php bloginfo('template_url'); ?>/images/favicon.ico" type="image/x-icon" />
        <link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo('stylesheet_url'); ?>" />
        <link rel="stylesheet" type="text/css" media="all" href="/wp-content/themes/sight/formalize.css" />
        <!--[if IE]><link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo('template_url'); ?>/ie.css" /><![endif]-->
        <?php
			wp_enqueue_script('jquery');
			wp_enqueue_script('cycle', get_template_directory_uri() . '/js/jquery.cycle.all.min.js', 'jquery', false);
			wp_enqueue_script('cookie', get_template_directory_uri() . '/js/jquery.cookie.js', 'jquery', false);
			wp_enqueue_script('columns', get_template_directory_uri() . '/js/jquery.columns.js', 'jquery', false);
			wp_enqueue_script('color', get_template_directory_uri() . '/js/jquery.color.js', 'jquery', false);
			wp_enqueue_script('fitvids', get_template_directory_uri() . '/js/jquery.fitvids.js', 'jquery', false);
            if ( is_singular() ) wp_enqueue_script( 'comment-reply' );
			if ( is_author() ) wp_enqueue_script('formalize', get_template_directory_uri() . '/js/jquery.formalize.min.js', 'jquery', false);
            wp_enqueue_script('script', get_template_directory_uri() . '/js/script.js', 'jquery', false);
		?>
        <?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
		<div class="wrapper">

            <div class="header clear">
                <div class="logo">
                    <a href="<?php bloginfo('home'); ?>"><img src="<?php echo (get_option('logo_url')) ? get_option('logo_url') : get_bloginfo('template_url') . '/images/logo.png' ?>" alt="<?php bloginfo('name'); ?>"/></a>
                </div>

                <?php get_search_form(); ?>

                <?php wp_nav_menu(array('menu' => 'Top menu', 'theme_location' => 'Top menu', 'depth' => 1, 'container' => 'div', 'container_class' => 'menu', 'menu_id' => false, 'menu_class' => false)); ?>


            </div>
			
			            <?php wp_nav_menu(array('menu' => 'Store menu', 'theme_location' => 'Store menu', 'depth' => 2, 'container' => 'div', 'container_class' => 'nav', 'menu_class' => 'dd', 'menu_id' => 'dd', 'walker' => new extended_walker())); ?>


            <!-- Container -->
  