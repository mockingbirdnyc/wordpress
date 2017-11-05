<?php
$biblebook = $_GET['bible_book'];
$resource = $_GET['submit'];

//$term =	$wp_query->get_queried_object();

$post_type = ( isset( $_GET['post_type'] ) ) ? $_GET['post_type'] : null;
$current_speaker = get_query_var('sermon_speaker');
$speaker_terms = get_terms('sermon_speaker');
$current_series = get_query_var('sermon_series');
$series_terms = get_terms('sermon_series');
$current_format = get_query_var('sermon_format');
$format_terms = get_terms('sermon_format');
$current_topic = get_query_var('sermon_topic');
$topic_terms = get_terms('sermon_topic');
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

$sermon_settings = get_option('ct_sermon_settings');  ?>

<?php
isset($sermon_settings['archive_filter_1']) ? $ct_sermon_filters_speaker = $sermon_settings['archive_filter_1'] : $ct_sermon_filters_speaker = null;
isset($sermon_settings['archive_filter_2']) ? $ct_sermon_filters_series = $sermon_settings['archive_filter_2'] : $ct_sermon_filters_series = null;
isset($sermon_settings['archive_filter_3']) ? $ct_sermon_filters_format = $sermon_settings['archive_filter_3'] : $ct_sermon_filters_format = null;
isset($sermon_settings['archive_filter_4']) ? $ct_sermon_filters_topic = $sermon_settings['archive_filter_4'] : $ct_sermon_filters_topic = null;
isset($sermon_settings['archive_filter_5']) ? $ct_sermon_filters_keyword = $sermon_settings['archive_filter_5'] : $ct_sermon_filters_keyword = null;
$ct_sermon_filters_button_text = $sermon_settings['archive_filters_button_text'];
if(empty($ct_sermon_filters_button_text)) $ct_sermon_filters_button_text = 'Search Sermons';
$ct_sermon_archive_title = $sermon_settings['archive_title'];
if(empty($ct_sermon_archive_title)) $ct_sermon_archive_title = '';
$ct_sermon_archive_slug = $sermon_settings['archive_slug'];
if(empty($ct_sermon_archive_slug)) $ct_sermon_archive_slug = 'sermons';
$ct_sermon_archive_tagline = $sermon_settings['archive_tagline'];
$ct_sermon_archive_layout = $sermon_settings['archive_layout'];
?>

<?php get_header(); ?>

<div class="content-title">

    <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
		<?php if(is_post_type_archive('ct_sermon') || $post_type == 'ct_sermon' || is_tax('sermon_speaker') || is_tax('sermon_series') || is_tax('sermon_format') || is_tax('sermon_topic') || $biblebook) { echo $ct_sermon_archive_title; ?>		
        <?php /* If this is a category archive */ } elseif (is_category()) { ?>
        <?php printf(__('%s'), single_cat_title('', false)); ?>
        <?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
        <?php printf(__('Posts tagged &quot;%s&quot;'), single_tag_title('', false) ); ?>
        <?php /* If this is a daily archive */ } elseif (is_day()) { ?>
        <?php printf(_c('Daily archive %s'), get_the_time(__('M j, Y'))); ?>
        <?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
        <?php printf(_c('Monthly archive %s'), get_the_time(__('F, Y'))); ?>
        <?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
        <?php printf(_c('Yearly archive %s'), get_the_time(__('Y'))); ?>
        <?php /* If this is an author archive */ } elseif (is_author()) { ?>
        Author Archive'); ?>
        <?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
        Blog Archives'); ?>
        <?php } ?>
		
		<!-- <span class="searched"><?php foreach ($speaker_terms as $term) { if($current_speaker == $term->slug) { echo $term->name; } } ?>
						<?php if($current_speaker && $current_series) { echo ' + '; } ?>
						<?php foreach ($series_terms as $term) { if($current_series == $term->slug) { echo $term->name; } } ?>
						<?php if(($current_speaker || $current_series) && $current_format) { echo ' + '; } ?>
						<?php foreach ($format_terms as $term) { if($current_format == $term->slug) { echo $term->name; } } ?>
						<?php if(($current_speaker || $current_series || $current_format) && $current_topic) { echo ' + '; } ?>
						<?php foreach ($topic_terms as $term) { if($current_topic == $term->slug) { echo $term->name; } } ?>
						<?php if(($current_speaker || $current_series || $current_format || $current_topic) && $biblebook ) { echo ' + '; } ?>
						<?php foreach ($books as $book) { if($biblebook == $book) {echo $book;} } ?>
						<?php if(($current_speaker || $current_series || $current_format || $current_topic || $biblebook ) && $search_query) { echo ' + '; } ?>
						<?php if($search_query && ($post_type == 'ct_sermon' || $current_speaker || $current_series || $current_format || $current_topic || $biblebook )): echo '&quot;'.$search_query.'&quot;'; endif; ?></span> -->
		
    <a href="javascript: void(0);" id="mode list"></a>
</div>
						
<?php if(is_post_type_archive('ct_sermon') || $post_type == 'ct_sermon' || is_tax('sermon_speaker') || is_tax('sermon_series') || is_tax('sermon_format') || is_tax('sermon_topic') || $biblebook || is_page('podcasts') ):

?>

<script>
	jQuery("#menu-item-35416").addClass("current-menu-item");
	if(jQuery("#dd .current-menu-item").length === 0) {
		jQuery("#menu-item-34100").addClass("current-menu-item");
	}
</script>

<?php wp_nav_menu( array( 'theme_location' => 'Resources menu', 'container_class' => 'resource_menu' ) ); ?>

<?php if($ct_sermon_filters_speaker || $ct_sermon_filters_series || $ct_sermon_filters_format || $ct_sermon_filters_topic || $ct_sermon_filters_keyword): ?>
				<form method="get" id="sermon-filter" action="<?php echo home_url('/'); ?><?php echo "resources" ?>">
					<div id="sermon_filter">
<?php if($ct_sermon_filters_speaker): ?>
						<div>
							<select name="sermon_speaker" id="sermon_speaker" style="display:none;">
								<option value="">Any Speaker</option>
								<?php dropdown_taxonomy_term('sermon_speaker'); ?>
							</select>
						</div>
<?php endif; ?>
<?php if($ct_sermon_filters_series): ?>
						<div>
							<select name="sermon_series" id="sermon_series" style="display:none;">
								<option value="">Any Series/Venue</option>
								<?php dropdown_taxonomy_term('sermon_series'); ?>
							</select>
						</div>
<?php endif; ?>
<?php if($ct_sermon_filters_format): ?>
						<div>
							<select name="sermon_format" id="sermon_format" style="display:none;">
								<option value="">Any Format</option>
								<?php dropdown_taxonomy_term('sermon_format'); ?>
							</select>
						</div>
<?php endif; ?>
<?php if($ct_sermon_filters_topic): ?>
						<div>
							<select name="sermon_topic" id="sermon_topic" style="display:none;">
								<option value="">Any Topic</option>
								<?php dropdown_taxonomy_term('sermon_topic'); ?>
							</select>
						</div>
<?php endif; ?>
<div>
							<select name="bible_book" id="bible_book" style="display:none;">
								<option value="">Any Scripture</option>
								<?php dropdown_books(); ?>
							</select>
						</div>
<?php if($ct_sermon_filters_keyword): ?>
						<div>
							<input type="hidden" name="post_type" value="ct_sermon" />
							<input type="text" name="s" size="20" placeholder="Search terms" value="<?php echo $s; ?>" class="sermon_keywords" />
						</div>
<?php endif; ?>
						<div><input type="submit" name="submit" class="button" value="<?php echo $ct_sermon_filters_button_text; ?>" /></div>
					</div>
				</form>
<?php 
	get_template_part('loop','ct_sermon');
	endif; 
	else: get_template_part('loop');
?>
<?php
endif; ?>						
						

<?php get_template_part('pagination'); ?>

<?php get_footer(); ?>
