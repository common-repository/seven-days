<?php
/*
Plugin Name: Seven Days
Plugin URI: http://wegrass.com/playground/seven-days/
Description: Display or hide widgets by day condition.
Author: Wegrass Interactive
Version: 0.2.2
Author URI: http://wegrass.com
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! defined( 'SEVEN_DAYS_PLUGIN_DIR' ) )
	define( 'SEVEN_DAYS_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . plugin_basename( dirname( __FILE__ ) ) );
	
if ( ! defined( 'SEVEN_DAYS_PLUGIN_URL' ) )
	define( 'SEVEN_DAYS_PLUGIN_URL', WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) );
	
if ( ! defined( 'SEVEN_DAYS_PLUGIN_BASENAME' ) )
	define( 'SEVEN_DAYS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
	
if ( ! defined( 'SEVEN_DAYS_PLUGIN_DIR_NAME' ) )
	define( 'SEVEN_DAYS_PLUGIN_DIR_NAME', trim( dirname( SEVEN_DAYS_PLUGIN_BASENAME ), '/' ) );

if ( ! defined( 'SEVEN_DAYS_OPTION' ) )
	define('SEVEN_DAYS_OPTION', 'seven_days_option');

if ( ! defined( 'SEVEN_DAYS_SUN' ) )
	define( 'SEVEN_DAYS_SUN', 0 );

if ( ! defined( 'SEVEN_DAYS_MON' ) )
	define( 'SEVEN_DAYS_MON', 1 );

if ( ! defined( 'SEVEN_DAYS_TUE' ) )
	define( 'SEVEN_DAYS_TUE', 2 );

if ( ! defined( 'SEVEN_DAYS_WED' ) )
	define( 'SEVEN_DAYS_WED', 3 );

if ( ! defined( 'SEVEN_DAYS_THU' ) )
	define( 'SEVEN_DAYS_THU', 4 );

if ( ! defined( 'SEVEN_DAYS_FRI' ) )
	define( 'SEVEN_DAYS_FRI', 5 );

if ( ! defined( 'SEVEN_DAYS_SAT' ) )
	define( 'SEVEN_DAYS_SAT', 6 );


register_uninstall_hook( __FILE__, 'uninstall_seven_days_callback' );
function uninstall_seven_days_callback(){
	delete_option( SEVEN_DAYS_OPTION );
}

add_action( 'sidebar_admin_setup', 'seven_days_expand_control' );
function seven_days_expand_control()
{
	global $wp_registered_widgets,$wp_registered_widget_controls;

	foreach ( $wp_registered_widgets as $id => $widget )
	{
		//check registered widget.
		if ( ! $wp_registered_widget_controls[$id] )
		wp_register_widget_control( $id, $widget['name'], 'seven_days_empty_control' );
		
		if (!array_key_exists( 0, $wp_registered_widget_controls[$id]['params'] ) || is_array( $wp_registered_widget_controls[$id]['params'][0] ) )
			$wp_registered_widget_controls[$id]['params'][0]['id_for_sd'] = $id;
		
		$wp_registered_widget_controls[$id]['callback_sd_redirect'] = $wp_registered_widget_controls[$id]['callback'];
		$wp_registered_widget_controls[$id]['callback'] = 'seven_days_extra_control';

	}
}

function seven_days_empty_control() {}

function seven_days_extra_control()
{
	global $wp_registered_widget_controls;
	$params = func_get_args();
	
	
	$id = ( is_array($params[0] ) ) ? $params[0]['id_for_sd'] : array_pop($params);
	$id_disp = $id;
	
	$callback=$wp_registered_widget_controls[$id]['callback_sd_redirect'];
	if ( is_callable( $callback ) )
		call_user_func_array( $callback, $params );
	
	
	if ( is_array($params[0] ) && isset( $params[0]['number'] ) ) $number=$params[0]['number'];
	if ( $number == -1 ) { $number = "%i%"; }
	if ( isset( $number ) ) $id_disp=$wp_registered_widget_controls[$id]['id_base'].'-'.$number;
	
	for ( $i=0; $i<=6; $i++ ) $seven_days_checked[$i] = ( show_seven_days( $id_disp,$i ) ) ? "checked" : "";
	
	echo "<div class=\"seven_days_checkbox\">";
	for ( $i=0; $i<=6; $i++ ){
		echo '<span class="checkbox '.$seven_days_checked[$i].'"></span>';
		echo '<input type="checkbox" class="hide" value="'.$i.'" '.$seven_days_checked[$i].'/>';
	}
	echo "</div><div style='clear:both;'></div>";
}

add_action( 'wp_head', 'seven_days_redirect_callback' );
function seven_days_redirect_callback(){
	global $wp_registered_widgets;
	
	foreach( $wp_registered_widgets as $id => $widget ){
		if( empty( $wp_registered_widgets[$id]['callback_sd_redirect'] ) ){
		    array_push( $wp_registered_widgets[$id]['params'], $id );
			$wp_registered_widgets[$id]['callback_sd_redirect'] = $wp_registered_widgets[$id]['callback'];
			$wp_registered_widgets[$id]['callback'] = 'seven_days_redirected_callback';
		}
	}
}

function seven_days_redirected_callback(){
	global $wp_registered_widgets;
	
	$params = func_get_args();
	$id = array_pop($params);
	$callback = $wp_registered_widgets[$id]['callback_sd_redirect'];
	
	$sd_options = get_option( SEVEN_DAYS_OPTION );
	
	$sd_value = "return TRUE;";
	
	if( ! empty( $sd_options[$id] ) ){
	    $today = getdate();
	
		if( ! show_seven_days($id, $today['wday'] ) ){
			$sd_value = "return FALSE;";
		}
	}
	
	$sd_value = ( eval( $sd_value ) && is_callable( $callback ) );
	
	if( $sd_value ){
	    call_user_func_array( $callback, $params );
	}
}

add_action( 'admin_head', 'seven_days_admin_head' );
function seven_days_admin_head() {
?>

<!-- Custom Form Elements -->
<link rel="stylesheet" type="text/css" media="all" href="<?php echo SEVEN_DAYS_PLUGIN_URL; ?>/css/style.css" />

<!-- Admin JS -->
<script type="text/javascript" src="<?php echo SEVEN_DAYS_PLUGIN_URL; ?>/libs/admin.js"></script>

<?php
}


add_action( 'wp_ajax_seven_days_update_action', 'seven_days_ajax_callback' );
function seven_days_ajax_callback() {

	$widget_id = $_POST['wid'];
    $day = $_POST['day'];

    update_seven_days_option( $widget_id, $day );
	die(); // this is required to return a proper result
}


function update_seven_days_option( $widget_id, $option_value ){
    if( ( ! $sd_options = get_option( SEVEN_DAYS_OPTION ) ) || ! is_array( $sd_options ) ) $sd_options = array();

    if( ( $widget_id != "" ) && ( $option_value != "" ) ) {
        $sd_options[$widget_id]['sd_value'] = $option_value;
        update_option( SEVEN_DAYS_OPTION, $sd_options );
    }
}

function get_seven_days_option( $widget_id ){
    $value_return = -1;
    if( ( $sd_options = get_option( SEVEN_DAYS_OPTION ) ) && is_array( $sd_options[$widget_id] ) ){
    	$value_return = $sd_options[$widget_id]['sd_value'];
    }

    return $value_return;
}

function show_seven_days( $widget_id, $day ){
    $seven_days_option = get_seven_days_option( $widget_id );
    if ( $seven_days_option != -1 ){
        $day_option = str_split( $seven_days_option );
        $isShow = FALSE;
        switch ( $day ){
            case SEVEN_DAYS_SUN:
                if( $day_option[0] == '1' ) $isShow = TRUE;
                break;
            case SEVEN_DAYS_MON:
                if( $day_option[1] == '1' ) $isShow = TRUE;
                break;
            case SEVEN_DAYS_TUE:
                if( $day_option[2] == '1' ) $isShow = TRUE;
                break;
            case SEVEN_DAYS_WED:
                if( $day_option[3] == '1' ) $isShow = TRUE;
                break;
            case SEVEN_DAYS_THU:
                if( $day_option[4] == '1' ) $isShow = TRUE;
                break;
            case SEVEN_DAYS_FRI:
                if( $day_option[5] == '1' ) $isShow = TRUE;
                break;
            case SEVEN_DAYS_SAT:
                if( $day_option[6] == '1' ) $isShow = TRUE;
                break;
            default:
        }
    }else{
        $isShow = TRUE;
    }

    return $isShow;
}
?>
