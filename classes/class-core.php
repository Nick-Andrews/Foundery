<?php
namespace foundery;
class core {
	protected		$xml;
	protected		$wp_nonce;
	public			$errors;
	public 			$maxseg = array();
	public 			$segment;
	const  			REGEXP_INT	=  "/^[0-9]+$/i";
	/*
	*Class constructor
	*/ 
	function __construct() {
   		$this->errors = false;
   		$tt = json_decode(  get_option("fndry-time-params ") );
		$this->segment = $tt->segment;
   		if( $tt ){
   			$this->maxseg =  $tt->maxhours;
   		}else{
   			$this->maxseg = 6;
   		}
   	}
	/**
	 * Get users types in Foundery system
	 * @return [object] WP query result
	 */
	function get_user_types(){
		global $wpdb;
		$query = "SELECT DISTINCT status FROM ".$wpdb->prefix."FNDRY_bookusers' ;";
		$res = $wpdb->get_results($query);
		if ( isset($res[0])){
			return $res;
		}
		return false;
	}
	/**
	 * Get all user data from Foundery system user relation for a specific type  
	 * @param  [string] $type, type of user [ 'hottdesk', 'staff'...] ]
	 * @return [object] WP query result
	 */
	function get_users($type){
		global $wpdb;
		$query = "SELECT * FROM ".$wpdb->prefix."FNDRY_bookusers WHERE status='".$type."' ;";
		$res = $wpdb->get_results($query);
		if ( isset($res[0])){
			return $res;
		}
		return false;
	}
   	/**
   	 * Determines users status in booking system
   	 * @param  [string] $email, email address
   	 * @return [boolean]  true or false depending whether they have a Foundery system status 
   	 */
   	function get_status($email){
   		global $wpdb;
   	
		if( is_user_logged_in()){   		
	   		$query = "SELECT status 
	   		FROM ".$wpdb->prefix."FNDRY_bookusers 
	   		WHERE email ='".$email."';";
	   		$res = $wpdb->get_results($query);
	   		if ( isset($res[0])){
				$tme =json_decode(  get_option("fndry-time-params"));
				$temp = $res[0]->status;
		   		if($tme){
		   			$res[] = $tme->maxhours->$temp;
		   		}
				$this->feed_me($res);
				return true;
	   		}
	   		$this->feed_me("null");
			return false;
	   	}
   	}
   	/**
   	 * Creates and returns a wp nonce code via ajax
   	 * @param  [string] $email, email address
	 * @return [boolean]  true or false depending whether they have a nonce code or not
   	 */
	function get_nonce($email){
   		global $wpdb;
		if( is_user_logged_in()){  
	   		$query = "SELECT userID,  wpID FROM ".$wpdb->prefix."FNDRY_bookusers WHERE email ='".$email."';";
	   		$res = $wpdb->get_results($query);
	   		if ( isset($res[0])){
				$nonce = wp_create_nonce( sha1($res[0]->wpID.$res[0]->userID."fndry")  );
				$this->feed_me($nonce);
				return true;
	   		}
	   	}
   		$this->feed_me("null");
		return false;
   	}
   	/**
   	 * Make a booking in the calendar system
   	 * @param  [string] $id , id for segment user is trying to book
   	 * @param  [integer] $userID, user id in booking system
   	 * @param  [integer] $roomID, id of room in booking system
   	 * @return [null] 
   	 */
   	function make_booking($id, $userID, $roomID){
   		global $wpdb;
   		$temp = explode( "-",$id);
		$segment = $temp[1]."-".$temp[2]."-".$temp[3]." ".$temp[4].":".$temp[5].":00";
		$bookr = "INSERT INTO ".$wpdb->prefix."FNDRY_bookmeta (userID,roomID,segment)
		VALUES(".$userID.",".$roomID.",'".$segment."' );";
		$res = $wpdb->query($bookr);
   	}
   	/**
   	 * Deletes a booking entry in the calendar system
   	 * @param  [string] $id , id fo segment user is trying to cancel
   	 * @param  [integer] $userID, user id in booking system
   	 * @param  [integer] $roomID, id of room in booking system
   	 * @return [null] 
   	 */
	function remove_booking($id, $userID, $roomID){
   		global $wpdb;
   		$temp = explode( "-",$id);
		$segment = $temp[0]."-".$temp[1]."-".$temp[2]." ".$temp[4].":".$temp[5].":00";
		$bookr = "DELETE FROM ".$wpdb->prefix."FNDRY_bookmeta WHERE userID='".$userID."'
		AND roomID = '".$roomID."' AND segment = '".$segment."' ";
		$wpdb->query($bookr);
   	}
   	/**
   	 * Count the number of bookings for a user on a specific day
   	 * @param  [string] $id   DOM Object ID string with date values
   	 * @param  [int] $userID Foundery system user id , is NOT the WP id 
   	 * @return [int]   number of bookings for a user on a specific day
   	 */
   	function count_day_books($id, $userID){
   		global $wpdb;
		$temp = explode( "-",$id);
		$segment = $temp[1]."-".$temp[2]."-".$temp[3];
		$totals =  "SELECT COUNT(*) as 'C' FROM ".$wpdb->prefix."FNDRY_bookmeta WHERE userID = '".$userID."' AND 
		segment >= '".$segment."' AND segment < DATE_ADD('".$segment."',INTERVAL 1 DAY);";
		$res = $wpdb->get_results($totals);
		return $res[0]->C;
   	}
   	/**
   	 * Method allows user to cancel a booking in the calendar system if user is valid logged in
   	 * @return [null] 
   	 */
	function cancel_booking(){
		global $wpdb;
   		global $current_user;
		$postdata = file_get_contents("php://input"); 
		$d = json_decode(stripslashes( $postdata) );
		$user = "SELECT userID FROM ".$wpdb->prefix."FNDRY_bookusers 
		WHERE wpID= '".$d->wpid."';";
		$res = $wpdb->get_results($user);
		if ( isset($res[0]) ){ ///have a user we know
			$this->remove_booking( $d->id, $res[0]->userID, $d->roomID);
		}
	}
	/**
	 * Ajax post catch to make a booking is routed depending on user status, number of segments already booked
	 * @return [null]
	 */
   	function catch_booking(){
   		global $wpdb;
   		global $current_user;
		$postdata = file_get_contents("php://input"); 
		$d = json_decode(stripslashes( $postdata) );
		$user = "SELECT userID , status FROM ".$wpdb->prefix."FNDRY_bookusers WHERE wpID= '".$d->wpid."' LIMIT 0,1;";
		$res = $wpdb->get_results($user);
		$status = $res[0]->status;
		$maxsegs =(60 /intval( $this->segment )) * intval( $this->maxseg->$status);
		if ( isset($res[0]) ){ ///have a user we know
			if ($res[0]->status =="prospect" ){
				$msg = "Thank you for logging in. <p><strong>Only members can book a boardroom using the portal.</strong></p> We will be in touch to confirm your details before we make a booking.";
				$this->add_msg($res[0]->userID,$msg );
			}else if ($res[0]->status =="banned" ){
				$msg = "You are not welcome here.";
				$this->add_msg($res[0]->userID,$msg );
			}else if( $this->count_day_books($d->id, $res[0]->userID) < $maxsegs ) {
				$this->make_booking( $d->id, $res[0]->userID, $d->roomID);
			}else{
				$temp = explode( "-",$d->id);
				$day = date('l jS \of F Y', mktime( 0,0,0, $temp[2],$temp[3],$temp[1]));
				$spec = (($this->maxseg->$status > 1)||($this->maxseg->$status == 0))?"s":"";
				$msg = "<em>Your membership entitles you to reserve ". $this->maxseg->$status." hour".$spec." per day.</em> <p>If you need to reserve more time than ". $this->maxseg->$status." hour".$spec." on ".$day.", then please contact the space co-ordinator.&#9786;</p>";
				$this->add_msg($res[0]->userID,$msg );
			}
		}else{///add user then send a message
			if (filter_var($d->myemail, FILTER_VALIDATE_EMAIL)){
				$tt= json_decode(get_option("fndry-time-params"));
				$maxsegs =(60 /intval( $this->segment )) * intval($this->maxseg->hotdesk);
				$query = "INSERT INTO ".$wpdb->prefix."FNDRY_bookusers (userID, name, fbID,wpID,inID, email, status, maxseg)
				VALUES(NULL, '".$d->myname."','".$d->fbid."','".$d->wpid."','".$d->inid."','".$d->myemail."','hotdesk', '".$maxsegs."');";
				$wpdb->query($query);
				$res = $wpdb->get_results($user);
				$msg = "Thank you for logging in. <p><strong>Members can book a boardroom using the portal.</strong></p>";
				$this->add_msg($res[0]->userID,$msg );
			}
		}
   	}
   	/**
   	 * Adds a message into booking system for a specific user
   	 * @param [integer] $id , user id in booking system
   	 * @param [string] $msg, message to user
   	 */
   	function add_msg($id, $msg){
   		global $wpdb;
   		$t = "SELECT rd, msg, userID FROM ".$wpdb->prefix."FNDRY_bookmsg WHERE userID = '".$id."';";
   		$test = $wpdb->get_results($t);
   		if (isset ( $test[0] )){
   			if ( $test[0]->rd == 0 ){
	   			$test2 = $wpdb->get_results($t);
	   			if (isset ( $test2[0] )){	
					return;
	   			}else{
	   				$nu = $test[0]->msg." | ".$msg;
	   				$query = "UPDATE ".$wpdb->prefix."FNDRY_bookmsg SET 
	   				msg='".$nu."' , rd='0'
	   				WHERE userID = '".$id."' ;";
	   			}
   			}else{
   				$query = "UPDATE ".$wpdb->prefix."FNDRY_bookmsg SET 
   				msg='".$msg."' , rd='0'
   				WHERE userID = '".$id."' ;";
   			}
   		}else{
	   		$query = "INSERT INTO ".$wpdb->prefix."FNDRY_bookmsg ( userID, msg, rd)
	   		VALUES( '".$id."', '".$msg."', 0);";
   		}	
   		$wpdb->query( $query);
   	}
   	/**
   	 * Get information for the rooms available in booking system
	 * @return [object] WP query result
   	 */
   	function get_about_rooms(){
   		global $wpdb;
   		$query = "SELECT * FROM ".$wpdb->prefix."FNDRY_bookroom;";
   		$res = $wpdb->get_results($query);
   		if ( isset( $res[0])){
   			return $res;
   		}
   		return false;
   	}
   	/**
   	 * Triggered by ajax post, updates booking system message table to indicate a message has been read
   	 * @return [null]
   	 */
   	function msg_read(){
   		global $wpdb;
   		$postdata = file_get_contents("php://input"); 
   		$temp = explode("-" ,$postdata);
   		$query = "UPDATE ".$wpdb->prefix."FNDRY_bookmsg SET rd = 1 WHERE userID = '".$temp[3]."';";
   		$wpdb->query( $query );
   	}
   	/** checks validity of wordpress nonce returned via ajax call
   	 * 
   	 * @param  [integer] $ID booking system  userid
   	 * @param  [string] $nonce, wordpress nonce
   	 * @return [boolean]  true or false  if nonce is correct
   	 */
   	function validate_nonce($ID, $nonce){
   		global $wpdb;
   		$query = "SELECT userID FROM ".$wpdb->prefix."FNDRY_bookusers WHERE ((fbID='".$ID."')||(wpID='".$ID."')||(inID='".$ID."')) LIMIT 0,1;";
   		$res = $wpdb->get_results($query); 
   		if ( isset( $res[0])){
   			$testnonce = wp_create_nonce( sha1($ID.$res[0]->userID."fndry")  );
   			if ($testnonce == $nonce ){
   				return true;
   			}
   		}
		return false;
   	}
   	/**
   	 * AJAX feeds current bookings for a given week and room
   	 * @param  [string] $week, UTC date object for current day
   	 * @param  [integer] $roomID, room id in booking system
   	 * @param  [integer] $ID  , user id in booking system
   	 * @param  [string] $nonce, wordpress nonce object
   	 * @return [null] 
   	 */
	function feed_bookings($week,  $roomID, $ID, $nonce){
   		global $wpdb;
   		$ms =  array();
   		if( $this->validate_nonce($ID, $nonce) ){
	   		$myID = "SELECT userID FROM ".$wpdb->prefix."FNDRY_bookusers WHERE ((fbID='".$ID."')||(wpID='".$ID."')||(inID='".$ID."')) LIMIT 0,1";
			$sunday =  "SELECT COUNT(*) FROM ".$wpdb->prefix."FNDRY_bookmeta WHERE userID = (".$myID.")
			 AND segment >= '".$week."' AND segment < DATE_ADD('".$week."',INTERVAL 1 DAY)";
			$monday =  "SELECT COUNT(*) FROM ".$wpdb->prefix."FNDRY_bookmeta WHERE userID = (".$myID.")
			 AND segment >= DATE_ADD('".$week."',INTERVAL 1 DAY) AND segment < DATE_ADD('".$week."',INTERVAL 2 DAY)";
			$tuesday =  "SELECT COUNT(*) FROM ".$wpdb->prefix."FNDRY_bookmeta WHERE userID = (".$myID.")
			 AND segment >= DATE_ADD('".$week."',INTERVAL 2 DAY) AND segment < DATE_ADD('".$week."',INTERVAL 3 DAY)";
			$wednesday =  "SELECT COUNT(*) FROM ".$wpdb->prefix."FNDRY_bookmeta WHERE userID = (".$myID.")
			 AND segment >= DATE_ADD('".$week."',INTERVAL 3 DAY) AND segment < DATE_ADD('".$week."',INTERVAL 4 DAY)";
			$thursday =  "SELECT COUNT(*)  FROM ".$wpdb->prefix."FNDRY_bookmeta WHERE userID = (".$myID.")
			 AND segment >= DATE_ADD('".$week."',INTERVAL 4 DAY) AND segment < DATE_ADD('".$week."',INTERVAL 5 DAY)";
			$friday =  "SELECT COUNT(*)  FROM ".$wpdb->prefix."FNDRY_bookmeta WHERE userID = (".$myID.")
			 AND segment >= DATE_ADD('".$week."',INTERVAL 5 DAY) AND segment < DATE_ADD('".$week."',INTERVAL 6 DAY)";
			 $saturday =  "SELECT COUNT(*) FROM ".$wpdb->prefix."FNDRY_bookmeta WHERE userID = (".$myID.")
			 AND segment >= DATE_ADD('".$week."',INTERVAL 6 DAY) AND segment < DATE_ADD('".$week."',INTERVAL 7 DAY)";
			$totals = "SELECT (".$sunday.") as S,(".$monday.") as M,(".$tuesday.")as T,(".$wednesday.")as W,(".$thursday.")as TH,(".$friday.") as F,(".$saturday.")as SS";
	   		$query = "SELECT 
	   		".$wpdb->prefix."FNDRY_bookmeta.log,
			".$wpdb->prefix."FNDRY_bookmeta.segment,
			".$wpdb->prefix."FNDRY_bookroom.name as 'room',
			".$wpdb->prefix."FNDRY_bookusers.name,
			".$wpdb->prefix."FNDRY_bookusers.fbID,
			".$wpdb->prefix."FNDRY_bookusers.wpID,
			".$wpdb->prefix."FNDRY_bookusers.inID,
			".$wpdb->prefix."FNDRY_bookusers.status
	   		FROM ".$wpdb->prefix."FNDRY_bookmeta 
	   		INNER JOIN ".$wpdb->prefix."FNDRY_bookusers
	   		INNER JOIN  ".$wpdb->prefix."FNDRY_bookroom  
	   		WHERE segment >='".$week."' AND segment < DATE_ADD('".$week."',INTERVAL 7 DAY)
	   		AND ".$wpdb->prefix."FNDRY_bookusers.userID =".$wpdb->prefix."FNDRY_bookmeta.userID
	   		AND ".$wpdb->prefix."FNDRY_bookroom.roomID = ".$wpdb->prefix."FNDRY_bookmeta.roomID
			AND ".$wpdb->prefix."FNDRY_bookmeta.roomID = '".$roomID."';"; 
			$justme = "SELECT * FROM ".$wpdb->prefix."FNDRY_bookmsg WHERE userID = (".$myID.") AND rd=0;";
   		}else{
   			$query = "SELECT 
	   		".$wpdb->prefix."FNDRY_bookmeta.log,
	   		".$wpdb->prefix."FNDRY_bookmeta.segment, 
	   		".$wpdb->prefix."FNDRY_bookroom.name as 'room',	@n as name,	@s as status
	   		FROM ".$wpdb->prefix."FNDRY_bookmeta 
	   		INNER JOIN ".$wpdb->prefix."FNDRY_bookusers
	   		INNER JOIN  ".$wpdb->prefix."FNDRY_bookroom  
	   		WHERE segment >='".$week."' AND segment < DATE_ADD('".$week."',INTERVAL 7 DAY)
	   		AND ".$wpdb->prefix."FNDRY_bookusers.userID =".$wpdb->prefix."FNDRY_bookmeta.userID
	   		AND ".$wpdb->prefix."FNDRY_bookroom.roomID = ".$wpdb->prefix."FNDRY_bookmeta.roomID
	   		AND ".$wpdb->prefix."FNDRY_bookmeta.roomID = '".$roomID."';"; 
   		}
   		$res = $wpdb->get_results($query);
		if ( isset( $totals)){
   			$tt = $wpdb->get_results($totals);
   			$res['tt'] = $tt[0];
   		} 
   		if ( isset($justme)){
   			$ms = $wpdb->get_results($justme);
   			$res['msg'] = $ms;
		}
   		if ( isset($res)){
   			$this->feed_me($res);
   		}else{
   			$this->feed_me(0);
   		}
   	}
   	/**
   	 * Utility method feeds data to XML method or send in server sent fashion as a formatted JSON object
   	 * @param  [object] $res, data object to encode in JSON and send to feed
   	 * @return [null] 
   	 */
   	protected function feed_me($res){
   		if( isset($_GET['xml'])){	   			
   			$this->feed_me_XML($res);
   		}else{
			if( (is_array($res)) || (is_object($res)) ){
				$jsoner =  json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);	
				header("Content-Type: text/event-stream");
				header('Cache-Control: no-cache');
				echo "data:".$jsoner. PHP_EOL;
				echo "retry: 5000".PHP_EOL;
				echo PHP_EOL;
				flush();
			}else{
				header("Content-Type: text/event-stream");
				header('Cache-Control: no-cache');
				echo "data:".$res. PHP_EOL;
				echo "retry: 0".PHP_EOL;
				echo PHP_EOL;
				flush();
			}
		}
	}
	/**
   	 * utility method feeds data in XML as a formatted JSON object
   	 * @param  [object] $res, data object to encode in JSON and send to feed
   	 * @return [null] 
   	 */
  	protected function feed_me_XML($res){
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET');
		header('Content-type: text/xml');
		echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
		echo '<payload size="'.count($res).'" >';
			if (is_object($res)) {
				foreach ( $res as $ind => $obj ) {
					$jsoner =  json_encode($obj ,  JSON_PRESERVE_ZERO_FRACTION|JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
					$temp = str_replace("false", "\"false\"", $jsoner);
					$temp = str_replace("-", "00", $temp);
					echo '
					<pack id="'.str_replace("amp;", "", $ind ).'" >\''.$jsoner.'\'</pack>';
				}
			}else{
				$jsoner =  json_encode($res ,   JSON_HEX_QUOT| JSON_UNESCAPED_SLASHES |JSON_HEX_TAG| JSON_HEX_AMP| JSON_NUMERIC_CHECK| JSON_PRETTY_PRINT| JSON_FORCE_OBJECT| JSON_PRESERVE_ZERO_FRACTION| JSON_UNESCAPED_UNICODE);
				$temp = str_replace("false", "\"false\"", $jsoner);
				echo '<pack id="0" >'.$temp.'</pack>';
			}		
		echo '</payload>';
		echo '';
	}
}
