<?php /* include theme options */ include( TEMPLATEPATH . '/functions/get-options.php' ); ?>

							<!-- BEGIN #related-posts -->
							<div id="related-posts" class="clearfix">
							
								<?php
								$backup = $post;  // backup the current object
								$tz_categories = get_the_category($post->ID);
								if ($tz_categories) {
									$tz_category_ids = array();
									foreach($tz_categories as $tz_individual_category) $tz_category_ids[] = $tz_individual_category->term_id;
								
									$args=array(
										'category__in' => $tz_category_ids,
										'post__not_in' => array($post->ID),
										'showposts'=>$tz_related_number, // Number of related posts that will be shown.
										'caller_get_posts'=>1
									);
									$tz_related_posts = new wp_query($args);
									if( $tz_related_posts->have_posts() ) { ?>
										
										<h3 class="widget-title">Related Posts <?php if ($tz_related_message) { ?><span><?php echo ($tz_related_message); ?></span><?php } ?></h3>
										
										<?php while ($tz_related_posts->have_posts()) {
											$tz_related_posts->the_post();?>
											
											<!-- BEGIN .post-container -->
											<div class="post-container">
											
												
												<div class="post-thumb">
													<?php
								get_the_image(array(
								'meta_key' => null,
								'size' => 'mini-thumbnail',
								'image_class' => 'mini-thumbnail'
								));
								?>
												</div>
                                               
												
                                                 <div class="post-info">
													<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php printf(__('Permanent Link to %s', 'framework'), get_the_title()); ?>"><?php echo get_the_title() /* post thumbnail settings configured in functions.php */ ?></a>
												</div>

											<!-- END .post-container -->
											</div>
										<?php
										}
									}
								}
								$post = $backup;  // copy it back
 								 wp_reset_query(); // to use the original query again
								?>
							
							<!-- END #related-posts -->
							</div>