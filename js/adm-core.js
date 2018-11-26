/**
 * Foundery User object for  WP Admin panel UI
 * @type {Object}
 */
var FU ={
	/**
	 * Creates actions events for lightbox UI for changing a non-status user to a status type in Foundery System
	 * @param  {array} nons   , jQuery array of DOM elelemts for no-status members
	 * @param  {object} params,  object representing status types "hottdesk", "dedidcated"... 
	 * @return {null} 
	 */
	edit_non_users: function(nons, params){
		for(var i = 0 ; i < nons.length;i++){
			nons[i].self = this;
			jQuery(nons[i]).click( function(){
				var nom = document.createElement("div");
				nom.innerHTML = "<em>adding status for => "+this.innerHTML+"</em>";
				var selectr = document.createElement("form");
				selectr.setAttribute("id", "user-selectr");
				selectr.setAttribute("name", "user-selectr");
				selectr.setAttribute("method", "post");
				selectr.setAttribute("action", "admin.php?page=fndryperms");
				var uses = document.createElement("input");
				uses.setAttribute("type","hidden");
				uses.setAttribute("value",this.id);
				uses.setAttribute("name","new-user-email");
				var stats = document.createElement("input");
				stats.setAttribute("type","hidden");
				stats.setAttribute("value","0")
				stats.setAttribute("name","status-set");
				stats.setAttribute("id","status-set");
				stats.setAttribute("change","submit()");
				selectr.appendChild(stats);
				selectr.appendChild(uses);
				lightbox(selectr, "New Status", true)
				jQuery(nom).insertBefore(selectr);
				this.self.create_status_buttons(params, "user-selectr", "no-type");
			});
		}
	},
	/**
	 * Creates actions events for Lightbox UI for changing a user status type in Foundery System
	 * @param  {array} users   , jQuery array of DOM elelemts for no-status members
	 * @param  {object} params,  object representing status types "hottdesk", "dedidcated"... 
	 * @return {null} 
	 */
	edit_users: function(users, params){
		for(var i = 0 ; i < users.length;i++){
			users[i].self = this;
			jQuery(users[i]).click( function(){
				var typer = this.parentNode.id.split("-");
				jQuery(this).hide();
				var ele = this.id.split("-");
				var nom = document.createElement("div");
				nom.innerHTML = "<em>edit member=> "+document.getElementById("user-"+ele[1]).innerHTML+"</em>";
				var selectr = document.createElement("form");
				selectr.setAttribute("id", "user-selectr");
				selectr.setAttribute("name", "user-selectr");
				selectr.setAttribute("method", "post");
				selectr.setAttribute("action", "admin.php?page=fndryperms");
				lightbox(selectr, "Change Status", true)
				jQuery(nom).insertBefore(selectr);
				var stats = document.createElement("input");
				stats.setAttribute("type","hidden");
				stats.setAttribute("value","0")
				stats.setAttribute("name","status-set");
				stats.setAttribute("id","status-set");
				stats.setAttribute("change","submit()");
				var uses = document.createElement("input");
				uses.setAttribute("type","hidden");
				uses.setAttribute("value",ele[1]);
				uses.setAttribute("name","user-set");
				selectr.appendChild(stats);
				selectr.appendChild(uses);
				this.self.create_status_buttons(params, "user-selectr", typer[0]);
			});
			
		}
	},
	/**
	 * Add DOM UI buttons representing status types into a lightbox
	 * @param  {object} params,  object representing status types "hottdesk", "dedidcated"... 
	 * @param  {string} wrapID , DOM object id to place buttons in
	 * @param  {string} type , current status type of user being edited
	 * @return {null}   
	 */
	create_status_buttons(params, wrapID, type){
		var butn;
		for( var k in params.maxhours){
			if( params.maxhours.hasOwnProperty(k)){
				butn = document.createElement("button");
				butn.setAttribute("class", "border-"+k+" user-status");
				butn.innerHTML= k;
				if( type==k){
					butn.setAttribute("class", "member-"+k+" user-status fndry");
				}
				jQuery(butn).click(	function(){
					document.getElementById("status-set").setAttribute("value", this.innerHTML);
				});
				document.getElementById(wrapID).appendChild(butn);
			}
		}
	}
} 
/**
 * Room management Object and methods
 * @type {Object}
 */
var RM = {
	/**
	 * Cretea yes no buttons in UI for Foundery Room delete process
	 * @param  {int} id  ,roomID number in DB tupl for Foundery rooms
	 * @param  {string} nom, name of the room 
	 * @return {null} 
	 */
	rdel: function( id, nom){
		document.getElementById("nom-editor").value = id;
		var msg = document.createElement("h3");
		msg.innerHTML = " Are you sure you want to delete "+nom+"?";
		document.getElementById("room-editor").appendChild(msg);
		var delr =  document.createElement("input");
		delr.setAttribute("name","room-deletr");
		delr.setAttribute("class","room-deletr");
		delr.setAttribute("type", "submit");
		delr.setAttribute("value", "YES. I AM SURE.");
		msg.appendChild(delr);
		var cnlr =  document.createElement("input");
		cnlr.setAttribute("name","no");
		cnlr.setAttribute("class","no");
		cnlr.setAttribute("type", "submit");
		cnlr.setAttribute("value", "NO");
		msg.appendChild(cnlr);
	},
	/**
	 * Creates UI input fields for adding a room to the Foundery system
	 * @return {null}
	 */
	snew: function(){
		jQuery( ".rmedit").remove()
		jQuery( ".room-nom").show();
		var nom =  document.createElement("input");
		nom.setAttribute("name","room-name");
		nom.setAttribute("value", "");
		nom.setAttribute("placeholder", "name");
		nom.setAttribute("class", "rmedit");
		var desc =  document.createElement("input");
		desc.setAttribute("name","room-desc");
		desc.setAttribute("value", "");
		desc.setAttribute("placeholder", "description");
		desc.setAttribute("class", "rmedit");
		var addr =  document.createElement("input");
		addr.setAttribute("name","room-addr");
		addr.setAttribute("value", "");
		addr.setAttribute("placeholder", "address");
		addr.setAttribute("class", "rmedit");
		var savr =  document.createElement("input");
		savr.setAttribute("name","room-new");
		savr.setAttribute("type", "submit");
		savr.setAttribute("value", "save");
		savr.setAttribute("class", "rmedit button-primary");
		document.getElementById("room-editor").appendChild(nom);
		document.getElementById("room-editor").appendChild(desc);
		document.getElementById("room-editor").appendChild(addr);
		document.getElementById("room-editor").appendChild(savr);
	},
	/**
	 * Creates UI input fields for editing an existing room in the Foundery system
	 * @param  {string} objID DOM object id with a reference to the roomID tuple index key
	 * @return {null}
	 */
	sedit: 	function (objID){
		var id = objID.replace(/nom-editor-/g,"");
		document.getElementById(objID).style.display = "none";
		jQuery( ".rmedit").remove()
		jQuery( ".room-nom").show();
		for ( var i = 0; i < r.length; i++){
			if ( id == r[i].roomID){
				document.getElementById("nom-editor").value = r[i].roomID;
				var nom =  document.createElement("input");
				nom.setAttribute("name","room-name");
				nom.setAttribute("value", r[i].name);
				nom.setAttribute("class", "rmedit");
				var desc =  document.createElement("input");
				desc.setAttribute("name","room-desc");
				desc.setAttribute("value", r[i].description);
				desc.setAttribute("class", "rmedit");
				var addr =  document.createElement("input");
				addr.setAttribute("name","room-addr");
				addr.setAttribute("value", r[i].address);
				addr.setAttribute("class", "rmedit");
				var savr =  document.createElement("input");
				savr.setAttribute("name","room-savr");
				savr.setAttribute("type", "submit");
				savr.setAttribute("value", "save");
				savr.setAttribute("class", "rmedit button-primary");
				document.getElementById("room-editor").appendChild(nom);
				document.getElementById("room-editor").appendChild(desc);
				document.getElementById("room-editor").appendChild(addr);
				document.getElementById("room-editor").appendChild(savr);
				return;
			}
		}
	}
}
/**
 * utility function switches cursor to default state
 * @return {null}
 */
function default_cursor(){
	document.body.style.cursor= 'default';
}
/**
 * utility function switches cursor to a pointer
 * @return {null}
 */
function point_cursor(){
	document.body.style.cursor='pointer';
}
/**
 * Provides UI animation to show saving action taking place
 * @return {[type]} [description]
 */
function saved_it(){
	var msg = document.createElement("div");
	msg.id = "expirez";
	msg.innerHTML = "<span id=\"expire-msg\">Saving.</span>";
	document.body.appendChild(msg);
	jQuery( msg).fadeTo(1000 , 0, function() {
		jQuery( "#expirez").remove()
	});
}
/**
 * creates a lightbox interface
 * @param  {object} obj , DOM object to insert into lightbox frame
 * @param  {string} msg , message to present to user
 * @param  {boolean} close, true or false to determine whether close trigger is added
 * @return {null} 
 */
function lightbox(obj, msg, close){
	jQuery( "#lightbox_wrap").remove();
	var wrap = document.createElement("div");
	wrap.setAttribute("id", "lightbox_wrap");
	wrap.setAttribute("class", "lb-wrap");
	var inner = document.createElement("div");
	inner.setAttribute("class", "lb-dialog");
	inner.setAttribute("id", "lb_dialog");
	wrap.appendChild(inner);
	jQuery("body").prepend(wrap);
	var title;
	if ( obj ){
		title = document.createElement("h1");
		title.innerHTML = msg;
		obj.style.display= "block";
		inner.appendChild(title);
		inner.appendChild( obj);	}else{
		title = document.createElement("span");
		title.setAttribute("id","lb_title");
		title.innerHTML = msg;
		inner.appendChild(title);
	}		
	if ( close){
		var xloser = document.createElement("div");
		xloser.setAttribute("class", "pricing-xloser");
		inner.appendChild(xloser);
		jQuery(xloser).click(function(){
			jQuery(document.getElementById("lightbox_wrap")).slideToggle("very slow");
		});
	}
}