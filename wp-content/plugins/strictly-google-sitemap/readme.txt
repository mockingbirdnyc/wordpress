=== Strictly Google Sitemap ===
Contributors: Strictly Software
Donate link: http://www.strictly-software.com/donate
Plugin Home: http://www.strictly-software.com/plugins/strictly-google-sitemap
Tags: Google, Sitemap, SEO, XML, Peformance, Memory, SiteIndex, CRON
Requires at least: 2.0.2
Tested up to: 3.1.4
Stable tag: 1.1.0

Strictly Google Sitemap is a feature rich Google XML Sitemap plugin designed with performance in mind for large sites.


== Description ==

This plugin was developed specifically with performance in mind as I was experiencing out of memory issues with existing sitemap plugins.
Developed for large wordpress sites that hold tens of thousands of articles, pages, tags and categories not only does this plugin have all 
the features of the existing market leader and more it runs using less memory and a comparatively tiny number of database queries. 

Features

* Content filtering by Posts, Pages, Categories, Tags, Archives, Authors and custom pages
* Set priority and change frequency levels for each content type
* Exclude content by category, page, post and author
* Ping all major search engines. Yahoo Application ID support
* Robots.txt directive
* Memory Management including an automatic management feature
* Built in Google XML Validator
* Server Load Limit to prevent rebuilds during stressful times
* Built in scheduler so that sites that import content are not re-created multiple times whenever articles are imported.
* Homepage SEO analysis report
* SERP index report (although Google/Bing are continiously changing their source code so scrape analysis may not work)
* Configuration, Usage and Setup Reports
* Sitemap Index feature to split sitemaps up into multiple files
* Two efficent build options that push the load from the website to the database



== Installation ==

This section describes how to install the plugin and get it working.

1. Download the plugin.
2. Unzip the strictly-google-sitemap compressed file.
3. Upload the directory strictly-google-sitemap to the /wp-content/plugins directory on your WordPress blog.
4. Activate the plugin through the 'Plugins' menu in WordPress.
5. Use the newly created Admin option within Wordpress titled Strictly Sitemap to set the configuration for the plugin.
6. For best performance follow the advice that the plugin gives regarding categorisation, permalinks and configuration


== Frequently Asked Questions ==

= Which Rebuild Type should I use =

If you rarely add articles to your site then the "Rebuild on Post Save option will suffice. If you reguarly import articles from feeds
then you should use the "Rebuild at scheduled intervals" option and then either setup a CRON or Web CRON job or use the inbuilt scheduler
system to set a rebuild period. If you import articles once every hour then rebuild once an hour.

= Which Build Method should I use =

This depends on the size of your system and the performance of your server and is best found out through trial and error. If you
are not using a Sitemap Index and all your articles are within one sitemap then the "Build and Join XML with SQL" option might be the fastest
as it will not need to split the records up into multiple files. If you are using multiple files or have a large number of records then
the "Build XML with SQL (join with PHP)" option might be better as the records can be split into files during the build process.

= This doesn't work with Multisites =

Yes this is true it doesn't.

= I have put my blog folder in a different place and the output URL's are wrong =

This is a known issue and seems to be related to that on some systems the "blogurl" option is populated and others it is not.
Therefore my system uses the "siteurl" option to determine the output location. Changing references in the main class from get_option('siteurl')
to get_option('blogurl') might fix this but I have not been able to test this.


= I have an error =

If you have any error messages installing the plugin then please try the following to rule out conflicts with other plugins
-Disable all other plugins and then try to re-activate the Strictly Google Sitemap plugin - some caching plugins can cause issues.
-If that worked, re-enable the plugins one by one to find the plugin causing the problem. Decide which plugin you want to use.
-If that didn't work check you have the latest version of the plugin software (from Wordpress) and the latest version of Wordpress installed
-Check you have Javascript and Cookies enabled.
-If you can code turn on the DEBUG constant and debug the code to find the problem otherwise contact me and offer me some money to fix the issue :)
-Please remember that you get what you pay for so you cannot expect 24 hour support for a free product. Please bear that in mind if you decide to email me. A donation button
 is on my site and in the plugin admin page.
-If you must email me and haven't chosen to donate even the smallest amount of money please read this >> http://blog.strictly-software.com/2011/10/naming-and-shaming-of-programming.html
-If you don't want to pay for support then ask a question on the message board and hope someone else fixes the problem. 


= But I need this or that and your plugin doesn't do it =

Sorry but tough luck. I wrote this plugin for my own requirements not anyone else and if you have conflicts with other plugins or require extra work then offer to pay me to do the development
or do the work yourself. This is what Open Source programming should be about. I wrote this plugin as other Sitemap plugins didn't do what I wanted them to and you should follow the same rules.
If you don't like this plugin or require a new feature you must remember that you have already bought a good amount of my time for the princely sum of £0.



== Changelog ==

= 1.0.5 =
* Fixed issue with sites that use custom database table prefixes.
* Added new function to StrictlyPlugin class called format_sql which replaces prefix placeholders with the correct prefix.

= 1.0.6 =
* Fixed issue related to sitemaps located in sub folders. Replaced custom function with a call to plugin_dir_url to get the URL for the plugin.

= 1.0.7 =
* Fixed issue that meant category and tag rewrite values were being returned without a leading / since wordpress 3.1
* Fixed issue so that custom pages were outtputted correctly.
* Fixed issue that was related to Wordpress changing their tag permalink structure from using %tag% to %post_tag% in version 3.1 that was causing urls to be outputted without the %post_tag% part of the URL being replaced with the tag description.
* Changed how the gzip files are written out to try and fix a problem with SERPs not picking up gzipped files.

= 1.0.8 =
* Removed the sponsorship message as per the issue outlined in http://gregsplugins.com/lib/2011/11/26/automattic-bullies/

= 1.0.9 =
* Fixed Page excluded bug in CreateAndBuildSitemap method

= 1.1.0 =
* Fixed issue with ping to BING instead of MSN url correctly