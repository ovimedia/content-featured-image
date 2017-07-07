<?php
/*
Plugin Name: Content feature image
Description: Set the first content image to feature post image.
Author: Ovi García - ovimedia.es
Author URI: http://www.ovimedia.es/
Text Domain: content-featured-image
Version: 0.3
Plugin URI: http://www.ovimedia.es/
*/

if ( ! class_exists( 'content_featured_image' ) ) 
{
	class content_featured_image 
    {        
        function __construct() 
        {   
            add_action( 'admin_menu', array( $this, 'cfi_admin_menu' ));
        }

        public function cfi_admin_menu() 
        {	
            $menu = add_menu_page( 'Content featured image', 'Content featured image', 'read',  
                                  'content-featured-image', array( $this,'cfi_options'), 'dashicons-format-gallery', 70);
        }    

        public function cfi_options()
        {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            
            ?>

            <form action="<?php echo get_admin_url()."admin.php?page=content-featured-image"; ?>" method="post" >

                <h4>Click the button to set the featured image from the first content image in the post.</h4>

                <p>Replace featured image with content image:
                <select id="force_replace" name="force_replace">
                    <option value="0">No </option>
                    <option value="1" >Yes</option>
                </select></p>

                <p>Select post type to asign featured images: <select id="post_type" name="post_type">

                    <?php
                        global $wpdb;

                        $results = $wpdb->get_results( 'SELECT DISTINCT post_type FROM '.$wpdb->prefix.'posts 
                        WHERE post_status like "publish" and post_type <> "nav_menu_item" 
                        and post_type <> "wpcf7_contact_form" order by 1 asc'  );
        
                        $post_types = array();

                        foreach ( $results as $row )
                        {
                            $post_types[] = $row->post_type;
                            
                            echo '<option ';

                            if( in_array($row->post_type, $types[0]) )
                                echo ' selected="selected" ';

                            echo ' value="'.$row->post_type.'">'.ucfirst ($row->post_type).'</option>';
                        } 

                    ?>
                
                </select></p>
               
                <input type="submit"  class="button button-primary"  value="Set featured images" />

            </form>

            <?php

            if(isset($_REQUEST["post_type"] )) 
            {
                $types = array("jpg", "png", "gif");

                $dir = "../wp-content/uploads/".date("Y")."/".date("m")."/";

                if(!file_exists ($dir))
                    mkdir($dir);

                $args = array(
                'numberposts' =>   -1,
                'post_type' => $_REQUEST['post_type'],
                ); 

                $posts = get_posts($args); 

                foreach($posts as $post)
                {
                    $post->ID;

                    $pos1 = strpos($post->post_content, 'src="') + 5;

                    $pos2 = strpos($post->post_content, '"', $pos1);

                    $url = substr($post->post_content, $pos1, $pos2 - $pos1);

                    if(get_the_post_thumbnail($post->ID) == "" || $_REQUEST['force_replace'] == "1")
                    {
                        if(strpos($url, get_home_url()) > 0)
                        {       
                            set_post_thumbnail($post->ID, $this->cfi_get_attachment_id_from_url($url));
                        }
                        else 
                        {
                            if(in_array(substr($url, -3), $types) )
                            {
                                $filename = $dir.basename($url);
                                file_put_contents($filename, fopen($url, "r"));   

                                $filetype = wp_check_filetype( basename( $filename ), null );

                                $wp_upload_dir = wp_upload_dir();

                                $attachment = array(
                                    'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
                                    'post_mime_type' => $filetype['type'],
                                    'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );

                                $attach_id = wp_insert_attachment( $attachment, $filename, $post->ID );

                                $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
                                
                                wp_update_attachment_metadata( $attach_id, $attach_data );
                                    
                                set_post_thumbnail($post->ID, $attach_id);
                            }
                        }
                    }
                }

                echo "<p>Featured pictures successfully assigned.</p>";
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
