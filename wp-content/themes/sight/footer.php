            </div>
            <!-- /Content -->

            <?php 
			if (is_page('User Guide') ):
			?>
			<div class="sidebar" id="page-sidebar"></div>
            <?php
			wp_reset_query();
			elseif ( is_page_template('page-wide.php') || is_page_template('page-donation.php') || is_page_template('page-contact.php') ) :;
			elseif ( is_tax( 'product_cat' ) || is_page(array('shop','checkout','transaction-results','your-account')) || is_singular('product') || is_page_template('archive-product.php') ) :
				echo '<img style="float: right; margin-right: 275px" src="http://www.mbird.com/wp-content/themes/sight/images/shopping_cart.png" />';
    			get_sidebar('store');
			elseif ( is_post_type_archive('ct_sermon') || $post_type == 'ct_sermon' || is_tax('sermon_speaker') || is_tax('sermon_series') || is_tax('sermon_format') || is_tax('sermon_topic') ):
				get_sidebar('resource');	
			elseif ( is_page() ) :
    			get_sidebar('page');	
			elseif ( is_single() ) :
    			get_sidebar('single');
			else :
    			get_sidebar();
			endif;
			?>

            </div>
            <!-- /Container -->
            </div>

            <!-- BEGIN #footer -->
		<div id="footer" class="footer">
		
			
			<!-- BEGIN #footer-inner -->
			<div id="footer-inner" class="clearfix">
			
				<!-- BEGIN .footer-widget -->
				<div class="footer-widget">
				
					<?php	/* Widgetised Area */	if ( !function_exists( 'dynamic_sidebar' ) || !dynamic_sidebar('Footer One') ) ?>
					
				<!-- END .footer-widget -->
				</div>
				
				<!-- BEGIN .footer-widget -->
				<div class="footer-widget">
				
					<?php	/* Widgetised Area */	if ( !function_exists( 'dynamic_sidebar' ) || !dynamic_sidebar('Footer Two') ) ?>
					
				<!-- END .footer-widget -->
				</div>
				
				<!-- BEGIN .footer-widget -->
				<div class="footer-widget footer-widget-last">
				
					<?php	/* Widgetised Area */	if ( !function_exists( 'dynamic_sidebar' ) || !dynamic_sidebar('Footer Three') ) ?>
					
				<!-- END .footer-widget -->
				</div>
				
			<!-- END #footer-inner -->
			</div>
			
			<!-- BEGIN #footer-notes -->
			<div id="footer-notes">
				
				<!-- BEGIN #footer-notes-inner  -->
				<div id="footer-notes-inner">
				
					<p class="copyright">&copy; <?php echo date('Y'); ?> <a href="<?php bloginfo( 'url' ); ?>"><?php bloginfo( 'name' ); ?></a>.</p>
					
					<p class="credit">Powered by <a href="http://wordpress.org/">WordPress</a> </p>
				
				<!-- END #footer-notes-inner -->
				</div>
				
			<!-- END #footer-notes -->
			</div>
		
		<!-- END #footer -->
		</div>
        
        <!-- Page generated: <?php timer_stop(1); ?> s, <?php echo get_num_queries(); ?> queries -->
        <?php wp_footer(); ?>

		<?php  echo (get_option('ga')) ? get_option('ga') : '' ?>

	</body>
</html>