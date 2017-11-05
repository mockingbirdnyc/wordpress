<?php

class StrictlyGoogleSitemap{

	public $version				= '1.1';
	public $build				= '0';
	public $author				= 'Robert Reid';
	public $company				= 'Strictly Software';
	public $plugin_name			= 'Strictly Google Sitemap';
	public $website				= 'http://www.strictly-software.com';
	public $plugin_for_xml		= 'strictly-google-sitemap-version';	
	
	protected $wp_version;

	protected $show_donate		= true;
	protected $windows;


	// Pathes and URLs
	protected $siteurl			= '';
	protected $clean_siteurl	= '';	

	// important flags
	protected $enabled				= false; // whether the sitemap builder is on/off allows you to keep the plugin active 
	protected $strictly_uninstall	= false; // uninstall plugin options from DB on deactivate	
	protected $last_build_date		= "";	// date of last build
	protected $build_time			= -1;	// time in seconds that last build took

	// cron management
	public $internal_webcron		= false; // set to true when the cron job is fired internally - helps prevent circular referencing
	public $forcebuild				= false; // set to true when forcing a cron rebuild during testing only!

	protected $post_rewrite;
	protected $page_rewrite;
	protected $category_rewrite;
	protected $tag_rewrite;
	protected $author_rewrite;
	protected $archive_rewrite;

	protected $do_tag_rewrite;
	protected $do_category_rewrite;
	protected $do_page_rewrite; 
	protected $do_post_rewrite;	
	protected $do_author_rewrite;
	protected $do_archive_rewrite;

	protected $post_requires_hourminsec		= false;
	protected $post_requires_yearmonthday	= false;
	protected $post_requires_author			= false;
	protected $post_requires_category		= false;
	protected $post_requires_postid			= false;

	protected $default_category				= '';
	protected $excluded_posts				= ''; 
	protected $loaded_permalinks			= false;

	protected $sitemap_data;
	protected $sitemap_custom_pages			= "";

	protected $pluginpath					= '';
	protected $pluginurl					= '';
	protected $xslurl						= '';
	protected $xslindexurl					= '';
	protected $sitemap_fullpath				= '';
	protected $sitemap_indexpath			= '';
	protected $sitemap_fullurl				= '';	
	protected $sitemap_url					= '';	
	protected $sitemap_urlindex				= '';
	protected $rootpath						= '';
	protected $cronurl						= '';
	protected $sitemap_name					= '';
	protected $sitemap_path					= '';
	protected $sitemap_schemaurl			= '';
	protected $sitemap_index_schemaurl		= '';
	
	protected $sitemap_file_count			= 0;
	protected $sitemap_gzip_file_count		= 0;
	protected $unbuffered_db_count			= 0;
	protected $sitemap_record_count			= 0;
	protected $total_records_pre_exec		= 0;
	protected $records_per_sitemap_default	= 40000;
	protected $sitemap_default_period		= 60;
	protected $sitemap_biggest_file			= 0;
	protected $sitemap_biggest_file_gzip	= 0;
	protected $load_value_default			= 1.0;

	protected $pingurls						= array();	
	protected $minperiod					= 5; // min period my custom cron allows

	protected $seo_google_index				= 0;
	protected $seo_yahoo_index				= 0;
	protected $seo_bing_index				= 0;
	protected $seo_ask_index				= 0;
	protected $seo_google_index_coverage	= 0;
	protected $seo_yahoo_index_coverage		= 0;
	protected $seo_bing_index_coverage		= 0;
	protected $seo_ask_index_coverage		= 0;
	protected $seo_index_report				= "";
	protected $seo_report					= "";
	protected $seo_report_date;

	protected $robot_build_msg				= '';
	protected $permalink_test_msg			= '';
	protected $msg							= '';
	protected $memory_msg					= '';

	// these vars will hold the key configurable settings
	protected $buildopts;
	protected $pingopts;
	protected $changefreq;
	protected $priority;
	protected $sitemap;

	// memory and DB counters
	protected $start_server_load;
	protected $end_server_load;
	protected $memory_limit;
	protected $memory_usage;	
	protected $database_queries;

	// default memory limit to set for script when it run the first time to ensure no issues arise and until a more realistice figure can be calculated
	protected $default_memory_limit			= '128M';
	protected $default_memory_limit_bytes	= 134217728;
	protected $bottom_memory_limit			= '32M';	// never drop memory levels below this
	protected $bottom_memory_limit_bytes	= 33554432; // should be $bottom_memory_limit in bytes

	protected $default_timeout				= 60; // default script timeout

	// possible options
	protected $changefreq_options = array(
		'always',
		'hourly',
		'daily',
		'weekly',
		'monthly',
		'yearly',
		'never'
	);

	protected $priority_options = array(
		'1.0',
		'0.9',
		'0.8',
		'0.7',
		'0.6',
		'0.5',
		'0.4',
		'0.3',
		'0.2',
		'0.1'
	);	

	/**
	 * Constructer for the class
	 * set up all the main properties and paths required for the sitemap object to run
	 *
	 */
	function __construct(){

		$this->clean_siteurl	= StrictlyPlugin::untrailingslashit(StrictlyPlugin::get_option('siteurl'));
		//$this->clean_siteurl	= StrictlyPlugin::untrailingslashit(StrictlyPlugin::get_option('blogurl'));

		ShowDebug("siteurl = " . get_option("siteurl"));
		ShowDebug("blogurl = " . get_option("blogurl"));
		ShowDebug("home = " . get_option("home"));

		$this->siteurl			= $this->clean_siteurl . "/";
		$this->pluginpath		= StrictlyPlugin::trailingslashit(str_replace("\\","/",dirname(__FILE__))); //handle windows
		$this->rootpath			= StrictlyTools::GetHomePath();

		ShowDebug( "replace rootpath: " . $this->rootpath . "<br> with site url: " . $this->siteurl . " in plugin path: " . $this->pluginpath );

		
		$this->pluginurl		= StrictlyPlugin::plugin_dir_url(__FILE__);

		ShowDebug( "plugin url is " . $this->pluginurl );

		//$this->pluginurl		= str_replace($this->rootpath,$this->siteurl,$this->pluginpath);		         
		
		// paths to the xml stylesheets
		$this->xslurl			= $this->pluginurl . 'sitemap.xsl';
		$this->xslindexurl		= $this->pluginurl . 'sitemapindex.xsl';

		// paths to the schema definitions. I use local copies (taken from http://www.sitemaps.org) because their site keeps
		// going down. This way it's quicker to load and to validate
		$this->sitemap_schemaurl		= $this->pluginurl . 'sitemap_schema.xsd';
		$this->sitemap_index_schemaurl	= $this->pluginurl . 'sitemapindex_schema.xsd';
		
		// the url for my google xml validator (was using a 3rd party but it kept breaking and going offline!)
		$this->sitemap_validator_url	= $this->pluginurl . 'validate.php';
		
		// set a flag so I know windows machines from LINUX for uptime checks
		$this->windows			= (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') ? true : false;

		$this->wp_version		= StrictlyPlugin::wp_version();

		
		// load up the main options set by user otherwise use the default values
		$this->GetOptions();

		// set up the actual paths by joining together specified directory + sitemap name
		$this->BuildPaths();

		
		ShowDebug( "pluginpath: " .$this->pluginpath . "<br>rootpath: " . $this->rootpath . "<br>plugin url: " . $this->pluginurl . "<br>xslurl: " . $this->xslurl . "<br>sitemap fullpath: " . $this->sitemap_fullpath);


		// set some local vars up for pinging
		$sitemap_url			= urlencode($this->sitemap_fullurl);
		$sitemap_gzipurl		= urlencode($this->sitemap_gzipurl);

		// holds the XML data
		$sitemap				= array();		

		// set up URL for cron commands
		$this->cronurl			= $this->pluginurl . 'cron.php?code=' . $this->strictly_croncode;

		$this->pingurls[] = array(
			'name'	  => 'ask',
			'service' => 'ASK.COM',
			'url'	  => 'http://submissions.ask.com/ping?sitemap='.$sitemap_url,
			'gzipurl' => 'http://submissions.ask.com/ping?sitemap='.$sitemap_gzipurl,
			'snippet' => 'Your Sitemap has been successfully received and added to our Sitemap queue.'
		);
		$this->pingurls[] = array(
			'name'	  => 'google',
			'service' => 'GOOGLE',
			'url'	  => 'http://www.google.com/webmasters/sitemaps/ping?sitemap='.$sitemap_url,
			'gzipurl' => 'http://www.google.com/webmasters/sitemaps/ping?sitemap='.$sitemap_gzipurl,
			'snippet' => 'Your Sitemap has been successfully added to our list of Sitemaps to crawl.'
		);
		$this->pingurls[] = array(
			'name'	  => 'bing',
			'service' => 'MSN',
			'url'	  => 'http://www.bing.com/webmaster/ping.aspx?siteMap='.$sitemap_url,
			'gzipurl' => 'http://www.bing.com/webmaster/ping.aspx?siteMap='.$sitemap_gzipurl,
			'snippet' => 'Thanks for submitting your sitemap.'
		);	

		$this->pingurls[] = array(
			'name'	  => 'yahoo',
			'service' => 'YAHOO',
			'url'	  => 'http://search.yahooapis.com/SiteExplorerService/V1/updateNotification?appid=' . $this->pingopts['yahoo_api'] . '&url='.$sitemap_url,
			'gzipurl' => 'http://search.yahooapis.com/SiteExplorerService/V1/updateNotification?appid=' . $this->pingopts['yahoo_api'] . '&url='.$sitemap_gzipurl,
			'snippet' => 'Update notification has successfully submitted.'
		);

		// setup wordpress hooks 
		$this->SetupWordpress();

		// setup rebuild hooks for updating when new posts are added - if required
		$this->SetupRebuildHooks();
		
	}


	/**
	 * Creates the actual path, url and filename for the sitemap
	 *
	 */
	function BuildPaths(){

		// set up the actual name, path and url for the sitemap now we know of any customisation
		$this->sitemap_fullpath	= $this->sitemap_path	.$this->sitemap_name	.'.xml';
		$this->sitemap_indexpath= $this->sitemap_path	.$this->sitemap_name	.'.xml'; //create path even though we may not need it
		$this->sitemap_fullurl	= $this->sitemap_url	.$this->sitemap_name	.'.xml';
		$this->sitemap_gzipurl	= $this->sitemap_fullurl						.'.gz';	
		

	}


	/**
	 * Sets up wordpress actions
	 *
	 */
	protected function SetupWordpress(){

		// register my admin page
		StrictlyPlugin::add_action('admin_menu',	array(&$this, 'RegisterAdminPage'));		
		
		// Robots.txt request in case user wants to add the sitemap for robots
		StrictlyPlugin::add_action('do_robots', array(&$this, 'AddSitemapToRobots'),100,0);
		
		// load any language specific text
		StrictlyPlugin::load_textdomain('strictlysitemap', dirname(__FILE__).'/language/'.get_locale().'.mo');
	}
	
	protected function SetupRebuildHooks(){

		// don't bother doing anything else if the Strictly Google Sitemap rebuild option has been disabled
		// we still want the sitemap to be available but we just don't want to rebuild it automatically
		if($this->sitemap['enabled']){

			// what type of rebuild option has been chosen

			// if we are rebuilding the sitemap everytime a new post/page is added - which is not recommended!
			if($this->sitemap['rebuild'] == "onpost"){

				// add hooks so that the sitemap is rebuilt when a post is added or removed
				// this is not recommended for performance especially on small systems that import multiple articles at a time

				StrictlyPlugin::add_action('delete_post',	array(&$this, 'PostRebuild'),9999,1);
				StrictlyPlugin::add_action('publish_post',	array(&$this, 'PostRebuild'),9999,1);
				StrictlyPlugin::add_action('publish_page',	array(&$this, 'PostRebuild'),9999,1);

			// if we are rebuilding the sitemap every X minutes and we are using the website to drive the scheduler
			}elseif($this->sitemap['crontype'] == "webcron"){
				
				// as the built in Wordpress cron scheduler caused me a big fat ole headache especially using OO methods as hooks
				// I am going to replicate a simple check>lock>load system for rebuilding at scheduled intervals.

				// check whether we need to rebuild and thata a sitemap is not already being rebuilt right now
				if($this->RequiresRebuild()){					

					// schedule says to rebuild so fire off webcron request
					$this->SpawnCron();
							
				}

			} // otherwise we are using either a UNIX type Cron job OR a webcron service to automate the rebuild
		}
	}


	/**
	 * This is called whenever a post is updated and the sitemap options specify to rebuild on this action
	 *
	 */
	function PostRebuild(){

		ShowDebug("IN PostRebuild");		

		// if we are rebuilding behind the scenes then we fire off a call to our webcron page which will rebuild
		// the sitemap in another process that won't hinder the current action otherwise we just rebuild now
		if($this->sitemap['behindscenes']){

			ShowDebug("doing it behind the scenes");
			
			// check this constant as if its already been defined then the cron.php page has already been called once
			// and we don't want to call it again as we will end up in a circular loop.

			if(defined('DOING_STRICTLY_WEBCRON')){
				// already been down this road...			
				return false;
			}

			// another safety check
			// As multiple includes may exist on a page all including the wordpress spaghetti by the time the CRON page is called
			// and then the actual RunCron function fires and a lock put in place we may have had 4+ include files get to this stage.
			// This is why I use another transient that only lives for a minute to ensure this doesn't happen as we don't want to fire
			// unneccessary HTTP requests especially on slow or under performing servers
					
			if (false === StrictlyPlugin::get_transient('strictly_google_sitemap_cron_spawn')){

				// set a 60 second lock to prevent multiple spawns this will die naturally
				StrictlyPlugin::set_transient('strictly_google_sitemap_cron_spawn',"SPAWNED",60);

				// set flag so I know its from an internal spawn not a Webcron/UNIX cron job and specify our flag that will force a rebuild
				// and ignore the time checks that the webcron job usually goes through (e.g to ensure its only rebuild at scheduled intervals)
				$cronurl = $this->cronurl . "&spw=1&force=1";

				ShowDebug("fire off a request to $cronurl");

				// fire off an HTTP request to run the job to prevent the current user getting a long page load delay
				// this request will not wait for a response so it shouldn't slow down the current page load!
				StrictlyPlugin::wp_remote_post($cronurl, array('timeout' => 0.01, 'blocking' => false));
			}
		}else{

			ShowDebug("rebuild now");

			$this->RebuildSitemap();
		}		
	}






	/**
	 * Spawns a simulated cron event by firing a non blocking HTTP request that doesn't wait for a response
	 * this ensures that the page that iniates the custom cron job won't be delayed waiting for the sitemap to be built
	 *
	 */
	protected function SpawnCron(){

		// check this constant as if its already been defined then the cron.php page has already been called once
		// and we don't want to call it again as we will end up in a circular loop.

		if(defined('DOING_STRICTLY_WEBCRON')){
			// already been down this road...			
			return false;
		}

		// another safety check
		// As multiple includes may exist on a page all including the wordpress spaghetti by the time the CRON page is called
		// and then the actual RunCron function fires and a lock put in place we may have had 4+ include files get to this stage.
		// This is why I use another transient that only lives for a minute to ensure this doesn't happen as we don't want to fire
		// unneccessary HTTP requests especially on slow or under performing servers
				
		if (false === StrictlyPlugin::get_transient('strictly_google_sitemap_cron_spawn')){

			// set a 60 second lock to prevent multiple spawns this will die naturally
			StrictlyPlugin::set_transient('strictly_google_sitemap_cron_spawn',"SPAWNED",60);

			// set flag so I know its from an internal spawn not a Webcron/UNIX cron job
			$cronurl = $this->cronurl . "&spw=1";

			//StrictlyTools::LogDebug("no 60 second lock so spawn new request for rebuild");

			// fire off an HTTP request to run the job to prevent the current user getting a long page load delay
			// this request will not wait for a response so it shouldn't slow down the current page load!
			StrictlyPlugin::wp_remote_post($cronurl, array('timeout' => 0.01, 'blocking' => false));
		}
	}

	/**
	 * Checks whether a rebuild is in process otherwise looks for the last time a rebuild was carried out and sees whether
	 * a new sitemap should be built or not. Used by WebCron based systems.
	 *
	 * @return boolean
	 */
	protected function RequiresRebuild(){
		
		// if our lock still exists then exit straight away
		if($this->IsLocked()){											
			return false;			
		}


		// Check the last rebuild date to see if we actually need to rebuild this sitemap?
		// as once our transient dies we can chech our schedule. In theory I could just wait until the transient dies...
		if($this->ReadyToBuild("FULL")){

			//StrictlyTools::LogDebug("IN RequiresRebuild - Sitemap requires a rebuild");

			// we need to rebuild the sitemap
			return true;	
			
		}

		return false;
	}

	/**
	 * Sets a transient flag which expires after the desired time period. Whilst this flag exists we know not
	 * to bother checking our timestamps to see if an automatic rebuild is required
	 *
	 */
	protected function SetLock(){

		switch ($this->sitemap['period_type']){
			case 'M':
				$s = 1200; // 20 mins
				break;
			case 'H':
				$s = 3600; // 1 hour
				break;
			case 'D':
				$s = 86400; // 1 day
				break;
		}	
		

		StrictlyPlugin::set_transient('strictly_google_sitemap_rebuild',"LOCKED",$s);
	}

	/**
	 * Checks whether a lock exists that prevents a rebuild from being carried out
	 *
	 * @return boolean
	 */
	protected function IsLocked(){		

		// check whether our transient value has expired
		if (false === StrictlyPlugin::get_transient('strictly_google_sitemap_rebuild')){
			// our lock has expired so we can rebuild our sitemap
			return false;
		}else{
			// our lock still exists
			return true;
		}
	}

	/**
	 * Checks whether the sitemap is ready to be built by comparing the last build date with the current date
	 * For my webcron schedule I keep it simple and only offer 3 options 20 mins, hourly and daily
	 *
	 * @param string $mode
	 * @return boolean
	 */
	protected function ReadyToBuild($mode){		

		$rebuild	= false;
		$lastbuilt	= $this->sitemap['lastbuilt'];		
		$period_type= $this->sitemap['period_type'];

		//StrictlyTools::LogDebug("In ReadyToBuild $mode last build date: $lastbuilt");

		// never been built yet so its okay
		if(empty($lastbuilt)){			
			return true;
		}

		$now	= date('Y-m-d\TH:i:s+00:00',time());		
		$diff	= (int)((strtotime($now) - strtotime($lastbuilt)) / 60);

		// for all tests we ensure at least 5 mins have passed to prevent hammering
		if($diff < $this->minperiod){
			return $rebuild;
		}

		// at least 5 mins have passed on quick tests. Which is all we want as we might be calling the cron page manually or through
		// a UNIX / Web based cron job.
		if($mode == "QUICK" || $this->sitemap['crontype']=="cron"){
			return true;
		}
		
		// otherwise ensure that our full specified period has passed as my own scheduler runs only every 20 mins, 1 hour or 1 day		

		// check gap between now and time we can run next
		switch ($period_type){

			case "M":
				$period = 20;	// 20 mins
				break;
			case "H":
				$period = 60;	// one hour
				break;
			case "D":
				$period = 1440;	// one day			
				break;

		}

		// if the difference is greater or equal to our period 20mins, 1 hour or 1 day
		if($diff >= $period){
			$rebuild = true;
		}
		return $rebuild;

	}



	/**
	 * Called from a cronjob or webcron job and rebuilds the sitemap from scratch
	 *
	 */
	public function RunCron(){			

		// This is my own little safety check to prevent someone taking down a site by hammering the webcron URL with a bot
		// it means that a sitemap won't get rebuilt more than once every 5 minutes even if someone sets a cron job on a 1 min interval		
		$ready = ($this->forcebuild) ? true : ($this->ReadyToBuild("QUICK"));

		if($ready){		
			
			//StrictlyTools::LogDebug("IN RunCron - Fire RebuildSitemap");

			// rebuild sitemap from scratch
			$this->RebuildSitemap();
		}
		
	}


	/**
	 * Checks the last known memory usage to see if we need to increase it for the next build
	 * In theory this shouldn't be needed but its a fallback option to ensure that the sitemap continues to get rebuilt
	 * without manual intervention
	 */
	protected function CheckMemoryLimit(){

		// get current limit
		$memory_limit = StrictlyTools::ConvertToBytes(ini_get('memory_limit'));		
		
		// convert our user set memory limit boundaries to bytes
		$memory_minamount	= StrictlyTools::ConvertToBytes($this->memory_minamount);
	
		// do we have an upper limit?
		if($this->memory_maxamount == "-1"){
			$memory_maxamount	= -1;
			$nolimit			= true;
		}else{
			$memory_maxamount	= StrictlyTools::ConvertToBytes($this->memory_maxamount);
			$nolimit			= false;
		}

		// get the amount of memory the script used when it last ran		
		if(!StrictlyTools::IsNothing($this->memory_usage)){

			$usage_bytes = StrictlyTools::ConvertToBytes($this->memory_usage);

		}else{
			
			// we have no current usage so if we have a value for our upper limit set it to that for our
			// first build then we can lower it later.

			// as its our first attempt ensure the upper limit is at least as big as our default lower limit
			// our value for no limit -1 will never be above our default
			if(!empty($memory_maxamount) && $memory_maxamount > $bottom_memory_limit_bytes){
				// set it to the specified upper limit e.g 128M
				@ini_set("memory_limit",$this->memory_maxamount);
			}else{
				// set it to our default upper limit
				@ini_set("memory_limit",$this->default_memory_limit);
			}

		
			$this->memory_msg .= __('<p>The system has increased your memory limit to ensure the initial build was successful. It will be adjusted to a more appropriate level the next time the sitemap is created.</p>','strictlysitemap');

			return;
		}

		// set the ideal amount
		if($usage_bytes < $memory_minamount){
			
			$new_memory_limit = $memory_minamount;

		}else{

			// find out the difference between what we used and the maximum we can use
			$per = round(($usage_bytes / $memory_limit) * 100);

			// if previous usage was within 15% of limit expand it upwards
			// and if was too low drop the level to something more appropriate		

			if($per > 85){

				$new_memory_limit = $memory_limit + (round($memory_limit / 100) * 15);
			}elseif($per < 50){

				$new_memory_limit = $usage_bytes + (round($usage_bytes / 100) * 15);

				// if its below our bottom limit then just leave it there
				if($new_memory_limit < $memory_minamount){

					$new_memory_limit = $memory_minamount;
				}
			}else{
				// current memory limit is ok				
				return;
			}

		}

		// check there is at least 15MB gap between last usage and limit as 15% when dealing with small sizes is not enough
		$diff = $new_memory_limit - $usage_bytes;
		
		if($diff < 15728640){

			$new_memory_limit = $usage_bytes + 15728640;
		}

		// check our new limit doesn't breach config settings for any upper limit
		// unless -1 has been set which means allow whatever limit no matter how high		
		if($memory_maxamount != -1){
			
			if($new_memory_limit > $memory_maxamount){
				
				// limit to big so set it to the max boundary which might not be enough! So raise a message for admin.
				
				$this->memory_msg .= sprintf(__('<p>The system tried to set the memory limit to %s but couldn\'t as this breached the specified upper memory limit of %s. Please consider increasing this value or setting it to -1 (no limit) so that enough resources can be assigned to ensure the sitemap is rebuilt without causing errors. The system is designed to always provide a 15MB buffer from the last known usage therefore it is recommended when specifying limits to ensure your max threshold is at least 15MB above your last usage amount.</p>','strictlysitemap'),StrictlyTools::ConvertFromBytes($new_memory_limit),StrictlyTools::FormatSize($this->memory_maxamount));

				$new_memory_limit = $memory_maxamount;

			}
		}

		// passing an integer so that it sets the level in bytes
		@ini_set("memory_limit",$new_memory_limit);                                                                                                    
	}


	/**
	 * Register AdminOptions with Wordpress
	 *
	 */
	function RegisterAdminPage() {
		StrictlyPlugin::add_options_page('Strictly Google Sitemap', 'Strictly Sitemap', 10, basename(__FILE__), array(&$this,'AdminOptions'));	
	}


	/**
	 * Rebuilds the sitemap from scratch
	 *
	 * @param bool $fromadmin
	 *
	 */
	protected function RebuildSitemap($fromadmin=false){		

		// reset any message
		$this->msg = "";

		//StrictlyTools::LogDebug("In RebuildSitemap fromadmin = " . intval($fromadmin));


		// if we are running a manual rebuild from the admin page then we skip checks to prevent duplicate builds as we should
		// know what we are doing!
		if(!$fromadmin){

			// for webcron based systems ensure we don't build concurrently - due to wordpresses nature this file could be loaded many times
			// on one page and therefore its possible to fire off concurrent rebuild requests.
			if($this->sitemap['crontype'] == "webcron" && $this->internal_webcron){

				// if we are not forcing a rebuild then double check to ensure we are not currently locked
				if(!$this->forcebuild && $this->IsLocked()){		
					return false;				
				}

				// set flag to prevent multiple concurrent builds - set it to our min gap which is 20mins as my webcron system wont allow
				// auto builds of less than 20min gaps apart
				$this->SetLock();
			}else{

				// we still don't want concurrent rebuilds as it might be possible to have admin manually build it and a feed be processed and
				// someone try to post or update an article. Admin running the rebuild from the sitemap manager page should be the only people
				// to override these checks

				if (false === StrictlyPlugin::get_transient('strictly_google_sitemap_in_build')){

					// set transient to 5 minutes. We delete it at the end of this process anyway and it should never take more than 5 mins to build
					StrictlyPlugin::set_transient('strictly_google_sitemap_rebuild',"LOCKED",300);
				}else{
					// another process is in the middle of building the sitemap so exit
					return false;
				}
				
			}
		}

		//ShowDebug("set the script timeout to " . $this->sitemap['timeout']);

		// set the script timeout		
		@set_time_limit($this->sitemap['timeout']);

		// even if we are not checking the server load to see if its too high before running we still collect the value for reporting
		if(!$this->windows && StrictlyTools::TestShellExec("uptime")){
			$checkload = true;
		}else{
			// flag so we don't try checking later on after the sitemap has been built
			$checkload = false;
		}

		// can we check the server load? Dont want to overload our server if we can help it
		if($checkload){

			// get the current server load for non windows machines
			$this->start_server_load = StrictlyTools::GetServerLoad();

			//ShowDebug("Server load before running is " . $this->start_server_load);

			// do we need to check the server load isn't too high?
			if($this->sitemap['checkload']){			

				// if the server load is equal to or above our max threshold then we dont run our rebuild
				if($this->start_server_load  >= $this->sitemap['load_value']){
				
					$this->msg = sprintf(__('Sitemap Build Aborted. The Server Load was %s which is equal to or above our specified threshold of %s','strictlysitemap'), $this->start_server_load, $this->sitemap['load_value']);

					return false;
				}
			}
		}

		// log how many DB queries this process uses so get the count so far

		// removed the unbuffered option due to the issue outlined here >> http://blog.strictly-software.com/2010/09/issue-with-mysqlunbufferedquery-concat.html
		// if it gets resolved then I will look into using it to see if it helps performance with php loop build mode
		if(1==0){
		//if($this->sitemap['sql_unbuffered']){
			$start_db_queries = 0; // we keep our own count
		}else{
			$start_db_queries = StrictlyPlugin::get_num_queries();
		}

		ShowDebug("before rebuild database queries is " . $start_db_queries);

		// log starting time
		$start_time = microtime(true);

		// if we haven't already loaded all the permalink structures
		if(!$this->loaded_permalinks){
			
			// set up default structures for permalinks once to prevent repeated calls on each record 
			$this->GetSitePermalinks();
		}

		// set other otpions such as trailing slash and default timestamps
		$this->SetupStructures();
			
		// build paths up for all our sitemap sections
		$this->BuildPaths();

		// if the user has set memory autoconfig on then we check whether the limit needs to be increased or decreased
		if($this->memory_autoconfig){
			$this->CheckMemoryLimit();
		}

		//ShowDebug("reset file counters from " . $this->sitemap_file_count);

		// reset global file counters
		$this->sitemap_file_count = $this->sitemap_gzip_file_count = 0;

		//ShowDebug("to = " . $this->sitemap_file_count);

		try{

			$ping = false;

			if($this->sitemap['sql_option']=='loopless'){

				// create the neccessary XML using the loopless option
				$this->Create();

				ShowDebug("how many records in the sitemap data array = " . count($this->sitemap_data));

				// if we don't have any records in our sitemap don't bother rebuilding it
				if(count($this->sitemap_data) > 0){

					// now we have an array bursting to the seams full of XML data. 
					// call our method to build the file(s) and any index file if required
					if($this->BuildSitemap($this->sitemap_data)){

						$ping = true;
					}	
				}
			// the loop
			}else{

				$this->CreateAndBuildSitemap();

				ShowDebug("how many records in the sitemap = " . $this->sitemap_record_count);

				if($this->sitemap_record_count > 0){
					$ping = true;
				}
				
			}

			// set stamp

			$this->sitemap['lastbuilt'] = date('Y-m-d\TH:i:s+00:00',time());										

			ShowDebug("do we ping search engines = " . $ping);

			if($ping){
				// ping search engines
				$this->PingSearchEngines();
			}

		}catch(Exception $e){
			$this->msg = sprintf(__('Sitemap Build Failed: %s','strictlysitemap'), $e->getMessage());
		}

		$end_time = microtime(true);

		$this->build_time = round($end_time - $start_time,2);

		// with the unbuffered option we create our own connection therefore we need to keep our own query count
		// see earlier comment about MySQL bug with unbuffered queries where GROUP BY is used
		if(1==0){
		//if($this->sitemap['sql_unbuffered']){
			$end_db_queries = $this->unbuffered_db_count;
		}else{
			$end_db_queries = StrictlyPlugin::get_num_queries();
		}

		//ShowDebug("database queries is " . $end_db_queries);

		$this->database_queries = $end_db_queries - $start_db_queries;

		//ShowDebug("total DB queries = " . $this->database_queries);

		// clear our cache
		unset($this->sitemap_data);
		
		// can we check the load?
		if($checkload){
	
			// get the current server load for non windows machines
			$this->end_server_load = StrictlyTools::GetServerLoad();	
			
			ShowDebug("the server load after building the sitemap is " . $this->end_server_load);
			
		}

		// log peak memory usage
		$this->LogMemoryUsage();
		
		ShowDebug("save new counter data such as sitemap_file_count = " . $this->sitemap_file_count . " to the DB");

		// save to DB so we can report later
		$this->SaveOptions();

		if(!$fromadmin){
			// delete our lock so other processes can build
			StrictlyPlugin::delete_transient('strictly_google_sitemap_rebuild');
		}
	}



	/**
	 * save new options to the DB
	 *
	 * @param object $object
	 */
	protected function SaveOptions(){

		ShowDebug("IN SaveOptions");

		$options = array(
			'changefreq'		=> $this->changefreq,
			'priority'			=> $this->priority,
			'sitemap'			=> $this->sitemap,
			'pingopts'			=> $this->pingopts,
			'buildopts'			=> $this->buildopts,
			'memory_usage'		=> $this->memory_usage, 
			'memory_limit'		=> $this->memory_limit,
			'database_queries'	=> $this->database_queries,
			'memory_autoconfig'	=> $this->memory_autoconfig,
			'memory_minamount'	=> $this->memory_minamount,
			'memory_maxamount'	=> $this->memory_maxamount,
			'build_time'		=> $this->build_time,
			'record_count'		=> $this->sitemap_record_count,
			'sitemap_name'		=> $this->sitemap_name,
			'sitemap_path'		=> $this->sitemap_path,
			'sitemap_url'		=> $this->sitemap_url,
			'sitemap_file_count'=> $this->sitemap_file_count,
			'start_server_load'	=> $this->start_server_load,
			'end_server_load'	=> $this->end_server_load
			
		);

		ShowDebug($options);

		// save options array to DB
		StrictlyPlugin::update_option('strictly_google_sitemap_settings', $options);

		// save uninstall option seperatley
		StrictlyPlugin::update_option('strictly_google_sitemap_uninstall',$this->strictly_uninstall);		
	}
	
	/**
	 * sets internal member properties with the values from the options array
	 *
	 * @param object $object
	 */
	protected function GetOptions(){		
		
		ShowDebug("IN GetOptions");

		// get saved options from the DB
		
		// Some important options that need to be accessed on their own away from the admin page are stored by themselves
		$this->strictly_croncode		= StrictlyPlugin::get_option('strictly_google_sitemap_croncode');				
		$this->strictly_uninstall		= StrictlyPlugin::get_option('strictly_google_sitemap_uninstall');
		
		
		// for all other customisable options I store arrays of value=key pairs
		$seo_options					= StrictlyPlugin::get_option('strictly_google_sitemap_seo_index');
		$seo_report_options				= StrictlyPlugin::get_option('strictly_google_sitemap_seo_report');
		$options						= StrictlyPlugin::get_option('strictly_google_sitemap_settings');

		// seo index values
		$this->seo_google_index			= $seo_options['seo_google_index'];
		$this->seo_yahoo_index			= $seo_options['seo_yahoo_index'];
		$this->seo_bing_index			= $seo_options['seo_bing_index'];
		$this->seo_ask_index			= $seo_options['seo_ask_index'];

		$this->seo_google_index_coverage= $seo_options['seo_google_index_coverage'];
		$this->seo_yahoo_index_coverage	= $seo_options['seo_yahoo_index_coverage'];
		$this->seo_bing_index_coverage	= $seo_options['seo_bing_index_coverage'];
		$this->seo_ask_index_coverage	= $seo_options['seo_ask_index_coverage'];

		$this->seo_index_report			= $seo_options['seo_index_report'];

		$this->seo_report				= $seo_report_options['seo_report'];
		$this->seo_report_date			= $seo_report_options['seo_report_date'];
		
		// set some properties
		if($options !== false){

			// get the main build, ping and sitemap options and ensure they have values
			$this->sitemap_name			= $options['sitemap_name'];
			$this->sitemap_path			= $options['sitemap_path'];
			$this->sitemap_url			= $options['sitemap_url'];
						


			$this->buildopts			= $options['buildopts'];			
			$this->pingopts				= $options['pingopts'];
			$this->changefreq			= $options['changefreq'];
			$this->priority				= $options['priority'];
			$this->sitemap				= $options['sitemap'];	

			// auto configure memory usage 
			$this->memory_autoconfig	= $options['memory_autoconfig'];
			$this->memory_minamount		= $options['memory_minamount'];
			$this->memory_maxamount		= $options['memory_maxamount'];			
			
			// get last settings for memory and database usage
			$this->memory_usage			= $options['memory_usage'];
			$this->memory_limit			= $options['memory_limit'];
			$this->database_queries		= $options['database_queries'];
			$this->build_time			= $options['build_time'];
			$this->sitemap_record_count	= $options['record_count'];
			$this->sitemap_file_count	= $options['sitemap_file_count'];
			$this->start_server_load	= $options['start_server_load'];
			$this->end_server_load		= $options['end_server_load'];

			ShowDebug("sitemap_file_count = " . $this->sitemap_file_count);
		}else{
			
			// set to default values
			$this->SetSystemDefaults(false);
		}
	}

	/**
	 * Sets the config options to the prefered system defaults
	 */
	protected function SetSystemDefaults(){

		// set the default name, location, url for the sitemap/sitemap index
		$this->sitemap_name		= "sitemap";
		$this->sitemap_path		= $this->rootpath;
		$this->sitemap_url		= $this->siteurl;

		$this->buildopts = array(
			'homepage'	=> true,
			'pages'		=> true,
			'posts'		=> true,
			'categoreis'=> true,
			'tags'		=> false,
			'authors'	=> false,
			'custom'	=> false
		);

		$this->pingopts = array(
			'google'	=> true,
			'bing'		=> true,
			'ask'		=> true,
			'yahoo'		=> false,
			'yahoo_api'	=> ''
		);	
		$this->changefreq = array(
			'homepage'	=> 'daily',
			'post'		=> 'weekly',
			'page'		=> 'monthly',
			'category'	=> 'daily',
			'tag'		=> 'daily',
			'author'	=> 'weekly',
			'custom'	=> 'monthly'
		);
		$this->priority = array(
			'homepage'	=> '1.0',
			'post'		=> '0.9',
			'page'		=> '0.7',
			'category'	=> '0.6',
			'tag'		=> '0.6',
			'author'	=> '0.5',
			'custom'	=> '0.5'
		);
		$this->sitemap = array(
			'records'		=> '40000',				
			'gzip'			=> false,
			'robotstxt'		=> false,			
			'crontype'		=> 'webcron',
			'period_type'	=> 'D',
			'rebuild'		=> 'oncron',
			'load_value'	=> 0.90,
			'checkload'		=> false,
			'timeout'		=> $this->default_timeout,
			'sql_unbuffered'=> false,	
			'sql_option'	=> 'loop',
			'sitemap_file_count' => 0
		);



		// auto configure memory usage 
		$this->memory_autoconfig= true;
		$this->memory_minamount	= $this->bottom_memory_limit;
		$this->memory_maxamount	= $this->default_memory_limit;

	}

	/**
	 * Checks whether WP-O-MATIC is installed as we don't recommend building a sitemap after each post otherwise
	 * you get unneccessary server load and the file is built X times. Read my article on Twitter Rushes which occur
	 * on posting links to Twitter as this also explains high loads that occur on posting >> http://blog.strictly-software.com/2010/10/wordpress-survival-guide-part-2.html
	 *
	 * @return boolean
	 */
	protected function CheckWPOMatic(){		

		$sql = "SHOW TABLES LIKE '##WP_PREFIX##wpo_%';";

		return StrictlyPlugin::check_rows($sql);
		
	}

	/**
	 * Checks whether Yet Another Related Post Plugin is installed as we don't recommend building a sitemap after each post otherwise
	 * you get unneccessary server load especially on large sites due to the complex insert queries YARPP does on each posting
	 *
	 * @return boolean
	 */
	protected function CheckYARPP(){

		$sql = "SHOW TABLES LIKE '##WP_PREFIX##yarpp_%';";

		return StrictlyPlugin::check_rows($sql);
		
	}
	
	/**
	 * Formats a value inputted by a user into MB
	 *
	 * @param string $size
	 * @param string $default
	 * @return string
	 */
	protected function FormatMemoryVal($size,$bound,&$msg){

		// -1 is a valid setting for the upper limit and means there is no limit
		if($bound == "upper" && $size == "-1"){
			return $size;
		}

		// user may enter 32M or 32 MB
		if(!empty($size) && preg_match("@^(\d+[\.0-9]*)\s?(M|MB)$@i",$size,$match)!==false){
			
			if(!$match || !isset($match[1])){
				
				if($bound == "lower"){
					return $this->bottom_memory_limit;
				}else{
					return $this->default_memory_limit;
				}			
			}

			$m		= $match[1] . "M";
			$bytes	= StrictlyTools::ConvertToBytes($m);

			// do we have a known last limit?
			if(!StrictlyTools::IsNothing($this->memory_usage)){
				$usage = StrictlyTools::ConvertToBytes($this->memory_usage);
			}else{
				if(function_exists("memory_get_peak_usage")) {
					$usage = memory_get_peak_usage(true);
				}elseif(function_exists("memory_get_usage")) {
					$usage =  memory_get_usage(true);
				} 
			}
			
			// check that both our min/max values are above actual usage
			if($bound == "lower"){
				
				if($bytes < $usage){

					// so set our bottom limit to either be our default or the actual usage + 5MB
					if($this->bottom_memory_limit_bytes > $usage){
						$m = $this->bottom_memory_limit;
					}else{
						$m = StrictlyTools::ConvertFromBytes($usage + 5242880);
					}

					$msg = __('<p>The specified lower memory limit is beneath the last known usage level and has been changed to a higher setting. Please review this value.</p>','strictlysitemap');
				}
				
				return $m;
				
			}else{
				
				if($bytes < $usage){

					if($this->default_memory_limit > $usage){
						$m = $this->default_memory_limit;
					}else{
						$m = StrictlyTools::ConvertFromBytes($usage + 5242880);
					}
					$msg = __('<p>The specified upper memory limit is beneath the last known usage level and has been changed to a higher setting. Please review this value.</p>','strictlysitemap');
				}
				return $m;
			}		
			
		}else{

			if($bound == "lower"){
				return $this->bottom_memory_limit;
			}else{
				return $this->default_memory_limit;
			}			
		}
	}

	/**
	 * Check the major search engines to see how many pages are indexed for this site
	 *
	 */
	protected function RunIndexReport(){

		$SERPMax	= "Google";
		$SERPMin	= "Google";
		$max=$min	= 0; // store SERP with max and min index coverage
		$domain		= preg_replace("@^https?://(www\.)?@","",$this->clean_siteurl);
		$url_domain	= urlencode($domain);

		$url = "http://www.google.co.uk/search?hl=en&as_epq=&num=10&as_sitesearch=" . $url_domain;

		$max = $min = $this->seo_google_index	= $this->GetSERPCount($url,"@<div id=resultStats>About (\S+?) results@i",1 );

		$max_coverage = $min_coverage = $this->seo_google_index_coverage = ($this->seo_google_index > 0) ? round(($this->seo_google_index / $this->sitemap_record_count) * 100,2) : 0;

		$url = "http://siteexplorer.search.yahoo.com/search;_ylt=?p="  . $url_domain;

		$this->seo_yahoo_index	= $this->GetSERPCount($url,"@<span class=\"btn\">Pages \((\S+?)\)@",1 );
		
		$this->seo_yahoo_index_coverage = ($this->seo_yahoo_index > 0) ? round(($this->seo_yahoo_index / $this->sitemap_record_count) * 100,2) : 0;


		if($this->seo_yahoo_index > $max){
			$max			= $this->seo_yahoo_index;
			$SERPMax		= "Yahoo";
			$max_coverage	= $this->seo_yahoo_index_coverage;
		}
		if($this->seo_yahoo_index < $min){
			$min			= $this->seo_yahoo_index;
			$SERPMin		= "Yahoo";
			$min_coverage	= $this->seo_yahoo_index_coverage;
		}

		$url = "http://www.bing.com/search?q=site%3a"  . $url_domain;

		$this->seo_bing_index	= $this->GetSERPCount($url,"@<span class=\"sb_count\" id=\"count\">\d+-\d+ of (\S+?) results@",1 );

		$this->seo_bing_index_coverage = ($this->seo_bing_index > 0) ? round(($this->seo_bing_index / $this->sitemap_record_count) * 100,2) : 0;

		if($this->seo_bing_index > $max){			
			$max			= $this->seo_bing_index;
			$SERPMax		= "Bing";
			$max_coverage	= $this->seo_bing_index_coverage;
		}
		if($this->seo_bing_index < $min){			
			$min			= $this->seo_bing_index;
			$SERPMin		= "Bing";
			$min_coverage	= $this->seo_bing_index_coverage;
		}

		// ask is a bit tricky but sometimes we get a result on a site: alone
		$url = "http://uk.ask.com/web?qsrc=1&o=0&l=dir&q=site:" .  $url_domain ."&dm=all";

		$this->seo_ask_index	= $this->GetSERPCount($url,"@<span id='indexLast' class='b'>\d+</span> of (\S+?)@",1 );

		if($this->seo_ask_index == "NA"){

			// use the blog name as a search term as on most sites this would OR should appear on the majority of pages anyway
			// remove tld and remove hypens as we could take manchester-news.co.uk and turn it to manchester news
			$domain = str_replace("-"," ",preg_replace("@\.(co\.uk|\w+)$@","",$domain));

			$url	= "http://uk.ask.com/web?qsrc=1&o=0&l=dir&q=" . $domain . "+site:" .  $url_domain ."&dm=all";

			$this->seo_ask_index	= $this->GetSERPCount($url,"@<span id='indexLast' class='b'>\d+</span> of (\S+?)@",1 );
		}

		$this->seo_ask_index_coverage = ($this->seo_ask_index > 0) ? round(($this->seo_ask_index / $this->sitemap_record_count) * 100,2) : 0;
 
		if($this->seo_ask_index > $max){
			$max			= $this->seo_ask_index;
			$SERP			= "Ask";
			$max_coverage	= $this->seo_bing_index_coverage;
		}
		if($this->seo_ask_index < $min){
			$min			= $this->seo_ask_index;
			$SERPMin		= "Ask";
			$min_coverage	= $this->seo_bing_index_coverage;
		}
		
		$this->seo_index_report = sprintf(__("<p>When the SEO report last ran your sitemap had %s records within it. Using this as a rough benchmark we can compare the number of pages indexed by the major search engines to get an estimated coverage percentage for this site. To carry out a 100%% accurate coverage test you would need to take each page within your sitemap and check to see if it can be found within the SERP index in question. Obviously for large sites this would take a lot of time and most likely lead to you being blocked for making too many automated requests. Therefore you should take these results with a large pinch of salt as you may have lots of pages within each search engine that are not within your sitemap and occasionally you may obtain coverage percentages that exceed 100%%.</p>","strictlysitemap"),number_format($this->sitemap_record_count));

		if($max > 0){
		
			$max_rating = $this->GetIndexRating($max_coverage);
			$min_rating = $this->GetIndexRating($min_coverage);			
	
			// These reports are an approximation as the SERP could have indexed pages that are not in the sitemap and pages in the sitemap
			// may not be indexed. However I am not going to check each indexed page in turn so I use the no of pages in the sitemap as a benchmark
			// and compare it to the no of indexed pages in each SERP

			$this->seo_index_report .= sprintf(__("<p>The search engine which has the best coverage of your site is %s which has indexed approximatley %s%% of your sitemap's pages. (%s)</p>","strictlysitemap"),$SERPMax, $max_coverage,$max_rating);


			$this->seo_index_report .= sprintf(__("<p>The search engine which has the worst coverage of your site is %s which has indexed approximatley %s%% of your sitemap's pages. (%s)</p>","strictlysitemap"),$SERPMin, $min_coverage,$min_rating);
		}else{

			$this->seo_index_report .= __('Your site currently has no coverage in the major search engines. Ensure your sitemap is submitted correctly and get some quality backlinks into your site ASAP.','strictlysitemap');
		}		
				
		$seo_options = array(
			'seo_google_index'			=> $this->seo_google_index,
			'seo_yahoo_index'			=> $this->seo_yahoo_index,
			'seo_bing_index'			=> $this->seo_bing_index,
			'seo_ask_index'				=> $this->seo_ask_index,
			'seo_index_report'			=> $this->seo_index_report,
			'seo_google_index_coverage'	=> $this->seo_google_index_coverage,
			'seo_yahoo_index_coverage'	=> $this->seo_yahoo_index_coverage,
			'seo_bing_index_coverage'	=> $this->seo_bing_index_coverage,
			'seo_ask_index_coverage'	=> $this->seo_ask_index_coverage
		);
		// save options array to DB
		StrictlyPlugin::update_option('strictly_google_sitemap_seo_index', $seo_options);
	}

	/**
	 * Run a detailed SEO report
	 *
	 */
	protected function RunSEOReport(){

		if(!class_exists("StrictlySEO")){
			return false;
		}

		// create SEO report object
		$seo = new StrictlySEO($this->clean_siteurl);
		
		// run the SEO report
		$seo->RunReport();

		// analyse the data
		$seo->Analyse();

		// get the report
		$this->seo_report		= $seo->seo_report;

		$this->seo_report_date	= date('Y-m-d\TH:i:s+00:00',time());	

		// save the report as we don't run this on each page load

		$seo_options = array(
			'seo_report'			=> $this->seo_report,
			'seo_report_date'		=> $this->seo_report_date			
		);

		// save options array to DB
		StrictlyPlugin::update_option('strictly_google_sitemap_seo_report', $seo_options);		

		return true;

	}

	/**
	 * Returns a rating for the index coverage a search engine has for a site
	 *
	 * @param float $coverage
	 * @returns string 
	 */
	protected function GetIndexRating($coverage){		
		
		if($coverage>90){
			$level = __('excellent','strictlysitemap');
		}else if($coverage > 70){
			$level = __('very good','strictlysitemap');
		}else if($coverage > 50){
			$level = __('good','strictlysitemap');
		}else if($coverage < 10){
			$level = __('very poor','strictlysitemap');
		}else if($coverage < 25){
			$level = __('poor','strictlysitemap');		
		}else{
			$level = __('average','strictlysitemap');
		}

		return $level;
	}


	/**
	 * Gets the record count from a search results page
	 *
	 */
	function GetSERPCount($url,$regex,$idx){

		$result	= "NA";
		$http	= $this->GetHTTP($url);						

		if($http['status']=="200"){

			$content = $http['body'];

			// use pattern
			preg_match($regex,$content,$match);

			if($match){				

				$result = $match[$idx];

				// ensure numbers like 8,934 get treated as integers not decimals
				$result	= intval(preg_replace("@\D+@","",$result));
			}

			unset($match);
		}

		return $result;
	}

	/**
	 * Formats the sitemap filename for display purposes by adding on the file extension
	 *
	 * @param string $name
	 * @param boolean $append
	 * @return string
	 */
	function FormatSitemapName($name,$append){
		
		$name = trim($name);

		if(!empty($name)){

			// ensure any guff filename is removed
			$name = preg_replace("@\..*$@","",$name);

			if($append){				
				// before adding on the correct suffix
				$name .= ".xml";
			}
		}

		return $name;
	}

	/*
	 * Creates the HTML for the admin page that allows the site administrator to configure the plugin
	 */
	function AdminOptions(){
		
		$errmsg = $low_memory_msg = $upper_memory_msg = $pagelist = "";
		$err	= false;

		// set a flag so we know whether user can run shell_exec and uptime
		if(!$this->windows){
			if(!StrictlyTools::TestShellExec("uptime")){
				$HasUptime = false;
			}else{
				$HasUptime = true;
			}
		}else{
			$HasUptime = false;
		}	

		ShowDebug("has uptime = " . $HasUptime);

		$showSEOidx = $showSEOrpt = $showConfig = false;

		// run our SEO report
		if(isset($_POST['StrictlyGoogleSitemapSEO'])){				

			// run SEO analysis on the homepage
			$this->RunSEOReport();

			$showSEOrpt = true;
		}
		// run SERP index report
		if(isset($_POST['StrictlyGoogleSitemapIndexReport'])){				

			// run the SERP index coverage test
			$this->RunIndexReport();

			$showSEOidx = true;
		}



		// reset all config to system defaults?
		if(isset($_POST['SetSystemDefaults'])){					
			$this->SetSystemDefaults();

			$showConfig = true;
		}

		if(isset($_POST['StrictlyGoogleSitemapSaveOptions'])){			

			$showConfig = true;

			// get admin settings from request
			$this->changefreq				= $_POST['changefreq'];
			$this->priority					= $_POST['priority'];
			$this->sitemap					= $_POST['sitemap'];
			$this->pingopts					= $_POST['pingopts'];
			$this->buildopts				= $_POST['buildopts'];
			$this->memory_autoconfig		= (bool) $_POST['memory_autoconfig'];
			$this->strictly_uninstall		= (bool) $_POST['uninstall'];
			$this->sitemap_name				= (string) stripslashes(trim($_POST["sitemap_name"]));
			$this->sitemap_path				= (string) stripslashes(trim($_POST["sitemap_path"]));
			$this->sitemap_url				= (string) stripslashes(trim($_POST["sitemap_url"]));						
			
			// ensure any numpties who added the filename have it removed				
			if(!empty($this->sitemap_path)){
				if(substr($this->sitemap_path,strlen($this->sitemap_name)*-1)==$this->sitemap_name){
					$this->sitemap_path = substr($this->sitemap_path,0,strlen($this->sitemap_name)*-1);
				}
			}
			if(!empty($this->sitemap_url)){				
				if(substr($this->sitemap_url,strlen($this->sitemap_name)*-1)==$this->sitemap_name){				
					$this->sitemap_url = substr($this->sitemap_url,0,strlen($this->sitemap_name)*-1);
				}
			}

			// remove any file extension added by the user as it will always be .xml
			$this->sitemap_name = $this->FormatSitemapName($this->sitemap_name,false);

			// ensure any changes to the sitemap paths are valid
			if(empty($this->sitemap_name) || preg_match("@[/\\\\]+@",$this->sitemap_name)){				
				$errmsg				.= __("<p>The value supplied for the sitemap filename is invalid and has been reset.</p>","strictlysitemap");
				$err				= true;
				$this->sitemap_name	= "sitemap";
			}
			
			if(empty($this->sitemap_path) || !preg_match("@[/]+@",$this->sitemap_path)){				
				$errmsg				.= __("<p>The value supplied for the sitemap path is invalid and has been reset.</p>","strictlysitemap");
				$err				= true;
				$this->sitemap_path	= $this->rootpath;
			}else if(!is_dir($this->sitemap_path)){
				$errmsg				.= sprintf(__("<p>The path supplied for the sitemap's location %s does not exist and has been reset.</p>","strictlysitemap"),$this->sitemap_path);
				$err				= true;
				$this->sitemap_path	= $this->rootpath;
			}else{				
				$this->sitemap_path	= StrictlyPlugin::trailingslashit($this->sitemap_path);
			}

			if(empty($this->sitemap_url) || !preg_match("@[/]+@",$this->sitemap_url)){				
				$errmsg				.= __("<p>The value supplied for the sitemap url is invalid and has been reset.</p>","strictlysitemap");
				$err				= true;
				$this->sitemap_url	= $this->siteurl;
			}else{				
				$this->sitemap_url	= StrictlyPlugin::trailingslashit($this->sitemap_url);
			}

			// reformat excluded arrays into a csv string for use in SQL and ensure tamper free
			if($this->buildopts['categories'] || $this->buildopts['posts']){				

				// take excluded cats
				$exc_cats		= $_POST['post_category'];
				$exc_cat_arr	= array();

				foreach((array) $exc_cats as $cat){
					if(!empty($cat) && is_numeric($cat)){
						$this->sitemap["excluded_cats"]	.= $cat .",";
						$exc_cat_arr[]					= $cat;
					}
				}
				if($this->sitemap["excluded_cats"] != ""){		
					$this->sitemap["excluded_cats"]		= substr($this->sitemap["excluded_cats"],0 ,-1);
					// save array for use in list
					$this->sitemap["excluded_cats_arr"]	= $exc_cat_arr;
				}else{
					$this->sitemap["excluded_cats_arr"]	= array();
				}

			}
			if($this->buildopts['pages'] || $this->buildopts['posts']){				
				// they supply us with ID's so just remove non integers or commas				
				$this->sitemap["excluded_posts"] = preg_replace("@[^\d,]+@","",stripslashes($this->sitemap["excluded_posts"]));
			}
			if(!empty($this->sitemap["excluded_authors"])){
				// remove spaces between commas
				$this->sitemap["excluded_authors"] = trim(preg_replace("@,\s+@",",",stripslashes($this->sitemap["excluded_authors"])));
			}
			
			// validate config values
			$this->memory_minamount		= $this->FormatMemoryVal(stripslashes($_POST['memory_minamount']),"lower",$low_memory_msg);			
			$this->memory_maxamount		= $this->FormatMemoryVal(stripslashes($_POST['memory_maxamount']),"upper",$upper_memory_msg);

			// did we have to change the memory boundary limits
			if(!empty($low_memory_msg)){
				$errmsg .= $low_memory_msg;
				$err	= true;
			}
			if(!empty($upper_memory_msg)){
				$errmsg .= $upper_memory_msg;
				$err	= true;
			}
			
			// default memory limit to set for script when it run the first time to ensure no issues arise and until a more realistice figure can be calculated


			// ensure the low level is below the high one! if not set to our defaults
			if($this->memory_maxamount != "-1"){
				// ensure the low level is below the high one! if not set to our defaults
				if(intval($this->memory_minamount) > intval($this->memory_maxamount)){

					ShowDebug("yes so use default");

					$this->memory_maxamount	= $this->default_memory_limit;
					$this->memory_minamount	= $this->bottom_memory_limit;

					$errmsg .= __("<p>The upper memory limit must be set to a value higher than the lower limit. The system has reset these values.</p>","strictlysitemap");
				}
			}


			// a sitemap can only have a max of 50000 items per file
			if(!is_numeric($this->sitemap['records']) || $this->sitemap['records']<1 || $this->sitemap['records']>50000){
				$this->sitemap['records'] = $this->records_per_sitemap_default;

				$errmsg .= __("<p>The value for the number of records per sitemap must be a number between 1 and 50,000. The system has reset this value.</p>","strictlysitemap");
			}

			ShowDebug("check uptime value windows = " . $this->windows . " load value = " . $this->sitemap['load_value']);

			if(!$this->windows){
				// ensure the load value is numeric
				if($HasUptime){
					if(!is_numeric($this->sitemap['load_value'])){			
						
						ShowDebug("invalid so reset");

						$this->sitemap['load_value'] = $this->load_value_default;
						$err = true; // setting this to true means that the options wont get saved to the DB

						$errmsg .= __("<p>The value for the server load average must be a number above 0.01. The system has reset this value.</p>","strictlysitemap");
					}
				}elseif($this->sitemap['checkload']){
					$errmsg .= __("<p>Your current server setup does not allow you to run system functions such as system or exec which are required to test for the server load. This option has been disabled.</p>","strictlysitemap");

					// disable
					$this->sitemap['checkload'] = false;
				}
			}

			ShowDebug("check timeout value = " . $this->sitemap['timeout']);
			
			if(!is_numeric($this->sitemap['timeout']) || $this->sitemap['timeout']<0){

				ShowDebug("invalid so reset");

				$this->sitemap['timeout'] = $this->default_timeout;
				$err = true;

				$errmsg .= __("<p>The script timeout value must be a number that is equal to or above 0 (0 = no timeout limit). The system has reset this value.</p>","strictlysitemap");
			}
			

			// a sitemap can only contain pages within the same domain
			if($this->buildopts['custompages']){
								
				if(!empty($this->sitemap['custompages'])){

					// remove any historical invalid params then split into lines to parse
					$custom_pages	= str_replace("http://www.examplesite.com/page 0.8 weekly","",preg_replace("@\[INVALID (URL|PRIORITY|CHANGE FREQUENCY)\]@","",$this->sitemap['custompages']));
					$custom_pages	= explode("\n",$custom_pages);					
					$len			= strlen($this->siteurl);
					$blogurl		= strtolower($this->siteurl);
					$cuserr			= false;

					foreach($custom_pages as $page){						
						$page = trim($page);

						if(!empty($page)){
							// ensure each url starts with the blogs domain and protocol
							$page = stripslashes(strtolower(trim($page)));
							
							// there maybe a specific change frequency or priority value
							list($url,$priority,$changefreq) = explode(" ",trim(preg_replace("@\s+@"," ",$page)));

							if(strlen($url)<$len || substr($url,0,$len) != $blogurl){
								$url	.= " [INVALID URL]";
								$cuserr	= true;
							}
							if(!empty($priority) && !preg_match("@^0\.[1-9]$@",$priority)){
								$priority	.= " [INVALID PRIORITY]";
								$cuserr		= true;
							}
							if(!empty($changefreq) && !preg_match("@^(always|never|hourly|daily|weekly|monthly|yearly)$@",$changefreq)){
								$changefreq .= " [INVALID CHANGE FREQUENCY]";
								$cuserr		= true;
							}							

							$pagelist	.= trim($url . " " . $priority . " " . $changefreq) ."\n";						}
					}

					unset($custom_pages,$page);

					// reset our custom page value
					$this->sitemap['custompages'] = $pagelist;

					if($cuserr){
						$err	= true;
						// set up error message
						$errmsg .= sprintf(__("<p>The list of supplied custom pages contains some invalid entries. A sitemap can only contain URL's from the domain that the sitemap resides within e.g %s. For more information please visit %s.</p>","strictlysitemap"),$this->siteurl,"<a href=\"http://www.sitemaps.org/protocol.php#location\">sitemaps.org</a>");
					}
				}
			}

			if(!$err){

				// save new values to the DB
				$this->SaveOptions();

				$this->msg = __('<p class="msg">Options Saved.</p>', 'strictlysitemap');

				// still might have some error messages due to values we may have set to defaults 
				if(!empty( $errmsg )){
					$this->msg .= $errmsg;
				}


			}else{

				$this->msg = __('<p class="warn">Configuration Error.</p>', 'strictlysitemap');

				$this->msg .= $errmsg;

				$this->msg .= __('<p>Please correct any issues and re-save.</p>', 'strictlysitemap');

			}		
		}
		
		// if we haven't done so already load up the permalink structures
		if(!$this->loaded_permalinks){
			// load up permalink structures
			$this->GetSitePermalinks();
		}

		// do we do a manual rebuild now?
		if(isset($_POST['StrictlyGoogleSitemapCreate'])){			

			// manually create a sitemap now!
			$this->RebuildSitemap(true);

			// set flag so I know we just rebuild the sitemap - for showing specific reports
			$rebuilt = true;
		}else{
			$rebuilt = false;	
		}
		
		// create a nonce for the AJAX calls 
		$nonce = wp_create_nonce("strictly-google-sitemap-nonce"); 

		echo '
			<style type="text/css">
				#StrictlySitemapAdmin{
					overflow:auto;
				}
				div.StrictlyMsg{										 
					font-weight:bold; 					
				}
				p.msg{
					font-weight:bold;
					color:green;
				}
				p.warn{
					font-weight:bold;
					color:#CC0000;
				}
				div.Cron{
					border:2px solid #000; 
					padding:8px; 
					font-weight:bold; 
					margin:10px;
				}
				div.hide{
					display:none;
				}
				div.show{
					display:block;
				}

				#StrictlySitemapAdmin h3 {
					font-size:12px;
					font-weight:bold;
					line-height:1;
					margin:0;
					padding:7px 9px 4px;
				}
				div.inside{
					padding: 10px;
				}
				div.exc_cats{
					border-color:#CEE1EF; 
					border-style:solid; 
					border-width:2px; 
					height:10em;
					margin:5px 0px 5px 40px; 
					overflow:auto; 
					padding:0.5em 0.5em;
				}
				img.arrowicon{
					cursor:pointer;
					margin-right:10px;
				}

				div label:first-child{					
					display:	inline-block;
					width:		250px;
				}
				span.notes{
					padding-left:	5px;
					font-size:		0.8em;					
				}
			</style>
			<script type="text/javascript">


			function validatesitemap(idx,u,g){				

				var output = "validatesitemap" + ((idx) ? "index" : "" ) + "result" + ((g) ? "gzip" : "");
				var url = "'. $this->sitemap_validator_url . '?_ajax_nonce=' . $nonce . '&url="+u+"&index="+ ((idx)?1:0);

				ShowDebug("url = " + url);

				jQuery.ajax({
					url: url,
					type: "GET",					
					dataType: "json",
					success : function(data){
						ShowDebug("data back from AJAX = " + data);
						ShowDebug("result = " + data.validateresult);
						ShowDebug("valid = " + data.valid);
						ShowDebug("typeof = " + typeof(data));
						ShowDebug("output to = " + output);
						var img;
						if(data.valid){
							img = "<img src=" + tickimg + " alt=\"' . __('tick','strictlysitemap') .'\" />";
						}else{
							img = "<img src=" + crossimg + " alt=\"' . __('cross','strictlysitemap') .'\" />";
						}
						document.getElementById(output).innerHTML = data.validateresult + "&nbsp;" + img;											

					}
				});

				
			}


			function togglebuild(opt){
				
				if(opt && opt.id){
					var n = opt.id.replace(/^buildopts_/,"");
					
					var c = "changefreq_" + n;
					var p = "priority_" + n;
					
					if(opt.checked){
						document.getElementById(c).disabled=false;
						document.getElementById(p).disabled=false;
						if(n=="custompages"){
							document.getElementById("sitemap_custompages").disabled=false;
						}
					}else{
						document.getElementById(c).disabled=true;
						document.getElementById(p).disabled=true;
						if(n=="custompages"){
							document.getElementById("sitemap_custompages").disabled=true;
						}
					}
					
				}
				return;
			}
			function togglecron(opt){				
				if(opt){
					
					if(opt.value=="onpost"){						
						document.getElementById("sitemap_crontypeweb").disabled=true;
						document.getElementById("sitemap_crontypecron").disabled=true;	
						document.getElementById("sitemap_period_type_min").disabled=true;
						document.getElementById("sitemap_period_type_hour").disabled=true;
						document.getElementById("sitemap_period_type_day").disabled=true;
						document.getElementById("sitemap_behindscenes").disabled=false;						
					}else{
						document.getElementById("sitemap_crontypeweb").disabled=false;	
						document.getElementById("sitemap_crontypecron").disabled=false;							
						document.getElementById("sitemap_period_type_min").disabled=false;
						document.getElementById("sitemap_period_type_hour").disabled=false;
						document.getElementById("sitemap_period_type_day").disabled=false;
						document.getElementById("sitemap_behindscenes").disabled=true;			
					}
				}
			}
			function toggleschedule(unix){
				
				if(!unix || unix.checked){
					document.getElementById("sitemap_period_type_min").disabled=true;
					document.getElementById("sitemap_period_type_hour").disabled=true;
					document.getElementById("sitemap_period_type_day").disabled=true;
				}else{
					document.getElementById("sitemap_period_type_min").disabled=false;
					document.getElementById("sitemap_period_type_hour").disabled=false;
					document.getElementById("sitemap_period_type_day").disabled=false;
				}
			}
			function togglememory(opt){				
				
				if(opt){
					document.getElementById("memory_minamount").readOnly=!opt.checked;
					document.getElementById("memory_maxamount").readOnly=!opt.checked;					
				}		
			}
			function toggleload(opt){				
				
				if(opt){
					document.getElementById("sitemap_load_value").readOnly=!opt.checked;					
				}		
			}

			function togglesql(opt){
				return;
				if(opt){
					if(document.getElementById("sitemap_rebuild_sql_option_loopless").checked){
						document.getElementById("sitemap_sql_unbuffered").checked=false;
						document.getElementById("sitemap_sql_unbuffered").disabled=true;
					}else{
						document.getElementById("sitemap_sql_unbuffered").disabled=false;
					}
				}
			}

			
			function ShowDebug(m){
				if(typeof(window.console)!="undefined"){
					console.log(m);
				}
			}

			var showimg = "' . $this->pluginurl . 'showcontent.png";
			var hideimg = "' . $this->pluginurl . 'hidecontent.png";
			var tickimg = "' . $this->pluginurl . 'tick.gif";
			var crossimg= "' . $this->pluginurl . 'cross.gif";

			// preload
			var preload = [showimg,hideimg,tickimg,crossimg];
			var images = new Array();

			for (i = 0; i < preload.length; i++){
				images[i] = new Image()
				images[i].src = preload[i];
			}


			jQuery(document).ready(function(){

				jQuery("#StrictlySitemapAdmin h3.hndle").prepend("<img src=\""+showimg+"\" alt=\"Arrow Down\" title=\"Show Details\" class=\"arrowicon\" />");
				

				jQuery("#StrictlySitemapAdmin h3.hndle").click(function() {	
					var h = jQuery(this);
					var ht = h.text();
					if(/Hide Details/.test(h.html())){
						h.html("<img src=\""+showimg+"\" alt=\"Arrow Down\" title=\"Show Details\" class=\"arrowicon\" />" + ht);
					}else{
						h.html("<img src=\""+hideimg+"\" alt=\"Arrow Up\" title=\"Hide Details\" class=\"arrowicon\" />" + ht);
					}
					
					h.next("div.inside").slideToggle("slow", function(){});
				});

				jQuery("#StrictlySitemapAdmin h3.hndle + div.inside").css("display","none");

				togglecron(document.getElementById("sitemap_onpost"));
				toggleschedule(document.getElementById("sitemap_crontypecron"));
				togglememory(document.getElementById("memory_autoconfig"));
				togglebuild(document.getElementById("buildopts_homepage"));
				togglebuild(document.getElementById("buildopts_page"));
				togglebuild(document.getElementById("buildopts_post"));
				togglesql(document.getElementById("sitemap_rebuild_sql_option_loopless"));
				togglebuild(document.getElementById("buildopts_category"));
				togglebuild(document.getElementById("buildopts_tag"));
				togglebuild(document.getElementById("buildopts_author"));	
				togglebuild(document.getElementById("buildopts_custompages"));	
				toggleload(document.getElementById("sitemap_checkload"));
			});


			jQuery(window).ready(function(){
				jQuery("#StrictlySitemapAdmin div.showonload").delay(1000).prev("h3.hndle").trigger("click");	  
			});

			</script>

			
		';


		echo '
			<div class="wrap" id="StrictlySitemapAdmin">
				<h2>' . $this->plugin_name . ' V'.$this->version . '.' . $this->build.'</h2>
				<div class="postbox"><div class="inside">
				<p>'.sprintf(__('Please check out %s for the latest news and details of other great plugins and tools.','strictlysitemap'),'<a href="' . $this->website . '" title="' . $this->company .'">' . $this->company . '</a>').'</p>
				</div></div>';
		

		ShowDebug("how many files do we have sitemap_file_count = " . $this->sitemap_file_count);

		// if we haven't just rebuilt it then show details of the files - otherwise the $msg will already contain these details along with
		// ping & validation links etc
		if(!$rebuilt){

			// how many sitemaps do we have
			if($this->sitemap_file_count == 0){

				$this->msg .= __('<p>You do not currently have a Strictly Google Sitemap associated with this site.</p>','strictlysitemap');

			}elseif($this->sitemap_file_count == 1){

				$this->msg .= sprintf(__('<p>You currently have a <a href="%s" title="View your sitemap">Strictly Google Sitemap</a> associated with this site that contains %s records.</p>','strictlysitemap'),$this->sitemap_fullurl,number_format($this->sitemap_record_count));

			}elseif($this->sitemap_file_count >= 1){

				$this->msg .= sprintf(__('<p>You currently have a <a href="%s" title="View your sitemap index file">Strictly Google Sitemap Index</a> that references %d sitemaps containing a total of %s records.</p>','strictlysitemap'),$this->sitemap_fullurl,$this->sitemap_file_count,number_format($this->sitemap_record_count));
			
			}
		}



		// do we have a rebuild date
		if(!StrictlyTools::IsNothing($this->sitemap['lastbuilt'])){
			
			// add the last build date to our report
			$this->msg .= sprintf(__('<p>The sitemap was last built at: %s </p>','strictlysitemap'),date('Y-m-d H:i:s',strtotime($this->sitemap['lastbuilt'])));

		}

		// how long did the last rebuild take
		if(isset($this->build_time) && $this->build_time != -1){			

			// add the last build date to our report
			if($this->build_time == 0){
				$this->msg .= __('<p>The last sitemap rebuild took less than a second to complete.</p>','strictlysitemap');
			}else{				
				$this->msg .= sprintf(__('<p>The last sitemap rebuild took approximately %s seconds to complete.</p>','strictlysitemap'),$this->build_time);
			}

		}

		ShowDebug("windows = " . $this->windows . " have uptime = " . $HasUptime);

		// for LINUX boxes we can show the server load before and after the last built
		if(!$this->windows){
			// ensure the uptime function is available otherwise we will never have values for this anyway
			if($HasUptime){

				ShowDebug("start load val = " . $this->start_server_load);
				ShowDebug("end load val = " . $this->end_server_load);

				// if we have values for before & after we can get a rise/fall
				if(!StrictlyTools::IsNothing($this->start_server_load) && !StrictlyTools::IsNothing($this->end_server_load)){

					ShowDebug("work out difference between start = " . $this->start_server_load . " and " . $this->end_server_load);

					// work out a rise
					$diff = $this->end_server_load - $this->start_server_load;

					ShowDebug("diff of " . $diff);

					if($diff < 0){
						$diffdesc = "(" . $diff .")"; // minus sign will already be there
					}elseif($diff>0){
						$diffdesc = "(+" . $diff .")";
					}else{
						$diffdesc = ""; // no difference so nothing to show
					}

					$this->msg .= sprintf(__('<p>The server load before the last rebuild was %s and afterwards it was %s %s</p>','strictlysitemap'),$this->start_server_load,$this->end_server_load,$diffdesc);

				}
			}
		}

		

		// do we have memory usage / db query details from last build
			
		if(!empty($this->database_queries)){
			$this->msg .= sprintf(__('<p>Database Queries Executed During Last Build: %d</p>','strictlysitemap'),$this->database_queries);
		}

		if(!empty($this->memory_usage)){
			$this->msg .= sprintf( __('<p>Memory Usage During Last Build: %s  </p>','strictlysitemap'), StrictlyTools::ConvertFromBytes($this->memory_usage));
		}	

		if(!empty($this->memory_limit)){
			$this->msg .= sprintf(__('<p>Memory Limit During Last Build: %s</p>','strictlysitemap'),StrictlyTools::ConvertFromBytes($this->memory_limit)) ;
		}						

		// do we have any memory config messages e.g did we reach a limit or have to increase it
		if(!empty($this->memory_msg)){
			$this->msg .= $this->memory_msg;
		}
			

		if(!empty($this->msg)){
			echo	'<div class="postbox">
						<h3 class="hndle">'.__('Plugin Information', 'strictlysitemap').'</h3>
						<div class="inside StrictlyMsg showonload">'.$this->msg.'</div>
					</div>';
		}
		
		// run SEO / permalink test
		if(!$this->PermalinkSEOTest()){
			echo	'<div class="postbox">
						<h3 class="hndle">'.__('Permalink Analysis Report', 'strictlysitemap').'</h3>
						<div class="inside StrictlyMsg showonload">'.$this->permalink_test_msg.'</div>
					</div>';
		}
		
		// run robots.txt test
		if(!$this->CheckRobots()){
			echo	'<div class="postbox">
						<h3 class="hndle">'.__('Robots.txt Report', 'strictlysitemap').'</h3>
						<div class="inside StrictlyMsg showonload">'.$this->robot_msg .'</div>
					</div>';				
		}
		// check whether WP-O-Matic is being used by the blog
		if($this->sitemap['rebuild']=='onpost'){
			
			// check some of the biggest most popular plugins that can cause problems when used in conjuntion with
			// a sitemap builder that rebuilds as posts are addded
			$yarpp		= $this->CheckYARPP();
			$wpomatic	= $this->CheckWPOMatic();

			if($wpomatic || $yarpp){
				// setup a message with our recommendation to use a scheduled rebuild
				echo	'<div class="postbox">
							<h3 class="hndle">'.__('Plugin Recommendations', 'strictlysitemap').'</h3>
							<div class="inside StrictlyMsg showonload">';

							if($wpomatic){							
								echo '<p>' . __('The system detects that you are or have been using WP-O-Matic to import content into your site and therefore recommends that you build your sitemaps at scheduled intervals rather than on each post. If you choose the latter option and import 10 articles you will create unneccessary overhead on your server by rebuilding the sitemap 10 times rather than once. Using a scheduled rebuild option resolves this problem.','strictlysitemap').'</p>';
							}
							if($yarpp){							
								echo '<p>' . __('The system detects that you are or have been using the Yet Another Related Post Plugin to return related posts for each article. Whilst this is a good plugin for generating related links it can cause performance problems due to the large database queries it runs to calculate relevant posts. As this calculation is carried out whenever a new post is added it can cause a bottleneck and is a good reason why you should build your sitemap at scheduled intervals rather than whenever a post is published.','strictlysitemap').'</p>';
							}

				echo	'	</div>
						</div>';
			}
		}

		// do we show the divs on load
		if($showSEOrpt){
			$seorptclass = " showonload";
		}else{
			$seorptclass = "";
		}
		if($showSEOidx){
			$seoindxclass = " showonload";
		}else{
			$seoindxclass = "";
		}

		// outtput seo report
		echo '			
				<form method="post" action="">
					<div class="postbox">						
						<h3 class="hndle">'.__('Homepage SEO Analysis Report', 'strictlysitemap').'</h3>					
						<div class="inside' . $seorptclass . '">
							<p>' . (StrictlyTools::IsNothing($this->seo_report_date) ? __("The SEO Report has never be run.","strictlysitemap") : sprintf(__("Last Report Date: %s","strictlysitemap"),date('Y-m-d H:i:s',strtotime($this->seo_report_date)) ) ) . '</p>
							<p>'.__("This report will give you a detailed analysis of your websites homepage including content analysis, meta checks, search engine statistics and other important data that will help you increase your SEO rankings.","strictlysitemap") .'</p>
							'. $this->seo_report . '	
							<div class="submit">
								<input type="submit" name="StrictlyGoogleSitemapSEO" id="seo_sm" value="'.__('Run Homepage SEO Report', 'strictlysitemap').'" />
							</div>
						</div>
					</div>
					<div class="postbox">						
						<h3 class="hndle">'.__('Search Engine Index Coverage Report', 'strictlysitemap').'</h3>					
						<div class="inside' . $seoindxclass . '">
							'. $this->seo_index_report . '													
							<ul>								
								<li>'.__('Ask Index','strictlysitemap').': ' . StrictlyTools::RepBlank($this->seo_ask_index,"NA") . ' (' . $this->seo_ask_index_coverage . '%)</li>
								<li>'.__('Bing Index','strictlysitemap').': ' . StrictlyTools::RepBlank($this->seo_bing_index,"NA") . ' (' . $this->seo_bing_index_coverage . '%)</li>
								<li>'.__('Google Index','strictlysitemap').': ' . StrictlyTools::RepBlank($this->seo_google_index,"NA") . ' (' . $this->seo_google_index_coverage . '%)</li>
								<li>'.__('Yahoo Index','strictlysitemap').': ' . StrictlyTools::RepBlank($this->seo_yahoo_index,"NA") . ' (' . $this->seo_yahoo_index_coverage . '%)</li>
							</ul>
							<small>'.__('These statistics show how many pages from your site have been indexed by each major search engine. The page counts are taken from their results in real time and the values may vary between reports due to the data center that handles the request.','strictlysitemap').'</small>	
							<div class="submit">
								<input type="submit" name="StrictlyGoogleSitemapIndexReport" id="seo_sm" value="'.__('Run Index Coverage Report', 'strictlysitemap').'" />
							</div>
						</div>
					</div>
				</form>';

		if($showConfig){
			$configclass = " showonload";
		}else{
			$configclass = "";
		}

		echo	'<form method="post" action="">
					<div class="postbox">
						<input type="hidden" name="sitemap[lastbuilt]" id="sitemap_lastbuilt" value="' . ((StrictlyTools::IsNothing($this->sitemap['lastbuilt'])) ? "" : $this->sitemap['lastbuilt']) . '" />
						<h3 class="hndle">'.__('Main Sitemap Options', 'strictlysitemap').'</h3>					
						<div class="inside' . $configclass . '">
							<p>'.__('These settings control when and how often the sitemap is rebuilt. Disable Auto Build if you only want to create the sitemap manually using this page.','strictlysitemap').'</p>					
							<div>
								<label for="sitemap_enabled">'.__('Enable Auto Build', 'strictlysitemap').'</label><input type="checkbox" name="sitemap[enabled]" id="sitemap_enabled" value="true" ' . (($this->sitemap['enabled']) ? 'checked="checked"' : '') . '/>			
							</div>
							<div>
								<label for="uninstall">'.__('Uninstall Plugin when deactivated', 'strictlysitemap').'</label><input type="checkbox" name="uninstall" id="uninstall" value="true" ' . (($this->strictly_uninstall) ? 'checked="checked"' : '') . '/>								
							</div>
							<p>'.__('Please only change the following settings if you really need to as it\'s recommended (although not necessary) to store the sitemap in your root directory as this proves you control the site and all the files within. If you do change the location you may need to <a href="http://googlewebmastercentral.blogspot.com/2008/01/sitemaps-faqs.html">set-up webmaster accounts</a> for each search engine you want to submit to.','strictlysitemap').'</p>	
							<div>								
								<label for="sitemap_name">'.__('Sitemap Filename', 'strictlysitemap').'</label><input type="text" name="sitemap_name" id="sitemap_name" value="' . esc_attr($this->FormatSitemapName($this->sitemap_name,true)) . '" size="50" maxlength="50" /><span class="notes">'.__('e.g sitemap.xml','strictlysitemap').'</span>						
							</div>
							<div>								
								<label for="sitemap_path">'.__('Sitemap Path', 'strictlysitemap').'</label><input type="text" name="sitemap_path" id="sitemap_path" value="' . esc_attr($this->sitemap_path) . '" size="50" maxlength="255" /><span class="notes">'.sprintf(__('e.g %s','strictlysitemap'),$this->rootpath).'</span>														
							</div>
							<div>								
								<label for="sitemap_url">'.__('Sitemap URL', 'strictlysitemap').'</label><input type="text" name="sitemap_url" id="sitemap_url" value="' .esc_attr($this->sitemap_url) . '" size="50" maxlength="255" /><span class="notes">'.sprintf(__('e.g %s','strictlysitemap'),$this->siteurl).'</span> 
							</div>
							<p>'.__('If Auto Build has been enabled then you can either choose to rebuild the sitemap at scheduled intervals by using a Cron or WebCron job (recommended) or automatically whenever a post or page is published or removed (not recommended on busy or large sites that import content).','strictlysitemap').'</p>
							<div>
								<label for="sitemap_rebuild_onpost">'.__('Rebuild Type', 'strictlysitemap').'</label>
								<input type="radio" name="sitemap[rebuild]" id="sitemap_rebuild_onpost" value="onpost" ' . (($this->sitemap['rebuild']=='onpost') ? 'checked="checked"' : '') . ' onclick="togglecron(this);" />
								<label for="sitemap_rebuild_onpost">'.__('Rebuild on Post Save', 'strictlysitemap').'</label>
								<input type="radio" name="sitemap[rebuild]" id="sitemap_rebuild_oncron" value="oncron" ' . (($this->sitemap['rebuild']=='oncron') ? 'checked="checked"' : '') . 'onclick="togglecron(this);" />
								<label for="sitemap_rebuild_oncron">'.__('Rebuild at scheduled intervals', 'strictlysitemap').'</label>
							</div> 							
							<div>
								<label for="sitemap_behindscenes">'.__('Rebuild behind the scenes', 'strictlysitemap').'</label>
								<input type="checkbox" name="sitemap[behindscenes]" id="sitemap_behindscenes" value="true" ' . (($this->sitemap['behindscenes']) ? 'checked="checked"' : '') . ' " /><span class="notes">'.__('speeds up posting','strictlysitemap').'
							</div>						
						</div>
					</div>
					<div class="postbox">
						<h3 class="hndle">'.__('Cron Schedule Options', 'strictlysitemap').'</h3>
						<div class="inside' . $configclass . '">
							<p>'.__('If you require the sitemap to be built precisely at a specified interval then it\'s recommended to use a UNIX style cron job which you will need to setup on your webserver. If you don\'t have access to your server then you could sign up to one of the many <a href="http://www.google.co.uk/search?q=Web+Cron+Service">WebCron services</a> that are available. If none of those options are feasible then you can use the Strictly Sitemap Cron option which will carry out the rebuild whenever a page is requested around the scheduled interval. This option has a downside in that if no page is requested for a while then the sitemap won\'t be built however the upside is that you can control the schedule from this page.','strictlysitemap').'</p>
							<p>'.__('Cron Command','strictlysitemap').'</p>
							<div class="Cron">*/20 * * * * '. StrictlyTools::GetCommand() . ' ' . attribute_escape($this->cronurl) . '</div>
							<p>'.__('WebCron Ready URL','strictlysitemap').'</p>
							<div class="Cron">' . attribute_escape($this->cronurl) . '</div>					
							<div>
								<label for="sitemap_crontypecron">'.__('Scheduler Method', 'strictlysitemap').'</label>
								<input type="radio" name="sitemap[crontype]" id="sitemap_crontypecron" value="cron" ' . (($this->sitemap['crontype']=='cron' || empty($this->sitemap['crontype'])) ? 'checked="checked"' : '') . ' onclick="toggleschedule(this);" />
								<label for="sitemap_crontypecron">'.__('UNIX Cron', 'strictlysitemap').'</label>
								<input type="radio" name="sitemap[crontype]" id="sitemap_crontypeweb" value="webcron" ' . (($this->sitemap['crontype']=='webcron') ? 'checked="checked"' : '') . ' onclick="toggleschedule(document.getElementById(\'sitemap_crontypecron\'));" />
								<label for="sitemap_crontypeweb">'.__('Strictly Sitemap Cron', 'strictlysitemap').'</label>	
							</div>					
							<div>
								<label for="sitemap_period_type_min">'.__('Rebuild Interval', 'strictlysitemap').'</label>
								<input type="radio" name="sitemap[period_type]" id="sitemap_period_type_min" value="M" ' . (($this->sitemap['period_type']=='M' || empty($this->sitemap['period_type'])) ? 'checked="checked"' : '') . ' />					
								<label for="sitemap_period_type_min">'.__('Every 20 Mins', 'strictlysitemap').'</label>
								<input type="radio" name="sitemap[period_type]" id="sitemap_period_type_hour" value="H" ' . (($this->sitemap['period_type']=='H') ? 'checked="checked"' : '') . ' />
								<label for="sitemap_period_type_hour">'.__('Every Hour', 'strictlysitemap').'</label>
								<input type="radio" name="sitemap[period_type]" id="sitemap_period_type_day" value="D" ' . (($this->sitemap['period_type']=='D') ? 'checked="checked"' : '') . ' />
								<label for="sitemap_period_type_day">'.__('Every Day', 'strictlysitemap').'</label>
							</div>
						</div>
					</div>
					<div class="postbox">
						<h3 class="hndle">'.__('Memory Management &amp; Performance Configuration','strictlysitemap').'</h3>
						<div class="inside' . $configclass . '">
							<p>'.__('The Strictly Google Sitemap plugin has specifically been developed with performance in mind however you may want to ensure that the sitemap rebuild operation always has enough memory to carry out it\'s operation even under high loads and very poor server performance. Enabling Auto Config ensures that the memory allocated to the plugin is constantly adjusted to an appropriate level which will be either 15% or 15MB above the last known usage level (depending on which is bigger). You can set the minimum and maxiumum memory allocation limits here to ensure the memory used is within a certain range. You can also set the maximum amount to -1 which means there is no upper limit.','strictlysitemap').'</p>
							<div>
								<label for="memory_autoconfig">'.__('Auto Config Memory Limit', 'strictlysitemap').'</label><input type="checkbox" name="memory_autoconfig" id="memory_autoconfig" value="true" ' . (($this->memory_autoconfig) ? 'checked="checked"' : '') . ' onclick="togglememory(this)" />	
							</div>
							<div>
								<label for="memory_minamount">'.__('Min Memory Usage', 'strictlysitemap').'</label><input type="text" name="memory_minamount" id="memory_minamount" value="' . ((empty($this->memory_minamount)) ? StrictlyTools::FormatSize($this->bottom_memory_limit) : StrictlyTools::FormatSize($this->memory_minamount)) . '" /><span class="notes">' . __('e.g 8MB', 'strictlysitemap').'				
							</div>
							<div>
								<label for="memory_maxamount">'.__('Max Memory Usage', 'strictlysitemap').'</label><input type="text" name="memory_maxamount" id="memory_maxamount" value="' . ((empty($this->memory_maxamount)) ? StrictlyTools::FormatSize($this->default_memory_limit) : StrictlyTools::FormatSize($this->memory_maxamount)) . '" /><span class="notes">'. __('e.g 128MB or set to -1 for no upper limit', 'strictlysitemap').'				
							</div>
							<p>'.__('The Strictly Google Sitemap has been developed to utilise the database a lot more than existing sitemap plugins and it does this by moving the build process from PHP to MySQL and by making a tiny number of queries and other function calls. You should experiment with both build methods to see which one performs best for your system but the default setting <em>Build XML with SQL</em> should perform well for small or large sites. If however you have a small site which doesn\'t require a sitemap index and multiple sitemaps you might find the <em>Build and Join XML with SQL</em> option performs better.','strictlysitemap').'</p>
							<div>
								<label for="sitemap_rebuild_sql_option_loopless">'.__('Build Method', 'strictlysitemap').'</label>
								<input type="radio" name="sitemap[sql_option]" id="sitemap_rebuild_sql_option_loopless" value="loopless" ' . (($this->sitemap['sql_option']=='loopless') ? 'checked="checked"' : '') . ' onclick="togglesql(this);" />
								<label for="sitemap_rebuild_sql_option_loopless">'.__('Build and Join XML with SQL', 'strictlysitemap').'</label>
								<input type="radio" name="sitemap[sql_option]" id="sitemap_rebuild_sql_option_loop" value="loop" ' . (($this->sitemap['sql_option']=='loop' || empty($this->sitemap['sql_option'])) ? 'checked="checked"' : '') . ' onclick="togglesql(this);"  />
								<label for="sitemap_rebuild_sql_option_loop">'.__('Build XML with SQL (join with PHP)', 'strictlysitemap').'</label>
							</div>';


							// due to the issue with mysql_unbuffered_query and CONCAT I am disabling this functionality for now
							if(1==0){
							//if(floatval($this->wp_version)>=2.2){ 
							echo '<div>
									<label for="sitemap_sql_unbuffered">'.__('Disable MySQL Buffering', 'strictlysitemap').'</label><input type="checkbox" name="sitemap[sql_unbuffered]" id="sitemap_sql_unbuffered" value="true" ' . (($this->sitemap['sql_unbuffered']) ? 'checked="checked"' : '') . '  /><span class="notes">'.__('Speeds up build times. Only available with the Build XML with SQL (join with PHP) option</small>','strictlysitemap').'</span>
								</div>';
							}
							
							echo '<p>'.sprintf(__('The Strictly Google Sitemap has been designed to run a lot quicker than other sitemap plugins as it doesn\'t have to make as many database queries or carry out as lots of formatting functions. However on very slow machines or servers under duress it may take a while to run in which case you should extend the timeout above the default value of %d.','strictlysitemap'),$this->default_timeout) .'	</p>
							<div>
								<label for="sitemap_timeout">'.__('Script Timeout in Seconds', 'strictlysitemap').'</label><input type="text" name="sitemap[timeout]" id="sitemap_timeout" value="' . ((empty($this->sitemap['timeout'])) ? $this->default_timeout : $this->sitemap['timeout']) . '" size="3" maxlength="5" /><span class="notes">' . __(' e.g 120','strictlysitemap') .'</span>								
							</div>'; 

							if(!$this->windows){

								echo '<p>'.__('Depending on your server setup the system can check your current server load to ensure that it\'s not above a certain level before running the sitemap job. If your webserver is under duress with a high server load then it\'s probably not wise to rebuild your sitemap especially if it has a large number of records.','strictlysitemap').'</p>';

								
								if(!$HasUptime){

									echo '<p class="warn">'.__('You do not seem to be able to run the system command shell_exec which is required for this feature to work correctly.','strictlysitemap').'</p>';

									$cl = "disabled=\"disabled\" ";
									$lv = "readonly=\"readonly\" ";
								}else{
									$cl = $lv = "";
								}


								echo	'<div>
											<label for="sitemap_checkload">'.__('Check load before rebuilding', 'strictlysitemap').'</label><input type="checkbox" ' . $cl . ' name="sitemap[checkload]" id="sitemap_checkload" value="true" ' . (($this->sitemap['checkload']) ? 'checked="checked"' : '') . ' onclick="toggleload(this);" />											
										</div>
										<div>
											<label for="load_value">'.__('Max Average Server Load to allow', 'strictlysitemap').'</label><input type="text" ' . $lv . ' name="sitemap[load_value]" id="sitemap_load_value" value="' . ((empty($this->sitemap['load_value'])) ? 1.0 : $this->sitemap['load_value']) . '" /><span class="notes">'.__('e.g 1.0','strictlysitemap').'</span>
										</div>';
									
								
							}
	

						echo '
						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle">'.__('Build Options', 'strictlysitemap').'</h3>
						<div class="inside' . $configclass . '">
							<div>
								<label for="buildopts_homepage">'.__('Include Homepage', 'strictlysitemap').'</label><input type="checkbox" name="buildopts[homepage]" id="buildopts_homepage" value="true" ' . (($this->buildopts['homepage']) ? 'checked="checked"' : '') . ' onclick="togglebuild(this)" />	
							</div>					
							<div>
								<label for="buildopts_post">'.__('Include Articles', 'strictlysitemap').'</label><input type="checkbox" name="buildopts[posts]" id="buildopts_post" value="true" ' . (($this->buildopts['posts']) ? 'checked="checked"' : '') . ' onclick="togglebuild(this)" />		
							</div>							
							<div>
								<label for="buildopts_page">'.__('Include Pages', 'strictlysitemap').'</label><input type="checkbox" name="buildopts[pages]" id="buildopts_page" value="true" ' . (($this->buildopts['pages']) ? 'checked="checked"' : '') . ' onclick="togglebuild(this)" />		
							</div>
							<div>
								<label for="buildopts_category">'.__('Include Categories', 'strictlysitemap').'</label><input type="checkbox" name="buildopts[categories]" id="buildopts_category" value="true" ' . (($this->buildopts['categories']) ? 'checked="checked"' : '') . ' onclick="togglebuild(this)" />	
							</div>
							<div>
								<label for="buildopts_tag">'.__('Include Tags', 'strictlysitemap').'</label><input type="checkbox" name="buildopts[tags]" id="buildopts_tag" value="true" ' . (($this->buildopts['tags']) ? 'checked="checked"' : '') . ' onclick="togglebuild(this)" />		
							</div>
							<div>
								<label for="buildopts_author">'.__('Include Authors', 'strictlysitemap').'</label><input type="checkbox" name="buildopts[authors]" id="buildopts_author" value="true" ' . (($this->buildopts['authors']) ? 'checked="checked"' : '') . ' onclick="togglebuild(this)" />
							</div>	
							<div>
								<label for="buildopts_archive">'.__('Include Archives', 'strictlysitemap').'</label><input type="checkbox" name="buildopts[archives]" id="buildopts_archive" value="true" ' . (($this->buildopts['archives']) ? 'checked="checked"' : '') . ' onclick="togglebuild(this)" />	
							</div>	
							<div>
								<label for="buildopts_custompages">'.__('Include Custom Pages', 'strictlysitemap').'</label><input type="checkbox" name="buildopts[custompages]" id="buildopts_custompages" value="true" ' . (($this->buildopts['custompages']) ? 'checked="checked"' : '') . ' onclick="togglebuild(this)" />							
							</div>
						</div>
					</div>	

					<div class="postbox">
						<h3 class="hndle">'.__('Change Frequency', 'strictlysitemap').'</h3>
						<div class="inside' . $configclass . '">
							<p>'.__('This option sets the period in which changes are expected for the item.', 'strictlysitemap').'</p>
							<div>
								<label for="changefreq_homepage">'.__('Homepage', 'strictlysitemap').'</label>' . StrictlyTools::drawlist("changefreq[homepage]","changefreq_homepage",$this->changefreq_options,$this->changefreq['homepage']) . '		
							</div>
							<div>
								<label for="changefreq_post">'.__('Articles', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("changefreq[post]","changefreq_post",$this->changefreq_options,$this->changefreq['post']) . '
							</div>
							<div>
								<label for="changefreq_page">'.__('Pages', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("changefreq[page]","changefreq_page",$this->changefreq_options,$this->changefreq['page']) . '
							</div>
							<div>
								<label for="changefreq_category">'.__('Categories', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("changefreq[category]","changefreq_category",$this->changefreq_options,$this->changefreq['category']) . '
							</div>
							<div>
								<label for="changefreq_tag">'.__('Tags', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("changefreq[tag]","changefreq_tag",$this->changefreq_options,$this->changefreq['tag']) . '	
							</div>
							<div>
								<label for="changefreq_author">'.__('Authors', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("changefreq[author]","changefreq_author",$this->changefreq_options,$this->changefreq['author']) . '	
							</div>
							<div>
								<label for="changefreq_archive">'.__('Archives', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("changefreq[archive]","changefreq_archive",$this->changefreq_options,$this->changefreq['archive']) . '	
							</div>
							<div>
								<label for="changefreq_custompages">'.__('Custom Pages', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("changefreq[custompages]","changefreq_custompages",$this->changefreq_options,$this->changefreq['custompages']) . '	
							</div>
						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle">'.__('Priority', 'strictlysitemap').'</h3>
						<div class="inside' . $configclass . '">
							<p>'.__('This option sets the priority for the item.', 'strictlysitemap').'</p>
							<div>
								<label for="priority_homepage">'.__('Homepage', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("priority[homepage]","priority_homepage",$this->priority_options,$this->priority['homepage']) . '		
							</div>
							<div>
								<label for="priority_post">'.__('Articles', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("priority[post]","priority_post",$this->priority_options,$this->priority['post']) . '			
							</div>
							<div>
								<label for="priority_page">'.__('Pages', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("priority[page]","priority_page",$this->priority_options,$this->priority['page']) . '			
							</div>
							<div>
								<label for="priority_category">'.__('Categories', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("priority[category]","priority_category",$this->priority_options,$this->priority['category']) . '			
							</div>
							<div>
								<label for="priority_tag">'.__('Tags', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("priority[tag]","priority_tag",$this->priority_options,$this->priority['tag']) . '				
							</div>
							<div>
								<label for="priority_author">'.__('Authors', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("priority[author]","priority_author",$this->priority_options,$this->priority['author']) . '				
							</div>
							<div>
								<label for="priority_archive">'.__('Archives', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("priority[archive]","priority_archive",$this->priority_options,$this->priority['archive']) . '				
							</div>
							<div>
								<label for="priority_custompages">'.__('Custom Pages', 'strictlysitemap').'</label>'. StrictlyTools::drawlist("priority[custompages]","priority_custompages",$this->priority_options,$this->priority['custompages']) . '		
							</div>
						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle">'.__('Custom Pages','strictlysitemap').'</h3>
						<div class="inside' . $configclass . '">
							<p>'.__('If you have some custom pages within this domain that you would like to be added to the sitemap then please add them here. Make sure you put each URL on its own line and if you want to add a specific change frequency or priority for the page then you can just add those values after the URL in the format: [URL] [Priority] [ChangeFrequency]','strictlysitemap').'</p>
							<textarea style="width:600px;height:200px;" name="sitemap[custompages]" id="sitemap_custompages">' . ((empty($this->sitemap['custompages'])) ? "http://www.examplesite.com/page 0.8 weekly" : esc_attr($this->sitemap['custompages'])) . '</textarea>							
						</div>
					</div>';


			echo '
					<div class="postbox">
						<h3 class="hndle">'.__('Filter Options','strictlysitemap').'</h3>
						<div class="inside' . $configclass . '">
							<p>'.__('If you don\'t want to include your full site in the sitemap then you can choose to filter the posts, pages, categories and authors that are included by using the following options.','strictlysitemap').'</p>
							<p>'.__('Exclude Posts belonging to the selected categories and prevent the same categories from appearing in the category section of the sitemap.', 'strictlysitemap').'</p>
							<div class="exc_cats" >
							<ul>';
								StrictlyPlugin::wp_category_checklist(0,0,$this->sitemap["excluded_cats_arr"],false); 					

					echo '	</ul>
							</div>
							<div>
							<label for="sitemap_excluded_posts">'.__('Exclude Posts or Pages', 'strictlysitemap').'</label><input type="text" name="sitemap[excluded_posts]" id="sitemap_excluded_posts" value="' . esc_attr($this->sitemap['excluded_posts']) . '" /><span class="notes">'.__('enter a comma separated list of post ID\'s e.g 481,2345','strictlysitemap').'</span>				
							</div>
							<div>
							<label for="sitemap_excluded_authors">'.__('Exclude Author Content', 'strictlysitemap').'</label><input type="text" name="sitemap[excluded_authors]" id="sitemap_excluded_authors" value="' . esc_attr($this->sitemap['excluded_authors']) . '" /><span class="notes">' . __('enter a comma separated list of author names e.g Admin, Dan Smith, Joey Deacon', 'strictlysitemap').'</span>						
							</div>
						</div>
					</div>

					<div class="postbox">						
						<h3 class="hndle">'.__('Sitemap Options', 'strictlysitemap').'</h3>
						<div class="inside' . $configclass . '">';
				echo '					
						<div>
							<label for="robotstxt">'.__('Add Sitemap link to Robots.txt', 'strictlysitemap').'</label><input type="checkbox" name="sitemap[robotstxt]" id="sitemap_robotstxt" value="true" ' . (($this->sitemap['robotstxt']) ? 'checked="checked"' : '') . '/>							
						</div>';
			
			if($this->IsGzipEnabled()){
					
				echo '
						<div>
							<label for="sitemap_gzip">'.__('Create GZip Version', 'strictlysitemap').'</label><input type="checkbox" name="sitemap[gzip]" id="sitemap_gzip" value="true" ' . (($this->sitemap['gzip']) ? 'checked="checked"' : '') . '/>							
						</div>';
			}
	
			echo '
					
						<div>
							<label for="sitemap_records">'.__('Records Per Sitemap', 'strictlysitemap').'</label><input type="text" name="sitemap[records]" id="sitemap_records" value="' . ((empty($this->sitemap['records']) || !is_numeric($this->sitemap['records'])) ? $this->records_per_sitemap_default : $this->sitemap['records']) . '" /><span class="notes">'.__('max value is 50,000','strictlysitemap').'</span>							
						</div>
						</div>
					</div>';

			echo	'<div class="postbox">
						<h3 class="hndle">'.__('Search Engine Notifications', 'strictlysitemap').'</h3>
						<div class="inside' . $configclass . '">
							<div>
								<label for="pingopts_ask">'.__('Notify Ask', 'strictlysitemap').'</label><input type="checkbox" name="pingopts[ask]" id="pingopts_ask" value="true" ' . (($this->pingopts['ask']) ? 'checked="checked"' : '') . '/>								
							</div>
							<div>
								<label for="pingopts_bing">'.__('Notify Bing', 'strictlysitemap').'</label><input type="checkbox" name="pingopts[bing]" id="pingopts_bing" value="true" ' . (($this->pingopts['bing']) ? 'checked="checked"' : '') . '/>
							</div>
							<div>
								<label for="pingopts_google">'.__('Notify Google', 'strictlysitemap').'</label><input type="checkbox" name="pingopts[google]" id="pingopts_google" value="true" ' . (($this->pingopts['google']) ? 'checked="checked"' : '') . '/>
							</div>
							<div>
								<label for="pingopts_yahoo">'.__('Notify Yahoo', 'strictlysitemap').'</label><input type="checkbox" name="pingopts[yahoo]" id="pingopts_yahoo" value="true" ' . (($this->pingopts['yahoo']) ? 'checked="checked"' : '') . '/>
							</div>
							<div>
								<label for="pingopts_yahoo_api">'.__('Yahoo Application ID', 'strictlysitemap').'</label>
								<input type="text" name="pingopts[yahoo_api]" id="pingopts_yahoo_api" value="' . ((StrictlyTools::IsNothing($this->pingopts['yahoo_api'])) ? "" : $this->pingopts['yahoo_api']) . '" />
								<a href="https://developer.apps.yahoo.com/wsregapp/">'.__('Request Yahoo Application ID','strictlysitemap').'</a>
							</div>							
						</div>
					</div>

					<div class="postbox">	
						<h3 class="hndle">'.__('Update Strictly Sitemap Configuration', 'strictlysitemap').'</h3>
						<div class="inside showonload">
							<div class="submit">
									<input type="submit" name="StrictlyGoogleSitemapSaveOptions" id="save_sm" value="'.__('Save Options', 'strictlysitemap').'" />
									<input type="reset" name="StrictlyGoogleSitemapResetOptions" id="reset_sm" value="'.__('Reset Options','strictlysitemap').'" onclick="return confirm(\''.__('Are you sure you want to clear any configuration changes you have made?','strictlysitemap').'\');" />
									<input type="submit" name="SetSystemDefaults" id="SetSystemDefaults" value="'.__('Set to Default','strictlysitemap').'" onclick="return confirm(\''.__('Are you sure you want to rest all options to the system defaults?','strictlysitemap').'\');" />
							</div>
						</div>
					</div>
				</form>';
		echo '
				<div class="postbox">	
					<h3 class="hndle">'.__('Create Sitemap manually', 'strictlysitemap').'</h3>
					<div class="inside showonload">
						<form method="post" action="">
							<div class="submit">
								<input type="submit" name="StrictlyGoogleSitemapCreate" value="'.__('Build Sitemap', 'strictlysitemap').'" />
							</div>
						</form>
					</div>
				</div>';


		if($this->show_donate){

			echo	'<div class="postbox">
						<h3 class="hndle">'.__('Donate to Stictly Software', 'strictlyautotags').'</h3>
						<div class="inside showonload">';
			echo		'<p>'.__('Your donations help ensure that my plugins continue to be free to use and any amount is appreciated.', 'strictlyautotags').'</p>';
			
				echo	'<div style="text-align:center;"><br />
							<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><br />
							<input type="hidden" name="cmd" value="_s-xclick"><br />
							<input type="hidden" name="hosted_button_id" value="6427652"><br />
							<input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
							<br /></form>
						</div>
					</div>
				</div>';
		}

			echo	'<div class="postbox">
						<h3 class="hndle">'.__('Strictly Software Recommendations', 'strictlyautotags').'</h3>
						<div class="inside">';

			echo	'<p>'.__('If you enjoy using this Wordpress plugin you might be interested in some other websites, tools and plugins I have		developed.', 'strictlyautotags').'</p>
					<ul>
						<li><a href="http://wordpress.org/extend/plugins/strictly-tweetbot/">'.__('Strictly Tweetbot','strictlysitemap').'</a>
							<p>'.__('Strictly Tweetbot allows you to post custom formatted tweets to multiple Twitter accounts whenever new articles are posted on your site. This plugin allows you to target tweets to specific content so that posts are only made if certain words are contained with an article and uses the new OAuth method of authentication which is easy to setup and control.','strictlysitemap').'</p>
						</li>
						<li><a href="http://wordpress.org/extend/plugins/strictly-autotags/">'.__('Strictly Auto Tags','strictlysitemap').'</a>
							<p>'.__('Strictly Auto Tags is a popular Wordpress plugin that automatically adds the most relevant tags to published posts.','strictlysitemap').'</p>
						</li>
						<li><a href="http://wordpress.org/extend/plugins/strictly-system-check/">'.__('Strictly System Check','strictlysitemap').'</a>
							<p>'.__('Strictly System Check is a Wordpress plugin that allows you to automatically check your sites status at scheduled intervals to ensure it\'s running smoothly. It will run some system checks on your site and server and send you an email if anything doesn\'t meet your requirements.','strictlyautotags').'</p>
						</li>
						<li><a href="http://www.strictly-software.com/online-tools">'.__('Strictly Online Tools','strictlysitemap').'</a>
							<p>'.__('Strictly Online Tools is a suite of free online tools I have developed which include encoders, unpackers, translators, compressors, scanners and much more.','strictlysitemap').'</p>
						</li>
						<li><a href="http://www.hattrickheaven.com">'.__('Hattrick Heaven','strictlysitemap').'</a>
							<p>'.__('If you like football then this site is for you. Get the latest football news, scores and league standings from around the web on one site. All content is crawled, scraped and reformated in real time so there is no need to leave the site when following news links. Check it out for yourself. ','strictlysitemap').'</p>
						</li>
						<li><a href="http://www.fromthestables.com">'.__('From The Stables','strictlysitemap').'</a>
							<p>'.__('If you like horse racing or betting and want that extra edge when using Betfair then this site is for you. It\'s a members only site that gives you inside information straight from the UK\'s top racing trainers every day.','strictlysitemap').'</p>
						</li>
						<li><a href="http://www.darkpolitricks.com">'.__('Dark Politricks','strictlysitemap').'</a>
							<p>'.__('Tired of being fed news from inside the box? Want to know the important news that the mainstream media doesn\'t want to report on? Then this site is for you. Alternative news, comment and analysis all in one place.','strictlysitemap').'</p>
						</li>						
					</ul>
					</div>
				</div>';
		

		echo '<div>';
	}

	/**
	 * Tests the sites current permalink settings to see whether they pose potential problems in terms of SEO or performance
	 *
	 * @return boolean
	 */
	protected function PermalinkSEOTest(){

		$this->permalink_test_msg = "<br />";

		// check that the blog is set up to be visible to robots
		if(StrictlyPlugin::get_option('blog_public')!=1) {

			$this->permalink_test_msg .= __('<p>Your current <a href="options-privacy.php">privacy settings</a> are preventing search engines from crawling your site.</p>','strictlysitemap');
		}

		// check whether the site is using a permalink
		if(!$this->do_post_rewrite){
			$this->permalink_test_msg .= __('<p>You are not currently using SEO friendly URL\'s for your posts. Consider enabling them in the Permalink options panel.</p>','strictlysitemap');
		}
			
		// I notice from live tests that %post_tag% doesn't work even if the documentation says it should probably because you can have any number of tags!
		// so if the permalink structure contains %tag% raise an error to the user! 
		if(preg_match("@%tag%@",$this->post_rewrite)){
			$this->permalink_test_msg .= __('<p>Your post permalink structure contains %tag% which is not supported by Wordpress.</p>','strictlysitemap');
		}


		if($this->post_requires_category){

			$category_depth = StrictlyPlugin::CheckCategoryDepth();

			if($category_depth > 0){

				$this->permalink_test_msg .= sprintf(__('<p>You currently have %d posts with over 3 levels of category. The plugin only supports up to 3 levels.</p>','strictlysitemap'),$category_depth);
			}
		}
		
		// check there are not too many parts within the permalink by counting the number of parameters e.g %category% %author% and so on
		// a good structure would be /%year%/%monthnum%/%day%/%postname% or maybe /%category%/%author%/%postname%/
		if(substr_count($this->post_rewrite,"%") > 8){
			// Its recommended that all file structures on a website should be less than 4 directories deep. 
			// Also every time a rewritten URL is accessed Wordpress has to parse it and work out which article to show, too many parts can lead to slow loads.
			$this->permalink_test_msg .= __('<p>Your permalink structure has too many parameters. Reducing them to four or less will help your SEO as well as increase Wordpress performance.</p>','strictlysitemap');
		}

		// Wordpress performance tip http://codex.wordpress.org/Using_Permalinks
		// always start the permalink structure with a numerical parameter e.g post_id year etc as this helps wordpress identify the link as belonging to a post
		// and not a page which always starts with text.
		if(!preg_match("@^/%(year|monthnum|day|hour|minute|second|post_id)%@",$this->post_rewrite)){
				$this->permalink_test_msg .= __('<p>For performance reasons its recommended to start your permalink structure with a numerical parameter such as %post_id% or %year%.</p>','strictlysitemap');
		}
		// make sure structure ends in either %post_id% or %postname%
		if(!preg_match("@/%(post_id|postname)%/?$@",$this->post_rewrite)){
				$this->permalink_test_msg .= __('<p>You should end your permalink structure with either %post_id% or %postname% so that the final directory points to an article.</p>','strictlysitemap');
		}	
		if($this->permalink_test_msg != "<br />"){
			$this->permalink_test_msg = substr($this->permalink_test_msg,6);

			// failed on one or more tests
			return false;
		}else{
			$this->permalink_test_msg = "";

			// passed all tests
			return true;
		}
	}

	
	/**
	 * loads the permalink structures set for the site for each section of the sitemap: posts, pages, tags, categories, authors
	 */
	protected function GetSitePermalinks(){		
			
		// get permalink structure for pages is always root + %pagename%
		$this->page_rewrite = rtrim(StrictlyPlugin::get_permastruct("page"),"/");	

		if( empty( $this->page_rewrite  )){
			$this->do_page_rewrite = false; 
		}else{
			$this->do_page_rewrite = true;
		}

		// admin can customise the post permalink structure
		$this->post_rewrite = rtrim(StrictlyPlugin::get_option('permalink_structure'),"/");	

		if( empty( $this->post_rewrite  )){
			$this->do_post_rewrite = false; 
		}else{
			$this->do_post_rewrite = true;
		}

		// check structure to see how complex it is as this will affect the SQL we use later on
		
		// does permalink have an author param?
		if ( strpos($this->post_rewrite, '%author%') !== false ) {
			$this->post_requires_author = true;
		}else{
			$this->post_requires_author = false;
		}

		// does permalink have an category parameter which means returning a hierarchy of up to 3 (category>category>category) anymore is bad for SEO anyway
		// and will slow wordpress URL rewriting down!
		if ( strpos($this->post_rewrite, '%category%') !== false ) {
			$this->post_requires_category = true;
		}else{
			$this->post_requires_category = false;
		}

		// does permalink have postid as a param
		if ( strpos($this->post_rewrite, '%post_id%') !== false ) {
			$this->post_requires_postid = true;
		}else{
			$this->post_requires_postid = false;
		}

		// does permalink have either year month day
		if ( strpos($this->post_rewrite, '%year%') !== false || strpos($this->post_rewrite, '%monthnum%') !== false || strpos($this->post_rewrite, '%day%') !== false  ) {
			$this->post_requires_yearmonthday = true;
		}else{
			$this->post_requires_yearmonthday = false;
		}

		// does permalink have either hour minute second which is really taking the piss but anyway!
		if ( strpos($this->post_rewrite, '%hour%') !== false || strpos($this->post_rewrite, '%minute%') !== false || strpos($this->post_rewrite, '%second%') !== false  ) {
			$this->post_requires_hourminsec = true;
		}else{
			$this->post_requires_hourminsec = false;
		}

		// get permalink structure for categories
		// the sitemap won't list out all parent/child category relationships just one per category to keep things simple
		$this->category_rewrite= rtrim(StrictlyPlugin::get_permastruct("category"),"/");	

		if ( empty( $this->category_rewrite) ) {
			$this->do_category_rewrite = false;
		}else{
			$this->do_category_rewrite = true;

			// ensure the start of the category rewrite url has a / as for some reason 3.0+ stopped adding it
			if(substr($this->category_rewrite,0,1) != "/"){
				$this->category_rewrite = "/" . $this->category_rewrite;
			}
		}

		
		// get permalink structure for tags
		// seems that before 3.1 they were using %tag% and after they use %post_tag%
		$this->tag_rewrite = rtrim(StrictlyPlugin::get_permastruct("tag"),"/");		

		if ( empty( $this->tag_rewrite ) ) {
			$this->do_tag_rewrite = false;
		}else{
			$this->do_tag_rewrite = true;

			// ensure the start of the category rewrite url has a / as for some reason 3.0+ stopped adding it
			if(substr($this->tag_rewrite,0,1) != "/"){
				$this->tag_rewrite = "/" . $this->tag_rewrite;
			}
		}


		// get permalink structure for authors
		$this->author_rewrite = rtrim(StrictlyPlugin::get_permastruct("author"),"/");		

		if ( empty( $this->author_rewrite ) ) {
			$this->do_author_rewrite = false;
		}else{
			$this->do_author_rewrite = true;

			// ensure the start of the category rewrite url has a / as for some reason 3.0+ stopped adding it
			if(substr($this->author_rewrite,0,1) != "/"){
				$this->author_rewrite = "/" . $this->author_rewrite;
			}
		}

		ShowDebug("get archive permastruct");

		// get permalink structure for archives
		$this->archive_rewrite = rtrim(StrictlyPlugin::get_permastruct("archive"),"/");		

		ShowDebug("archive_rewrite = " . $this->archive_rewrite);

		if ( empty( $this->archive_rewrite ) ) {
			$this->do_archive_rewrite = false;
		}else{
			$this->do_archive_rewrite = true;

			
			// ensure the start of the category rewrite url has a / as for some reason 3.0+ stopped adding it
			if(substr($this->archive_rewrite,0,1) != "/"){
				$this->archive_rewrite = "/" . $this->archive_rewrite;
			}
		}

		// set flag so we don't do this again unless we need to
		$this->loaded_permalinks = true;
	}
		
	/*
	 * Store everything we need in one go to prevent unneccessary calls to wordpress functions on each loop iteration
	 */
	protected function SetupStructures(){
		
		// if site is setup to use a specific page on the homepage then we need to skip it on our build
		// as the homepage is always included anyway
		if (StrictlyPlugin::get_option('show_on_front') ==  'page'){
			
			// get the post id to ignore
			$this->excluded_posts = StrictlyPlugin::get_option('page_on_front');
		}
		// get the default category to use for posts without one specified
		$this->default_category = StrictlyPlugin::get_default_category();


		// get current date to use for the homepage, categories and tags 
		$this->default_timestamp = StrictlyTools::FormatLastModDate();

		// store whether trailing slashes need to be added 
		$this->addtrailingslash = StrictlyPlugin::use_trailing_slashes();

		ShowDebug( "do we use trailing slashes = " .$this->addtrailingslash );
	}


	/**
	 * log memory usage so that we can report on potential issues
	 */
	protected function LogMemoryUsage(){

		if(function_exists("memory_get_peak_usage")) {
			$this->memory_usage = memory_get_peak_usage(true);
		}elseif(function_exists("memory_get_usage")) {
			$this->memory_usage =  memory_get_usage(true);
		} 

		// store the current memory limit
		$this->memory_limit = StrictlyTools::ConvertToBytes(ini_get('memory_limit'));
		
	}

	





	/**
	 * Builds the sitemap in a similar way to the Create function but without the GROUPCONCAT functions. This method does have the overhead
	 * of having to loop through the recordset but it does mean we don't have to split the recordset up later on to find out the no of records
	 * This function also combines the BuildSitemap method as there is no need for a separate method to split an array and create the files
	 *
	 * @returns bool
	 */
	protected function CreateAndBuildSitemap(){			
		
		ShowDebug( "IN Create Loop");

		$this->records_per_sitemap = (int)$this->sitemap['records'];

		$totalrecs = $sitemaps = $recs = 0;

		
		// do we add the homepage?		
		if($this->buildopts["homepage"]){

			// ensure our homepage appears at the top of the sitemap
			$sites = "\t<url>\n";
			$sites .= "\t\t<loc>"		.$this->siteurl					."</loc>\n";
			$sites .= "\t\t<lastmod>"	.$this->default_timestamp		."</lastmod>\n";
			$sites .= "\t\t<changefreq>".$this->changefreq['homepage']	."</changefreq>\n";
			$sites .= "\t\t<priority>"	.$this->priority['homepage']	."</priority>\n";
			$sites .= "\t</url>\n";		
			
			// add to cache - better to use arrays than string concatonation especially on large strings
			$this->sitemap_data[] = $sites;

			$recs ++;
			$totalrecs ++;
		}


		// do we add pages ?
		if($this->buildopts["pages"]){
			
			$sql_select = $sql_from = $sql_where = "";

			if($this->do_page_rewrite){

				if($this->addtrailingslash){
					$sql_pageurl = "'" . $this->clean_siteurl . "/' , post_name , '/'";
				}else{
					$sql_pageurl = "'" . $this->clean_siteurl . "/' , post_name";
				}
				
			}else{
				$sql_pageurl = "'" . $this->clean_siteurl . "/?page_id=', id";
			}

			
			// are we excluding posts?
            if(!empty($this->sitemap["excluded_posts"])){
                $sql_where .= "AND wp.id NOT IN(" . $this->sitemap["excluded_posts"] .") ";
            }

			
			$sql = "SELECT  CONCAT('\t<url>\n\t\t<loc>'," . $sql_pageurl . ",'</loc>\n\t\t<lastmod>', REPLACE(post_modified_gmt,' ','T'),'Z</lastmod>\n\t\t<changefreq>" . $this->changefreq["page"] . "</changefreq>\n\t\t<priority>" . $this->priority["page"] . "</priority>\n\t</url>') as XMLSitemap
					FROM	##WP_PREFIX##posts as wp
					WHERE	post_password='' AND post_status='publish' AND post_type='page' " 
							. $sql_where . " 
					ORDER BY post_modified_gmt DESC;";

			ShowDebug($sql) . "<br />";

			// there is an issue with mysql_unbuffered_query in that CONCAT and REPLACE only work on the first row and subsequent
			// rows end up with the values from row 1. Seems to be an issue with the MySQL query engine so I have disabled it for now
			//if($this->sitemap['sql_unbuffered']){
			if(1==0){

				ShowDebug("using a non buffered query so create a custom connection to the DB");

				// If we are using mysql_unbuffered_query then we create a custom connection to the DB so that we don't interfere
				// with the standard wordpress db connection object. The unbuffered query option will return results one at a time
				// rather than wait until the whole recordset is ready before returning it.
				$con = StrictlyPlugin::db_connect(true);

				$results = StrictlyPlugin::get_unbuffered_query_results($con,$sql);

				$this->unbuffered_db_count ++;

				ShowDebug("UNBUFFERED recs so far " . $recs . " we want " . $this->records_per_sitemap . " per file");

				while($row = mysql_fetch_object($results)) {

					$this->sitemap_data[] .= $row->XMLSitemap . "\n";

					$recs ++;
					$totalrecs ++;

					// have we breached our file size limit
					if($recs >= $this->records_per_sitemap){

						ShowDebug($recs . " >= " . $this->records_per_sitemap . " so create a new sitemap");

						$sitemaps++;

						// need to increment our filenames
						$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$sitemaps .'.xml';		

						ShowDebug("look at content for this file = " . implode("<br />",$this->sitemap_data));

						// create a sitemap file with all records in our array
						$this->BuildSitemapFile($this->sitemap_data,$sitemappath);

						// clear out our array
						unset($this->sitemap_data);

						// reset counter
						$recs = 0;
					}
				}

			}else{

				$results = StrictlyPlugin::get_query_results($sql);
				
				ShowDebug("recs so far " . $recs . " we want " . $this->records_per_sitemap . " per file");

				foreach($results as $row){
					$this->sitemap_data[] .= $row->XMLSitemap . "\n";

					$recs ++;
					$totalrecs ++;

					// have we breached our file size limit
					if($recs >= $this->records_per_sitemap){

						ShowDebug($recs . " >= " . $this->records_per_sitemap . " so create a new sitemap");

						$sitemaps++;

						// need to increment our filenames
						$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$sitemaps .'.xml';		

						ShowDebug("look at content for this file = " . implode("<br />",$this->sitemap_data));

						// create a sitemap file with all records in our array
						$this->BuildSitemapFile($this->sitemap_data,$sitemappath);

						// clear out our array
						unset($this->sitemap_data);

						// reset counter
						$recs = 0;
					}
				}
			}
			
			// cleanup as we go
			unset($results);
		}

		ShowDebug("end of loop total recs = " . $totalrecs . " sitemaps = " . $sitemaps . " recs = " . $recs);

		ShowDebug("this->post_rewrite  = " . $this->post_rewrite );


		// do we add posts ?
		if($this->buildopts["posts"]){
			
			// what type of link are we outputting?
			if($this->do_post_rewrite){


				if($this->addtrailingslash){
					$sql_posturl =  "'" . $this->clean_siteurl . $this->post_rewrite . "/'"; 
				}else{
					$sql_posturl =  "'" . $this->clean_siteurl . $this->post_rewrite . "'"; 
				}				

				//$sql_posturl =  "'" . $this->clean_siteurl . $this->post_rewrite . "'"; 
			}else{
				$sql_posturl =	"'" . $this->clean_siteurl . "/?p=' + CAST(t.id as varchar)";
			}
			
			$sql_select = $sql_from = "";
			
			$sql_where = "post_password='' AND post_status='publish' AND post_type='post' ";
			
			// do we need to get all the component parts for a rewritten post URL?
			if($this->do_post_rewrite){

				// ensure the correct replace functions are wrapped around the URL depending on the known permalink params

				// do we need to add post_id
				if($this->post_requires_postid){
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%post_id%',t.id)";
				}

				// do we need to add author name OR are we excluding posts by an author
				if($this->post_requires_author || !empty($this->sitemap["excluded_authors"])){
					
					if($this->post_requires_author){
						$sql_posturl = "REPLACE(" .$sql_posturl . ",'%author%',t.user_nicename)";

						$sql_select .= ", wu.user_nicename";
					}

					// need to join to users table to get author name
					$sql_from .= "LEFT JOIN ##WP_PREFIX##users as wu
								  ON wu.ID = wp.post_author ";

					if(!empty($this->sitemap["excluded_authors"])){
						$sql_where .= "AND display_name NOT IN('" . str_replace(",","','",$this->sitemap["excluded_authors"]) ."') ";
					}
				}

				// do we need to add category chain of up to 3 levels e.g /news/latest/uk/
				// also ensure any empty records use the default category
				if($this->post_requires_category){
					
					// default to not adding a slash on the last category item as we might end up with doubles depending on whether the category parameter
					// is the last item in the peramlink structure 
					$category_slash = "";

					// is the category param the last one in the permalink structure? If its not then we don't need to append a slash to the last category in the
					// category hierarchy parent/child/grandchild/
					if(preg_match("@%category%/?$@", $this->post_rewrite)){
						
						// category is the last item in the structure only add a slash on if the site requires it
						if($this->addtrailingslash){
							$category_slash = "/";
						}
					}
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%category%',COALESCE(CategorySlugs,'" . $this->default_category . "') )";

					// need a 3 way SUB SELECT to ensure up to 3 levels of category are shown
					// anymore than 3 won't work and is likely to kill Wordpres performance anyway when loading pages!
					$sql_select .=	",(SELECT CONCAT(COALESCE(CONCAT(t3.slug,'/'),''), COALESCE(CONCAT(t2.slug,'/'),''), COALESCE(CONCAT(t.slug,'" . $category_slash  ."'),'')) as CategorySlugs
									FROM ##WP_PREFIX##term_relationships as r 
									LEFT JOIN	##WP_PREFIX##term_taxonomy as wt 
									ON	wt.term_taxonomy_id = r.term_taxonomy_id 
									LEFT JOIN	##WP_PREFIX##terms as t 
									ON	wt.term_id = t.term_id 
									LEFT JOIN ##WP_PREFIX##terms as t2 
									ON	wt.Parent = t2.term_id 
									LEFT JOIN ##WP_PREFIX##term_taxonomy as wt2
									ON wt2.term_id =t2.term_id AND wt2.taxonomy='category'
									LEFT JOIN ##WP_PREFIX##terms as t3
									ON wt2.parent = t3.term_id
									WHERE r.object_id = wp.id AND wt.taxonomy='category' 
									ORDER BY CASE WHEN t3.Slug IS NOT NULL AND t2.Slug IS NOT NULL AND t.Slug IS NOT NULL THEN 3
											WHEN t3.Slug IS NULL AND t2.Slug IS NOT NULL AND t.Slug IS NOT NULL THEN 2
											WHEN t3.Slug IS NULL AND t2.Slug IS NULL AND t.Slug IS NOT NULL THEN 1
											ELSE 0 END  DESC LIMIT 1) as CategorySlugs ";

				}
				
				// do we need to add year, month, day
				if($this->post_requires_yearmonthday){

					// replace year param
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%year%',year(t.post_date) )";

					// replace month param
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%monthnum%',RIGHT(concat('0' ,month(t.post_date)),2) )";

					// replace day param
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%day%',RIGHT(concat('0' ,day(t.post_date)),2) )";
				}

				// do we need to add hour, minute, second
				if($this->post_requires_yearmonthday){

					// replace year param
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%hour%',RIGHT(concat('0' ,hour(t.post_date)),2) )";

					// replace month param
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%minute%',RIGHT(concat('0' ,minute(t.post_date)),2) )";

					// replace day param
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%second%',RIGHT(concat('0' ,second(t.post_date)),2) )";
				}


				// now add post_name
				$sql_posturl = "REPLACE(" .$sql_posturl . ",'%postname%',t.post_name)";
				
			}
			
			// are we excluding posts?
			if(!empty($this->sitemap["excluded_posts"])){
				$sql_where .= "AND wp.id NOT IN(" . $this->sitemap["excluded_posts"] .") ";
			}

			

			// are we excluding posts belonging to certain categories?
			if(!empty($this->sitemap["excluded_cats"])){

				$sql_from	.= "JOIN ##WP_PREFIX##term_relationships AS tr 
								ON wp.id = tr.object_id
								JOIN ##WP_PREFIX##term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id ";

				$sql_where	.= "AND tt.taxonomy IN ('category') AND tt.term_id NOT IN (" . $this->sitemap["excluded_cats"] .") ";
			}

			$sql = "SELECT	CONCAT('\t<url>\n\t\t<loc>'," . $sql_posturl . ",'</loc>\n\t\t<lastmod>',REPLACE(post_modified_gmt,' ','T'),'Z</lastmod>\n\t\t<changefreq>" . $this->changefreq["post"] . "</changefreq>\n\t\t<priority>" . $this->priority["post"] . "</priority>\n\t</url>') as XMLSitemap
			FROM	(SELECT DISTINCT wp.id, post_name, post_modified_gmt, post_date " . $sql_select. "				
					FROM	##WP_PREFIX##posts as wp " . $sql_from . " 
					WHERE	" . $sql_where . ") as t
					ORDER BY post_modified_gmt DESC;";				

			ShowDebug( $sql);

			if(1==0){
			//if($this->sitemap['sql_unbuffered']){

				$results = StrictlyPlugin::get_unbuffered_query_results($con,$sql);

				$this->unbuffered_db_count ++;

				ShowDebug("UNBUFFERED recs so far " . $recs . " we want " . $this->records_per_sitemap . " per file");

				while($row = mysql_fetch_object($results)) {

					$this->sitemap_data[] .= $row->XMLSitemap . "\n";

					$recs ++;
					$totalrecs ++;

					// have we breached our file size limit
					if($recs >= $this->records_per_sitemap){

						ShowDebug($recs . " >= " . $this->records_per_sitemap . " so create a new sitemap");

						$sitemaps++;

						// need to increment our filenames
						$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$sitemaps .'.xml';		

						ShowDebug("look at content for this file = " . implode("<br />",$this->sitemap_data));

						// create a sitemap file with all records in our array
						$this->BuildSitemapFile($this->sitemap_data,$sitemappath);

						// clear out our array
						unset($this->sitemap_data);

						// reset counter
						$recs = 0;
					}
				}

			}else{

				$results = StrictlyPlugin::get_query_results($sql);
				
				foreach($results as $row){
					$this->sitemap_data[] .= $row->XMLSitemap . "\n";

					$recs ++;
					$totalrecs ++;

					// have we breached our file size limit
					if($recs >= $this->records_per_sitemap){

						$sitemaps++;

						// need to increment our filenames
						$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$sitemaps .'.xml';		

						// create a sitemap file with all records in our array
						$this->BuildSitemapFile($this->sitemap_data,$sitemappath);

						// clear out our array
						unset($this->sitemap_data);

						// reset counter
						$recs = 0;
					}
				}
			}

			// cleanup as we go
			unset($results);
			
		}

ShowDebug("this->clean_siteurl  = " . $this->clean_siteurl );
ShowDebug("this->post_rewrite  = " . $this->post_rewrite );
ShowDebug("this->author_rewrite  = " . $this->author_rewrite );
ShowDebug("this->category_rewrite  = " . $this->category_rewrite );
ShowDebug("this->tag_rewrite  = " . $this->tag_rewrite );




		// do we ouput authors?
		if($this->buildopts["authors"]){

			if($this->do_author_rewrite){

				if($this->addtrailingslash){
					$sql_authorurl =  "'" . $this->clean_siteurl . $this->author_rewrite."/'";
				}else{
					$sql_authorurl =  "'" . $this->clean_siteurl . $this->author_rewrite. "'";
				}

				$sql_authorurl = "REPLACE(" .$sql_authorurl . ",'%author%',user_nicename)";

			}else{
				$sql_authorurl = "'" . $this->clean_siteurl . "/?author=', id";
			}	


			if(!empty($this->sitemap["excluded_authors"])){
				$sql_where = "AND display_name NOT IN('" . $this->sitemap["excluded_authors"] ."') ";
			}else{
				$sql_where = "";
			}

			$sql =		"SELECT CONCAT('\t<url>\n\t\t<loc>'," . $sql_authorurl . ",'</loc>\n\t\t<lastmod>" . $this->default_timestamp	 . "</lastmod>\n\t\t<changefreq>" . $this->changefreq["author"] . "</changefreq>\n\t\t<priority>" . $this->priority["author"] . "</priority>\n\t</url>') as XMLSitemap						
						FROM ##WP_PREFIX##posts as p
						JOIN ##WP_PREFIX##users as u
						ON u.ID = p.post_author
						WHERE p.post_status = 'publish'
								AND p.post_type = 'post'
								AND p.post_password = ''
								". $sql_where ."
						GROUP BY u.ID,p.post_author
						ORDER BY user_nicename;";

						
			ShowDebug( $sql );

			if(1==0){
			//if($this->sitemap['sql_unbuffered']){

				$results = StrictlyPlugin::get_unbuffered_query_results($con,$sql);

				$this->unbuffered_db_count ++;

				ShowDebug("UNBUFFERED recs so far " . $recs . " we want " . $this->records_per_sitemap . " per file");

				while($row = mysql_fetch_object($results)) {

					$this->sitemap_data[] .= $row->XMLSitemap . "\n";

					$recs ++;
					$totalrecs ++;

					// have we breached our file size limit
					if($recs >= $this->records_per_sitemap){

						ShowDebug($recs . " >= " . $this->records_per_sitemap . " so create a new sitemap");

						$sitemaps++;

						// need to increment our filenames
						$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$sitemaps .'.xml';		

						ShowDebug("look at content for this file = " . implode("<br />",$this->sitemap_data));

						// create a sitemap file with all records in our array
						$this->BuildSitemapFile($this->sitemap_data,$sitemappath);

						// clear out our array
						unset($this->sitemap_data);

						// reset counter
						$recs = 0;
					}
				}

			}else{

				$results = StrictlyPlugin::get_query_results($sql);
				
				foreach($results as $row){
					$this->sitemap_data[] .= $row->XMLSitemap . "\n";

					$recs ++;
					$totalrecs ++;

					// have we breached our file size limit
					if($recs >= $this->records_per_sitemap){

						$sitemaps++;

						// need to increment our filenames
						$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$sitemaps .'.xml';		

						// create a sitemap file with all records in our array
						$this->BuildSitemapFile($this->sitemap_data,$sitemappath);

						// clear out our array
						unset($this->sitemap_data);

						// reset counter
						$recs = 0;
					}
				}
			}

			// cleanup as we go
			unset($results);
			
		}

		// People should keep their code up to date for security reasons. The old style category tables have been replaced long ago!
		// However if taxonomy is not supported we cannot add categories or tags to the DB as I am only using the new format
		if(!$this->IsTaxonomySupported()){
			return;
		}

		// do we ouput categories?
		if($this->buildopts["categories"]){

			if($this->do_category_rewrite){

				if($this->addtrailingslash){
					$sql_categoryurl =  "'" . $this->clean_siteurl . $this->category_rewrite."/'";
				}else{
					$sql_categoryurl =  "'" . $this->clean_siteurl . $this->category_rewrite. "'";
				}

				$sql_categoryurl = "REPLACE(" .$sql_categoryurl . ",'%category%',t.slug)";
			}else{
				$sql_categoryurl = "'" . $this->clean_siteurl . "/?cat=', t.term_id";
			}							
			
			// are we excluding posts belonging to certain categories?
			if(!empty($this->sitemap["excluded_cats"])){
				$sql_where	= "AND tt.term_id NOT IN (" . $this->sitemap["excluded_cats"] .") ";
			}else{
				$sql_where  = "";
			}

			$sql = "SELECT  CONCAT('\t<url>\n\t\t<loc>'," . $sql_categoryurl . ",'</loc>\n\t\t<lastmod>',REPLACE(NOW(),' ','T'),'Z</lastmod>\n\t\t<changefreq>" . $this->changefreq["category"] . "</changefreq>\n\t\t<priority>" . $this->priority["category"] . "</priority>\n\t</url>') as XMLSitemap
					FROM	##WP_PREFIX##terms AS t 
					JOIN	##WP_PREFIX##term_taxonomy AS tt 
						ON	t.term_id = tt.term_id
					WHERE	tt.taxonomy IN ('category') " 
							. $sql_where . 
					"ORDER BY Name;";
			
			ShowDebug( $sql );

			if(1==0){
			//if($this->sitemap['sql_unbuffered']){

				$results = StrictlyPlugin::get_unbuffered_query_results($con,$sql);

				$this->unbuffered_db_count ++;

				ShowDebug("UNBUFFERED recs so far " . $recs . " we want " . $this->records_per_sitemap . " per file");

				while($row = mysql_fetch_object($results)) {

					$this->sitemap_data[] .= $row->XMLSitemap . "\n";

					$recs ++;
					$totalrecs ++;

					// have we breached our file size limit
					if($recs >= $this->records_per_sitemap){

						ShowDebug($recs . " >= " . $this->records_per_sitemap . " so create a new sitemap");

						$sitemaps++;

						// need to increment our filenames
						$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$sitemaps .'.xml';		

						ShowDebug("look at content for this file = " . implode("<br />",$this->sitemap_data));

						// create a sitemap file with all records in our array
						$this->BuildSitemapFile($this->sitemap_data,$sitemappath);

						// clear out our array
						unset($this->sitemap_data);

						// reset counter
						$recs = 0;
					}
				}

			}else{

				$results = StrictlyPlugin::get_query_results($sql);
				
				foreach($results as $row){
					$this->sitemap_data[] .= $row->XMLSitemap . "\n";

					$recs ++;
					$totalrecs ++;

					// have we breached our file size limit
					if($recs >= $this->records_per_sitemap){

						$sitemaps++;

						// need to increment our filenames
						$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$sitemaps .'.xml';		

						// create a sitemap file with all records in our array
						$this->BuildSitemapFile($this->sitemap_data,$sitemappath);

						// clear out our array
						unset($this->sitemap_data);

						// reset counter
						$recs = 0;
					}
				}
			}
			// cleanup as we go
			unset($results);
			

		}

		// do we ouput tags?
		if($this->buildopts["tags"]){

			// do tags
			if($this->do_tag_rewrite){

				if($this->addtrailingslash){
					$sql_tagurl =  "'" . $this->clean_siteurl . $this->tag_rewrite ."/'";
				}else{
					$sql_tagurl =  "'" . $this->clean_siteurl . $this->tag_rewrite . "'";
				}

				
				// they seem to have changed from %tag% to %post_tag% around 3.0.1 I'm not too sure but I know in 3.1 they use %post_tag%
				if(floatval($wp_version) < 3.1){
					// do both just in case
					$sql_tagurl = "REPLACE(REPLACE(" .$sql_tagurl . ",'%tag%',t.slug),'%post_tag%',t.slug)";
				}else{
					$sql_tagurl = "REPLACE(" .$sql_tagurl . ",'%post_tag%',t.slug)";
				}

			}else{
				$sql_tagurl = "'" . $this->clean_siteurl . "/?tag=', t.slug";
			}		
			

			$sql = "SELECT  CONCAT('\t<url>\n\t\t<loc>'," . $sql_tagurl . ",'</loc>\n\t\t<lastmod>',REPLACE(NOW(),' ','T'),'Z</lastmod>\n\t\t<changefreq>" . $this->changefreq["tag"] . "</changefreq>\n\t\t<priority>" . $this->priority["tag"] . "</priority>\n\t</url>') as XMLSitemap
					FROM	##WP_PREFIX##terms AS t 
					JOIN	##WP_PREFIX##term_taxonomy AS tt 
						ON	t.term_id = tt.term_id
					WHERE	tt.taxonomy IN ('post_tag')
					ORDER BY Name;";
			
			ShowDebug( $sql);

			if(1==0){
			//if($this->sitemap['sql_unbuffered']){

				$results = StrictlyPlugin::get_unbuffered_query_results($con,$sql);

				$this->unbuffered_db_count ++;

				ShowDebug("UNBUFFERED recs so far " . $recs . " we want " . $this->records_per_sitemap . " per file");

				while($row = mysql_fetch_object($results)) {

					$this->sitemap_data[] .= $row->XMLSitemap . "\n";

					$recs ++;
					$totalrecs ++;

					// have we breached our file size limit
					if($recs >= $this->records_per_sitemap){

						ShowDebug($recs . " >= " . $this->records_per_sitemap . " so create a new sitemap");

						$sitemaps++;

						// need to increment our filenames
						$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$sitemaps .'.xml';		

						ShowDebug("look at content for this file = " . implode("<br />",$this->sitemap_data));

						// create a sitemap file with all records in our array
						$this->BuildSitemapFile($this->sitemap_data,$sitemappath);

						// clear out our array
						unset($this->sitemap_data);

						// reset counter
						$recs = 0;
					}
				}

			}else{

				$results = StrictlyPlugin::get_query_results($sql);
				
				foreach($results as $row){
					$this->sitemap_data[] .= $row->XMLSitemap . "\n";

					$recs ++;
					$totalrecs ++;

					// have we breached our file size limit
					if($recs >= $this->records_per_sitemap){

						$sitemaps++;

						// need to increment our filenames
						$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$sitemaps .'.xml';		

						// create a sitemap file with all records in our array
						$this->BuildSitemapFile($this->sitemap_data,$sitemappath);

						// clear out our array
						unset($this->sitemap_data);

						// reset counter
						$recs = 0;
					}
				}
			}

			// cleanup as we go
			unset($results);		

		}	


		// do we ouput archives?
		if($this->buildopts["archives"]){

			// do archives
			if($this->do_archive_rewrite){

				if($this->addtrailingslash){
					$sql_archiveurl =  "'" . $this->clean_siteurl . $this->archive_rewrite ."/'";
				}else{
					$sql_archiveurl =  "'" . $this->clean_siteurl . $this->archive_rewrite . "'";
				}

				// replace year param
				$sql_archiveurl = "REPLACE(" .$sql_archiveurl . ",'%year%',year(post_date) )";

				// replace month param
				$sql_archiveurl = "REPLACE(" .$sql_archiveurl . ",'%monthnum%',RIGHT(concat('0' ,month(post_date)),2) )";

				
			}else{
				$sql_archiveurl = "'" . $this->clean_siteurl . "/?m=', YEAR(post_date_gmt),RIGHT(concat('0' ,month(post_date)),2)";
			}		
		

			$sql = "SELECT  CONCAT('\t<url>\n\t\t<loc>'," . $sql_archiveurl . ",'</loc>\n\t\t<lastmod>',REPLACE(MAX(post_date_gmt),' ','T'),'Z</lastmod>\n\t\t<changefreq>" . $this->changefreq["archive"] . "</changefreq>\n\t\t<priority>" . $this->priority["archive"] . "</priority>\n\t</url>') as XMLSitemap
					FROM	##WP_PREFIX##posts
					WHERE	post_date < NOW()
							AND post_status = 'publish'
							AND post_type = 'post'			
							" . (floatval($wp_version) < 2.1?"AND post_date_gmt <= NOW()":"") . "
					GROUP BY YEAR(post_date_gmt),MONTH(post_date_gmt)
					ORDER BY post_date_gmt DESC;";
			
			ShowDebug( $sql);


			if(1==0){
			//if($this->sitemap['sql_unbuffered']){

				$results = StrictlyPlugin::get_unbuffered_query_results($con,$sql);

				$this->unbuffered_db_count ++;

				ShowDebug("UNBUFFERED recs so far " . $recs . " we want " . $this->records_per_sitemap . " per file");

				while($row = mysql_fetch_object($results)) {

					$this->sitemap_data[] .= $row->XMLSitemap . "\n";

					$recs ++;
					$totalrecs ++;

					// have we breached our file size limit
					if($recs >= $this->records_per_sitemap){

						ShowDebug($recs . " >= " . $this->records_per_sitemap . " so create a new sitemap");

						$sitemaps++;

						// need to increment our filenames
						$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$sitemaps .'.xml';		

						ShowDebug("look at content for this file = " . implode("<br />",$this->sitemap_data));

						// create a sitemap file with all records in our array
						$this->BuildSitemapFile($this->sitemap_data,$sitemappath);

						// clear out our array
						unset($this->sitemap_data);

						// reset counter
						$recs = 0;
					}
				}

			}else{

				$results = StrictlyPlugin::get_query_results($sql);
				
				foreach($results as $row){
					$this->sitemap_data[] .= $row->XMLSitemap . "\n";

					$recs ++;
					$totalrecs ++;

					// have we breached our file size limit
					if($recs >= $this->records_per_sitemap){

						$sitemaps++;

						// need to increment our filenames
						$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$sitemaps .'.xml';		

						// create a sitemap file with all records in our array
						$this->BuildSitemapFile($this->sitemap_data,$sitemappath);

						// clear out our array
						unset($this->sitemap_data);

						// reset counter
						$recs = 0;
					}
				}
			}

			// cleanup as we go
			unset($results);		

		}


		// close the connection we made
		if(1==0){
		//if($this->sitemap['sql_unbuffered']){
			mysql_close($con);
		}

		ShowDebug("do we have custom pages = " . $this->buildopts["custompages"]);

		// do we have custom pages to add?
		if($this->buildopts["custompages"]){

			$custom = $this->sitemap["custompages"];

			ShowDebug("yes $custom");

			if(!empty($custom)){


				// each custom page should be on its own line
				$custom_pages = explode("\n",$custom);

				ShowDebug($custom_pages );

				foreach($custom_pages as $page){
					
					$page = trim($page);

					ShowDebug("page $page");

					if(!empty($page)){

						// there maybe a specific change frequency or priority value
						list($url,$priority,$changefreq) = explode(" ",trim(preg_replace("@\s+@"," ",$page)));

						if(!preg_match("@^0\.[1-9]$@",$priority)){
							$priority = $this->priority['custompages'];
						}
						if(!preg_match("@^(always|never|hourly|daily|weekly|monthly|yearly)$@",$changefreq)){
							$changefreq = $this->changefreq['custompages'];
						}
						
						ShowDebug("check page exists from $url");

						// try to get the correct date the file was last updated
						$http = $this->GetHTTP(trim($url));

						if($http['status']=="200"){
								
							$lastmod = trim($http['headers']['last-modified']);

							if(!empty($lastmod)){
								$lastmod = StrictlyTools::ConvertFromFileStamp($lastmod);
							}else{
								// use the current date
								$lastmod = $this->default_timestamp;
							}

							$sites = "\t<url>\n";
							$sites .= "\t\t<loc>"		.$url			."</loc>\n";
							$sites .= "\t\t<lastmod>"	.$lastmod		."</lastmod>\n";
							$sites .= "\t\t<changefreq>".$changefreq	."</changefreq>\n";
							$sites .= "\t\t<priority>"	.$priority		."</priority>\n";
							$sites .= "\t</url>\n";		

							ShowDebug("add $sites");

							$this->sitemap_data[] = $sites;

							$recs ++;
							$totalrecs ++;

							// have we breached our file size limit
							if($recs >= $this->records_per_sitemap){

								$sitemaps++;

								// need to increment our filenames
								$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$sitemaps .'.xml';		

								// create a sitemap file with all records in our array
								$this->BuildSitemapFile($this->sitemap_data,$sitemappath);

								// clear out our array
								unset($this->sitemap_data);

								// reset counter
								$recs = 0;
							}

						}
					}
				}
				unset($custom_pages,$page);
			}
		}

		ShowDebug("total recs = $recs");

		// add any remaining records (or all of them depending on the amount) to a sitemap	
		if($recs > 0){

			ShowDebug("add the remaining $recs into a sitemap");

			$sitemaps++;

			// if its our first sitemap we dont append the file counter to the name
			if($sitemaps > 1){
				// need to increment our filenames
				$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$sitemaps .'.xml';		
			}else{
				$sitemappath = $this->sitemap_path	.$this->sitemap_name .'.xml';		
			}

			//ShowDebug("look at content for this file = " . implode("<br />",$this->sitemap_data));

			// create a sitemap file with all records in our array
			$this->BuildSitemapFile($this->sitemap_data,$sitemappath);

			// clear out our array
			unset($this->sitemap_data);
		}

		ShowDebug("total no of sitemaps " . $sitemaps);

		// do we need to create an index file ?
		if($sitemaps > 1){

			ShowDebug("create a sitemap index file");
			
			// create a site index file
			$sitemapindex = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
							"<?xml-stylesheet type=\"text/xsl\" href=\"".$this->xslindexurl."\"?>\n".
							"<!-- generator=\"wordpress/2.7\" -->\n" .
							"<!-- sitemap-generator-url=\"" . $this->website . "\" " .  $this->plugin_for_xml . "=\"".$this->version . "." . $this->build . "\" -->\n".
							"<!-- generated-on=\"".date('d. F Y')."\" -->\n" .
							"<sitemapindex xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd\" xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";	

			

			for($x = 1; $x <= $sitemaps; $x++){
				
				$sitemapindex .= "\t<sitemap>\n".
								 "\t\t<loc>"		. $this->clean_siteurl		. "/sitemap-" . $x . ".xml</loc>\n".
								 "\t\t<lastmod>"	. $this->default_timestamp	. "</lastmod>\n".
								 "\t</sitemap>\n";
			}

			$sitemapindex .= '</sitemapindex>';

			// create the sitemap index file
			if(!$this->CreateFile($this->sitemap_indexpath, $sitemapindex, "Sitemap Index",$msg,false)){
				// append any error message
				$this->msg .= $msg;
				return false;
			}

			// do we also create a gzip version - can only be set if site supports it
			if($this->sitemap['gzip']){

				//$gz = gzencode($sitemapindex,9);

				if(!$this->CreateFile($this->sitemap_indexpath . ".gz", $sitemapindex, "Sitemap Index",$msg,true)){
					// append any error message
					$this->msg .= $msg;
					return false;
				}
			}

			unset($sitemapindex);

		}

		// store no of records in sitemap
		$this->sitemap_record_count = $totalrecs;

		ShowDebug("no of sitemaps $sitemaps with " . $this->sitemap_record_count . " records");
		ShowDebug("biggest sitemap size = " . $this->sitemap_biggest_file . " and gzip = " . $this->sitemap_biggest_file_gzip);
		

		// all files created so set up a message to tell the user
		if($sitemaps > 1){
			if($this->sitemap['gzip']){
				$build_msg = sprintf(__('<p>An uncompressed and compressed version of the Sitemap has been created.</p><p>Both versions contain 1 Sitemap Index file and %d Sitemaps with a total of %s entries.</p><p>Size of largest sitemap file: %s</p></p>Size of largest gzip sitemap file: %s</p>', 'strictlysitemap'), $sitemaps,number_format($this->sitemap_record_count),StrictlyTools::ConvertFromBytes($this->sitemap_biggest_file),StrictlyTools::ConvertFromBytes($this->sitemap_biggest_file_gzip));
			}else{
				$build_msg = sprintf(__('<p>1 Sitemap Index file and %d Sitemaps with %s entries were created.</p><p>Size of largest sitemap file: %s</p>', 'strictlysitemap'), $sitemaps,number_format($this->sitemap_record_count),StrictlyTools::ConvertFromBytes($this->sitemap_biggest_file));
			}
		}else{
			if($this->sitemap['gzip']){
				$build_msg = sprintf(__('<p>An uncompressed and compressed version of the Sitemap has been created containing %s entries each.</p><p>Size of sitemap file: %s</p></p>Size of gzip sitemap file: %s</p>', 'strictlysitemap'),number_format($this->sitemap_record_count),StrictlyTools::ConvertFromBytes($this->sitemap_biggest_file),StrictlyTools::ConvertFromBytes($this->sitemap_biggest_file_gzip));
			}else{
				$build_msg = sprintf(__('<p>1 Sitemap with %s entries was created.</p><p>Size of sitemap file: %s</p>', 'strictlysitemap'),number_format($this->sitemap_record_count),StrictlyTools::ConvertFromBytes($this->sitemap_biggest_file));
			}
		}
		
		// ensure the biggest files are less than 10MB in size as this is the limit imposed by the sitemap standard
		if(($this->sitemap['gzip'] && $this->sitemap_biggest_file_gzip > 10485760) || ($this->sitemap_biggest_file > 10485760)){
			
			$build_msg .= __("<p>One or more sitemap files are over the allowed limit of 10MB. Please reduce the number of records per file.</p>"."strictlysitemap");			

		}

		// create some links to validate the sitemap OR sitemap index
		if($sitemaps > 1){
			$validatelink = '<a href="javascript:void(0);" onclick="validatesitemap(true,\'' . urlencode($this->sitemap_fullurl) . '\',false);return false;">'.__('validate','strictlysitemap').'</a> <span id="validatesitemapindexresult"></span>';

			if($this->sitemap['gzip']){
				$validatelinkgzip = '<a href="javascript:void(0);" onclick="validatesitemap(true,\'' . urlencode($this->sitemap_gzipurl) . '\',true);return false;">'.__('validate','strictlysitemap').'</a> <span id="validatesitemapindexresultgzip"></span>';
			}
		}else{
			$validatelink = '<a href="javascript:void(0);" onclick="validatesitemap(false,\'' . urlencode( $this->sitemap_fullurl) . '\',false);return false;">'.__('validate','strictlysitemap').'</a> <span id="validatesitemapresult"></span>';

			if($this->sitemap['gzip']){
				$validatelinkgzip = '<a href="javascript:void(0);" onclick="validatesitemap(false,\'' . urlencode($this->sitemap_gzipurl) . '\',true);return false;">'.__('validate','strictlysitemap').'</a> <span id="validatesitemapresultgzip"></span>';
			}
		}


		$build_msg .= sprintf(__('<p>Sitemap: %s %s</p>', 'strictlysitemap'),'<a href="'.$this->sitemap_fullurl.'">'.$this->sitemap_fullurl.'</a>',$validatelink);
		if($this->sitemap['gzip']){
			$build_msg .= sprintf(__('<p>Sitemap: %s %s</p>', 'strictlysitemap'),'<a href="'.$this->sitemap_gzipurl.'">'.$this->sitemap_gzipurl.'</a>',$validatelinkgzip);
		}

		// set global message
		$this->msg .= $build_msg;

		return true;		

	}




	/**
	 * Builds the sitemap in a memory friendly way avoiding the massive overhead and leakage that nested loops cause when each iteration
	 * is calling wordpress functions to return permalink formats that only need to be collected once!
	 */
	protected function Create(){		
		
		ShowDebug("IN Create");
				
		// do we add the homepage?		
		if($this->buildopts["homepage"]){

			ShowDebug("Add homepage");

			// ensure our homepage appears at the top of the sitemap
			$sites = "\t<url>\n";
			$sites .= "\t\t<loc>"		.$this->siteurl					."</loc>\n";
			$sites .= "\t\t<lastmod>"	.$this->default_timestamp		."</lastmod>\n";
			$sites .= "\t\t<changefreq>".$this->changefreq['homepage']	."</changefreq>\n";
			$sites .= "\t\t<priority>"	.$this->priority['homepage']	."</priority>\n";
			$sites .= "\t</url>\n";	
			
			// add to cache - better to use arrays than string concatonation especially on large strings
			$this->sitemap_data[] = $sites;

			ShowDebug("count of records in sitemap_data array = " . count($this->sitemap_data));
		}



		/*	to save a possible expensive preg_match on the combined XML when building the file to work out how many
			records there are later in case we need to split the file into multiples we do a count of all the records
			first. This way we only carry out this pattern matching if necessary	*/


		// all where statements are build now for the COUNTs and then used later on


		// do we add pages ?
		if($this->buildopts["pages"]){			

			// do we need to exclude a page if its being used as the homepape?
			if(!empty($this->excluded_posts)){
				$sql_page_where = "AND wp.id <> " . $this->excluded_posts . " ";
			}

			$sql1 = "(SELECT COUNT(*) FROM ##WP_PREFIX##posts as wp WHERE post_password='' AND post_status='publish' AND post_type = 'page' " . $sql_page_where . ") as pagecount";
		}else{
			$sql1 = "0 as pagecount";
		}

		// do we add posts ?
		if($this->buildopts["posts"]){
			
			$sql_post_where = "post_password='' AND post_status='publish' AND post_type='post' ";

			if(!empty($this->sitemap["excluded_authors"])){
				
				// need to join to authors				
				$sql_post_from .=	"LEFT JOIN ##WP_PREFIX##users as wu
									ON wu.ID = wp.post_author ";

				$sql_post_where .= "AND display_name NOT IN('" . str_replace(",","','",$this->sitemap["excluded_authors"]) ."') ";
			}

			// are we excluding posts?
			if(!empty($this->sitemap["excluded_posts"])){
				$sql_post_where .= "AND wp.id NOT IN(" . $this->sitemap["excluded_posts"] .") ";
			}

			// are we excluding posts belonging to certain categories?
			if(!empty($this->sitemap["excluded_cats"])){

				$sql_post_from	.= "JOIN ##WP_PREFIX##term_relationships AS tr 
									ON wp.id = tr.object_id
									JOIN ##WP_PREFIX##term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id ";

				$sql_post_where	.= "AND tt.taxonomy IN ('category') AND tt.term_id NOT IN (" . $this->sitemap["excluded_cats"] .") ";
			}

			$sql2 = "(SELECT COUNT(*) FROM ##WP_PREFIX##posts as wp " . $sql_post_from . "WHERE  " . $sql_post_where . ") as postcount";
		}else{
			$sql2 = "0 as postcount";
		}

		// do we ouput authors?
		if($this->buildopts["authors"]){

			if(!empty($this->sitemap["excluded_authors"])){
				$sql_author_where = "AND display_name NOT IN('" . $this->sitemap["excluded_authors"] ."') ";
			}

			$sql3 = "(SELECT COUNT(DISTINCT(u.ID)) FROM ##WP_PREFIX##posts as p JOIN ##WP_PREFIX##users as u ON u.ID = p.post_author WHERE p.post_status = 'publish' AND p.post_type = 'post' AND p.post_password = '' ". $sql_author_where .") as authorcount";
		}else{
			$sql3 = "0 as authorcount";
		}

		// do we ouput categories?
		if($this->buildopts["categories"]){					
			
			// are we excluding posts belonging to certain categories?
			if(!empty($this->sitemap["excluded_cats"])){
				$sql_category_where	= "AND tt.term_id NOT IN (" . $this->sitemap["excluded_cats"] .") ";
			}

			$sql4 = "(SELECT COUNT(*) FROM	##WP_PREFIX##terms AS t JOIN	##WP_PREFIX##term_taxonomy AS tt ON t.term_id = tt.term_id WHERE	tt.taxonomy IN ('category') " . $sql_category_where  .") as categorycount";
		}else{
			$sql4 = "0 as categorycount";
		}
		
		// no tag where required
		if($this->buildopts["tags"]){		
			$sql5 = "(SELECT COUNT(*) FROM	##WP_PREFIX##terms AS t JOIN	##WP_PREFIX##term_taxonomy AS tt ON t.term_id = tt.term_id WHERE	tt.taxonomy IN ('post_tag')) as tagcount";
		}else{
			$sql5 = "0 as tagcount";
		}

		// no archive where required
		if($this->buildopts["archives"]){		
			$sql6 = "(SELECT COUNT(*) FROM (SELECT count(ID) as posts	FROM ##WP_PREFIX##posts WHERE post_date < NOW() AND post_status = 'publish' AND post_type = 'post' " . (floatval($wp_version) < 2.1?"AND post_date_gmt <= NOW()":"") . " GROUP BY YEAR(post_date_gmt),MONTH(post_date_gmt)) as t) as archivecount";
		}else{
			$sql6 = "0 as archivecount";
		}

		$sql = "SELECT " . $sql1 .", " . $sql2 . ", " . $sql3 . ", " . $sql4 . ", " . $sql5 . ", " . $sql6 . ";";

		ShowDebug($sql);

		$results	= StrictlyPlugin::get_query_results($sql);		
			
		// should only be one row containing the XML blob data for all the pages already XML formatted
		foreach($results as $row){
			
			// which one is bigger cats or posts?
			$pagecount		= $row->pagecount;
			$postcount		= $row->postcount;
			$authorcount	= $row->authorcount;
			$catcount		= $row->categorycount;
			$tagcount		= $row->tagcount;
			$archivecount	= $row->archivecount;

			ShowDebug("pagecount = $pagecount postcount = $postcount authorcount = $authorcount catcount = $catcount tagcount = $tagcount archivecount = $archivecount");

			// store the total no of records - this is very likely to change by much if at all so it can safely be used to determine whether
			// or not we need to split the XML string up into chunks once the build has been completed
			$this->total_records_pre_exec = $pagecount + $postcount + $authorcount + $catcount + $tagcount + $archivecount + 1; // add 1 for the homepage!

			ShowDebug("Total no of records before building == " . $this->total_records_pre_exec);

		}

		// cleanup as we go
		unset($results,$sql);

		// we also need to set the GROUP_CONCAT_MAX_LEN setting so that it's large enough for all the string joins
		// we need to do. To calculate this figure and generously so to be safe I take the number of rows from
		// the largest section of data to be used in the multiplier.

		$catcount = $catcount + $tagcount;
		$postcount= $pagecount + $postcount + $authorcount;

		ShowDebug("which is bigger catcount = $catcount OR postcount = $postcount");

		// use the biggest value
		$rows		= ($catcount > $postcount) ? $catcount : $postcount;

		ShowDebug("rows for group_concat_max_len sun = $rows");

		// if we have a valid number
		if($rows > 0){

			// want to take the longest possible format as insurance
			$catlen		= strlen($this->category_rewrite);
			$postlen	= strlen($this->post_rewrite );

			$len		= ($catlen>$postlen) ? $catlen : $postlen;

			// add the size of our blog url which is always going to be added on each record
			$len		= $len + strlen($this->clean_siteurl);

			// without actually doing another SELECT to find the max possible length I need to find out the max possible size to set the join buffer
			// so that the full XML is built and not cut off. Therefore I need to be generous in my allowance and expect the worst
			// for permalinks with %category% I assume a 3 level parent>child>grandchild on each post with a max 200 chars for the slug
			// and 200 for the post title. I also add 150 for the XML wrapper and take the size of the permalink structure as well.
			if($this->post_requires_category){
				$len = ($len + 1000) * $rows;
			}else{
				$len = ($len + 400) * $rows;
			}			
			// run SQL to set the correct group concat max length setting
			StrictlyPlugin::exec_qry("SET SESSION group_concat_max_len = $len;");

		}else{
			// default to a big group concat setting which should cover most eventualities
			StrictlyPlugin::exec_qry("SET SESSION group_concat_max_len = 20971520;");
		}

		ShowDebug("do we build pages?");

		// do we add pages ?
		if($this->buildopts["pages"]){
			
			$sql_select = $sql_from = $sql_where = "";

			if($this->do_page_rewrite){

				if($this->addtrailingslash){
					$sql_pageurl = "'" . $this->clean_siteurl . "/' , post_name , '/'";
				}else{
					$sql_pageurl = "'" . $this->clean_siteurl . "/' , post_name";
				}
				
			}else{
				$sql_pageurl = "'" . $this->clean_siteurl . "/?page_id=', id";
			}

			// do we need to exclude a page if its being used as the homepape?
		/*	if(!empty($this->excluded_posts)){
				$sql_where .= "AND wp.id <> " . $this->excluded_posts . " ";
			}*/

			
			$sql = "SELECT  GROUP_CONCAT(CONCAT('\t<url>\n\t\t<loc>'," . $sql_pageurl . ",'</loc>\n\t\t<lastmod>', REPLACE(post_modified_gmt,' ','T'),'Z</lastmod>\n\t\t<changefreq>" . $this->changefreq["page"] . "</changefreq>\n\t\t<priority>" . $this->priority["page"] . "</priority>\n\t</url>\n') ORDER BY post_modified_gmt DESC SEPARATOR '\n') as XMLSitemap
					FROM	##WP_PREFIX##posts as wp
					WHERE	post_password='' AND post_status='publish' AND post_type='page' " . $sql_page_where . ";";

			$this->sitemap_data[] = StrictlyPlugin::get_xml_result($sql);			
		}

		// do we add posts ?
		if($this->buildopts["posts"]){
			
			// what type of link are we outputting?
			if($this->do_post_rewrite){

				if($this->addtrailingslash){
					$sql_posturl =  "'" . $this->clean_siteurl . $this->post_rewrite . "/'"; 
				}else{
					$sql_posturl =  "'" . $this->clean_siteurl . $this->post_rewrite . "'"; 
				}	
				//$sql_posturl =  "'" . $this->clean_siteurl . $this->post_rewrite . "'"; 
			}else{
				$sql_posturl =	"'" . $this->clean_siteurl . "/?p=' + CAST(t.id as varchar)";
			}
			
			$sql_select = $sql_from = "";
			
			//$sql_post_where = "post_password='' AND post_status='publish' AND post_type='post' ";
			
			
			// do we need to get all the component parts for a rewritten post URL?
			if($this->do_post_rewrite){

				// ensure the correct replace functions are wrapped around the URL depending on the known permalink params

				// do we need to add post_id
				if($this->post_requires_postid){
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%post_id%',t.id)";
				}

				// do we need to add author name OR are we excluding posts by an author
				if($this->post_requires_author || !empty($this->sitemap["excluded_authors"])){
					
					if($this->post_requires_author){
						$sql_posturl = "REPLACE(" .$sql_posturl . ",'%author%',t.user_nicename)";

						$sql_select .= ", wu.user_nicename";
					}

					// need to join to users table to get author name
					$sql_from .= "LEFT JOIN ##WP_PREFIX##users as wu
								  ON wu.ID = wp.post_author ";

			/*		if(!empty($this->sitemap["excluded_authors"])){
						$sql_post_where .= "AND display_name NOT IN('" . str_replace(",","','",$this->sitemap["excluded_authors"]) ."') ";
					}*/
				}

				// do we need to add category chain of up to 3 levels e.g /news/latest/uk/
				// also ensure any empty records use the default category
				if($this->post_requires_category){
					
					// default to not adding a slash on the last category item as we might end up with doubles depending on whether the category parameter
					// is the last item in the peramlink structure 
					$category_slash = "";

					// is the category param the last one in the permalink structure? If its not then we don't need to append a slash to the last category in the
					// category hierarchy parent/child/grandchild/
					if(preg_match("@%category%/?$@", $this->post_rewrite)){
						
						// category is the last item in the structure only add a slash on if the site requires it
						if($this->addtrailingslash){
							$category_slash = "/";
						}
					}
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%category%',COALESCE(CategorySlugs,'" . $this->default_category . "') )";

					// need a 3 way SUB SELECT to ensure up to 3 levels of category are shown
					// anymore than 3 won't work and is likely to kill Wordpres performance anyway when loading pages!
					$sql_select .=	",(SELECT CONCAT(COALESCE(CONCAT(t3.slug,'/'),''), COALESCE(CONCAT(t2.slug,'/'),''), COALESCE(CONCAT(t.slug,'" . $category_slash  ."'),'')) as CategorySlugs
									FROM ##WP_PREFIX##term_relationships as r 
									LEFT JOIN	##WP_PREFIX##term_taxonomy as wt 
									ON	wt.term_taxonomy_id = r.term_taxonomy_id 
									LEFT JOIN	##WP_PREFIX##terms as t 
									ON	wt.term_id = t.term_id 
									LEFT JOIN ##WP_PREFIX##terms as t2 
									ON	wt.Parent = t2.term_id 
									LEFT JOIN ##WP_PREFIX##term_taxonomy as wt2
									ON wt2.term_id =t2.term_id AND wt2.taxonomy='category'
									LEFT JOIN ##WP_PREFIX##terms as t3
									ON wt2.parent = t3.term_id
									WHERE r.object_id = wp.id AND wt.taxonomy='category' 
									ORDER BY CASE WHEN t3.Slug IS NOT NULL AND t2.Slug IS NOT NULL AND t.Slug IS NOT NULL THEN 3
											WHEN t3.Slug IS NULL AND t2.Slug IS NOT NULL AND t.Slug IS NOT NULL THEN 2
											WHEN t3.Slug IS NULL AND t2.Slug IS NULL AND t.Slug IS NOT NULL THEN 1
											ELSE 0 END  DESC LIMIT 1) as CategorySlugs ";

				}
				
				// do we need to add year, month, day
				if($this->post_requires_yearmonthday){

					// replace year param
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%year%',year(t.post_date) )";

					// replace month param
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%monthnum%',RIGHT(concat('0' ,month(t.post_date)),2) )";

					// replace day param
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%day%',RIGHT(concat('0' ,day(t.post_date)),2) )";
				}

				// do we need to add hour, minute, second
				if($this->post_requires_yearmonthday){

					// replace year param
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%hour%',RIGHT(concat('0' ,hour(t.post_date)),2) )";

					// replace month param
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%minute%',RIGHT(concat('0' ,minute(t.post_date)),2) )";

					// replace day param
					$sql_posturl = "REPLACE(" .$sql_posturl . ",'%second%',RIGHT(concat('0' ,second(t.post_date)),2) )";
				}


				// now add post_name
				$sql_posturl = "REPLACE(" .$sql_posturl . ",'%postname%',t.post_name)";
				
			}
			
			// are we excluding posts?
		/*	if(!empty($this->sitemap["excluded_posts"])){
				$sql_where .= "AND wp.id NOT IN(" . $this->sitemap["excluded_posts"] .") ";
			}*/

			// are we excluding posts belonging to certain categories?
			if(!empty($this->sitemap["excluded_cats"])){

				$sql_from	.= "JOIN ##WP_PREFIX##term_relationships AS tr 
								ON wp.id = tr.object_id
								JOIN ##WP_PREFIX##term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id ";

				//$sql_where	.= "AND tt.taxonomy IN ('category') AND tt.term_id NOT IN (" . $this->sitemap["excluded_cats"] .") ";
			}

			$sql = "SELECT	GROUP_CONCAT(CONCAT('\t<url>\n\t\t<loc>'," . $sql_posturl . ",'</loc>\n\t\t<lastmod>',REPLACE(post_modified_gmt,' ','T'),'Z</lastmod>\n\t\t<changefreq>" . $this->changefreq["post"] . "</changefreq>\n\t\t<priority>" . $this->priority["post"] . "</priority>\n\t</url>\n') ORDER BY post_modified_gmt DESC SEPARATOR '\n') as XMLSitemap
			FROM	(SELECT DISTINCT wp.id, post_name, post_modified_gmt, post_date " . $sql_select. "				
					FROM	##WP_PREFIX##posts as wp " . $sql_from . " 
					WHERE	" . $sql_post_where . ") as t;";				

			// run SQL
			$this->sitemap_data[] = StrictlyPlugin::get_xml_result($sql);
			
		}


		// do we ouput authors?
		if($this->buildopts["authors"]){

			if($this->do_author_rewrite){

				if($this->addtrailingslash){
					$sql_authorurl =  "'" . $this->clean_siteurl . $this->author_rewrite."/'";
				}else{
					$sql_authorurl =  "'" . $this->clean_siteurl . $this->author_rewrite. "'";
				}

				$sql_authorurl = "REPLACE(" .$sql_authorurl . ",'%author%',user_nicename)";

			}else{
				$sql_authorurl = "'" . $this->clean_siteurl . "/?author=', id";
			}	

/*
			if(!empty($this->sitemap["excluded_authors"])){
				$sql_where = "AND display_name NOT IN('" . $this->sitemap["excluded_authors"] ."') ";
			}else{
				$sql_where = "";
			}
*/
			$sql =		"SELECT GROUP_CONCAT(CONCAT('\t<url>\n\t\t<loc>'," . $sql_authorurl . ",'</loc>\n\t\t<lastmod>" . $this->default_timestamp	 . "</lastmod>\n\t\t<changefreq>" . $this->changefreq["author"] . "</changefreq>\n\t\t<priority>" . $this->priority["author"] . "</priority>\n\t</url>') ORDER BY user_nicename SEPARATOR '\n') as XMLSitemap
						FROM (
						SELECT u.ID,u.user_nicename
						FROM ##WP_PREFIX##posts as p
						JOIN ##WP_PREFIX##users as u
						ON u.ID = p.post_author
						WHERE p.post_status = 'publish'
								AND p.post_type = 'post'
								AND p.post_password = ''
								". $sql_author_where ."
						GROUP BY u.ID,p.post_author
						) as t;";

			$this->sitemap_data[] = StrictlyPlugin::get_xml_result($sql);
		}

		// People should keep their code up to date for security reasons. The old style category tables have been replaced long ago!
		// However if taxonomy is not supported we cannot add categories or tags to the DB as I am only using the new format
		if(!$this->IsTaxonomySupported()){
			return;
		}

		// do we ouput categories?
		if($this->buildopts["categories"]){

			if($this->do_category_rewrite){

				if($this->addtrailingslash){
					$sql_categoryurl =  "'" . $this->clean_siteurl . $this->category_rewrite."/'";
				}else{
					$sql_categoryurl =  "'" . $this->clean_siteurl . $this->category_rewrite. "'";
				}

				$sql_categoryurl = "REPLACE(" .$sql_categoryurl . ",'%category%',t.slug)";
			}else{
				$sql_categoryurl = "'" . $this->clean_siteurl . "/?cat=', t.term_id";
			}							
			
			// are we excluding posts belonging to certain categories?
	/*		if(!empty($this->sitemap["excluded_cats"])){
				$sql_where	= "AND tt.term_id NOT IN (" . $this->sitemap["excluded_cats"] .") ";
			}else{
				$sql_where  = "";
			} */

			$sql = "SELECT  GROUP_CONCAT(CONCAT('\t<url>\n\t\t<loc>'," . $sql_categoryurl . ",'</loc>\n\t\t<lastmod>',REPLACE(NOW(),' ','T'),'Z</lastmod>\n\t\t<changefreq>" . $this->changefreq["category"] . "</changefreq>\n\t\t<priority>" . $this->priority["category"] . "</priority>\n\t</url>') ORDER BY Name SEPARATOR '\n') as XMLSitemap
					FROM	##WP_PREFIX##terms AS t 
					JOIN	##WP_PREFIX##term_taxonomy AS tt 
						ON	t.term_id = tt.term_id
					WHERE	tt.taxonomy IN ('category') " . $sql_category_where;
			
			$this->sitemap_data[] = StrictlyPlugin::get_xml_result($sql);

		}

		// do we ouput tags?
		if($this->buildopts["tags"]){

			// do tags
			if($this->do_tag_rewrite){

				if($this->addtrailingslash){
					$sql_tagurl =  "'" . $this->clean_siteurl . $this->tag_rewrite ."/'";
				}else{
					$sql_tagurl =  "'" . $this->clean_siteurl . $this->tag_rewrite . "'";
				}

				// they seem to have changed from %tag% to %post_tag% around 3.0.1 I'm not too sure but I know in 3.1 they use %post_tag%
				if(floatval($wp_version) < 3.1){
					// do both just in case
					$sql_tagurl = "REPLACE(REPLACE(" .$sql_tagurl . ",'%tag%',t.slug),'%post_tag%',t.slug)";
				}else{
					$sql_tagurl = "REPLACE(" .$sql_tagurl . ",'%post_tag%',t.slug)";
				}
			}else{
				$sql_tagurl = "'" . $this->clean_siteurl . "/?tag=', t.slug";
			}		
			

			$sql = "SELECT  GROUP_CONCAT(CONCAT('\t<url>\n\t\t<loc>'," . $sql_tagurl . ",'</loc>\n\t\t<lastmod>',REPLACE(NOW(),' ','T'),'Z</lastmod>\n\t\t<changefreq>" . $this->changefreq["tag"] . "</changefreq>\n\t\t<priority>" . $this->priority["tag"] . "</priority>\n\t</url>') ORDER BY Name SEPARATOR '\n') as XMLSitemap
					FROM	##WP_PREFIX##terms AS t 
					JOIN	##WP_PREFIX##term_taxonomy AS tt 
					ON	t.term_id = tt.term_id
					WHERE	tt.taxonomy IN ('post_tag');";
			
			$this->sitemap_data[] = StrictlyPlugin::get_xml_result($sql);

		}	

		ShowDebug("do we output archives  == " . $this->buildopts["archives"]);

		// do we ouput archives?
		if($this->buildopts["archives"]){

			ShowDebug("yes do we rewrite = " . $this->do_archive_rewrite);

			// do archives
			if($this->do_archive_rewrite){

				if($this->addtrailingslash){
					$sql_archiveurl =  "'" . $this->clean_siteurl . $this->archive_rewrite ."/'";
				}else{
					$sql_archiveurl =  "'" . $this->clean_siteurl . $this->archive_rewrite . "'";
				}

				// replace year param
				$sql_archiveurl = "REPLACE(" .$sql_archiveurl . ",'%year%',year(post_date) )";

				// replace month param
				$sql_archiveurl = "REPLACE(" .$sql_archiveurl . ",'%monthnum%',RIGHT(concat('0' ,month(post_date)),2) )";

				
			}else{
				$sql_archiveurl = "'" . $this->clean_siteurl . "/?m=', YEAR(post_date_gmt),RIGHT(concat('0' ,month(post_date)),2)";
			}		
		
			// not using a group_concat here because I need to do specific grouping

			$sql = "SELECT  CONCAT('\t<url>\n\t\t<loc>'," . $sql_archiveurl . ",'</loc>\n\t\t<lastmod>',REPLACE(MAX(post_date_gmt),' ','T'),'Z</lastmod>\n\t\t<changefreq>" . $this->changefreq["archive"] . "</changefreq>\n\t\t<priority>" . $this->priority["archive"] . "</priority>\n\t</url>') as XMLSitemap
					FROM	##WP_PREFIX##posts
					WHERE	post_date < NOW()
							AND post_status = 'publish'
							AND post_type = 'post'			
							" . (floatval($wp_version) < 2.1?"AND post_date_gmt <= NOW()":"") . "
					GROUP BY YEAR(post_date_gmt),MONTH(post_date_gmt)
					ORDER BY post_date_gmt DESC;";
			
			ShowDebug( $sql);


			$results = StrictlyPlugin::get_query_results($sql);
			
			foreach($results as $row){

				ShowDebug("add row to cache");

				$this->sitemap_data[] .= $row->XMLSitemap . "\n";

			}
			
			// cleanup as we go
			unset($results);	

		}


		ShowDebug("all archives done");

		ShowDebug("count of records in sitemap_data array = " . count($this->sitemap_data));

		ShowDebug("do we have custom pages = " . $this->buildopts["custompages"]);

		// do we have custom pages to add?
		if($this->buildopts["custompages"]){


			$custom = $this->sitemap["custompages"];

			ShowDebug("yes custom pages = $custom");

			if(!empty($custom)){

				// each custom page should be on its own line
				$custom_pages = explode("\n",$custom);

				ShowDebug($custom_pages);

				foreach($custom_pages as $page){
					
					$page = trim($page);

					ShowDebug("custom page == $page");

					if(!empty($page)){

						// there maybe a specific change frequency or priority value
						list($url,$priority,$changefreq) = explode(" ",trim(preg_replace("@\s+@"," ",$page)));

						ShowDebug("url = $url");
						ShowDebug("priority = $priority");
						ShowDebug("changefreq = $changefreq");


						if(!preg_match("@^0\.[1-9]$@",$priority)){
							$priority = $this->priority['custompages'];
						}
						if(!preg_match("@^(always|never|hourly|daily|weekly|monthly|yearly)$@",$changefreq)){
							$changefreq = $this->changefreq['custompages'];
						}
						
						ShowDebug("check $url exists");

						// try to get the correct date the file was last updated
						$http = $this->GetHTTP(trim($url));

						if($http['status']=="200"){

								
							$lastmod = trim($http['headers']['last-modified']);

							
							ShowDebug("yes last mode date is $lastmod");

							if(!empty($lastmod)){
								$lastmod = StrictlyTools::ConvertFromFileStamp($lastmod);
							}else{
								// use the current date
								$lastmod = $this->default_timestamp;
							}

							$sites = "\t<url>\n";
							$sites .= "\t\t<loc>"		.$url			."</loc>\n";
							$sites .= "\t\t<lastmod>"	.$lastmod		."</lastmod>\n";
							$sites .= "\t\t<changefreq>".$changefreq	."</changefreq>\n";
							$sites .= "\t\t<priority>"	.$priority		."</priority>\n";
							$sites .= "\t</url>\n";		

							$this->sitemap_data[] = $sites;
						}
					}
				}
				unset($custom_pages,$page);
			}
		}

		ShowDebug("END of CREATE() count of records in sitemap_data array = " . count($this->sitemap_data));
	}

	 /**
	 * Pings each major search engine e.g Google, Ask, Bing, Yahoo to let them know a new sitemap has been built
	 */
	protected function PingSearchEngines(){

		ShowDebug("IN PingSearchEngines");

		// might not be pinging anything
		$pinged		= false;
		$pingmsg	= "";		

		// now ping all servers

		foreach($this->pingurls as $engine){
			// get name of service
			$name = $engine['name'];

			ShowDebug("do we ping " . $name . " == " . $this->pingopts[$name]);

			// only ping it if the user has specified he wants to
			if($this->pingopts[$name]){

				ShowDebug("yes we will ping $name");				

				// if its yahoo only bother pinging if they have set up an API key
				if($name == "Yahoo" && empty($this->pingopts['yahoo_api'])){					
					$skip = true;
				}else{
					$skip = false;
				}

				ShowDebug("do we skip = " . $skip);

				if(!$skip){

					$pinged = true;

					// are we pinging with a gzip sitemap or a normal one
					if($this->sitemap['gzip']){
						$pingurl = $engine['gzipurl'];
					}else{
						$pingurl = $engine['url'];
					}

					ShowDebug("Ping $pingurl");

					// ping the url and get back response object
					$http = $this->GetHTTP($pingurl);							
					if($http['status']=="200"){

						ShowDebug("PINGED $pingurl OK");

						if(strpos($http['body'], $engine['snippet']) !== false){
							$pingmsg .= '<li>'.sprintf(__('%s was pinged successfully with: ', 'strictlysitemap'), $engine['service']).'<a href="'.$pingurl.'">'.$pingurl.'</a></li>';
						}else{
							$pingmsg .= '<li><span style="color:#cc0000">'.sprintf(__('Unsuccessful ping attempt to %s with: ', 'strictlysitemap').'</span>', $engine['service']).'<a href="'.$pingurl.'">'.$pingurl.'</a></li>';			
						}
					}else{

						ShowDebug("PINGED $pingurl FAILED");

						$pingmsg .= '<li><span style="color:#cc0000">'.sprintf(__('Unsuccessful ping attempt to %s due to an HTTP error: %s ', 'strictlysitemap').'</span>', $engine['service'], $http['message']).'<a href="'.$pingurl.'">'.$pingurl.'</a></li>';		
					}
				}
			}
			
		}

		// if we pinged at least one service then update the global message
		if($pinged){
			$this->msg .= '<ul style="margin-top:10px;">' . $pingmsg . '</ul>';
		}else{
			$this->msg .= '<ul style="margin-top:10px;">' . __('No Search Engines were pinged','strictlysitemap') . '</ul>';
		}
	}

	/**
	 * Checks the sites Robots.txt file to see if it already contains a reference to the sitemap. Does a "real" check by loading it
	 * the actual robots.txt file from the web
	 *
	 * @return boolean
	 */
	protected function CheckRobots(){

		$url = $this->clean_siteurl . "/robots.txt";

		$http = $this->GetHTTP($url);

		if($http["status"]=="200"){
			
			// store whether the file is empty or not as we need to know whether a new line should be added before any new directives
			$robots = trim($http["body"]);
			if(empty($robots)){
				$this->sitemap['emptyrobots'] = true;
			}else{
				$this->sitemap['emptyrobots'] = false;					
			}

			// now look for sitemap directive
			$regex = "Sitemap:\s+";

			// look for sitemap:
			if(!preg_match("/Sitemap:/i",$http["body"])){
				
				// set a message for our admin
				$this->robot_msg = sprintf(__("Your robots.txt file doesn't contain a Sitemap directive to help search engine crawlers locate your site links e.g <strong>Sitemap: %s </strong>","strictlysitemap"), $this->sitemap_fullurl );
				
				return false;				
			}else{
				return true; 
			}

		}elseif($http["status"]=="404"){
			$this->robot_msg = sprintf(__("No robots.txt file could be found at: %s","strictlysitemap"), $url);
		}else{
			$this->robot_msg = sprintf(__("The robots.txt file could not be accessed at: %s due to a %s error.","strictlysitemap"), $url, $http["message"]);
		}
		
		return false;

	}

	/**
	 * validates the sitemap or sitemap index against the correct schemas
	 *
	 * @param string $url
	 * @param bool $index
	 * @return bool
	 */
	public function ValidateSitemap($url,$index){

		ShowDebug("IN ValidateSitemap $url $index");

		// we load the sitemap up
		$http = $this->GetHTTP($url);

		if($http["status"] == "200"){
			
			$xml = $http["body"];
			
			// for gzipped files uncompress
			if(substr($url,-3)==".gz"){				
				
				// decompress the gzip otherwise any XML checks will fail
				$xml = gzinflate(substr($xml,10));

			}	

			$xdoc = new DomDocument;				

			// Load the xml document in the DOMDocument object which tests whether its well formed as it will
			// error/return false if its not
			if($xdoc->loadXML($xml)){

				// its well formed so load up the correct schema and test the xml against it

				// if its a sitemap index we load a different xsd
				if($index==true){
					$schemaHTTP = $this->GetHTTP($this->sitemap_index_schemaurl);
				}else{
					$schemaHTTP = $this->GetHTTP($this->sitemap_schemaurl);
				}

				ShowDebug("status = " . $schemaHTTP["status"]);

				if($schemaHTTP["status"]=="200"){

					$schema = $schemaHTTP["body"];					

					// Validate the XML file against the schema
					if ($xdoc->schemaValidateSource($schema)){					
						return "Valid";
					}else{
						return "Invalid";
					}
				}
			}else{
				return "Invalid XML";
			}

		}else{
			return $http["message"];
		}		
	}

	/**
	 * Wrapper function for wp_remote_get that uses the best available method for accessing remote content e.g CURL,FSOCK,HTTP
	 * This function just reformats the status and message so it can be accessed easier and returns an error message
	 *
	 * @param string $url
	 * @return array
	 */
	protected function GetHTTP($url){
	
		$http = StrictlyPlugin::wp_remote_get($url);

		// check for a response
		if(isset($http["response"])){

			$status = $http["response"]["code"];
			$message= $http["response"]["message"];
			$headers= $http["headers"];
			$success= true;

			if($status == "200"){
				$body = $http["body"];
			}else{
				$body = "";
			}
		}elseif(isset($http["errors"])){
			$status	= $headers = $body = null;
			$message= implode($http["errors"]["http_request_failed"],"");
			$success= false;			
		}else{
			$status	= $headers = $body = null;
			$message= "Unknown error making HTTP request to: $url";
			$success= false;
		}
		
		unset($http);

		return array('success' => $success, 'status' => $status, 'message' => $message, 'body' => $body, 'headers' => $headers);		
		
	}

	/**
	 * Creates the correct number of sitemap files and an index file if required
	 *
	 * @param array $sitemap
	 * @return boolean
	 */
	protected function BuildSitemap($sitemap){

		ShowDebug("IN BuildSitemap");

		ShowDebug("count of records in sitemap array = " . count($sitemap));

		// join our array which will have 8 sections at most (home,pages,post,articles,categories,tags,custom,archive)
		// together into one string which we can then split into an array of all component parts if we need to
		$total_xml	= implode('',$sitemap);

		ShowDebug("total length of XML = " . strlen($total_xml) . "<br/>" . $total_xml);

		// do we ned to create multiple files?
		$this->records_per_sitemap = (int)$this->sitemap['records'];

		// use the count we did before building the XML to determine whether a split is required
		// very unlikely that posts will have been added in the <1 second it took to build the file however if we are
		// within 20 records of the sitemap file limit we will check the XML just in case new categories/tags or posts
		// managed to get added during the build (maybe there is a Wordpress variable that can be set to prevent this?)
		ShowDebug("there were " . $this->total_records_pre_exec . " records before the build started");

		if($this->total_records_pre_exec > ($this->records_per_sitemap - 20)){

			ShowDebug("within 20 of our limit so count by splitting the XML instead");

			// get a "true" count of the no of records in our sitemap. Need to do this as we didn't do a cumbersome loop
			// through the recordset to build it in the first place. Obviously this is an overhead (and probably the biggest one in this method)
			// even so its much less of an overhead than carrying out thousands of loop iterations and DB calls to get permalink structures and all the rest that other plugins do
			$recs		= preg_match_all("@(<url>[\s\S]+?</url>)@",$total_xml,$match);		

			ShowDebug("after preg_match_all split we have " . $recs . " records");

		}else{
			ShowDebug("set recs to " . $this->total_records_pre_exec);

			$recs		= $this->total_records_pre_exec;
		}

		// if we have more records than we want per sitemap file then we need to create a sitemap index file and multiple sitemaps
		// a sitemap is only allowed a maximum of 50,000 records in it anyway		

		ShowDebug("there are $recs in the XML and we have " . $this->records_per_sitemap . " per file");

		if($recs > $this->records_per_sitemap){

			ShowDebug("create an index file");

			// create a site index file
			$sitemapindex = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
							"<?xml-stylesheet type=\"text/xsl\" href=\"".$this->xslindexurl."\"?>\n".
							"<!-- generator=\"wordpress/2.7\" -->\n" .
							"<!-- sitemap-generator-url=\"" . $this->website . "\" " .  $this->plugin_for_xml . "=\"".$this->version . "." . $this->build . "\" -->\n".
							"<!-- generated-on=\"".date('d. F Y')."\" -->\n" .
							"<sitemapindex xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd\" xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";	
							

			$files = $recs / $this->records_per_sitemap;
			if($files <> intval($files)) $files = intval($files + 0.5);

			ShowDebug("we need to build $files sitemap files");


			for($x = 1; $x <= $files; $x++){
				
				$sitemapindex .= "\t<sitemap>\n".
								 "\t\t<loc>"		. $this->clean_siteurl		. "/sitemap-" . $x . ".xml</loc>\n".
								 "\t\t<lastmod>"	. $this->default_timestamp	. "</lastmod>\n".
								 "\t</sitemap>\n";
			}

			$sitemapindex .= '</sitemapindex>';

			// create the sitemap index file
			if(!$this->CreateFile($this->sitemap_indexpath, $sitemapindex, "Sitemap Index",$msg,false)){
				// append any error message
				$this->msg .= $msg;
				return false;
			}

			// do we also create a gzip version - can only be set if site supports it
			if($this->sitemap['gzip']){

				//$gz = gzencode($sitemapindex,9);

				if(!$this->CreateFile($this->sitemap_indexpath . ".gz", $sitemapindex, "Sitemap Index",$msg,true)){
					// append any error message
					$this->msg .= $msg;
					return false;
				}
			}

			unset($sitemapindex);
		}else{
			$files = 1; //only need one file
		}		

		ShowDebug("how many files are needed = $files");

		// multiple files will use up more memory as we have to split up the string into chunks
		if($files == 1){

			unset($sitemap,$match);

			$this->BuildSitemapFile($total_xml,$this->sitemap_fullpath);

		}else{

			ShowDebug("split into chunks");

			// split array into chunks of specified size
			$chunks = array_chunk($match[1], $this->records_per_sitemap, true);		

			ShowDebug("we have " . count($chunks) . " chunks");

			unset($sitemap,$match);

			// reset counter variable so I can resuse it
			$x = 1;

			// loop through chunks creating a file per block
			foreach($chunks as $chunk){

				// need to increment our filenames
				$sitemappath = $this->sitemap_path	.$this->sitemap_name . "-" .$x .'.xml';				

				$x++;

				$this->BuildSitemapFile(str_replace("</url><url>","</url>\n\t<url>",implode("",$chunk)),$sitemappath);

			}

			unset($chunks,$chunk);
		}
	
		ShowDebug("store no of recs in this->sitemap_record_count = " . $recs);
		ShowDebug("formatted is " . number_format($this->sitemap_record_count));

		// store no of records in sitemap
		$this->sitemap_record_count = $recs;

		// all files created so set up a message to tell the user
		if($files > 1){
			if($this->sitemap['gzip']){
				$build_msg = sprintf(__('<p>An uncompressed and compressed version of the Sitemap has been created.</p><p>Both versions contain 1 Sitemap Index file and %d Sitemaps with a total of %s entries.</p><p>Size of largest sitemap file: %s</p></p>Size of largest gzip sitemap file: %s</p>', 'strictlysitemap'), $files,number_format($this->sitemap_record_count),StrictlyTools::ConvertFromBytes($this->sitemap_biggest_file),StrictlyTools::ConvertFromBytes($this->sitemap_biggest_file_gzip));
			}else{
				$build_msg = sprintf(__('<p>1 Sitemap Index file and %d Sitemaps with %s entries were created.</p><p>Size of largest sitemap file: %s</p>', 'strictlysitemap'), $files,number_format($this->sitemap_record_count),StrictlyTools::ConvertFromBytes($this->sitemap_biggest_file));
			}
		}else{
			if($this->sitemap['gzip']){
				$build_msg = sprintf(__('<p>An uncompressed and compressed version of the Sitemap has been created containing %s entries each.</p><p>Size of sitemap file: %s</p></p>Size of gzip sitemap file: %s</p>', 'strictlysitemap'),number_format($this->sitemap_record_count),StrictlyTools::ConvertFromBytes($this->sitemap_biggest_file),StrictlyTools::ConvertFromBytes($this->sitemap_biggest_file_gzip));
			}else{
				$build_msg = sprintf(__('<p>1 Sitemap with %s entries was created.</p><p>Size of sitemap file: %s</p>', 'strictlysitemap'),number_format($this->sitemap_record_count),StrictlyTools::ConvertFromBytes($this->sitemap_biggest_file));
			}
		}
		
		// ensure the biggest files are less than 10MB in size as this is the limit imposed by the sitemap standard
		if(($this->sitemap['gzip'] && $this->sitemap_biggest_file_gzip > 10485760) || ($this->sitemap_biggest_file > 10485760)){
			
			$build_msg .= __("<p>One or more sitemap files are over the allowed limit of 10MB. Please reduce the number of records per file.</p>"."strictlysitemap");			

		}

		ShowDebug("sitemap url = " . $this->sitemap_url);
		ShowDebug("sitemap url = " . $this->sitemap_gzipurl);

		// create some links to validate the sitemap OR sitemap index
		if($files > 1){
			$validatelink = '<a href="javascript:void(0);" onclick="validatesitemap(true,\'' . urlencode($this->sitemap_fullurl) . '\',false);return false;">'.__('validate','strictlysitemap').'</a> <span id="validatesitemapindexresult"></span>';

			if($this->sitemap['gzip']){
				$validatelinkgzip = '<a href="javascript:void(0);" onclick="validatesitemap(true,\'' . urlencode($this->sitemap_gzipurl) . '\',true);return false;">'.__('validate','strictlysitemap').'</a> <span id="validatesitemapindexresultgzip"></span>';
			}
		}else{
			$validatelink = '<a href="javascript:void(0);" onclick="validatesitemap(false,\'' . urlencode($this->sitemap_fullurl) . '\',false);return false;">'.__('validate','strictlysitemap').'</a> <span id="validatesitemapresult"></span>';

			if($this->sitemap['gzip']){
				$validatelinkgzip = '<a href="javascript:void(0);" onclick="validatesitemap(false,\'' . urlencode($this->sitemap_gzipurl) . '\',true);return false;">'.__('validate','strictlysitemap').'</a> <span id="validatesitemapresultgzip"></span>';
			}
		}

		$build_msg .= sprintf(__('<p>Sitemap: %s %s</p>', 'strictlysitemap'),'<a href="'.$this->sitemap_fullurl.'">'.$this->sitemap_fullurl.'</a>',$validatelink);
		if($this->sitemap['gzip']){
			$build_msg .= sprintf(__('<p>Sitemap: %s %s</p>', 'strictlysitemap'),'<a href="'.$this->sitemap_gzipurl.'">'.$this->sitemap_gzipurl.'</a>',$validatelinkgzip);
		}

		// set global message
		$this->msg .= $build_msg;

		return true;		

	}

	/**
	 * Creates a sitemap file
	 * 
	 * @param string $content
	 * @return bool
	 *
	 */
	protected function BuildSitemapFile($content,$sitemappath){

		ShowDebug("IN BuildSitemapFile");
		
		// we may be passing in a string OR an array that needs to be formatted into a string
		if(is_array($content)){

			ShowDebug("data is in an array so implode");

			$content = implode("",$content);
		}
		
		ShowDebug("len of content for file = " . strlen($content));

		$sitemapxml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
							"<?xml-stylesheet type=\"text/xsl\" href=\"".$this->xslurl."\"?>\n".
							"<!-- generator=\"wordpress/2.7\" -->\n" .
							"<!-- sitemap-generator-url=\"" . $this->website . "\" " .  $this->plugin_for_xml . "=\"".$this->version . "." . $this->build . "\" -->\n" .
							"<!-- generated-on=\"".date('d. F Y')."\" -->\n" .
							"<urlset xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\" xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n\t".$content."\n</urlset>\n";
		
		// save file and quit on error
		if(!$this->CreateFile($sitemappath, $sitemapxml, "Sitemap",$msg,false)){
			// append any error message
			$this->msg .= $msg;
			return false;
		}else{
			$this->sitemap_file_count ++;
		}

		// log file size as max size per sitemap is 10MB
		$size = filesize($sitemappath);			

		ShowDebug("size of this file = " . $sitemappath . " is " . $size . " is it bigger than existing biggest file = " . $this->sitemap_biggest_file);

		// if this size was bigger than last file size replace it
		if($size > $this->sitemap_biggest_file){

			$this->sitemap_biggest_file = $size;

			ShowDebug("yes so set to " . $this->sitemap_biggest_file);
		}

		// do we also create a gzip version
		if($this->sitemap['gzip']){

			//$gz = gzencode($sitemapxml,9);

			if(!$this->CreateFile($sitemappath . ".gz", $sitemapxml, "Sitemap",$msg,true)){
				// append any error message
				$this->msg .= $msg;					
				return false;
			}else{
				$this->sitemap_gzip_file_count ++;
			}

			// log file size as max size per sitemap is 10MB
			$size = filesize($sitemappath . ".gz");				

			ShowDebug("size of this GZIP file = " . $sitemappath . " is " . $size . " is it bigger than existing biggest file = " . $this->sitemap_biggest_file_gzip);

			// if this size was bigger than last file size replace it
			if($size > $this->sitemap_biggest_file_gzip){
				$this->sitemap_biggest_file_gzip = $size;

				ShowDebug("yes so set to " . $this->sitemap_biggest_file_gzip);
			}
		}

		return true;
	}


	/**
	 * Updates the Robots.txt to add a Sitemap directive	 
	 */
	function AddSitemapToRobots(){	
		if($this->sitemap['robotstxt']){
			
			// if we created a gzip sitemap then point to that
			if($this->sitemap['gzip']){
				$robots_sitemap_url = $this->sitemap_fullurl . ".gz";
			}else{
				$robots_sitemap_url = $this->sitemap_fullurl;
			}

			// if the robots file is empty then there is no need for a newline before the directive
			if($this->sitemap['emptyrobots']){
				echo "Sitemap: " . $robots_sitemap_url;
			}else{
				echo "\nSitemap: " . $robots_sitemap_url;
			}		
		}
	}

	/**
	 * Writes a file out if possible. Used to create a sitemap or index
	 *	 
	 * @param string $filename
	 * @param string $content
	 * @param string $filetype
	 * @return boolean
	 */
	protected function CreateFile($filename, $content, $filetype, &$msg, $gzip=false){

		$result = false;

		$content = trim($content);

		// don't bother if there is nothing to output
		if(!empty($content)){
			
			// check we have permission to write to this file and try to obtain permission if not
			if($this->IsFileWritable($filename)){
				
				if($gzip){

					$fh = gzopen($filename, 'wb9');

					if($fh){
						
						if(gzwrite($fh, $content) === false){
							echo sprintf(__('<p>ERROR: Could not gzip write to %s file: %s</p>', 'strictlysitemap'),$filetype,$filename);				
						}else{							
							$result = true;
						}

						gzclose($fh);

					}else{
						$msg = sprintf(__('<p>ERROR: Could not open %s file: %s</p>', 'strictlysitemap'),$filetype,$filename);					
					}

				}else{

					if(function_exists('file_put_contents')){
						try{
							file_put_contents($filename, $content);

							$result = true;
						}catch(Exception $e){
							$msg = $e->getMessage();
						}
					}else{
						$fh = fopen($filename, 'w');
						if($fh){
							if(fwrite($fh, $content) === false){
								$msg = sprintf(__('<p>ERROR: Could not write to %s file: %s</p>', 'strictlysitemap'),$filetype,$filename);				
							}else{
								$result = true;
							}
							fclose($fh);
						}else{
							$msg = sprintf(__('<p>ERROR: Could not open %s file: %s</p>', 'strictlysitemap'),$filetype,$filename);					
						}
					}
				}
			}else{
				$msg = sprintf(__('<p>ERROR: The %s file could not be updated due to permission problems.</p><p>Please make %s writable by using a command such as:</p><p>chmod 767 %s</p>', 'strictlysitemap'), $filetype,StrictlyTools::GetFilename($filename),$filename);				
			}

			return $result;
		}
	}


	/**
	 * Checks if this version of WordPress supports the new taxonomy system
	 *	 
	 * @return boolean
	 */
	protected function IsTaxonomySupported() {
		return (function_exists("get_taxonomy") && function_exists("get_terms"));
	}

	/**
	 * Returns true if GZIP is enabled on the system
	 *
	 * @return boolean
	 */
	protected function IsGzipEnabled() {
		return (function_exists("gzwrite"));
	}

	/**
	 * Checks if a file is writable and tries to make it if not.	
	 *
	 * @param string $filename
	 * @return boolean 
	 */
	protected function IsFileWritable($filename) {
		//can we write to our specified location?
		if(!is_writable($filename)) {
			// no so try to make the file writable - for security reasons this really shouldn't work!
			if(!@chmod($filename, 0666)) {
				$pathtofilename = dirname($filename);
				// Check if parent directory is writable
				if(!is_writable($pathtofilename)) {
					// nope so try to make it writable
					if(!@chmod($pathtoffilename, 0666)) {												
						return false;
					}
				}
			}
		}		
		return true;
	}
}