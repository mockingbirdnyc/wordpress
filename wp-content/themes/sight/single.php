<?php get_header(); ?>

    <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>

        <div class="content-title">
            <?php the_category(' <span>/</span> '); ?>
            <?php /*?><a href="http://facebook.com/share.php?u=<?php the_permalink() ?>&amp;t=<?php echo urlencode(the_title('','', false)) ?>" target="_blank" class="f" title="Share on Facebook"></a>
            <a href="http://twitter.com/home?status=<?php the_title(); ?> <?php echo getTinyUrl(get_permalink($post->ID)); ?>" target="_blank" class="t" title="Spread the word on Twitter"></a>
            <a href="http://digg.com/submit?phase=2&amp;url=<?php the_permalink() ?>&amp;title=<?php the_title(); ?>" target="_blank" class="di" title="Bookmark on Del.icio.us"></a>
            <a href="http://stumbleupon.com/submit?url=<?php the_permalink() ?>&amp;title=<?php echo urlencode(the_title('','', false)) ?>" target="_blank" class="su" title="Share on StumbleUpon"></a><?php */?>
        </div>

        <div class="entry">
            <div <?php post_class('single clear'); ?> id="post_<?php the_ID(); ?>">
                <div class="post-meta">
                    <h1><?php the_title(); ?></h1>
                    by <span class="post-author"><a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>" title="Posts by <?php the_author(); ?>"><?php the_author(); ?></a></span> on <span
                        class="post-date"><?php the_time(__('M j, Y')) ?></span> &bull; <span><?php the_time() ?></span> <?php edit_post_link( __( 'Edit entry'), '&bull; '); ?><a
                        href="#comments" class="post-comms"><?php comments_number(__(''), __('1 Comment'), __('% Comments'), '', __('Comments Closed') ); ?></a></div>
                <div class="post-content">
                 <?php the_content(); 
                
                /**
                 * Get the current Url taking into account Https and Port
                 * @link http://css-tricks.com/snippets/php/get-current-page-url/
                 * @version Refactored by @AlexParraSilva
                 */
                function getCurrentUrl() {
                    $url  = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http';
                    $url .= '://' . $_SERVER['SERVER_NAME'];
                    $url .= in_array( $_SERVER['SERVER_PORT'], array('80', '443') ) ? '' : ':' . $_SERVER['SERVER_PORT'];
                    $url .= $_SERVER['REQUEST_URI'];
                    return $url;
                }
                ?>
                <div class="sharedaddy sd-sharing-enabled floated">
                  <div class="robots-nocontent sd-block sd-social sd-social-icon sd-sharing">
                    <div class="sd-content">
                      <ul>
                        <li class="share-email share-service-visible">
                          <a rel="nofollow" class="share-email sd-button share-icon no-text"
                          href="<?php getCurrentUrl() ?>?share=email&amp;nb=1"
                          target="_blank" title="Click to email this to a friend"></a>
                        </li>
                        <li class="share-facebook">
                          <a rel="nofollow" class="share-facebook sd-button share-icon no-text"
                          href="<?php getCurrentUrl() ?>?share=facebook&amp;nb=1"
                          target="_blank" title="Share on Facebook" id="sharing-facebook-64988"></a>
                        </li>
                        <li class="share-twitter">
                          <a rel="nofollow" class="share-twitter sd-button share-icon no-text"
                          href="<?php getCurrentUrl() ?>?share=twitter&amp;nb=1"
                          target="_blank" title="Click to share on Twitter" id="sharing-twitter-64988"></a>
                        </li>
                        <li class="share-pinterest">
                          <a rel="nofollow" class="share-pinterest sd-button share-icon no-text"
                          href="<?php getCurrentUrl() ?>?share=pinterest&amp;nb=1"
                          target="_blank" title="Click to share on Pinterest"></a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                </div>
                <div class="post-footer"><?php the_tags(__('<strong>Tags: </strong>'), ', '); ?></div>
                <div class="post-footer author"><?php echo get_avatar( get_the_author_email(), '60' ); ?>Read more on and from <?php the_author_posts_link(); ?>.<br /> Or get in touch.</div>
                  <!--BEGIN .author-bio-->
						<!--<div class="author-bio clear">
							<img class="fold-left" src="<?php bloginfo('template_directory'); ?>/images/bg-fold-left.png" alt="fold-left" width="5" height="5" />
							<img class="fold-right" src="<?php bloginfo('template_directory'); ?>/images/bg-fold-right.png" alt="fold-right" width="5" height="5" />
							<?php echo get_avatar( get_the_author_email(), '60' ); ?>
							<div class="author-info">
								<div class="author-title"><?php _e('About the author', 'framework') ?></div>
								<div class="author-description"><?php the_author_meta("description"); ?></div>
                                <div class="author-description">Other posts by <?php the_author_posts_link(); ?></div>
							</div>
                         </div>  -->
						<!--END .author-bio-->
                        <?php if(function_exists('get_related_posts_slider')) {get_related_posts_slider();} ?>
						
					<!-- <div class="related-posts">
	<h3>Related Posts</h3>
<ul>
<?php if( has_tag() ) { ?>
<?php
//for use in the loop, list 3 post titles related to first tag on current post
$backup = $post;  // backup the current object
$tags = wp_get_post_tags($post->ID);
$tagIDs = array();
if ($tags) {
$tagcount = count($tags);
for ($i = 0; $i < $tagcount; $i++) {
$tagIDs[$i] = $tags[$i]->term_id;
}
$args=array('tag__in' => $tagIDs, 'post__not_in' => array($post->ID), 'showposts'=>3, 'orderby'=> rand, 'ignore_sticky_posts'=>1);
$my_query = new WP_Query($args);
if( $my_query->have_posts() ) {
while ($my_query->have_posts()) : $my_query->the_post(); ?>
<li>
<a href="<?php the_permalink();?>" title="<?php the_title();?>">
<?php
$args = array(
	'image_scan'         => true,
	'width'              => '150',
	'height'             => '150',
	'format'             => 'img',
	'thumbnail_id_save'  => true, // Set 'featured image'.
);

if ( function_exists( 'get_the_image' ) ) get_the_image($args); ?>
</a>
<h5 class="related-article"><a href="<?php the_permalink();?>" title="<?php the_title();?>"><?php the_title();?></a></h5>
</li>
<?php endwhile; ?>
<?php } else { ?>
<h4><?php _e('No related posts found!', 'uxde'); ?></h4>
<?php }
}
$post = $backup;  // copy it back
wp_reset_query(); // to use the original query again
?>
<?php } else { ?>
<?php
global $post;
$tmp_post = $post;
$args = array( 'numberposts' => 3 );
$myposts = get_posts( $args );
foreach( $myposts as $post ) : setup_postdata($post); ?>
<li>
<a href="<?php the_permalink();?>" title="<?php the_title();?>">
<?php
$args = array(
	'image_scan'         => true,
	'width'              => '150',
	'height'             => '150',
	'format'             => 'img',
	'thumbnail_id_save'  => true, // Set 'featured image'.
);

if ( function_exists( 'get_the_image' ) ) get_the_image($args); ?>
</a>
<h5 class="related-article"><a href="<?php the_permalink();?>" title="<?php the_title();?>"><?php the_title();?></a></h5>
</li>
<?php endforeach; ?>
<?php $post = $tmp_post; ?>
<?php } ?>
</ul>
</div>
					-->	
            </div>
  
            
        </div>

        <?php endwhile; ?>
    <?php endif; ?>

<?php comments_template(); ?>

<?php get_footer(); ?>