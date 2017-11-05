<?php
/*
  Template Name: Fixpostimages     
 */


$args = array('year' => 2011, 'numberposts' => 100, 'offset' => 317);
$posts = get_posts( $args );
$i = 1;
foreach( $posts as $post ) {
    
    echo '<h1>'.$i.'</h1>';
    the_title('<h3>', '</h3>');
    $content = $post->post_content;
    $postid = get_the_ID();
   // find those ugly linked images 
    $re = '% # Match IMG wrapped in A element.
(<a\b[^>]+?href=")([^"]*)("[^>]*><img\b[^>]+?src=")([^"]*)("[^>]*></a>)
%ix';

   $content = preg_replace_callback($re,
           create_function(
                '$matches',   
                '$stripped = str_replace("%", "", $matches[2]); return $matches[1].$stripped.$matches[3].$stripped.$matches[5];'
                   ),
           $content);

   echo $content;
   echo '<hr />';


   $new_post = array();
   $new_post['ID'] = $postid;
   $new_post['post_content'] = $content;
  

   // Update the post into the database
 wp_update_post( $new_post );
 $i++;
}
?>
