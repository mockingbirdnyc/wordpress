<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php bloginfo('text_direction'); ?>" xml:lang="<?php bloginfo('language'); ?>">
    <head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
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
        
        <!-- ios -->
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
		
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
        <link rel="stylesheet" href="<?php 
    echo get_stylesheet_uri() 
    . '?t=' . filemtime( get_stylesheet_directory() . '/style.css' ); 
?>">
        <link rel="stylesheet" type="text/css" media="all" href="/wp-content/themes/sight/formalize.css" />
        <link rel="stylesheet" type="text/css" media="all" href="/wp-content/themes/sight/typicons.css" />
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
			if ( is_post_type_archive( 'ct_sermon' ) || is_tax('sermon_speaker') || is_tax('sermon_format') || is_tax('sermon_series') || is_tax('sermon_topic') ) wp_enqueue_script('selectbox','https://cdn.jsdelivr.net/jquery.selectbox/0.2/js/jquery.selectbox-0.2.min.js', 'jquery', false);
            wp_enqueue_script('script', get_template_directory_uri() . '/js/script.js', 'jquery', false);
		?>
        <?php wp_head(); ?>
        <?php if ( is_home() && !get_option('ss_disable') ) : ?>
        <script type="text/javascript">
            (function($) {
                $(function() {
                    $('#slideshow').cycle({
                        fx:     'scrollHorz',
                        timeout: <?php echo (get_option('ss_timeout')) ? get_option('ss_timeout') : '8000' ?>,
                        next:   '#rarr',
                        prev:   '#larr'
                    });
                })
            })(jQuery)
        </script>
        <?php endif; ?>
	</head>
	<?php $biblebook = $_GET['bible_book']; ?>
	<body <?php body_class(); ?> >
		<div class="ribbon">

             <?php wp_nav_menu(array('menu' => 'Top menu', 'theme_location' => 'Top menu', 'depth' => 2, 'container' => 'div', 'container_class' => 'menu',  'menu_class' => 'tdd', 'menu_id' => 'tdd' ));?>
           
           <div id="newhere"><a href="/new-here/">New Here?</a></div>
        </div>
		<div class="wrapper<?php if ( is_single() || is_home() || is_category() || is_tag() || is_author() || is_archive() || is_post_type_archive('ct_sermon')  || $post_type == 'ct_sermon' || is_tax('sermon_speaker') || is_tax('sermon_series') || is_tax('sermon_format') || is_tax('sermon_topic') || $biblebook || is_page('podcasts') ) { echo ' blog'; $is_blog = true; } ?>">

            <div class="header clear">
                <div class="logo">
                    <a href="<?php bloginfo('home'); ?>"><img src="<?php echo (get_option('logo_url')) ? get_option('logo_url') : get_bloginfo('template_url') . '/images/logo.png' ?>" alt="<?php bloginfo('name'); ?>"/></a>
                </div>

                <?php get_search_form(); ?>

                

            </div>

            <?php if($is_blog) {wp_nav_menu(array('menu' => 'Navigation', 'theme_location' => 'Navigation', 'depth' => 2, 'container' => 'div', 'container_class' => 'nav', 'menu_class' => 'dd', 'menu_id' => 'dd', 'walker' => new extended_walker())); }?>
			
			<?php
  if(is_page(array('cart','checkout','order-tracking'))) {

  			         wp_nav_menu(array('menu' => 'Store menu', 'theme_location' => 'Store menu', 'depth' => 2, 'container' => 'div', 'container_class' => 'nav', 'menu_class' => 'dd', 'menu_id' => 'dd', 'walker' => new extended_walker()));					 
	}
 // else if(is_post_type_archive('ct_sermon') || $post_type == 'ct_sermon' || is_tax('sermon_speaker') || is_tax('sermon_series') || is_tax('sermon_format') || is_tax('sermon_topic')  || $biblebook ){
  
  
	//wp_nav_menu(array('menu' => 'Resources menu', 'theme_location' => 'Resources menu', 'depth' => 2, 'container' => 'div', 'container_class' => 'nav', 'menu_class' => 'dd', 'menu_id' => 'dd', 'walker' => new extended_walker()));	
  
  //}	
  
 elseif(is_page( array( 'donate', 'finances-qa' ) ) ){
		wp_nav_menu(array('menu' => 'Support menu', 'theme_location' => 'Support menu', 'depth' => 2, 'container' => 'div', 'container_class' => 'nav', 'menu_class' => 'dd', 'menu_id' => 'dd', 'walker' => new extended_walker()));	

 }
 
 elseif(is_page( array( 'history-and-mission', 'faq', 'staff', 'contact', 'glossary' ) ) ){
		wp_nav_menu(array('menu' => 'About menu', 'theme_location' => 'Support menu', 'depth' => 2, 'container' => 'div', 'container_class' => 'nav', 'menu_class' => 'dd', 'menu_id' => 'dd', 'walker' => new extended_walker()));	

 }
 
  else if(is_page() && !is_404() &&!is_author() && $post->post_parent) {
 $children = wp_list_pages("title_li=&child_of=".$post->post_parent."&echo=0");}
  else if(is_page() && !is_404() &&!is_author()) {
  $children = wp_list_pages("title_li=&child_of=".$post->ID."&echo=0");}
  if ($children) { ?>
  <div class="nav">
      <ul id="dd" class="dd">
  <?php echo $children; ?>
  </ul>
    </div>
  <?php } ?>
  
         <div id="social">
                    <a href="http://visitor.r20.constantcontact.com/d.jsp?llr=lg6b66cab&p=oi&m=1102662661323&sit=gkkoz9leb&f=4c9a83c4-58d1-4d4c-a4a7-2c3fdd5360ff"><span class="typcn typcn-mail">&nbsp;</span></a>
                    <a href="https://twitter.com/mockingbirdmin"><span class="typcn typcn-social-twitter">&nbsp;</span></a>
                    <a href="https://instagram.com/mockingbirdnyc/"><span class="typcn typcn-social-instagram">&nbsp;</span></a>
                    <a href="https://www.facebook.com/mockingbirdmin"><span class="typcn typcn-social-facebook">&nbsp;</span></a>&nbsp;
          </div>
			
            <?php if ( is_home() && !get_option('ss_disable') ) get_template_part('slideshow'); ?>
            
              

            <!-- Container -->
            <div id="container" class="clear">
                <!-- Content -->
				<div id="content" <?php if(is_page_template('page-wide.php') || is_page_template('page-donation.php') || is_page_template('page-contact.php')) echo "class='wide'";?>>
                