<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

$settings = get_option('ct_podcast_settings');

$args = array(
	'post_type' => 'ct_sermon',
	'post_status' => 'publish',
	'posts_per_page' => -1,
	'orderby' => 'date',
	'order' => 'DESC',
);
query_posts( $args );

echo '<?xml version="1.0" encoding="UTF-8"?>' ?>

<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:atom="http://www.w3.org/2005/Atom" version="2.0">
	<channel>
		<title><?php echo esc_html( $settings['title'] ) ?></title>
		<link><?php echo esc_url( $settings['website_link'] ) ?></link>
		<atom:link href="http://www.mbird.com/feed/podcast/" rel="self" type="application/rss+xml" />
		<language><?php echo esc_html( $settings['language'] ) ?></language>
		<copyright><?php echo esc_html( $settings['copyright'] ) ?></copyright>
		<itunes:subtitle><?php echo esc_html( $settings['itunes_subtitle'] ) ?></itunes:subtitle>
		<itunes:author><?php echo esc_html( $settings['itunes_author'] ) ?></itunes:author>
		<itunes:summary><?php echo esc_html( $settings['itunes_summary'] ) ?></itunes:summary>
		<description><?php echo esc_html( $settings['description'] ) ?></description>
		<itunes:owner>
			<itunes:name><?php echo esc_html( $settings['itunes_owner_name'] ) ?></itunes:name>
			<itunes:email><?php echo esc_html( $settings['itunes_owner_email'] ) ?></itunes:email>
		</itunes:owner>
		<itunes:explicit><?php echo esc_html( $settings['itunes_explicit_content'] ) ?></itunes:explicit>
		<itunes:image href="<?php echo esc_url( $settings['itunes_cover_image'] ) ?>" />
		<itunes:category text="<?php echo esc_attr( $settings['itunes_top_category'] ) ?>">
			<itunes:category text="<?php echo esc_attr( $settings['itunes_sub_category'] ) ?>"/>
		</itunes:category>
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

<?php
$speaker = strip_tags( get_the_term_list( $post->ID, 'sermon_speaker', '', ' &amp; ', '' ) );
$series = strip_tags( get_the_term_list( $post->ID, 'sermon_series', '', ', ', '' ) );
$topic = strip_tags( get_the_term_list( $post->ID, 'sermon_topic', '', ', ', '' ) );
$topic = ( $topic ) ? sprintf( '<itunes:keywords>%s</itunes:keywords>', $topic ) : null;

$subtitle = strip_tags( get_the_excerpt() );

$subtitle = (strlen($subtitle) > 255) ? substr($subtitle,0,252).'...' : $subtitle;

$post_image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'large' );
$post_image = ( $post_image ) ? $post_image['0'] : null;

$characters = array(" ","'","&");
$entities = array( '%20', '%27','%26');

$audio_file = esc_url(str_replace($characters, $entities, get_post_meta( $post->ID, '_ct_sm_audio_file', true )));
$audio_duration = get_post_meta( $post->ID, '_ct_sm_audio_length', true );
$audio_file_size = get_post_meta( $post->ID, '_ct_sm_file_size', true );

if (!$audio_file_size || $audio_file_size === "0" ){

	$audio_file_size = ct_get_filesize( esc_url( $audio_file ) );
	add_post_meta( $post->ID, '_ct_sm_file_size', $audio_file_size,  true ) || update_post_meta( $post->ID, '_ct_sm_file_size', $audio_file_size);

}



?>
<?php if ( $audio_file && $audio_file_size && $audio_duration ) : ?>
		<item>
			<title><?php the_title() ?></title>
			<link><?php the_permalink() ?></link>
			<description><?php echo html_entity_decode( strip_tags( get_the_content() ) ) ?></description>
			<itunes:author><?php echo $speaker ?></itunes:author>
			<itunes:subtitle><?php echo $subtitle ?></itunes:subtitle>
			<itunes:summary><?php /* echo strip_tags( get_the_excerpt() ) */?>podcast</itunes:summary>
<?php if ( $post_image ) : ?>
			<itunes:image href="<?php echo esc_url($post_image) ?>" />
<?php endif; ?>
			<enclosure url="<?php echo esc_url($audio_file) ?>" length="<?php echo $audio_file_size ?>" type="audio/mpeg" />
			<guid><?php echo $audio_file ?></guid>
			<pubDate><?php echo get_the_time('D, d M Y H:i:s T') ?></pubDate>
			<itunes:duration><?php echo esc_html( $audio_duration ) ?></itunes:duration>
<?php if ( $topic ) : ?>
			<?php echo $topic . "\n" ?>
<?php endif; ?>
		</item>
<?php endif; ?>
<?php endwhile; endif; wp_reset_query(); ?>
	</channel>
</rss>
