<?php
class book_users extends \foundery\core {
	public $dom;
	public $users;
	public $params;
	/*Class constructor
	*/ 
	function book_users(){
		global $wpdb;
		$this->dom = new DOMDocument();
		$this->params = get_option("fndry-time-params");
		$temp = json_decode( $this->params  );
		if( $temp){
			$this->users = $temp->maxhours; ///keys are user types
		}
	}
	/**
	 * Get users who have logins for the Wordpress site but are not yet in Foundery system
	 * @return [object] WP query result
	 */
	function get_nonstatus_users(){
		global $wpdb;
		$inner ="SELECT email from ".$wpdb->prefix."fndry_bookusers";
		$query = "SELECT user_email, user_nicename FROM ".$wpdb->prefix."users WHERE user_email NOT IN (".$inner.") ;";
		$res = $wpdb->get_results( $query);
		if( isset($res[0])){
			return $res;
		}
		return false;
	}
	/**
	 * Displays a DOM element LI representing a user in the Foundery system 
	 * @param  [Object] $d data object containgn user data
	 * @return [null]    
	 */
	function display_user($d){
		$wrap = $this->dom->createElement("li");
		$wrap->setAttribute("id","user-".$d->wpID);
		$wrap->setAttribute("class","edit-user");
		$wrap->appendChild( $this->dom->createTextNode( $d->name." : [".$d->email."] " ));
		$this->dom->getElementById($d->status."-user-wrap")->appendChild($wrap);
	}
	/**
	 * Displays a DOM element LI representing a non-user, but, is a registered Wordpress user, in the Foundery system 
	 * @param  [Object] $d data object containgn user data
	 * @return [null]    
	 */
	function display_non_aligned($d){
		$wrap = $this->dom->createElement("li");
		$wrap->setAttribute("class","non-user");
		$wrap->setAttribute("id",$d->user_email);
		
		$wrap->appendChild( $this->dom->createTextNode( $d->user_nicename." : [".$d->user_email."] " ));
		$this->dom->getElementById("foundery-non-users")->appendChild($wrap);

	}
	/**
	 * Set up some inline javascript for the Foundery UI in the Wordpress admin panel
	 * @return [null] 
	 */
	function inline_javascript(){
?>
<script>
window.onload = function(){
	var params = JSON.parse( '<?php echo $this->params; ?>');
	var nons = jQuery(".non-user" );
	var butss = jQuery(".edit-user");
	FU.edit_non_users(nons, params);
	FU.edit_users(butss, params);
}
</script>
<?php
	}
	/**
	 * Presently unused method to track user bokking history
	 * @param  [int] $id  Foundery system user id, not the WP id
	 * @return [object] WP query result
	 */
	function user_history($id){
		global $wpdb;
		$query = "SELECT ".$wpdb->prefix."FNDRY_bookmeta.log, 
		".$wpdb->prefix."FNDRY_bookmeta.segment, 
		".$wpdb->prefix."FNDRY_bookusers.name,
		".$wpdb->prefix."FNDRY_bookusers.email,
		".$wpdb->prefix."FNDRY_bookroom.name
		 FROM  
		 ".$wpdb->prefix."FNDRY_bookmeta 
		 INNER JOIN  
		 ".$wpdb->prefix."FNDRY_bookusers 
		 INNER JOIN
		 ".$wpdb->prefix."FNDRY_bookroom
		 WHERE 
		 ".$wpdb->prefix."FNDRY_bookmeta.userID = ".$wpdb->prefix."FNDRY_bookusers.userID
		 AND 
		 ".$wpdb->prefix."FNDRY_bookusers.userID = '".$id."'
		 AND 
		  ".$wpdb->prefix."FNDRY_bookmeta.roomID =  ".$wpdb->prefix."FNDRY_bookroom.roomID;";
		$res = $wpdb->get_results($query);
		if ( isset($res[0])){
			return $res;
		}
		return false;
	}
	/**
	 * Update a users status in the Foundery system DB tuple referencing Foundery userid
	 * @param  [int] $id  Foundery system user id, not the WP id
	 * @param  [string] $type , Status type for the user"
	 * @return [null]     
	 */
	function user_status_update($id, $type){
		global $wpdb;
		$query = "UPDATE ".$wpdb->prefix."FNDRY_bookusers SET status = '".$type."' WHERE userID = '".$id."' ;";
		$wpdb->query($query);
	}
	/**
	 * Update a users status in the Foundery system DB tuple referencing Woprdpress userid
	 * @param  [int] $id  Foundery system user id, not the WP id
	 * @param  [string] $type , Status type for the user"
	 * @return [null]     
	 */
	function update_user($status, $ID){
		global $wpdb;
		$query = "UPDATE ".$wpdb->prefix."FNDRY_bookusers SET status='".$status."' 
		WHERE wpID = '".$ID."';";
		$wpdb->query( $query);
	}
	/**
	 * Add a new user to the Foundery system from the WP user relation
	 * @param [string] $email  , email address registed in the WP user relation
	 * @param [string] $status , status to be assigned to the user
	 */
	function add_new_user( $email, $status){
		global $wpdb;
		if (filter_var($email, FILTER_VALIDATE_EMAIL)){
	   		$tt = json_decode(  get_option("fndry-time-params ") );
			if( $tt ){
   				$this->maxseg =  $tt->maxhours;
   			}else{
   				$this->maxseg = 6;
   			}
 			$idr = "SELECT ID FROM ".$wpdb->prefix."users WHERE user_email ='".$email."'";
			$nom = "SELECT user_nicename FROM ".$wpdb->prefix."users WHERE user_email ='".$email."'";
			$query = "INSERT INTO ".$wpdb->prefix."FNDRY_bookusers (userID, name, wpID, email, status, maxseg)
				VALUES(NULL, (".$nom."),(".$idr."),'".$email."','".$status."', '".$this->maxseg->$status."');";
			$wpdb->query($query );
		}
	}
	/**
	 * Create DOM elements for presenting the users by Foundery system status 
	 * @return [null] 
	 */
	function create_holders(){
		$this->dom->loadHTML('<div id="foundery-non-users"></div><div id="all-users"></div>' );
		$hed = $this->dom->createElement("h2");
		$hed->appendChild( $this->dom->createTextNode( "Change the status of your members."));
		$this->dom->getElementById("all-users")->appendChild($hed);
		$wrap = $this->dom->createElement("div");
		$this->dom->getElementById("all-users")->appendChild($wrap);
		foreach( $this->users as $st => $mx){
			$pros = $this->dom->createElement("ul");
			$pros->setAttribute("id", $st."-user-wrap");
			$pros->setAttribute("class", "member-".$st."");
			$wrap->appendChild($pros);
			$pros->appendChild( $this->dom->createTextNode( "".$st.""));
		}	
	}
}

$bu = new book_users();
if( ( isset( $_POST['status-set']))&&( isset($_POST['user-set'])) ){
	$bu->update_user($_POST['status-set'], $_POST['user-set'] );
}
if( ( isset( $_POST['status-set']))&&( isset($_POST['new-user-email'])) ){
	$bu->add_new_user($_POST['new-user-email'], $_POST['status-set'] );
}
$bu->inline_javascript();
$bu->create_holders();
foreach( $bu->users as $type => $int ){
	$u = $bu->get_users($type);
	foreach ( $u as $ind => $obj){	
		$bu->display_user($obj);
	}
}
$non = $bu->get_nonstatus_users();
if( $non){
	$hed2 = $bu->dom->createElement("h3");
	$hed2->appendChild( $bu->dom->createTextNode( "Non-status members.". count($non)));
	$bu->dom->getElementById("foundery-non-users")->appendChild($hed2);
	foreach( $non as $k => $v ){
		$bu->display_non_aligned($v);
	}
}
echo $bu->dom->saveXML();
?>