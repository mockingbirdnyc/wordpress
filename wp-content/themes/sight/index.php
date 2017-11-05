<?php get_header(); ?>

<div class="content-title">Latest entries</div>

<?php query_posts(array(
        'paged' => $paged,
    )
); ?>

<?php get_template_part('loop'); ?>

<?php wp_reset_query(); ?>

<?php get_template_part('pagination'); ?>

<?php get_footer(); ?>
