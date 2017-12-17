<?php
/*ob_start();
require_once('FirePHPCore/FirePHP.class.php');
$firephp = FirePHP::getInstance(true);
require_once('FirePHPCore/fb.php');*/

//global $wp_roles;
//$wp_roles->add_cap( 'author', 'publish_posts');
//$wp_roles->add_cap( 'author', 'read_private_pages');


/*** Theme setup ***/

add_theme_support( 'post-thumbnails' );
add_theme_support( 'automatic-feed-links' );

if ( ! isset( $content_width ) )
$content_width = 930;

function sight_setup() {
    update_option('thumbnail_size_w', 290);
    update_option('thumbnail_size_h', 290);
    add_image_size( 'soft-thumbnail', 290, 290, false );
    add_image_size( 'mini-thumbnail', 60, 60, true );
    add_image_size('slide', 640, 290, true);
    register_nav_menu('Navigation', __('Navigation'));
    register_nav_menu('Top menu', __('Top menu'));
	register_nav_menu('Store menu', __('Store menu'));
	register_nav_menu('Resources menu', __('Resources menu'));
	register_nav_menu('Support menu', __('Support menu'));
	register_nav_menu('About menu', __('About menu'));
}
add_action( 'init', 'sight_setup' );

if ( is_admin() && isset($_GET['activated'] ) && $pagenow == 'themes.php' ) {
    update_option( 'posts_per_page', 12 );
    update_option( 'paging_mode', 'default' );
}



/*** Navigation ***/

if ( !is_nav_menu('Navigation') || !is_nav_menu('Top menu') ) {
    $menu_id1 = wp_create_nav_menu('Navigation');
    $menu_id2 = wp_create_nav_menu('Top menu');
    wp_update_nav_menu_item($menu_id1, 1);
    wp_update_nav_menu_item($menu_id2, 1);
}

class extended_walker extends Walker_Nav_Menu{
	function display_element( $element, &$children_elements, $max_depth, $depth=0, $args, &$output ) {

		if ( !$element )
			return;

		$id_field = $this->db_fields['id'];

		//display this element
		if ( is_array( $args[0] ) )
			$args[0]['has_children'] = ! empty( $children_elements[$element->$id_field] );

		//Adds the 'parent' class to the current item if it has children
		if( ! empty( $children_elements[$element->$id_field] ) )
			array_push($element->classes,'parent');

		$cb_args = array_merge( array(&$output, $element, $depth), $args);

		call_user_func_array(array(&$this, 'start_el'), $cb_args);

		$id = $element->$id_field;

		// descend only when the depth is right and there are childrens for this element
		if ( ($max_depth == 0 || $max_depth > $depth+1 ) && isset( $children_elements[$id]) ) {

			foreach( $children_elements[ $id ] as $child ){

				if ( !isset($newlevel) ) {
					$newlevel = true;
					//start the child delimiter
					$cb_args = array_merge( array(&$output, $depth), $args);
					call_user_func_array(array(&$this, 'start_lvl'), $cb_args);
				}
				$this->display_element( $child, $children_elements, $max_depth, $depth + 1, $args, $output );
			}
			unset( $children_elements[ $id ] );
		}

		if ( isset($newlevel) && $newlevel ){
			//end the child delimiter
			$cb_args = array_merge( array(&$output, $depth), $args);
			call_user_func_array(array(&$this, 'end_lvl'), $cb_args);
		}

		//end this element
		$cb_args = array_merge( array(&$output, $element, $depth), $args);
		call_user_func_array(array(&$this, 'end_el'), $cb_args);
	}
}

// Metaboxes

// custom constant (opposite of TEMPLATEPATH)
define('_TEMPLATEURL', WP_CONTENT_URL . '/' . stristr(TEMPLATEPATH, 'themes'));

include_once 'WPAlchemy/MetaBox.php';

// include css to style the custom meta boxes, this should be a global
// stylesheet used by all similar meta boxes
if (is_admin())
{
	wp_enqueue_style('custom_meta_css', _TEMPLATEURL . '/custom/meta.css');
}


if ( current_user_can( 'edit_others_posts' ) ) {
$slideshow_checkbox_mb = new WPAlchemy_MetaBox(array
(
	'id' => '_sldeshow_checkbox_meta',
	'title' => 'Featured Options',
	'types' => array('post'), // added only for pages and to custom post type "events"
	'context' => 'side', // same as above, defaults to "normal"
	'priority' => 'low', // same as above, defaults to "high"
	'mode' => WPALCHEMY_MODE_EXTRACT,
	'prefix' => '_slide_',
	'autosave' => TRUE,
	'template' => TEMPLATEPATH . '/custom/slideshow_meta.php',
));
}

$excerpt_checkbox_mb = new WPAlchemy_MetaBox(array
(
	'id' => '_excerpt_checkbox_meta',
	'title' => 'Excerpt Options',
	'types' => array('post'), // added only for pages and to custom post type "events"
	'context' => 'side', // same as above, defaults to "normal"
	'priority' => 'low', // same as above, defaults to "high"
	'autosave' => TRUE,
	'template' => TEMPLATEPATH . '/custom/excerpt_meta.php',
));

$glossexclude_checkbox_mb = new WPAlchemy_MetaBox(array
(
	'id' => '_glossexclude_checkbox_meta',
	'title' => 'Glossary Options',
	'types' => array('post'), // added only for pages and to custom post type "events"
	'context' => 'side', // same as above, defaults to "normal"
	'priority' => 'low', // same as above, defaults to "high"
	'autosave' => TRUE,
	'template' => TEMPLATEPATH . '/custom/glossexclude_meta.php',
));

$syn_text_mb = new WPAlchemy_MetaBox(array
(
	'id' => '_syn_text_meta',
	'title' => 'Synonym Option',
	'types' => array('glossary'), // added only for pages and to custom post type "events"
	'context' => 'side', // same as above, defaults to "normal"
	'priority' => 'high', // same as above, defaults to "high"
	'autosave' => TRUE,
	'template' => TEMPLATEPATH . '/custom/syn_meta.php',
));



/* End of metaboxes */


/*** Options ***/

add_action('admin_menu', 'options_admin_menu');

function options_admin_menu() {
	// here's where we add our theme options page link to the dashboard sidebar
	add_theme_page("Sight Theme Options", "Theme Options", 'update_themes', 'sightoptions', 'options_page');
}

function options_page() {
    if ( $_POST['update_options'] == 'true' ) { options_update(); }  //check options update
	?>
    <div class="wrap">
        <div id="icon-options-general" class="icon32"><br /></div>
		<h2>Sight Theme Options</h2>

        <form method="post" action="">
			<input type="hidden" name="update_options" value="true" />

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="logo_url">Custom logo URL:'); ?></label></th>
                    <td><input type="text" name="logo_url" id="logo_url" size="50" value="<?php echo get_option('logo_url'); ?>"/><br/><span
                            class="description"> <a href="<?php bloginfo("url"); ?>/wp-admin/media-new.php" target="_blank">Upload your logo</a> (max 290px x 128px) using WordPress Media Library and insert its URL here </span><br/><br/><img src="<?php echo (get_option('logo_url')) ? get_option('logo_url') : get_bloginfo('template_url') . '/images/logo.png' ?>"
                     alt=""/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bg_color">Custom background color:'); ?></label></th>
                    <td><input type="text" name="bg_color" id="bg_color" size="20" value="<?php echo get_option('bg_color'); ?>"/><span
                            class="description"> e.g., <strong>#27292a</strong> or <strong>black</strong></span></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="ss_disable">Disable slideshow:'); ?></label></th>
                    <td><input type="checkbox" name="ss_disable" id="ss_disable" <?php echo (get_option('ss_disable'))? 'checked="checked"' : ''; ?>/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="ss_timeout">Timeout for slideshow (ms):'); ?></label></th>
                    <td><input type="text" name="ss_timeout" id="ss_timeout" size="20" value="<?php echo get_option('ss_timeout'); ?>"/><span>Untitled event
                            class="description"> e.g., <strong>7000</strong></span></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label>Pagination:'); ?></label></th>
                    <td>
                        <input type="radio" name="paging_mode" value="default" <?php echo (get_option('paging_mode') == 'default')? 'checked="checked"' : ''; ?>/><span class="description">Default + WP Page-Navi support</span><br/>
                        <input type="radio" name="paging_mode" value="ajax" <?php echo (get_option('paging_mode') == 'ajax')? 'checked="checked"' : ''; ?>/><span class="description">AJAX-fetching posts</span><br/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="ga">Google Analytics code:'); ?></label></th>
                    <td><textarea name="ga" id="ga" cols="48" rows="18"><?php echo get_option('ga'); ?></textarea></td>
                </tr>
            </table>

            <p><input type="submit" value="Save Changes" class="button button-primary" /></p>
        </form>
    </div>
<?php
}

// Update options

function options_update() {
	update_option('logo_url', $_POST['logo_url']);
	update_option('bg_color', $_POST['bg_color']);
	update_option('ss_disable', $_POST['ss_disable']);
	update_option('ss_timeout', $_POST['ss_timeout']);
	update_option('paging_mode', $_POST['paging_mode']);
	update_option('ga', stripslashes_deep($_POST['ga']));
}

/*** Widgets ***/

if (function_exists('register_sidebar')) {

    register_sidebar(array(
        'name'=>'Main Sidebar',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div></div>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3><div class="widget-body clear">'
    ));
	register_sidebar(array(
		'name' => 'Page Sidebar',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget' => '</div></div>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3><div class="widget-body clear">',
	));
	register_sidebar(array(
		'name' => 'Store Sidebar',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget' => '</div></div>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3><div class="widget-body clear">',
	));
	register_sidebar(array(
		'name' => 'Resource Sidebar',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget' => '</div></div>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3><div class="widget-body clear">',
	));
	register_sidebar(array(
		'name' => 'Post Sidebar',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget' => '</div></div>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3><div class="widget-body clear">',
	));
	register_sidebar(array(
		'name' => 'Footer One',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget' => '</div></div>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3><div class="widget-body clear">',
	));
	register_sidebar(array(
		'name' => 'Footer Two',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget' => '</div></div>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3><div class="widget-body clear">',
	));
	register_sidebar(array(
		'name' => 'Footer Three',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget' => '</div></div>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3><div class="widget-body clear">',
	));

}

// Add the 125x125 Ad Block Custom Widget
include("functions/widget-ad125.php");

// Add the 300x250 Ad Block Custom Widget
include("functions/widget-ad300x250.php");

// Add the 120x240 Ad Block Custom Widget
include("functions/widget-ad120x240.php");

// Add the Latest Tweets Custom Widget
include("functions/widget-tweets.php");

// Add the Flickr Photos Custom Widget
//include("functions/widget-flickr.php");

// allow shortcodes in widget
add_filter('widget_text', 'do_shortcode');

// Add the Custom Video Widget
include("functions/widget-video.php");

// Add the Tabbed Content Widget
include("functions/widget-tabbed.php");

/*
Widget Name: Sermons List Widget
Description: Display a list of sermons. Supports multiple usage.
Author: ChurchThemes
Author URI: http://churchthemes.net
*/

add_action('init', 'sermon_list_widget');
function sermon_list_widget() {

	$prefix = 'sermon-list'; // $id prefix
	$name = __('List Resources');
	$widget_ops = array('classname' => 'sermon_list', 'description' => __('Display a list of resources that match a certain criteria and order them however you like. Supports multiple usage.'));
	$control_ops = array('width' => 200, 'height' => 200, 'id_base' => $prefix);

	$options = get_option('sermon_list');
	if(isset($options[0])) unset($options[0]);

	if(!empty($options)){
		foreach(array_keys($options) as $widget_number){
			wp_register_sidebar_widget($prefix.'-'.$widget_number, $name, 'sermon_list', $widget_ops, array( 'number' => $widget_number ));
			wp_register_widget_control($prefix.'-'.$widget_number, $name, 'sermon_list_control', $control_ops, array( 'number' => $widget_number ));
		}
	} else{
		$options = array();
		$widget_number = 1;
		wp_register_sidebar_widget($prefix.'-'.$widget_number, $name, 'sermon_list', $widget_ops, array( 'number' => $widget_number ));
		wp_register_widget_control($prefix.'-'.$widget_number, $name, 'sermon_list_control', $control_ops, array( 'number' => $widget_number ));
	}
}

function sermon_list($args, $vars = array()) {
    extract($args);
    $widget_number = (int)str_replace('sermon-list-', '', @$widget_id);
    $options = get_option('sermon_list');
    if(!empty($options[$widget_number])){
    	$vars = $options[$widget_number];
    }
    // widget open tags
		echo $before_widget;

		// print content and widget end tags
		$title = stripslashes($vars['title']);
		$num = $vars['num'];
		$ids = $vars['ids'];
		if(empty($num)) $num = 3;
		$order_by = $vars['order_by'];
		$the_order = $vars['the_order'];
		$show_image = $vars['show_image'];
		$show_date = $vars['show_date'];
		$show_speaker = $vars['show_speaker'];
		$speaker = $vars['sermon_speaker'];
		if($speaker):
			$the_speaker = get_term_by('id', $speaker, 'sermon_speaker');
			$speaker = $the_speaker->slug;
		endif;
		$service = $vars['sermon_service'];
		if($service):
			$the_service = get_term_by('id', $service, 'sermon_service');
			$service = $the_service->slug;
		endif;
		$series = $vars['sermon_series'];
		if($series):
			$the_series = get_term_by('id', $series, 'sermon_series');
			$series = $the_series->slug;
		endif;
		$topic = $vars['sermon_topic'];
		if($topic):
			$the_topic = get_term_by('id', $topic, 'sermon_topic');
			$topic = $the_topic->slug;
		endif;



		global $post;

if($order_by == 'meta_value_num'):
		$args=array(
			'post_type' => 'ct_sermon',
			'post_status' => 'publish',
			'paged' => true,
			'p' => $id,
			'posts_per_page' => $num,
			'sermon_speaker' => $speaker,
			'sermon_service' => $service,
			'sermon_series' => $series,
			'sermon_topic' => $topic,
			'meta_key' => 'Views',
			'orderby' => $order_by,
			'order' => $the_order,
		);
else:
		$args=array(
			'post_type' => 'ct_sermon',
			'post_status' => 'publish',
			'paged' => true,
			'p' => $id,
			'posts_per_page' => $num,
			'sermon_speaker' => $speaker,
			'sermon_service' => $service,
			'sermon_series' => $series,
			'sermon_topic' => $topic,
			'orderby' => $order_by,
			'order' => $the_order,
		);
endif;

if ($ids) {
	if($order_by == 'meta_value_num'):
		$args=array(
			'post_type' => 'ct_sermon',
			'post_status' => 'publish',
			'paged' => true,
			'post__in' => explode(",",$ids),
			'meta_key' => 'Views',
			'orderby' => $order_by,
			'order' => $the_order,
		);
	else:
	$args=array(
			'post_type' => 'ct_sermon',
			'post_status' => 'publish',
			'paged' => true,
			'post__in' => explode(",",$ids),
			'orderby' => $order_by,
			'order' => $the_order,
		);
	endif;
}
		$query = null;
		$query = new WP_Query($args);

		if($title):
			echo $before_title . $title . $after_title;
			echo "<div class=\"tab\"><ul class=\"list_widget tz_tab_widget\">\n";
			$i = 0;
			if( $query->have_posts() ) : while ($query->have_posts()) : $query->the_post(); $i++;
				$sermon_speaker = get_the_term_list($post->ID, 'sermon_speaker', '', ' + ', '');
				$the_title = strip_tags(get_the_title());
				$the_thumb = get_the_image(array(
								'meta_key' => null,
								'size' => 'mini-thumbnail',
								'image_class' => 'mini-thumbnail',
								'echo' => false,
								'image_class' => 'mini-thumbnail'
								));

				if($query->post_count == 1):
					echo "<li class=\"clearfix first last\">\n";
				elseif($i == 1):
					echo "<li class=\"clearfix first\">\n";
				elseif($i == $query->post_count):
					echo "<li class=\"clearfix last\">\n";
				else:
					echo "<li class=\"clearfix\">\n";
				endif;
				echo "";
				if($show_image == 'true' && !empty($the_thumb)):
					echo $the_thumb;
				endif;

				if($show_image == 'false' || empty($the_thumb)):
					echo "<h3 class=\"entrytitle\"><a class=\"title\" href=\"".get_permalink()."\">".$the_title."</a></h3>\n";
				else:
					echo "<h3 class=\"entrytitle\">".$the_title."</h3>\n";
				endif;

				echo "<div class=\"entry-meta entry-header\">";
				echo "<span class=\"published\">".the_time( get_option('date_format') ) . "</span>";
				echo "</div>";
				echo "</li>\n";
			endwhile; wp_reset_query();
			else:
				echo "<li><p class=\"left noresults\">Sorry, no sermons found.</p></li>";
			endif;
				echo "</ul></div>\n";
				echo $after_widget;
		endif;
}

function sermon_list_control($args) {

	$prefix = 'sermon-list'; // $id prefix

	$options = get_option('sermon_list');
	if(empty($options)) $options = array();
	if(isset($options[0])) unset($options[0]);

	// update options array
	if(!empty($_POST[$prefix]) && is_array($_POST)){
		foreach($_POST[$prefix] as $widget_number => $values){
			if(empty($values) && isset($options[$widget_number])) // user clicked cancel
				continue;

			if(!isset($options[$widget_number]) && $args['number'] == -1){
				$args['number'] = $widget_number;
				$options['last_number'] = $widget_number;
			}
			$options[$widget_number] = $values;
		}

		// update number
		if($args['number'] == -1 && !empty($options['last_number'])){
			$args['number'] = $options['last_number'];
		}

		// clear unused options and update options in DB. return actual options array
		$options = sermon_list_update($prefix, $options, $_POST[$prefix], $_POST['sidebar'], 'sermon_list');
	}

	// $number - is dynamic number for multi widget, gived by WP
	// by default $number = -1 (if no widgets activated). In this case we should use %i% for inputs
	//   to allow WP generate number automatically
	$number = ($args['number'] == -1)? '%i%' : $args['number'];

	// now we can output control
	$opts = @$options[$number];

	$title = @$opts['title'];
	$num = @$opts['num'];
	$order_by = @$opts['order_by'];
	$the_order = @$opts['the_order'];
	$show_image = @$opts['show_image'];
	$show_date = @$opts['show_date'];
	$show_speaker = @$opts['show_speaker'];
	$speaker = @$opts['sermon_speaker'];
	$service = @$opts['sermon_service'];
	$series = @$opts['sermon_series'];
	$topic = @$opts['sermon_topic'];
	$ids = @$opts['ids'];

	?>
	<p>
		<label for="<?php echo $prefix; ?>[<?php echo $number; ?>][title]"><?php _e('Title', 'churchthemes'); ?> *</label>
		<br />
		<input type="text" name="<?php echo $prefix; ?>[<?php echo $number; ?>][title]" value="<?php echo stripslashes($title); ?>" class="widefat<?php if(empty($title)): echo ' error'; endif; ?>" />
	</p>
	<p>
		<label for="<?php echo $prefix; ?>[<?php echo $number; ?>][order_by]"><?php _e('Order By', 'churchthemes'); ?></label>
		<br />
		<select name="<?php echo $prefix; ?>[<?php echo $number; ?>][order_by]">
			<option value="date"<?php if($order_by == 'date'): echo ' selected="selected"'; endif; ?>><?php _e('Post Date', 'churchthemes'); ?></option>
			<option value="title"<?php if($order_by == 'title'): echo ' selected="selected"'; endif; ?>><?php _e('Title', 'churchthemes'); ?></option>
			<option value="modified"<?php if($order_by == 'modified'): echo ' selected="selected"'; endif; ?>><?php _e('Date Modified', 'churchthemes'); ?></option>
			<option value="menu_order"<?php if($order_by == 'menu_order'): echo ' selected="selected"'; endif; ?>><?php _e('Menu Order', 'churchthemes'); ?></option>
			<option value="id"<?php if($order_by == 'id'): echo ' selected="selected"'; endif; ?>><?php _e('Post ID', 'churchthemes'); ?></option>
			<option value="rand"<?php if($order_by == 'rand'): echo ' selected="selected"'; endif; ?>><?php _e('Random', 'churchthemes'); ?></option>
			<option value="meta_value_num"<?php if($order_by == 'meta_value_num'): echo ' selected="selected"'; endif; ?>><?php _e('View Count', 'churchthemes'); ?></option>
		</select>
	</p>
	<p>
		<label for="<?php echo $prefix; ?>[<?php echo $number; ?>][the_order]"><?php _e('Order', 'churchthemes'); ?></label>
		<br />
		<select name="<?php echo $prefix; ?>[<?php echo $number; ?>][the_order]">
			<option value="DESC"<?php if($the_order == 'DESC'): echo ' selected="selected"'; endif; ?>><?php _e('Descending', 'churchthemes'); ?></option>
			<option value="ASC"<?php if($the_order == 'ASC'): echo ' selected="selected"'; endif; ?>><?php _e('Ascending', 'churchthemes'); ?></option>
		</select>
	</p>
	<p>
		<label for="<?php echo $prefix; ?>[<?php echo $number; ?>][show_image]"><?php _e('Thumbnail Image', 'churchthemes'); ?></label>
		<br />
		<select name="<?php echo $prefix; ?>[<?php echo $number; ?>][show_image]">
			<option value="true"<?php if($show_image == 'true'): echo ' selected="selected"'; endif; ?>><?php _e('Show', 'churchthemes'); ?></option>
			<option value="false"<?php if($show_image == 'false'): echo ' selected="selected"'; endif; ?>><?php _e('Hide', 'churchthemes'); ?></option>
		</select>
	</p>
	<p>
		<label for="<?php echo $prefix; ?>[<?php echo $number; ?>][show_date]"><?php _e('Date', 'churchthemes'); ?></label>
		<br />
		<select name="<?php echo $prefix; ?>[<?php echo $number; ?>][show_date]">
			<option value="true"<?php if($show_date == 'true'): echo ' selected="selected"'; endif; ?>><?php _e('Show', 'churchthemes'); ?></option>
			<option value="false"<?php if($show_date == 'false'): echo ' selected="selected"'; endif; ?>><?php _e('Hide', 'churchthemes'); ?></option>
		</select>
	</p>
	<p>
		<label for="<?php echo $prefix; ?>[<?php echo $number; ?>][show_speaker]"><?php _e('Speaker', 'churchthemes'); ?></label>
		<br />
		<select name="<?php echo $prefix; ?>[<?php echo $number; ?>][show_speaker]">
			<option value="true"<?php if($show_speaker == 'true'): echo ' selected="selected"'; endif; ?>><?php _e('Show', 'churchthemes'); ?></option>
			<option value="false"<?php if($show_speaker == 'false'): echo ' selected="selected"'; endif; ?>><?php _e('Hide', 'churchthemes'); ?></option>
		</select>
	</p>
	<p>
		<label for="<?php echo $prefix; ?>[<?php echo $number; ?>]"><?php _e('Display', 'churchthemes'); ?></label>
		<br />
		<?php wp_dropdown_categories('show_option_all=All Speakers&selected='.$speaker.'&show_count=1&hierarchical=0&hide_empty=0&orderby=title&name='.$prefix.'['.$number.'][sermon_speaker]&taxonomy=sermon_speaker'); ?>
	</p>
	<p>
		<?php wp_dropdown_categories('show_option_all=All Services&selected='.$service.'&show_count=1&hierarchical=1&hide_empty=0&orderby=title&name='.$prefix.'['.$number.'][sermon_service]&taxonomy=sermon_service'); ?>
	</p>
	<p>
		<?php wp_dropdown_categories('show_option_all=All Series&selected='.$series.'&show_count=1&hierarchical=1&hide_empty=0&orderby=title&name='.$prefix.'['.$number.'][sermon_series]&taxonomy=sermon_series'); ?>
	</p>
	<p>
		<?php wp_dropdown_categories('show_option_all=All Topics&selected='.$topic.'&show_count=1&hierarchical=0&hide_empty=0&orderby=title&name='.$prefix.'['.$number.'][sermon_topic]&taxonomy=sermon_topic'); ?>
	</p>
	<p>
		<label for="<?php echo $prefix; ?>[<?php echo $number; ?>][num]"><?php _e('Number of Sermons', 'churchthemes'); ?></label>
		<br />
		<input type="text" name="<?php echo $prefix; ?>[<?php echo $number; ?>][num]" size="2" placeholder="3" value="<?php echo stripslashes($num); ?>" />
		<br />
		<small><em><?php _e('Enter -1 to display unlimited results', 'churchthemes'); ?></em></small>
	</p>
	<p>
		<label for="<?php echo $prefix; ?>[<?php echo $number; ?>][ids]"><?php _e('Manual Choices', 'churchthemes'); ?></label>
		<br />
		<input type="text" name="<?php echo $prefix; ?>[<?php echo $number; ?>][ids]" value="<?php echo stripslashes($ids); ?>" class="widefat" />
		<br />
		<small><em><?php _e('Enter sermon ids seperated by commas to override "Display" and "Number"', 'churchthemes'); ?></em></small>
	</p>
	<?php
}

// helper function can be defined in another plugin
if(!function_exists('sermon_list_update')){
	function sermon_list_update($id_prefix, $options, $post, $sidebar, $option_name = ''){
		global $wp_registered_widgets;
		static $updated = false;

		// get active sidebar
		$sidebars_widgets = wp_get_sidebars_widgets();
		if ( isset($sidebars_widgets[$sidebar]) )
			$this_sidebar =& $sidebars_widgets[$sidebar];
		else
			$this_sidebar = array();

		// search unused options
		foreach ( $this_sidebar as $_widget_id ) {
			if(preg_match('/'.$id_prefix.'-([0-9]+)/i', $_widget_id, $match)){
				$widget_number = $match[1];

				// $_POST['widget-id'] contain current widgets set for current sidebar
				// $this_sidebar is not updated yet, so we can determine which was deleted
				if(!in_array($match[0], $_POST['widget-id'])){
					unset($options[$widget_number]);
				}
			}
		}

		// update database
		if(!empty($option_name)){
			update_option($option_name, $options);
			$updated = true;
		}

		// return updated array
		return $options;
	}
}

// Add the Shortcodes
include("functions/theme-shortcodes.php");

class GetConnected extends WP_Widget {

    function GetConnected() {
        parent::WP_Widget(false, $name = 'Social Links');
    }

    function widget($args, $instance) {
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        ?>
            <?php echo $before_widget; ?>
                <?php if ( $title )
                    echo $before_title . $title . $after_title;  else echo '<div class="widget-body clear">'; ?>

                    <!-- RSS -->
                    <div class="getconnected_rss">
                    <a href="<?php echo ( get_option('feedburner_url') )? get_option('feedburner_url') : get_bloginfo('rss2_url'); ?>">RSS Feed</a>
                    <?php //echo (get_option('feedburner_url') && function_exists('feedcount'))? feedcount( get_option('feedburner_url') ) : ''; ?>
                    </div>
                    <!-- /RSS -->

                    <!-- Twitter -->
                    <?php if ( get_option('twitter_url') ) : ?>
                    <div class="getconnected_twitter">
                    <a href="<?php echo get_option('twitter_url'); ?>">Twitter</a>
					<span><?php if ( function_exists('twittercount') ) twittercount( get_option('twitter_url') ); ?> followers</span>
                    </div>
                    <?php endif; ?>
                    <!-- /Twitter -->

                    <!-- Facebook -->
                    <?php if ( get_option('fb_url') ) : ?>
                    <div class="getconnected_fb">
                    <a href="<?php echo get_option('fb_url'); ?>">Facebook</a>
                    <span><?php echo get_option('fb_text'); ?></span>

                    </div>
                    <?php endif; ?>
                    <!-- /Facebook -->

                    <!-- Flickr -->
                    <?php if ( get_option('flickr_url') ) : ?>
                    <div class="getconnected_flickr">
                    <a href="<?php echo get_option('flickr_url'); ?>">Flickr group</a>
                    <span><?php echo get_option('flickr_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Flickr -->

                    <!-- Behance -->
                    <?php if ( get_option('behance_url') ) : ?>
                    <div class="getconnected_behance">
                    <a href="<?php echo get_option('behance_url'); ?>">Behance</a>
                    <span><?php echo get_option('behance_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Behance -->

                    <!-- Delicious -->
                    <?php if ( get_option('delicious_url') ) : ?>
                    <div class="getconnected_delicious">
                    <a href="<?php echo get_option('delicious_url'); ?>">Delicious</a>
                    <span><?php echo get_option('delicious_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Delicious -->

                    <!-- Stumbleupon -->
                    <?php if ( get_option('stumbleupon_url') ) : ?>
                    <div class="getconnected_stumbleupon">
                    <a href="<?php echo get_option('stumbleupon_url'); ?>">Stumbleupon</a>
                    <span><?php echo get_option('stumbleupon_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Stumbleupon -->

                    <!-- Tumblr -->
                    <?php if ( get_option('tumblr_url') ) : ?>
                    <div class="getconnected_tumblr">
                    <a href="<?php echo get_option('tumblr_url'); ?>">Tumblr</a>
                    <span><?php echo get_option('tumblr_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Tumblr -->

                    <!-- Vimeo -->
                    <?php if ( get_option('vimeo_url') ) : ?>
                    <div class="getconnected_vimeo">
                    <a href="<?php echo get_option('vimeo_url'); ?>">Vimeo</a>
                    <span><?php echo get_option('vimeo_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Vimeo -->

                    <!-- Youtube -->
                    <?php if ( get_option('youtube_url') ) : ?>
                    <div class="getconnected_youtube">
                    <a href="<?php echo get_option('youtube_url'); ?>">Youtube</a>
                    <span><?php echo get_option('youtube_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Youtube -->

                    <!-- Mailing List -->
                    <?php if ( get_option('constantcontact_url') ) : ?>
                    <div class="getconnected_constantcontact">
                    <a href="<?php echo get_option('constantcontact_url'); ?>">Mailing List</a>
                    <span><?php echo get_option('constantcontact_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Mailing List -->

            <?php echo $after_widget; ?>
        <?php
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);

        update_option('feedburner_url', $_POST['feedburner_url']);
        update_option('twitter_url', $_POST['twitter_url']);
        update_option('fb_url', $_POST['fb_url']);
        update_option('flickr_url', $_POST['flickr_url']);
        update_option('behance_url', $_POST['behance_url']);
        update_option('delicious_url', $_POST['delicious_url']);
        update_option('stumbleupon_url', $_POST['stumbleupon_url']);
        update_option('tumblr_url', $_POST['tumblr_url']);
        update_option('vimeo_url', $_POST['vimeo_url']);
        update_option('youtube_url', $_POST['youtube_url']);
        update_option('constantcontact_url', $_POST['constantcontact_url']);

        update_option('fb_text', $_POST['fb_text']);
        update_option('flickr_text', $_POST['flickr_text']);
        update_option('behance_text', $_POST['behance_text']);
        update_option('delicious_text', $_POST['delicious_text']);
        update_option('stumbleupon_text', $_POST['stumbleupon_text']);
        update_option('tumblr_text', $_POST['tumblr_text']);
        update_option('vimeo_text', $_POST['vimeo_text']);
        update_option('constantcontact_text', $_POST['constantcontact_text']);

        return $instance;
    }

    function form($instance) {

        $title = esc_attr($instance['title']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>">Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>

            <script type="text/javascript">
                (function($) {
                    $(function() {
                        $('.social_options').hide();
                        $('.social_title').toggle(
                            function(){ $(this).next().slideDown(100) },
                            function(){ $(this).next().slideUp(100) }
                        );
                    })
                })(jQuery)
            </script>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">FeedBurner</a>
                <p class="social_options">
                    <label for="feedburner_url">FeedBurner feed url:'); ?></label>
                    <input type="text" name="feedburner_url" id="feedburner_url" class="widefat"
                           value="<?php echo get_option('feedburner_url'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Twitter</a>
                <p class="social_options">
                    <label for="twitter_url">Profile url:</label>
                    <input type="text" name="twitter_url" id="twitter_url" class="widefat" value="<?php echo get_option('twitter_url'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Facebook</a>
                <p class="social_options">
                    <label for="fb_url">Profile url:</label>
                    <input type="text" name="fb_url" id="fb_url" class="widefat" value="<?php echo get_option('fb_url'); ?>"/>
                    <label for="fb_text">Description:</label>
                    <input type="text" name="fb_text" id="fb_text" class="widefat" value="<?php echo get_option('fb_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Flickr</a>
                <p class="social_options">
                    <label for="flickr_url">Profile url:</label>
                    <input type="text" name="flickr_url" id="flickr_url" class="widefat" value="<?php echo get_option('flickr_url'); ?>"/>
                    <label for="flickr_text">Description:</label>
                    <input type="text" name="flickr_text" id="flickr_text" class="widefat" value="<?php echo get_option('flickr_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Behance</a>
                <p class="social_options">
                    <label for="behance_url">Profile url:</label>
                    <input type="text" name="behance_url" id="behance_url" class="widefat" value="<?php echo get_option('behance_url'); ?>"/>
                    <label for="behance_text">Description:</label>
                    <input type="text" name="behance_text" id="behance_text" class="widefat" value="<?php echo get_option('behance_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Delicious</a>
                <p class="social_options">
                    <label for="delicious_url">Profile url:</label>
                    <input type="text" name="delicious_url" id="delicious_url" class="widefat" value="<?php echo get_option('delicious_url'); ?>"/>
                    <label for="delicious_text">Description:</label>
                    <input type="text" name="delicious_text" id="delicious_text" class="widefat" value="<?php echo get_option('delicious_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Stumbleupon</a>
                <p class="social_options">
                    <label for="stumbleupon_url">Profile url:</label>
                    <input type="text" name="stumbleupon_url" id="stumbleupon_url" class="widefat" value="<?php echo get_option('stumbleupon_url'); ?>"/>
                    <label for="stumbleupon_text">Description:</label>
                    <input type="text" name="stumbleupon_text" id="stumbleupon_text" class="widefat" value="<?php echo get_option('stumbleupon_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Tumblr</a>
                <p class="social_options">
                    <label for="tumblr_url">Profile url:</label>
                    <input type="text" name="tumblr_url" id="tumblr_url" class="widefat" value="<?php echo get_option('tumblr_url'); ?>"/>
                    <label for="tumblr_text">Description:</label>
                    <input type="text" name="tumblr_text" id="tumblr_text" class="widefat" value="<?php echo get_option('tumblr_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Vimeo</a>
                <p class="social_options">
                    <label for="vimeo_url">Profile url:</label>
                    <input type="text" name="vimeo_url" id="vimeo_url" class="widefat" value="<?php echo get_option('vimeo_url'); ?>"/>
                    <label for="vimeo_text">Description:</label>
                    <input type="text" name="vimeo_text" id="vimeo_text" class="widefat" value="<?php echo get_option('vimeo_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Youtube</a>
                <p class="social_options">
                    <label for="youtube_url">Profile url:</label>
                    <input type="text" name="youtube_url" id="youtube_url" class="widefat" value="<?php echo get_option('youtube_url'); ?>"/>
                    <label for="youtube_text">Description:</label>
                    <input type="text" name="youtube_text" id="youtube_text" class="widefat" value="<?php echo get_option('youtube_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Mailing List</a>
                <p class="social_options">
                    <label for="youtube_url">Profile url:</label>
                    <input type="text" name="constantcontact_url" id="constantcontact_url" class="widefat" value="<?php echo get_option('constantcontact_url'); ?>"/>
                    <label for="youtube_text">Description:</label>
                    <input type="text" name="constantcontact_text" id="constantcontact_text" class="widefat" value="<?php echo get_option('constantcontact_text'); ?>"/>
                </p>
            </div>
        <?php
    }

}
add_action('widgets_init', create_function('', 'return register_widget("GetConnected");'));

class Recentposts_thumbnail extends WP_Widget {

    function Recentposts_thumbnail() {
        parent::WP_Widget(false, $name = 'Sight Recent Posts');
    }

    function widget($args, $instance) {
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        ?>
            <?php echo $before_widget; ?>
            <?php if ( $title ) echo $before_title . $title . $after_title;  else echo '<div class="widget-body clear">'; ?>
			<ul>
            <?php
                global $post;
                if (get_option('rpthumb_qty')) $rpthumb_qty = get_option('rpthumb_qty'); else $rpthumb_qty = 5;
                $q_args = array(
                    'numberposts' => $rpthumb_qty,
                );
                $rpthumb_posts = get_posts($q_args);
                foreach ( $rpthumb_posts as $post ) :
                    setup_postdata($post);
            ?>
				<li class="clearfix">
                    <?php
								get_the_image(array(
								'meta_key' => null,
								'size' => 'mini-thumbnail',
								'image_class' => 'mini-thumbnail'

								));
					?>
                    <h3 class="entry-title"><a href="<?php the_permalink(); ?>" class="title"><?php the_title();?></a></h3>
							<div class="entry-meta entry-header">
								<span class="published"><?php the_time( get_option('date_format') ); ?></span>
								<span class="meta-sep">|</span>
								<span class="comment-count"><?php comments_popup_link(__('No comments', 'framework'), __('1 Comment', 'framework'), __('% Comments', 'framework')); ?></span>
							</div>
						</li>


            <?php endforeach; ?>
			</ul>
            <?php echo $after_widget; ?>
        <?php
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        update_option('rpthumb_qty', $_POST['rpthumb_qty']);
        update_option('rpthumb_thumb', $_POST['rpthumb_thumb']);
        return $instance;
    }

    function form($instance) {
        $title = esc_attr($instance['title']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>">Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
            <p><label for="rpthumb_qty">Number of posts:  </label><input type="text" name="rpthumb_qty" id="rpthumb_qty" size="2" value="<?php echo get_option('rpthumb_qty'); ?>"/></p>
            <p><label for="rpthumb_thumb">Hide thumbnails:  </label><input type="checkbox" name="rpthumb_thumb" id="rpthumb_thumb" <?php echo (get_option('rpthumb_thumb'))? 'checked="checked"' : ''; ?>/></p>
        <?php
    }

}
add_action('widgets_init', create_function('', 'return register_widget("Recentposts_thumbnail");'));

/*** Comments ***/

function commentslist($comment, $args, $depth) {
	$GLOBALS['comment'] = $comment;
        require_once(ABSPATH . WPINC . '/registration.php');
        if ($comment->user_id || email_exists($comment->comment_author_email)){
            //comment by registered user
            $avatar = '/images/bird_comments_big.png';
            }else{
            //comment by none registered user
            $avatar = '/images/bird_comments_pink.png';
            }
        ?>
	<li>
        <div id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
            <table>
                <tr>
                    <td>
                        <?php echo get_avatar($comment, 70, get_bloginfo('template_url').$avatar); ?>
                        <?php if ($comment->comment_type) echo '<img class="avatar avatar-70 photo" height="70" width="70" src="'.get_bloginfo('template_url').'/images/trackback.gif" />';?>
                    </td>
                    <td>
                        <div class="comment-meta">
                            <?php printf(__('<p class="comment-author"><span>%s</span> says:</p>'), get_comment_author_link()) ?>
                            <?php printf(__('<p class="comment-date">%s</p>'), get_comment_date('M j, Y')) ?>
                            <?php comment_reply_link(array_merge($args, array('depth' => $depth, 'max_depth' => $args['max_depth']))) ?>
                        </div>
                    </td>
                    <td>
                        <div class="comment-text">
                            <?php if ($comment->comment_approved == '0') : ?>
                                <p>Your comment is awaiting moderation.') ?></p>
                                <br/>
                            <?php endif; ?>
                            <?php comment_text() ?>
                        </div>
                    </td>
                </tr>
            </table>
         </div>
<?php
}

/*** Misc ***/

function feedcount($feedurl='http://feeds.feedburner.com/wpshower') {
    $feedid = explode('/', $feedurl);
    $feedid = end($feedid);
    $twodayago = date('Y-m-d', strtotime('-2 days', time()));
    $onedayago = date('Y-m-d', strtotime('-1 days', time()));
    $today = date('Y-m-d');

    $api = "https://feedburner.google.com/api/awareness/1.0/GetFeedData?uri=$feedid&dates=$twodayago,$onedayago";

    //Initialize a cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $api);
    $data = curl_exec($ch);
    $base_code = curl_getinfo($ch);
    curl_close($ch);

    if ($base_code['http_code']=='401'){
        $burner_count_circulation = 'This feed does not permit Awareness API access';
        $burner_date = $today;
    } else {

        $xml = new SimpleXMLElement($data, TRUE); //Parse XML via SimpleXML Class
        $bis = $xml->attributes();  //Bis Contain first attribute, It usually is ok or fail in FeedBurner

        if ($bis=='ok'){
            foreach ($xml->feed as $feed) {
                if ($feed->entry[1]['circulation']=='0'){
                    $burner_count_circulation = $feed->entry[0]['circulation'];
                    $burner_date  =  $feed->entry[0]['date'];
                } else {
                    $burner_count_circulation = $feed->entry[1]['circulation'];
                    $burner_date  =  $feed->entry[1]['date'];
                }
            }
        }

        if ($bis=='fail'){
            switch ($xml->err['code']) {
                case 1:
                    $burner_count_circulation = 'Feed Not Found';
                    break;
                case 5:
                    $burner_count_circulation = 'Missing required parameter (URI)';
                    break;
                case 6:
                    $burner_count_circulation = 'Malformed parameter (DATES)';
                    break;
            }
            $burner_date = $today;
        }

    }
    if ( $bis != 'fail' && $burner_count_circulation != '' ) {
        echo '<span>'.$burner_count_circulation.' readers</span>';
    } else {
        echo '<span></span>';
    }
}

function twittercount($twitter_url='https://twitter.com/users/show/mockingbirdmin') {
    $url = "https://twitter.com/users/show/mockingbirdmin";
	$response = file_get_contents ( $url );
	$t_profile = new SimpleXMLElement ( $response );
	$count = $t_profile->followers_count;
	echo $count;
}

function seo_title() {
    global $page, $paged;
    $sep = " | "; # delimiter
    $newtitle = get_bloginfo('name'); # default title

    # Single & Page ##################################
    if (is_single() || is_page())
        $newtitle = single_post_title("", false);

    # Category ######################################
    if (is_category())
        $newtitle = single_cat_title("", false);

    # Tag ###########################################
    if (is_tag())
     $newtitle = single_tag_title("", false);

    # Search result ################################
    if (is_search())
     $newtitle = "Search Result " . $s;

    # Taxonomy #######################################
    if (is_tax()) {
        $curr_tax = get_taxonomy(get_query_var('taxonomy'));
        $curr_term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy')); # current term data
        # if it's term
        if (!empty($curr_term)) {
            $newtitle = $curr_tax->label . $sep . $curr_term->name;
        } else {
            $newtitle = $curr_tax->label;
        }
    }

    # Page number
    if ($paged >= 2 || $page >= 2)
            $newtitle .= $sep . sprintf('Page %s', max($paged, $page));

    # Home & Front Page ########################################
    if (is_home() || is_front_page()) {
        $newtitle = get_bloginfo('name') . $sep . get_bloginfo('description');
    } else {
        $newtitle .=  $sep . get_bloginfo('name');
    }
	return $newtitle;
}
add_filter('wp_title', 'seo_title');

//function new_excerpt_length($length) {
//	return 200;
//}
//add_filter('excerpt_length', 'new_excerpt_length');


function getTinyUrl($url) {
    $tinyurl = file_get_contents("http://tinyurl.com/api-create.php?url=".$url);
    return $tinyurl;
}

function smart_excerpt($string, $limit) {
    $words = explode(" ",$string);
    if ( count($words) >= $limit) $dots = '...';
    echo implode(" ",array_splice($words,0,$limit)).$dots;
}

function find_image() {
	static $found_image = true;
	$numargs = func_num_args();

	if ($numargs > 0) {
		$found_image = false;
		return false;
	}

	elseif ($found_image == false) {
	$found_image = true;
	return false;
	}

	return true;
}



// my excerpts
function new_wp_trim_excerpt($text) { // Fakes an excerpt if needed
	global $post;
	$post_type = get_post_type($post);

	switch($post_type) {
	case 'ct_sermon': $teaser = '<p class="moarplz"><a href="'. get_permalink($post->ID) . '">Listen, Download and Share</a></p>';
	break;

	default: $teaser = '<p class="moarplz"><a href="'. get_permalink($post->ID) . '">Read More > > ></a></p>';
	}


	if ( '' == $text ) {
		$text = get_the_content('');
		$text = apply_filters('the_content', $text);
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text, '<p>');
		$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
		$excerpt_length = 100;
		$words = explode(' ', $text, $excerpt_length + 1);
		if (count($words)> $excerpt_length) {
			$dots = '...';
			array_pop($words);
			$text = implode(' ', $words).$dots.$teaser;
		}
		else
		{
			$text = get_the_content();
		}
	}
	return $text;
}

remove_filter('get_the_excerpt', 'wp_trim_excerpt');
add_filter('get_the_excerpt', 'new_wp_trim_excerpt');

function comments_link_attributes(){
    return 'class="comments_popup_link"';
}
add_filter('comments_popup_link_attributes', 'comments_link_attributes');

function next_posts_attributes(){
    return 'class="nextpostslink"';
}
add_filter('next_posts_link_attributes', 'next_posts_attributes');




// Add browser detection class to body tag
add_filter('body_class','tz_browser_body_class');
function tz_browser_body_class($classes) {
	global $is_lynx, $is_gecko, $is_IE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone;

	if($is_lynx) $classes[] = 'lynx';
	elseif($is_gecko) $classes[] = 'gecko';
	elseif($is_opera) $classes[] = 'opera';
	elseif($is_NS4) $classes[] = 'ns4';
	elseif($is_safari) $classes[] = 'safari';
	elseif($is_chrome) $classes[] = 'chrome';
	elseif($is_IE) $classes[] = 'ie';
	else $classes[] = 'unknown';

	if($is_iphone) $classes[] = 'iphone';
	return $classes;
}


// Output the styling for the seperated Pings
function tz_list_pings($comment, $args, $depth) {
       $GLOBALS['comment'] = $comment; ?>
<li id="comment-<?php comment_ID(); ?>"><?php comment_author_link(); ?>
<?php }


// Make a custom login logo and link
function tz_custom_login_logo() {
    echo '<style type="text/css">
        h1 a { background-image:url('.get_bloginfo('template_directory').'/images/custom-login-logo.png) !important; }
    </style>';
}
function tz_wp_login_url() {
echo bloginfo('url');
}
function tz_wp_login_title() {
echo get_option('blogname');
}

add_action('login_head', 'tz_custom_login_logo');
add_filter('login_headerurl', 'tz_wp_login_url');
add_filter('login_headertitle', 'tz_wp_login_title');

// remove version #
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wlwmanifest_link');

// author pages

/**
 * Overwrites the user's nicename with the user's display name
 *
 * Runs every time a user is created or updated
 *
 * @author	Konstantin Obenland
 * @since	1.0 - 19.02.2011
 *
 * @param	string	$name	The default nicename
 *
 * @return	string	The sanitized nicename
 */
function wp_author_slug_pre_user_nicename( $name ){

	if( ! empty($_REQUEST['display_name']) ){
		return sanitize_title( $_REQUEST['display_name'] );
	}

	return $name;
}
add_filter( 'pre_user_nicename', 'wp_author_slug_pre_user_nicename' );

// Hook for post status changes
add_filter('transition_post_status', 'notify_status',10,3);
function notify_status($new_status, $old_status, $post) {
    global $current_user;
	$contributor = get_userdata($post->post_author);
    if ($old_status != 'pending' && $new_status == 'pending') {
      $emails=get_option('admin_email');
      if(strlen($emails)) {
        $subject='['.get_option('blogname').'] "'.$post->post_title.'" pending review';
        $message="A new post by {$contributor->display_name} is pending review.\n\n";
        $message.="Author   : {$contributor->user_login} <{$contributor->user_email}> (IP: {$_SERVER['REMOTE_ADDR']})\n";
        $message.="Title    : {$post->post_title}\n";
		$category = get_the_category($post->ID);
		if(isset($category[0]))
			$message.="Category : {$category[0]->name}\n";;
        $message.="Review it: ".get_option('siteurl')."/wp-admin/post.php?action=edit&post={$post->ID}\n\n\n";

        wp_mail( $emails, $subject, $message);
      }
	}
}

// glossary

//Add options needed for plugin
	add_option('red_glossaryOnlySingle', 0); //Show on Home and Category Pages or just single post pages?
	add_option('red_glossaryOnPages', 1); //Show on Pages or just posts?
	add_option('red_glossaryID', 0); //The ID of the main Glossary Page
	add_option('red_glossaryTooltip', 0); //Use tooltips on glossary items?
	add_option('red_glossaryDiffLinkClass', 0); //Use different class to style glossary list
	add_option('red_glossaryPermalink', 'glossary'); //Set permalink name
	add_option('red_glossaryFirstOnly', 0); //Search for all occurances in a post or only one?
// Register glossary custom post type
	function create_post_types(){
		$glossaryPermalink = get_option('red_glossaryPermalink');
		$labels = array(
    'name' => _x('Glossary', 'post type general name'),
    'singular_name' => _x('Term', 'post type singular name'),
    'add_new' => _x('Add New', 'term'),
    'add_new_item' => __('Add New Term'),
    'edit_item' => __('Edit Term'),
    'new_item' => __('New Term'),
    'all_items' => __('Glossary'),
    'view_item' => __('View Glossary'),
    'search_items' => __('Search Glossary'),
    'not_found' =>  __('No term found'),
	'not_found_in_trash' => __('No term found in Trash'),
    'parent_item_colon' => '',
    'menu_name' => 'Glossary'

  );
		$args = array(
			'labels' => $labels,
			'description' => '',
			'public' => true,
			'show_ui' => true,
			'_builtin' => false,
			'capability_type' => 'post',
			'hierarchical' => false,
			'rewrite' => array('slug' => $glossaryPermalink),
			'query_var' => true,
			'supports' => array('title','editor','excerpt'));
		register_post_type('glossary',$args);
		flush_rewrite_rules();
	}
	add_action( 'init', 'create_post_types');

function red_glossary_parse($content){

	global $glossexclude_checkbox_mb; // check for "omit glossary links"
	$glossmeta = $glossexclude_checkbox_mb->the_meta();
	if (is_array($glossmeta) && $glossmeta["cb_single"] == "yes"){
		return $content;
	}

	//Run the glossary parser
	if (((!is_page() && get_option('red_glossaryOnlySingle') == 0) OR
	(!is_page() && get_option('red_glossaryOnlySingle') == 1 && is_single()) OR
	(is_page() && get_option('red_glossaryOnPages') == 1))){
		$glossary_index = get_children(array(
											'post_type'		=> 'glossary',
											'post_status'	=> 'publish',
											));
		$current_title = get_the_title();
		if ($glossary_index){
			$timestamp = time();
			foreach($glossary_index as $glossary_item){
				$timestamp++;
				$glossary_title = $glossary_item->post_title;
				if ($current_title == $glossary_title) { // make sure no links to self
				continue;
				}
				$glossary_search = '/\b'.$glossary_title.'s*?\b(?=([^"]*"[^"]*")*[^"]*$)/i';
				$glossary_replace = '<a'.$timestamp.'>$0</a'.$timestamp.'>';
				if (get_option('red_glossaryFirstOnly') == 1) {
					$content_temp = preg_replace($glossary_search, $glossary_replace, $content, 1);
				}
				else {
					$content_temp = preg_replace($glossary_search, $glossary_replace, $content);
				}
				$content_temp = rtrim($content_temp);

					$link_search = '/<a'.$timestamp.'>('.$glossary_item->post_title.'[A-Za-z]*?)<\/a'.$timestamp.'>/i';
					if (get_option('red_glossaryTooltip') == 1) {
						$link_replace = '<a class="glossaryLink" href="' . get_permalink($glossary_item) . '" title="Glossary: '. $glossary_title . '" onmouseover="tooltip.show(\'' . addslashes($glossary_item->post_excerpt) . '\');" onmouseout="tooltip.hide();">$1</a>';
					}
					else {
						$link_replace = '<a class="glossaryLink" href="' . get_permalink($glossary_item) . '" title="Glossary: '. $glossary_title . '">$1</a>';
					}
					$content_temp = preg_replace($link_search, $link_replace, $content_temp);
					$content = $content_temp;
			}
		}
	}
	return $content;
}


//Make sure parser runs before the post or page content is outputted
add_filter('the_content', 'red_glossary_parse');

//create the actual glossary
function red_glossary_createList($content){
	$glossaryPageID = get_option('red_glossaryID');
	if (is_numeric($glossaryPageID) && is_page($glossaryPageID)){
		$glossary_index = get_children(array(
											'post_type'		=> 'glossary',
											'post_status'	=> 'publish',
											'orderby'		=> 'title',
											'order'			=> 'ASC',
											));
		if ($glossary_index){
			$content .= '<div id="glossaryList">';
			$letters = array();
			//style links based on option
			if (get_option('red_glossaryDiffLinkClass') == 1) {
				$glossary_style = 'glossaryLinkMain';
			}
			else {
				$glossary_style = 'glossaryLink';
			}
			foreach($glossary_index as $glossary_item){
				$term = $glossary_item->post_title;
				$letter = $term[0];
				if (!in_array($letter, $letters)) {
					array_push($letters, $letter);
					$content .= '<h4>'.$letter.'</h4>';
				}
				//show tooltip based on user option
				if (get_option('red_glossaryTooltip') == 1) {
					$content .= '<p><a class="' . $glossary_style . '" href="' . get_permalink($glossary_item) . '" onmouseover="tooltip.show(\'' . addslashes($glossary_item->post_content) . '\');" onmouseout="tooltip.hide();">'. $term . '</a></p>';
				}
				else {
					$content .= '<p><a class="' . $glossary_style . '" href="' . get_permalink($glossary_item) . '">'. $term . '</a></p>';
				}
			}
			$content .= '</div>';
		}
	}
	return $content;
}

add_filter('the_content', 'red_glossary_createList');


//admin page user interface
add_action('admin_menu', 'glossary_menu');

function glossary_menu() {
  add_options_page('Glossary Options', 'Glossary', 8, __FILE__, 'glossary_options');
}

function glossary_options() {
	if (isset($_POST["red_glossarySave"])) {
		//update the page options
		update_option('red_glossaryID',$_POST["red_glossaryID"]);
		update_option('red_glossaryID',$_POST["red_glossaryPermalink"]);
		$options_names = array('red_glossaryOnlySingle', 'red_glossaryOnPages', 'red_glossaryTooltip', 'red_glossaryDiffLinkClass', 'red_glossaryFirstOnly');
		foreach($options_names as $option_name){
			if ($_POST[$option_name] == 1) {
				update_option($option_name,1);
			}
			else {
				update_option($option_name,0);
			}
		}
	}
	?>

<div class="wrap">
  <h2>Glossary</h2>
  <form method="post" action="options.php">
    <?php wp_nonce_field('update-options');	?>
    <table class="form-table">
      <tr valign="top">
        <th scope="row">Main Glossary Page</th>
        <td><input type="text" name="red_glossaryID" value="<?php echo get_option('red_glossaryID'); ?>" /></td>
        <td colspan="2">Enter the page ID of the page you would like to use as the glossary (list of terms).  The page will be generated automatically for you on the specified page (so you should leave the content blank).  This is optional - terms will still be highlighted in relevant posts/pages but there won't be a central list of terms if this is left blank.</td>
      </tr>
      <tr valign="top">
        <th scope="row">Only show terms on single pages?</th>
        <td><input type="checkbox" name="red_glossaryOnlySingle" <?php checked(true, get_option('red_glossaryOnlySingle')); ?> value="1" /></td>
        <td colspan="2">Select this option if you wish to only highlight glossary terms when viewing a single page/post.  This can be used so terms aren't highlighted on your homepage for example.</td>
      </tr>
      <tr valign="top">
        <th scope="row">Highlight terms on pages?</th>
        <td><input type="checkbox" name="red_glossaryOnPages" <?php checked(true, get_option('red_glossaryOnPages')); ?> value="1" /></td>
        <td colspan="2">Select this option if you wish for the glossary to highlight terms on pages as well as posts.  With this deselected, only posts will be searched for matching glossary terms.</td>
      </tr>
      <tr valign="top">
        <th scope="row">Use tooltip?</th>
        <td><input type="checkbox" name="red_glossaryTooltip" <?php checked(true, get_option('red_glossaryTooltip')); ?> value="1" /></td>
        <td colspan="2">Select this option if you wish for the definition to show in a tooltip when the user hovers over the term.  The tooltip can be style differently using the tooltip.css and tooltip.js files in the plugin folder.</td>
      </tr>
      <tr valign="top">
        <th scope="row">Style main glossary page differently?</th>
        <td><input type="checkbox" name="red_glossaryDiffLinkClass" <?php checked(true, get_option('red_glossaryDiffLinkClass')); ?> value="1" /></td>
        <td colspan="2">Select this option if you wish for the links in the main glossary listing to be styled differently than the term links.  By selecting this option you will be able to use the class 'glossaryLinkMain' to style only the links on the glossary page otherwise they will retain the class 'glossaryLink' and will be identical to the linked terms.</td>
      </tr>
      <tr valign="top">
        <th scope="row">Glossary Permalink</th>
        <td><input type="text" name="red_glossaryPermalink" value="<?php echo get_option('red_glossaryPermalink'); ?>" /></td>
        <td colspan="2">Enter the name you would like to use for the permalink to the glossary.  By default this is glossary, however you can update this if you wish. eg. http://mysite.com/<strong>glossary</strong>/term</td>
      </tr>
      <tr valign="top">
        <th scope="row">Highlight first occurance only?</th>
        <td><input type="checkbox" name="red_glossaryFirstOnly" <?php checked(true, get_option('red_glossaryFirstOnly')); ?> value="1" /></td>
        <td colspan="2">Select this option if you want to only highlight the first occurance of each term on a page/post.</td>
      </tr>
    </table>
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="red_glossaryID,red_glossaryOnlySingle,red_glossaryOnPages,red_glossaryTooltip,red_glossaryDiffLinkClass,red_glossaryPermalink,red_glossaryFirstOnly" />
    <p class="submit">
      <input type="submit" class="button-primary" value="Save Changes') ?>" name="red_glossarySave" />
    </p>
  </form>
</div>
<?php
}
/*
Plugin Name: Simply Show IDs
Plugin URI: http://sivel.net/wordpress/simply-show-ids/
Description: Simply shows the ID of Posts, Pages, Media, Links, Categories, Tags and Users in the admin tables for easy access. Very lightweight.
Author: Matt Martz
Author URI: http://sivel.net
Version: 1.3.3

	Copyright (c) 2009-2010 Matt Martz (http://sivel.net)
	Simply Show IDs is released under the GNU General Public License (GPL)
	http://www.gnu.org/licenses/gpl-2.0.txt
*/

// Prepend the new column to the columns array
function ssid_column($cols) {
	$cols['ssid'] = 'ID';
	return $cols;
}

// Echo the ID for the new column
function ssid_value($column_name, $id) {
	if ($column_name == 'ssid')
		echo $id;
}

function ssid_return_value($value, $column_name, $id) {
	if ($column_name == 'ssid')
		$value = $id;
	return $value;
}

// Output CSS for width of new column
function ssid_css() {
?>
<style type="text/css">
	#ssid { width: 50px; } /* Simply Show IDs */
</style>
<?php
}

// Actions/Filters for various tables and the css output
function ssid_add() {
	add_action('admin_head', 'ssid_css');

	add_filter('manage_posts_columns', 'ssid_column');
	add_action('manage_posts_custom_column', 'ssid_value', 10, 2);

	add_filter('manage_pages_columns', 'ssid_column');
	add_action('manage_pages_custom_column', 'ssid_value', 10, 2);

	add_filter('manage_media_columns', 'ssid_column');
	add_action('manage_media_custom_column', 'ssid_value', 10, 2);

	add_filter('manage_link-manager_columns', 'ssid_column');
	add_action('manage_link_custom_column', 'ssid_value', 10, 2);

	add_action('manage_edit-link-categories_columns', 'ssid_column');
	add_filter('manage_link_categories_custom_column', 'ssid_return_value', 10, 3);

	foreach ( get_taxonomies() as $taxonomy ) {
		add_action("manage_edit-${taxonomy}_columns", 'ssid_column');
		add_filter("manage_${taxonomy}_custom_column", 'ssid_return_value', 10, 3);
	}

	add_action('manage_users_columns', 'ssid_column');
	add_filter('manage_users_custom_column', 'ssid_return_value', 10, 3);

	add_action('manage_edit-comments_columns', 'ssid_column');
	add_action('manage_comments_custom_column', 'ssid_value', 10, 2);
}

add_action('admin_init', 'ssid_add');

// no share on glossary
function page_disable_share($post_id)
{
if ( 'glossary' != get_post_type() )
    return;

    update_post_meta($post_id, "sharing_disabled", 1);
}
add_action('save_post', 'page_disable_share');

function admin_private_parent_metabox($output)
{
	global $post;

	$args = array(
		'post_type'			=> $post->post_type,
		'exclude_tree'		=> $post->ID,
		'selected'			=> $post->post_parent,
		'name'				=> 'parent_id',
		'show_option_none'	=> __('(no parent)'),
		'sort_column'		=> 'menu_order, post_title',
		'echo'				=> 0,
		'post_status'		=> array('publish', 'private'),
	);

	$defaults = array(
		'depth'					=> 0,
		'child_of'				=> 0,
		'selected'				=> 0,
		'echo'					=> 1,
		'name'					=> 'page_id',
		'id'					=> '',
		'show_option_none'		=> '',
		'show_option_no_change'	=> '',
		'option_none_value'		=> '',
	);

	$r = wp_parse_args($args, $defaults);
	extract($r, EXTR_SKIP);

	$pages = get_pages($r);
	$name = esc_attr($name);
	// Back-compat with old system where both id and name were based on $name argument
	if (empty($id))
	{
		$id = $name;
	}

	if (!empty($pages))
	{
		$output = "<select name=\"$name\" id=\"$id\">\n";

		if ($show_option_no_change)
		{
			$output .= "\t<option value=\"-1\">$show_option_no_change</option>";
		}
		if ($show_option_none)
		{
			$output .= "\t<option value=\"" . esc_attr($option_none_value) . "\">$show_option_none</option>\n";
		}
		$output .= walk_page_dropdown_tree($pages, $depth, $r);
		$output .= "</select>\n";
	}

	return $output;
}
add_filter('wp_dropdown_pages', 'admin_private_parent_metabox');

add_action('wp_head', 'the_slug');
function the_slug($echo=true){
  $slug = basename(get_permalink());
  do_action('before_slug', $slug);
  $slug = apply_filters('slug_filter', $slug);
  if( $echo ) echo $slug;
  do_action('after_slug', $slug);
  return $slug;
}

/** Store **/
add_theme_support( 'woocommerce' );

remove_action( 'woocommerce_product_tabs', 'on_show_included_products');
remove_action( 'woocommerce_product_tab_panels' ,'on_show_included_products_panel' );

add_filter('upload_mimes', 'custom_upload_mimes');
function custom_upload_mimes ( $existing_mimes=array() ) {
// add your extension to the array
$existing_mimes['prc'] = 'application/x-mobipocket-ebook';
$existing_mimes['mobi'] = 'application/x-mobipocket-ebook';
$existing_mimes['epub'] = 'application/epub+zip';
// add as many as you like
// removing existing file types
return $existing_mimes;
}

// Sermon stuff

/**s
 * Post list shortcode
 * @shortcode post_list
 */
function churchthemes_posts_shortcode($atts, $content = null){
	global $post, $wp_query;
	extract(shortcode_atts(array(
		// Default behaviors
		'post_status' => 'publish',
		'num' => get_option( 'posts_per_page' ),
		'paging' => 'show',
		'images' => 'show',
		'offset' => '', // number of posts to displace
		'orderby' => 'date',
		'order' => 'DESC',
		'p' => '', // post ID
		'name' => '', // post slug
		'post__in' => '', // posts to retrieve, comma separated IDs
		'post__not_in' => '', // posts to ignore, comma separated IDs
		'year' => '', // 4 digit year (e.g. 2012)
		'monthnum' => '', // 1-12
		'w' => '', // 0-53
		'day' => '', // 1-31
		'hour' => '', // 0-23
		'minute' => '', // 0-60
		'second' => '', // 0-60
		'author' => '', // author ID
		'author_name' => '', // author username
		'tag' => '', // tag slug, if separated by "+" the functionality becomes identical to tag_slug__and
		'tag_id' => '', // tag ID
		'tag__and' => '', // posts that are tagged both x AND y, comma separated IDs
		'tag__in' => '', // posts that are tagged x OR y, comma separated IDs
		'tag__not_in' => '', // exclude posts with these tags, comma separated IDs
		'tag_slug__and' => '', // posts that are tagged both x AND y, comma separated slugs
		'tag_slug__in' => '', // posts that are tagged x OR y, comma separated slugs
		'cat' => '', // category ID
		'category_name' => '', // category slug
		'category__and' => '', // posts that are in both categories x AND y, comma separated IDs
		'category__in' => '', // posts that are in categories x OR y, comma separated IDs
		'category__not_in' => '', // exclude posts from these categories, comma separated IDs
	), $atts));

	if($orderby == 'views'): $orderby = 'meta_value_num'; endif;
	if($paging == 'hide'):
		$paged = null;
	else:
		$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
	endif;

	$args = array(
		'post_type' => 'post', // only return posts
		'post_status' => $post_status, // default: publish
		'posts_per_page' => $num, // default: Settings > Reading > Blog pages show at most
		'paged' => $paged, // default: paged
		'offset' => $offset,
		'orderby' => $orderby, // default: date
		'order' => $order, // default: DESC
		'p' => $p,
		'name' => $name,
		'year' => $year,
		'monthnum' => $monthnum,
		'w' => $w,
		'day' => $day,
		'hour' => $hour,
		'minute' => $minute,
		'second' => $second,
		'author' => $author,
		'author_name' => $author_name,
		'tag' => $tag,
		'cat' => $cat,
		'category_name' => $category_name,
	);

	// the following parameters require array values
	if ($orderby == 'meta_value_num') {
		$args = array_merge( $args, array( 'meta_key' => 'Views' ) );
	}
	if ($post__in) {
		$args = array_merge( $args, array( 'post__in' => explode(',', $post__in) ) );
	}
	if ($post__not_in) {
		$args = array_merge( $args, array( 'post__not_in' => explode(',', $post__not_in) ) );
	}
	if ($tag_id) {
		$args = array_merge( $args, array( 'tag_id' => explode(',', $tag_id) ) );
	}
	if ($tag__and) {
		$args = array_merge( $args, array( 'tag__and' => explode(',', $tag__and) ) );
	}
	if ($tag__in) {
		$args = array_merge( $args, array( 'tag__in' => explode(',', $tag__in) ) );
	}
	if ($tag__not_in) {
		$args = array_merge( $args, array( 'tag__not_in' => explode(',', $tag__not_in) ) );
	}
	if ($tag_slug__and) {
		$args = array_merge( $args, array( 'tag_slug__and' => explode(',', $tag_slug__and) ) );
	}
	if ($tag_slug__in) {
		$args = array_merge( $args, array( 'tag_slug__in' => explode(',', $tag_slug__in) ) );
	}
	if ($category__and) {
		$args = array_merge( $args, array( 'category__and' => explode(',', $category__and) ) );
	}
	if ($category__in) {
		$args = array_merge( $args, array( 'category__in' => explode(',', $category__in) ) );
	}
	if ($category__not_in) {
		$args = array_merge( $args, array( 'category__not_in' => explode(',', $category__not_in) ) );
	}

	query_posts($args);

	ob_start();
	if ( $images != 'hide' ) {
			include('shortcode-posts.php');
	}
	else {
		include('shortcode-posts-noimage.php');
	}
	if($paging != 'hide') {
		pagination();
	}
	wp_reset_query();
	$content = ob_get_clean();
	return $content;
}
add_shortcode( 'posts', 'churchthemes_posts_shortcode' );

// End Posts Shortcode

/* SERMON */

// Register Post Type
add_action('init', 'sm_register');

function sm_register() {
	$labels = array(
		'name' => 'Resources',
		'singular_name' => 'Resource',
		'add_new' => _x( 'Add New', 'ct_sermon' ),
		'add_new_item' => 'Add New Resource',
		'edit_item' => 'Edit Resource',
		'new_item' => 'New Resource',
		'view_item' => 'View Resource',
		'search_items' => 'Search Resources',
		'not_found' =>  'No Resourcess found',
		'not_found_in_trash' => 'No Resources found in Trash' ,
		'parent_item_colon' => ''
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
    'show_in_rest' => true,
		'publicly_queryable' => true,
		'exclude_from_search' => false,
		'show_ui' => true,
		'has_archive' => 'resources',
		'query_var' => true,
		'rewrite' => array('slug' => $archive_slug),
		'capability_type' => 'post',
		'hierarchical' => false,
		'menu_position' => 10,
		'menu_icon' => get_template_directory_uri() . '/images/menu_icon-sermon-16.png',
		'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'comments','custom-fields' ),
		'taxonomies' => array( 'sermon_speaker', 'sermon_format', 'sermon_series', 'sermon_topic' )
	);

	register_post_type('ct_sermon', $args);

	flush_rewrite_rules(false);

}
// End Register Post Type

// Create Custom Taxonomies
add_action( 'init', 'create_sermon_taxonomies', 0 );

function create_sermon_taxonomies() {

	// Speakers Taxonomy (Non-Hierarchical)
	$labels = array(
		'name' => _x( 'Speakers', 'taxonomy general name' ),
		'singular_name' => _x( 'Speaker', 'taxonomy singular name' ),
		'search_items' =>  'Search Speakers',
		'popular_items' => 'Popular Speakers',
		'all_items' => 'All Speakers',
		'parent_item' => null,
		'parent_item_colon' => null,
		'edit_item' => 'Edit Speaker',
		'update_item' => 'Update Speaker',
		'add_new_item' => 'Add New Speaker',
		'new_item_name' => 'New Speaker Name',
		'separate_items_with_commas' => 'Separate Speakers with commas',
		'add_or_remove_items' => 'Add or remove Speakers',
		'choose_from_most_used' => 'Choose from the most used Speakers'
	);
	register_taxonomy( 'sermon_speaker', 'ct_sermon', array(
		'hierarchical' => false,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
    'show_in_rest' => true,
		'rewrite' => array( 'slug' => 'speakers' ),
	));
	// End Speakers Taxonomy

	// Services Taxonomy (Hierarchical)
	$labels = array(
		'name' => _x( 'Formats', 'taxonomy general name' ),
		'singular_name' => _x( 'Format', 'taxonomy singular name' ),
		'search_items' =>  'Search Formats',
		'all_items' => 'All Formats',
		'parent_item' => 'Parent Format',
		'parent_item_colon' => 'Parent Format:',
		'edit_item' => 'Edit Format',
		'update_item' => 'Update Format',
		'add_new_item' => 'Add New Format',
		'new_item_name' => 'New Format Name',
	);
	register_taxonomy( 'sermon_format', array( 'ct_sermon' ), array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
    'show_in_rest' => true,
		'rewrite' => array( 'slug' => 'formats' ),
	));
	// End Services Taxonomy

	// Series Taxonomy (Hierarchical)
	$labels = array(
		'name' => _x( 'Series', 'taxonomy general name' ),
		'singular_name' => _x( 'Series', 'taxonomy singular name' ),
		'search_items' =>  'Search Series',
		'all_items' => 'All Series',
		'parent_item' => null,
		'parent_item_colon' => null,
		'edit_item' => 'Edit Series',
		'update_item' => 'Update Series',
		'add_new_item' => 'Add New Series',
		'new_item_name' => 'New Series Name',
	);
	register_taxonomy( 'sermon_series', array( 'ct_sermon' ), array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
    'show_in_rest' => true,
		//'rewrite' => array( 'slug' => 'series' ),
	));
	// End Series Taxonomy

	// Topics Taxonomy (Non-Hierarchical)
	$labels = array(
		'name' => _x( 'Topics', 'taxonomy general name' ),
		'singular_name' => _x( 'Topic', 'taxonomy singular name' ),
		'search_items' =>  'Search Topics',
		'popular_items' => 'Popular Topics',
		'all_items' => 'All Topics',
		'parent_item' => null,
		'parent_item_colon' => null,
		'edit_item' => 'Edit Topic',
		'update_item' => 'Update Topic',
		'add_new_item' => 'Add New Topic',
		'new_item_name' => 'New Topic Name',
		'separate_items_with_commas' => 'Separate Topics with commas',
		'add_or_remove_items' => 'Add or remove Topics',
		'choose_from_most_used' => 'Choose from the most used Topics'
	);
	register_taxonomy( 'sermon_topic', 'ct_sermon', array(
		'hierarchical' => false,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
    'show_in_rest' => true,
		'rewrite' => array( 'slug' => 'topics' ),
	));
	// End Topics Taxonomy

}
// End Custom Taxonomies


// Create Submenu
add_action('admin_menu', 'sm_submenu');

function sm_submenu() {

	// Add to end of admin_menu action function
	global $submenu;
	$submenu['edit.php?post_type=ct_sermon'][5][0] = __('All Resources');
	$post_type_object = get_post_type_object('ct_sermon');
	$post_type_object->labels->name = "Resources";

}
// End Submenu

// Create Sermon Options Box
add_action("admin_init", "sm_admin_init");

function sm_admin_init(){
	add_meta_box("sm_meta", "Sermon Options", "sm_meta_options", "ct_sermon", "normal", "core");
}
// End Sermon Options Box

// Custom Field Keys
function sm_meta_options(){
	global $post;
	$custom = get_post_custom($post->ID);
	isset($custom["_ct_sm_bible01_book"][0]) ? $sm_bible01_book = $custom["_ct_sm_bible01_book"][0] : $sm_bible01_book = null;
	isset($custom["_ct_sm_bible01_start_chap"][0]) ? $sm_bible01_start_chap = $custom["_ct_sm_bible01_start_chap"][0] : $sm_bible01_start_chap = null;
	isset($custom["_ct_sm_bible01_start_verse"][0]) ? $sm_bible01_start_verse = $custom["_ct_sm_bible01_start_verse"][0] : $sm_bible01_start_verse = null;
	isset($custom["_ct_sm_bible01_end_chap"][0]) ? $sm_bible01_end_chap = $custom["_ct_sm_bible01_end_chap"][0] : $sm_bible01_end_chap = null;
	isset($custom["_ct_sm_bible01_end_verse"][0]) ? $sm_bible01_end_verse = $custom["_ct_sm_bible01_end_verse"][0] : $sm_bible01_end_verse = null;
	isset($custom["_ct_sm_bible02_book"][0]) ? $sm_bible02_book = $custom["_ct_sm_bible02_book"][0] : $sm_bible02_book = null;
	isset($custom["_ct_sm_bible02_start_chap"][0]) ? $sm_bible02_start_chap = $custom["_ct_sm_bible02_start_chap"][0] : $sm_bible02_start_chap = null;
	isset($custom["_ct_sm_bible02_start_verse"][0]) ? $sm_bible02_start_verse = $custom["_ct_sm_bible02_start_verse"][0] : $sm_bible02_start_verse = null;
	isset($custom["_ct_sm_bible02_end_chap"][0]) ? $sm_bible02_end_chap = $custom["_ct_sm_bible02_end_chap"][0] : $sm_bible02_end_chap = null;
	isset($custom["_ct_sm_bible02_end_verse"][0]) ? $sm_bible02_end_verse = $custom["_ct_sm_bible02_end_verse"][0] : $sm_bible02_end_verse = null;
	isset($custom["_ct_sm_bible03_book"][0]) ? $sm_bible03_book = $custom["_ct_sm_bible03_book"][0] : $sm_bible03_book = null;
	isset($custom["_ct_sm_bible03_start_chap"][0]) ? $sm_bible03_start_chap = $custom["_ct_sm_bible03_start_chap"][0] : $sm_bible03_start_chap = null;
	isset($custom["_ct_sm_bible03_start_verse"][0]) ? $sm_bible03_start_verse = $custom["_ct_sm_bible03_start_verse"][0] : $sm_bible03_start_verse = null;
	isset($custom["_ct_sm_bible03_end_chap"][0]) ? $sm_bible03_end_chap = $custom["_ct_sm_bible03_end_chap"][0] : $sm_bible03_end_chap = null;
	isset($custom["_ct_sm_bible03_end_verse"][0]) ? $sm_bible03_end_verse = $custom["_ct_sm_bible03_end_verse"][0] : $sm_bible03_end_verse = null;
	isset($custom["_ct_sm_bible04_book"][0]) ? $sm_bible04_book = $custom["_ct_sm_bible04_book"][0] : $sm_bible04_book = null;
	isset($custom["_ct_sm_bible04_start_chap"][0]) ? $sm_bible04_start_chap = $custom["_ct_sm_bible04_start_chap"][0] : $sm_bible04_start_chap = null;
	isset($custom["_ct_sm_bible04_start_verse"][0]) ? $sm_bible04_start_verse = $custom["_ct_sm_bible04_start_verse"][0] : $sm_bible04_start_verse = null;
	isset($custom["_ct_sm_bible04_end_chap"][0]) ? $sm_bible04_end_chap = $custom["_ct_sm_bible04_end_chap"][0] : $sm_bible04_end_chap = null;
	isset($custom["_ct_sm_bible04_end_verse"][0]) ? $sm_bible04_end_verse = $custom["_ct_sm_bible04_end_verse"][0] : $sm_bible04_end_verse = null;
	isset($custom["_ct_sm_bible05_book"][0]) ? $sm_bible05_book = $custom["_ct_sm_bible05_book"][0] : $sm_bible05_book = null;
	isset($custom["_ct_sm_bible05_start_chap"][0]) ? $sm_bible05_start_chap = $custom["_ct_sm_bible05_start_chap"][0] : $sm_bible05_start_chap = null;
	isset($custom["_ct_sm_bible05_start_verse"][0]) ? $sm_bible05_start_verse = $custom["_ct_sm_bible05_start_verse"][0] : $sm_bible05_start_verse = null;
	isset($custom["_ct_sm_bible05_end_chap"][0]) ? $sm_bible05_end_chap = $custom["_ct_sm_bible05_end_chap"][0] : $sm_bible05_end_chap = null;
	isset($custom["_ct_sm_bible05_end_verse"][0]) ? $sm_bible05_end_verse = $custom["_ct_sm_bible05_end_verse"][0] : $sm_bible05_end_verse = null;
	isset($custom["_ct_sm_audio_file"][0]) ? $sm_audio_file = $custom["_ct_sm_audio_file"][0] : $sm_audio_file = null;
	isset($custom["_ct_sm_file_size"][0]) ? $sm_file_size = $custom["_ct_sm_file_size"][0] : $sm_file_size = null;
	isset($custom["_ct_sm_audio_length"][0]) ? $sm_audio_length = $custom["_ct_sm_audio_length"][0] : $sm_audio_length = null;
	isset($custom["_ct_sm_video_embed"][0]) ? $sm_video_embed = $custom["_ct_sm_video_embed"][0] : $sm_video_embed = null;
	isset($custom["_ct_sm_video_file"][0]) ? $sm_video_file = $custom["_ct_sm_video_file"][0] : $sm_video_file = null;
	isset($custom["_ct_sm_sg_file"][0]) ? $sm_sg_file = $custom["_ct_sm_sg_file"][0] : $sm_sg_file = null;
	isset($custom["_ct_sm_notes"][0]) ? $sm_notes = $custom["_ct_sm_notes"][0] : $sm_notes = null;
// End Custom Field Keys

// Start HTML
	?>
	<script type="text/javascript" charset="utf-8">
	var uploadID;
	jQuery(document).ready(function() {
		jQuery('#upload_audio').click(function() {
			uploadID = jQuery(this).prev('input');
			tb_show('', 'media-upload.php?TB_iframe=true');
			return false;
		});
		window.send_to_editor = function(html) {
			audiourl = jQuery(html).attr('href');
			uploadID.val(audiourl); /*assign the value to the input*/
			tb_remove();
		};
		jQuery('#upload_video').click(function() {
			uploadID = jQuery(this).prev('input');
			tb_show('', 'media-upload.php?TB_iframe=true');
			return false;
		});
		window.send_to_editor = function(html) {
			videourl = jQuery(html).attr('href');
			uploadID.val(videourl); /*assign the value to the input*/
			tb_remove();
		};
		jQuery('#upload_doc').click(function() {
			uploadID = jQuery(this).prev('input');
			tb_show('', 'media-upload.php?TB_iframe=true');
			return false;
		});
		window.send_to_editor = function(html) {
			docurl = jQuery(html).attr('href');
			uploadID.val(docurl); /*assign the value to the input*/
			tb_remove();
		};
	});
	</script>

	<h2 class="meta_section">Featured Image</h2>

	<div class="meta_item first">
		<a title="Set Featured Image" href="media-upload.php?post_id=<?php echo $post->ID; ?>&amp;type=image&amp;TB_iframe=1&amp;width=640&amp;height=285" id="set-post-thumbnail" class="thickbox button rbutton">Set Featured Image</a>
		<br />
		<span>To ensure the best image quality possible, please use a JPG image that is at least 608 x 342 (pixels)</span>
	</div>

	<hr class="meta_divider" />

	<h2 class="meta_section">Bible References</h2>

	<div class="meta_item">
		<label>Passage 1</label>
		<select name="_ct_sm_bible01_book">
			<option value=""<?php if($sm_bible01_book=="") echo " selected";?>>- Select Book -</option>
			<option value="Genesis"<?php if($sm_bible01_book=="Genesis") echo " selected";?>>Genesis</option>
			<option value="Exodus"<?php if($sm_bible01_book=="Exodus") echo " selected";?>>Exodus</option>
			<option value="Leviticus"<?php if($sm_bible01_book=="Leviticus") echo " selected";?>>Leviticus</option>
			<option value="Numbers"<?php if($sm_bible01_book=="Numbers") echo " selected";?>>Numbers</option>
			<option value="Deuteronomy"<?php if($sm_bible01_book=="Deuteronomy") echo " selected";?>>Deuteronomy</option>
			<option value="Joshua"<?php if($sm_bible01_book=="Joshua") echo " selected";?>>Joshua</option>
			<option value="Judges"<?php if($sm_bible01_book=="Judges") echo " selected";?>>Judges</option>
			<option value="Ruth"<?php if($sm_bible01_book=="Ruth") echo " selected";?>>Ruth</option>
			<option value="1 Samuel"<?php if($sm_bible01_book=="1 Samuel") echo " selected";?>>1 Samuel</option>
			<option value="2 Samuel"<?php if($sm_bible01_book=="2 Samuel") echo " selected";?>>2 Samuel</option>
			<option value="1 Kings"<?php if($sm_bible01_book=="1 Kings") echo " selected";?>>1 Kings</option>
			<option value="2 Kings"<?php if($sm_bible01_book=="2 Kings") echo " selected";?>>2 Kings</option>
			<option value="1 Chronicles"<?php if($sm_bible01_book=="1 Chronicles") echo " selected";?>>1 Chronicles</option>
			<option value="2 Chronicles"<?php if($sm_bible01_book=="2 Chronicles") echo " selected";?>>2 Chronicles</option>
			<option value="Ezra"<?php if($sm_bible01_book=="Ezra") echo " selected";?>>Ezra</option>
			<option value="Nehemiah"<?php if($sm_bible01_book=="Nehemiah") echo " selected";?>>Nehemiah</option>
			<option value="Esther"<?php if($sm_bible01_book=="Esther") echo " selected";?>>Esther</option>
			<option value="Job"<?php if($sm_bible01_book=="Job") echo " selected";?>>Job</option>
			<option value="Psalm"<?php if($sm_bible01_book=="Psalm") echo " selected";?>>Psalm</option>
			<option value="Proverbs"<?php if($sm_bible01_book=="Proverbs") echo " selected";?>>Proverbs</option>
			<option value="Ecclesiastes"<?php if($sm_bible01_book=="Ecclesiastes") echo " selected";?>>Ecclesiastes</option>
			<option value="Song of Solomon"<?php if($sm_bible01_book=="Song of Solomon") echo " selected";?>>Song of Solomon</option>
			<option value="Isaiah"<?php if($sm_bible01_book=="Isaiah") echo " selected";?>>Isaiah</option>
			<option value="Jeremiah"<?php if($sm_bible01_book=="Jeremiah") echo " selected";?>>Jeremiah</option>
			<option value="Lamentations"<?php if($sm_bible01_book=="Lamentations") echo " selected";?>>Lamentations</option>
			<option value="Ezekiel"<?php if($sm_bible01_book=="Ezekiel") echo " selected";?>>Ezekiel</option>
			<option value="Daniel"<?php if($sm_bible01_book=="Daniel") echo " selected";?>>Daniel</option>
			<option value="Hosea"<?php if($sm_bible01_book=="Hosea") echo " selected";?>>Hosea</option>
			<option value="Joel"<?php if($sm_bible01_book=="Joel") echo " selected";?>>Joel</option>
			<option value="Amos"<?php if($sm_bible01_book=="Amos") echo " selected";?>>Amos</option>
			<option value="Obadiah"<?php if($sm_bible01_book=="Obadiah") echo " selected";?>>Obadiah</option>
			<option value="Jonah"<?php if($sm_bible01_book=="Jonah") echo " selected";?>>Jonah</option>
			<option value="Micah"<?php if($sm_bible01_book=="Micah") echo " selected";?>>Micah</option>
			<option value="Nahum"<?php if($sm_bible01_book=="Nahum") echo " selected";?>>Nahum</option>
			<option value="Habakkuk"<?php if($sm_bible01_book=="Habakkuk") echo " selected";?>>Habakkuk</option>
			<option value="Zephaniah"<?php if($sm_bible01_book=="Zephaniah") echo " selected";?>>Zephaniah</option>
			<option value="Haggai"<?php if($sm_bible01_book=="Haggai") echo " selected";?>>Haggai</option>
			<option value="Zechariah"<?php if($sm_bible01_book=="Zechariah") echo " selected";?>>Zechariah</option>
			<option value="Malachi"<?php if($sm_bible01_book=="Malachi") echo " selected";?>>Malachi</option>
			<option value="Matthew"<?php if($sm_bible01_book=="Matthew") echo " selected";?>>Matthew</option>
			<option value="Mark"<?php if($sm_bible01_book=="Mark") echo " selected";?>>Mark</option>
			<option value="Luke"<?php if($sm_bible01_book=="Luke") echo " selected";?>>Luke</option>
			<option value="John"<?php if($sm_bible01_book=="John") echo " selected";?>>John</option>
			<option value="Acts"<?php if($sm_bible01_book=="Acts") echo " selected";?>>Acts</option>
			<option value="Romans"<?php if($sm_bible01_book=="Romans") echo " selected";?>>Romans</option>
			<option value="1 Corinthians"<?php if($sm_bible01_book=="1 Corinthians") echo " selected";?>>1 Corinthians</option>
			<option value="2 Corinthians"<?php if($sm_bible01_book=="2 Corinthians") echo " selected";?>>2 Corinthians</option>
			<option value="Galatians"<?php if($sm_bible01_book=="Galatians") echo " selected";?>>Galatians</option>
			<option value="Ephesians"<?php if($sm_bible01_book=="Ephesians") echo " selected";?>>Ephesians</option>
			<option value="Philippians"<?php if($sm_bible01_book=="Philippians") echo " selected";?>>Philippians</option>
			<option value="Colossians"<?php if($sm_bible01_book=="Colossians") echo " selected";?>>Colossians</option>
			<option value="1 Thessalonians"<?php if($sm_bible01_book=="1 Thessalonians") echo " selected";?>>1 Thessalonians</option>
			<option value="2 Thessalonians"<?php if($sm_bible01_book=="2 Thessalonians") echo " selected";?>>2 Thessalonians</option>
			<option value="1 Timothy"<?php if($sm_bible01_book=="1 Timothy") echo " selected";?>>1 Timothy</option>
			<option value="2 Timothy"<?php if($sm_bible01_book=="2 Timothy") echo " selected";?>>2 Timothy</option>
			<option value="Titus"<?php if($sm_bible01_book=="Titus") echo " selected";?>>Titus</option>
			<option value="Philemon"<?php if($sm_bible01_book=="Philemon") echo " selected";?>>Philemon</option>
			<option value="Hebrews"<?php if($sm_bible01_book=="Hebrews") echo " selected";?>>Hebrews</option>
			<option value="James"<?php if($sm_bible01_book=="James") echo " selected";?>>James</option>
			<option value="1 Peter"<?php if($sm_bible01_book=="1 Peter") echo " selected";?>>1 Peter</option>
			<option value="2 Peter"<?php if($sm_bible01_book=="2 Peter") echo " selected";?>>2 Peter</option>
			<option value="1 John"<?php if($sm_bible01_book=="1 John") echo " selected";?>>1 John</option>
			<option value="2 John"<?php if($sm_bible01_book=="2 John") echo " selected";?>>2 John</option>
			<option value="3 John"<?php if($sm_bible01_book=="3 John") echo " selected";?>>3 John</option>
			<option value="Jude"<?php if($sm_bible01_book=="Jude") echo " selected";?>>Jude</option>
			<option value="Revelation"<?php if($sm_bible01_book=="Revelation") echo " selected";?>>Revelation</option>
		</select>
	</div>

	<div class="meta_item verse_start">
		<label for="_ct_sm_bible01_start_chap">Start</label>
		<input type="text" name="_ct_sm_bible01_start_chap" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible01_start_chap); ?>" /> : <input type="text" name="_ct_sm_bible01_start_verse" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible01_start_verse); ?>" />
	</div>

	<div class="meta_item verse_end">
		<label for="_ct_sm_bible01_end_chap">End</label>
		<input type="text" name="_ct_sm_bible01_end_chap" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible01_end_chap); ?>" /> : <input type="text" name="_ct_sm_bible01_end_verse" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible01_end_verse); ?>" />
	</div>

	<div class="meta_item">
		<label>Passage 2</label>
		<select name="_ct_sm_bible02_book">
			<option value=""<?php if($sm_bible02_book=="") echo " selected";?>>- Select Book -</option>
			<option value="Genesis"<?php if($sm_bible02_book=="Genesis") echo " selected";?>>Genesis</option>
			<option value="Exodus"<?php if($sm_bible02_book=="Exodus") echo " selected";?>>Exodus</option>
			<option value="Leviticus"<?php if($sm_bible02_book=="Leviticus") echo " selected";?>>Leviticus</option>
			<option value="Numbers"<?php if($sm_bible02_book=="Numbers") echo " selected";?>>Numbers</option>
			<option value="Deuteronomy"<?php if($sm_bible02_book=="Deuteronomy") echo " selected";?>>Deuteronomy</option>
			<option value="Joshua"<?php if($sm_bible02_book=="Joshua") echo " selected";?>>Joshua</option>
			<option value="Judges"<?php if($sm_bible02_book=="Judges") echo " selected";?>>Judges</option>
			<option value="Ruth"<?php if($sm_bible02_book=="Ruth") echo " selected";?>>Ruth</option>
			<option value="1 Samuel"<?php if($sm_bible02_book=="1 Samuel") echo " selected";?>>1 Samuel</option>
			<option value="2 Samuel"<?php if($sm_bible02_book=="2 Samuel") echo " selected";?>>2 Samuel</option>
			<option value="1 Kings"<?php if($sm_bible02_book=="1 Kings") echo " selected";?>>1 Kings</option>
			<option value="2 Kings"<?php if($sm_bible02_book=="2 Kings") echo " selected";?>>2 Kings</option>
			<option value="1 Chronicles"<?php if($sm_bible02_book=="1 Chronicles") echo " selected";?>>1 Chronicles</option>
			<option value="2 Chronicles"<?php if($sm_bible02_book=="2 Chronicles") echo " selected";?>>2 Chronicles</option>
			<option value="Ezra"<?php if($sm_bible02_book=="Ezra") echo " selected";?>>Ezra</option>
			<option value="Nehemiah"<?php if($sm_bible02_book=="Nehemiah") echo " selected";?>>Nehemiah</option>
			<option value="Esther"<?php if($sm_bible02_book=="Esther") echo " selected";?>>Esther</option>
			<option value="Job"<?php if($sm_bible02_book=="Job") echo " selected";?>>Job</option>
			<option value="Psalm"<?php if($sm_bible02_book=="Psalm") echo " selected";?>>Psalm</option>
			<option value="Proverbs"<?php if($sm_bible02_book=="Proverbs") echo " selected";?>>Proverbs</option>
			<option value="Ecclesiastes"<?php if($sm_bible02_book=="Ecclesiastes") echo " selected";?>>Ecclesiastes</option>
			<option value="Song of Solomon"<?php if($sm_bible02_book=="Song of Solomon") echo " selected";?>>Song of Solomon</option>
			<option value="Isaiah"<?php if($sm_bible02_book=="Isaiah") echo " selected";?>>Isaiah</option>
			<option value="Jeremiah"<?php if($sm_bible02_book=="Jeremiah") echo " selected";?>>Jeremiah</option>
			<option value="Lamentations"<?php if($sm_bible02_book=="Lamentations") echo " selected";?>>Lamentations</option>
			<option value="Ezekiel"<?php if($sm_bible02_book=="Ezekiel") echo " selected";?>>Ezekiel</option>
			<option value="Daniel"<?php if($sm_bible02_book=="Daniel") echo " selected";?>>Daniel</option>
			<option value="Hosea"<?php if($sm_bible02_book=="Hosea") echo " selected";?>>Hosea</option>
			<option value="Joel"<?php if($sm_bible02_book=="Joel") echo " selected";?>>Joel</option>
			<option value="Amos"<?php if($sm_bible02_book=="Amos") echo " selected";?>>Amos</option>
			<option value="Obadiah"<?php if($sm_bible02_book=="Obadiah") echo " selected";?>>Obadiah</option>
			<option value="Jonah"<?php if($sm_bible02_book=="Jonah") echo " selected";?>>Jonah</option>
			<option value="Micah"<?php if($sm_bible02_book=="Micah") echo " selected";?>>Micah</option>
			<option value="Nahum"<?php if($sm_bible02_book=="Nahum") echo " selected";?>>Nahum</option>
			<option value="Habakkuk"<?php if($sm_bible02_book=="Habakkuk") echo " selected";?>>Habakkuk</option>
			<option value="Zephaniah"<?php if($sm_bible02_book=="Zephaniah") echo " selected";?>>Zephaniah</option>
			<option value="Haggai"<?php if($sm_bible02_book=="Haggai") echo " selected";?>>Haggai</option>
			<option value="Zechariah"<?php if($sm_bible02_book=="Zechariah") echo " selected";?>>Zechariah</option>
			<option value="Malachi"<?php if($sm_bible02_book=="Malachi") echo " selected";?>>Malachi</option>
			<option value="Matthew"<?php if($sm_bible02_book=="Matthew") echo " selected";?>>Matthew</option>
			<option value="Mark"<?php if($sm_bible02_book=="Mark") echo " selected";?>>Mark</option>
			<option value="Luke"<?php if($sm_bible02_book=="Luke") echo " selected";?>>Luke</option>
			<option value="John"<?php if($sm_bible02_book=="John") echo " selected";?>>John</option>
			<option value="Acts"<?php if($sm_bible02_book=="Acts") echo " selected";?>>Acts</option>
			<option value="Romans"<?php if($sm_bible02_book=="Romans") echo " selected";?>>Romans</option>
			<option value="1 Corinthians"<?php if($sm_bible02_book=="1 Corinthians") echo " selected";?>>1 Corinthians</option>
			<option value="2 Corinthians"<?php if($sm_bible02_book=="2 Corinthians") echo " selected";?>>2 Corinthians</option>
			<option value="Galatians"<?php if($sm_bible02_book=="Galatians") echo " selected";?>>Galatians</option>
			<option value="Ephesians"<?php if($sm_bible02_book=="Ephesians") echo " selected";?>>Ephesians</option>
			<option value="Philippians"<?php if($sm_bible02_book=="Philippians") echo " selected";?>>Philippians</option>
			<option value="Colossians"<?php if($sm_bible02_book=="Colossians") echo " selected";?>>Colossians</option>
			<option value="1 Thessalonians"<?php if($sm_bible02_book=="1 Thessalonians") echo " selected";?>>1 Thessalonians</option>
			<option value="2 Thessalonians"<?php if($sm_bible02_book=="2 Thessalonians") echo " selected";?>>2 Thessalonians</option>
			<option value="1 Timothy"<?php if($sm_bible02_book=="1 Timothy") echo " selected";?>>1 Timothy</option>
			<option value="2 Timothy"<?php if($sm_bible02_book=="2 Timothy") echo " selected";?>>2 Timothy</option>
			<option value="Titus"<?php if($sm_bible02_book=="Titus") echo " selected";?>>Titus</option>
			<option value="Philemon"<?php if($sm_bible02_book=="Philemon") echo " selected";?>>Philemon</option>
			<option value="Hebrews"<?php if($sm_bible02_book=="Hebrews") echo " selected";?>>Hebrews</option>
			<option value="James"<?php if($sm_bible02_book=="James") echo " selected";?>>James</option>
			<option value="1 Peter"<?php if($sm_bible02_book=="1 Peter") echo " selected";?>>1 Peter</option>
			<option value="2 Peter"<?php if($sm_bible02_book=="2 Peter") echo " selected";?>>2 Peter</option>
			<option value="1 John"<?php if($sm_bible02_book=="1 John") echo " selected";?>>1 John</option>
			<option value="2 John"<?php if($sm_bible02_book=="2 John") echo " selected";?>>2 John</option>
			<option value="3 John"<?php if($sm_bible02_book=="3 John") echo " selected";?>>3 John</option>
			<option value="Jude"<?php if($sm_bible02_book=="Jude") echo " selected";?>>Jude</option>
			<option value="Revelation"<?php if($sm_bible02_book=="Revelation") echo " selected";?>>Revelation</option>
		</select>
	</div>

	<div class="meta_item verse_start">
		<label for="_ct_sm_bible02_start_chap">Start</label>
		<input type="text" name="_ct_sm_bible02_start_chap" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible02_start_chap); ?>" /> : <input type="text" name="_ct_sm_bible02_start_verse" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible02_start_verse); ?>" />
	</div>

	<div class="meta_item verse_end">
		<label for="_ct_sm_bible02_end_chap">End</label>
		<input type="text" name="_ct_sm_bible02_end_chap" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible02_end_chap); ?>" /> : <input type="text" name="_ct_sm_bible02_end_verse" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible02_end_verse); ?>" />
	</div>

	<div class="meta_item">
		<label>Passage 3</label>
		<select name="_ct_sm_bible03_book">
			<option value=""<?php if($sm_bible03_book=="") echo " selected";?>>- Select Book -</option>
			<option value="Genesis"<?php if($sm_bible03_book=="Genesis") echo " selected";?>>Genesis</option>
			<option value="Exodus"<?php if($sm_bible03_book=="Exodus") echo " selected";?>>Exodus</option>
			<option value="Leviticus"<?php if($sm_bible03_book=="Leviticus") echo " selected";?>>Leviticus</option>
			<option value="Numbers"<?php if($sm_bible03_book=="Numbers") echo " selected";?>>Numbers</option>
			<option value="Deuteronomy"<?php if($sm_bible03_book=="Deuteronomy") echo " selected";?>>Deuteronomy</option>
			<option value="Joshua"<?php if($sm_bible03_book=="Joshua") echo " selected";?>>Joshua</option>
			<option value="Judges"<?php if($sm_bible03_book=="Judges") echo " selected";?>>Judges</option>
			<option value="Ruth"<?php if($sm_bible03_book=="Ruth") echo " selected";?>>Ruth</option>
			<option value="1 Samuel"<?php if($sm_bible03_book=="1 Samuel") echo " selected";?>>1 Samuel</option>
			<option value="2 Samuel"<?php if($sm_bible03_book=="2 Samuel") echo " selected";?>>2 Samuel</option>
			<option value="1 Kings"<?php if($sm_bible03_book=="1 Kings") echo " selected";?>>1 Kings</option>
			<option value="2 Kings"<?php if($sm_bible03_book=="2 Kings") echo " selected";?>>2 Kings</option>
			<option value="1 Chronicles"<?php if($sm_bible03_book=="1 Chronicles") echo " selected";?>>1 Chronicles</option>
			<option value="2 Chronicles"<?php if($sm_bible03_book=="2 Chronicles") echo " selected";?>>2 Chronicles</option>
			<option value="Ezra"<?php if($sm_bible03_book=="Ezra") echo " selected";?>>Ezra</option>
			<option value="Nehemiah"<?php if($sm_bible03_book=="Nehemiah") echo " selected";?>>Nehemiah</option>
			<option value="Esther"<?php if($sm_bible03_book=="Esther") echo " selected";?>>Esther</option>
			<option value="Job"<?php if($sm_bible03_book=="Job") echo " selected";?>>Job</option>
			<option value="Psalm"<?php if($sm_bible03_book=="Psalm") echo " selected";?>>Psalm</option>
			<option value="Proverbs"<?php if($sm_bible03_book=="Proverbs") echo " selected";?>>Proverbs</option>
			<option value="Ecclesiastes"<?php if($sm_bible03_book=="Ecclesiastes") echo " selected";?>>Ecclesiastes</option>
			<option value="Song of Solomon"<?php if($sm_bible03_book=="Song of Solomon") echo " selected";?>>Song of Solomon</option>
			<option value="Isaiah"<?php if($sm_bible03_book=="Isaiah") echo " selected";?>>Isaiah</option>
			<option value="Jeremiah"<?php if($sm_bible03_book=="Jeremiah") echo " selected";?>>Jeremiah</option>
			<option value="Lamentations"<?php if($sm_bible03_book=="Lamentations") echo " selected";?>>Lamentations</option>
			<option value="Ezekiel"<?php if($sm_bible03_book=="Ezekiel") echo " selected";?>>Ezekiel</option>
			<option value="Daniel"<?php if($sm_bible03_book=="Daniel") echo " selected";?>>Daniel</option>
			<option value="Hosea"<?php if($sm_bible03_book=="Hosea") echo " selected";?>>Hosea</option>
			<option value="Joel"<?php if($sm_bible03_book=="Joel") echo " selected";?>>Joel</option>
			<option value="Amos"<?php if($sm_bible03_book=="Amos") echo " selected";?>>Amos</option>
			<option value="Obadiah"<?php if($sm_bible03_book=="Obadiah") echo " selected";?>>Obadiah</option>
			<option value="Jonah"<?php if($sm_bible03_book=="Jonah") echo " selected";?>>Jonah</option>
			<option value="Micah"<?php if($sm_bible03_book=="Micah") echo " selected";?>>Micah</option>
			<option value="Nahum"<?php if($sm_bible03_book=="Nahum") echo " selected";?>>Nahum</option>
			<option value="Habakkuk"<?php if($sm_bible03_book=="Habakkuk") echo " selected";?>>Habakkuk</option>
			<option value="Zephaniah"<?php if($sm_bible03_book=="Zephaniah") echo " selected";?>>Zephaniah</option>
			<option value="Haggai"<?php if($sm_bible03_book=="Haggai") echo " selected";?>>Haggai</option>
			<option value="Zechariah"<?php if($sm_bible03_book=="Zechariah") echo " selected";?>>Zechariah</option>
			<option value="Malachi"<?php if($sm_bible03_book=="Malachi") echo " selected";?>>Malachi</option>
			<option value="Matthew"<?php if($sm_bible03_book=="Matthew") echo " selected";?>>Matthew</option>
			<option value="Mark"<?php if($sm_bible03_book=="Mark") echo " selected";?>>Mark</option>
			<option value="Luke"<?php if($sm_bible03_book=="Luke") echo " selected";?>>Luke</option>
			<option value="John"<?php if($sm_bible03_book=="John") echo " selected";?>>John</option>
			<option value="Acts"<?php if($sm_bible03_book=="Acts") echo " selected";?>>Acts</option>
			<option value="Romans"<?php if($sm_bible03_book=="Romans") echo " selected";?>>Romans</option>
			<option value="1 Corinthians"<?php if($sm_bible03_book=="1 Corinthians") echo " selected";?>>1 Corinthians</option>
			<option value="2 Corinthians"<?php if($sm_bible03_book=="2 Corinthians") echo " selected";?>>2 Corinthians</option>
			<option value="Galatians"<?php if($sm_bible03_book=="Galatians") echo " selected";?>>Galatians</option>
			<option value="Ephesians"<?php if($sm_bible03_book=="Ephesians") echo " selected";?>>Ephesians</option>
			<option value="Philippians"<?php if($sm_bible03_book=="Philippians") echo " selected";?>>Philippians</option>
			<option value="Colossians"<?php if($sm_bible03_book=="Colossians") echo " selected";?>>Colossians</option>
			<option value="1 Thessalonians"<?php if($sm_bible03_book=="1 Thessalonians") echo " selected";?>>1 Thessalonians</option>
			<option value="2 Thessalonians"<?php if($sm_bible03_book=="2 Thessalonians") echo " selected";?>>2 Thessalonians</option>
			<option value="1 Timothy"<?php if($sm_bible03_book=="1 Timothy") echo " selected";?>>1 Timothy</option>
			<option value="2 Timothy"<?php if($sm_bible03_book=="2 Timothy") echo " selected";?>>2 Timothy</option>
			<option value="Titus"<?php if($sm_bible03_book=="Titus") echo " selected";?>>Titus</option>
			<option value="Philemon"<?php if($sm_bible03_book=="Philemon") echo " selected";?>>Philemon</option>
			<option value="Hebrews"<?php if($sm_bible03_book=="Hebrews") echo " selected";?>>Hebrews</option>
			<option value="James"<?php if($sm_bible03_book=="James") echo " selected";?>>James</option>
			<option value="1 Peter"<?php if($sm_bible03_book=="1 Peter") echo " selected";?>>1 Peter</option>
			<option value="2 Peter"<?php if($sm_bible03_book=="2 Peter") echo " selected";?>>2 Peter</option>
			<option value="1 John"<?php if($sm_bible03_book=="1 John") echo " selected";?>>1 John</option>
			<option value="2 John"<?php if($sm_bible03_book=="2 John") echo " selected";?>>2 John</option>
			<option value="3 John"<?php if($sm_bible03_book=="3 John") echo " selected";?>>3 John</option>
			<option value="Jude"<?php if($sm_bible03_book=="Jude") echo " selected";?>>Jude</option>
			<option value="Revelation"<?php if($sm_bible03_book=="Revelation") echo " selected";?>>Revelation</option>
		</select>
	</div>

	<div class="meta_item verse_start">
		<label for="_ct_sm_bible03_start_chap">Start</label>
		<input type="text" name="_ct_sm_bible03_start_chap" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible03_start_chap); ?>" /> : <input type="text" name="_ct_sm_bible03_start_verse" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible03_start_verse); ?>" />
	</div>

	<div class="meta_item verse_end">
		<label for="_ct_sm_bible03_end_chap">End</label>
		<input type="text" name="_ct_sm_bible03_end_chap" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible03_end_chap); ?>" /> : <input type="text" name="_ct_sm_bible03_end_verse" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible03_end_verse); ?>" />
	</div>

	<div class="meta_item">
		<label>Passage 4</label>
		<select name="_ct_sm_bible04_book">
			<option value=""<?php if($sm_bible04_book=="") echo " selected";?>>- Select Book -</option>
			<option value="Genesis"<?php if($sm_bible04_book=="Genesis") echo " selected";?>>Genesis</option>
			<option value="Exodus"<?php if($sm_bible04_book=="Exodus") echo " selected";?>>Exodus</option>
			<option value="Leviticus"<?php if($sm_bible04_book=="Leviticus") echo " selected";?>>Leviticus</option>
			<option value="Numbers"<?php if($sm_bible04_book=="Numbers") echo " selected";?>>Numbers</option>
			<option value="Deuteronomy"<?php if($sm_bible04_book=="Deuteronomy") echo " selected";?>>Deuteronomy</option>
			<option value="Joshua"<?php if($sm_bible04_book=="Joshua") echo " selected";?>>Joshua</option>
			<option value="Judges"<?php if($sm_bible04_book=="Judges") echo " selected";?>>Judges</option>
			<option value="Ruth"<?php if($sm_bible04_book=="Ruth") echo " selected";?>>Ruth</option>
			<option value="1 Samuel"<?php if($sm_bible04_book=="1 Samuel") echo " selected";?>>1 Samuel</option>
			<option value="2 Samuel"<?php if($sm_bible04_book=="2 Samuel") echo " selected";?>>2 Samuel</option>
			<option value="1 Kings"<?php if($sm_bible04_book=="1 Kings") echo " selected";?>>1 Kings</option>
			<option value="2 Kings"<?php if($sm_bible04_book=="2 Kings") echo " selected";?>>2 Kings</option>
			<option value="1 Chronicles"<?php if($sm_bible04_book=="1 Chronicles") echo " selected";?>>1 Chronicles</option>
			<option value="2 Chronicles"<?php if($sm_bible04_book=="2 Chronicles") echo " selected";?>>2 Chronicles</option>
			<option value="Ezra"<?php if($sm_bible04_book=="Ezra") echo " selected";?>>Ezra</option>
			<option value="Nehemiah"<?php if($sm_bible04_book=="Nehemiah") echo " selected";?>>Nehemiah</option>
			<option value="Esther"<?php if($sm_bible04_book=="Esther") echo " selected";?>>Esther</option>
			<option value="Job"<?php if($sm_bible04_book=="Job") echo " selected";?>>Job</option>
			<option value="Psalm"<?php if($sm_bible04_book=="Psalm") echo " selected";?>>Psalm</option>
			<option value="Proverbs"<?php if($sm_bible04_book=="Proverbs") echo " selected";?>>Proverbs</option>
			<option value="Ecclesiastes"<?php if($sm_bible04_book=="Ecclesiastes") echo " selected";?>>Ecclesiastes</option>
			<option value="Song of Solomon"<?php if($sm_bible04_book=="Song of Solomon") echo " selected";?>>Song of Solomon</option>
			<option value="Isaiah"<?php if($sm_bible04_book=="Isaiah") echo " selected";?>>Isaiah</option>
			<option value="Jeremiah"<?php if($sm_bible04_book=="Jeremiah") echo " selected";?>>Jeremiah</option>
			<option value="Lamentations"<?php if($sm_bible04_book=="Lamentations") echo " selected";?>>Lamentations</option>
			<option value="Ezekiel"<?php if($sm_bible04_book=="Ezekiel") echo " selected";?>>Ezekiel</option>
			<option value="Daniel"<?php if($sm_bible04_book=="Daniel") echo " selected";?>>Daniel</option>
			<option value="Hosea"<?php if($sm_bible04_book=="Hosea") echo " selected";?>>Hosea</option>
			<option value="Joel"<?php if($sm_bible04_book=="Joel") echo " selected";?>>Joel</option>
			<option value="Amos"<?php if($sm_bible04_book=="Amos") echo " selected";?>>Amos</option>
			<option value="Obadiah"<?php if($sm_bible04_book=="Obadiah") echo " selected";?>>Obadiah</option>
			<option value="Jonah"<?php if($sm_bible04_book=="Jonah") echo " selected";?>>Jonah</option>
			<option value="Micah"<?php if($sm_bible04_book=="Micah") echo " selected";?>>Micah</option>
			<option value="Nahum"<?php if($sm_bible04_book=="Nahum") echo " selected";?>>Nahum</option>
			<option value="Habakkuk"<?php if($sm_bible04_book=="Habakkuk") echo " selected";?>>Habakkuk</option>
			<option value="Zephaniah"<?php if($sm_bible04_book=="Zephaniah") echo " selected";?>>Zephaniah</option>
			<option value="Haggai"<?php if($sm_bible04_book=="Haggai") echo " selected";?>>Haggai</option>
			<option value="Zechariah"<?php if($sm_bible04_book=="Zechariah") echo " selected";?>>Zechariah</option>
			<option value="Malachi"<?php if($sm_bible04_book=="Malachi") echo " selected";?>>Malachi</option>
			<option value="Matthew"<?php if($sm_bible04_book=="Matthew") echo " selected";?>>Matthew</option>
			<option value="Mark"<?php if($sm_bible04_book=="Mark") echo " selected";?>>Mark</option>
			<option value="Luke"<?php if($sm_bible04_book=="Luke") echo " selected";?>>Luke</option>
			<option value="John"<?php if($sm_bible04_book=="John") echo " selected";?>>John</option>
			<option value="Acts"<?php if($sm_bible04_book=="Acts") echo " selected";?>>Acts</option>
			<option value="Romans"<?php if($sm_bible04_book=="Romans") echo " selected";?>>Romans</option>
			<option value="1 Corinthians"<?php if($sm_bible04_book=="1 Corinthians") echo " selected";?>>1 Corinthians</option>
			<option value="2 Corinthians"<?php if($sm_bible04_book=="2 Corinthians") echo " selected";?>>2 Corinthians</option>
			<option value="Galatians"<?php if($sm_bible04_book=="Galatians") echo " selected";?>>Galatians</option>
			<option value="Ephesians"<?php if($sm_bible04_book=="Ephesians") echo " selected";?>>Ephesians</option>
			<option value="Philippians"<?php if($sm_bible04_book=="Philippians") echo " selected";?>>Philippians</option>
			<option value="Colossians"<?php if($sm_bible04_book=="Colossians") echo " selected";?>>Colossians</option>
			<option value="1 Thessalonians"<?php if($sm_bible04_book=="1 Thessalonians") echo " selected";?>>1 Thessalonians</option>
			<option value="2 Thessalonians"<?php if($sm_bible04_book=="2 Thessalonians") echo " selected";?>>2 Thessalonians</option>
			<option value="1 Timothy"<?php if($sm_bible04_book=="1 Timothy") echo " selected";?>>1 Timothy</option>
			<option value="2 Timothy"<?php if($sm_bible04_book=="2 Timothy") echo " selected";?>>2 Timothy</option>
			<option value="Titus"<?php if($sm_bible04_book=="Titus") echo " selected";?>>Titus</option>
			<option value="Philemon"<?php if($sm_bible04_book=="Philemon") echo " selected";?>>Philemon</option>
			<option value="Hebrews"<?php if($sm_bible04_book=="Hebrews") echo " selected";?>>Hebrews</option>
			<option value="James"<?php if($sm_bible04_book=="James") echo " selected";?>>James</option>
			<option value="1 Peter"<?php if($sm_bible04_book=="1 Peter") echo " selected";?>>1 Peter</option>
			<option value="2 Peter"<?php if($sm_bible04_book=="2 Peter") echo " selected";?>>2 Peter</option>
			<option value="1 John"<?php if($sm_bible04_book=="1 John") echo " selected";?>>1 John</option>
			<option value="2 John"<?php if($sm_bible04_book=="2 John") echo " selected";?>>2 John</option>
			<option value="3 John"<?php if($sm_bible04_book=="3 John") echo " selected";?>>3 John</option>
			<option value="Jude"<?php if($sm_bible04_book=="Jude") echo " selected";?>>Jude</option>
			<option value="Revelation"<?php if($sm_bible04_book=="Revelation") echo " selected";?>>Revelation</option>
		</select>
	</div>

	<div class="meta_item verse_start">
		<label for="_ct_sm_bible04_start_chap">Start</label>
		<input type="text" name="_ct_sm_bible04_start_chap" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible04_start_chap); ?>" /> : <input type="text" name="_ct_sm_bible04_start_verse" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible04_start_verse); ?>" />
	</div>

	<div class="meta_item verse_end">
		<label for="_ct_sm_bible04_end_chap">End</label>
		<input type="text" name="_ct_sm_bible04_end_chap" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible04_end_chap); ?>" /> : <input type="text" name="_ct_sm_bible04_end_verse" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible04_end_verse); ?>" />
	</div>

	<div class="meta_item">
		<label>Passage 5</label>
		<select name="_ct_sm_bible05_book">
			<option value=""<?php if($sm_bible05_book=="") echo " selected";?>>- Select Book -</option>
			<option value="Genesis"<?php if($sm_bible05_book=="Genesis") echo " selected";?>>Genesis</option>
			<option value="Exodus"<?php if($sm_bible05_book=="Exodus") echo " selected";?>>Exodus</option>
			<option value="Leviticus"<?php if($sm_bible05_book=="Leviticus") echo " selected";?>>Leviticus</option>
			<option value="Numbers"<?php if($sm_bible05_book=="Numbers") echo " selected";?>>Numbers</option>
			<option value="Deuteronomy"<?php if($sm_bible05_book=="Deuteronomy") echo " selected";?>>Deuteronomy</option>
			<option value="Joshua"<?php if($sm_bible05_book=="Joshua") echo " selected";?>>Joshua</option>
			<option value="Judges"<?php if($sm_bible05_book=="Judges") echo " selected";?>>Judges</option>
			<option value="Ruth"<?php if($sm_bible05_book=="Ruth") echo " selected";?>>Ruth</option>
			<option value="1 Samuel"<?php if($sm_bible05_book=="1 Samuel") echo " selected";?>>1 Samuel</option>
			<option value="2 Samuel"<?php if($sm_bible05_book=="2 Samuel") echo " selected";?>>2 Samuel</option>
			<option value="1 Kings"<?php if($sm_bible05_book=="1 Kings") echo " selected";?>>1 Kings</option>
			<option value="2 Kings"<?php if($sm_bible05_book=="2 Kings") echo " selected";?>>2 Kings</option>
			<option value="1 Chronicles"<?php if($sm_bible05_book=="1 Chronicles") echo " selected";?>>1 Chronicles</option>
			<option value="2 Chronicles"<?php if($sm_bible05_book=="2 Chronicles") echo " selected";?>>2 Chronicles</option>
			<option value="Ezra"<?php if($sm_bible05_book=="Ezra") echo " selected";?>>Ezra</option>
			<option value="Nehemiah"<?php if($sm_bible05_book=="Nehemiah") echo " selected";?>>Nehemiah</option>
			<option value="Esther"<?php if($sm_bible05_book=="Esther") echo " selected";?>>Esther</option>
			<option value="Job"<?php if($sm_bible05_book=="Job") echo " selected";?>>Job</option>
			<option value="Psalm"<?php if($sm_bible05_book=="Psalm") echo " selected";?>>Psalm</option>
			<option value="Proverbs"<?php if($sm_bible05_book=="Proverbs") echo " selected";?>>Proverbs</option>
			<option value="Ecclesiastes"<?php if($sm_bible05_book=="Ecclesiastes") echo " selected";?>>Ecclesiastes</option>
			<option value="Song of Solomon"<?php if($sm_bible05_book=="Song of Solomon") echo " selected";?>>Song of Solomon</option>
			<option value="Isaiah"<?php if($sm_bible05_book=="Isaiah") echo " selected";?>>Isaiah</option>
			<option value="Jeremiah"<?php if($sm_bible05_book=="Jeremiah") echo " selected";?>>Jeremiah</option>
			<option value="Lamentations"<?php if($sm_bible05_book=="Lamentations") echo " selected";?>>Lamentations</option>
			<option value="Ezekiel"<?php if($sm_bible05_book=="Ezekiel") echo " selected";?>>Ezekiel</option>
			<option value="Daniel"<?php if($sm_bible05_book=="Daniel") echo " selected";?>>Daniel</option>
			<option value="Hosea"<?php if($sm_bible05_book=="Hosea") echo " selected";?>>Hosea</option>
			<option value="Joel"<?php if($sm_bible05_book=="Joel") echo " selected";?>>Joel</option>
			<option value="Amos"<?php if($sm_bible05_book=="Amos") echo " selected";?>>Amos</option>
			<option value="Obadiah"<?php if($sm_bible05_book=="Obadiah") echo " selected";?>>Obadiah</option>
			<option value="Jonah"<?php if($sm_bible05_book=="Jonah") echo " selected";?>>Jonah</option>
			<option value="Micah"<?php if($sm_bible05_book=="Micah") echo " selected";?>>Micah</option>
			<option value="Nahum"<?php if($sm_bible05_book=="Nahum") echo " selected";?>>Nahum</option>
			<option value="Habakkuk"<?php if($sm_bible05_book=="Habakkuk") echo " selected";?>>Habakkuk</option>
			<option value="Zephaniah"<?php if($sm_bible05_book=="Zephaniah") echo " selected";?>>Zephaniah</option>
			<option value="Haggai"<?php if($sm_bible05_book=="Haggai") echo " selected";?>>Haggai</option>
			<option value="Zechariah"<?php if($sm_bible05_book=="Zechariah") echo " selected";?>>Zechariah</option>
			<option value="Malachi"<?php if($sm_bible05_book=="Malachi") echo " selected";?>>Malachi</option>
			<option value="Matthew"<?php if($sm_bible05_book=="Matthew") echo " selected";?>>Matthew</option>
			<option value="Mark"<?php if($sm_bible05_book=="Mark") echo " selected";?>>Mark</option>
			<option value="Luke"<?php if($sm_bible05_book=="Luke") echo " selected";?>>Luke</option>
			<option value="John"<?php if($sm_bible05_book=="John") echo " selected";?>>John</option>
			<option value="Acts"<?php if($sm_bible05_book=="Acts") echo " selected";?>>Acts</option>
			<option value="Romans"<?php if($sm_bible05_book=="Romans") echo " selected";?>>Romans</option>
			<option value="1 Corinthians"<?php if($sm_bible05_book=="1 Corinthians") echo " selected";?>>1 Corinthians</option>
			<option value="2 Corinthians"<?php if($sm_bible05_book=="2 Corinthians") echo " selected";?>>2 Corinthians</option>
			<option value="Galatians"<?php if($sm_bible05_book=="Galatians") echo " selected";?>>Galatians</option>
			<option value="Ephesians"<?php if($sm_bible05_book=="Ephesians") echo " selected";?>>Ephesians</option>
			<option value="Philippians"<?php if($sm_bible05_book=="Philippians") echo " selected";?>>Philippians</option>
			<option value="Colossians"<?php if($sm_bible05_book=="Colossians") echo " selected";?>>Colossians</option>
			<option value="1 Thessalonians"<?php if($sm_bible05_book=="1 Thessalonians") echo " selected";?>>1 Thessalonians</option>
			<option value="2 Thessalonians"<?php if($sm_bible05_book=="2 Thessalonians") echo " selected";?>>2 Thessalonians</option>
			<option value="1 Timothy"<?php if($sm_bible05_book=="1 Timothy") echo " selected";?>>1 Timothy</option>
			<option value="2 Timothy"<?php if($sm_bible05_book=="2 Timothy") echo " selected";?>>2 Timothy</option>
			<option value="Titus"<?php if($sm_bible05_book=="Titus") echo " selected";?>>Titus</option>
			<option value="Philemon"<?php if($sm_bible05_book=="Philemon") echo " selected";?>>Philemon</option>
			<option value="Hebrews"<?php if($sm_bible05_book=="Hebrews") echo " selected";?>>Hebrews</option>
			<option value="James"<?php if($sm_bible05_book=="James") echo " selected";?>>James</option>
			<option value="1 Peter"<?php if($sm_bible05_book=="1 Peter") echo " selected";?>>1 Peter</option>
			<option value="2 Peter"<?php if($sm_bible05_book=="2 Peter") echo " selected";?>>2 Peter</option>
			<option value="1 John"<?php if($sm_bible05_book=="1 John") echo " selected";?>>1 John</option>
			<option value="2 John"<?php if($sm_bible05_book=="2 John") echo " selected";?>>2 John</option>
			<option value="3 John"<?php if($sm_bible05_book=="3 John") echo " selected";?>>3 John</option>
			<option value="Jude"<?php if($sm_bible05_book=="Jude") echo " selected";?>>Jude</option>
			<option value="Revelation"<?php if($sm_bible05_book=="Revelation") echo " selected";?>>Revelation</option>
		</select>
	</div>

	<div class="meta_item verse_start">
		<label for="_ct_sm_bible05_start_chap">Start</label>
		<input type="text" name="_ct_sm_bible05_start_chap" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible05_start_chap); ?>" /> : <input type="text" name="_ct_sm_bible05_start_verse" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible05_start_verse); ?>" />
	</div>

	<div class="meta_item verse_end">
		<label for="_ct_sm_bible05_end_chap">End</label>
		<input type="text" name="_ct_sm_bible05_end_chap" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible05_end_chap); ?>" /> : <input type="text" name="_ct_sm_bible05_end_verse" size="5" autocomplete="on" value="<?php echo esc_attr($sm_bible05_end_verse); ?>" />
	</div>

	<hr class="meta_divider" />

	<h2 class="meta_section">Audio Content</h2>

	<p class="meta_info">These fields are required for your Podcast RSS Feed to validate.<br /><a href="edit.php?post_type=ct_sermon&page=podcast-settings">View Podcast Settings</a></p>

	<?php

	$characters = array(" ","'");

	$entities = array( '%20', '%27');


	?>

	<div class="meta_item">
		<label for="_ct_sm_audio_file">Audio Source</label>
		<input type="text" name="_ct_sm_audio_file" size="70" autocomplete="on" placeholder="e.g. http://mychurch.org/wp-content/sermons/audio/2011.01.01_format_speaker.mp3" value="<?php echo esc_url(str_replace($characters, $entities, $sm_audio_file)); ?>" />
		<input id="upload_audio" type="button" class="thickbox button rbutton" value="Upload File" />
		<span>Enter the URL of the audio file (must be an MP3).</span>
	</div>

	<div class="meta_item">
		<label for="_ct_sm_audio_length">Audio Length</label>
		<input type="text" name="_ct_sm_audio_length" size="10" autocomplete="on" placeholder="e.g. 55:36" value="<?php echo wp_filter_nohtml_kses($sm_audio_length); ?>" />
		<span>The Audio Length is the duration of playback in hours, minutes and seconds (hh:mm:ss).</span>
	</div>

	<div class="meta_item">
		<label for="_ct_sm_file_size">File Size</label>
		<input type="text" name="_ct_sm_file_size" size="10" autocomplete="on" placeholder="1122334455" value="<?php echo wp_filter_nohtml_kses($sm_file_size); ?>" />
		<span>Don't touch this.</span>
	</div>



	<hr class="meta_divider" />

	<h2 class="meta_section">Video Content</h2>

	<p class="meta_info">Enter the embed code provided by your video service (such as Vimeo or YouTube) below. This field can also accept shortcodes.</p>

	<div class="meta_item">
		<label for="_ct_sm_video_embed">Embed Code</label>
		<textarea name="_ct_sm_video_embed" cols="60" rows="8" placeholder="e.g. &lt;iframe src=&quot;http://player.vimeo.com/video/26069328?title=0..."><?php echo esc_textarea($sm_video_embed); ?></textarea>
		<span>Embed your video using a width of 608 pixels.</span>
	</div>

	<p class="meta_info clear"><br /><br />The fields below are required if you would like to give users the option to download the video file.</p>

	<div class="meta_item">
		<label for="_ct_sm_video_file">Video Source</label>
		<input type="text" name="_ct_sm_video_file" size="70" autocomplete="on" placeholder="e.g. http://mychurch.org/wp-content/sermons/video/2011.01.01_format_speaker.mp4" value="<?php echo esc_url($sm_video_file); ?>" />
		<input id="upload_video" type="button" class="thickbox button rbutton" value="Upload File" />
		<span>Enter the URL of the video file (M4V or MP4 recommended).</span>
	</div>


	<hr class="meta_divider" />

	<h2 class="meta_section">Document</h2>

	<p class="meta_info">These fields are required to display a downloadable document.</p>

	<div class="meta_item">
		<label for="_ct_sm_sg_file">Document File</label>
		<input type="text" name="_ct_sm_sg_file" size="70" autocomplete="on" placeholder="e.g. http://mychurch.org/wp-content/sermons/docs/2011.01.01_study_guide.pdf" value="<?php echo esc_url($sm_sg_file); ?>" />
		<input id="upload_doc" type="button" class="thickbox button rbutton" value="Upload File" />
		<span>Enter the URL of the document file.</span>
	</div>

	<div class="meta_item">


	<hr class="meta_divider" />

	<h2 class="meta_section">More</h2>

	<div class="meta_item">
		<label for="_ct_sm_notes">
			Admin Notes
			<br /><br />
			<span class="label_note">Not Published</span>
		</label>
		<textarea type="text" name="_ct_sm_notes" cols="60" rows="8"><?php echo wp_filter_nohtml_kses($sm_notes); ?></textarea>
	</div>

	<div class="meta_clear"></div>

<?php
// End HTML
}

// Save Custom Field Values
add_action('save_post', 'save_ct_sm_meta');

function save_ct_sm_meta(){

	global $post_id;

	if(isset($_POST['post_type']) && ($_POST['post_type'] == "ct_sermon")):

		$sm_bible01_book = $_POST['_ct_sm_bible01_book'];
		update_post_meta($post_id, '_ct_sm_bible01_book', $sm_bible01_book);

		$sm_bible01_start_chap = $_POST['_ct_sm_bible01_start_chap'];
		update_post_meta($post_id, '_ct_sm_bible01_start_chap', $sm_bible01_start_chap);

		$sm_bible01_start_verse = $_POST['_ct_sm_bible01_start_verse'];
		update_post_meta($post_id, '_ct_sm_bible01_start_verse', $sm_bible01_start_verse);

		$sm_bible01_end_chap = $_POST['_ct_sm_bible01_end_chap'];
		update_post_meta($post_id, '_ct_sm_bible01_end_chap', $sm_bible01_end_chap);

		$sm_bible01_end_verse = $_POST['_ct_sm_bible01_end_verse'];
		update_post_meta($post_id, '_ct_sm_bible01_end_verse', $sm_bible01_end_verse);

		$sm_bible02_book = $_POST['_ct_sm_bible02_book'];
		update_post_meta($post_id, '_ct_sm_bible02_book', $sm_bible02_book);

		$sm_bible02_start_chap = $_POST['_ct_sm_bible02_start_chap'];
		update_post_meta($post_id, '_ct_sm_bible02_start_chap', $sm_bible02_start_chap);

		$sm_bible02_start_verse = $_POST['_ct_sm_bible02_start_verse'];
		update_post_meta($post_id, '_ct_sm_bible02_start_verse', $sm_bible02_start_verse);

		$sm_bible02_end_chap = $_POST['_ct_sm_bible02_end_chap'];
		update_post_meta($post_id, '_ct_sm_bible02_end_chap', $sm_bible02_end_chap);

		$sm_bible02_end_verse = $_POST['_ct_sm_bible02_end_verse'];
		update_post_meta($post_id, '_ct_sm_bible02_end_verse', $sm_bible02_end_verse);

		$sm_bible03_book = $_POST['_ct_sm_bible03_book'];
		update_post_meta($post_id, '_ct_sm_bible03_book', $sm_bible03_book);

		$sm_bible03_start_chap = $_POST['_ct_sm_bible03_start_chap'];
		update_post_meta($post_id, '_ct_sm_bible03_start_chap', $sm_bible03_start_chap);

		$sm_bible03_start_verse = $_POST['_ct_sm_bible03_start_verse'];
		update_post_meta($post_id, '_ct_sm_bible03_start_verse', $sm_bible03_start_verse);

		$sm_bible03_end_chap = $_POST['_ct_sm_bible03_end_chap'];
		update_post_meta($post_id, '_ct_sm_bible03_end_chap', $sm_bible03_end_chap);

		$sm_bible03_end_verse = $_POST['_ct_sm_bible03_end_verse'];
		update_post_meta($post_id, '_ct_sm_bible03_end_verse', $sm_bible03_end_verse);

		$sm_bible04_book = $_POST['_ct_sm_bible04_book'];
		update_post_meta($post_id, '_ct_sm_bible04_book', $sm_bible04_book);

		$sm_bible04_start_chap = $_POST['_ct_sm_bible04_start_chap'];
		update_post_meta($post_id, '_ct_sm_bible04_start_chap', $sm_bible04_start_chap);

		$sm_bible04_start_verse = $_POST['_ct_sm_bible04_start_verse'];
		update_post_meta($post_id, '_ct_sm_bible04_start_verse', $sm_bible04_start_verse);

		$sm_bible04_end_chap = $_POST['_ct_sm_bible04_end_chap'];
		update_post_meta($post_id, '_ct_sm_bible04_end_chap', $sm_bible04_end_chap);

		$sm_bible04_end_verse = $_POST['_ct_sm_bible04_end_verse'];
		update_post_meta($post_id, '_ct_sm_bible04_end_verse', $sm_bible04_end_verse);

		$sm_bible05_book = $_POST['_ct_sm_bible05_book'];
		update_post_meta($post_id, '_ct_sm_bible05_book', $sm_bible05_book);

		$sm_bible05_start_chap = $_POST['_ct_sm_bible05_start_chap'];
		update_post_meta($post_id, '_ct_sm_bible05_start_chap', $sm_bible05_start_chap);

		$sm_bible05_start_verse = $_POST['_ct_sm_bible05_start_verse'];
		update_post_meta($post_id, '_ct_sm_bible05_start_verse', $sm_bible05_start_verse);

		$sm_bible05_end_chap = $_POST['_ct_sm_bible05_end_chap'];
		update_post_meta($post_id, '_ct_sm_bible05_end_chap', $sm_bible05_end_chap);

		$sm_bible05_end_verse = $_POST['_ct_sm_bible05_end_verse'];
		update_post_meta($post_id, '_ct_sm_bible05_end_verse', $sm_bible05_end_verse);

		$sm_audio_file = $_POST['_ct_sm_audio_file'];
		update_post_meta($post_id, '_ct_sm_audio_file', $sm_audio_file);

		$sm_audio_length = wp_filter_nohtml_kses( $_POST['_ct_sm_audio_length'] );
		update_post_meta($post_id, '_ct_sm_audio_length', $sm_audio_length);

		$audio_file_size = wp_filter_nohtml_kses( $_POST['_ct_sm_file_size'] );
		update_post_meta($post_id, '_ct_sm_file_size', $audio_file_size);

		$sm_video_embed = $_POST['_ct_sm_video_embed'];
		update_post_meta($post_id, '_ct_sm_video_embed', $sm_video_embed);

		$sm_video_file = $_POST['_ct_sm_video_file'];
		update_post_meta($post_id, '_ct_sm_video_file', $sm_video_file);

		$sm_sg_file = esc_url( $_POST['_ct_sm_sg_file'] );
		update_post_meta($post_id, '_ct_sm_sg_file', $sm_sg_file);


		$sm_notes = wp_filter_nohtml_kses( $_POST['_ct_sm_notes'] );
		update_post_meta($post_id, '_ct_sm_notes', $sm_notes);

	endif;
}
// End Custom Field Values
// End Sermon Options Box

// Custom Columns
function sm_register_columns($columns){
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __('Title', 'churchthemes'),
			'sm_speaker' => __('Speaker', 'churchthemes'),
			'sm_series' => __('Series', 'churchthemes'),
			'sm_format' => __('Format', 'churchthemes'),
			'sm_topic' => __('Topic', 'churchthemes'),
			'sm_views' => __('Views', 'churchthemes'),
			'sm_image' => __('Featured Image', 'churchthemes'),
			'sm_id' => __('ID', 'churchthemes'),
		);
		return $columns;
}
add_filter('manage_edit-ct_sermon_columns', 'sm_register_columns');

function sm_display_columns($column){
		global $post;
		$custom = get_post_custom();
		switch ($column)
		{
			case 'sm_speaker':
				echo get_the_term_list($post->ID, 'sermon_speaker', '', ', ', '');
				break;
			case 'sm_format':
				echo get_the_term_list($post->ID, 'sermon_format', '', ', ', '');
				break;
			case 'sm_series':
				echo get_the_term_list($post->ID, 'sermon_series', '', ', ', '');
				break;
			case 'sm_topic':
				echo get_the_term_list($post->ID, 'sermon_topic', '', ', ', '');
				break;
			case 'sm_views':
				echo get_post_views($post->ID);
				break;
			case 'sm_image':
				echo get_the_post_thumbnail($post->ID, 'admin');
				break;
			case 'sm_id':
				echo get_the_ID($post->ID);
				break;
		}
}
add_action('manage_posts_custom_column', 'sm_display_columns');

// End Custom Columns

// Create Shortcodes

add_shortcode("sermons", "ct_sc_sermons");

class ChurchThemes_Sermon_Shortcode {

	static $add_script;

	function init() {
		add_shortcode('sermons', array(__CLASS__, 'ct_sc_sermons'));
	}

	function ct_sc_sermons($atts, $content = null) {
		extract(shortcode_atts(
			array(
				// Default behaviors if values aren't specified
				'id' => '',
				'num' => get_option( 'posts_per_page' ),
				'paging' => 'show',
				'speaker' => '',
				'format' => '',
				'series' => ' ',
				'topic' => '',
				'orderby' => 'date',
				'order' => 'DESC',
				'images' => 'show',
			), $atts));

		global $post;

		if($orderby == 'views'): $orderby = 'meta_value_num'; endif;
		if($paging == 'hide'):
			$paged = null;
		else:
			$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
		endif;

if($orderby == 'meta_value_num'):
		$args=array(
			'post_type' => 'ct_sermon',
			'post_status' => 'publish',
			'paged' => $paged,
			'p' => $id,
			'posts_per_page' => $num,
			'sermon_speaker' => $speaker,
			'sermon_formats' => $format,
			'sermon_series' => $series,
			'sermon_topic' => $topic,
			'meta_key' => 'Views',
			'orderby' => $orderby,
			'order' => $order,
		);
else:
		$args=array(
			'post_type' => 'ct_sermon',
			'post_status' => 'publish',
			'paged' => $paged,
			'p' => $id,
			'posts_per_page' => $num,
			'sermon_speaker' => $speaker,
			'sermon_formats' => $format,
			'sermon_series' => $series,
			'sermon_topic' => $topic,
			'orderby' => $orderby,
			'order' => $order,
		);
endif;

		query_posts($args);

		ob_start();
		if ( $images != 'hide' ) {
			include('shortcode-sermons.php');
		}
		else {
			include('shortcode-sermons-noimage.php');
		}
		if($paging != 'hide') {
			pagination();
		}
		wp_reset_query();
		$content = ob_get_clean();
		return $content;

	}
}

ChurchThemes_Sermon_Shortcode::init();

// End Shortcodes

// Load up styles in WP Admin area
function churchthemes_admin_enqueue_scripts() {
	if ( is_admin() ) {
		wp_register_style( 'churchthemes-admin', get_template_directory_uri() . '/custom/admin.css', array(), false );
		wp_enqueue_style('churchthemes-admin');
	}
}
add_action('admin_enqueue_scripts', 'churchthemes_admin_enqueue_scripts');



if(!function_exists('ct_get_filesize')) {
	function ct_get_filesize( $url, $timeout = 30 ) {
		// Create a curl connection
		$getsize = curl_init();

		// Set the url we're requesting
		curl_setopt($getsize, CURLOPT_URL, $url);

		// Set a valid user agent
		curl_setopt($getsize, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.11) Gecko/20071127 Firefox/2.0.0.11");

		// Don't output any response directly to the browser
		curl_setopt($getsize, CURLOPT_RETURNTRANSFER, true);

		// Don't return the header (we'll use curl_getinfo();
		curl_setopt($getsize, CURLOPT_HEADER, false);

		// Don't download the body content
		curl_setopt($getsize, CURLOPT_NOBODY, true);

		// Follow location headers
		curl_setopt($getsize, CURLOPT_FOLLOWLOCATION, true);

		// Set the timeout (in seconds)
		curl_setopt($getsize, CURLOPT_TIMEOUT, $timeout);

		// Run the curl functions to process the request
		$getsize_store = curl_exec($getsize);
		$getsize_error = curl_error($getsize);
		$getsize_info = curl_getinfo($getsize);

		// Close the connection
		curl_close($getsize); // Print the file size in bytes

		return $getsize_info['download_content_length'];
	}
}

if(!function_exists('ct_posted_by')) {
	/**
	 * Prints HTML with meta information for the current post date/time and author.
	 */
	function ct_posted_by() {
		printf( '<span class="%1$s">Posted by %2$s</span><br /><br />',
			'meta-prep-author',
			sprintf( '<span class="author vcard"><a class="url fn n" href="%1$s" title="%2$s">%3$s</a></span>',
				get_author_posts_url( get_the_author_meta( 'ID' ) ),
				sprintf( esc_attr__( 'View all posts by %s' ), get_the_author() ),
				get_the_author()
			)
		);
	}
}


if(!function_exists('ct_sermon_meta')) {
	/**
	 * Prints HTML with meta information for the current sermon (speaker, series, service and topics).
	 */
	function ct_sermon_meta() {
		global $post;
		// Retrieves tag list of current post, separated by commas.
		$tag_list = get_the_tag_list( '', ', ' );
		$sermon_speaker = get_the_term_list($post->ID, 'sermon_speaker');
		$sermon_series = get_the_term_list($post->ID, 'sermon_series');
		$sermon_format = get_the_term_list($post->ID, 'sermon_format');
		if ( $sermon_speaker ) {
			printf( '%s&nbsp;<span>/</span>&nbsp;', $sermon_speaker );
		}
		if ( $sermon_series ) {
			printf( '%s&nbsp;<span>/</span>&nbsp;', $sermon_series );
		}
		if ( $sermon_format ) {
			printf( '%s&nbsp;', $sermon_format );
		}
	}
}

// Create custom RSS feed for sermon podcasting
if(!function_exists('ct_sermon_podcast_feed')) {
	function ct_sermon_podcast_feed() {
		load_template( TEMPLATEPATH . '/custom/podcast-feed.php');
	}
	add_action('do_feed_podcast', 'ct_sermon_podcast_feed', 10, 1);
}


// Custom rewrite for podcast feed
if(!function_exists('ct_sermon_podcast_feed_rewrite')) {
	function ct_sermon_podcast_feed_rewrite($wp_rewrite) {
		$feed_rules = array(
			'feed/(.+)' => 'index.php?feed=' . $wp_rewrite->preg_index(1),
			'(.+).xml' => 'index.php?feed='. $wp_rewrite->preg_index(1)
		);
		$wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;
	}
	add_filter('generate_rewrite_rules', 'ct_sermon_podcast_feed_rewrite');
}


// Flush rewrite rules
if(!function_exists('ct_flush_rules')) {
	function ct_flush_rules() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}
	add_filter('register_activation_hook', 'ct_flush_rules');
}


// Spoof the lastpostmodified date feeds
if(!function_exists('ct_spoof_lastpostmodified')) {
	function ct_spoof_lastpostmodified($lastpostmodified, $timezone) {
		// WP caches the feed (status 304 - see line 354 in wp-includes/classes.php)
		// We need this to not happen :)
		global $wp;
		if (!empty($wp->query_vars['feed'])){
			$lastpostmodified = date("Y-m-d H:i:s");  // Now
		}
		return $lastpostmodified;
	}
	add_filter('get_lastpostmodified','ct_spoof_lastpostmodified',10,2);
}

// Custom taxonomy terms dropdown function
if(!function_exists('dropdown_taxonomy_term')) {
	function dropdown_taxonomy_term($taxonomy) {
		$terms = get_terms($taxonomy);
		foreach ($terms as $term) {
			$term_slug = $term->slug;
			$current_speaker = get_query_var('sermon_speaker');
			$current_series = get_query_var('sermon_series');
			$current_format = get_query_var('sermon_format');
			$current_topic = get_query_var('sermon_topic');
			if($term_slug == $current_speaker || $term_slug == $current_series || $term_slug == $current_format || $term_slug == $current_topic) {
				echo '<option value="'.$term->slug.'" selected>'.$term->name.'</option>';
			} else {
				echo '<option value="'.$term->slug.'">'.$term->name.'</option>';
			}
		}
	}
}


// CSS3 Button shortcode
if(!function_exists('ct_css3_button_shortcode')) {
	function ct_css3_button_shortcode($atts, $content = null) {
		extract(shortcode_atts(
			array(
				'text' => 'menu_order',
				'url' => '',
				'target' => '_self',
				'title' => '',
				'rel' => '',
			), $atts));
		return "<p><a href=\"$url\" class=\"button\" target=\"$target\">$text</a></p>";
	}
	add_shortcode('button', 'ct_css3_button_shortcode');
}

// Build an array of meta values from all posts in a specified post type
function churchthemes_get_meta_values( $key = null, $type = null, $status = null ) {
	global $wpdb;
	if ( !$key ) {
		return;
	}
	$meta_values = $wpdb->get_col( $wpdb->prepare( "
		SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
		LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE pm.meta_key = '%s'
		AND p.post_type = '%s'
		AND p.post_status = '%s'
	", $key, $type, $status ) );
	return $meta_values;
}

/* END SERMON */

add_action( 'admin_init', 'ct_sermon_settings_init' );
add_action( 'admin_menu', 'ct_sermon_settings_add_page' );

/**
 * Init plugin options to white list our options
 */
function ct_sermon_settings_init(){
	register_setting( 'ct_sermon', 'ct_sermon_settings', 'ct_sermon_settings_validate' );
}

/**
 * Load up the menu page
 */
function ct_sermon_settings_add_page() {
	add_submenu_page('edit.php?post_type=ct_sermon', 'Settings', 'Settings', 'manage_options', 'sermon-settings', 'ct_sermon_settings_do_page');
}

/**
 * Create arrays for our select and radio options
 */
$select_orderby = array(
	'date' => array(
		'value' =>	'date',
		'label' => 'Post Date'
	),
	'title' => array(
		'value' =>	'title',
		'label' => 'Title'
	),
	'modified' => array(
		'value' => 'modified',
		'label' => 'Date Modified'
	),
	'menu_order' => array(
		'value' => 'menu_order',
		'label' => 'Menu Order'
	),
	'id' => array(
		'value' => 'id',
		'label' => 'Post ID'
	),
	'rand' => array(
		'value' => 'rand',
		'label' => 'Random'
	),
	'views' => array(
		'value' => 'views',
		'label' => 'View Count'
	)
);

$select_layout = array(
	'right' => array(
		'value' =>	'right',
		'label' => 'Sidebar Right'
	),
	'left' => array(
		'value' =>	'left',
		'label' => 'Sidebar Left'
	),
	'full' => array(
		'value' => 'full',
		'label' => 'No Sidebar (Full Width)'
	)
);

$radio_order = array(
	'asc' => array(
		'value' => 'ASC',
		'label' => 'Ascending'
	),
	'desc' => array(
		'value' => 'DESC',
		'label' => 'Descending'
	)
);

$radio_toggle = array(
	'on' => array(
		'value' => 'on',
		'label' => 'On'
	),
	'off' => array(
		'value' => 'off',
		'label' => 'Off'
	)
);

/**
 * Create the options page
 */
function ct_sermon_settings_do_page() {
	global $select_orderby, $select_layout, $radio_order, $radio_toggle;

	if ( ! isset( $_REQUEST['settings-updated'] ) )
		$_REQUEST['settings-updated'] = false;

	?>
	<div class="wrap churchthemes">
		<?php screen_icon(); echo "<h2>" . 'Sermon Settings' . "</h2>"; ?>

		<?php if ( false !== $_REQUEST['settings-updated'] ) : ?>
		<div class="updated fade"><p><strong>Options saved</strong></p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'ct_sermon' ); ?>
			<?php $options = get_option( 'ct_sermon_settings' ); ?>

			<table class="form-table churchthemes">
				<tr>
					<th scope="row">Order Sermons By</th>
					<td class="option">
						<select name="ct_sermon_settings[orderby]">
							<?php
								$selected = $options['orderby'];
								$p = '';
								$r = '';
								foreach ( $select_orderby as $option ) {
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
					<td class="info">
						<p>Select what you would like to order your Sermon Archives by.</p>
					</td>
				</tr>

				<tr><th scope="row">Order Direction</th>
					<td class="option">
						<fieldset><legend class="screen-reader-text"><span>Order Direction</span></legend>
						<?php
							if ( ! isset( $checked ) )
								$checked = '';
							foreach ( $radio_order as $option ) {
								if(isset($options['order'])) {
									$radio_setting = $options['order'];
								} else {
									$radio_setting = null;
								}
								if ( '' != $radio_setting ) {
									if ( $options['order'] == $option['value'] ) {
										$checked = "checked=\"checked\"";
									} else {
										$checked = '';
									}
								}
								?>
								<label class="description">
									<input type="radio" name="ct_sermon_settings[order]" value="<?php echo esc_attr( $option['value'] ); ?>" <?php echo $checked; ?> /> <?php echo $option['label']; ?>
								</label>
								<br />
								<?php
							}
						?>
						</fieldset>
					</td>
					<td class="info">
						<p>Select the order direction for the Sermon Archives.</p>
					</td>
				</tr>

				<tr>
					<th scope="row">Archive Title</th>
					<td class="option">
						<input id="ct_sermon_settings[archive_title]" class="regular-text" type="text" name="ct_sermon_settings[archive_title]" placeholder="Sermon Archives" value="<?php echo wp_filter_nohtml_kses( $options['archive_title'] ); ?>" />
					</td>
					<td class="info">
						<p>Name displayed as the page title when viewing an archive of sermons.</p>
						<p><em>If blank, "Sermon Archives" will be used by default.</em></p>
					</td>
				</tr>

				<tr>
					<th scope="row">Archive Slug</th>
					<td class="option">
						<input id="ct_sermon_settings[archive_slug]" class="regular-text" type="text" name="ct_sermon_settings[archive_slug]" placeholder="sermons" value="<?php echo wp_filter_nohtml_kses( $options['archive_slug'] ); ?>" />
					</td>
					<td class="info">
						<p>Choose a slug for your Sermon Archives. It will then be accessible at:<br />http://yourchurch.org/<strong>slug</strong><p>
						<p><em>If this field is left blank, "sermons" will be used by default.</em></p>
					</td>
				</tr>

				<tr>
					<th scope="row">Archive Tagline</th>
					<td class="option">
						<input id="ct_sermon_settings[archive_tagline]" class="regular-text" type="text" name="ct_sermon_settings[archive_tagline]" placeholder="e.g. Live Content from" value="<?php echo wp_filter_nohtml_kses( $options['archive_tagline'] ); ?>" />
					</td>
					<td class="info">
						<p>Text displayed to the right of the Archive Title when viewing an archive of sermons.</p>
					</td>
				</tr>

				<tr>
					<th scope="row">Archive Layout</th>
					<td class="option">
						<select name="ct_sermon_settings[archive_layout]">
							<?php
								$selected = $options['archive_layout'];
								$p = '';
								$r = '';
								foreach ( $select_layout as $option ) {
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
					<td class="info">
						<p>Select the layout you would like to use when viewing the Sermon Archives.</p>
					</td>
				</tr>

				<tr>
					<th scope="row">Archive Sidebar</th>
					<td class="option">
						<select name="ct_sermon_settings[archive_sidebar]">
							<?php
								$selected = $options['archive_sidebar'];
								$p = '';
								$r = '';
								$ct_sidebars = get_option('ct_generated_sidebars');
								foreach ($ct_sidebars as $key => $value) {
									$select_sidebars = array( $key => array('value' => $key, 'label' => $value));
									foreach ( $select_sidebars as $option ) {
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
								}
								echo $p . $r;
							?>
						</select>
					</td>
					<td class="info">
						<p>Select the <a href="themes.php?page=sidebars">Sidebar</a> to be displayed when viewing the Sermon Archives.</p>
					</td>
				</tr>

				<tr class="top">
					<th scope="row">Archive Filters</th>
					<td>
						<input id="ct_sermon_settings[archive_filter_1]" name="ct_sermon_settings[archive_filter_1]" type="checkbox" value="1" <?php if(isset($options['archive_filter_1'])) checked( '1', $options['archive_filter_1'] ); ?> />
						<label class="description" for="ct_sermon_settings[archive_filter_1]">Speakers</label>
						<br />
						<input id="ct_sermon_settings[archive_filter_2]" name="ct_sermon_settings[archive_filter_2]" type="checkbox" value="1" <?php if(isset($options['archive_filter_2'])) checked( '1', $options['archive_filter_2'] ); ?> />
						<label class="description" for="ct_sermon_settings[archive_filter_2]">Series</label>
						<br />
						<input id="ct_sermon_settings[archive_filter_3]" name="ct_sermon_settings[archive_filter_3]" type="checkbox" value="1" <?php if(isset($options['archive_filter_3'])) checked( '1', $options['archive_filter_3'] ); ?> />
						<label class="description" for="ct_sermon_settings[archive_filter_3]">Formats</label>
						<br />
						<input id="ct_sermon_settings[archive_filter_4]" name="ct_sermon_settings[archive_filter_4]" type="checkbox" value="1" <?php if(isset($options['archive_filter_4'])) checked( '1', $options['archive_filter_4'] ); ?> />
						<label class="description" for="ct_sermon_settings[archive_filter_4]">Topics</label>
						<br />
						<input id="ct_sermon_settings[archive_filter_5]" name="ct_sermon_settings[archive_filter_5]" type="checkbox" value="1" <?php if(isset($options['archive_filter_5'])) checked( '1', $options['archive_filter_5'] ); ?> />
						<label class="description" for="ct_sermon_settings[archive_filter_5]">Search Terms</label>
						<br />
					</td>
					<td class="info">
						<p>Choose which filters will be active when users search your Sermon Archives.</p>
						<p><em>Leave blank to disable filtering.</em></p>
					</td>
				</tr>

				<tr>
					<th scope="row">Archive Filters Button Text</th>
					<td class="option">
						<input id="ct_sermon_settings[archive_filters_button_text]" class="regular-text" type="text" name="ct_sermon_settings[archive_filters_button_text]" placeholder="Search Sermons" value="<?php echo wp_filter_nohtml_kses( $options['archive_filters_button_text'] ); ?>" />
					</td>
					<td class="info">
						<p>Decide how the sermon filter\'s submit button will read as users browse your Sermon Archives.<p>
						<p><em>If this field is left blank, "Search Sermons" will be used by default.</em></p>
					</td>
				</tr>

				<tr>
					<th scope="row">Single Sermon Layout</th>
					<td class="option">
						<select name="ct_sermon_settings[single_layout]">
							<?php
								$selected = $options['single_layout'];
								$p = '';
								$r = '';
								foreach ( $select_layout as $option ) {
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
					<td class="info">
						<p>Select the layout you would like to use when viewing a single Sermon.</p>
					</td>
				</tr>

				<tr>
					<th scope="row">Single Sermon Sidebar</th>
					<td class="option">
						<select name="ct_sermon_settings[single_sidebar]">
							<?php
								$selected = $options['single_sidebar'];
								$p = '';
								$r = '';
								$ct_sidebars = get_option('ct_generated_sidebars');
								foreach ($ct_sidebars as $key => $value) {
									$select_sidebars = array( $key => array('value' => $key, 'label' => $value));
									foreach ( $select_sidebars as $option ) {
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
								}
								echo $p . $r;
							?>
						</select>
					</td>
					<td class="info">
						<p>Select the <a href="themes.php?page=sidebars">Sidebar</a> to be displayed when viewing a single Sermon.</p>
					</td>
				</tr>

				<tr>
					<th scope="row">Podcast Subscribe URL</th>
					<td class="option">
						<input id="ct_sermon_settings[podcast_subscribe_url]" class="regular-text" type="text" name="ct_sermon_settings[podcast_subscribe_url]" placeholder="e.g. http://www.itunes.com/podcast?id=FEEDID" value="<?php echo esc_url( $options['podcast_subscribe_url'] ); ?>" />
					</td>
					<td class="info">
						<p>Paste your iTunes Store Link here so people can subscribe to your podcast.<p>
					</td>
				</tr>

				<tr>
					<th scope="row">Podcast Subscribe Button Text</th>
					<td class="option">
						<input id="ct_sermon_settings[podcast_subscribe_button_text]" class="regular-text" type="text" name="ct_sermon_settings[podcast_subscribe_button_text]" placeholder="Subscribe to Podcast" value="<?php echo wp_filter_nohtml_kses( $options['podcast_subscribe_button_text'] ); ?>" />
					</td>
					<td class="info">
						<p>When your Podcast Feed is enabled this button will appear on single sermon pages to allow someone to subscribe to your podcast.<p>
						<p><em>If blank, "Subscribe to Podcast" will be used by default.</em><p>
					</td>
				</tr>

				<tr><th scope="row">Facebook Likes</th>
					<td class="option">
						<fieldset><legend class="screen-reader-text"><span>Facebook Likes</span></legend>
						<?php
							if ( ! isset( $checked ) )
								$checked = '';
							foreach ( $radio_toggle as $option ) {
								if(isset($options['facebook_likes'])) {
									$radio_setting = $options['facebook_likes'];
								} else {
									$radio_setting = null;
								}
								if ( '' != $radio_setting ) {
									if ( $options['facebook_likes'] == $option['value'] ) {
										$checked = "checked=\"checked\"";
									} else {
										$checked = '';
									}
								}
								?>
								<label class="description">
									<input type="radio" name="ct_sermon_settings[facebook_likes]" value="<?php echo esc_attr( $option['value'] ); ?>" <?php echo $checked; ?> /> <?php echo $option['label']; ?>
								</label>
								<br />
								<?php
							}
						?>
						</fieldset>
					</td>
					<td class="info">
						<p>Users can show their Facebook friends that they like a particular sermon by clicking this button as well as see the total number of past likes.</p>
					</td>
				</tr>

				<tr><th scope="row">Tweet This</th>
					<td class="option">
						<fieldset><legend class="screen-reader-text"><span>Tweet This</span></legend>
						<?php
							if ( ! isset( $checked ) )
								$checked = '';
							foreach ( $radio_toggle as $option ) {
								if(isset($options['tweet_this'])) {
									$radio_setting = $options['tweet_this'];
								} else {
									$radio_setting = null;
								}
								if ( '' != $radio_setting ) {
									if ( $options['tweet_this'] == $option['value'] ) {
										$checked = "checked=\"checked\"";
									} else {
										$checked = '';
									}
								}
								?>
								<label class="description">
									<input type="radio" name="ct_sermon_settings[tweet_this]" value="<?php echo esc_attr( $option['value'] ); ?>" <?php echo $checked; ?> /> <?php echo $option['label']; ?>
								</label>
								<br />
								<?php
							}
						?>
						</fieldset>
					</td>
					<td class="info">
						<p>Users can quickly tweet about a sermon by clicking this button as well as see the total number of past tweets.</p>
					</td>
				</tr>

			</table>

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
function ct_sermon_settings_validate( $input ) {
	global $select_orderby, $select_layout, $select_sidebars, $radio_order, $radio_toggle;

	// Our checkbox value is either 0 or 1
	if ( ! isset( $input['option1'] ) )
		$input['option1'] = null;
	$input['option1'] = ( $input['option1'] == 1 ) ? 1 : 0;

	// Say our text option must be safe text with no HTML tags
	if ( ! isset( $input['sometext'] ) )
		$input['sometext'] = null;
	$input['sometext'] = wp_filter_nohtml_kses( $input['sometext'] );

	// Our select option must actually be in our array of select options
	if ( ! isset( $input['select1'] ) || ! array_key_exists( $input['select1'], $select_orderby ) )
		$input['select1'] = null;

	if ( ! isset( $input['select2'] ) || ! array_key_exists( $input['select2'], $select_layout ) )
		$input['select2'] = null;

	if ( ! isset( $input['select3'] ) || ! array_key_exists( $input['select3'], $select_sidebars ) )
		$input['select3'] = null;

	// Our radio option must actually be in our array of radio options
	if ( ! isset( $input['radio1'] ) || ! array_key_exists( $input['radio1'], $radio_order ) )
		$input['radio1'] = null;

	if ( ! isset( $input['radio2'] ) || ! array_key_exists( $input['radio2'], $radio_toggle ) )
		$input['radio2'] = null;

	return $input;
}

// PODCASTING

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

			<h3>General</h3>

			<table class="form-table churchthemes">

				<tr>
					<th scope="row">Title</th>
					<td class="option" colspan="2">
						<input id="ct_podcast_settings[title]" class="regular-text" type="text" name="ct_podcast_settings[title]" placeholder="e.g. Mockingbird" value="<?php echo wp_filter_nohtml_kses( $options['title'] ); ?>" />
					</td>
				</tr>

				<tr>
					<th scope="row">Description</th>
					<td class="option" colspan="2">
						<input id="ct_podcast_settings[description]" class="regular-text" type="text" name="ct_podcast_settings[description]" placeholder="e.g. Connecting the Christian faith with the realities of everyday life" value="<?php echo wp_filter_nohtml_kses( $options['description'] ); ?>" />
					</td>
				</tr>

				<tr>
					<th scope="row">Website Link</th>
					<td class="option" colspan="2">
						<input id="ct_podcast_settings[website_link]" class="regular-text" type="text" name="ct_podcast_settings[website_link]" placeholder="e.g. http://mbird.com" value="<?php echo esc_url( $options['website_link'] ); ?>" />
					</td>
				</tr>

				<tr>
					<th scope="row">Language</th>
					<td class="option" colspan="2">
						<input id="ct_podcast_settings[language]" class="regular-text" type="text" name="ct_podcast_settings[language]" placeholder="e.g. English" value="<?php echo wp_filter_nohtml_kses( $options['language'] ); ?>" />
					</td>
				</tr>

				<tr>
					<th scope="row">Copyright</th>
					<td class="option">
						<input id="ct_podcast_settings[copyright]" class="regular-text" type="text" name="ct_podcast_settings[copyright]" placeholder="e.g. Copyright" value="<?php echo htmlspecialchars( esc_attr( $options['copyright'] ) ); ?>" />
					</td>
					<td class="info">
						<p><em>Tip: Use "' . htmlspecialchars('&copy;') . '" to generate a copyright symbol.</em></p>
					</td>
				</tr>

				<tr>
					<th scope="row">Webmaster Name</th>
					<td class="option" colspan="2">
						<input id="ct_podcast_settings[webmaster_name]" class="regular-text" type="text" name="ct_podcast_settings[webmaster_name]" placeholder="e.g. Admin" value="<?php echo wp_filter_nohtml_kses( $options['webmaster_name'] ); ?>" />
					</td>
				</tr>

				<tr>
					<th scope="row">Webmaster Email</th>
					<td class="option" colspan="2">
						<input id="ct_podcast_settings[webmaster_email]" class="regular-text" type="text" name="ct_podcast_settings[webmaster_email]" placeholder="e.g. info@gmail.com" value="<?php echo wp_filter_nohtml_kses( $options['webmaster_email'] ); ?>" />
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
						<input id="ct_podcast_settings[itunes_owner_name]" class="regular-text" type="text" name="ct_podcast_settings[itunes_owner_name]" placeholder="e.g. Mockingbird" value="<?php echo wp_filter_nohtml_kses( $options['itunes_owner_name'] ); ?>" />
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

// feedz

// replace the default posts feed with feedburner
function appthemes_custom_rss_feed( $output, $feed ) {
    if ( strpos( $output, 'comments' ) ) {return $output;}

    return esc_url( 'http://feeds.feedburner.com/mbird' );
}
add_action( 'feed_link', 'appthemes_custom_rss_feed', 10, 2 );

/**
 * Display a the excerpt with unique length and more text at each instance.
 * Choose whether or not length refers to word count or character count, for
 * more fine-grained control over the size of the rendered excerpt.
 * @param {mixed} $args A WordPress-style query string or array consisting of 'length' (integer), 'more' (str), and 'unit' ('word' || 'char')
 * @return {string} The excerpt
 */
function churchthemes_get_the_excerpt( $args ){
	static $cache = array(); // function cache to improve create_function eval performance
	$all_units = array('word', 'char',);
	$defaults = array(
		'unit' => 'word',
		'wpautop' => true,
	);
	extract(wp_parse_args($args, $defaults), EXTR_SKIP); // => $length, $more, $unit

	// Filter the excerpt length, either in terms of word count or char count
	if( isset($length) ){
		assert( is_numeric($length) );
		assert( in_array( $unit, $all_units ) );
		if( $unit === 'word' ){
			$func = sprintf('return %d;', var_export($length, true));
			if( isset($cache[$func]) ){
				$length_filter = $cache[$func];
			}
			else {
				$cache[$func] = $length_filter = create_function('', $func);
			}
			add_filter( 'excerpt_length', $length_filter );
		}
		else if( $unit === 'char' ) {
			$params = '$text, $num_words, $more, $original_text';
			$func = sprintf('return substr(wp_strip_all_tags($original_text), 0, %d) . $more;', $length);
			if( isset($cache[$func]) ){
				$wp_trim_words_filter = $cache[$func];
			}
			else {
				$cache[$func] = $wp_trim_words_filter = create_function($params, $func);
			}
			add_filter( 'wp_trim_words', $wp_trim_words_filter, 10, 4 ); // @todo This does wpautop??? Thus requiring force_balance_tags
		}
	}

	// Customize the excerpt_more if specified
	if( isset($more) ){
		$func = sprintf('return %s;', var_export($more, true));
		if( isset($cache[$func]) ){
			$more_filter = $cache[$func];
		}
		else {
			$cache[$func] = $more_filter = create_function('', $func);
		}
		add_filter('excerpt_more', $more_filter);
	}

	$excerpt = get_the_excerpt();

	// Remove filters that were added
	if( isset($length_filter) ){
		remove_filter( 'excerpt_length', $length_filter );
	}
	if( isset($more_filter) ){
		remove_filter( 'excerpt_more', $more_filter );
	}
	if( isset($wp_trim_words_filter) ){
		remove_filter( 'wp_trim_words', $wp_trim_words_filter );
	}

	if( $wpautop ){ // @todo Should this apply the_content filters? Should it even wpautop by default?
		$excerpt = wpautop($excerpt);
	}
	return $excerpt;
}

function dropdown_books() {
		$books = array(
    1  => 'Genesis',
    2  => 'Exodus',
    3  => 'Leviticus',
    4  => 'Numbers',
    5  => 'Deuteronomy',
    6  => 'Joshua',
    7  => 'Judges',
    8  => 'Ruth',
    9  => '1 Samuel',
    10 => '2 Samuel',
    11 => '1 Kings',
    12 => '2 Kings',
    13 => '1 Chronicles',
    14 => '2 Chronicles',
    15 => 'Ezra',
    16 => 'Nehemiah',
    17 => 'Esther',
    18 => 'Job',
    19 => 'Psalm',
    20 => 'Proverbs',
    21 => 'Ecclesiastes',
    22 => 'Song of Solomon',
    23 => 'Isaiah',
    24 => 'Jeremiah',
    25 => 'Lamentations',
    26 => 'Ezekiel',
    27 => 'Daniel',
    28 => 'Hosea',
    29 => 'Joel',
    30 => 'Amos',
    31 => 'Obadiah',
    32 => 'Jonah',
    33 => 'Micah',
    34 => 'Nahum',
    35 => 'Habakkuk',
    36 => 'Zephaniah',
    37 => 'Haggai',
    38 => 'Zechariah',
    39 => 'Malachi',
    40 => 'Matthew',
    41 => 'Mark',
    42 => 'Luke',
    43 => 'John',
    44 => 'Acts',
    45 => 'Romans',
    46 => '1 Corinthians',
    47 => '2 Corinthians',
    48 => 'Galatians',
    49 => 'Ephesians',
    50 => 'Philippians',
    51 => 'Colossians',
    52 => '1 Thessalonians',
    53 => '2 Thessalonians',
    54 => '1 Timothy',
    55 => '2 Timothy',
    56 => 'Titus',
    57 => 'Philemon',
    58 => 'Hebrews',
    59 => 'James',
    60 => '1 Peter',
    61 => '2 Peter',
    62 => '1 John',
    63 => '2 John',
    64 => '3 John',
    65 => 'Jude',
    66 => 'Revelation'
);
		foreach ($books as $book) {
			$current_book = $_GET['bible_book'];
			if($book == $current_book) {
				echo '<option value="'.$book.'" selected>'.$book.'</option>';
			} else {
				echo '<option value="'.$book.'">'.$book.'</option>';
			}
		}
	}

/* Empty Search Redirect */

function search_unset( $vars ) {

	if( isset( $_GET['s'] ) && empty( $_GET['s'] ) )

    // Adds the term Empty Search in place of an empty entry
	unset( $vars['s'] );
	return $vars;
}
add_filter( 'request', 'search_unset' );

function meta_search_all_public_post_types( $q ) {
    if ( ! empty( $_GET['bible_book'] ) )
        $q->set( 'meta_value', $_GET['bible_book'] );

}
add_action( 'pre_get_posts', 'meta_search_all_public_post_types' );

add_filter("wp_head", "wpds_increament_post_view");
function get_post_views($post_id=NULL){
    global $post;
    if($post_id==NULL)
        $post_id = $post->ID;
    if(!empty($post_id)){
        $views_key = 'wpds_post_views';
        $views = get_post_meta($post_id, $views_key, true);
        if(empty($views) || !is_numeric($views)){
            delete_post_meta($post_id, $views_key);
            add_post_meta($post_id, $views_key, '0');
            return "0 View";
        }
        else if($views == 1)
            return "1 View";
        return $views.' Views';
    }
}
function wpds_increament_post_view() {
    global $post;

    if(is_singular()){
        $views_key = 'wpds_post_views';
        $views = get_post_meta($post->ID, $views_key, true);
        if(empty($views) || !is_numeric($views)){
            delete_post_meta($post->ID, $views_key);
            add_post_meta($post->ID, $views_key, '1');
        }else
            update_post_meta($post->ID, $views_key, ++$views);
    }
}

function conditional_script_loading() {
    if ( is_page("new-here") ) {
        	wp_enqueue_script( 'cssregions', get_template_directory_uri() . '/js/cssregions.min.js', array(), '1.0.0', true );
    }
}

add_action('wp_enqueue_scripts', 'conditional_script_loading');

add_filter( 'ssp_archive_slug', 'ssp_modify_podcast_archive_slug' );
function ssp_modify_podcast_archive_slug ( $slug ) {
  return 'pz-podcast';
}

add_filter( 'ssp_feed_slug', 'ssp_modify_podcast_feed_slug' );
function ssp_modify_podcast_feed_slug ( $slug ) {
  return 'pz-podcast';
}

add_filter( 'pre_get_posts', 'my_get_posts' );

function my_get_posts( $query ) {

	if ( is_home() && $query->is_main_query() )
		$query->set( 'post_type', array( 'post', 'podcast' ) );

	return $query;
}

?>
