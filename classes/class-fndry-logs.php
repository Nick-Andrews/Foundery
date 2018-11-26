<?php
class fndry_logs extends \foundery\core {
	public $dom;
	public $savers;
	public $rejects;
	/*Class constructor
	*/ 
	function fndry_logs(){
		$this->dom = new DOMDocument();
		$this->dom->loadHTML('<div id="setup-wrap"><h2>Foundery Reservation System Log.</h2></div>' );
		$this->savers = array();
		$this->rejects = array();
	}
	/**
	 * [get_stats description]
	 * @return [type] [description]
	 */
	function get_stats(){
		global $wpdb;
		$query = "SELECT 
		".$wpdb->prefix."FNDRY_bookmeta.segment as 'Time',
		".$wpdb->prefix."FNDRY_bookroom.name as 'Room',
		".$wpdb->prefix."FNDRY_bookroom.roomID as 'roomID',
		".$wpdb->prefix."FNDRY_bookusers.status as 'Status',
		".$wpdb->prefix."users.user_nicename as 'Member'
		FROM ".$wpdb->prefix."FNDRY_bookmeta INNER JOIN 
		".$wpdb->prefix."FNDRY_bookusers INNER JOIN 
		".$wpdb->prefix."FNDRY_bookroom INNER JOIN 
		".$wpdb->prefix."users  
		WHERE ".$wpdb->prefix."FNDRY_bookmeta.userID = ".$wpdb->prefix."FNDRY_bookusers.userID
		AND ".$wpdb->prefix."FNDRY_bookmeta.roomID = ".$wpdb->prefix."FNDRY_bookroom.roomID
		AND ".$wpdb->prefix."FNDRY_bookusers.wpID = ".$wpdb->prefix."users.ID
		ORDER BY roomID ASC, member ASC, Time ASC";
		$res = $wpdb->get_results($query);
		if( isset($res[0])){
			return $res;
		}
		return false;
	}
	/**
	 * Method create a DOM UI representation of the reservation system logs.
	 * @return [null]
	 */
	function meta_mgr(){
		$wrap =  $this->dom->createElement("article");
		$form = $this->dom->createElement("form");
		$form->setAttribute("id","room-logs");
		$form->setAttribute("name","room-logs");
		$form->setAttribute("method","post");
		$form->setAttribute("action","admin.php?page=fndrylogs");
		$wrap->appendChild($form);
		$subber =  $this->dom->createElement("input");
		$subber->setAttribute("type","hidden");
		$subber->setAttribute("name","log-editor");
		$subber->setAttribute("value","0");
		$form->appendChild($subber);
		$this->dom->getElementById("setup-wrap")->appendChild($wrap);
		$daystart =  $this->dom->createElement("input");
		$daystart->setAttribute("type", "submit");
		$daystart->setAttribute("value", "Truncate Log");
		$daystart->setAttribute("class", "button-primary");
		$daystart->setAttribute("id", "truncate-log");
		$daystop =  $this->dom->createElement("span");
		$daystop->appendChild( $this->dom->createTextNode( "cancel"));
		$daystop->setAttribute("class", "button-primary");
		$daystop->setAttribute("id", "cancel-truncate-log");
		$daystart->setAttribute("onclick","submit()");
		$stats = $this->get_stats();
		$tbl =  $this->dom->createElement("table");
		$tbl->setAttribute("class","roomstats");
		$form->appendChild($tbl);
		$form->appendChild($daystart);
		$form->appendChild($daystop);
		$thead = $this->dom->createElement("thead");
		$tbl->appendChild( $thead );
		foreach( $stats as $k => $obj){
			$tr = $this->dom->createElement("tr");
			$tbl->appendChild( $tr );
			if($thisroom != $obj->roomID ){
				$thisroom = $obj->roomID;
				$tr->setAttribute("class", "room-start");
				$td = $this->dom->createElement("td");
				$td->appendChild( $this->dom->createTextNode( $obj->Room));
				$td->setAttribute("colspan", 4);
				$tr->appendChild( $td );
				$tr = $this->dom->createElement("tr");
				$tbl->appendChild( $tr );
			}
			if($thisuser != $obj->Member ){
				$thisuser = $obj->Member;
				$nuclass = "newuser";
				$tr = $this->dom->createElement("tr");
				$tbl->appendChild( $tr );
				$td = $this->dom->createElement("td");
				$td->setAttribute("class", "member-".$obj->Status);
				$td->appendChild( $this->dom->createTextNode( "".$obj->Member));
				$td->setAttribute("colspan", 4);
				$tr->appendChild( $td );
				$td->appendChild( $this->dom->createTextNode( " : ".$obj->Status));
				$tr = $this->dom->createElement("tr");
				$tbl->appendChild( $tr );
			}
			$td = $this->dom->createElement("td");
			$td->appendChild( $this->dom->createTextNode( $obj->Time));
			$tr->appendChild( $td );
			$td = $this->dom->createElement("td");
			$td->appendChild( $this->dom->createTextNode( $obj->Member));
			$tr->appendChild( $td );
			$td = $this->dom->createElement("td");
			$td->appendChild( $this->dom->createTextNode( $obj->Room));
			$tr->appendChild( $td );
			$timeslot = $obj->Time;
			$tdadmin = $this->dom->createElement("td");
			$subber =  $this->dom->createElement("input");
			$subber->setAttribute("type","checkbox");
			$subber->setAttribute("name","fndry-cancel-".$obj->roomID."[]");
			$subber->setAttribute("class","time-cancel");
			$subber->setAttribute("value", $timeslot );
			$tdadmin->appendChild( $subber );
			$tr->appendChild( $tdadmin );
		}
	}
	/**
	 * Manage the Foundery bookmeta log truncation process
	 * @param  [object] $p, PHP $_POST object
	 * @return [null]
	 */
	function manage_logs($p){
		global $wpdb;
		foreach( $p as $k => $v){
			if( strpos(  $k, "ndry-cancel-") === 1){ ///delete segments
				$sqlstr=""; 
				foreach( $v as $ind => $seg){
					if( count($v) == ($ind+1) ) {
						$sqlstr .= "'".$seg."'";	
					}else{
						$sqlstr .= "'".$seg."',";	
					}
				}
				$room = substr( $k, 13 , (strlen($k)-13) );
				$query = "DELETE FROM ".$wpdb->prefix."FNDRY_bookmeta WHERE segment IN (".$sqlstr.") AND roomID = '".$room."' ";
				$wpdb->query($query );
			}
		}
	}
	/**
	 * Some inline javascript to manage the UI buttons for truncating | cancel actions
	 * @return [null] 
	 */
	function inline_javascript(){
	?>	
		<script>
			function clear_check(){
				var n = jQuery(".time-cancel");
				for( var i=0,l=n.length; i<l ; i++){
					n[i].checked= false
				}
			}
			window.onload = function(){
				var stp = jQuery("#setup-wrap").offset()
				jQuery(".time-cancel").click(function(ev){
					jQuery("#truncate-log").show();
					jQuery("#truncate-log").css({"top":(ev.pageY-stp.top)+"px"});
					jQuery("#cancel-truncate-log").show();
					jQuery("#cancel-truncate-log").css({"top":(ev.pageY-stp.top)+"px"});
				})
				jQuery("#cancel-truncate-log").click(function(ev){
					clear_check()
					jQuery("#truncate-log").hide();
					jQuery("#cancel-truncate-log").hide();

				});
			}
		</script>
	<?php	
	}
}

$fa = new fndry_logs();
$fa->inline_javascript();
if( isset( $_POST['log-editor'])){
	$fa->manage_logs($_POST); 
}
$fa->meta_mgr();

echo $fa->dom->saveXML();





?>