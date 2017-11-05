<?php
function my_init() {
	if (is_admin()) {
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-tabs');
		wp_register_script('tabs', get_bloginfo('template_directory').'/functions/tabs.js');
		wp_enqueue_script('tabs');
		wp_register_style('theme-options', get_bloginfo('template_directory').'/functions/theme-options.css');
		wp_enqueue_style('theme-options');
	}
}
add_action('init', 'my_init');



// ==========================//
//             Start the theme options!                  //
// ==========================//

	$themename = "ZillaPress";
	$shortname = "tz";

	$options = array (
		
	array(	"name" => __("selected", 'framework'),
	"id" => $shortname."_selectedtab",
	"std" => "",
	"type" => "hidden"),

	array(	"type" => "opentab"),
	
	array(	"type" => "open"),
	
	array(	"name" => __('Logo and Favicon Settings', 'framework'),
	"id" => $shortname."_logo_settings",
	"type" => "title"),
	
	array(	"name" => __("Upload Logo", 'framework'),
	"desc" => __("Enter the full URL of an image you would like to use as a logo e.g http://www.example.com/logo.png", 'framework'),
	"id" => $shortname."_logo_url",
	"std" => "",
	"type" => "file"),
	
	array(	"name" => __("Enable plain text logo",'framework'),
	"desc" => __("Check this box to use a plain text logo rather than an image. Info will be taken from your WordPress settings.", 'framework'),
	"id" => $shortname."_plain_logo",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Favicon URL", 'framework'),
	"desc" => __("Enter the full URL of your favicon e.g. http://www.example.com/favicon.ico", 'framework'),
	"id" => $shortname."_favicon_url",
	"std" => get_bloginfo('template_directory')."/favicon.ico",
	"type" => "file"),
	
	array(	"type" => "close"),
	
	array(	"type" => "open"),
	
	array(	"name" => __("Banner Settings", 'framework'),
	"id" => $shortname."_banner_settings",
	"type" => "title"),
	
	array(	"name" => __("Show Header Banner", 'framework'),
	"desc" => __("Check this to show a banner in the header of your site (468px x 60px)", 'framework'),
	"id" => $shortname."_banner_header",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Banner Image URL", 'framework'),
	"desc" => __("Enter the full image URL of your banner (468px x 60px) e.g. http://www.example.com/banner.gif", 'framework'),
	"id" => $shortname."_banner_img_url",
	"std" => "",
	"type" => "text"),
	
	array(	"name" => __("Banner Destination URL", 'framework'),
	"desc" => __("Enter the full destination URL for your banner e.g. http://www.example.com", 'framework'),
	"id" => $shortname."_banner_dest_url",
	"std" => "",
	"type" => "text"),
	
	array(	"type" => "close"),
	
	array(	"type" => "open"),
	
	array(	"name" => __("Analytics Settings", 'framework'),
	"id" => $shortname."_analytics",
	"type" => "title"),
	
	array(	"name" => __("Google Analytics Code", 'framework'),
	"desc" => __("Enter your full Google Analytics code (or any other site tracking code) here. It will be inserted before the closing body tag.", 'framework'),
	"id" => $shortname."_g_analytics",
	"std" => "",
	"type" => "textarea"),
	
	array(	"name" => __("FeedBurner URL", 'framework'),
	"desc" => __("Enter your full feedBurner URL (or any other preferred feed URL) if you wish to use FeedBurner over the standard WordPress Feed e.g. http://feeds.feedburner.com/yoururlhere", 'framework'),
	"id" => $shortname."_feedburner",
	"std" => "",
	"type" => "text"),

	array(	"name" => __("FeedBurner Email URL", 'framework'),
	"desc" => __("Enter your full FeedBurner email URL if you wish to enable users to subscribe to posts by email e.g. http://feedburner.google.com/fb/a/mailverify?uri=usernamehere", 'framework'),
	"id" => $shortname."_feedburner_email",
	"std" => "",
	"type" => "text"),
	
	array(	"type" => "close"),
	
	array(	"type" => "open"),
	
	array(	"name" => __('Contact form settings', 'framework'),
	"id" => $shortname."_form_settings",
	"type" => "title"),
	
	array(	"name" => __("Email address", 'framework'),
	"desc" => __("Enter the email address where you'd like to receive emails from the contact form, or leave blank to use admin email.", 'framework'),
	"id" => $shortname."_email",
	"std" => "",
	"type" => "text"),
	
	array(	"type" => "close"),
		
	array(	"type" => "closetab"),
	
	array(	"type" => "opentab"),
	
	array(	"name" => __("Navigation Notes", 'framework'),
	"desc" => __("The navigation settings below will be overwritten if you use WordPress 3.0 custom menus.", 'framework'),
	"id" => $shortname."_nav_notes",
	"type" => "note"),
	
	array(	"type" => "open"),
	
	array(	"name" => __("Navigation Settings", 'framework'),
	"id" => $shortname."_general",
	"type" => "title"),
	
	array(	"name" => __("Show Home Link", 'framework'),
	"desc" => __("Check this box to show a \"home\" link in the main navigation.", 'framework'),
	"id" => $shortname."_home_link",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Exclude from Navigation", 'framework'),
	"desc" => __("Enter a comma-separated list of any Category IDs you wish to exclude from the navigation (e.g. 1,5,6,) ", 'framework'),
	"id" => $shortname."_nav_exclude",
	"std" => "",
	"type" => "text"),
	
	array(	"name" => __("Menu Order", 'framework'),
	"desc" => __("Choose what order the navigation will be in, either the order you set in WordPress settings (menu_order) or alphabetically (post_title).", 'framework'),
	"id" => $shortname."_nav_order",
	"std" => "post_title",
	"type" => "select",
	"options" => array("post_title", "menu_order")),
	
	array(	"type" => "close"),
	
	array(	"type" => "open"),
	
	array(	"name" => __("Primary Navigation Settings", 'framework'),
	"id" => $shortname."_primary_nav_settings",
	"type" => "title"),
	
	array(	"name" => __("Exclude from Navigation", 'framework'),
	"desc" => __("Enter a comma-separated list of any Category IDs you wish to exclude from the navigation (e.g. 1,5,6,) ", 'framework'),
	"id" => $shortname."_primary_nav_exclude",
	"std" => "",
	"type" => "text"),
	
	array(	"type" => "close"),
	
	array(	"type" => "closetab"),
	
	array(	"type" => "opentab"),
	
	array(	"type" => "open"),
	
	array(	"name" => __("Post Settings", 'framework'),
	"id" => $shortname."_general",
	"type" => "title"),
	
	array(	"name" => __("Show Featured Slider", 'framework'),
	"desc" => __("Check this box to show a \"featured posts\" slider. All posts tagged \"featured\" will appear in the slider.", 'framework'),
	"id" => $shortname."_featured_slider",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Autoscroll Interval", 'framework'),
	"desc" => __("Set the interval time between featured slider autoscrolling where 1000 = 1second.", 'framework'),
	"id" => $shortname."_autoscroll",
	"std" => "9000",
	"type" => "text"),
	
	array(	"name" => __("Auto-insert Post Thumb", 'framework'),
	"desc" => __("Check this box to auto-insert the post thumbnails immediately before the post content.", 'framework'),
	"id" => $shortname."_insert_image",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Show Author Bios", 'framework'),
	"desc" => __("Check this to show an author bio panel on each post page.", 'framework'),
	"id" => $shortname."_author_bio",
	"std" => "false",
	"type" => "checkbox"),

	array(	"type" => "close"),
	
	array(	"type" => "open"),
	
	array(	"name" => __("Related Posts", 'framework'),
	"id" => $shortname."_related_posts",
	"type" => "title"),
	
	array(	"name" => __("Show Related Posts", 'framework'),
	"desc" => __("Check this to show related posts (same category) on post pages", 'framework'),
	"id" => $shortname."_show_related",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("No. of Related Posts", 'framework'),
	"desc" => __("Enter the number of related posts you wish to display", 'framework'),
	"id" => $shortname."_related_number",
	"std" => "5",
	"type" => "text"),
	
	array(	"name" => __("Related Post Message", 'framework'),
	"desc" => __("Enter a message describing the related posts e.g. If you liked this you may like...", 'framework'),
	"id" => $shortname."_related_message",
	"std" => "",
	"type" => "text"),
	
	array(	"type" => "close"),
	
	array(	"type" => "closetab"),
	
	array(	"type" => "opentab"),
	
	array(	"type" => "open"),
	
	array(	"name" => __('Post sharing', 'framework'),
	"id" => $shortname."_post_sharing",
	"type" => "title"),
	
	array(	"name" => __("Enable post sharing links", 'framework'),
	"desc" => __("Check this box to enable post sharing links on single post pages. ", 'framework'),
	"id" => $shortname."_sharing_enable",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Enable sharing to Twitter", 'framework'),
	"desc" => __("Check this box to enable post sharing to Twitter.", 'framework'),
	"id" => $shortname."_enable_twitter",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Enable sharing to Facebook", 'framework'),
	"desc" => __("Check this box to enable post sharing to Facebook.", 'framework'),
	"id" => $shortname."_enable_fb",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Enable sharing to Digg", 'framework'),
	"desc" => __("Check this box to enable post sharing to Digg.", 'framework'),
	"id" => $shortname."_enable_digg",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Enable sharing to Reddit", 'framework'),
	"desc" => __("Check this box to enable post sharing to Reddit.", 'framework'),
	"id" => $shortname."_enable_reddit",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Enable sharing to Delicious", 'framework'),
	"desc" => __("Check this box to enable post sharing to Delicious.", 'framework'),
	"id" => $shortname."_enable_del",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Enable sharing to Stumble", 'framework'),
	"desc" => __("Check this box to enable post sharing to Stumble.", 'framework'),
	"id" => $shortname."_enable_stumble",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Enable sharing to Google Buzz", 'framework'),
	"desc" => __("Check this box to enable post sharing to Google Buzz.", 'framework'),
	"id" => $shortname."_enable_gbuzz",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Enable sharing to Yahoo Buzz", 'framework'),
	"desc" => __("Check this box to enable post sharing to Yahoo Buzz.", 'framework'),
	"id" => $shortname."_enable_ybuzz",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Enable sharing to Technorati", 'framework'),
	"desc" => __("Check this box to enable post sharing to Technorati.", 'framework'),
	"id" => $shortname."_enable_techno",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Enable sharing to LinkedIn", 'framework'),
	"desc" => __("Check this box to enable post sharing to LinkedIn.", 'framework'),
	"id" => $shortname."_enable_linkedin",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"name" => __("Enable sharing via e-mail", 'framework'),
	"desc" => __("Check this box to enable post sharing via e-mail.", 'framework'),
	"id" => $shortname."_enable_email",
	"std" => "false",
	"type" => "checkbox"),

	array(	"name" => __("Enable sharing to Google", 'framework'),
	"desc" => __("Check this box to enable post sharing to Google.", 'framework'),
	"id" => $shortname."_enable_google",
	"std" => "false",
	"type" => "checkbox"),
	
	array(	"type" => "close"),
	
	array(	"type" => "closetab")
	
	);

	/*Add The Theme Options Page*/
	function mytheme_add_admin() {

    global $themename, $shortname, $options;

    if ( $_GET['page'] == basename(__FILE__) ) {

        if ( 'save' == $_REQUEST['action'] ) {
			
				$url = $_REQUEST['tz_selectedtab'];
            	if ($url == ''){
					$url = 'themes.php?page=theme-options.php&saved=true&tab=1';
				} else {
					$t = substr($url, -1);
					$url = 'themes.php?page=theme-options.php&saved=true&tab='.$t;
				}
				
    			foreach ($options as $value) {
					if ( ($value['id'] != 'tz_logo_url') && ($value['id'] != 'tz_favicon_url') ){
                    	update_option( $value['id'], $_REQUEST[ $value['id'] ] ); 
					}	
				}

                foreach ($options as $value) {
					if ( ($value['id'] != 'tz_logo_url') && ($value['id'] != 'tz_favicon_url') ){
                    	if( isset( $_REQUEST[ $value['id'] ] ) ) { update_option( $value['id'], $_REQUEST[ $value['id'] ]  ); } else { delete_option( $value['id'] ); } 
					}
				}

	
			// If files has been uploaded, move them to the /uploads dir and update the option value
			if (  (isset($_FILES['tz_logo_url'])) && ($_FILES['tz_logo_url']['error'] == UPLOAD_ERR_OK)  ) {
				$overrides = array('test_form' => false); 
		        $file = wp_handle_upload($_FILES['tz_logo_url'], $overrides);
				$urlimage = $file['url'];
				update_option('tz_logo_url', $urlimage);
			}
			
			if (  (isset($_FILES['tz_favicon_url'])) && ($_FILES['tz_favicon_url']['error'] == UPLOAD_ERR_OK)  ) {
				$overrides = array('test_form' => false); 
		        $file = wp_handle_upload($_FILES['tz_favicon_url'], $overrides);
				$urlimage = $file['url'];
				update_option('tz_favicon_url', $urlimage);
			}
			
				header("Location: ".$url);
                die;

        } else if( 'reset' == $_REQUEST['action'] ) {

            foreach ($options as $value) {
                delete_option( $value['id'] ); }

            header("Location: themes.php?page=theme-options.php&reset=true");
            die;
            
        }
    }

    add_theme_page($themename." Options", "Options", 'edit_themes', basename(__FILE__), 'mytheme_admin');
	}

	function mytheme_admin() {

    global $themename, $shortname, $options;

    if ( $_REQUEST['saved'] ) echo '<div id="message" class="updated fade"><p><strong>'.$themename.' settings saved.</strong></p></div>';
    if ( $_REQUEST['reset'] ) echo '<div id="message" class="updated fade"><p><strong>'.$themename.' settings reset.</strong></p></div>';

?>
		<?php // now style the actual theme options page ?>
		
		<div class="wrap">
			<div id="icon-themes" class="icon32"><br /></div>
			<h2><?php _e('Theme Options', 'framework') ?></h2>

			<form method="post" action="" enctype="multipart/form-data" class="options-form">
			<input type="hidden" name="selectedtab" id="selectedtab" value="0"/>	
			
			<div id="tabs" class="metabox-holder">
				<ul id="tab-items">
				<li><a href="#tabs-1"><?php _e('General Settings', 'framework') ?></a></li>
				<li><a href="#tabs-2"><?php _e('Navigation Settings', 'framework') ?></a></li>
				<li><a href="#tabs-3"><?php _e('Post Settings', 'framework') ?></a></li>
				<li><a href="#tabs-4"><?php _e('Social Sharing', 'framework') ?></a></li>
				</ul>
				<div class="clear"></div>
				
				
				<div class="postbox-container">

				<?php 
				$tab = 1;
				foreach ($options as $value) { 
				switch ( $value['type'] ) {

				case "opentab": ?>
				<div id="tabs-<?php echo $tab;?>">
				<?php 
				$tab++;
				break;
				
				case "closetab": ?>
				</div><!-- #tabs- -->
				<?php
				break;

				case "open": // style the opening
				?>
				<!-- div id="tabs-<?php echo $tab;?>" -->
				<div class="postbox">

				<?php 
				break;
				
				case "title": // style the titles
				?>
				<h3 class="hndle"><span><?php echo $value['name']; ?></span></h3>
				<div class="inside">		
					
				<?php break;
				
				case "note": // style the notes
				?>
				<div class="notes"><p><?php echo $value['desc']; ?></p></div>
				
				<?php 
				break;
				
				case "hidden": ?>
				
				<input name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" type="hidden" value="<?php if ( get_settings( $value['id'] ) != "") { echo stripslashes(htmlspecialchars(get_settings( $value['id'] ), ENT_QUOTES)); } else { echo $value['std']; } ?>" />
								
				
				<?php break;
				
				case "text": // style the text boxes
				?>
				
				<div class="textcont">
					<div class="value">
						<?php echo $value['name']; ?>:
					</div>
					<div class="input">
						<input name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" type="<?php echo $value['type']; ?>" value="<?php if ( get_settings( $value['id'] ) != "") { echo stripslashes(htmlspecialchars(get_settings( $value['id'] ), ENT_QUOTES)); } else { echo $value['std']; } ?>" />
						<p><?php echo stripslashes(htmlspecialchars($value['desc'])); ?></p>
					</div>
					<div class="clear"></div>
				</div>
				
				
				<?php break;
				
				case "file": // style the upload boxes
				?>
				
				<div class="textcont">
					<div class="value">
						<?php echo $value['name']; ?>:
					</div>
					<div class="input">
						<table class="form-table">
							<tr valign="top">
								<th scope="row">File:</th>
								<td><input type="file" name="<?php echo $value['id']; ?>" class="tz-upload" size="40" border="0" /></td>
							</tr>
						</table>
							<p><?php _e('Current file:', 'framework') ?> <?php echo get_option($value['id']); ?></p>
					</div>
					<div class="clear"></div>
				</div>
				
				
				<?php break;
				
				case "textarea": // style the textareas
				?>
				
				<div class="textcont">
				<div class="value"><?php echo $value['name']; ?>:</div>
				<div class="input">
				<textarea name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" type="<?php echo $value['type']; ?>"><?php if ( get_settings( $value['id'] ) != "") { echo stripslashes(htmlspecialchars(get_settings( $value['id'] ), ENT_QUOTES)); } else { echo $value['std']; } ?></textarea>
				<p><?php echo stripslashes(htmlspecialchars($value['desc'])); ?></p>
				</div>
				<div class="clear"></div>
				</div>
				
					
				<?php break;
				
				case "checkbox": // style the checkboxes
				?>
				
				<div class="textcont">
				<div class="value check-value"><?php echo $value['name']; ?>:</div>
				<div class="input">
				<?php if(get_option($value['id'])){ $checked = "checked=\"checked\""; }else{ $checked = "";} ?>
				<p><input type="checkbox" name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" value="true" <?php echo $checked; ?> />
				<?php echo stripslashes(htmlspecialchars($value['desc'])); ?></p>
				</div>
				<div class="clear"></div>
				</div>
				
									
				<?php break;
				
				case "select": // style the select
				?>
				
				<div class="textcont">
				<div class="value"><?php echo $value['name']; ?>:</div>
				<div class="input">
				<select name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>"><?php foreach ($value['options'] as $option) { ?><option<?php if ( get_settings( $value['id'] ) == $option) { echo ' selected="selected"'; } elseif ($option == $value['std']) { echo ' selected="selected"'; } ?>><?php echo $option; ?></option><?php } ?></select>
				<p><?php echo stripslashes(htmlspecialchars($value['desc'])); ?></p>
				</div>
				<div class="clear"></div>
				</div>
				
					
				<?php break;
				
				case "close": // style the closing
				?>
					
					</div><!-- inside -->
				</div><!-- post box -->
				
				<p class="submit">
						<input name="save" type="submit" class="button" value="Save Settings" />    
						<input type="hidden" name="action" value="save" />
					</p>
				
				<?php break;
				} 
			}
			?>
			
			</div><!-- postbox container -->
			</div><!-- metabox holder -->
			
		
			</form>
			<form method="post" action="">
				<p class="submit">
					<input name="reset" type="submit" class="button" value="Reset" />
					<input type="hidden" name="action" value="reset" />
					<span><?php _e('Caution: will restore theme defaults', 'framework') ?></span>
				</p>
			</form>
	</div>
	
	<?php
	}



add_action('admin_menu', 'mytheme_add_admin');


// ==========================//
//             END the theme options!                   //
// ==========================//

?>