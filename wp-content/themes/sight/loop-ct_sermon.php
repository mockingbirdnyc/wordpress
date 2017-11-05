<?php
/*
===================================================================================================
WARNING! DO NOT EDIT THIS FILE OR ANY TEMPLATE FILES IN THIS THEME!

To make it easy to update your theme, you should not edit this file. Instead, you should create a
Child Theme first. This will ensure your template changes are not lost when updating the theme.

You can learn more about creating Child Themes here: http://codex.wordpress.org/Child_Themes

You have been warned! :)
===================================================================================================
*/
?>
<?php


$sermon_settings = get_option('ct_sermon_settings');
isset($sermon_settings['orderby']) ? $orderby = $sermon_settings['orderby'] : $orderby = null;
isset($sermon_settings['order']) ? $order = $sermon_settings['order'] : $order = null;


if(empty($orderby)) $orderby = 'date';
if(empty($order)) $order = 'DESC';

global $post;

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

if($orderby == 'views'):
	$args=array(
		'post_type' => 'ct_sermon',
		'post_status' => 'publish',
		'paged' => $paged,
		'meta_key' => 'Views',
		'orderby' => 'meta_value_num',
		'order' => $order,
		'sermon_speaker' => get_query_var('sermon_speaker'),
		'sermon_series' => get_query_var('sermon_series'),
		'sermon_format' => get_query_var('sermon_format'),
		'sermon_topic' => get_query_var('sermon_topic'),
		//'s' => get_query_var('s')
	);
else:
	$args=array(
		'post_type' => 'ct_sermon',
		'post_status' => 'publish',
		'paged' => $paged,
		'orderby' => $orderby,
		'order' => $order,
		'sermon_speaker' => get_query_var('sermon_speaker'),
		'sermon_series' => get_query_var('sermon_series'),
		'sermon_format' => get_query_var('sermon_format'),
		'sermon_topic' => get_query_var('sermon_topic'),
		//'s' => get_query_var('s')
	);
endif;


$query = new WP_Query($args);

$i = 0;

if($query->have_posts()): while($query->have_posts()): $query->the_post();

$i++;

$sermon_speaker = get_the_term_list($post->ID, 'sermon_speaker', '', ' + ');

$sm_bible01_book = get_post_meta($post->ID, '_ct_sm_bible01_book', true);
$sm_bible01_start_chap = get_post_meta($post->ID, '_ct_sm_bible01_start_chap', true);
$sm_bible01_start_verse = get_post_meta($post->ID, '_ct_sm_bible01_start_verse', true);
$sm_bible01_end_chap = get_post_meta($post->ID, '_ct_sm_bible01_end_chap', true);
$sm_bible01_end_verse = get_post_meta($post->ID, '_ct_sm_bible01_end_verse', true);

$sm_bible02_book = get_post_meta($post->ID, '_ct_sm_bible02_book', true);
$sm_bible02_start_chap = get_post_meta($post->ID, '_ct_sm_bible02_start_chap', true);
$sm_bible02_start_verse = get_post_meta($post->ID, '_ct_sm_bible02_start_verse', true);
$sm_bible02_end_chap = get_post_meta($post->ID, '_ct_sm_bible02_end_chap', true);
$sm_bible02_end_verse = get_post_meta($post->ID, '_ct_sm_bible02_end_verse', true);

$sm_bible03_book = get_post_meta($post->ID, '_ct_sm_bible03_book', true);
$sm_bible03_start_chap = get_post_meta($post->ID, '_ct_sm_bible03_start_chap', true);
$sm_bible03_start_verse = get_post_meta($post->ID, '_ct_sm_bible03_start_verse', true);
$sm_bible03_end_chap = get_post_meta($post->ID, '_ct_sm_bible03_end_chap', true);
$sm_bible03_end_verse = get_post_meta($post->ID, '_ct_sm_bible03_end_verse', true);

$sm_bible04_book = get_post_meta($post->ID, '_ct_sm_bible04_book', true);
$sm_bible04_start_chap = get_post_meta($post->ID, '_ct_sm_bible04_start_chap', true);
$sm_bible04_start_verse = get_post_meta($post->ID, '_ct_sm_bible04_start_verse', true);
$sm_bible04_end_chap = get_post_meta($post->ID, '_ct_sm_bible04_end_chap', true);
$sm_bible04_end_verse = get_post_meta($post->ID, '_ct_sm_bible04_end_verse', true);

$sm_bible05_book = get_post_meta($post->ID, '_ct_sm_bible05_book', true);
$sm_bible05_start_chap = get_post_meta($post->ID, '_ct_sm_bible05_start_chap', true);
$sm_bible05_start_verse = get_post_meta($post->ID, '_ct_sm_bible05_start_verse', true);
$sm_bible05_end_chap = get_post_meta($post->ID, '_ct_sm_bible05_end_chap', true);
$sm_bible05_end_verse = get_post_meta($post->ID, '_ct_sm_bible05_end_verse', true);

$img_atts = array(
	'alt'	=> trim(strip_tags($post->post_title)),
	'title'	=> trim(strip_tags($post->post_title)),
);
//remove_filter( 'the_content', 'sharing_display',19);
//remove_filter( 'the_excerpt', 'sharing_display',19);
?>
<div id="loop" class="list clear">

<div <?php post_class('post ct_sermon clear'); ?> id="post_<?php the_ID(); ?>">
	<?php
				get_the_image(array(
				'meta_key' => null,
				'image_class' => 'thumb',
				'callback' => 'find_image'
			));
			?>

	<!-- <div class="post-category"><?php echo get_the_term_list($post->ID, 'sermon_topic', '', '&nbsp;&nbsp;&nbsp;&nbsp;'); ?></div> -->
	<div class="post-category"><?php echo get_the_term_list($post->ID, 'sermon_series', '', '&nbsp;&nbsp;&nbsp;&nbsp;'); ?></div>
            <h2><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h2>
<?php
if (current_user_can('administrator')){
    global $wpdb;
    echo "<pre>";
    print_r($wpdb->queries);
    echo "</pre>";
}
?>

		<div class="post-meta">by <span class="post-author"><?php echo $sermon_speaker; ?></span>
                                   on <span
                        class="post-date"><?php the_time(__('M j, Y')) ?></span> <?php
							$book = $sm_bible01_book;
							$start_chap = $sm_bible01_start_chap;
							$start_verse = $sm_bible01_start_verse;
							$end_chap = $sm_bible01_end_chap;
							$end_verse = $sm_bible01_end_verse;

							if(!empty($book)):
								echo 'on <span>'. $book;
							endif;

							if(!empty($start_chap)):
								echo ' '. $start_chap;
							endif;

							if(!empty($start_chap) && !empty($start_verse)):
								echo ':'. $start_verse;
							endif;

							if(!empty($end_chap) && !empty($end_verse)):
								if($start_chap == $end_chap):
									echo ' - '. $end_verse;
								else:
									echo ' - '. $end_chap .':'. $end_verse;
								endif;
							endif;

							if(empty($end_chap) && !empty($end_verse)):
								if(!empty($start_verse)):
									echo ' - '. $end_verse;
								endif;
							endif;

							if(!empty($end_chap) && empty($end_verse)):
								if(!empty($start_chap) && empty($start_verse)):
									echo ' - '. $end_chap;
								endif;
							endif;

							if(!empty($book)):
								echo '</span>';
							endif;
						?>
						<?php
							$book = $sm_bible02_book;
							$start_chap = $sm_bible02_start_chap;
							$start_verse = $sm_bible02_start_verse;
							$end_chap = $sm_bible02_end_chap;
							$end_verse = $sm_bible02_end_verse;

							if(!empty($book)):
								echo ' and <span>'. $book;
							endif;

							if(!empty($start_chap)):
								echo ' '. $start_chap;
							endif;

							if(!empty($start_chap) && !empty($start_verse)):
								echo ':'. $start_verse;
							endif;

							if(!empty($end_chap) && !empty($end_verse)):
								if($start_chap == $end_chap):
									echo ' - '. $end_verse;
								else:
									echo ' - '. $end_chap .':'. $end_verse;
								endif;
							endif;

							if(empty($end_chap) && !empty($end_verse)):
								if(!empty($start_verse)):
									echo ' - '. $end_verse;
								endif;
							endif;

							if(!empty($end_chap) && empty($end_verse)):
								if(!empty($start_chap) && empty($start_verse)):
									echo ' - '. $end_chap;
								endif;
							endif;

							if(!empty($book)):
								echo '</span>';
							endif;
						?>
						<?php
							$book = $sm_bible03_book;
							$start_chap = $sm_bible03_start_chap;
							$start_verse = $sm_bible03_start_verse;
							$end_chap = $sm_bible03_end_chap;
							$end_verse = $sm_bible03_end_verse;

							if(!empty($book)):
								echo ' and <span>'. $book;
							endif;

							if(!empty($start_chap)):
								echo ' '. $start_chap;
							endif;

							if(!empty($start_chap) && !empty($start_verse)):
								echo ':'. $start_verse;
							endif;

							if(!empty($end_chap) && !empty($end_verse)):
								if($start_chap == $end_chap):
									echo ' - '. $end_verse;
								else:
									echo ' - '. $end_chap .':'. $end_verse;
								endif;
							endif;

							if(empty($end_chap) && !empty($end_verse)):
								if(!empty($start_verse)):
									echo ' - '. $end_verse;
								endif;
							endif;

							if(!empty($end_chap) && empty($end_verse)):
								if(!empty($start_chap) && empty($start_verse)):
									echo ' - '. $end_chap;
								endif;
							endif;

							if(!empty($book)):
								echo '</span>';
							endif;
						?>
						<?php
							$book = $sm_bible04_book;
							$start_chap = $sm_bible04_start_chap;
							$start_verse = $sm_bible04_start_verse;
							$end_chap = $sm_bible04_end_chap;
							$end_verse = $sm_bible04_end_verse;

							if(!empty($book)):
								echo ' and <span>'. $book;
							endif;

							if(!empty($start_chap)):
								echo ' '. $start_chap;
							endif;

							if(!empty($start_chap) && !empty($start_verse)):
								echo ':'. $start_verse;
							endif;

							if(!empty($end_chap) && !empty($end_verse)):
								if($start_chap == $end_chap):
									echo ' - '. $end_verse;
								else:
									echo ' - '. $end_chap .':'. $end_verse;
								endif;
							endif;

							if(empty($end_chap) && !empty($end_verse)):
								if(!empty($start_verse)):
									echo ' - '. $end_verse;
								endif;
							endif;

							if(!empty($end_chap) && empty($end_verse)):
								if(!empty($start_chap) && empty($start_verse)):
									echo ' - '. $end_chap;
								endif;
							endif;

							if(!empty($book)):
								echo '</span>';
							endif;
						?>
						<?php
							$book = $sm_bible05_book;
							$start_chap = $sm_bible05_start_chap;
							$start_verse = $sm_bible05_start_verse;
							$end_chap = $sm_bible05_end_chap;
							$end_verse = $sm_bible05_end_verse;

							if(!empty($book)):
								echo ' and <span>'. $book;
							endif;

							if(!empty($start_chap)):
								echo ' '. $start_chap;
							endif;

							if(!empty($start_chap) && !empty($start_verse)):
								echo ':'. $start_verse;
							endif;

							if(!empty($end_chap) && !empty($end_verse)):
								if($start_chap == $end_chap):
									echo ' - '. $end_verse;
								else:
									echo ' - '. $end_chap .':'. $end_verse;
								endif;
							endif;

							if(empty($end_chap) && !empty($end_verse)):
								if(!empty($start_verse)):
									echo ' - '. $end_verse;
								endif;
							endif;

							if(!empty($end_chap) && empty($end_verse)):
								if(!empty($start_chap) && empty($start_verse)):
									echo ' - '. $end_chap;
								endif;
							endif;

							if(!empty($book)):
								echo '</span>';
							endif;
						?> <?php edit_post_link( __( 'Edit entry'), '<em>&bull; </em>'); ?>
            </div>
             <div class="post-content excerpt">
			<?php the_excerpt();
				//endif; ?>
            </div>

	<?php
	$sm_video_embed = get_post_meta($post->ID, '_ct_sm_video_embed', true);
	$sm_video_file = get_post_meta($post->ID, '_ct_sm_video_file', true);


	$sm_audio_length = get_post_meta($post->ID, '_ct_sm_audio_length', true);
	$sm_audio_file = get_post_meta($post->ID, '_ct_sm_audio_file', true);

	$sm_sg_file = get_post_meta($post->ID, '_ct_sm_sg_file', true);
	?>
	<?php if($sm_video_embed): ?>
				<div class="video_player">
					<?php echo apply_filters( 'the_content', $sm_video_embed ) ?>
				</div>
	<?php endif; ?>
	<?php if(!empty($sm_audio_file)): ?>
					<div class="audio_player">
						<div class="player"><?php echo do_shortcode("[audio ". $sm_audio_file ."]"); ?></div>
						<p class="audio download"><a href="<?php echo $sm_audio_file ?>" title="right-click to download">Download</a></p>
					</div>
	<?php endif; ?>
	<?php if($sm_sg_file): ?>

						<div class="action">
						<p>
						<a href="<?php echo $sm_sg_file; ?>" class="print download" title="right-click to download">Download</a>
						</p>
						</div>
<?php endif; ?>
	<?php
	if ( function_exists( 'sharing_display' ) ) {
    echo sharing_display();
	}
	?>
	<div class="clear"></div>
</div>
</div>
<?php endwhile; else: ?>
<div class="post ct_sermon first">
	<p><?php _e('Sorry, nothing was found matching that criteria. Please try your search again, please.', 'churchthemes'); ?></p>
</div>
<?php endif; ?>
