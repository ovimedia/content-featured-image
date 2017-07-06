<?php
/*
Plugin Name: Content feature image
Description: Set the first content image to feature post image.
Author: Ovi García - ovimedia.es
Author URI: http://www.ovimedia.es/
Text Domain: content-featured-image
Version: 0.1
Plugin URI: http://www.ovimedia.es/
*/

if ( ! class_exists( 'content_featured_image' ) ) 
{
	class content_featured_image 
    {        
        function __construct() 
        {   
            add_action( 'admin_menu', array( $this, 'cfi_admin_menu' ));
            add_action( 'init', array( $this, 'cfi_featured_images') );
        }

        public function cfi_admin_menu() 
        {	
            $menu = add_menu_page( 'Content featured image', 'Content featured image', 'read',  
                                  'content-featured-image', array( $this,'cfi_options'), 'dashicons-format-gallery', 70);
        }    

        public function cfi_featured_images() 
        {
			$page_viewed = basename($_SERVER['REQUEST_URI']);

            if( $page_viewed == "cfi_featured_images" ) 
            {
                $this->cfi_set_featured_images();
                wp_redirect("./wp-admin/admin.php?page=content-featured-image&result=ok");
                exit();
            }
		}

        public function cfi_options()
        {
            ?>
            <form action="/cfi_featured_images" method="post" >

                <h4>Click the button to set the featured image from the first content image in the post.</h4>
                
                <input type="submit"  class="button button-primary"  value="Set featured images" />

            </form>
            <?php

            if($_REQUEST["result"] == "ok") echo "<p>Featured pictures successfully assigned.</p>";
        }

        public function cfi_set_featured_images()
        {
            $args = array(
                'numberposts' =>   -1,
                'post_type' => "post",
            ); 

            $posts = get_posts($args); 

            foreach($posts as $post)
            {
                $post->ID;

                $pos1 = strpos($post->post_content, 'src="') + 5;

                $pos2 = strpos($post->post_content, '"', $pos1);

                $url = substr($post->post_content, $pos1, $pos2 - $pos1);

                if(get_the_post_thumbnail($post->ID) == "")
                    set_post_thumbnail($post->ID, $this->cfi_get_attachment_id_from_url($url));
            }
        }

        public function cfi_get_attachment_id_from_url( $attachment_url = '' ) 
        {
            global $wpdb;

            $attachment_id = false;
        
            if ( '' == $attachment_url ) return;
        
            $upload_dir_paths = wp_upload_dir();
        
            if ( false !== strpos( $attachment_url, $upload_dir_paths['baseurl'] ) ) 
            {
                $attachment_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url );
        
                $attachment_url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $attachment_url );
        
                $attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, 
                $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id 
                AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' 
                AND wposts.post_type = 'attachment'", $attachment_url ) );
            }
        
            return $attachment_id;
        }

    }
}

$GLOBALS['content_featured_image'] = new content_featured_image();   
    
?>
