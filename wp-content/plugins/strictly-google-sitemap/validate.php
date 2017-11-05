<?php
		
	ignore_user_abort(true);
	
	// load up wordpress spaghetti
	require_once($_SERVER["DOCUMENT_ROOT"] . "/wp-config.php");
	
	// output all the various no cache headers eg pragma
	nocache_headers();
	
	// check nonce
	check_ajax_referer('strictly-google-sitemap-nonce');

	// check our cron code matches the one created when the plugin was activated
	if(isset($_REQUEST['url']) && isset($_REQUEST['index']))
	{
		// look for flag from an internal spawn e.g a strictly webcron that is using page loads to control rebuilds
		$url	= urldecode($_REQUEST['url']);
		$index	= urldecode($_REQUEST['index']);
		
	
		// call our control method which will validate the xml and the sitemap against the known google schemas
		$validateresult = StrictlyControl::SitemapValidator($url,$index);

		// put into an array
		$results = array(
				'valid' => ($validateresult=="Valid" ? true : false),
				'validateresult' => $validateresult				
			);

		// load in this file as it will let us encode and decode JSON
		require_once(ABSPATH."/wp-includes/js/tinymce/plugins/spellchecker/classes/utils/JSON.php");
		
		$json_obj	= new Moxiecode_JSON();
		$json		= $json_obj->encode($results); 

		// echo out our response
		echo $json;	

	}