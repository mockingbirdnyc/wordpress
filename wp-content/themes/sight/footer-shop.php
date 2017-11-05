
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