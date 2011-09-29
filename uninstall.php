<?php
//If uninstall not called from WordPress exit
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

$theme_settings = get_option( 'genesis-settings' );

foreach ($theme_settings as $setting => $data) {
	
	if ( $setting == 'gh_custom_hooks' )
		unset( $theme_settings[ $setting ] );
		
}

?>