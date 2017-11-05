<?php
	
	
	ignore_user_abort(true);

	//ShowDebug("in cron");

	// Avoid Wordpress spaghetti soup and circular referencing
	if ( defined('DOING_STRICTLY_WEBCRON') ){

		die();
	}

	// set a constant up which we will check in the sitemap class to prevent circular referencing
	define('DOING_STRICTLY_WEBCRON',true);	
	

	// load up wordpress spaghetti
	require_once($_SERVER["DOCUMENT_ROOT"] . "/wp-config.php");

	//ShowDebug("doing cron");
	
	// output all the various no cache headers eg pragma
	nocache_headers();
	
	//ShowDebug("cron code from option = " . StrictlyPlugin::get_option('strictly_google_sitemap_croncode') );

	// if my plugin has been uninstalled then quit now
	if(! StrictlyPlugin::get_option('strictly_google_sitemap_croncode')){
	  return false;
	}

	//ShowDebug("chck against " . $code);

	// check our cron code matches the one created when the plugin was activated
	if(isset($_REQUEST['code']) && $_REQUEST['code'] == StrictlyPlugin::get_option('strictly_google_sitemap_croncode')) 
	{

		//ShowDebug("internal spawn?");

		// look for flag from an internal spawn e.g a strictly webcron that is using page loads to control rebuilds
		$internal	= (isset($_REQUEST['spw']) && $_REQUEST['spw'] == "1") ? true : false;

		// look for a force flag which we use during testing and which means timestamp checks are skipped
		$forcebuild	= (isset($_REQUEST['force']) && $_REQUEST['force'] == "1") ? true : false;

		//ShowDebug("call a rebuild with $internal and $forcebuild");

		// call our control method which will check whether the sitemap should be built or not and prevent concurrent rebuilds
		StrictlyControl::SitemapBuilder($internal,$forcebuild);

	}

	//ShowDebug("end");
