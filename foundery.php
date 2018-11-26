<?php
/*
Plugin name: The Foundery Co-Work Booking System.
Plugin URI: http://www.websolutions.to
Description: An boardroom and reserved space booking system for co-working members initally created for The Foundery Toronto; The Foundery was the original co-work space in Toronto Canada. This software has been released now one year after the closing of The Foundery.
Author: Nick Andrews: Onsite Community Co-Ordinator 2016-2017, The Foundery, Toronto Canada.
Tags: Co-working Toronto, Co-Work Reservation Booking System, The Foundery Toronto.
Date: 2018-11-31


Initiate database tables required to run the Foundery Booking System. 
 * 
 */
add_action( 'get_header', 'fndry_init' );
function fndry_init() {
	global $wpdb;
	$nu_table = $wpdb->prefix."FNDRY_bookusers";
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$nu_table'") != $nu_table  ) {
		$sql = "CREATE TABLE IF NOT EXISTS " .$nu_table ."  (
			userID bigint(20) NOT NULL AUTO_INCREMENT,
  			fbID varchar(30) ,
  			wpID bigint(20) ,
  			inID varchar( 30),
  			email varchar(200),
  			maxseg tinyint(3 ),
			log timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			status enum('hotdesk','dedicated','parttime','office','guest','staff','event','banned')NOT NULL,
			name varchar(200) NOT NULL,
			UNIQUE KEY id (userID)
		)";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}	
	$nu_table = $wpdb->prefix."FNDRY_bookmeta";
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$nu_table'") != $nu_table  ) {
		$sql = "CREATE TABLE IF NOT EXISTS " .$nu_table ."  (
			userID bigint(20),
			log timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			roomID  int(3),
			segment DATETIME NOT NULL,
			UNIQUE KEY id (segment, roomID)
		)";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	$nu_table = $wpdb->prefix."FNDRY_bookmsg";
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$nu_table'") != $nu_table  ) {
		$sql = "CREATE TABLE IF NOT EXISTS " .$nu_table ."  (
			userID bigint(20) NOT NULL ,
			log timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			roomID  int(3),
			msg mediumtext,
			rd int(1) NOT NULL,
			UNIQUE KEY id (userID)
		)";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	$nu_table = $wpdb->prefix."FNDRY_bookroom";
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$nu_table'") != $nu_table  ) {
		$sql = "CREATE TABLE IF NOT EXISTS " .$nu_table ."  (
			roomID bigint(20) NOT NULL AUTO_INCREMENT,
			name varchar( 100) NOT NULL,
			description  mediumtext,
			address varchar( 300),
			images mediumblob,
			facilities mediumblob,
			UNIQUE KEY id (roomID)
		)";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}
//*********************// 
// FOUNDERY CORE CLASS //
//*********************// 
$fndry;
define( 'FNDRY_PATH', plugin_dir_url( __FILE__ )  );
@include("classes/class-core.php");
$fndry = new foundery\core();

add_action('init', 'foundery_header_scripts');
function foundery_header_scripts()
{
    if ($GLOBALS['pagenow'] != 'wp-login.php' && !is_admin()) {
        wp_register_script('founderycore', FNDRY_PATH.'js/core.js', array('jquery'), '1.0.0');
        wp_enqueue_script('founderycore'); 
		wp_register_style( 'foundery-style', FNDRY_PATH. 'css/style.css' , false, NULL);
	    wp_enqueue_style( 'foundery-style' );
	}
}
/**
 * catch the Ajax requests for booking calendar access
 * @return [type] [description]
 */
add_action('init', 'process_feeder', 9999999);
function process_feeder(){  
    global $fndry;
    global $current_user;
    if( (isset ( $_GET['fbook']))&&(preg_match( $fndry::REGEXP_INT, $_GET['fbook']))){  
        $fndry->catch_booking();
        exit;
    }else  if( (isset ( $_GET['fcancel']))&&(preg_match( $fndry::REGEXP_INT, $_GET['fcancel']))){ 
        $fndry->cancel_booking();
        exit;
    } else if ( (isset ( $_GET['fmsg']))&&(preg_match( $fndry::REGEXP_INT, $_GET['fmsg']))){
        $fndry->msg_read();
        exit;
    }else  if( (isset ( $_GET['ffeed']))&&(preg_match( $fndry::REGEXP_INT, $_GET['ffeed']))){ 
        $ts = intval($_GET['ffeed']/1000);
        $date = new DateTime("@$ts");
        if ( isset ($_GET['w'])){///wp logged
            $fndry->feed_bookings($date->format('Y-m-d 00:00:00'), $_GET['room'], $_GET['w'], $_GET['n']);
        }else if ( isset ($_GET['i'])){  ///ln logged
            $fndry->feed_bookings($date->format('Y-m-d 00:00:00'), $_GET['room'], $_GET['i'], $_GET['n']);
        }else{ ///fb logged
            $fndry->feed_bookings($date->format('Y-m-d 00:00:00'), $_GET['room'], $_GET['f'], $_GET['n']);
        }
        exit;
    }else  if( (isset ( $_GET['nfeed']))&&(filter_var($_GET['nfeed'], FILTER_VALIDATE_EMAIL))){
        $fndry->get_nonce( $_GET['nfeed'] );
        exit;
    }else  if( (isset ( $_GET['sfeed']))&&(filter_var($_GET['sfeed'], FILTER_VALIDATE_EMAIL))){
         $fndry->get_status( $_GET['sfeed'] );
        exit;
    } 
}

add_action('wp_print_footer_scripts', 'set_up_footer');	
function set_up_footer(){
	global $fndry ;
	$rooms = $fndry->get_about_rooms();
	$times = get_option('fndry-time-params');
	$user = wp_get_current_user();
	if ( $user->ID ){
?>
<script>
    var wrap = document.createElement("div");
    wrap.setAttribute("id","fndry-booking");
    document.body.appendChild(wrap);
    var icon = document.createElement("div");
    icon.setAttribute("id","fndry_launcher");
    document.body.appendChild(icon);
    jQuery(icon).click(function(){
        if(jQuery(this).hasClass("fndry-open") ){
            jQuery(this).removeClass("fndry-open");
            BK.onoff = false; 
            BK.turn_off();
            jQuery("#fndry-booking").hide();
            jQuery("#lightbox_wrap").hide();
        }else{
            BK.onoff = true; 
            BK.setnow();
            BK.week_layout();
            BK.get_calendar_bookings();
            jQuery(this).addClass("fndry-open");
            jQuery("#fndry-booking").show();
        }
    });
    var fblogged = true;
    BK.wpid = <?php echo $user->ID; ?>;
    var temp= JSON.parse('<?php echo $times; ?>' );
    var hour = temp.strt.split(":")
    BK.amstart = parseInt( hour[0] ) 
    BK.amstart += parseFloat( hour[1]/60 ) 
    BK.futures = parseInt( temp.fweeks);
    BK.segment = parseInt( temp.segment ) ;
    var segs = temp.duration.split(":");
    BK.segments_day = (segs[0]*60 )/BK.segment;
    BK.segments_day += (segs[1] )/BK.segment;
    BK.myname = '<?php echo $user->user_nicename; ?>';
    BK.myemail = '<?php echo $user->user_email; ?>';
    BK.get_me_nonce();
    BK.setnow(false);
<?php 
	if(  $rooms){
?>
setTimeout( function(){
<?php
	foreach( $rooms as $ind =>$obj){
	?>
    BK.rooms.push( new ROOMR('<?php echo $obj->roomID; ?>', 
    '<?php echo $obj->name;?>',  '<?php echo $obj->description;?>', '<?php echo $obj->address;?>' ));
	<?php
	} 
	?>
    BK.week_layout();
    BK.dynamise_active_segments();
    BK.turn_off();
}, 500);
<?php
	}
?></script>
<?php
	}
}
add_action('admin_init', 'fndry_admin_scripts');
function fndry_admin_scripts(){
		wp_register_style( 'foundery-style-adm', FNDRY_PATH. 'css/foundery-style-adm.css' , false, NULL);
	    wp_enqueue_style( 'foundery-style-adm' );

	    wp_register_script('admcore', FNDRY_PATH.'js/adm-core.js', array('jquery'), '1.0.0'); 
		wp_enqueue_script('admcore'); 
}

add_action( 'admin_menu', 'fndry_admin_menu' );
function fndry_admin_menu(){
    wp_enqueue_media();
    $icon = FNDRY_PATH."css/img/fire-tiny.png";
    $page0 = add_menu_page ( 'Admsetup', 'The Foundery', 'manage_options', 'setuppr', 
    	'fndry_setup',$icon,'4');
   	$page1 = add_submenu_page('setuppr', 'User Permisssions', 'Foundery Perms', 'manage_options', 
   	'fndryperms', 'book_users');
    $page2 = add_submenu_page ( 'setuppr', 'Foundery Admins', 'Foundery Logs', 'manage_options', 'fndrylogs', 'fndry_logs');
}
function fndry_setup(){
  @include("classes/class-setup-room.php");
}
function fndry_logs(){
  @include("classes/class-fndry-logs.php");
}
function book_users(){
    @include("classes/class-book-users.php");
}
?>