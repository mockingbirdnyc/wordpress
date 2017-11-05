<?php get_header(); ?>

    <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>
        <?php if ( is_page('podcasts') ) {
            wp_nav_menu( array( 'theme_location' => 'Resources menu', 'container_class' => 'resource_menu' ) );
        }?>
        <div class="entry">
            <div <?php post_class('single clear'); ?> id="post_<?php the_ID(); ?>">
               <!--  <div class="post-meta">
					<h1><?php the_title(); ?></h1> 
                   
                    
                    
                </div>-->
                <div class="post-content"><?php the_content(); ?></div>
            </div>
        </div>

        <?php endwhile; ?>
    <?php endif; ?>



<?php get_footer(); ?>