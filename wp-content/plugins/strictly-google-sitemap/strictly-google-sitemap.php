<?php

/**
 * Plugin Name: Strictly Google Sitemap 
 * Version: 1.1.0
 * Plugin URI: http://www.strictly-software.com/plugins/strictly-google-sitemap/
 * Description: This plugin will generate a sitemaps.org compatible sitemap of your WordPress blog which is supported by Ask.com, Google, MSN Search and YAHOO. This object has been developed specifically to overcome the numerous performance issues related to other sitemap plugins that regularly cause out of memory issues by reducing the number of database queries that are executed, writing the code so that formatting and URL related functions are only run once and by removing unneccessary nested loops and system functions. The plugin also offers a number of features such as inbuilt XML validation, Site Indexes, SEO Reports and Configuration and performance analysis.
 * Author: Rob Reid
 * Author URI: http://www.strictly-software.com 
 * =======================================================================
 */

/**
 *
 * GPL Licence:
 * ==============================================================================
 * Copyright 2010 Strictly Software
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 */

require_once(dirname(__FILE__) . "/strictly-google-sitemap.class.php");
require_once(dirname(__FILE__) . "/strictly-seo.class.php");
require_once(dirname(__FILE__) . "/strictly-google-sitemap-control.php");

// register my activate hook to setup the plugin
register_activation_hook(__FILE__, 'StrictlyControl::Activate');

// register my deactivate hook to ensure when the plugin is deactivated everything is cleaned up
register_deactivation_hook(__FILE__, 'StrictlyControl::Deactivate');

// init my object
add_action('init', 'StrictlyControl::Init');

