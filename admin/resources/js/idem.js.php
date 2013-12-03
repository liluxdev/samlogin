<?php
define( 'DS', DIRECTORY_SEPARATOR );
require_once(JPATH_BASE.DS.'components'.DS.'com_idemauth'.DS.'config'.DS.'idemauth_config.inc.php');
?>
//Js

var currentLocation=location.href;
var joomlaBase="<?php echo $idemauth_config['joomla-baseurl']; ?>";
var toReplace=currentLocation.replace(joomlaBase,"");


var JIdem = new Class({

	state    : false,
	link     : null,
	switcher : null,

	initialize: function()
	{
		//Create dynamic elements
		var switcher = new Element('a', { 'styles': {'cursor': 'pointer'},'id': 'idem-link'});
		switcher.inject($('form-login'));



		var link = new Element('a', { 'styles': {'text-align' : 'right', 'display' : 'block', 'font-size' : 'xx-small'}, 'href' : 'http://idem.garr.it'});
		link.inject($('form-login'));

		//Initialise members
		this.switcher = switcher;
		this.link     = link;
		this.state    = Cookie.get('login-idem');
		this.length   = $('form-login-password').getSize().size.y;

                   if (Cookie.get('login-idem')===false) {this.state=1;}
		this.switchID(this.state, 0);

		this.switcher.addEvent('click', (function(event) {
			this.state = this.state ^ 1;
			this.switchID(this.state, 300);
			Cookie.set('login-idem', this.state);
		}).bind(this));
	},

	switchID : function(state, time)
	{
		var password = $('form-login-password');
		var username = $('modlgn_username');
                var remember = $('form-login-remember');
                var forgot = $('form-login-forgot');
                var idpcb = $('modlgn_idp');
                var username2 = $('form-login-username');
	     	var idp = $('form-login-idp');
     	var idpspacer = $('idp-spacer');

		if(state == 0)
		{

			var text = "<img src='"+imgprefix+"components/com_idemauth/resources/images/idem-small.png'/>&nbsp; IDEM Login";
			password.effect('height',  {duration: time}).start(0, this.length);
                     username2.effect('height',  {duration: time}).start(0, this.length);
                     remember.effect('height',  {duration: time}).start(0, this.length);
                      forgot.effect('height',  {duration: time}).start(0, this.length);
                         idpspacer.style.display='none';
                       try{
                         $("form-login").action= $("form-login").action.replace("/components/com_idemauth/resources/loginReceiver.php",toReplace);
                     }catch(e){alert(e);}
                     idp.effect('height',  {duration: time}).start(this.length,0);
		}
		else
		{

	             var text = "Non-Idem Login";
		     password.effect('height',  {duration: time}).start(this.length, 0);
                     username2.effect('height',  {duration: time}).start(this.length, 0);
                        remember.effect('height',  {duration: time}).start(this.length,0);
                      forgot.effect('height',  {duration: time}).start(this.length,0);
                         idpspacer.style.display='';
                     try{

                         $("form-login").action= $("form-login").action.replace(toReplace,"/components/com_idemauth/resources/loginReceiver.php");
                     }catch(e){}
                     idp.effect('height',  {duration: time}).start(0,this.length);

		}

              password.effect('opacity', {duration: time}).start(state,1-state);
              username2.effect('opacity', {duration: time}).start(state,1-state);
              idp.effect('opacity', {duration: time}).start(1-state,state);
               remember.effect('opacity', {duration: time}).start(state,1-state);
              forgot.effect('opacity', {duration: time}).start(state,1-state);


		this.switcher.setHTML(text);
		this.link.setHTML("Informazioni su IDEM");
	}
});

var JIdem_com = new Class({

	state    : false,
	link     : null,
	switcher : null,

	initialize: function()
	{

		//Create dynamic elements
		var switcher = new Element('a', { 'styles': {'cursor': 'pointer'},'id': 'com-idem-link'});
		switcher.inject($('com-form-login'));


		var link = new Element('a', { 'styles': {'text-align' : 'right', 'display' : 'block', 'font-size' : 'xx-small'}, 'href' : 'http://idem.garr.it'});
		link.inject($('com-form-login'));

		//Initialise members
		this.switcher = switcher;
		this.link     = link;
		this.state    = Cookie.get('login-idem');
		this.length   = $('com-form-login-password').getSize().size.y;
                if (Cookie.get('login-idem')===false) {this.state=1;}
		this.switchID(this.state, 0);

		this.switcher.addEvent('click', (function(event) {
			this.state = this.state ^ 1;
			this.switchID(this.state, 300);
			Cookie.set('login-idem', this.state);
		}).bind(this));
	},

	switchID : function(state, time)
	{
		var password = $('com-form-login-password');
		var username = $('username');
                var idpcb = $('comlgn_idp');
                var username2 = $('com-form-login-username');
		 var idp = $('com-form-login-idp');
                 var remember = $('com-form-login-remember');
                var forgot = $('com-form-login-forgot');
    	var idpspacer = $('com-idp-spacer');

		if(state == 0)
		{

			var text = "<img src='"+imgprefix+"components/com_idemauth/resources/images/idem-small.png'/>&nbsp; IDEM Login";
			password.effect('height',  {duration: time}).start(0, this.length);
                     username2.effect('height',  {duration: time}).start(0, this.length);
                      remember.effect('height',  {duration: time}).start(0, this.length);
                      forgot.effect('height',  {duration: time}).start(0, this.length);
                      idpspacer.style.display='none';
                     try{
                         $("com-form-login").action= $("com-form-login").action.replace("/components/com_idemauth/resources/loginReceiver.php",toReplace);
                     }catch(e){}
                     idp.effect('height',  {duration: time}).start(this.length,0);
		}
		else
		{

	             var text = "Non-IDEM Login";
	             password.effect('height',  {duration: time}).start(this.length, 0);
                     username2.effect('height',  {duration: time}).start(this.length, 0);
                        remember.effect('height',  {duration: time}).start(this.length,0);
                      forgot.effect('height',  {duration: time}).start(this.length,0);
                         idpspacer.style.display='';
                       try{
                         $("com-form-login").action= $("com-form-login").action.replace(toReplace,"/components/com_idemauth/resources/loginReceiver.php");
                     }catch(e){}
                     idp.effect('height',  {duration: time}).start(1,this.length);


		}

		password.effect('opacity', {duration: time}).start(state,1-state);
              username2.effect('opacity', {duration: time}).start(state,1-state);
              remember.effect('opacity', {duration: time}).start(state,1-state);
              forgot.effect('opacity', {duration: time}).start(state,1-state);

              idp.effect('opacity', {duration: time}).start(1-state,state);

		this.switcher.setHTML(text);
		this.link.setHTML("Informazioni su IDEM");
	}
});


document.idem = null
document.com_idem = null
window.addEvent('domready', function(){
  if (typeof modlogin != 'undefined' && modlogin == 1) {
  	var idem = new JIdem();
  	document.idem = idem;
  };
  if (typeof comlogin != 'undefined' && comlogin == 1) {
  	var com_idem = new JIdem_com();
  	document.com_idem = idem;
  };
});
