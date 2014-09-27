<?php
die();
/**
 * @package     Too Many Files
 * @copyright   Copyright (C) 2013-2014 Riccardo Zorn. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Riccardo Zorn <support@fasterjoomla.com> - http://www.fasterjoomla.com/toomanyfiles
 */

?>

Too Many Files for Joomla Admin
Homepage: http://www.fasterjoomla.com/toomanyfiles

--------------------------------------
Version 1.5.6 (2014/06/02) 
	Last plugin-only version
	
Version 2.0 (2014/06/03)
	Added component, with download feature, and temporary list of files. 

	//@TODO 
	1. Use sort libraries options: jQuery first etc.
	2. Save library list by id in urls;
	3. Default for first library not found $lastResourceOrder
	4. Automatic groups (compare library lists)
	5. Instances browseable
	
	
PRO Version June 2014 (comment of css4min.php)
 * =====================
 * This library optionally allows for complete configuration performed by the component Too Many Files Pro.
 * The configuration consists of two options: use_pro and resource_package.
 * If set, a totally different approach is followed when compressing the files.
 * 
 * Instead of compressing the files that are found on every page, it uses a pre-defined "package" which contains
 * user-picked scripts and libraries. Everytime a library is requested, which is contained in the "package", the
 * full package is served. Everytime an extra library is requested, the default behaviour applies.
 * 
 *   By using the Pro component you select the heavy libraries that - even compressed - take up valuable user time,
 *   which are used commonly throughout the site.
 *   Combining them in a single package saves precious time and resources.	
 