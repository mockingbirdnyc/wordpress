<?php

if(!function_exists('is_me')){

	
	// turn debug on for one IP only
	function is_me(){	
		
		$ip = "";           
		if (getenv("HTTP_CLIENT_IP")){ 
			$ip = getenv("HTTP_CLIENT_IP"); 
		}elseif(getenv("HTTP_X_FORWARDED_FOR")){
			$ip = getenv("HTTP_X_FORWARDED_FOR"); 			
		}elseif(getenv("REMOTE_ADDR")){
			$ip = getenv("REMOTE_ADDR");
		}else {
			$ip = "NA";
		}
		

		// put your IP here and remove the return false from above!!
		if($ip == "81.110.245.846"){
			return true;
		}else{
			return false;
		}

	}
}


// if the DEBUG constant hasn't been set then create it and turn it off
if(!defined('DEBUG')){


	if(is_me()){		
		define('DEBUG',FALSE);
	}else{		
		define('DEBUG',FALSE);
	}
}


// wrapper functions that point to Wordpress functions
// this way the "plugin" is still under GPL licence however the Strictly Google Sitemap library
// which consists of the strictly-google-sitemap.class.php and strictly-seo.class.php file are not
// feel free to do whatever you want this!
if(!defined('StrictlyPlugin')){

	class StrictlyPlugin{

		/**
		 * wrapper function for wordpress property wp_version
		 *
		 * @returns float
		 *
		 */
		function wp_version(){
			global $wp_version;
			return $wp_version;
		}

		/**
		 * wrapper function for wordpress function untrailingslashit
		 *
		 * @param string $val
		 * @return string
		 */
		function untrailingslashit($val){
			return untrailingslashit($val);
		}

		/**
		 * wrapper function for wordpress function trailingslashit
		 *
		 * @param string $val
		 * @return string
		 */
		function trailingslashit($val){
			return trailingslashit($val);
		}

		/**
		 * wrapper function for wordpress function get_option
		 *
		 * @param string $val
		 * @return variant
		 */
		function get_option($opt){
			return get_option($opt);
		}

		/**
		 * wrapper function for wordpress function get_option
		 *
		 * @param string $opt
		 * @return string
		 */
		function get_bloginfo($opt){
			return get_bloginfo($opt);
		}

		/**
		 * wrapper function for wordpress function plugin_dir_url
		 *
		 * @param string $file
		 * @return string
		 */
		function plugin_dir_url($file){
			return plugin_dir_url($file);
		}

		/**
		 * wrapper function for wordpress function update_option
		 *
		 * @param string $name
		 * @param variant $opt
		 */
		function update_option($name,$opt){
			update_option($name,$opt);
		}

		/**
		 * wrapper function for wordpress function add_action
		 *
		 * @param string $name
		 * @param array $actions
		 * @param integer $priority
		 * @param integer $args
		 */
		function add_action($name,$actions,$priority=10,$args=1){
			add_action($name,$actions,$priority,$args);
		}

		/**
		 * wrapper function for wordpress function load_textdomain
		 *
		 * @param string $dom
		 * @param string $file
		 */
		function load_textdomain($dom,$file){
			load_textdomain($dom,$file);
		}

		/**
		 * wrapper function for wordpress function get_transient
		 *
		 * @param string $name
		 * @return variant
		 */
		function get_transient($name){
			return get_transient($name);
		}

		/**
		 * wrapper function for wordpress function delete_transient
		 *
		 * @param string $name
		 * @return variant
		 */
		function delete_transient($name){
			return delete_transient($name);
		}

		/**
		 * wrapper function for wordpress function set_transient
		 *
		 * @param string $name
		 * @param variant $val
		 * @param integer $duration
		 */
		function set_transient($name,$val,$duration){
			set_transient($name,$val,$duration);
		}

		/**
		 * wrapper function for wordpress function wp_remote_post
		 *
		 * @param string $url
		 * @param variant $vals		
		 */
		function wp_remote_post($url,$vals){
			wp_remote_post($url,$vals);
		}

		/**
		 * wrapper function for wordpress function wp_remote_get
		 *
		 * @param string $url		
		 */
		function wp_remote_get($url){
			return (array)wp_remote_get($url);
		}

		/**
		 * wrapper function for wordpress function add_options_page
		 *
		 * @param string $page
		 * @param string $menu	
		 * @param string $cap
		 * @param string $slug
		 * @param function $func	
		 */
		function add_options_page($page,$menu,$cap,$slug,$func){
			add_options_page($page,$menu,$cap,$slug,$func);
		}

		/**
		 * wrapper function for wordpress function get_num_queries
		 *
		 * @return integer
		 */
		function get_num_queries(){
			return get_num_queries();
		}

		/**
		 * wrapper function for wordpress function check_rows
		 *
		 * @param string $sql
		 * @return boolean
		 */
		function check_rows($sql){

			global $wpdb;

			// replace placeholder for prefix with correct value now we have access to the wpdb object
			$sql = StrictlyPlugin::format_sql($sql);

			$results = $wpdb->get_results( $sql );
			if ( ! $wpdb->num_rows )
			{
				$ret = true;
			}else{
				$ret = false;
			}     
			unset($results);

			return $ret;
		}

		/**
		 * wrapper function for wordpress function wp_category_checklist
		 *
		 * @param integer $a
		 * @param integer $b
		 * @param array $c
		 * @param boolean $d
		 */
		function wp_category_checklist($a,$b,$c,$d){
			wp_category_checklist($a,$b,$c,$d);			
		}
	
		/**
		 * Checks whether a site has too many categories in parent/child relationships against posts
		 * The plugin only supports up to 3 levels
		 *
		 * @return integer
		 */
		function CheckCategoryDepth(){

			global $wpdb;

			$sql	=	"SELECT COUNT(*) as depth
						FROM	{$wpdb->prefix}_term_relationships as r
						LEFT JOIN	{$wpdb->prefix}_term_taxonomy as wt
						ON	wt.term_taxonomy_id = r.term_taxonomy_id AND wt.taxonomy='category'
						LEFT JOIN	{$wpdb->prefix}_terms as t
						ON	wt.term_id = t.term_id
						LEFT JOIN {$wpdb->prefix}_terms as t2
						ON	wt.Parent = t2.term_id
						LEFT JOIN	{$wpdb->prefix}_term_taxonomy as wt2
						ON	t2.term_id = wt2.term_taxonomy_id AND wt2.taxonomy='category'
						LEFT JOIN {$wpdb->prefix}_terms as t3
						ON	wt2.Parent = t3.term_id
						LEFT JOIN	{$wpdb->prefix}_term_taxonomy as wt3
						ON	t3.term_id = wt3.term_taxonomy_id AND wt3.taxonomy='category'
						LEFT JOIN {$wpdb->prefix}_terms as t4
						ON	wt3.Parent = t4.term_id
						JOIN {$wpdb->prefix}_posts as wp
						WHERE r.object_id = wp.id AND post_type='post' AND t4.name IS NOT NULL";

			//

			$results	= $wpdb->get_results($sql);
			
			// should only be one row containing the no of posts that exist which have at least 4 categories in parent/child relationship
			foreach($results as $row){			
				$category_depth = $row->depth;
			}

			// cleanup as we go
			unset($results);

			return $category_depth;

		}

		/**
		 * gets the correct permalink structure for the related object
		 *
		 * @param string $type
		 * @return string
		 */
		function get_permastruct($type){
			global $wp_rewrite;

			switch ($type){

				case "page":
					return $wp_rewrite->get_page_permastruct();
					break;
				case "category":
					return $wp_rewrite->get_category_permastruct();
					break;
				case "tag":
					return $wp_rewrite->get_tag_permastruct();
					break;
				case "author":
					return $wp_rewrite->get_author_permastruct();
					break;
				case "archive":
					return $wp_rewrite->get_month_permastruct();
					break;
				
			}			
			
		}

		/**
		 * returns whether the site has been set to add trailing slashes to the end of permalinks
		 *		
		 * @return boolean
		 */
		function use_trailing_slashes(){
			global $wp_rewrite;

			return $wp_rewrite->use_trailing_slashes;
		}

		/**
		 * returns the default category set for the site
		 *
		 * @return string		
		 */
		function get_default_category(){
			global $wp_rewrite;

			return get_category( get_option( 'default_category' ) )->slug;
		}

		/**
		 * executes an sql query
		 *
		 * @param string $sql		
		 */
		function exec_qry($sql){
			global $wpdb;

			// replace placeholder for prefix with correct value now we have access to the wpdb object
			$sql = StrictlyPlugin::format_sql($sql);

			$wpdb->query($sql);
		}

		/**
		 * returns the result of an SQL query that outputs one line of XML
		 *
		 * @param string $sql		
		 */
		function get_xml_result($sql){

			global $wpdb;

			// replace placeholder for prefix with correct value now we have access to the wpdb object
			$sql = StrictlyPlugin::format_sql($sql);
			
			ShowDebug("IN get_xml_result SQL = $sql");

			$results	= $wpdb->get_results($sql);			
			
			// should only be one row containing the XML blob data for all the pages already XML formatted
			foreach($results as $row){
				$data = $row->XMLSitemap . "\n";
			}

			// cleanup as we go
			unset($results);

			return $data;
		}

		/**
		 * returns the result of an SQL query
		 *
		 * @param string $sql	
		 * @return object
		 */
		function get_query_results($sql){

			global $wpdb;

			// replace placeholder for prefix with correct value now we have access to the wpdb object
			$sql = StrictlyPlugin::format_sql($sql);
			
			ShowDebug("IN get_query_results SQL = $sql");

			$results	= $wpdb->get_results($sql);					
			
			return $results;
		}

		/**
		 * Adds to any SQL the correct wordpress table prefix if the correct placeholder has been used
		 *
		 * @param string $sql
		 * @return string
		 */
		function format_sql($sql){

			global $wpdb;

			if(!empty($sql)){

				// replace placeholder for prefix with correct value now we have access to the wpdb object
				$sql = preg_replace("@##WP_PREFIX##@",$wpdb->prefix,$sql);

			}

			return $sql;
		}

		/**
		 * returns the result of an SQL query as an unbuffered query 
		 *
		 * @param object $con
		 * @param string $sql	
		 * @return object
		 */
		function get_unbuffered_query_results($con,$sql){

			// replace placeholder for prefix with correct value now we have access to the wpdb object
			$sql = StrictlyPlugin::format_sql($sql);

			$results = mysql_unbuffered_query($sql,$con);
				
			if(!$results) {
				trigger_error("MySQL unbuffered query failed: " . mysql_error(),E_USER_NOTICE);
				return;
			}				
			
			return $results;
		}

		/**
		 * create a connection to the wordpress DB
		 *		 
		 * @param string $newconn
		 * @return object
		 */
		function db_connect($newconn){

			$con = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD,$newconn);

			if(!$con) {
				trigger_error("MySQL Connection failed: " . mysql_error(),E_USER_NOTICE);
				return;
			}
			if(!mysql_select_db(DB_NAME,$con)) {
				trigger_error("MySQL DB Select failed: " . mysql_error(),E_USER_NOTICE);
				return;
			}

			return $con;
		}

		
				
	}


}


if(!defined('StrictlyControl')){

	/**
	 * This class holds the activate and deactivate methods
	 */
	class StrictlyControl{

		private static $StrictlyGoogleSitemap;

		/**
		 * Called when plugin is activated. Ensures that all the relevant options exist in the database
		 * This is useful when updates to my plugin are released as I can add any new options easily
		 */
		public static function Activate(){
			
			// set up a new key to be used on cron jobs
			$cron_key = substr(md5(time()), 0, 8);

			StrictlyTools::addMissingOptions(array(
				"strictly_google_sitemap_croncode"  => $cron_key,
				"strictly_google_sitemap_uninstall" => false
				));
			
		}

		/**
		 * Called when plugin is deactivated and removes all the settings related to the plugin
		 *
		 */
		public static function Deactivate(){

			$uninstall  = get_option('strictly_google_sitemap_uninstall');


			// if user chose to uninstall on de-activation then remove all the settings related to the Strictly Google Sitemap plugin
			if($uninstall){

				// no need to remove transient options as they get removed automatically
				StrictlyTools::deleteOptions(array(
					"strictly_google_sitemap_croncode",
					"strictly_google_sitemap_settings",
					"strictly_google_sitemap_seo_report",
					"strictly_google_sitemap_seo_index",
					"strictly_google_sitemap_uninstall"
					));
				
			}

			// kill any remaining transients
			delete_transient('strictly_google_sitemap_rebuild');
			delete_transient('strictly_google_sitemap_cron_spawn');

			
		}


		/**
		 * Init is called on every page not just when the plugin is activated and creates an instance of my sitemap object
		 *
		 */
		public static function Init(){
			
			if(!isset(StrictlyControl::$StrictlyGoogleSitemap)){
				// create class and all the good stuff that comes with it
				StrictlyControl::$StrictlyGoogleSitemap = new StrictlyGoogleSitemap(); 
			}

		}


		/**
		 * Called when the Cron.php page is fired
		 *
		 * @param boolean $internal
		 * @param boolean $forcebuild
		 */
		public static function SitemapBuilder($internal=false,$forcebuild=false){
			
			// the sitemap object will have already been created at this point

			// set a flag so I know whether the cronjob was initiated by an internal page load
			StrictlyControl::$StrictlyGoogleSitemap->internal_webcron = $internal;
			
			// set a flag to ensure the normal timestamp and locking checks are skipped to force a rebuild
			StrictlyControl::$StrictlyGoogleSitemap->forcebuild = $forcebuild;
					
			// call the RunCron command to rebuild the sitemap			
			StrictlyControl::$StrictlyGoogleSitemap->RunCron($forcebuild);

		}

		/**
		 * Called when the validate.php page is fired
		 *
		 * @param boolean $internal
		 * @param boolean $forcebuild
		 */
		public static function SitemapValidator($url,$index=false){
			
			// the sitemap object will have already been created at this point

			// run my google validator
			$result = StrictlyControl::$StrictlyGoogleSitemap->ValidateSitemap($url,$index);
			
			return $result;

		}
	}
}


if(!defined('StrictlyTools')){
	
	/**
	 * This class holds a number of functions used by Strictly Google Sitemap which are referenced statically
	 */
	class StrictlyTools{
		
		/**
		 * Logs to a debug file which is useful for viewing issues after the fact
		 *
		 * @param stromg $msg
		 */
		function LogDebug($msg){

			if(is_array($msg)){

				$n = date('r') . ': '. StrictlyTools::FormatData($msg);
				
				file_put_contents(dirname(dirname(dirname(dirname(__FILE__)))) . "/cron_debug.log",$n . "\n", FILE_APPEND);
				//file_put_contents(dirname(dirname(dirname(__FILE__))) . "/cron_debug.log",$n . "\n", FILE_APPEND);
			}else{

				$n = date('r') . ': '. $msg;

				file_put_contents(dirname(dirname(dirname(dirname(__FILE__)))) . "/cron_debug.log",$n . "\n", FILE_APPEND);
				//file_put_contents(dirname(dirname(dirname(__FILE__))) . "/cron_debug.log",$n . "\n", FILE_APPEND);
			}
		}

		/**
		 * Adds the supplied array of options to Wordpress
		 *
		 * @options array
		 */
		function addOptions($options)
		{
			foreach($options as $option => $var){
				add_option($option, $var); 
			}
			return true;
		} 
		 
		/**
		 * Takes the supplied array of options and adds any that are missing into Wordpress
		 * This is used when upgrades to the component are carried out
		 *
		 * @options array
		 * @return bool
		 */
		function addMissingOptions($options)
		{
			$opt = array();
			
			foreach($options as $option => $vars){
			  if(! get_option($option)) $opt[$option] = $vars;
			}

			return count($opt) ? StrictlyTools::addOptions($opt) : true;
		}

		/**
		 * Takes the supplied array of options and removes them from Wordpress
		 *
		 * @options array
		 */		  
		function deleteOptions($options)
		{
			foreach($options as $option){
				delete_option($option);
			}
		}

		
		
		/**
		 * Converts a formatted string ini file size value such as 128M into an integer containing the number of bytes
		 *
		 * @param string $size
		 * @return int
		 */
		function ConvertToBytes($size) {
			
			preg_match("@(\d+(?:\.\d+)?)([KMGTP]?)B?$@i",$size,$match);

			if(!$match){
				return 0;
			}else{
				$size = $match[1];
				$last = $match[2];				
			}

			switch($last) {				
				case 'K':
					return $size * 1024;
					break;				
				case 'M':
					return $size * 1048576;
					break;
				case 'G':
					return $size * 1073741824;
					break;
				default:
					return $size;
			}
		}

		/**
		 * Converts an integer value containing a number of bytes into a formated value e.g 1024 = 1kb
		 *
		 * @param integer $size
		 * @return string
		 */
		function ConvertFromBytes($size){

			$unit=array('B','KB','MB','GB','TB','PB');

			return @round($size/pow(1024,($i=floor(log($size,1024)))),2).$unit[$i];
		}
		
		
		/**
		 * Formats a PHP ini type size e.g 20M into standard format e.g 20MB
		 *
		 * @param integer $size
		 * @return string
		 */
		function FormatSize($size){

			// Ignore my option for no limit to size which is -1
			if($size == "-1") return $size;
			if(!empty($size)){
				if(preg_match("@\d+(?:\.\d+)?[KMGTP]?$@i",$size)){
					return strtoupper($size) . "B";
				}
			}
			return $size;
		}

		/**
		 * Formats datetime for XML if no value is supplied the current datetime is used
		 *
		 * @param datetime $lastmod
		 * @param string $format
		 * @return datetime
		 */
		function FormatLastModDate($lastmod="",$format="ISO"){

			// if no value supplied set to current date time
			if(empty($lastmod)){
				 return date('Y-m-d\TH:i:s\Z',time());
			}else{
				// will either be ISO 2010-06-10 or UK format 10/06/2010 OR 10-06-2010. Defaults to ISO
				list($date,$time) = preg_split("/ /",$lastmod);

				// handle dates 10-02-2010 and 10/02/2010
				list($v1,$v2,$v3) = preg_split('/[-\/]/',$date);

				// ensure we have it right way round
				if(($format=="ISO" && strlen($v3)==4) || $format!="ISO"){
					$year	= $v3;
					$month	= $v2;
					$day	= $v1;
				}else{
					$year	= $v1;
					$month	= $v2;
					$day	= $v3;
				}

				if(isset($time)){
					list($hour,$min,$sec) = preg_split('/:/',$time);
				}else{
					$hour=$min=$sec=0;
				}

				 $lastmod = mktime(intval($hour), intval($min), intval($sec), intval($month), intval($day), intval($year));		 
			}

			$lastmod = date('Y-m-d\TH:i:s\Z',$lastmod);

			return $lastmod;
		}

		/**
		 * Converts a string in the format of Sat, 27 Jun 2009 17:53:15 GMT to 2009-06-27T17:53:15+00:00
		 *
		 * @param string $str
		 * @return date
		 */
		function ConvertFromFileStamp($str){
		
			// remove the weekday and GMT part
			$str = preg_replace("@^\w+, @","",$str);

			$month = null;
			switch(substr($str, 3, 3)){
				case "Jan": $month = "01"; break;
				case "Feb": $month = "02"; break;
				case "Mar": $month = "03"; break;
				case "Apr": $month = "04"; break;
				case "May": $month = "05"; break;
				case "Jun": $month = "06"; break;
				case "Jul": $month = "07"; break;
				case "Aug": $month = "08"; break;
				case "Sep": $month = "09"; break;
				case "Oct": $month = "10"; break;
				case "Nov": $month = "11"; break;
				case "Dec": $month = "12"; break;
			}
			
			$mk = mktime(substr($str, 12, 2), substr($str, 15, 2), substr($str, 18, 2), $month, substr($str, 0, 2), substr($str, 7, 4));

			return date('Y-m-d\TH:i:s\Z',$mk);
		}

		/**
		 * test whether we can run the system command shell_exec
		 *
		 * @return bool
		 *
		 */
		function TestShellExec($func){

			if(!function_exists("shell_exec")){
				return false;
			}

			$i = @shell_exec($func); 

			if(empty($i)){
				return false;
			}else{
				return true;
			}
		}

		/**
		 * Checks the current server load
		 *
		 * @param boolean $win
		 * @return string 
		 *
		 */
		function GetServerLoad($win=false){
	 
			$os = strtoupper(PHP_OS); 
			
			if(substr($os, 0, 3) !== 'WIN'){
				if(file_exists("/proc/loadavg")) {				
					$load	= file_get_contents("/proc/loadavg"); 
					$load	= explode(' ', $load); 				
					return $load[0]; 
				}elseif(function_exists("shell_exec")) { 				
					$load	= @shell_exec("uptime");
					$load	= explode(' ', $load);        
					return $load[count($load)-3]; 
				}else { 
					return false; 
				} 
			// do we try to handle windows servers?
			}elseif($win){ 
				if(class_exists("COM")) { 				
					$wmi		= new COM("WinMgmts:\\\\."); 
					$cpus		= $wmi->InstancesOf("Win32_Processor"); 
					$cpuload	= 0; 
					$i			= 0;   
					// Old PHP
					if(version_compare('4.50.0', PHP_VERSION) == 1) { 
						// PHP 4 					
						while ($cpu = $cpus->Next()) { 
							$cpuload += $cpu->LoadPercentage; 
							$i++; 
						} 
					} else { 
						// PHP 5 					
						foreach($cpus as $cpu) { 
							$cpuload += $cpu->LoadPercentage; 
							$i++; 
						} 
					} 
					$cpuload = round($cpuload / $i, 2); 
					return "$cpuload%"; 
				} else { 
					return false; 
				} 
			} 
		}

		/**
		 * returns the binary path of supplied programs if possible. Taken from WP-O-Matic
		 *
		 * @param string $program
		 * @param string $append
		 * @param string $fallback
		 * @return string
		 */
		function GetBinaryPath($program, $append = '', $fallback = null)
		{ 
			$win = substr(PHP_OS, 0, 3) == 'WIN';
		
			// enforce API
			if (!is_string($program) || '' == $program) {
				return $fallback;
			}

			// available since 4.3.0RC2
			if (defined('PATH_SEPARATOR')) {
				$path_delim = PATH_SEPARATOR;
			} else {
				$path_delim = $win ? ';' : ':';
			}
			// full path given
			if (basename($program) != $program) {
				$path_elements[]	= dirname($program);
				$program			= basename($program);
			} else {
				// Honour safe mode
				if (!ini_get('safe_mode') || !$path = ini_get('safe_mode_exec_dir')) {
					$path = getenv('PATH');
					if (!$path) {
						$path = getenv('Path'); // some OSes are just stupid enough to do this
					}
				}
				$path_elements = explode($path_delim, $path);
			}

			if ($win) {
				$exe_suffixes = getenv('PATHEXT')
									? explode($path_delim, getenv('PATHEXT'))
									: array('.exe','.bat','.cmd','.com');
				// allow passing a command.exe param
				if (strpos($program, '.') !== false) {
					array_unshift($exe_suffixes, '');
				}
				// is_executable() is not available on windows for PHP4
				$pear_is_executable = (function_exists('is_executable')) ? 'is_executable' : 'is_file';
			} else {
				$exe_suffixes		= array('');
				$pear_is_executable = 'is_executable';
			}

			foreach ($exe_suffixes as $suff) {
				foreach ($path_elements as $dir) {
					$file = $dir . DIRECTORY_SEPARATOR . $program . $suff;
					if (@$pear_is_executable($file)) {
						return $file . $append;
					}
				}
			}
			return $fallback;
		}

		/**
		 * Finds a suitable command to run cron commands with to offer the user
		 *
		 * @return string
		 */
		function GetCommand()
		{
			$commands = array(
			  @StrictlyTools::GetBinaryPath('curl'),
			  @StrictlyTools::GetBinaryPath('wget'),
			  @StrictlyTools::GetBinaryPath('lynx', ' -dump'),
			  @StrictlyTools::GetBinaryPath('ftp')
			);
			
			return StrictlyTools::Pick($commands[0], $commands[1], $commands[2], $commands[3], '<em>{wget or similar command here}</em>');
		}

		/**
		 * pick first non null item from supplied list of arguments
		 *
		 * @return string
		 */
		function Pick()
		{
			$argc = func_num_args();
			for ($i = 0; $i < $argc; $i++) {
				$arg = func_get_arg($i);
				if (! is_null($arg)) {
					return $arg;
				}
			}

			return null;    
		}

		/**
		 * Combination of empty and is_set
		 * 
		 * @param object $obj
		 * @return boolen
		 */
		function IsNothing($obj){
			if(isset($obj)){
				if(!empty($obj)){
					return false;
				}
			}
			return true;
		}
	

		/**
		 * Flattens a multi-dimensional array into a string
		 *
		 * @param array
		 * @return string
		 */
		static function FormatData($arr){
		
			if(!is_array($arr)){
				return $arr;
			}

			$i = 1;
			$output = "";
			foreach($arr as $key => $val){
				if(is_array($val)){
					if($i>1){
						$output .= '\n';
					}
					
					$output .= StrictlyTools::FormatData($val);
				}else{
					if($i>1){
						$output .=  '\n';
					}			
					$output .=  $val;			
				}
				$i++;
			}
			$output = preg_replace("/\n$/","",$output);
			
			return $output;
		
		}

		/**
		 * Outputs an HTML select list which selects a single item
		 *
		 * @param string $name
		 * @param string $id
		 * @param array $items
		 * @param string $val
		 */
		function drawlist($name,$id,$items,$val){

			$sel = "<select name=\"" . $name . "\" id=\"" . $id . "\">";

			foreach($items as $opt){
				$sel .= "<option value=\"" . $opt . "\" " . ($val == $opt ? ' selected="selected"' : '') . ">" . $opt . "</option>";
			}

			$sel .= "</select>";

			return $sel;
			
		}

		/**
		 * Replace a blank value with a replacement
		 *
		 * @param string $val
		 * @param string $rep
		 * @return string
		 */
		function RepBlank($val,$rep){
			
			if(StrictlyTools::IsNothing($val)){
				return $rep;
			}else{
				return $val;
			}
		}

		/** return the filename part of a filepath
		 *
		 * @param string $filepath
		 * @return string
		 */
		function GetFilename($filepath){
			$file = "";
			if(!empty($filepath)){
				 $file = preg_replace("@^.+[/\\\\]([^/\\\\]+)$@","$1",$filepath);
			}
			return $file;
		}

		/**
		 * Returns the path to the blog directory - taken from Arne Bracholds Sitemap plugin
		 *		
		 * @return string The full path to the blog directory
		*/
		function GetHomePath() {
			
			$res="";
			//Check if we are in the admin area -> get_home_path() is avaiable
			if(function_exists("get_home_path")) {
				$res = get_home_path();
			} else {
				//get_home_path() is not available, but we can't include the admin
				//libraries because many plugins check for the "check_admin_referer"
				//function to detect if you are on an admin page. So we have to copy
				//the get_home_path function in our own...
				$home = get_option( 'home' );
				if ( $home != '' && $home != get_option( 'siteurl' ) ) {
					$home_path	= parse_url( $home );
					$home_path	= $home_path['path'];
					$root		= str_replace( $_SERVER["PHP_SELF"], '', $_SERVER["SCRIPT_FILENAME"] );
					$home_path	= trailingslashit( $root.$home_path );
				} else {
					$home_path	= ABSPATH;
				}

				$res = $home_path;
			}
			return $res;
		}

		/**
		 * Returns the current version for this plugin by using Wordpress API to extract the meta data
		 *
		 * @return string 
		 */
		function GetVersion() {
			if(!function_exists('get_plugin_data')) {
				if(file_exists(ABSPATH . 'wp-admin/includes/plugin.php')){
					require_once(ABSPATH . 'wp-admin/includes/plugin.php'); //2.3+
				}else if(file_exists(ABSPATH . 'wp-admin/admin-functions.php')){
					require_once(ABSPATH . 'wp-admin/admin-functions.php'); //2.1
				}else{
					return (isset(StrictlyControl::$StrictlyGoogleSitemap)) ? StrictlyControl::$StrictlyGoogleSitemap->version . "." .StrictlyControl::$StrictlyGoogleSitemap->build  : "NA";
				}
			}
			$data = get_plugin_data(__FILE__);
			return $data['Version'];
		}



	}
}

if(!function_exists('ShowDebug')){
	
	/**
	 * function to output debug to page
	 *
	 * @param string $msg
	 */
	function ShowDebug($msg){
		if(DEBUG){
			
			if(!empty($msg)){				
				if(is_array($msg)){
					print_r($msg);
					echo "<br />";
				}else{				
					echo htmlspecialchars($msg) . "<br>";
				}
			}
			
		}
	}
}

