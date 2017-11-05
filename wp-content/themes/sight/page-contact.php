<?php
/*
  Template Name: Contact Redirect
 */
?>
<?php get_header(); ?>

    <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>

        <div class="entry">
            <div <?php post_class('single clear'); ?> id="post_<?php the_ID(); ?>">

                <?php 
				$page_id = 11521; 
				$page_data = get_page( $page_id ); // You must pass in a variable to the get_page function. If you pass in a value (e.g. get_page ( 123 ); ), WordPress will generate an error. 

				$content = apply_filters('the_content', $page_data->post_content); // Get Content and retain Wordpress filters such as paragraph tags.
				
				echo '<div class="post-content">'.$content.'</div>'; // Output Content
				?>
            </div>
        </div>

        <?php endwhile; ?>
    <?php endif; ?>



<?php get_footer(); ?>