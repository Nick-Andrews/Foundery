/**
 * an equality test for an undefined object I will use
 */
var nuller;
/**
 * Dynamic Booking Calendar Object for reserving assets in co-working spaces 
 * @type {Object}
 * 
 */
var BK = {
	d: null,
	m: null,
	y: null,
	weekstart: null,
	weekday: null,
	month: null,
	h: null,
	min: null,
	sec: null,
	now: null,
	epochtime: null,
	DAY_INC: 86400000,/// 1000*60*60*24
	segments_day: 20, 
	amstart : null,
	myname: null,
	fbid: null,
	wpid: null,
	inid: null,
	mystatus: null,
	myemail: null,
	roomid: 2,
	nonce:null,
	rooms: [],
	maxhours: 3.0,
	futures: 3,
	weekinc: 0,
	days: [],
	months: [],
	onoff: false,
	n_feed: null,
	s_feed : null,
	cal_feed: null,
	/**
	 * Shuts off the recursive loop updating claendar data
	 * @return {null} 
	 */
	turn_off: function(){
		if ( window.EventSource){
		    if ( this.cal_feed  != nuller ) { this.cal_feed.close(); }
		}else{
			if ( this.cal_feed  != nuller ) { this.cal_feed.abort(); }
		}  
		this.onoff = false;  
	},
	/**
	 * turns on recursive loop updating claendar data with current clock time in 60 second increments
	 * @return {null} 
	 */
	setnow: function(){
		this.epochtime = Date.now();
		this.now = new Date( this.epochtime );
		this.y = this.now.getFullYear();
		this.m = this.now.getMonth()+1;
		this.d = this.now.getDate();
		this.h = this.now.getHours();
		this.min = this.now.getMinutes();
		this.sec = this.now.getSeconds();
		this.days = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
		this.months = ["Jan","Feb","Mar","Apr","May","June","July","Aug","Sept","Oct","Nov","Dec"];
		this.weekday = this.days [this.now.getDay()];
		this.month = this.months[this.m-1];
		this.weekstart =  (this.epochtime - ( this.now.getDay() * this.DAY_INC))+(  this.weekinc  * 7 * this.DAY_INC) ;
		setTimeout( function(){
			if( BK.onoff ) {
				BK.setnow(true);
				BK.week_layout();
				BK.get_calendar_bookings();
			}
		},60000);
  	},
	/**
	 * Ajax call to retrieve wordpress nonce string
	 * @return {null}  
	 */
	get_me_nonce: function(){
	 	if ( window.EventSource){
	    	if ( this.n_feed  != nuller ) { this.n_feed.close(); }
	    	this.n_feed = new EventSource("?nfeed="+BK.myemail);
	    	console.log( "?nfeed="+BK.myemail);
	     	this.n_feed.onmessage  =  this.receive_nonce;
	    }else{
	    	if ( this.n_feed  != nuller ) { this.n_feed.abort(); }
      		this.n_feed =openServerReq();
      		this.n_feed.self = this;
	        this.n_feed .open("GET", "?nfeed="+BK.myemail+"&xml=1");
	        this.n_feed.onreadystatechange  =   this.receive_nonce;
	        this.n_feed.setRequestHeader("Content-Type", "text/xml");
	        this.n_feed.send("");
	     }
	},
	/**
	 * catch wp nonce object and switch parsing based on XML or server sent type
	 * @param  {server sent data event} e , wp_nonce char string if available
	 * @return {null}  
	 */
	receive_nonce: function(e){
		 if ( typeof(e.data) == "undefined"  ){ ///xml feed
	      if ((BK.n_feed.readyState == this.self.n_feed.DONE) && ( BK.n_feed.responseXML != null )) {
	        var xmlDoc = BK.n_feed.responseXML;
	        if ( xmlDoc ) {
	            BK.nonce = JSON.parse( xmlDoc.getElementsByTagName("pack")[0].childNodes[0].nodeValue );
	            BK.n_feed.abort();
	        }
	      }
	    }else{
			BK.nonce =  e.data;
			BK.n_feed.close();
	    }
	    BK.get_me_status();
	},

	/**
	 * Ajax call to retrieve booking system user status 
	 * @return { data / xml object} user wordpress status[hottdesk, staff, banned, prospect...]
	 * @return {null}  
	 */
	get_me_status: function(){
	 	if ( window.EventSource){
	    	if ( this.s_feed  != nuller ) { this.s_feed.close(); }
	    	this.s_feed = new EventSource("?sfeed="+BK.myemail);
	     	this.s_feed.onmessage  =  this.receive_status;
	    }else{
	    	if ( this.s_feed  != nuller ) { this.s_feed.abort(); }
      		this.s_feed =openServerReq();
      		this.s_feed.self = this;
	        this.s_feed .open("GET", "?sfeed="+BK.myemail+"&xml=1");
	        this.s_feed.onreadystatechange  =   this.receive_status;
	        this.s_feed.setRequestHeader("Content-Type", "text/xml");
	        this.s_feed.send("");
	    }
	},
	/**
	 * catch data and switch parsing based on XML or server sent type
	 * @param  {server sent data event} e , users status in booking system
	 * @return {null}  
	 */
	receive_status: function(e){
		var stats; 
		 if ( e.data == nuller ){ ///xml feed
	      if ((BK.s_feed.readyState == BK.s_feed.DONE) && ( BK.s_feed.responseXML != null )) {
	        var xmlDoc = BK.s_feed.responseXML;
	        if ( xmlDoc ) {
	        	stats  = JSON.parse( xmlDoc.getElementsByTagName("pack")[0].childNodes[0].nodeValue );
	         	BK.s_feed.abort();
	        }
	      }
	    }else{
			stats = JSON.parse(e.data);
			BK.s_feed.close();
	    }
	    if( stats){
 			BK.maxhours = (parseInt(stats[1]) > 0)?parseInt(stats[1]):0;
	    	BK.mystatus = stats[0].status;
		}
	},
	/**
	 * retrieves calendar bookings for proscribed period based on start UTC and user accreditation [fb, linkedin, wordpress]
	 * @param  {UTC} start, desired monday start time in UTC milliseconds for calendar week
	 * @return {null}  
	 */
	get_calendar_bookings: function(start){
		if ( start == null ){	start =  (this.epochtime - ( this.now.getDay() * this.DAY_INC))+(  this.weekinc  * 7 * this.DAY_INC)  }
	 	if ( window.EventSource){
		    if ( this.cal_feed  != nuller ) { this.cal_feed.close(); }
			if ( BK.fbid != null){
				this.cal_feed = new EventSource("?ffeed="+start+"&room="+BK.room+"&f="+BK.fbid+"&n="+BK.nonce);
			}else if( BK.wpid != null) {
				this.cal_feed = new EventSource("?ffeed="+start+"&room="+BK.room+"&w="+BK.wpid+"&n="+BK.nonce);
			}else if( BK.inid != null) {
				this.cal_feed = new EventSource("?ffeed="+start+"&room="+BK.room+"&i="+BK.inid+"&n="+BK.nonce);
			}
		    this.cal_feed.onmessage  =  this.receive_time;
	    }else{
	    	if ( this.cal_feed != nuller ) {  this.cal_feed.abort(); }
	      	this.cal_feed = openServerReq();
			if ( BK.fbid != null){
				this.cal_feed.open("GET","?ffeed="+start+"&room="+BK.room+"&f="+BK.fbid+"&n="+BK.nonce+"&xml=1");
			}else if( BK.wpid != null) {
				this.cal_feed.open("GET", "?ffeed="+start+"&room="+BK.room+"&w="+BK.wpid+"&n="+BK.nonce+"&xml=1");
			}else if( BK.inid != null) {
				this.cal_feed.open("GET","?ffeed="+start+"&room="+BK.room+"&i="+BK.inid+"&n="+BK.nonce+"&xml=1");
			}
	        this.cal_feed.onreadystatechange  =  this.receive_time;
	        this.cal_feed.setRequestHeader("Content-Type", "text/xml");
	        this.cal_feed.send("");
	    }
	},
	/**
	 * catch calendar date and time bookings object
	 * @param  {server sent data event} e , JSON char string 
	 * @return {null}  
	 */
	receive_time: function(e){
		 if ( e.data == nuller ){ ///xml feed
	      if ((BK.cal_feed.readyState == BK.cal_feed.DONE) && ( BK.cal_feed.responseXML != null )) {
	        var xmlDoc = BK.cal_feed.responseXML;
	        if ( xmlDoc ) {
	            var d  = JSON.parse( xmlDoc.getElementsByTagName("pack")[0].childNodes[0].nodeValue );
	          	BK.cal_feed.abort();
	        }
	      }
	    }else{
			var d = JSON.parse(e.data);
			BK.cal_feed.close();
	    }
	    BK.week_layout();
		BK.update_calendar(d);
	},
	/**
	 * updates text strings for the dates of week in booking calendar based upon users' selection
	 * @param  {integer} start , UTC time code for beginning of week selected
	 * @return {null}    
	 */
	update_week_dates: function(start){
		var week = new Date(start);
		this.y = week.getFullYear();
		this.m = week.getMonth()+1;
		this.d = week.getDate();
		this.h = week.getHours();
		this.min = week.getMinutes();
		this.sec = week.getSeconds();
		jQuery(".day-title").hide();
			for ( var i = 0 ; i < 7; i++){ ///day iteration
				var dtoggler = document.getElementById("toggle-"+this.days[i]);
				if ( i < week.getDay()){
					tday = new Date( start - ((week.getDay()-i)* this.DAY_INC) );
				}else if ( i == week.getDay()){
					tday = week;
				}else{
					tday = new Date( start + (( i-week.getDay())* this.DAY_INC) );
				}
				dtoggler.rel = tday.getFullYear()+"-"+(tday.getMonth()+1)+"-"+tday.getDate();
				document.getElementById("day-title-"+this.days[i]).innerHTML = this.days[i]+" "+tday.getDate()+" "+this.months[tday.getMonth()];
			}
		jQuery(".day-title").show();
	},
	/**
	 * parses data object for calendar to update booking information by date and hour segement
	 * @param  {object} d , data object with calendar booking information
	 * @return {null}  
	 */
	update_calendar: function(d){
		if( ! BK.onoff ) return;
		var tots = jQuery( ".mytotals");
		for ( var j = 0; j < tots.length; j++){
			tots[j].rel = BK.maxhours;
			tots[j].style.height = "0px"; 
		}
		var xlo= function(){
			var dd = document.getElementById( this.parentNode.parentNode.parentNode.id).rel;
			jQuery(this.parentNode).addClass("pending");
			this.onoff = false;
			this.self.try_to_cancel(dd+"-"+this.rel);
			var nu =  (  this.rel  * 7 * this.self.DAY_INC) + this.self.weekstart;
			jQuery( "#"+this.parentNode.id+" > span.booked-by").html("");
			jQuery( "#"+this.parentNode.id+" > span.bookme").show();
			jQuery( "#"+this.parentNode.id+" > span.book-icon").show();
			jQuery( this).remove();
			this.self.get_calendar_bookings(nu);
			this.onoff = true;
		};
		jQuery(".booked-by").html("");
		jQuery(".xloser").remove();
		jQuery( ".pending").removeClass("pending");
		jQuery(".bookme").show();
		jQuery(".book-icon").show();
		for (var i in d){
			if (( d.hasOwnProperty(i))&&( d[i] != null ) ){
				if ( d[i].segment){
					var temp = d[i].segment.split(" ");
					var dy = temp[0].split("-");				
					var t  = temp[1].split(":");
					var dater = new Date( dy[0],parseInt(dy[1]-1),dy[2],0,0,0);
					var findid;
					if ( t[1] == 0 ){
						findid = ""+this.days[ dater.getDay() ]+"-"+parseInt(t[0])+"-0";
					}else{
						findid = ""+this.days[ dater.getDay() ]+"-"+parseInt(t[0])+"-"+t[1];
					}
					if (document.getElementById("t-"+findid) != nuller ) {
						if (( this.mystatus == "staff")||
							( this.mystatus=="hotdesk")||
							( this.mystatus=="dedicated")||
							( this.mystatus=="parttime")||
							( this.mystatus=="office"))
						{
							jQuery("#t-"+findid).children("span.booked-by").html(d[i].name);
							jQuery("#t-"+findid).removeClass("pending");
						}else{
							jQuery("#t-"+findid).children("span.booked-by").html("BOOKED");
						}
						jQuery("#t-"+findid).children("span.book-icon").hide();
						var addcloser = false;
						if(((d[i].fbID == BK.fbid)&&(BK.fbid != null)) || ((d[i].inID == BK.inid)&&(BK.inid != null))|| ((d[i].wpID == BK.wpid)&&(BK.wpid != null)) ){
							addcloser = true;
						}
						if ( addcloser){
							var xloser = document.getElementById("delete-t-"+findid);
							if ( xloser == nuller ){
								xloser = document.createElement("span");
								xloser.setAttribute("id","delete-t-"+findid);
								xloser.setAttribute("class","xloser");
								xloser.rel = findid;
								xloser.self = this;
								document.getElementById("t-"+findid).appendChild( xloser) ; 
								jQuery( xloser).click(xlo);
							}
						}else{
							jQuery("#delete-t-"+findid).remove()
						}
					}
				}else if( i == "msg") {
					if (d[i].length >0 ) { 
						lightbox(null,"<h2>Attention</h2>", false);
						for( var k = 0 ; k < d[i].length; k++){
							BK.user_msg(d[i][k]);
						}
					} if( d[i][0] ){///for xml feed
						lightbox(null,"<h2>Attention</h2>", false);
						BK.user_msg(d[i][0]);
					}
				}else if( i== "tt") {
					update_totals(d[i]);
				}
			}
		}
	},
	/**
	 * pops a lightbox with a message to user about calendar booking, i.e. "you need to book an event"
	 * @param  {object} d , user message object
	 * @return {null}
	 */
	user_msg: function(d){
		var wrap = document.createElement("div");
		wrap.setAttribute("class", "user-msg-wrap");
		var msg = document.createElement("span");
		msg.innerHTML = d.msg;
		wrap.appendChild(msg);
		var ok = document.createElement("div");
		ok.setAttribute("class","xloser");
		ok.setAttribute("id", "ok-msg-0-"+d.userID);
		ok.innerHTML = "OK";
		wrap.appendChild(ok);
		ok.self = this;
		jQuery(ok).click(function(){
			jQuery("#fndry-booking").show();
			jQuery("#lightbox_wrap").remove();
			this.self.confirm_msg(this.id );
		});
		document.getElementById("lb_dialog").appendChild( wrap);
	},
	/**
	 * Server post update triggered by user clicking OK in lightbox
	 * @param  {integer} id, userid of booking system users
	 * @return {null} 
	 */
	confirm_msg: function(id){
		var ajax = new XMLHttpRequest();	
		ajax.open("POST",'?fmsg=1&i=0&nonce=0' ,true);
		ajax.setRequestHeader('Content-Type', 'application/upload');
		ajax.send( id);
	},
	/**
	 * creates buttons for selecting different weeks in booking calendar
	 * @return {DOM object} , html object containg buttons for selecting week dates
	 */
	week_selector: function(){
		var wrap = document.createElement("div");
		var temp;
		wrap.setAttribute("class","week-select-wrap");
		var getweek = function(){
			this.self.onoff = false;
			jQuery( ".toggler > div.hourwrap").remove();
			jQuery(".activeweek").removeClass("activeweek");
			jQuery(this).addClass("activeweek");
			if( this.rel > 0 ){
				jQuery(".hotday").removeClass("hotday");
			}
			var nu =  (  this.rel  * 7 * this.self.DAY_INC) + this.self.weekstart;
			this.self.weekinc = this.rel;
			this.self.update_week_dates( parseInt( nu ));
			this.self.get_calendar_bookings(nu);
			this.self.onoff = true;
		};
		for(var i = 0 ; i < this.futures; i++){
			temp = document.createElement("div");
			temp.setAttribute("id","weekfuture-"+i);
			if ( i == 0 ){
				temp.setAttribute("class","week-selector activeweek");
			}else{
				temp.setAttribute("class","week-selector");
			}
			temp.rel = i ;
			temp.self = this;
			switch( i){
				case 0:
				temp.innerHTML = "THIS WEEK";
				break;
				case 1:
				temp.innerHTML = "NEXT WEEK";
				break;
				default:
				temp.innerHTML = "IN "+i+" WEEKS";
				break;
			}
			jQuery(temp).click(getweek);
			wrap.appendChild(temp);
		}
		return wrap;
	},
	/**
	 * creates buttons for selecting different rooms in booking calendar
	 * @return {DOM object} , html object containg buttons for selecting rooms
	 */
	room_selector: function(){
		var wrap = document.createElement("div");
		wrap.setAttribute("class","room-select-wrap");
		var anchor = document.createElement("a");
		anchor.setAttribute("id","booking");
		wrap.appendChild(anchor);
		var xlo = function(){
			jQuery( ".toggler > div.hourwrap").remove();
			BK.room = this.rel;
			jQuery( ".activeroom").removeClass("activeroom");
			jQuery(this).addClass("activeroom");
			BK.show_room_details();
			var nu =  (  this.self.weekinc  * 7 * this.self.DAY_INC) + this.self.weekstart;
			BK.clear_calendar();
			BK.get_calendar_bookings(nu);
			jQuery(".weekday").show();
		};
		for(var i = 0 ; i < BK.rooms.length; i++){
			var temp = document.createElement("div");
			temp.setAttribute("id","room-"+BK.rooms[i].id);
			temp.setAttribute("class","room-selector");
			if( i== 0 ){
				jQuery(temp).addClass("activeroom");
				BK.room = BK.rooms[i].id;
			}
			temp.innerHTML = BK.rooms[i].title+" ";
			temp.rel = BK.rooms[i].id;
			temp.self = this;
			jQuery(temp).click(xlo);
			wrap.appendChild(temp);
		}
		return wrap;
	},
	/**
	 * Desplays details baout a room space when selected
	 * @return {null}
	 */
	show_room_details: function(  ){
		for( var i = 0 ; i < BK.rooms.length; i++){
			if( BK.rooms[i].id == BK.room){
				var wrap = document.getElementById("room-"+BK.room);
				var desc = document.getElementById("room-"+BK.room+"-description");
				if( desc == nuller ) {
					desc = document.createElement("span");
					desc.setAttribute("id", "room-"+BK.room+"-description");
					desc.setAttribute("class", "room-desc");
					desc.innerHTML = BK.rooms[i].desc+"<span>"+BK.rooms[i].address+"</span>";
					wrap.appendChild( desc)
				}
			}
		}
	},
	/**
	 * utlity function clears booking calendar DOM elements
	 * @return {null} 
	 */
	clear_calendar:function(){
		jQuery( ".booked-by").html(" ");
		jQuery( ".quarterhour").show();
	},
	/**
	 * Elaborate method creates DOM layout for single week in booking calendar
	 * @return {null}
	 */
	week_layout: function(){
		var wrap = document.getElementById("week_wrap");
		if ( wrap == nuller) {
			wrap = document.createElement( "div");
			wrap.setAttribute("id","week_wrap");
			wrap.setAttribute("class","week-wrap");
			jQuery("#fndry-booking").append(wrap);
			wrap.appendChild(BK.room_selector() );
			BK.show_room_details();
			wrap.appendChild(BK.week_selector() );
		}
		var ampm;
		var temp;
		var total;
		var toggler ;
		var dtitle;
		var twrap;
		var tday;
		var thour;
		var sclo = function(){////toggles columns of day segments
			if( document.getElementById("toggle-"+this.parentNode.rel).style.display =="block"){
				jQuery("#toggle-"+this.parentNode.rel).hide("fast", function(){
					window.scrollTo(0,jQuery("#day-title-"+ this.parentNode.rel).offset().top - jQuery("#day-title-"+ this.parentNode.rel).outerHeight());
				});
			}else{
				jQuery(".toggler").hide("fast");
				jQuery("#toggle-"+this.parentNode.rel).show("fast", function(){
					window.scrollTo(0,jQuery("#day-title-"+ this.parentNode.rel).offset().top - jQuery("#day-title-"+ this.parentNode.rel).outerHeight());
				});
			}
		};
		var xlo= function(){///activated when bell icon clicked
			jQuery( this.parentNode).addClass("pending");
			var dd = document.getElementById( this.parentNode.parentNode.parentNode.id).rel;
			node.self.try_to_book("t-"+dd+"-"+this.rel);
			var nu =  (  node.self.weekinc  * 7 * node.self.DAY_INC) + node.self.weekstart;
		};
		for ( var i = 0 ; i < 7; i++){ ///day iteration
			var douze = 0;
			temp = document.getElementById("d-"+this.days[i] );
			if ( i < this.now.getDay()){ ///past day
				tday = new Date( this.epochtime - ((this.now.getDay()-i)* this.DAY_INC)+(7*this.DAY_INC*this.weekinc));
			}else if ( i == this.now.getDay()){
				if ( this.weekinc == 0 ){ ///today
					tday = this.now;
					jQuery( temp).addClass("hotday");
				}else{ ///same day other week
					tday = new Date( this.epochtime - ((this.now.getDay()-i)* this.DAY_INC)+(7*this.DAY_INC*this.weekinc) );
				}
			}else{///future day
				tday = new Date( this.epochtime + (( i-this.now.getDay())* this.DAY_INC)+(7*this.DAY_INC*this.weekinc) );
			}
			if ( temp == nuller){ ///create day wrap and toggler
				temp = document.createElement( "div");///day wrap
				temp.setAttribute("id","d-"+this.days[i]);
				temp.setAttribute("class","weekday "+this.days[i]+" segment-"+this.segment);
				dtitle = document.createElement("div");
				dtitle.setAttribute("class","day-title");
				dtitle.setAttribute("id","day-title-"+this.days[i]);
				dtitle.innerHTML = this.days[i]+" "+tday.getDate()+" "+this.months[tday.getMonth()];
				var tempID = this.days[i]+"-"+tday.getDate()+"-"+this.months[tday.getMonth()];
				temp.appendChild(dtitle);
				temp.self = this;
				temp.rel = this.days[i];
				jQuery(dtitle).click(sclo);
				wrap.appendChild(temp);
				twrap = document.createElement("div");
				twrap.setAttribute("class","hundred-wrap");
				total = document.createElement("span");
				total.setAttribute("id", "total-"+this.days[i]);
				total.setAttribute("class","mytotals");
				total.rel = BK.maxhours;
				twrap.appendChild(total);
				document.getElementById("d-"+this.days[i]).appendChild(twrap);
				toggler = document.createElement("div");
				toggler.setAttribute("id", "toggle-"+this.days[i]);
				toggler.setAttribute("class","toggler");
				temp.appendChild(toggler);
				toggler.rel =  tday.getFullYear()+"-"+(tday.getMonth()+1)+"-"+tday.getDate();
			}
			var mins = 0;
			var passed = false;
			var hourwrap;
			for (var j = 0 ; j < this.segments_day; j++){ //iterate through number of segments in a day
				thour = new Date(tday.getFullYear(),tday.getMonth(),tday.getDate(),this.amstart,(j*BK.segment),0,0 );
				var cls;
				if(( j % (60 /BK.segment) ) ==0 ) {
					hourwrap = document.getElementById(this.days[thour.getDay()]+"-"+(this.amstart+(j/(60 /BK.segment)))+"");
					if ( hourwrap == nuller ) {
						hourwrap = document.createElement("div");
						hourwrap.setAttribute("class","hourwrap");
						hourwrap.setAttribute("id", this.days[thour.getDay()]+"-"+(this.amstart+(j/(60 /BK.segment)))+"");
						hourwrap.setAttribute("id", this.days[thour.getDay()]+"-"+(this.amstart+(j/(60 /BK.segment)))+"");
					}
					if ( this.now.getDate() == thour.getDate() ){///is today
						if(  this.now.getHours() >=  thour.getHours()) { ///past hours time
							hourwrap.setAttribute("class","hourwrap passe");
							passed= true;
						}else{///to come hour or now hour
							hourwrap.setAttribute("class","hourwrap current");
							passed = false;
						}
					}else if( ( this.now.getDate() < thour.getDate() )&&( this.now.getMonth() <= thour.getMonth() ) ) { ///to come day
						hourwrap.setAttribute("class","hourwrap current");
						passed = false;
					}else if( this.now.getMonth() < thour.getMonth() ) {
						hourwrap.setAttribute("class","hourwrap current x");
						passed = false;
					}else{///past day
						hourwrap.setAttribute("class","hourwrap passe");
						passed= true;
					}	
					document.getElementById("toggle-"+this.days[thour.getDay()]).appendChild(hourwrap);
					thour.setHours(this.amstart + (j/(60 /BK.segment)));
					mins= 0;
					cls = "tophour quarterhour";
				}else{
					mins++;
					cls = "quarterhour";
				}
				var nodewrap = document.getElementById("t-"+this.days[thour.getDay()]+"-"+thour.getHours()+"-"+(mins*BK.segment));
				if ( nodewrap == nuller) {
					nodewrap =  document.createElement( "div");	
					nodewrap.setAttribute("id","t-"+this.days[thour.getDay()]+"-"+thour.getHours()+"-"+(mins*BK.segment));
					nodewrap.setAttribute("class","hour-segment");
					node = document.createElement( "div");	
					node.setAttribute("id","s-"+thour.getFullYear()+"-"+(thour.getMonth()+1)+"-"+thour.getDate()+"-"+thour.getHours()+"-"+(mins*BK.segment));
					node.setAttribute("class","book-segment "+cls);
					node.self = this;
					nodewrap.appendChild(node);
					var bookme = document.createElement("span");
					bookme.setAttribute("class", "book-icon");
					bookme.rel = thour.getHours()+"-"+(mins*BK.segment);
					nodewrap.appendChild(bookme);
					if(passed){
						jQuery(bookme).hide();
					}else{
						jQuery(bookme).click(xlo);	
					}
				 	var bookedby = document.createElement("span");
					bookedby.setAttribute("class", "booked-by");
					nodewrap.appendChild(bookedby);
				}
				if ( thour.getHours()> 11){ampm = "pm"; }else{ampm = "am";}
				douze = 12;
				var m = (mins==0)?"00 "+ampm:(mins*BK.segment)+" "+ampm+"";
				if ( (thour.getHours()-douze) == 0 ){//have noon hour
					node.innerHTML= "12:"+m;
				}else if( thour.getHours() > douze){
					node.innerHTML= (thour.getHours()-douze)+":"+m;
				}else{
					node.innerHTML= (thour.getHours())+":"+m;
				}
				hourwrap.appendChild(nodewrap);
			}
		}
	},
	/**
	 * adds dynamic functionality to DOM elements in booking calendar
	 * @return {null}
	 */
	dynamise_active_segments: function (){
		jQuery( "div.current > div > .quarterhour").mouseover(function(){
			point_cursor();
		});
		jQuery( "div.current > div > .quarterhour").mouseleave(function(){
			default_cursor();
		});
		jQuery("div.current > div > .quarterhour").click(function(){
			if ( this.self.status =="banned" ) return;
			if ( typeof( this.self.status) == "undefined")return;
			jQuery( this).addClass("pending");
			this.self.try_to_book(this.id);
			this.self.get_calendar_bookings();
		});
	},
	/**
	 * sets up server post event to cancel a booking by user
	 * @param  {string} id, DOm id of item cliked upon to cancel "2017-8-14-Monday-16-0"
	 * @return {null}  
	 */
	try_to_cancel: function(id){
		if ( window.EventSource){
			 BK.cal_feed.close();
		 }else{
			 BK.cal_feed.abort();
		}
		var d = new BOOKR();
		d.id = id;
		d.myname = BK.myname;
		d.roomID = BK.room;
		d.fbid = BK.fbid;
		d.inid = BK.inid;
		d.wpid = BK.wpid;
		d.myemail =  BK.myemail;
		var pack  = addslashes( JSON.stringify(d) );
		var ajax = new XMLHttpRequest();	
		ajax.open("POST",'?fcancel=1&i=0&nonce=0' ,true);
		ajax.setRequestHeader('Content-Type', 'application/upload');
		ajax.send( pack);
		setTimeout(function(){
			BK.get_calendar_bookings();
		}, 1000);
	},
	/**
	 * sets up ajax call to book a time slot
	 * @param  {string} id , DOM id of element clicked upon "t-2017-8-14-16-0"
	 * @return {null} 
	 */
	try_to_book: function(id){
		if ( fblogged == false){
			lightbox(document.getElementById("my-login-wrap"), "", true);
		}
		if(BK.mystatus=="banned"){
			window.location.href="http://www.google.ca";
		}else if( fblogged == true){
			if ( window.EventSource){
				 BK.cal_feed.close();
			 }else{
				 BK.cal_feed.abort();
			}
			var d = new BOOKR();
			d.id = id;
			d.myname = BK.myname;
			d.roomID = BK.room;
			d.fbid = BK.fbid;
			d.inid = BK.inid;
			d.wpid = BK.wpid;
			d.myemail =  BK.myemail;
			var pack  = addslashes( JSON.stringify(d) );
			var ajax = new XMLHttpRequest();	
			ajax.open("POST",'?fbook=1&i=0&nonce=0' ,true);
			ajax.setRequestHeader('Content-Type', 'application/upload');
			ajax.send( pack);
			setTimeout(function(){
 				BK.get_calendar_bookings(  ( (  BK.weekinc  * 7 * BK.DAY_INC) +BK.weekstart ));
			}, 1000);
		}
	},
};
/**
 * creates a lightbox interface
 * @param  {object} obj , DOM object to insert into lightbox frame
 * @param  {string} msg , message to present to user
 * @param  {boolean} close, true or false to determine whether close trigger is added
 * @return {null} 
 */
function lightbox(obj, msg, close){
	jQuery("#fndry-booking" ).hide();
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
		inner.appendChild( obj);
	}else{
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
			jQuery("#fndry-booking" ).show();
			jQuery(document.getElementById("lightbox_wrap")).slideToggle();
		});
	}
}
/**
 * constructor for room booking object data packet
 */
function BOOKR(){
	this.id = null;
	this.myname = null;
	this.fbid = null;
	this.wpid = null;
	this.inid = null;
	this.roomID = null;
	this.myemail = null;
}
/**
 * constructor for physical room space object data packet
 */
function ROOMR(id,title,desc, address){
	this.id = id;
	this.title = title;
	this.desc = desc;
	this.address = address;
}
/**
 * utility function to add slashes to a string
 * @param  {string} str, string to add escape code slashes to 
 * @return {string}    string returned with slashes
 */
function addslashes(str) {
  return (str + '')
    .replace(/[\\"']/g, '\\$&')
    .replace(/\u0000/g, '\\0');
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
 * utility function formats a date string into yyyy-mm-dd format
 * @param  {integer} year , numerical representation of year
 * @param  {integer} month , numerical representation of month
 * @param  {integer} day , numerical representation of day of month
 * @return {string}     yyyy-mm-dd string
 */
function datestringer( year, month, day ){
	var s = year;
	if ( month < 10){
		s += "-0"+parseInt(month+1);
	}else{
		s += "-"+parseInt(month+1);
	}
	if ( day < 10 ){
		s +="-0"+day;
	}else{
		s +="-"+day;
	}
	return s;
}
/**
 * updates the booking calendar UI to show how much of allotted time per user per day has been booked in a week
 * @param  {object]} d , data object with number of segments currently booked and sorted by day of week
 * @return {null} 
 */
function update_totals( d){
	for( var i in d){
		if ( d.hasOwnProperty(i)){
			var siz = parseInt(d[i]*100) / (BK.maxhours * (60 /BK.segment));
			var obj ;
			switch( i) {
				case "S":
					obj = document.getElementById("total-Sunday");
				break;
				case "M":
					obj = document.getElementById("total-Monday");
				break;
				case "T":
					obj = document.getElementById("total-Tuesday");
				break;
				case "W":
					obj = document.getElementById("total-Wednesday");
				break;
				case "TH":
					obj = document.getElementById("total-Thursday");
				break;
				case "F":
					obj = document.getElementById("total-Friday");
				break;
				case "SS":
					obj = document.getElementById("total-Saturday");
				break;
			}
			obj.style.height = siz+"%";
			if ( parseInt( siz )== 100){
				obj.style.background ="#ee212c";
			}else{
				obj.style.background ="#f7be16";
			}
		}
	}
}
/**
 * Utility function to initiate XHR
 *  
 * @return {object} XHR request
 */
 function openServerReq(){
   var req = null;
   if (window.XMLHttpRequest) {
      try {
         req = new XMLHttpRequest();
      } catch(e) {
         req = null;
      }
   // branch for IE/Windows ActiveX version
   } else if (window.ActiveXObject) {
      try {
         req = new ActiveXObject("Msxml2.XMLHTTP");
      } catch(e) {
         try {
            req = new ActiveXObject("Microsoft.XMLHTTP");
         } catch(e) {
            req = null;
         }
      }
   }
   return req;
}