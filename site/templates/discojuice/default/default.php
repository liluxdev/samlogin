<?php
/**
 * @version		$Id: default.php 2437 2013-01-29 14:14:53Z lefteris.kavadas $
 * @package		SocialConnect
 * @author		JoomlaWorks http://www.joomlaworks.net
 * @copyright	Copyright (c) 2006 - 2013 JoomlaWorks Ltd. All rights reserved.
 * @license		http://www.joomlaworks.net/license
 */

defined('_JEXEC') or die; ?>
<script type="text/javascript" 
		src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>

	<script type="text/javascript" language="javascript" 
		src="//cdn.discojuice.org/engine/discojuice-stable.min.js"></script>
	<script type="text/javascript" language="javascript" 
		src="//cdn.discojuice.org/engine/idpdiscovery.js"></script>

	<link rel="stylesheet" type="text/css" 
		href="//cdn.discojuice.org/css/discojuice.css" />


	<style type="text/css">
		body {
			text-align: center;
		}
		div.discojuice {
			text-align: left;
			/*position: relative;*/
                        left:0px;
                        position: absolute;
			width: 600px;
			margin-right: auto;
			margin-left: auto;
		}
	</style>

	
	<script type="text/javascript">


		jQuery("document").ready(function() {
<?php 
$sitedomain=JURI::getInstance(JURI::root())->getHost();
?>
			var acl = [<?php echo '"'.$sitedomain.'"';?>];
			var options = {
				"title": <?php echo '"'.JFactory::getConfig()->getValue("config.sitename","Service Provider Name").'"';?>,
				"feeds": <?php echo $this->feedsJSON;?>
			};
			var djc = DiscoJuice.Hosted.getConfig(options);

			djc.always = true;
			djc.callback = IdPDiscovery.setup(djc, acl);
                        //djc.metadata.push('https://example.org/additional-metadata.js');
			jQuery("#discojuice").DiscoJuice(djc);
                        // $("a.signon").DiscoJuice(djc);     


		});


	</script>
        <div id="discojuice"></div>
        