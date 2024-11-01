<?php
/**
 * @package WP Links Page
 * @version 4.9.6  */
/*
Plugin Name: WP Links Page
Plugin URI:  http://www.wplinkspage.com/
Description: This plugin provides an easy way to add links to your site.
Version: 4.9.6
Author: Robert Macchi
*/

include_once(ABSPATH.'wp-admin/includes/plugin.php');

function wplpf_is_requirements_met() {

    // Check if WP Links Page Pro is active
    if ( is_plugin_active('wp-links-page-pro/wp-links-page-pro.php') && get_option('wplp_free_passes_req') != 'true' ) {

        return false;
    }

    return true;
}

function wplpf_disable_plugin() {

    if ( current_user_can('activate_plugins') && is_plugin_active( plugin_basename( __FILE__ )) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}

function wplpf_show_notice() {
    echo '<div class="notice notice-error"><p><strong>WP Links Page</strong> shouldn\'t be activated while WP Links Page Pro is active. Use WP Links Page Pro instead.</p></div>';
}

if ( !wplpf_is_requirements_met() ) {
include_once(ABSPATH.'wp-admin/includes/plugin.php');
	add_action( 'admin_init', 'wplpf_disable_plugin' );
	add_action( 'admin_notices', 'wplpf_show_notice' );
} else {

$upload_dir = wp_upload_dir();
$wplf_upload = $upload_dir['basedir'].'/wp-links-page/';
if( ! file_exists( $wplf_upload ) )
    wp_mkdir_p( $wplf_upload );

if (!defined('WPLP_UPLOAD_DIR')) {
    define('WPLP_UPLOAD_DIR', $wplf_upload);
}

if (!defined('WPLP_UPLOAD_URL')) {
    define('WPLP_UPLOAD_URL', $upload_dir['baseurl'].'/'.'wp-links-page/');
}

/** Require dependencies */
require_once( ABSPATH . 'wp-admin/includes/image.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/media.php' );
require_once( ABSPATH . 'wp-includes/media-template.php' );

add_filter( 'cron_schedules', 'wp_links_page_free_add_intervals');

	add_option( 'wplp_screenshot_size', 'large', '', 'yes' );
	add_option( 'wplp_screenshot_refresh', 'weekly', '', 'yes' );
	add_option( 'wplp_apikey', '', '', 'yes' );



	add_action( 'wpmu_new_blog', 'wplf_new_blog', 10, 6);
	add_action( 'wp_links_page_free_event', 'wp_links_page_free_event_hook');

	register_activation_hook(__FILE__, 'wp_links_page_free_setup_schedule');
	register_deactivation_hook( __FILE__, 'wp_links_page_free_deactivation');



/** Admin Init **/
if ( is_admin() ) {
	add_action( 'admin_init', 'wp_links_page_free_settings');
	add_action( 'add_meta_boxes_wplp_link', 'wplf_links_metaboxes' );
	add_action( 'admin_menu', 'wplf_menu');
	add_action( 'admin_enqueue_scripts', 'wplf_admin_enqueue_scripts' );
}



	function wp_links_page_free_setup_schedule() {
		$screenshot_refresh = esc_attr( get_option('wplp_screenshot_refresh') );
		wp_clear_scheduled_hook( 'wp_links_page_event' );
		wp_clear_scheduled_hook( 'wp_links_page_free_event' );
		wp_schedule_event( time(), $screenshot_refresh, 'wp_links_page_free_event');
	}

	function wplf_enqueue_shortcode_scripts($posts) {
		wp_register_script(  'wplf-display-js', plugins_url( 'wp-links-page/js/wp-links-display.js', 'wp-links-page' ), array( 'jquery' ), false, true);
		wp_localize_script( 'wplf-display-js', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce('ajax-nonce' ) ));
		wp_register_style( 'wplf-display-style',  plugins_url( 'wp-links-page/css/wp-links-display.css', 'wp-links-page' ), array(), false, 'all' );
	}

	add_action( 'wp_enqueue_scripts', 'wplf_enqueue_shortcode_scripts' );

	function wplf_admin_enqueue_scripts( $hook ) {
		global $typenow;
		if (($hook == 'post-new.php' || $hook == 'edit.php' || $hook == 'post.php')  && $typenow == 'wplp_link') {
		wp_enqueue_script('jquery-ui-progressbar');
		wp_enqueue_script( 'wplf-js', plugins_url( 'wp-links-page/js/wp-links-page.js', 'wp-links-page' ), array( 'jquery', 'jquery-ui-progressbar' ), null, true );
		wp_enqueue_script( 'wplf-qe-js', plugins_url( 'wp-links-page/js/wp-links-page-quick-edit.js', 'wp-links-page' ), array( 'jquery', 'inline-edit-post' ), '', true );
		wp_localize_script( 'wplf-js', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce('ajax-nonce' ) ) );
		$translation_array = array( 'pluginUrl' => plugins_url( 'wp-links-page' ) );
		//after wp_enqueue_script
		wp_localize_script( 'wplf-js', 'wplf', $translation_array );
		wp_enqueue_style('wplf-admin-ui-css', plugins_url( 'wp-links-page/css/jquery-ui.css', 'wp-links-page' ),false, '', false);
		wp_enqueue_style( 'wplf-style',  plugins_url( 'wp-links-page/css/wp-links-page.css', 'wp-links-page' ), null, null, false );
		} else if ($hook == 'wplp_link_page_wplf_subpage-menu') {
			wp_enqueue_script('jquery-ui-progressbar');
			wp_enqueue_script( 'wplf-js', plugins_url( 'wp-links-page/js/wp-links-page.js', 'wp-links-page' ), array( 'jquery', 'jquery-ui-progressbar' ), null, true );
			wp_localize_script( 'wplf-js', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce('ajax-nonce' ) ) );
			wp_enqueue_media();
			wp_enqueue_style('wplf-admin-ui-css', plugins_url( 'wp-links-page/css/jquery-ui.css', 'wp-links-page' ),false, '', false);
			wp_enqueue_style( 'wplf-style',  plugins_url( 'wp-links-page/css/wp-links-page.css', 'wp-links-page' ), null, null, false );
		} else if ($hook == 'wplp_link_page_wplf_subpage3-menu') {
			wp_enqueue_script( 'wplf-shortcode-js', plugins_url( 'wp-links-page/js/wp-links-shortcode.js', 'wp-links-page' ), array( 'jquery', 'jquery-ui-tabs' ), null, true );
			wp_enqueue_style( 'wplf-style',  plugins_url( 'wp-links-page/css/wp-links-page.css', 'wp-links-page' ), null, null, false );
			wp_enqueue_style( 'ti-style',  plugins_url( 'wp-links-page/css/themify-icons.css', 'wp-links-page' ), null, null, false );
		} else if ($hook == 'wplp_link_page_wplf_subpage2-menu') {
			wp_enqueue_style( 'wplf-style',  plugins_url( 'wp-links-page/css/wp-links-page.css', 'wp-links-page' ), null, null, false );
		}

	}

function wp_links_page_free_add_intervals($schedules) {
		$schedules['threedays'] = array(
			'interval' => 259200,
			'display' => __('Every Three Days')
		);
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display' => __('Weekly')
		);
		$schedules['biweekly'] = array(
			'interval' => 1209600,
			'display' => __('Every Two Weeks')
		);
		$schedules['monthly'] = array(
			'interval' => 2635200,
			'display' => __('Monthly')
		);
		return $schedules;
	}

	function wp_links_page_free_deactivation() {
		wp_clear_scheduled_hook( 'wp_links_page_free_event' );
	}

	function wp_links_page_free_event_hook() {
		global $wpdb;
		$custom_post_type = 'wplp_link';
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s and post_status = 'publish'", $custom_post_type ), ARRAY_A );
		$total = '';

		foreach ($results as $index => $post) {
			$arg = array($post['ID'],false);
			wp_schedule_single_event( time(), 'wp_ajax_wplf_ajax_update_screenshots', $arg );
		}
	}


	function wplf_ajax_update_screenshots($id = '', $override = false) {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {
         die ( 'Nonce verification failed.');
     }
    if ( !current_user_can( 'manage_options' ) ) {
      die ( 'You do not have sufficient permission permission to do this.');
    }
		if (isset($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
			$id = sanitize_text_field($id);
		} elseif (empty($id)) {
		 die(json_encode(array('message' => 'ERROR', 'code' => 1336)));
		}

		$post = get_post($id);
		$mk = wplf_filter_metadata( get_post_meta( $id ) );

		$ss_size = get_option('wplp_screenshot_size');

		if (!empty($mk['wplp_screenshot_url'])) {
			$url = $mk['wplp_screenshot_url'];
		} else { $url = $post->post_title; }

		if (!empty($mk['wplp_display'])) {
			$display = $mk['wplp_display'];
		} else { $display = $post->post_title; }

		if (isset($url)) {
			if (!(substr($url, 0, 4) == 'http')) {
				$url = 'https://' . $url;
			}
		}else {die();}


		if ($mk['wplp_no_update'] != 'no' && $mk['wplp_media_image'] != 'true') {

			if ($ss_size == 'large') {

        $wplp_featured_image = "https://s0.wp.com/mshots/v1/".$url."?w=1280";


					// Add Featured Image to Post
					$image_url        = $wplp_featured_image; // Define the image URL here
					$image_name       = preg_replace("/[^a-zA-Z0-9]+/", "", $display);
					wplf_large_screenshot($image_url, $image_name, $id);

				} elseif ($ss_size == 'small') {

					$image_name       = preg_replace("/[^a-zA-Z0-9]+/", "", $display);
					wplf_small_screenshot_url($image_name, $url, $id);
				}
			}
	}

	add_action( 'wp_ajax_wplf_ajax_update_screenshots', 'wplf_ajax_update_screenshots');


function wplf_menu() {

		$wplf_subpage3 = add_submenu_page(
			'edit.php?post_type=wplp_link',
			'WP Links Page | Shortcode',
			'Shortcode',
			'manage_options',
			'wplf_subpage3-menu',
			'wplf_shortcode_page');
		$wplf_subpage = add_submenu_page(
			'edit.php?post_type=wplp_link',
			'WP Links Page | Settings',
			'Settings',
			'manage_options',
			'wplf_subpage-menu',
			'wplf_subpage_options');
		$wplf_subpage2 = add_submenu_page(
			'edit.php?post_type=wplp_link',
			'WP Links Page | Help',
			'Help',
			'manage_options',
			'wplf_subpage2-menu',
			'wplf_help_page');

	}


function my_custom_post_wplf_link() {

  $labels = array(
    'name'               => _x( 'Links', 'post type general name' ),
    'singular_name'      => _x( 'Link', 'post type singular name' ),
    'add_new'            => _x( 'Add New', 'Link' ),
    'add_new_item'       => __( 'Add New Link' ),
    'edit_item'          => __( 'Edit Link' ),
    'new_item'           => __( 'New Link' ),
    'all_items'          => __( 'All Links' ),
    'view_item'          => __( 'View Link' ),
    'search_items'       => __( 'Search Links' ),
    'not_found'          => __( 'No Links found' ),
    'not_found_in_trash' => __( 'No Links found in the Trash' ),
    'parent_item_colon'  => '',
    'menu_name'          => 'WP Links Page'
  );
  $args = array(
    'labels'        => $labels,
    'description'   => 'Holds our links and link specific data',
    'public'        => false,
    'menu_position' => 5,
    'supports'      => array( 'title', 'editor', 'thumbnail' ),
    'has_archive'   => true,
	'show_in_menu'	=> true,
	'show_ui'		=> true,
	'menu_icon'     => 'dashicons-admin-links',
	'capabilities' => array(
				'edit_post'          => 'manage_options',
				'read_post'          => 'manage_options',
				'delete_post'        => 'manage_options',
				'edit_posts'         => 'manage_options',
				'edit_others_posts'  => 'manage_options',
				'delete_posts'       => 'manage_options',
				'publish_posts'      => 'manage_options',
				'read_private_posts' => 'manage_options'
	)
   /* 'taxonomies' => array( 'category', 'post_tag' )*/


  );
  register_post_type( 'wplp_link', $args );
}
add_action( 'init', 'my_custom_post_wplf_link' );



/**
 * Query Filter for Custom Post Types
 */
 function wplf_query_filter($query) {
	if ( ! is_admin() && $query->is_main_query() && is_post_type_archive( 'wplp_link' ) ) {
	   $query->set( 'orderby', 'ID' );
       $query->set( 'order', 'DESC' );
		return;
	}
}

add_action('pre_get_posts','wplf_query_filter');

/**
 * Change edit.php page  define( 'WP_DEBUG_DISPLAY', false );
*/





add_action( 'load-edit.php', function() {
  add_filter( 'views_edit-wplp_link', 'wplf_link_edit' );
});

function wplf_link_edit($views) {
	global $wpdb;
	$custom_post_type = 'wplp_link';
    $results = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s and post_status = 'publish'", $custom_post_type ), ARRAY_A );
	$total = '';

    foreach( $results as $index => $post ) {
		if ($total == '') {
			$total = $post['ID'];
		} else {
        	$total .= ','.$post['ID'];
		}
    }


 echo '

  <button id="update-screenshots" class="button button-primary button-large" style="float:left; margin-right: 20px;" data-total="'.$total.'">Update Screenshots</button>
	<div id="progressbar">
              <div class="progress-label"></div>
        </div><div class="clearfix" style="clear:both"></div>

 ';
 return $views;
}

add_action( 'admin_head-edit.php', 'wplf_quick_edit_remove' );

function wplf_quick_edit_remove()
{

    global $current_screen;
    if( 'edit-wplp_link' != $current_screen->id )
        return;
    ?>
    <script type="text/javascript">
        jQuery(document).ready( function($) {
			$('span.title:contains("Title")').each(function (i) {
                $(this).html('Link Url');
				$(this).parent().parent().append('<label><span class="title">Link Display</span><span class="input-text-wrap"><input type="text" name="wplp_display" value="" /></span></label><label><span class="title">Description</span><textarea name="wplf_description"></textarea></label><br class="clear">');
            });
			$('span:contains("Slug")').each(function (i) {
                $(this).parent().remove();
            });
            $('span:contains("Password")').each(function (i) {
                $(this).parent().parent().remove();
            });
            $('span:contains("Date")').each(function (i) {
                $(this).parent().remove();
            });
            $('.inline-edit-date').each(function (i) {
                $(this).remove();
            });
			$('#wplf-custom.inline-edit-col-left').each(function (i) {
				$(this).css('font-weight:bold;');
			});
        });
    </script>
    <?php
}
/**
 * Edit Custom Post Type List
 */

 add_filter( 'manage_wplp_link_posts_columns', 'set_custom_edit_wplf_link_columns' );
 add_action( 'manage_wplp_link_posts_custom_column' , 'wplf_custom_columns', 10, 2 );

function set_custom_edit_wplf_link_columns($columns) {
    unset( $columns['author'] );
    unset( $columns['date'] );
    $columns['screenshot'] = 'Screenshot';
    $columns['description'] = 'Description';
	$columns['title'] = 'Link Display';
    $columns['id'] = 'ID';

	$a = $columns;
	$b = array('cb', 'screenshot', 'title', 'description', 'id'); // rule indicating new key order
	$c = array();
	foreach($b as $index) {
		$c[$index] = $a[$index];
	}
	$columns = $c;

    return $columns;
}

function wplf_custom_columns( $column, $post_id ) {
	switch ( $column ) {
		case 'id':
			echo $post_id;
			break;
		case 'screenshot':
			$display = get_post_meta( $post_id, 'wplp_display', true );
			$image = get_the_post_thumbnail( $post_id, 'thumbnail' );
			echo $image.'<p id="wplp_display_'.$post_id.'" class="hidden">'.$display.'</p>';
			break;


	case 'description':
			$content_post = get_post($post_id);
			$content = $content_post->post_content;
			$content = apply_filters('the_content', $content);
			$content = str_replace(']]>', ']]&gt;', $content);
			echo '<div id="wplf_description_'.$post_id.'">'.$content.'</div>';
			break;

	}
}


function wplf_link_display_title( $title, $id = null ) {
	if (is_admin()) {
		if (get_post_type($id) == 'wplp_link') {
			$display = get_post_meta( $id, 'wplp_display', true );
			if ($display == '') {
				$display = $title;
			}
			return $display;
		} else { return $title; }
	} else { return $title; }
}
add_filter( 'the_title', 'wplf_link_display_title', 10, 2 );


/**
 *   Adds a metabox
 */
function wplf_links_metaboxes() {

	add_meta_box(
		'wplp_screenshot',
		'Screenshot',
		'wplf_post_thumbnail_meta_box',
		'wplp_link',
		'advanced',
		'default' );

	add_meta_box(
		'wplp_display',
		'Link Display',
		'wplf_display_func',
		'wplp_link',
		'advanced',
		'default'
	);

}

/* Move Screenshot Metabox to before title */

add_action('edit_form_after_title', function() {
    global $post, $wp_meta_boxes, $typenow;
	if ($typenow == 'wplp_link') {
		do_meta_boxes(get_current_screen(), 'advanced', $post);
		unset($wp_meta_boxes[get_post_type($post)]['advanced']);
	}
});


function wplf_display_func() {

	global $post;
	// Nonce field to validate form request came from current site
	wp_nonce_field( basename( __FILE__ ), 'wplf_fields' );

	// Get the display data if it's already been entered
	$display = get_post_meta( $post->ID, 'wplp_display', true );
	if ($display == "Auto Draft") { $display = ''; }

    echo '<label for="display">Link Display</label>
    <p class="description">This field defaults to the link domain.</p>
    <input id="wplp_display" name="wplp_display" maxlength="255" type="text" value="'.$display.'">';

}

function wplf_post_thumbnail_meta_box( $post ) {
	$mk = wplf_filter_metadata( get_post_meta( $post->ID ) );
	$thumb_id = get_post_thumbnail_id( $post->ID );
	$thumb = wp_get_attachment_url($thumb_id);
	if ($thumb != '' ) {
		$display = '';
	} else $display = 'display:none;';

	$loading = plugin_dir_url( __FILE__ ) . 'images/loading.gif';
	$screenshot_size = get_option( 'wplp_screenshot_size');
	if (isset($mk['wplp_media_image'])) { $media = $mk['wplp_media_image'];} else {$media = '';}
	if (isset($mk['wplp_screenshot_url'])) { $screenshot_url = $mk['wplp_screenshot_url'];} else {$screenshot_url = '';}
	if (isset($mk['wplp_no_update'])) { $no_update = $mk['wplp_no_update'];} else {$no_update = '';}
	if (empty($media)) $media = 'false';

		echo '
		<div id="titlediv">
<div id="titlewrap">
	<input type="text" name="post_title" size="30" value="'.$post->post_title.'" placeholder="Link Url" class="ss" id="title" spellcheck="true" autocomplete="off">
</div>
	</div>
	<p class="description">Enter the Link Url in this field. The screenshot will generate automatically as soon as you are finished. If the screenshot is not generating properly try using the full url including the "http://" or "https://".</p>
		<img class="wplp_featured" src="'.$thumb.'" style="'.$display.' width:300px; margin: 10px 0;" />
		<div class="wplp_loading" style="width: 300px; display:none;text-align: center; border: 1px solid #DDD; margin: 10px 0;">
		<img class="wplp_loading" src="'.$loading.'" style="display:none; width: 100px;" />
		<p class="wplp_loading" style="display: none;">Generating Screenshot...</p>
		</div>
		<br>
		<label for"wplp_screenshot_url"><b>Screenshot URL: &nbsp;&nbsp;<b></label><input id="wplp_screenshot_url" type="text" name="wplp_screenshot_url" value="'.$screenshot_url.'" style="width: 80%;"/>
		<p class="description">This field is useful for affiliate links. Your affiliate link can go in the "Link URL" field above, and the direct URL can go in the "Screenshot URL" field to retrieve the expected screenshot. Click "Generate New Screenshot" after entering the url below to retrieve the new screenshot.</p>
		<input id="wplp_media_image" type="hidden" name="wplp_media_image" value="'.$media.'" />
		<input id="wplp_featured_image" type="hidden" name="wplp_featured_image" value="'.$thumb_id.'" />
		<input id="wplp_screenshot_size" type="hidden" name="wplp_screenshot_size" value="'.$screenshot_size.'" />
		<br>
		<p class="hide-if-no-js">
		<a class="set-featured-thumbnail setfeatured button" href="#" title="Choose Image">Choose Image</a>
		&nbsp;<input id="apikey" type="hidden" class="apikey" value="'.esc_attr( get_option('wplp_apikey')).'" /><a class="set-featured-screenshot generate button button-primary" href="#" title="Generate New Screenshot">Generate New Screenshot</a><br>
		<br><label for="wplp_no_update"><input id="wplp_no_update" type="checkbox" name="wplp_no_update" value="no"';
		if ($no_update == 'no') {
			echo 'checked="checked"';
		} else echo 'data="not checked"';
		echo ' />Don\'t update this screenshot. Keep the current image.</label>';
}

function wplf_file_get_contents_curl($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}


	function wplf_update_from_previous() {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {
         die ( 'Nonce verification failed.');
     }
		if (isset($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
			$id = sanitize_text_field($id);
		} else die(json_encode(array('message' => 'ERROR', 'code' => 'no id')));
		$ss_size = get_option('wplp_screenshot_size');
		global $wpdb;
		$table = $wpdb->prefix.'wp_links_page_free_table';
		$links = $wpdb->get_results("SELECT * FROM $table WHERE id = $id ORDER BY weight");
			foreach ($links as $link) {

				if (!empty($link->display)) {
					$display = $link->display;
				} else { $display = $link->url; }

				$metadata = json_decode($metadata);
				if ($metadata->title == 'WPLPNotAllowed') {$metadata->title = '';}
				if ($link->no_update == 1) {
					$no_update = 'no';
				} else {
					$no_update = 'false';
				}
				$new_link = array(
				  'post_title'    => sanitize_text_field( $link->url ),
				  'post_content'  => wp_kses_post($link->description),
				  'post_status'   => 'publish',
				  'post_type'	  => 'wplp_link',
				  'meta_input' => array(
									'wplp_display' => sanitize_text_field($display),
									'wplp_no_update' => $no_update,
									'wplp_screenshot_url' => $link->ssurl,
									'wplp_media_image' => 'false',
									'wplp_media_fav' => 'false',
								),
				);
				$new = wp_insert_post( $new_link );

				if (!empty($link->ssurl)) {
					$url = $link->ssurl;
				} else { $url = $link->url; }

				if (isset($url)) {
					if (!(substr($url, 0, 4) == 'http')) {
						$url = 'https://' . $url;
					}
				}else {die();}


					if ($ss_size == 'large') {

            $wplp_featured_image = "https://s0.wp.com/mshots/v1/".$url."?w=1280";


							// Add Featured Image to Post
							$image_url        = $wplp_featured_image; // Define the image URL here
							$image_name       = preg_replace("/[^a-zA-Z0-9]+/", "", $display);
							wplf_large_screenshot($image_url, $image_name, $new);

						} elseif ($ss_size == 'small') {

								$image_name       = preg_replace("/[^a-zA-Z0-9]+/", "", $display);
								wplf_small_screenshot_url($image_name, $url, $new);
						}
					}
	}

	add_action( 'wp_ajax_wplf_update_from_previous', 'wplf_update_from_previous');

/**
 * Save the metabox data
 */

 add_filter( 'wp_insert_post_data' , 'wplf_filter_post_data' , '99', 2 );

function wplf_filter_post_data( $data , $postarr ) {
	if (isset($postarr['action'])) {$action = $postarr['action'];} else {$action = '';}
    // Change post content on quick edit
	if ($postarr['post_type'] == 'wplp_link' && $action == 'inline-save') {
		if (isset($postarr['wplf_description'])) {
			$data['post_content'] = wp_kses_post($postarr['wplf_description']);
			$postarr['post_content'] = wp_kses_post($postarr['wplf_description']);
			$postarr['content'] = wp_kses_post($postarr['wplf_description']);
		}
	}
    return $data;
}

function wplf_display_save( $post_id, $post ) {

    /*
     * In production code, $slug should be set only once in the plugin,
     * preferably as a class property, rather than in each function that needs it.
     */
	$post_type = get_post_type($post_id);
	$post_status = get_post_status($post_id);
    if ( "wplp_link" != $post_type || $post_status == 'auto-draft') return;
	if (isset($_POST['action'])) {
	if ( $_POST['action'] == 'wplf_update_from_previous' || $_POST['action'] == 'wplf_import_list') return;
	}
	$mk = wplf_filter_metadata( get_post_meta( $post_id ) );

	$ss_size = get_option('wplp_screenshot_size');

  error_log(print_r($_POST,true));

    // - Update the post's metadata.
	if ( isset( $_POST['wplp_display'] ) ) {
		update_post_meta( $post_id, 'wplp_display', sanitize_text_field( $_POST['wplp_display'] ) );
	} elseif (!isset($mk['wplp_display'])) {
		update_post_meta( $post_id, 'wplp_display', sanitize_text_field( $_POST['post_title'] ) );
		$_POST['wplp_display'] = $_POST['post_title'];
	}

	if ( isset( $_POST['wplp_screenshot_url'] ) ) {
		update_post_meta( $post_id, 'wplp_screenshot_url', sanitize_text_field( $_POST['wplp_screenshot_url'] ) );
	}

	if( isset( $_POST[ 'wplp_no_update' ] ) ) {
		update_post_meta( $post_id, 'wplp_no_update', 'no' );
		$no_update = true;
	} else {
		update_post_meta( $post_id, 'wplp_no_update', 'false' );
		$no_update = false;
	}

	if ( isset( $_POST['wplp_featured_image']) && $_POST['wplp_featured_image'] != '' && !is_numeric($_POST['wplp_featured_image']) ) {
		if ($no_update == true || $_POST['wplp_media_image'] == 'true') {
			update_post_meta( $post_id, 'wplp_no_update', 'no' );

		} else {
			if ($ss_size == 'large' ) {
				if (!empty($_POST['wplp_featured_image'])) {
					$image_url        = $_POST['wplp_featured_image']; // Define the image URL here
					$image_name       = preg_replace("/[^a-zA-Z0-9]+/", "", $_POST['wplp_display']);
					wplf_large_screenshot_quick($image_url, $image_name, $post_id);
				}
			} elseif ($ss_size == 'small') {

				if (!empty($_POST['wplp_featured_image'])) {

					// Add Featured Image to Post
					$image_name       = preg_replace("/[^a-zA-Z0-9]+/", "", $_POST['wplp_display']);
					wplf_small_screenshot($image_name, $_POST['wplp_featured_image'], $post_id);
				}

			}
		}

    } else if (isset($_POST['wplp_featured_image']) && is_numeric($_POST['wplp_featured_image']) ) {

			$post_thumbnail_id = get_post_thumbnail_id( $post_id );
		if ($mk['wplp_media_image'] != $_POST['wplp_media_image'] && $_POST['wplp_media_image'] == 'true') {
			$post_thumbnail_id = get_post_thumbnail_id( $post_id );
			if ($post_thumbnail_id != $_POST['wplp_featured_image'] && $mk['wplp_media_image'] == 'false' && !empty($post_thumbnail_id)) {
				wp_delete_attachment( $post_thumbnail_id, true );
			}
		}
		set_post_thumbnail( $post_id, $_POST['wplp_featured_image'] );
		update_post_meta( $post_id, 'wplp_media_image', sanitize_text_field( $_POST['wplp_media_image'] ) );
	} else {
		set_post_thumbnail( $post_id, '' );
	}

	if ( !isset( $mk['wplp_media_image']) && isset($_POST['wplp_media_image'] ) ) {
		update_post_meta( $post_id, 'wplp_media_image', sanitize_text_field( $_POST['wplp_media_image'] ) );
	}

	if ( !isset( $mk['wplp_media_fav'] )  && isset($_POST['wplp_media_fav']) ) {
		update_post_meta( $post_id, 'wplp_media_fav', sanitize_text_field( $_POST['wplp_media_fav'] ) );
	}

}
add_action( 'save_post', 'wplf_display_save', 10, 3 );

function wplf_delete_func( $postid ){
    $mk = wplf_filter_metadata( get_post_meta( $postid ) );

    global $post_type;
    if ( $post_type != 'wplp_link' ) return;

    $post_thumbnail_id = get_post_thumbnail_id( $postid );

	if (!empty($post_thumbnail_id) && $mk['wplp_media_image'] == 'false') {
		wp_delete_attachment( $post_thumbnail_id, true );
	}
}
add_action( 'before_delete_post', 'wplf_delete_func' );

add_filter('gettext', 'wplf_text_filter', 20, 3);
/*
 * Change the text in the admin for my custom post type
 *
**/
function wplf_text_filter( $translated_text, $untranslated_text, $domain ) {

  global $typenow;

  if( is_admin() && 'wplp_link' == $typenow )  {

    //make the changes to the text
    switch( $untranslated_text ) {

        case 'Enter title here':
          $translated_text = __( 'Enter Link Url','text_domain' );
        break;

     }
   }
   return $translated_text;
}

function wplf_array_push_assoc($array, $key, $value){
$array[$key] = $value;
return $array;
}

function wplf_filter_metadata($array){
$mk = array();
foreach($array as $k => $v){
if(is_array($v) && count($v) == 1){
$mk = wplf_array_push_assoc($mk, $k, $v[0]);
} else {
$mk = wplf_array_push_assoc($mk, $k, $v);
}
}
return $mk;
}

function wplf_update_button($post_ID) {
    return '<button id="'.$post_ID.'" class="update button button-primary button-large" style="display: none;">Update Screenshot</button>';
}


function wplf_help_page() {
	?>
    <h1>Documentation</h1>
	<div class="fusion-one-half fusion-layout-column fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><h3><strong>Installation</strong></h3>
	<h4>Uploading via WordPress Dashboard</h4>
	<ol>
	<li>Navigate to the &#8216;Add New&#8217; in the plugins dashboard</li>
	<li>Navigate to the &#8216;Upload&#8217; area</li>
	<li>Select wp-links-page.zip from your computer</li>
	<li>Click &#8216;Install Now&#8217;</li>
	<li>Activate the plugin in the Plugin dashboard</li>
	</ol>
	<h4>Using FTP</h4>
	<ol>
	<li>Download wp-links-page.zip</li>
	<li>Extract the wp-links-page.zip directory to your computer</li>
	<li>Upload the wp-links-page.zip directory to the <code>/wp-content/plugins/</code> directory</li>
	<li>Activate the plugin in the Plugin dashboard</li>
	</ol>
	</div></div><div class="fusion-one-half fusion-layout-column fusion-column-last fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><p><img class="alignnone size-full wp-image-126" src="<?php echo plugins_url( "images/Install-Plugin.jpg", __FILE__ ); ?>" alt="Install Plugin" /></p>
	</div></div><div class="fusion-clearfix"></div><div class="fusion-sep-clear"></div><div class="fusion-separator fusion-full-width-sep sep-single" style="border-color:#e0dede;border-top-width:1px;margin-left: auto;margin-right: auto;margin-top:px;margin-bottom:30px;"></div>
	<div class="fusion-one-half fusion-layout-column fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><h3><strong>Adding and Editing Links</strong></h3>
	<p >Visit the WP Links Page section of the dashboard to add and edit the links.</p>
	<p >Click the Add New Link button or menu item to get started.</p>
	<p >Adding a link is much like adding a Post.</p>
    <p >When adding a link, as soon as you finish entering the Link Url and then move out of that field, the screenshot will populate automatically for you. You can enter the description  as well.</p>
    <p>If you wish to pull the screenshot from one url but have the link go to a different address, as is the case with most affiliate links, enter the url you wish the screenshot to come from in the 'Screenshot Url' field and click 'Generate Screenshot'.</p>
    <p>Sometimes WP Links Page cannot retrieve a screenshot because a website is built with flash, has a slow loading time, or for other reasons. If you should need to use your own image instead of the automatic screenshot WP Links Page generates, simply click 'Choose Image' in the Screenshot box on the add/edit link screen and choose a new image from the media library. Should you wish to return to using a screenshot simply click 'Generate New Screenshot' which is next to 'Choose Image'.</p>
    <p >To edit your links simply click the edit link inside the "All Links" page as you would with regular posts. You will be brought to the same form you used when adding your link originally. Make any changes and then click Update to save them.</p>
   </div></div><div class="fusion-one-half fusion-layout-column fusion-column-last fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><p><img class="alignnone size-full wp-image-205" src="<?php echo plugins_url( "images/Add-New-Link.jpg", __FILE__ ); ?>" alt="add edit links" /></p>
	</div></div><div class="fusion-clearfix"></div><div class="fusion-sep-clear"></div><div class="fusion-separator fusion-full-width-sep sep-single" style="border-color:#e0dede;border-top-width:1px;margin-left: auto;margin-right: auto;margin-top:px;margin-bottom:30px;"></div>
    <div class="fusion-one-half fusion-layout-column fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><h3><strong>All Links</strong></h3>
    <p>On the 'All Links' page you can view all of the links you have on your site.</p>
    <p>The links are sorted by their ID and should appear with the newest links first going to the oldest links.</p>
    <p>You can change the sort with filters at the top of this page, and even search for a particlular link that you need to edit.</p>
	<p>With the quick edit you can change the link url, link display, description, order and status or each link easily.</p>
    <p >Clicking the ‘Update Screenshots’ button on the 'All Links' page can take several minutes depending on your connection and the amount of links you have. Please be patient while it retrieves new images. A progress bar will display to show you how much longer you may need to wait.</p>
    </div></div><div class="fusion-one-half fusion-layout-column fusion-column-last fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><p><img class="alignnone size-full wp-image-205" src="<?php echo plugins_url( "images/Links List.jpg", __FILE__ ); ?>" alt="add edit links" /></p>
	</div></div><div class="fusion-clearfix"></div><div class="fusion-sep-clear"></div><div class="fusion-separator fusion-full-width-sep sep-single" style="border-color:#e0dede;border-top-width:1px;margin-left: auto;margin-right: auto;margin-top:px;margin-bottom:30px;"></div>
	<div class="fusion-one-half fusion-layout-column fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><h3><strong>Shortcode Builder</strong></h3>
	<p >Visit the 'Shortcode' page in the WP Links Page section to create the right shortcode for your desired display.</p>
    <p>You do not need to select all options, only the options you desire. The shortcode will display using default options for all the choices you do not select.</p>
	<p>In the Display section you will choose which kind of display your links should have: Grid or List.</p>
    <p>The Display Settings section will not show up until you have chosen a display, since each display has its own set of options such as the number of columns for the grid.<p>
    <p>In the Link Ordering section you will choose how to sort your links.</p>
    <p>In the Image, Title, and Description sections you can decide which data to use, and how to display it. The images sizes are automatically pulled from your version of wordpress, and often your theme will create extra image sizes to use. The shortcode will automatically default to 'Medium', but you may prefer a different size. Again, you do not need to choose any options here, the display will use it's defaults.</p>
    <br />
    <p style="font-weight:bold">As you choose options on this page your shortcode will appear at the bottom of the page. This shortcode will not remain after you leave the page. Please copy and paste your shortcode before you exit the page to avoid having to build your display again.</p>
    <br />
    <p>These are the available options for the WP Links Page shortcode:</p>
    <ul>
    <li><b>'ids'</b> - You can enter any ID or comma separated list of IDs here to only display specific links. The ids are listed on the 'All Links' page in the last column of each link. The default for this field is blank. Ex: ids="1,2,3"</li>

	<li><b>'display'</b> - This is used for each display type. The options are 'grid' or 'list'. The default for this field is 'grid'. Ex: display="grid"</li>

	<li><b>'cols'</b> - This is the number of columns your grid should have. You can enter any number into this field, but 2, 3, 4, 5, and 6 are recommended. The default for this field is '3'. Ex: cols="3"</li>

	<li><b>'orderby'</b> - This is used to sort the links differently. This accepts most common WP_Query orderby arguments. This field defaults to 'ID'. Ex: orderby="ID"</li>

    <li><b>'order'</b> - This is whether to sort ascending 'ASC' or descending 'DESC'. The default is 'DESC'. Ex: order="DESC"</li>

	<li><b>'img_size'</b> - This is what size of image to use in your display. The options vary from site to site, but the default, 'medium', is one of the standard wordpress sizes. For a full list of your image sizes available visit the 'Shortcode' page. Ex: img_size="medium"</li>

	<li><b>'img_style'</b> - This field will add css to the images. You can place any valid CSS here and it will be applied to your image. This is blank by default. Ex: img_style="box-shadow: 0 5px 10px 0 rgba(0,0,0,0.2),0 5px 15px 0 rgba(0,0,0,0.19);"</li>

	<li><b>'title_style'</b> - This field will add css to the titles. You can place any valid CSS here and it will be applied to your title. This is blank by default. Ex: title_style="font-color: #ccc;"</li>

	<li><b>'desc'</b> - This lets you choose which description to use in your display. Your options are either 'content' which is the Description, or 'meta' which is the Meta Description. You can also use 'none' or simply leave this field blank to display no description. This defaults to 'none'. Ex: desc="content"</li>

	<li><b>'desc_style'</b> - This field will add css to the descriptions. You can place any valid CSS here and it will be applied to your description. This is blank by default. Ex: desc_style="font-style: italic;"</li>
	<li><b>'description_link'</b> - This field will allow the description to be Linked. This is blank by default. Ex: description_link="yes"</li>
    </ul>
	<br/>
		<p style="font-weight:bold">Copy and paste your shortcode in a post or page to display your links before you exit the page to avoid having to build your display again.</p>
	<br/>
	<p><img class="alignnone size-full wp-image-110" src="<?php echo plugins_url( "images/Page-Post-Gut.jpg", __FILE__ ); ?>" alt="page post" /></p>
	</div></div><div class="fusion-one-half fusion-layout-column fusion-column-last fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><p><img class="alignnone size-full wp-image-110" src="<?php echo plugins_url( "images/Shortcodes.jpg", __FILE__ ); ?>" alt="settings page" /></p>
	</div></div><div class="fusion-clearfix"></div><div class="fusion-sep-clear"></div><div class="fusion-separator fusion-full-width-sep sep-single" style="border-color:#e0dede;border-top-width:1px;margin-left: auto;margin-right: auto;margin-top:px;margin-bottom:30px;"></div>
	<div class="fusion-one-half fusion-layout-column fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><h3><strong>Settings</strong></h3>
	<p >On the 'Settings' page you have various options for your Links.</p>

    <p>The Screenshot size option allows you to choose which size of screenshot to retrieve. This is actually a choice between two different screenshot API’s to use. The 500px Width uses a Google API and has better quality screenshots. However, the images are smaller and it usually requires an API key to work properly. The 1200px Width uses a WordPress API that does not require an API key, but sometimes doesn’t fetch screenshots properly for every website.</p>
	<p>Input field for a Google PageSpeed Insights API Key. If you are having trouble updating we suggest using this feature. This API Key is only for use with the 500px width images.</p>
    <p>The screenshot refresh rate is how often to generate new screenshots. The options are: Never, Daily, Weekly, Every two Weeks, Monthly.</p>
	<p>NOTE: If you have 100's of links or limited server resources consider using a monthly refresh rate or not refreshing automatically. </p>
	</div></div><div class="fusion-one-half fusion-layout-column fusion-column-last fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><p><img class="alignnone wp-image-184 size-full" src="<?php echo plugins_url( "images/Settings-1200.jpg", __FILE__ ); ?>" alt="Shortcode Example" /></p><p><img class="alignnone wp-image-184 size-full" src="<?php echo plugins_url( "images/Settings-500.jpg", __FILE__ ); ?>" alt="Shortcode Example" /></p>

	</div></div><div class="fusion-clearfix"></div><div class="fusion-sep-clear"></div><div class="fusion-separator fusion-full-width-sep sep-single" style="border-color:#e0dede;border-top-width:1px;margin-left: auto;margin-right: auto;margin-top:px;margin-bottom:30px;"></div>

	<div class="fusion-one-half fusion-layout-column fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><h1><strong>WP Links Page Pro Features</strong></h1>
	<strong>Multiple Displays to Choose From</strong>
    <p>We have included several different displays including Grid, List, Compact List and Carousel. When combined with the ability to add title and description this makes a variety of displays to choose from.</p>
    <strong>Display Your Links by Categories and Tags</strong>
    <p>You can now display your links by category or tag which provides a helpful way to group related links together, and quickly show your viewers exactly what they are looking for.</p>
	<strong>Pagination</strong>
	<p>Pager – This selects the type of pager to use for your links if you have too many to show on one page. This option uses standard page numbers as in WordPress. </p>
	<p>View More - This will add a View More button at the end of your links which will load more links. The text in the view more button is also customizable.</p>
	<p>Infinite - This will automatically load more links as soon as the visitor scrolls to the bottom of the page. </p>
	<strong>Metadata</strong>
	<p>Besides the main function of retrieving and updating screenshots, we have included the ability to retrieve favicons and meta information from websites which adds to your display choices.  Favicon, meta title and meta description can be used instead of the default screenshot, title and description.</p>
	<strong>Admin Sort/Edit </strong>
	<p>You can change the sort with filters at the top of this page, and even search for a particlular link that you need to edit. With the quick edit you can change the link url, link display, description, categories, order, tags, and status or each link easily.</p>
	<strong>Import Links </strong>
	<p>The Import Links feature allows you to import links from any comma separated list of urls. Simply paste your list of links into the ‘Links’ textarea, add a category if you want, and then press ‘Import’ at the bottom of the page. A progress bar will display, and when finished import it will notify you of any errors or if all links were imported successfully</p>
	<br><br>
	<h1>For More Information and Demo</h1><h3><a href="http://www.wplinkspage.com/" target="_blank">  Please Visit us at wplinkspage.com</a></h3>
	</div></div><div class="fusion-one-half fusion-layout-column fusion-column-last fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><p><img class="alignnone wp-image-184 size-full" src="<?php echo plugins_url( "images/WP-Links-Page-Pro.jpg", __FILE__ ); ?>" alt="WP Links Page Pro" /></p>
	</div></div><div class="fusion-clearfix"></div><div class="fusion-sep-clear"></div><div class="fusion-separator fusion-full-width-sep sep-single" style="border-color:#e0dede;border-top-width:1px;margin-left: auto;margin-right: auto;margin-top:px;margin-bottom:30px;"></div>


	<a href="http://www.wplinkspage.com/" target="_blank">To Upgrade to WP Links Page Pro, or for further assistance please visit us at wplinkspage.com</a>

	<?php
}

function wplf_shortcode_page() {
	?>
		<h2>Shortcodes</h2>
		<p class="description">Here you can generate shortcode based on the options you choose.</p>

        <div id="wplf-sb">
  <div id="tabs-1" aria-labelledby="ui-id-1">
    <h3>Display</h3>
    <hr style="border: 1px solid; width: 50%;" align="left">
    <div class="radio-i">
  	<p>Which display would you like to use?</p>
    <label><input name="wplf-display" value="grid" type="radio"><i class="ti-size-xxl ti-layout-grid3-alt"></i><br><span>Grid</span></label>
  	<label><input name="wplf-display" value="list" type="radio"><i class="ti-size-xxl ti-layout-list-thumb-alt"></i><br><span>List</span></label>
    </div>
  </div>
  <br><br>
  <div id="tabs-2">
  <h3>Display Settings</h3>
  <hr style="border: 1px solid; width: 50%;" align="left">
  <p class="description">Choose a display above to see the settings available for that display.</p>
  <div class="grid radio-no-i">
  	<p>How many columns should your grid have?</p>
    <label><input type="radio" name="wplf-columns" value="2"><br><span>2 Columns</span></label>
  	<label><input type="radio" name="wplf-columns" value="3"><br><span>3 Columns</span></label>
  	<label><input type="radio" name="wplf-columns" value="4"><br><span>4 Columns</span></label>
  	<label><input type="radio" name="wplf-columns" value="5"><br><span>5 Columns</span></label>
  	<label><input type="radio" name="wplf-columns" value="6"><br><span>6 Columns</span></label>
    <br>
 </div>
  </div>
  <br><br>
  <div id="tabs-3">
  <h3>Link Ordering</h3>
  <hr style="border: 1px solid; width: 50%;" align="left">
  <div class="radio-no-i">
  	<p>How do you want to sort your links?</p>
  	<label><input type="radio" name="wplf-order" value="title"><br><span>By Title (Link Display)</span></label>
  	<label><input type="radio" name="wplf-order" value="ID"><br><span>By Link ID</span></label>
  	<label><input type="radio" name="wplf-order" value="date"><br><span>By Date</span></label>
  	<label><input type="radio" name="wplf-order" value="rand"><br><span>Random</span></label>
    <br>
   <div>
  	<p>Should they be descending or ascending?</p>
  	<label><input type="radio" name="wplf-orderby" value="ASC"><br><span>Ascending</span></label>
  	<label><input type="radio" name="wplf-orderby" value="DESC"><br><span>Descending</span></label>
    <br>
  </div>
  <br>

  </div>

  <br><br>
  <div id="tabs-4">
  <h3>Image</h3>
  <hr style="border: 1px solid; width: 50%;" align="left">
  <div class="radio-no-i">
    <div>
  	<p>What size of image should this display use?</p>
    <?php
	$sizes = get_intermediate_image_sizes();

	foreach($sizes as $size) {
		echo '<label><input type="radio" name="wplf-image-size" value="'.$size.'"><br><span>'.ucwords($size).'</span></label>
		';
	}

	?>
  	<label><input type="radio" name="wplf-image-size" value="full"><br><span>Original</span></label>
    <br>
    </div></div><div class="checks">
  	<p>Should the image be styled?</p>
    <label><input type="checkbox" name="wplf-image-style" value="border"><br><span>Border</span></label>
  	<label><input type="checkbox" name="wplf-image-style" value="shadow"><br><span>Shadow</span></label>
  </div></div>
  <br><br>
  <div id="tabs-5" >
  <h3>Title</h3>
  <hr style="border: 1px solid; width: 50%;" align="left">
  <div class="radio-no-i">
    </div><div class="checks">
  	<p>Should the title be styled?</p>
    <label><input type="checkbox" name="wplf-title-style" value="bold"><br><span>Bold</span></label>
  	<label><input type="checkbox" name="wplf-title-style" value="italic"><br><span>Italic</span></label>
  	<label><input type="checkbox" name="wplf-title-style" value="underline"><br><span>Underline</span></label>
    <br>
    </div><div class="radio-no-i">
  	<p>How should the title be aligned?</p>
    <label><input type="radio" name="wplf-title-align" value="left"><br><span>Left</span></label>
  	<label><input type="radio" name="wplf-title-align" value="right"><br><span>Right</span></label>
  	<label><input type="radio" name="wplf-title-align" value="center"><br><span>Center</span></label>
    <br>
  </div>
  	<p>Do you want to change the font-size?</p>
    <label for="wplf-title-size">Font size: </label><input name="wplf-title-size" type="text"> px<br>
  </div>
  <br><br>
  <div id="tabs-6" >
  <h3>Description</h3>
  <hr style="border: 1px solid; width: 50%;" align="left">
  <div class="radio-no-i">
  	<p>What description should this display use?</p>
    <label><input type="radio" name="wplf-desc" value="content"><br><span>Link Description</span></label>
  	<label><input type="radio" name="wplf-desc" value="none"><br><span>None</span></label><br>
    <br>
    </div><div class="checks">
  	<p>Should the description be styled?</p>
    <label><input type="checkbox" name="wplf-desc-style" value="bold"><br><span>Bold</span></label>
  	<label><input type="checkbox" name="wplf-desc-style" value="italic"><br><span>Italic</span></label>
  	<label><input type="checkbox" name="wplf-desc-style" value="underline"><br><span>Underline</span></label>
    <br>
    </div><div class="radio-no-i">
  	<p>How should the description be aligned?</p>
    <label><input type="radio" name="wplf-desc-align" value="left"><br><span>Left</span></label>
  	<label><input type="radio" name="wplf-desc-align" value="right"><br><span>Right</span></label>
  	<label><input type="radio" name="wplf-desc-align" value="center"><br><span>Center</span></label>
    <br>
	 </div><div class="radio-no-i">
  	<p>Would you link the description to be Linked?</p>
    <label><input type="radio" name="wplf-description_link" value="no"><br><span>No</span></label>
  	<label><input type="radio" name="wplf-description_link" value="yes"><br><span>Yes</span></label>
    <br>
  </div>
  	<p>Do you want to change the font-size?</p>
    <label for="wplf-desc-size">Font size: </label><input name="wplf-desc-size" type="text"><br>
  </div>
</div>
<div class="wplf-shortcode">
<p>Your Shortcode</p>
	<textarea id="final-shortcode">[wp_links_page]</textarea>
</div>
<div class="clear">
</div>
</div>
    <?php
}

function wplf_subpage_options() {

		if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
			$sr = get_option('wplp_screenshot_refresh');
			$timestamp = time();
			if ($sr == 'daily') {$rate = '+1 day';}
			if ($sr == 'threedays') {$rate = '+3 days';}
			if ($sr == 'weekly') {$rate = '+1 week';}
			if ($sr == 'biweekly') {$rate = '+2 weeks';}
			if ($sr == 'monthly') {$rate = '+1 month';}
			if ($sr == 'never') {
				wp_clear_scheduled_hook( 'wp_links_page_free_event' );
			} else {
				$exists = wp_get_schedule( 'wp_links_page_free_event' );
				if ($exists == false) {
					wp_schedule_event(time(), $sr, 'wp_links_page_free_event');
				} else {
				$next_event = strtotime($rate, $timestamp);
				$time = wp_next_scheduled( 'wp_links_page_free_event' );
				wp_clear_scheduled_hook( 'wp_links_page_free_event' );
				wp_schedule_event( $next_event, $sr, 'wp_links_page_free_event' );
				}
			}
		}
    	$apikey = esc_attr( get_option('wplp_apikey'));
		echo '<div class="wrap wplf-settings">
		<h1>WP Links Page Settings</h1>';
		echo '<form method="post" action="options.php">';
		settings_fields( 'wp-links-page-option-group' );
		do_settings_sections( 'wp-links-page-option-group' );

		$screenshot_size = esc_attr( get_option('wplp_screenshot_size') );
		$screenshot_refresh = esc_attr( get_option('wplp_screenshot_refresh') );

		echo '<table class="form-table"><tbody>
		<tr>
			<th scope="row" class="screenshot"><label class="label" for="wplp_screenshot_size" >Screenshot Size</label></th>
	        <td class="screenshot">
			<label><input type="radio" name="wplp_screenshot_size" value="small" ';
		echo ($screenshot_size=='small')?'checked':'';
		echo ' >500px Width</label><br>
			<label><input type="radio" name="wplp_screenshot_size" value="large" ';
		echo ($screenshot_size=='large')?'checked':'';
		echo ' >1200px Width</label><br/>';
		if ($screenshot_size == 'small') {$screenshot_size = '500px Width';}
		if ($screenshot_size == 'large') {$screenshot_size = '1200px Width';}
		echo '<p class="description">What size of screenshots should WP Links Page retrieve?<br/>The screenshot size is currently set to '.$screenshot_size.'.</p></td></tr>';
		echo '';


        echo '<tr>
          <th scope="row"><label class="label" for="wplp_apikey" >Optional: Google PageSpeed Insights API key</label></th>
          <td>
          <label>
        <input id="wplp_apikey" type="text" name="wplp_apikey" value="'.$apikey.'" />';
        echo '<p class="description">If you are having trouble updating your links while on the 500px Width Screenshot Size, enter a Google PageSpeed Insights API Key here. <a href="https://developers.google.com/speed/docs/insights/v5/get-started">Get an API key here.</a><p>Your current API is '.$apikey.'.</p></td></tr>';

		echo '<tr>
			<th scope="row" class="screenshot" ><label class="label" for="wplp_screenshot_refresh" >Screenshot Refresh Rate</label></th>
	        <td class="screenshot" >
			<label><input type="radio" name="wplp_screenshot_refresh" value="never" data-current="'.$screenshot_refresh.'" ';
		echo ($screenshot_refresh=='never')?'checked':'';
		echo ' >Never</label><br/>
			<label><input type="radio" name="wplp_screenshot_refresh" value="daily" ';
		echo ($screenshot_refresh=='daily')?'checked':'';
		echo ' >Daily</label><br/>
			<label><input type="radio" name="wplp_screenshot_refresh" value="threedays" ';
		echo ($screenshot_refresh=='threedays')?'checked':'';
		echo ' >Every Three Days</label><br/>
			<label><input type="radio" name="wplp_screenshot_refresh" value="weekly" ';
		echo ($screenshot_refresh=='weekly')?'checked':'';
		echo ' >Weekly</label><br/>
			<label><input type="radio" name="wplp_screenshot_refresh" value="biweekly" ';
		echo ($screenshot_refresh=='biweekly')?'checked':'';
		echo ' >Every Two Weeks</label><br/>
			<label><input type="radio" name="wplp_screenshot_refresh" value="monthly" ';
		echo ($screenshot_refresh=='monthly')?'checked':'';
		echo ' >Monthly</label><br/>';
		if ($screenshot_refresh == 'never') {$screenshot_refresh = 'Never';}
		if ($screenshot_refresh == 'daily') {$screenshot_refresh = 'Daily';}
		if ($screenshot_refresh == 'threedays') {$screenshot_refresh = 'Every Three Days';}
		if ($screenshot_refresh == 'weekly') {$screenshot_refresh = 'Weekly';}
		if ($screenshot_refresh == 'biweekly') {$screenshot_refresh = 'Every Two Weeks';}
		if ($screenshot_refresh == 'monthly') {$screenshot_refresh = 'Monthly';}
		echo '<p class="description">How often should WP Links Page get new screenshots for your links?<br/>The refresh rate is currently set to '.$screenshot_refresh.'.</p></td></tr>';
			echo '</td></tr></tbody></table>';
		submit_button();
	}

	function wp_links_page_free_settings() { // whitelist options
		register_setting( 'wp-links-page-option-group', 'wplp_screenshot_size' );
		register_setting( 'wp-links-page-option-group', 'wplp_screenshot_refresh' );
		register_setting( 'wp-links-page-option-group', 'wplp_apikey' );
	}

	function wplf_large_screenshot($image_url, $image_name, $post_id) {
		// Add Featured Image to Post
		$upload_dir       = WPLP_UPLOAD_DIR; // Set upload folder
		$unique_file_name = wp_unique_filename( $upload_dir, $image_name ); // Generate unique name
		$filename         = basename( $unique_file_name ); // Create image file name


		// Save as a temporary file
		$down_url = $image_url . '.jpg';
		$tmp1 = download_url( $down_url );
    @unlink($tmp1);
    sleep(15);
		$tmp = download_url( $down_url );

		// Check for download errors
		if ( is_wp_error( $tmp ) )
		{
			@unlink( $tmp );
			return $tmp;
		}

		$img_url = WPLP_UPLOAD_URL.$image_name.".jpg";

		$file = WPLP_UPLOAD_DIR . $image_name . '.jpg';


		// Take care of image files without extension:
		$path = pathinfo( $tmp );
		if( ! isset( $path['extension'] ) ):
			$tmpnew = $tmp . '.jpg';
			if( ! rename( $tmp, $tmpnew ) ):
				return '';
			else:
				$name = $filename.'.jpg';
				$tmp = $tmpnew;
			endif;
		endif;
		if( $path['extension'] == 'tmp' ):
			$tmpnew = $path['dirname'].'/'.$path['filename'] . '.jpg';
			if( ! rename( $tmp, $tmpnew ) ):
				return '';
			else:
				$name = $filename.'.jpg';
				$tmp = $tmpnew;
			endif;
		endif;

		$file = WPLP_UPLOAD_DIR . $filename . time() . '.jpg';
		$move = rename($tmp, $file);


		// Check image file type
		$wp_filetype = wp_check_filetype( $file, null );

		// Set attachment data
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		// Create the attachment
		$attach_id = wp_insert_attachment( $attachment, $file, $post_id );

		// Include image.php
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		// Define attachment metadata
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

		// Assign metadata to attachment
		wp_update_attachment_metadata( $attach_id, $attach_data );

		// Get current screenshot ID and delete if it exists
		$post_thumbnail_id = get_post_thumbnail_id( $post_id );
		$mk = wplf_filter_metadata( get_post_meta( $post_id ) );
		if (!empty($post_thumbnail_id) && $mk['wplp_media_image'] != 'true') {
			wp_delete_attachment( $post_thumbnail_id, true );
		}

		// And finally assign featured image to post
		set_post_thumbnail( $post_id, $attach_id );
		update_post_meta( $post_id, 'wplp_media_image', 'false');

    @unlink( $tmp );
		return 'success';
	}

  function wplf_large_screenshot_quick($image_url, $image_name, $post_id) {
		// Add Featured Image to Post
		$upload_dir       = WPLP_UPLOAD_DIR; // Set upload folder
		$unique_file_name = wp_unique_filename( $upload_dir, $image_name ); // Generate unique name
		$filename         = basename( $unique_file_name ); // Create image file name


		// Save as a temporary file
		$down_url = $image_url . '.jpg';
		$tmp = download_url( $down_url );

    error_log(print_r($tmp,true));
		// Check for download errors
		if ( is_wp_error( $tmp ) )
		{

			//@unlink( $tmp );
			return $tmp;
		}

		$img_url = WPLP_UPLOAD_URL.$image_name.".jpg";

		$file = WPLP_UPLOAD_DIR . $image_name . '.jpg';


		// Take care of image files without extension:
		$path = pathinfo( $tmp );
		if( ! isset( $path['extension'] ) ):
			$tmpnew = $tmp . '.jpg';
			if( ! rename( $tmp, $tmpnew ) ):
				return '';
			else:
				$name = $filename.'.jpg';
				$tmp = $tmpnew;
			endif;
		endif;
		if( $path['extension'] == 'tmp' ):
			$tmpnew = $path['dirname'].'/'.$path['filename'] . '.jpg';
			if( ! rename( $tmp, $tmpnew ) ):
				return '';
			else:
				$name = $filename.'.jpg';
				$tmp = $tmpnew;
			endif;
		endif;


		$exists = file_exists($file);
    error_log(print_r($exists,true));
		if ($exists == true) {
			$file = WPLP_UPLOAD_DIR . $filename . time() . '.jpg';
		}
		$move = rename($tmp, $file);


		// Check image file type
		$wp_filetype = wp_check_filetype( $file, null );

		// Set attachment data
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		// Create the attachment
		$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
    error_log(print_r($attach_id,true));

		// Include image.php
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		// Define attachment metadata
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

		// Assign metadata to attachment
		wp_update_attachment_metadata( $attach_id, $attach_data );

		// Get current screenshot ID and delete if it exists
		$post_thumbnail_id = get_post_thumbnail_id( $post_id );
		$mk = wplf_filter_metadata( get_post_meta( $post_id ) );
		if (!empty($post_thumbnail_id) && $mk['wplp_media_image'] != 'true') {
			wp_delete_attachment( $post_thumbnail_id, true );
		}

		// And finally assign featured image to post
		set_post_thumbnail( $post_id, $attach_id );
		$updatePm = update_post_meta( $post_id, 'wplp_media_image', 'false');

    error_log(print_r($updatePm,true));

    @unlink( $tmp );
		return 'success';
	}

	function wplf_small_screenshot_url($image_name, $url, $post_id) {
    $apikey = esc_attr( get_option('wplp_apikey'));
		$screenshot = file_get_contents('https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed?url='.$url."&key=".$apikey);
		$data_whole = json_decode($screenshot);

		if (isset($data_whole->error) || empty($screenshot)) {
			if (!(substr($url, 0, 4) == 'http')) {
				$url2 = 'https%3A%2F%2F' . $url;
        $apikey = esc_attr( get_option('wplp_apikey'));
				$screenshot = file_get_contents('https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed?url='.$url2."&key=".$apikey);
				$data_whole = json_decode($screenshot);
			}
		}
		if (isset($data_whole->error) || empty($screenshot)) {
			if (!(substr($url, 0, 3) == 'www')) {
				$url3 = 'https%3A%2F%2F' . 'www.' . $url;
        $apikey = esc_attr( get_option('wplp_apikey'));
				$screenshot = file_get_contents('https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed?url='.$url3."&key=".$apikey);
				$data_whole = json_decode($screenshot);
			}
		}
		if (isset($data_whole->error)) {
				die(json_encode(array('message' => 'ERROR', 'code' => 'data returned error')));
		}
		if (isset($data_whole->lighthouseResult->audits->{'final-screenshot'}->details->data)) {
			$data = $data_whole->lighthouseResult->audits->{'final-screenshot'}->details->data;
      $data = str_replace('data:image/jpeg;base64','',$data);
		} else {
		die(json_encode(array('message' => 'ERROR', 'code' => 'no screenshot')));}
		$data = str_replace('_', '/', $data);
		$data = str_replace('-', '+', $data);
		$base64img = str_replace('data:image/jpeg;base64,', '', $data);

		$data   		      = base64_decode($data);
		$upload_dir       = WPLP_UPLOAD_DIR; // Set upload folder
		$image_data       = $data; // img data
		$unique_file_name = wp_unique_filename( $upload_dir, $image_name ); // Generate unique name
		$filename         = basename( $unique_file_name ); // Create image file name


		$tmp = WPLP_UPLOAD_DIR . $image_name . '.jpg';
		// Create the image  file on the server
		file_put_contents( $tmp, $image_data );

		$exists = file_exists($tmp);
		if ($exists == true) {
			$file = WPLP_UPLOAD_DIR . $filename . time() . '.jpg';
		}
		$move = rename($tmp, $file);


		// Check image file type
		$wp_filetype = wp_check_filetype( $file, null );

		// Set attachment data
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		// Create the attachment
		$attach_id = wp_insert_attachment( $attachment, $file, $post_id );

		// Include image.php
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		// Define attachment metadata
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

		// Assign metadata to attachment
		wp_update_attachment_metadata( $attach_id, $attach_data );

		// Get current screenshot ID and delete if it exists
		$post_thumbnail_id = get_post_thumbnail_id( $post_id );
		$mk = wplf_filter_metadata( get_post_meta( $post_id ) );
		if (!empty($post_thumbnail_id) && $mk['wplp_media_image'] != 'true') {
			wp_delete_attachment( $post_thumbnail_id, true );
		}

		// And finally assign featured image to post
		set_post_thumbnail( $post_id, $attach_id );
		update_post_meta( $post_id, 'wplp_media_image', 'false');

		return 'success';
	}

	function wplf_small_screenshot($image_name, $data, $post_id) {
		$data = str_replace('_', '/', $data);
		$data = str_replace('-', '+', $data);
		$base64img = str_replace('data:image/jpeg;base64,', '', $data);

		$data   		  = base64_decode($data);
		$upload_dir       = WPLP_UPLOAD_DIR; // Set upload folder
		$image_data       = $data; // img data
		$unique_file_name = wp_unique_filename( $upload_dir, $image_name ); // Generate unique name
		$filename         = basename( $unique_file_name ); // Create image file name


		$tmp = WPLP_UPLOAD_DIR . $image_name . '.jpg';
		// Create the image  file on the server
		$filep = file_put_contents( $tmp, $image_data );

		$exists = file_exists($tmp);
		if ($exists == true) {
			$file = WPLP_UPLOAD_DIR . $filename . time() . '.jpg';
		}
		$move = rename($tmp, $file);

		// Check image file type
		$wp_filetype = wp_check_filetype( $file, null );

		// Set attachment data
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		// Create the attachment
		$attach_id = wp_insert_attachment( $attachment, $file, $post_id );

		// Include image.php
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		// Define attachment metadata
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

		// Assign metadata to attachment
		wp_update_attachment_metadata( $attach_id, $attach_data );
		$post_thumbnail_id = get_post_thumbnail_id( $post_id );

		$mk = wplf_filter_metadata( get_post_meta( $post_id ) );
		if (!empty($post_thumbnail_id) && $mk['wplp_media_image'] != 'true') {
			wp_delete_attachment( $post_thumbnail_id, true );
		}

		// And finally assign featured image to post
		set_post_thumbnail( $post_id, $attach_id );
		update_post_meta( $post_id, 'wplp_media_image', 'false');

		return 'success';
	}

	/* Shortcode */

	add_filter( 'the_content', 'wplf_remove_autop', 0 );
	function wplf_remove_autop( $content )
	{
		 global $post;
		 // Check for single page and image post type and remove
		 if ( $post->post_type == 'wplp_link' )
			  remove_filter('the_content', 'wpautop');

		 return $content;
	}

	function wplf_shortcode($atts){

		if (get_option('wplp_grid') != false) {
			$dis = get_option('wplf_grid');
		} else { $dis = 'grid'; }
		if (get_option('wplp_width') != false) {
			$col = get_option('wplf_width');
		} else { $col = '3'; }

		if (get_option('wplpf_grid') != false) {
			$dis = get_option('wplff_grid');
		} else { $dis = 'grid'; }
		if (get_option('wplpf_width') != false) {
			$col = get_option('wplff_width');
		} else { $col = '3'; }

		$vars = shortcode_atts( array(
			'ids' => '',
			'type' => '',
			'display' => 'grid',
			'cols' => '3',
			'order' => 'DESC',
			'orderby' => 'ID',
			'sort' => '',
			'img_size' => 'medium',
			'img_style' => '',
			'title_style' => '',
			'desc' => '',
			'description' => '',
			'description_link' => 'yes',
			'desc_style' => '',
			), $atts );

		$default_num_posts = get_option( 'posts_per_page' );
		$display = esc_attr($vars['display']);
		$type = esc_attr($vars['type']);
		if ($type != '' && $display == 'grid') {
			$display = $type;
		}
		$cols = esc_attr($vars['cols']);

		$order = esc_attr($vars['order']);
		$meta = '';
		$orderby = esc_attr($vars['orderby']);
		$sort = esc_attr($vars['sort']);
		if ($sort == 'random' && $orderby == 'ID') {
			$oderby = 'rand';
		}
		if ($orderby == 'title') {
			$orderby = 'meta_value';
			$meta = 'wplp_display';
		}
		$img_size = esc_attr($vars['img_size']);
		$img_style = esc_attr($vars['img_style']);
		$title_style = esc_attr($vars['title_style']);
		$desc = esc_attr($vars['desc']);
		$description = esc_attr($vars['description']);
		if ($description == 'yes' && $desc == '') {
			$desc = 'content';
		}
		$description_link = esc_attr($vars['description_link']);
		$desc_style = esc_attr($vars['desc_style']);
		$ids = esc_attr($vars['ids']);


		wp_enqueue_style('wplf-display-style');
		wp_enqueue_script('wplf-display-js');

		global $wpdb;
		$grid = '';
		$list = '';
		$gallery = '';
		$i = 0;

		$query_args = array('post_type' => 'wplp_link', 'order' => $order, 'posts_per_page' => '-1', 'orderby' => $orderby, 'meta_key' => 'wplp_display', 'metakey' => $meta, 'post_status' => 'publish');

		if ($ids != '') {
			$idarr = explode(',', $ids);
			$query_args['post__in'] = $idarr;
		}
		remove_all_filters('posts_orderby');
		$custom_query = new WP_Query( $query_args );
		//print('<pre>'.print_r($custom_query,true).'</pre>');

		while($custom_query->have_posts()) : $custom_query->the_post();
			$post_id = get_the_ID();
			$mk = wplf_filter_metadata( get_post_meta( $post_id ) );
			if (isset($mk['wplp_display'])) {$mdisp = $mk['wplp_display'];} else {$mdisp = '';}

      $url = the_title("","",false);
			// Image
			$thumb = get_post_thumbnail_id($post_id);
			$img = wp_get_attachment_image($thumb, $img_size, false, array('style' => $img_style));


			// Title
			$title_display = $mdisp;


			// Description
			$description = '';
			if ($desc == 'content') {
				$description = apply_filters('the_content',get_the_content());
				$description = '<p class="wplf_desc" style="'.$desc_style.'">'.$description.'</p>';
			}




			if ($description_link == 'yes') {
				$description = $description.'</a>';
			} else {
				$description = '</a>'.$description;
			}



			if ($display == 'grid') {
				$gallery .= '<figure id="gallery-item-'.$i.'" class="gallery-item wplf-item">
				<div class="gallery-icon landscape">
				<a class="wplf_link" href="'.$url.'" target="_blank">
				'.$img.'
				<p class="wplf_display" style="'.$title_style.'" >'.$title_display.'</p>
				'.$description.'
				</div>
				</figure>';
			} elseif ($display == 'list') {
				$list .= '<div id="wplf_list-item-'.$i.'" class="list-item wplf-item">
				<a class="wplf_link" href="'.$url.'" target="_blank">
				<div class="list-img">'.$img.'</div>
				<p class="wplf_display" style="'.$title_style.'" >'.$title_display.'</p>
				'.$description.'
				</div>
				<hr>';
			}
		$i++;

		endwhile;

		if ($display == 'grid') {
			$output = '<div style="clear:both;"></div><div id="gallery-wplf" class="galleryid-wplf gallery-columns-'.$cols.' wplf-display">'.$gallery.'</div><div style="clear:both;"></div>';
		} elseif ($display == 'list') {
			$output = '<div style="clear:both;"></div><div id="list-wplf" class="listid-wplf wplf-display">'.$list.'</div><div style="clear:both;"></div>';
		}
		wp_reset_query();

		return $output;
	}
	add_shortcode('wp_links_page', 'wplf_shortcode');


	add_shortcode('wp_links_page_free', 'wplf_shortcode');
}
?>
