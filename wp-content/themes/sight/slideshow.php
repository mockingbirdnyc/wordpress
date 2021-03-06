<?php
    $args = array(
        'meta_key' => '_slide_cb_single',
        'meta_value' => 'yes',
        'numberposts' => -1,
        'orderby' => 'rand'
        );
    $slides = get_posts($args);

    if ( !empty($slides) ) : $exl_posts = Array(); ?>

        <div class="slideshow"><div id="slideshow">

        <?php foreach( $slides as $post ) :
            setup_postdata($post);
            global $exl_posts;
            $exl_posts[] = $post->ID;
        ?>
        <div class="slide clear">
            <div class="post">
          
                <?php
                                $image_meta = get_post_meta($post->ID, '_slide_cb_single2', true);
                                if ($image_meta !== 'yes'){
				get_the_image(array(
				'meta_key' => null,
				'size' => 'slide',
                 'image_scan' => false
				));
                                
                                }
				?>    
                <!-- <div class="post-category"><?php the_category(' / '); ?></div> -->
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

                <div class="post-meta">by <span class="post-author"><a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>" title="Posts by <?php the_author(); ?>"><?php the_author(); ?></a></span> on <span
                        class="post-date"><?php the_time(__('M j, Y')) ?></span> &bull; <?php comments_popup_link(__(''), __('1 Comment'), __('% Comments'), '', __('Comments Closed') ); ?> <?php edit_post_link( __( 'Edit entry'), '&bull; '); ?></div>
                <div class="post-content"><?php if ( has_post_thumbnail() && function_exists('smart_excerpt') ) smart_excerpt(get_the_excerpt(), 50); else smart_excerpt(get_the_excerpt(), 50); ?></div>
            </div>
        </div>
        <?php endforeach; ?>

        </div>

            <a href="javascript: void(0);" id="larr"></a>
            <a href="javascript: void(0);" id="rarr"></a>
        </div>
    <?php endif; ?>