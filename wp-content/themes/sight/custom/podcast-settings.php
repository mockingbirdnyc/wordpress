<?php

function churchthemes_podcast_settings_enqueue_scripts() {
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'podcast-settings' ) {
		wp_enqueue_script('media-upload');
		wp_enqueue_script('thickbox');
		wp_enqueue_style('thickbox');
	}
}
add_action( 'admin_enqueue_scripts', 'churchthemes_podcast_settings_enqueue_scripts' );

add_action( 'admin_init', 'ct_podcast_settings_init' );
add_action( 'admin_menu', 'ct_podcast_settings_add_page' );

/**
 * Init plugin options to white list our options
 */
function ct_podcast_settings_init(){
	register_setting( 'ct_podcast', 'ct_podcast_settings', 'ct_podcast_settings_validate' );
}

/**
 * Load up the menu page
 */
function ct_podcast_settings_add_page() {
	add_submenu_page('edit.php?post_type=ct_sermon', 'Podcast Settings', 'Podcast', 'manage_options', 'podcast-settings', 'ct_podcast_settings_do_page');
}

/**
 * Create arrays for our select and radio options
 */
$select_explicit_content = array(
	'no' => array(
		'value' =>	'no',
		'label' => 'No'
	),
	'yes' => array(
		'value' =>	'yes',
		'label' => 'Yes'
	)
);

/**
 * Create the options page
 */
function ct_podcast_settings_do_page() {
	global $select_explicit_content;
	
	/**
	 * Grab current domain for use in the 'Owner Email' setting
	 */
	$url = home_url();
	$parse = parse_url($url);
	
	
	/**
	 * Grab current user info for 'Webmaster' settings
	 */
	global $current_user;
	get_currentuserinfo();
	$admin_fname = $current_user->user_firstname;
	$admin_lname = $current_user->user_lastname;
	$admin_email = $current_user->user_email;

	if ( ! isset( $_REQUEST['settings-updated'] ) )
		$_REQUEST['settings-updated'] = false;

	?>
	<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#upload_cover_image').click(function() {
			uploadID = jQuery(this).prev('input');
			tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
			return false;
		});
		window.send_to_editor = function(html) {
			imgurl = jQuery('img',html).attr('src');
			uploadID.val(imgurl); /*assign the value to the input*/
			tb_remove();
		};
	});
	</script>
	<div class="wrap churchthemes">
		<?php screen_icon(); echo "<h2>Podcast Settings</h2>"; ?>

		<?php if ( false !== $_REQUEST['settings-updated'] ) : ?>
		<div class="updated fade"><p><strong>Options saved</strong></p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'ct_podcast' ); ?>
			<?php $options = get_option( 'ct_podcast_settings' ); ?>
			
			<h3>'General'</h3>

			<table class="form-table churchthemes">
			
				<tr>
					<th scope="row">Title</th>
					<td class="option" colspan="2">
						<input id="ct_podcast_settings[title]" class="regular-text" type="text" name="ct_podcast_settings[title]" placeholder="e.g. ' . get_bloginfo('name'), 'churchthemes' ); ?>" value="<?php echo wp_filter_nohtml_kses( $options['title'] ); ?>" />
					</td>
				</tr>
				
				<tr>
					<th scope="row">Description</th>
					<td class="option" colspan="2">
						<input id="ct_podcast_settings[description]" class="regular-text" type="text" name="ct_podcast_settings[description]" placeholder="e.g. ' . get_bloginfo('description'), 'churchthemes' ); ?>" value="<?php echo wp_filter_nohtml_kses( $options['description'] ); ?>" />
					</td>
				</tr>
				
				<tr>
					<th scope="row">Website Link</th>
					<td class="option" colspan="2">
						<input id="ct_podcast_settings[website_link]" class="regular-text" type="text" name="ct_podcast_settings[website_link]" placeholder="e.g. ' . $url, 'churchthemes' ); ?>" value="<?php echo esc_url( $options['website_link'] ); ?>" />
					</td>
				</tr>
				
				<tr>
					<th scope="row">Language</th>
					<td class="option" colspan="2">
						<input id="ct_podcast_settings[language]" class="regular-text" type="text" name="ct_podcast_settings[language]" placeholder="e.g. ' . get_bloginfo('language'), 'churchthemes' ); ?>" value="<?php echo wp_filter_nohtml_kses( $options['language'] ); ?>" />
					</td>
				</tr>
				
				<tr>
					<th scope="row">Copyright</th>
					<td class="option">
						<input id="ct_podcast_settings[copyright]" class="regular-text" type="text" name="ct_podcast_settings[copyright]" placeholder="e.g. Copyright ' . htmlspecialchars('&copy;') . ' ' . get_bloginfo('name'), 'churchthemes' ); ?>" value="<?php echo htmlspecialchars( esc_attr( $options['copyright'] ) ); ?>" />
					</td>
					<td class="info">
						<p><em>Tip: Use &amp;copy to generate a copyright symbol.</em></p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">Webmaster Name</th>
					<td class="option" colspan="2">
						<input id="ct_podcast_settings[webmaster_name]" class="regular-text" type="text" name="ct_podcast_settings[webmaster_name]" placeholder="e.g. WebMaster J'" value="<?php echo wp_filter_nohtml_kses( $options['webmaster_name'] ); ?>" />
					</td>
				</tr>
				
				<tr>
					<th scope="row">Webmaster Email</th>
					<td class="option" colspan="2">
						<input id="ct_podcast_settings[webmaster_email]" class="regular-text" type="text" name="ct_podcast_settings[webmaster_email]" placeholder="e.g. info@mbird.com" value="<?php echo wp_filter_nohtml_kses( $options['webmaster_email'] ); ?>" />
					</td>
				</tr>
				
			</table>
			
			<br /><br />
			<h3>iTunes</h3>
			
			<table class="form-table churchthemes">
				
				<tr>
					<th scope="row">Author</th>
					<td class="option">
						<input id="ct_podcast_settings[itunes_author]" class="regular-text" type="text" name="ct_podcast_settings[itunes_author]" placeholder="e.g. Primary Speaker or Church Name" value="<?php echo wp_filter_nohtml_kses( $options['itunes_author'] ); ?>" />
					</td>
					<td class="info">
						<p>This will display at the "Artist" in the iTunes Store.</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">Subtitle</th>
					<td class="option">
						<input id="ct_podcast_settings[itunes_subtitle]" class="regular-text" type="text" name="ct_podcast_settings[itunes_subtitle]" placeholder="e.g. Preaching and teaching audio from" value="<?php echo wp_filter_nohtml_kses( $options['itunes_subtitle'] ); ?>" />
					</td>
					<td class="info">
						<p>Your subtitle should briefly tell the listener what they can expect to hear.</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">Summary</th>
					<td class="option">
						<textarea id="ct_podcast_settings[itunes_summary]" class="large-text" cols="40" rows="5" name="ct_podcast_settings[itunes_summary]" placeholder="e.g. Weekly teaching audio brought to you by ' . get_bloginfo('name') . ' in City Name. ' . get_bloginfo('name') . ' exists to make Jesus famous by loving the city, caring for the church, and providing free teaching resources such as this Podcast."><?php echo esc_textarea( $options['itunes_summary'] ); ?></textarea>
					</td>
					<td class="info">
						<p>Keep your Podcast Summary short, sweet and informative. Be sure to include a brief statement about your mission and in what region your audio content originates.</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">Owner Name</th>
					<td class="option">
						<input id="ct_podcast_settings[itunes_owner_name]" class="regular-text" type="text" name="ct_podcast_settings[itunes_owner_name]" placeholder="e.g. mbird" value="<?php echo wp_filter_nohtml_kses( $options['itunes_owner_name'] ); ?>" />
					</td>
					<td class="info">
						<p>This should typically be the name of your Church.</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">Owner Email</th>
					<td class="option">
						<input id="ct_podcast_settings[itunes_owner_email]" class="regular-text" type="text" name="ct_podcast_settings[itunes_owner_email]" placeholder="e.g. info@mbird.com" value="<?php echo wp_filter_nohtml_kses( $options['itunes_owner_email'] ); ?>" />
					</td>
					<td class="info">
						<p>Use an email address that you don\'t mind being made public. If someone wants to contact you regarding your Podcast this is the address they will use.</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">Explicit Content</th>
					<td class="option" colspan="2">
						<select name="ct_podcast_settings[itunes_explicit_content]">
							<?php
								$selected = $options['itunes_explicit_content'];
								$p = '';
								$r = '';
								foreach ( $select_explicit_content as $option ) {
									if(isset($option['label'])) {
										$label = $option['label'];
									} else {
										$label = null;
									}
									if ( $selected == $option['value'] ) // Make default first in list
										$p = "\n\t<option style=\"padding-right: 10px;\" selected='selected' value='" . esc_attr( $option['value'] ) . "'>$label</option>";
									else
										$r .= "\n\t<option style=\"padding-right: 10px;\" value='" . esc_attr( $option['value'] ) . "'>$label</option>";
								}
								echo $p . $r;
							?>
						</select>
					</td>
				</tr>
				
				<tr class="top">
					<th scope="row">Cover Image</th>
					<td class="option">
						<input id="ct_podcast_settings[itunes_cover_image]" class="regular-text" type="text" name="ct_podcast_settings[itunes_cover_image]" value="<?php echo esc_url( $options['itunes_cover_image'] ); ?>" />
						<input id="upload_cover_image" type="button" class="button" value="Upload Image" />
<?php if($options['itunes_cover_image']): ?>
						<br />
						<img src="<?php echo esc_url( $options['itunes_cover_image'] ); ?>" class="preview" />
<?php endif; ?>
					</td>
					<td class="info">
						<p>This JPG will serve as the Podcast artwork in the iTunes Store.</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">Top Category</th>
					<td class="option">
						<input id="ct_podcast_settings[itunes_top_category]" class="regular-text" type="text" name="ct_podcast_settings[itunes_top_category]" placeholder="e.g. Religion & Spirituality" value="<?php echo wp_filter_nohtml_kses( $options['itunes_top_category'] ); ?>" />
					</td>
					<td class="info">
						<p>Choose the appropriate top-level category for your Podcast listing in iTunes.</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">Sub Category</th>
					<td class="option">
						<input id="ct_podcast_settings[itunes_sub_category]" class="regular-text" type="text" name="ct_podcast_settings[itunes_sub_category]" placeholder="e.g. Christianity" value="<?php echo wp_filter_nohtml_kses( $options['itunes_sub_category'] ); ?>" />
					</td>
					<td class="info">
						<p>Choose the appropriate sub category for your Podcast listing in iTunes.</p>
					</td>
				</tr>
						
			</table>
			
			<br /><br />
			<h3>Submit to iTunes</h3>
			<table class="form-table churchthemes">
				<tr>
					<th scope="row">Podcast Feed URL</th>
					<td class="option">
						<input type="text" class="regular-text" readonly="readonly" value="<?php echo $url; ?>/feed/podcast" />
					</td>
					<td class="info">
						<p>Use the <a href="http://www.feedvalidator.org/check.cgi?url=<?php echo $url; ?>/feed/podcast" target="_blank">Feed Validator</a> to diagnose and fix any problems before submitting your Podcast to iTunes.</p>
					</td>
				</tr>
			</table>
			
			<br />
			<p>Once your Podcast Settings are complete and your Sermons are ready, it\'s time to <a href="https://phobos.apple.com/WebObjects/MZFinance.woa/wa/publishPodcast" target="_blank">Submit Your Podcast</a> to the iTunes Store!</p>
			
			<p>Alternatively, if you want to track your Podcast subscribers, simply pass the Podcast Feed URL above through <a href="http://feedburner.google.com/" target="_blank">FeedBurner</a>. FeedBurner will then give you a new URL to submit to iTunes instead.</p>
			
			<p>Please read the <a href="http://www.apple.com/itunes/podcasts/creatorfaq.html" target="_blank">iTunes FAQ for Podcast Makers</a> for more information.</p>
			
			<p class="submit clear">
				<input type="submit" class="button-primary" value="Save Settings" />
			</p>
		</form>
		
	</div>
	<?php
}

/**
 * Sanitize and validate input. Accepts an array, return a sanitized array.
 */
function ct_podcast_settings_validate( $input ) {
	global $select_explicit_content;

	// Say our text option must be safe text with no HTML tags
	if ( ! isset( $input['sometext'] ) )
		$input['sometext'] = null;
	$input['sometext'] = wp_filter_nohtml_kses( $input['sometext'] );

	// Our select option must actually be in our array of select options
	if ( ! isset( $input['select1'] ) || ! array_key_exists( $input['select1'], $select_explicit_content ) )
		$input['select1'] = null;

	// Say our textarea option must be safe text with the allowed tags for posts
	if ( ! isset( $input['sometextarea'] ) )
		$input['sometextarea'] = null;
	$input['sometextarea'] = wp_filter_post_kses( $input['sometextarea'] );

	return $input;
}

// adapted from http://planetozh.com/blog/2009/05/handling-plugins-options-in-wordpress-28-with-register_setting/

?>