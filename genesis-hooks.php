<?php

/*
Plugin Name: Genesis Hooks
Plugin URI: http://www.wpsmith.net/genesis-hooks
Description: Automatically displays Genesis structual hook names in the browser for all pages.
Version: 0.4.1
Author: Travis Smith & Rafal Tomal
Author URI: http://www.wpsmith.net/
License: GPLv2

    Copyright 2011  Travis Smith  (email : travis@wpsmith.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * The translation domain for __() and _e().
 */
define( 'GH_DOMAIN' , 'genesis-hooks' );

/* Prevent direct access to the plugin */
if ( !defined( 'ABSPATH' ) ) {
    wp_die( __( "Sorry, you are not allowed to access this page directly.", GCB_DOMAIN ) );
}

register_activation_hook( __FILE__, 'gh_activation_check' );

/**
 * Checks for minimum Genesis Theme version before allowing plugin to activate
 *
 * @author Nathan Rice
 * @since 0.1
 * @version 0.2
 */
function gh_activation_check() {

    $latest = '1.7';

    $theme_info = get_theme_data( TEMPLATEPATH . '/style.css' );

    if ( basename( TEMPLATEPATH ) != 'genesis' ) {
        deactivate_plugins( plugin_basename( __FILE__ ) ); // Deactivate ourself
        wp_die( sprintf( __( 'Sorry, you can\'t activate unless you have installed and actived %1$sGenesis%2$s or a %3$sGenesis Child Theme%2$s', GCB_DOMAIN ), '<a href="http://wpsmith.net/go/genesis">', '</a>', '<a href="http://wpsmith.net/go/spthemes">' ) );
    }
	
	$theme_info = get_theme_data(TEMPLATEPATH.'/style.css');
	
	if( basename( TEMPLATEPATH ) != 'genesis' ) {
		deactivate_plugins(plugin_basename(__FILE__)); // Deactivate ourself
		wp_die('Sorry, you can\'t activate unless you have installed <a href="http://wpsmith.net/go/genesis">Genesis</a>');
	}

	if ( version_compare( $theme_info['Version'], $latest, '<' ) ) {
		deactivate_plugins(plugin_basename(__FILE__)); // Deactivate ourself
		wp_die( sprintf( __( 'Sorry, you can\'t activate without %1$sGenesis %2$s%3$s or greater', GCB_DOMAIN ), '<a href="http://wpsmith.net/go/genesis">', $latest, '</a>' ) );
	}
	
	gh_update_check();
}

//	add "Settings" link to plugin page
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__) , 'gh_action_links' );
function gh_action_links( $links ) {
	$gh_settings_link = sprintf( '<a href="%s">%s</a>' , admin_url( 'admin.php?page=genesis' ) , __( 'Settings' ) );
	array_unshift( $links, $gh_settings_link );
	return $links;
}


function gh_update_check() {
	$options = get_option ( GENESIS_SETTINGS_FIELD );
	if ( ! $options ['gh_role'] ) {
		$options ['gh_role'] = 'administrator';
		update_option ( GENESIS_SETTINGS_FIELD , $options );
	}
}

/*
 * Sets defaults in the Genesis Theme Settings
 */
add_filter( 'genesis_theme_settings_defaults', 'genesis_hooks_defaults' );
function genesis_hooks_defaults ( $defaults ) {
	$defaults = array(
		'gh_custom_hooks' => '',
		'gh_role' => 'administrator',
	);
	
	return $defaults;
}

add_action( 'admin_menu', 'gh_theme_settings_init', 15 );
/**
 * This is a necessary go-between to get our scripts and boxes loaded
 * on the theme settings page only, and not the rest of the admin
 */
function gh_theme_settings_init () {
    global $_genesis_theme_settings_pagehook;

    add_action( 'load-' . $_genesis_theme_settings_pagehook, 'gh_theme_settings_boxes' );
} 

/**
 * Adds a Genesis Featured Images Metabox to Genesis > Theme Settings
 * 
 */
function gh_theme_settings_boxes () {
    global $_genesis_theme_settings_pagehook;

    add_meta_box( 'genesis-theme-settings-hooks' , __( 'Genesis Hooks Settings', GH_DOMAIN ), 'genesis_theme_settings_hooks_box' , $_genesis_theme_settings_pagehook , 'column2' );
}



function genesis_theme_settings_hooks_box () { ?>
	<p></p>
	
	<p><label for="<?php echo GENESIS_SETTINGS_FIELD; ?>[gh_custom_hooks]"><?php _e( 'Enter your custom hooks here using a comma to separate hooks.', GH_DOMAIN ); ?></label></p>
	<input type="text" size="100" name="<?php echo GENESIS_SETTINGS_FIELD; ?>[gh_custom_hooks]" id="gh_custom_hooks" value="<?php echo ( genesis_get_option('gh_custom_hooks' ) ) ? esc_attr( genesis_get_option( 'gh_custom_hooks' ) ) : ''; ?>" />

	<p><span class="description"><?php printf( __( '(e.g., genesis_home,genesis_before).', GH_DOMAIN ) ); ?></span></p>
	
	<p><label for="<?php echo GENESIS_SETTINGS_FIELD; ?>[gh_custom_hooks]"><?php _e( 'Select the appropriate role level to view the hooks.', GH_DOMAIN ); ?></label></p>
	<p><select name="<?php echo GENESIS_SETTINGS_FIELD; ?>[gh_role]" id="gh_role">
		<?php wp_dropdown_roles( genesis_get_option('gh_role' ) ); ?>
	</select></p>
	<p><span class="description"><?php printf( __( '(e.g., If you select Editor, only those who can edit_others_posts (an Editor capability) will be able to see the hooks. This defaults to administrators.).', GH_DOMAIN ) ); ?></span></p>
	
<?php
}

function gh_get_cap( $role ) {
	$cap = array ( 
		'administrator' => 'manage_options',
		'editor' => 'edit_others_posts',
		'author' => 'edit_published_posts',
		'contributor' => 'edit_posts',
		'subscriber' => 'read',
	);
	
	return $cap[$role];
}


// set styles
add_action( 'wp_print_styles' , 'genesis_hooks_css' );
function genesis_hooks_css () {
	echo '
		<style>
			span.genesis_hook { font-size:11px; font-family:Arial; margin:5px; padding:2px 10px; background:red; color:white; position:absolute; clear:both; text-decoration:none; border:1px solid white; filter:alpha(opacity=60); -moz-opacity:0.6; -khtml-opacity: 0.6; opacity: 0.6; }
			span:hover.genesis_hook { background:gray; text-decoration:none; z-index:100; filter:alpha(opacity=100); -moz-opacity:1.0; -khtml-opacity:1.0; opacity:1.0; }
		</style>
	';
}


genesis_hooks_setup();
function genesis_hooks_setup () {
	// Genesis actions
	$arrGenesisActions = array(	'genesis_home',
								'genesis_before_',
								'genesis_before_header',
								'genesis_header',
								'genesis_header_right',
								'genesis_site_title',
								'genesis_site_description',
								'genesis_after_header',
								'genesis_before_content_sidebar_wrap',
								'genesis_before_content',
								'genesis_before_loop',
								'genesis_loop',
								'genesis_before_post',
								'genesis_before_post_title',
								'genesis_post_title',
								'genesis_after_post_title',
								'genesis_before_post_content',
								'genesis_post_content',
								'genesis_after_post_content',
								'genesis_after_post',
								'genesis_before_comments',
								'genesis_list_comments',
								'genesis_before_comment',
								'genesis_comment',
								'genesis_after_comment',
								'genesis_after_comments',
								'genesis_before_pings',
								'genesis_list_pings',
								'genesis_after_pings',
								'genesis_before_respond',
								'genesis_before_comment_form',
								'genesis_comment_form',
								'genesis_after_comment_form',
								'genesis_after_respond',
								'genesis_after_endwhile',
								'genesis_loop_else',
								'genesis_after_loop',
								'genesis_after_content',
								'genesis_before_sidebar_widget_area',
								'genesis_after_sidebar_widget_area',
								'genesis_after_content_sidebar_wrap',
								'genesis_before_sidebar_alt_widget_area',
								'genesis_after_sidebar_alt_widget_area',
								'genesis_before_footer',
								'genesis_footer',
								'genesis_after_footer',
								'genesis_after',
								
								// Add Genesis Featured Widget Amplified Hooks
								'gfwa_before_loop',
								'gfwa_before_post_content',
								'gfwa_post_content',
								'gfwa_after_post_content',
								'gfwa_endwhile',
								'gfwa_after_loop',
								'gfwa_list_items',
								'gfwa_print_list_items',
								'gfwa_category_more',
								'gfwa_after_category_more',
								'gfwa_form_first_column',
								'gfwa_form_second_column',
								
								//Add WordPress Comment Hooks
								'comment_form_before',
								'comment_form_must_log_in_after',
								'comment_form_top',
								'comment_form_logged_in_after',
								'comment_form_before_fields',
								'comment_form_after_fields',
								'comment_form',
								'comment_form_after',
								'comment_form_comments_closed',
							);
							
	$genesis_settings = get_option( 'genesis-settings' );
	$custom_genesis_hooks = explode( ',' , $genesis_settings[ 'gh_custom_hooks' ] );
	$genesis_hooks = array_merge( $arrGenesisActions , $custom_genesis_hooks );
	
								
	foreach ( $genesis_hooks as $action ) {
		add_action( $action , 'genesis_hooks' , 1 );
	}
}

function genesis_hooks () {
	if ( ( ! is_user_logged_in() ) || ( ! current_user_can( gh_get_cap( genesis_get_option('gh_role' ) ) ) ) )
		return;
	$current_action = current_filter ();
	echo '<span class="genesis_hook">' . $current_action . '</span>';
}
?>