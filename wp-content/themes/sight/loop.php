<?php if ( have_posts() ) : ?>
    <div id="loop" class="list clear">

    <?php while ( have_posts() ) : the_post(); 
		global $excerpt_checkbox_mb;
		$exmeta = $excerpt_checkbox_mb->the_meta();
		if ( !is_array($exmeta) ){
			$exmeta = (array)$exmeta;
		}
		$text = get_the_content('');
		$text = apply_filters('the_content', $text);
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text, '<p>');
		$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
		$excerpt_length = 100;
		$words = explode(' ', $text, $excerpt_length + 1);
		if (count($words)> $excerpt_length) {$word_count = true;}
                else {$word_count = false;}
	?>

        <div <?php post_class('post clear'); ?> id="post_<?php the_ID(); ?>">

		<?php 


		if ( $exmeta['cb_single'] != "yes" && $word_count ){
				get_the_image(array(
				'meta_key' => null,
				'image_class' => 'thumb',
				'callback' => 'find_image'
			));
			} 
			?>
           
            <h2><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h2>

            <div class="post-meta">by <span class="post-author"><a
                    href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>" title="Posts by <?php the_author(); ?>"><?php the_author(); ?></a></span>
                                   on <span
                        class="post-date"><?php the_time(__('M j, Y')) ?></span> <em>&bull; </em><?php comments_popup_link(__(''), __('1 Comment'), __('% Comments'), '', __('Comments Closed')); ?> <?php edit_post_link( __( 'Edit entry'), '<em>&bull; </em>'); ?>
            </div>
            <?php 
			$image = find_image();
			?>
            <!--if full post, add left padding to avoid image wrap-->
            <?php if ( ($exmeta['cb_single'] == "yes") || !$word_count ) : ?>
            <div class="post-content">
			<?php the_content();
			elseif ($image) : ?>
            <div class="post-content post-psuedo-col"> 
            <?php the_excerpt();
				else : ?>
             <div class="post-content"> 
			<?php the_excerpt();
				endif; ?>
            </div>
					
        </div>

    <?php endwhile; ?>

    </div>

<?php endif; ?>
