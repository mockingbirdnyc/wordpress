<?php get_header(); ?>
<?php
  $curauth = (isset($_GET['author_name'])) ? get_user_by('slug', $author_name) : get_userdata(intval($author));
  get_userdata(intval($author));
  function validate_gravatar($email) {
    // Craft a potential url and test its headers
    $hash = md5($email);
    $uri = 'http://www.gravatar.com/avatar/' . $hash . '?d=404';
    $headers = @get_headers($uri);
    if (!preg_match("|200|", $headers[0])) {
      $has_valid_avatar = FALSE;
    } else {
      $has_valid_avatar = TRUE;
    }
    return $has_valid_avatar;
  }
  $user_email = $curauth->user_email;
  $has_avatar = validate_gravatar($user_email);
?>

  <div class="content-title">About <?php echo $curauth->display_name; ?></div>

  <div id="curauth" class="post-content">

  <!-- The logic to determine whether the author has an avatar has broken...
  <?php if ($has_avatar) : ?>
    <div id="avatar"><?php echo get_avatar($curauth->ID, 70); ?></div>
  <?php endif; ?>
  -->

  <?php if ($curauth->user_description) : ?>
    <p><?php echo $curauth->user_description; ?></p>
  <?php endif; ?>
  <?php if ($curauth->user_url) : ?>
    <p>
      <a href="<?php echo $curauth->user_url; ?>">
        <?php echo $curauth->user_url; ?>
      </a>
    </p>
  <?php endif; ?>
  <?php if (!$curauth->user_description && !$curauth->user_url) : ?>
    <p>A riddle wrapped in a mystery inside an enigma...</p>
  <?php endif; ?>

    <p>
      <a href="#contact-author" class="contact-author-link">
        Contact <?php echo $curauth->display_name; ?>
      </a>
    </p>
  </div>

<div class="content-title">
  <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
  <?php _e('Author Archive'); ?>
  <a href="javascript: void(0);" id="mode"<?php if ($_COOKIE['mode'] == 'grid') echo ' class="flip"'; ?>></a> </div>
<ul>
<!-- The Loop -->
<?php get_template_part('loop'); ?>
<?php get_template_part('pagination'); ?>

<div class="content-title" id="contact-author">Contact</div>
<div id="curauth" class="post-content">
  <?php echo do_shortcode( '[contact-form 1 "Contact Author"]' ); ?>
</div>

<?php get_footer(); ?>
