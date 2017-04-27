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
	remove_meta_box('layout_meta', 'post', 'side');
	remove_meta_box('layout_meta', 'post', 'normal');
}
add_action('do_meta_boxes', 'remove_post_meta_boxes');

/**
 * Remove screen options from pages to simplify user experience
 * @author Xavi Meler
 */
function remove_page_meta_boxes() {
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
	if ( ! isset($user_id) ) {
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
        $data['ping_status'] = 'close';
    }elseif ( $data['post_type'] == 'post' && $data['post_status'] == 'auto-draft' ) {
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

/**
 * Set default selected media size to "large".
 * If large size not exists, select Original size.
 * @author Xavier Meler (jmeler@xtec.cat)
 */

function default_image_size (){
    return 'large';
}

add_filter('pre_option_image_default_size','default_image_size');

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
function posts_per_page ($query) {
    if ( $query->is_search() || $query->is_archive() || $query->is_author()){
        set_query_var('posts_per_page', 10);
    }
}

add_filter('pre_get_posts', 'posts_per_page');


/**
 * Block access to some admin pages to roles contributor and xtec_teacher
 *
 * @author Nacho Abejaro
 * @author Toni Ginard
 * @author Xavier Nieto
 */
function exclude_pages_from_admin() {

    global $pagenow;

    // Get roles to current user
    $user = wp_get_current_user();
    $role = (array) $user->roles;

    $restrictedPages = array(
        'edit-comments.php',
        'tools.php',
    );

    // If user has role xtec_teacher, filter only restricted pages
    if ( in_array( 'xtec_teacher', $role ) ){
        if ( in_array( $pagenow, $restrictedPages )) {
            wp_die( __( 'You do not have permission to do that.' ) );
        } else {
            return ;
        }
    }

    // If user only has role contributor, filter also simple calendar pages
    if ( in_array( 'contributor', $role ) ){
        $restrictedPagesWithPost = array (
            'edit.php?post_type=calendar',
            'post-new.php?post_type=calendar',
            'edit.php?post_type=gce_feed',
            'post-new.php?post_type=gce_feed',
        );

        $postUrl = $pagenow . '?post_type=' . get_current_post_type();

        if ( in_array( $pagenow, $restrictedPages ) || in_array( $postUrl, $restrictedPagesWithPost )) {
            wp_die( __( 'You do not have permission to do that.' ) );
        }
    }

}
add_action('admin_init', 'exclude_pages_from_admin');

/**
 * Hide menu options in the Dashboard to roles contributor and xtec_teacher
 *
 * @author Nacho Abejaro
 * @author Xavi Nieto
 * @author Toni Ginard
 */
function xtec_remove_menu_pages() {

    $user = wp_get_current_user();
    $roles = (array) $user->roles;

    if ( in_array( 'contributor', $roles ) || in_array( 'xtec_teacher', $roles )) {
        remove_menu_page('edit-comments.php');
        remove_menu_page('edit.php?post_type=calendar');
        remove_menu_page('tools.php');
    }
}
add_action('admin_menu', 'xtec_remove_menu_pages');

/**
 * Get the user Role
 * @author Nacho Abejaro
 */
function getRole() {

    $user_id = get_current_user_id();

    // @aginard: in multisite system (XTECBlocs) the meta_key has the form of
    //   wp_$blogid_capatilities, but in single site (Agora), it has the form
    //   wp_capabilities. We need to differentiate them.
    if (is_multisite() && !MULTISITE) { // !MULTISITE is an exception for WordPress MU installations
        $blogId = get_current_blog_id();
        $caps = get_user_meta($user_id, 'wp_' . $blogId . '_capabilities', true);
    } else {
        $caps = get_user_meta($user_id, 'wp_capabilities', true);
    }
    
    return is_array($caps) ? key($caps) : false;
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
        	// @nacho Show components for admin users in XTECBlocs
        	if  ( ($hide_element == 'components') && (is_xtecblocs()) ){
        		$WORDPRESS_SOCIAL_LOGIN_ADMIN_TABS[$hide_element]["visible"] = true;
        	}else {        	
            	$WORDPRESS_SOCIAL_LOGIN_ADMIN_TABS[$hide_element]["visible"] = false;
        	}
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

/**
 * Plugin Name: Tinymce Category Description
 * Description: Adds a tinymce editor to the category description box
 * Author: Paulund
 * Author URI: http://www.paulund.co.uk
 * Version: 1.0
 * License: GPL2
*/
// Remove the html filtering
remove_filter( 'pre_term_description', 'wp_filter_kses' );
remove_filter( 'term_description', 'wp_kses_data' );

function cat_description($tag)
{ ?>
    <table class="form-table">
        <tr class="form-field">
            <th scope="row" valign="top"><label for="description"><?php _ex('Description', 'Taxonomy Description'); ?></label></th>
            <td>
            <?php
                $settings = array('wpautop' => true, 'media_buttons' => true, 'quicktags' => true, 'textarea_rows' => '15', 'textarea_name' => 'description' );
                wp_editor(htmlspecialchars_decode(wp_kses_post($tag->description , ENT_QUOTES, 'UTF-8'),ENT_QUOTES), 'cat_description', $settings);
            ?>
            <br />
            <span class="description"><?php _e('The description is not prominent by default; however, some themes may show it.'); ?></span>
            </td>
        </tr>
    </table>
    <?php
}

// Allow HTML in description category/tag
add_filter('edit_category_form_fields', 'cat_description');
add_filter('edit_tag_form_fields', 'cat_description');

// Remove the field "description" in category edition and in bp-docs tags
function remove_default_category_description() {
    global $current_screen;

    if (($current_screen->id == 'edit-category') 
        || ($current_screen->id == 'edit-post_tag') 
        || ($current_screen->id == 'edit-bp_docs_associated_item')
        || ($current_screen->id == 'edit-bp_docs_tag')) {
    ?>
        <script type="text/javascript">
        jQuery(function($) {
            $('textarea#description').closest('tr.form-field').remove();
        });
        </script>
    <?php
    }
}
add_action('admin_head', 'remove_default_category_description');

/**
 * Empty the attribute onerror in img tags
 * 
 * @author Toni Ginard
 * @param string $text The content to be cleaned
 * @return string The content cleaned
 */
function clean_onerror_attribute($text) {
    if (strpos($text, 'onerror')) {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true); // Don't show warnings due to malformed HTML code
        $dom->loadHTML($text);
        $toRemove = '';
        // Get the contents of all the img elements
        foreach ($dom->getElementsByTagName('img') as $node) {
            $dom->saveHtml($node);
            if ($node->hasAttribute('onerror')) {
                $toRemove[] = $node->getAttribute('onerror');
            }
        }
        // Remove the content of the onerror attribute
        $text = str_replace($toRemove, '', $text);
    }
    return $text;
}

// Clean onerror attribute in widget text
add_filter('widget_text', 'clean_onerror_attribute');
// Clean onerror attribute in all the posts (pages, articles, ...)
add_filter('the_content', 'clean_onerror_attribute');

//Get multiple categories and filter posts from URL
//Solution taken from:
// http://wordpress.stackexchange.com/questions/27158/wordpress-multiple-category-search
add_action( 'parse_request', 'multiple_categories', 11);
function multiple_categories( $query ) {

    if (!isset($query->query_vars['category_name'])) {
        return $query;
    }

    $and = strpos($query->query_vars['category_name'], '+');
    $or = strpos($query->query_vars['category_name'], ',');
    if ($and === false && $or === false) {
        return $query;
    }

    // split cat query on a space to get IDs separated by '+' in URL
    $cats = explode('/', $query->query_vars['category_name']);
    $subcat = array_pop($cats);
    if ($and) {
        $subcats = explode('+', $subcat);
    } else {
        $subcats = explode(',', $subcat);
    }

    if (count($subcats) > 1) {
        $maincat = implode('/', $cats);
        if (!empty($maincat)) {
            $maincat .= '/';
        }
        $catids = array();
        foreach ($subcats as $subcat) {
            $cat = get_category_by_path($maincat.$subcat);
            if ($cat) {
                $catids[] = $cat->cat_ID;
            }
        }
        if (!empty($catids)) {
            unset($query->query_vars['category_name']);
            if ($and) {
                $query->query_vars['category__and'] = $catids;
            } else {
                $query->query_vars['category__in'] = $catids;
            }
        }
    }
    return $query;
}

/* Check script label to only allow Gallery.io scripts
 * @author Xavi Nieto
 * @param array $data
 * @return array The $data
 */
function check_content_script_types( $data ) {

    global $current_user;

    // Check user role is contributor
    if ( in_array( 'contributor', $current_user->roles ) ) {

        $scriptError = false;
        $dataClean = $data;
        $contentPostForCheckScriptTypes = $data['post_content'];

        // split to array. delimiter "/<script/"
        $searchScript = preg_split("/<script/",$contentPostForCheckScriptTypes);

        if ( count( $searchScript ) > 1 ) {
            for ( $i = 1; $i < count( $searchScript ); $i++ ) {

                // Initialize flags
                $searchScriptGallery;
                $searchScriptGalleryJQuery;

                $searchScriptGallery = preg_match_all("/\sGalleryLoaded\s/",$searchScript[$i],$matches); // Check gallery.io script

                // Check return 2 matches exactly
                if ( count( $matches[0]) != 2 ) {
                    $searchScriptGallery = 0;
                }

                $searchScriptGalleryJQuery = preg_match('/(http\b|https\b)*:\/\/cdn.jsdelivr.net\/jquery/',$searchScript[$i]); // Check jQuery script to cdn.jsdelivr.net*/

                if ( $searchScriptGallery == 0 && $searchScriptGalleryJQuery == 0 ){
                    $scriptError = true;

                    // Search close label script
                    $searchScriptResult = explode('</script>',$searchScript[$i]);
                    if ( count ( $searchScriptResult ) > 1 ) {
                        $dataClean['post_content'] = str_replace('<script'.$searchScriptResult[0].'</script>','',$dataClean['post_content']); // Delete code script not valid into scripts label
                    } else {
                        $dataClean['post_content'] = str_replace('<script'.$searchScript[$i],'',$dataClean['post_content']); // Delete code script not valid
                    }
                }
            }
        }

        if( $scriptError === true ){
            // If we found error return $dataClean ($data modify)
            return $dataClean;
        } else {
            // If we not found error return original $data
            return $data;
        }
    } else {
        return $data;
    }

}
add_filter( 'wp_insert_post_data', 'check_content_script_types' );

/**
 * Load shortcodes into taxonomy descriptions, like categories or tags
 *
 * @author Xavi Nieto
 */
add_filter( 'term_description', 'shortcode_unautop');
add_filter( 'term_description', 'do_shortcode');

/**
 * Deactivate pingback to avoid attacks to other sites
 *
 * @author Toni Ginard
 */
add_filter( 'xmlrpc_methods', function( $methods ) {
    unset( $methods['pingback.ping'] );
    return $methods;
}, 1 );

/**
 * Add flashvars to valid elements for tinymc
 *
 * @author Xavier Nieto
 */
function add_flashvars_tinymc( $init ) {

    $ext = 'embed[width|height|name|flashvars|src|bgcolor|align|play|loop|quality|allowscriptaccess|type|pluginspage]';

    if ( isset( $init['extended_valid_elements'] ) ) {
        $init['extended_valid_elements'] .= ',' . $ext;
    } else {
        $init['extended_valid_elements'] = $ext;
    }

    return $init;
}
add_filter('tiny_mce_before_init', 'add_flashvars_tinymc');

/**
 * Allow requests to some external hosts (like Gencat) to avoid problems with RSS (for instance)
 *
 * @author Sara Arjona Téllez
 */
function xtec_allowed_external_host( $allow, $host, $url ) {
    if (strpos($host, '.gencat.cat') !== FALSE || strpos($host, '.xtec.cat') !== FALSE || strpos($host, '.edu365.cat') !== FALSE) {
        $allow = true;
    }
    return $allow;
}
add_filter('http_request_host_is_external', 'xtec_allowed_external_host', 10, 3);

// Simple Calendar

/**
 * Hidden some metaboxes to nav-menus
 *
 * @author Xavier Nieto
 */
function remove_nav_menu_metaboxes( $metaboxes ){
    if ( ! is_xtec_super_admin() ) {
        remove_meta_box('add-calendar_category', 'nav-menus', 'side');
    }
}
add_action( 'admin_head-nav-menus.php', 'remove_nav_menu_metaboxes', 10, 1 );

/**
 * Remove recording cookie for posts protected with password
 *
 * @author Sara Arjona Téllez (from http://agora.xtec.cat/moodle/moodle/mod/forum/discuss.php?d=93207)
 */
function set_cookie_expire () {
    return false;
}
add_filter('post_password_expires', 'set_cookie_expire');


/**
 * Disable Self Pingbacks in WordPress
 *
 * @author Nacho Abejaro
 */
function no_self_ping( &$links ) {
    $home = get_option( 'home' );
    foreach ( $links as $l => $link )
        if ( 0 === strpos( $link, $home ) )
            unset($links[$l]);
}

add_action( 'pre_ping', 'no_self_ping' );

/**
 * Disable checkbox Trackbacks and Pingbacks into article
 *
 * @author Nacho Abejaro
 */
add_action( 'admin_menu', 'remove_discussion_meta_box' );
add_action( 'add_meta_boxes', 'add_custom_discussion_meta_box' );

function remove_discussion_meta_box() {
    if (!is_xtec_super_admin()) {
        remove_meta_box('commentstatusdiv', 'post', 'normal');
    }
}

function add_custom_discussion_meta_box() {
    add_meta_box(
        'custom_discussion',
        __( 'Discussion' ),
        'custom_discussion_meta_box',
        'post'
    );
}

function custom_discussion_meta_box($post) {
    ?>
    <input name="advanced_view" type="hidden" value="1" />
    <p class="meta-options">
        <label for="comment_status" class="selectit">
            <input name="comment_status" type="checkbox" id="comment_status"
                   value="open" <?php checked($post->comment_status, 'open'); ?> />
            <?php _e( 'Allow comments.' ) ?>
        </label>

        <input name="ping_status" type="hidden" id="ping_status" value="closed" />

        <?php do_action('post_comment_status_meta_box-options', $post); ?>
    </p>
    <?php
}


/* Add field to select order posts into categories and change order to show posts pages
 * @author Xavi Nieto
 */

// Add custom field to select order posts into category
function cat_sort($tag){

    $term_id = $tag->term_id;
    $cat_meta = get_option( "category_$term_id");

?>
    <table class="form-table">
        <tr class="form-field">
            <th scope="row" valign="top"><label for="sort"><?php _e('Posts order', 'common-functions'); ?></label></th>
            <td>
            <select name="cat_sort">
                <option value="DESC" <?php if( $cat_meta['sort_posts'] == 'DESC' ){ ?> selected <?php } ?>><?php _e('Newest first','common-functions') ?></option>
                <option value="ASC" <?php if( $cat_meta['sort_posts'] == 'ASC' ){ ?> selected <?php } ?>><?php _e('Oldest first','common-functions') ?></option>
            </select>
            </td>
        </tr>
    </table>
    <?php
}

add_filter('edit_category_form_fields', 'cat_sort');
add_filter('edit_tag_form_fields', 'cat_sort');

// Save option into category
function save_sort_category_field($term_id){
    if ( isset( $_POST['cat_sort'] ) ) {
        $cat_meta = get_option( "category_$term_id");
        $cat_meta['sort_posts'] = $_POST['cat_sort'];
        update_option( "category_$term_id", $cat_meta );
    }
}
add_action ( 'edited_category', 'save_sort_category_field');

// Get option order to customize posts
function get_category_name_page($query){
    if ( $query->query_vars['category_name'] ) {
        $category_slug = array_pop(explode('/',$query->query_vars['category_name']));
        $cat_ID = get_category_by_slug($category_slug)->term_id;
        $cat_meta = get_option( "category_$cat_ID");
        $_SESSION['xtec_category'] = $cat_meta['sort_posts'];
        add_action( 'pre_get_posts', 'change_order_post' );
    } else if( is_xtecblocs() ) {
        $_SESSION['xtec_category'] = get_option('xtec_order_posts');
        add_action( 'pre_get_posts', 'change_order_post' );
    }
}
add_action( 'parse_request', 'get_category_name_page' );

// Change order to show posts
function change_order_post( $query ){
    if ( $_SESSION['xtec_category'] == 'ASC' ){
        $query->set('order', 'ASC');
    } else {
        $query->set('order', 'DESC');
    }
}

// Check is a homepage and get order to home category selected into 'reactor_options'
function using_front_page_conditional_tag() {
    if ( is_front_page() and ! is_xtecblocs() ) {
        $reactor_options = get_option('reactor_options');
        $cat_ID = $reactor_options['frontpage_post_category'];
        if( !isset($cat_ID) or $cat_ID == '-1'){
            $_SESSION['xtec_category'] = get_option('xtec_order_posts');
        } else {
            $cat_meta = get_option( "category_$cat_ID");
            $_SESSION['xtec_category'] = $cat_meta['sort_posts'];
        }
        add_action( 'pre_get_posts', 'change_order_post' );
    }
}
add_action( 'loop_start', 'using_front_page_conditional_tag' );

/* Admin init */
add_action( 'admin_init', 'my_settings_init' );

/* Settings Init */
function my_settings_init(){
    /* Register Settings */
    register_setting(
        'reading',               // Options group
        'xtec_order_posts',      // Option name/database
        'order_posts_settings_sanitize' // sanitize callback function
    );

    /* Create settings section */
    add_settings_section(
        'xtec_home_order_post',            // Section ID
        '',                                // Section title
        '', // Section callback function
        'reading'                          // Settings page slug
    );

    /* Create settings field */
    add_settings_field(
        'my-settings-field-id',       // Field ID
        __('Posts order into home page','common-functions'),       // Field title
        'order_posts_field_callback', // Field callback function
        'reading',                    // Settings page slug
        'xtec_home_order_post'        // Section ID
    );
}

/* Sanitize Callback Function */
function order_posts_settings_sanitize( $input ){
    return ( $input == 'ASC' ) ? 'ASC' : 'DESC';
}

/* Settings Field Callback */
function order_posts_field_callback(){
    ?>
    <label for="droid-identification">
        <select id="my-settings-field-id" name="xtec_order_posts">
            <option value="DESC" <?php if( get_option('xtec_order_posts') == 'DESC' ){ ?> selected <?php } ?>><?php _e('Newest first','common-functions') ?></option>
            <option value="ASC" <?php if( get_option('xtec_order_posts') == 'ASC' ){ ?> selected <?php } ?>><?php _e('Oldest first','common-functions') ?></option>
        </select>
    </label>
    <?php
}

// END: Add field to select order posts into categories and change order to show posts pages
