<?php

class StrictlySEO{

	public $url;

	public $content;

	public $links = 0;

	public $nofollow = 0;

	public $title;

	public $description;

	public $h1;

	public $xhtmlvalid;

	public $emails;

	public $PR = 0;

	public $yahoo_mentions	= 0;

	public $google_mentions = 0;

	public $bing_mentions = 0;

	public $twitter_mentions = 0;

	public $twitter_mentions_paged = false;

	private $domain;

	public $seo_report = "";

	/**
	 * init class object
	 *	
	 */
	function __construct($url){

		ShowDebug("SET URL = " . $url);

		$this->url = $url;
	}

	/**
	 * run the SEO report
	 *	
	 */
	public function RunReport(){

		ShowDebug("run report for " . $this->url);

		// get the domain
		$this->domain = $this->GetDomain($this->url);

		// scrape
		$html = $this->GetHTTP($this->url);

		ShowDebug("status = " . $html["status"]);

		if($html["status"]=="200"){

			$this->content = $html["body"];

			$this->links = preg_match_all('/<a [^>]+?>/i',$this->content,$patterns); 

			ShowDebug( "there are " .$this->links . " on the page");

			$this->nofollow = preg_match_all('/<a [^>]*rel="nofollow".*?>/i',$this->content,$patterns); 
 
			ShowDebug( "there are " . $this->nofollow . " nofollow links on the page");

			preg_match('@<title>(.+?)</title>@i',$this->content,$patterns); 

			if($patterns){
				$this->title = $patterns[1];
			}
	
			preg_match('@<meta name="description" content="(.+?)"@i',$this->content,$patterns); 

			if($patterns){
				$this->description = $patterns[1];
			}

			preg_match_all('@<h1.*?>(.+?)</h1>@i',$this->content,$patterns); 

			if($patterns){
				$this->h1 = $patterns;
			}


			$this->emails = $this->GetEmails($this->content);
	
			$this->PR = $this->GetPageRank($this->url);

			$this->xhtmlvalid = $this->GetValidXHTML($this->url);

			// get yahoo mentions
			$this->yahoo_mentions = $this->GetYahooMentions();

			// get Bing mentions
			$this->bing_mentions = $this->GetBingMentions();

			// get no of mentions in google
			$this->google_mentions = $this->GetGoogleMentions();

			// get mentions on twitter
			$this->twitter_mentions = $this->GetTwitterMentions();
			
		}
	}

	/**
	 * Look for email addresses that have not been hidden correctly from scrapers
	 *
	 */
	public function GetEmails($content){

		if(!empty($content)){

			$content = html_entity_decode($content);

			preg_match_all('/[\.&a-zA-Z0-9_-]{1,}@[a-zA-Z0-9-_]{1,}\.[a-zA-Z]{2,6}(?:\.[a-zA-Z]{2,6})?/',$content,$patterns); 

			$clean = "";
			
			// remove dupes
			foreach($patterns[0] as $email){
				if(stripos($clean,$email . "<br />")===false){
					$clean .= $email . "<br />";
				}
			}


			// look for weak hidden emails
			preg_match_all('/[\.&a-zA-Z0-9_-]{1,}(?:\s*[\(\[\{]|\s+)at(?:[\}\)\]]\s*|\s+)[a-zA-Z0-9-_]{1,}\.[a-zA-Z]{2,6}(?:\.[a-zA-Z]{2,6})?/i',$content,$patterns); 


			// remove dupes
			foreach($patterns[0] as $email){
				// ensure it has the word "at" in				
				$email = preg_replace("/\W+at\W+/i","@",$email);
				
				// check in clean list				
				if(stripos($clean,$email . "<br />")===false){				
					$clean .= $email . "<br />";
				}
			}

			// weak JS 			
			preg_match_all('/document\.write\([\'"]([\.&a-zA-Z0-9_-]{1,}(?:\s*[\(\[\{]?at[\}\)\]]?\s*|@)[a-zA-Z0-9-_]{1,}\.[a-zA-Z]{2,6}(?:\.[a-zA-Z]{2,6})?)[\'"]\)/i',$content,$patterns); 

		
			// remove dupes
			foreach($patterns[1] as $email){				
				if(stripos($clean,$email . "<br />")===false){
					$clean .= $email . "<br />";
				}
			}

			return $clean;
		}
	}

	/**
	 * Analyse the SEO report data
	 *
	 */
	public function Analyse(){

		$name = StrictlyPlugin::get_bloginfo("name");

		ShowDebug("blog name = " . $name);

		if(empty($this->title)){
			$this->seo_report .= __("<p>Your homepage doesn't have a <strong>TITLE tag</strong> set.</p>","strictlysitemap");
		}else if($this->title == $name){
			$this->seo_report .= __("<p>Your homepage <strong>TITLE tag</strong> should be extended to include more information about the site and the homepage content. Consider adding relevant targeted keywords before your site name e.g <strong>ASP.NET, Web Development - Strictly Software</strong></p>","strictlysitemap");
		}
		
		if(strlen($this->title)>70){
			$this->seo_report .= sprintf(__("<p>Your homepage title tag is too long at %d characters. 65 characters including spaces is the limit although Google supports up to 70 characters.</p>","strictlysitemap"),strlen($this->title));
		}
		
		if(empty($this->description)){
			$this->seo_report .= __("<p>Your homepage doesn't have a <strong>META Description tag</strong> set. This is an important place for you to add extra content for search engines as well as to summarise the content. Some search engines will use this data on their results pages so it should be considered a major content driver to your site.</p>","strictlysitemap");
		}

		ShowDebug("how many h1 = " . count($this->h1));
	
		if(count($this->h1)==0){
			$this->seo_report .= __("<p>Your homepage doesn't contain any <strong>H1 tags</strong>. After your title tag they are the most important tags for conveying your key terms and most important content to search engines. Try repeating the content from your title tag within your headers either exactly or with slightly different forms and inflections. It is very hard to rank top for 1 or 2 word terms but considerably easier for 3 to 5 word terms (long tail). Header tags are a great place to target these long tail terms.</p>","strictlysitemap");
		}else{
			foreach($this->h1[1] as $item){
			
				$item = trim(strip_tags($item));
				 
				ShowDebug("does " . html_entity_decode($item)  . " == " .$name . " OR does " . str_replace(" ","",$item) . " == " . $name);				

				if(strtolower($item) == strtolower($name) || strtolower(str_replace(" ","",$item)) == strtolower(str_replace(" ","",$name))){
					$this->seo_report .= __("<p>One or more of your <strong>H1 tags</strong> is an exact match to your site name. Don't waste valuable SEO space by repeating your sitename as it's likely you will already rank 1st in all major search engines on your site or company name alone. Therefore you should be using header tags to target your key search terms that you wish to be found by.</p>","strictlysitemap");
					break;
				}
			}
		}

		if($this->links == 0){
			$this->seo_report .= __("<p>You have no outbound links on your homepage. This looks like a poor site structure and you need to build up your site so that all content is accessible through links from your homepage. Try to ensure that no part of your publically accessible site is more than 3 links away from your homepage as not only is this good for userbility it makes the crawlers life easier as well.</p>","strictlysitemap");
		}else if($this->links < 5){
			
			$this->seo_report .= sprintf(__("<p>You only have %d links on your homepage. Consider adding links to other relevant and high quality sites. Share links and receive traffic in return.</p>","strictlysitemap"),$this->links);
		}else{
			if($this->nofollow>0){
				$this->seo_report .= sprintf(__("<p>Your homepage contains %d links %d of them are nofollow.</p>","strictlysitemap"),$this->links,$this->nofollow );
			}else{
				$this->seo_report .= sprintf(__("<p>Your homepage contains %d links.</p>","strictlysitemap"),number_format($this->links));
			}
			
				
			if($this->links > 100){

				$this->seo_report .= __("<p>Google used to recommend a maximum of 100 links per page for technical reasons however this limit no longer applies. They do however recommend limiting the number of links to around this number for usability reasons as well as to prevent your site from looking too spammy. Consider making some of your links nofollow especially comments, links to logon pages, help and other non essential content.</p>","strictlysitemap");

			}
		}
		
		
		$this->seo_report .= sprintf(__("<p>Your sites domain %s is mentioned %d times in Yahoo's search engine.</p>","strictlysitemap"),$this->domain,$this->yahoo_mentions);
		
		$this->seo_report .= sprintf(__("<p>Your sites domain %s is mentioned %d times in Google's search index.</p>","strictlysitemap"),$this->domain,$this->google_mentions);

		$this->seo_report .= sprintf(__("<p>Your sites domain %s is mentioned %d times in Bing's search index.</p>","strictlysitemap"),$this->domain,$this->bing_mentions);


		ShowDebug("no of yahoo mentions = " . $this->yahoo_mentions . " intval = " . intval($this->yahoo_mentions));

		ShowDebug("no of google mentions = " . $this->google_mentions . " intval = " . intval($this->google_mentions));

		ShowDebug("no of bing mentions = " . $this->google_mentions . " intval = " . intval($this->bing_mentions));


		if($this->yahoo_mentions < 10 && $this->google_mentions < 10 && $this->bing_mentions < 10){
			$this->seo_report .= __("<p>Your site is hardly mentioned in any of the major search engines which means that you are losing out on lots of potential visitors that would come to your site from other indexed sites. Try to get backlinks from as many high quality sites as possible and always go for relevancy over quantity. The less links on the page that links to you the better and the more important the page is on the linking site the better for example it\'s better to get a link from a sites homepage than a deep page that isn\'t indexed.","strictlysitemap");
		}else{
			if($this->google_mentions < 50){
				$this->seo_report .= __("<p>Your site has very low coverage in Google's index. Ensure that your sitemap is being submitted correctly and that you are not blocking Googlebot from crawling your site. You can imporove your index coverage by increasing the amount of backlinks from other relevant and high page ranked sites.","strictlysitemap");

			}
			
			if($this->yahoo_mentions < 50){
				$this->seo_report .= __("<p>Your site has very low coverage in Yahoo's index. Ensure that your sitemap is being submitted correctly and that you are not blocking YSlurp from crawling your site. You can imporove your index coverage by increasing the amount of backlinks from other relevant Yahoo indexed sites.","strictlysitemap");

			}
			
			if($this->bing_mentions < 50){
				$this->seo_report .= __("<p>Your site has very low coverage in Microsoft's Bing index. Ensure that your sitemap is being submitted correctly and that you are not blocking MSNBot from crawling your site. You can imporove your index coverage by increasing the amount of backlinks from other relevant Bing indexed sites","strictlysitemap");
			}
		}



		ShowDebug("no of twitter mentions " . $this->twitter_mentions);

		// if there is more than one page of results then we say "at least"

		if($this->twitter_mentions_paged && $this->twitter_mentions > 0){
			
			$this->seo_report .= sprintf(__("<p>search.twitter.com contains multiple pages of results that mention your sites domain %s at least %d times.</p>","strictlysitemap"),$this->domain,$this->twitter_mentions);

		}elseif(!$this->twitter_mentions_paged && $this->twitter_mentions >0){

			$this->seo_report .= sprintf(__("<p>search.twitter.com contains a single page of results that mention your sites domain %s %d times. Consider improving your sites exposure in social media by using tools such as the <a href=\"http://www.strictly-software.com/plugins/strictly-tweetbot\">Strictly Tweetbot</a> to automatically post tweets to multiple accounts when new content is added to your site.</p>","strictlysitemap"),$this->domain,$this->twitter_mentions);

		}elseif($this->twitter_mentions == 0){

			$this->seo_report .= sprintf(__("<p>Your sites domain %s is not currently mentioned in Twitter. If you don\'t already have a Twitter account consider creating one as posting details of each blog article along with relevant hash tags is a great way of driving traffic to your site and increasing your sites index coverage. Consider using the <a href=\"http://www.strictly-software.com/plugins/strictly-tweetbot\">Strictly Tweetbot</a> to automatically post tweets to multiple accounts whenever new content is added to your site.</p>","strictlysitemap"),$this->domain,$this->twitter_mentions);
		}

		

		ShowDebug("PR = " . $this->PR);

		if($this->PR >=3){
			$this->seo_report .= sprintf(__("<p>Your homepage has a Google Toolbar Page Rank of %d.</p>","strictlysitemap"),$this->PR);
		}else{
			$this->seo_report .= sprintf(__("<p>Your homepage has a Google Toolbar Page Rank of %d. PR is a good indication of how popular your site is and you can increase this ranking by gaining good quality inbound links from sites with high PR themselves. The fewer links they have on the same page that links to you the higher the score so aquiring links from low PR directory sites with thousands of other outbound links on the same page is not a helpful move.</p>","strictlysitemap"),$this->PR);
		}


		if($this->xhtmlvalid=="Valid"){
			$this->seo_report .= __("<p>Your homepage passes XHTML validation tests.</p>","strictlysitemap");
		}else{
			$this->seo_report .= __("<p>Your homepage does not pass XHTML validation tests.</p><p>Although XHTML validation has no relation to search engine optimisation a well formed page can speed up page loads as browsers have to do less cleaning operations. It also helps accessability tools such as speech browsers that interpret the XHTML for people will sight problems.</p><p>Please note that you can have a well formed XHTML structure that still doesn't validate and many pages that have been minified or optimised for speed by having unneccessary attributes removed will not validate (view the source for www.google.com for an example)</p>","strictlysitemap");
		}

		if(!empty($this->emails)){
			$this->seo_report .= sprintf(__("<p>The following email addresses were easily extracted from your homepage: <strong>" . $this->emails . "</strong></p><p>You should consider various methods of hiding email addresses to prevent spambots from taking them including using images and obfusicated javascript to output the address into the HTML.</p>","strictlysitemap"),$this->emails);
		}	

	}

	/**
	 * return whether the url is XHTML valid
	 *
	 * @param string $url
	 * @return string
	 */
	public function GetValidXHTML($url){

		ShowDebug( "validate XHTML");

		$validxhtml = "";
		$validurl = "http://validator.w3.org/check?verbose=1&uri=" . urlencode($url);

		$html = $this->GetHTTP($validurl);

		if($html["status"]=="200"){

			preg_match("/(<h3 id=\"congrats\">Congratulations<\/h3>\s+<p>)([\s\S]+?)(<\/p>)/i", $html["body"],$match);

			if($match){

				$validxhtml = "Valid";

			}else{
				
				preg_match("/(<td colspan=\"2\" class=\"invalid\">)([^<]+)(<)/i",$html["body"],$match);

				if($match){
					$validxhtml = "Invalid";
				}else{
					$validxhtml = "Unknown";
				}
			}				

		}else{
			$validxhtml = "Unknown";
		}

		return $validxhtml;
	}

	/**
	 * return the number of mentions in Google
	 *
	 * @return integer
	 */
	public function GetGoogleMentions(){

		$mentions = 0;

		$google = "http://www.google.co.uk/search?hl=en&q=%22" . $this->domain . "%22";
		
		ShowDebug("get Google mentions from " . $google);

		$html = $this->GetHTTP($google);
	
		ShowDebug("status = " . $html["status"] );

		if($html["status"] == "200"){
			
			ShowDebug("status 200!");

			$results = $html["body"];
			
			// new format
			preg_match("@resultStats>About (\S+?) results@i",$results,$match);

			//preg_match("/of about <b>(\S+?)<\/b> for /i",$results,$match);

			if(!$match){

				ShowDebug("no match look for something else");

				preg_match("/<\/b> of <b>(\S+?)<\/b> for /i",$results,$match);
			}

			if($match){				
				$mentions = $match[1];

				ShowDebug("MENTIONS = " . $mentions);

				$mentions = intval(str_replace(",","",$mentions));
				
			}else{

				ShowDebug("NO mentions");

				$mentions = 0;
			}

		}	

		ShowDebug("RETURN $mentions");

		return $mentions;
	}

	/**
	 * return the number of times this site/domain is mentioned in Yahoo
	 *
	 * @return integer
	 */
	public function GetYahooMentions(){

		$linkurl = "http://uk.search.yahoo.com/search?p=%22" . urlencode($this->domain) . "%22%20-site:" .urlencode($this->domain);

		ShowDebug("Get Yahoo Mentions from " . $linkurl);

		// get URL back links from YAHOO
		$html = $this->GetHTTP($linkurl);
		
		if($html["status"]=="200"){
			
			ShowDebug("200 status");

			preg_match("/<strong id=\"resultCount\">(\S+?)<\/strong>/i",$html["body"],$match);

			if($match){

				$result = $match[1];

				ShowDebug("raw match is " . $result);

				$result	= intval(preg_replace("@\D+@","",$result));
				
				ShowDebug("RETURN " . $result);

				return $result;
			}
			
		}

		return 0;
	}

	/**
	 * return the number of times this site/domain is mentioned in Bing
	 *
	 * @return integer
	 */
	public function GetBingMentions(){

		$linkurl = "http://www.bing.com/search?q=%22" . urlencode($this->domain) . "%22%20-site:" .urlencode($this->domain);

		ShowDebug("Get Bing Mentions rom " . $linkurl);

		// get URL back links from YAHOO
		$html = $this->GetHTTP($linkurl);
		
		if($html["status"]=="200"){
			
			ShowDebug("200 status");

			preg_match("/<span class=\"sb_count\" id=\"count\">\d+-\d+ of (\S+?) results/i",$html["body"],$match);

			if($match){

				$result = $match[1];

				ShowDebug("raw match is " . $result);

				$result	= intval(preg_replace("@\D+@","",$result));
				
				ShowDebug("RETURN " . $result);

				return $result;
			}
			
		}

		return 0;
	}


	/**
	 * return the number of times this site/domain is mentioned in Twitter
	 *
	 * @return integer
	 */
	public function GetTwitterMentions(){

		$linkurl = "http://search.twitter.com/search?q=" . urlencode($this->domain);

		ShowDebug("Get Twitter Mentions rom " . $linkurl);

		// get URL back links from YAHOO
		$html = $this->GetHTTP($linkurl);
		
		if($html["status"]=="200"){
			
			ShowDebug("200 status");

			$result = preg_match_all("/(<li class=\"result)/i",$html["body"],$match);
			
			ShowDebug("RETURN " . $result);

			// if there is an "older" link then there are more than X 
			if(stripos($html["body"],"class=\"next\">Older</a>")===false){
				// only one page of results
				$this->twitter_mentions_paged = false; 
			}else{
				// more than one page
				$this->twitter_mentions_paged = true;
			}
			

			return $result;			
			
		}

		return 0;
	}

	/**
	 * return the domain part of a URL ignoring sub domains
	 *
	 * @param string $inputURL
	 * @return string
	 */
	protected function GetDomain( $inputURL ) {

		// make sure any protocol is stripped
		$inputURL = preg_replace("/^\S+:\/\//","",$inputURL);

		$arrayURL = explode( '.', $inputURL );
		$i = count( $arrayURL ) - 1;

		if( $i >=2){
			if(strlen( $arrayURL[ $i ] ) == 2 && strlen( $arrayURL[ $i - 1 ] ) <= 3 ) {
			
				if($arrayURL[ $i - 2 ] == "www"){
					$cleanURL = $arrayURL[ $i - 1 ] . '.' . $arrayURL[ $i ];
				}else{
					$cleanURL = $arrayURL[ $i - 2 ] . '.' .$arrayURL[ $i - 1 ] . '.' . $arrayURL[ $i ];
				}
			}elseif($arrayURL[ $i ]=="name" && $arrayURL[ $i - 2 ]!="www"){
				$cleanURL = $arrayURL[ $i - 2 ] . '.' .$arrayURL[ $i - 1 ] . '.' . $arrayURL[ $i ];
			}else{
				$cleanURL = $arrayURL[ $i - 1 ] . '.' . $arrayURL[ $i ];
			}
		} else {

			$cleanURL = $arrayURL[ $i - 1 ] . '.' . $arrayURL[ $i ];

		}

		return $cleanURL;

	}

	/**
	 * Wrapper function for wp_remote_get that uses the best available method for accessing remote content e.g CURL,FSOCK,HTTP
	 * This function just reformats the status and message so it can be accessed easier and quicker
	 *
	 * @param string $url
	 * @return array
	 */
	protected function GetHTTP($url){
	
		if(method_exists('StrictlyPlugin','wp_remote_get')){
			$http = StrictlyPlugin::wp_remote_get($url);
		}else{
			$http = array();		// add backup method? CURL/FOPEN/FSOCK ?
		}

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
	 * convert a string to a 32-bit integer
	 *
	 * @param string $str
	 * @param float $check
	 * @param int $magic
	 * @return float
	 */
	protected function StrToNum($str, $check, $magic) {
		$Int32Unit = 4294967296;  // 2^32

		$length = strlen($str);
		for ($i = 0; $i < $length; $i++) {
			$check *= $magic; 	
			// If the float is beyond the boundaries of integer (usually +/- 2.15e+9 = 2^31), 
			// the result of converting to integer is undefined
			// refer to http://www.php.net/manual/en/language.types.integer.php
			if ($check >= $Int32Unit) {
				$check = ($check - $Int32Unit * (int) ($check / $Int32Unit));
				// if the check less than -2^31
				$check = ($check < -2147483648) ? ($check + $Int32Unit) : $check;
			}
			$check += ord($str{$i}); 
		}
		return $check;
	}

	/**
	 * generate a hash for a url
	 *
	 * @param string $input	
	 * @return string
	 */
	protected function HashURL($input) {
		$Check1 = $this->StrToNum($input, 0x1505, 0x21);
		$Check2 = $this->StrToNum($input, 0, 0x1003F);

		$Check1 >>= 2; 	
		$Check1 = (($Check1 >> 4) & 0x3FFFFC0 ) | ($Check1 & 0x3F);
		$Check1 = (($Check1 >> 4) & 0x3FFC00 ) | ($Check1 & 0x3FF);
		$Check1 = (($Check1 >> 4) & 0x3C000 ) | ($Check1 & 0x3FFF);	
		
		$T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) <<2 ) | ($Check2 & 0xF0F );
		$T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000 );
		
		return ($T1 | $T2);
	}

	
	/**
	 * generate a checksum for the hash string
	 *
	 * @param string $Hashnum	
	 * @return string
	 */
	protected function CheckHash($Hashnum) {
		$CheckByte	= 0;
		$Flag		= 0;

		$HashStr	= sprintf('%u', $Hashnum) ;
		$length		= strlen($HashStr);
		
		for ($i = $length - 1;  $i >= 0;  $i --) {
			$Re = $HashStr{$i};
			if (1 === ($Flag % 2)) {              
				$Re += $Re;     
				$Re = (int)($Re / 10) + ($Re % 10);
			}
			$CheckByte += $Re;
			$Flag ++;	
		}

		$CheckByte %= 10;
		if (0 !== $CheckByte) {
			$CheckByte = 10 - $CheckByte;
			if (1 === ($Flag % 2) ) {
				if (1 === ($CheckByte % 2)) {
					$CheckByte += 9;
				}
				$CheckByte >>= 1;
			}
		}

		return '7'.$CheckByte.$HashStr;
	}

	
	/**
	 * return the pagerank checksum hash
	 *
	 * @param string $url	
	 * @return string
	 */
	protected function getch($url) { 
		return $this->CheckHash($this->HashURL($url)); 
	}


	/**
	 * return the pagerank for a url
	 *
	 * @param string $url	
	 * @return int
	 */
	public function GetPageRank($url){

		ShowDebug("get PR from $url");

		$pagerank	= -1;		
		$ch			= $this->getch($url);		
		$host		= "toolbarqueries.google.com";

		// try toolbar queries first
		$fp = fsockopen($host, 80, $errno, $errstr, 30);

		// if no luck go for the main google site
		if(!$fp){

			ShowDebug("Toolbar failed goto Google");

			$host= "www.google.com";
			$fp	 = fsockopen($host, 80, $errno, $errstr, 30);
		}
		if($fp){
			
			$useragent =  (empty($_SERVER["HTTP_USER_AGENT"])) ? 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.1.7) Gecko/20091221 Firefox/3.5.7 (.NET CLR 3.5.30729)' : $_SERVER["HTTP_USER_AGENT"];

			$out = "GET /search?client=navclient-auto&ch=" . $ch . "&features=Rank&q=info:" . $url . " HTTP/1.1\r\n";
			$out .= "User-Agent: $useragent\r\n";
			$out .= "Host: $host\r\n";
			$out .= "Connection: Close\r\n\r\n";
			

			fwrite($fp, $out);
			while (!feof($fp)){
				$data = fgets($fp, 128);	
				
				ShowDebug("response is $data");

				$pos = strpos($data, "Rank_");
				if($pos === false){} else{
					$pr			= substr($data, $pos + 9);
					$pr			= trim($pr);
					$pr			= str_replace("\n",'',$pr);
					$pagerank	= $pr;
					break;
				}
			}
			fclose($fp);			
		}

		ShowDebug("return $pagerank");

		return $pagerank;
	}

	
}