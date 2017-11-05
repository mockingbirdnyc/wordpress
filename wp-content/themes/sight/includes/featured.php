<!-- BEGIN #slider -->
<div id="slider">
	
	<!-- BEGIN .infiniteCarousel -->
	<div class="infiniteCarousel">
		
		<!-- BEGIN .wrapper -->
		<div class="wrapper">
		
	    	<ul>
				<?php
				$featuredPosts = new WP_Query();
				$featuredPosts->query('tag=featured&posts_per_page=-1');
				while ($featuredPosts->have_posts()) : $featuredPosts->the_post(); ?>
					
					<li>
					
						<a href="<?php the_permalink(); ?>" class="slider-item">
						<?php if (  (function_exists('has_post_thumbnail')) && (has_post_thumbnail())  ) { ?>
							<?php the_post_thumbnail('thumbnail-featured'); ?>
						<?php } ?>
						</a>
					
						<!-- BEGIN .hover -->
						<div class="hover">
							
							<span class="hidden url"><?php the_permalink(); ?></span>
							<span class="tit"><?php the_title(); ?></span>
							<div>
								<span class="dat"><?php the_time( get_option('date_format') ); ?></span>
								<span class="com"><?php comments_number('No Comments','1 Comment','% Comments'); ?></span>
								<span class="by"><?php _e('By', 'framework') ?> <?php the_author(); ?></span>
							</div>
						
						<!-- END .hover-->
						</div>
						
					</li>
					
				<?php endwhile; ?>
				
		 	</ul>        
	    
	    <!-- END .wrapper -->
	    </div>
	
	<!-- END .infiniteCarousel -->
	</div>

<!-- END #slider -->		
</div>