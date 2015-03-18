<?php
die();
/**
 * @package     Too Many Files plugin
 * @copyright   Copyright (C) 2012-2014 Riccardo Zorn. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Riccardo Zorn <support@tmg.it> - http://www.fasterjoomla.com/too-many-files-optimize-your-site-js-and-css
 */
defined('_JEXEC') or die;
?>

Too Many Files - Plugin for Joomla Admin
Homepage: http://www.fasterjoomla.com/

--------------------------------------
Version 1.1.6 (2013/04/28) alpha 
	ATTENTION: This is Alpha software. We're still implementing some basic functionality so it's quite fair to assume it won't work on your site unless it's a fresh Joomla! 2.5 installation with no extra extensions, and even then it may cause trouble.
	Please contact us if you are interested in the product. Download at your own risk (the worst that can happen though is that you may have to uninstall it).

	Initial public distribution
	Reduce the number of css and js calls in your page, and load scripts after content.
	After minification comes compression, .htaccess automatic generation, and is able to serve a pre-compressed copy of your js and css files to all clients who support it.
	Please visite the extension's page on fasterjoomla.com to learn more and find all reference.
	
Version 1.2.4 (2013-03-18) alpha
	First stable version
	
Version 1.2.5 (2013-05-08) alpha
	Introduced support for @import styles. It should no longer be necessary to exclude any css files from the join/minify process.
	Tested on: Joomla 2.5+ - 3.2+, jomsocial, dj-classifieds, rsforms, chronoforms, jomres, sobi, zoo, yootheme/warp,
	 	rockettheme/gantry, themeforest, foundation, h5bp templates and more, javascript libraries: twitter bootstrap, yui, any jQuery plugin
	
Version 1.2.6
	Support several syntaxes: 
		local files invoked with a param i.e. template/$template/js/somefile.js?ver=11
		remote js/css files served by php and/or invoked with a param i.e. Google maps api
	
Version 1.3.0 (2014-01-15) beta
	Several improvements:
	- Updated the supported libraries versions
	- Disabled when user is logged in (solves most issues with complex )

Version 1.4.0 (2014-02-12) beta
	Added a configuration option to selectively disable pages based on Menu Itemid.
	Added a configuration option to select for which user groups the plugin will be enabled
	
Version 1.4.2 (2014-02-21) beta
	Now mootools library processing exclusion is enforced also for admin users. 
	Debug modes enforced to produce more consistent output. 
	
Version 1.5.0 (2014-02-22) beta
	New: Removing JComments, JTooltip, keepAlive removes both the libraries and the scripts, so you don't get errors.  
	If mootools Core is removed, these will be all removed since they can't work without it.

Version 1.5.5 (2014-03-05) production
	published on the JED

Version 1.5.6 (2014-05-31) production
	Improved support for IE conditional comments.
---------------------------------------	

As of June, 2014 the plugin Too Many Files has been improved to support
a smarter compression with the new Pro component "Too Many Files Pro".
This currently does not affect existing features.

In an ongoing effort to keep things as simple as possible for the user,
the "Too Many Files Pro" scans your site and gathers most of the information 
that is necessary for the configuration, thus making it easier for it to work
with no manual configuration at all, and to ease testing of custom configurations.


Version 1.5.11 (2014-11-11)
	Improved reliability and compatibility with other extensions
	most notably excluded the creation of the custom 
	.htaccess in the css4min cache folder to prevent 
	incompatibilities with Admin Tools by Akeeba.
	
Version 1.6.1 (2014-12-02)
*	Added extra configuration params to specify the exact version and source of libraries
	The configuration is backwards compatible: i.e. your previous settings will continue
	to work; however, once you open the plugin the old configuration options will be changed and
	you'll have to choose an explicit library.  Once you have made your choice, toomanyfiles
	will not automatically change library version when it updates.
	This ensures you can use jQuery 1 or 2 in the exact version you tested for.
*	Deprecation check: removed JError.
*	Added support for improperly formatted <link > tags

NOTE : Joomla 3 compatibility only!!! A future version will restore 2.5 compatibility,
	but in order to speed up the integration with the TooManyFilesPro component faster,
	the next few versions will be 3.x only.

Version 1.6.2 (2015-03-16)
*	Extra configurations params now work in Joomla 3.3 and 3.4
*   Fix relative paths gone wrong i.e. loading a script from a relative path such as 
			modules/assets/something.js
		instead of 
			/modules/assets/something.js
			
Version 1.6.3 (2015-03-18)
*   fix jQuery compatibility issues.
		Just enable the flag "Fix $-jQuery Error" in the plugin Header Management options.
		
		Some scripts rely on the prefix $ being available; 
		however, it is customary to load jQuery invoking noConflict() in Joomla!  
		This option re-assigns $ to jQuery, so further scripts can rely on $ being jQuery.

		