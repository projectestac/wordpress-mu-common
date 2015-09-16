<?php
/*
Plugin Name: CommonFunctions lib
Plugin URI: https://github.com/projectestac/agora_nodes
Description: A pluggin to include common functions which affects to all themes
Version: 1.0
Author: Àrea TAC - Departament d'Ensenyament de Catalunya
*/


/**
 * Remove screen options from posts to simplify user experience
 * @author Xavi Meler
 */
function remove_post_meta_boxes() {
	remove_meta_box('trackbacksdiv', 'post', 'normal');
	remove_meta_box('trackbacksdiv', 'post', 'side');
	remove_meta_box('postcustom', 'post', 'normal');
	remove_meta_box('postcustom', 'post', 'side');
	remove_meta_box('rawhtml_meta_box', 'post', 'side');
	remove_meta_box('rawhtml_meta_box', 'post', 'normal');
	remove_meta_box('layout_meta', 'post', 'side');
	remove_meta_box('layout_meta', 'post', 'normal');
}
add_action('do_meta_boxes', 'remove_post_meta_boxes');

/**
 * Remove screen options from pages to simplify user experience
 * @author Xavi Meler
 */
function remove_page_meta_boxes() {
	remove_meta_box('rawhtml_meta_box', 'page', 'normal');
	remove_meta_box('rawhtml_meta_box', 'page', 'side');
	remove_meta_box('postcustom', 'page', 'normal');
	remove_meta_box('postimagediv', 'page', 'side');
}
add_action('do_meta_boxes', 'remove_page_meta_boxes');

/**
 * Check if user don't have preferences, and
 * Sets order and initial position from boxes
 * for pages or articles
 * @author Nacho Abejaro
 * @author Sara Arjona
 */
function set_order_meta_boxes($hidden, $screen) {
	$post_type = $screen->post_type;
	// So this can be used without hooking into user_register
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	//$user_meta = get_user_meta($user_id);
	$meta_key = array(
		'order' => "meta-box-order_$post_type",
		'hidden' => "metaboxhidden_$post_type",
		'closed' => "closedpostboxes_$post_type",
	);

	// If user have preferences, do nothing
	if ( ! get_user_meta($user_id, $meta_key['order'], true) ) {
		if ( $post_type == 'post' ) {
			// Defines position of the meta-boxes
			$meta_value = array(
				'side' => 'submitdiv,postimagediv,postexcerpt,formatdiv,metabox1,tagsdiv-post',
				'normal' => 'categorydiv',
				'advanced' => '',
			);
			update_user_meta($user_id, $meta_key['order'], $meta_value);

			// Defines hidden meta-boxes
			$meta_value = array('authordiv', 'commentsdiv', 'commentstatusdiv', 'formatdiv', 'layout_meta', 'revisionsdiv', 'slugdiv', 'ping_status');
			update_user_meta($user_id, $meta_key['hidden'], $meta_value);
		} elseif ( $post_type == 'page' ) {
			// Defines position of the meta-boxes
			$meta_value = array(
				'side' => 'submitdiv,pageparentdiv',
				'normal' => 'commentstatusdiv',
				'advanced' => '',
			);
			update_user_meta($user_id, $meta_key['order'], $meta_value);

			// Defines hidden meta-boxes
			$meta_value = array('authordiv', 'commentsdiv', 'commentstatusdiv', 'revisionsdiv', 'slugdiv');
			update_user_meta($user_id, $meta_key['hidden'], $meta_value);

			// Defines collapsed meta-boxes
			$meta_value = array('layout_meta');
			update_user_meta($user_id, $meta_key['closed'], $meta_value);
		}
	}
}
add_action('add_meta_boxes', 'set_order_meta_boxes', 10, 2);

/**
 * Disable or enable comments and pings for pages or articles
 * @author Nacho Abejaro
 */
function default_comments_off( $data ) {
	if( $data['post_type'] == 'page' && $data['post_status'] == 'auto-draft' ) {
		$data['comment_status'] = 'close';
        $data['ping_status'] = 'close';
    }elseif ( $data['post_type'] == 'post' && $data['post_status'] == 'auto-draft' ) {
    	$data['comment_status'] = 'open';
    	$data['ping_status'] = 'open';
    }
	return $data;
}
add_filter( 'wp_insert_post_data', 'default_comments_off' );

/**
 * Add upload images capability to the contributor rol
 * @author Xavi Meler
 */
function add_contributor_caps() {
	$role = get_role('contributor');
	$role->add_cap('upload_files');
}
add_action('admin_init', 'add_contributor_caps');

/**
 * Restricting contributors to view only media library items they upload
 * TODO: fix counter (now counter show all files count)
 * @author Xavi Meler
*/
function users_own_attachments( $wp_query_obj ) {
	global $current_user, $pagenow;

	if ( ! is_a($current_user, 'WP_User') ) {
		return;
	}

	if ( ('edit.php' != $pagenow) && ('upload.php' != $pagenow ) &&
	(( 'admin-ajax.php' != $pagenow ) || ( $_REQUEST['action'] != 'query-attachments' ) ) ) {
		return;
	}

	// Apply to this roles: Subscriptor, Contributor and Author
	if ( ! current_user_can('delete_pages') ) {
		$wp_query_obj->set('author', $current_user->id);
	}

	return;
}
add_action('pre_get_posts','users_own_attachments');

/**
 * Remove the "Dashboard" from the admin menu for contributor user roles
 * @author Nacho Abejaro
 */
function remove_contributor_dashboard() {

    $role = getRole();

    if ($role === 'contributor') {
        remove_menu_page('edit-comments.php');
        remove_menu_page('edit.php?post_type=gce_feed');
        remove_menu_page('tools.php');
    }
}

add_action('admin_menu', 'remove_contributor_dashboard');

/**
 * Disable gravatar.com calls.
 * @author Víctor Saavedra (vsaavedr@xtec.cat)
 */
function remove_gravatar ($avatar, $id_or_email, $size, $default, $alt) {
	$default = admin_url('images/mysteryman.png');
	return "<img alt='{$alt}' src='{$default}' class='avatar avatar-{$size} photo avatar-default' height='{$size}' width='{$size}' />";
}

add_filter('get_avatar', 'remove_gravatar', 1, 5);


/**
 * Avoid upload large images (>2MB).
 * @author Xavier Meler (jmeler@xtec.cat)
 * Thanks fischi
 * http://wordpress.stackexchange.com/questions/131066/prevent-large-image-uploads/131076#131076
 */

function avoid_large_images_upload($file) {
    $type = $file['type'];
    $is_image = strpos($type, 'image');

    if ($is_image!==false){
        $size = $file['size'];
        $size = $size / 1024; // KB
        $limitKB = 2048;      // KB
        $limitMB = $limitKB/1024;

        if ( ( $size > $limitKB ) ) {
            $file['error'] = __('Image files must be smaller than ', 'common-functions').$limitMB.' MB. '. __('Recommended width image:', 'common-functions')." 1024px.";
        }
    }
    return $file;
}

add_filter('wp_handle_upload_prefilter', 'avoid_large_images_upload');


/**
 * Display extra warning message related to maximum image size
 * @author Xavier Meler (jmeler@xtec.cat)
 */

function warning_size_image() {
    echo  __('Image files must be smaller than ', 'common-functions').' 2 MB. '. __('Recommended width image:', 'common-functions')." 1024px <a target='_blank' href='https://sites.google.com/a/xtec.cat/ajudaxtecblocs/insercio-de-continguts/fitxers-d-audio-i-video#TOC-Qu-cal-fer-si-els-fitxers-d-imatge-s-n-molt-grans-'>Ajuda</a>";

}

add_filter('post-upload-ui', 'warning_size_image');


/**
 * Hide full size
 * @author Xavier Meler (jmeler@xtec.cat)
 * Thanks wycks
 * https://gist.github.com/wycks/4949242
 */

function add_image_insert_override($size_names){
        $size_names = array(
                          'thumbnail' => __('Thumbnail'),
                          'medium'    => __('Medium'),
                          'large'     => __('Large'),
                        );
      return $size_names;
};

add_filter('image_size_names_choose', 'add_image_insert_override' );


/**
 * RSS Shortcode
 * @author Xavier Meler (jmeler@xtec.cat)
 */
function rss_shortcode($atts) {

    include_once(ABSPATH . WPINC . '/feed.php');

    $attributes = shortcode_atts(array(
        'feeds' => '',
        'quantity' => 5,
        'notitle' => '',
            ), $atts);

    $my_feeds = explode(",", $attributes['feeds']);

    foreach ($my_feeds as $feed) :

        $rss = fetch_feed($feed);
        if (!is_wp_error($rss)) : // Checks that the object is created correctly
            $maxitems = $rss->get_item_quantity($attributes['quantity']);
            $rss_items = $rss->get_items(0, $maxitems);
            $rss_title = '<a href="' . $rss->get_permalink() . '" target="_blank">' . strtoupper($rss->get_title()) . '</a>';
        endif;

        echo '<div class="rss-sc">';
        if ($attributes['notitle'] === '') {
            echo '<div class="rss-title">' . $rss_title . '</div>';
        }
        echo '<ul>';

        // Check items
        if ($maxitems == 0) {
            echo '<li>' . __('No item', 'common-functions') . '.</li>';
        } else {
            foreach ($rss_items as $item) :
                // Get human date (comment if you want to use non human date)
                $item_date = __('>', 'common-functions') . " " . human_time_diff($item->get_date('U'), current_time('timestamp'));
                echo '<li>';
                echo '<a href="' . esc_url($item->get_permalink()) . '" title="' . $item_date . '">';
                echo esc_html($item->get_title());
                echo '</a>';
                echo ' <span class="rss-date">' . $item_date . '</span><br />';
                echo '<div class="rss-excerpt">';
                $content = $item->get_content();
                $content = wp_html_excerpt($content, 150) . ' ...';
                echo $content;
                echo '</div>';
                echo '</li>';
            endforeach;
        }
        echo '</ul></div>';

    endforeach;
}

add_shortcode('rss', 'rss_shortcode');

/**
* Add feature image to rss
* @author Brad Dalton
* @author Xavier Meler (jmeler@xtec.cat)
*/
function add_post_thumbnail_rss($content) {
    global $post;
    if ( has_post_thumbnail( $post->ID ) ){
        $content = '' . get_the_post_thumbnail( $post->ID, 'thumbnail'). '' . $content;
    }
    return $content;
}

add_filter('the_content_feed', 'add_post_thumbnail_rss');
add_filter('the_excerpt_rss',  'add_post_thumbnail_rss');

/**
* Add tags to rss
* @author Xavier Meler (jmeler@xtec.cat)
*/
function add_tags_rss() {
    global $post;
    $posttags = wp_get_post_tags($post->ID);
    if (count(array_filter($posttags))>0) {
      foreach($posttags as $tag) {
        echo("<tag>$tag->name</tag>");
      }
    }
 }

add_action('rss2_item', 'add_tags_rss');

/**
 * Set number of posts per page for search and archive template
 * @author Xavier Meler (jmeler@xtec.cat)
 */
function posts_per_page() {
    if ( is_search() || is_archive() || is_author()){
        set_query_var('posts_per_page', 10);
    }
}

add_filter('pre_get_posts', 'posts_per_page');


/**
 * Exclude admin pages for Contributor user role
 * @author Nacho Abejaro
 */
function exclude_pages_from_admin($query) {
    global $pagenow;

    $role = getRole();

    $restrictedPages = array(
        'edit-comments.php',
        'tools.php',
    );

    $restrictedPagesWithPost = array(
        'edit.php?post_type=gce_feed',
        'post-new.php?post_type=gce_feed',
    );

    $post_type = get_current_post_type();

    $postUrl = $pagenow . '?post_type=' . $post_type;

    if ($role == 'contributor' && (
            ( in_array($pagenow, $restrictedPages) || in_array($postUrl, $restrictedPagesWithPost) ))
    ) {
        wp_die(__('You do not have permission to do that.'));
    }
}

add_filter('parse_query', 'exclude_pages_from_admin');

/**
 * Get the user Role
 * @author Nacho Abejaro
 */
function getRole() {
    
    $user_id = get_current_user_id();

    // @aginard: in multisite system (XTECBlocs) the meta_key has the form of
    //   wp_$blogid_capatilities, but in single site (Agora), it has the form
    //   wp_capabilities. We need to differentiate them.
    if (is_multisite()) {
        $blogId = get_current_blog_id();
        $caps = get_user_meta($user_id, 'wp_' . $blogId . '_capabilities', true);
    } else {
        $caps = get_user_meta($user_id, 'wp_capabilities', true);
    }

    $roles = array_keys((array) $caps);
    $role = $roles[0];

    return $role;
}

/**
 * Get the current post_type
 * @author Nacho Abejaro
 */
function get_current_post_type() {
    global $post, $typenow, $current_screen;

    if ($post && $post->post_type) {
        // We have a post so we can just get the post type from that
        return $post->post_type;
    } elseif ($typenow) {
        // Check the global $typenow - set in admin.php
        return $typenow;
    } elseif ($current_screen && $current_screen->post_type) {
        // Check the global $current_screen object - set in sceen.php
        return $current_screen->post_type;
    } elseif (isset($_REQUEST['post_type'])) {
        // Lastly check the post_type querystring
        return sanitize_key($_REQUEST['post_type']);
    } else {
        // We do not know the post type!
        return null;
    }
}

/**
 * Hide tabs and sections for non superadmin users 
 * @author Xavi Meler
 */
add_action('wsl_admin_main_start','social_login_hide_elements');

function social_login_hide_elements() {
    global $WORDPRESS_SOCIAL_LOGIN_ADMIN_TABS;

    if (!is_xtec_super_admin()) {
        // Hide tabs
        $hide_elements = ["login-widget", "components", "tools", "help"];
        foreach ($hide_elements as $hide_element) {
            $WORDPRESS_SOCIAL_LOGIN_ADMIN_TABS[$hide_element]["visible"] = false;
        }
        // Hide upper right links (Documentation, Suport, Github)
        echo "<style> .wsl-container .alignright { display:none; } </style>";
    }
}

/**
 * WP_Social_login modifications
 * 
 * Hide filter_profile, profile_completion and membership_level sections for non xtecadmin user
 * Added blacklist feature, added blacklist section
 * Reorder sections to adjust to the new authentication process
 * 
 * @author Xavi Meler
 */
// Register new settings for email's blacklist feature
add_action('wsl_register_setting', 'register_blacklist_email');

function register_blacklist_email() {
    register_setting('wsl-settings-group-bouncer', 'wsl_settings_bouncer_new_users_restrict_blacklist_enabled');
    register_setting('wsl-settings-group-bouncer', 'wsl_settings_bouncer_new_users_restrict_blacklist_list');
    register_setting('wsl-settings-group-bouncer', 'wsl_settings_bouncer_new_users_restrict_blacklist_text_bounce');
}


/* Access Control tab modifications */
add_filter('wsl_component_bouncer_setup_alter_sections', 'overwrite_setup_sections');

function overwrite_setup_sections($sections) {
    $sections = array(
        'wsl_widget' => 'wsl_component_bouncer_setup_wsl_widget',
        'filters_blacklist_mails' => 'wsl_component_bouncer_setup_filters_blacklist_mails',
        'filters_whitelist_mails' => 'wsl_component_bouncer_setup_filters_mails',
        'filters_domains' => 'wsl_component_bouncer_setup_filters_domains',
    );

    if (is_xtec_super_admin()) {
        $sections['filters_urls'] = 'wsl_component_bouncer_setup_filters_urls';
        $sections['membership_level'] = 'wsl_component_bouncer_setup_membership_level';
        $sections['profile_completion'] = 'wsl_component_bouncer_setup_profile_completion';
        $sections['user_moderation'] = 'wsl_component_bouncer_setup_user_moderation';
    }

    return $sections;
}


// Blacklist setup section
function wsl_component_bouncer_setup_filters_blacklist_mails() {
?>
    <div class="stuffbox">
    	<h3>
    		<label><?php _wsl_e("BLACKLIST", 'wordpress-social-login') ?></label>
    	</h3>
    	<div class="inside"> 
    		<p>     
                <?php _wsl_e("Email addresses of blocked users", 'wordpress-social-login') ?>.
            </p>
    		<table width="100%" border="0" cellpadding="5" cellspacing="2" style="border-top:1px solid #ccc;">  
    		  <tr>
    			<td width="200" align="right"><strong><?php _wsl_e("Enabled", 'wordpress-social-login') ?> :</strong></td>
    			<td> 
    				<select name="wsl_settings_bouncer_new_users_restrict_blacklist_enabled">
    					<option <?php if (get_option('wsl_settings_bouncer_new_users_restrict_blacklist_enabled') == 1) echo "selected"; ?> value="1"><?php _wsl_e("Yes", 'wordpress-social-login') ?></option>
    					<option <?php if (get_option('wsl_settings_bouncer_new_users_restrict_blacklist_enabled') == 2) echo "selected"; ?> value="2"><?php _wsl_e("No", 'wordpress-social-login') ?></option> 
    				</select>
    			</td>
    		  </tr>   
    		  <tr>
    			<td width="200" align="right" valign="top"><p><strong><?php _wsl_e("E-mails list", 'wordpress-social-login') ?> :</strong></p></td>
    			<td> 
    				<textarea style="width:100%;height:60px;margin-top:6px;" name="wsl_settings_bouncer_new_users_restrict_blacklist_list"><?php echo get_option('wsl_settings_bouncer_new_users_restrict_blacklist_list'); ?></textarea>  
    			</td>
    		  </tr>  
    		  <tr>
    			<td width="200" align="right" valign="top"><p><strong><?php _wsl_e("Bounce text", 'wordpress-social-login') ?> :</strong></p></td>
    			<td> 
                <?php
                    wsl_render_wp_editor( "wsl_settings_bouncer_new_users_restrict_blacklist_text_bounce", get_option( 'wsl_settings_bouncer_new_users_restrict_blacklist_text_bounce')); 
				?>
			</td>
		  </tr>  
		</table>  
	</div>
</div>
<?php
}