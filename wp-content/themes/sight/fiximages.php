<?php

/*
  Template Name: Fiximages
 */
?>
<?php get_header(); ?>
<?php

$posts = get_posts(array(
            'post_type' => 'attachment',
            'numberposts' => -1,
        ));

// Recursive String Replace - recursive_array_replace(mixed, mixed, array);
function recursive_array_replace($find, $replace, $array){
if (!is_array($array)) {
return str_replace($find, $replace, $array);
}
$newArray = array();
foreach ($array as $key => $value) {
$newArray[$key] = recursive_array_replace($find, $replace, $value);
}
return $newArray;
}

foreach ($posts as $post) {
    // retrieve data, unserialized automatically
    $meta = get_post_meta($post->ID, '_wp_attachment_metadata', true);
    if (strpos($meta[file], "%")) {
        
        $meta = recursive_array_replace('%', '', $meta);
        echo "<p>renamed to ".$meta[file]."</p>";
        // write it back
        update_post_meta($post->ID, '_wp_attachment_metadata', $meta);
    }

    
}
?>
<?php get_footer(); ?>