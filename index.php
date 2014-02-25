<?php
/*
Plugin Name: Activation tester
Description: Run some tests before activate this plugin
Author: Julio Potier
Author URI: http://boiteaweb.fr
Version: 1.0
Last Update: 25 fev. 14
*/

add_action( 'admin_init', 'my_plugin_check_version' );
function my_plugin_check_version()
{
	// This is where you set you needs
	$mandatory = array(	'PluginName'=>'Activation tester v1.0', 
						'WordPress'=>'10.0', 
						'PHP'=>'10.0', 
						'MySQL'=>'10.0', 
						'Function exists'=>'str_unknow', 
						'Class exists'=>'UnKnownClass', 
						'Module active'=>'mod_unknow', 
						'Plugin active'=>'unknow-plugin-slug' 
					);

	// Avoid Notice error
	$errors = array();

	// loop the mandatory things
	foreach( $mandatory as $what => $how ) {
		switch( $what ) {
			case 'WordPress':
					if( version_compare( $GLOBALS['wp_version'], $how ) < 0 )
					{
						$errors[$what] = $how;
					}
				break;
			case 'PHP':
					if( version_compare( phpversion(), $how ) < 0 )
					{
						$errors[$what] = $how;
					}
				break;
			case 'MySQL':
					if( version_compare( mysql_get_server_info(), $how ) < 0 )
					{
						$errors[$what] = $how;
					}
				break;
			case 'Function exists':
					if( !function_exists( $how ) )
					{
						$errors[$what] = $how;
					}
				break;
			case 'Class exists':
					if( !class_exists( $how ) )
					{
						$errors[$what] = $how;
					}
				break;
			case 'Module active':
					if( !function_exists( 'apache_get_modules' ) || in_array( $how, apache_get_modules() ) )
					{
						$errors[$what] = $how;
					}
				break;
			case 'Plugin active':
					if( !is_plugin_active( $how ) )
					{
						$errors[$what] = $how;
					}
				break;
		}
	}

	// Add a filter for devs
	$errors = apply_filters( 'validate_errors', $errors, $mandatory['PluginName'] );

	// We got errors!
	if( !empty( $errors ) )
	{
		global $current_user;

		// We add the plugin name for late use
		$errors['PluginName'] = $mandatory['PluginName'];

		// Set a transient with these errors
		set_transient( 'myplugin_disabled_notice' . $current_user->ID, $errors );

		// Remove the activate flag
        unset( $_GET['activate'] );

        // Deactivate this plugin
    	deactivate_plugins( plugin_basename( __FILE__ ) );
	}
}

add_action( 'admin_notices', 'myplugin_disabled_notice' );
function myplugin_disabled_notice()
{
	global $current_user;
	// We got errors!
	if( $errors = get_transient( 'myplugin_disabled_notice' . $current_user->ID ) )
	{
		// Remove the transient
		delete_transient( 'myplugin_disabled_notice' . $current_user->ID );

		// Pop the plugin name
		$plugin_name = array_pop( $errors );

		// Begin the buffer output
		$error = '<ul>';

		// Loop on each error, you can change the "i18n domain" here -> my_plugin (i would like to avoid this)
		foreach( $errors as $what => $how) {
			$error .= '<li>'.sprintf( __( '&middot; Requires %s: <code>%s</code>', 'my_plugin' ), $what, $how ).'</li>';
		}
		
		// End the buffer output
		$error .= '</ul>';

		// Echo the output using a WordPress string (no i18n needed)
		echo '<div class="error"><p>' . sprintf( __( 'The plugin <code>%s</code> has been <strong>deactivated</strong> due to an error: %s' ), $plugin_name, $error ) . '</p></div>';
	}
}