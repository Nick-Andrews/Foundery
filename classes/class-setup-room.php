<?php
class setup_room extends \foundery\core {
	public $dom;
	public $hours_day;
	public $day_open;
	public $segments_hour;
	public $advance_weeks;
	/**
	 * Class constructor
	*/ 
	function __construct(){
		$this->dom = new DOMDocument();
		$this->dom->loadHTML('<div id="setup-wrap"><h2>Foundery Reservation System Settings.</h2></div>' );
		$this->day_open= "8:00";	
		$this->day_close= "10:00";	
		$this->segments_hour= 30;
		$this->advance_weeks= 3;	
	}
	/**
	 * Get the time option settings for the Foundery system
	 * @return [null]
	 */
	public function get_save_time_params(){
		global $wpdb;
		$temp = get_option( "fndry-time-params");
		if($temp ){
			$t = json_decode( $temp );
			$this->day_open = $t->strt;
			$this->day_close = $t->duration;
			$this->segments_hour = $t->segment;
			$this->advance_weeks=  $t->fweeks;
		}
	}
	/**
	 * Create DOM ui for managing the Foundery System time setup option parameters
	 * @return [null]
	 */
	public function book_params(){
		global $wpdb;
		$c = get_option( "fndry-time-params" ) ;
		if( $c) {
			$curr = json_decode( $c);
		}
		$wrap =  $this->dom->createElement("article");
		$hed = $this->dom->createElement("h3");
		$hed->appendChild( $this->dom->createTextNode( "STEP 1: Set times of operation."));
		$wrap->appendChild( $hed);
		$form = $this->dom->createElement("form");
		$form->setAttribute("id","room-params");
		$form->setAttribute("name","room-params");
		$form->setAttribute("method","post");
		$form->setAttribute("action","admin.php?page=setuppr");
		$wrap->appendChild($form);
		$hed = $this->dom->createElement("h4");
		$hed->appendChild( $this->dom->createTextNode( "Set open, close and hourly segmentation."));
		$form->appendChild( $hed);
		$dslabel =  $this->dom->createElement("label");
		$dslabel->appendChild( $this->dom->createTextNode( "Open time(24 hr)"));
		$form->appendChild($dslabel);
		$daystart = $this->dom->createElement('select');
		$form->appendChild($daystart);
		$dn = $this->dom->createAttribute('name');
		$dn->value = 'daystart';
		$daystart->appendChild($dn);
		//Add options to select box
		function hr ( $a, $b , $interval)
		{
			if( $interval){
				$t=explode(":", $interval);
				$timestamp	= date('H:i', mktime($a+$t[0], $b+$t[1]) );
			}else{
			  	$timestamp = date('H:i', mktime($a, $b));
			}	
		    return  $timestamp;
		}
		$ampm = "am";
		///set daystart select
		for($i=0;$i<24;$i++)
		{
		    for($j=0;$j<=3;$j++)
		    {
		        $k = $j * 15;
		        $hour = hr($i, $k, null);
		        if( $i >= 12){
		        	$ampm = "pm";
		    	}
	        	$opt = $this->dom->createElement('option', (''.$hour.''.$ampm));
				$domAttribute = $this->dom->createAttribute('value');
				if( $hour == $this->day_open){
					$dn = $this->dom->createAttribute('selected');
		        	$dn->value = true;
			        $opt->appendChild($dn);	
			        $closehour = hr($i, $k, $this->day_close);
		        }
				$domAttribute->value = $hour;
				$opt->appendChild($domAttribute);
				$daystart->appendChild($opt);
		    }
		}
		$dllabel =  $this->dom->createElement("label");
		$dllabel->appendChild( $this->dom->createTextNode( "Close time(24 hr)"));
		$form->appendChild($dllabel);
		$dayclose = $this->dom->createElement('select');
		$form->appendChild($dayclose);	
		$dn = $this->dom->createAttribute('name');
		$dn->value = 'ordmag-page';
		$dayclose->appendChild($dn);	
		$ampm = "am";
		///set dayclose / duration select
		for($i=0;$i<24;$i++)
		{
		    for($j=0;$j<=3;$j++)
		    {
		        $k = $j * 15;
		        $hour = hr($i, $k, null);
		        if( $i >= 12){
		        	$ampm = "pm";
		    	}
	        	$opt = $this->dom->createElement('option', (''.$hour.''.$ampm));
				$domAttribute = $this->dom->createAttribute('value');
					if( $hour ==  $closehour){
					$dn = $this->dom->createAttribute('selected');
		        	$dn->value = true;
			        $opt->appendChild($dn);	
		        }
				$domAttribute->value = $hour;
				$opt->appendChild($domAttribute);
				$dayclose->appendChild($opt);
		    }
		}
		$seglabel =  $this->dom->createElement("label");
		$seglabel->appendChild( $this->dom->createTextNode( "Hourly Segmentation"));
		$form->appendChild($seglabel);
		$rwrap1 =$this->dom->createElement("span");
		$rwrap1->appendChild($this->dom->createTextNode(":15"));
		$r15 = $this->dom->createElement("input");
		$r15->setAttribute("type", "radio");
		$dn = $this->dom->createAttribute('name');
		$dn->value = 'segments';
		$r15->appendChild($dn);	
		$domAttribute = $this->dom->createAttribute('value');
		$domAttribute->value = 15;
		if( intval($this->segments_hour)==15){
			$checked = $this->dom->createAttribute('checked');
			$checked->value = true;
			$r15->appendChild($checked);	
		} 
		$r15->appendChild($domAttribute);
		$rwrap2 =$this->dom->createElement("span");
		$rwrap2->appendChild($this->dom->createTextNode(":30"));
		$r30 = $this->dom->createElement("input");
		$r30->setAttribute("type", "radio");
		$dn = $this->dom->createAttribute('name');
		$dn->value = 'segments';
		$r30->appendChild($dn);	
		$domAttribute = $this->dom->createAttribute('value');
		$domAttribute->value = 30;
		if( intval($this->segments_hour)==30){
			$checked = $this->dom->createAttribute('checked');
			$checked->value = true;
			$r30->appendChild($checked);	
		} 
		$r30->appendChild($domAttribute);
		$rwrap1->appendChild($r15);
		$rwrap2->appendChild($r30);
		$form->appendChild($rwrap1);
		$form->appendChild($rwrap2);
		$uperms =$this->dom->createElement("div");
		$hed = $this->dom->createElement("h4");
		$hed->appendChild( $this->dom->createTextNode("Set the max number of hours/day users can reserve, based upon their member status."));
		$uperms->appendChild( $hed);
		$form->appendChild($uperms);
		if( (isset( $curr->maxhours)) &&  (is_object($curr->maxhours)) ) { 
			$types = array(
			'hotdesk' => $curr->maxhours->hotdesk,
			'dedicated' => $curr->maxhours->dedicated,
			'parttime' => $curr->maxhours->parttime,
			'office' => $curr->maxhours->office,
			'guest' => $curr->maxhours->guest,
			'staff' => $curr->maxhours->staff,
			'event' => $curr->maxhours->event
			);
		}else{
			$types = array(
			'hotdesk' => 3,
			'dedicated' => 3,
			'parttime' => 3,
			'office' => 3,
			'guest' => 3,
			'staff' => 3,
			'event' => 3);
		}
		foreach($types as $str => $int){
			$u =$this->dom->createElement("span");
			$u->setAttribute("class", "user-status member-".$str." ");
			$u->appendChild( $this->dom->createTextNode( $str));
			$uperms->appendChild($u);
			$umax = $this->dom->createElement('select');
			$dn = $this->dom->createAttribute('name');
			$dn->value = 'segments-'.$str;
			$umax->appendChild($dn);	
			$u->appendChild($umax);	
			$opt = $this->dom->createElement('option', "hours per day");
			$dn = $this->dom->createAttribute('disabled');
	        $dn->value = true;
		    $opt->appendChild($dn);	
			$umax->appendChild($opt);
			for( $i = 0 ; $i < 24; $i++){
				$opt = $this->dom->createElement('option', $i);
				$domAttribute = $this->dom->createAttribute('value');
				if( $types[$str] == $i)	{
					$dn = $this->dom->createAttribute('selected');
	        		$dn->value = true;
		    		$opt->appendChild($dn);	
		     	 }
				$domAttribute->value = $i;
				$opt->appendChild($domAttribute);
				$umax->appendChild($opt);
			}
		}
		$uweeks =$this->dom->createElement("div");
		$hed = $this->dom->createElement("h4");
		$hed->appendChild( $this->dom->createTextNode("Set the number of weeks members can book in advance."));
		$uweeks->appendChild( $hed);
		$form->appendChild($uweeks);
		$u =$this->dom->createElement("span");
		$u->setAttribute("class", "advance-weeks");
		$u->appendChild( $this->dom->createTextNode("Weeks In Advance"));
		$uweeks->appendChild($u);
		$uwk = $this->dom->createElement('select');
		$dn = $this->dom->createAttribute('name');
		$dn->value = 'advance-weeks';
		$uwk->appendChild($dn);	
		$u->appendChild($uwk);	
		for( $i = 0 ; $i < 12; $i++){
			$opt = $this->dom->createElement('option', $i);
			$domAttribute = $this->dom->createAttribute('value');
			if( $this->advance_weeks == $i)	{
				$dn = $this->dom->createAttribute('selected');
        		$dn->value = true;
	    		$opt->appendChild($dn);	
	     	 }
			$domAttribute->value = $i;
			$opt->appendChild($domAttribute);
			$uwk->appendChild($opt);
		}
		$subber =  $this->dom->createElement("input");
		$subber->setAttribute("type","submit");
		$subber->setAttribute("name","time-editor");
		$subber->setAttribute("id","time-editor");
		$subber->setAttribute("class","button-primary");
		$subber->setAttribute("value","Save Time Settings");
		$form->appendChild($subber);
		$this->dom->getElementById("setup-wrap")->appendChild($wrap);
	}
	/**
	 * Create DOM ui for managing information about room spaces
	 * @param  [object] $r , room objects array
	 * @return [null] 
	 */
	public function edit_rooms($r=null){
		$wrap =  $this->dom->createElement("article");
		$this->dom->getElementById("setup-wrap")->appendChild($wrap);
		$form = $this->dom->createElement("form");
		$form->setAttribute("id","room-editor");
		$form->setAttribute("name","room-editor");
		$form->setAttribute("method","post");
		$form->setAttribute("action","admin.php?page=setuppr");
		$wrap->appendChild($form);
		$hed = $this->dom->createElement("h3");
		$hed->appendChild( $this->dom->createTextNode("Step 2: Enter information about the room space(s) available."));
		$form->appendChild( $hed);
		$wrap = $this->dom->createElement("div");
		$wrap->setAttribute("id", "room-edit-wrap");
		$form->appendChild( $wrap);
		$addnew = $this->dom->createElement("span");
		$addnew->setAttribute("id", "add-room");
		$addnew->setAttribute("class", "button-primary");
		$addnew->appendChild( $this->dom->createTextNode("Add A Space")); 
		$addnew->setAttribute("onclick", "RM.snew()");
		$wrap->appendChild($addnew);
		$subber =  $this->dom->createElement("input");
		$subber->setAttribute("type","hidden");
		$subber->setAttribute("name","nom-editor");
		$subber->setAttribute("id","nom-editor");
		$subber->setAttribute("onchange","submit()");
		$subber->setAttribute("value","0");
		$form->appendChild($subber);
		foreach( $r as $ind => $obj){
			$val = json_encode($obj);
			$subb = $this->dom->createElement("li");
			$subb->setAttribute("id","nom-editor-".$obj->roomID);
			$subb->appendChild( $this->dom->createTextNode( $obj->name." [edit] "));
			$subb->setAttribute("onclick","RM.sedit(this.id)");
			$subb->setAttribute("class","room-nom");
			$dele = $this->dom->createElement("input");
			$dele->setAttribute("onclick","RM.rdel('".$obj->roomID."', '".$obj->name."')");
			$dele->setAttribute("type", "button");
			$dele->setAttribute("value", "X");
			$subb->appendChild($dele);
			$wrap->appendChild($subb);
		}
	}
	/**
	 * Save time parameter options  on post to page with some javascript UI to indicate a save 
	 * @param  [object] $p, $_POST object
	 * @return [null] 
	 */
	public function save_time($p=null){
		?>
		<script>window.onload= function(){saved_it();}</script>
		<?php 
		global $wpdb; 
		$t = explode(":", $p['daystart']);
		$tt = explode(":", $p['dayclose']);
		$fweeks = $p['advance-weeks'];
		$interval = date('H:i', mktime($tt[0], $tt[1])-mktime($t[0], $t[1]) );
		$segday = $interval * ($p['segments']+60)/60;
		$daystart = date('H:i', mktime($t[0], $t[1]));	
		$types = array(
			'hotdesk'=> 3,
			'dedicated'=> 3,
			'parttime'=> 3,
			'office'=> 3,
			'guest'=> 3,
			'staff'=> 3,
			'event'=> 3,
			'banned'=> 0);
			foreach( $types as $k => $v){
				if( isset( $_POST['segments-'.$k])){
					$types[$k] =  $_POST['segments-'.$k];
				}
			}
		$opt = new optioned($daystart,$interval, $p['segments'], 0 , $types, $fweeks);
		$this->day_open = $daystart;
		$this->day_close = $interval;
		$this->segments_hour = $p['segments'];
		$this->advance_weeks = $p['advance-weeks'];
		$o = json_encode($opt);
		update_option( "fndry-time-params", $o);
	}
	/**
	 * Save room data edit to Foundery system relation with some javascript UI to indicate a save 
	 * @param  [object] $p, $_POST object
	 * @return [null] 
	 */
	public function save_room($p=null){
		?>
		<script>window.onload= function(){saved_it();}</script>
		<?php 
		if(isset($p['room-savr'])){
			$this->update_room($p);
		}elseif (isset($p['room-deletr'])){
			$this->delete_room($p);
		}else if(isset($p['room-new'])){
			$this->add_room($p);
		}
	}
	/**
	 * Update room tuple in Foundery System
	 * @param  [object] $p, $_POST object
	 * @return [null] 
	 */
	function update_room($p){
		global $wpdb;
		$query = "UPDATE ".$wpdb->prefix."FNDRY_bookroom SET 
		name = '".$p['room-name']."',
		description = '".$p['room-desc']."',
		address = '".$p['room-addr']."'	
		WHERE  roomID = '".$p['nom-editor']."' ;";
		$wpdb->query ( $query);
	}
	/**
	 * Delete room tuple in Foundery System
	 * @param  [object] $p, $_POST object
	 * @return [null] 
	 */
	protected function delete_room($p=null){
		global $wpdb;
		$query = "DELETE FROM ".$wpdb->prefix."FNDRY_bookroom 
		WHERE  roomID = '".$p['nom-editor']."' ;";
		$wpdb->query ( $query);
		$query = "DELETE FROM ".$wpdb->prefix."FNDRY_bookmeta 
		WHERE  roomID = '".$p['nom-editor']."' ;";
		$wpdb->query ( $query);

	}
	/**
	 * Add room tuple in Foundery System
	 * @param  [object] $p, $_POST object
	 * @return [null] 
	 */
	protected function add_room($p=null){
		global $wpdb;
		$query = "INSERT INTO ".$wpdb->prefix."FNDRY_bookroom 
		(roomID, name, description, address) VALUES 
		(NULL, '".$p['room-name']."', '".$p['room-desc']."', '".$p['room-addr']."') ;";
		$wpdb->query ( $query);
	}


}
class optioned{
	function __construct($start,$dur,$seg,$rmID, $maxs, $wks) {
   		$this->strt = $start;
		$this->duration = $dur;
		$this->segment = $seg;
		$this->roomID = $rmID;
		$this->maxhours = $maxs;
		$this->fweeks = $wks;
   	}
}

$sr = new setup_room();
$sr->get_save_time_params();
if( isset($_POST['nom-editor'])){
	$sr->save_room($_POST);
}
if( isset($_POST['time-editor'])){
	$sr->save_time($_POST);
}
$r =  $sr->get_about_rooms() ;
?>
<script> 
var rooms = '<?php echo json_encode($r); ?>';
var r = JSON.parse(rooms);
</script>
<?php
$sr->book_params(  );
$sr->edit_rooms( $r );
echo $sr->dom->saveXML();
?>