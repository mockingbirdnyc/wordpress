<?php
/*
Plugin Name: Jetpack Easy Playlists
Plugin URI: http://www.jamesfishwick.com/software/auto-jetpack-playlist/
Description: A simple [audio] shortcode wrapper to generate playlists automatically from mp3s attached to your post/page. Requires Jetpack.
Version: 2.4
Author: James Fishwick
Author URI: http://www.jamesfishwick.com/
License: GPL2
*/
/*  Copyright 2012 James Fishwick fishwick@gmail.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* -------- dependency function via http://fullthrottledevelopment.com/creating-dependent-wordpress-plugins --------- */

// Runs every time plugins.php is loaded
function dependency_check(){
	global $pagenow;
	if ( $pagenow != 'plugins.php' ){ return; }

	// Set your requirements
	$required_plugin = 'jetpack/jetpack.php';
	$dependant_plugin = 'easy_jetpack_playlists/easy_jetpack_playlists.php';

	// If this plugin is being activated
	if ( isset($_GET['activate']) && $_GET['activate'] == 'true' ) {
		if ( $plugins = get_option('active_plugins') ){
			if ( !in_array( $required_plugin , $plugins ) ){
				if ($keys = array_keys($plugins,$dependant_plugin) ) {
					unset($plugins[$keys[0]]);
					if ( update_option('active_plugins',$plugins) ){
						unset($_GET['activate']);
						add_action('admin_notices', 'required_plugin_missing_warning');
					}
				}			
			}
		}
	}elseif( ( (isset($_GET['action']) && $_GET['action'] == 'deactivate' ) ) && ( isset($_GET['plugin']) && $_GET['plugin'] == $required_plugin ) ){
		if ( $plugins = get_option('active_plugins') ){
			if ( in_array( $dependant_plugin , $plugins ) ){
				if ($keys = array_keys($plugins,$dependant_plugin) ) {
					unset($plugins[$keys[0]]);
					if ( update_option('active_plugins',$plugins) ){
						add_action('admin_notices', 'dependant_plugin_deactivated');
					}
				}			
			}
		}		
	}
}
if ( is_admin() ){
	add_action('plugins_loaded','dependency_check');
}

// Add's notification div when admin attempts to activate dependent plugin without without required plugin
function required_plugin_missing_warning(){
	?><div id='required_plugin_missing_warning' class='updated fade'><p><strong>Jetpack Not Activated!</strong> You must install and activate <i>Jetpack</i> for <i>Easy Jetpack Playlists to work</i>.<a href="<?php echo admin_url('plugin-install.php?tab=plugin-information&plugin=jetpack&TB_iframe=true&width=640&height=517'); ?>" class="thickbox onclick"> Install now</a></p></div><?php
}

// NOT WORKING YET BECAUSE WP REDIRECTS AFTER I ADD MY HOOK - Adds notification div when admin deactivates required plugin
function dependant_plugin_deactivated(){
	die('here');
	?><div id='dependant_plugin_deactivated' class='updated fade'><p><strong>Jetpack Easy Playlists Deactivated.</strong><i>Jetpack Easy Playlists</i> is dependant on <i>Jetpack</i>. It cannot be reactivated until <i>Jetpack</i>  is reactivated first.</p></div><?php
}

/* -------- end dependancy --------- */

function array_implode( $glue, $separator, $array ) {
			if ( ! is_array( $array ) ) return $array;
			$string = array();
			foreach ( $array as $key => $val ) {
				if ( is_array( $val ) )
				$val = implode( ',', $val );
			$string[] = "{$key}{$glue}{$val}";
			}
			return implode( $separator, $string );
		}
function var_to_str($in)
{
   if(is_bool($in))
   {
      if($in)
         return "true";
      else
         return "false";
   }
   else
      return $in;
}		
		
function is_mp3( $file ) {
		$extension = end( explode( ".", $file->guid ) );
		return ($extension == "mp3");
		}

function get_ID_by_slug($page_slug) {
    $page = get_page_by_path($page_slug);
    if ($page) {
        return $page->ID;
    } else {
        return null;
    }
}
		
add_shortcode('jplaylist', 'easyjp_playlists');

function easyjp_playlists( $atts ) {
		//if( class_exists( 'AudioShortcode' ) && method_exists( 'AudioShortcode', 'audio_shortcode' ) ) {
		//if ( function_exists('Jetpack::get_active_modules') ) {
		if( in_array("shortcodes", Jetpack::get_active_modules()) ) {
		
		// these will store location, and artist and title info for the player
		$urls = $titles = $artists = array();
		$pidset = false;
		$list = "";
		
		// from what post/page are we getting the attachments? -- pid att
		if ( isset($atts['pid']) ) {
			$pidset = true;
			$pid = $atts['pid'];
			unset( $atts['pid'] );
			$pid  = ( is_numeric($pid) ? $pid : url_to_postid($pid) );
		}
		
		else {
			$pid = get_the_ID();
		}
		
		// prepares attributes to be passed in the way [audio] wants them. To do: filter atts?
		$results = array_implode('=',"|", $atts);
		
		// gets all the audio attachments. 
		$attachments = get_children( array(
		'post_parent'    => $pid,
		'post_type'      => 'attachment',
		'numberposts'    => -1, // show all -1
		'post_status'    => 'inherit',
		'post_mime_type' => 'audio/mpeg,',
		'order'          => 'ASC',
		'orderby'        => 'menu_order ASC'
		) );
		
		$attachments = array_filter($attachments, "is_mp3"); // only mp3s can be used in playlists. filter out other audio (mime won't get this all right)
		
		if ($attachments) {
		
		// push all the data in to the arryas
		foreach ( $attachments as $attachment_id => $attachment ) {
		
			array_push($urls, wp_get_attachment_url( $attachment_id )); // url of each mp3
			array_push($titles, get_the_title($attachment_id)); // gets the title of the mp3. This is the literal title in the media library
			array_push($artists, $attachment->post_excerpt); // for attachments, getting the post excerpt retrives the data in the "caption" field. Using this field to store artist name
		}
		
		// shuffle all arrays in the same way
		if ( isset($atts['random']) ) {
			$count = count($urls);
			$order = range(1, $count);

			shuffle($order);
			array_multisort($order, $urls, $titles, $artists);
		}
		
		// return our arrays as a comma seperated strings, which is what the [audio] shortcode wants
		$comma_separated_urls = implode(",", $urls);
		$comma_separated_titles = implode(",", $titles);
		$comma_separated_artists = implode(",", $artists);
		
		// output song info?
		
		if ( isset($atts['print']) ) {
			foreach ($titles as $key => $value) {
				$listitem = $value ." &mdash; ".$artists[$key] ;
				if ( isset($atts['linked']) ) {
					$list .= '<li>'.$listitem .'&nbsp;<a target="_blank" href="'.$urls[$key] .'">[download]</a></li>';
				}
				else { 
					$list .= "<li>".$listitem ."</li>";;
				}
			}
			if ($atts['print'] == 'ol') {
				$list = '<ol>'.$list.'</ol>';
			} 
			else {
				$list = '<ul>'.$list.'</ul>';
			}
		}
		
		//give data to jetpack 
		$playlist = do_shortcode('[audio '.$comma_separated_urls.'|titles='.$comma_separated_titles.'|artists='.$comma_separated_artists.'|'.$results.']');
		
		// look for external atts and try to make them into dimensions
		if ( isset($atts['external']) ) {
			$dims = explode( ',', $atts['external'] );
			$dims= array_map('intval', $dims);
			if($dims[0] == 0) {
				$dims[0] = 350;
			}
			if (!isset($dims[1])) {
				$dims[1]=500;
			}
			
			// add  script to generate pop-up window
			$playlist.= PHP_EOL.'<script type="text/javascript">function jep_popup() {var w = window.open("","jep","toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width='.$dims[0].',height='.$dims[1].'");w.document.write("<html><head><title>Playlist</title><link rel=\"stylesheet\" href=\"'.get_stylesheet_uri().'\" /></head><body><div class=\"post type-jep\"><h1 class=\"entry-title\">'.get_the_title($pid).'</h1><div class=\"post_content entry-content\">" + unescape('.json_encode($playlist . $list).') + "</div></div></body></html>");w.document.close(); }</script>'.PHP_EOL.'<form><input type="button" value="Pop-up" onclick="jep_popup()" /></form>';
		}
		
		return $playlist.PHP_EOL.$list;
		}
		
		else {
		$jep_error_msg = "Sorry pal, no mp3s found for your playlist. ";
		if ($pidset) {$jep_error_msg .=" Check your page/post id?";}
		return "<p><strong style='color:red'>".$jep_error_msg."</strong></p>";
		}
		
	}
	else {
		return "<p><strong style='color:red'>Whoops, your playlist won't work without Jetpack's shortcode functionality!</strong></p>";
	}
}?>