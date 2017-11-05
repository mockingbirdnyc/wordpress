<?php get_header(); ?>

    <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>

        <div class="content-title">
           
        </div>

        <div class="entry">
            <div <?php post_class('single clear'); ?> id="post_<?php the_ID(); ?>">
                <div class="post-meta">
					<h1>Glossary: <?php the_title(); ?></h1>
                    </div>
					<div class="post-content post-10838">
					<?php 
					$syn_meta = get_post_meta($post->ID, '_syn_text_meta', true);
					if ($syn_meta['term']) {
						$page = get_page_by_title( $syn_meta['term'], 'OBJECT', 'glossary' );
						$content = $page->post_content;
						$content = apply_filters('the_content', $content);
						$content = str_replace(']]>', ']]&gt;', $content);
						?>
						
						<h3>Main Entry: <a href="<?php echo get_page_link($page->ID); ?>"><?php echo $syn_meta['term'] ?></a></h3>
						<?php echo $content ?>
						
						<?php
					}
					else {
						?>
						<?php the_content(); ?>
						<?php
					}
					?>
					<hr />
				<?php 
				$page_id = 10838; 
				$page_data = get_page( $page_id ); // You must pass in a variable to the get_page function. If you pass in a value (e.g. get_page ( 123 ); ), WordPress will generate an error. 

				$content = apply_filters('the_content', $page_data->post_content); // Get Content and retain Wordpress filters such as paragraph tags.
				
				echo $content; // Output Content
				?>			
				<h3><a href="/glossary">Return to Glossary</a></div>				
				<div class="post-footer"></div>
          
            <div class="post-navigation clear">
                <?php
                    $prev_post = get_adjacent_post(false, '', true);
                    $next_post = get_adjacent_post(false, '', false); ?>
                    <?php if ($prev_post) : $prev_post_url = get_permalink($prev_post->ID); $prev_post_title = $prev_post->post_title; ?>
                        <a class="post-prev" href="<?php echo $prev_post_url; ?>"><em>Previous term</em><span><?php echo $prev_post_title; ?></span></a>
                    <?php endif; ?>
                    <?php if ($next_post) : $next_post_url = get_permalink($next_post->ID); $next_post_title = $next_post->post_title; ?>
                        <a class="post-next" href="<?php echo $next_post_url; ?>"><em>Next term</em><span><?php echo $next_post_title; ?></span></a>
                    <?php endif; ?>
                <div class="line"></div>
            </div>
			</div>
            
        </div>

        <?php endwhile; ?>
    <?php endif; ?>

<?php get_footer(); ?>