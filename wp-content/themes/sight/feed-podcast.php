<?php
/**
 * Podcast RSS feed template
 *
 * @package WordPress
 * @subpackage SeriouslySimplePodcasting
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $ss_podcasting, $wp_query;

// Hide all errors
error_reporting( 0 );

// Allow feed access by default
$give_access = true;

// Check if feed is password protected
$protection = get_option( 'ss_podcasting_protect', '' );

// Handle feed protection if required
if ( $protection && $protection == 'on' ) {

	$give_access = false;

	// Request password and give access if correct
	if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) && ! isset( $_SERVER['PHP_AUTH_PW'] ) ) {
	    $give_access = false;
	} else {
		$username = get_option( 'ss_podcasting_protection_username' );
		$password = get_option( 'ss_podcasting_protection_password' );

		if ( $_SERVER['PHP_AUTH_USER'] == $username ) {
			if ( md5( $_SERVER['PHP_AUTH_PW'] ) == $password ) {
				$give_access = true;
			}
		}
	}
}

// Get specified podcast series
$podcast_series = '';
if ( isset( $_GET['podcast_series'] ) && $_GET['podcast_series'] ) {
	$podcast_series = esc_attr( $_GET['podcast_series'] );
} elseif ( isset( $wp_query->query_vars['podcast_series'] ) && $wp_query->query_vars['podcast_series'] ) {
	$podcast_series = esc_attr( $wp_query->query_vars['podcast_series'] );
}

// Get series ID
$series_id = 0;
if ( $podcast_series ) {
	$series = get_term_by( 'slug', $podcast_series, 'series' );
	$series_id = $series->term_id;
}

// Allow dynamic access control
$give_access = apply_filters( 'ssp_feed_access', $give_access, $series_id );

// Send 401 status and display no access message if access has been denied
if ( ! $give_access ) {

	// Set default message
	$message = __( 'You are not permitted to view this podcast feed.' , 'seriously-simple-podcasting' );

	// Check message option from plugin settings
	$message_option = get_option('ss_podcasting_protection_no_access_message');
	if ( $message_option ) {
		$message = $message_option;
	}

	// Allow message to be filtered dynamically
	$message = apply_filters( 'ssp_feed_no_access_message', $message );

	$no_access_message = '<div style="text-align:center;font-family:sans-serif;border:1px solid red;background:pink;padding:20px 0;color:red;">' . $message . '</div>';

	header('WWW-Authenticate: Basic realm="Podcast Feed"');
    header('HTTP/1.0 401 Unauthorized');

	die( $no_access_message );
}

// If redirect is on, get new feed URL and redirect if setting was changed more than 48 hours ago
$redirect = get_option( 'ss_podcasting_redirect_feed' );
$new_feed_url = false;
if ( $redirect && $redirect == 'on' ) {

	$new_feed_url = get_option( 'ss_podcasting_new_feed_url' );
	$update_date = get_option( 'ss_podcasting_redirect_feed_date' );

	if ( $new_feed_url && $update_date ) {
		$redirect_date = strtotime( '+2 days' , $update_date );
		$current_date = time();

		// Redirect with 301 if it is more than 2 days since redirect was saved
		if ( $current_date > $redirect_date ) {
			header ( 'HTTP/1.1 301 Moved Permanently' );
			header ( 'Location: ' . $new_feed_url );
			exit;
		}
	}
}

// If this is a series-sepcific feed, then check if we need to redirect
if( $series_id ) {
	$redirect = get_option( 'ss_podcasting_redirect_feed_' . $series_id );
	$new_feed_url = false;
	if ( $redirect && $redirect == 'on' ) {
		$new_feed_url = get_option( 'ss_podcasting_new_feed_url_' . $series_id );
		if ( $new_feed_url ) {
			header ( 'HTTP/1.1 301 Moved Permanently' );
			header ( 'Location: ' . $new_feed_url );
			exit;
		}
	}
}

// Podcast title
$title = get_option( 'ss_podcasting_data_title', get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_title = get_option( 'ss_podcasting_data_title_' . $series_id, '' );
	if ( $series_title ) {
		$title = $series_title;
	}
}
$title = apply_filters( 'ssp_feed_title', $title, $series_id );

// Podcast description
$description = get_option( 'ss_podcasting_data_description', get_bloginfo( 'description' ) );
if ( $podcast_series ) {
	$series_description = get_option( 'ss_podcasting_data_description_' . $series_id, '' );
	if ( $series_description ) {
		$description = $series_description;
	}
}
$podcast_description = mb_substr( strip_tags( $description ), 0, 3999 );
$podcast_description = apply_filters( 'ssp_feed_description', $podcast_description, $series_id );

// Podcast language
$language = get_option( 'ss_podcasting_data_language', get_bloginfo( 'language' ) );
if ( $podcast_series ) {
	$series_language = get_option( 'ss_podcasting_data_language_' . $series_id, '' );
	if ( $series_language ) {
		$language = $series_language;
	}
}
$language = apply_filters( 'ssp_feed_language', $language, $series_id );

// Podcast copyright string
$copyright = get_option( 'ss_podcasting_data_copyright', '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_copyright = get_option( 'ss_podcasting_data_copyright_' . $series_id, '' );
	if ( $series_copyright ) {
		$copyright = $series_copyright;
	}
}
$copyright = apply_filters( 'ssp_feed_copyright', $copyright, $series_id );

// Podcast subtitle
$subtitle = get_option( 'ss_podcasting_data_subtitle', get_bloginfo( 'description' ) );
if ( $podcast_series ) {
	$series_subtitle = get_option( 'ss_podcasting_data_subtitle_' . $series_id, '' );
	if ( $series_subtitle ) {
		$subtitle = $series_subtitle;
	}
}
$subtitle = apply_filters( 'ssp_feed_subtitle', $subtitle, $series_id );

// Podcast author
$author = get_option( 'ss_podcasting_data_author', get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_author = get_option( 'ss_podcasting_data_author_' . $series_id, '' );
	if ( $series_author ) {
		$author = $series_author;
	}
}
$author = apply_filters( 'ssp_feed_author', $author, $series_id );

// Podcast owner name
$owner_name = get_option( 'ss_podcasting_data_owner_name', get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_owner_name = get_option( 'ss_podcasting_data_owner_name_' . $series_id, '' );
	if ( $series_owner_name ) {
		$owner_name = $series_owner_name;
	}
}
$owner_name = apply_filters( 'ssp_feed_owner_name', $owner_name, $series_id );

// Podcast owner email address
$owner_email = get_option( 'ss_podcasting_data_owner_email', get_bloginfo( 'admin_email' ) );
if ( $podcast_series ) {
	$series_owner_email = get_option( 'ss_podcasting_data_owner_email_' . $series_id, '' );
	if ( $series_owner_email ) {
		$owner_email = $series_owner_email;
	}
}
$owner_email = apply_filters( 'ssp_feed_owner_email', $owner_email, $series_id );

// Podcast explicit setting
$explicit_option = get_option( 'ss_podcasting_explicit', '' );
if ( $podcast_series ) {
	$series_explicit_option = get_option( 'ss_podcasting_explicit_' . $series_id, '' );
	$explicit_option = $series_explicit_option;
}
$explicit_option = apply_filters( 'ssp_feed_explicit', $explicit_option, $series_id );
if ( $explicit_option && 'on' == $explicit_option ) {
	$itunes_explicit = 'yes';
	$googleplay_explicit = 'Yes';
} else {
	$itunes_explicit = 'clean';
	$googleplay_explicit = 'No';
}

// Podcast complete setting
$complete_option = get_option( 'ss_podcasting_complete', '' );
if ( $podcast_series ) {
	$series_complete_option = get_option( 'ss_podcasting_complete_' . $series_id, '' );
	$complete_option = $series_complete_option;
}
$complete_option = apply_filters( 'ssp_feed_complete', $complete_option, $series_id );
if ( $complete_option && 'on' == $complete_option ) {
	$complete = 'yes';
} else {
	$complete = '';
}

// Podcast cover image
$image = get_option( 'ss_podcasting_data_image', '' );
if ( $podcast_series ) {
	$series_image = get_option( 'ss_podcasting_data_image_' . $series_id, 'no-image' );
	if ( 'no-image' != $series_image ) {
		$image = $series_image;
	}
}
$image = apply_filters( 'ssp_feed_image', $image, $series_id );

// Podcast category and subcategory (all levels) - can be filtered with `ssp_feed_category_output`
$category1 = ssp_get_feed_category_output( 1, $series_id );
$category2 = ssp_get_feed_category_output( 2, $series_id );
$category3 = ssp_get_feed_category_output( 3, $series_id );

// Get stylehseet URL (filterable to allow custom RSS stylesheets)
$stylehseet_url = apply_filters( 'ssp_rss_stylesheet', $ss_podcasting->template_url . 'feed-stylesheet.xsl' );

// Set RSS content type and charset headers
header( 'Content-Type: ' . feed_content_type( 'podcast' ) . '; charset=' . get_option( 'blog_charset' ), true );

// Use `echo` for first line to prevent any extra characters at start of document
echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?>' . "\n";

// Include RSS stylesheet
if( $stylehseet_url ) {
	echo '<?xml-stylesheet type="text/xsl" href="' . esc_url( $stylehseet_url ) . '"?>';
} ?>

<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
	xmlns:googleplay="http://www.google.com/schemas/play-podcasts/1.0"
	<?php do_action( 'rss2_ns' ); ?>
>

	<channel>
		<title><?php echo esc_html( $title ); ?></title>
		<atom:link href="<?php esc_url( self_link() ); ?>" rel="self" type="application/rss+xml" />
		<link><?php echo esc_url( apply_filters( 'ssp_feed_channel_link_tag', $ss_podcasting->home_url, $podcast_series ) ) ?></link>
		<description><?php echo esc_html( $description ); ?></description>
		<lastBuildDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ) ); ?></lastBuildDate>
		<language><?php echo esc_html( $language ); ?></language>
		<copyright><?php echo esc_html( $copyright ); ?></copyright>
		<itunes:subtitle><?php echo esc_html( $subtitle ); ?></itunes:subtitle>
		<itunes:author><?php echo esc_html( $author ); ?></itunes:author>
		<googleplay:author><?php echo esc_html( $author ); ?></googleplay:author>
		<googleplay:email><?php echo esc_html( $owner_email ); ?></googleplay:email>
		<itunes:summary><?php echo esc_html( $podcast_description ); ?></itunes:summary>
		<googleplay:description><?php echo esc_html( $podcast_description ); ?></googleplay:description>
		<itunes:owner>
			<itunes:name><?php echo esc_html( $owner_name ); ?></itunes:name>
			<itunes:email><?php echo esc_html( $owner_email ); ?></itunes:email>
		</itunes:owner>
		<itunes:explicit><?php echo esc_html( $itunes_explicit ); ?></itunes:explicit>
		<googleplay:explicit><?php echo esc_html( $googleplay_explicit ); ?></googleplay:explicit>
		<?php if( $complete ) { ?><itunes:complete><?php echo esc_html( $complete ); ?></itunes:complete><?php }
if ( $image ) {
		?><itunes:image href="<?php echo esc_url( $image ); ?>"></itunes:image>
		<googleplay:image href="<?php echo esc_url( $image ); ?>"></googleplay:image>
		<image>
			<url><?php echo esc_url( $image ); ?></url>
			<title><?php echo esc_html( $title ); ?></title>
			<link><?php echo esc_url( apply_filters( 'ssp_feed_channel_link_tag', $ss_podcasting->home_url, $podcast_series ) ) ?></link>
		</image>
<?php }
if ( isset( $category1['category'] ) && $category1['category'] ) { ?>
		<itunes:category text="<?php echo esc_attr( $category1['category'] ); ?>">
<?php if ( isset( $category1['subcategory'] ) && $category1['subcategory'] ) { ?>
			<itunes:category text="<?php echo esc_attr( $category1['subcategory'] ); ?>"></itunes:category>
<?php } ?>
		</itunes:category>
<?php } ?>
<?php if ( isset( $category2['category'] ) && $category2['category'] ) { ?>
		<itunes:category text="<?php echo esc_attr( $category2['category'] ); ?>">
<?php if ( isset( $category2['subcategory'] ) && $category2['subcategory'] ) { ?>
			<itunes:category text="<?php echo esc_attr( $category2['subcategory'] ); ?>"></itunes:category>
<?php } ?>
		</itunes:category>
<?php } ?>
<?php if ( isset( $category3['category'] ) && $category3['category'] ) { ?>
		<itunes:category text="<?php echo esc_attr( $category3['category'] ); ?>">
<?php if ( isset( $category3['subcategory'] ) && $category3['subcategory'] ) { ?>
			<itunes:category text="<?php echo esc_attr( $category3['subcategory'] ); ?>"></itunes:category>
<?php } ?>
		</itunes:category>
	<?php } ?>
	<?php if ( $new_feed_url ) { ?>
		<itunes:new-feed-url><?php echo esc_url( $new_feed_url ); ?></itunes:new-feed-url>
	<?php }

		// Prevent WP core from outputting an <image> element
		remove_action( 'rss2_head', 'rss2_site_icon' );

		// Add RSS2 headers
		do_action( 'rss2_head' );

		// Get post IDs of all podcast episodes
		$num_posts = intval( apply_filters( 'ssp_feed_number_of_posts', get_option( 'posts_per_rss', 10 ) ) );

		$args = ssp_episodes( $num_posts, $podcast_series, true, 'feed' );

		$qry = new WP_Query( $args );

		if ( $qry->have_posts() ) {
			while ( $qry->have_posts()) {
				$qry->the_post();

				// Audio file
				$audio_file = $ss_podcasting->get_enclosure( get_the_ID() );
				if ( get_option( 'permalink_structure' ) ) {
					$enclosure = $ss_podcasting->get_episode_download_link( get_the_ID() );
				} else {
					$enclosure = $audio_file;
				}

				$enclosure = apply_filters( 'ssp_feed_item_enclosure', $enclosure, get_the_ID() );

				// If there is no enclosure then go no further
				if ( ! isset( $enclosure ) || ! $enclosure ) {
					continue;
				}

				// Get episode image from post featured image
				$episode_image = '';
				$image_id = get_post_thumbnail_id( get_the_ID() );
				if ( $image_id ) {
					$image_att = wp_get_attachment_image_src( $image_id, 'full' );
					if ( $image_att ) {
						$episode_image = $image_att[0];
					}
				}
				$episode_image = apply_filters( 'ssp_feed_item_image', $episode_image, get_the_ID() );

				// Episode duration (default to 0:00 to ensure there is always a value for this)
				$duration = get_post_meta( get_the_ID(), 'duration', true );
				if ( ! $duration ) {
					$duration = '0:00';
				}
				$duration = apply_filters( 'ssp_feed_item_duration', $duration, get_the_ID() );

				// File size
				$size = get_post_meta( get_the_ID(), 'filesize_raw', true );
				if ( ! $size ) {
					$size = 1;
				}
				$size = apply_filters( 'ssp_feed_item_size', $size, get_the_ID() );

				// File MIME type (default to MP3/MP4 to ensure there is always a value for this)
				$mime_type = $ss_podcasting->get_attachment_mimetype( $audio_file );
				if ( ! $mime_type ) {

					// Get the episode type (audio or video) to determine the appropriate default MIME type
					$episode_type = $ss_podcasting->get_episode_type( get_the_ID() );

					switch( $episode_type ) {
						case 'audio': $mime_type = 'audio/mpeg'; break;
						case 'video': $mime_type = 'video/mp4'; break;
					}
				}
				$mime_type = apply_filters( 'ssp_feed_item_mime_type', $mime_type, get_the_ID() );

				// Episode explicit flag
				$ep_explicit = get_post_meta( get_the_ID(), 'explicit', true );
				$ep_explicit = apply_filters( 'ssp_feed_item_explicit', $ep_explicit, get_the_ID() );
				if ( $ep_explicit && $ep_explicit == 'on' ) {
					$itunes_explicit_flag = 'yes';
					$googleplay_explicit_flag = 'Yes';
				} else {
					$itunes_explicit_flag = 'clean';
					$googleplay_explicit_flag = 'No';
				}

				// Episode block flag
				$ep_block = get_post_meta( get_the_ID(), 'block', true );
				$ep_block = apply_filters( 'ssp_feed_item_block', $ep_block, get_the_ID() );
				if ( $ep_block && $ep_block == 'on' ) {
					$block_flag = 'yes';
				} else {
					$block_flag = 'no';
				}

				// Episode author
				$author = esc_html( get_the_author() );
				$author = apply_filters( 'ssp_feed_item_author', $author, get_the_ID() );

				// Episode content (with iframes removed)
				$content = get_the_content_feed( 'rss2' );
				$content = preg_replace( '/<\/?iframe(.|\s)*?>/', '', $content );
				$content = apply_filters( 'ssp_feed_item_content', $content, get_the_ID() );

				// iTunes summary is the full episode content, but must be shorter than 4000 characters
				$itunes_summary = mb_substr( $content, 0, 3999 );
				$itunes_summary = apply_filters( 'ssp_feed_item_itunes_summary', $itunes_summary, get_the_ID() );
				$gp_description = apply_filters( 'ssp_feed_item_gp_description', $itunes_summary, get_the_ID() );

				// Episode description
				ob_start();
				the_excerpt_rss();
				$description = ob_get_clean();
				$description = apply_filters( 'ssp_feed_item_description', $description, get_the_ID() );

				// iTunes subtitle does not allow any HTML and must be shorter than 255 characters
				$itunes_subtitle = strip_tags( strip_shortcodes( $description ) );
				$itunes_subtitle = str_replace( array( '>', '<', '\'', '"', '`', '[andhellip;]', '[&hellip;]', '[&#8230;]' ), array( '', '', '', '', '', '', '', '' ), $itunes_subtitle );
				$itunes_subtitle = mb_substr( $itunes_subtitle, 0, 254 );
				$itunes_subtitle = apply_filters( 'ssp_feed_item_itunes_subtitle', $itunes_subtitle, get_the_ID() );

		?>
		<item>
			<title><?php esc_html( the_title_rss() ); ?></title>
			<link><?php esc_url( the_permalink_rss() ); ?></link>
			<pubDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ) ); ?></pubDate>
			<dc:creator><?php echo $author; ?></dc:creator>
			<guid isPermaLink="false"><?php esc_html( the_guid() ); ?></guid>
			<description><![CDATA[<?php echo $description; ?>]]></description>
			<itunes:subtitle><![CDATA[<?php echo $itunes_subtitle; ?>]]></itunes:subtitle>
			<content:encoded><![CDATA[<?php echo $content; ?>]]></content:encoded>
			<itunes:summary><![CDATA[<?php echo $itunes_summary; ?>]]></itunes:summary>
			<googleplay:description><![CDATA[<?php echo $gp_description; ?>]]></googleplay:description>
<?php if ( $episode_image ) { ?>
			<itunes:image href="<?php echo esc_url( $episode_image ); ?>"></itunes:image>
			<googleplay:image href="<?php echo esc_url( $episode_image ); ?>"></googleplay:image>
<?php } ?>
			<enclosure url="<?php echo esc_url( $enclosure ); ?>" length="<?php echo esc_attr( $size ); ?>" type="<?php echo esc_attr( $mime_type ); ?>"></enclosure>
			<itunes:explicit><?php echo esc_html( $itunes_explicit_flag ); ?></itunes:explicit>
			<googleplay:explicit><?php echo esc_html( $googleplay_explicit_flag ); ?></googleplay:explicit>
			<itunes:block><?php echo esc_html( $block_flag ); ?></itunes:block>
			<googleplay:block><?php echo esc_html( $block_flag ); ?></googleplay:block>
			<itunes:duration><?php echo esc_html( $duration ); ?></itunes:duration>
			<itunes:author><?php echo $author; ?></itunes:author>
		</item>
<?php }
} ?>
<item>
           <title>Episode 211 - Son, This Is She</title>
           <itunes:author>Paul Zahl</itunes:author>
           <description>
               <![CDATA[There is this amazing supposed contrast between the God Who comes to us from without, and the God Who speaks to us from within.

Historic Christianity generally hears the First.
Eastern religion generally hears the second.

Personally, I hear both -- by which I mean,
a lot of Love is "channelled" or "made flesh" in the inspirations I feel to love and to cherish that are indistinguishable from my own best self.
("I'd like to know where you got the notion" (Rock the Boat) -- The Hues Corporation, 1974).

Yet when I'm in a jam, when I am simply unable to be in touch at all
with my true and best self -- when I'm beset, alone, and without defense or protection -- then it's all about the God who Reaches Down and Helps.
(Like 'Delphine' at the end of The Green Ray.)  "Save Me" (Fleetwood Mac, 1990).

This cast speaks of our deafness and blindness when it comes to hearing and seeing God in, well, what lies before us, in our purview -- with a little help from Howard Pyle, James Garner, Manly Wade Wellman, Donald Trump, William Hale White, and Christopher Isherwood. It's Joe Meek, though, together with Geoff Goddard, who cuts the knot.

The cast is dedicated to Ethan Richardson.]]>
           </description>
           <itunes:subtitle>There is this amazing supposed contrast between the God Who comes to us from without, and the God Who speaks to us from within.

Historic Christianity generally hears the First.
Eastern religion generally hears the second.

Personally, I hear both -- by which I mean,
a lot of Love is "channelled" or "made flesh" in the inspirations I feel to love and to cherish that are indistinguishable from my own best self.
("I'd like to know where you got the notion" (Rock the Boat) -- The Hues Corporation, 1974).

Yet when I'm in a jam, when I am simply unable to be in touch at all
with my true and best self -- when I'm beset, alone, and without defense or protection -- then it's all about the God who Reaches Down and Helps.
(Like 'Delphine' at the end of The Green Ray.)  "Save Me" (Fleetwood Mac, 1990).

This cast speaks of our deafness and blindness when it comes to hearing and seeing God in, well, what lies before us, in our purview -- with a little help from Howard Pyle, James Garner, Manly Wade Wellman, Donald Trump, William Hale White, and Christopher Isherwood. It's Joe Meek, though, together with Geoff Goddard, who cuts the knot.

The cast is dedicated to Ethan Richardson.</itunes:subtitle>
           <itunes:summary>There is this amazing supposed contrast between the God Who comes to us from without, and the God Who speaks to us from within.

Historic Christianity generally hears the First.
Eastern religion generally hears the second.

Personally, I hear both -- by which I mean,
a lot of Love is "channelled" or "made flesh" in the inspirations I feel to love and to cherish that are indistinguishable from my own best self.
("I'd like to know where you got the notion" (Rock the Boat) -- The Hues Corporation, 1974).

Yet when I'm in a jam, when I am simply unable to be in touch at all
with my true and best self -- when I'm beset, alone, and without defense or protection -- then it's all about the God who Reaches Down and Helps.
(Like 'Delphine' at the end of The Green Ray.)  "Save Me" (Fleetwood Mac, 1990).

This cast speaks of our deafness and blindness when it comes to hearing and seeing God in, well, what lies before us, in our purview -- with a little help from Howard Pyle, James Garner, Manly Wade Wellman, Donald Trump, William Hale White, and Christopher Isherwood. It's Joe Meek, though, together with Geoff Goddard, who cuts the knot.

The cast is dedicated to Ethan Richardson.</itunes:summary>
           <enclosure url="http://mbird.com//podcastgen/media/Episode%20211%20-%20Son%20This%20Is%20She.m4a" type="audio/x-m4a" length="24232417" />
           <guid>http://mbird.com//podcastgen/media/Episode%20211%20-%20Son%20This%20Is%20She.m4a</guid>
           <pubDate>Mon, 8 February 2016 07:09:23 -0400</pubDate>
           <category>Christianity</category>
           <itunes:explicit>no</itunes:explicit>
           <itunes:duration>24:52</itunes:duration>
           <itunes:keywords />
       </item>

       <item>
           <title>Episode 210 - Saved!</title>
           <itunes:author>Paul Zahl</itunes:author>
           <description>
               <![CDATA[When you were in a tight spot, how did help get through to you,
assuming help did get through to you?

Did God speak from out of the whirlwind -- of crisis, panic, and despair?
Or did aid come from inside yourself -- a 'how-to' or random thought that proved serviceable in the midst?
If you're a regular listener to PZ's Podcast, you may well answer, the former. That's certainly what happened to PZ!

Nevertheless, your source of inspiration, and help, and salvation in the imminent immanent sense of the word: what was it?

You won't be surprised that I've been thinking, in this connection, about UFOs.I saw a Big One in the early '80s -- as did John Zahl, who was with me at the time. And ever since Battle in Outer Space (1959) came out, I've been a kind of believer.  But never mind.

What's interesting, though, is that Booth Tarkington was a kind of believer, also. As was Nevil Shute.  As was Rudyard Kipling.  (You have to read Kipling's short story "A Matter of Fact", just to name one.)  Each of these writers left room, over on the margins, for the Unknown.  Each was thusly religious.

In your experience of crisis, from what source has "the power of God unto salvation" (Romans 1:16) come?  "Tolle lege"?  "My heart was strangely warmed"? "Saul, Saul, why persecutest thou me?" "I was wrong" (Robert Wyatt).  Tell me about it.

This podcast is dedicated to John Arthur Zahl.]]>
           </description>
           <itunes:subtitle>When you were in a tight spot, how did help get through to you,
assuming help did get through to you?

Did God speak from out of the whirlwind -- of crisis, panic, and despair?
Or did aid come from inside yourself -- a 'how-to' or random thought that proved serviceable in the midst?
If you're a regular listener to PZ's Podcast, you may well answer, the former. That's certainly what happened to PZ!

Nevertheless, your source of inspiration, and help, and salvation in the imminent immanent sense of the word: what was it?

You won't be surprised that I've been thinking, in this connection, about UFOs.I saw a Big One in the early '80s -- as did John Zahl, who was with me at the time. And ever since Battle in Outer Space (1959) came out, I've been a kind of believer.  But never mind.

What's interesting, though, is that Booth Tarkington was a kind of believer, also. As was Nevil Shute.  As was Rudyard Kipling.  (You have to read Kipling's short story "A Matter of Fact", just to name one.)  Each of these writers left room, over on the margins, for the Unknown.  Each was thusly religious.

In your experience of crisis, from what source has "the power of God unto salvation" (Romans 1:16) come?  "Tolle lege"?  "My heart was strangely warmed"? "Saul, Saul, why persecutest thou me?" "I was wrong" (Robert Wyatt).  Tell me about it.

This podcast is dedicated to John Arthur Zahl.</itunes:subtitle>
           <itunes:summary>When you were in a tight spot, how did help get through to you,
assuming help did get through to you?

Did God speak from out of the whirlwind -- of crisis, panic, and despair?
Or did aid come from inside yourself -- a 'how-to' or random thought that proved serviceable in the midst?
If you're a regular listener to PZ's Podcast, you may well answer, the former. That's certainly what happened to PZ!

Nevertheless, your source of inspiration, and help, and salvation in the imminent immanent sense of the word: what was it?

You won't be surprised that I've been thinking, in this connection, about UFOs.I saw a Big One in the early '80s -- as did John Zahl, who was with me at the time. And ever since Battle in Outer Space (1959) came out, I've been a kind of believer.  But never mind.

What's interesting, though, is that Booth Tarkington was a kind of believer, also. As was Nevil Shute.  As was Rudyard Kipling.  (You have to read Kipling's short story "A Matter of Fact", just to name one.)  Each of these writers left room, over on the margins, for the Unknown.  Each was thusly religious.

In your experience of crisis, from what source has "the power of God unto salvation" (Romans 1:16) come?  "Tolle lege"?  "My heart was strangely warmed"? "Saul, Saul, why persecutest thou me?" "I was wrong" (Robert Wyatt).  Tell me about it.

This podcast is dedicated to John Arthur Zahl.</itunes:summary>
           <enclosure url="http://mbird.com/podcastgen/media/Episode%20210%20-%20Saved.m4a" type="audio/x-m4a" length="25259777" />
           <guid>http://mbird.com/podcastgen/media/Episode%20210%20-%20Saved.m4a</guid>
           <pubDate>Sun, 31 January 2016 07:09:23 -0400</pubDate>
           <category>Christianity</category>
           <itunes:explicit>no</itunes:explicit>
           <itunes:duration>25:55</itunes:duration>
           <itunes:keywords />
       </item>

<item>
<title>Episode 209 - How To Be Popular If You're a Guy</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[The answer to that question has to lie, somehow, in whatever explains
the popular success of Rodney Marvin ('Rod') McKuen.

Rod McKuen died a year ago, and did you know he sold 100 million records?  No kidding.  Rod McKuen sold 100 million records.

(He also sold 60 million books.  But hey...) Here is a man who was universally dismissed, from day one of his earthly success, as being a "kitschy" Philistine and arch-sentimentalist.  No critic had a word of praise for him.  Ever, ever, ever.  And that's been true right up to the present day.

And let the People say: He sold a hundred million records.

Here is a writer who when you actually take time to listen to his songs
wrote from a fetal position of complete understanding,.. of me and you.

Now let the People say: He sold a hundred million records.

Here is a man who, like Rudyard Kipling -- if it weren't for T.S. Eliot and George Orwell --
would have no friends in "high places" (at least after he died).
People loved his work. Critics hated it.

Therefore let the People say: He sold a hundred million records.

So listen up, guys:
If you want to be popular, then say what you think, say it deep, say it real, and speak it... from the earthed position of the earliest human child.]]>
</description>
<itunes:subtitle>The answer to that question has to lie, somehow, in whatever explains
the popular success of Rodney Marvin ('Rod') McKuen.

Rod McKuen died a year ago, and did you know he sold 100 million records?  No kidding.  Rod McKuen sold 100 million records.

(He also sold 60 million books.  But hey...)

Here is a man who was universally dismissed, from day one of his earthly success, as being a "kitschy" Philistine and arch-sentimentalist.  No critic had a word of praise for him.  Ever, ever, ever.  And that's been true right up to the present day.

And let the People say: He sold a hundred million records.

Here is a writer who when you actually take time to listen to his songs
wrote from a fetal position of complete understanding,.. of me and you.

Now let the People say: He sold a hundred million records.

Here is a man who, like Rudyard Kipling -- if it weren't for T.S. Eliot and George Orwell --
would have no friends in "high places" (at least after he died).
People loved his work. Critics hated it.

Therefore let the People say: He sold a hundred million records.

So listen up, guys:
If you want to be popular, then say what you think, say it deep, say it real, and speak it... from the earthed position of the earliest human child.</itunes:subtitle>
<itunes:summary>The answer to that question has to lie, somehow, in whatever explains
the popular success of Rodney Marvin ('Rod') McKuen.

Rod McKuen died a year ago, and did you know he sold 100 million records?  No kidding.  Rod McKuen sold 100 million records.

(He also sold 60 million books.  But hey...)

Here is a man who was universally dismissed, from day one of his earthly success, as being a "kitschy" Philistine and arch-sentimentalist.  No critic had a word of praise for him.  Ever, ever, ever.  And that's been true right up to the present day.

And let the People say: He sold a hundred million records.

Here is a writer who when you actually take time to listen to his songs
wrote from a fetal position of complete understanding,.. of me and you.

Now let the People say: He sold a hundred million records.

Here is a man who, like Rudyard Kipling -- if it weren't for T.S. Eliot and George Orwell --
would have no friends in "high places" (at least after he died).
People loved his work. Critics hated it.

Therefore let the People say: He sold a hundred million records.

So listen up, guys:
If you want to be popular, then say what you think, say it deep, say it real, and speak it... from the earthed position of the earliest human child.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20209%20-%20How%20To%20Be%20Popular%20If%20You%27re%20a%20Guy.m4a" type="audio/x-m4a" length="24620739" />
<guid>http://mbird.com/podcastgen/media/Episode%20209%20-%20How%20To%20Be%20Popular%20If%20You%27re%20a%20Guy.m4a</guid>
<pubDate>Mon, 25 January 2016 12:09:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>25:16</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 208 - Five O'Clock World</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Now we think that reality, the "real world", is what happens "between nine and five", that is, what happens at work, in the office, at school, in career, and so forth.  And a lot of people want to tell us that's true.

"But not The Vogues.  They were from Pittsburgh and they understood about shifts and hourly pay.  Yet they understand more than that!

"For the fact is, 'as you lay dying' (Faulkner), you won't give your "nine-to-five" life a single second thought.  Not one single second thought! You'll forget it all, in the absolute blink of an eye.
That's just a fact of old age and death -- the fact of our mortality.

"What you won't forget, however, is "the long haired girl who waits for you/To ease your troubled mind".  Or the one who did.  Hopefully, it's the same person. As The Vogues observe: in light of her, ''Nothing else matters at all.'

"This cast explores the 'Five O'Clock World' in terms of romantic love.
Not sororal or fraternal love.  Not even paternal and maternal love.
But romantic love.  For that's the core of loving for men and women.  That is "where the action is" (Freddy Cannon).

"Moreover, it is the core of the Gospel.  If you want to understand what Christ did, for you, look at your experience of romantic love.  For better or worse, look at your experience of romantic love.  Like my friend Lloyd Fonvielle, who put one brilliant experience that way underneath the microscope just a few weeks before he died.  And what he came up with!:
Golly, there's no doubting it. The Gospel is the historic, true and universal metaphor, allegory and analogy of that which romantic love instantiates to the core within human experience.  If you want to understand the love of God,
observe the 'Love-O'-(Men and) Women' (Kipling).

"For many of my listeners, this will be a message from your future.
You may not hear it -- you may rebuff it -- and I understand why.  But hey, one day...]]>
</description>
<itunes:subtitle>Now we think that reality, the "real world", is what happens "between nine and five", that is, what happens at work, in the office, at school, in career, and so forth.  And a lot of people want to tell us that's true.

"But not The Vogues.  They were from Pittsburgh and they understood about shifts and hourly pay.  Yet they understand more than that!

"For the fact is, 'as you lay dying' (Faulkner), you won't give your "nine-to-five" life a single second thought.  Not one single second thought! You'll forget it all, in the absolute blink of an eye.
That's just a fact of old age and death -- the fact of our mortality.

"What you won't forget, however, is "the long haired girl who waits for you/To ease your troubled mind".  Or the one who did.  Hopefully, it's the same person. As The Vogues observe: in light of her, ''Nothing else matters at all.'

"This cast explores the 'Five O'Clock World' in terms of romantic love.
Not sororal or fraternal love.  Not even paternal and maternal love.
But romantic love.  For that's the core of loving for men and women.  That is "where the action is" (Freddy Cannon).

"Moreover, it is the core of the Gospel.  If you want to understand what Christ did, for you, look at your experience of romantic love.  For better or worse, look at your experience of romantic love.  Like my friend Lloyd Fonvielle, who put one brilliant experience that way underneath the microscope just a few weeks before he died.  And what he came up with!:
Golly, there's no doubting it. The Gospel is the historic, true and universal metaphor, allegory and analogy of that which romantic love instantiates to the core within human experience.  If you want to understand the love of God,
observe the 'Love-O'-(Men and) Women' (Kipling).

"For many of my listeners, this will be a message from your future.
You may not hear it -- you may rebuff it -- and I understand why.  But hey, one day...</itunes:subtitle>
<itunes:summary>Now we think that reality, the "real world", is what happens "between nine and five", that is, what happens at work, in the office, at school, in career, and so forth.  And a lot of people want to tell us that's true.

"But not The Vogues.  They were from Pittsburgh and they understood about shifts and hourly pay.  Yet they understand more than that!

"For the fact is, 'as you lay dying' (Faulkner), you won't give your "nine-to-five" life a single second thought.  Not one single second thought! You'll forget it all, in the absolute blink of an eye.
That's just a fact of old age and death -- the fact of our mortality.

"What you won't forget, however, is "the long haired girl who waits for you/To ease your troubled mind".  Or the one who did.  Hopefully, it's the same person. As The Vogues observe: in light of her, ''Nothing else matters at all.'

"This cast explores the 'Five O'Clock World' in terms of romantic love.
Not sororal or fraternal love.  Not even paternal and maternal love.
But romantic love.  For that's the core of loving for men and women.  That is "where the action is" (Freddy Cannon).

"Moreover, it is the core of the Gospel.  If you want to understand what Christ did, for you, look at your experience of romantic love.  For better or worse, look at your experience of romantic love.  Like my friend Lloyd Fonvielle, who put one brilliant experience that way underneath the microscope just a few weeks before he died.  And what he came up with!:
Golly, there's no doubting it. The Gospel is the historic, true and universal metaphor, allegory and analogy of that which romantic love instantiates to the core within human experience.  If you want to understand the love of God,
observe the 'Love-O'-(Men and) Women' (Kipling).

"For many of my listeners, this will be a message from your future.
You may not hear it -- you may rebuff it -- and I understand why.  But hey, one day...</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20208%20-%20Five%20O%27Clock%20World.m4a" type="audio/x-m4a" length="24236032" />
<guid>http://mbird.com/podcastgen/media/Episode%20208%20-%20Five%20O%27Clock%20World.m4a</guid>
<pubDate>Wed, 7 January 2016 23:09:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>24:52</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 207 - Is Paris Burning? (1966)</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Here are a few thoughts concerning the atrocity attacks in Paris.
I talk about Islam (and "Islamophobia"), Syrian migration into Europe,
Original Sin and "low" vs. "high" anthropology, reaction-formations among young men when drones are over their heads and they have no control, let alone "buy-in"; and finally, a threatening experience Mary and I had on Times Square recently.  Call this PZ's perspective on a current (big) event.]]>
</description>
<itunes:subtitle>Here are a few thoughts concerning the atrocity attacks in Paris.
I talk about Islam (and "Islamophobia"), Syrian migration into Europe,
Original Sin and "low" vs. "high" anthropology, reaction-formations among young men when drones are over their heads and they have no control, let alone "buy-in"; and finally, a threatening experience Mary and I had on Times Square recently.  Call this PZ's perspective on a current (big) event.</itunes:subtitle>
<itunes:summary>IHere are a few thoughts concerning the atrocity attacks in Paris.
I talk about Islam (and "Islamophobia"), Syrian migration into Europe,
Original Sin and "low" vs. "high" anthropology, reaction-formations among young men when drones are over their heads and they have no control, let alone "buy-in"; and finally, a threatening experience Mary and I had on Times Square recently.  Call this PZ's perspective on a current (big) event.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20207%20-%20Is%20Paris%20Burning_%20(1966).m4a" type="audio/x-m4a" length="24942966" />
<guid>http://mbird.com/podcastgen/media/Episode%20207%20-%20Is%20Paris%20Burning_%20(1966).m4a</guid>
<pubDate>Sun, 15 November 2015 23:09:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>25:36</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 206 - The Rich Man and Lazarus</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[I keep getting requests for a sort of "early morning Bible study" -- giving the 'treatment', you might say, to a New Testament text that stings, and also helps.  So that's what I'll do for a few episodes, beginning with this one.

Christ's Parable of the Rich Man (aka 'Dives') and Lazarus is given in St. Luke, Chapter 16.  It's a scorcher, as rough and sand-paper-like as anything he ever said. It's got that devastating line, that between there (hell) and here (heaven) there is a great gulf fixed, an impassable, untraversable barrier.

I believe this.  (My own experience confirmed it, tho' I wish it hadn't!)
That being the case, that "when you die, the time for doing is over" (Fr. Richard Ragni), what does it mean for a person in practice?  Well, it mandates a careful review of your true situation: who do you think you are, and where are you?  (Listen, I'm with you: would rather not deal!  No, no, no. Just give me a new DVD daily, like Return of the Fly or Billion Dollar Brain -- there are always new blessings like Billion Dollar Brain waiting for you (you'll never run out even if you live forever) -- and I'm set.

Unfortunately, I'm not set.  For no one knoweth the hour.
Don't delay.  Billion Dollar Brain (1967) you can put off.
Your stroke, your heart attack you can't.
Have a Panic Attack instead.  Based on this podcast.]]>
</description>
<itunes:subtitle>I keep getting requests for a sort of "early morning Bible study" -- giving the 'treatment', you might say, to a New Testament text that stings, and also helps.  So that's what I'll do for a few episodes, beginning with this one.

Christ's Parable of the Rich Man (aka 'Dives') and Lazarus is given in St. Luke, Chapter 16.  It's a scorcher, as rough and sand-paper-like as anything he ever said. It's got that devastating line, that between there (hell) and here (heaven) there is a great gulf fixed, an impassable, untraversable barrier.

I believe this.  (My own experience confirmed it, tho' I wish it hadn't!)
That being the case, that "when you die, the time for doing is over" (Fr. Richard Ragni), what does it mean for a person in practice?  Well, it mandates a careful review of your true situation: who do you think you are, and where are you?  (Listen, I'm with you: would rather not deal!  No, no, no. Just give me a new DVD daily, like Return of the Fly or Billion Dollar Brain -- there are always new blessings like Billion Dollar Brain waiting for you (you'll never run out even if you live forever) -- and I'm set.

Unfortunately, I'm not set.  For no one knoweth the hour.
Don't delay.  Billion Dollar Brain (1967) you can put off.
Your stroke, your heart attack you can't.
Have a Panic Attack instead.  Based on this podcast.</itunes:subtitle>
<itunes:summary>I keep getting requests for a sort of "early morning Bible study" -- giving the 'treatment', you might say, to a New Testament text that stings, and also helps.  So that's what I'll do for a few episodes, beginning with this one.

Christ's Parable of the Rich Man (aka 'Dives') and Lazarus is given in St. Luke, Chapter 16.  It's a scorcher, as rough and sand-paper-like as anything he ever said. It's got that devastating line, that between there (hell) and here (heaven) there is a great gulf fixed, an impassable, untraversable barrier.

I believe this.  (My own experience confirmed it, tho' I wish it hadn't!)
That being the case, that "when you die, the time for doing is over" (Fr. Richard Ragni), what does it mean for a person in practice?  Well, it mandates a careful review of your true situation: who do you think you are, and where are you?  (Listen, I'm with you: would rather not deal!  No, no, no. Just give me a new DVD daily, like Return of the Fly or Billion Dollar Brain -- there are always new blessings like Billion Dollar Brain waiting for you (you'll never run out even if you live forever) -- and I'm set.

Unfortunately, I'm not set.  For no one knoweth the hour.
Don't delay.  Billion Dollar Brain (1967) you can put off.
Your stroke, your heart attack you can't.
Have a Panic Attack instead.  Based on this podcast.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20206%20-%20The%20Rich%20Man%20and%20Lazarus.m4a" type="audio/x-m4a" length="20284151" />
<guid>http://mbird.com/podcastgen/media/Episode%20206%20-%20The%20Rich%20Man%20and%20Lazarus.m4a</guid>
<pubDate>Mon, 09 November 2015 07:09:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>20:49</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 205 - Unforeseen</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[It's not an abstraction!  It's more than something just to talk about  or consider. It could happen to you.  In fact, it probably will.

I'm talking about unforeseen death.
Some people hold on for a long time, even when they don't really want to.
Other people want to hold on, but illness intervenes and they go a dozen years earlier than they expected.  (You never expect it.)
Other people had a bad habit in youth and maybe adulthood, and it catches them later.  They never thought they would be hooked up to a respirator personally.

"I Had Too Much To Dream Last Night" (Electric Prunes, 1967):
That is, I thought I was coughing myself to death.
A habitual "nervous" cough turned into an atomic reaction and I suffocated.
Sweet Dreams Are Made of This?

"Are You Ready?": Bob Dylan asked in 1980.
"No", I might answer, in 2015.  "But I'd like to be."

Sunday after Sunday I hear sermons that seem completely to sidestep the one really big reason a person would go to church.  John Wesley never sidestepped it.  Nor did Luther.  St. Ignatius didn't, either.  Don't you.]]>
</description>
<itunes:subtitle>It's not an abstraction!  It's more than something just to talk about  or consider. It could happen to you.  In fact, it probably will.

I'm talking about unforeseen death.
Some people hold on for a long time, even when they don't really want to.
Other people want to hold on, but illness intervenes and they go a dozen years earlier than they expected.  (You never expect it.)
Other people had a bad habit in youth and maybe adulthood, and it catches them later.  They never thought they would be hooked up to a respirator personally.

"I Had Too Much To Dream Last Night" (Electric Prunes, 1967):
That is, I thought I was coughing myself to death.
A habitual "nervous" cough turned into an atomic reaction and I suffocated.
Sweet Dreams Are Made of This?

"Are You Ready?": Bob Dylan asked in 1980.
"No", I might answer, in 2015.  "But I'd like to be."

Sunday after Sunday I hear sermons that seem completely to sidestep the one really big reason a person would go to church.  John Wesley never sidestepped it.  Nor did Luther.  St. Ignatius didn't, either.  Don't you.</itunes:subtitle>
<itunes:summary>It's not an abstraction!  It's more than something just to talk about  or consider. It could happen to you.  In fact, it probably will.

I'm talking about unforeseen death.
Some people hold on for a long time, even when they don't really want to.
Other people want to hold on, but illness intervenes and they go a dozen years earlier than they expected.  (You never expect it.)
Other people had a bad habit in youth and maybe adulthood, and it catches them later.  They never thought they would be hooked up to a respirator personally.

"I Had Too Much To Dream Last Night" (Electric Prunes, 1967):
That is, I thought I was coughing myself to death.
A habitual "nervous" cough turned into an atomic reaction and I suffocated.
Sweet Dreams Are Made of This?

"Are You Ready?": Bob Dylan asked in 1980.
"No", I might answer, in 2015.  "But I'd like to be."

Sunday after Sunday I hear sermons that seem completely to sidestep the one really big reason a person would go to church.  John Wesley never sidestepped it.  Nor did Luther.  St. Ignatius didn't, either.  Don't you.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20205%20-%20Unforeseen%202.m4a" type="audio/x-m4a" length="20241288" />
<guid>http://mbird.com/podcastgen/media/Episode%20205%20-%20Unforeseen%202.m4a</guid>
<pubDate>Mon, 09 November 2015 07:09:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>20:46</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 204 - Honest to God</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Pop songs about love are like a corkscrew for understanding the Bible.
Songs like "Hooked on a Feeling" and "Don't Pull Your Love Out on Me, Baby", together with a zillion co-belligerants that are written and performed "In the Name of Love" (Thompson Twins), reveal the nature of love and loss, undoings and exaltings, and painful stasis and buoyed forward movement.

Now just imagine if professional New Testament scholars "parsed" pop songs the way they want to parse the Gospels.  You can't do it.  Or rather, you don't need to do it.  "She Loves You" (The Beatles) is so obviously true.  "Tracks of My Tears" is obviously true.  "My Girl" is obviously true.

Just like the Bible, or most of it.  When you read the Bible through the lens of acknowledged pain and the deficits that come from being  emotional human persons -- if you do that, the Bible makes sense.  Doesn't need parsing.

William Tyndale was right! The "simplest ploughboy" can understand the Bible, or at least enough of the Bible to make sense of life, and loss.

Read the Bible the way you listen to Motown.  "Reach Out (I'll Be There)" is true, to life.  As is... Luke 24.  LUV U (PZ)
]]>
</description>
<itunes:subtitle>Pop songs about love are like a corkscrew for understanding the Bible.
Songs like "Hooked on a Feeling" and "Don't Pull Your Love Out on Me, Baby", together with a zillion co-belligerants that are written and performed "In the Name of Love" (Thompson Twins), reveal the nature of love and loss, undoings and exaltings, and painful stasis and buoyed forward movement.

Now just imagine if professional New Testament scholars "parsed" pop songs the way they want to parse the Gospels.  You can't do it.  Or rather, you don't need to do it.  "She Loves You" (The Beatles) is so obviously true.  "Tracks of My Tears" is obviously true.  "My Girl" is obviously true.

Just like the Bible, or most of it.  When you read the Bible through the lens of acknowledged pain and the deficits that come from being  emotional human persons -- if you do that, the Bible makes sense.  Doesn't need parsing.

William Tyndale was right! The "simplest ploughboy" can understand the Bible, or at least enough of the Bible to make sense of life, and loss.

Read the Bible the way you listen to Motown.  "Reach Out (I'll Be There)" is true, to life.  As is... Luke 24.  LUV U (PZ)
</itunes:subtitle>
<itunes:summary>Pop songs about love are like a corkscrew for understanding the Bible.
Songs like "Hooked on a Feeling" and "Don't Pull Your Love Out on Me, Baby", together with a zillion co-belligerants that are written and performed "In the Name of Love" (Thompson Twins), reveal the nature of love and loss, undoings and exaltings, and painful stasis and buoyed forward movement.

Now just imagine if professional New Testament scholars "parsed" pop songs the way they want to parse the Gospels.  You can't do it.  Or rather, you don't need to do it.  "She Loves You" (The Beatles) is so obviously true.  "Tracks of My Tears" is obviously true.  "My Girl" is obviously true.

Just like the Bible, or most of it.  When you read the Bible through the lens of acknowledged pain and the deficits that come from being  emotional human persons -- if you do that, the Bible makes sense.  Doesn't need parsing.

William Tyndale was right! The "simplest ploughboy" can understand the Bible, or at least enough of the Bible to make sense of life, and loss.

Read the Bible the way you listen to Motown.  "Reach Out (I'll Be There)" is true, to life.  As is... Luke 24.  LUV U (PZ)
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20204%20-%20Honest%20to%20God.m4a" type="audio/x-m4a" length="21482293" />
<guid>http://mbird.com/podcastgen/media/Episode%20204%20-%20Honest%20to%20God.m4a</guid>
<pubDate>Thu, 01 October 2015 07:09:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:22:02</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 203 - Pope Francis and the Historical Jesus</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[
The music is "Good Vibrations" at the start, by The Beach Boys; and "I Knew Jesus (Before He Was a Super Star)", at the end, by Glen Campbell.

Here is the description for iTunes and also the blurb for Mockingbird:

So much has been written -- I mean, SO MUCH -- concerning the so-called Historical Jesus: a welter of books and "Untersuchungen".  I've spent most of my career reading these books, and writing a few, too.

Then Pope Francis came along and put them all in a cocked hat.  This is because if you want to see with your own eyes how Jesus operated in the New Testament -- how he acted, how he spoke, how he was desired, and how he was received -- all you need to do is watch Francis.  Phrancis.

The way Christ was with Zacchaeus, Bartimaeus, the man at the Pool of Bethesda, the woman with the issue of blood, Jairus -- the beat goes on:
that's the way Francis acts, and acted while he was under the scrutiny of all of us.
Just watch him on the Philadelphia Airport tarmac, at the shrine to St. Mary the Untier of Knots in Philadelphia, at Our Lady Queen of Angels School in East Harlem,
and at the state prison near Philadelphia.  Just watch!

All your questions about the conduct and message of the historical Jesus -- or almost all of them -- will be answered.  It just takes a few videos, a couple speeches, a few infants kissed, a few cripples blessed.

Remember the song by The Fifth Dimension, "Blowing Away"?  (George Harrison wrote a similar song.)  So much of what I read and learned over 40 ytears just got, well, blown away -- by the Real Thing.]]>
</description>
<itunes:subtitle>
The music is "Good Vibrations" at the start, by The Beach Boys; and "I Knew Jesus (Before He Was a Super Star)", at the end, by Glen Campbell.

Here is the description for iTunes and also the blurb for Mockingbird:

So much has been written -- I mean, SO MUCH -- concerning the so-called Historical Jesus: a welter of books and "Untersuchungen".  I've spent most of my career reading these books, and writing a few, too.

Then Pope Francis came along and put them all in a cocked hat.  This is because if you want to see with your own eyes how Jesus operated in the New Testament -- how he acted, how he spoke, how he was desired, and how he was received -- all you need to do is watch Francis.  Phrancis.

The way Christ was with Zacchaeus, Bartimaeus, the man at the Pool of Bethesda, the woman with the issue of blood, Jairus -- the beat goes on:
that's the way Francis acts, and acted while he was under the scrutiny of all of us.
Just watch him on the Philadelphia Airport tarmac, at the shrine to St. Mary the Untier of Knots in Philadelphia, at Our Lady Queen of Angels School in East Harlem,
and at the state prison near Philadelphia.  Just watch!

All your questions about the conduct and message of the historical Jesus -- or almost all of them -- will be answered.  It just takes a few videos, a couple speeches, a few infants kissed, a few cripples blessed.

Remember the song by The Fifth Dimension, "Blowing Away"?  (George Harrison wrote a similar song.)  So much of what I read and learned over 40 ytears just got, well, blown away -- by the Real Thing.</itunes:subtitle>
<itunes:summary>
The music is "Good Vibrations" at the start, by The Beach Boys; and "I Knew Jesus (Before He Was a Super Star)", at the end, by Glen Campbell.

Here is the description for iTunes and also the blurb for Mockingbird:

So much has been written -- I mean, SO MUCH -- concerning the so-called Historical Jesus: a welter of books and "Untersuchungen".  I've spent most of my career reading these books, and writing a few, too.

Then Pope Francis came along and put them all in a cocked hat.  This is because if you want to see with your own eyes how Jesus operated in the New Testament -- how he acted, how he spoke, how he was desired, and how he was received -- all you need to do is watch Francis.  Phrancis.

The way Christ was with Zacchaeus, Bartimaeus, the man at the Pool of Bethesda, the woman with the issue of blood, Jairus -- the beat goes on:
that's the way Francis acts, and acted while he was under the scrutiny of all of us.
Just watch him on the Philadelphia Airport tarmac, at the shrine to St. Mary the Untier of Knots in Philadelphia, at Our Lady Queen of Angels School in East Harlem,
and at the state prison near Philadelphia.  Just watch!

All your questions about the conduct and message of the historical Jesus -- or almost all of them -- will be answered.  It just takes a few videos, a couple speeches, a few infants kissed, a few cripples blessed.

Remember the song by The Fifth Dimension, "Blowing Away"?  (George Harrison wrote a similar song.)  So much of what I read and learned over 40 ytears just got, well, blown away -- by the Real Thing.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20203%20-%20Pope%20Francis%20and%20the%20Quest%20for%20the%20Historical%20Jesus.m4a" type="audio/x-m4a" length="21034143" />
<guid>http://mbird.com/podcastgen/media/Episode%20203%20-%20Pope%20Francis%20and%20the%20Quest%20for%20the%20Historical%20Jesus.m4a" type="audio/x-m4a" length="21034143</guid>
<pubDate>Thu, 01 October 2015 07:09:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:21:00</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 202: Pope Francis</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Did you cry at any point as you watched Pope Francis in action during his visit?  If you did, when was it?  What made you cry?

"Now it wasn't just John Boehner!  I noticed as I watched the Pope inter-acting with individuals, and especially with individuals in acute need or distress, that it was those encounters that touched me personally.  (I was abreacting all over the place.)

I don't have spina bifida.  I'm not in a wheelchair.  I'm not six years old, nor 84 (yet).  Nor am I homeless.  But hey: Sometimes I Feel Like a Motherless Child!  My tears flow freely, and often not freely enough.  In other words, I identify with distress and need.  I identify with exclusion, tho' you might not know it.  I identify with rejection and exile, tho' again, you might not know it.

The point is, everybody's at their own point of need.  Everybody's got something they're thinking about that's painful.

Pope Francis, walking in the steps of the great Understander, the great Sympathizer, touched the core pain.  He touched the core pain of many, many people.  It was busting out all over.

I think we're each walking in "The Tracks of My Tears". (Thank God for Smokey Robinson and the Miracles.)  And when they bubble up, you can just smell the healing.]]>
</description>
<itunes:subtitle>Did you cry at any point as you watched Pope Francis in action during his visit?  If you did, when was it?  What made you cry?

"Now it wasn't just John Boehner!  I noticed as I watched the Pope inter-acting with individuals, and especially with individuals in acute need or distress, that it was those encounters that touched me personally.  (I was abreacting all over the place.)

I don't have spina bifida.  I'm not in a wheelchair.  I'm not six years old, nor 84 (yet).  Nor am I homeless.  But hey: Sometimes I Feel Like a Motherless Child!  My tears flow freely, and often not freely enough.  In other words, I identify with distress and need.  I identify with exclusion, tho' you might not know it.  I identify with rejection and exile, tho' again, you might not know it.

The point is, everybody's at their own point of need.  Everybody's got something they're thinking about that's painful.

Pope Francis, walking in the steps of the great Understander, the great Sympathizer, touched the core pain.  He touched the core pain of many, many people.  It was busting out all over.

I think we're each walking in "The Tracks of My Tears". (Thank God for Smokey Robinson and the Miracles.)  And when they bubble up, you can just smell the healing.</itunes:subtitle>
<itunes:summary>Did you cry at any point as you watched Pope Francis in action during his visit?  If you did, when was it?  What made you cry?

"Now it wasn't just John Boehner!  I noticed as I watched the Pope inter-acting with individuals, and especially with individuals in acute need or distress, that it was those encounters that touched me personally.  (I was abreacting all over the place.)

I don't have spina bifida.  I'm not in a wheelchair.  I'm not six years old, nor 84 (yet).  Nor am I homeless.  But hey: Sometimes I Feel Like a Motherless Child!  My tears flow freely, and often not freely enough.  In other words, I identify with distress and need.  I identify with exclusion, tho' you might not know it.  I identify with rejection and exile, tho' again, you might not know it.

The point is, everybody's at their own point of need.  Everybody's got something they're thinking about that's painful.

Pope Francis, walking in the steps of the great Understander, the great Sympathizer, touched the core pain.  He touched the core pain of many, many people.  It was busting out all over.

I think we're each walking in "The Tracks of My Tears". (Thank God for Smokey Robinson and the Miracles.)  And when they bubble up, you can just smell the healing.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20202-%20Pope%20Francis-%20SPECIAL%20EDITION.m4a" type="audio/x-m4a" length="21034143" />
<guid>http://mbird.com/podcastgen/media/Episode%20202-%20Pope%20Francis-%20SPECIAL%20EDITION.m4a</guid>
<pubDate>Mon, 28 Sep 2015 07:09:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:21:35</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 201: The Real Thing</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Is there anything to it?
Is vertical religion -- not just calls to social justice, not just implied belief (system) -- but actual vertical religion rooted in anything resembling fact?

I'm utterly bummed these days by mainstream Christianity that just leaps over the religious element on the way to the "mission" element.  There's nothing there, I mean nothing there -- to aid an everyday sufferer.  Like me, for example.

On the other hand, evangelicals continue to fake it royally.  They'll talk you blue in the face about God's being present in the worst and darkest moments of your life.  But when it's you who is actually there, you who is sitting flummoxed in the Shadows of Knight (1966), they act as if they didn't mean a word of it.  Grace?  Real Grace? "You have got to be kidding."  Book him!

The theme of this 201st podcast is Real Religion.  Does it exist?
What is it like if it does?

Oh, and SEE The Sentinel (1976).  Don't miss The Sentinel (1976).
It's about to be released on Blu Ray; and with all its memorable eccentricities,
it is a total home run about The Real Thing.  LUV U.  (PZ)

This podcast is dedicated to Melina Smith.]]>
</description>
<itunes:subtitle>Is there anything to it?
Is vertical religion -- not just calls to social justice, not just implied belief (system) -- but actual vertical religion rooted in anything resembling fact?

I'm utterly bummed these days by mainstream Christianity that just leaps over the religious element on the way to the "mission" element.  There's nothing there, I mean nothing there -- to aid an everyday sufferer.  Like me, for example.

On the other hand, evangelicals continue to fake it royally.  They'll talk you blue in the face about God's being present in the worst and darkest moments of your life.  But when it's you who is actually there, you who is sitting flummoxed in the Shadows of Knight (1966), they act as if they didn't mean a word of it.  Grace?  Real Grace? "You have got to be kidding."  Book him!

The theme of this 201st podcast is Real Religion.  Does it exist?
What is it like if it does?

Oh, and SEE The Sentinel (1976).  Don't miss The Sentinel (1976).
It's about to be released on Blu Ray; and with all its memorable eccentricities,
it is a total home run about The Real Thing.  LUV U.  (PZ)

This podcast is dedicated to Melina Smith.</itunes:subtitle>
<itunes:summary>Is there anything to it?
Is vertical religion -- not just calls to social justice, not just implied belief (system) -- but actual vertical religion rooted in anything resembling fact?

I'm utterly bummed these days by mainstream Christianity that just leaps over the religious element on the way to the "mission" element.  There's nothing there, I mean nothing there -- to aid an everyday sufferer.  Like me, for example.

On the other hand, evangelicals continue to fake it royally.  They'll talk you blue in the face about God's being present in the worst and darkest moments of your life.  But when it's you who is actually there, you who is sitting flummoxed in the Shadows of Knight (1966), they act as if they didn't mean a word of it.  Grace?  Real Grace? "You have got to be kidding."  Book him!

The theme of this 201st podcast is Real Religion.  Does it exist?
What is it like if it does?

Oh, and SEE The Sentinel (1976).  Don't miss The Sentinel (1976).
It's about to be released on Blu Ray; and with all its memorable eccentricities,
it is a total home run about The Real Thing.  LUV U.  (PZ)

This podcast is dedicated to Melina Smith.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20201%20-%20The%20Real%20Thing.m4a" type="audio/x-m4a" length="23920752" />
<guid>http://mbird.com/podcastgen/media/Episode%20201%20-%20The%20Real%20Thing.m4a</guid>
<pubDate>Tue, 16 Sep 2015 21:09:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:24:33</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 200: Catatonia</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is not the Who's Final Tour.  (They always come back.)
So maybe it is the Who's Final Tour.

Whatever it is, it's Podcast 200, and that's a benchmark.
Somehow.
So I decided to sum up the two core themes of the last... 100 or so
casts, and also tell you something that's blown my mind recently.
It's an instance of catatonia by way of Catalonia.

Seriously, the two core themes of PZ's Podcast are the durability and necessity of romantic connection; and the presence of God when a person is at the end of his or her rope.  'God meets us at our point of need.'

Gosh, I've seen that happen a lot.  Not least of all, to me.

And I know, too, from Mary  -- 'Along Comes Mary' (The Association) -- that the boy-girl side of things is paramount.  Nothing above it.

Now, for 23 short minutes, Come Fly With Me.]]>
</description>
<itunes:subtitle>IThis is not the Who's Final Tour.  (They always come back.)
So maybe it is the Who's Final Tour.

Whatever it is, it's Podcast 200, and that's a benchmark.
Somehow.
So I decided to sum up the two core themes of the last... 100 or so
casts, and also tell you something that's blown my mind recently.
It's an instance of catatonia by way of Catalonia.

Seriously, the two core themes of PZ's Podcast are the durability and necessity of romantic connection; and the presence of God when a person is at the end of his or her rope.  'God meets us at our point of need.'

Gosh, I've seen that happen a lot.  Not least of all, to me.

And I know, too, from Mary  -- 'Along Comes Mary' (The Association) -- that the boy-girl side of things is paramount.  Nothing above it.

Now, for 23 short minutes, Come Fly With Me.</itunes:subtitle>
<itunes:summary>This is not the Who's Final Tour.  (They always come back.)
So maybe it is the Who's Final Tour.

Whatever it is, it's Podcast 200, and that's a benchmark.
Somehow.
So I decided to sum up the two core themes of the last... 100 or so
casts, and also tell you something that's blown my mind recently.
It's an instance of catatonia by way of Catalonia.

Seriously, the two core themes of PZ's Podcast are the durability and necessity of romantic connection; and the presence of God when a person is at the end of his or her rope.  'God meets us at our point of need.'

Gosh, I've seen that happen a lot.  Not least of all, to me.

And I know, too, from Mary  -- 'Along Comes Mary' (The Association) -- that the boy-girl side of things is paramount.  Nothing above it.

Now, for 23 short minutes, Come Fly With Me.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20200%20-%20Catatonia.m4a" type="audio/x-m4a" length="22138564" />
<guid>http://mbird.com/podcastgen/media/Episode%20200%20-%20Catatonia.m4a</guid>
<pubDate>Tue, 12 Aug 2015 21:09:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:23:43</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 199: What Actually Happens</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[If you don't factor in the element of romantic love -- or at least its possibility -- you'll surprise yourself when you start making decisions in life.

Sometimes I wish I could give a college commencement address.  (No one is ever going to ask.)  But I should like to talk about romantic love, and its over-riding, over-reaching, superseding strength as an element -- the decisive element -- in personal decision-making.

I can't really say that, though.  Many people seem to want to "privilege" career and/or professional choices over their love life.  They seem to want to, at least.  But then you surprise yourself!  You quit your job, or apply for a job in another city, or go back to school; and the real reason is that you've met someone, or want to.  Even desperately want to.

Romantic love always wins.  Tho' it takes too long these days.
So much romantic time is wasted by the effort, energy and time, g__dammit, given to careers that end up, eventually, feeling phony, futile, arbitrary, and selfish.

What am I saying?  Put romantic love first. Hey, and then, work's a piece of cake.  You'll probably be promoted at work the moment you start promoting yourself to her.]]>
</description>
<itunes:subtitle>If you don't factor in the element of romantic love -- or at least its possibility -- you'll surprise yourself when you start making decisions in life.

Sometimes I wish I could give a college commencement address.  (No one is ever going to ask.)  But I should like to talk about romantic love, and its over-riding, over-reaching, superseding strength as an element -- the decisive element -- in personal decision-making.

I can't really say that, though.  Many people seem to want to "privilege" career and/or professional choices over their love life.  They seem to want to, at least.  But then you surprise yourself!  You quit your job, or apply for a job in another city, or go back to school; and the real reason is that you've met someone, or want to.  Even desperately want to.

Romantic love always wins.  Tho' it takes too long these days.
So much romantic time is wasted by the effort, energy and time, g__dammit, given to careers that end up, eventually, feeling phony, futile, arbitrary, and selfish.

What am I saying?  Put romantic love first. Hey, and then, work's a piece of cake.  You'll probably be promoted at work the moment you start promoting yourself to her.</itunes:subtitle>
<itunes:summary>If you don't factor in the element of romantic love -- or at least its possibility -- you'll surprise yourself when you start making decisions in life.

Sometimes I wish I could give a college commencement address.  (No one is ever going to ask.)  But I should like to talk about romantic love, and its over-riding, over-reaching, superseding strength as an element -- the decisive element -- in personal decision-making.

I can't really say that, though.  Many people seem to want to "privilege" career and/or professional choices over their love life.  They seem to want to, at least.  But then you surprise yourself!  You quit your job, or apply for a job in another city, or go back to school; and the real reason is that you've met someone, or want to.  Even desperately want to.

Romantic love always wins.  Tho' it takes too long these days.
So much romantic time is wasted by the effort, energy and time, g__dammit, given to careers that end up, eventually, feeling phony, futile, arbitrary, and selfish.

What am I saying?  Put romantic love first. Hey, and then, work's a piece of cake.  You'll probably be promoted at work the moment you start promoting yourself to her.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20199%20-%20What%20Actually%20Happens.m4a" type="audio/x-m4a" length="21860322" />
<guid>http://mbird.com/podcastgen/media/Episode%20199%20-%20What%20Actually%20Happens.m4a</guid>
<pubDate>Sun, 08 Aug 2015 21:09:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:22:26</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 198: Mirage Fighter</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Talk about being misunderstood!:
Artur London was one of the 11 most misunderstood men in the world,
at least at the end of 1951.  London was a defendant in the Slansky Trial,
a "show trial" under Joseph Stalin.

After suffering the most inhuman torture and brainwashing, London was sentenced to life imprisonment for crimes not one of which he had come 10,000 light years close to committing.

Later on, Arthur London was released, rehabilitated; and now they name streets after him.

Arthur London said that his life's struggle was to differentiate between the essence of an ideal, and the form in which that ideal had taken shape politically -- a debased and wicked form, it turns out.  London also said that being a Communist in a Soviet prison was like being a Christian tortured by the Spanish Inquisition.  Christians, like Communists, could only survive, and persist,
if they clearly separated the Thing Signified from the Sign -- the Substance from the Form.

Good luck, Arthur!
How'd it work for you, Paul?
Way to go, Czech Communist Party.  That's the way (O Mother Church)/ I Like It (KC & The Sunshine Band)]]>
</description>
<itunes:subtitle>Talk about being misunderstood!:
Artur London was one of the 11 most misunderstood men in the world,
at least at the end of 1951.  London was a defendant in the Slansky Trial,
a "show trial" under Joseph Stalin.

After suffering the most inhuman torture and brainwashing, London was sentenced to life imprisonment for crimes not one of which he had come 10,000 light years close to committing.

Later on, Arthur London was released, rehabilitated; and now they name streets after him.

Arthur London said that his life's struggle was to differentiate between the essence of an ideal, and the form in which that ideal had taken shape politically -- a debased and wicked form, it turns out.  London also said that being a Communist in a Soviet prison was like being a Christian tortured by the Spanish Inquisition.  Christians, like Communists, could only survive, and persist,
if they clearly separated the Thing Signified from the Sign -- the Substance from the Form.

Good luck, Arthur!
How'd it work for you, Paul?
Way to go, Czech Communist Party.  That's the way (O Mother Church)/ I Like It (KC &amp; The Sunshine Band)</itunes:subtitle>
<itunes:summary>Talk about being misunderstood!:
Artur London was one of the 11 most misunderstood men in the world,
at least at the end of 1951.  London was a defendant in the Slansky Trial,
a "show trial" under Joseph Stalin.

After suffering the most inhuman torture and brainwashing, London was sentenced to life imprisonment for crimes not one of which he had come 10,000 light years close to committing.

Later on, Arthur London was released, rehabilitated; and now they name streets after him.

Arthur London said that his life's struggle was to differentiate between the essence of an ideal, and the form in which that ideal had taken shape politically -- a debased and wicked form, it turns out.  London also said that being a Communist in a Soviet prison was like being a Christian tortured by the Spanish Inquisition.  Christians, like Communists, could only survive, and persist,
if they clearly separated the Thing Signified from the Sign -- the Substance from the Form.

Good luck, Arthur!
How'd it work for you, Paul?
Way to go, Czech Communist Party.  That's the way (O Mother Church)/ I Like It (KC &amp; The Sunshine Band)</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20198%20-%20Mirage%20Fighter.m4a" type="audio/x-m4a" length="22593892" />
<guid>http://mbird.com/podcastgen/media/Episode%20198%20-%20Mirage%20Fighter.m4a</guid>
<pubDate>Sun, 08 Aug 2015 19:14:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:23:11</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 197: The Sacraments Rightly Understood</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[The church is today so vastly over-eucharisted that you can barely pause to catch your breath.  This cast offers an alternative view of the Holy Communion, as well as of Baptism. The original Prayer Book definition of a sacrament was that it is 'an outward and visible sign of an inward and spiritual grace'.  What a refined and powerful expression.  So now Let Smokey Sing (ABC) and find... The Face Behind the Mask (1941).  This cast is dedicated to Nancy W. Hanna.]]>
</description>
<itunes:subtitle>The church is today so vastly over-eucharisted that you can barely pause to catch your breath.  This cast offers an alternative view of the Holy Communion, as well as of Baptism. The original Prayer Book definition of a sacrament was that it is 'an outward and visible sign of an inward and spiritual grace'.  What a refined and powerful expression.  So now Let Smokey Sing (ABC) and find... The Face Behind the Mask (1941).  This cast is dedicated to Nancy W. Hanna.</itunes:subtitle>
<itunes:summary>The church is today so vastly over-eucharisted that you can barely pause to catch your breath.  This cast offers an alternative view of the Holy Communion, as well as of Baptism. The original Prayer Book definition of a sacrament was that it is 'an outward and visible sign of an inward and spiritual grace'.  What a refined and powerful expression.  So now Let Smokey Sing (ABC) and find... The Face Behind the Mask (1941).  This cast is dedicated to Nancy W. Hanna.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20197%20-%20The%20Sacraments%20Rightly%20Understood.m4a" type="audio/x-m4a" length="20514259" />
<guid>http://mbird.com/podcastgen/media/Episode%20197%20-%20The%20Sacraments%20Rightly%20Understood.m4a</guid>
<pubDate>Sun, 08 Aug 2015 10:14:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:21:03</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 196: Cimarron</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["The movie Cimarron, which was released in 1931, won the Academy Award for Best Picture that year.  (Did you know this?)

It's great blessing, Cimarron -- which was based on the novel Cimarron, written by Edna Ferber.  But you'd never know it's a blessing if you relied on the critics.

Cimarron has become notorious in recent times for its racial and ethnic stereotyping.  When you read contemporary descriptions of this movie,
it's as if you're being told to put your hands in front of your eyes and also cup them around your ears.

Yet the amazing thing is that Cimarron is actually the opposite of what it's accused of being.  It's actually a definitive portrait of "Radical Hospitality",
as the pharisees and hypocrites are all smote; and the outsiders and excluded people are all promoted!  Cimarron depicts the triumph of the "minority" in life.
You've got to see it.

But only if you aren't carrying so much presuppositional baggage that your eyes are already closed and your ears already shut.  Cimarron  is a portrait of that great House for All Sinners and Saints.

Oh, and there's a mistake at the end of the cast:
The music is not by Chrissie Hynde.  It's by Talk Talk.]]>
</description>
<itunes:subtitle>"The movie Cimarron, which was released in 1931, won the Academy Award for Best Picture that year.  (Did you know this?)

It's great blessing, Cimarron -- which was based on the novel Cimarron, written by Edna Ferber.  But you'd never know it's a blessing if you relied on the critics.

Cimarron has become notorious in recent times for its racial and ethnic stereotyping.  When you read contemporary descriptions of this movie,
it's as if you're being told to put your hands in front of your eyes and also cup them around your ears.

Yet the amazing thing is that Cimarron is actually the opposite of what it's accused of being.  It's actually a definitive portrait of "Radical Hospitality",
as the pharisees and hypocrites are all smote; and the outsiders and excluded people are all promoted!  Cimarron depicts the triumph of the "minority" in life.
You've got to see it.

But only if you aren't carrying so much presuppositional baggage that your eyes are already closed and your ears already shut.  Cimarron  is a portrait of that great House for All Sinners and Saints.

Oh, and there's a mistake at the end of the cast:
The music is not by Chrissie Hynde.  It's by Talk Talk.</itunes:subtitle>
<itunes:summary>"The movie Cimarron, which was released in 1931, won the Academy Award for Best Picture that year.  (Did you know this?)

It's great blessing, Cimarron -- which was based on the novel Cimarron, written by Edna Ferber.  But you'd never know it's a blessing if you relied on the critics.

Cimarron has become notorious in recent times for its racial and ethnic stereotyping.  When you read contemporary descriptions of this movie,
it's as if you're being told to put your hands in front of your eyes and also cup them around your ears.

Yet the amazing thing is that Cimarron is actually the opposite of what it's accused of being.  It's actually a definitive portrait of "Radical Hospitality",
as the pharisees and hypocrites are all smote; and the outsiders and excluded people are all promoted!  Cimarron depicts the triumph of the "minority" in life.
You've got to see it.

But only if you aren't carrying so much presuppositional baggage that your eyes are already closed and your ears already shut.  Cimarron  is a portrait of that great House for All Sinners and Saints.

Oh, and there's a mistake at the end of the cast:
The music is not by Chrissie Hynde.  It's by Talk Talk.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20196%20-%20Cimarron%20(1931).m4a" type="audio/x-m4a" length="24438293 " />
<guid>http://mbird.com/podcastgen/media/Episode%20196%20-%20Cimarron%20(1931).m4a</guid>
<pubDate>Fri, 31 Jul 2015 10:14:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:25:04</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 195: Shag (The Movie)</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Shag The Movie (1989) is a great little entertainment!  It captures perfectly, and with high humor and enormous love and heart, the Beach Music phenomenon of the 1960s.  Today, however, it touches a current issue -- right from the opening credits.  What do you do with distressing material -- images and associations that relate to things you'd rather forget?  The general answer seems to be, well, you burn them!  You do away with them.  You haul them down -- and out.]]>
</description>
<itunes:subtitle>Shag The Movie (1989) is a great little entertainment!  It captures perfectly, and with high humor and enormous love and heart, the Beach Music phenomenon of the 1960s.  Today, however, it touches a current issue -- right from the opening credits.  What do you do with distressing material -- images and associations that relate to things you'd rather forget?  The general answer seems to be, well, you burn them!  You do away with them.  You haul them down -- and out.</itunes:subtitle>
<itunes:summary>Shag The Movie (1989) is a great little entertainment!  It captures perfectly, and with high humor and enormous love and heart, the Beach Music phenomenon of the 1960s.  Today, however, it touches a current issue -- right from the opening credits.  What do you do with distressing material -- images and associations that relate to things you'd rather forget?  The general answer seems to be, well, you burn them!  You do away with them.  You haul them down -- and out.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20195%20-%20Shag%20(The%20Movie).m4a" type="audio/x-m4a" length="20397371" />
<guid>http://mbird.com/podcastgen/media/Episode%20195%20-%20Shag%20(The%20Movie).m4a</guid>
<pubDate>Tue, 28 Jul 2015 10:14:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:20:56</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 194: Left Hand Path</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[I think I'm supposed to understand why right-wing people are intolerant. But it's harder for me to understand why left-wing people are intolerant. Guess I thought they were supposed to be about freedom and diversity.  Come to find out, they're not. So I had to go back to a source that's almost been "blacklisted" itself. It's the movie My Son John (1952), starring Helen Hayes and Robert Wagner. Hey, but isn't that a reactionary movie from the Eisenhower movie? No, it's not.  It's an excruciating journey into the cause of liberal intolerance. If I -- meaning PZ -- had a mother and a father like 'John' does in My Son John, I'd probably do what he did.  I'm almost sure I'd want to. ]]>
</description>
<itunes:subtitle>I think I'm supposed to understand why right-wing people are intolerant. But it's harder for me to understand why left-wing people are intolerant. Guess I thought they were supposed to be about freedom and diversity.  Come to find out, they're not. So I had to go back to a source that's almost been "blacklisted" itself. It's the movie My Son John (1952), starring Helen Hayes and Robert Wagner. Hey, but isn't that a reactionary movie from the Eisenhower movie? No, it's not.  It's an excruciating journey into the cause of liberal intolerance. If I -- meaning PZ -- had a mother and a father like 'John' does in My Son John, I'd probably do what he did.  I'm almost sure I'd want to.</itunes:subtitle>
<itunes:summary>I think I'm supposed to understand why right-wing people are intolerant. But it's harder for me to understand why left-wing people are intolerant. Guess I thought they were supposed to be about freedom and diversity.  Come to find out, they're not. So I had to go back to a source that's almost been "blacklisted" itself. It's the movie My Son John (1952), starring Helen Hayes and Robert Wagner. Hey, but isn't that a reactionary movie from the Eisenhower movie? No, it's not.  It's an excruciating journey into the cause of liberal intolerance. If I -- meaning PZ -- had a mother and a father like 'John' does in My Son John, I'd probably do what he did.  I'm almost sure I'd want to.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20194%20-%20Left%20Hand%20Path%202.m4a" type="audio/x-m4a" length="19246708" />
<guid>http://mbird.com/podcastgen/media/Episode%20194%20-%20Left%20Hand%20Path%202.m4a</guid>
<pubDate>Fri, 24 Jul 2015 10:14:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:19:45</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 193: Cross Dressing</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[The Gallant Hours (1959) is one heuristic movie.  Not only does it teach the Church a thing or two about how to honor faithful service, but it depicts an entirely ideal instance of how to dress properly if you're a minister -- or, Heav'n forfend, a "priest".  The last scene of The Gallant Hours is one amazing illustration of the triumph of substance over form in connection with haberdashery. If you're a member of the clergy, or are close to one, PLEASE, help them dress down.  We need clergy who dress down!  The future of the world depends on it. ]]>
</description>
<itunes:subtitle>The Gallant Hours (1959) is one heuristic movie.  Not only does it teach the Church a thing or two about how to honor faithful service, but it depicts an entirely ideal instance of how to dress properly if you're a minister -- or, Heav'n forfend, a "priest".  The last scene of The Gallant Hours is one amazing illustration of the triumph of substance over form in connection with haberdashery. If you're a member of the clergy, or are close to one, PLEASE, help them dress down.  We need clergy who dress down!  The future of the world depends on it. </itunes:subtitle>
<itunes:summary>The Gallant Hours (1959) is one heuristic movie.  Not only does it teach the Church a thing or two about how to honor faithful service, but it depicts an entirely ideal instance of how to dress properly if you're a minister -- or, Heav'n forfend, a "priest".  The last scene of The Gallant Hours is one amazing illustration of the triumph of substance over form in connection with haberdashery. If you're a member of the clergy, or are close to one, PLEASE, help them dress down.  We need clergy who dress down!  The future of the world depends on it. </itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20193%20-%20Cross%20Dressing.m4a" type="audio/x-m4a" length="22089280" />
<guid>http://mbird.com/podcastgen/media/Episode%20193%20-%20Cross%20Dressing.m4a</guid>
<pubDate>Tue, 21 Jul 2015 10:14:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:22:40</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 192: How to Save the Church (But Our Lips Are Sealed)</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[The Church I have known all my life is in free fall numerically.
I'm talking about Sunday attendance in everyday parishes.
This is not conceptual:  one parish Mary and I served for six and a half years has recently closed.  (Yes, it's been there since 1832, and now is literally a shell, the congregation having gone formally out of existence!)  Another church we served, also for six years but out on Long Island, has seen its attendance fall so drastically that its diocese wants to convert it into a different kind of ministry altogether.
But this podcast is not a list of what went wrong, but what could be done right.  The lesson, dear reader, comes from a little movie called The Gallant Hours.  This movie's got just about everything you need to know, about everything.]]>
</description>
<itunes:subtitle>The Church I have known all my life is in free fall numerically.
I'm talking about Sunday attendance in everyday parishes.
This is not conceptual:  one parish Mary and I served for six and a half years has recently closed.  (Yes, it's been there since 1832, and now is literally a shell, the congregation having gone formally out of existence!)  Another church we served, also for six years but out on Long Island, has seen its attendance fall so drastically that its diocese wants to convert it into a different kind of ministry altogether.
But this podcast is not a list of what went wrong, but what could be done right.  The lesson, dear reader, comes from a little movie called The Gallant Hours.  This movie's got just about everything you need to know, about everything.</itunes:subtitle>
<itunes:summary>The Church I have known all my life is in free fall numerically.
I'm talking about Sunday attendance in everyday parishes.
This is not conceptual:  one parish Mary and I served for six and a half years has recently closed.  (Yes, it's been there since 1832, and now is literally a shell, the congregation having gone formally out of existence!)  Another church we served, also for six years but out on Long Island, has seen its attendance fall so drastically that its diocese wants to convert it into a different kind of ministry altogether.
But this podcast is not a list of what went wrong, but what could be done right.  The lesson, dear reader, comes from a little movie called The Gallant Hours.  This movie's got just about everything you need to know, about everything.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20192%20--%20How%20to%20Save%20the%20Church%20(But%20Our%20Lips%20Are%20Sealed).m4a" type="audio/x-m4a" length="23059693 " />
<guid>http://mbird.com/podcastgen/media/Episode%20192%20--%20How%20to%20Save%20the%20Church%20(But%20Our%20Lips%20Are%20Sealed).m4a</guid>
<pubDate>Mon, 20 Jul 2015 10:14:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:23:40</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 191: Shakin' All Over</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This talk concerns the indelibility of certain memories, and why they, and not other memories, are indelible.  It also concerns a worrying vision I had in January.  But it's all one!  Here is my little attempt at some wise counsel: how to integrate indelible aspects of your grown life with the fact that you'll see it all again, up close and personal, the day you die.  But again, it's all One. And hey, didn't Charles Reade say, "It is never too late to mend."]]>
</description>
<itunes:subtitle>This talk concerns the indelibility of certain memories, and why they, and not other memories, are indelible.  It also concerns a worrying vision I had in January.  But it's all one!  Here is my little attempt at some wise counsel: how to integrate indelible aspects of your grown life with the fact that you'll see it all again, up close and personal, the day you die.  But again, it's all One. And hey, didn't Charles Reade say, "It is never too late to mend."</itunes:subtitle>
<itunes:summary>This talk concerns the indelibility of certain memories, and why they, and not other memories, are indelible.  It also concerns a worrying vision I had in January.  But it's all one!  Here is my little attempt at some wise counsel: how to integrate indelible aspects of your grown life with the fact that you'll see it all again, up close and personal, the day you die.  But again, it's all One. And hey, didn't Charles Reade say, "It is never too late to mend."</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media//Episode%20191%20--%20Shakin%27%20All%20Over.m4a" type="audio/x-m4a" length="21463040 " />
<guid>http://mbird.com/podcastgen/media//Episode%20191%20--%20Shakin%27%20All%20Over.m4a</guid>
<pubDate>Mon, 20 Jul 2015 09:14:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:22:01</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 190 - PZ's Fabulous New Dating Tips for Gals</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is a word to your future self.  You probably can't hear it today.
But I predict you'll hear it loud and clear in five years,  or maybe ten.  This is a word to your future self.  It's a new fabulous dating tip, and carries almost no exceptions, tho' I wish it did!  It has to do with internet dating, with the aging process (especially in men), and with the poignant voice of experience.
If you can "Now Hear This", it could save you years of excruciating suffering.  I mean years, maybe decades.  Maybe the rest of your life.]]>
</description>
<itunes:subtitle>This is a word to your future self.  You probably can't hear it today. But I predict you'll hear it loud and clear in five years,  or maybe ten.  This is a word to your future self.  It's a new fabulous dating tip, and carries almost no exceptions, tho' I wish it did!  It has to do with internet dating, with the aging process (especially in men), and with the poignant voice of experience. If you can "Now Hear This", it could save you years of excruciating suffering.  I mean years, maybe decades.  Maybe the rest of your life.</itunes:subtitle>
<itunes:summary>This is a word to your future self.  You probably can't hear it today. But I predict you'll hear it loud and clear in five years,  or maybe ten.  This is a word to your future self.  It's a new fabulous dating tip, and carries almost no exceptions, tho' I wish it did!  It has to do with internet dating, with the aging process (especially in men), and with the poignant voice of experience. If you can "Now Hear This", it could save you years of excruciating suffering.  I mean years, maybe decades.  Maybe the rest of your life.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20190%20-%20PZ%27s%20Fabulous%20New%20Dating%20Tip%20for%20Gals.m4a" type="audio/x-m4a" length="18341681" />
<guid>http://mbird.com/podcastgen/media/Episode%20190%20-%20PZ%27s%20Fabulous%20New%20Dating%20Tip%20for%20Gals.m4a</guid>
<pubDate>Mon, 06 Jul 2015 09:14:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:18:49</itunes:duration>
<itunes:keywords />
</item>


<item>
<title>Episode 189 - Why Weepest Thou?</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["What makes you cry?  When you have an irruption of strong feeling -- and I mean tears in this case -- what is going on?   This cast tries to get underneath some emotions we all feel, and in terms of music.  It is a subjective "take" on one's music and one's highs and lows.  And it's in the service of a Way Maker that tends in the direction of peace of mind."]]>
</description>
<itunes:subtitle>"What makes you cry?  When you have an irruption of strong feeling -- and I mean tears in this case -- what is going on?   This cast tries to get underneath some emotions we all feel, and in terms of music.  It is a subjective "take" on one's music and one's highs and lows.  And it's in the service of a Way Maker that tends in the direction of peace of mind."</itunes:subtitle>
<itunes:summary>"What makes you cry?  When you have an irruption of strong feeling -- and I mean tears in this case -- what is going on?   This cast tries to get underneath some emotions we all feel, and in terms of music.  It is a subjective "take" on one's music and one's highs and lows.  And it's in the service of a Way Maker that tends in the direction of peace of mind."</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20189%20-%20Why%20Weepest%20Thou_%202.m4a" type="audio/x-m4a" length="17774512" />
<guid>http://mbird.com/podcastgen/media/Episode%20189%20-%20Why%20Weepest%20Thou_%202.m4a</guid>
<pubDate>Sun, 28 Jun 2015 09:14:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:18:14</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 188 - Scuppernong</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Tupper Saussy (1936-2007) was a musician behind The Neon Philharmonic, who produced two memorable albums in 1968-69.  He was also a polymath who let himself get in the sights of the Internal Revenue Service,
and paid a heavy price for it.  Moreover, he was a devout Christian, of old-fashioned Episcopalian provenance.   This week he is on my mind because the fate of Tupper Saussy made me think of a friend who is in some trouble. "Handle Me With Care" is what Tupper Saussy needed.  It is what my friend needs.  And it's what the world never and the church rarely does]]>
</description>
<itunes:subtitle>Tupper Saussy (1936-2007) was a musician behind The Neon Philharmonic, who produced two memorable albums in 1968-69.  He was also a polymath who let himself get in the sights of the Internal Revenue Service,
and paid a heavy price for it.  Moreover, he was a devout Christian, of old-fashioned Episcopalian provenance.   This week he is on my mind because the fate of Tupper Saussy made me think of a friend who is in some trouble. "Handle Me With Care" is what Tupper Saussy needed.  It is what my friend needs.  And it's what the world never and the church rarely does</itunes:subtitle>
<itunes:summary>Tupper Saussy (1936-2007) was a musician behind The Neon Philharmonic, who produced two memorable albums in 1968-69.  He was also a polymath who let himself get in the sights of the Internal Revenue Service,
and paid a heavy price for it.  Moreover, he was a devout Christian, of old-fashioned Episcopalian provenance.   This week he is on my mind because the fate of Tupper Saussy made me think of a friend who is in some trouble. "Handle Me With Care" is what Tupper Saussy needed.  It is what my friend needs.  And it's what the world never and the church rarely does</itunes:summary>
<enclosure url="http://mbird.com//podcastgen/media/Episode%20188%20-%20Scuppernong.m4a" type="audio/x-m4a" length="19598189 " />
<guid>http://mbird.com//podcastgen/media/Episode%20188%20-%20Scuppernong.m4a</guid>
<pubDate>Tue, 23 Jun 2015 20:41:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:20:06</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 187 - Norwegian Wood</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Nevil Shute, whose proper name was Nevil Shute Norway, was a British novelist whose work took an odd turn in mid-career.  He was a kind of parasitologist of human nature, always asking the big questions:
Why do people act the way they do? How does the past affect the present?
Is there something more to it that is beyond the apparent?  Shute thought there was, but he was a tentative explorer. (He was also a churchgoer.) Did he pierce "the veil"?  My answer to that is maybe.]]>
</description>
<itunes:subtitle>Nevil Shute, whose proper name was Nevil Shute Norway, was a British novelist whose work took an odd turn in mid-career.  He was a kind of parasitologist of human nature, always asking the big questions:
Why do people act the way they do? How does the past affect the present?
Is there something more to it that is beyond the apparent?  Shute thought there was, but he was a tentative explorer. (He was also a churchgoer.) Did he pierce "the veil"?  My answer to that is maybe.</itunes:subtitle>
<itunes:summary>Nevil Shute, whose proper name was Nevil Shute Norway, was a British novelist whose work took an odd turn in mid-career.  He was a kind of parasitologist of human nature, always asking the big questions:
Why do people act the way they do? How does the past affect the present?
Is there something more to it that is beyond the apparent?  Shute thought there was, but he was a tentative explorer. (He was also a churchgoer.) Did he pierce "the veil"?  My answer to that is maybe.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20187%20-%20Norwegian%20Wood%202.m4a" type="audio/x-m4a" length="19976192" />
<guid>http://mbird.com/podcastgen/media/Episode%20187%20-%20Norwegian%20Wood%202.m4a</guid>
<pubDate>Mon, 15 Jun 2015 20:41:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:20:30</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 186 - Dead End (My Friend)</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA['No' is the worst word you can ever hear.  (I realize the virtues of saying 'No', yourself, on certain occasions.  But when 'No' is said to you, especially at an impressionable age, it's the worst.)  This cast is about the
damage created by 'No', especially in romance.]]>
</description>
<itunes:subtitle>'No' is the worst word you can ever hear.  (I realize the virtues of saying 'No', yourself, on certain occasions.  But when 'No' is said to you, especially at an impressionable age, it's the worst.)  This cast is about the
damage created by 'No', especially in romance.</itunes:subtitle>
<itunes:summary>'No' is the worst word you can ever hear.  (I realize the virtues of saying 'No', yourself, on certain occasions.  But when 'No' is said to you, especially at an impressionable age, it's the worst.)  This cast is about the
damage created by 'No', especially in romance.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20186%20-%20Dead%20End%20(My%20Friend).m4a" type="audio/x-m4a" length="18538496" />
<guid>http://mbird.com/podcastgen/media/Episode%20186%20-%20Dead%20End%20(My%20Friend).m4a</guid>
<pubDate>Sun, 14 Jun 2015 20:41:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:19:01</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 185 - One Toke Over The Line (Sweet Mary)</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[What think ye when I say that 95% of what you are doing
is futile and meaningless?  Well, let's put it another way:
From the standpoint of the after-life, what you are doing is...
you fill in the blanks.
But you can still make it!
You've got to learn how to meditate, and learn how to throw a Crucifix.
Podcast 185 is dedicated to Mary C. Zahl.]]>
</description>
<itunes:subtitle>What think ye when I say that 95% of what you are doing
    is futile and meaningless?</itunes:subtitle>
<itunes:summary>What think ye when I say that 95% of what you are doing
    is futile and meaningless?</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20185%20-%20One%20Toke%20Over%20The%20Line%20(Sweet%20Mary).m4a" type="audio/x-m4a" length="24068096" />
<guid>http://mbird.com/podcastgen/media/Episode%20185%20-%20One%20Toke%20Over%20The%20Line%20(Sweet%20Mary).m4a</guid>
<pubDate>Wed, 11 Mar 2015 20:41:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:24:42</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 184 - Hysteria</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[In life you can be trapped by forces that are bigger than you are.  Especially in professional life.
It's possible to "wander in" -- or rather, bumble in -- to a situation in which you get used by somebody else
to accomplish a plan of theirs of which you yourself are (at the time) unaware.
Here is my homage to Jimmy Sangster movies.
In particular, behold Hysteria, a masterpiece of intrigue from 1965.
Watch out!  And take comfort, too]]>
</description>
<itunes:subtitle>In life you can be trapped by forces that are bigger than you are.  Especially in professional life. </itunes:subtitle>
<itunes:summary>In life you can be trapped by forces that are bigger than you are.  Especially in professional life. .</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20184%20-%20Hysteria.m4a" type="audio/x-m4a" length="23674980" />
<guid>http://mbird.com/podcastgen/media/Episode%20184%20-%20Hysteria.m4a</guid>
<pubDate>Sun, 8 Mar 2015 20:41:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:24:17</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 183 - Dr. Syn</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Oh, to encounter an integrated minister!
We all want to be integrated -- to be ourselves in the pulpit
and also out of it.
But it's tricky to pull off.
Pharisaical elements in the church -- usually one or two individuals
in the parish, who are present -- unconsciously -- in order to hide out themselves in some way or another -- can't long abide a minister who
is himself or herself.

Most of your listeners love it.
But there are one or two who, well, have an allergy.
(They are the ones that can get you every time.)

But then along comes someone like 'Mr. Tryan' in George Eliot's Scenes of Clerical Life.  He breaks the mold.

Or, somewhat spectacularly, Dr. Syn.  Dr. Syn, who was known in the movies as 'Dr. Bliss', is just about the most thoroughly integrated Anglican clergyman in history.  Could any of us be like him?  Dr. Syn is a brilliant swordsman, an agile swinger from church chandeliers, a powerful preacher,
a rousing music leader, a crafty smuggler, a loving father, a wily impeder of the taxation and revenue service, and a kindly pastor to his entire flock.  He is Robin Hood and 'Fletcher of Madeley' rolled into one.  Dr. Syn makes one wish to keep on going.

Hope you like him.  Maybe we can do a breakout in his honor at Mockingbird.  But everyone who comes will need to bring preaching bands, and
a phosphorescent mask.]]>
</description>
<itunes:subtitle>Oh, to encounter an integrated minister! We all want to be integrated -- to be ourselves in the pulpit and also out of it. But it's tricky to pull off.</itunes:subtitle>
<itunes:summary>Oh, to encounter an integrated minister! We all want to be integrated -- to be ourselves in the pulpit and also out of it. But it's tricky to pull off.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20183%20-%20Dr.%20Syn%202.m4a" type="audio/x-m4a" length="21761794" />
<guid>http://mbird.com/podcastgen/media/Episode%20183%20-%20Dr.%20Syn%202.m4a</guid>
<pubDate>Fri, 6 Mar 2015 20:41:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:22:20</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 181 - Dualism Clinic with James Bernard</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Come to find out, dualism has a limited but necessary role in resolving the human dilemma, i.e., in living.  The percentage is maybe 20% most of the time, but it's possibly 90% some of the time.  The English composer James Bernard is Exhibit A here, and a most brilliant exhibit his work has become.]]>
</description>
<itunes:subtitle>Come to find out, dualism has a limited but necessary role in resolving the human dilemma, i.e., in living.  The percentage is maybe 20% most of the time, but it's possibly 90% some of the time.  The English composer James Bernard is Exhibit A here, and a most brilliant exhibit his work has become.</itunes:subtitle>
<itunes:summary>Come to find out, dualism has a limited but necessary role in resolving the human dilemma, i.e., in living.  The percentage is maybe 20% most of the time, but it's possibly 90% some of the time.  The English composer James Bernard is Exhibit A here, and a most brilliant exhibit his work has become.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_181_-_dualism_clinic_with_james_bernard_2.m4a" length="21826302" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_181_-_dualism_clinic_with_james_bernard_2.m4a</guid>
<pubDate>Sun, 25 Jan 2015 20:41:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:22:24</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 180 - Metropolitan Life</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[]This is the tableau of a childhood memory,
a memory that came literally to life recently.
I entered a dream, but then the dream was real.
A little like the The Lion, the Witch and the Wardrobe but in reverse.
With help from Orpheus and, by way of backdraft,
the Warrens. ]]>
</description>
<itunes:subtitle>This is the tableau of a childhood memory, a memory that came literally to life recently. I entered a dream, but then the dream was real. A little like the The Lion, the Witch and the Wardrobe but in reverse. With help from Orpheus and, by way of backdraft, the Warrens.</itunes:subtitle>
<itunes:summary>This is the tableau of a childhood memory, a memory that came literally to life recently. I entered a dream, but then the dream was real. A little like the The Lion, the Witch and the Wardrobe but in reverse. With help from Orpheus and, by way of backdraft, the Warrens.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_180_-_metropolitan_life_2.m4a" length="22134345" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_180_-_metropolitan_life_2.m4a</guid>
<pubDate>Fri, 21 Nov 2014 20:41:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:22:43</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 179 - Ere the Winter Storms</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Why are so many unchanged, I mean fundamentally unchanged,
by the red lights of life?  What accounts for persons' resistance
to the lessons of catastrophe? This week Robert W. Anderson, not 'Sister Mary Ignatius', explains it all to us.]]>
</description>
<itunes:subtitle>Why are so many unchanged, I mean fundamentally unchanged, by the red lights of life?  What accounts for persons' resistance to the lessons of catastrophe? This week Robert W. Anderson, not 'Sister Mary Ignatius', explains it all to us.</itunes:subtitle>
<itunes:summary>Why are so many unchanged, I mean fundamentally unchanged, by the red lights of life?  What accounts for persons' resistance to the lessons of catastrophe? This week Robert W. Anderson, not 'Sister Mary Ignatius', explains it all to us.</itunes:summary>
<link>http://mbird.com/podcastgen/media/2015-02-13_episode_179_-_ere_the_winter_storms.m4a</link>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_179_-_ere_the_winter_storms.m4a" length="24274109" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_179_-_ere_the_winter_storms.m4a</guid>
<pubDate>Fri, 14 Nov 2014 20:41:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:24:54</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 178 - Without Which Not</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Things recently got so bad somewhere that it looks like all hope is gone.
The thing "imploded", like 'Susan' in The Buckinghams' otherwise cheery pop single.
Poor Susan!
Is there still hope?  PZ thinks there is.  But it comes from over the border!
And from the year 1917.]]>
</description>
<itunes:subtitle>Things recently got so bad somewhere that it looks like all hope is gone. The thing "imploded", like 'Susan' in The Buckinghams' otherwise cheery pop single. Poor Susan! Is there still hope?  PZ thinks there is.  But it comes from over the border! And from the year 1917.</itunes:subtitle>
<itunes:summary>Things recently got so bad somewhere that it looks like all hope is gone.
    The thing "imploded", like 'Susan' in The Buckinghams' otherwise cheery pop single.
    Poor Susan!
    Is there still hope?  PZ thinks there is.  But it comes from over the border!
    And from the year 1917.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_178_-_without_which_not.m4a" length="13740921" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_178_-_without_which_not.m4a</guid>
<pubDate>Wed, 29 Oct 2014 20:41:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:27:59</itunes:duration>
<itunes:keywords />
</item>

<item>
<title>Episode 177 - Whipped Cream</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Big explosions in Church!
Like at the beginning of "Cloverfield".
What do they mean?
Is there any hope in the aftermath?
Well, would I be recording this if I didn't think so,
from Lake Tahoe, as it turns out?
With help from Herb Alpert.
And Jane Austen.
This podcast is dedicated to Melina and Jacob Smith.]]>
</description>
<itunes:subtitle>Big explosions in Church! Like at the beginning of "Cloverfield". What do they mean? s there any hope in the aftermath?</itunes:subtitle>
<itunes:summary>Big explosions in Church!
    Like at the beginning of "Cloverfield".
    What do they mean?
    Is there any hope in the aftermath?
    Well, would I be recording this if I didn't think so,
    from Lake Tahoe, as it turns out?
    With help from Herb Alpert.
    And Jane Austen.
    This podcast is dedicated to Melina and Jacob Smith.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_podcast_177_-_whipped_cream.m4a" length="13906649" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_podcast_177_-_whipped_cream.m4a</guid>
<pubDate>Tue, 07 Oct 2014 20:41:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:28:20</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 176 - Everything Is Tuesday</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[August Coda.
Labor Day Coda.
General Johnson Coda.
Mergers Not Acquisitions Coda.
]]>
</description>
<itunes:subtitle>August Coda. Labor Day Coda. General Johnson Coda.  Mergers Not Acquisitions Coda.  </itunes:subtitle>
<itunes:summary>August Coda.
    Labor Day Coda.
    General Johnson Coda.
    Mergers Not Acquisitions Coda.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_176_-_everything_is_tuesday_2.m4a" length="10031360" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_176_-_everything_is_tuesday_2.m4a</guid>
<pubDate>Tue, 26 Aug 2014 20:41:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:20:12</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 175 - Does the Name Grimsby Do Anything to You?</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[An August summation,
from one explorer to hopefully others.
Rod Serling describes a unique case of one.
Then Armando Trovajoli puts into music the
secret of life.  Yes, the secret of life.]]>
</description>
<itunes:subtitle>A little August summation, from one explorer to hopefully others.  Rod Serling writes about an under-appreciated instance of one.  Then Armando Trovajoli delivers the secret of life.  The secret of life.</itunes:subtitle>
<itunes:summary>An August summation,
    from one explorer to hopefully others.
    Rod Serling describes a unique case of one.
    Then Armando Trovajoli puts into music the
    secret of life.  Yes, the secret of life.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_175_-_grimsby.m4a" length="14195632" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_175_-_grimsby.m4a</guid>
<pubDate>Tue, 26 Aug 2014 14:55:05 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:28:41</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 174 - Federal Theology in the Letters of Samuel Rutherford</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["So, here's the thing." :
Wanna know what faith is?
Listen to ABBA.
Wanna arrest the decline of,
oh, let's say,
mainstream Protestantism?
Listen to ABBA.
Wanna understand yourself?
Listen to ABBA.]]>
</description>
<itunes:subtitle>&quot;So, here&apos;s the thing&quot;: You wanna know about faith? Listen to ABBA. Wanna arrest the decline of, oh, let&apos;s say, mainstream Protestantism? Listen to ABBA.</itunes:subtitle>
<itunes:summary>&quot;So, here&apos;s the thing.&quot; :
    Wanna know what faith is?
    Listen to ABBA.
    Wanna arrest the decline of,
    oh, let&apos;s say,
    mainstream Protestantism?
    Listen to ABBA.
    Wanna understand yourself?
    Listen to ABBA.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_174_-_federal_theology_in_the_letters_of_samuel_rutherford.m4a" length="14365392" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_174_-_federal_theology_in_the_letters_of_samuel_rutherford.m4a</guid>
<pubDate>Fri, 22 Aug 2014 17:52:22 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:29:01</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 173 - And the Winner Is</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[There is so much truth here.
So much emotional truth, I mean.
It could have been someone else.
It could have been something else.
It could have come from somewhere else.
But it came from
ABBA.]]>
</description>
<itunes:subtitle>There is so much truth here.  Emotional truth, I mean.  It could have been somebody else.  It could have been something else.  But the truth was from ABBA.</itunes:subtitle>
<itunes:summary>There is so much truth here.
    So much emotional truth, I mean.
    It could have been someone else.
    It could have been something else.
    It could have come from somewhere else.
    But it came from
    ABBA.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_173_-_and_the_winner_is.m4a" length="13229200" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_173_-_and_the_winner_is.m4a</guid>
<pubDate>Thu, 21 Aug 2014 10:50:06 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:26:43</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 172 - Phony Wars</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[The subject is reality vs. ideology.  'Pet' Clark wanted to be Superwoman.
I wanted to be a totally focussed pastor, great dad, and good husband.
'Helen' wanted to be "a woman of today".  We all failed!   "Sorry, it's not possible" (Petula says).  And yet, a little child has led me.  Lower case.
But upper case, too.]]>
</description>
<itunes:subtitle>The subject is reality vs. ideology. &apos;Pet&apos; Clark wanted to be something, I wanted to be something, &apos;Helen&apos; wanted to be something.  We all failed.  &quot;Sorry, it&apos;s not possible&quot;. And yet, a little child led me -- lower case and upper case.</itunes:subtitle>
<itunes:summary>The subject is reality vs. ideology.  &apos;Pet&apos; Clark wanted to be Superwoman.
    I wanted to be a totally focussed pastor, great dad, and good husband.
    &apos;Helen&apos; wanted to be &quot;a woman of today&quot;.  We all failed!   &quot;Sorry, it&apos;s not possible&quot; (Petula says).  And yet, a little child has led me.  Lower case.
    But upper case, too.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_172_-_phony_wars.m4a" length="0" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_172_-_phony_wars.m4a</guid>
<pubDate>Wed, 20 Aug 2014 18:47:14 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:31:16</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 171 - If You Can&apos;t Stand the Heat</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Here is made a principled decision to opt out, of all manner of causes and notions.  With the injunction, however, that in order to heal, you have to feel.
Eric Clapton starts us out.  The House Band brings it on home.
]]>
</description>
<itunes:subtitle>In which is made a principled decision to OPT OUT, of all manner of causes and notions.  Yet, too, the injunction that in order to heal, you have to feel.  Eric Clapton starts us out.  The House Band brings it on home.</itunes:subtitle>
<itunes:summary>Here is made a principled decision to opt out, of all manner of causes and notions.  With the injunction, however, that in order to heal, you have to feel.
    Eric Clapton starts us out.  The House Band brings it on home.
</itunes:summary>
<enclosure url="http:/mbird.com/podcastgen/media/2015-02-13_episode_171_-_if_you_can%27t_stand_the_heat....m4a" length="18081568" type="audio/x-m4a"/>
<guid>http:/mbird.com/podcastgen/media/2015-02-13_episode_171_-_if_you_can%27t_stand_the_heat....m4a</guid>
<pubDate>Thu, 07 Aug 2014 14:21:38 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:36:36</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 170 - Farewell to the First Golden Era</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is a podcast to celebrate:  my 170th, in which are offered
some Summer reading, a Concluding Un-Scientific Postscript,
and the best track ever recorded by a certain Wonder.
Hope you like it!]]>
</description>
<itunes:subtitle>This is one to celebrate, the  170th, in which are offered some Summer reading, a concluding un-scientific postscript, and the best song ever recorded by a certain Wonder. </itunes:subtitle>
<itunes:summary>This is a podcast to celebrate:  my 170th, in which are offered
    some Summer reading, a Concluding Un-Scientific Postscript,
    and the best track ever recorded by a certain Wonder.
    Hope you like it!</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_170_-_back_towards_the_middle.m4a" length="12631088" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_170_-_back_towards_the_middle.m4a</guid>
<pubDate>Tue, 10 Jun 2014 10:10:20 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:25:29</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 169 - Wooden Ships</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is about Meister Eckhart and Rudolf Otto, and CS & N.
But it's really about whether and how to engage the world,
given what we now know about it.  Guess I'm  skeptical, more than ever;
and was surprised to have to dissent from the Master.
First time!]]>
</description>
<itunes:subtitle>This is about Meister Eckhart and Rudolf Otto, and CS &amp; N.  But it&apos;s REALLY about how and whether to engage the world -- given what we now know.  I&apos;m skeptical,  and was surprised to find myself dissenting from the Master. First time!</itunes:subtitle>
<itunes:summary>This is about Meister Eckhart and Rudolf Otto, and CS &amp; N.
    But it&apos;s really about whether and how to engage the world,
    given what we now know about it.  Guess I&apos;m  skeptical, more than ever;
    and was surprised to have to dissent from the Master.
    First time!</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_169_-_wooden_ships.m4a" length="20674992" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_169_-_wooden_ships.m4a</guid>
<pubDate>Fri, 23 May 2014 05:56:47 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:41:53</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 168 - &quot;Generation Zahl&quot;</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[A penetrating and courageous televison program from Germany opened me up recently.  It was an instance of what Stefan Kolditz, the writer of the program, called a "non-ideological access" to a tragedy.
But not just their tragedy.  My tragedy.  Yours, too, maybe.]]>
</description>
<itunes:subtitle>A penetrating and courageous  television program from Germany opened me up recently.  It was what Stefan Kolditz,  who wrote the screenplay,  called a &quot;non-ideological access&quot; to a tragedy.  Not just their tragedy.  My tragedy. Yours, too, maybe.</itunes:subtitle>
<itunes:summary>A penetrating and courageous televison program from Germany opened me up recently.  It was an instance of what Stefan Kolditz, the writer of the program, called a &quot;non-ideological access&quot; to a tragedy.
    But not just their tragedy.  My tragedy.  Yours, too, maybe.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_168_-__generation_zahl_.m4a" length="0" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_168_-__generation_zahl_.m4a</guid>
<pubDate>Sun, 18 May 2014 14:57:29 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:29:27</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 167 - Emotion</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is all about one thing.
It didn't take Melanchthon to teach me about it,
nor Thomas Cranmer.
No.
It took Burton Cummings to teach me about it.
And life!
So Stand Tall; and for God's sake, don't do something foolish.]]>
</description>
<itunes:subtitle>This is all about One Thing, and for me it&apos;s the core.  It didn&apos;t take Melanchthon to teach it to me, nor Thomas Cranmer.  No.  It took Burton Cummings to teach me.  And life.  So Stand Tall; and for God&apos;s sake, don&apos;t do something foolish.</itunes:subtitle>
<itunes:summary>This is all about one thing.
    It didn&apos;t take Melanchthon to teach me about it,
    nor Thomas Cranmer.
    No.
    It took Burton Cummings to teach me about it.
    And life!
    So Stand Tall; and for God&apos;s sake, don&apos;t do something foolish.</itunes:summary>
<link>http://mbird.com/podcastgen/media/2015-02-13_episode_167_-_emotion.m4a</link>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_167_-_emotion.m4a" length="14922704" type="audio/x-m4a"/>
<pubDate>Fri, 09 May 2014 12:51:53 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:10</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 166 - The House That Jack Built</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Well, the glass ceiling is finally breaking.
It's happening right before our eyes.
But Aretha's going to help us see the "Kehrseite".
With a little help from Lesley Gore, too.
"Come and see." (John 1:46)]]>
</description>
<itunes:subtitle>Well, the glass ceiling is finally breaking, and it&apos;s taking place right in front of our eyes.  But Aretha&apos;s going to help us see the &quot;Kehrseite&quot;, with a boost from wonderful Lesley Gore.  &quot;Come and See&quot; (John 1:46).</itunes:subtitle>
<itunes:summary>Well, the glass ceiling is finally breaking.
    It&apos;s happening right before our eyes.
    But Aretha&apos;s going to help us see the &quot;Kehrseite&quot;.
    With a little help from Lesley Gore, too.
    &quot;Come and see.&quot; (John 1:46)</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_166_-_the_house_that_jack_built.m4a" length="13649984" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_166_-_the_house_that_jack_built.m4a</guid>
<pubDate>Wed, 30 Apr 2014 17:40:56 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:27:34</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 165 - Cosmic Recension</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Meister Eckhart,
meet Burton Cummings.
And Randy Bachman.
And me.]]>
</description>
<itunes:subtitle>Meister Eckhart, meet Burton Cummings. And Randy Bachman. And me.</itunes:subtitle>
<itunes:summary>Meister Eckhart,
    meet Burton Cummings.
    And Randy Bachman.
    And me.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_165_-_cosmic_recension.m4a" length="11554944" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_165_-_cosmic_recension.m4a</guid>
<pubDate>Fri, 11 Apr 2014 15:39:58 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:23:18</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 164 - Happy Clappy</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["No use calling, 'cause the sky is falling,
and I'm getting pretty near the end."
This concerns the practical consequences of
(near-)death in life.  Join forces with Wolfman Jack (R.I.P.)
and The Guess Who; and 'Charlie Kane'.]]>
</description>
<itunes:subtitle>&quot;No use calling, &apos;cause the sky is falling, and I&apos;m getting pretty near the end.&quot;  This is in further explanation of (near-)death and its practical consequences for &quot;Everyday People&quot; (Sly &amp; co.).  Therefore...</itunes:subtitle>
<itunes:summary>&quot;No use calling, &apos;cause the sky is falling,
    and I&apos;m getting pretty near the end.&quot;
    This concerns the practical consequences of
    (near-)death in life.  Join forces with Wolfman Jack (R.I.P.)
    and The Guess Who; and &apos;Charlie Kane&apos;.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_164_-_happy_clappy.m4a" length="0" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_164_-_happy_clappy.m4a</guid>
<pubDate>Wed, 19 Feb 2014 10:05:11 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:34</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 163 -- Deetour</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[It's getting bigger.  Bigger, at least, from where I sit.
The Contraption, I mean.
And thank you, Karen Young!
And thank you, Mike Francis!
This podcast is dedicated to JAZ, the Minister of Edits.]]>
</description>
<itunes:subtitle>It&apos;s getting bigger.  Bigger, at least, from where I sit.  The Contraption, I mean. And thank you, Karen Young!  And thank you, Mike Francis!  Episode 163 is dedicated to the Minister of Edits.</itunes:subtitle>
<itunes:summary>It&apos;s getting bigger.  Bigger, at least, from where I sit.
    The Contraption, I mean.
    And thank you, Karen Young!
    And thank you, Mike Francis!
    This podcast is dedicated to JAZ, the Minister of Edits.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_163_--_deetour.m4a" length="0" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_163_--_deetour.m4a</guid>
<pubDate>Thu, 13 Feb 2014 10:43:47 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:32:50</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 162 - Rain Dance</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Thinking about Obamacare got me onto this one.
But it's not about Obamacare!
It's about Reality.
And Guess What?]]>
</description>
<itunes:subtitle>Thinking about Obamacare, the circular argument of the century, got me onto this.  But it&apos;s not about Obamacare!  It&apos;s about Reality.  And Guess What?</itunes:subtitle>
<itunes:summary>Thinking about Obamacare got me onto this one.
    But it&apos;s not about Obamacare!
    It&apos;s about Reality.
    And Guess What?</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_162_-_rain_dance.m4a" length="13819952" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_162_-_rain_dance.m4a</guid>
<pubDate>Wed, 05 Feb 2014 11:43:50 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:27:55</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 161 - PBS</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[That's Percy Bysshe Shelley,
who gets a little help -- as if he needed it -- from Eric Burdon,
and B.T.O, and John Harris Harper.
And MAY this meditation on termination not be half-baked.]]>
</description>
<itunes:subtitle>That&apos;s Percy Bysshe Shelley, with a little help from Eric Burdon, and from B.T.O., and from John Harris Harper. And MAY this meditation on termination not be half-baked.</itunes:subtitle>
<itunes:summary>That&apos;s Percy Bysshe Shelley,
    who gets a little help -- as if he needed it -- from Eric Burdon,
    and B.T.O, and John Harris Harper.
    And MAY this meditation on termination not be half-baked.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_161_-_pbs.m4a" length="0" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_161_-_pbs.m4a</guid>
<pubDate>Thu, 09 Jan 2014 10:38:57 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:51</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 160 - Who Is Going To Love Me?</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[How can we know God?
Where is God locatable?
With a little help from D. Warwick and a little from St. John,
I want to answer.
Podcast 160 is dedicated to Jono Linebaugh.]]>
</description>
<itunes:subtitle>How can we know God?  Where is God locatable?  With a little help from Dionne Warwick and a little from St. John, I want to answer.  Podcast 160 is dedicated to Jono Linebaugh.</itunes:subtitle>
<itunes:summary>How can we know God?
    Where is God locatable?
    With a little help from D. Warwick and a little from St. John,
    I want to answer.
    Podcast 160 is dedicated to Jono Linebaugh.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_160_-_blp_oil_spill.m4a" length="13490336" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_160_-_blp_oil_spill.m4a</guid>
<pubDate>Fri, 20 Dec 2013 08:14:41 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:43</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 159 - The Happiest Actual Life</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[It's really possible:
"the happiest actual life", I mean.
That was Booth Tarkington's phrase for the hope we could have
in real terms, even when circumstances went against us
and our intrinsic indelible nature went against us.
Case in point: his novel "Alice Adams" (1921).
Case in point: his character 'Alice Adams'.
I think the story is so real as to be Real.
]]>
</description>
<itunes:subtitle>It&apos;s really possible.  To have &quot;the happiest actual life&quot;, I mean.  The phrase is from Booth Tarkington, who portrays something like that life in his novel &quot;Alice Adams&quot; (1921).  </itunes:subtitle>
<itunes:summary>It&apos;s really possible:
    &quot;the happiest actual life&quot;, I mean.
    That was Booth Tarkington&apos;s phrase for the hope we could have
    in real terms, even when circumstances went against us
    and our intrinsic indelible nature went against us.
    Case in point: his novel &quot;Alice Adams&quot; (1921).
    Case in point: his character &apos;Alice Adams&apos;.
    I think the story is so real as to be Real.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_159_-_the_happiest_actual_life.m4a" length="0" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_159_-_the_happiest_actual_life.m4a</guid>
<pubDate>Sun, 17 Nov 2013 10:34:52 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:28:58</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 158 - Changing Social Conditions in Indianapolis</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Boy, do we need a miracle.
Such things really happen.
As in Booth Tarkington, and as in John Galsworthy.
As in me and you.
And as in: The Buckinghams.]]>
</description>
<itunes:subtitle>Boy, we need a miracle.  And such things really happen: as in Tarkington, as in Galsworthy.  As in me and you. And as in: The Buckinghams.</itunes:subtitle>
<itunes:summary>Boy, do we need a miracle.
    Such things really happen.
    As in Booth Tarkington, and as in John Galsworthy.
    As in me and you.
    And as in: The Buckinghams.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_158_-_changing_social_conditions_in_indianapolis.m4a" length="17032976" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_158_-_changing_social_conditions_in_indianapolis.m4a</guid>
<pubDate>Mon, 11 Nov 2013 08:01:57 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:34:28</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 157 - Every Mother&apos;s Son</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Taking a break now for a couple weeks, but wanted to leave a little white-pebble trail -- not of tears, but of hope.  "Come on down to my boat, baby"; and I'm talking about you, Miss Wyckoff; and you, Mr. Cardew; and you, Mr. Zahl.]]>
</description>
<itunes:subtitle>Taking a break now for a few weeks, but wanted to leave a white-pebble trail,  not of tears but of hope.  &quot;Come on down to my boat, baby&quot; -- and I&apos;m talking about you, Miss Wyckoff; and you, Mr. Rutherford; and you, Mr. Zahl.</itunes:subtitle>
<itunes:summary>Taking a break now for a couple weeks, but wanted to leave a little white-pebble trail -- not of tears, but of hope.  &quot;Come on down to my boat, baby&quot;; and I&apos;m talking about you, Miss Wyckoff; and you, Mr. Cardew; and you, Mr. Zahl.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_157_-_sing_a_simple_song.m4a" length="14612400" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_157_-_sing_a_simple_song.m4a</guid>
<pubDate>Sat, 19 Oct 2013 16:27:59 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:29:32</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 156 - I Am Curious (Orange)</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[A Protestant spin on a Golden Oldie from Sweden.
This is also a warning against categorization -- a very personal warning,
as I've suffered from categorization and feel it keenly still.
"Och du?"
]]>
</description>
<itunes:subtitle>A Protestant spin on a Copper Oldie, and a warning, a a very personal warning, against categorization.</itunes:subtitle>
<itunes:summary>A Protestant spin on a Golden Oldie from Sweden.
    This is also a warning against categorization -- a very personal warning,
    as I&apos;ve suffered from categorization and feel it keenly still.
    &quot;Och du?&quot;
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_156_-_i_am_curious_(orange)_2.m4a" length="13042080" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_156_-_i_am_curious_(orange)_2.m4a</guid>
<pubDate>Wed, 16 Oct 2013 16:55:48 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:26:20</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 155 - Mandy</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Alternate title: Mandy and the Episcopals.
Irving Berlin sets the stage;
Sandra Dee plays the lead,
together with Troy Donohue;
and James Gould Cozzens,
like Sister Mary Ignatius,
Explains It All for You.
]]>
</description>
<itunes:subtitle>Alternate title: Mandy and The Episcopals.  Irving Berlin sets the stage, Sandra Dee plays the lead (with Troy Donohue), and James Gould Cozzens, like Sister Mary Ignatius, explains it all for you.</itunes:subtitle>
<itunes:summary>Alternate title: Mandy and the Episcopals.
    Irving Berlin sets the stage;
    Sandra Dee plays the lead,
    together with Troy Donohue;
    and James Gould Cozzens,
    like Sister Mary Ignatius,
    Explains It All for You.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_155_-_miss_o'dell.m4a" length="19367904" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_155_-_miss_o'dell.m4a</guid>
<pubDate>Sun, 06 Oct 2013 13:53:38 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:37:50</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 154 - Kramer</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Kramer is my word for transmitted family dis-function and disease.
Kramer in this sense requires acute attention.
With help from The Contraption, Kramer actually can be reduced.
In this podcast, Richard Egan steps up to help us,
with a little help from Faith.
Percy, I mean.]]>
</description>
<itunes:subtitle>Kramer is my word for transmitted family dis-function and disease.  It needs acute attention.  With the help of The Contraption, Kramer can be reduced.  Richard Egan steps in to help us here.</itunes:subtitle>
<itunes:summary>Kramer is my word for transmitted family dis-function and disease.
    Kramer in this sense requires acute attention.
    With help from The Contraption, Kramer actually can be reduced.
    In this podcast, Richard Egan steps up to help us,
    with a little help from Faith.
    Percy, I mean.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20154%20-%20Kramer.m4a" type="audio/x-m4a" length="14930096" />
<guid>http://mbird.com/podcastgen/media/Episode%20154%20-%20Kramer.m4a</guid>
<pubDate>Wed, 02 Oct 2013 11:21:50 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:11</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 153 - Love in the 40s</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[When you're 'mature', you're sometimes not.
I learned this in my 40s.
I first learned it in a parish, in 'Cheever country'.
But it was also in 'Miami Vice', every Friday night.
Valerie and Tubbs taught me,
as did 'Sonny' and Theresa.
And Jan Hammer.
There was all this dread, too.
Was it a dream?]]>
</description>
<itunes:subtitle>When you&apos;re &apos;mature&apos;,  you&apos;re sometimes not mature.  I learned this in my 40s.  Partly I was taught by &quot;Miami Vice&quot; and Jan Hammer.  Life felt SO serious, like Tubbs and Valerie, and Crockett and Theresa.  So full of dread. Or not.</itunes:subtitle>
<itunes:summary>When you&apos;re &apos;mature&apos;, you&apos;re sometimes not.
    I learned this in my 40s.
    I first learned it in a parish, in &apos;Cheever country&apos;.
    But it was also in &apos;Miami Vice&apos;, every Friday night.
    Valerie and Tubbs taught me,
    as did &apos;Sonny&apos; and Theresa.
    And Jan Hammer.
    There was all this dread, too.
    Was it a dream?</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20153%20-%20Love%20in%20the%2040s%202.m4a" type="audio/x-m4a" length="15070880" />
<guid>http://mbird.com/podcastgen/media/Episode%20153%20-%20Love%20in%20the%2040s%202.m4a</guid>
<pubDate>Mon, 30 Sep 2013 15:12:53 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:28</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 152 - Groovy Kind of Love</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[The text is Isherwood's journal entry for August 3, 1967.
The topic:
How to grow in love for the people who are right around you.
Lesley Gore is going to help us, plus, naturally, William Hale White;
plus Gerald Heard; plus Wayne Fontana.]]>
</description>
<itunes:subtitle>The text is Isherwood&apos;s diary entry for August 3, 1967.  The topic: How can you grow in love for the people around you?  Lesley Gore is going to help us, plus William Hale White, as usual; plus Gerald Heard; plus, of course, Wayne Fontana.</itunes:subtitle>
<itunes:summary>The text is Isherwood&apos;s journal entry for August 3, 1967.
    The topic:
    How to grow in love for the people who are right around you.
    Lesley Gore is going to help us, plus, naturally, William Hale White;
    plus Gerald Heard; plus Wayne Fontana.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_152_-_increase_of_affection.m4a" length="15805792" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_152_-_increase_of_affection.m4a</guid>
<pubDate>Sun, 29 Sep 2013 12:03:23 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:31:58</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 151 - Girl Talk</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[I've just written a book.
It is called "PZ's Panopticon:
An Off-the-Wall Guide to World Religiion".
It's not about gender differences nor does it concern ideology.
It looks at the religions of the world in terms of one question:
What does this or that religion have to offer a dying person?
My book concerns religion for a person in extremis.
Dying seems to "concentrate the mind wonderfully" (Samuel Johnson).
I think it serves a most concentrating purpose in helping a person
sift through the wisdom of religion.
Oh, and by religion, I also mean religions that are not called religions,
such as celebrity, sex, things, one's children, one's life-partner,
one's ideology, and the power that you have and exercise in your life.
Power is a big religion.
Religion covers almost anything that habitually or functionally is
worshipped.
This podcast is dedicated to Ray Ortlund.]]>
</description>
<itunes:subtitle>I&apos;ve just written a book.  It&apos;s not about gender differences.  Nor is it about ideology.  It is about near death and dying; and what the world religions have to offer a person in extremis.  This podcast id dedicated to Ray Ortlund.</itunes:subtitle>
<itunes:summary>I&apos;ve just written a book.
    It is called &quot;PZ&apos;s Panopticon:
    An Off-the-Wall Guide to World Religiion&quot;.
    It&apos;s not about gender differences nor does it concern ideology.
    It looks at the religions of the world in terms of one question:
    What does this or that religion have to offer a dying person?
    My book concerns religion for a person in extremis.
    Dying seems to &quot;concentrate the mind wonderfully&quot; (Samuel Johnson).
    I think it serves a most concentrating purpose in helping a person
    sift through the wisdom of religion.
    Oh, and by religion, I also mean religions that are not called religions,
    such as celebrity, sex, things, one&apos;s children, one&apos;s life-partner,
    one&apos;s ideology, and the power that you have and exercise in your life.
    Power is a big religion.
    Religion covers almost anything that habitually or functionally is
    worshipped.
    This podcast is dedicated to Ray Ortlund.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_151_-_girl_talk.m4a" length="17234672" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_151_-_girl_talk.m4a</guid>
<pubDate>Fri, 27 Sep 2013 12:52:13 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:34:52</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 150 - Early Roman Kings</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is about the Ancient Romans:
their psychic position, their spirit-world of augury,
and the effect of the birth of Christ.
With help from Bob Dylan.
Two corrections, too:
The Thornton Wilder book is "The Woman of Andros",
and 'Camulodunum' was the Roman name for Colchester.
]]>
</description>
<itunes:subtitle>This is about the Ancient Romans: their psychic position, their reliance on augury, and the coming of Christ.  With help from Bob Dylan.  Two corrections, too: the Wilder book is &quot;The Woman of Andros&quot;; and &apos;Camulodunum&apos; was the Roman name for Colchester.</itunes:subtitle>
<itunes:summary>This is about the Ancient Romans:
    their psychic position, their spirit-world of augury,
    and the effect of the birth of Christ.
    With help from Bob Dylan.
    Two corrections, too:
    The Thornton Wilder book is &quot;The Woman of Andros&quot;,
    and &apos;Camulodunum&apos; was the Roman name for Colchester.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_150_-_the_ancient_romans.m4a" length="24851504" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_150_-_the_ancient_romans.m4a</guid>
<pubDate>Wed, 28 Aug 2013 12:44:34 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:50:24</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 149 - A Heartache, A Shadow, A Lifetime</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is a reflection on 45 years of New Testament scholarship.
That's 45 years in 45 minutes -- one minute for every year.
And Dave Mason puts it all in perspective.]]>
</description>
<itunes:subtitle>This is my reflection on 45 years of New Testament scholarship.  Forty-five years in 45 minutes.  That&apos;s one minute for every year. </itunes:subtitle>
<itunes:summary>This is a reflection on 45 years of New Testament scholarship.
    That&apos;s 45 years in 45 minutes -- one minute for every year.
    And Dave Mason puts it all in perspective.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_149_-_a_heartache,_a_shadow,_a_lifetime.m4a" length="23270384" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_149_-_a_heartache,_a_shadow,_a_lifetime.m4a</guid>
<pubDate>Sun, 25 Aug 2013 09:05:22 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:47:10</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 148 - INGSOC</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["A little trick with Dick" (The Name Game):
This is about language, control, and Purr-FEC
tion.  With thanks to Eric Blair, too.]]>
</description>
<itunes:subtitle>&quot;A little trick with Dick&quot; (The Name Game).  This is about language, control, and Purr-FECtion.</itunes:subtitle>
<itunes:summary>&quot;A little trick with Dick&quot; (The Name Game):
    This is about language, control, and Purr-FEC
    tion.  With thanks to Eric Blair, too.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_148_-_ingsoc.m4a" length="13898304" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_148_-_ingsoc.m4a</guid>
<pubDate>Sun, 04 Aug 2013 11:16:43 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:28:04</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 147 - Transcendence</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[What can you do when you're face to face with The Antagonist?
I'll tell you this much: no one gets out of here alive.
Unless there are Martians.

This podcast is about suffering, and it's also about transcendence.]]>
</description>
<itunes:subtitle>What can you do when you&apos;re face to face with The Antagonist?  Well, I&apos;ll tell you this much: no one gets out of here alive.  Unless, however, there are Martians.  This talk is about suffering and it&apos;s about transcendence.</itunes:subtitle>
<itunes:summary>What can you do when you&apos;re face to face with The Antagonist?
    I&apos;ll tell you this much: no one gets out of here alive.
    Unless there are Martians.

    This podcast is about suffering, and it&apos;s also about transcendence.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20147%20-%20Transcendence.m4a" length="0" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/Episode%20147%20-%20Transcendence.m4a</guid>
<pubDate>Sat, 03 Aug 2013 14:52:53 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:22:25</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 146 - Sermon for the Feast Day of Hey Jude</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[It's about nervous breakdowns -- maybe your 19th.
It's about George's Way with us.
And it's about the music.]]>
</description>
<itunes:subtitle>It&apos;s about nervous breakdowns -- maybe your 19th.  And George&apos;s Way with us.</itunes:subtitle>
<itunes:summary>It&apos;s about nervous breakdowns -- maybe your 19th.
    It&apos;s about George&apos;s Way with us.
    And it&apos;s about the music.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_146_-_sermon_for_the_feast_day_of_hey_jude.m4a" length="12258048" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_146_-_sermon_for_the_feast_day_of_hey_jude.m4a</guid>
<pubDate>Fri, 28 Jun 2013 10:25:13 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:24:44</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 145 - Soul Coaxing</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[What's really important?
"Soul Coaxing" is really important.
But not the practice.
The song!
By Raymond Lefevre and his Orchestra.
THAT's really important.
Gosh, I hope you like this.
]]>
</description>
<itunes:subtitle>What&apos;s really important?  I think &quot;Soul Coaxing&quot; is really important.  But not the  practice.  The song!  By Raymond Lefevre and his Orchestra.  Gosh, I hope you like this.</itunes:subtitle>
<itunes:summary>What&apos;s really important?
    &quot;Soul Coaxing&quot; is really important.
    But not the practice.
    The song!
    By Raymond Lefevre and his Orchestra.
    THAT&apos;s really important.
    Gosh, I hope you like this.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_145_-_soul_coaxing_3.m4a" length="10454832" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_145_-_soul_coaxing_3.m4a</guid>
<pubDate>Mon, 24 Jun 2013 09:57:19 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:21:03</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 144 - Good Luck, Miss Wyckoff</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Here we journey to the outer limits of compassion.
Will that suffice?
Or do we need a little help from our friends --
like Jeff Beck, maybe.]]>
</description>
<itunes:subtitle>Here we journey to the outer limits of compassion.  Will that suffice?  Or do need a little help from our friends -- like Jeff Beck, maybe.</itunes:subtitle>
<itunes:summary>Here we journey to the outer limits of compassion.
    Will that suffice?
    Or do we need a little help from our friends --
    like Jeff Beck, maybe.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_144_-_good_luck,_miss_wyckoff.m4a" length="15235792" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_144_-_good_luck,_miss_wyckoff.m4a</guid>
<pubDate>Sat, 22 Jun 2013 16:10:41 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:48</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 143 - Old Man River</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[And now a word from our sponsor -- George!]]>
</description>
<itunes:subtitle>And now a word from our sponsor -- George!</itunes:subtitle>
<itunes:summary>And now a word from our sponsor -- George!</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_143_-_old_man_river_2.m4a" length="11307968" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_143_-_old_man_river_2.m4a</guid>
<pubDate>Thu, 16 May 2013 04:31:51 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:22:48</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 142 - Girl Can&apos;t Help It</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[In which I talk about George, my new hero.]]>
</description>
<itunes:subtitle>In which I talk about George, my new hero.</itunes:subtitle>
<itunes:summary>In which I talk about George, my new hero.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_142_-_girl_can't_help_it.m4a" length="16420160" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_142_-_girl_can't_help_it.m4a</guid>
<pubDate>Wed, 15 May 2013 15:03:43 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:13</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 141 - Easter with Los Straitjackets</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Here's the Gospel as I would put it this Easter.
It's never not been the Have Mercy on Me (Cannonball Adderley/
The Buckinghams) of God in relation to the Outta Gear (Los Straitjackets) of us.  But it needs to not become a mental exercise.
It needs to be hooked into us, the whole being of our compounded selves.
If it's not making the connection, then it will fail.
(And it often does.)]]>
</description>
<itunes:subtitle>Here&apos;s the Gospel as I see it this Easter.  It&apos;s never not the Have Mercy on Me (Cannonball Adderley) of God in relation to the Outta Gear (Los Straitjackets) of us. But it&apos;s gotta stop being mental, and start being, well, hooked in.</itunes:subtitle>
<itunes:summary>Here&apos;s the Gospel as I would put it this Easter.
    It&apos;s never not been the Have Mercy on Me (Cannonball Adderley/
    The Buckinghams) of God in relation to the Outta Gear (Los Straitjackets) of us.  But it needs to not become a mental exercise.
    It needs to be hooked into us, the whole being of our compounded selves.
    If it&apos;s not making the connection, then it will fail.
    (And it often does.)</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_141_-_outta_gear.m4a" length="14726512" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_141_-_outta_gear.m4a</guid>
<pubDate>Thu, 21 Mar 2013 11:21:12 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:29:46</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 140 - Make It Easy on Yourself</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is a meditation on self-forgiveness.
I used to think that was a lame phrase,
an exercise in twaddle.
Not so!
Here we have The Walker Brothers,
Los Straitjackets, even Frankie (Goes to Hollywood).
The Lesson This Morning is from Isherwood's journal entry of
July 14, 1940, which is to say,
the Second of the Two Great Commandments.]]>
</description>
<itunes:subtitle>A meditation on self-forgiveness.  Used to think that was a lame phrase, an exercise in twaddle.  Not so!</itunes:subtitle>
<itunes:summary>This is a meditation on self-forgiveness.
    I used to think that was a lame phrase,
    an exercise in twaddle.
    Not so!
    Here we have The Walker Brothers,
    Los Straitjackets, even Frankie (Goes to Hollywood).
    The Lesson This Morning is from Isherwood&apos;s journal entry of
    July 14, 1940, which is to say,
    the Second of the Two Great Commandments.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_140_-_make_it_easy_on_yourself.m4a" length="16524768" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_140_-_make_it_easy_on_yourself.m4a</guid>
<pubDate>Sun, 17 Mar 2013 09:57:19 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:25</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 139 - Journey with Boo (Me and You)</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[It's here:  that surgical song by Lobo, the balladeer's portrait of an ordinary, heart-rending tragedy.  Because the picture's true to life, however, there may be room for hope.
Roll up for a magical mystery tour, -- with a Dog Named Boo.]]>
</description>
<itunes:subtitle>It&apos;s here, that surgical song by Lobo, the artist&apos;s sympathetic portrait of a common heartfelt situation.  Because it&apos;s true to life, however, there may be some hope.  Roll up, for a magical mystery tour, with a Dog Named Boo.</itunes:subtitle>
<itunes:summary>It&apos;s here:  that surgical song by Lobo, the balladeer&apos;s portrait of an ordinary, heart-rending tragedy.  Because the picture&apos;s true to life, however, there may be room for hope.
    Roll up for a magical mystery tour, -- with a Dog Named Boo.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20139%20-%20Journey%20with%20Boo%2C%20Me%20and%20You.m4a" length="0" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/Episode%20139%20-%20Journey%20with%20Boo%2C%20Me%20and%20You.m4a</guid>
<pubDate>Fri, 15 Feb 2013 13:31:28 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:31:17</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 138 - Lobo&apos;s Dating Tips for Christian Guys</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[He has much to teach us!
This podcast, for me, is Camp.]]>
</description>
<itunes:subtitle>He has much to teach us!  This podcast, for me, is Camp.</itunes:subtitle>
<itunes:summary>He has much to teach us!
    This podcast, for me, is Camp.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_138_-_lobo's_dating_tips_for_christian_guys.m4a" length="10944880" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_138_-_lobo's_dating_tips_for_christian_guys.m4a</guid>
<pubDate>Thu, 14 Feb 2013 11:01:28 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:22:03</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 137 - Hero of the War</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[A short exegesis of personal pacifism.
Scott Walker's song "Hero of the War" made me do it!
Oh, and it's John Lennon in "Oh! What a Lovely War".
That's a correction.]]>
</description>
<itunes:subtitle>A short exegesis concerning personal pacifism.  Scott Walker&apos;s song &quot;Hero of the War&quot; MADE me do it!  Oh, and it&apos;s John Lennon in &quot;Oh! What a Lovely War&quot;.  That&apos;s a correction.</itunes:subtitle>
<itunes:summary>A short exegesis of personal pacifism.
    Scott Walker&apos;s song &quot;Hero of the War&quot; made me do it!
    Oh, and it&apos;s John Lennon in &quot;Oh! What a Lovely War&quot;.
    That&apos;s a correction.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_137_-_hero_of_the_war.m4a" length="11651648" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_137_-_hero_of_the_war.m4a</guid>
<pubDate>Sat, 02 Feb 2013 12:38:17 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:23:30</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 136 - Peaches La Verne</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[The La Verne Seminar, which took place in the Summer of 1941,
is the second most desired destination for PZ the Time Traveler.
If only one could have been there.  It was the ultimate religious retreat!

But still, I think I'd choose, for first place, if I had to choose,
a trip to Universal Studios during the Great Depression,
to witness the filming of that most desired of all works of cinema art:
The Bride of Frankenstein.]]>
</description>
<itunes:subtitle>The La Verne Seminar, which took place in the Summer of 1941, is the second most important destination for PZ the Time Traveler.  Here&apos;s why.  It was a credible, persuasive religious retreat.  If only one had been able to be there!</itunes:subtitle>
<itunes:summary>The La Verne Seminar, which took place in the Summer of 1941,
    is the second most desired destination for PZ the Time Traveler.
    If only one could have been there.  It was the ultimate religious retreat!

    But still, I think I&apos;d choose, for first place, if I had to choose,
    a trip to Universal Studios during the Great Depression,
    to witness the filming of that most desired of all works of cinema art:
    The Bride of Frankenstein.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20136%20-%20Peaches%20La%20Verne.m4a" length="0" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/Episode%20136%20-%20Peaches%20La%20Verne.m4a</guid>
<pubDate>Thu, 31 Jan 2013 10:13:08 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:28:38</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 135 - Elevator</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[That's Where the Happy People Go!
Here is "a new way of talking, a new way of walking" --
about praying, about grace, about One Love and the
Underground River.
Jerry Lewis (but you won't like this) has a walk-on, too.]]>
</description>
<itunes:subtitle>That&apos;s Where the Happy People Go!</itunes:subtitle>
<itunes:summary>That&apos;s Where the Happy People Go!
    Here is &quot;a new way of talking, a new way of walking&quot; --
    about praying, about grace, about One Love and the
    Underground River.
    Jerry Lewis (but you won&apos;t like this) has a walk-on, too.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_135_-_elevator.m4a" length="17380016" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_135_-_elevator.m4a</guid>
<pubDate>Wed, 30 Jan 2013 11:36:52 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:35:10</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 134 - Pillar of Salt</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[The music! --
evoking Lot's wife and then the Lord's words to St. Peter.
I guess I think it's more and more about the music.
But let's here it for the Haiku,
tu.]]>
</description>
<itunes:subtitle>The music -- evoking Lot&apos;s wife, and then the Lord&apos;s words to Saint Peter.  I sometimes think it&apos;s all about the music.  </itunes:subtitle>
<itunes:summary>The music! --
    evoking Lot&apos;s wife and then the Lord&apos;s words to St. Peter.
    I guess I think it&apos;s more and more about the music.
    But let&apos;s here it for the Haiku,
    tu.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20134%20-%20Pillar%20of%20Salt.m4a" length="0" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/Episode%20134%20-%20Pillar%20of%20Salt.m4a</guid>
<pubDate>Thu, 24 Jan 2013 10:45:23 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:21:24</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 133 - Brandy Station</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is not a case of "interpretive signage"  !
You'll have to make up your mind on your own.
But Looking Glass will be there to help you,
followed by, close by, Scott W.]]>
</description>
<itunes:subtitle>This is not a case of &quot;interpretive signage&quot;! You&apos;ll have to make up your own mind. But Looking Glass is going to help you, together with, coming right behind, Scott W.</itunes:subtitle>
<itunes:summary>This is not a case of &quot;interpretive signage&quot;  !
    You&apos;ll have to make up your mind on your own.
    But Looking Glass will be there to help you,
    followed by, close by, Scott W.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20133%20-%20Brandy%20Station.m4a" length="0" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/Episode%20133%20-%20Brandy%20Station.m4a</guid>
<pubDate>Thu, 24 Jan 2013 07:54:05 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:19</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 132 - Love in the First Degree</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is about forging forward
in the spiritual life.
Let Bananarama lead the way!]]>
</description>
<itunes:subtitle>This is about forging forward in the spiritual life.  Let Bananarama lead the way!</itunes:subtitle>
<itunes:summary>This is about forging forward
    in the spiritual life.
    Let Bananarama lead the way!</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_132_-_love_in_the_first_degree_2.m4a" length="13757072" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_132_-_love_in_the_first_degree_2.m4a</guid>
<pubDate>Wed, 16 Jan 2013 11:16:37 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:27:47</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 131 - 52 Pickup</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Here is a thought for the end of the year.
And Merry Christmas to all!]]>
</description>
<itunes:subtitle>Here is a thought for the end of the year.  Merry Christmas to all!</itunes:subtitle>
<itunes:summary>Here is a thought for the end of the year.
    And Merry Christmas to all!</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20131%20-%2052%20Pickup.m4a" type="audio/x-m4a" length="12892832" />
<guid>http://mbird.com/podcastgen/media/Episode%20131%20-%2052%20Pickup.m4a</guid>
<pubDate>Wed, 19 Dec 2012 11:20:43 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:26:01</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Podcast 130 - OK, All Right! - Victor Hugo</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Had to do this one.
Victor Hugo is great.
"Victor Hugo" the Phenomenon seems like another turn
of Journey's "Wheel".  (Listen and you'll find out why.)
Nevertheless, I had fun doing this and hope you like it.
Karen Carpenter (R.I.P.) helped me. Mr. Leitch, too.]]>
</description>
<itunes:subtitle>I had to do it, tho&apos; didn&apos;t want to.  Victor Hugo is great; &quot;Victor Hugo&quot; the Phenomenon seems like just another turn of Journey&apos;s &quot;Wheel in the Sky&quot;.  Even so, I had fun with this and hope you like it.</itunes:subtitle>
<itunes:summary>Had to do this one.
    Victor Hugo is great.
    &quot;Victor Hugo&quot; the Phenomenon seems like another turn
    of Journey&apos;s &quot;Wheel&quot;.  (Listen and you&apos;ll find out why.)
    Nevertheless, I had fun doing this and hope you like it.
    Karen Carpenter (R.I.P.) helped me. Mr. Leitch, too.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%20130%20-%20OK%2C%20All%20Right%21%20-%20Victor%20Hugo.m4a" type="audio/x-m4a" length="14876240" />
<guid>http://mbird.com/podcastgen/media/Podcast%20130%20-%20OK%2C%20All%20Right%21%20-%20Victor%20Hugo.m4a</guid>
<pubDate>Fri, 14 Dec 2012 08:41:32 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:04</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 129 - First Infinite Frost</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is an experiment.
It's a true story, from the true-life adventures,
tho' I truly wish it never happened.
Is PZ trying for a James Agee moment?
Maybe so.
Podcast 129 is dedicated to Adrienne Parks.]]>
</description>
<itunes:subtitle>An experiment: a true story, from the true-life adventures, but told backwards -- the way it felt at the time.  I truly wish this had never happened.  Is PZ trying for a James Agee moment?  Maybe so.  Podcast 129 is dedicated to Adrienne Parks.</itunes:subtitle>
<itunes:summary>This is an experiment.
    It&apos;s a true story, from the true-life adventures,
    tho&apos; I truly wish it never happened.
    Is PZ trying for a James Agee moment?
    Maybe so.
    Podcast 129 is dedicated to Adrienne Parks.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_129_-_first_infinite_frost_2.m4a" length="12177152" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_129_-_first_infinite_frost_2.m4a</guid>
<pubDate>Wed, 12 Dec 2012 19:33:05 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:24:34</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 128 - Dissociated Chef d&apos;Oeuvre</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This podcast is not just about another movie, the 1973 musical version of "Lost Horizon".  It's about Reflections of yourself, the divine Approach when "I Come to You", and the Things I Will Not Miss.  The movie's an incongruous knockout.  This is because it's about Life.]]>
</description>
<itunes:subtitle>This is not just about another movie, the 1973 musical version of &quot;Lost Horizon&quot;.  It&apos;s about Reflections of yourself, the Approach mirrored in the song &quot;I Come to You&quot;, and the Things I Will Not Miss.  The movie&apos;s a knockout because it&apos;s about Real Life.</itunes:subtitle>
<itunes:summary>This podcast is not just about another movie, the 1973 musical version of &quot;Lost Horizon&quot;.  It&apos;s about Reflections of yourself, the divine Approach when &quot;I Come to You&quot;, and the Things I Will Not Miss.  The movie&apos;s an incongruous knockout.  This is because it&apos;s about Life.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_128_-_dissociated_chef_d'oeuvre_2.m4a" length="12681680" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_128_-_dissociated_chef_d'oeuvre_2.m4a</guid>
<pubDate>Tue, 11 Dec 2012 08:07:32 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:25:36</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 127 - Hotel Taft</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Look within yourself, look inside the Black Cauldron.
If you take the time to Drag the Line, you'll almost definitely
find your hope, even joy.  Let the bells ring, and let's
Listen to the Music.]]>
</description>
<itunes:subtitle>Look within yourself,  look inside the Black Cauldron.  If you take the time to Drag the Line, you&apos;ll almost definitely find hope, even joy.  Let the bells ring and Listen to the Music.!</itunes:subtitle>
<itunes:summary>Look within yourself, look inside the Black Cauldron.
    If you take the time to Drag the Line, you&apos;ll almost definitely
    find your hope, even joy.  Let the bells ring, and let&apos;s
    Listen to the Music.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_127_-_hotel_taft.m4a" length="16247856" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_127_-_hotel_taft.m4a</guid>
<pubDate>Sun, 09 Dec 2012 10:57:03 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:32:52</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Podcast 126 - Amberley Wildbrooks</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Suffering, Transitoriness, and Insubstantiality:
three marks of being that seem beyond dispute,
at least from the perspective of experience.  To be sure, the last,
insubstantiality, takes some unpacking.
Podcast 126 drinks some Matthew's Southern Comfort, and
makes common cause with The Peanut Butter Conspiracy.]]>
</description>
<itunes:subtitle>Suffering, Transitoriness, Insubstantiality: three marks of being that seem to me beyond dispute.  The last takes a little unpacking, which I try to do.  Podcast 126 draws on Matthew&apos;s Southern Comfort, and The Peanut Butter Conspiracy.</itunes:subtitle>
<itunes:summary>Suffering, Transitoriness, and Insubstantiality:
    three marks of being that seem beyond dispute,
    at least from the perspective of experience.  To be sure, the last,
    insubstantiality, takes some unpacking.
    Podcast 126 drinks some Matthew&apos;s Southern Comfort, and
    makes common cause with The Peanut Butter Conspiracy.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%20126%20-%20Amberley%20Wildbrooks.m4a" type="audio/x-m4a" length="16322752" />
<guid>http://mbird.com/podcastgen/media/Podcast%20126%20-%20Amberley%20Wildbrooks.m4a</guid>
<pubDate>Wed, 05 Dec 2012 10:14:15 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:01</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 125 - Now What?</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[In the spirit of the J. Geils Band, 'Sinuhe the Egyptian' spent his entire life looking for it.  A proto-hippie, an inspired near-mad man (not across the water), gave Sinuhe the answer.  The result was elation, and courage, and even creation.  And for me.  And for  you?]]>
</description>
<itunes:subtitle>In the spirit of the J. Geils Band, Sinuhe the Egyptian looked for it.  A proto-hippie Pharaoh gave it to him.  The result was a good result.  And for you and me!</itunes:subtitle>
<itunes:summary>In the spirit of the J. Geils Band, &apos;Sinuhe the Egyptian&apos; spent his entire life looking for it.  A proto-hippie, an inspired near-mad man (not across the water), gave Sinuhe the answer.  The result was elation, and courage, and even creation.  And for me.  And for  you?</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20125%20-%20Now%20What_.m4a" length="0" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/Episode%20125%20-%20Now%20What_.m4a</guid>
<pubDate>Tue, 04 Dec 2012 13:22:22 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:26</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 124 - Done</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Here's a Sixth Sense!
Galsworthy sheds light -- but where did it come from? -- and
jump-starts us "Going Up The Country".
]]>
</description>
<itunes:subtitle>Here&apos;s a Sixth Sense!  Galsworthy enlightens in the brightest way, and jump-starts us &quot;Going Up The Country.&quot;</itunes:subtitle>
<itunes:summary>Here&apos;s a Sixth Sense!
    Galsworthy sheds light -- but where did it come from? -- and
    jump-starts us &quot;Going Up The Country&quot;.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_124_-_done.m4a" length="14495424" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_124_-_done.m4a</guid>
<pubDate>Thu, 29 Nov 2012 09:54:18 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:29:17</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 123 - Saint&apos;s Progress</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[John Galsworthy's play "A Bit O'Love" (1915) and his novel "Saint's Progress" (1919) diagnose the problem and also the possibility inherent in parish ministry, and especially within parish clergy.  Galsworthy gives  his readers a shattering exercise but also a hopeful one.
So we just want to say: Goodbye, Columbus !]]>
</description>
<itunes:subtitle>John Galsworthy&apos;s play &quot;A Bit O&apos;Love&quot; (1915) and his novel &quot;Saints&apos;s Progress&quot; (1919) diagnose the problem and also the possibility of Christian ministry. They diagnose it  to the point of heartbreak.  And yet there is hope.  Goodbye, Columbus!</itunes:subtitle>
<itunes:summary>John Galsworthy&apos;s play &quot;A Bit O&apos;Love&quot; (1915) and his novel &quot;Saint&apos;s Progress&quot; (1919) diagnose the problem and also the possibility inherent in parish ministry, and especially within parish clergy.  Galsworthy gives  his readers a shattering exercise but also a hopeful one.
    So we just want to say: Goodbye, Columbus !</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20123%20-%20Saint%27s%20Progress.m4a" length="0" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/Episode%20123%20-%20Saint%27s%20Progress.m4a</guid>
<pubDate>Wed, 28 Nov 2012 08:13:44 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:37:05</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 122 -- Worst That Could Happen</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[It's being labelled a "Zwinglian"!
And there's something even worse than that.
This podcast is a plea for the wheels to be put back on
religion.]]>
</description>
<itunes:subtitle>It&apos;s being labelled a &quot;Zwinglian&quot;!  And there&apos;s something even worse than that.  This cast is a plea for the wheels to be put back on religion.</itunes:subtitle>
<itunes:summary>It&apos;s being labelled a &quot;Zwinglian&quot;!
    And there&apos;s something even worse than that.
    This podcast is a plea for the wheels to be put back on
    religion.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_122_--_worst_that_could_happen.m4a" length="16869296" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_122_--_worst_that_could_happen.m4a</guid>
<pubDate>Fri, 09 Nov 2012 12:28:21 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:34:08</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 121 - Hold That Ghost</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Freedom and Love:
Love can't exist from anything but, and
Freedom can't result in anything but.
This cast wants to consult St. Augustine, concerning human nature;
and Bud Abbott and Lou Costello, concerning
intangibles.
Maxim Gorky makes an appearance, too.
I hope you'll like what he says.]]>
</description>
<itunes:subtitle>Freedom and Love: Love can&apos;t exist from anything but, and Freedom won&apos;t issue in anything but.  This cast consults St. Augustine, on human nature; and Bud Abbott and Lou Costello, on, well, intangibles. Maxim Gorky makes an appearance.</itunes:subtitle>
<itunes:summary>Freedom and Love:
    Love can&apos;t exist from anything but, and
    Freedom can&apos;t result in anything but.
    This cast wants to consult St. Augustine, concerning human nature;
    and Bud Abbott and Lou Costello, concerning
    intangibles.
    Maxim Gorky makes an appearance, too.
    I hope you&apos;ll like what he says.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_121_-_hold_that_ghost.m4a" length="17507072" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_121_-_hold_that_ghost.m4a</guid>
<pubDate>Wed, 07 Nov 2012 09:46:49 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:35:26</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 120 - The Black Castle</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Here's a short talk about creativity, renewal, "work stoppage", and a couple of terrific movies.  It's also a lesson in How to Empty a Room!]]>
</description>
<itunes:subtitle>Here&apos;s a short talk about creativity, &quot;work stoppage&quot;, renewal, and a couple of wonderful movies.  Also, it&apos;s a lesson in how to empty a room!</itunes:subtitle>
<itunes:summary>Here&apos;s a short talk about creativity, renewal, &quot;work stoppage&quot;, and a couple of terrific movies.  It&apos;s also a lesson in How to Empty a Room!</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20120%20-%20The%20Black%20Castle.m4a" type="audio/x-m4a" length="13979488" />
<guid>http://mbird.com/podcastgen/media/Episode%20120%20-%20The%20Black%20Castle.m4a</guid>
<pubDate>Fri, 02 Nov 2012 11:22:30 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:28:14</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Podcast 119 - Over the River II</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Did I get across?
Well, maybe "a toe on the road", to quote a religious psychologist I know.
This cast gets some help from a steel guitar, and also from the Rev. James Cleveland.  Will the world "hear from me again" (Fu Manchu)? Dunno.]]>
</description>
<itunes:subtitle>Did I get across?  Well maybe a &quot;toe on the road&quot;, to quote a religious psychologist I know.  This comes to you with a little help from a steel guitar, and also from James Cleveland.  Will the world &quot;hear from me again&quot; (Fu Manchu)? Dunno.</itunes:subtitle>
<itunes:summary>Did I get across?
    Well, maybe &quot;a toe on the road&quot;, to quote a religious psychologist I know.
    This cast gets some help from a steel guitar, and also from the Rev. James Cleveland.  Will the world &quot;hear from me again&quot; (Fu Manchu)? Dunno.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20119%20-%20Over%20the%20River%20II%202.m4a" length="13511920" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/Episode%20119%20-%20Over%20the%20River%20II%202.m4a</guid>
<pubDate>Sun, 16 Sep 2012 20:32:31 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:27:42</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 119 - Over the River I</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["Trouble in my way" is the name of the game.  This podcast tells the story of how it came to me, and what it forced me to learn.  Episode 119 of PZ's Podcast is a two part swan song.]]>
</description>
<itunes:subtitle>&quot;Trouble in my way&quot; is the name of the game.  This podcast tells how it came to me, and what I was forced to learn from it.  This is part one of a two-part &quot;swan song&quot;.</itunes:subtitle>
<itunes:summary>&quot;Trouble in my way&quot; is the name of the game.  This podcast tells the story of how it came to me, and what it forced me to learn.  Episode 119 of PZ&apos;s Podcast is a two part swan song.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20119%20-%20Over%20the%20River%20II%202.m4a" type="audio/x-m4a" length="13511920" />
<guid>http://mbird.com/podcastgen/media/Episode%20119%20-%20Over%20the%20River%20II%202.m4a</guid>
<pubDate>Sun, 16 Sep 2012 19:36:25 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:27:17</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 118 - Les Elucubrations de PZ</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is intended to be the opposite of a rant.
Even if  I wanted to, I could not come a thousand light years close to
Antoine's great one, which once so delighed the French.
What I can try to  give you instead  is a little reading list, plus a little movie, a profound one, even a study in scarlet.
]]>
</description>
<itunes:subtitle>Not a rant, like Antoine&apos;s great one, but maybe some &quot;Lightworks&quot;: a current reading list and even a movie, all in red.  </itunes:subtitle>
<itunes:summary>This is intended to be the opposite of a rant.
    Even if  I wanted to, I could not come a thousand light years close to
    Antoine&apos;s great one, which once so delighed the French.
    What I can try to  give you instead  is a little reading list, plus a little movie, a profound one, even a study in scarlet.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20118%20-%20Les%20Elucubrations%20de%20PZ.m4a" type="audio/x-m4a" length="15348128" />
<guid>http://mbird.com/podcastgen/media/Episode%20118%20-%20Les%20Elucubrations%20de%20PZ.m4a</guid>
<pubDate>Thu, 13 Sep 2012 12:57:28 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:31:02</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 117 - Horror Hotel</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This tight  expressionist outing is a study in egos prepared to take any measures in order to prolong (ego-) life.  It's a sure fail, but most instructive.  Then there's the fog, and the blocking of the characters in the fog.  It endures in the memory.
There is also an outstanding note of psychotronic Episcopal haberdashery and service schedules.  'Mr. Russell' is a wonderful minister!]]>
</description>
<itunes:subtitle>This tight expressionist movie is the real thing, about real (occult) egos willing to take any measures possible in order to prolong life.  It&apos;s a sure fail, this prolongation. But so revealing.  And the fog ...  and the blocking. </itunes:subtitle>
<itunes:summary>This tight  expressionist outing is a study in egos prepared to take any measures in order to prolong (ego-) life.  It&apos;s a sure fail, but most instructive.  Then there&apos;s the fog, and the blocking of the characters in the fog.  It endures in the memory.
    There is also an outstanding note of psychotronic Episcopal haberdashery and service schedules.  &apos;Mr. Russell&apos; is a wonderful minister!</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_117_-_horror_hotel.m4a" length="14104880" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_117_-_horror_hotel.m4a</guid>
<pubDate>Tue, 11 Sep 2012 16:00:06 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:28:30</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 116 - Wing Thing</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Another meditation on hope (i.e., the Wing Thing), via death; yet
death concretely and in the now, death you can get your skull around
today and not tomorrow.
Akira Ifukube is here to help undress us, as is Diogenes the Cynic, and Ludger Tom Ring; and, wouldn't you know, Raymond Scott.
Podcast 116 is dedicated to Hewes Hull.]]>
</description>
<itunes:subtitle>Another meditation on hope (i.e., the Wing Thing) via death, but concrete death: death you can get your skull around.  Akira Ifukube is here to help us, as is Diogenes the Cynic; as is, again, Raymond Scott.  The cast is dedicated to Hewes Hull.</itunes:subtitle>
<itunes:summary>Another meditation on hope (i.e., the Wing Thing), via death; yet
    death concretely and in the now, death you can get your skull around
    today and not tomorrow.
    Akira Ifukube is here to help undress us, as is Diogenes the Cynic, and Ludger Tom Ring; and, wouldn&apos;t you know, Raymond Scott.
    Podcast 116 is dedicated to Hewes Hull.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_116_-_wing_thing.m4a" length="11144992" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_116_-_wing_thing.m4a</guid>
<pubDate>Mon, 27 Aug 2012 23:34:51 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:22:28</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 115 - In the event of</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["What makes the melon ball bounce?"
What makes you bounce?
This is an undressed talk about death,
and death's funny aftermath.]]>
</description>
<itunes:subtitle>&quot;What makes the melon ball bounce?&quot;  What makes you bounce?  This is an undressed talk about death, real death;  and its funny aftermath.</itunes:subtitle>
<itunes:summary>&quot;What makes the melon ball bounce?&quot;
    What makes you bounce?
    This is an undressed talk about death,
    and death&apos;s funny aftermath.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_115_-_in_the_event_of.m4a" length="14945376" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_115_-_in_the_event_of.m4a</guid>
<pubDate>Sat, 25 Aug 2012 11:21:04 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:12</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 114 - A Slight Shiver</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Sequel to "Return to Form",
with a push from Serling and a lift from Dylan.]]>
</description>
<itunes:subtitle>Sequel to &quot;Return to Form&quot;, with a push from Serling and a lift from Dylan.  </itunes:subtitle>
<itunes:summary>Sequel to &quot;Return to Form&quot;,
    with a push from Serling and a lift from Dylan.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_114_-_a_slight_shiver.m4a" length="15216992" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_114_-_a_slight_shiver.m4a</guid>
<pubDate>Mon, 13 Aug 2012 10:00:03 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:46</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 113 - Return to Form</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is podcast one in a new "story arc" --
a study in defeatedness, and a new hope
I strangely feel.
You could call it cross-notes of a
theological psychologist.]]>
</description>
<itunes:subtitle>This is podcast one in a new &quot;story arc&quot; -- a study in defeatedness, and a new hope I strangely feel.  You could call it cross-notes of a theological psychologist.</itunes:subtitle>
<itunes:summary>This is podcast one in a new &quot;story arc&quot; --
    a study in defeatedness, and a new hope
    I strangely feel.
    You could call it cross-notes of a
    theological psychologist.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_113_-_return_to_form.m4a" length="13843104" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_113_-_return_to_form.m4a</guid>
<pubDate>Sat, 11 Aug 2012 15:58:52 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:27:58</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 113 - The Two Geralds</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Gerald Fried (b. 1928) and Gerald Heard (d. 1971): both were communicators of the non-rational, both were exponents of the subterranean echo.  Fried did it through B-movie (and other) musical scores; Heard, through mystery novels and fantastic short stories.  Jesus did it, too, through similes and parables.  (If the second Gerald chose for his "nom-de-plume" 'H.F. Heard', I wonder what name Christ would have chosen, had His  stories been published.)]]>
</description>
<itunes:subtitle>Gerald Fried (b.1928) and Gerald Heard (d. 1971): both were communicators of the non-rational, both exponents of the subterranean echo.  Fried did it through B-movie (and other) musical scores; Heard, through fantastic mysteries.  Jesus did it, too.</itunes:subtitle>
<itunes:summary>Gerald Fried (b. 1928) and Gerald Heard (d. 1971): both were communicators of the non-rational, both were exponents of the subterranean echo.  Fried did it through B-movie (and other) musical scores; Heard, through mystery novels and fantastic short stories.  Jesus did it, too, through similes and parables.  (If the second Gerald chose for his &quot;nom-de-plume&quot; &apos;H.F. Heard&apos;, I wonder what name Christ would have chosen, had His  stories been published.)</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20113%20-%20The%20Two%20Geralds.m4a" length="0" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/Episode%20113%20-%20The%20Two%20Geralds.m4a</guid>
<pubDate>Thu, 28 Jun 2012 10:56:19 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:25</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 112 - Kipling&apos;s Lightworks</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Kipling shed light!
From "Recessional" to "Children's Song",
this podcast sings his praise.
Kipling was also a 'both-and' thinker,
a rare eirenic gift, and
a Gift for Today.
Episode 112 is dedicated to Stuart Gerson.]]>
</description>
<itunes:subtitle>Kipling shed Light!  From &quot;Recessional&quot; to &quot;Children&apos;s Song&quot;, this podcast sings his praise.  He was also a &apos;both-and&apos; thinker, a rare eirenic gift.  Episode 112 is dedicated to Stuart Gerson.</itunes:subtitle>
<itunes:summary>Kipling shed light!
    From &quot;Recessional&quot; to &quot;Children&apos;s Song&quot;,
    this podcast sings his praise.
    Kipling was also a &apos;both-and&apos; thinker,
    a rare eirenic gift, and
    a Gift for Today.
    Episode 112 is dedicated to Stuart Gerson.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_112_-_kipling's_lightworks.m4a" length="19797712" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_112_-_kipling's_lightworks.m4a</guid>
<pubDate>Thu, 21 Jun 2012 11:28:31 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:40:06</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 110 - Color Him Father</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[John Betjeman listed five masters of the English ghost story, or supernatural tale.  All five of them were the sons of Protestant ministers.
What was going on with these sons, and their fathers.
'The Winstons' can tell us the answer,
in their 1969 45 that we loved so,
we sons of our fathers.]]>
</description>
<itunes:subtitle>John Betjeman listed five masters of  the English ghost story, or supernatural tale.  Each of them was the son of a Protestant minister.  What was going on with these sons and their fathers?  Let &apos;The Winstons&apos; , from 1969, fill out the picture.</itunes:subtitle>
<itunes:summary>John Betjeman listed five masters of the English ghost story, or supernatural tale.  All five of them were the sons of Protestant ministers.
    What was going on with these sons, and their fathers.
    &apos;The Winstons&apos; can tell us the answer,
    in their 1969 45 that we loved so,
    we sons of our fathers.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_110_-_color_him_father.m4a" length="16223936" type="audio/x-m4a"/>
<guid>http://mbird.com/podcastgen/media/2015-02-13_episode_110_-_color_him_father.m4a</guid>
<pubDate>Mon, 04 Jun 2012 14:41:42 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:32:49</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 108 - J.C. Ryle Considered</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Bishop Ryle made at least three big mistakes during his long ministry.
If he were able to speak now -- he died in 1900 -- I believe he would admit them.  To me they are revealing mistakes, from which there is something to learn.
J.C. Ryle also had a core strength:
He had been saved in his youth, when his world fell apart.
He was a Christian, in other words, for the right reason.
Yet like many spiritual people, there were still
"unevangelized dark continents" inside him.
Had these been "colonized" by the great Word that saved the young man, Ryle might have avoided the mistakes he made as an older man.
]]>
</description>
<itunes:subtitle />
<itunes:summary>Bishop Ryle made at least three big mistakes during his long ministry.
    If he were able to speak now -- he died in 1900 -- I believe he would admit them.  To me they are revealing mistakes, from which there is something to learn.
    J.C. Ryle also had a core strength:
    He had been saved in his youth, when his world fell apart.
    He was a Christian, in other words, for the right reason.
    Yet like many spiritual people, there were still
    &quot;unevangelized dark continents&quot; inside him.
    Had these been &quot;colonized&quot; by the great Word that saved the young man, Ryle might have avoided the mistakes he made as an older man.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20108%20-%20J.C.%20Ryle%20Considered.m4a" type="audio/x-m4a" length="13833392" />
<guid>http://mbird.com/podcastgen/media/Episode%20108%20-%20J.C.%20Ryle%20Considered.m4a</guid>
<pubDate>Fri, 25 May 2012 12:00:21 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:27:56</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 107 - Bishop Ryle</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[John Charles Ryle, who lived from l816 to 1900,
was "a giant of a man with the heart of a child".
He was a Christian warrior in the Church of England,
who contended against High Churchmen and Liberals
for 60 years, concluding his ministry as the first Bishop of Liverpool.
J.C. Ryle  is a fascinating character, a hero-type with some
interesting weaknesses.
This podcast tells the story of his life.
It is dedicated to my friend Fred Rogers.]]>
</description>
<itunes:subtitle />
<itunes:summary>John Charles Ryle, who lived from l816 to 1900,
    was &quot;a giant of a man with the heart of a child&quot;.
    He was a Christian warrior in the Church of England,
    who contended against High Churchmen and Liberals
    for 60 years, concluding his ministry as the first Bishop of Liverpool.
    J.C. Ryle  is a fascinating character, a hero-type with some
    interesting weaknesses.
    This podcast tells the story of his life.
    It is dedicated to my friend Fred Rogers.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20107%20-%20Bishop%20Ryle.m4a" type="audio/x-m4a" length="17419968" />
<guid>http://mbird.com/podcastgen/media/Episode%20107%20-%20Bishop%20Ryle.m4a</guid>
<pubDate>Fri, 25 May 2012 11:49:48 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:35:15</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 106 - Requiem</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Alternate Title:
I Feel Like I Lose When I Win.
Also, there's a correction:
It was 'Fraulein Doktor', not the actress who played her (Suzy Kendall),
who died young, at age 52, in 1940.]]>
</description>
<itunes:subtitle />
<itunes:summary>Alternate Title:
    I Feel Like I Lose When I Win.
    Also, there&apos;s a correction:
    It was &apos;Fraulein Doktor&apos;, not the actress who played her (Suzy Kendall),
    who died young, at age 52, in 1940.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20106%20-%20I%20Feel%20Like%20I%20Lose%20When%20I%20Win.m4a" type="audio/x-m4a" length="13565152" />
<guid>http://mbird.com/podcastgen/media/Episode%20106%20-%20I%20Feel%20Like%20I%20Lose%20When%20I%20Win.m4a</guid>
<pubDate>Mon, 21 May 2012 08:36:18 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:27:24</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 104 - What does it take (to win your love)?</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[A meditation on defense:
that's what this is.
Someone wrote that the inner being of a human being
is "covered by thirty or forty skins or hides, like an ox's or a bear's,
so thick and hard".
Too true!
What's to get through?
Is there an antipode, a blessed antipode,
to such a coverage from hope?
I honestly think there is.
(Even if you've only got a toe on the Road.)]]>
</description>
<itunes:subtitle />
<itunes:summary>A meditation on defense:
    that&apos;s what this is.
    Someone wrote that the inner being of a human being
    is &quot;covered by thirty or forty skins or hides, like an ox&apos;s or a bear&apos;s,
    so thick and hard&quot;.
    Too true!
    What&apos;s to get through?
    Is there an antipode, a blessed antipode,
    to such a coverage from hope?
    I honestly think there is.
    (Even if you&apos;ve only got a toe on the Road.)</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20104%20-%20What%20does%20it%20take%20%28to%20win%20your%20love%29_.m4a" type="audio/x-m4a" length="14416032" />
<guid>http://mbird.com/podcastgen/media/Episode%20104%20-%20What%20does%20it%20take%20%28to%20win%20your%20love%29_.m4a</guid>
<pubDate>Wed, 16 May 2012 09:30:45 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:29:08</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 103 - Flowers for Algernon II</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[How does the ego actually die?
Or rather, what does a person look like when their ego has died,
or is dying?
Can we see this -- the "seed falling into the ground"?
Algernon Blackwood wrote about the dying.
He wrote about it vividly and concretely, not just symbolically.
This podcast quotes from two of Blackwood's "Eternity" stories:
"The Centaur" (1911) and "A Descent into Egypt" (1914).
The theme is healing, at the end of the day;
and even,
priesthood.]]>
</description>
<itunes:subtitle />
<itunes:summary>How does the ego actually die?
    Or rather, what does a person look like when their ego has died,
    or is dying?
    Can we see this -- the &quot;seed falling into the ground&quot;?
    Algernon Blackwood wrote about the dying.
    He wrote about it vividly and concretely, not just symbolically.
    This podcast quotes from two of Blackwood&apos;s &quot;Eternity&quot; stories:
    &quot;The Centaur&quot; (1911) and &quot;A Descent into Egypt&quot; (1914).
    The theme is healing, at the end of the day;
    and even,
    priesthood.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20103%20-%20Flowers%20for%20Algernon%20II.m4a" type="audio/x-m4a" length="17781360" />
<guid>http://mbird.com/podcastgen/media/Episode%20103%20-%20Flowers%20for%20Algernon%20II.m4a</guid>
<pubDate>Tue, 24 Apr 2012 17:06:10 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:35:59</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 102 - Flowers for Algernon I</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Algernon Blackwood (1869-1951) knew a lot.
In reaction to his Sandemanian childhood, he still remained a religious person, all his life.
In his "weird tales" Blackwood tried to map a religious way
forward -- through an inspired imagination.
I used to put Arthur Machen at the top of the list
of writers of supernatural horror.
Because of a change in me, Blackwood is now number one.
This podcast, together with Episode 103, which comes next,
follows directly from "Eternity".]]>
</description>
<itunes:subtitle />
<itunes:summary>Algernon Blackwood (1869-1951) knew a lot.
    In reaction to his Sandemanian childhood, he still remained a religious person, all his life.
    In his &quot;weird tales&quot; Blackwood tried to map a religious way
    forward -- through an inspired imagination.
    I used to put Arthur Machen at the top of the list
    of writers of supernatural horror.
    Because of a change in me, Blackwood is now number one.
    This podcast, together with Episode 103, which comes next,
    follows directly from &quot;Eternity&quot;.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20102%20-%20Flowers%20for%20Algernon%20I.m4a" type="audio/x-m4a" length="18815632" />
<guid>http://mbird.com/podcastgen/media/Episode%20102%20-%20Flowers%20for%20Algernon%20I.m4a</guid>
<pubDate>Tue, 24 Apr 2012 16:54:42 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:38:06</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 100 - Eternity</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[What dies when we die, and what continues to live?
What should we fear in relation to physical death,
and what can we affirm?
Philip Larkin gives a little assist here,
but so does St. Francis.
This is Episode 100.]]>
</description>
<itunes:subtitle />
<itunes:summary>What dies when we die, and what continues to live?
    What should we fear in relation to physical death,
    and what can we affirm?
    Philip Larkin gives a little assist here,
    but so does St. Francis.
    This is Episode 100.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20100%20-%20Eternity%202.m4a" type="audio/x-m4a" length="17477424" />
<guid>http://mbird.com/podcastgen/media/Episode%20100%20-%20Eternity%202.m4a</guid>
<pubDate>Sat, 14 Apr 2012 09:44:03 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:35:22</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 101 - I feel like I win when I lose</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Between "Waterloo" and "Lay all your love on me",
I don't see how you could achieve a purer pop moment.
Or just a purer moment period!
The insight within these two 45s is communicated to perfection.
What is that insight?
Well, two things:
first, "all I've learned has overturned" (note the 'Euro' English).
I thought I knew myself.  Then LUV came knocking,
and "everything is new and everything is you."
That's the way people really are.
Second, "now it seems my only chance is giving up the fight...
I feel like I win when I lose."
This is good religion's word to the ego.
I feel like I win when I lose.
This is also the 101st (Airborne) Podcast.
]]>
</description>
<itunes:subtitle />
<itunes:summary>Between &quot;Waterloo&quot; and &quot;Lay all your love on me&quot;,
    I don&apos;t see how you could achieve a purer pop moment.
    Or just a purer moment period!
    The insight within these two 45s is communicated to perfection.
    What is that insight?
    Well, two things:
    first, &quot;all I&apos;ve learned has overturned&quot; (note the &apos;Euro&apos; English).
    I thought I knew myself.  Then LUV came knocking,
    and &quot;everything is new and everything is you.&quot;
    That&apos;s the way people really are.
    Second, &quot;now it seems my only chance is giving up the fight...
    I feel like I win when I lose.&quot;
    This is good religion&apos;s word to the ego.
    I feel like I win when I lose.
    This is also the 101st (Airborne) Podcast.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%20101%20-%20I%20feel%20like%20I%20win%20when%20I%20lose.m4a" type="audio/x-m4a" length="17748832" />
<guid>http://mbird.com/podcastgen/media/Episode%20101%20-%20I%20feel%20like%20I%20win%20when%20I%20lose.m4a</guid>
<pubDate>Thu, 12 Apr 2012 09:21:45 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:35:55</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Previously Unreleased: Heinz</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Heinz Burt, known as "Heinz", the Wild Boy of Pop, was, you could say,
Joe Meek's muse.
Meek did everything possible to make his "Heinz" into a star.
Although Meek failed to do that,
he produced a large body of fabulous music around his Golden Child.
This podcast, previously unreleased, deals with the alchemy of imputation; the theme of unrequited love and consequent melancholy in much of the gold that Meek created out of Heinz; and with the proximity, to almost all of us, of mental illness.
There are two factual mistakes in the cast:
The town of Eastleigh is in Hampshire, not "New Hampshire";
and the song connected to the movie "Circus of Horrors" was sung by Garry Mills.  It is entitled "Look for a Star".]]>
</description>
<itunes:subtitle />
<itunes:summary>Heinz Burt, known as &quot;Heinz&quot;, the Wild Boy of Pop, was, you could say,
    Joe Meek&apos;s muse.
    Meek did everything possible to make his &quot;Heinz&quot; into a star.
    Although Meek failed to do that,
    he produced a large body of fabulous music around his Golden Child.
    This podcast, previously unreleased, deals with the alchemy of imputation; the theme of unrequited love and consequent melancholy in much of the gold that Meek created out of Heinz; and with the proximity, to almost all of us, of mental illness.
    There are two factual mistakes in the cast:
    The town of Eastleigh is in Hampshire, not &quot;New Hampshire&quot;;
    and the song connected to the movie &quot;Circus of Horrors&quot; was sung by Garry Mills.  It is entitled &quot;Look for a Star&quot;.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%2017.m4a" type="audio/x-m4a" length="15093424" />
<guid>http://mbird.com/podcastgen/media/Podcast%2017.m4a</guid>
<pubDate>Tue, 10 Apr 2012 09:47:01 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:30</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Previously Unreleased: Joe Meek</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["The Nazareth Principle" (Simeon Zahl) and Joe Meek:
they're synonymous.
Joe Meek was an improbable genius, who Hear(d) a New World.
His wondrous work, achieved under conditions so unusual as to make the mind boggle, is a pure example of Christ's being labelled
by the question, "Can anything good come out of Nazareth?"
This podcast lay languishing in the vaults,
mainly because there are two mistakes in it:
the speaker confuses the guitarist Jimmy Page with the guitarist
Ritchie Blackmore; and,
'Screaming Lord Sutch', with 'Lord Buckethead'.
Other than that, he's satisfied with it.
Moreover, he believes in what he said.]]>
</description>
<itunes:subtitle />
<itunes:summary>&quot;The Nazareth Principle&quot; (Simeon Zahl) and Joe Meek:
    they&apos;re synonymous.
    Joe Meek was an improbable genius, who Hear(d) a New World.
    His wondrous work, achieved under conditions so unusual as to make the mind boggle, is a pure example of Christ&apos;s being labelled
    by the question, &quot;Can anything good come out of Nazareth?&quot;
    This podcast lay languishing in the vaults,
    mainly because there are two mistakes in it:
    the speaker confuses the guitarist Jimmy Page with the guitarist
    Ritchie Blackmore; and,
    &apos;Screaming Lord Sutch&apos;, with &apos;Lord Buckethead&apos;.
    Other than that, he&apos;s satisfied with it.
    Moreover, he believes in what he said.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%2012.m4a" type="audio/x-m4a" length="24078784" />
<guid>http://mbird.com/podcastgen/media/Podcast%2012.m4a</guid>
<pubDate>Fri, 23 Mar 2012 06:28:49 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:48:49</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 99 9/10 - Twisterella</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[When reality comes crashing in to call,
you've got to be prepared for a re-think.
It's what happens to 'Billy Liar', in another dazzling English rose,
the movie "Billy Liar" from 1963.
It's based on a novel then a play,
but the visuals bring it home.
A man of 19, who flees from his life, for his life, into a fantasy world,
begins to falter, then crumble, in the face of reality.
(O Lucky Man! -- at age 19, to begin to see.)
Like the English city in which he lives,
in which every building seems to be being bulldozed
in service of urban renewal,
'Billy Fisher' -- Billy Liar -- is watching "everything go".
"Not one stone" (of his plummeting life) "will be left on stone".
There's help, however, in the form of a girl,
a precious girl,
who is able to care and not care.
She's the hope!
She knows something Billy doesn't, and few do.
Can she save our phantastic hero?
Could she save you?
Listen to "Twisterella".
Or rather, see "Billy Liar", and SEE.]]>
</description>
<itunes:subtitle />
<itunes:summary>When reality comes crashing in to call,
    you&apos;ve got to be prepared for a re-think.
    It&apos;s what happens to &apos;Billy Liar&apos;, in another dazzling English rose,
    the movie &quot;Billy Liar&quot; from 1963.
    It&apos;s based on a novel then a play,
    but the visuals bring it home.
    A man of 19, who flees from his life, for his life, into a fantasy world,
    begins to falter, then crumble, in the face of reality.
    (O Lucky Man! -- at age 19, to begin to see.)
    Like the English city in which he lives,
    in which every building seems to be being bulldozed
    in service of urban renewal,
    &apos;Billy Fisher&apos; -- Billy Liar -- is watching &quot;everything go&quot;.
    &quot;Not one stone&quot; (of his plummeting life) &quot;will be left on stone&quot;.
    There&apos;s help, however, in the form of a girl,
    a precious girl,
    who is able to care and not care.
    She&apos;s the hope!
    She knows something Billy doesn&apos;t, and few do.
    Can she save our phantastic hero?
    Could she save you?
    Listen to &quot;Twisterella&quot;.
    Or rather, see &quot;Billy Liar&quot;, and SEE.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2099%20whatever%20-%20Twisterella%202.m4a" type="audio/x-m4a" length="16007744" />
<guid>http://mbird.com/podcastgen/media/Episode%2099%20whatever%20-%20Twisterella%202.m4a</guid>
<pubDate>Fri, 16 Mar 2012 23:05:29 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:32:22</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 99 5/8 - A Kind of Loving</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This podcast is about categorization -- the pitfalls of categorization.
With people, with friends (and prospective friends), with husbands and wives (and prospective husbands and wives), with everybody.
It's also about possession -- the pitfalls of possession.
Especially with people you love.
My surface subject is a 1962 movie entitled "A Kind of Loving": an English rose.  But the real subject is putting life into categories,
and love into objects.
Note the new intro, too.
It's got 45 RPM crackling noises.

]]>
</description>
<itunes:subtitle />
<itunes:summary>This podcast is about categorization -- the pitfalls of categorization.
    With people, with friends (and prospective friends), with husbands and wives (and prospective husbands and wives), with everybody.
    It&apos;s also about possession -- the pitfalls of possession.
    Especially with people you love.
    My surface subject is a 1962 movie entitled &quot;A Kind of Loving&quot;: an English rose.  But the real subject is putting life into categories,
    and love into objects.
    Note the new intro, too.
    It&apos;s got 45 RPM crackling noises.

</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2099%205_8%20-%20A%20Kind%20of%20Loving.m4a" type="audio/x-m4a" length="13532592" />
<guid>http://mbird.com/podcastgen/media/Episode%2099%205_8%20-%20A%20Kind%20of%20Loving.m4a</guid>
<pubDate>Tue, 13 Mar 2012 12:30:12 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:27:20</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 99 -- A Night at the Bardo</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Harpo's Night at the Bardo --
but not Harpo's, actually.
It was mine:.
It was PZ's Night at the Bardo.
From dusk till dawn.
This is something that actually happened.
I saw my own death,
or rather, myself dying, on a reclining chair in an airplane,
on March 1, 2012.
It was an unpleasant, elucidating experience.
It rattled me!
Let me tell you all about it.]]>
</description>
<itunes:subtitle />
<itunes:summary>Harpo&apos;s Night at the Bardo --
    but not Harpo&apos;s, actually.
    It was mine:.
    It was PZ&apos;s Night at the Bardo.
    From dusk till dawn.
    This is something that actually happened.
    I saw my own death,
    or rather, myself dying, on a reclining chair in an airplane,
    on March 1, 2012.
    It was an unpleasant, elucidating experience.
    It rattled me!
    Let me tell you all about it.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2099%20--%20A%20Night%20at%20the%20Bardo%202.m4a" type="audio/x-m4a" length="14350816" />
<guid>http://mbird.com/podcastgen/media/Episode%2099%20--%20A%20Night%20at%20the%20Bardo%202.m4a</guid>
<pubDate>Sat, 10 Mar 2012 17:15:05 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:29:00</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 98 - Reflections in a Golden Eye</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[If you want to find out what true north is in your life -- in other words,
where you are really going -- notice what books you are drawn to.
Or what movies you really like.  Or what  music you're putting on your iPod these days.  Or what television show you can't miss this week.
Those things function as a truth north for your life's actual direction.
This podcast looks at two revealing sentences, within two modern masterpieces, of this phenomenon of true north's revelation.
Operationally, I am wondering where you ("the living" -- B. 'Boris' Pickett)
will come down.]]>
</description>
<itunes:subtitle />
<itunes:summary>If you want to find out what true north is in your life -- in other words,
    where you are really going -- notice what books you are drawn to.
    Or what movies you really like.  Or what  music you&apos;re putting on your iPod these days.  Or what television show you can&apos;t miss this week.
    Those things function as a truth north for your life&apos;s actual direction.
    This podcast looks at two revealing sentences, within two modern masterpieces, of this phenomenon of true north&apos;s revelation.
    Operationally, I am wondering where you (&quot;the living&quot; -- B. &apos;Boris&apos; Pickett)
    will come down.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2098%20-%20Reflections%20in%20a%20Golden%20Eye%202.m4a" type="audio/x-m4a" length="16280560" />
<guid>http://mbird.com/podcastgen/media/Episode%2098%20-%20Reflections%20in%20a%20Golden%20Eye%202.m4a</guid>
<pubDate>Sat, 10 Mar 2012 17:02:20 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:32:56</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 97 - Surprise (Symphony)</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["Oops!  I did it again!":
it just came over me.
Despite a break, a real break, very soon to come,
Lola compelled one to speak.
I mean, "Lola", the 1961 movie by Jacques Demy.
This podcast is a memo on ego-less communication.
It can really happen, and almost never does.
But you can't beat it -- you can't beat it -- when it does.
In just about any aspect of life you can name.]]>
</description>
<itunes:subtitle />
<itunes:summary>&quot;Oops!  I did it again!&quot;:
    it just came over me.
    Despite a break, a real break, very soon to come,
    Lola compelled one to speak.
    I mean, &quot;Lola&quot;, the 1961 movie by Jacques Demy.
    This podcast is a memo on ego-less communication.
    It can really happen, and almost never does.
    But you can&apos;t beat it -- you can&apos;t beat it -- when it does.
    In just about any aspect of life you can name.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2097%20-%20Surprise%20Symphony.m4a" type="audio/x-m4a" length="11929952" />
<guid>http://mbird.com/podcastgen/media/Episode%2097%20-%20Surprise%20Symphony.m4a</guid>
<pubDate>Mon, 13 Feb 2012 13:38:33 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:24:04</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 96 - Strack-Billerbeck</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["Disputed Passage" (Lloyd C. Douglas) is what this podcast is not.
There are any number of issues to talk about,
yet so many are so particuar,
and rally around themselves all kinds of differing opinions.
I'd rather do -- that is, try to do in a small way -- something of what  Claude Berri actually did in "Jean de Florette/Manon of the Spring" (1986), which was, in his own words,
to scrape down to the universal:
our human nature and suffering,  in common -- the tie that binds.
After this cast, I am taking a short break.
But it's really just  "pre-production" time, for the next season of,
"Fireball XL 5".]]>
</description>
<itunes:subtitle />
<itunes:summary>&quot;Disputed Passage&quot; (Lloyd C. Douglas) is what this podcast is not.
    There are any number of issues to talk about,
    yet so many are so particuar,
    and rally around themselves all kinds of differing opinions.
    I&apos;d rather do -- that is, try to do in a small way -- something of what  Claude Berri actually did in &quot;Jean de Florette/Manon of the Spring&quot; (1986), which was, in his own words,
    to scrape down to the universal:
    our human nature and suffering,  in common -- the tie that binds.
    After this cast, I am taking a short break.
    But it&apos;s really just  &quot;pre-production&quot; time, for the next season of,
    &quot;Fireball XL 5&quot;.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2095%20-%20Strack-Billerbeck.m4a" type="audio/x-m4a" length="11995136" />
<guid>http://mbird.com/podcastgen/media/Episode%2095%20-%20Strack-Billerbeck.m4a</guid>
<pubDate>Sat, 11 Feb 2012 10:47:48 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:24:12</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Mini Podcast 94 - My New Program</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Language changes, changes, changes.
"Elle coule, coule, coule."
Like a simple but undeviating "conversation" at the drive -through window of the  bank.
Or like the use of the word "program".
"Program"  doesn't mean a Lenten series anymore.
It doesn't mean what it used to mean.
It means something else now.
So I need  your help,
to devise a more robust program than just  another pot luck.]]>
</description>
<itunes:subtitle />
<itunes:summary>Language changes, changes, changes.
    &quot;Elle coule, coule, coule.&quot;
    Like a simple but undeviating &quot;conversation&quot; at the drive -through window of the  bank.
    Or like the use of the word &quot;program&quot;.
    &quot;Program&quot;  doesn&apos;t mean a Lenten series anymore.
    It doesn&apos;t mean what it used to mean.
    It means something else now.
    So I need  your help,
    to devise a more robust program than just  another pot luck.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Mini%20Podcast%2094%20-%20My%20new%20Program.m4a" type="audio/x-m4a" length="7938832" />
<guid>http://mbird.com/podcastgen/media/Mini%20Podcast%2094%20-%20My%20new%20Program.m4a</guid>
<pubDate>Fri, 03 Feb 2012 16:19:08 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:15:56</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 93 - Falsification</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["Falsification" is another word for compartmentalization.
When we falsify reality -- as in "being untrue", either to a person or to convictions that we (otherwise) hold sincerely -- we get, well,
what we deserve.
The New Testament gets falsified all the time; and
the obloquy which falsification, when found out, gets us,
clouds everything --  not to mention the very goods we actually could give.
Those goods are Reality and Mercy.
This podcast goes from Cozzens (don't worry) to Christians to lawyers to "Perfidia" to "Band of Gold".  But mainly, it goes out to ...  me and you.]]>
</description>
<itunes:subtitle />
<itunes:summary>&quot;Falsification&quot; is another word for compartmentalization.
    When we falsify reality -- as in &quot;being untrue&quot;, either to a person or to convictions that we (otherwise) hold sincerely -- we get, well,
    what we deserve.
    The New Testament gets falsified all the time; and
    the obloquy which falsification, when found out, gets us,
    clouds everything --  not to mention the very goods we actually could give.
    Those goods are Reality and Mercy.
    This podcast goes from Cozzens (don&apos;t worry) to Christians to lawyers to &quot;Perfidia&quot; to &quot;Band of Gold&quot;.  But mainly, it goes out to ...  me and you.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2093%20-%20Falsification%202.m4a" type="audio/x-m4a" length="15250048" />
<guid>http://mbird.com/podcastgen/media/Episode%2093%20-%20Falsification%202.m4a</guid>
<pubDate>Fri, 03 Feb 2012 08:26:39 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:50</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 92 - G-d</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["Kuh-hay-tchuh-pek".
It means 'God', or rather G-d, in Martian.
You can find out all about "Kuh-hay-tchuh-pek" in the now Criterioned
1964 movie "Robinson Crusoe on Mars".
"Kuh-hay-tchuh-pek" is God, and a very right and proper God, too.
He is Divine Order, but He is also a Nice Guy.
This podcast is about G-d.
I hope you'll like Him.]]>
</description>
<itunes:subtitle />
<itunes:summary>&quot;Kuh-hay-tchuh-pek&quot;.
    It means &apos;God&apos;, or rather G-d, in Martian.
    You can find out all about &quot;Kuh-hay-tchuh-pek&quot; in the now Criterioned
    1964 movie &quot;Robinson Crusoe on Mars&quot;.
    &quot;Kuh-hay-tchuh-pek&quot; is God, and a very right and proper God, too.
    He is Divine Order, but He is also a Nice Guy.
    This podcast is about G-d.
    I hope you&apos;ll like Him.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2093%20-%20G-d.m4a" type="audio/x-m4a" length="9983184" />
<guid>http://mbird.com/podcastgen/media/Episode%2093%20-%20G-d.m4a</guid>
<pubDate>Wed, 01 Feb 2012 13:27:29 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:20:06</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 91 - Sequels</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Sequels are strange:
sometimes they're better than the original,
most of the time they're worse.
What makes a good sequel?
"The Empire Strikes Back", for example;
or "The Invisible Man Returns";  or
"The Ghost of Frankenstein".
Well, preaching -- I mean preaching in the formal sense, i.e., preaching in churches -- is a study in sequels.
When you preach a sermon, you're in a long succession.
It goes all the way back to the Sermon on the Mount.
That was a good one.
Most of its sequels, however, don't seem to have the same power.
They tend to be soon forgotten.
I want to learn from "The Invisible Man's Revenge", and "Ghost", and "Hand" (you know what I mean) in order to know what makes a good  sequel.
This is a podcast on the art and science of preaching.]]>
</description>
<itunes:subtitle />
<itunes:summary>Sequels are strange:
    sometimes they&apos;re better than the original,
    most of the time they&apos;re worse.
    What makes a good sequel?
    &quot;The Empire Strikes Back&quot;, for example;
    or &quot;The Invisible Man Returns&quot;;  or
    &quot;The Ghost of Frankenstein&quot;.
    Well, preaching -- I mean preaching in the formal sense, i.e., preaching in churches -- is a study in sequels.
    When you preach a sermon, you&apos;re in a long succession.
    It goes all the way back to the Sermon on the Mount.
    That was a good one.
    Most of its sequels, however, don&apos;t seem to have the same power.
    They tend to be soon forgotten.
    I want to learn from &quot;The Invisible Man&apos;s Revenge&quot;, and &quot;Ghost&quot;, and &quot;Hand&quot; (you know what I mean) in order to know what makes a good  sequel.
    This is a podcast on the art and science of preaching.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2089%20-%20Sequels.m4a" type="audio/x-m4a" length="15659248" />
<guid>http://mbird.com/podcastgen/media/Episode%2089%20-%20Sequels.m4a</guid>
<pubDate>Fri, 27 Jan 2012 11:19:45 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:31:40</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 88 - Tana and Tahrir</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[I don't believe in "reality",
or rather, I believe what looks like reality is seldom reality.
This can be easily proved by a quick viewing of ...
"The Mummy Ghost" (1944).
Once look at that wonderful movie
is able to confer
an accurate understanding of reality.
This is because "The Mummy's Ghost" 's reality IS reality.
Podcast 88 concerns Kharis, Tana Leaves, and
the Arab Spring.
P.S. From Kerouac:
" 'Facts' are sophistries."]]>
</description>
<itunes:subtitle />
<itunes:summary>I don&apos;t believe in &quot;reality&quot;,
    or rather, I believe what looks like reality is seldom reality.
    This can be easily proved by a quick viewing of ...
    &quot;The Mummy Ghost&quot; (1944).
    Once look at that wonderful movie
    is able to confer
    an accurate understanding of reality.
    This is because &quot;The Mummy&apos;s Ghost&quot; &apos;s reality IS reality.
    Podcast 88 concerns Kharis, Tana Leaves, and
    the Arab Spring.
    P.S. From Kerouac:
    &quot; &apos;Facts&apos; are sophistries.&quot;</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2088%20-%20Tana%20and%20Tahrir.m4a" type="audio/x-m4a" length="16444432" />
<guid>http://mbird.com/podcastgen/media/Episode%2088%20-%20Tana%20and%20Tahrir.m4a</guid>
<pubDate>Fri, 20 Jan 2012 09:28:56 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:16</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 87 - Bette Davis Eyes</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[They are all, like Ray Milland, "The Man with the X-Ray Eyes" --
these Huguenot heroes:
Marot, Duplessis-Mornay, de Beze, de Coligny, de Rohan,
d'Aubigne.
That includes their English co-religionists, such as
Whitgift and Abbott and Grindal.
These are eyes of defeat, eyes that convey an end to
self-reference, eyes of a markedly ego-less state.
You simply have to undergo defeat, have to, in order to, well,
become a little child.
Old ancient wisdom.]]>
</description>
<itunes:subtitle />
<itunes:summary>They are all, like Ray Milland, &quot;The Man with the X-Ray Eyes&quot; --
    these Huguenot heroes:
    Marot, Duplessis-Mornay, de Beze, de Coligny, de Rohan,
    d&apos;Aubigne.
    That includes their English co-religionists, such as
    Whitgift and Abbott and Grindal.
    These are eyes of defeat, eyes that convey an end to
    self-reference, eyes of a markedly ego-less state.
    You simply have to undergo defeat, have to, in order to, well,
    become a little child.
    Old ancient wisdom.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2087%20-%20Bette%20Davis%20Eyes.m4a" type="audio/x-m4a" length="15478960" />
<guid>http://mbird.com/podcastgen/media/Episode%2087%20-%20Bette%20Davis%20Eyes.m4a</guid>
<pubDate>Thu, 19 Jan 2012 11:38:22 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:31:18</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 86 - Supermarionation II</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This podcast tries to go a little deeper with Supermarionation.
It is really about social class, and the kind of alliance that inevitably imperils a religion whose goal is emancipating the human race.
We start with "It Happened One Night", then chart our way north,  to an old surprising hymn.]]>
</description>
<itunes:subtitle />
<itunes:summary>This podcast tries to go a little deeper with Supermarionation.
    It is really about social class, and the kind of alliance that inevitably imperils a religion whose goal is emancipating the human race.
    We start with &quot;It Happened One Night&quot;, then chart our way north,  to an old surprising hymn.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2086%20-%20Supermarionation%20II.m4a" type="audio/x-m4a" length="13728864" />
<guid>http://mbird.com/podcastgen/media/Episode%2086%20-%20Supermarionation%20II.m4a</guid>
<pubDate>Wed, 11 Jan 2012 14:29:41 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:27:44</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 85 - Protestant Episcopalians in Supermarionation</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Can the mind of man and woman conceive that the subject of Episcopal haberdashery
in the movies might be interesting and meaningful?
Well, yes, it might be, at least to me.
This podcast surveys Protestant Episcopal clothing in the movies and television.
We travel in our sound machine from "The Bishop's Wife" to "Family Plot" to "Night of the Iguana" to "The Sandpiper"; and we end up on British tv -- in Supermarionation.
Maybe this is completely unimportant.
Then again...
I dedicate the cast to Fred Rogers, fellow pilgrim and dialogue partner.]]>
</description>
<itunes:subtitle />
<itunes:summary>Can the mind of man and woman conceive that the subject of Episcopal haberdashery
    in the movies might be interesting and meaningful?
    Well, yes, it might be, at least to me.
    This podcast surveys Protestant Episcopal clothing in the movies and television.
    We travel in our sound machine from &quot;The Bishop&apos;s Wife&quot; to &quot;Family Plot&quot; to &quot;Night of the Iguana&quot; to &quot;The Sandpiper&quot;; and we end up on British tv -- in Supermarionation.
    Maybe this is completely unimportant.
    Then again...
    I dedicate the cast to Fred Rogers, fellow pilgrim and dialogue partner.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2085%20-%20Protestant%20Episcopalians%20in%20Supermarionation.m4a" type="audio/x-m4a" length="18472432" />
<guid>http://mbird.com/podcastgen/media/Episode%2085%20-%20Protestant%20Episcopalians%20in%20Supermarionation.m4a</guid>
<pubDate>Tue, 10 Jan 2012 07:47:23 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:37:24</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 84 - Yvette Vickers (f. 4.27.11)</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Yvette Vickers played supporting roles in two unforgettable 1950's science-fiction movies:
"Attack of the 50 Foot Woman" and "Attack of the Giant Leeches".
As far as I'm concerned, she stole the show both times.
But, Yvette Vickers is now dead.
Or rather, she was found dead,
on the 27th of April last year (201l).
The conditions under which she was found, and the conditions under which she apparently lived her life near the end of it,
evoke floods of compassion.  They simply have to.
How could this have happened?
How could Yvette Vickers, our once-and-future (saucy) flame,
have ended that way?
This podcast -- I wouldn't mind calling it pastoral -- is an attempt to understand.


]]>
</description>
<itunes:subtitle />
<itunes:summary>Yvette Vickers played supporting roles in two unforgettable 1950&apos;s science-fiction movies:
    &quot;Attack of the 50 Foot Woman&quot; and &quot;Attack of the Giant Leeches&quot;.
    As far as I&apos;m concerned, she stole the show both times.
    But, Yvette Vickers is now dead.
    Or rather, she was found dead,
    on the 27th of April last year (201l).
    The conditions under which she was found, and the conditions under which she apparently lived her life near the end of it,
    evoke floods of compassion.  They simply have to.
    How could this have happened?
    How could Yvette Vickers, our once-and-future (saucy) flame,
    have ended that way?
    This podcast -- I wouldn&apos;t mind calling it pastoral -- is an attempt to understand.


</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2084%20-%20Yvette%20Vickers%20%28f.%204.27.11%29.m4a" type="audio/x-m4a" length="16411904" />
<guid>http://mbird.com/podcastgen/media/Episode%2084%20-%20Yvette%20Vickers%20%28f.%204.27.11%29.m4a</guid>
<pubDate>Mon, 09 Jan 2012 11:52:10 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:12</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 82 -- Speaking in Tongues</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is a theme with me --
the pros and cons (there aren't many cons) of learning foreign languages.
Also, how does it actually work?
Why is one language easier for a given person to learn than another?
Also, what's the relatiion between learning a language to read,
and learning a language to speak?
And why is psychology so important, personal psychology,
in the acquisition of a foreign 'tongue'?
Here is 50 years' experience of pain and suffering (and altered states)
rolled up into a single half hour.
]]>
</description>
<itunes:subtitle />
<itunes:summary>This is a theme with me --
    the pros and cons (there aren&apos;t many cons) of learning foreign languages.
    Also, how does it actually work?
    Why is one language easier for a given person to learn than another?
    Also, what&apos;s the relatiion between learning a language to read,
    and learning a language to speak?
    And why is psychology so important, personal psychology,
    in the acquisition of a foreign &apos;tongue&apos;?
    Here is 50 years&apos; experience of pain and suffering (and altered states)
    rolled up into a single half hour.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2082%20--%20Foreign%20Languages.m4a" type="audio/x-m4a" length="16362496" />
<guid>http://mbird.com/podcastgen/media/Episode%2082%20--%20Foreign%20Languages.m4a</guid>
<pubDate>Wed, 28 Dec 2011 11:17:34 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:06</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 81 - Violette amoureuse</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[From our house to your house,
at the Turning of the Year:
a portrait of the dignity that is able to inhere within romantic love --
sometimes.
The subject is a short scene, a musical number really,
in a late Jacques Demy, "Une Chambre en Ville" (1982).
You can YouTube it by typing in "Violette amoureuse".
I have faith you will be richly repaid.
Try to marry a 'Violette' if you possibly can -- or, if it's too late,
tell your children about her.
]]>
</description>
<itunes:subtitle />
<itunes:summary>From our house to your house,
    at the Turning of the Year:
    a portrait of the dignity that is able to inhere within romantic love --
    sometimes.
    The subject is a short scene, a musical number really,
    in a late Jacques Demy, &quot;Une Chambre en Ville&quot; (1982).
    You can YouTube it by typing in &quot;Violette amoureuse&quot;.
    I have faith you will be richly repaid.
    Try to marry a &apos;Violette&apos; if you possibly can -- or, if it&apos;s too late,
    tell your children about her.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2081%20-%20Violette%20amoureuse.m4a" type="audio/x-m4a" length="12584064" />
<guid>http://mbird.com/podcastgen/media/Episode%2081%20-%20Violette%20amoureuse.m4a</guid>
<pubDate>Tue, 27 Dec 2011 17:39:58 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:25:24</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 79 - Would you speak up, please?</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Why am I "afraid to say what I really want to say" (Jack Kerouac)?
That's a line from "Visions of Gerard", and many could echo it.
This podcast is about changing mores,
specifically the contrast between a sensational murder case of the 1930s and a sensational case of recent times.
Then there's Ken Russell's "The Devils" (197),
a charming little movie -- and the shifting sands of killing
inquisition.
Maybe I should quit while I'm ahead.]]>
</description>
<itunes:subtitle />
<itunes:summary>Why am I &quot;afraid to say what I really want to say&quot; (Jack Kerouac)?
    That&apos;s a line from &quot;Visions of Gerard&quot;, and many could echo it.
    This podcast is about changing mores,
    specifically the contrast between a sensational murder case of the 1930s and a sensational case of recent times.
    Then there&apos;s Ken Russell&apos;s &quot;The Devils&quot; (197),
    a charming little movie -- and the shifting sands of killing
    inquisition.
    Maybe I should quit while I&apos;m ahead.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2079%20-%20Would%20you%20speak%20up%2C%20please_%20Hell%2C%20no%21.m4a" type="audio/x-m4a" length="17507392" />
<guid>http://mbird.com/podcastgen/media/Episode%2079%20-%20Would%20you%20speak%20up%2C%20please_%20Hell%2C%20no%21.m4a</guid>
<pubDate>Fri, 16 Dec 2011 13:46:25 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:35:26</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 78 - Under Satan&apos;s Sun</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[
This is PZ's Christmas Podcast.]]>
</description>
<itunes:subtitle>This is PZ&apos;s Christmas Podcast.</itunes:subtitle>
<itunes:summary>
    This is PZ&apos;s Christmas Podcast.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2078%20-%20Under%20Satan%27s%20Sun.m4a" type="audio/x-m4a" length="14808432" />
<guid>http://mbird.com/podcastgen/media/Episode%2078%20-%20Under%20Satan%27s%20Sun.m4a</guid>
<pubDate>Sun, 11 Dec 2011 11:58:15 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:29:56</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 77 - Canned Heat</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[What constitutes you, as a human being?
What are the parts which make you the whole you are?
A single sentence from Huxley's  "After many a summer dies the swan"
can help,
together with Fritz Lang's "Woman in the Moon".
It's not about the ego.
I am so sorry that human education pumps up
that flat tire.
Is there another way to educate ... ourselves?]]>
</description>
<itunes:subtitle />
<itunes:summary>What constitutes you, as a human being?
    What are the parts which make you the whole you are?
    A single sentence from Huxley&apos;s  &quot;After many a summer dies the swan&quot;
    can help,
    together with Fritz Lang&apos;s &quot;Woman in the Moon&quot;.
    It&apos;s not about the ego.
    I am so sorry that human education pumps up
    that flat tire.
    Is there another way to educate ... ourselves?</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2077%20-%20Canned%20Heat.m4a" type="audio/x-m4a" length="14563280" />
<guid>http://mbird.com/podcastgen/media/Episode%2077%20-%20Canned%20Heat.m4a</guid>
<pubDate>Sat, 03 Dec 2011 09:07:03 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:29:26</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 76 - Lounge Crooner Classics</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[I'm shooting for quality today.
In the spirit of earlier podcasts  concerning Giant Crab Movies and Journey,this podcast concerns what might today be called "Lounge Crooner Classics".
In their day, they were pop songs commissioned to be played over the credits of movies and then sold as singles.
We're talking about "Voyage to the Bottom of the Sea" by Frankie Avalon;
"Journey to the 7th Planet" by Otto Brandenburg;
"The Lost Continent" by The Peddlers; and
"The Vengeance of She" by Robert Field.
These are absurd performances of human art  and commerce pitched
to the highest possible degree.  At least in my opinion.
Moreover, they can help you with your anger!
Few things do more to diminish anger than a feel for the absurd.
This podcast is intended to help the speaker, and the listener,
with his or her anger.
"Come with me,
And take a Voyage,
To the Bottom,
Of the Sea."]]>
</description>
<itunes:subtitle />
<itunes:summary>I&apos;m shooting for quality today.
    In the spirit of earlier podcasts  concerning Giant Crab Movies and Journey,this podcast concerns what might today be called &quot;Lounge Crooner Classics&quot;.
    In their day, they were pop songs commissioned to be played over the credits of movies and then sold as singles.
    We&apos;re talking about &quot;Voyage to the Bottom of the Sea&quot; by Frankie Avalon;
    &quot;Journey to the 7th Planet&quot; by Otto Brandenburg;
    &quot;The Lost Continent&quot; by The Peddlers; and
    &quot;The Vengeance of She&quot; by Robert Field.
    These are absurd performances of human art  and commerce pitched
    to the highest possible degree.  At least in my opinion.
    Moreover, they can help you with your anger!
    Few things do more to diminish anger than a feel for the absurd.
    This podcast is intended to help the speaker, and the listener,
    with his or her anger.
    &quot;Come with me,
    And take a Voyage,
    To the Bottom,
    Of the Sea.&quot;</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2076%20-%20Lounge%20Crooner%20Classics.m4a" type="audio/x-m4a" length="14923104" />
<guid>http://mbird.com/podcastgen/media/Episode%2076%20-%20Lounge%20Crooner%20Classics.m4a</guid>
<pubDate>Sat, 26 Nov 2011 17:12:14 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:10</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 74 - &quot;Please Come to Boston&quot;</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[I tried to follow the invitation of that song recently.
Saw a lot of things, found out a lot of things,
remembered a lot of things,
heard a couple of new things.
It was a definite pilgrimage.
I would like to tell you about it.

]]>
</description>
<itunes:subtitle />
<itunes:summary>I tried to follow the invitation of that song recently.
    Saw a lot of things, found out a lot of things,
    remembered a lot of things,
    heard a couple of new things.
    It was a definite pilgrimage.
    I would like to tell you about it.

</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2074%20-%20_Please%20Come%20to%20Boston_.m4a" type="audio/x-m4a" length="15168384" />
<guid>http://mbird.com/podcastgen/media/Episode%2074%20-%20_Please%20Come%20to%20Boston_.m4a</guid>
<pubDate>Tue, 22 Nov 2011 10:50:06 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:40</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 73 - When I&apos;m 64</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Can the "young" be instructed by the "old"?
Can Nigel Kneale's "Planet People" be even saved
by the over 70s?
To put this another way, are there two messages to life:
one for the first half and another for the second?
Ultimately, no.
There is one message.
Alack! : It comes through suffering.
Pump up the volume.]]>
</description>
<itunes:subtitle />
<itunes:summary>Can the &quot;young&quot; be instructed by the &quot;old&quot;?
    Can Nigel Kneale&apos;s &quot;Planet People&quot; be even saved
    by the over 70s?
    To put this another way, are there two messages to life:
    one for the first half and another for the second?
    Ultimately, no.
    There is one message.
    Alack! : It comes through suffering.
    Pump up the volume.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2071%20-%20When%20I%27m%2064.m4a" type="audio/x-m4a" length="13925440" />
<guid>http://mbird.com/podcastgen/media/Episode%2071%20-%20When%20I%27m%2064.m4a</guid>
<pubDate>Thu, 03 Nov 2011 07:07:07 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:28:08</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 72 - Making Plans for Nigel</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Nigel Kneale (1922-2006) was absolute murder,
in the Reggae sense.
No writer of English science fiction
thought more originally
than Nigel Kneale, who mostly wrote teleplays for the BBC.
His "Quatermass (pro. 'Kway-ter-mass') and the Pit" from 1959
attempted to explain the whole history of religion
via Martians.  It strangely works.
Kneale's "Quatermass" (1979) showed how the "young" are unable to save themselves from generational self-slaughter.  Only "seniors" can save 'em!
There's a lot to Kneale, He's one other of those unusual humanists
who understood about Original Sin.
These rare birds -- they're all  "murder" -- have much to tell us.]]>
</description>
<itunes:subtitle />
<itunes:summary>Nigel Kneale (1922-2006) was absolute murder,
    in the Reggae sense.
    No writer of English science fiction
    thought more originally
    than Nigel Kneale, who mostly wrote teleplays for the BBC.
    His &quot;Quatermass (pro. &apos;Kway-ter-mass&apos;) and the Pit&quot; from 1959
    attempted to explain the whole history of religion
    via Martians.  It strangely works.
    Kneale&apos;s &quot;Quatermass&quot; (1979) showed how the &quot;young&quot; are unable to save themselves from generational self-slaughter.  Only &quot;seniors&quot; can save &apos;em!
    There&apos;s a lot to Kneale, He&apos;s one other of those unusual humanists
    who understood about Original Sin.
    These rare birds -- they&apos;re all  &quot;murder&quot; -- have much to tell us.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2071%20-%20Nigel%20Kneale.m4a" type="audio/x-m4a" length="15757520" />
<guid>http://mbird.com/podcastgen/media/Episode%2071%20-%20Nigel%20Kneale.m4a</guid>
<pubDate>Mon, 31 Oct 2011 11:46:08 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:31:52</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 71 - Removals Men II</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Rejoicing at someone's execution, in "disturbing images",
is hard enough to absorb.
To add the unaccountable silence of Christians in relation to such joy
is almost impossible to absorb.
What's to love in this world, in this planetary race of not so human beings?
We're hoping to get a little help today from Harnack and Huxley.
(Wonder what would have happened if they'd ever met?
I feel almost certain that Holl, Harnack's A student, would have liked Huxley.)]]>
</description>
<itunes:subtitle />
<itunes:summary>Rejoicing at someone&apos;s execution, in &quot;disturbing images&quot;,
    is hard enough to absorb.
    To add the unaccountable silence of Christians in relation to such joy
    is almost impossible to absorb.
    What&apos;s to love in this world, in this planetary race of not so human beings?
    We&apos;re hoping to get a little help today from Harnack and Huxley.
    (Wonder what would have happened if they&apos;d ever met?
    I feel almost certain that Holl, Harnack&apos;s A student, would have liked Huxley.)</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Removals%20Men%20II.m4a" type="audio/x-m4a" length="15282624" />
<guid>http://mbird.com/podcastgen/media/Removals%20Men%20II.m4a</guid>
<pubDate>Fri, 21 Oct 2011 11:48:20 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:54</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 70 - Removals Men</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is about the use of language to cover an uinpleasant reality.
It's not just about the "removal" of an al awlaki or a "new chapter in the history of Libya" accomplished by means of the murder of a POW who was captured alive.
It's about resigning yourself to something you cannot change.]]>
</description>
<itunes:subtitle />
<itunes:summary>This is about the use of language to cover an uinpleasant reality.
    It&apos;s not just about the &quot;removal&quot; of an al awlaki or a &quot;new chapter in the history of Libya&quot; accomplished by means of the murder of a POW who was captured alive.
    It&apos;s about resigning yourself to something you cannot change.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2067%20-%20Removal_2.m4a" type="audio/x-m4a" length="14105008" />
<guid>http://mbird.com/podcastgen/media/Episode%2067%20-%20Removal_2.m4a</guid>
<pubDate>Fri, 21 Oct 2011 09:32:41 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:28:30</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 69 -  Pipes of Pan</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Arthur Machen meets St. Matthew's Gospel,
Chapter 11, Verses 16-19.
You can try to make your voice heard with an engaging, danceable tune,
and it will pass like a shadow over the water..
(Think "Men Without Hats".)
Or you can try it in a shrill, scratchy key,
and it will still be forgotten, fast.
(Thiink P.J. Proby .)
Whether it flops or not, however,
that's not the point .
Someone will probably eventually hear it, and take it up.
Think Joe Meek.]]>
</description>
<itunes:subtitle />
<itunes:summary>Arthur Machen meets St. Matthew&apos;s Gospel,
    Chapter 11, Verses 16-19.
    You can try to make your voice heard with an engaging, danceable tune,
    and it will pass like a shadow over the water..
    (Think &quot;Men Without Hats&quot;.)
    Or you can try it in a shrill, scratchy key,
    and it will still be forgotten, fast.
    (Thiink P.J. Proby .)
    Whether it flops or not, however,
    that&apos;s not the point .
    Someone will probably eventually hear it, and take it up.
    Think Joe Meek.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2070%20-%20The%20Pipes%20of%20Pan.m4a" type="audio/x-m4a" length="11962592" />
<guid>http://mbird.com/podcastgen/media/Episode%2070%20-%20The%20Pipes%20of%20Pan.m4a</guid>
<pubDate>Sat, 15 Oct 2011 05:34:01 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:24:08</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 68 - The Inward Voice, Pt. 2</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[There is nothing quite like the Inward Voice of
'Mark Rutherford', the novelist whose real name was William Hale White.
He wore a mask over a mask,
and his six novels constitute a kind of ultimate Inward Voice within
Victorian fiction.
Today we look at his "Revolution in Tanner's Lane" (1890), which reveals the worst and also the best  of the Romans 7 understanding of
human nature.
Cradled in this unique book -- "Revolution" -- is a message I think the world's gotta hear.
I don't think it ever will, but STILL 'MarkRutherford' committed his Inward Voice to paper, and we know a lot more about ourselves because of him..
]]>
</description>
<itunes:subtitle />
<itunes:summary>There is nothing quite like the Inward Voice of
    &apos;Mark Rutherford&apos;, the novelist whose real name was William Hale White.
    He wore a mask over a mask,
    and his six novels constitute a kind of ultimate Inward Voice within
    Victorian fiction.
    Today we look at his &quot;Revolution in Tanner&apos;s Lane&quot; (1890), which reveals the worst and also the best  of the Romans 7 understanding of
    human nature.
    Cradled in this unique book -- &quot;Revolution&quot; -- is a message I think the world&apos;s gotta hear.
    I don&apos;t think it ever will, but STILL &apos;MarkRutherford&apos; committed his Inward Voice to paper, and we know a lot more about ourselves because of him..
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2069%20-%20Inwardness%2C%20Pt.%202.m4a" type="audio/x-m4a" length="17212944" />
<guid>http://mbird.com/podcastgen/media/Episode%2069%20-%20Inwardness%2C%20Pt.%202.m4a</guid>
<pubDate>Sun, 09 Oct 2011 19:46:06 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:34:50</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 67 - The Inward Voice, Pt. 1</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Here is a two-parter concerning your inward voice:
What is it, and how do you find it?
From a Romans 7 point of view, the inward voice (and voices)
is almost all that matters.
Now get it down!  Write it down!  Put it on paper, or else it'll probably just
"Fade Away" (Rolling Stones).
This is personal archaeology, yours and mine, and it involves digging,
and lifting.]]>
</description>
<itunes:subtitle />
<itunes:summary>Here is a two-parter concerning your inward voice:
    What is it, and how do you find it?
    From a Romans 7 point of view, the inward voice (and voices)
    is almost all that matters.
    Now get it down!  Write it down!  Put it on paper, or else it&apos;ll probably just
    &quot;Fade Away&quot; (Rolling Stones).
    This is personal archaeology, yours and mine, and it involves digging,
    and lifting.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2068%20-%20Inwardness.m4a" type="audio/x-m4a" length="16836928" />
<guid>http://mbird.com/podcastgen/media/Episode%2068%20-%20Inwardness.m4a</guid>
<pubDate>Sun, 09 Oct 2011 19:38:44 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:34:04</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 66 - Altars by the Roadside</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Now here's a find:
a passage in the novel "Revolution in Tanner's Lane" (1890)
by 'Mark Rutherford' (aka William Hale White),
in which the author answers the question I set in the previous cast.
If there is a word from religion to the middle-aged and "mature" --
i.e., a word of humbled acquiescence to the disillusioned and shaken --
what is  religion's word to the young?
Can the same message of experienced wisdom and non-identification,
which seems able to communicate with immediacy to the shattered,
have something to say to the young and engaged,
to the active members of this world,  all  "wishin' and hopin'" and
working and fretting?
The Rev. Thomas Bradshaw, the genuine-article preacher in Mark
Rutherford's great book, offers a word to "My young friends" (p. 268)
that is a mighty dart to the young but shot from an old man's quiver.
In this cast, let me read  you what Mr. Bradshaw has to say,
then you tell me whether it answers the practical question.]]>
</description>
<itunes:subtitle />
<itunes:summary>Now here&apos;s a find:
    a passage in the novel &quot;Revolution in Tanner&apos;s Lane&quot; (1890)
    by &apos;Mark Rutherford&apos; (aka William Hale White),
    in which the author answers the question I set in the previous cast.
    If there is a word from religion to the middle-aged and &quot;mature&quot; --
    i.e., a word of humbled acquiescence to the disillusioned and shaken --
    what is  religion&apos;s word to the young?
    Can the same message of experienced wisdom and non-identification,
    which seems able to communicate with immediacy to the shattered,
    have something to say to the young and engaged,
    to the active members of this world,  all  &quot;wishin&apos; and hopin&apos;&quot; and
    working and fretting?
    The Rev. Thomas Bradshaw, the genuine-article preacher in Mark
    Rutherford&apos;s great book, offers a word to &quot;My young friends&quot; (p. 268)
    that is a mighty dart to the young but shot from an old man&apos;s quiver.
    In this cast, let me read  you what Mr. Bradshaw has to say,
    then you tell me whether it answers the practical question.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2072%20-%20Altars%20by%20the%20Roadside.m4a" type="audio/x-m4a" length="10523168" />
<guid>http://mbird.com/podcastgen/media/Episode%2072%20-%20Altars%20by%20the%20Roadside.m4a</guid>
<pubDate>Wed, 05 Oct 2011 09:59:33 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:21:12</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 65 - One Message or Two?</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Does life-wisdom offer the same message to the non-disillusioned, who are often on the younger side, as it does to the disillusioned, who are often over-50?
It's a live issue for me, since a gospel of hope to the shattered can sound depressing to people who are working on wresting something like success from life.
Interestingly, many religious pioneers, from Pachomius to Zwingli, from Clare to the "Little Flower", were young when they received a message of negation, but also a new and different theme of affirmation.
Is there a philosophical link between "Build Me Up, Buttercup" (The Foundations) and "The Levee's Gonna Break" (Dylan)?
That's the subject of this podcast.]]>
</description>
<itunes:subtitle />
<itunes:summary>Does life-wisdom offer the same message to the non-disillusioned, who are often on the younger side, as it does to the disillusioned, who are often over-50?
    It&apos;s a live issue for me, since a gospel of hope to the shattered can sound depressing to people who are working on wresting something like success from life.
    Interestingly, many religious pioneers, from Pachomius to Zwingli, from Clare to the &quot;Little Flower&quot;, were young when they received a message of negation, but also a new and different theme of affirmation.
    Is there a philosophical link between &quot;Build Me Up, Buttercup&quot; (The Foundations) and &quot;The Levee&apos;s Gonna Break&quot; (Dylan)?
    That&apos;s the subject of this podcast.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2065%20-%20One%20Message%20or%20Two_.m4a" type="audio/x-m4a" length="16918560" />
<guid>http://mbird.com/podcastgen/media/Episode%2065%20-%20One%20Message%20or%20Two_.m4a</guid>
<pubDate>Sat, 01 Oct 2011 16:08:39 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:34:14</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 64 - My New Law Firm</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[My new law firm is called "Scrambling, Rattled, and Bracing, P.A.".
It is a firm devoted to the project of complete control.
It helps me "scramble" to contain unexpected problems;
prevents me from getting "rattled" by unexpected threats;
and gets me "braced" in anticipation of feared outcomes.
In other words -- you guessed it -- my new law firm helps me get control
of my life.  I pay it to get me ready for every eventuality.
Oddly, though, it hasn't worked as well as I had hoped.
I'm still scrambling, I still get rattled, and I spend every weekend bracing
for Monday.
But hey ! : I've got hopes.  If I can just get a little control ...]]>
</description>
<itunes:subtitle />
<itunes:summary>My new law firm is called &quot;Scrambling, Rattled, and Bracing, P.A.&quot;.
    It is a firm devoted to the project of complete control.
    It helps me &quot;scramble&quot; to contain unexpected problems;
    prevents me from getting &quot;rattled&quot; by unexpected threats;
    and gets me &quot;braced&quot; in anticipation of feared outcomes.
    In other words -- you guessed it -- my new law firm helps me get control
    of my life.  I pay it to get me ready for every eventuality.
    Oddly, though, it hasn&apos;t worked as well as I had hoped.
    I&apos;m still scrambling, I still get rattled, and I spend every weekend bracing
    for Monday.
    But hey ! : I&apos;ve got hopes.  If I can just get a little control ...</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2064%20-%20My%20New%20Law%20Firm.m4a" type="audio/x-m4a" length="15904352" />
<guid>http://mbird.com/podcastgen/media/Episode%2064%20-%20My%20New%20Law%20Firm.m4a</guid>
<pubDate>Tue, 27 Sep 2011 19:30:44 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:32:10</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 63 - One Step Beyond</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This ancient show, much of which is now richly available on YouTube, let alone DVD, understood something important.  It understood about the "collective unconscious" and the nature of the Love that exists underneath human loves.  The several great episodes in this terse ancient treasure, from 1959 to 1961, depict reality so unflinchingly that you can barely look --- and,  the underlying reality of God.   I actually think  "One Step Beyond" is a profounder prototype for "Touched by an Angel". Plus, the music! -- especially Harry Lubin's theme entitled "Weird".  Not his "Fear", which you've heard a hundred times; but  his "Weird".
And here's the 'Dean's Question' for this podcast:  How did William James  decide to define God?]]>
</description>
<itunes:subtitle />
<itunes:summary>This ancient show, much of which is now richly available on YouTube, let alone DVD, understood something important.  It understood about the &quot;collective unconscious&quot; and the nature of the Love that exists underneath human loves.  The several great episodes in this terse ancient treasure, from 1959 to 1961, depict reality so unflinchingly that you can barely look --- and,  the underlying reality of God.   I actually think  &quot;One Step Beyond&quot; is a profounder prototype for &quot;Touched by an Angel&quot;. Plus, the music! -- especially Harry Lubin&apos;s theme entitled &quot;Weird&quot;.  Not his &quot;Fear&quot;, which you&apos;ve heard a hundred times; but  his &quot;Weird&quot;.
    And here&apos;s the &apos;Dean&apos;s Question&apos; for this podcast:  How did William James  decide to define God?</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2063%20-%20One%20Step%20Beyond.m4a" type="audio/x-m4a" length="18080032" />
<guid>http://mbird.com/podcastgen/media/Episode%2063%20-%20One%20Step%20Beyond.m4a</guid>
<pubDate>Sun, 18 Sep 2011 09:12:06 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:36:36</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 62 - What part of you isn&apos;t angry?</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Anger -- it's everywhere.
The question is,
at whom or at what are you NOT angry?
Well, you can't be angry at anyone or anything you love.
Or rather, you can't be angry at that part of anyone or anything that you love.
This podcast is about seismic anger -- into which the internet is just
a current window.  Every age has its window.
This podcast hunts for an answer.]]>
</description>
<itunes:subtitle />
<itunes:summary>Anger -- it&apos;s everywhere.
    The question is,
    at whom or at what are you NOT angry?
    Well, you can&apos;t be angry at anyone or anything you love.
    Or rather, you can&apos;t be angry at that part of anyone or anything that you love.
    This podcast is about seismic anger -- into which the internet is just
    a current window.  Every age has its window.
    This podcast hunts for an answer.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2063%20-%20What%20part%20of%20us%20isn%27t%20angry_.m4a" type="audio/x-m4a" length="16313440" />
<guid>http://mbird.com/podcastgen/media/Episode%2063%20-%20What%20part%20of%20us%20isn%27t%20angry_.m4a</guid>
<pubDate>Sat, 10 Sep 2011 10:21:17 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:00</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 58 - The Umbrellas of Cherbourg</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This gorgeous 1964 film is everything people say it is, and makes you wonder sometimes whether its director and writer, Jacques Demy, was too good for this world.
Let's also hear it for Michel Legrand, who wrote the score.
What I wish to eyeball, and what this podcast is about,  is its vision of romance,
for "Umbrellas of Cherbourg" is about
first love, lost love, best love, et enfin, true love.
The hero's "Je crois que tu peux partir" ("It's time for  you to go.") is so wonderfully masculine, and faithful, and cognizant but 'he's not buying',
that I truly wish every woman in the world who has lost faith in men
could see this movie.
My podcast is about True Love.
It is dedicated to Nick Greenwood.]]>
</description>
<itunes:subtitle />
<itunes:summary>This gorgeous 1964 film is everything people say it is, and makes you wonder sometimes whether its director and writer, Jacques Demy, was too good for this world.
    Let&apos;s also hear it for Michel Legrand, who wrote the score.
    What I wish to eyeball, and what this podcast is about,  is its vision of romance,
    for &quot;Umbrellas of Cherbourg&quot; is about
    first love, lost love, best love, et enfin, true love.
    The hero&apos;s &quot;Je crois que tu peux partir&quot; (&quot;It&apos;s time for  you to go.&quot;) is so wonderfully masculine, and faithful, and cognizant but &apos;he&apos;s not buying&apos;,
    that I truly wish every woman in the world who has lost faith in men
    could see this movie.
    My podcast is about True Love.
    It is dedicated to Nick Greenwood.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/The%20Umbrellas%20of%20Cherbourg.m4a" type="audio/x-m4a" length="22479840" />
<guid>http://mbird.com/podcastgen/media/The%20Umbrellas%20of%20Cherbourg.m4a</guid>
<pubDate>Sun, 14 Aug 2011 18:07:45 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:45:34</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 57 - Beyond the Time Barrier</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Lord Buckley broke down a barrier that is exceptionally hard to break down.
He broke down the barrier between the Sacred and the Profane.
Several of his 'hipsemantic' monologues, once you begin to study them, are fascinating expressions of Christian ideas, but expressed in the terms of an offbeat and wacky nightclub personality.  I don't know of anything like them.
In this second and concluding podcast on a genuine comic genius,
I read, sitting on my white azz, Lord Buckley's riff on "Quo Vadis", entitled "Nero".
Once again, My Lords and Ladies of the Court, I give you Richard Myrle Buckley , together with his affecting 'familiar', OO-Bop-A-Lap.]]>
</description>
<itunes:subtitle />
<itunes:summary>Lord Buckley broke down a barrier that is exceptionally hard to break down.
    He broke down the barrier between the Sacred and the Profane.
    Several of his &apos;hipsemantic&apos; monologues, once you begin to study them, are fascinating expressions of Christian ideas, but expressed in the terms of an offbeat and wacky nightclub personality.  I don&apos;t know of anything like them.
    In this second and concluding podcast on a genuine comic genius,
    I read, sitting on my white azz, Lord Buckley&apos;s riff on &quot;Quo Vadis&quot;, entitled &quot;Nero&quot;.
    Once again, My Lords and Ladies of the Court, I give you Richard Myrle Buckley , together with his affecting &apos;familiar&apos;, OO-Bop-A-Lap.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2057%20-%20Beyond%20the%20Time%20Barrier.m4a" type="audio/x-m4a" length="10065168" />
<guid>http://mbird.com/podcastgen/media/Episode%2057%20-%20Beyond%20the%20Time%20Barrier.m4a</guid>
<pubDate>Fri, 05 Aug 2011 20:25:37 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:20:16</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 56 - Lord Buckley </title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Lord Buckley (aka Richard Myrle Buckley, l906-1960) was a "way out" nightclub comic and monologist, who created "hipsemantic" routines based on famous people -- very famous! -- and famous works of literature.
Lord Buckley's most famous monologue was called "The Nazz" and is a "hipster" re-telling of three miracles of Our Savior, which was Lord Buckley's frequently invoked term for Christ.  "The Nazz" is a homage to Jesus that exists in a class by itself.
If anything you've ever heard or read breaks the barrier between the Sacred and the Profane, "The Nazz" does it.
In this podcast, PZ gives a public reading of Lord Buckley's "The Nazz".
The reading can't fail to be sort of an atrocity -- I almost entitled this cast "The Nazz and My White Azz" -- as the original was performed entirely in African-American iidiom.
Nevertheless, this readng could do the alternate thing of getting down to what Buckley actually wrote and actually said, for his substance is sublime.
PZ owes his appreciation of Lord Buckley to Bill Bowman.]]>
</description>
<itunes:subtitle />
<itunes:summary>Lord Buckley (aka Richard Myrle Buckley, l906-1960) was a &quot;way out&quot; nightclub comic and monologist, who created &quot;hipsemantic&quot; routines based on famous people -- very famous! -- and famous works of literature.
    Lord Buckley&apos;s most famous monologue was called &quot;The Nazz&quot; and is a &quot;hipster&quot; re-telling of three miracles of Our Savior, which was Lord Buckley&apos;s frequently invoked term for Christ.  &quot;The Nazz&quot; is a homage to Jesus that exists in a class by itself.
    If anything you&apos;ve ever heard or read breaks the barrier between the Sacred and the Profane, &quot;The Nazz&quot; does it.
    In this podcast, PZ gives a public reading of Lord Buckley&apos;s &quot;The Nazz&quot;.
    The reading can&apos;t fail to be sort of an atrocity -- I almost entitled this cast &quot;The Nazz and My White Azz&quot; -- as the original was performed entirely in African-American iidiom.
    Nevertheless, this readng could do the alternate thing of getting down to what Buckley actually wrote and actually said, for his substance is sublime.
    PZ owes his appreciation of Lord Buckley to Bill Bowman.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2056%20-%20Lord%20Buckley%20and%20The%20Nazz%202.m4a" type="audio/x-m4a" length="13957968" />
<guid>http://mbird.com/podcastgen/media/Episode%2056%20-%20Lord%20Buckley%20and%20The%20Nazz%202.m4a</guid>
<pubDate>Sun, 31 Jul 2011 07:42:52 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:28:12</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 54 - My Sharona</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is My Sharona of faith,
a series of four theses, briefly explained,
that express an approach to everyday living,
and understanding.
I hope you like them.
]]>
</description>
<itunes:subtitle />
<itunes:summary>This is My Sharona of faith,
    a series of four theses, briefly explained,
    that express an approach to everyday living,
    and understanding.
    I hope you like them.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/My%20Sharona%20-%20Final%20Cut.m4a" type="audio/x-m4a" length="15823232" />
<guid>http://mbird.com/podcastgen/media/My%20Sharona%20-%20Final%20Cut.m4a</guid>
<pubDate>Sat, 09 Jul 2011 08:59:45 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:32:00</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 53 - How to Tell the Future</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[It's possible to tell the future.
It's actually pretty easy.
You have to know about human nature,
and you have to know about fashion.
You have to know that human nature doesn't change,
and you have to know that fashion changes all the time.
It changes right to left, then left to right, then back again.  Then the same, again.
And again.
"My Ever Changing Moods" (Style Council)
You, too, can be a fortune teller.
Here's how.
]]>
</description>
<itunes:subtitle />
<itunes:summary>It&apos;s possible to tell the future.
    It&apos;s actually pretty easy.
    You have to know about human nature,
    and you have to know about fashion.
    You have to know that human nature doesn&apos;t change,
    and you have to know that fashion changes all the time.
    It changes right to left, then left to right, then back again.  Then the same, again.
    And again.
    &quot;My Ever Changing Moods&quot; (Style Council)
    You, too, can be a fortune teller.
    Here&apos;s how.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2056%20-%20Prognostication.m4a" type="audio/x-m4a" length="18816112" />
<guid>http://mbird.com/podcastgen/media/Episode%2056%20-%20Prognostication.m4a</guid>
<pubDate>Sat, 02 Jul 2011 08:33:40 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:38:06</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Area 51 - William Inge</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[William Inge (1913-1973) wrote plays of restrained optimism concerning broken families in small Kansas towns of the 1920's and '30's..  He understood about the importance of sex in everyday life -- even in Protestant Middle-Western America during the Great Depression.  He also understood about the Church and its disappointing failure to help people when the bottom fell out of their lives.
Yet there a wistfulness to Inge.  He seems to be saying, 'If only'.  If only our religious tradition had not declined so from the teachings of Christ.
This podcast talks about William Inge's perspective on the Church Defeated -- by itself !  He writes of sufferers with tender sympathy, with grace in practice.  ]]>
</description>
<itunes:subtitle />
<itunes:summary>William Inge (1913-1973) wrote plays of restrained optimism concerning broken families in small Kansas towns of the 1920&apos;s and &apos;30&apos;s..  He understood about the importance of sex in everyday life -- even in Protestant Middle-Western America during the Great Depression.  He also understood about the Church and its disappointing failure to help people when the bottom fell out of their lives.
    Yet there a wistfulness to Inge.  He seems to be saying, &apos;If only&apos;.  If only our religious tradition had not declined so from the teachings of Christ.
    This podcast talks about William Inge&apos;s perspective on the Church Defeated -- by itself !  He writes of sufferers with tender sympathy, with grace in practice.  </itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Wm.%20Inge%20and%20the%20Church.m4a" type="audio/x-m4a" length="17277984" />
<guid>http://mbird.com/podcastgen/media/Wm.%20Inge%20and%20the%20Church.m4a</guid>
<pubDate>Sat, 18 Jun 2011 08:49:38 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:34:58</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 50- Human Nature</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[It just may be the worst thing about America today:
our view of human nature.
If you listen to almost any -- and I mean, any -- commentator, speechmaker, pundit, or spokesperson, of literally any and every organization, institution, medium,  or government office, you are going to hear about taking charge, and imposing control -- of everything and everybody.  (I hate that they'll now ticket you if you're caught smoking in New York City.  That's insane!  No more "Shake Shack" for us, I am dashed to say.)
The pitiful thing is, their idea of human nature is not true.
It is simply not true.
We are being fed an understanding of human nature that is inaccurate.
It is innacurate from stem to stern.
Therefore there is no HOPE being offered.  Everything is rooted in a fallacy.  "Shallow Hal"
This is Episode 50 of "PZ's Podcast".  Philip Wylie's going to help us out again, but so is wonderful William Inge, and inspired Frenchman Jacques Demy.  I'm going to let them take us there, to
Strawberry Fields ... Forever.]]>
</description>
<itunes:subtitle />
<itunes:summary>It just may be the worst thing about America today:
    our view of human nature.
    If you listen to almost any -- and I mean, any -- commentator, speechmaker, pundit, or spokesperson, of literally any and every organization, institution, medium,  or government office, you are going to hear about taking charge, and imposing control -- of everything and everybody.  (I hate that they&apos;ll now ticket you if you&apos;re caught smoking in New York City.  That&apos;s insane!  No more &quot;Shake Shack&quot; for us, I am dashed to say.)
    The pitiful thing is, their idea of human nature is not true.
    It is simply not true.
    We are being fed an understanding of human nature that is inaccurate.
    It is innacurate from stem to stern.
    Therefore there is no HOPE being offered.  Everything is rooted in a fallacy.  &quot;Shallow Hal&quot;
    This is Episode 50 of &quot;PZ&apos;s Podcast&quot;.  Philip Wylie&apos;s going to help us out again, but so is wonderful William Inge, and inspired Frenchman Jacques Demy.  I&apos;m going to let them take us there, to
    Strawberry Fields ... Forever.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2051%20-%20Human%20Nature.m4a" type="audio/x-m4a" length="16362144" />
<guid>http://mbird.com/podcastgen/media/Episode%2051%20-%20Human%20Nature.m4a</guid>
<pubDate>Sat, 11 Jun 2011 12:02:21 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:06</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 49 - &quot;Unknown and yet well known&quot;</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Another one of those unknown authors.
But he has so much to tell us,
first about sex and then about Christianity.
About  the former, he puts first things first.
About the latter, he puts Jesus on the "Enola Gay".
Would that Philip Wylie were here today, to put Jesus on a predator drone,
or on one of those Navy SEAL helicopters which flew into Pakistan recently.
]]>
</description>
<itunes:subtitle />
<itunes:summary>Another one of those unknown authors.
    But he has so much to tell us,
    first about sex and then about Christianity.
    About  the former, he puts first things first.
    About the latter, he puts Jesus on the &quot;Enola Gay&quot;.
    Would that Philip Wylie were here today, to put Jesus on a predator drone,
    or on one of those Navy SEAL helicopters which flew into Pakistan recently.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Philip%20Wylie%20II%202.m4a" type="audio/x-m4a" length="21400288" />
<guid>http://mbird.com/podcastgen/media/Philip%20Wylie%20II%202.m4a</guid>
<pubDate>Wed, 08 Jun 2011 13:42:05 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:43:22</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 48 - The Disappearance</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Philip Wylie was a prophet in the war between the sexes.
His 1951 novel "The Disappearance", in which, through an unexplained 'cosmic blink', all the women disappear from the world of the men and all the men disappear from the world of the women, is so noble and so disturbing, so wrenching and so uplifting, so wise and so uncommonly religious, that it becomes required reading for everyone who is a man and everyone who is a woman.
]]>
</description>
<itunes:subtitle />
<itunes:summary>Philip Wylie was a prophet in the war between the sexes.
    His 1951 novel &quot;The Disappearance&quot;, in which, through an unexplained &apos;cosmic blink&apos;, all the women disappear from the world of the men and all the men disappear from the world of the women, is so noble and so disturbing, so wrenching and so uplifting, so wise and so uncommonly religious, that it becomes required reading for everyone who is a man and everyone who is a woman.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/The%20Disappearance%20-%20PZ%27s%20Podcast.m4a" type="audio/x-m4a" length="26716240" />
<guid>http://mbird.com/podcastgen/media/The%20Disappearance%20-%20PZ%27s%20Podcast.m4a</guid>
<pubDate>Sun, 29 May 2011 08:03:25 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:54:12</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 45 - Duncan Burne-Wilke</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Herman Wouk's 1985 novel "War and Remembrance" has a most prophetic minor character buried within its 1300 pages.
This character is a philosophical and definitely sweet English aristocrat  named Duncan Burne-Wilke, whom we meet in the "CBI" or "China Burma India" theater of the Second World War.
Burne-Wilke envisages the end of Western colonialism on account of  a massive disillusionment caused by the War.  But he also thinks in religious terms concerning the future of America and England.  He sees the future in terms of the "Bhagavad gita", and a "turning East" of which we are now aware and in relation to which the Christian churches are having to live, defensively.
My podcast speaks of one small voice within a large contemporary epic.
Burne-Wilke's disenchanted words are "crying to be heard" (Traffic), and also responded to.  He haunts the bittersweet narrative of  Wouk's marvelous  book.]]>
</description>
<itunes:subtitle />
<itunes:summary>Herman Wouk&apos;s 1985 novel &quot;War and Remembrance&quot; has a most prophetic minor character buried within its 1300 pages.
    This character is a philosophical and definitely sweet English aristocrat  named Duncan Burne-Wilke, whom we meet in the &quot;CBI&quot; or &quot;China Burma India&quot; theater of the Second World War.
    Burne-Wilke envisages the end of Western colonialism on account of  a massive disillusionment caused by the War.  But he also thinks in religious terms concerning the future of America and England.  He sees the future in terms of the &quot;Bhagavad gita&quot;, and a &quot;turning East&quot; of which we are now aware and in relation to which the Christian churches are having to live, defensively.
    My podcast speaks of one small voice within a large contemporary epic.
    Burne-Wilke&apos;s disenchanted words are &quot;crying to be heard&quot; (Traffic), and also responded to.  He haunts the bittersweet narrative of  Wouk&apos;s marvelous  book.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2034%20-%20Duncan%20Burne-Wilke.m4a" type="audio/x-m4a" length="13140224" />
<guid>http://mbird.com/podcastgen/media/Episode%2034%20-%20Duncan%20Burne-Wilke.m4a</guid>
<pubDate>Sat, 07 May 2011 21:51:30 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:26:32</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 44- The Razor&apos;s Edge</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is my favorite book.
It's also Bill Murray's.
It is called "The Razor's Edge" and was written by Somerset Maugham.
It was published in 1944.

It tells the story of some well-to-do Americans from Lake Forest,
who all find what they're looking for in life.

One of them, 'Larry Darrell', loses his life only to save it.
He is the hero, and I think he could be yours.

P.S. Who's "Ruysbroek"?]]>
</description>
<itunes:subtitle />
<itunes:summary>This is my favorite book.
    It&apos;s also Bill Murray&apos;s.
    It is called &quot;The Razor&apos;s Edge&quot; and was written by Somerset Maugham.
    It was published in 1944.

    It tells the story of some well-to-do Americans from Lake Forest,
    who all find what they&apos;re looking for in life.

    One of them, &apos;Larry Darrell&apos;, loses his life only to save it.
    He is the hero, and I think he could be yours.

    P.S. Who&apos;s &quot;Ruysbroek&quot;?</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2031%20-%20The%20Razor%27s%20Edge.m4a" type="audio/x-m4a" length="15397568" />
<guid>http://mbird.com/podcastgen/media/Episode%2031%20-%20The%20Razor%27s%20Edge.m4a</guid>
<pubDate>Sat, 30 Apr 2011 11:38:12 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:31:08</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 43 - &quot;The Green Pastures&quot;</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["The Green Pastures" is a 1930 American play, and 1936 Hollywood movie, that was once as famous as "Our Town".  Now, for reasons of political correctness, it is rarely seen and seldom taught.  Even the DVD has to carry a 'Warning' label.  (Good Grief!)
How dearly we have robbed ourselves of a pearl of truly great price.
Marc Connelly's "The Green Pastures" deals theatrically with the transition in the Bible from Law to Grace.  (It is not Marcionite!)
Has God's Mercy, in relation to God's Law, ever been staged like this?
I can't think of an example.
You've got to see "The Green Pastures".  The character 'Hezdrel', alone, will... blow... your... mind.]]>
</description>
<itunes:subtitle />
<itunes:summary>&quot;The Green Pastures&quot; is a 1930 American play, and 1936 Hollywood movie, that was once as famous as &quot;Our Town&quot;.  Now, for reasons of political correctness, it is rarely seen and seldom taught.  Even the DVD has to carry a &apos;Warning&apos; label.  (Good Grief!)
    How dearly we have robbed ourselves of a pearl of truly great price.
    Marc Connelly&apos;s &quot;The Green Pastures&quot; deals theatrically with the transition in the Bible from Law to Grace.  (It is not Marcionite!)
    Has God&apos;s Mercy, in relation to God&apos;s Law, ever been staged like this?
    I can&apos;t think of an example.
    You&apos;ve got to see &quot;The Green Pastures&quot;.  The character &apos;Hezdrel&apos;, alone, will... blow... your... mind.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2032%20-%20_The%20Green%20Pastures_%202.m4a" type="audio/x-m4a" length="16869504" />
<guid>http://mbird.com/podcastgen/media/Episode%2032%20-%20_The%20Green%20Pastures_%202.m4a</guid>
<pubDate>Sun, 17 Apr 2011 11:45:41 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:34:08</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 42 - Bishop Bell - The Play</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Bishop Bell appears as a main character in Rolf Hochhuth's 1967 play entitled "Soldiers".  Bell confronts Churchill on the morality of murder from the air, especially when it involves the murder of civilians.  Such a confrontation never actually took place, but the Bishop and the Prime Minister had the thoughts and stated them.  The PM detested Bell.
In Act Three of Hochhuth's play, Bell loses and Churchill wins.  In the moral balance, Churchill lost and Bell won.  "Soldiers" is a play about the massacre of this world that is repeatedly staged by Power.  As in the case of un-manned drone aircraft today.  Nobody seems to care.  Nobody 'gives'.   Yet one day...]]>
</description>
<itunes:subtitle />
<itunes:summary>Bishop Bell appears as a main character in Rolf Hochhuth&apos;s 1967 play entitled &quot;Soldiers&quot;.  Bell confronts Churchill on the morality of murder from the air, especially when it involves the murder of civilians.  Such a confrontation never actually took place, but the Bishop and the Prime Minister had the thoughts and stated them.  The PM detested Bell.
    In Act Three of Hochhuth&apos;s play, Bell loses and Churchill wins.  In the moral balance, Churchill lost and Bell won.  &quot;Soldiers&quot; is a play about the massacre of this world that is repeatedly staged by Power.  As in the case of un-manned drone aircraft today.  Nobody seems to care.  Nobody &apos;gives&apos;.   Yet one day...</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2042%20-%20Bishop%20Bell%20-%20The%20Play.m4a" type="audio/x-m4a" length="17311184" />
<guid>http://mbird.com/podcastgen/media/Episode%2042%20-%20Bishop%20Bell%20-%20The%20Play.m4a</guid>
<pubDate>Thu, 07 Apr 2011 22:10:38 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:35:02</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 41 - Bishop Bell - The Speech</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[George K.A. Bell (1883-1958) was the Bishop of Chichester during World War II.  He addressed the House of Lords on February 9, 1944, questioning the Government on the use of "carpet bombing" of German cities.  Bishop Bell regarded this kind of bombing, which was intended to destroy German morale and bring the war to an end, as a war crime.
At the time, Bell was the only person in Britain willing to say such a thing in a 'national' forum such as the Parliament.  He was attacked all across the board as being 'pro-German' and almost a traitor.  (He had, incidentally, been the first public  figure in the country to criticize Hitler's anti-semitic legislation.  He had done so in 1934.)
Because of his speech in the Lords,  Bishop Bell  lost all chance of promotion in the Church of England.
Today, however, he is almost canonized there, and certainly within the Church.
This podcast is about Bell's speech.  It also relates his theme to the current use of un-manned drone aircraft to commit targed assassination from the air -- or rather, from Las Vegas.]]>
</description>
<itunes:subtitle />
<itunes:summary>George K.A. Bell (1883-1958) was the Bishop of Chichester during World War II.  He addressed the House of Lords on February 9, 1944, questioning the Government on the use of &quot;carpet bombing&quot; of German cities.  Bishop Bell regarded this kind of bombing, which was intended to destroy German morale and bring the war to an end, as a war crime.
    At the time, Bell was the only person in Britain willing to say such a thing in a &apos;national&apos; forum such as the Parliament.  He was attacked all across the board as being &apos;pro-German&apos; and almost a traitor.  (He had, incidentally, been the first public  figure in the country to criticize Hitler&apos;s anti-semitic legislation.  He had done so in 1934.)
    Because of his speech in the Lords,  Bishop Bell  lost all chance of promotion in the Church of England.
    Today, however, he is almost canonized there, and certainly within the Church.
    This podcast is about Bell&apos;s speech.  It also relates his theme to the current use of un-manned drone aircraft to commit targed assassination from the air -- or rather, from Las Vegas.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2041%20-%20Bishop%20Bell%20-%20The%20Speech%202.m4a" type="audio/x-m4a" length="16673376" />
<guid>http://mbird.com/podcastgen/media/Episode%2041%20-%20Bishop%20Bell%20-%20The%20Speech%202.m4a</guid>
<pubDate>Sun, 27 Mar 2011 08:08:00 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:44</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 40 - &quot;No Popery&quot;</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Religious partisanship is normal, explicable, and terminal.
It kills Christianity. Itt sure killed me.
Or maybe it wised me up.
This podcast concerns Charles Dickens' novel "Barnaby Rudge", which was published in 1841.  Dickens' subject was the "No Popery" riots that took place in 1780 in London.  They are also known as the  "Gordon Riots".
Dickens used this astonishing episode to observe the causes of theological hatred, and its consequences.
Dickens was a conscious Protestant and heartfelt Christian,
but he was upset by religious malice.
"Barnaby Rudge" gets  to the bottom of it, in 661 pages.
This podcast gives you the Reader's Digest version in 36 minutes.]]>
</description>
<itunes:subtitle />
<itunes:summary>Religious partisanship is normal, explicable, and terminal.
    It kills Christianity. Itt sure killed me.
    Or maybe it wised me up.
    This podcast concerns Charles Dickens&apos; novel &quot;Barnaby Rudge&quot;, which was published in 1841.  Dickens&apos; subject was the &quot;No Popery&quot; riots that took place in 1780 in London.  They are also known as the  &quot;Gordon Riots&quot;.
    Dickens used this astonishing episode to observe the causes of theological hatred, and its consequences.
    Dickens was a conscious Protestant and heartfelt Christian,
    but he was upset by religious malice.
    &quot;Barnaby Rudge&quot; gets  to the bottom of it, in 661 pages.
    This podcast gives you the Reader&apos;s Digest version in 36 minutes.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2039%20-%20_No%20Popery_.m4a" type="audio/x-m4a" length="17785616" />
<guid>http://mbird.com/podcastgen/media/Episode%2039%20-%20_No%20Popery_.m4a</guid>
<pubDate>Sat, 19 Mar 2011 16:26:05 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:36:00</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 39 - The Phoenix Club</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Life in a Final Club!
"The Social Network" has made it high profile all of a sudden.
What it was, was fun, dellightful, blessedly un-serious in a way serious world,
with a  taste of Evelyn Waugh.
We loved it.
Why was the story never told?
That's a story.
Podcast 39 is published  in loving memory of
Page Farnsworth Grubb, '71.]]>
</description>
<itunes:subtitle />
<itunes:summary>Life in a Final Club!
    &quot;The Social Network&quot; has made it high profile all of a sudden.
    What it was, was fun, dellightful, blessedly un-serious in a way serious world,
    with a  taste of Evelyn Waugh.
    We loved it.
    Why was the story never told?
    That&apos;s a story.
    Podcast 39 is published  in loving memory of
    Page Farnsworth Grubb, &apos;71.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2040%20-%20The%20Phoenix%20Club.m4a" type="audio/x-m4a" length="23837344" />
<guid>http://mbird.com/podcastgen/media/Episode%2040%20-%20The%20Phoenix%20Club.m4a</guid>
<pubDate>Sun, 13 Mar 2011 07:04:08 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:48:20</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 37- The Yardbirds</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is an impression of The Yardbirds,
the first avant-garde band we ever knew.
With Eric Clapton to start, then Jeff Beck,
then Jeff Beck and Jimmy Page, then
Jimmy Page only, their music, especially the guitar breaks,
lived on the edge  of INSANITY.
To this day, I still have Yardbirds days. They are wonderful.
There was also a personal Close Encounter, with Friends.
In this podcast I tell a story and try to give an impression,
followed by a few, well, theological comments.
Podcast 37 is dedicated to William Cox Bowman.
]]>
</description>
<itunes:subtitle />
<itunes:summary>This is an impression of The Yardbirds,
    the first avant-garde band we ever knew.
    With Eric Clapton to start, then Jeff Beck,
    then Jeff Beck and Jimmy Page, then
    Jimmy Page only, their music, especially the guitar breaks,
    lived on the edge  of INSANITY.
    To this day, I still have Yardbirds days. They are wonderful.
    There was also a personal Close Encounter, with Friends.
    In this podcast I tell a story and try to give an impression,
    followed by a few, well, theological comments.
    Podcast 37 is dedicated to William Cox Bowman.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2047%20-%20The%20Yardbirds%203.m4a" type="audio/x-m4a" length="16477024" />
<guid>http://mbird.com/podcastgen/media/Episode%2047%20-%20The%20Yardbirds%203.m4a</guid>
<pubDate>Sun, 27 Feb 2011 06:32:02 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:20</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 33 - &quot;Mr.&quot; Priest</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This podcast is about professional titles:
the more reduced in circumstances an institution,
the more high-flown its titles.
Did you know that until about 1970 Episcopal clergy were always called
'Mr." ?  (They were never called 'Father', except in one parish, max two,
per city.)  The later Cardinal Newman was 'Mr. Newman', and Edward Bouverie Pusey was 'Mr. Pusey'.
But don't take my word for it.  Read W. M. Thackerey, read E.M. Forster.  See 'Showboat', the 1936 version.
An interesting principle seems to be at work:  when things are going great, the leader is just a regular person, like everybody else.  He's 'Mr. Irwine', as in Eliot's "Adam Bede".
But when things begin to go south, and the world gets against  you, the leader becomes:  The Most Metropolitical and Right Honorable Dr. of Sacred Letters Obadiah Slope.
Anyway, I'd sure rather be Mr. Midshipman Easy!
Listen to this, and you may want to work for the car wash down the street.
Oh, and no one will believe you anyway.
Maybe if you tell 'em, Father Paul told you.]]>
</description>
<itunes:subtitle />
<itunes:summary>This podcast is about professional titles:
    the more reduced in circumstances an institution,
    the more high-flown its titles.
    Did you know that until about 1970 Episcopal clergy were always called
    &apos;Mr.&quot; ?  (They were never called &apos;Father&apos;, except in one parish, max two,
    per city.)  The later Cardinal Newman was &apos;Mr. Newman&apos;, and Edward Bouverie Pusey was &apos;Mr. Pusey&apos;.
    But don&apos;t take my word for it.  Read W. M. Thackerey, read E.M. Forster.  See &apos;Showboat&apos;, the 1936 version.
    An interesting principle seems to be at work:  when things are going great, the leader is just a regular person, like everybody else.  He&apos;s &apos;Mr. Irwine&apos;, as in Eliot&apos;s &quot;Adam Bede&quot;.
    But when things begin to go south, and the world gets against  you, the leader becomes:  The Most Metropolitical and Right Honorable Dr. of Sacred Letters Obadiah Slope.
    Anyway, I&apos;d sure rather be Mr. Midshipman Easy!
    Listen to this, and you may want to work for the car wash down the street.
    Oh, and no one will believe you anyway.
    Maybe if you tell &apos;em, Father Paul told you.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2042%20-%20_Mr._%20Priest.m4a" type="audio/x-m4a" length="15773440" />
<guid>http://mbird.com/podcastgen/media/Episode%2042%20-%20_Mr._%20Priest.m4a</guid>
<pubDate>Sun, 13 Feb 2011 09:26:21 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:31:54</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 32 - Protestant Interiors II</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Here's a little gazetteer of Episcopal Protestant interiors.
They're nice.
Delaware's is in the middle of nowhere, and Boston's finest is Unitarian.
George Washington sat beneath a central pulpit in Alexandria and "Low Country'" farmers did the same.  And don't forget the Motor City: I mean,
Duanesburg, New York.  But always remember this -- even if you are actually able to visit these places, no one will ever believe you when you get back home.
They simply CAN'T exist!]]>
</description>
<itunes:subtitle />
<itunes:summary>Here&apos;s a little gazetteer of Episcopal Protestant interiors.
    They&apos;re nice.
    Delaware&apos;s is in the middle of nowhere, and Boston&apos;s finest is Unitarian.
    George Washington sat beneath a central pulpit in Alexandria and &quot;Low Country&apos;&quot; farmers did the same.  And don&apos;t forget the Motor City: I mean,
    Duanesburg, New York.  But always remember this -- even if you are actually able to visit these places, no one will ever believe you when you get back home.
    They simply CAN&apos;T exist!</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2039%20-%20Protestant%20Interiors%20II%202.m4a" type="audio/x-m4a" length="16444560" />
<guid>http://mbird.com/podcastgen/media/Episode%2039%20-%20Protestant%20Interiors%20II%202.m4a</guid>
<pubDate>Tue, 08 Feb 2011 16:33:47 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:16</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 31 - Protestant Interiors</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This one is about Protestant aesthetics
as expressed in architecture and design.
It is 'a tale told by an idiot',  however, for no one ever believes you.
Only Henny Penny says the Episcopal Church
was once Protestant and 'Low'  -- right  up to the Disco Era.
Memory being what it is, this is the tale of a forgotten 200 years.

The Song Remains the Same in about 200 precious survivals in
England, as well as 50 or so on the East Coast of the U.S.A.
There, the glass is clear; the design, simple; and the message, unmediated.
There, less is more.


]]>
</description>
<itunes:subtitle />
<itunes:summary>This one is about Protestant aesthetics
    as expressed in architecture and design.
    It is &apos;a tale told by an idiot&apos;,  however, for no one ever believes you.
    Only Henny Penny says the Episcopal Church
    was once Protestant and &apos;Low&apos;  -- right  up to the Disco Era.
    Memory being what it is, this is the tale of a forgotten 200 years.

    The Song Remains the Same in about 200 precious survivals in
    England, as well as 50 or so on the East Coast of the U.S.A.
    There, the glass is clear; the design, simple; and the message, unmediated.
    There, less is more.


</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2037%20-%20Protestant%20Interiors.m4a" type="audio/x-m4a" length="18079952" />
<guid>http://mbird.com/podcastgen/media/Episode%2037%20-%20Protestant%20Interiors.m4a</guid>
<pubDate>Sun, 06 Feb 2011 08:11:49 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:36:36</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 30 - Shock Theater</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Late Saturday nights was a time for little boys to howl.
"Shock Theater" came on around one!
We learned every line of the 'original' "Dracula" (1931), memorized every release date of every Mummy movie from 1932 to 1945, and, most important, got married for life to:  "The Bride of Frankenstein".
This is the story of those late Saturday nights, which gave our mothers such trouble, since it was they who would have to ...  wake us up for church.]]>
</description>
<itunes:subtitle />
<itunes:summary>Late Saturday nights was a time for little boys to howl.
    &quot;Shock Theater&quot; came on around one!
    We learned every line of the &apos;original&apos; &quot;Dracula&quot; (1931), memorized every release date of every Mummy movie from 1932 to 1945, and, most important, got married for life to:  &quot;The Bride of Frankenstein&quot;.
    This is the story of those late Saturday nights, which gave our mothers such trouble, since it was they who would have to ...  wake us up for church.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2030%20-%20Shock%20Theater.m4a" type="audio/x-m4a" length="15266656" />
<guid>http://mbird.com/podcastgen/media/Episode%2030%20-%20Shock%20Theater.m4a</guid>
<pubDate>Wed, 02 Feb 2011 07:02:07 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:52</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 29 - The Circle</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[The Circle was a movie theater in downtown Washington where two boys discovered foreign film.
Boris Karloff and James Whale became superceded by Sergei Eisenstein and Francois Truffaut.
Or mostly. (We were only 13 years old, for crying out loud.)
This podcast tells our Tales from the Circle.  Every word is true.
It is Part III of The Moviegoer and is dedicated to Lloyd Fonvielle.
]]>
</description>
<itunes:subtitle />
<itunes:summary>The Circle was a movie theater in downtown Washington where two boys discovered foreign film.
    Boris Karloff and James Whale became superceded by Sergei Eisenstein and Francois Truffaut.
    Or mostly. (We were only 13 years old, for crying out loud.)
    This podcast tells our Tales from the Circle.  Every word is true.
    It is Part III of The Moviegoer and is dedicated to Lloyd Fonvielle.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2029%20-%20The%20Circle.m4a" type="audio/x-m4a" length="15004848" />
<guid>http://mbird.com/podcastgen/media/Episode%2029%20-%20The%20Circle.m4a</guid>
<pubDate>Sun, 30 Jan 2011 07:48:29 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:20</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 28 - Premature Burial</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Part II of The Moviegoer, in which our ten-year-old hero discovers
Edgar Allan Poe via Roger Corman in the downtown movie palaces of
Loew's Capital, Loew's Palace, and R.K.O. Keith's.
He comes face to face with a strange new Glynis Johns before encountering "The Vampire and the Ballerina" exactly one block  from the White House.
]]>
</description>
<itunes:subtitle />
<itunes:summary>Part II of The Moviegoer, in which our ten-year-old hero discovers
    Edgar Allan Poe via Roger Corman in the downtown movie palaces of
    Loew&apos;s Capital, Loew&apos;s Palace, and R.K.O. Keith&apos;s.
    He comes face to face with a strange new Glynis Johns before encountering &quot;The Vampire and the Ballerina&quot; exactly one block  from the White House.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2028%20-%20Premature%20Burial.m4a" type="audio/x-m4a" length="14661536" />
<guid>http://mbird.com/podcastgen/media/Episode%2028%20-%20Premature%20Burial.m4a</guid>
<pubDate>Wed, 26 Jan 2011 17:27:05 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:29:38</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 27 - The Crawling Eye</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[This is the story of a conversion.
It happened in the Fall of 1959,
and I've never looked back.
It happened in connection with some mountaineering in the Swiss Alps.
Like the man in "The Crawling Eye",
I lost my head.
Still haven't found it.
]]>
</description>
<itunes:subtitle />
<itunes:summary>This is the story of a conversion.
    It happened in the Fall of 1959,
    and I&apos;ve never looked back.
    It happened in connection with some mountaineering in the Swiss Alps.
    Like the man in &quot;The Crawling Eye&quot;,
    I lost my head.
    Still haven&apos;t found it.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2027%20-%20The%20Crawling%20Eye.m4a" type="audio/x-m4a" length="15103072" />
<guid>http://mbird.com/podcastgen/media/Episode%2027%20-%20The%20Crawling%20Eye.m4a</guid>
<pubDate>Sun, 23 Jan 2011 12:10:23 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:30:32</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 26 - P.E. II</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[We're not finished yet.
Cozzens cuts to the core of Anglo-Catholicism
yet without throwing stones.
He wants to understand.
And his account of a hijacked P.E. funeral in "Eyes to See"
is so close to home, well,
that it makes you want to scream.
]]>
</description>
<itunes:subtitle />
<itunes:summary>We&apos;re not finished yet.
    Cozzens cuts to the core of Anglo-Catholicism
    yet without throwing stones.
    He wants to understand.
    And his account of a hijacked P.E. funeral in &quot;Eyes to See&quot;
    is so close to home, well,
    that it makes you want to scream.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2026%20-%20P.E.%20II.m4a" type="audio/x-m4a" length="14464864" />
<guid>http://mbird.com/podcastgen/media/Episode%2026%20-%20P.E.%20II.m4a</guid>
<pubDate>Wed, 19 Jan 2011 10:34:39 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:29:14</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 25 - P.E.</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["P.E." is for Protestant Episcopal.
35 years I've been ordained and it took Cozzens to teach me some sore lessons.
For me they came late.
But,  "For you the living/This Mash was meant, too."
"When you get to my house,
Tell them 'Jimmy' sent you."]]>
</description>
<itunes:subtitle>P.E. is for &quot;Protestant Episcopal&quot;.  35 years ordained and I never learned these things.  James Gould Cozzens could have taught me.  He knew.  Are we too late? </itunes:subtitle>
<itunes:summary>&quot;P.E.&quot; is for Protestant Episcopal.
    35 years I&apos;ve been ordained and it took Cozzens to teach me some sore lessons.
    For me they came late.
    But,  &quot;For you the living/This Mash was meant, too.&quot;
    &quot;When you get to my house,
    Tell them &apos;Jimmy&apos; sent you.&quot;</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2025%20-%20P.E..m4a" type="audio/x-m4a" length="13859760" />
<guid>http://mbird.com/podcastgen/media/Episode%2025%20-%20P.E..m4a</guid>
<pubDate>Sat, 15 Jan 2011 05:23:04 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:28:00</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 22 - Journey</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[What's the greatest rock 'n roll band of all time?
Could a group sum up everything that has gone before
and thus WRAP the genre?
Yes, it could.  They did.
Their name was "Journey".
But Wait!  Hear me out.]]>
</description>
<itunes:subtitle>What&apos;s the greatest rock &apos;n roll group of all time?  What band sums it all up, such that nothing more can be said?:                                                    Journey.  The band&apos;s name is Journey.                             But wait, hear me out!</itunes:subtitle>
<itunes:summary>What&apos;s the greatest rock &apos;n roll band of all time?
    Could a group sum up everything that has gone before
    and thus WRAP the genre?
    Yes, it could.  They did.
    Their name was &quot;Journey&quot;.
    But Wait!  Hear me out.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2022%20-%20Journey.m4a" type="audio/x-m4a" length="15561040" />
<guid>http://mbird.com/podcastgen/media/Episode%2022%20-%20Journey.m4a</guid>
<pubDate>Sun, 02 Jan 2011 09:26:50 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:31:28</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 21 - Plymouth Adventure</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Dan Curtis went straight from Gothic Horror soap operas to the greatest epic ever made for television.  His heart was always in his work,  from "Dark Shadows" to "The Night Stalker" to... "The Winds of War".  When it comes to his 29-hour genius production "War and Remembrance,  Can't Touch This!
Here is the story of an undepressed man.]]>
</description>
<itunes:subtitle>Dan Curtis went straight from Gothic Horror soap operas to the greatest epic in television history.  He was a pure popular artist, who simply loved what he was doing.  This is the story of an undepressed man.  </itunes:subtitle>
<itunes:summary>Dan Curtis went straight from Gothic Horror soap operas to the greatest epic ever made for television.  His heart was always in his work,  from &quot;Dark Shadows&quot; to &quot;The Night Stalker&quot; to... &quot;The Winds of War&quot;.  When it comes to his 29-hour genius production &quot;War and Remembrance,  Can&apos;t Touch This!
    Here is the story of an undepressed man.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2021%20-%20Plymouth%20Adventure.m4a" type="audio/x-m4a" length="16051728" />
<guid>http://mbird.com/podcastgen/media/Episode%2021%20-%20Plymouth%20Adventure.m4a</guid>
<pubDate>Sat, 13 Nov 2010 13:20:59 -0500</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:32:28</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 20 - I Learned to Yodel</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Did you know meditation can make you a better Protestant?
Here's why.]]>
</description>
<itunes:subtitle>Did you know meditation can make you a better Protestant?  Here&apos;s why.</itunes:subtitle>
<itunes:summary>Did you know meditation can make you a better Protestant?
    Here&apos;s why.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Episode%2020%20-%20I%20Learned%20to%20Yodel.m4a" type="audio/x-m4a" length="16363824" />
<guid>http://mbird.com/podcastgen/media/Episode%2020%20-%20I%20Learned%20to%20Yodel.m4a</guid>
<pubDate>Tue, 26 Oct 2010 10:01:04 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:06</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 19: The Gothic</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[New thoughts on 'the Gothic' in movies and literature -- from Irvin S. Cobb, whose Gothic story "Fishhead" was termed a "banefully effective tale" by H. P. Lovecraft; to Ray Russell, of "Sardonicus" fame; to Roger Corman, who brought the House down around Roderick Usher.
Turns out it's all about bodily disintegration in an enclosed space, and the dead hand of the past  upon the hopes of the present.
The Gothic becomes a fascinating study in the quest for bookings on the Last Metro.]]>
</description>
<itunes:subtitle>The boys in the basement turn out to be... a dream.</itunes:subtitle>
<itunes:summary>New thoughts on &apos;the Gothic&apos; in movies and literature -- from Irvin S. Cobb, whose Gothic story &quot;Fishhead&quot; was termed a &quot;banefully effective tale&quot; by H. P. Lovecraft; to Ray Russell, of &quot;Sardonicus&quot; fame; to Roger Corman, who brought the House down around Roderick Usher.
    Turns out it&apos;s all about bodily disintegration in an enclosed space, and the dead hand of the past  upon the hopes of the present.
    The Gothic becomes a fascinating study in the quest for bookings on the Last Metro.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%2019%20-%20Final%20Take.m4a" type="audio/x-m4a" length="17032912" />
<guid>http://mbird.com/podcastgen/media/Podcast%2019%20-%20Final%20Take.m4a</guid>
<pubDate>Wed, 20 Oct 2010 10:38:37 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:34:28</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 17: The Hammer and the Cross</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Hammer Horror is a beautiful thing -- everything movies should be, or almost everything.
There is also this delightful religious dimension, in which the High Priest of Karnak prays in the language of  the Book of Common Prayer and Peter Cushing is 'fighting evil every bit as much' as a Church of England entymologist/bishop in "Hound of the Baskervilles".
Here is my little 'National Geographic Society lecture', on one of the nicest acres of filmdom and fandom.  It was recorded at Constitution Hall in our Nation's Capital.
]]>
</description>
<itunes:subtitle>A National Geographic Society Lecture on Hammer Horror</itunes:subtitle>
<itunes:summary>Hammer Horror is a beautiful thing -- everything movies should be, or almost everything.
    There is also this delightful religious dimension, in which the High Priest of Karnak prays in the language of  the Book of Common Prayer and Peter Cushing is &apos;fighting evil every bit as much&apos; as a Church of England entymologist/bishop in &quot;Hound of the Baskervilles&quot;.
    Here is my little &apos;National Geographic Society lecture&apos;, on one of the nicest acres of filmdom and fandom.  It was recorded at Constitution Hall in our Nation&apos;s Capital.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%2022.m4a" type="audio/x-m4a" length="18184672" />
<guid>http://mbird.com/podcastgen/media/Podcast%2022.m4a</guid>
<pubDate>Wed, 29 Sep 2010 18:33:00 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:36:48</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 16: Irvin S. Cobb</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Irvin S. Cobb (1876-1944) was famous in his day, but is unread now.
Ours is the loss!  His "Judge Priest" stories are as parabolic of grace as it gets.  They exude peace, love, and understanding.  And what's so funny about that?
Here's your chance to bone up on Irvin S. Cobb!
By the way,  John Ford liked Cobb so much that
he made two movies out of his stories, and then put him in a third.
In 1961 Ford made a personal pilgrimage to Cobb's grave at Paducah, Kentucky.
Two weeks from tomorrow I hope to do the same.

]]>
</description>
<itunes:subtitle>Another unread author: But Wait!  Hear me out.</itunes:subtitle>
<itunes:summary>Irvin S. Cobb (1876-1944) was famous in his day, but is unread now.
    Ours is the loss!  His &quot;Judge Priest&quot; stories are as parabolic of grace as it gets.  They exude peace, love, and understanding.  And what&apos;s so funny about that?
    Here&apos;s your chance to bone up on Irvin S. Cobb!
    By the way,  John Ford liked Cobb so much that
    he made two movies out of his stories, and then put him in a third.
    In 1961 Ford made a personal pilgrimage to Cobb&apos;s grave at Paducah, Kentucky.
    Two weeks from tomorrow I hope to do the same.

</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%2024.m4a" type="audio/x-m4a" length="15663280" />
<guid>http://mbird.com/podcastgen/media/Podcast%2024.m4a</guid>
<pubDate>Wed, 22 Sep 2010 18:00:39 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:31:40</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 15: Hot August Night</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[The Jansenists never declined.
They got wiped out good.
Think "End of the Line" by the Traveling Wilburys.
Pascal enters and exits, assisted by Roberto Rossellini's tv show (1971) and Jack Kerouac's bar game (1969).
The 'Sun King' plays his cruel part, while Our Ladies of Port-Royal
hold the line.
They really hold the line!
As Sainte-Beuve wrote of the Jansenists,
"They were from Calvary".]]>
</description>
<itunes:subtitle>But 1664, not 1969! -- Part Two on the Jansenists.</itunes:subtitle>
<itunes:summary>The Jansenists never declined.
    They got wiped out good.
    Think &quot;End of the Line&quot; by the Traveling Wilburys.
    Pascal enters and exits, assisted by Roberto Rossellini&apos;s tv show (1971) and Jack Kerouac&apos;s bar game (1969).
    The &apos;Sun King&apos; plays his cruel part, while Our Ladies of Port-Royal
    hold the line.
    They really hold the line!
    As Sainte-Beuve wrote of the Jansenists,
    &quot;They were from Calvary&quot;.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%2019%203.m4a" type="audio/x-m4a" length="17279648" />
<guid>http://mbird.com/podcastgen/media/Podcast%2019%203.m4a</guid>
<pubDate>Thu, 16 Sep 2010 06:56:08 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:34:58</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode 14: Paris When It Sizzles</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Jansenism was a religious movement in Seventeenth-Century France that threatened Church and State.  Its apologists, including Blaise Pascal and Jean Racine, thought their movement, based on its re-discovery of the teachings of St. Augustine,  could save Christianity from the Protestants.
Its detractors thought Jansenism WAS Protestantism, but a Fifth Column of it, burrowing away within the Catholic Church.  The two positions were irreconcilable.
The Jansenists lost, and lost catastrophically.
What an interesting lesson here in 'Church', and State.]]>
</description>
<itunes:subtitle>Jansenism: the fourth most interesting thing ever to happen in the history of Christianity.  What was Pascal thinking? Et Racine?</itunes:subtitle>
<itunes:summary>Jansenism was a religious movement in Seventeenth-Century France that threatened Church and State.  Its apologists, including Blaise Pascal and Jean Racine, thought their movement, based on its re-discovery of the teachings of St. Augustine,  could save Christianity from the Protestants.
    Its detractors thought Jansenism WAS Protestantism, but a Fifth Column of it, burrowing away within the Catholic Church.  The two positions were irreconcilable.
    The Jansenists lost, and lost catastrophically.
    What an interesting lesson here in &apos;Church&apos;, and State.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%2018.m4a" type="audio/x-m4a" length="16123904" />
<guid>http://mbird.com/podcastgen/media/Podcast%2018.m4a</guid>
<pubDate>Thu, 09 Sep 2010 08:03:35 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:32:36</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Sneak Peek: &quot;By Love Possessed&quot;</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["By Love Possessed" was hailed at first as the great novel of its decade.  A few months later it was traduced as a symbol of Eisenhower-era 'middle-brow' complacency.  The second verdict stuck.
The problem was its message: it praised acquiescence rather than transformation.  It is indeed a 'novel of resignation'.  Is that a good thing?]]>
</description>
<itunes:subtitle>This 1957 novel is among the unsung greats.  It is sometimes called a &apos;novel of resignation&apos;.  Is that a good thing?</itunes:subtitle>
<itunes:summary>&quot;By Love Possessed&quot; was hailed at first as the great novel of its decade.  A few months later it was traduced as a symbol of Eisenhower-era &apos;middle-brow&apos; complacency.  The second verdict stuck.
    The problem was its message: it praised acquiescence rather than transformation.  It is indeed a &apos;novel of resignation&apos;.  Is that a good thing?</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%20Eight%203.m4a" type="audio/x-m4a" length="15952224" />
<guid>http://mbird.com/podcastgen/media/Podcast%20Eight%203.m4a</guid>
<pubDate>Sun, 29 Aug 2010 08:58:41 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:32:15</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Sunday Supplement: The Life of James Gould Cozzens</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[James Gould Cozzens (1903-1978) observed life accurately.  in 1957 he told 'Time' Magazine that "most people get a raw deal from life, and life is what it is".  His novels "By Love Possessed" and "Guard of Honor" are among the greatest of 20th Century novels.

]]>
</description>
<itunes:subtitle>A fascinating literary life, the story of a man who knew a great deal and wrote it all down.</itunes:subtitle>
<itunes:summary>James Gould Cozzens (1903-1978) observed life accurately.  in 1957 he told &apos;Time&apos; Magazine that &quot;most people get a raw deal from life, and life is what it is&quot;.  His novels &quot;By Love Possessed&quot; and &quot;Guard of Honor&quot; are among the greatest of 20th Century novels.

</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%20Seven%203.m4a" type="audio/x-m4a" length="18058112" />
<guid>http://mbird.com/podcastgen/media/Podcast%20Seven%203.m4a</guid>
<pubDate>Sun, 29 Aug 2010 08:42:03 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:36:33</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>BONUS Episode!:Giant Crab Movies</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[In this amazing weekend bonus episode, our hero must claw his way through the history of giant-crab movies.  Does he survive?  You be the judge!]]>
</description>
<itunes:subtitle>In this amazing bonus episode, our hero claws his way through the history of giant-crab movies.</itunes:subtitle>
<itunes:summary>In this amazing weekend bonus episode, our hero must claw his way through the history of giant-crab movies.  Does he survive?  You be the judge!</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%2011.m4a" type="audio/x-m4a" length="16718304" />
<guid>http://mbird.com/podcastgen/media/Podcast%2011.m4a</guid>
<pubDate>Fri, 20 Aug 2010 09:05:54 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:33:49</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode Seven - &quot;Man Gave Names to all the Animals&quot; </title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA["Man Gave Names to all the Animals" (Bob Dylan),
meaning
Eric Burdon and The Animals.

Thoughts on true greatness, thoughts on Fun.]]>
</description>
<itunes:subtitle>&quot;Man Gave Names to all the Animals&quot; (Bob Dylan).  And he named them Eric Burdon, Alan Price, Chas Chandler, Hilton Valentine, and John Steel.</itunes:subtitle>
<itunes:summary>&quot;Man Gave Names to all the Animals&quot; (Bob Dylan),
    meaning
    Eric Burdon and The Animals.

    Thoughts on true greatness, thoughts on Fun.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%2013.m4a" type="audio/x-m4a" length="22450112" />
<guid>http://mbird.com/podcastgen/media/Podcast%2013.m4a</guid>
<pubDate>Wed, 18 Aug 2010 08:39:54 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:45:30</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode Six - The Browning Version</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[From a perfect movie comes a Version of the 25th Chorus of "Mexico City Blues":
Is my own, is your own,
Is not Owned by Self-Owner
but found by Self-Loser --
Old Ancient Teaching".

This podcast is dedicated to David Browder.
]]>
</description>
<itunes:subtitle>&quot;Old Ancient Teaching&quot;.  Dedicated to David Browder.</itunes:subtitle>
<itunes:summary>From a perfect movie comes a Version of the 25th Chorus of &quot;Mexico City Blues&quot;:
    Is my own, is your own,
    Is not Owned by Self-Owner
    but found by Self-Loser --
    Old Ancient Teaching&quot;.

    This podcast is dedicated to David Browder.
</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%20Ten.m4a" type="audio/x-m4a" length="18782144" />
<guid>http://mbird.com/podcastgen/media/Podcast%20Ten.m4a</guid>
<pubDate>Wed, 18 Aug 2010 08:27:08 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:38:01</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Bohemian Rhapsody -- The Rite One</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[The subject is preaching, the Achilles Heel of American religion.  We turn to Jack Kerouac's "List of Essentials" in spontaneous expression for help.  Turns out it's the singer not the song.]]>
</description>
<itunes:subtitle>The subject is preaching, an Achilles Heel in American religion.  I turn to Jack Kerouac for some help.</itunes:subtitle>
<itunes:summary>The subject is preaching, the Achilles Heel of American religion.  We turn to Jack Kerouac&apos;s &quot;List of Essentials&quot; in spontaneous expression for help.  Turns out it&apos;s the singer not the song.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%20Five%202_2.m4a" type="audio/x-m4a" length="18337392" />
<guid>http://mbird.com/podcastgen/media/Podcast%20Five%202_2.m4a</guid>
<pubDate>Tue, 10 Aug 2010 12:55:01 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:37:07</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Beatnik Beach</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[The title of a song by the Go-Go's sets the stage for this second cast on the preaching art.  Once again, it's the singer not the song.  Or at least, that's where we start.
Welcome to Beatnik Beach!]]>
</description>
<itunes:subtitle>The title of a song by the Go-Go&apos;s sets the stage for part two on preaching.  Welcome to Beatnik Beach!</itunes:subtitle>
<itunes:summary>The title of a song by the Go-Go&apos;s sets the stage for this second cast on the preaching art.  Once again, it&apos;s the singer not the song.  Or at least, that&apos;s where we start.
    Welcome to Beatnik Beach!</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%20Six%202.m4a" type="audio/x-m4a" length="18667392" />
<guid>http://mbird.com/podcastgen/media/Podcast%20Six%202.m4a</guid>
<pubDate>Tue, 10 Aug 2010 12:27:06 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:37:47</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode One - What&apos;s it all about, Alfie?</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[In which our hero introduces you to his search.  "For you the living, this Mash was meant, too."]]>
</description>
<itunes:subtitle>In which our hero introduces you to his search.  &quot;For you the living, this Mash was meant, too.&quot;</itunes:subtitle>
<itunes:summary>In which our hero introduces you to his search.  &quot;For you the living, this Mash was meant, too.&quot;</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%20Nine%202.m4a" type="audio/x-m4a" length="20658608" />
<guid>http://mbird.com/podcastgen/media/Podcast%20Nine%202.m4a</guid>
<pubDate>Wed, 04 Aug 2010 09:59:40 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:41:51</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode Two - The Alcestiad, Act One</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Our hero, incarnated as an ancient Greek princess, finds Love and Happiness, Thornton-Wilder style.]]>
</description>
<itunes:subtitle>Our hero, incarnated as an ancient Greek princess, finds Love and Happiness, Thornton-Wilder style.</itunes:subtitle>
<itunes:summary>Our hero, incarnated as an ancient Greek princess, finds Love and Happiness, Thornton-Wilder style.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%20Three%203.m4a" type="audio/x-m4a" length="18533488" />
<guid>http://mbird.com/podcastgen/media/Podcast%20Three%203.m4a</guid>
<pubDate>Wed, 04 Aug 2010 09:58:46 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:37:31</itunes:duration>
<itunes:keywords />
</item>
<item>
<title>Episode Three - The Alcestiad, Act Three</title>
<itunes:author>Paul Zahl</itunes:author>
<description>
    <![CDATA[Our hero, again incarnated as the Queen of Thessaly, heads south, only to still find happiness.]]>
</description>
<itunes:subtitle>Our hero, again incarnated as the Queen of Thessaly, heads south, only to still find happiness.</itunes:subtitle>
<itunes:summary>Our hero, again incarnated as the Queen of Thessaly, heads south, only to still find happiness.</itunes:summary>
<enclosure url="http://mbird.com/podcastgen/media/Podcast%20Four%203.m4a" type="audio/x-m4a" length="19280560" />
<guid>http://mbird.com/podcastgen/media/Podcast%20Four%203.m4a</guid>
<pubDate>Wed, 04 Aug 2010 09:57:57 -0400</pubDate>
<category>Christianity</category>
<itunes:explicit>no</itunes:explicit>
<itunes:duration>00:39:02</itunes:duration>
<itunes:keywords />
</item>

        <item>
            <title>Episode 209 - How To Be Popular If You're a Guy</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[The answer to that question has to lie, somehow, in whatever explains
the popular success of Rodney Marvin ('Rod') McKuen.

Rod McKuen died a year ago, and did you know he sold 100 million records?  No kidding.  Rod McKuen sold 100 million records.

(He also sold 60 million books.  But hey...) Here is a man who was universally dismissed, from day one of his earthly success, as being a "kitschy" Philistine and arch-sentimentalist.  No critic had a word of praise for him.  Ever, ever, ever.  And that's been true right up to the present day.

And let the People say: He sold a hundred million records.

Here is a writer who when you actually take time to listen to his songs
wrote from a fetal position of complete understanding,.. of me and you.

Now let the People say: He sold a hundred million records.

Here is a man who, like Rudyard Kipling -- if it weren't for T.S. Eliot and George Orwell --
would have no friends in "high places" (at least after he died).
People loved his work. Critics hated it.

Therefore let the People say: He sold a hundred million records.

So listen up, guys:
If you want to be popular, then say what you think, say it deep, say it real, and speak it... from the earthed position of the earliest human child.]]>
            </description>
            <itunes:subtitle>The answer to that question has to lie, somehow, in whatever explains
the popular success of Rodney Marvin ('Rod') McKuen.

Rod McKuen died a year ago, and did you know he sold 100 million records?  No kidding.  Rod McKuen sold 100 million records.

(He also sold 60 million books.  But hey...)

Here is a man who was universally dismissed, from day one of his earthly success, as being a "kitschy" Philistine and arch-sentimentalist.  No critic had a word of praise for him.  Ever, ever, ever.  And that's been true right up to the present day.

And let the People say: He sold a hundred million records.

Here is a writer who when you actually take time to listen to his songs
wrote from a fetal position of complete understanding,.. of me and you.

Now let the People say: He sold a hundred million records.

Here is a man who, like Rudyard Kipling -- if it weren't for T.S. Eliot and George Orwell --
would have no friends in "high places" (at least after he died).
People loved his work. Critics hated it.

Therefore let the People say: He sold a hundred million records.

So listen up, guys:
If you want to be popular, then say what you think, say it deep, say it real, and speak it... from the earthed position of the earliest human child.</itunes:subtitle>
            <itunes:summary>The answer to that question has to lie, somehow, in whatever explains
the popular success of Rodney Marvin ('Rod') McKuen.

Rod McKuen died a year ago, and did you know he sold 100 million records?  No kidding.  Rod McKuen sold 100 million records.

(He also sold 60 million books.  But hey...)

Here is a man who was universally dismissed, from day one of his earthly success, as being a "kitschy" Philistine and arch-sentimentalist.  No critic had a word of praise for him.  Ever, ever, ever.  And that's been true right up to the present day.

And let the People say: He sold a hundred million records.

Here is a writer who when you actually take time to listen to his songs
wrote from a fetal position of complete understanding,.. of me and you.

Now let the People say: He sold a hundred million records.

Here is a man who, like Rudyard Kipling -- if it weren't for T.S. Eliot and George Orwell --
would have no friends in "high places" (at least after he died).
People loved his work. Critics hated it.

Therefore let the People say: He sold a hundred million records.

So listen up, guys:
If you want to be popular, then say what you think, say it deep, say it real, and speak it... from the earthed position of the earliest human child.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20209%20-%20How%20To%20Be%20Popular%20If%20You%27re%20a%20Guy.m4a" type="audio/x-m4a" length="24620739" />
            <guid>http://mbird.com/podcastgen/media/Episode%20209%20-%20How%20To%20Be%20Popular%20If%20You%27re%20a%20Guy.m4a</guid>
            <pubDate>Mon, 25 January 2016 12:09:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>25:16</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 208 - Five O'Clock World</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Now we think that reality, the "real world", is what happens "between nine and five", that is, what happens at work, in the office, at school, in career, and so forth.  And a lot of people want to tell us that's true.

"But not The Vogues.  They were from Pittsburgh and they understood about shifts and hourly pay.  Yet they understand more than that!

"For the fact is, 'as you lay dying' (Faulkner), you won't give your "nine-to-five" life a single second thought.  Not one single second thought! You'll forget it all, in the absolute blink of an eye.
That's just a fact of old age and death -- the fact of our mortality.

"What you won't forget, however, is "the long haired girl who waits for you/To ease your troubled mind".  Or the one who did.  Hopefully, it's the same person. As The Vogues observe: in light of her, ''Nothing else matters at all.'

"This cast explores the 'Five O'Clock World' in terms of romantic love.
Not sororal or fraternal love.  Not even paternal and maternal love.
But romantic love.  For that's the core of loving for men and women.  That is "where the action is" (Freddy Cannon).

"Moreover, it is the core of the Gospel.  If you want to understand what Christ did, for you, look at your experience of romantic love.  For better or worse, look at your experience of romantic love.  Like my friend Lloyd Fonvielle, who put one brilliant experience that way underneath the microscope just a few weeks before he died.  And what he came up with!:
Golly, there's no doubting it. The Gospel is the historic, true and universal metaphor, allegory and analogy of that which romantic love instantiates to the core within human experience.  If you want to understand the love of God,
observe the 'Love-O'-(Men and) Women' (Kipling).

"For many of my listeners, this will be a message from your future.
You may not hear it -- you may rebuff it -- and I understand why.  But hey, one day...]]>
            </description>
            <itunes:subtitle>Now we think that reality, the "real world", is what happens "between nine and five", that is, what happens at work, in the office, at school, in career, and so forth.  And a lot of people want to tell us that's true.

"But not The Vogues.  They were from Pittsburgh and they understood about shifts and hourly pay.  Yet they understand more than that!

"For the fact is, 'as you lay dying' (Faulkner), you won't give your "nine-to-five" life a single second thought.  Not one single second thought! You'll forget it all, in the absolute blink of an eye.
That's just a fact of old age and death -- the fact of our mortality.

"What you won't forget, however, is "the long haired girl who waits for you/To ease your troubled mind".  Or the one who did.  Hopefully, it's the same person. As The Vogues observe: in light of her, ''Nothing else matters at all.'

"This cast explores the 'Five O'Clock World' in terms of romantic love.
Not sororal or fraternal love.  Not even paternal and maternal love.
But romantic love.  For that's the core of loving for men and women.  That is "where the action is" (Freddy Cannon).

"Moreover, it is the core of the Gospel.  If you want to understand what Christ did, for you, look at your experience of romantic love.  For better or worse, look at your experience of romantic love.  Like my friend Lloyd Fonvielle, who put one brilliant experience that way underneath the microscope just a few weeks before he died.  And what he came up with!:
Golly, there's no doubting it. The Gospel is the historic, true and universal metaphor, allegory and analogy of that which romantic love instantiates to the core within human experience.  If you want to understand the love of God,
observe the 'Love-O'-(Men and) Women' (Kipling).

"For many of my listeners, this will be a message from your future.
You may not hear it -- you may rebuff it -- and I understand why.  But hey, one day...</itunes:subtitle>
            <itunes:summary>Now we think that reality, the "real world", is what happens "between nine and five", that is, what happens at work, in the office, at school, in career, and so forth.  And a lot of people want to tell us that's true.

"But not The Vogues.  They were from Pittsburgh and they understood about shifts and hourly pay.  Yet they understand more than that!

"For the fact is, 'as you lay dying' (Faulkner), you won't give your "nine-to-five" life a single second thought.  Not one single second thought! You'll forget it all, in the absolute blink of an eye.
That's just a fact of old age and death -- the fact of our mortality.

"What you won't forget, however, is "the long haired girl who waits for you/To ease your troubled mind".  Or the one who did.  Hopefully, it's the same person. As The Vogues observe: in light of her, ''Nothing else matters at all.'

"This cast explores the 'Five O'Clock World' in terms of romantic love.
Not sororal or fraternal love.  Not even paternal and maternal love.
But romantic love.  For that's the core of loving for men and women.  That is "where the action is" (Freddy Cannon).

"Moreover, it is the core of the Gospel.  If you want to understand what Christ did, for you, look at your experience of romantic love.  For better or worse, look at your experience of romantic love.  Like my friend Lloyd Fonvielle, who put one brilliant experience that way underneath the microscope just a few weeks before he died.  And what he came up with!:
Golly, there's no doubting it. The Gospel is the historic, true and universal metaphor, allegory and analogy of that which romantic love instantiates to the core within human experience.  If you want to understand the love of God,
observe the 'Love-O'-(Men and) Women' (Kipling).

"For many of my listeners, this will be a message from your future.
You may not hear it -- you may rebuff it -- and I understand why.  But hey, one day...</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20208%20-%20Five%20O%27Clock%20World.m4a" type="audio/x-m4a" length="24236032" />
            <guid>http://mbird.com/podcastgen/media/Episode%20208%20-%20Five%20O%27Clock%20World.m4a</guid>
            <pubDate>Wed, 7 January 2016 23:09:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>24:52</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 207 - Is Paris Burning? (1966)</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Here are a few thoughts concerning the atrocity attacks in Paris.
I talk about Islam (and "Islamophobia"), Syrian migration into Europe,
Original Sin and "low" vs. "high" anthropology, reaction-formations among young men when drones are over their heads and they have no control, let alone "buy-in"; and finally, a threatening experience Mary and I had on Times Square recently.  Call this PZ's perspective on a current (big) event.]]>
            </description>
            <itunes:subtitle>Here are a few thoughts concerning the atrocity attacks in Paris.
I talk about Islam (and "Islamophobia"), Syrian migration into Europe,
Original Sin and "low" vs. "high" anthropology, reaction-formations among young men when drones are over their heads and they have no control, let alone "buy-in"; and finally, a threatening experience Mary and I had on Times Square recently.  Call this PZ's perspective on a current (big) event.</itunes:subtitle>
            <itunes:summary>IHere are a few thoughts concerning the atrocity attacks in Paris.
I talk about Islam (and "Islamophobia"), Syrian migration into Europe,
Original Sin and "low" vs. "high" anthropology, reaction-formations among young men when drones are over their heads and they have no control, let alone "buy-in"; and finally, a threatening experience Mary and I had on Times Square recently.  Call this PZ's perspective on a current (big) event.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20207%20-%20Is%20Paris%20Burning_%20(1966).m4a" type="audio/x-m4a" length="24942966" />
            <guid>http://mbird.com/podcastgen/media/Episode%20207%20-%20Is%20Paris%20Burning_%20(1966).m4a</guid>
            <pubDate>Sun, 15 November 2015 23:09:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>25:36</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 206 - The Rich Man and Lazarus</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[I keep getting requests for a sort of "early morning Bible study" -- giving the 'treatment', you might say, to a New Testament text that stings, and also helps.  So that's what I'll do for a few episodes, beginning with this one.

Christ's Parable of the Rich Man (aka 'Dives') and Lazarus is given in St. Luke, Chapter 16.  It's a scorcher, as rough and sand-paper-like as anything he ever said. It's got that devastating line, that between there (hell) and here (heaven) there is a great gulf fixed, an impassable, untraversable barrier.

I believe this.  (My own experience confirmed it, tho' I wish it hadn't!)
That being the case, that "when you die, the time for doing is over" (Fr. Richard Ragni), what does it mean for a person in practice?  Well, it mandates a careful review of your true situation: who do you think you are, and where are you?  (Listen, I'm with you: would rather not deal!  No, no, no. Just give me a new DVD daily, like Return of the Fly or Billion Dollar Brain -- there are always new blessings like Billion Dollar Brain waiting for you (you'll never run out even if you live forever) -- and I'm set.

Unfortunately, I'm not set.  For no one knoweth the hour.
Don't delay.  Billion Dollar Brain (1967) you can put off.
Your stroke, your heart attack you can't.
Have a Panic Attack instead.  Based on this podcast.]]>
            </description>
            <itunes:subtitle>I keep getting requests for a sort of "early morning Bible study" -- giving the 'treatment', you might say, to a New Testament text that stings, and also helps.  So that's what I'll do for a few episodes, beginning with this one.

Christ's Parable of the Rich Man (aka 'Dives') and Lazarus is given in St. Luke, Chapter 16.  It's a scorcher, as rough and sand-paper-like as anything he ever said. It's got that devastating line, that between there (hell) and here (heaven) there is a great gulf fixed, an impassable, untraversable barrier.

I believe this.  (My own experience confirmed it, tho' I wish it hadn't!)
That being the case, that "when you die, the time for doing is over" (Fr. Richard Ragni), what does it mean for a person in practice?  Well, it mandates a careful review of your true situation: who do you think you are, and where are you?  (Listen, I'm with you: would rather not deal!  No, no, no. Just give me a new DVD daily, like Return of the Fly or Billion Dollar Brain -- there are always new blessings like Billion Dollar Brain waiting for you (you'll never run out even if you live forever) -- and I'm set.

Unfortunately, I'm not set.  For no one knoweth the hour.
Don't delay.  Billion Dollar Brain (1967) you can put off.
Your stroke, your heart attack you can't.
Have a Panic Attack instead.  Based on this podcast.</itunes:subtitle>
            <itunes:summary>I keep getting requests for a sort of "early morning Bible study" -- giving the 'treatment', you might say, to a New Testament text that stings, and also helps.  So that's what I'll do for a few episodes, beginning with this one.

Christ's Parable of the Rich Man (aka 'Dives') and Lazarus is given in St. Luke, Chapter 16.  It's a scorcher, as rough and sand-paper-like as anything he ever said. It's got that devastating line, that between there (hell) and here (heaven) there is a great gulf fixed, an impassable, untraversable barrier.

I believe this.  (My own experience confirmed it, tho' I wish it hadn't!)
That being the case, that "when you die, the time for doing is over" (Fr. Richard Ragni), what does it mean for a person in practice?  Well, it mandates a careful review of your true situation: who do you think you are, and where are you?  (Listen, I'm with you: would rather not deal!  No, no, no. Just give me a new DVD daily, like Return of the Fly or Billion Dollar Brain -- there are always new blessings like Billion Dollar Brain waiting for you (you'll never run out even if you live forever) -- and I'm set.

Unfortunately, I'm not set.  For no one knoweth the hour.
Don't delay.  Billion Dollar Brain (1967) you can put off.
Your stroke, your heart attack you can't.
Have a Panic Attack instead.  Based on this podcast.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20206%20-%20The%20Rich%20Man%20and%20Lazarus.m4a" type="audio/x-m4a" length="20284151" />
            <guid>http://mbird.com/podcastgen/media/Episode%20206%20-%20The%20Rich%20Man%20and%20Lazarus.m4a</guid>
            <pubDate>Mon, 09 November 2015 07:09:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>20:49</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 205 - Unforeseen</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[It's not an abstraction!  It's more than something just to talk about  or consider. It could happen to you.  In fact, it probably will.

I'm talking about unforeseen death.
Some people hold on for a long time, even when they don't really want to.
Other people want to hold on, but illness intervenes and they go a dozen years earlier than they expected.  (You never expect it.)
Other people had a bad habit in youth and maybe adulthood, and it catches them later.  They never thought they would be hooked up to a respirator personally.

"I Had Too Much To Dream Last Night" (Electric Prunes, 1967):
That is, I thought I was coughing myself to death.
A habitual "nervous" cough turned into an atomic reaction and I suffocated.
Sweet Dreams Are Made of This?

"Are You Ready?": Bob Dylan asked in 1980.
"No", I might answer, in 2015.  "But I'd like to be."

Sunday after Sunday I hear sermons that seem completely to sidestep the one really big reason a person would go to church.  John Wesley never sidestepped it.  Nor did Luther.  St. Ignatius didn't, either.  Don't you.]]>
            </description>
            <itunes:subtitle>It's not an abstraction!  It's more than something just to talk about  or consider. It could happen to you.  In fact, it probably will.

I'm talking about unforeseen death.
Some people hold on for a long time, even when they don't really want to.
Other people want to hold on, but illness intervenes and they go a dozen years earlier than they expected.  (You never expect it.)
Other people had a bad habit in youth and maybe adulthood, and it catches them later.  They never thought they would be hooked up to a respirator personally.

"I Had Too Much To Dream Last Night" (Electric Prunes, 1967):
That is, I thought I was coughing myself to death.
A habitual "nervous" cough turned into an atomic reaction and I suffocated.
Sweet Dreams Are Made of This?

"Are You Ready?": Bob Dylan asked in 1980.
"No", I might answer, in 2015.  "But I'd like to be."

Sunday after Sunday I hear sermons that seem completely to sidestep the one really big reason a person would go to church.  John Wesley never sidestepped it.  Nor did Luther.  St. Ignatius didn't, either.  Don't you.</itunes:subtitle>
            <itunes:summary>It's not an abstraction!  It's more than something just to talk about  or consider. It could happen to you.  In fact, it probably will.

I'm talking about unforeseen death.
Some people hold on for a long time, even when they don't really want to.
Other people want to hold on, but illness intervenes and they go a dozen years earlier than they expected.  (You never expect it.)
Other people had a bad habit in youth and maybe adulthood, and it catches them later.  They never thought they would be hooked up to a respirator personally.

"I Had Too Much To Dream Last Night" (Electric Prunes, 1967):
That is, I thought I was coughing myself to death.
A habitual "nervous" cough turned into an atomic reaction and I suffocated.
Sweet Dreams Are Made of This?

"Are You Ready?": Bob Dylan asked in 1980.
"No", I might answer, in 2015.  "But I'd like to be."

Sunday after Sunday I hear sermons that seem completely to sidestep the one really big reason a person would go to church.  John Wesley never sidestepped it.  Nor did Luther.  St. Ignatius didn't, either.  Don't you.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20205%20-%20Unforeseen%202.m4a" type="audio/x-m4a" length="20241288" />
            <guid>http://mbird.com/podcastgen/media/Episode%20205%20-%20Unforeseen%202.m4a</guid>
            <pubDate>Mon, 09 November 2015 07:09:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>20:46</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 204 - Honest to God</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Pop songs about love are like a corkscrew for understanding the Bible.
Songs like "Hooked on a Feeling" and "Don't Pull Your Love Out on Me, Baby", together with a zillion co-belligerants that are written and performed "In the Name of Love" (Thompson Twins), reveal the nature of love and loss, undoings and exaltings, and painful stasis and buoyed forward movement.

Now just imagine if professional New Testament scholars "parsed" pop songs the way they want to parse the Gospels.  You can't do it.  Or rather, you don't need to do it.  "She Loves You" (The Beatles) is so obviously true.  "Tracks of My Tears" is obviously true.  "My Girl" is obviously true.

Just like the Bible, or most of it.  When you read the Bible through the lens of acknowledged pain and the deficits that come from being  emotional human persons -- if you do that, the Bible makes sense.  Doesn't need parsing.

William Tyndale was right! The "simplest ploughboy" can understand the Bible, or at least enough of the Bible to make sense of life, and loss.

Read the Bible the way you listen to Motown.  "Reach Out (I'll Be There)" is true, to life.  As is... Luke 24.  LUV U (PZ)
]]>
            </description>
            <itunes:subtitle>Pop songs about love are like a corkscrew for understanding the Bible.
Songs like "Hooked on a Feeling" and "Don't Pull Your Love Out on Me, Baby", together with a zillion co-belligerants that are written and performed "In the Name of Love" (Thompson Twins), reveal the nature of love and loss, undoings and exaltings, and painful stasis and buoyed forward movement.

Now just imagine if professional New Testament scholars "parsed" pop songs the way they want to parse the Gospels.  You can't do it.  Or rather, you don't need to do it.  "She Loves You" (The Beatles) is so obviously true.  "Tracks of My Tears" is obviously true.  "My Girl" is obviously true.

Just like the Bible, or most of it.  When you read the Bible through the lens of acknowledged pain and the deficits that come from being  emotional human persons -- if you do that, the Bible makes sense.  Doesn't need parsing.

William Tyndale was right! The "simplest ploughboy" can understand the Bible, or at least enough of the Bible to make sense of life, and loss.

Read the Bible the way you listen to Motown.  "Reach Out (I'll Be There)" is true, to life.  As is... Luke 24.  LUV U (PZ)
            </itunes:subtitle>
            <itunes:summary>Pop songs about love are like a corkscrew for understanding the Bible.
Songs like "Hooked on a Feeling" and "Don't Pull Your Love Out on Me, Baby", together with a zillion co-belligerants that are written and performed "In the Name of Love" (Thompson Twins), reveal the nature of love and loss, undoings and exaltings, and painful stasis and buoyed forward movement.

Now just imagine if professional New Testament scholars "parsed" pop songs the way they want to parse the Gospels.  You can't do it.  Or rather, you don't need to do it.  "She Loves You" (The Beatles) is so obviously true.  "Tracks of My Tears" is obviously true.  "My Girl" is obviously true.

Just like the Bible, or most of it.  When you read the Bible through the lens of acknowledged pain and the deficits that come from being  emotional human persons -- if you do that, the Bible makes sense.  Doesn't need parsing.

William Tyndale was right! The "simplest ploughboy" can understand the Bible, or at least enough of the Bible to make sense of life, and loss.

Read the Bible the way you listen to Motown.  "Reach Out (I'll Be There)" is true, to life.  As is... Luke 24.  LUV U (PZ)
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20204%20-%20Honest%20to%20God.m4a" type="audio/x-m4a" length="21482293" />
            <guid>http://mbird.com/podcastgen/media/Episode%20204%20-%20Honest%20to%20God.m4a</guid>
            <pubDate>Thu, 01 October 2015 07:09:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:22:02</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 203 - Pope Francis and the Historical Jesus</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[
The music is "Good Vibrations" at the start, by The Beach Boys; and "I Knew Jesus (Before He Was a Super Star)", at the end, by Glen Campbell.

Here is the description for iTunes and also the blurb for Mockingbird:

So much has been written -- I mean, SO MUCH -- concerning the so-called Historical Jesus: a welter of books and "Untersuchungen".  I've spent most of my career reading these books, and writing a few, too.

Then Pope Francis came along and put them all in a cocked hat.  This is because if you want to see with your own eyes how Jesus operated in the New Testament -- how he acted, how he spoke, how he was desired, and how he was received -- all you need to do is watch Francis.  Phrancis.

The way Christ was with Zacchaeus, Bartimaeus, the man at the Pool of Bethesda, the woman with the issue of blood, Jairus -- the beat goes on:
that's the way Francis acts, and acted while he was under the scrutiny of all of us.
Just watch him on the Philadelphia Airport tarmac, at the shrine to St. Mary the Untier of Knots in Philadelphia, at Our Lady Queen of Angels School in East Harlem,
and at the state prison near Philadelphia.  Just watch!

All your questions about the conduct and message of the historical Jesus -- or almost all of them -- will be answered.  It just takes a few videos, a couple speeches, a few infants kissed, a few cripples blessed.

Remember the song by The Fifth Dimension, "Blowing Away"?  (George Harrison wrote a similar song.)  So much of what I read and learned over 40 ytears just got, well, blown away -- by the Real Thing.]]>
            </description>
            <itunes:subtitle>
The music is "Good Vibrations" at the start, by The Beach Boys; and "I Knew Jesus (Before He Was a Super Star)", at the end, by Glen Campbell.

Here is the description for iTunes and also the blurb for Mockingbird:

So much has been written -- I mean, SO MUCH -- concerning the so-called Historical Jesus: a welter of books and "Untersuchungen".  I've spent most of my career reading these books, and writing a few, too.

Then Pope Francis came along and put them all in a cocked hat.  This is because if you want to see with your own eyes how Jesus operated in the New Testament -- how he acted, how he spoke, how he was desired, and how he was received -- all you need to do is watch Francis.  Phrancis.

The way Christ was with Zacchaeus, Bartimaeus, the man at the Pool of Bethesda, the woman with the issue of blood, Jairus -- the beat goes on:
that's the way Francis acts, and acted while he was under the scrutiny of all of us.
Just watch him on the Philadelphia Airport tarmac, at the shrine to St. Mary the Untier of Knots in Philadelphia, at Our Lady Queen of Angels School in East Harlem,
and at the state prison near Philadelphia.  Just watch!

All your questions about the conduct and message of the historical Jesus -- or almost all of them -- will be answered.  It just takes a few videos, a couple speeches, a few infants kissed, a few cripples blessed.

Remember the song by The Fifth Dimension, "Blowing Away"?  (George Harrison wrote a similar song.)  So much of what I read and learned over 40 ytears just got, well, blown away -- by the Real Thing.</itunes:subtitle>
            <itunes:summary>
The music is "Good Vibrations" at the start, by The Beach Boys; and "I Knew Jesus (Before He Was a Super Star)", at the end, by Glen Campbell.

Here is the description for iTunes and also the blurb for Mockingbird:

So much has been written -- I mean, SO MUCH -- concerning the so-called Historical Jesus: a welter of books and "Untersuchungen".  I've spent most of my career reading these books, and writing a few, too.

Then Pope Francis came along and put them all in a cocked hat.  This is because if you want to see with your own eyes how Jesus operated in the New Testament -- how he acted, how he spoke, how he was desired, and how he was received -- all you need to do is watch Francis.  Phrancis.

The way Christ was with Zacchaeus, Bartimaeus, the man at the Pool of Bethesda, the woman with the issue of blood, Jairus -- the beat goes on:
that's the way Francis acts, and acted while he was under the scrutiny of all of us.
Just watch him on the Philadelphia Airport tarmac, at the shrine to St. Mary the Untier of Knots in Philadelphia, at Our Lady Queen of Angels School in East Harlem,
and at the state prison near Philadelphia.  Just watch!

All your questions about the conduct and message of the historical Jesus -- or almost all of them -- will be answered.  It just takes a few videos, a couple speeches, a few infants kissed, a few cripples blessed.

Remember the song by The Fifth Dimension, "Blowing Away"?  (George Harrison wrote a similar song.)  So much of what I read and learned over 40 ytears just got, well, blown away -- by the Real Thing.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20203%20-%20Pope%20Francis%20and%20the%20Quest%20for%20the%20Historical%20Jesus.m4a" type="audio/x-m4a" length="21034143" />
            <guid>http://mbird.com/podcastgen/media/Episode%20203%20-%20Pope%20Francis%20and%20the%20Quest%20for%20the%20Historical%20Jesus.m4a" type="audio/x-m4a" length="21034143</guid>
            <pubDate>Thu, 01 October 2015 07:09:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:21:00</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 202: Pope Francis</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Did you cry at any point as you watched Pope Francis in action during his visit?  If you did, when was it?  What made you cry?

"Now it wasn't just John Boehner!  I noticed as I watched the Pope inter-acting with individuals, and especially with individuals in acute need or distress, that it was those encounters that touched me personally.  (I was abreacting all over the place.)

I don't have spina bifida.  I'm not in a wheelchair.  I'm not six years old, nor 84 (yet).  Nor am I homeless.  But hey: Sometimes I Feel Like a Motherless Child!  My tears flow freely, and often not freely enough.  In other words, I identify with distress and need.  I identify with exclusion, tho' you might not know it.  I identify with rejection and exile, tho' again, you might not know it.

The point is, everybody's at their own point of need.  Everybody's got something they're thinking about that's painful.

Pope Francis, walking in the steps of the great Understander, the great Sympathizer, touched the core pain.  He touched the core pain of many, many people.  It was busting out all over.

I think we're each walking in "The Tracks of My Tears". (Thank God for Smokey Robinson and the Miracles.)  And when they bubble up, you can just smell the healing.]]>
            </description>
            <itunes:subtitle>Did you cry at any point as you watched Pope Francis in action during his visit?  If you did, when was it?  What made you cry?

"Now it wasn't just John Boehner!  I noticed as I watched the Pope inter-acting with individuals, and especially with individuals in acute need or distress, that it was those encounters that touched me personally.  (I was abreacting all over the place.)

I don't have spina bifida.  I'm not in a wheelchair.  I'm not six years old, nor 84 (yet).  Nor am I homeless.  But hey: Sometimes I Feel Like a Motherless Child!  My tears flow freely, and often not freely enough.  In other words, I identify with distress and need.  I identify with exclusion, tho' you might not know it.  I identify with rejection and exile, tho' again, you might not know it.

The point is, everybody's at their own point of need.  Everybody's got something they're thinking about that's painful.

Pope Francis, walking in the steps of the great Understander, the great Sympathizer, touched the core pain.  He touched the core pain of many, many people.  It was busting out all over.

I think we're each walking in "The Tracks of My Tears". (Thank God for Smokey Robinson and the Miracles.)  And when they bubble up, you can just smell the healing.</itunes:subtitle>
            <itunes:summary>Did you cry at any point as you watched Pope Francis in action during his visit?  If you did, when was it?  What made you cry?

"Now it wasn't just John Boehner!  I noticed as I watched the Pope inter-acting with individuals, and especially with individuals in acute need or distress, that it was those encounters that touched me personally.  (I was abreacting all over the place.)

I don't have spina bifida.  I'm not in a wheelchair.  I'm not six years old, nor 84 (yet).  Nor am I homeless.  But hey: Sometimes I Feel Like a Motherless Child!  My tears flow freely, and often not freely enough.  In other words, I identify with distress and need.  I identify with exclusion, tho' you might not know it.  I identify with rejection and exile, tho' again, you might not know it.

The point is, everybody's at their own point of need.  Everybody's got something they're thinking about that's painful.

Pope Francis, walking in the steps of the great Understander, the great Sympathizer, touched the core pain.  He touched the core pain of many, many people.  It was busting out all over.

I think we're each walking in "The Tracks of My Tears". (Thank God for Smokey Robinson and the Miracles.)  And when they bubble up, you can just smell the healing.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20202-%20Pope%20Francis-%20SPECIAL%20EDITION.m4a" type="audio/x-m4a" length="21034143" />
            <guid>http://mbird.com/podcastgen/media/Episode%20202-%20Pope%20Francis-%20SPECIAL%20EDITION.m4a</guid>
            <pubDate>Mon, 28 Sep 2015 07:09:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:21:35</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 201: The Real Thing</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Is there anything to it?
Is vertical religion -- not just calls to social justice, not just implied belief (system) -- but actual vertical religion rooted in anything resembling fact?

I'm utterly bummed these days by mainstream Christianity that just leaps over the religious element on the way to the "mission" element.  There's nothing there, I mean nothing there -- to aid an everyday sufferer.  Like me, for example.

On the other hand, evangelicals continue to fake it royally.  They'll talk you blue in the face about God's being present in the worst and darkest moments of your life.  But when it's you who is actually there, you who is sitting flummoxed in the Shadows of Knight (1966), they act as if they didn't mean a word of it.  Grace?  Real Grace? "You have got to be kidding."  Book him!

The theme of this 201st podcast is Real Religion.  Does it exist?
What is it like if it does?

Oh, and SEE The Sentinel (1976).  Don't miss The Sentinel (1976).
It's about to be released on Blu Ray; and with all its memorable eccentricities,
it is a total home run about The Real Thing.  LUV U.  (PZ)

This podcast is dedicated to Melina Smith.]]>
            </description>
            <itunes:subtitle>Is there anything to it?
Is vertical religion -- not just calls to social justice, not just implied belief (system) -- but actual vertical religion rooted in anything resembling fact?

I'm utterly bummed these days by mainstream Christianity that just leaps over the religious element on the way to the "mission" element.  There's nothing there, I mean nothing there -- to aid an everyday sufferer.  Like me, for example.

On the other hand, evangelicals continue to fake it royally.  They'll talk you blue in the face about God's being present in the worst and darkest moments of your life.  But when it's you who is actually there, you who is sitting flummoxed in the Shadows of Knight (1966), they act as if they didn't mean a word of it.  Grace?  Real Grace? "You have got to be kidding."  Book him!

The theme of this 201st podcast is Real Religion.  Does it exist?
What is it like if it does?

Oh, and SEE The Sentinel (1976).  Don't miss The Sentinel (1976).
It's about to be released on Blu Ray; and with all its memorable eccentricities,
it is a total home run about The Real Thing.  LUV U.  (PZ)

This podcast is dedicated to Melina Smith.</itunes:subtitle>
            <itunes:summary>Is there anything to it?
Is vertical religion -- not just calls to social justice, not just implied belief (system) -- but actual vertical religion rooted in anything resembling fact?

I'm utterly bummed these days by mainstream Christianity that just leaps over the religious element on the way to the "mission" element.  There's nothing there, I mean nothing there -- to aid an everyday sufferer.  Like me, for example.

On the other hand, evangelicals continue to fake it royally.  They'll talk you blue in the face about God's being present in the worst and darkest moments of your life.  But when it's you who is actually there, you who is sitting flummoxed in the Shadows of Knight (1966), they act as if they didn't mean a word of it.  Grace?  Real Grace? "You have got to be kidding."  Book him!

The theme of this 201st podcast is Real Religion.  Does it exist?
What is it like if it does?

Oh, and SEE The Sentinel (1976).  Don't miss The Sentinel (1976).
It's about to be released on Blu Ray; and with all its memorable eccentricities,
it is a total home run about The Real Thing.  LUV U.  (PZ)

This podcast is dedicated to Melina Smith.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20201%20-%20The%20Real%20Thing.m4a" type="audio/x-m4a" length="23920752" />
            <guid>http://mbird.com/podcastgen/media/Episode%20201%20-%20The%20Real%20Thing.m4a</guid>
            <pubDate>Tue, 16 Sep 2015 21:09:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:24:33</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 200: Catatonia</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is not the Who's Final Tour.  (They always come back.)
So maybe it is the Who's Final Tour.

Whatever it is, it's Podcast 200, and that's a benchmark.
Somehow.
So I decided to sum up the two core themes of the last... 100 or so
casts, and also tell you something that's blown my mind recently.
It's an instance of catatonia by way of Catalonia.

Seriously, the two core themes of PZ's Podcast are the durability and necessity of romantic connection; and the presence of God when a person is at the end of his or her rope.  'God meets us at our point of need.'

Gosh, I've seen that happen a lot.  Not least of all, to me.

And I know, too, from Mary  -- 'Along Comes Mary' (The Association) -- that the boy-girl side of things is paramount.  Nothing above it.

Now, for 23 short minutes, Come Fly With Me.]]>
            </description>
            <itunes:subtitle>IThis is not the Who's Final Tour.  (They always come back.)
So maybe it is the Who's Final Tour.

Whatever it is, it's Podcast 200, and that's a benchmark.
Somehow.
So I decided to sum up the two core themes of the last... 100 or so
casts, and also tell you something that's blown my mind recently.
It's an instance of catatonia by way of Catalonia.

Seriously, the two core themes of PZ's Podcast are the durability and necessity of romantic connection; and the presence of God when a person is at the end of his or her rope.  'God meets us at our point of need.'

Gosh, I've seen that happen a lot.  Not least of all, to me.

And I know, too, from Mary  -- 'Along Comes Mary' (The Association) -- that the boy-girl side of things is paramount.  Nothing above it.

Now, for 23 short minutes, Come Fly With Me.</itunes:subtitle>
            <itunes:summary>This is not the Who's Final Tour.  (They always come back.)
So maybe it is the Who's Final Tour.

Whatever it is, it's Podcast 200, and that's a benchmark.
Somehow.
So I decided to sum up the two core themes of the last... 100 or so
casts, and also tell you something that's blown my mind recently.
It's an instance of catatonia by way of Catalonia.

Seriously, the two core themes of PZ's Podcast are the durability and necessity of romantic connection; and the presence of God when a person is at the end of his or her rope.  'God meets us at our point of need.'

Gosh, I've seen that happen a lot.  Not least of all, to me.

And I know, too, from Mary  -- 'Along Comes Mary' (The Association) -- that the boy-girl side of things is paramount.  Nothing above it.

Now, for 23 short minutes, Come Fly With Me.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20200%20-%20Catatonia.m4a" type="audio/x-m4a" length="22138564" />
            <guid>http://mbird.com/podcastgen/media/Episode%20200%20-%20Catatonia.m4a</guid>
            <pubDate>Tue, 12 Aug 2015 21:09:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:23:43</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 199: What Actually Happens</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[If you don't factor in the element of romantic love -- or at least its possibility -- you'll surprise yourself when you start making decisions in life.

Sometimes I wish I could give a college commencement address.  (No one is ever going to ask.)  But I should like to talk about romantic love, and its over-riding, over-reaching, superseding strength as an element -- the decisive element -- in personal decision-making.

I can't really say that, though.  Many people seem to want to "privilege" career and/or professional choices over their love life.  They seem to want to, at least.  But then you surprise yourself!  You quit your job, or apply for a job in another city, or go back to school; and the real reason is that you've met someone, or want to.  Even desperately want to.

Romantic love always wins.  Tho' it takes too long these days.
So much romantic time is wasted by the effort, energy and time, g__dammit, given to careers that end up, eventually, feeling phony, futile, arbitrary, and selfish.

What am I saying?  Put romantic love first. Hey, and then, work's a piece of cake.  You'll probably be promoted at work the moment you start promoting yourself to her.]]>
            </description>
            <itunes:subtitle>If you don't factor in the element of romantic love -- or at least its possibility -- you'll surprise yourself when you start making decisions in life.

Sometimes I wish I could give a college commencement address.  (No one is ever going to ask.)  But I should like to talk about romantic love, and its over-riding, over-reaching, superseding strength as an element -- the decisive element -- in personal decision-making.

I can't really say that, though.  Many people seem to want to "privilege" career and/or professional choices over their love life.  They seem to want to, at least.  But then you surprise yourself!  You quit your job, or apply for a job in another city, or go back to school; and the real reason is that you've met someone, or want to.  Even desperately want to.

Romantic love always wins.  Tho' it takes too long these days.
So much romantic time is wasted by the effort, energy and time, g__dammit, given to careers that end up, eventually, feeling phony, futile, arbitrary, and selfish.

What am I saying?  Put romantic love first. Hey, and then, work's a piece of cake.  You'll probably be promoted at work the moment you start promoting yourself to her.</itunes:subtitle>
            <itunes:summary>If you don't factor in the element of romantic love -- or at least its possibility -- you'll surprise yourself when you start making decisions in life.

Sometimes I wish I could give a college commencement address.  (No one is ever going to ask.)  But I should like to talk about romantic love, and its over-riding, over-reaching, superseding strength as an element -- the decisive element -- in personal decision-making.

I can't really say that, though.  Many people seem to want to "privilege" career and/or professional choices over their love life.  They seem to want to, at least.  But then you surprise yourself!  You quit your job, or apply for a job in another city, or go back to school; and the real reason is that you've met someone, or want to.  Even desperately want to.

Romantic love always wins.  Tho' it takes too long these days.
So much romantic time is wasted by the effort, energy and time, g__dammit, given to careers that end up, eventually, feeling phony, futile, arbitrary, and selfish.

What am I saying?  Put romantic love first. Hey, and then, work's a piece of cake.  You'll probably be promoted at work the moment you start promoting yourself to her.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20199%20-%20What%20Actually%20Happens.m4a" type="audio/x-m4a" length="21860322" />
            <guid>http://mbird.com/podcastgen/media/Episode%20199%20-%20What%20Actually%20Happens.m4a</guid>
            <pubDate>Sun, 08 Aug 2015 21:09:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:22:26</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 198: Mirage Fighter</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Talk about being misunderstood!:
Artur London was one of the 11 most misunderstood men in the world,
at least at the end of 1951.  London was a defendant in the Slansky Trial,
a "show trial" under Joseph Stalin.

After suffering the most inhuman torture and brainwashing, London was sentenced to life imprisonment for crimes not one of which he had come 10,000 light years close to committing.

Later on, Arthur London was released, rehabilitated; and now they name streets after him.

Arthur London said that his life's struggle was to differentiate between the essence of an ideal, and the form in which that ideal had taken shape politically -- a debased and wicked form, it turns out.  London also said that being a Communist in a Soviet prison was like being a Christian tortured by the Spanish Inquisition.  Christians, like Communists, could only survive, and persist,
if they clearly separated the Thing Signified from the Sign -- the Substance from the Form.

Good luck, Arthur!
How'd it work for you, Paul?
Way to go, Czech Communist Party.  That's the way (O Mother Church)/ I Like It (KC & The Sunshine Band)]]>
            </description>
            <itunes:subtitle>Talk about being misunderstood!:
Artur London was one of the 11 most misunderstood men in the world,
at least at the end of 1951.  London was a defendant in the Slansky Trial,
a "show trial" under Joseph Stalin.

After suffering the most inhuman torture and brainwashing, London was sentenced to life imprisonment for crimes not one of which he had come 10,000 light years close to committing.

Later on, Arthur London was released, rehabilitated; and now they name streets after him.

Arthur London said that his life's struggle was to differentiate between the essence of an ideal, and the form in which that ideal had taken shape politically -- a debased and wicked form, it turns out.  London also said that being a Communist in a Soviet prison was like being a Christian tortured by the Spanish Inquisition.  Christians, like Communists, could only survive, and persist,
if they clearly separated the Thing Signified from the Sign -- the Substance from the Form.

Good luck, Arthur!
How'd it work for you, Paul?
Way to go, Czech Communist Party.  That's the way (O Mother Church)/ I Like It (KC &amp; The Sunshine Band)</itunes:subtitle>
            <itunes:summary>Talk about being misunderstood!:
Artur London was one of the 11 most misunderstood men in the world,
at least at the end of 1951.  London was a defendant in the Slansky Trial,
a "show trial" under Joseph Stalin.

After suffering the most inhuman torture and brainwashing, London was sentenced to life imprisonment for crimes not one of which he had come 10,000 light years close to committing.

Later on, Arthur London was released, rehabilitated; and now they name streets after him.

Arthur London said that his life's struggle was to differentiate between the essence of an ideal, and the form in which that ideal had taken shape politically -- a debased and wicked form, it turns out.  London also said that being a Communist in a Soviet prison was like being a Christian tortured by the Spanish Inquisition.  Christians, like Communists, could only survive, and persist,
if they clearly separated the Thing Signified from the Sign -- the Substance from the Form.

Good luck, Arthur!
How'd it work for you, Paul?
Way to go, Czech Communist Party.  That's the way (O Mother Church)/ I Like It (KC &amp; The Sunshine Band)</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20198%20-%20Mirage%20Fighter.m4a" type="audio/x-m4a" length="22593892" />
            <guid>http://mbird.com/podcastgen/media/Episode%20198%20-%20Mirage%20Fighter.m4a</guid>
            <pubDate>Sun, 08 Aug 2015 19:14:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:23:11</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 197: The Sacraments Rightly Understood</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[The church is today so vastly over-eucharisted that you can barely pause to catch your breath.  This cast offers an alternative view of the Holy Communion, as well as of Baptism. The original Prayer Book definition of a sacrament was that it is 'an outward and visible sign of an inward and spiritual grace'.  What a refined and powerful expression.  So now Let Smokey Sing (ABC) and find... The Face Behind the Mask (1941).  This cast is dedicated to Nancy W. Hanna.]]>
            </description>
            <itunes:subtitle>The church is today so vastly over-eucharisted that you can barely pause to catch your breath.  This cast offers an alternative view of the Holy Communion, as well as of Baptism. The original Prayer Book definition of a sacrament was that it is 'an outward and visible sign of an inward and spiritual grace'.  What a refined and powerful expression.  So now Let Smokey Sing (ABC) and find... The Face Behind the Mask (1941).  This cast is dedicated to Nancy W. Hanna.</itunes:subtitle>
            <itunes:summary>The church is today so vastly over-eucharisted that you can barely pause to catch your breath.  This cast offers an alternative view of the Holy Communion, as well as of Baptism. The original Prayer Book definition of a sacrament was that it is 'an outward and visible sign of an inward and spiritual grace'.  What a refined and powerful expression.  So now Let Smokey Sing (ABC) and find... The Face Behind the Mask (1941).  This cast is dedicated to Nancy W. Hanna.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20197%20-%20The%20Sacraments%20Rightly%20Understood.m4a" type="audio/x-m4a" length="20514259" />
            <guid>http://mbird.com/podcastgen/media/Episode%20197%20-%20The%20Sacraments%20Rightly%20Understood.m4a</guid>
            <pubDate>Sun, 08 Aug 2015 10:14:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:21:03</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 196: Cimarron</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["The movie Cimarron, which was released in 1931, won the Academy Award for Best Picture that year.  (Did you know this?)

It's great blessing, Cimarron -- which was based on the novel Cimarron, written by Edna Ferber.  But you'd never know it's a blessing if you relied on the critics.

Cimarron has become notorious in recent times for its racial and ethnic stereotyping.  When you read contemporary descriptions of this movie,
it's as if you're being told to put your hands in front of your eyes and also cup them around your ears.

Yet the amazing thing is that Cimarron is actually the opposite of what it's accused of being.  It's actually a definitive portrait of "Radical Hospitality",
as the pharisees and hypocrites are all smote; and the outsiders and excluded people are all promoted!  Cimarron depicts the triumph of the "minority" in life.
You've got to see it.

But only if you aren't carrying so much presuppositional baggage that your eyes are already closed and your ears already shut.  Cimarron  is a portrait of that great House for All Sinners and Saints.

Oh, and there's a mistake at the end of the cast:
The music is not by Chrissie Hynde.  It's by Talk Talk.]]>
            </description>
            <itunes:subtitle>"The movie Cimarron, which was released in 1931, won the Academy Award for Best Picture that year.  (Did you know this?)

It's great blessing, Cimarron -- which was based on the novel Cimarron, written by Edna Ferber.  But you'd never know it's a blessing if you relied on the critics.

Cimarron has become notorious in recent times for its racial and ethnic stereotyping.  When you read contemporary descriptions of this movie,
it's as if you're being told to put your hands in front of your eyes and also cup them around your ears.

Yet the amazing thing is that Cimarron is actually the opposite of what it's accused of being.  It's actually a definitive portrait of "Radical Hospitality",
as the pharisees and hypocrites are all smote; and the outsiders and excluded people are all promoted!  Cimarron depicts the triumph of the "minority" in life.
You've got to see it.

But only if you aren't carrying so much presuppositional baggage that your eyes are already closed and your ears already shut.  Cimarron  is a portrait of that great House for All Sinners and Saints.

Oh, and there's a mistake at the end of the cast:
The music is not by Chrissie Hynde.  It's by Talk Talk.</itunes:subtitle>
            <itunes:summary>"The movie Cimarron, which was released in 1931, won the Academy Award for Best Picture that year.  (Did you know this?)

It's great blessing, Cimarron -- which was based on the novel Cimarron, written by Edna Ferber.  But you'd never know it's a blessing if you relied on the critics.

Cimarron has become notorious in recent times for its racial and ethnic stereotyping.  When you read contemporary descriptions of this movie,
it's as if you're being told to put your hands in front of your eyes and also cup them around your ears.

Yet the amazing thing is that Cimarron is actually the opposite of what it's accused of being.  It's actually a definitive portrait of "Radical Hospitality",
as the pharisees and hypocrites are all smote; and the outsiders and excluded people are all promoted!  Cimarron depicts the triumph of the "minority" in life.
You've got to see it.

But only if you aren't carrying so much presuppositional baggage that your eyes are already closed and your ears already shut.  Cimarron  is a portrait of that great House for All Sinners and Saints.

Oh, and there's a mistake at the end of the cast:
The music is not by Chrissie Hynde.  It's by Talk Talk.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20196%20-%20Cimarron%20(1931).m4a" type="audio/x-m4a" length="24438293 " />
            <guid>http://mbird.com/podcastgen/media/Episode%20196%20-%20Cimarron%20(1931).m4a</guid>
            <pubDate>Fri, 31 Jul 2015 10:14:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:25:04</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 195: Shag (The Movie)</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Shag The Movie (1989) is a great little entertainment!  It captures perfectly, and with high humor and enormous love and heart, the Beach Music phenomenon of the 1960s.  Today, however, it touches a current issue -- right from the opening credits.  What do you do with distressing material -- images and associations that relate to things you'd rather forget?  The general answer seems to be, well, you burn them!  You do away with them.  You haul them down -- and out.]]>
            </description>
            <itunes:subtitle>Shag The Movie (1989) is a great little entertainment!  It captures perfectly, and with high humor and enormous love and heart, the Beach Music phenomenon of the 1960s.  Today, however, it touches a current issue -- right from the opening credits.  What do you do with distressing material -- images and associations that relate to things you'd rather forget?  The general answer seems to be, well, you burn them!  You do away with them.  You haul them down -- and out.</itunes:subtitle>
            <itunes:summary>Shag The Movie (1989) is a great little entertainment!  It captures perfectly, and with high humor and enormous love and heart, the Beach Music phenomenon of the 1960s.  Today, however, it touches a current issue -- right from the opening credits.  What do you do with distressing material -- images and associations that relate to things you'd rather forget?  The general answer seems to be, well, you burn them!  You do away with them.  You haul them down -- and out.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20195%20-%20Shag%20(The%20Movie).m4a" type="audio/x-m4a" length="20397371" />
            <guid>http://mbird.com/podcastgen/media/Episode%20195%20-%20Shag%20(The%20Movie).m4a</guid>
            <pubDate>Tue, 28 Jul 2015 10:14:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:20:56</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 194: Left Hand Path</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[I think I'm supposed to understand why right-wing people are intolerant. But it's harder for me to understand why left-wing people are intolerant. Guess I thought they were supposed to be about freedom and diversity.  Come to find out, they're not. So I had to go back to a source that's almost been "blacklisted" itself. It's the movie My Son John (1952), starring Helen Hayes and Robert Wagner. Hey, but isn't that a reactionary movie from the Eisenhower movie? No, it's not.  It's an excruciating journey into the cause of liberal intolerance. If I -- meaning PZ -- had a mother and a father like 'John' does in My Son John, I'd probably do what he did.  I'm almost sure I'd want to. ]]>
            </description>
            <itunes:subtitle>I think I'm supposed to understand why right-wing people are intolerant. But it's harder for me to understand why left-wing people are intolerant. Guess I thought they were supposed to be about freedom and diversity.  Come to find out, they're not. So I had to go back to a source that's almost been "blacklisted" itself. It's the movie My Son John (1952), starring Helen Hayes and Robert Wagner. Hey, but isn't that a reactionary movie from the Eisenhower movie? No, it's not.  It's an excruciating journey into the cause of liberal intolerance. If I -- meaning PZ -- had a mother and a father like 'John' does in My Son John, I'd probably do what he did.  I'm almost sure I'd want to.</itunes:subtitle>
            <itunes:summary>I think I'm supposed to understand why right-wing people are intolerant. But it's harder for me to understand why left-wing people are intolerant. Guess I thought they were supposed to be about freedom and diversity.  Come to find out, they're not. So I had to go back to a source that's almost been "blacklisted" itself. It's the movie My Son John (1952), starring Helen Hayes and Robert Wagner. Hey, but isn't that a reactionary movie from the Eisenhower movie? No, it's not.  It's an excruciating journey into the cause of liberal intolerance. If I -- meaning PZ -- had a mother and a father like 'John' does in My Son John, I'd probably do what he did.  I'm almost sure I'd want to.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20194%20-%20Left%20Hand%20Path%202.m4a" type="audio/x-m4a" length="19246708" />
            <guid>http://mbird.com/podcastgen/media/Episode%20194%20-%20Left%20Hand%20Path%202.m4a</guid>
            <pubDate>Fri, 24 Jul 2015 10:14:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:19:45</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 193: Cross Dressing</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[The Gallant Hours (1959) is one heuristic movie.  Not only does it teach the Church a thing or two about how to honor faithful service, but it depicts an entirely ideal instance of how to dress properly if you're a minister -- or, Heav'n forfend, a "priest".  The last scene of The Gallant Hours is one amazing illustration of the triumph of substance over form in connection with haberdashery. If you're a member of the clergy, or are close to one, PLEASE, help them dress down.  We need clergy who dress down!  The future of the world depends on it. ]]>
            </description>
            <itunes:subtitle>The Gallant Hours (1959) is one heuristic movie.  Not only does it teach the Church a thing or two about how to honor faithful service, but it depicts an entirely ideal instance of how to dress properly if you're a minister -- or, Heav'n forfend, a "priest".  The last scene of The Gallant Hours is one amazing illustration of the triumph of substance over form in connection with haberdashery. If you're a member of the clergy, or are close to one, PLEASE, help them dress down.  We need clergy who dress down!  The future of the world depends on it. </itunes:subtitle>
            <itunes:summary>The Gallant Hours (1959) is one heuristic movie.  Not only does it teach the Church a thing or two about how to honor faithful service, but it depicts an entirely ideal instance of how to dress properly if you're a minister -- or, Heav'n forfend, a "priest".  The last scene of The Gallant Hours is one amazing illustration of the triumph of substance over form in connection with haberdashery. If you're a member of the clergy, or are close to one, PLEASE, help them dress down.  We need clergy who dress down!  The future of the world depends on it. </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20193%20-%20Cross%20Dressing.m4a" type="audio/x-m4a" length="22089280" />
            <guid>http://mbird.com/podcastgen/media/Episode%20193%20-%20Cross%20Dressing.m4a</guid>
            <pubDate>Tue, 21 Jul 2015 10:14:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:22:40</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 192: How to Save the Church (But Our Lips Are Sealed)</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[The Church I have known all my life is in free fall numerically.
I'm talking about Sunday attendance in everyday parishes.
This is not conceptual:  one parish Mary and I served for six and a half years has recently closed.  (Yes, it's been there since 1832, and now is literally a shell, the congregation having gone formally out of existence!)  Another church we served, also for six years but out on Long Island, has seen its attendance fall so drastically that its diocese wants to convert it into a different kind of ministry altogether.
But this podcast is not a list of what went wrong, but what could be done right.  The lesson, dear reader, comes from a little movie called The Gallant Hours.  This movie's got just about everything you need to know, about everything.]]>
            </description>
            <itunes:subtitle>The Church I have known all my life is in free fall numerically.
I'm talking about Sunday attendance in everyday parishes.
This is not conceptual:  one parish Mary and I served for six and a half years has recently closed.  (Yes, it's been there since 1832, and now is literally a shell, the congregation having gone formally out of existence!)  Another church we served, also for six years but out on Long Island, has seen its attendance fall so drastically that its diocese wants to convert it into a different kind of ministry altogether.
But this podcast is not a list of what went wrong, but what could be done right.  The lesson, dear reader, comes from a little movie called The Gallant Hours.  This movie's got just about everything you need to know, about everything.</itunes:subtitle>
            <itunes:summary>The Church I have known all my life is in free fall numerically.
I'm talking about Sunday attendance in everyday parishes.
This is not conceptual:  one parish Mary and I served for six and a half years has recently closed.  (Yes, it's been there since 1832, and now is literally a shell, the congregation having gone formally out of existence!)  Another church we served, also for six years but out on Long Island, has seen its attendance fall so drastically that its diocese wants to convert it into a different kind of ministry altogether.
But this podcast is not a list of what went wrong, but what could be done right.  The lesson, dear reader, comes from a little movie called The Gallant Hours.  This movie's got just about everything you need to know, about everything.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20192%20--%20How%20to%20Save%20the%20Church%20(But%20Our%20Lips%20Are%20Sealed).m4a" type="audio/x-m4a" length="23059693 " />
            <guid>http://mbird.com/podcastgen/media/Episode%20192%20--%20How%20to%20Save%20the%20Church%20(But%20Our%20Lips%20Are%20Sealed).m4a</guid>
            <pubDate>Mon, 20 Jul 2015 10:14:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:23:40</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 191: Shakin' All Over</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This talk concerns the indelibility of certain memories, and why they, and not other memories, are indelible.  It also concerns a worrying vision I had in January.  But it's all one!  Here is my little attempt at some wise counsel: how to integrate indelible aspects of your grown life with the fact that you'll see it all again, up close and personal, the day you die.  But again, it's all One. And hey, didn't Charles Reade say, "It is never too late to mend."]]>
            </description>
            <itunes:subtitle>This talk concerns the indelibility of certain memories, and why they, and not other memories, are indelible.  It also concerns a worrying vision I had in January.  But it's all one!  Here is my little attempt at some wise counsel: how to integrate indelible aspects of your grown life with the fact that you'll see it all again, up close and personal, the day you die.  But again, it's all One. And hey, didn't Charles Reade say, "It is never too late to mend."</itunes:subtitle>
            <itunes:summary>This talk concerns the indelibility of certain memories, and why they, and not other memories, are indelible.  It also concerns a worrying vision I had in January.  But it's all one!  Here is my little attempt at some wise counsel: how to integrate indelible aspects of your grown life with the fact that you'll see it all again, up close and personal, the day you die.  But again, it's all One. And hey, didn't Charles Reade say, "It is never too late to mend."</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media//Episode%20191%20--%20Shakin%27%20All%20Over.m4a" type="audio/x-m4a" length="21463040 " />
            <guid>http://mbird.com/podcastgen/media//Episode%20191%20--%20Shakin%27%20All%20Over.m4a</guid>
            <pubDate>Mon, 20 Jul 2015 09:14:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:22:01</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 190 - PZ's Fabulous New Dating Tips for Gals</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is a word to your future self.  You probably can't hear it today.
But I predict you'll hear it loud and clear in five years,  or maybe ten.  This is a word to your future self.  It's a new fabulous dating tip, and carries almost no exceptions, tho' I wish it did!  It has to do with internet dating, with the aging process (especially in men), and with the poignant voice of experience.
If you can "Now Hear This", it could save you years of excruciating suffering.  I mean years, maybe decades.  Maybe the rest of your life.]]>
            </description>
            <itunes:subtitle>This is a word to your future self.  You probably can't hear it today. But I predict you'll hear it loud and clear in five years,  or maybe ten.  This is a word to your future self.  It's a new fabulous dating tip, and carries almost no exceptions, tho' I wish it did!  It has to do with internet dating, with the aging process (especially in men), and with the poignant voice of experience. If you can "Now Hear This", it could save you years of excruciating suffering.  I mean years, maybe decades.  Maybe the rest of your life.</itunes:subtitle>
            <itunes:summary>This is a word to your future self.  You probably can't hear it today. But I predict you'll hear it loud and clear in five years,  or maybe ten.  This is a word to your future self.  It's a new fabulous dating tip, and carries almost no exceptions, tho' I wish it did!  It has to do with internet dating, with the aging process (especially in men), and with the poignant voice of experience. If you can "Now Hear This", it could save you years of excruciating suffering.  I mean years, maybe decades.  Maybe the rest of your life.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20190%20-%20PZ%27s%20Fabulous%20New%20Dating%20Tip%20for%20Gals.m4a" type="audio/x-m4a" length="18341681" />
            <guid>http://mbird.com/podcastgen/media/Episode%20190%20-%20PZ%27s%20Fabulous%20New%20Dating%20Tip%20for%20Gals.m4a</guid>
            <pubDate>Mon, 06 Jul 2015 09:14:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:18:49</itunes:duration>
            <itunes:keywords />
        </item>


        <item>
            <title>Episode 189 - Why Weepest Thou?</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["What makes you cry?  When you have an irruption of strong feeling -- and I mean tears in this case -- what is going on?   This cast tries to get underneath some emotions we all feel, and in terms of music.  It is a subjective "take" on one's music and one's highs and lows.  And it's in the service of a Way Maker that tends in the direction of peace of mind."]]>
            </description>
            <itunes:subtitle>"What makes you cry?  When you have an irruption of strong feeling -- and I mean tears in this case -- what is going on?   This cast tries to get underneath some emotions we all feel, and in terms of music.  It is a subjective "take" on one's music and one's highs and lows.  And it's in the service of a Way Maker that tends in the direction of peace of mind."</itunes:subtitle>
            <itunes:summary>"What makes you cry?  When you have an irruption of strong feeling -- and I mean tears in this case -- what is going on?   This cast tries to get underneath some emotions we all feel, and in terms of music.  It is a subjective "take" on one's music and one's highs and lows.  And it's in the service of a Way Maker that tends in the direction of peace of mind."</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20189%20-%20Why%20Weepest%20Thou_%202.m4a" type="audio/x-m4a" length="17774512" />
            <guid>http://mbird.com/podcastgen/media/Episode%20189%20-%20Why%20Weepest%20Thou_%202.m4a</guid>
            <pubDate>Sun, 28 Jun 2015 09:14:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:18:14</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 188 - Scuppernong</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Tupper Saussy (1936-2007) was a musician behind The Neon Philharmonic, who produced two memorable albums in 1968-69.  He was also a polymath who let himself get in the sights of the Internal Revenue Service,
and paid a heavy price for it.  Moreover, he was a devout Christian, of old-fashioned Episcopalian provenance.   This week he is on my mind because the fate of Tupper Saussy made me think of a friend who is in some trouble. "Handle Me With Care" is what Tupper Saussy needed.  It is what my friend needs.  And it's what the world never and the church rarely does]]>
            </description>
            <itunes:subtitle>Tupper Saussy (1936-2007) was a musician behind The Neon Philharmonic, who produced two memorable albums in 1968-69.  He was also a polymath who let himself get in the sights of the Internal Revenue Service,
and paid a heavy price for it.  Moreover, he was a devout Christian, of old-fashioned Episcopalian provenance.   This week he is on my mind because the fate of Tupper Saussy made me think of a friend who is in some trouble. "Handle Me With Care" is what Tupper Saussy needed.  It is what my friend needs.  And it's what the world never and the church rarely does</itunes:subtitle>
            <itunes:summary>Tupper Saussy (1936-2007) was a musician behind The Neon Philharmonic, who produced two memorable albums in 1968-69.  He was also a polymath who let himself get in the sights of the Internal Revenue Service,
and paid a heavy price for it.  Moreover, he was a devout Christian, of old-fashioned Episcopalian provenance.   This week he is on my mind because the fate of Tupper Saussy made me think of a friend who is in some trouble. "Handle Me With Care" is what Tupper Saussy needed.  It is what my friend needs.  And it's what the world never and the church rarely does</itunes:summary>
            <enclosure url="http://mbird.com//podcastgen/media/Episode%20188%20-%20Scuppernong.m4a" type="audio/x-m4a" length="19598189 " />
            <guid>http://mbird.com//podcastgen/media/Episode%20188%20-%20Scuppernong.m4a</guid>
            <pubDate>Tue, 23 Jun 2015 20:41:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:20:06</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 187 - Norwegian Wood</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Nevil Shute, whose proper name was Nevil Shute Norway, was a British novelist whose work took an odd turn in mid-career.  He was a kind of parasitologist of human nature, always asking the big questions:
Why do people act the way they do? How does the past affect the present?
Is there something more to it that is beyond the apparent?  Shute thought there was, but he was a tentative explorer. (He was also a churchgoer.) Did he pierce "the veil"?  My answer to that is maybe.]]>
            </description>
            <itunes:subtitle>Nevil Shute, whose proper name was Nevil Shute Norway, was a British novelist whose work took an odd turn in mid-career.  He was a kind of parasitologist of human nature, always asking the big questions:
Why do people act the way they do? How does the past affect the present?
Is there something more to it that is beyond the apparent?  Shute thought there was, but he was a tentative explorer. (He was also a churchgoer.) Did he pierce "the veil"?  My answer to that is maybe.</itunes:subtitle>
            <itunes:summary>Nevil Shute, whose proper name was Nevil Shute Norway, was a British novelist whose work took an odd turn in mid-career.  He was a kind of parasitologist of human nature, always asking the big questions:
Why do people act the way they do? How does the past affect the present?
Is there something more to it that is beyond the apparent?  Shute thought there was, but he was a tentative explorer. (He was also a churchgoer.) Did he pierce "the veil"?  My answer to that is maybe.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20187%20-%20Norwegian%20Wood%202.m4a" type="audio/x-m4a" length="19976192" />
            <guid>http://mbird.com/podcastgen/media/Episode%20187%20-%20Norwegian%20Wood%202.m4a</guid>
            <pubDate>Mon, 15 Jun 2015 20:41:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:20:30</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 186 - Dead End (My Friend)</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA['No' is the worst word you can ever hear.  (I realize the virtues of saying 'No', yourself, on certain occasions.  But when 'No' is said to you, especially at an impressionable age, it's the worst.)  This cast is about the
damage created by 'No', especially in romance.]]>
            </description>
            <itunes:subtitle>'No' is the worst word you can ever hear.  (I realize the virtues of saying 'No', yourself, on certain occasions.  But when 'No' is said to you, especially at an impressionable age, it's the worst.)  This cast is about the
damage created by 'No', especially in romance.</itunes:subtitle>
            <itunes:summary>'No' is the worst word you can ever hear.  (I realize the virtues of saying 'No', yourself, on certain occasions.  But when 'No' is said to you, especially at an impressionable age, it's the worst.)  This cast is about the
damage created by 'No', especially in romance.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20186%20-%20Dead%20End%20(My%20Friend).m4a" type="audio/x-m4a" length="18538496" />
            <guid>http://mbird.com/podcastgen/media/Episode%20186%20-%20Dead%20End%20(My%20Friend).m4a</guid>
            <pubDate>Sun, 14 Jun 2015 20:41:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:19:01</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 185 - One Toke Over The Line (Sweet Mary)</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[What think ye when I say that 95% of what you are doing
is futile and meaningless?  Well, let's put it another way:
From the standpoint of the after-life, what you are doing is...
you fill in the blanks.
But you can still make it!
You've got to learn how to meditate, and learn how to throw a Crucifix.
Podcast 185 is dedicated to Mary C. Zahl.]]>
            </description>
            <itunes:subtitle>What think ye when I say that 95% of what you are doing
                is futile and meaningless?</itunes:subtitle>
            <itunes:summary>What think ye when I say that 95% of what you are doing
                is futile and meaningless?</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20185%20-%20One%20Toke%20Over%20The%20Line%20(Sweet%20Mary).m4a" type="audio/x-m4a" length="24068096" />
            <guid>http://mbird.com/podcastgen/media/Episode%20185%20-%20One%20Toke%20Over%20The%20Line%20(Sweet%20Mary).m4a</guid>
            <pubDate>Wed, 11 Mar 2015 20:41:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:24:42</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 184 - Hysteria</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[In life you can be trapped by forces that are bigger than you are.  Especially in professional life.
It's possible to "wander in" -- or rather, bumble in -- to a situation in which you get used by somebody else
to accomplish a plan of theirs of which you yourself are (at the time) unaware.
Here is my homage to Jimmy Sangster movies.
In particular, behold Hysteria, a masterpiece of intrigue from 1965.
Watch out!  And take comfort, too]]>
            </description>
            <itunes:subtitle>In life you can be trapped by forces that are bigger than you are.  Especially in professional life. </itunes:subtitle>
            <itunes:summary>In life you can be trapped by forces that are bigger than you are.  Especially in professional life. .</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20184%20-%20Hysteria.m4a" type="audio/x-m4a" length="23674980" />
            <guid>http://mbird.com/podcastgen/media/Episode%20184%20-%20Hysteria.m4a</guid>
            <pubDate>Sun, 8 Mar 2015 20:41:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:24:17</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 183 - Dr. Syn</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Oh, to encounter an integrated minister!
We all want to be integrated -- to be ourselves in the pulpit
and also out of it.
But it's tricky to pull off.
Pharisaical elements in the church -- usually one or two individuals
in the parish, who are present -- unconsciously -- in order to hide out themselves in some way or another -- can't long abide a minister who
is himself or herself.

Most of your listeners love it.
But there are one or two who, well, have an allergy.
(They are the ones that can get you every time.)

But then along comes someone like 'Mr. Tryan' in George Eliot's Scenes of Clerical Life.  He breaks the mold.

Or, somewhat spectacularly, Dr. Syn.  Dr. Syn, who was known in the movies as 'Dr. Bliss', is just about the most thoroughly integrated Anglican clergyman in history.  Could any of us be like him?  Dr. Syn is a brilliant swordsman, an agile swinger from church chandeliers, a powerful preacher,
a rousing music leader, a crafty smuggler, a loving father, a wily impeder of the taxation and revenue service, and a kindly pastor to his entire flock.  He is Robin Hood and 'Fletcher of Madeley' rolled into one.  Dr. Syn makes one wish to keep on going.

Hope you like him.  Maybe we can do a breakout in his honor at Mockingbird.  But everyone who comes will need to bring preaching bands, and
a phosphorescent mask.]]>
            </description>
            <itunes:subtitle>Oh, to encounter an integrated minister! We all want to be integrated -- to be ourselves in the pulpit and also out of it. But it's tricky to pull off.</itunes:subtitle>
            <itunes:summary>Oh, to encounter an integrated minister! We all want to be integrated -- to be ourselves in the pulpit and also out of it. But it's tricky to pull off.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20183%20-%20Dr.%20Syn%202.m4a" type="audio/x-m4a" length="21761794" />
            <guid>http://mbird.com/podcastgen/media/Episode%20183%20-%20Dr.%20Syn%202.m4a</guid>
            <pubDate>Fri, 6 Mar 2015 20:41:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:22:20</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 181 - Dualism Clinic with James Bernard</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Come to find out, dualism has a limited but necessary role in resolving the human dilemma, i.e., in living.  The percentage is maybe 20% most of the time, but it's possibly 90% some of the time.  The English composer James Bernard is Exhibit A here, and a most brilliant exhibit his work has become.]]>
            </description>
            <itunes:subtitle>Come to find out, dualism has a limited but necessary role in resolving the human dilemma, i.e., in living.  The percentage is maybe 20% most of the time, but it's possibly 90% some of the time.  The English composer James Bernard is Exhibit A here, and a most brilliant exhibit his work has become.</itunes:subtitle>
            <itunes:summary>Come to find out, dualism has a limited but necessary role in resolving the human dilemma, i.e., in living.  The percentage is maybe 20% most of the time, but it's possibly 90% some of the time.  The English composer James Bernard is Exhibit A here, and a most brilliant exhibit his work has become.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_181_-_dualism_clinic_with_james_bernard_2.m4a" length="21826302" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_181_-_dualism_clinic_with_james_bernard_2.m4a</guid>
            <pubDate>Sun, 25 Jan 2015 20:41:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:22:24</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 180 - Metropolitan Life</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[]This is the tableau of a childhood memory,
a memory that came literally to life recently.
I entered a dream, but then the dream was real.
A little like the The Lion, the Witch and the Wardrobe but in reverse.
With help from Orpheus and, by way of backdraft,
the Warrens. ]]>
            </description>
            <itunes:subtitle>This is the tableau of a childhood memory, a memory that came literally to life recently. I entered a dream, but then the dream was real. A little like the The Lion, the Witch and the Wardrobe but in reverse. With help from Orpheus and, by way of backdraft, the Warrens.</itunes:subtitle>
            <itunes:summary>This is the tableau of a childhood memory, a memory that came literally to life recently. I entered a dream, but then the dream was real. A little like the The Lion, the Witch and the Wardrobe but in reverse. With help from Orpheus and, by way of backdraft, the Warrens.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_180_-_metropolitan_life_2.m4a" length="22134345" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_180_-_metropolitan_life_2.m4a</guid>
            <pubDate>Fri, 21 Nov 2014 20:41:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:22:43</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 179 - Ere the Winter Storms</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Why are so many unchanged, I mean fundamentally unchanged,
by the red lights of life?  What accounts for persons' resistance
to the lessons of catastrophe? This week Robert W. Anderson, not 'Sister Mary Ignatius', explains it all to us.]]>
            </description>
            <itunes:subtitle>Why are so many unchanged, I mean fundamentally unchanged, by the red lights of life?  What accounts for persons' resistance to the lessons of catastrophe? This week Robert W. Anderson, not 'Sister Mary Ignatius', explains it all to us.</itunes:subtitle>
            <itunes:summary>Why are so many unchanged, I mean fundamentally unchanged, by the red lights of life?  What accounts for persons' resistance to the lessons of catastrophe? This week Robert W. Anderson, not 'Sister Mary Ignatius', explains it all to us.</itunes:summary>
            <link>http://mbird.com/podcastgen/media/2015-02-13_episode_179_-_ere_the_winter_storms.m4a</link>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_179_-_ere_the_winter_storms.m4a" length="24274109" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_179_-_ere_the_winter_storms.m4a</guid>
            <pubDate>Fri, 14 Nov 2014 20:41:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:24:54</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 178 - Without Which Not</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Things recently got so bad somewhere that it looks like all hope is gone.
The thing "imploded", like 'Susan' in The Buckinghams' otherwise cheery pop single.
Poor Susan!
Is there still hope?  PZ thinks there is.  But it comes from over the border!
And from the year 1917.]]>
            </description>
            <itunes:subtitle>Things recently got so bad somewhere that it looks like all hope is gone. The thing "imploded", like 'Susan' in The Buckinghams' otherwise cheery pop single. Poor Susan! Is there still hope?  PZ thinks there is.  But it comes from over the border! And from the year 1917.</itunes:subtitle>
            <itunes:summary>Things recently got so bad somewhere that it looks like all hope is gone.
                The thing "imploded", like 'Susan' in The Buckinghams' otherwise cheery pop single.
                Poor Susan!
                Is there still hope?  PZ thinks there is.  But it comes from over the border!
                And from the year 1917.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_178_-_without_which_not.m4a" length="13740921" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_178_-_without_which_not.m4a</guid>
            <pubDate>Wed, 29 Oct 2014 20:41:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:27:59</itunes:duration>
            <itunes:keywords />
        </item>

        <item>
            <title>Episode 177 - Whipped Cream</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Big explosions in Church!
Like at the beginning of "Cloverfield".
What do they mean?
Is there any hope in the aftermath?
Well, would I be recording this if I didn't think so,
from Lake Tahoe, as it turns out?
With help from Herb Alpert.
And Jane Austen.
This podcast is dedicated to Melina and Jacob Smith.]]>
            </description>
            <itunes:subtitle>Big explosions in Church! Like at the beginning of "Cloverfield". What do they mean? s there any hope in the aftermath?</itunes:subtitle>
            <itunes:summary>Big explosions in Church!
                Like at the beginning of "Cloverfield".
                What do they mean?
                Is there any hope in the aftermath?
                Well, would I be recording this if I didn't think so,
                from Lake Tahoe, as it turns out?
                With help from Herb Alpert.
                And Jane Austen.
                This podcast is dedicated to Melina and Jacob Smith.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_podcast_177_-_whipped_cream.m4a" length="13906649" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_podcast_177_-_whipped_cream.m4a</guid>
            <pubDate>Tue, 07 Oct 2014 20:41:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:28:20</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 176 - Everything Is Tuesday</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[August Coda.
Labor Day Coda.
General Johnson Coda.
Mergers Not Acquisitions Coda.
]]>
            </description>
            <itunes:subtitle>August Coda. Labor Day Coda. General Johnson Coda.  Mergers Not Acquisitions Coda.  </itunes:subtitle>
            <itunes:summary>August Coda.
                Labor Day Coda.
                General Johnson Coda.
                Mergers Not Acquisitions Coda.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_176_-_everything_is_tuesday_2.m4a" length="10031360" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_176_-_everything_is_tuesday_2.m4a</guid>
            <pubDate>Tue, 26 Aug 2014 20:41:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:20:12</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 175 - Does the Name Grimsby Do Anything to You?</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[An August summation,
from one explorer to hopefully others.
Rod Serling describes a unique case of one.
Then Armando Trovajoli puts into music the
secret of life.  Yes, the secret of life.]]>
            </description>
            <itunes:subtitle>A little August summation, from one explorer to hopefully others.  Rod Serling writes about an under-appreciated instance of one.  Then Armando Trovajoli delivers the secret of life.  The secret of life.</itunes:subtitle>
            <itunes:summary>An August summation,
                from one explorer to hopefully others.
                Rod Serling describes a unique case of one.
                Then Armando Trovajoli puts into music the
                secret of life.  Yes, the secret of life.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_175_-_grimsby.m4a" length="14195632" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_175_-_grimsby.m4a</guid>
            <pubDate>Tue, 26 Aug 2014 14:55:05 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:28:41</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 174 - Federal Theology in the Letters of Samuel Rutherford</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["So, here's the thing." :
Wanna know what faith is?
Listen to ABBA.
Wanna arrest the decline of,
oh, let's say,
mainstream Protestantism?
Listen to ABBA.
Wanna understand yourself?
Listen to ABBA.]]>
            </description>
            <itunes:subtitle>&quot;So, here&apos;s the thing&quot;: You wanna know about faith? Listen to ABBA. Wanna arrest the decline of, oh, let&apos;s say, mainstream Protestantism? Listen to ABBA.</itunes:subtitle>
            <itunes:summary>&quot;So, here&apos;s the thing.&quot; :
                Wanna know what faith is?
                Listen to ABBA.
                Wanna arrest the decline of,
                oh, let&apos;s say,
                mainstream Protestantism?
                Listen to ABBA.
                Wanna understand yourself?
                Listen to ABBA.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_174_-_federal_theology_in_the_letters_of_samuel_rutherford.m4a" length="14365392" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_174_-_federal_theology_in_the_letters_of_samuel_rutherford.m4a</guid>
            <pubDate>Fri, 22 Aug 2014 17:52:22 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:29:01</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 173 - And the Winner Is</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[There is so much truth here.
So much emotional truth, I mean.
It could have been someone else.
It could have been something else.
It could have come from somewhere else.
But it came from
ABBA.]]>
            </description>
            <itunes:subtitle>There is so much truth here.  Emotional truth, I mean.  It could have been somebody else.  It could have been something else.  But the truth was from ABBA.</itunes:subtitle>
            <itunes:summary>There is so much truth here.
                So much emotional truth, I mean.
                It could have been someone else.
                It could have been something else.
                It could have come from somewhere else.
                But it came from
                ABBA.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_173_-_and_the_winner_is.m4a" length="13229200" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_173_-_and_the_winner_is.m4a</guid>
            <pubDate>Thu, 21 Aug 2014 10:50:06 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:26:43</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 172 - Phony Wars</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[The subject is reality vs. ideology.  'Pet' Clark wanted to be Superwoman.
I wanted to be a totally focussed pastor, great dad, and good husband.
'Helen' wanted to be "a woman of today".  We all failed!   "Sorry, it's not possible" (Petula says).  And yet, a little child has led me.  Lower case.
But upper case, too.]]>
            </description>
            <itunes:subtitle>The subject is reality vs. ideology. &apos;Pet&apos; Clark wanted to be something, I wanted to be something, &apos;Helen&apos; wanted to be something.  We all failed.  &quot;Sorry, it&apos;s not possible&quot;. And yet, a little child led me -- lower case and upper case.</itunes:subtitle>
            <itunes:summary>The subject is reality vs. ideology.  &apos;Pet&apos; Clark wanted to be Superwoman.
                I wanted to be a totally focussed pastor, great dad, and good husband.
                &apos;Helen&apos; wanted to be &quot;a woman of today&quot;.  We all failed!   &quot;Sorry, it&apos;s not possible&quot; (Petula says).  And yet, a little child has led me.  Lower case.
                But upper case, too.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_172_-_phony_wars.m4a" length="0" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_172_-_phony_wars.m4a</guid>
            <pubDate>Wed, 20 Aug 2014 18:47:14 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:31:16</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 171 - If You Can&apos;t Stand the Heat</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Here is made a principled decision to opt out, of all manner of causes and notions.  With the injunction, however, that in order to heal, you have to feel.
Eric Clapton starts us out.  The House Band brings it on home.
]]>
            </description>
            <itunes:subtitle>In which is made a principled decision to OPT OUT, of all manner of causes and notions.  Yet, too, the injunction that in order to heal, you have to feel.  Eric Clapton starts us out.  The House Band brings it on home.</itunes:subtitle>
            <itunes:summary>Here is made a principled decision to opt out, of all manner of causes and notions.  With the injunction, however, that in order to heal, you have to feel.
                Eric Clapton starts us out.  The House Band brings it on home.
            </itunes:summary>
            <enclosure url="http:/mbird.com/podcastgen/media/2015-02-13_episode_171_-_if_you_can%27t_stand_the_heat....m4a" length="18081568" type="audio/x-m4a"/>
            <guid>http:/mbird.com/podcastgen/media/2015-02-13_episode_171_-_if_you_can%27t_stand_the_heat....m4a</guid>
            <pubDate>Thu, 07 Aug 2014 14:21:38 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:36:36</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 170 - Farewell to the First Golden Era</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is a podcast to celebrate:  my 170th, in which are offered
some Summer reading, a Concluding Un-Scientific Postscript,
and the best track ever recorded by a certain Wonder.
Hope you like it!]]>
            </description>
            <itunes:subtitle>This is one to celebrate, the  170th, in which are offered some Summer reading, a concluding un-scientific postscript, and the best song ever recorded by a certain Wonder. </itunes:subtitle>
            <itunes:summary>This is a podcast to celebrate:  my 170th, in which are offered
                some Summer reading, a Concluding Un-Scientific Postscript,
                and the best track ever recorded by a certain Wonder.
                Hope you like it!</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_170_-_back_towards_the_middle.m4a" length="12631088" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_170_-_back_towards_the_middle.m4a</guid>
            <pubDate>Tue, 10 Jun 2014 10:10:20 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:25:29</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 169 - Wooden Ships</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is about Meister Eckhart and Rudolf Otto, and CS & N.
But it's really about whether and how to engage the world,
given what we now know about it.  Guess I'm  skeptical, more than ever;
and was surprised to have to dissent from the Master.
First time!]]>
            </description>
            <itunes:subtitle>This is about Meister Eckhart and Rudolf Otto, and CS &amp; N.  But it&apos;s REALLY about how and whether to engage the world -- given what we now know.  I&apos;m skeptical,  and was surprised to find myself dissenting from the Master. First time!</itunes:subtitle>
            <itunes:summary>This is about Meister Eckhart and Rudolf Otto, and CS &amp; N.
                But it&apos;s really about whether and how to engage the world,
                given what we now know about it.  Guess I&apos;m  skeptical, more than ever;
                and was surprised to have to dissent from the Master.
                First time!</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_169_-_wooden_ships.m4a" length="20674992" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_169_-_wooden_ships.m4a</guid>
            <pubDate>Fri, 23 May 2014 05:56:47 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:41:53</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 168 - &quot;Generation Zahl&quot;</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[A penetrating and courageous televison program from Germany opened me up recently.  It was an instance of what Stefan Kolditz, the writer of the program, called a "non-ideological access" to a tragedy.
But not just their tragedy.  My tragedy.  Yours, too, maybe.]]>
            </description>
            <itunes:subtitle>A penetrating and courageous  television program from Germany opened me up recently.  It was what Stefan Kolditz,  who wrote the screenplay,  called a &quot;non-ideological access&quot; to a tragedy.  Not just their tragedy.  My tragedy. Yours, too, maybe.</itunes:subtitle>
            <itunes:summary>A penetrating and courageous televison program from Germany opened me up recently.  It was an instance of what Stefan Kolditz, the writer of the program, called a &quot;non-ideological access&quot; to a tragedy.
                But not just their tragedy.  My tragedy.  Yours, too, maybe.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_168_-__generation_zahl_.m4a" length="0" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_168_-__generation_zahl_.m4a</guid>
            <pubDate>Sun, 18 May 2014 14:57:29 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:29:27</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 167 - Emotion</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is all about one thing.
It didn't take Melanchthon to teach me about it,
nor Thomas Cranmer.
No.
It took Burton Cummings to teach me about it.
And life!
So Stand Tall; and for God's sake, don't do something foolish.]]>
            </description>
            <itunes:subtitle>This is all about One Thing, and for me it&apos;s the core.  It didn&apos;t take Melanchthon to teach it to me, nor Thomas Cranmer.  No.  It took Burton Cummings to teach me.  And life.  So Stand Tall; and for God&apos;s sake, don&apos;t do something foolish.</itunes:subtitle>
            <itunes:summary>This is all about one thing.
                It didn&apos;t take Melanchthon to teach me about it,
                nor Thomas Cranmer.
                No.
                It took Burton Cummings to teach me about it.
                And life!
                So Stand Tall; and for God&apos;s sake, don&apos;t do something foolish.</itunes:summary>
            <link>http://mbird.com/podcastgen/media/2015-02-13_episode_167_-_emotion.m4a</link>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_167_-_emotion.m4a" length="14922704" type="audio/x-m4a"/>
            <pubDate>Fri, 09 May 2014 12:51:53 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:10</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 166 - The House That Jack Built</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Well, the glass ceiling is finally breaking.
It's happening right before our eyes.
But Aretha's going to help us see the "Kehrseite".
With a little help from Lesley Gore, too.
"Come and see." (John 1:46)]]>
            </description>
            <itunes:subtitle>Well, the glass ceiling is finally breaking, and it&apos;s taking place right in front of our eyes.  But Aretha&apos;s going to help us see the &quot;Kehrseite&quot;, with a boost from wonderful Lesley Gore.  &quot;Come and See&quot; (John 1:46).</itunes:subtitle>
            <itunes:summary>Well, the glass ceiling is finally breaking.
                It&apos;s happening right before our eyes.
                But Aretha&apos;s going to help us see the &quot;Kehrseite&quot;.
                With a little help from Lesley Gore, too.
                &quot;Come and see.&quot; (John 1:46)</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_166_-_the_house_that_jack_built.m4a" length="13649984" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_166_-_the_house_that_jack_built.m4a</guid>
            <pubDate>Wed, 30 Apr 2014 17:40:56 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:27:34</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 165 - Cosmic Recension</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Meister Eckhart,
meet Burton Cummings.
And Randy Bachman.
And me.]]>
            </description>
            <itunes:subtitle>Meister Eckhart, meet Burton Cummings. And Randy Bachman. And me.</itunes:subtitle>
            <itunes:summary>Meister Eckhart,
                meet Burton Cummings.
                And Randy Bachman.
                And me.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_165_-_cosmic_recension.m4a" length="11554944" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_165_-_cosmic_recension.m4a</guid>
            <pubDate>Fri, 11 Apr 2014 15:39:58 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:23:18</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 164 - Happy Clappy</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["No use calling, 'cause the sky is falling,
and I'm getting pretty near the end."
This concerns the practical consequences of
(near-)death in life.  Join forces with Wolfman Jack (R.I.P.)
and The Guess Who; and 'Charlie Kane'.]]>
            </description>
            <itunes:subtitle>&quot;No use calling, &apos;cause the sky is falling, and I&apos;m getting pretty near the end.&quot;  This is in further explanation of (near-)death and its practical consequences for &quot;Everyday People&quot; (Sly &amp; co.).  Therefore...</itunes:subtitle>
            <itunes:summary>&quot;No use calling, &apos;cause the sky is falling,
                and I&apos;m getting pretty near the end.&quot;
                This concerns the practical consequences of
                (near-)death in life.  Join forces with Wolfman Jack (R.I.P.)
                and The Guess Who; and &apos;Charlie Kane&apos;.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_164_-_happy_clappy.m4a" length="0" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_164_-_happy_clappy.m4a</guid>
            <pubDate>Wed, 19 Feb 2014 10:05:11 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:34</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 163 -- Deetour</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[It's getting bigger.  Bigger, at least, from where I sit.
The Contraption, I mean.
And thank you, Karen Young!
And thank you, Mike Francis!
This podcast is dedicated to JAZ, the Minister of Edits.]]>
            </description>
            <itunes:subtitle>It&apos;s getting bigger.  Bigger, at least, from where I sit.  The Contraption, I mean. And thank you, Karen Young!  And thank you, Mike Francis!  Episode 163 is dedicated to the Minister of Edits.</itunes:subtitle>
            <itunes:summary>It&apos;s getting bigger.  Bigger, at least, from where I sit.
                The Contraption, I mean.
                And thank you, Karen Young!
                And thank you, Mike Francis!
                This podcast is dedicated to JAZ, the Minister of Edits.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_163_--_deetour.m4a" length="0" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_163_--_deetour.m4a</guid>
            <pubDate>Thu, 13 Feb 2014 10:43:47 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:32:50</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 162 - Rain Dance</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Thinking about Obamacare got me onto this one.
But it's not about Obamacare!
It's about Reality.
And Guess What?]]>
            </description>
            <itunes:subtitle>Thinking about Obamacare, the circular argument of the century, got me onto this.  But it&apos;s not about Obamacare!  It&apos;s about Reality.  And Guess What?</itunes:subtitle>
            <itunes:summary>Thinking about Obamacare got me onto this one.
                But it&apos;s not about Obamacare!
                It&apos;s about Reality.
                And Guess What?</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_162_-_rain_dance.m4a" length="13819952" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_162_-_rain_dance.m4a</guid>
            <pubDate>Wed, 05 Feb 2014 11:43:50 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:27:55</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 161 - PBS</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[That's Percy Bysshe Shelley,
who gets a little help -- as if he needed it -- from Eric Burdon,
and B.T.O, and John Harris Harper.
And MAY this meditation on termination not be half-baked.]]>
            </description>
            <itunes:subtitle>That&apos;s Percy Bysshe Shelley, with a little help from Eric Burdon, and from B.T.O., and from John Harris Harper. And MAY this meditation on termination not be half-baked.</itunes:subtitle>
            <itunes:summary>That&apos;s Percy Bysshe Shelley,
                who gets a little help -- as if he needed it -- from Eric Burdon,
                and B.T.O, and John Harris Harper.
                And MAY this meditation on termination not be half-baked.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_161_-_pbs.m4a" length="0" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_161_-_pbs.m4a</guid>
            <pubDate>Thu, 09 Jan 2014 10:38:57 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:51</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 160 - Who Is Going To Love Me?</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[How can we know God?
Where is God locatable?
With a little help from D. Warwick and a little from St. John,
I want to answer.
Podcast 160 is dedicated to Jono Linebaugh.]]>
            </description>
            <itunes:subtitle>How can we know God?  Where is God locatable?  With a little help from Dionne Warwick and a little from St. John, I want to answer.  Podcast 160 is dedicated to Jono Linebaugh.</itunes:subtitle>
            <itunes:summary>How can we know God?
                Where is God locatable?
                With a little help from D. Warwick and a little from St. John,
                I want to answer.
                Podcast 160 is dedicated to Jono Linebaugh.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_160_-_blp_oil_spill.m4a" length="13490336" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_160_-_blp_oil_spill.m4a</guid>
            <pubDate>Fri, 20 Dec 2013 08:14:41 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:43</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 159 - The Happiest Actual Life</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[It's really possible:
"the happiest actual life", I mean.
That was Booth Tarkington's phrase for the hope we could have
in real terms, even when circumstances went against us
and our intrinsic indelible nature went against us.
Case in point: his novel "Alice Adams" (1921).
Case in point: his character 'Alice Adams'.
I think the story is so real as to be Real.
]]>
            </description>
            <itunes:subtitle>It&apos;s really possible.  To have &quot;the happiest actual life&quot;, I mean.  The phrase is from Booth Tarkington, who portrays something like that life in his novel &quot;Alice Adams&quot; (1921).  </itunes:subtitle>
            <itunes:summary>It&apos;s really possible:
                &quot;the happiest actual life&quot;, I mean.
                That was Booth Tarkington&apos;s phrase for the hope we could have
                in real terms, even when circumstances went against us
                and our intrinsic indelible nature went against us.
                Case in point: his novel &quot;Alice Adams&quot; (1921).
                Case in point: his character &apos;Alice Adams&apos;.
                I think the story is so real as to be Real.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_159_-_the_happiest_actual_life.m4a" length="0" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_159_-_the_happiest_actual_life.m4a</guid>
            <pubDate>Sun, 17 Nov 2013 10:34:52 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:28:58</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 158 - Changing Social Conditions in Indianapolis</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Boy, do we need a miracle.
Such things really happen.
As in Booth Tarkington, and as in John Galsworthy.
As in me and you.
And as in: The Buckinghams.]]>
            </description>
            <itunes:subtitle>Boy, we need a miracle.  And such things really happen: as in Tarkington, as in Galsworthy.  As in me and you. And as in: The Buckinghams.</itunes:subtitle>
            <itunes:summary>Boy, do we need a miracle.
                Such things really happen.
                As in Booth Tarkington, and as in John Galsworthy.
                As in me and you.
                And as in: The Buckinghams.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_158_-_changing_social_conditions_in_indianapolis.m4a" length="17032976" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_158_-_changing_social_conditions_in_indianapolis.m4a</guid>
            <pubDate>Mon, 11 Nov 2013 08:01:57 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:34:28</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 157 - Every Mother&apos;s Son</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Taking a break now for a couple weeks, but wanted to leave a little white-pebble trail -- not of tears, but of hope.  "Come on down to my boat, baby"; and I'm talking about you, Miss Wyckoff; and you, Mr. Cardew; and you, Mr. Zahl.]]>
            </description>
            <itunes:subtitle>Taking a break now for a few weeks, but wanted to leave a white-pebble trail,  not of tears but of hope.  &quot;Come on down to my boat, baby&quot; -- and I&apos;m talking about you, Miss Wyckoff; and you, Mr. Rutherford; and you, Mr. Zahl.</itunes:subtitle>
            <itunes:summary>Taking a break now for a couple weeks, but wanted to leave a little white-pebble trail -- not of tears, but of hope.  &quot;Come on down to my boat, baby&quot;; and I&apos;m talking about you, Miss Wyckoff; and you, Mr. Cardew; and you, Mr. Zahl.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_157_-_sing_a_simple_song.m4a" length="14612400" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_157_-_sing_a_simple_song.m4a</guid>
            <pubDate>Sat, 19 Oct 2013 16:27:59 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:29:32</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 156 - I Am Curious (Orange)</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[A Protestant spin on a Golden Oldie from Sweden.
This is also a warning against categorization -- a very personal warning,
as I've suffered from categorization and feel it keenly still.
"Och du?"
]]>
            </description>
            <itunes:subtitle>A Protestant spin on a Copper Oldie, and a warning, a a very personal warning, against categorization.</itunes:subtitle>
            <itunes:summary>A Protestant spin on a Golden Oldie from Sweden.
                This is also a warning against categorization -- a very personal warning,
                as I&apos;ve suffered from categorization and feel it keenly still.
                &quot;Och du?&quot;
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_156_-_i_am_curious_(orange)_2.m4a" length="13042080" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_156_-_i_am_curious_(orange)_2.m4a</guid>
            <pubDate>Wed, 16 Oct 2013 16:55:48 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:26:20</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 155 - Mandy</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Alternate title: Mandy and the Episcopals.
Irving Berlin sets the stage;
Sandra Dee plays the lead,
together with Troy Donohue;
and James Gould Cozzens,
like Sister Mary Ignatius,
Explains It All for You.
]]>
            </description>
            <itunes:subtitle>Alternate title: Mandy and The Episcopals.  Irving Berlin sets the stage, Sandra Dee plays the lead (with Troy Donohue), and James Gould Cozzens, like Sister Mary Ignatius, explains it all for you.</itunes:subtitle>
            <itunes:summary>Alternate title: Mandy and the Episcopals.
                Irving Berlin sets the stage;
                Sandra Dee plays the lead,
                together with Troy Donohue;
                and James Gould Cozzens,
                like Sister Mary Ignatius,
                Explains It All for You.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_155_-_miss_o'dell.m4a" length="19367904" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_155_-_miss_o'dell.m4a</guid>
            <pubDate>Sun, 06 Oct 2013 13:53:38 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:37:50</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 154 - Kramer</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Kramer is my word for transmitted family dis-function and disease.
Kramer in this sense requires acute attention.
With help from The Contraption, Kramer actually can be reduced.
In this podcast, Richard Egan steps up to help us,
with a little help from Faith.
Percy, I mean.]]>
            </description>
            <itunes:subtitle>Kramer is my word for transmitted family dis-function and disease.  It needs acute attention.  With the help of The Contraption, Kramer can be reduced.  Richard Egan steps in to help us here.</itunes:subtitle>
            <itunes:summary>Kramer is my word for transmitted family dis-function and disease.
                Kramer in this sense requires acute attention.
                With help from The Contraption, Kramer actually can be reduced.
                In this podcast, Richard Egan steps up to help us,
                with a little help from Faith.
                Percy, I mean.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20154%20-%20Kramer.m4a" type="audio/x-m4a" length="14930096" />
            <guid>http://mbird.com/podcastgen/media/Episode%20154%20-%20Kramer.m4a</guid>
            <pubDate>Wed, 02 Oct 2013 11:21:50 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:11</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 153 - Love in the 40s</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[When you're 'mature', you're sometimes not.
I learned this in my 40s.
I first learned it in a parish, in 'Cheever country'.
But it was also in 'Miami Vice', every Friday night.
Valerie and Tubbs taught me,
as did 'Sonny' and Theresa.
And Jan Hammer.
There was all this dread, too.
Was it a dream?]]>
            </description>
            <itunes:subtitle>When you&apos;re &apos;mature&apos;,  you&apos;re sometimes not mature.  I learned this in my 40s.  Partly I was taught by &quot;Miami Vice&quot; and Jan Hammer.  Life felt SO serious, like Tubbs and Valerie, and Crockett and Theresa.  So full of dread. Or not.</itunes:subtitle>
            <itunes:summary>When you&apos;re &apos;mature&apos;, you&apos;re sometimes not.
                I learned this in my 40s.
                I first learned it in a parish, in &apos;Cheever country&apos;.
                But it was also in &apos;Miami Vice&apos;, every Friday night.
                Valerie and Tubbs taught me,
                as did &apos;Sonny&apos; and Theresa.
                And Jan Hammer.
                There was all this dread, too.
                Was it a dream?</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20153%20-%20Love%20in%20the%2040s%202.m4a" type="audio/x-m4a" length="15070880" />
            <guid>http://mbird.com/podcastgen/media/Episode%20153%20-%20Love%20in%20the%2040s%202.m4a</guid>
            <pubDate>Mon, 30 Sep 2013 15:12:53 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:28</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 152 - Groovy Kind of Love</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[The text is Isherwood's journal entry for August 3, 1967.
The topic:
How to grow in love for the people who are right around you.
Lesley Gore is going to help us, plus, naturally, William Hale White;
plus Gerald Heard; plus Wayne Fontana.]]>
            </description>
            <itunes:subtitle>The text is Isherwood&apos;s diary entry for August 3, 1967.  The topic: How can you grow in love for the people around you?  Lesley Gore is going to help us, plus William Hale White, as usual; plus Gerald Heard; plus, of course, Wayne Fontana.</itunes:subtitle>
            <itunes:summary>The text is Isherwood&apos;s journal entry for August 3, 1967.
                The topic:
                How to grow in love for the people who are right around you.
                Lesley Gore is going to help us, plus, naturally, William Hale White;
                plus Gerald Heard; plus Wayne Fontana.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_152_-_increase_of_affection.m4a" length="15805792" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_152_-_increase_of_affection.m4a</guid>
            <pubDate>Sun, 29 Sep 2013 12:03:23 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:31:58</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 151 - Girl Talk</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[I've just written a book.
It is called "PZ's Panopticon:
An Off-the-Wall Guide to World Religiion".
It's not about gender differences nor does it concern ideology.
It looks at the religions of the world in terms of one question:
What does this or that religion have to offer a dying person?
My book concerns religion for a person in extremis.
Dying seems to "concentrate the mind wonderfully" (Samuel Johnson).
I think it serves a most concentrating purpose in helping a person
sift through the wisdom of religion.
Oh, and by religion, I also mean religions that are not called religions,
such as celebrity, sex, things, one's children, one's life-partner,
one's ideology, and the power that you have and exercise in your life.
Power is a big religion.
Religion covers almost anything that habitually or functionally is
worshipped.
This podcast is dedicated to Ray Ortlund.]]>
            </description>
            <itunes:subtitle>I&apos;ve just written a book.  It&apos;s not about gender differences.  Nor is it about ideology.  It is about near death and dying; and what the world religions have to offer a person in extremis.  This podcast id dedicated to Ray Ortlund.</itunes:subtitle>
            <itunes:summary>I&apos;ve just written a book.
                It is called &quot;PZ&apos;s Panopticon:
                An Off-the-Wall Guide to World Religiion&quot;.
                It&apos;s not about gender differences nor does it concern ideology.
                It looks at the religions of the world in terms of one question:
                What does this or that religion have to offer a dying person?
                My book concerns religion for a person in extremis.
                Dying seems to &quot;concentrate the mind wonderfully&quot; (Samuel Johnson).
                I think it serves a most concentrating purpose in helping a person
                sift through the wisdom of religion.
                Oh, and by religion, I also mean religions that are not called religions,
                such as celebrity, sex, things, one&apos;s children, one&apos;s life-partner,
                one&apos;s ideology, and the power that you have and exercise in your life.
                Power is a big religion.
                Religion covers almost anything that habitually or functionally is
                worshipped.
                This podcast is dedicated to Ray Ortlund.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_151_-_girl_talk.m4a" length="17234672" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_151_-_girl_talk.m4a</guid>
            <pubDate>Fri, 27 Sep 2013 12:52:13 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:34:52</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 150 - Early Roman Kings</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is about the Ancient Romans:
their psychic position, their spirit-world of augury,
and the effect of the birth of Christ.
With help from Bob Dylan.
Two corrections, too:
The Thornton Wilder book is "The Woman of Andros",
and 'Camulodunum' was the Roman name for Colchester.
]]>
            </description>
            <itunes:subtitle>This is about the Ancient Romans: their psychic position, their reliance on augury, and the coming of Christ.  With help from Bob Dylan.  Two corrections, too: the Wilder book is &quot;The Woman of Andros&quot;; and &apos;Camulodunum&apos; was the Roman name for Colchester.</itunes:subtitle>
            <itunes:summary>This is about the Ancient Romans:
                their psychic position, their spirit-world of augury,
                and the effect of the birth of Christ.
                With help from Bob Dylan.
                Two corrections, too:
                The Thornton Wilder book is &quot;The Woman of Andros&quot;,
                and &apos;Camulodunum&apos; was the Roman name for Colchester.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_150_-_the_ancient_romans.m4a" length="24851504" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_150_-_the_ancient_romans.m4a</guid>
            <pubDate>Wed, 28 Aug 2013 12:44:34 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:50:24</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 149 - A Heartache, A Shadow, A Lifetime</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is a reflection on 45 years of New Testament scholarship.
That's 45 years in 45 minutes -- one minute for every year.
And Dave Mason puts it all in perspective.]]>
            </description>
            <itunes:subtitle>This is my reflection on 45 years of New Testament scholarship.  Forty-five years in 45 minutes.  That&apos;s one minute for every year. </itunes:subtitle>
            <itunes:summary>This is a reflection on 45 years of New Testament scholarship.
                That&apos;s 45 years in 45 minutes -- one minute for every year.
                And Dave Mason puts it all in perspective.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_149_-_a_heartache,_a_shadow,_a_lifetime.m4a" length="23270384" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_149_-_a_heartache,_a_shadow,_a_lifetime.m4a</guid>
            <pubDate>Sun, 25 Aug 2013 09:05:22 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:47:10</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 148 - INGSOC</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["A little trick with Dick" (The Name Game):
This is about language, control, and Purr-FEC
tion.  With thanks to Eric Blair, too.]]>
            </description>
            <itunes:subtitle>&quot;A little trick with Dick&quot; (The Name Game).  This is about language, control, and Purr-FECtion.</itunes:subtitle>
            <itunes:summary>&quot;A little trick with Dick&quot; (The Name Game):
                This is about language, control, and Purr-FEC
                tion.  With thanks to Eric Blair, too.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_148_-_ingsoc.m4a" length="13898304" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_148_-_ingsoc.m4a</guid>
            <pubDate>Sun, 04 Aug 2013 11:16:43 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:28:04</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 147 - Transcendence</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[What can you do when you're face to face with The Antagonist?
I'll tell you this much: no one gets out of here alive.
Unless there are Martians.

This podcast is about suffering, and it's also about transcendence.]]>
            </description>
            <itunes:subtitle>What can you do when you&apos;re face to face with The Antagonist?  Well, I&apos;ll tell you this much: no one gets out of here alive.  Unless, however, there are Martians.  This talk is about suffering and it&apos;s about transcendence.</itunes:subtitle>
            <itunes:summary>What can you do when you&apos;re face to face with The Antagonist?
                I&apos;ll tell you this much: no one gets out of here alive.
                Unless there are Martians.

                This podcast is about suffering, and it&apos;s also about transcendence.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20147%20-%20Transcendence.m4a" length="0" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/Episode%20147%20-%20Transcendence.m4a</guid>
            <pubDate>Sat, 03 Aug 2013 14:52:53 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:22:25</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 146 - Sermon for the Feast Day of Hey Jude</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[It's about nervous breakdowns -- maybe your 19th.
It's about George's Way with us.
And it's about the music.]]>
            </description>
            <itunes:subtitle>It&apos;s about nervous breakdowns -- maybe your 19th.  And George&apos;s Way with us.</itunes:subtitle>
            <itunes:summary>It&apos;s about nervous breakdowns -- maybe your 19th.
                It&apos;s about George&apos;s Way with us.
                And it&apos;s about the music.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_146_-_sermon_for_the_feast_day_of_hey_jude.m4a" length="12258048" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_146_-_sermon_for_the_feast_day_of_hey_jude.m4a</guid>
            <pubDate>Fri, 28 Jun 2013 10:25:13 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:24:44</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 145 - Soul Coaxing</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[What's really important?
"Soul Coaxing" is really important.
But not the practice.
The song!
By Raymond Lefevre and his Orchestra.
THAT's really important.
Gosh, I hope you like this.
]]>
            </description>
            <itunes:subtitle>What&apos;s really important?  I think &quot;Soul Coaxing&quot; is really important.  But not the  practice.  The song!  By Raymond Lefevre and his Orchestra.  Gosh, I hope you like this.</itunes:subtitle>
            <itunes:summary>What&apos;s really important?
                &quot;Soul Coaxing&quot; is really important.
                But not the practice.
                The song!
                By Raymond Lefevre and his Orchestra.
                THAT&apos;s really important.
                Gosh, I hope you like this.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_145_-_soul_coaxing_3.m4a" length="10454832" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_145_-_soul_coaxing_3.m4a</guid>
            <pubDate>Mon, 24 Jun 2013 09:57:19 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:21:03</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 144 - Good Luck, Miss Wyckoff</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Here we journey to the outer limits of compassion.
Will that suffice?
Or do we need a little help from our friends --
like Jeff Beck, maybe.]]>
            </description>
            <itunes:subtitle>Here we journey to the outer limits of compassion.  Will that suffice?  Or do need a little help from our friends -- like Jeff Beck, maybe.</itunes:subtitle>
            <itunes:summary>Here we journey to the outer limits of compassion.
                Will that suffice?
                Or do we need a little help from our friends --
                like Jeff Beck, maybe.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_144_-_good_luck,_miss_wyckoff.m4a" length="15235792" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_144_-_good_luck,_miss_wyckoff.m4a</guid>
            <pubDate>Sat, 22 Jun 2013 16:10:41 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:48</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 143 - Old Man River</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[And now a word from our sponsor -- George!]]>
            </description>
            <itunes:subtitle>And now a word from our sponsor -- George!</itunes:subtitle>
            <itunes:summary>And now a word from our sponsor -- George!</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_143_-_old_man_river_2.m4a" length="11307968" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_143_-_old_man_river_2.m4a</guid>
            <pubDate>Thu, 16 May 2013 04:31:51 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:22:48</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 142 - Girl Can&apos;t Help It</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[In which I talk about George, my new hero.]]>
            </description>
            <itunes:subtitle>In which I talk about George, my new hero.</itunes:subtitle>
            <itunes:summary>In which I talk about George, my new hero.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_142_-_girl_can't_help_it.m4a" length="16420160" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_142_-_girl_can't_help_it.m4a</guid>
            <pubDate>Wed, 15 May 2013 15:03:43 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:13</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 141 - Easter with Los Straitjackets</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Here's the Gospel as I would put it this Easter.
It's never not been the Have Mercy on Me (Cannonball Adderley/
The Buckinghams) of God in relation to the Outta Gear (Los Straitjackets) of us.  But it needs to not become a mental exercise.
It needs to be hooked into us, the whole being of our compounded selves.
If it's not making the connection, then it will fail.
(And it often does.)]]>
            </description>
            <itunes:subtitle>Here&apos;s the Gospel as I see it this Easter.  It&apos;s never not the Have Mercy on Me (Cannonball Adderley) of God in relation to the Outta Gear (Los Straitjackets) of us. But it&apos;s gotta stop being mental, and start being, well, hooked in.</itunes:subtitle>
            <itunes:summary>Here&apos;s the Gospel as I would put it this Easter.
                It&apos;s never not been the Have Mercy on Me (Cannonball Adderley/
                The Buckinghams) of God in relation to the Outta Gear (Los Straitjackets) of us.  But it needs to not become a mental exercise.
                It needs to be hooked into us, the whole being of our compounded selves.
                If it&apos;s not making the connection, then it will fail.
                (And it often does.)</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_141_-_outta_gear.m4a" length="14726512" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_141_-_outta_gear.m4a</guid>
            <pubDate>Thu, 21 Mar 2013 11:21:12 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:29:46</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 140 - Make It Easy on Yourself</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is a meditation on self-forgiveness.
I used to think that was a lame phrase,
an exercise in twaddle.
Not so!
Here we have The Walker Brothers,
Los Straitjackets, even Frankie (Goes to Hollywood).
The Lesson This Morning is from Isherwood's journal entry of
July 14, 1940, which is to say,
the Second of the Two Great Commandments.]]>
            </description>
            <itunes:subtitle>A meditation on self-forgiveness.  Used to think that was a lame phrase, an exercise in twaddle.  Not so!</itunes:subtitle>
            <itunes:summary>This is a meditation on self-forgiveness.
                I used to think that was a lame phrase,
                an exercise in twaddle.
                Not so!
                Here we have The Walker Brothers,
                Los Straitjackets, even Frankie (Goes to Hollywood).
                The Lesson This Morning is from Isherwood&apos;s journal entry of
                July 14, 1940, which is to say,
                the Second of the Two Great Commandments.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_140_-_make_it_easy_on_yourself.m4a" length="16524768" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_140_-_make_it_easy_on_yourself.m4a</guid>
            <pubDate>Sun, 17 Mar 2013 09:57:19 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:25</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 139 - Journey with Boo (Me and You)</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[It's here:  that surgical song by Lobo, the balladeer's portrait of an ordinary, heart-rending tragedy.  Because the picture's true to life, however, there may be room for hope.
Roll up for a magical mystery tour, -- with a Dog Named Boo.]]>
            </description>
            <itunes:subtitle>It&apos;s here, that surgical song by Lobo, the artist&apos;s sympathetic portrait of a common heartfelt situation.  Because it&apos;s true to life, however, there may be some hope.  Roll up, for a magical mystery tour, with a Dog Named Boo.</itunes:subtitle>
            <itunes:summary>It&apos;s here:  that surgical song by Lobo, the balladeer&apos;s portrait of an ordinary, heart-rending tragedy.  Because the picture&apos;s true to life, however, there may be room for hope.
                Roll up for a magical mystery tour, -- with a Dog Named Boo.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20139%20-%20Journey%20with%20Boo%2C%20Me%20and%20You.m4a" length="0" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/Episode%20139%20-%20Journey%20with%20Boo%2C%20Me%20and%20You.m4a</guid>
            <pubDate>Fri, 15 Feb 2013 13:31:28 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:31:17</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 138 - Lobo&apos;s Dating Tips for Christian Guys</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[He has much to teach us!
This podcast, for me, is Camp.]]>
            </description>
            <itunes:subtitle>He has much to teach us!  This podcast, for me, is Camp.</itunes:subtitle>
            <itunes:summary>He has much to teach us!
                This podcast, for me, is Camp.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_138_-_lobo's_dating_tips_for_christian_guys.m4a" length="10944880" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_138_-_lobo's_dating_tips_for_christian_guys.m4a</guid>
            <pubDate>Thu, 14 Feb 2013 11:01:28 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:22:03</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 137 - Hero of the War</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[A short exegesis of personal pacifism.
Scott Walker's song "Hero of the War" made me do it!
Oh, and it's John Lennon in "Oh! What a Lovely War".
That's a correction.]]>
            </description>
            <itunes:subtitle>A short exegesis concerning personal pacifism.  Scott Walker&apos;s song &quot;Hero of the War&quot; MADE me do it!  Oh, and it&apos;s John Lennon in &quot;Oh! What a Lovely War&quot;.  That&apos;s a correction.</itunes:subtitle>
            <itunes:summary>A short exegesis of personal pacifism.
                Scott Walker&apos;s song &quot;Hero of the War&quot; made me do it!
                Oh, and it&apos;s John Lennon in &quot;Oh! What a Lovely War&quot;.
                That&apos;s a correction.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_137_-_hero_of_the_war.m4a" length="11651648" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_137_-_hero_of_the_war.m4a</guid>
            <pubDate>Sat, 02 Feb 2013 12:38:17 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:23:30</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 136 - Peaches La Verne</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[The La Verne Seminar, which took place in the Summer of 1941,
is the second most desired destination for PZ the Time Traveler.
If only one could have been there.  It was the ultimate religious retreat!

But still, I think I'd choose, for first place, if I had to choose,
a trip to Universal Studios during the Great Depression,
to witness the filming of that most desired of all works of cinema art:
The Bride of Frankenstein.]]>
            </description>
            <itunes:subtitle>The La Verne Seminar, which took place in the Summer of 1941, is the second most important destination for PZ the Time Traveler.  Here&apos;s why.  It was a credible, persuasive religious retreat.  If only one had been able to be there!</itunes:subtitle>
            <itunes:summary>The La Verne Seminar, which took place in the Summer of 1941,
                is the second most desired destination for PZ the Time Traveler.
                If only one could have been there.  It was the ultimate religious retreat!

                But still, I think I&apos;d choose, for first place, if I had to choose,
                a trip to Universal Studios during the Great Depression,
                to witness the filming of that most desired of all works of cinema art:
                The Bride of Frankenstein.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20136%20-%20Peaches%20La%20Verne.m4a" length="0" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/Episode%20136%20-%20Peaches%20La%20Verne.m4a</guid>
            <pubDate>Thu, 31 Jan 2013 10:13:08 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:28:38</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 135 - Elevator</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[That's Where the Happy People Go!
Here is "a new way of talking, a new way of walking" --
about praying, about grace, about One Love and the
Underground River.
Jerry Lewis (but you won't like this) has a walk-on, too.]]>
            </description>
            <itunes:subtitle>That&apos;s Where the Happy People Go!</itunes:subtitle>
            <itunes:summary>That&apos;s Where the Happy People Go!
                Here is &quot;a new way of talking, a new way of walking&quot; --
                about praying, about grace, about One Love and the
                Underground River.
                Jerry Lewis (but you won&apos;t like this) has a walk-on, too.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_135_-_elevator.m4a" length="17380016" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_135_-_elevator.m4a</guid>
            <pubDate>Wed, 30 Jan 2013 11:36:52 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:35:10</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 134 - Pillar of Salt</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[The music! --
evoking Lot's wife and then the Lord's words to St. Peter.
I guess I think it's more and more about the music.
But let's here it for the Haiku,
tu.]]>
            </description>
            <itunes:subtitle>The music -- evoking Lot&apos;s wife, and then the Lord&apos;s words to Saint Peter.  I sometimes think it&apos;s all about the music.  </itunes:subtitle>
            <itunes:summary>The music! --
                evoking Lot&apos;s wife and then the Lord&apos;s words to St. Peter.
                I guess I think it&apos;s more and more about the music.
                But let&apos;s here it for the Haiku,
                tu.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20134%20-%20Pillar%20of%20Salt.m4a" length="0" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/Episode%20134%20-%20Pillar%20of%20Salt.m4a</guid>
            <pubDate>Thu, 24 Jan 2013 10:45:23 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:21:24</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 133 - Brandy Station</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is not a case of "interpretive signage"  !
You'll have to make up your mind on your own.
But Looking Glass will be there to help you,
followed by, close by, Scott W.]]>
            </description>
            <itunes:subtitle>This is not a case of &quot;interpretive signage&quot;! You&apos;ll have to make up your own mind. But Looking Glass is going to help you, together with, coming right behind, Scott W.</itunes:subtitle>
            <itunes:summary>This is not a case of &quot;interpretive signage&quot;  !
                You&apos;ll have to make up your mind on your own.
                But Looking Glass will be there to help you,
                followed by, close by, Scott W.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20133%20-%20Brandy%20Station.m4a" length="0" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/Episode%20133%20-%20Brandy%20Station.m4a</guid>
            <pubDate>Thu, 24 Jan 2013 07:54:05 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:19</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 132 - Love in the First Degree</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is about forging forward
in the spiritual life.
Let Bananarama lead the way!]]>
            </description>
            <itunes:subtitle>This is about forging forward in the spiritual life.  Let Bananarama lead the way!</itunes:subtitle>
            <itunes:summary>This is about forging forward
                in the spiritual life.
                Let Bananarama lead the way!</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_132_-_love_in_the_first_degree_2.m4a" length="13757072" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_132_-_love_in_the_first_degree_2.m4a</guid>
            <pubDate>Wed, 16 Jan 2013 11:16:37 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:27:47</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 131 - 52 Pickup</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Here is a thought for the end of the year.
And Merry Christmas to all!]]>
            </description>
            <itunes:subtitle>Here is a thought for the end of the year.  Merry Christmas to all!</itunes:subtitle>
            <itunes:summary>Here is a thought for the end of the year.
                And Merry Christmas to all!</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20131%20-%2052%20Pickup.m4a" type="audio/x-m4a" length="12892832" />
            <guid>http://mbird.com/podcastgen/media/Episode%20131%20-%2052%20Pickup.m4a</guid>
            <pubDate>Wed, 19 Dec 2012 11:20:43 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:26:01</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Podcast 130 - OK, All Right! - Victor Hugo</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Had to do this one.
Victor Hugo is great.
"Victor Hugo" the Phenomenon seems like another turn
of Journey's "Wheel".  (Listen and you'll find out why.)
Nevertheless, I had fun doing this and hope you like it.
Karen Carpenter (R.I.P.) helped me. Mr. Leitch, too.]]>
            </description>
            <itunes:subtitle>I had to do it, tho&apos; didn&apos;t want to.  Victor Hugo is great; &quot;Victor Hugo&quot; the Phenomenon seems like just another turn of Journey&apos;s &quot;Wheel in the Sky&quot;.  Even so, I had fun with this and hope you like it.</itunes:subtitle>
            <itunes:summary>Had to do this one.
                Victor Hugo is great.
                &quot;Victor Hugo&quot; the Phenomenon seems like another turn
                of Journey&apos;s &quot;Wheel&quot;.  (Listen and you&apos;ll find out why.)
                Nevertheless, I had fun doing this and hope you like it.
                Karen Carpenter (R.I.P.) helped me. Mr. Leitch, too.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%20130%20-%20OK%2C%20All%20Right%21%20-%20Victor%20Hugo.m4a" type="audio/x-m4a" length="14876240" />
            <guid>http://mbird.com/podcastgen/media/Podcast%20130%20-%20OK%2C%20All%20Right%21%20-%20Victor%20Hugo.m4a</guid>
            <pubDate>Fri, 14 Dec 2012 08:41:32 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:04</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 129 - First Infinite Frost</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is an experiment.
It's a true story, from the true-life adventures,
tho' I truly wish it never happened.
Is PZ trying for a James Agee moment?
Maybe so.
Podcast 129 is dedicated to Adrienne Parks.]]>
            </description>
            <itunes:subtitle>An experiment: a true story, from the true-life adventures, but told backwards -- the way it felt at the time.  I truly wish this had never happened.  Is PZ trying for a James Agee moment?  Maybe so.  Podcast 129 is dedicated to Adrienne Parks.</itunes:subtitle>
            <itunes:summary>This is an experiment.
                It&apos;s a true story, from the true-life adventures,
                tho&apos; I truly wish it never happened.
                Is PZ trying for a James Agee moment?
                Maybe so.
                Podcast 129 is dedicated to Adrienne Parks.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_129_-_first_infinite_frost_2.m4a" length="12177152" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_129_-_first_infinite_frost_2.m4a</guid>
            <pubDate>Wed, 12 Dec 2012 19:33:05 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:24:34</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 128 - Dissociated Chef d&apos;Oeuvre</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This podcast is not just about another movie, the 1973 musical version of "Lost Horizon".  It's about Reflections of yourself, the divine Approach when "I Come to You", and the Things I Will Not Miss.  The movie's an incongruous knockout.  This is because it's about Life.]]>
            </description>
            <itunes:subtitle>This is not just about another movie, the 1973 musical version of &quot;Lost Horizon&quot;.  It&apos;s about Reflections of yourself, the Approach mirrored in the song &quot;I Come to You&quot;, and the Things I Will Not Miss.  The movie&apos;s a knockout because it&apos;s about Real Life.</itunes:subtitle>
            <itunes:summary>This podcast is not just about another movie, the 1973 musical version of &quot;Lost Horizon&quot;.  It&apos;s about Reflections of yourself, the divine Approach when &quot;I Come to You&quot;, and the Things I Will Not Miss.  The movie&apos;s an incongruous knockout.  This is because it&apos;s about Life.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_128_-_dissociated_chef_d'oeuvre_2.m4a" length="12681680" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_128_-_dissociated_chef_d'oeuvre_2.m4a</guid>
            <pubDate>Tue, 11 Dec 2012 08:07:32 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:25:36</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 127 - Hotel Taft</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Look within yourself, look inside the Black Cauldron.
If you take the time to Drag the Line, you'll almost definitely
find your hope, even joy.  Let the bells ring, and let's
Listen to the Music.]]>
            </description>
            <itunes:subtitle>Look within yourself,  look inside the Black Cauldron.  If you take the time to Drag the Line, you&apos;ll almost definitely find hope, even joy.  Let the bells ring and Listen to the Music.!</itunes:subtitle>
            <itunes:summary>Look within yourself, look inside the Black Cauldron.
                If you take the time to Drag the Line, you&apos;ll almost definitely
                find your hope, even joy.  Let the bells ring, and let&apos;s
                Listen to the Music.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_127_-_hotel_taft.m4a" length="16247856" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_127_-_hotel_taft.m4a</guid>
            <pubDate>Sun, 09 Dec 2012 10:57:03 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:32:52</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Podcast 126 - Amberley Wildbrooks</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Suffering, Transitoriness, and Insubstantiality:
three marks of being that seem beyond dispute,
at least from the perspective of experience.  To be sure, the last,
insubstantiality, takes some unpacking.
Podcast 126 drinks some Matthew's Southern Comfort, and
makes common cause with The Peanut Butter Conspiracy.]]>
            </description>
            <itunes:subtitle>Suffering, Transitoriness, Insubstantiality: three marks of being that seem to me beyond dispute.  The last takes a little unpacking, which I try to do.  Podcast 126 draws on Matthew&apos;s Southern Comfort, and The Peanut Butter Conspiracy.</itunes:subtitle>
            <itunes:summary>Suffering, Transitoriness, and Insubstantiality:
                three marks of being that seem beyond dispute,
                at least from the perspective of experience.  To be sure, the last,
                insubstantiality, takes some unpacking.
                Podcast 126 drinks some Matthew&apos;s Southern Comfort, and
                makes common cause with The Peanut Butter Conspiracy.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%20126%20-%20Amberley%20Wildbrooks.m4a" type="audio/x-m4a" length="16322752" />
            <guid>http://mbird.com/podcastgen/media/Podcast%20126%20-%20Amberley%20Wildbrooks.m4a</guid>
            <pubDate>Wed, 05 Dec 2012 10:14:15 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:01</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 125 - Now What?</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[In the spirit of the J. Geils Band, 'Sinuhe the Egyptian' spent his entire life looking for it.  A proto-hippie, an inspired near-mad man (not across the water), gave Sinuhe the answer.  The result was elation, and courage, and even creation.  And for me.  And for  you?]]>
            </description>
            <itunes:subtitle>In the spirit of the J. Geils Band, Sinuhe the Egyptian looked for it.  A proto-hippie Pharaoh gave it to him.  The result was a good result.  And for you and me!</itunes:subtitle>
            <itunes:summary>In the spirit of the J. Geils Band, &apos;Sinuhe the Egyptian&apos; spent his entire life looking for it.  A proto-hippie, an inspired near-mad man (not across the water), gave Sinuhe the answer.  The result was elation, and courage, and even creation.  And for me.  And for  you?</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20125%20-%20Now%20What_.m4a" length="0" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/Episode%20125%20-%20Now%20What_.m4a</guid>
            <pubDate>Tue, 04 Dec 2012 13:22:22 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:26</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 124 - Done</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Here's a Sixth Sense!
Galsworthy sheds light -- but where did it come from? -- and
jump-starts us "Going Up The Country".
]]>
            </description>
            <itunes:subtitle>Here&apos;s a Sixth Sense!  Galsworthy enlightens in the brightest way, and jump-starts us &quot;Going Up The Country.&quot;</itunes:subtitle>
            <itunes:summary>Here&apos;s a Sixth Sense!
                Galsworthy sheds light -- but where did it come from? -- and
                jump-starts us &quot;Going Up The Country&quot;.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_124_-_done.m4a" length="14495424" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_124_-_done.m4a</guid>
            <pubDate>Thu, 29 Nov 2012 09:54:18 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:29:17</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 123 - Saint&apos;s Progress</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[John Galsworthy's play "A Bit O'Love" (1915) and his novel "Saint's Progress" (1919) diagnose the problem and also the possibility inherent in parish ministry, and especially within parish clergy.  Galsworthy gives  his readers a shattering exercise but also a hopeful one.
So we just want to say: Goodbye, Columbus !]]>
            </description>
            <itunes:subtitle>John Galsworthy&apos;s play &quot;A Bit O&apos;Love&quot; (1915) and his novel &quot;Saints&apos;s Progress&quot; (1919) diagnose the problem and also the possibility of Christian ministry. They diagnose it  to the point of heartbreak.  And yet there is hope.  Goodbye, Columbus!</itunes:subtitle>
            <itunes:summary>John Galsworthy&apos;s play &quot;A Bit O&apos;Love&quot; (1915) and his novel &quot;Saint&apos;s Progress&quot; (1919) diagnose the problem and also the possibility inherent in parish ministry, and especially within parish clergy.  Galsworthy gives  his readers a shattering exercise but also a hopeful one.
                So we just want to say: Goodbye, Columbus !</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20123%20-%20Saint%27s%20Progress.m4a" length="0" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/Episode%20123%20-%20Saint%27s%20Progress.m4a</guid>
            <pubDate>Wed, 28 Nov 2012 08:13:44 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:37:05</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 122 -- Worst That Could Happen</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[It's being labelled a "Zwinglian"!
And there's something even worse than that.
This podcast is a plea for the wheels to be put back on
religion.]]>
            </description>
            <itunes:subtitle>It&apos;s being labelled a &quot;Zwinglian&quot;!  And there&apos;s something even worse than that.  This cast is a plea for the wheels to be put back on religion.</itunes:subtitle>
            <itunes:summary>It&apos;s being labelled a &quot;Zwinglian&quot;!
                And there&apos;s something even worse than that.
                This podcast is a plea for the wheels to be put back on
                religion.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_122_--_worst_that_could_happen.m4a" length="16869296" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_122_--_worst_that_could_happen.m4a</guid>
            <pubDate>Fri, 09 Nov 2012 12:28:21 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:34:08</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 121 - Hold That Ghost</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Freedom and Love:
Love can't exist from anything but, and
Freedom can't result in anything but.
This cast wants to consult St. Augustine, concerning human nature;
and Bud Abbott and Lou Costello, concerning
intangibles.
Maxim Gorky makes an appearance, too.
I hope you'll like what he says.]]>
            </description>
            <itunes:subtitle>Freedom and Love: Love can&apos;t exist from anything but, and Freedom won&apos;t issue in anything but.  This cast consults St. Augustine, on human nature; and Bud Abbott and Lou Costello, on, well, intangibles. Maxim Gorky makes an appearance.</itunes:subtitle>
            <itunes:summary>Freedom and Love:
                Love can&apos;t exist from anything but, and
                Freedom can&apos;t result in anything but.
                This cast wants to consult St. Augustine, concerning human nature;
                and Bud Abbott and Lou Costello, concerning
                intangibles.
                Maxim Gorky makes an appearance, too.
                I hope you&apos;ll like what he says.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_121_-_hold_that_ghost.m4a" length="17507072" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_121_-_hold_that_ghost.m4a</guid>
            <pubDate>Wed, 07 Nov 2012 09:46:49 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:35:26</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 120 - The Black Castle</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Here's a short talk about creativity, renewal, "work stoppage", and a couple of terrific movies.  It's also a lesson in How to Empty a Room!]]>
            </description>
            <itunes:subtitle>Here&apos;s a short talk about creativity, &quot;work stoppage&quot;, renewal, and a couple of wonderful movies.  Also, it&apos;s a lesson in how to empty a room!</itunes:subtitle>
            <itunes:summary>Here&apos;s a short talk about creativity, renewal, &quot;work stoppage&quot;, and a couple of terrific movies.  It&apos;s also a lesson in How to Empty a Room!</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20120%20-%20The%20Black%20Castle.m4a" type="audio/x-m4a" length="13979488" />
            <guid>http://mbird.com/podcastgen/media/Episode%20120%20-%20The%20Black%20Castle.m4a</guid>
            <pubDate>Fri, 02 Nov 2012 11:22:30 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:28:14</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Podcast 119 - Over the River II</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Did I get across?
Well, maybe "a toe on the road", to quote a religious psychologist I know.
This cast gets some help from a steel guitar, and also from the Rev. James Cleveland.  Will the world "hear from me again" (Fu Manchu)? Dunno.]]>
            </description>
            <itunes:subtitle>Did I get across?  Well maybe a &quot;toe on the road&quot;, to quote a religious psychologist I know.  This comes to you with a little help from a steel guitar, and also from James Cleveland.  Will the world &quot;hear from me again&quot; (Fu Manchu)? Dunno.</itunes:subtitle>
            <itunes:summary>Did I get across?
                Well, maybe &quot;a toe on the road&quot;, to quote a religious psychologist I know.
                This cast gets some help from a steel guitar, and also from the Rev. James Cleveland.  Will the world &quot;hear from me again&quot; (Fu Manchu)? Dunno.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20119%20-%20Over%20the%20River%20II%202.m4a" length="13511920" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/Episode%20119%20-%20Over%20the%20River%20II%202.m4a</guid>
            <pubDate>Sun, 16 Sep 2012 20:32:31 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:27:42</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 119 - Over the River I</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["Trouble in my way" is the name of the game.  This podcast tells the story of how it came to me, and what it forced me to learn.  Episode 119 of PZ's Podcast is a two part swan song.]]>
            </description>
            <itunes:subtitle>&quot;Trouble in my way&quot; is the name of the game.  This podcast tells how it came to me, and what I was forced to learn from it.  This is part one of a two-part &quot;swan song&quot;.</itunes:subtitle>
            <itunes:summary>&quot;Trouble in my way&quot; is the name of the game.  This podcast tells the story of how it came to me, and what it forced me to learn.  Episode 119 of PZ&apos;s Podcast is a two part swan song.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20119%20-%20Over%20the%20River%20II%202.m4a" type="audio/x-m4a" length="13511920" />
            <guid>http://mbird.com/podcastgen/media/Episode%20119%20-%20Over%20the%20River%20II%202.m4a</guid>
            <pubDate>Sun, 16 Sep 2012 19:36:25 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:27:17</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 118 - Les Elucubrations de PZ</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is intended to be the opposite of a rant.
Even if  I wanted to, I could not come a thousand light years close to
Antoine's great one, which once so delighed the French.
What I can try to  give you instead  is a little reading list, plus a little movie, a profound one, even a study in scarlet.
]]>
            </description>
            <itunes:subtitle>Not a rant, like Antoine&apos;s great one, but maybe some &quot;Lightworks&quot;: a current reading list and even a movie, all in red.  </itunes:subtitle>
            <itunes:summary>This is intended to be the opposite of a rant.
                Even if  I wanted to, I could not come a thousand light years close to
                Antoine&apos;s great one, which once so delighed the French.
                What I can try to  give you instead  is a little reading list, plus a little movie, a profound one, even a study in scarlet.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20118%20-%20Les%20Elucubrations%20de%20PZ.m4a" type="audio/x-m4a" length="15348128" />
            <guid>http://mbird.com/podcastgen/media/Episode%20118%20-%20Les%20Elucubrations%20de%20PZ.m4a</guid>
            <pubDate>Thu, 13 Sep 2012 12:57:28 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:31:02</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 117 - Horror Hotel</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This tight  expressionist outing is a study in egos prepared to take any measures in order to prolong (ego-) life.  It's a sure fail, but most instructive.  Then there's the fog, and the blocking of the characters in the fog.  It endures in the memory.
There is also an outstanding note of psychotronic Episcopal haberdashery and service schedules.  'Mr. Russell' is a wonderful minister!]]>
            </description>
            <itunes:subtitle>This tight expressionist movie is the real thing, about real (occult) egos willing to take any measures possible in order to prolong life.  It&apos;s a sure fail, this prolongation. But so revealing.  And the fog ...  and the blocking. </itunes:subtitle>
            <itunes:summary>This tight  expressionist outing is a study in egos prepared to take any measures in order to prolong (ego-) life.  It&apos;s a sure fail, but most instructive.  Then there&apos;s the fog, and the blocking of the characters in the fog.  It endures in the memory.
                There is also an outstanding note of psychotronic Episcopal haberdashery and service schedules.  &apos;Mr. Russell&apos; is a wonderful minister!</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_117_-_horror_hotel.m4a" length="14104880" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_117_-_horror_hotel.m4a</guid>
            <pubDate>Tue, 11 Sep 2012 16:00:06 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:28:30</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 116 - Wing Thing</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Another meditation on hope (i.e., the Wing Thing), via death; yet
death concretely and in the now, death you can get your skull around
today and not tomorrow.
Akira Ifukube is here to help undress us, as is Diogenes the Cynic, and Ludger Tom Ring; and, wouldn't you know, Raymond Scott.
Podcast 116 is dedicated to Hewes Hull.]]>
            </description>
            <itunes:subtitle>Another meditation on hope (i.e., the Wing Thing) via death, but concrete death: death you can get your skull around.  Akira Ifukube is here to help us, as is Diogenes the Cynic; as is, again, Raymond Scott.  The cast is dedicated to Hewes Hull.</itunes:subtitle>
            <itunes:summary>Another meditation on hope (i.e., the Wing Thing), via death; yet
                death concretely and in the now, death you can get your skull around
                today and not tomorrow.
                Akira Ifukube is here to help undress us, as is Diogenes the Cynic, and Ludger Tom Ring; and, wouldn&apos;t you know, Raymond Scott.
                Podcast 116 is dedicated to Hewes Hull.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_116_-_wing_thing.m4a" length="11144992" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_116_-_wing_thing.m4a</guid>
            <pubDate>Mon, 27 Aug 2012 23:34:51 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:22:28</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 115 - In the event of</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["What makes the melon ball bounce?"
What makes you bounce?
This is an undressed talk about death,
and death's funny aftermath.]]>
            </description>
            <itunes:subtitle>&quot;What makes the melon ball bounce?&quot;  What makes you bounce?  This is an undressed talk about death, real death;  and its funny aftermath.</itunes:subtitle>
            <itunes:summary>&quot;What makes the melon ball bounce?&quot;
                What makes you bounce?
                This is an undressed talk about death,
                and death&apos;s funny aftermath.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_115_-_in_the_event_of.m4a" length="14945376" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_115_-_in_the_event_of.m4a</guid>
            <pubDate>Sat, 25 Aug 2012 11:21:04 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:12</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 114 - A Slight Shiver</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Sequel to "Return to Form",
with a push from Serling and a lift from Dylan.]]>
            </description>
            <itunes:subtitle>Sequel to &quot;Return to Form&quot;, with a push from Serling and a lift from Dylan.  </itunes:subtitle>
            <itunes:summary>Sequel to &quot;Return to Form&quot;,
                with a push from Serling and a lift from Dylan.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_114_-_a_slight_shiver.m4a" length="15216992" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_114_-_a_slight_shiver.m4a</guid>
            <pubDate>Mon, 13 Aug 2012 10:00:03 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:46</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 113 - Return to Form</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is podcast one in a new "story arc" --
a study in defeatedness, and a new hope
I strangely feel.
You could call it cross-notes of a
theological psychologist.]]>
            </description>
            <itunes:subtitle>This is podcast one in a new &quot;story arc&quot; -- a study in defeatedness, and a new hope I strangely feel.  You could call it cross-notes of a theological psychologist.</itunes:subtitle>
            <itunes:summary>This is podcast one in a new &quot;story arc&quot; --
                a study in defeatedness, and a new hope
                I strangely feel.
                You could call it cross-notes of a
                theological psychologist.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_113_-_return_to_form.m4a" length="13843104" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_113_-_return_to_form.m4a</guid>
            <pubDate>Sat, 11 Aug 2012 15:58:52 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:27:58</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 113 - The Two Geralds</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Gerald Fried (b. 1928) and Gerald Heard (d. 1971): both were communicators of the non-rational, both were exponents of the subterranean echo.  Fried did it through B-movie (and other) musical scores; Heard, through mystery novels and fantastic short stories.  Jesus did it, too, through similes and parables.  (If the second Gerald chose for his "nom-de-plume" 'H.F. Heard', I wonder what name Christ would have chosen, had His  stories been published.)]]>
            </description>
            <itunes:subtitle>Gerald Fried (b.1928) and Gerald Heard (d. 1971): both were communicators of the non-rational, both exponents of the subterranean echo.  Fried did it through B-movie (and other) musical scores; Heard, through fantastic mysteries.  Jesus did it, too.</itunes:subtitle>
            <itunes:summary>Gerald Fried (b. 1928) and Gerald Heard (d. 1971): both were communicators of the non-rational, both were exponents of the subterranean echo.  Fried did it through B-movie (and other) musical scores; Heard, through mystery novels and fantastic short stories.  Jesus did it, too, through similes and parables.  (If the second Gerald chose for his &quot;nom-de-plume&quot; &apos;H.F. Heard&apos;, I wonder what name Christ would have chosen, had His  stories been published.)</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20113%20-%20The%20Two%20Geralds.m4a" length="0" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/Episode%20113%20-%20The%20Two%20Geralds.m4a</guid>
            <pubDate>Thu, 28 Jun 2012 10:56:19 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:25</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 112 - Kipling&apos;s Lightworks</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Kipling shed light!
From "Recessional" to "Children's Song",
this podcast sings his praise.
Kipling was also a 'both-and' thinker,
a rare eirenic gift, and
a Gift for Today.
Episode 112 is dedicated to Stuart Gerson.]]>
            </description>
            <itunes:subtitle>Kipling shed Light!  From &quot;Recessional&quot; to &quot;Children&apos;s Song&quot;, this podcast sings his praise.  He was also a &apos;both-and&apos; thinker, a rare eirenic gift.  Episode 112 is dedicated to Stuart Gerson.</itunes:subtitle>
            <itunes:summary>Kipling shed light!
                From &quot;Recessional&quot; to &quot;Children&apos;s Song&quot;,
                this podcast sings his praise.
                Kipling was also a &apos;both-and&apos; thinker,
                a rare eirenic gift, and
                a Gift for Today.
                Episode 112 is dedicated to Stuart Gerson.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_112_-_kipling's_lightworks.m4a" length="19797712" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_112_-_kipling's_lightworks.m4a</guid>
            <pubDate>Thu, 21 Jun 2012 11:28:31 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:40:06</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 110 - Color Him Father</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[John Betjeman listed five masters of the English ghost story, or supernatural tale.  All five of them were the sons of Protestant ministers.
What was going on with these sons, and their fathers.
'The Winstons' can tell us the answer,
in their 1969 45 that we loved so,
we sons of our fathers.]]>
            </description>
            <itunes:subtitle>John Betjeman listed five masters of  the English ghost story, or supernatural tale.  Each of them was the son of a Protestant minister.  What was going on with these sons and their fathers?  Let &apos;The Winstons&apos; , from 1969, fill out the picture.</itunes:subtitle>
            <itunes:summary>John Betjeman listed five masters of the English ghost story, or supernatural tale.  All five of them were the sons of Protestant ministers.
                What was going on with these sons, and their fathers.
                &apos;The Winstons&apos; can tell us the answer,
                in their 1969 45 that we loved so,
                we sons of our fathers.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/2015-02-13_episode_110_-_color_him_father.m4a" length="16223936" type="audio/x-m4a"/>
            <guid>http://mbird.com/podcastgen/media/2015-02-13_episode_110_-_color_him_father.m4a</guid>
            <pubDate>Mon, 04 Jun 2012 14:41:42 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:32:49</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 108 - J.C. Ryle Considered</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Bishop Ryle made at least three big mistakes during his long ministry.
If he were able to speak now -- he died in 1900 -- I believe he would admit them.  To me they are revealing mistakes, from which there is something to learn.
J.C. Ryle also had a core strength:
He had been saved in his youth, when his world fell apart.
He was a Christian, in other words, for the right reason.
Yet like many spiritual people, there were still
"unevangelized dark continents" inside him.
Had these been "colonized" by the great Word that saved the young man, Ryle might have avoided the mistakes he made as an older man.
]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Bishop Ryle made at least three big mistakes during his long ministry.
                If he were able to speak now -- he died in 1900 -- I believe he would admit them.  To me they are revealing mistakes, from which there is something to learn.
                J.C. Ryle also had a core strength:
                He had been saved in his youth, when his world fell apart.
                He was a Christian, in other words, for the right reason.
                Yet like many spiritual people, there were still
                &quot;unevangelized dark continents&quot; inside him.
                Had these been &quot;colonized&quot; by the great Word that saved the young man, Ryle might have avoided the mistakes he made as an older man.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20108%20-%20J.C.%20Ryle%20Considered.m4a" type="audio/x-m4a" length="13833392" />
            <guid>http://mbird.com/podcastgen/media/Episode%20108%20-%20J.C.%20Ryle%20Considered.m4a</guid>
            <pubDate>Fri, 25 May 2012 12:00:21 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:27:56</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 107 - Bishop Ryle</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[John Charles Ryle, who lived from l816 to 1900,
was "a giant of a man with the heart of a child".
He was a Christian warrior in the Church of England,
who contended against High Churchmen and Liberals
for 60 years, concluding his ministry as the first Bishop of Liverpool.
J.C. Ryle  is a fascinating character, a hero-type with some
interesting weaknesses.
This podcast tells the story of his life.
It is dedicated to my friend Fred Rogers.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>John Charles Ryle, who lived from l816 to 1900,
                was &quot;a giant of a man with the heart of a child&quot;.
                He was a Christian warrior in the Church of England,
                who contended against High Churchmen and Liberals
                for 60 years, concluding his ministry as the first Bishop of Liverpool.
                J.C. Ryle  is a fascinating character, a hero-type with some
                interesting weaknesses.
                This podcast tells the story of his life.
                It is dedicated to my friend Fred Rogers.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20107%20-%20Bishop%20Ryle.m4a" type="audio/x-m4a" length="17419968" />
            <guid>http://mbird.com/podcastgen/media/Episode%20107%20-%20Bishop%20Ryle.m4a</guid>
            <pubDate>Fri, 25 May 2012 11:49:48 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:35:15</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 106 - Requiem</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Alternate Title:
I Feel Like I Lose When I Win.
Also, there's a correction:
It was 'Fraulein Doktor', not the actress who played her (Suzy Kendall),
who died young, at age 52, in 1940.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Alternate Title:
                I Feel Like I Lose When I Win.
                Also, there&apos;s a correction:
                It was &apos;Fraulein Doktor&apos;, not the actress who played her (Suzy Kendall),
                who died young, at age 52, in 1940.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20106%20-%20I%20Feel%20Like%20I%20Lose%20When%20I%20Win.m4a" type="audio/x-m4a" length="13565152" />
            <guid>http://mbird.com/podcastgen/media/Episode%20106%20-%20I%20Feel%20Like%20I%20Lose%20When%20I%20Win.m4a</guid>
            <pubDate>Mon, 21 May 2012 08:36:18 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:27:24</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 104 - What does it take (to win your love)?</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[A meditation on defense:
that's what this is.
Someone wrote that the inner being of a human being
is "covered by thirty or forty skins or hides, like an ox's or a bear's,
so thick and hard".
Too true!
What's to get through?
Is there an antipode, a blessed antipode,
to such a coverage from hope?
I honestly think there is.
(Even if you've only got a toe on the Road.)]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>A meditation on defense:
                that&apos;s what this is.
                Someone wrote that the inner being of a human being
                is &quot;covered by thirty or forty skins or hides, like an ox&apos;s or a bear&apos;s,
                so thick and hard&quot;.
                Too true!
                What&apos;s to get through?
                Is there an antipode, a blessed antipode,
                to such a coverage from hope?
                I honestly think there is.
                (Even if you&apos;ve only got a toe on the Road.)</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20104%20-%20What%20does%20it%20take%20%28to%20win%20your%20love%29_.m4a" type="audio/x-m4a" length="14416032" />
            <guid>http://mbird.com/podcastgen/media/Episode%20104%20-%20What%20does%20it%20take%20%28to%20win%20your%20love%29_.m4a</guid>
            <pubDate>Wed, 16 May 2012 09:30:45 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:29:08</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 103 - Flowers for Algernon II</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[How does the ego actually die?
Or rather, what does a person look like when their ego has died,
or is dying?
Can we see this -- the "seed falling into the ground"?
Algernon Blackwood wrote about the dying.
He wrote about it vividly and concretely, not just symbolically.
This podcast quotes from two of Blackwood's "Eternity" stories:
"The Centaur" (1911) and "A Descent into Egypt" (1914).
The theme is healing, at the end of the day;
and even,
priesthood.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>How does the ego actually die?
                Or rather, what does a person look like when their ego has died,
                or is dying?
                Can we see this -- the &quot;seed falling into the ground&quot;?
                Algernon Blackwood wrote about the dying.
                He wrote about it vividly and concretely, not just symbolically.
                This podcast quotes from two of Blackwood&apos;s &quot;Eternity&quot; stories:
                &quot;The Centaur&quot; (1911) and &quot;A Descent into Egypt&quot; (1914).
                The theme is healing, at the end of the day;
                and even,
                priesthood.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20103%20-%20Flowers%20for%20Algernon%20II.m4a" type="audio/x-m4a" length="17781360" />
            <guid>http://mbird.com/podcastgen/media/Episode%20103%20-%20Flowers%20for%20Algernon%20II.m4a</guid>
            <pubDate>Tue, 24 Apr 2012 17:06:10 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:35:59</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 102 - Flowers for Algernon I</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Algernon Blackwood (1869-1951) knew a lot.
In reaction to his Sandemanian childhood, he still remained a religious person, all his life.
In his "weird tales" Blackwood tried to map a religious way
forward -- through an inspired imagination.
I used to put Arthur Machen at the top of the list
of writers of supernatural horror.
Because of a change in me, Blackwood is now number one.
This podcast, together with Episode 103, which comes next,
follows directly from "Eternity".]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Algernon Blackwood (1869-1951) knew a lot.
                In reaction to his Sandemanian childhood, he still remained a religious person, all his life.
                In his &quot;weird tales&quot; Blackwood tried to map a religious way
                forward -- through an inspired imagination.
                I used to put Arthur Machen at the top of the list
                of writers of supernatural horror.
                Because of a change in me, Blackwood is now number one.
                This podcast, together with Episode 103, which comes next,
                follows directly from &quot;Eternity&quot;.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20102%20-%20Flowers%20for%20Algernon%20I.m4a" type="audio/x-m4a" length="18815632" />
            <guid>http://mbird.com/podcastgen/media/Episode%20102%20-%20Flowers%20for%20Algernon%20I.m4a</guid>
            <pubDate>Tue, 24 Apr 2012 16:54:42 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:38:06</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 100 - Eternity</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[What dies when we die, and what continues to live?
What should we fear in relation to physical death,
and what can we affirm?
Philip Larkin gives a little assist here,
but so does St. Francis.
This is Episode 100.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>What dies when we die, and what continues to live?
                What should we fear in relation to physical death,
                and what can we affirm?
                Philip Larkin gives a little assist here,
                but so does St. Francis.
                This is Episode 100.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20100%20-%20Eternity%202.m4a" type="audio/x-m4a" length="17477424" />
            <guid>http://mbird.com/podcastgen/media/Episode%20100%20-%20Eternity%202.m4a</guid>
            <pubDate>Sat, 14 Apr 2012 09:44:03 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:35:22</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 101 - I feel like I win when I lose</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Between "Waterloo" and "Lay all your love on me",
I don't see how you could achieve a purer pop moment.
Or just a purer moment period!
The insight within these two 45s is communicated to perfection.
What is that insight?
Well, two things:
first, "all I've learned has overturned" (note the 'Euro' English).
I thought I knew myself.  Then LUV came knocking,
and "everything is new and everything is you."
That's the way people really are.
Second, "now it seems my only chance is giving up the fight...
I feel like I win when I lose."
This is good religion's word to the ego.
I feel like I win when I lose.
This is also the 101st (Airborne) Podcast.
]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Between &quot;Waterloo&quot; and &quot;Lay all your love on me&quot;,
                I don&apos;t see how you could achieve a purer pop moment.
                Or just a purer moment period!
                The insight within these two 45s is communicated to perfection.
                What is that insight?
                Well, two things:
                first, &quot;all I&apos;ve learned has overturned&quot; (note the &apos;Euro&apos; English).
                I thought I knew myself.  Then LUV came knocking,
                and &quot;everything is new and everything is you.&quot;
                That&apos;s the way people really are.
                Second, &quot;now it seems my only chance is giving up the fight...
                I feel like I win when I lose.&quot;
                This is good religion&apos;s word to the ego.
                I feel like I win when I lose.
                This is also the 101st (Airborne) Podcast.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%20101%20-%20I%20feel%20like%20I%20win%20when%20I%20lose.m4a" type="audio/x-m4a" length="17748832" />
            <guid>http://mbird.com/podcastgen/media/Episode%20101%20-%20I%20feel%20like%20I%20win%20when%20I%20lose.m4a</guid>
            <pubDate>Thu, 12 Apr 2012 09:21:45 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:35:55</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Previously Unreleased: Heinz</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Heinz Burt, known as "Heinz", the Wild Boy of Pop, was, you could say,
Joe Meek's muse.
Meek did everything possible to make his "Heinz" into a star.
Although Meek failed to do that,
he produced a large body of fabulous music around his Golden Child.
This podcast, previously unreleased, deals with the alchemy of imputation; the theme of unrequited love and consequent melancholy in much of the gold that Meek created out of Heinz; and with the proximity, to almost all of us, of mental illness.
There are two factual mistakes in the cast:
The town of Eastleigh is in Hampshire, not "New Hampshire";
and the song connected to the movie "Circus of Horrors" was sung by Garry Mills.  It is entitled "Look for a Star".]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Heinz Burt, known as &quot;Heinz&quot;, the Wild Boy of Pop, was, you could say,
                Joe Meek&apos;s muse.
                Meek did everything possible to make his &quot;Heinz&quot; into a star.
                Although Meek failed to do that,
                he produced a large body of fabulous music around his Golden Child.
                This podcast, previously unreleased, deals with the alchemy of imputation; the theme of unrequited love and consequent melancholy in much of the gold that Meek created out of Heinz; and with the proximity, to almost all of us, of mental illness.
                There are two factual mistakes in the cast:
                The town of Eastleigh is in Hampshire, not &quot;New Hampshire&quot;;
                and the song connected to the movie &quot;Circus of Horrors&quot; was sung by Garry Mills.  It is entitled &quot;Look for a Star&quot;.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%2017.m4a" type="audio/x-m4a" length="15093424" />
            <guid>http://mbird.com/podcastgen/media/Podcast%2017.m4a</guid>
            <pubDate>Tue, 10 Apr 2012 09:47:01 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:30</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Previously Unreleased: Joe Meek</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["The Nazareth Principle" (Simeon Zahl) and Joe Meek:
they're synonymous.
Joe Meek was an improbable genius, who Hear(d) a New World.
His wondrous work, achieved under conditions so unusual as to make the mind boggle, is a pure example of Christ's being labelled
by the question, "Can anything good come out of Nazareth?"
This podcast lay languishing in the vaults,
mainly because there are two mistakes in it:
the speaker confuses the guitarist Jimmy Page with the guitarist
Ritchie Blackmore; and,
'Screaming Lord Sutch', with 'Lord Buckethead'.
Other than that, he's satisfied with it.
Moreover, he believes in what he said.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>&quot;The Nazareth Principle&quot; (Simeon Zahl) and Joe Meek:
                they&apos;re synonymous.
                Joe Meek was an improbable genius, who Hear(d) a New World.
                His wondrous work, achieved under conditions so unusual as to make the mind boggle, is a pure example of Christ&apos;s being labelled
                by the question, &quot;Can anything good come out of Nazareth?&quot;
                This podcast lay languishing in the vaults,
                mainly because there are two mistakes in it:
                the speaker confuses the guitarist Jimmy Page with the guitarist
                Ritchie Blackmore; and,
                &apos;Screaming Lord Sutch&apos;, with &apos;Lord Buckethead&apos;.
                Other than that, he&apos;s satisfied with it.
                Moreover, he believes in what he said.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%2012.m4a" type="audio/x-m4a" length="24078784" />
            <guid>http://mbird.com/podcastgen/media/Podcast%2012.m4a</guid>
            <pubDate>Fri, 23 Mar 2012 06:28:49 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:48:49</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 99 9/10 - Twisterella</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[When reality comes crashing in to call,
you've got to be prepared for a re-think.
It's what happens to 'Billy Liar', in another dazzling English rose,
the movie "Billy Liar" from 1963.
It's based on a novel then a play,
but the visuals bring it home.
A man of 19, who flees from his life, for his life, into a fantasy world,
begins to falter, then crumble, in the face of reality.
(O Lucky Man! -- at age 19, to begin to see.)
Like the English city in which he lives,
in which every building seems to be being bulldozed
in service of urban renewal,
'Billy Fisher' -- Billy Liar -- is watching "everything go".
"Not one stone" (of his plummeting life) "will be left on stone".
There's help, however, in the form of a girl,
a precious girl,
who is able to care and not care.
She's the hope!
She knows something Billy doesn't, and few do.
Can she save our phantastic hero?
Could she save you?
Listen to "Twisterella".
Or rather, see "Billy Liar", and SEE.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>When reality comes crashing in to call,
                you&apos;ve got to be prepared for a re-think.
                It&apos;s what happens to &apos;Billy Liar&apos;, in another dazzling English rose,
                the movie &quot;Billy Liar&quot; from 1963.
                It&apos;s based on a novel then a play,
                but the visuals bring it home.
                A man of 19, who flees from his life, for his life, into a fantasy world,
                begins to falter, then crumble, in the face of reality.
                (O Lucky Man! -- at age 19, to begin to see.)
                Like the English city in which he lives,
                in which every building seems to be being bulldozed
                in service of urban renewal,
                &apos;Billy Fisher&apos; -- Billy Liar -- is watching &quot;everything go&quot;.
                &quot;Not one stone&quot; (of his plummeting life) &quot;will be left on stone&quot;.
                There&apos;s help, however, in the form of a girl,
                a precious girl,
                who is able to care and not care.
                She&apos;s the hope!
                She knows something Billy doesn&apos;t, and few do.
                Can she save our phantastic hero?
                Could she save you?
                Listen to &quot;Twisterella&quot;.
                Or rather, see &quot;Billy Liar&quot;, and SEE.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2099%20whatever%20-%20Twisterella%202.m4a" type="audio/x-m4a" length="16007744" />
            <guid>http://mbird.com/podcastgen/media/Episode%2099%20whatever%20-%20Twisterella%202.m4a</guid>
            <pubDate>Fri, 16 Mar 2012 23:05:29 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:32:22</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 99 5/8 - A Kind of Loving</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This podcast is about categorization -- the pitfalls of categorization.
With people, with friends (and prospective friends), with husbands and wives (and prospective husbands and wives), with everybody.
It's also about possession -- the pitfalls of possession.
Especially with people you love.
My surface subject is a 1962 movie entitled "A Kind of Loving": an English rose.  But the real subject is putting life into categories,
and love into objects.
Note the new intro, too.
It's got 45 RPM crackling noises.

]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>This podcast is about categorization -- the pitfalls of categorization.
                With people, with friends (and prospective friends), with husbands and wives (and prospective husbands and wives), with everybody.
                It&apos;s also about possession -- the pitfalls of possession.
                Especially with people you love.
                My surface subject is a 1962 movie entitled &quot;A Kind of Loving&quot;: an English rose.  But the real subject is putting life into categories,
                and love into objects.
                Note the new intro, too.
                It&apos;s got 45 RPM crackling noises.

            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2099%205_8%20-%20A%20Kind%20of%20Loving.m4a" type="audio/x-m4a" length="13532592" />
            <guid>http://mbird.com/podcastgen/media/Episode%2099%205_8%20-%20A%20Kind%20of%20Loving.m4a</guid>
            <pubDate>Tue, 13 Mar 2012 12:30:12 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:27:20</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 99 -- A Night at the Bardo</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Harpo's Night at the Bardo --
but not Harpo's, actually.
It was mine:.
It was PZ's Night at the Bardo.
From dusk till dawn.
This is something that actually happened.
I saw my own death,
or rather, myself dying, on a reclining chair in an airplane,
on March 1, 2012.
It was an unpleasant, elucidating experience.
It rattled me!
Let me tell you all about it.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Harpo&apos;s Night at the Bardo --
                but not Harpo&apos;s, actually.
                It was mine:.
                It was PZ&apos;s Night at the Bardo.
                From dusk till dawn.
                This is something that actually happened.
                I saw my own death,
                or rather, myself dying, on a reclining chair in an airplane,
                on March 1, 2012.
                It was an unpleasant, elucidating experience.
                It rattled me!
                Let me tell you all about it.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2099%20--%20A%20Night%20at%20the%20Bardo%202.m4a" type="audio/x-m4a" length="14350816" />
            <guid>http://mbird.com/podcastgen/media/Episode%2099%20--%20A%20Night%20at%20the%20Bardo%202.m4a</guid>
            <pubDate>Sat, 10 Mar 2012 17:15:05 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:29:00</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 98 - Reflections in a Golden Eye</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[If you want to find out what true north is in your life -- in other words,
where you are really going -- notice what books you are drawn to.
Or what movies you really like.  Or what  music you're putting on your iPod these days.  Or what television show you can't miss this week.
Those things function as a truth north for your life's actual direction.
This podcast looks at two revealing sentences, within two modern masterpieces, of this phenomenon of true north's revelation.
Operationally, I am wondering where you ("the living" -- B. 'Boris' Pickett)
will come down.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>If you want to find out what true north is in your life -- in other words,
                where you are really going -- notice what books you are drawn to.
                Or what movies you really like.  Or what  music you&apos;re putting on your iPod these days.  Or what television show you can&apos;t miss this week.
                Those things function as a truth north for your life&apos;s actual direction.
                This podcast looks at two revealing sentences, within two modern masterpieces, of this phenomenon of true north&apos;s revelation.
                Operationally, I am wondering where you (&quot;the living&quot; -- B. &apos;Boris&apos; Pickett)
                will come down.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2098%20-%20Reflections%20in%20a%20Golden%20Eye%202.m4a" type="audio/x-m4a" length="16280560" />
            <guid>http://mbird.com/podcastgen/media/Episode%2098%20-%20Reflections%20in%20a%20Golden%20Eye%202.m4a</guid>
            <pubDate>Sat, 10 Mar 2012 17:02:20 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:32:56</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 97 - Surprise (Symphony)</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["Oops!  I did it again!":
it just came over me.
Despite a break, a real break, very soon to come,
Lola compelled one to speak.
I mean, "Lola", the 1961 movie by Jacques Demy.
This podcast is a memo on ego-less communication.
It can really happen, and almost never does.
But you can't beat it -- you can't beat it -- when it does.
In just about any aspect of life you can name.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>&quot;Oops!  I did it again!&quot;:
                it just came over me.
                Despite a break, a real break, very soon to come,
                Lola compelled one to speak.
                I mean, &quot;Lola&quot;, the 1961 movie by Jacques Demy.
                This podcast is a memo on ego-less communication.
                It can really happen, and almost never does.
                But you can&apos;t beat it -- you can&apos;t beat it -- when it does.
                In just about any aspect of life you can name.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2097%20-%20Surprise%20Symphony.m4a" type="audio/x-m4a" length="11929952" />
            <guid>http://mbird.com/podcastgen/media/Episode%2097%20-%20Surprise%20Symphony.m4a</guid>
            <pubDate>Mon, 13 Feb 2012 13:38:33 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:24:04</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 96 - Strack-Billerbeck</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["Disputed Passage" (Lloyd C. Douglas) is what this podcast is not.
There are any number of issues to talk about,
yet so many are so particuar,
and rally around themselves all kinds of differing opinions.
I'd rather do -- that is, try to do in a small way -- something of what  Claude Berri actually did in "Jean de Florette/Manon of the Spring" (1986), which was, in his own words,
to scrape down to the universal:
our human nature and suffering,  in common -- the tie that binds.
After this cast, I am taking a short break.
But it's really just  "pre-production" time, for the next season of,
"Fireball XL 5".]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>&quot;Disputed Passage&quot; (Lloyd C. Douglas) is what this podcast is not.
                There are any number of issues to talk about,
                yet so many are so particuar,
                and rally around themselves all kinds of differing opinions.
                I&apos;d rather do -- that is, try to do in a small way -- something of what  Claude Berri actually did in &quot;Jean de Florette/Manon of the Spring&quot; (1986), which was, in his own words,
                to scrape down to the universal:
                our human nature and suffering,  in common -- the tie that binds.
                After this cast, I am taking a short break.
                But it&apos;s really just  &quot;pre-production&quot; time, for the next season of,
                &quot;Fireball XL 5&quot;.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2095%20-%20Strack-Billerbeck.m4a" type="audio/x-m4a" length="11995136" />
            <guid>http://mbird.com/podcastgen/media/Episode%2095%20-%20Strack-Billerbeck.m4a</guid>
            <pubDate>Sat, 11 Feb 2012 10:47:48 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:24:12</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Mini Podcast 94 - My New Program</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Language changes, changes, changes.
"Elle coule, coule, coule."
Like a simple but undeviating "conversation" at the drive -through window of the  bank.
Or like the use of the word "program".
"Program"  doesn't mean a Lenten series anymore.
It doesn't mean what it used to mean.
It means something else now.
So I need  your help,
to devise a more robust program than just  another pot luck.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Language changes, changes, changes.
                &quot;Elle coule, coule, coule.&quot;
                Like a simple but undeviating &quot;conversation&quot; at the drive -through window of the  bank.
                Or like the use of the word &quot;program&quot;.
                &quot;Program&quot;  doesn&apos;t mean a Lenten series anymore.
                It doesn&apos;t mean what it used to mean.
                It means something else now.
                So I need  your help,
                to devise a more robust program than just  another pot luck.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Mini%20Podcast%2094%20-%20My%20new%20Program.m4a" type="audio/x-m4a" length="7938832" />
            <guid>http://mbird.com/podcastgen/media/Mini%20Podcast%2094%20-%20My%20new%20Program.m4a</guid>
            <pubDate>Fri, 03 Feb 2012 16:19:08 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:15:56</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 93 - Falsification</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["Falsification" is another word for compartmentalization.
When we falsify reality -- as in "being untrue", either to a person or to convictions that we (otherwise) hold sincerely -- we get, well,
what we deserve.
The New Testament gets falsified all the time; and
the obloquy which falsification, when found out, gets us,
clouds everything --  not to mention the very goods we actually could give.
Those goods are Reality and Mercy.
This podcast goes from Cozzens (don't worry) to Christians to lawyers to "Perfidia" to "Band of Gold".  But mainly, it goes out to ...  me and you.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>&quot;Falsification&quot; is another word for compartmentalization.
                When we falsify reality -- as in &quot;being untrue&quot;, either to a person or to convictions that we (otherwise) hold sincerely -- we get, well,
                what we deserve.
                The New Testament gets falsified all the time; and
                the obloquy which falsification, when found out, gets us,
                clouds everything --  not to mention the very goods we actually could give.
                Those goods are Reality and Mercy.
                This podcast goes from Cozzens (don&apos;t worry) to Christians to lawyers to &quot;Perfidia&quot; to &quot;Band of Gold&quot;.  But mainly, it goes out to ...  me and you.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2093%20-%20Falsification%202.m4a" type="audio/x-m4a" length="15250048" />
            <guid>http://mbird.com/podcastgen/media/Episode%2093%20-%20Falsification%202.m4a</guid>
            <pubDate>Fri, 03 Feb 2012 08:26:39 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:50</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 92 - G-d</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["Kuh-hay-tchuh-pek".
It means 'God', or rather G-d, in Martian.
You can find out all about "Kuh-hay-tchuh-pek" in the now Criterioned
1964 movie "Robinson Crusoe on Mars".
"Kuh-hay-tchuh-pek" is God, and a very right and proper God, too.
He is Divine Order, but He is also a Nice Guy.
This podcast is about G-d.
I hope you'll like Him.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>&quot;Kuh-hay-tchuh-pek&quot;.
                It means &apos;God&apos;, or rather G-d, in Martian.
                You can find out all about &quot;Kuh-hay-tchuh-pek&quot; in the now Criterioned
                1964 movie &quot;Robinson Crusoe on Mars&quot;.
                &quot;Kuh-hay-tchuh-pek&quot; is God, and a very right and proper God, too.
                He is Divine Order, but He is also a Nice Guy.
                This podcast is about G-d.
                I hope you&apos;ll like Him.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2093%20-%20G-d.m4a" type="audio/x-m4a" length="9983184" />
            <guid>http://mbird.com/podcastgen/media/Episode%2093%20-%20G-d.m4a</guid>
            <pubDate>Wed, 01 Feb 2012 13:27:29 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:20:06</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 91 - Sequels</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Sequels are strange:
sometimes they're better than the original,
most of the time they're worse.
What makes a good sequel?
"The Empire Strikes Back", for example;
or "The Invisible Man Returns";  or
"The Ghost of Frankenstein".
Well, preaching -- I mean preaching in the formal sense, i.e., preaching in churches -- is a study in sequels.
When you preach a sermon, you're in a long succession.
It goes all the way back to the Sermon on the Mount.
That was a good one.
Most of its sequels, however, don't seem to have the same power.
They tend to be soon forgotten.
I want to learn from "The Invisible Man's Revenge", and "Ghost", and "Hand" (you know what I mean) in order to know what makes a good  sequel.
This is a podcast on the art and science of preaching.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Sequels are strange:
                sometimes they&apos;re better than the original,
                most of the time they&apos;re worse.
                What makes a good sequel?
                &quot;The Empire Strikes Back&quot;, for example;
                or &quot;The Invisible Man Returns&quot;;  or
                &quot;The Ghost of Frankenstein&quot;.
                Well, preaching -- I mean preaching in the formal sense, i.e., preaching in churches -- is a study in sequels.
                When you preach a sermon, you&apos;re in a long succession.
                It goes all the way back to the Sermon on the Mount.
                That was a good one.
                Most of its sequels, however, don&apos;t seem to have the same power.
                They tend to be soon forgotten.
                I want to learn from &quot;The Invisible Man&apos;s Revenge&quot;, and &quot;Ghost&quot;, and &quot;Hand&quot; (you know what I mean) in order to know what makes a good  sequel.
                This is a podcast on the art and science of preaching.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2089%20-%20Sequels.m4a" type="audio/x-m4a" length="15659248" />
            <guid>http://mbird.com/podcastgen/media/Episode%2089%20-%20Sequels.m4a</guid>
            <pubDate>Fri, 27 Jan 2012 11:19:45 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:31:40</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 88 - Tana and Tahrir</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[I don't believe in "reality",
or rather, I believe what looks like reality is seldom reality.
This can be easily proved by a quick viewing of ...
"The Mummy Ghost" (1944).
Once look at that wonderful movie
is able to confer
an accurate understanding of reality.
This is because "The Mummy's Ghost" 's reality IS reality.
Podcast 88 concerns Kharis, Tana Leaves, and
the Arab Spring.
P.S. From Kerouac:
" 'Facts' are sophistries."]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>I don&apos;t believe in &quot;reality&quot;,
                or rather, I believe what looks like reality is seldom reality.
                This can be easily proved by a quick viewing of ...
                &quot;The Mummy Ghost&quot; (1944).
                Once look at that wonderful movie
                is able to confer
                an accurate understanding of reality.
                This is because &quot;The Mummy&apos;s Ghost&quot; &apos;s reality IS reality.
                Podcast 88 concerns Kharis, Tana Leaves, and
                the Arab Spring.
                P.S. From Kerouac:
                &quot; &apos;Facts&apos; are sophistries.&quot;</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2088%20-%20Tana%20and%20Tahrir.m4a" type="audio/x-m4a" length="16444432" />
            <guid>http://mbird.com/podcastgen/media/Episode%2088%20-%20Tana%20and%20Tahrir.m4a</guid>
            <pubDate>Fri, 20 Jan 2012 09:28:56 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:16</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 87 - Bette Davis Eyes</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[They are all, like Ray Milland, "The Man with the X-Ray Eyes" --
these Huguenot heroes:
Marot, Duplessis-Mornay, de Beze, de Coligny, de Rohan,
d'Aubigne.
That includes their English co-religionists, such as
Whitgift and Abbott and Grindal.
These are eyes of defeat, eyes that convey an end to
self-reference, eyes of a markedly ego-less state.
You simply have to undergo defeat, have to, in order to, well,
become a little child.
Old ancient wisdom.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>They are all, like Ray Milland, &quot;The Man with the X-Ray Eyes&quot; --
                these Huguenot heroes:
                Marot, Duplessis-Mornay, de Beze, de Coligny, de Rohan,
                d&apos;Aubigne.
                That includes their English co-religionists, such as
                Whitgift and Abbott and Grindal.
                These are eyes of defeat, eyes that convey an end to
                self-reference, eyes of a markedly ego-less state.
                You simply have to undergo defeat, have to, in order to, well,
                become a little child.
                Old ancient wisdom.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2087%20-%20Bette%20Davis%20Eyes.m4a" type="audio/x-m4a" length="15478960" />
            <guid>http://mbird.com/podcastgen/media/Episode%2087%20-%20Bette%20Davis%20Eyes.m4a</guid>
            <pubDate>Thu, 19 Jan 2012 11:38:22 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:31:18</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 86 - Supermarionation II</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This podcast tries to go a little deeper with Supermarionation.
It is really about social class, and the kind of alliance that inevitably imperils a religion whose goal is emancipating the human race.
We start with "It Happened One Night", then chart our way north,  to an old surprising hymn.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>This podcast tries to go a little deeper with Supermarionation.
                It is really about social class, and the kind of alliance that inevitably imperils a religion whose goal is emancipating the human race.
                We start with &quot;It Happened One Night&quot;, then chart our way north,  to an old surprising hymn.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2086%20-%20Supermarionation%20II.m4a" type="audio/x-m4a" length="13728864" />
            <guid>http://mbird.com/podcastgen/media/Episode%2086%20-%20Supermarionation%20II.m4a</guid>
            <pubDate>Wed, 11 Jan 2012 14:29:41 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:27:44</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 85 - Protestant Episcopalians in Supermarionation</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Can the mind of man and woman conceive that the subject of Episcopal haberdashery
in the movies might be interesting and meaningful?
Well, yes, it might be, at least to me.
This podcast surveys Protestant Episcopal clothing in the movies and television.
We travel in our sound machine from "The Bishop's Wife" to "Family Plot" to "Night of the Iguana" to "The Sandpiper"; and we end up on British tv -- in Supermarionation.
Maybe this is completely unimportant.
Then again...
I dedicate the cast to Fred Rogers, fellow pilgrim and dialogue partner.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Can the mind of man and woman conceive that the subject of Episcopal haberdashery
                in the movies might be interesting and meaningful?
                Well, yes, it might be, at least to me.
                This podcast surveys Protestant Episcopal clothing in the movies and television.
                We travel in our sound machine from &quot;The Bishop&apos;s Wife&quot; to &quot;Family Plot&quot; to &quot;Night of the Iguana&quot; to &quot;The Sandpiper&quot;; and we end up on British tv -- in Supermarionation.
                Maybe this is completely unimportant.
                Then again...
                I dedicate the cast to Fred Rogers, fellow pilgrim and dialogue partner.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2085%20-%20Protestant%20Episcopalians%20in%20Supermarionation.m4a" type="audio/x-m4a" length="18472432" />
            <guid>http://mbird.com/podcastgen/media/Episode%2085%20-%20Protestant%20Episcopalians%20in%20Supermarionation.m4a</guid>
            <pubDate>Tue, 10 Jan 2012 07:47:23 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:37:24</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 84 - Yvette Vickers (f. 4.27.11)</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Yvette Vickers played supporting roles in two unforgettable 1950's science-fiction movies:
"Attack of the 50 Foot Woman" and "Attack of the Giant Leeches".
As far as I'm concerned, she stole the show both times.
But, Yvette Vickers is now dead.
Or rather, she was found dead,
on the 27th of April last year (201l).
The conditions under which she was found, and the conditions under which she apparently lived her life near the end of it,
evoke floods of compassion.  They simply have to.
How could this have happened?
How could Yvette Vickers, our once-and-future (saucy) flame,
have ended that way?
This podcast -- I wouldn't mind calling it pastoral -- is an attempt to understand.


]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Yvette Vickers played supporting roles in two unforgettable 1950&apos;s science-fiction movies:
                &quot;Attack of the 50 Foot Woman&quot; and &quot;Attack of the Giant Leeches&quot;.
                As far as I&apos;m concerned, she stole the show both times.
                But, Yvette Vickers is now dead.
                Or rather, she was found dead,
                on the 27th of April last year (201l).
                The conditions under which she was found, and the conditions under which she apparently lived her life near the end of it,
                evoke floods of compassion.  They simply have to.
                How could this have happened?
                How could Yvette Vickers, our once-and-future (saucy) flame,
                have ended that way?
                This podcast -- I wouldn&apos;t mind calling it pastoral -- is an attempt to understand.


            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2084%20-%20Yvette%20Vickers%20%28f.%204.27.11%29.m4a" type="audio/x-m4a" length="16411904" />
            <guid>http://mbird.com/podcastgen/media/Episode%2084%20-%20Yvette%20Vickers%20%28f.%204.27.11%29.m4a</guid>
            <pubDate>Mon, 09 Jan 2012 11:52:10 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:12</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 82 -- Speaking in Tongues</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is a theme with me --
the pros and cons (there aren't many cons) of learning foreign languages.
Also, how does it actually work?
Why is one language easier for a given person to learn than another?
Also, what's the relatiion between learning a language to read,
and learning a language to speak?
And why is psychology so important, personal psychology,
in the acquisition of a foreign 'tongue'?
Here is 50 years' experience of pain and suffering (and altered states)
rolled up into a single half hour.
]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>This is a theme with me --
                the pros and cons (there aren&apos;t many cons) of learning foreign languages.
                Also, how does it actually work?
                Why is one language easier for a given person to learn than another?
                Also, what&apos;s the relatiion between learning a language to read,
                and learning a language to speak?
                And why is psychology so important, personal psychology,
                in the acquisition of a foreign &apos;tongue&apos;?
                Here is 50 years&apos; experience of pain and suffering (and altered states)
                rolled up into a single half hour.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2082%20--%20Foreign%20Languages.m4a" type="audio/x-m4a" length="16362496" />
            <guid>http://mbird.com/podcastgen/media/Episode%2082%20--%20Foreign%20Languages.m4a</guid>
            <pubDate>Wed, 28 Dec 2011 11:17:34 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:06</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 81 - Violette amoureuse</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[From our house to your house,
at the Turning of the Year:
a portrait of the dignity that is able to inhere within romantic love --
sometimes.
The subject is a short scene, a musical number really,
in a late Jacques Demy, "Une Chambre en Ville" (1982).
You can YouTube it by typing in "Violette amoureuse".
I have faith you will be richly repaid.
Try to marry a 'Violette' if you possibly can -- or, if it's too late,
tell your children about her.
]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>From our house to your house,
                at the Turning of the Year:
                a portrait of the dignity that is able to inhere within romantic love --
                sometimes.
                The subject is a short scene, a musical number really,
                in a late Jacques Demy, &quot;Une Chambre en Ville&quot; (1982).
                You can YouTube it by typing in &quot;Violette amoureuse&quot;.
                I have faith you will be richly repaid.
                Try to marry a &apos;Violette&apos; if you possibly can -- or, if it&apos;s too late,
                tell your children about her.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2081%20-%20Violette%20amoureuse.m4a" type="audio/x-m4a" length="12584064" />
            <guid>http://mbird.com/podcastgen/media/Episode%2081%20-%20Violette%20amoureuse.m4a</guid>
            <pubDate>Tue, 27 Dec 2011 17:39:58 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:25:24</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 79 - Would you speak up, please?</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Why am I "afraid to say what I really want to say" (Jack Kerouac)?
That's a line from "Visions of Gerard", and many could echo it.
This podcast is about changing mores,
specifically the contrast between a sensational murder case of the 1930s and a sensational case of recent times.
Then there's Ken Russell's "The Devils" (197),
a charming little movie -- and the shifting sands of killing
inquisition.
Maybe I should quit while I'm ahead.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Why am I &quot;afraid to say what I really want to say&quot; (Jack Kerouac)?
                That&apos;s a line from &quot;Visions of Gerard&quot;, and many could echo it.
                This podcast is about changing mores,
                specifically the contrast between a sensational murder case of the 1930s and a sensational case of recent times.
                Then there&apos;s Ken Russell&apos;s &quot;The Devils&quot; (197),
                a charming little movie -- and the shifting sands of killing
                inquisition.
                Maybe I should quit while I&apos;m ahead.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2079%20-%20Would%20you%20speak%20up%2C%20please_%20Hell%2C%20no%21.m4a" type="audio/x-m4a" length="17507392" />
            <guid>http://mbird.com/podcastgen/media/Episode%2079%20-%20Would%20you%20speak%20up%2C%20please_%20Hell%2C%20no%21.m4a</guid>
            <pubDate>Fri, 16 Dec 2011 13:46:25 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:35:26</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 78 - Under Satan&apos;s Sun</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[
This is PZ's Christmas Podcast.]]>
            </description>
            <itunes:subtitle>This is PZ&apos;s Christmas Podcast.</itunes:subtitle>
            <itunes:summary>
                This is PZ&apos;s Christmas Podcast.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2078%20-%20Under%20Satan%27s%20Sun.m4a" type="audio/x-m4a" length="14808432" />
            <guid>http://mbird.com/podcastgen/media/Episode%2078%20-%20Under%20Satan%27s%20Sun.m4a</guid>
            <pubDate>Sun, 11 Dec 2011 11:58:15 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:29:56</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 77 - Canned Heat</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[What constitutes you, as a human being?
What are the parts which make you the whole you are?
A single sentence from Huxley's  "After many a summer dies the swan"
can help,
together with Fritz Lang's "Woman in the Moon".
It's not about the ego.
I am so sorry that human education pumps up
that flat tire.
Is there another way to educate ... ourselves?]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>What constitutes you, as a human being?
                What are the parts which make you the whole you are?
                A single sentence from Huxley&apos;s  &quot;After many a summer dies the swan&quot;
                can help,
                together with Fritz Lang&apos;s &quot;Woman in the Moon&quot;.
                It&apos;s not about the ego.
                I am so sorry that human education pumps up
                that flat tire.
                Is there another way to educate ... ourselves?</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2077%20-%20Canned%20Heat.m4a" type="audio/x-m4a" length="14563280" />
            <guid>http://mbird.com/podcastgen/media/Episode%2077%20-%20Canned%20Heat.m4a</guid>
            <pubDate>Sat, 03 Dec 2011 09:07:03 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:29:26</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 76 - Lounge Crooner Classics</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[I'm shooting for quality today.
In the spirit of earlier podcasts  concerning Giant Crab Movies and Journey,this podcast concerns what might today be called "Lounge Crooner Classics".
In their day, they were pop songs commissioned to be played over the credits of movies and then sold as singles.
We're talking about "Voyage to the Bottom of the Sea" by Frankie Avalon;
"Journey to the 7th Planet" by Otto Brandenburg;
"The Lost Continent" by The Peddlers; and
"The Vengeance of She" by Robert Field.
These are absurd performances of human art  and commerce pitched
to the highest possible degree.  At least in my opinion.
Moreover, they can help you with your anger!
Few things do more to diminish anger than a feel for the absurd.
This podcast is intended to help the speaker, and the listener,
with his or her anger.
"Come with me,
And take a Voyage,
To the Bottom,
Of the Sea."]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>I&apos;m shooting for quality today.
                In the spirit of earlier podcasts  concerning Giant Crab Movies and Journey,this podcast concerns what might today be called &quot;Lounge Crooner Classics&quot;.
                In their day, they were pop songs commissioned to be played over the credits of movies and then sold as singles.
                We&apos;re talking about &quot;Voyage to the Bottom of the Sea&quot; by Frankie Avalon;
                &quot;Journey to the 7th Planet&quot; by Otto Brandenburg;
                &quot;The Lost Continent&quot; by The Peddlers; and
                &quot;The Vengeance of She&quot; by Robert Field.
                These are absurd performances of human art  and commerce pitched
                to the highest possible degree.  At least in my opinion.
                Moreover, they can help you with your anger!
                Few things do more to diminish anger than a feel for the absurd.
                This podcast is intended to help the speaker, and the listener,
                with his or her anger.
                &quot;Come with me,
                And take a Voyage,
                To the Bottom,
                Of the Sea.&quot;</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2076%20-%20Lounge%20Crooner%20Classics.m4a" type="audio/x-m4a" length="14923104" />
            <guid>http://mbird.com/podcastgen/media/Episode%2076%20-%20Lounge%20Crooner%20Classics.m4a</guid>
            <pubDate>Sat, 26 Nov 2011 17:12:14 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:10</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 74 - &quot;Please Come to Boston&quot;</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[I tried to follow the invitation of that song recently.
Saw a lot of things, found out a lot of things,
remembered a lot of things,
heard a couple of new things.
It was a definite pilgrimage.
I would like to tell you about it.

]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>I tried to follow the invitation of that song recently.
                Saw a lot of things, found out a lot of things,
                remembered a lot of things,
                heard a couple of new things.
                It was a definite pilgrimage.
                I would like to tell you about it.

            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2074%20-%20_Please%20Come%20to%20Boston_.m4a" type="audio/x-m4a" length="15168384" />
            <guid>http://mbird.com/podcastgen/media/Episode%2074%20-%20_Please%20Come%20to%20Boston_.m4a</guid>
            <pubDate>Tue, 22 Nov 2011 10:50:06 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:40</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 73 - When I&apos;m 64</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Can the "young" be instructed by the "old"?
Can Nigel Kneale's "Planet People" be even saved
by the over 70s?
To put this another way, are there two messages to life:
one for the first half and another for the second?
Ultimately, no.
There is one message.
Alack! : It comes through suffering.
Pump up the volume.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Can the &quot;young&quot; be instructed by the &quot;old&quot;?
                Can Nigel Kneale&apos;s &quot;Planet People&quot; be even saved
                by the over 70s?
                To put this another way, are there two messages to life:
                one for the first half and another for the second?
                Ultimately, no.
                There is one message.
                Alack! : It comes through suffering.
                Pump up the volume.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2071%20-%20When%20I%27m%2064.m4a" type="audio/x-m4a" length="13925440" />
            <guid>http://mbird.com/podcastgen/media/Episode%2071%20-%20When%20I%27m%2064.m4a</guid>
            <pubDate>Thu, 03 Nov 2011 07:07:07 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:28:08</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 72 - Making Plans for Nigel</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Nigel Kneale (1922-2006) was absolute murder,
in the Reggae sense.
No writer of English science fiction
thought more originally
than Nigel Kneale, who mostly wrote teleplays for the BBC.
His "Quatermass (pro. 'Kway-ter-mass') and the Pit" from 1959
attempted to explain the whole history of religion
via Martians.  It strangely works.
Kneale's "Quatermass" (1979) showed how the "young" are unable to save themselves from generational self-slaughter.  Only "seniors" can save 'em!
There's a lot to Kneale, He's one other of those unusual humanists
who understood about Original Sin.
These rare birds -- they're all  "murder" -- have much to tell us.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Nigel Kneale (1922-2006) was absolute murder,
                in the Reggae sense.
                No writer of English science fiction
                thought more originally
                than Nigel Kneale, who mostly wrote teleplays for the BBC.
                His &quot;Quatermass (pro. &apos;Kway-ter-mass&apos;) and the Pit&quot; from 1959
                attempted to explain the whole history of religion
                via Martians.  It strangely works.
                Kneale&apos;s &quot;Quatermass&quot; (1979) showed how the &quot;young&quot; are unable to save themselves from generational self-slaughter.  Only &quot;seniors&quot; can save &apos;em!
                There&apos;s a lot to Kneale, He&apos;s one other of those unusual humanists
                who understood about Original Sin.
                These rare birds -- they&apos;re all  &quot;murder&quot; -- have much to tell us.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2071%20-%20Nigel%20Kneale.m4a" type="audio/x-m4a" length="15757520" />
            <guid>http://mbird.com/podcastgen/media/Episode%2071%20-%20Nigel%20Kneale.m4a</guid>
            <pubDate>Mon, 31 Oct 2011 11:46:08 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:31:52</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 71 - Removals Men II</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Rejoicing at someone's execution, in "disturbing images",
is hard enough to absorb.
To add the unaccountable silence of Christians in relation to such joy
is almost impossible to absorb.
What's to love in this world, in this planetary race of not so human beings?
We're hoping to get a little help today from Harnack and Huxley.
(Wonder what would have happened if they'd ever met?
I feel almost certain that Holl, Harnack's A student, would have liked Huxley.)]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Rejoicing at someone&apos;s execution, in &quot;disturbing images&quot;,
                is hard enough to absorb.
                To add the unaccountable silence of Christians in relation to such joy
                is almost impossible to absorb.
                What&apos;s to love in this world, in this planetary race of not so human beings?
                We&apos;re hoping to get a little help today from Harnack and Huxley.
                (Wonder what would have happened if they&apos;d ever met?
                I feel almost certain that Holl, Harnack&apos;s A student, would have liked Huxley.)</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Removals%20Men%20II.m4a" type="audio/x-m4a" length="15282624" />
            <guid>http://mbird.com/podcastgen/media/Removals%20Men%20II.m4a</guid>
            <pubDate>Fri, 21 Oct 2011 11:48:20 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:54</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 70 - Removals Men</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is about the use of language to cover an uinpleasant reality.
It's not just about the "removal" of an al awlaki or a "new chapter in the history of Libya" accomplished by means of the murder of a POW who was captured alive.
It's about resigning yourself to something you cannot change.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>This is about the use of language to cover an uinpleasant reality.
                It&apos;s not just about the &quot;removal&quot; of an al awlaki or a &quot;new chapter in the history of Libya&quot; accomplished by means of the murder of a POW who was captured alive.
                It&apos;s about resigning yourself to something you cannot change.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2067%20-%20Removal_2.m4a" type="audio/x-m4a" length="14105008" />
            <guid>http://mbird.com/podcastgen/media/Episode%2067%20-%20Removal_2.m4a</guid>
            <pubDate>Fri, 21 Oct 2011 09:32:41 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:28:30</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 69 -  Pipes of Pan</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Arthur Machen meets St. Matthew's Gospel,
Chapter 11, Verses 16-19.
You can try to make your voice heard with an engaging, danceable tune,
and it will pass like a shadow over the water..
(Think "Men Without Hats".)
Or you can try it in a shrill, scratchy key,
and it will still be forgotten, fast.
(Thiink P.J. Proby .)
Whether it flops or not, however,
that's not the point .
Someone will probably eventually hear it, and take it up.
Think Joe Meek.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Arthur Machen meets St. Matthew&apos;s Gospel,
                Chapter 11, Verses 16-19.
                You can try to make your voice heard with an engaging, danceable tune,
                and it will pass like a shadow over the water..
                (Think &quot;Men Without Hats&quot;.)
                Or you can try it in a shrill, scratchy key,
                and it will still be forgotten, fast.
                (Thiink P.J. Proby .)
                Whether it flops or not, however,
                that&apos;s not the point .
                Someone will probably eventually hear it, and take it up.
                Think Joe Meek.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2070%20-%20The%20Pipes%20of%20Pan.m4a" type="audio/x-m4a" length="11962592" />
            <guid>http://mbird.com/podcastgen/media/Episode%2070%20-%20The%20Pipes%20of%20Pan.m4a</guid>
            <pubDate>Sat, 15 Oct 2011 05:34:01 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:24:08</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 68 - The Inward Voice, Pt. 2</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[There is nothing quite like the Inward Voice of
'Mark Rutherford', the novelist whose real name was William Hale White.
He wore a mask over a mask,
and his six novels constitute a kind of ultimate Inward Voice within
Victorian fiction.
Today we look at his "Revolution in Tanner's Lane" (1890), which reveals the worst and also the best  of the Romans 7 understanding of
human nature.
Cradled in this unique book -- "Revolution" -- is a message I think the world's gotta hear.
I don't think it ever will, but STILL 'MarkRutherford' committed his Inward Voice to paper, and we know a lot more about ourselves because of him..
]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>There is nothing quite like the Inward Voice of
                &apos;Mark Rutherford&apos;, the novelist whose real name was William Hale White.
                He wore a mask over a mask,
                and his six novels constitute a kind of ultimate Inward Voice within
                Victorian fiction.
                Today we look at his &quot;Revolution in Tanner&apos;s Lane&quot; (1890), which reveals the worst and also the best  of the Romans 7 understanding of
                human nature.
                Cradled in this unique book -- &quot;Revolution&quot; -- is a message I think the world&apos;s gotta hear.
                I don&apos;t think it ever will, but STILL &apos;MarkRutherford&apos; committed his Inward Voice to paper, and we know a lot more about ourselves because of him..
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2069%20-%20Inwardness%2C%20Pt.%202.m4a" type="audio/x-m4a" length="17212944" />
            <guid>http://mbird.com/podcastgen/media/Episode%2069%20-%20Inwardness%2C%20Pt.%202.m4a</guid>
            <pubDate>Sun, 09 Oct 2011 19:46:06 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:34:50</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 67 - The Inward Voice, Pt. 1</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Here is a two-parter concerning your inward voice:
What is it, and how do you find it?
From a Romans 7 point of view, the inward voice (and voices)
is almost all that matters.
Now get it down!  Write it down!  Put it on paper, or else it'll probably just
"Fade Away" (Rolling Stones).
This is personal archaeology, yours and mine, and it involves digging,
and lifting.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Here is a two-parter concerning your inward voice:
                What is it, and how do you find it?
                From a Romans 7 point of view, the inward voice (and voices)
                is almost all that matters.
                Now get it down!  Write it down!  Put it on paper, or else it&apos;ll probably just
                &quot;Fade Away&quot; (Rolling Stones).
                This is personal archaeology, yours and mine, and it involves digging,
                and lifting.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2068%20-%20Inwardness.m4a" type="audio/x-m4a" length="16836928" />
            <guid>http://mbird.com/podcastgen/media/Episode%2068%20-%20Inwardness.m4a</guid>
            <pubDate>Sun, 09 Oct 2011 19:38:44 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:34:04</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 66 - Altars by the Roadside</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Now here's a find:
a passage in the novel "Revolution in Tanner's Lane" (1890)
by 'Mark Rutherford' (aka William Hale White),
in which the author answers the question I set in the previous cast.
If there is a word from religion to the middle-aged and "mature" --
i.e., a word of humbled acquiescence to the disillusioned and shaken --
what is  religion's word to the young?
Can the same message of experienced wisdom and non-identification,
which seems able to communicate with immediacy to the shattered,
have something to say to the young and engaged,
to the active members of this world,  all  "wishin' and hopin'" and
working and fretting?
The Rev. Thomas Bradshaw, the genuine-article preacher in Mark
Rutherford's great book, offers a word to "My young friends" (p. 268)
that is a mighty dart to the young but shot from an old man's quiver.
In this cast, let me read  you what Mr. Bradshaw has to say,
then you tell me whether it answers the practical question.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Now here&apos;s a find:
                a passage in the novel &quot;Revolution in Tanner&apos;s Lane&quot; (1890)
                by &apos;Mark Rutherford&apos; (aka William Hale White),
                in which the author answers the question I set in the previous cast.
                If there is a word from religion to the middle-aged and &quot;mature&quot; --
                i.e., a word of humbled acquiescence to the disillusioned and shaken --
                what is  religion&apos;s word to the young?
                Can the same message of experienced wisdom and non-identification,
                which seems able to communicate with immediacy to the shattered,
                have something to say to the young and engaged,
                to the active members of this world,  all  &quot;wishin&apos; and hopin&apos;&quot; and
                working and fretting?
                The Rev. Thomas Bradshaw, the genuine-article preacher in Mark
                Rutherford&apos;s great book, offers a word to &quot;My young friends&quot; (p. 268)
                that is a mighty dart to the young but shot from an old man&apos;s quiver.
                In this cast, let me read  you what Mr. Bradshaw has to say,
                then you tell me whether it answers the practical question.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2072%20-%20Altars%20by%20the%20Roadside.m4a" type="audio/x-m4a" length="10523168" />
            <guid>http://mbird.com/podcastgen/media/Episode%2072%20-%20Altars%20by%20the%20Roadside.m4a</guid>
            <pubDate>Wed, 05 Oct 2011 09:59:33 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:21:12</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 65 - One Message or Two?</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Does life-wisdom offer the same message to the non-disillusioned, who are often on the younger side, as it does to the disillusioned, who are often over-50?
It's a live issue for me, since a gospel of hope to the shattered can sound depressing to people who are working on wresting something like success from life.
Interestingly, many religious pioneers, from Pachomius to Zwingli, from Clare to the "Little Flower", were young when they received a message of negation, but also a new and different theme of affirmation.
Is there a philosophical link between "Build Me Up, Buttercup" (The Foundations) and "The Levee's Gonna Break" (Dylan)?
That's the subject of this podcast.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Does life-wisdom offer the same message to the non-disillusioned, who are often on the younger side, as it does to the disillusioned, who are often over-50?
                It&apos;s a live issue for me, since a gospel of hope to the shattered can sound depressing to people who are working on wresting something like success from life.
                Interestingly, many religious pioneers, from Pachomius to Zwingli, from Clare to the &quot;Little Flower&quot;, were young when they received a message of negation, but also a new and different theme of affirmation.
                Is there a philosophical link between &quot;Build Me Up, Buttercup&quot; (The Foundations) and &quot;The Levee&apos;s Gonna Break&quot; (Dylan)?
                That&apos;s the subject of this podcast.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2065%20-%20One%20Message%20or%20Two_.m4a" type="audio/x-m4a" length="16918560" />
            <guid>http://mbird.com/podcastgen/media/Episode%2065%20-%20One%20Message%20or%20Two_.m4a</guid>
            <pubDate>Sat, 01 Oct 2011 16:08:39 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:34:14</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 64 - My New Law Firm</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[My new law firm is called "Scrambling, Rattled, and Bracing, P.A.".
It is a firm devoted to the project of complete control.
It helps me "scramble" to contain unexpected problems;
prevents me from getting "rattled" by unexpected threats;
and gets me "braced" in anticipation of feared outcomes.
In other words -- you guessed it -- my new law firm helps me get control
of my life.  I pay it to get me ready for every eventuality.
Oddly, though, it hasn't worked as well as I had hoped.
I'm still scrambling, I still get rattled, and I spend every weekend bracing
for Monday.
But hey ! : I've got hopes.  If I can just get a little control ...]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>My new law firm is called &quot;Scrambling, Rattled, and Bracing, P.A.&quot;.
                It is a firm devoted to the project of complete control.
                It helps me &quot;scramble&quot; to contain unexpected problems;
                prevents me from getting &quot;rattled&quot; by unexpected threats;
                and gets me &quot;braced&quot; in anticipation of feared outcomes.
                In other words -- you guessed it -- my new law firm helps me get control
                of my life.  I pay it to get me ready for every eventuality.
                Oddly, though, it hasn&apos;t worked as well as I had hoped.
                I&apos;m still scrambling, I still get rattled, and I spend every weekend bracing
                for Monday.
                But hey ! : I&apos;ve got hopes.  If I can just get a little control ...</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2064%20-%20My%20New%20Law%20Firm.m4a" type="audio/x-m4a" length="15904352" />
            <guid>http://mbird.com/podcastgen/media/Episode%2064%20-%20My%20New%20Law%20Firm.m4a</guid>
            <pubDate>Tue, 27 Sep 2011 19:30:44 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:32:10</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 63 - One Step Beyond</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This ancient show, much of which is now richly available on YouTube, let alone DVD, understood something important.  It understood about the "collective unconscious" and the nature of the Love that exists underneath human loves.  The several great episodes in this terse ancient treasure, from 1959 to 1961, depict reality so unflinchingly that you can barely look --- and,  the underlying reality of God.   I actually think  "One Step Beyond" is a profounder prototype for "Touched by an Angel". Plus, the music! -- especially Harry Lubin's theme entitled "Weird".  Not his "Fear", which you've heard a hundred times; but  his "Weird".
And here's the 'Dean's Question' for this podcast:  How did William James  decide to define God?]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>This ancient show, much of which is now richly available on YouTube, let alone DVD, understood something important.  It understood about the &quot;collective unconscious&quot; and the nature of the Love that exists underneath human loves.  The several great episodes in this terse ancient treasure, from 1959 to 1961, depict reality so unflinchingly that you can barely look --- and,  the underlying reality of God.   I actually think  &quot;One Step Beyond&quot; is a profounder prototype for &quot;Touched by an Angel&quot;. Plus, the music! -- especially Harry Lubin&apos;s theme entitled &quot;Weird&quot;.  Not his &quot;Fear&quot;, which you&apos;ve heard a hundred times; but  his &quot;Weird&quot;.
                And here&apos;s the &apos;Dean&apos;s Question&apos; for this podcast:  How did William James  decide to define God?</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2063%20-%20One%20Step%20Beyond.m4a" type="audio/x-m4a" length="18080032" />
            <guid>http://mbird.com/podcastgen/media/Episode%2063%20-%20One%20Step%20Beyond.m4a</guid>
            <pubDate>Sun, 18 Sep 2011 09:12:06 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:36:36</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 62 - What part of you isn&apos;t angry?</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Anger -- it's everywhere.
The question is,
at whom or at what are you NOT angry?
Well, you can't be angry at anyone or anything you love.
Or rather, you can't be angry at that part of anyone or anything that you love.
This podcast is about seismic anger -- into which the internet is just
a current window.  Every age has its window.
This podcast hunts for an answer.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Anger -- it&apos;s everywhere.
                The question is,
                at whom or at what are you NOT angry?
                Well, you can&apos;t be angry at anyone or anything you love.
                Or rather, you can&apos;t be angry at that part of anyone or anything that you love.
                This podcast is about seismic anger -- into which the internet is just
                a current window.  Every age has its window.
                This podcast hunts for an answer.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2063%20-%20What%20part%20of%20us%20isn%27t%20angry_.m4a" type="audio/x-m4a" length="16313440" />
            <guid>http://mbird.com/podcastgen/media/Episode%2063%20-%20What%20part%20of%20us%20isn%27t%20angry_.m4a</guid>
            <pubDate>Sat, 10 Sep 2011 10:21:17 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:00</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 58 - The Umbrellas of Cherbourg</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This gorgeous 1964 film is everything people say it is, and makes you wonder sometimes whether its director and writer, Jacques Demy, was too good for this world.
Let's also hear it for Michel Legrand, who wrote the score.
What I wish to eyeball, and what this podcast is about,  is its vision of romance,
for "Umbrellas of Cherbourg" is about
first love, lost love, best love, et enfin, true love.
The hero's "Je crois que tu peux partir" ("It's time for  you to go.") is so wonderfully masculine, and faithful, and cognizant but 'he's not buying',
that I truly wish every woman in the world who has lost faith in men
could see this movie.
My podcast is about True Love.
It is dedicated to Nick Greenwood.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>This gorgeous 1964 film is everything people say it is, and makes you wonder sometimes whether its director and writer, Jacques Demy, was too good for this world.
                Let&apos;s also hear it for Michel Legrand, who wrote the score.
                What I wish to eyeball, and what this podcast is about,  is its vision of romance,
                for &quot;Umbrellas of Cherbourg&quot; is about
                first love, lost love, best love, et enfin, true love.
                The hero&apos;s &quot;Je crois que tu peux partir&quot; (&quot;It&apos;s time for  you to go.&quot;) is so wonderfully masculine, and faithful, and cognizant but &apos;he&apos;s not buying&apos;,
                that I truly wish every woman in the world who has lost faith in men
                could see this movie.
                My podcast is about True Love.
                It is dedicated to Nick Greenwood.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/The%20Umbrellas%20of%20Cherbourg.m4a" type="audio/x-m4a" length="22479840" />
            <guid>http://mbird.com/podcastgen/media/The%20Umbrellas%20of%20Cherbourg.m4a</guid>
            <pubDate>Sun, 14 Aug 2011 18:07:45 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:45:34</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 57 - Beyond the Time Barrier</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Lord Buckley broke down a barrier that is exceptionally hard to break down.
He broke down the barrier between the Sacred and the Profane.
Several of his 'hipsemantic' monologues, once you begin to study them, are fascinating expressions of Christian ideas, but expressed in the terms of an offbeat and wacky nightclub personality.  I don't know of anything like them.
In this second and concluding podcast on a genuine comic genius,
I read, sitting on my white azz, Lord Buckley's riff on "Quo Vadis", entitled "Nero".
Once again, My Lords and Ladies of the Court, I give you Richard Myrle Buckley , together with his affecting 'familiar', OO-Bop-A-Lap.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Lord Buckley broke down a barrier that is exceptionally hard to break down.
                He broke down the barrier between the Sacred and the Profane.
                Several of his &apos;hipsemantic&apos; monologues, once you begin to study them, are fascinating expressions of Christian ideas, but expressed in the terms of an offbeat and wacky nightclub personality.  I don&apos;t know of anything like them.
                In this second and concluding podcast on a genuine comic genius,
                I read, sitting on my white azz, Lord Buckley&apos;s riff on &quot;Quo Vadis&quot;, entitled &quot;Nero&quot;.
                Once again, My Lords and Ladies of the Court, I give you Richard Myrle Buckley , together with his affecting &apos;familiar&apos;, OO-Bop-A-Lap.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2057%20-%20Beyond%20the%20Time%20Barrier.m4a" type="audio/x-m4a" length="10065168" />
            <guid>http://mbird.com/podcastgen/media/Episode%2057%20-%20Beyond%20the%20Time%20Barrier.m4a</guid>
            <pubDate>Fri, 05 Aug 2011 20:25:37 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:20:16</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 56 - Lord Buckley </title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Lord Buckley (aka Richard Myrle Buckley, l906-1960) was a "way out" nightclub comic and monologist, who created "hipsemantic" routines based on famous people -- very famous! -- and famous works of literature.
Lord Buckley's most famous monologue was called "The Nazz" and is a "hipster" re-telling of three miracles of Our Savior, which was Lord Buckley's frequently invoked term for Christ.  "The Nazz" is a homage to Jesus that exists in a class by itself.
If anything you've ever heard or read breaks the barrier between the Sacred and the Profane, "The Nazz" does it.
In this podcast, PZ gives a public reading of Lord Buckley's "The Nazz".
The reading can't fail to be sort of an atrocity -- I almost entitled this cast "The Nazz and My White Azz" -- as the original was performed entirely in African-American iidiom.
Nevertheless, this readng could do the alternate thing of getting down to what Buckley actually wrote and actually said, for his substance is sublime.
PZ owes his appreciation of Lord Buckley to Bill Bowman.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Lord Buckley (aka Richard Myrle Buckley, l906-1960) was a &quot;way out&quot; nightclub comic and monologist, who created &quot;hipsemantic&quot; routines based on famous people -- very famous! -- and famous works of literature.
                Lord Buckley&apos;s most famous monologue was called &quot;The Nazz&quot; and is a &quot;hipster&quot; re-telling of three miracles of Our Savior, which was Lord Buckley&apos;s frequently invoked term for Christ.  &quot;The Nazz&quot; is a homage to Jesus that exists in a class by itself.
                If anything you&apos;ve ever heard or read breaks the barrier between the Sacred and the Profane, &quot;The Nazz&quot; does it.
                In this podcast, PZ gives a public reading of Lord Buckley&apos;s &quot;The Nazz&quot;.
                The reading can&apos;t fail to be sort of an atrocity -- I almost entitled this cast &quot;The Nazz and My White Azz&quot; -- as the original was performed entirely in African-American iidiom.
                Nevertheless, this readng could do the alternate thing of getting down to what Buckley actually wrote and actually said, for his substance is sublime.
                PZ owes his appreciation of Lord Buckley to Bill Bowman.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2056%20-%20Lord%20Buckley%20and%20The%20Nazz%202.m4a" type="audio/x-m4a" length="13957968" />
            <guid>http://mbird.com/podcastgen/media/Episode%2056%20-%20Lord%20Buckley%20and%20The%20Nazz%202.m4a</guid>
            <pubDate>Sun, 31 Jul 2011 07:42:52 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:28:12</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 54 - My Sharona</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is My Sharona of faith,
a series of four theses, briefly explained,
that express an approach to everyday living,
and understanding.
I hope you like them.
]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>This is My Sharona of faith,
                a series of four theses, briefly explained,
                that express an approach to everyday living,
                and understanding.
                I hope you like them.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/My%20Sharona%20-%20Final%20Cut.m4a" type="audio/x-m4a" length="15823232" />
            <guid>http://mbird.com/podcastgen/media/My%20Sharona%20-%20Final%20Cut.m4a</guid>
            <pubDate>Sat, 09 Jul 2011 08:59:45 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:32:00</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 53 - How to Tell the Future</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[It's possible to tell the future.
It's actually pretty easy.
You have to know about human nature,
and you have to know about fashion.
You have to know that human nature doesn't change,
and you have to know that fashion changes all the time.
It changes right to left, then left to right, then back again.  Then the same, again.
And again.
"My Ever Changing Moods" (Style Council)
You, too, can be a fortune teller.
Here's how.
]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>It&apos;s possible to tell the future.
                It&apos;s actually pretty easy.
                You have to know about human nature,
                and you have to know about fashion.
                You have to know that human nature doesn&apos;t change,
                and you have to know that fashion changes all the time.
                It changes right to left, then left to right, then back again.  Then the same, again.
                And again.
                &quot;My Ever Changing Moods&quot; (Style Council)
                You, too, can be a fortune teller.
                Here&apos;s how.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2056%20-%20Prognostication.m4a" type="audio/x-m4a" length="18816112" />
            <guid>http://mbird.com/podcastgen/media/Episode%2056%20-%20Prognostication.m4a</guid>
            <pubDate>Sat, 02 Jul 2011 08:33:40 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:38:06</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Area 51 - William Inge</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[William Inge (1913-1973) wrote plays of restrained optimism concerning broken families in small Kansas towns of the 1920's and '30's..  He understood about the importance of sex in everyday life -- even in Protestant Middle-Western America during the Great Depression.  He also understood about the Church and its disappointing failure to help people when the bottom fell out of their lives.
Yet there a wistfulness to Inge.  He seems to be saying, 'If only'.  If only our religious tradition had not declined so from the teachings of Christ.
This podcast talks about William Inge's perspective on the Church Defeated -- by itself !  He writes of sufferers with tender sympathy, with grace in practice.  ]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>William Inge (1913-1973) wrote plays of restrained optimism concerning broken families in small Kansas towns of the 1920&apos;s and &apos;30&apos;s..  He understood about the importance of sex in everyday life -- even in Protestant Middle-Western America during the Great Depression.  He also understood about the Church and its disappointing failure to help people when the bottom fell out of their lives.
                Yet there a wistfulness to Inge.  He seems to be saying, &apos;If only&apos;.  If only our religious tradition had not declined so from the teachings of Christ.
                This podcast talks about William Inge&apos;s perspective on the Church Defeated -- by itself !  He writes of sufferers with tender sympathy, with grace in practice.  </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Wm.%20Inge%20and%20the%20Church.m4a" type="audio/x-m4a" length="17277984" />
            <guid>http://mbird.com/podcastgen/media/Wm.%20Inge%20and%20the%20Church.m4a</guid>
            <pubDate>Sat, 18 Jun 2011 08:49:38 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:34:58</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 50- Human Nature</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[It just may be the worst thing about America today:
our view of human nature.
If you listen to almost any -- and I mean, any -- commentator, speechmaker, pundit, or spokesperson, of literally any and every organization, institution, medium,  or government office, you are going to hear about taking charge, and imposing control -- of everything and everybody.  (I hate that they'll now ticket you if you're caught smoking in New York City.  That's insane!  No more "Shake Shack" for us, I am dashed to say.)
The pitiful thing is, their idea of human nature is not true.
It is simply not true.
We are being fed an understanding of human nature that is inaccurate.
It is innacurate from stem to stern.
Therefore there is no HOPE being offered.  Everything is rooted in a fallacy.  "Shallow Hal"
This is Episode 50 of "PZ's Podcast".  Philip Wylie's going to help us out again, but so is wonderful William Inge, and inspired Frenchman Jacques Demy.  I'm going to let them take us there, to
Strawberry Fields ... Forever.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>It just may be the worst thing about America today:
                our view of human nature.
                If you listen to almost any -- and I mean, any -- commentator, speechmaker, pundit, or spokesperson, of literally any and every organization, institution, medium,  or government office, you are going to hear about taking charge, and imposing control -- of everything and everybody.  (I hate that they&apos;ll now ticket you if you&apos;re caught smoking in New York City.  That&apos;s insane!  No more &quot;Shake Shack&quot; for us, I am dashed to say.)
                The pitiful thing is, their idea of human nature is not true.
                It is simply not true.
                We are being fed an understanding of human nature that is inaccurate.
                It is innacurate from stem to stern.
                Therefore there is no HOPE being offered.  Everything is rooted in a fallacy.  &quot;Shallow Hal&quot;
                This is Episode 50 of &quot;PZ&apos;s Podcast&quot;.  Philip Wylie&apos;s going to help us out again, but so is wonderful William Inge, and inspired Frenchman Jacques Demy.  I&apos;m going to let them take us there, to
                Strawberry Fields ... Forever.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2051%20-%20Human%20Nature.m4a" type="audio/x-m4a" length="16362144" />
            <guid>http://mbird.com/podcastgen/media/Episode%2051%20-%20Human%20Nature.m4a</guid>
            <pubDate>Sat, 11 Jun 2011 12:02:21 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:06</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 49 - &quot;Unknown and yet well known&quot;</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Another one of those unknown authors.
But he has so much to tell us,
first about sex and then about Christianity.
About  the former, he puts first things first.
About the latter, he puts Jesus on the "Enola Gay".
Would that Philip Wylie were here today, to put Jesus on a predator drone,
or on one of those Navy SEAL helicopters which flew into Pakistan recently.
]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Another one of those unknown authors.
                But he has so much to tell us,
                first about sex and then about Christianity.
                About  the former, he puts first things first.
                About the latter, he puts Jesus on the &quot;Enola Gay&quot;.
                Would that Philip Wylie were here today, to put Jesus on a predator drone,
                or on one of those Navy SEAL helicopters which flew into Pakistan recently.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Philip%20Wylie%20II%202.m4a" type="audio/x-m4a" length="21400288" />
            <guid>http://mbird.com/podcastgen/media/Philip%20Wylie%20II%202.m4a</guid>
            <pubDate>Wed, 08 Jun 2011 13:42:05 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:43:22</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 48 - The Disappearance</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Philip Wylie was a prophet in the war between the sexes.
His 1951 novel "The Disappearance", in which, through an unexplained 'cosmic blink', all the women disappear from the world of the men and all the men disappear from the world of the women, is so noble and so disturbing, so wrenching and so uplifting, so wise and so uncommonly religious, that it becomes required reading for everyone who is a man and everyone who is a woman.
]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Philip Wylie was a prophet in the war between the sexes.
                His 1951 novel &quot;The Disappearance&quot;, in which, through an unexplained &apos;cosmic blink&apos;, all the women disappear from the world of the men and all the men disappear from the world of the women, is so noble and so disturbing, so wrenching and so uplifting, so wise and so uncommonly religious, that it becomes required reading for everyone who is a man and everyone who is a woman.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/The%20Disappearance%20-%20PZ%27s%20Podcast.m4a" type="audio/x-m4a" length="26716240" />
            <guid>http://mbird.com/podcastgen/media/The%20Disappearance%20-%20PZ%27s%20Podcast.m4a</guid>
            <pubDate>Sun, 29 May 2011 08:03:25 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:54:12</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 45 - Duncan Burne-Wilke</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Herman Wouk's 1985 novel "War and Remembrance" has a most prophetic minor character buried within its 1300 pages.
This character is a philosophical and definitely sweet English aristocrat  named Duncan Burne-Wilke, whom we meet in the "CBI" or "China Burma India" theater of the Second World War.
Burne-Wilke envisages the end of Western colonialism on account of  a massive disillusionment caused by the War.  But he also thinks in religious terms concerning the future of America and England.  He sees the future in terms of the "Bhagavad gita", and a "turning East" of which we are now aware and in relation to which the Christian churches are having to live, defensively.
My podcast speaks of one small voice within a large contemporary epic.
Burne-Wilke's disenchanted words are "crying to be heard" (Traffic), and also responded to.  He haunts the bittersweet narrative of  Wouk's marvelous  book.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Herman Wouk&apos;s 1985 novel &quot;War and Remembrance&quot; has a most prophetic minor character buried within its 1300 pages.
                This character is a philosophical and definitely sweet English aristocrat  named Duncan Burne-Wilke, whom we meet in the &quot;CBI&quot; or &quot;China Burma India&quot; theater of the Second World War.
                Burne-Wilke envisages the end of Western colonialism on account of  a massive disillusionment caused by the War.  But he also thinks in religious terms concerning the future of America and England.  He sees the future in terms of the &quot;Bhagavad gita&quot;, and a &quot;turning East&quot; of which we are now aware and in relation to which the Christian churches are having to live, defensively.
                My podcast speaks of one small voice within a large contemporary epic.
                Burne-Wilke&apos;s disenchanted words are &quot;crying to be heard&quot; (Traffic), and also responded to.  He haunts the bittersweet narrative of  Wouk&apos;s marvelous  book.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2034%20-%20Duncan%20Burne-Wilke.m4a" type="audio/x-m4a" length="13140224" />
            <guid>http://mbird.com/podcastgen/media/Episode%2034%20-%20Duncan%20Burne-Wilke.m4a</guid>
            <pubDate>Sat, 07 May 2011 21:51:30 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:26:32</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 44- The Razor&apos;s Edge</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is my favorite book.
It's also Bill Murray's.
It is called "The Razor's Edge" and was written by Somerset Maugham.
It was published in 1944.

It tells the story of some well-to-do Americans from Lake Forest,
who all find what they're looking for in life.

One of them, 'Larry Darrell', loses his life only to save it.
He is the hero, and I think he could be yours.

P.S. Who's "Ruysbroek"?]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>This is my favorite book.
                It&apos;s also Bill Murray&apos;s.
                It is called &quot;The Razor&apos;s Edge&quot; and was written by Somerset Maugham.
                It was published in 1944.

                It tells the story of some well-to-do Americans from Lake Forest,
                who all find what they&apos;re looking for in life.

                One of them, &apos;Larry Darrell&apos;, loses his life only to save it.
                He is the hero, and I think he could be yours.

                P.S. Who&apos;s &quot;Ruysbroek&quot;?</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2031%20-%20The%20Razor%27s%20Edge.m4a" type="audio/x-m4a" length="15397568" />
            <guid>http://mbird.com/podcastgen/media/Episode%2031%20-%20The%20Razor%27s%20Edge.m4a</guid>
            <pubDate>Sat, 30 Apr 2011 11:38:12 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:31:08</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 43 - &quot;The Green Pastures&quot;</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["The Green Pastures" is a 1930 American play, and 1936 Hollywood movie, that was once as famous as "Our Town".  Now, for reasons of political correctness, it is rarely seen and seldom taught.  Even the DVD has to carry a 'Warning' label.  (Good Grief!)
How dearly we have robbed ourselves of a pearl of truly great price.
Marc Connelly's "The Green Pastures" deals theatrically with the transition in the Bible from Law to Grace.  (It is not Marcionite!)
Has God's Mercy, in relation to God's Law, ever been staged like this?
I can't think of an example.
You've got to see "The Green Pastures".  The character 'Hezdrel', alone, will... blow... your... mind.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>&quot;The Green Pastures&quot; is a 1930 American play, and 1936 Hollywood movie, that was once as famous as &quot;Our Town&quot;.  Now, for reasons of political correctness, it is rarely seen and seldom taught.  Even the DVD has to carry a &apos;Warning&apos; label.  (Good Grief!)
                How dearly we have robbed ourselves of a pearl of truly great price.
                Marc Connelly&apos;s &quot;The Green Pastures&quot; deals theatrically with the transition in the Bible from Law to Grace.  (It is not Marcionite!)
                Has God&apos;s Mercy, in relation to God&apos;s Law, ever been staged like this?
                I can&apos;t think of an example.
                You&apos;ve got to see &quot;The Green Pastures&quot;.  The character &apos;Hezdrel&apos;, alone, will... blow... your... mind.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2032%20-%20_The%20Green%20Pastures_%202.m4a" type="audio/x-m4a" length="16869504" />
            <guid>http://mbird.com/podcastgen/media/Episode%2032%20-%20_The%20Green%20Pastures_%202.m4a</guid>
            <pubDate>Sun, 17 Apr 2011 11:45:41 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:34:08</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 42 - Bishop Bell - The Play</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Bishop Bell appears as a main character in Rolf Hochhuth's 1967 play entitled "Soldiers".  Bell confronts Churchill on the morality of murder from the air, especially when it involves the murder of civilians.  Such a confrontation never actually took place, but the Bishop and the Prime Minister had the thoughts and stated them.  The PM detested Bell.
In Act Three of Hochhuth's play, Bell loses and Churchill wins.  In the moral balance, Churchill lost and Bell won.  "Soldiers" is a play about the massacre of this world that is repeatedly staged by Power.  As in the case of un-manned drone aircraft today.  Nobody seems to care.  Nobody 'gives'.   Yet one day...]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Bishop Bell appears as a main character in Rolf Hochhuth&apos;s 1967 play entitled &quot;Soldiers&quot;.  Bell confronts Churchill on the morality of murder from the air, especially when it involves the murder of civilians.  Such a confrontation never actually took place, but the Bishop and the Prime Minister had the thoughts and stated them.  The PM detested Bell.
                In Act Three of Hochhuth&apos;s play, Bell loses and Churchill wins.  In the moral balance, Churchill lost and Bell won.  &quot;Soldiers&quot; is a play about the massacre of this world that is repeatedly staged by Power.  As in the case of un-manned drone aircraft today.  Nobody seems to care.  Nobody &apos;gives&apos;.   Yet one day...</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2042%20-%20Bishop%20Bell%20-%20The%20Play.m4a" type="audio/x-m4a" length="17311184" />
            <guid>http://mbird.com/podcastgen/media/Episode%2042%20-%20Bishop%20Bell%20-%20The%20Play.m4a</guid>
            <pubDate>Thu, 07 Apr 2011 22:10:38 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:35:02</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 41 - Bishop Bell - The Speech</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[George K.A. Bell (1883-1958) was the Bishop of Chichester during World War II.  He addressed the House of Lords on February 9, 1944, questioning the Government on the use of "carpet bombing" of German cities.  Bishop Bell regarded this kind of bombing, which was intended to destroy German morale and bring the war to an end, as a war crime.
At the time, Bell was the only person in Britain willing to say such a thing in a 'national' forum such as the Parliament.  He was attacked all across the board as being 'pro-German' and almost a traitor.  (He had, incidentally, been the first public  figure in the country to criticize Hitler's anti-semitic legislation.  He had done so in 1934.)
Because of his speech in the Lords,  Bishop Bell  lost all chance of promotion in the Church of England.
Today, however, he is almost canonized there, and certainly within the Church.
This podcast is about Bell's speech.  It also relates his theme to the current use of un-manned drone aircraft to commit targed assassination from the air -- or rather, from Las Vegas.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>George K.A. Bell (1883-1958) was the Bishop of Chichester during World War II.  He addressed the House of Lords on February 9, 1944, questioning the Government on the use of &quot;carpet bombing&quot; of German cities.  Bishop Bell regarded this kind of bombing, which was intended to destroy German morale and bring the war to an end, as a war crime.
                At the time, Bell was the only person in Britain willing to say such a thing in a &apos;national&apos; forum such as the Parliament.  He was attacked all across the board as being &apos;pro-German&apos; and almost a traitor.  (He had, incidentally, been the first public  figure in the country to criticize Hitler&apos;s anti-semitic legislation.  He had done so in 1934.)
                Because of his speech in the Lords,  Bishop Bell  lost all chance of promotion in the Church of England.
                Today, however, he is almost canonized there, and certainly within the Church.
                This podcast is about Bell&apos;s speech.  It also relates his theme to the current use of un-manned drone aircraft to commit targed assassination from the air -- or rather, from Las Vegas.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2041%20-%20Bishop%20Bell%20-%20The%20Speech%202.m4a" type="audio/x-m4a" length="16673376" />
            <guid>http://mbird.com/podcastgen/media/Episode%2041%20-%20Bishop%20Bell%20-%20The%20Speech%202.m4a</guid>
            <pubDate>Sun, 27 Mar 2011 08:08:00 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:44</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 40 - &quot;No Popery&quot;</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Religious partisanship is normal, explicable, and terminal.
It kills Christianity. Itt sure killed me.
Or maybe it wised me up.
This podcast concerns Charles Dickens' novel "Barnaby Rudge", which was published in 1841.  Dickens' subject was the "No Popery" riots that took place in 1780 in London.  They are also known as the  "Gordon Riots".
Dickens used this astonishing episode to observe the causes of theological hatred, and its consequences.
Dickens was a conscious Protestant and heartfelt Christian,
but he was upset by religious malice.
"Barnaby Rudge" gets  to the bottom of it, in 661 pages.
This podcast gives you the Reader's Digest version in 36 minutes.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Religious partisanship is normal, explicable, and terminal.
                It kills Christianity. Itt sure killed me.
                Or maybe it wised me up.
                This podcast concerns Charles Dickens&apos; novel &quot;Barnaby Rudge&quot;, which was published in 1841.  Dickens&apos; subject was the &quot;No Popery&quot; riots that took place in 1780 in London.  They are also known as the  &quot;Gordon Riots&quot;.
                Dickens used this astonishing episode to observe the causes of theological hatred, and its consequences.
                Dickens was a conscious Protestant and heartfelt Christian,
                but he was upset by religious malice.
                &quot;Barnaby Rudge&quot; gets  to the bottom of it, in 661 pages.
                This podcast gives you the Reader&apos;s Digest version in 36 minutes.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2039%20-%20_No%20Popery_.m4a" type="audio/x-m4a" length="17785616" />
            <guid>http://mbird.com/podcastgen/media/Episode%2039%20-%20_No%20Popery_.m4a</guid>
            <pubDate>Sat, 19 Mar 2011 16:26:05 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:36:00</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 39 - The Phoenix Club</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Life in a Final Club!
"The Social Network" has made it high profile all of a sudden.
What it was, was fun, dellightful, blessedly un-serious in a way serious world,
with a  taste of Evelyn Waugh.
We loved it.
Why was the story never told?
That's a story.
Podcast 39 is published  in loving memory of
Page Farnsworth Grubb, '71.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Life in a Final Club!
                &quot;The Social Network&quot; has made it high profile all of a sudden.
                What it was, was fun, dellightful, blessedly un-serious in a way serious world,
                with a  taste of Evelyn Waugh.
                We loved it.
                Why was the story never told?
                That&apos;s a story.
                Podcast 39 is published  in loving memory of
                Page Farnsworth Grubb, &apos;71.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2040%20-%20The%20Phoenix%20Club.m4a" type="audio/x-m4a" length="23837344" />
            <guid>http://mbird.com/podcastgen/media/Episode%2040%20-%20The%20Phoenix%20Club.m4a</guid>
            <pubDate>Sun, 13 Mar 2011 07:04:08 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:48:20</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 37- The Yardbirds</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is an impression of The Yardbirds,
the first avant-garde band we ever knew.
With Eric Clapton to start, then Jeff Beck,
then Jeff Beck and Jimmy Page, then
Jimmy Page only, their music, especially the guitar breaks,
lived on the edge  of INSANITY.
To this day, I still have Yardbirds days. They are wonderful.
There was also a personal Close Encounter, with Friends.
In this podcast I tell a story and try to give an impression,
followed by a few, well, theological comments.
Podcast 37 is dedicated to William Cox Bowman.
]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>This is an impression of The Yardbirds,
                the first avant-garde band we ever knew.
                With Eric Clapton to start, then Jeff Beck,
                then Jeff Beck and Jimmy Page, then
                Jimmy Page only, their music, especially the guitar breaks,
                lived on the edge  of INSANITY.
                To this day, I still have Yardbirds days. They are wonderful.
                There was also a personal Close Encounter, with Friends.
                In this podcast I tell a story and try to give an impression,
                followed by a few, well, theological comments.
                Podcast 37 is dedicated to William Cox Bowman.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2047%20-%20The%20Yardbirds%203.m4a" type="audio/x-m4a" length="16477024" />
            <guid>http://mbird.com/podcastgen/media/Episode%2047%20-%20The%20Yardbirds%203.m4a</guid>
            <pubDate>Sun, 27 Feb 2011 06:32:02 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:20</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 33 - &quot;Mr.&quot; Priest</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This podcast is about professional titles:
the more reduced in circumstances an institution,
the more high-flown its titles.
Did you know that until about 1970 Episcopal clergy were always called
'Mr." ?  (They were never called 'Father', except in one parish, max two,
per city.)  The later Cardinal Newman was 'Mr. Newman', and Edward Bouverie Pusey was 'Mr. Pusey'.
But don't take my word for it.  Read W. M. Thackerey, read E.M. Forster.  See 'Showboat', the 1936 version.
An interesting principle seems to be at work:  when things are going great, the leader is just a regular person, like everybody else.  He's 'Mr. Irwine', as in Eliot's "Adam Bede".
But when things begin to go south, and the world gets against  you, the leader becomes:  The Most Metropolitical and Right Honorable Dr. of Sacred Letters Obadiah Slope.
Anyway, I'd sure rather be Mr. Midshipman Easy!
Listen to this, and you may want to work for the car wash down the street.
Oh, and no one will believe you anyway.
Maybe if you tell 'em, Father Paul told you.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>This podcast is about professional titles:
                the more reduced in circumstances an institution,
                the more high-flown its titles.
                Did you know that until about 1970 Episcopal clergy were always called
                &apos;Mr.&quot; ?  (They were never called &apos;Father&apos;, except in one parish, max two,
                per city.)  The later Cardinal Newman was &apos;Mr. Newman&apos;, and Edward Bouverie Pusey was &apos;Mr. Pusey&apos;.
                But don&apos;t take my word for it.  Read W. M. Thackerey, read E.M. Forster.  See &apos;Showboat&apos;, the 1936 version.
                An interesting principle seems to be at work:  when things are going great, the leader is just a regular person, like everybody else.  He&apos;s &apos;Mr. Irwine&apos;, as in Eliot&apos;s &quot;Adam Bede&quot;.
                But when things begin to go south, and the world gets against  you, the leader becomes:  The Most Metropolitical and Right Honorable Dr. of Sacred Letters Obadiah Slope.
                Anyway, I&apos;d sure rather be Mr. Midshipman Easy!
                Listen to this, and you may want to work for the car wash down the street.
                Oh, and no one will believe you anyway.
                Maybe if you tell &apos;em, Father Paul told you.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2042%20-%20_Mr._%20Priest.m4a" type="audio/x-m4a" length="15773440" />
            <guid>http://mbird.com/podcastgen/media/Episode%2042%20-%20_Mr._%20Priest.m4a</guid>
            <pubDate>Sun, 13 Feb 2011 09:26:21 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:31:54</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 32 - Protestant Interiors II</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Here's a little gazetteer of Episcopal Protestant interiors.
They're nice.
Delaware's is in the middle of nowhere, and Boston's finest is Unitarian.
George Washington sat beneath a central pulpit in Alexandria and "Low Country'" farmers did the same.  And don't forget the Motor City: I mean,
Duanesburg, New York.  But always remember this -- even if you are actually able to visit these places, no one will ever believe you when you get back home.
They simply CAN'T exist!]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Here&apos;s a little gazetteer of Episcopal Protestant interiors.
                They&apos;re nice.
                Delaware&apos;s is in the middle of nowhere, and Boston&apos;s finest is Unitarian.
                George Washington sat beneath a central pulpit in Alexandria and &quot;Low Country&apos;&quot; farmers did the same.  And don&apos;t forget the Motor City: I mean,
                Duanesburg, New York.  But always remember this -- even if you are actually able to visit these places, no one will ever believe you when you get back home.
                They simply CAN&apos;T exist!</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2039%20-%20Protestant%20Interiors%20II%202.m4a" type="audio/x-m4a" length="16444560" />
            <guid>http://mbird.com/podcastgen/media/Episode%2039%20-%20Protestant%20Interiors%20II%202.m4a</guid>
            <pubDate>Tue, 08 Feb 2011 16:33:47 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:16</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 31 - Protestant Interiors</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This one is about Protestant aesthetics
as expressed in architecture and design.
It is 'a tale told by an idiot',  however, for no one ever believes you.
Only Henny Penny says the Episcopal Church
was once Protestant and 'Low'  -- right  up to the Disco Era.
Memory being what it is, this is the tale of a forgotten 200 years.

The Song Remains the Same in about 200 precious survivals in
England, as well as 50 or so on the East Coast of the U.S.A.
There, the glass is clear; the design, simple; and the message, unmediated.
There, less is more.


]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>This one is about Protestant aesthetics
                as expressed in architecture and design.
                It is &apos;a tale told by an idiot&apos;,  however, for no one ever believes you.
                Only Henny Penny says the Episcopal Church
                was once Protestant and &apos;Low&apos;  -- right  up to the Disco Era.
                Memory being what it is, this is the tale of a forgotten 200 years.

                The Song Remains the Same in about 200 precious survivals in
                England, as well as 50 or so on the East Coast of the U.S.A.
                There, the glass is clear; the design, simple; and the message, unmediated.
                There, less is more.


            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2037%20-%20Protestant%20Interiors.m4a" type="audio/x-m4a" length="18079952" />
            <guid>http://mbird.com/podcastgen/media/Episode%2037%20-%20Protestant%20Interiors.m4a</guid>
            <pubDate>Sun, 06 Feb 2011 08:11:49 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:36:36</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 30 - Shock Theater</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Late Saturday nights was a time for little boys to howl.
"Shock Theater" came on around one!
We learned every line of the 'original' "Dracula" (1931), memorized every release date of every Mummy movie from 1932 to 1945, and, most important, got married for life to:  "The Bride of Frankenstein".
This is the story of those late Saturday nights, which gave our mothers such trouble, since it was they who would have to ...  wake us up for church.]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Late Saturday nights was a time for little boys to howl.
                &quot;Shock Theater&quot; came on around one!
                We learned every line of the &apos;original&apos; &quot;Dracula&quot; (1931), memorized every release date of every Mummy movie from 1932 to 1945, and, most important, got married for life to:  &quot;The Bride of Frankenstein&quot;.
                This is the story of those late Saturday nights, which gave our mothers such trouble, since it was they who would have to ...  wake us up for church.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2030%20-%20Shock%20Theater.m4a" type="audio/x-m4a" length="15266656" />
            <guid>http://mbird.com/podcastgen/media/Episode%2030%20-%20Shock%20Theater.m4a</guid>
            <pubDate>Wed, 02 Feb 2011 07:02:07 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:52</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 29 - The Circle</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[The Circle was a movie theater in downtown Washington where two boys discovered foreign film.
Boris Karloff and James Whale became superceded by Sergei Eisenstein and Francois Truffaut.
Or mostly. (We were only 13 years old, for crying out loud.)
This podcast tells our Tales from the Circle.  Every word is true.
It is Part III of The Moviegoer and is dedicated to Lloyd Fonvielle.
]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>The Circle was a movie theater in downtown Washington where two boys discovered foreign film.
                Boris Karloff and James Whale became superceded by Sergei Eisenstein and Francois Truffaut.
                Or mostly. (We were only 13 years old, for crying out loud.)
                This podcast tells our Tales from the Circle.  Every word is true.
                It is Part III of The Moviegoer and is dedicated to Lloyd Fonvielle.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2029%20-%20The%20Circle.m4a" type="audio/x-m4a" length="15004848" />
            <guid>http://mbird.com/podcastgen/media/Episode%2029%20-%20The%20Circle.m4a</guid>
            <pubDate>Sun, 30 Jan 2011 07:48:29 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:20</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 28 - Premature Burial</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Part II of The Moviegoer, in which our ten-year-old hero discovers
Edgar Allan Poe via Roger Corman in the downtown movie palaces of
Loew's Capital, Loew's Palace, and R.K.O. Keith's.
He comes face to face with a strange new Glynis Johns before encountering "The Vampire and the Ballerina" exactly one block  from the White House.
]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>Part II of The Moviegoer, in which our ten-year-old hero discovers
                Edgar Allan Poe via Roger Corman in the downtown movie palaces of
                Loew&apos;s Capital, Loew&apos;s Palace, and R.K.O. Keith&apos;s.
                He comes face to face with a strange new Glynis Johns before encountering &quot;The Vampire and the Ballerina&quot; exactly one block  from the White House.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2028%20-%20Premature%20Burial.m4a" type="audio/x-m4a" length="14661536" />
            <guid>http://mbird.com/podcastgen/media/Episode%2028%20-%20Premature%20Burial.m4a</guid>
            <pubDate>Wed, 26 Jan 2011 17:27:05 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:29:38</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 27 - The Crawling Eye</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[This is the story of a conversion.
It happened in the Fall of 1959,
and I've never looked back.
It happened in connection with some mountaineering in the Swiss Alps.
Like the man in "The Crawling Eye",
I lost my head.
Still haven't found it.
]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>This is the story of a conversion.
                It happened in the Fall of 1959,
                and I&apos;ve never looked back.
                It happened in connection with some mountaineering in the Swiss Alps.
                Like the man in &quot;The Crawling Eye&quot;,
                I lost my head.
                Still haven&apos;t found it.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2027%20-%20The%20Crawling%20Eye.m4a" type="audio/x-m4a" length="15103072" />
            <guid>http://mbird.com/podcastgen/media/Episode%2027%20-%20The%20Crawling%20Eye.m4a</guid>
            <pubDate>Sun, 23 Jan 2011 12:10:23 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:30:32</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 26 - P.E. II</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[We're not finished yet.
Cozzens cuts to the core of Anglo-Catholicism
yet without throwing stones.
He wants to understand.
And his account of a hijacked P.E. funeral in "Eyes to See"
is so close to home, well,
that it makes you want to scream.
]]>
            </description>
            <itunes:subtitle />
            <itunes:summary>We&apos;re not finished yet.
                Cozzens cuts to the core of Anglo-Catholicism
                yet without throwing stones.
                He wants to understand.
                And his account of a hijacked P.E. funeral in &quot;Eyes to See&quot;
                is so close to home, well,
                that it makes you want to scream.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2026%20-%20P.E.%20II.m4a" type="audio/x-m4a" length="14464864" />
            <guid>http://mbird.com/podcastgen/media/Episode%2026%20-%20P.E.%20II.m4a</guid>
            <pubDate>Wed, 19 Jan 2011 10:34:39 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:29:14</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 25 - P.E.</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["P.E." is for Protestant Episcopal.
35 years I've been ordained and it took Cozzens to teach me some sore lessons.
For me they came late.
But,  "For you the living/This Mash was meant, too."
"When you get to my house,
Tell them 'Jimmy' sent you."]]>
            </description>
            <itunes:subtitle>P.E. is for &quot;Protestant Episcopal&quot;.  35 years ordained and I never learned these things.  James Gould Cozzens could have taught me.  He knew.  Are we too late? </itunes:subtitle>
            <itunes:summary>&quot;P.E.&quot; is for Protestant Episcopal.
                35 years I&apos;ve been ordained and it took Cozzens to teach me some sore lessons.
                For me they came late.
                But,  &quot;For you the living/This Mash was meant, too.&quot;
                &quot;When you get to my house,
                Tell them &apos;Jimmy&apos; sent you.&quot;</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2025%20-%20P.E..m4a" type="audio/x-m4a" length="13859760" />
            <guid>http://mbird.com/podcastgen/media/Episode%2025%20-%20P.E..m4a</guid>
            <pubDate>Sat, 15 Jan 2011 05:23:04 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:28:00</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 22 - Journey</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[What's the greatest rock 'n roll band of all time?
Could a group sum up everything that has gone before
and thus WRAP the genre?
Yes, it could.  They did.
Their name was "Journey".
But Wait!  Hear me out.]]>
            </description>
            <itunes:subtitle>What&apos;s the greatest rock &apos;n roll group of all time?  What band sums it all up, such that nothing more can be said?:                                                    Journey.  The band&apos;s name is Journey.                             But wait, hear me out!</itunes:subtitle>
            <itunes:summary>What&apos;s the greatest rock &apos;n roll band of all time?
                Could a group sum up everything that has gone before
                and thus WRAP the genre?
                Yes, it could.  They did.
                Their name was &quot;Journey&quot;.
                But Wait!  Hear me out.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2022%20-%20Journey.m4a" type="audio/x-m4a" length="15561040" />
            <guid>http://mbird.com/podcastgen/media/Episode%2022%20-%20Journey.m4a</guid>
            <pubDate>Sun, 02 Jan 2011 09:26:50 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:31:28</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 21 - Plymouth Adventure</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Dan Curtis went straight from Gothic Horror soap operas to the greatest epic ever made for television.  His heart was always in his work,  from "Dark Shadows" to "The Night Stalker" to... "The Winds of War".  When it comes to his 29-hour genius production "War and Remembrance,  Can't Touch This!
Here is the story of an undepressed man.]]>
            </description>
            <itunes:subtitle>Dan Curtis went straight from Gothic Horror soap operas to the greatest epic in television history.  He was a pure popular artist, who simply loved what he was doing.  This is the story of an undepressed man.  </itunes:subtitle>
            <itunes:summary>Dan Curtis went straight from Gothic Horror soap operas to the greatest epic ever made for television.  His heart was always in his work,  from &quot;Dark Shadows&quot; to &quot;The Night Stalker&quot; to... &quot;The Winds of War&quot;.  When it comes to his 29-hour genius production &quot;War and Remembrance,  Can&apos;t Touch This!
                Here is the story of an undepressed man.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2021%20-%20Plymouth%20Adventure.m4a" type="audio/x-m4a" length="16051728" />
            <guid>http://mbird.com/podcastgen/media/Episode%2021%20-%20Plymouth%20Adventure.m4a</guid>
            <pubDate>Sat, 13 Nov 2010 13:20:59 -0500</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:32:28</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 20 - I Learned to Yodel</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Did you know meditation can make you a better Protestant?
Here's why.]]>
            </description>
            <itunes:subtitle>Did you know meditation can make you a better Protestant?  Here&apos;s why.</itunes:subtitle>
            <itunes:summary>Did you know meditation can make you a better Protestant?
                Here&apos;s why.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Episode%2020%20-%20I%20Learned%20to%20Yodel.m4a" type="audio/x-m4a" length="16363824" />
            <guid>http://mbird.com/podcastgen/media/Episode%2020%20-%20I%20Learned%20to%20Yodel.m4a</guid>
            <pubDate>Tue, 26 Oct 2010 10:01:04 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:06</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 19: The Gothic</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[New thoughts on 'the Gothic' in movies and literature -- from Irvin S. Cobb, whose Gothic story "Fishhead" was termed a "banefully effective tale" by H. P. Lovecraft; to Ray Russell, of "Sardonicus" fame; to Roger Corman, who brought the House down around Roderick Usher.
Turns out it's all about bodily disintegration in an enclosed space, and the dead hand of the past  upon the hopes of the present.
The Gothic becomes a fascinating study in the quest for bookings on the Last Metro.]]>
            </description>
            <itunes:subtitle>The boys in the basement turn out to be... a dream.</itunes:subtitle>
            <itunes:summary>New thoughts on &apos;the Gothic&apos; in movies and literature -- from Irvin S. Cobb, whose Gothic story &quot;Fishhead&quot; was termed a &quot;banefully effective tale&quot; by H. P. Lovecraft; to Ray Russell, of &quot;Sardonicus&quot; fame; to Roger Corman, who brought the House down around Roderick Usher.
                Turns out it&apos;s all about bodily disintegration in an enclosed space, and the dead hand of the past  upon the hopes of the present.
                The Gothic becomes a fascinating study in the quest for bookings on the Last Metro.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%2019%20-%20Final%20Take.m4a" type="audio/x-m4a" length="17032912" />
            <guid>http://mbird.com/podcastgen/media/Podcast%2019%20-%20Final%20Take.m4a</guid>
            <pubDate>Wed, 20 Oct 2010 10:38:37 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:34:28</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 17: The Hammer and the Cross</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Hammer Horror is a beautiful thing -- everything movies should be, or almost everything.
There is also this delightful religious dimension, in which the High Priest of Karnak prays in the language of  the Book of Common Prayer and Peter Cushing is 'fighting evil every bit as much' as a Church of England entymologist/bishop in "Hound of the Baskervilles".
Here is my little 'National Geographic Society lecture', on one of the nicest acres of filmdom and fandom.  It was recorded at Constitution Hall in our Nation's Capital.
]]>
            </description>
            <itunes:subtitle>A National Geographic Society Lecture on Hammer Horror</itunes:subtitle>
            <itunes:summary>Hammer Horror is a beautiful thing -- everything movies should be, or almost everything.
                There is also this delightful religious dimension, in which the High Priest of Karnak prays in the language of  the Book of Common Prayer and Peter Cushing is &apos;fighting evil every bit as much&apos; as a Church of England entymologist/bishop in &quot;Hound of the Baskervilles&quot;.
                Here is my little &apos;National Geographic Society lecture&apos;, on one of the nicest acres of filmdom and fandom.  It was recorded at Constitution Hall in our Nation&apos;s Capital.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%2022.m4a" type="audio/x-m4a" length="18184672" />
            <guid>http://mbird.com/podcastgen/media/Podcast%2022.m4a</guid>
            <pubDate>Wed, 29 Sep 2010 18:33:00 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:36:48</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 16: Irvin S. Cobb</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Irvin S. Cobb (1876-1944) was famous in his day, but is unread now.
Ours is the loss!  His "Judge Priest" stories are as parabolic of grace as it gets.  They exude peace, love, and understanding.  And what's so funny about that?
Here's your chance to bone up on Irvin S. Cobb!
By the way,  John Ford liked Cobb so much that
he made two movies out of his stories, and then put him in a third.
In 1961 Ford made a personal pilgrimage to Cobb's grave at Paducah, Kentucky.
Two weeks from tomorrow I hope to do the same.

]]>
            </description>
            <itunes:subtitle>Another unread author: But Wait!  Hear me out.</itunes:subtitle>
            <itunes:summary>Irvin S. Cobb (1876-1944) was famous in his day, but is unread now.
                Ours is the loss!  His &quot;Judge Priest&quot; stories are as parabolic of grace as it gets.  They exude peace, love, and understanding.  And what&apos;s so funny about that?
                Here&apos;s your chance to bone up on Irvin S. Cobb!
                By the way,  John Ford liked Cobb so much that
                he made two movies out of his stories, and then put him in a third.
                In 1961 Ford made a personal pilgrimage to Cobb&apos;s grave at Paducah, Kentucky.
                Two weeks from tomorrow I hope to do the same.

            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%2024.m4a" type="audio/x-m4a" length="15663280" />
            <guid>http://mbird.com/podcastgen/media/Podcast%2024.m4a</guid>
            <pubDate>Wed, 22 Sep 2010 18:00:39 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:31:40</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 15: Hot August Night</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[The Jansenists never declined.
They got wiped out good.
Think "End of the Line" by the Traveling Wilburys.
Pascal enters and exits, assisted by Roberto Rossellini's tv show (1971) and Jack Kerouac's bar game (1969).
The 'Sun King' plays his cruel part, while Our Ladies of Port-Royal
hold the line.
They really hold the line!
As Sainte-Beuve wrote of the Jansenists,
"They were from Calvary".]]>
            </description>
            <itunes:subtitle>But 1664, not 1969! -- Part Two on the Jansenists.</itunes:subtitle>
            <itunes:summary>The Jansenists never declined.
                They got wiped out good.
                Think &quot;End of the Line&quot; by the Traveling Wilburys.
                Pascal enters and exits, assisted by Roberto Rossellini&apos;s tv show (1971) and Jack Kerouac&apos;s bar game (1969).
                The &apos;Sun King&apos; plays his cruel part, while Our Ladies of Port-Royal
                hold the line.
                They really hold the line!
                As Sainte-Beuve wrote of the Jansenists,
                &quot;They were from Calvary&quot;.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%2019%203.m4a" type="audio/x-m4a" length="17279648" />
            <guid>http://mbird.com/podcastgen/media/Podcast%2019%203.m4a</guid>
            <pubDate>Thu, 16 Sep 2010 06:56:08 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:34:58</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode 14: Paris When It Sizzles</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Jansenism was a religious movement in Seventeenth-Century France that threatened Church and State.  Its apologists, including Blaise Pascal and Jean Racine, thought their movement, based on its re-discovery of the teachings of St. Augustine,  could save Christianity from the Protestants.
Its detractors thought Jansenism WAS Protestantism, but a Fifth Column of it, burrowing away within the Catholic Church.  The two positions were irreconcilable.
The Jansenists lost, and lost catastrophically.
What an interesting lesson here in 'Church', and State.]]>
            </description>
            <itunes:subtitle>Jansenism: the fourth most interesting thing ever to happen in the history of Christianity.  What was Pascal thinking? Et Racine?</itunes:subtitle>
            <itunes:summary>Jansenism was a religious movement in Seventeenth-Century France that threatened Church and State.  Its apologists, including Blaise Pascal and Jean Racine, thought their movement, based on its re-discovery of the teachings of St. Augustine,  could save Christianity from the Protestants.
                Its detractors thought Jansenism WAS Protestantism, but a Fifth Column of it, burrowing away within the Catholic Church.  The two positions were irreconcilable.
                The Jansenists lost, and lost catastrophically.
                What an interesting lesson here in &apos;Church&apos;, and State.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%2018.m4a" type="audio/x-m4a" length="16123904" />
            <guid>http://mbird.com/podcastgen/media/Podcast%2018.m4a</guid>
            <pubDate>Thu, 09 Sep 2010 08:03:35 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:32:36</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Sneak Peek: &quot;By Love Possessed&quot;</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["By Love Possessed" was hailed at first as the great novel of its decade.  A few months later it was traduced as a symbol of Eisenhower-era 'middle-brow' complacency.  The second verdict stuck.
The problem was its message: it praised acquiescence rather than transformation.  It is indeed a 'novel of resignation'.  Is that a good thing?]]>
            </description>
            <itunes:subtitle>This 1957 novel is among the unsung greats.  It is sometimes called a &apos;novel of resignation&apos;.  Is that a good thing?</itunes:subtitle>
            <itunes:summary>&quot;By Love Possessed&quot; was hailed at first as the great novel of its decade.  A few months later it was traduced as a symbol of Eisenhower-era &apos;middle-brow&apos; complacency.  The second verdict stuck.
                The problem was its message: it praised acquiescence rather than transformation.  It is indeed a &apos;novel of resignation&apos;.  Is that a good thing?</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%20Eight%203.m4a" type="audio/x-m4a" length="15952224" />
            <guid>http://mbird.com/podcastgen/media/Podcast%20Eight%203.m4a</guid>
            <pubDate>Sun, 29 Aug 2010 08:58:41 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:32:15</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Sunday Supplement: The Life of James Gould Cozzens</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[James Gould Cozzens (1903-1978) observed life accurately.  in 1957 he told 'Time' Magazine that "most people get a raw deal from life, and life is what it is".  His novels "By Love Possessed" and "Guard of Honor" are among the greatest of 20th Century novels.

]]>
            </description>
            <itunes:subtitle>A fascinating literary life, the story of a man who knew a great deal and wrote it all down.</itunes:subtitle>
            <itunes:summary>James Gould Cozzens (1903-1978) observed life accurately.  in 1957 he told &apos;Time&apos; Magazine that &quot;most people get a raw deal from life, and life is what it is&quot;.  His novels &quot;By Love Possessed&quot; and &quot;Guard of Honor&quot; are among the greatest of 20th Century novels.

            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%20Seven%203.m4a" type="audio/x-m4a" length="18058112" />
            <guid>http://mbird.com/podcastgen/media/Podcast%20Seven%203.m4a</guid>
            <pubDate>Sun, 29 Aug 2010 08:42:03 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:36:33</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>BONUS Episode!:Giant Crab Movies</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[In this amazing weekend bonus episode, our hero must claw his way through the history of giant-crab movies.  Does he survive?  You be the judge!]]>
            </description>
            <itunes:subtitle>In this amazing bonus episode, our hero claws his way through the history of giant-crab movies.</itunes:subtitle>
            <itunes:summary>In this amazing weekend bonus episode, our hero must claw his way through the history of giant-crab movies.  Does he survive?  You be the judge!</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%2011.m4a" type="audio/x-m4a" length="16718304" />
            <guid>http://mbird.com/podcastgen/media/Podcast%2011.m4a</guid>
            <pubDate>Fri, 20 Aug 2010 09:05:54 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:33:49</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode Seven - &quot;Man Gave Names to all the Animals&quot; </title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA["Man Gave Names to all the Animals" (Bob Dylan),
meaning
Eric Burdon and The Animals.

Thoughts on true greatness, thoughts on Fun.]]>
            </description>
            <itunes:subtitle>&quot;Man Gave Names to all the Animals&quot; (Bob Dylan).  And he named them Eric Burdon, Alan Price, Chas Chandler, Hilton Valentine, and John Steel.</itunes:subtitle>
            <itunes:summary>&quot;Man Gave Names to all the Animals&quot; (Bob Dylan),
                meaning
                Eric Burdon and The Animals.

                Thoughts on true greatness, thoughts on Fun.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%2013.m4a" type="audio/x-m4a" length="22450112" />
            <guid>http://mbird.com/podcastgen/media/Podcast%2013.m4a</guid>
            <pubDate>Wed, 18 Aug 2010 08:39:54 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:45:30</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode Six - The Browning Version</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[From a perfect movie comes a Version of the 25th Chorus of "Mexico City Blues":
Is my own, is your own,
Is not Owned by Self-Owner
but found by Self-Loser --
Old Ancient Teaching".

This podcast is dedicated to David Browder.
]]>
            </description>
            <itunes:subtitle>&quot;Old Ancient Teaching&quot;.  Dedicated to David Browder.</itunes:subtitle>
            <itunes:summary>From a perfect movie comes a Version of the 25th Chorus of &quot;Mexico City Blues&quot;:
                Is my own, is your own,
                Is not Owned by Self-Owner
                but found by Self-Loser --
                Old Ancient Teaching&quot;.

                This podcast is dedicated to David Browder.
            </itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%20Ten.m4a" type="audio/x-m4a" length="18782144" />
            <guid>http://mbird.com/podcastgen/media/Podcast%20Ten.m4a</guid>
            <pubDate>Wed, 18 Aug 2010 08:27:08 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:38:01</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Bohemian Rhapsody -- The Rite One</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[The subject is preaching, the Achilles Heel of American religion.  We turn to Jack Kerouac's "List of Essentials" in spontaneous expression for help.  Turns out it's the singer not the song.]]>
            </description>
            <itunes:subtitle>The subject is preaching, an Achilles Heel in American religion.  I turn to Jack Kerouac for some help.</itunes:subtitle>
            <itunes:summary>The subject is preaching, the Achilles Heel of American religion.  We turn to Jack Kerouac&apos;s &quot;List of Essentials&quot; in spontaneous expression for help.  Turns out it&apos;s the singer not the song.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%20Five%202_2.m4a" type="audio/x-m4a" length="18337392" />
            <guid>http://mbird.com/podcastgen/media/Podcast%20Five%202_2.m4a</guid>
            <pubDate>Tue, 10 Aug 2010 12:55:01 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:37:07</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Beatnik Beach</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[The title of a song by the Go-Go's sets the stage for this second cast on the preaching art.  Once again, it's the singer not the song.  Or at least, that's where we start.
Welcome to Beatnik Beach!]]>
            </description>
            <itunes:subtitle>The title of a song by the Go-Go&apos;s sets the stage for part two on preaching.  Welcome to Beatnik Beach!</itunes:subtitle>
            <itunes:summary>The title of a song by the Go-Go&apos;s sets the stage for this second cast on the preaching art.  Once again, it&apos;s the singer not the song.  Or at least, that&apos;s where we start.
                Welcome to Beatnik Beach!</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%20Six%202.m4a" type="audio/x-m4a" length="18667392" />
            <guid>http://mbird.com/podcastgen/media/Podcast%20Six%202.m4a</guid>
            <pubDate>Tue, 10 Aug 2010 12:27:06 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:37:47</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode One - What&apos;s it all about, Alfie?</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[In which our hero introduces you to his search.  "For you the living, this Mash was meant, too."]]>
            </description>
            <itunes:subtitle>In which our hero introduces you to his search.  &quot;For you the living, this Mash was meant, too.&quot;</itunes:subtitle>
            <itunes:summary>In which our hero introduces you to his search.  &quot;For you the living, this Mash was meant, too.&quot;</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%20Nine%202.m4a" type="audio/x-m4a" length="20658608" />
            <guid>http://mbird.com/podcastgen/media/Podcast%20Nine%202.m4a</guid>
            <pubDate>Wed, 04 Aug 2010 09:59:40 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:41:51</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode Two - The Alcestiad, Act One</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Our hero, incarnated as an ancient Greek princess, finds Love and Happiness, Thornton-Wilder style.]]>
            </description>
            <itunes:subtitle>Our hero, incarnated as an ancient Greek princess, finds Love and Happiness, Thornton-Wilder style.</itunes:subtitle>
            <itunes:summary>Our hero, incarnated as an ancient Greek princess, finds Love and Happiness, Thornton-Wilder style.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%20Three%203.m4a" type="audio/x-m4a" length="18533488" />
            <guid>http://mbird.com/podcastgen/media/Podcast%20Three%203.m4a</guid>
            <pubDate>Wed, 04 Aug 2010 09:58:46 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:37:31</itunes:duration>
            <itunes:keywords />
        </item>
        <item>
            <title>Episode Three - The Alcestiad, Act Three</title>
            <itunes:author>Paul Zahl</itunes:author>
            <description>
                <![CDATA[Our hero, again incarnated as the Queen of Thessaly, heads south, only to still find happiness.]]>
            </description>
            <itunes:subtitle>Our hero, again incarnated as the Queen of Thessaly, heads south, only to still find happiness.</itunes:subtitle>
            <itunes:summary>Our hero, again incarnated as the Queen of Thessaly, heads south, only to still find happiness.</itunes:summary>
            <enclosure url="http://mbird.com/podcastgen/media/Podcast%20Four%203.m4a" type="audio/x-m4a" length="19280560" />
            <guid>http://mbird.com/podcastgen/media/Podcast%20Four%203.m4a</guid>
            <pubDate>Wed, 04 Aug 2010 09:57:57 -0400</pubDate>
            <category>Christianity</category>
            <itunes:explicit>no</itunes:explicit>
            <itunes:duration>00:39:02</itunes:duration>
            <itunes:keywords />
        </item>

</channel>
</rss><?php exit; ?>
