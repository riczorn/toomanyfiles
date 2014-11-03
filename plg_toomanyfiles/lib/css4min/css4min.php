<?php
/**
 * css4Min
 * Combine and compact Javascript and CSS resources
 * 
 * @package toomanyfiles.css4min
 * @author Riccardo Zorn support@fasterjoomla.com
 * @copyright (C) 2012 - 2014 http://www.fasterjoomla.com
 * @license GNU/GPL v2 or greater http://www.gnu.org/licenses/gpl-2.0.html
 *
 * This class has the purpose of reducing the number of requests a webpage makes.
 * This is accomplished by joining (putting together) all css files and js files, 
 * then minifying and compressing.
 * A cache system with pre-compressed gzipped files ensures maximum performance.
 * 
 * Although this class is intended for use with Joomla by the plugin toomanyfiles, 
 * there is a stand-alone test in the test subfolder.
 * plugins/system/toomanyfiles/lib/css4min/test/
 *
 * The headers are set so that the browser cache never expires; every time a new version is cached
 * its URI changes.
 * 
 * PRO Version June 2014
 * =====================
 * This library optionally allows for complete configuration performed by the component 
 * Too Many Files Pro.
 * The configuration consists of two options: use_pro and resource_package.
 * If set, a totally different approach is followed when compressing the files.
 * 
 * Instead of compressing the files that are found on every page, it uses a pre-defined 
 * "package" which contains user-picked scripts and libraries. 
 * Everytime a library is requested, which is contained in the "package", the
 * full package is served. Everytime an extra library is requested, the default behaviour applies.
 * 
 *   By using the Pro component you select the heavy libraries that - even compressed - 
 *   take up valuable user time, which are used commonly throughout the site.
 *   Combining them in a single package saves precious time and resources, not only by 
 *   compressing once and downloading on all pages, but also on not needing to check
 *   their last modified time at every page load (resulting in several fewer reads from disk
 *   and possibly a few ms saved depending on hosting.
 *
 */
defined('_JEXEC') or die();
/*
 * Usage:
 * During page output put all your css or js 
 * - in a Joomla JDocumentHtml:: Head format
 * - in an array, with the full paths relative to the root of the site just
 * as you would output them. 
 * - in a comma separated string of paths.
 *
 * $css4Min = new Css4Min();
 *
 * This sets up some default paths, if they are not correct you may set them:
 * $css4Min->documentroot = "/home/www/public_html";
 * $css4Min->$cachedir = $css4Min->documentroot."/cache";
 *
 * Now you should invoke the method
 * $css4Min(addFiles())
 * with your array.
 *
 * Now you can generate the cache file on the fly, and return the script or style tags:
 * echo $css4Min->render()
 *
 * The returned string should be echoed in your output where the scripts or styles declarations should go.
 * since page caching may be enabled, the url inserted must be that of the uncompressed file, or the optional loader 
 * script which serves the right file depending on the browser.
 *
 * render() will check if a cache file exists or generate a cache file
 * and return the full style or script tags to include.
 * If you wish to include defer async or other attributes, do NOT echo render(), just invoke it and
 * then use $css4Min->getCacheFileURI() OR invoke addFiles with a Joomla Document Header structure with the desired attributes set.
 *
 * If an error occurs, the system rolls back nicely and outputs the complete style or script declarations for
 * all files, plus an error message in a <!--comment--> just before them to inform you of the problem.
 *
 * Create a list of files as it compresses them to make sure we don't add the same file twice: while this may break
 * your layout, it's for sure an indication of an issue.
 * 
 * Log <!-- message always on error, or if isdebug is true.
 *
 * CREDITS
 * 
 * Riccardo Zorn, main idea and development
 * 
 * This work was made possible also by the articles and code of:
 * 
 * The urlrewriter class from the Minify project http://code.google.com/p/minify/ (which is included in a slightly modified form to suit the project)
 * Reinhold Weber' approach http://reinholdweber.com as published on http://www.catswhocode.com/blog/3-ways-to-compress-css-files-using-php
 * CSS and Javascript Combinator 0.5 Copyright 2006 by Niels Leenheer http://rakaz.nl/code/combine
 * Lee Willis's article (for mod_rewrite rules) http://www.leewillis.co.uk/gzip-joomla-tips-faster-website/
 * Mark Nottingham http://www.mnot.net/cache_docs/
 * 
 * The following libraries / scripts / regexprs are available to use through the configuration of the plugin:
 * 
 * Packer (javascript packer) by Dean Edwards http://joliclic.free.fr/php/javascript-packer/en/
 * James Padosley http://james.padolsey.com/javascript/javascript-comment-removal-revisted/
 * RockJock http://razorsharpcode.blogspot.it/2010/02/lightweight-javascript-and-css.html 
 */

//defined('DS') || define('DS',DIRECTORY_SEPARATOR); // make things easier if you're not using joomla!
defined('DS') || define('DS',"/"); // make things easier if you're not using joomla!

require_once("UriRewriter.php");

class Css4Min {
	
	// 	examples	// 	normal							| site home in subfolder
	//  site url:	// 	http://mysite.com				| http://mysite.com/johnny
	//--------------//----------------------------------+-------------------------
	var $cachedir;	// 	cache							| johnny/cache
					//  (will be appended to $wwwroot)
	var $wwwroot;	// 	/home/mysite/public_html		| /home/user1/html
	var $siteroot;	// 	/home/mysite/public_html		| /home/user1/html/johnny
	var $base;		// 	<empty string>					| /johnny
	var $siteurl;	//  http://mysite.com				| http://mysite.com/johnny

	// these are public so you may override them
	var $files;
	var $cachenamecss;
	var $cachenamejs;
	var $message;
	var $buffer; // the output buffer;
	var $isdebug=0;
	var $removeComments=true;
	var $processJS=true;
	var $processCSS=true;
	var $arraykeys = array('scripts','styleSheets');
	var $errorCount;
	var $excludeFiles=array(); // files which should be skipped when inlining
	
	var $pro=false;
	var $resource_package;
	
	
	function Css4Min() {
		$this->message = "";
		$this->siteroot = dirname(dirname(__FILE__));
		while ($this->siteroot && !file_exists($this->siteroot."/configuration.php")) {
			$this->siteroot = dirname($this->siteroot);
		}
		$this->wwwroot = $this->siteroot;
		
		if ( !isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on' ) {
			$this->siteurl = 'http://'.$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT']=='80' ? '' : ':'.$_SERVER['SERVER_PORT']);
		} 
		else {
			$this->siteurl = 'https://'.$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT']=='443' ? '' : ':'.$_SERVER['SERVER_PORT']);
		}
		
		$this->cachedir = "cache".DS."css4min";
		$this->base = "";
		$this->reset();
	}

	protected function reset() {
		unset($this->files);
		$this->buffer = "";
		$this->cachenamecss = NULL;
		$this->cachenamejs = NULL;
		$this->message = "";
		$this->errorCount=0;
	}

	/**
	 * Adds a file to the correct container
	 * 
	 * @param string $file
	 */
	function addFile($file) {
		$isCss = $this->isCss($file);
		
		$arraykey = $this->arraykeys[$isCss];
		$mime = "text/".($isCss?'css':'javascript');
		if ($isCss) {
				$options = array('mime'=>'text/css', 'media'=>NULL, "attribs"=>array());
			} else {
				$options = array('mime'=>'text/javascript', 'defer'=>false, 'async'=>false);
			}
		$this->debug("Adding $file to $arraykey");
		$this->files[$arraykey][$file]=$options;	
	}
	
	/**
	 *
	 * @param an array of filenames, a comma separated list, or a joomla jdocument Head structure 
	 * (see function makeJoomla() for a description).
	 */
	function addFiles(&$files) {
		$this->reset();
		
		// Some initialization is necessary:
		foreach ($this->arraykeys as $arraykey) {
			$this->getCacheName($arraykey);
		}
		Minifier::$options = array(
			'wwwroot'=>$this->wwwroot,
			'removeComments'=>$this->removeComments,
			'processJS'=>$this->processJS
		);
		
		if (empty($files)) {
			$this->addMessage("no params");
			return false;
		}
		else if (is_string($files)) {
			$files = explode(",",$files);
		}
		else if (!is_array ($files)) {
			$this->error("Wrong params type: ".$files);
			return false;
		}
			
		if (count($files)==0) {
			$this->error('No files added');
			unset($this->files);
			return false;
		} else {
			// I received an array, but I don't know if it's a joomla array or a file-only array: 
			// so first of all I'll transform it
			if (array_key_exists('scripts',$files) || array_key_exists('script',$files) || array_key_exists('styleSheets',$files)) {
				// this is already in joomla format
				$this->files = $files;
			} else {
				// I have to create an appropriate joomla file,
				$this->makeJoomlaHead($files); // will fill $this->files
			}
			
			// the array is (now) fine, let's see if the files exist
			foreach ($this->arraykeys as $arraykey)
				if (isset($this->files[$arraykey])) {
					$index = 0;
					foreach ($this->files[$arraykey] as $file=>$options)
					{
						$file = str_replace($this->siteurl,"",$file);
						// remove ?random=11; but only on local file inclusion: not on remote scripts which may need params.
						//error_log('addFiles '.$file);
						if ((($querypos = strpos($file,'?'))>0) // the file has a query string i.e. ?random=42  
							&& ($this->isLocalAndStatic($file)) ) {
							$oldfile = $file;
							$file = substr($file,0,$querypos);
							if ($this->isdebug>1) {error_log('--FIXED addFiles '.$file);}
							// now I need to replace the array key at $index
							$thisArray = $this->files[$arraykey];
							unset($thisArray[$oldfile]);
							$thisArray =  
								array_slice($thisArray, 0, $index, true) +
								array($file => $options) +
								array_slice($thisArray, $index, count($thisArray)-$index, true);
							$this->files[$arraykey] = $thisArray;
						}
						if ($this->isLocalAndStatic($file) ) {
							$fileName = $this->wwwroot.'/'.ltrim($file,'/');
							// let's exclude php-generating css and js scripts
							if ($i = strpos($fileName,'?')>0) {
							//	error_log("debug local file $i: ".$fileName );
								//$fileName = preg_replace("#^(.*?)\??.*$#","a:\1",$fileName);
								$fileName = strstr ($fileName,'?',true);
							//	error_log('    >'.$fileName);
							}
							if (!file_exists($fileName)) {
								$this->error("css4min: File not found ". $fileName);//$this->wwwroot.DS.$file);
								
								if ($this->isdebug>3) {
									echo "<hr><h5 style='font-size:24px;background-color:red;color:black'>".join("<br>",explode("\n",$this->message))."</h5><hr>";
								}
								
								return false;
							}
						}
					$index++; // this is used to rearrange the array.
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Return a joomla head structure from a list of files
	 */
	function makeJoomlaHead($files) {
		/* the structure is:
		 * scripts: $head['scripts'][] = array("path/scriptname.js"=>array("mime"=> "text/javascript"));
		 * styles:  $head['styleSheets'][] = array("path/custom.css"=>array("mime"=> "text/css", "media"=>NULL, "attribs"=> array()));
		 */
		foreach ($files as $file) { // of course we don't expect options here...
			$this->addFile($file);
		}
	}
	/**
	 * Get the last modified date + files names' md5 hash.
	 */
	function getCacheName($arraykey) {
		if ($arraykey=='scripts') {
			$cachefilename = trim($this->cachenamejs);
			$ext = "js";		
		} else {
			$cachefilename = trim($this->cachenamecss);
			$ext = "css";
		} 
		
		if (!empty($cachefilename))
			return $cachefilename;
			
		$lastModified = 0;
		$allPaths = "";
		if (isset($this->files[$arraykey])) {
			foreach ($this->files[$arraykey] as $file=>$options) {
				$file = str_replace($this->siteurl,"",$file);
				$file = preg_replace("/(\\?.*)$/","",$file);
						
				if ($this->isLocalAndStatic($file)) {
					if (!$this->isExcluded($file)) {
						$allPaths .= $file;
						$lastModified = max($lastModified, filemtime($this->wwwroot . DS . $file));
					}
				}
			}
		}
		if ($lastModified==0) {
			return $cachefilename = null;
		} 
		$suffix = $this->removeComments?"":"-full";
		$cachefilename = $lastModified . '-' . md5($allPaths) . "$suffix.$ext";
		if ($arraykey=='scripts') {
			$this->cachenamejs = $cachefilename;
		} else  {
			$this->cachenamecss = $cachefilename;
		} 
		return $cachefilename;
	}

	/**
	 * Return the absolute path of the cache item i.e. /home/www/public_html/cache/css4min
	 */
	function getCacheFilePath($arraykey) {
		return $this->wwwroot . DS . $this->cachedir . DS . $this->getCacheName($arraykey);
	}
	
	/**
	 * Return the URI of the cache item i.e. http://example.com/cache/css4min/1312123123.js
	 */
	function getCacheFileURI($arraykey) {
		return DS . $this->cachedir . DS . $this->getCacheName($arraykey);
	}

	/**
	 * Process the list of files, determine type, and invoke correct sequencer / importer,
	 * then remove from the list of files.
	 *
	 * Some files may be skipped because:
	 * - they contain // which means they are off-site;
	 * - the compression failed
	 *
	 * Those files will be output anyhow since we don't remove the items from the headers unless compression is fine.
	 */
	function render() {
		$this->createCacheFiles();
		return $this->renderTags();
	}
	
	/**
	 * Function isLocalAndStatic: returns true if the file is local i.e. does not contain a protocol or the site url is local.
	 */
	function isLocalAndStatic($file) {
		// be careful here, "//ajax.googleapis.com" is possible so don't check for true
		$isLocal = 
			(
					// not remote for sure:
				(strpos($file,"//")===false) 
			|| 
					// not local file with domain name prepended 
				(!empty($this->siteurl) 
						&& (strpos($file,$this->siteurl)!==false) 
				)
			);
		if ($isLocal) {
			// let's exclude scripts too:
			// scripts must contain php 
			if (strpos($file,'.php')>0) {
				$isLocal = false;
			}
			// or - but we're including this just in case of really really nasty coders 
			// componentpath/something/? invoking an index.php in
			// a subfolder of the component (i.e. cobalt was the one to pass this wonderful test!)
			if (strpos($file, '/?')>0) {
				$isLocal = false;
			}
			// there could be a further exception: where ? is added to a folder... do we really 
			// want to waste precious processing time testing for this?
			if ($querypos = strpos($file, '?')>0) {
				$file = JPATH_BASE . '/' . substr($file, 0, $querypos);
				if (file_exists($file)) {
					// now let's check that it's not a folder
					if (!is_file($file)) {
						// most likely it's a folder.
						$isLocal = false;
					}
				}
			}
		}
		return $isLocal;
	}
	
	/**
	 * Count the local items for a given key
	 * @param $arraykey
	 */
	function countItems($arraykey) {
		$itemCount = 0;
		if (isset($this->files[$arraykey])) {
			foreach ($this->files[$arraykey] as $file=>$options) {
				if ($this->isLocalAndStatic($file))
					$itemCount++;
			}
		}
		return $itemCount;
	}

	/**
	 * Bridge function which receives the JDocumentHTML::getHeadData() format
	 */
	function joomla(&$files) {
		$this->debug('<h3>Joomla!</h3>');
		// verify that $files is correct and add it, or parse it and create a new array in $this->files
		if ($this->addFiles($files)) {
			$this->debug();
			$this->debug("<h4>joomla files (): ".count($files['styleSheets'])." styles/".count($files['scripts'])." scripts </h4>");
			$this->debug("<h4>joomla css4min->files (): ".count($this->files['styleSheets'])." styles/".count($this->files['scripts'])." scripts </h4>");
			$this->createCacheFiles();
			$this->debug("<h5>joomla files (): ".count($files['styleSheets'])." styles/".count($files['scripts'])." scripts </h5>");
			$this->debug("<h5>joomla css4min->files (): ".count($this->files['styleSheets'])." styles/".count($this->files['scripts'])." scripts </h5>");
			
			//$this->debug();
			/* the structure is:
			 * scripts: $head['scripts'][] = array("path/scriptname.js"=>array("mime"=> "text/javascript"));
			 * styles:  $head['styleSheets'][] = array("path/custom.css"=>array("mime"=> "text/css", "media"=>NULL, "attribs"=> array()));
			 */
			return $this->files;
		}
		else 
			return $files;
	}
	
	function isCss($file) {
		return pathinfo($file,PATHINFO_EXTENSION)=='css';
	}
	
	function renderTags() {
		$tags = "";
		foreach ($this->arraykeys as $arraykey) {
			foreach($this->files[$arraykey] as $file=>$options) {
				if ($this->isCss($file)) { 			
					$tags .= sprintf('<link rel="stylesheet" href="'.$file.'"/>'."\n");
				} else {
					$tags .= sprintf('<script type="text/javascript" src="'.$file.'"></script>'."\n");				
				}
			}
		}
		if (($this->isdebug>1) && $this->message) {
			return "<!-- Css4Min messages: ".$this->message." -->\n" . $tags;
		} else 
		return $tags;
	}
	
	/**
	 * Create cache files for the pro package (removing the files therein),
	 * then proceed to creating cache files for the scripts and stylesheets.
	 */
	protected function createCacheFiles() {
		$this->createProPackage();
		if ($this->processJS)
			if ($this->countItems('scripts'))
				$this->createCacheFile('scripts');
		if ($this->processCSS)
			if ($this->countItems('styleSheets'))
				$this->createCacheFile('styleSheets');
	}
	
	/**
	 * remove files that are already in the package
	 * insert the package in place of the first instance
	 *  oPTION: BUT NOT NECESSARY:
	 * compress the package by creating two extra keys: scriptsMain and styleSheetsMain 
	 * where Main = group name, in the future we may support multiple package groups.
	 * No in realtà forse c'è un modo migliore. Staccare la compressione dalla struttura.
	 * Cioè passare i due array alla compressione, senza che la compressione debba preoccuparsi
	 * di cosa si tratta!
	 * 
	 */
	static $package_added=false;
	protected function createProPackage() {
		if ($this->pro) {
			/*
			 * TODO This is the main part missing.
			 */
			$this->packageToGroups();// add the new keys based on the group;
			// @ TODO copy package files to a proper structure and create the cachefiles
			//$this->stripFilesInPackage(); 
		}
	}
	
	protected function packageToGroups() {
		if (!empty($this->resource_package)) {
			foreach($this->resource_package as $group=>$package) {
				$g = new stdClass();
				$g->used = false;
				$g->compressedStyles = ''; // the filename of the compressed file
				$g->compressedScripts = ''; 
				$this->groups[$group] = $g;
			}
		}
	}
	
	/**
	 * Parses the files list and if files are local, 
	 * - remove them from the list 
	 * - compress/minify them.
	 * - add to the list the filename of the compressed version
	 */
	protected function createCacheFile($arraykey) {
		//$this->debug( "<h4>Create cache file ($arraykey): ".count($this->files['styleSheets'])." styles/".count($this->files['scripts'])." scripts </h4>");
		$cachepath = $this->getCacheFilePath($arraykey);
		$cachefile = $this->getCacheFileURI($arraykey);
		$files = $this->files[$arraykey];
		//$this->debug( "createCacheFile $arraykey: $cachepath");
		$dir = dirname($cachepath);
		// initialize the cache folder:
		if (!file_exists($dir)) {
			if (!mkdir($dir, 0755, TRUE)) {
				$this->addMessage('Cannot create cache folder '.$arraykey);
				return false;
			}
			// since the folder wasn't there, let's copy the .htaccess and index.html
			//and copy the .htaccess if it's not there:
			
// 			2014/10/28: admintools writes an incompatible .htaccess that conflicts with ours.
// 				this is a seriuos issue, as our .htaccess contains instructions that 
// 				tell browsers to grab the compressed files, and breaks the 
// 				layout if it's not there;
						
// 			if (!copy(dirname(__FILE__).DS.'htaccess_sample',dirname($cachepath)."/.htaccess")) {
// 					$this->error('Cannot copy '.dirname(__FILE__).DS.'htaccess_sample to '.dirname($cachepath)."/.htaccess");
// 				}
			file_put_contents(dirname($cachepath)."/index.html", "<!doctype html>");
		}
		
		// if the file was already cached, serve it
		if (file_exists($cachepath)) {
			$itemCount = 0;
			foreach($this->files[$arraykey] as $file=>$options) {
				if ($this->isLocalAndStatic($file)) {
					if (!$this->isExcluded($file)) {
						unset($this->files[$arraykey][$file]);
					}
				}
			}
			$this->addFile($cachefile);
		} else {
			// we need to create the file:
			Minifier::URIRewriterInit();
			$this->initBuffer();

			
			foreach($this->files[$arraykey] as $file=>$options) {
				// this is the main processing function
				if ($this->isLocalAndStatic($file)) {
					if (!$this->isExcluded($file)) {
						if ($this->addToBuffer($file)) { // krz here add the media,screen,print keys
							unset($this->files[$arraykey][$file]);
						} 
						else {
							// we try to make it clear there was a serious error:
							$this->error("There was a blocking error processing $file");
							if ($this->isdebug>1) {
								$this->error($this->message);
							} 
						}
					}
				}
			}
			
			// $this->URIRewriterGetImports(); is invoked by saveBuffer!
				
			// will return false also on empty buffer
			if ($this->saveBuffer($arraykey)) {
				$this->addFile($cachefile);
			} 
		}
		//$this->debug( "<h5>//Create cache file: ".count($this->files['styleSheets'])." styles/".count($this->files['scripts'])." scripts </h5>");
		
	}
	/**
	 *  performs initialization
	 */
	function initBuffer() {
		$this->buffer = "";
	
	}
	/**
	 * Loads the file, minifies it, appends it to $buffer and returns
	 * 		true on success and
	 * 		false on failure OR on unappropriate file
	 * Check the $message.
	 * @param $file
	 */
	
	protected function addToBuffer($file) {
		$tmpContent = "";
		$this->addMessage ("inlining file $file");
		$file = str_replace($this->siteurl,"",$file);
		// if the url contains "//" - and it's not the local siteurl because we replaced it in addFiles
		
		if (!$this->isLocalAndStatic($file)) {
			$this->debug ("Css4Min Will not inline remote resource: $file (siteurl:".$this->siteurl.")");
			return false;
		} 
		else if (Minifier::renderResource($tmpContent, $file)) {
			$this->buffer .= $tmpContent;
			return true; 
		} else { 
			// execution should never be here, since we're checking for file existance in addFiles()
			$this->error("addToBuffer Could not open file $file.\n".$tmpContent);
			if ($this->isdebug>3) {
				echo "<h1 style='background-color:red;color:black'>$tmpContent</h1>";
			}
			return false;
		}
	}
	/**
	 * Check the $excludedFiles array to see if this file was excluded in the options: 
	 * beware: Only a part of the filename is sufficient to exclude it.
	 * @param  $file
	 */
	protected function isExcluded($file) {
		foreach($this->excludeFiles as $excl) {
			$excl = trim($excl);
			if (!empty($excl))
				if (strpos($file,$excl)!==false) {
					return true;
				}
		}
		// let's make sure the file is not in the cache already:
		if (strpos($file,$this->cachedir)!==false) {
			return true;
		}
			
		return false;
	}
	
	/**
	 * writes the file to disk
	 * it also compresses the file 
	 */
	protected function saveBuffer($arraykey) {
		$this->debug( "SaveBuffer $arraykey ".count($this->buffer)."");
		$this->buffer =
			join("\n",Minifier::URIRewriterGetImports()) ."\n".
			trim($this->buffer);
		if (empty($this->buffer) || $this->buffer == "\n") {
			return false;
		} else {
			// write the file 
			$cachefile = $this->getCacheFilePath($arraykey);
			if ($f = fopen($cachefile,'wb')) {
				fwrite($f, $this->buffer);
				fclose($f);
				//unset($this->buffer);
				
				// now compress 
				if ($f = fopen($cachefile."gz",'wb')) {
					fwrite($f, gzencode($this->buffer,9));
					fclose($f);
				} else {
					$this->error("Could not compress the file ".$cachefile);
				}
				return true;
			}
		}
		return false;
	}
	/**
	 * Debug function, no effect if isdebug is false
	 * @param $msg
	 */
	function addMessage($msg) {
		$this->message .= $msg . "\n";
	}

	/**
	 * Error, just appends an error to the messages, writes it to standard error, and increments errorCount
	 * @param $msg
	 */
	function error($msg) {
		error_log($msg);
		$this->addMessage('ERROR:'.$msg);
		$this->errorCount++;
	}
	
	function debug($msg='') {
		if (!empty($msg)) {
			//echo "<h5>DEBUG msg $msg</h5>";
			$this->addMessage ( "<span class='smaller red'>$msg</span><br>");
			return;
		}
		if ($this->isdebug<3) {
			return;
		} 
		
		// just output some vars:
		echo "<ul><li>files:";
		echo "<ul class='green'>";
		foreach($this->arraykeys as $arraykey) {	
			if (count($this->files[$arraykey] )==0)  
				echo "<li>no files in $arraykey";
			else 
				foreach ($this->files[$arraykey] as $file=>$options) {
					echo "<li>$arraykey: $file";
				}
		}
		
		echo "</ul>";
		echo "<li>siteroot:".$this->siteroot;
		echo "<ul class='blue'>";
		echo "<li>wwwroot:".$this->wwwroot;
		echo "<li>cachedir:".$this->cachedir;
		echo "</ul><ul class='red'>";
		echo "<li>base:".$this->base;
		echo "<li>cachename css:".$this->cachenamecss;
		echo "<li>cachename js:".$this->cachenamejs;
		echo "</ul>";
		echo "<li>message:".join("<br>",explode("\n",$this->message));
		echo "</ul>";
	}
}


class Minifier {
	public static $options = array();
	
	static function renderResource(&$tmpContent,$fileName) {
		$fileName = preg_replace('/\?.*$/','',$fileName);
		self::$options['filetype'] = pathinfo($fileName,PATHINFO_EXTENSION);
		if (strpos($fileName,'.php?')>0) { 
			//$filename= substr($filename,0,strpos($filename,'?'));
			return false;
		}
		if ($tmpContent = file_get_contents(self::$options['wwwroot'] . DS . ltrim($fileName,DS))) {
			$tmpContent .= "\n";
		} else {
			$errorMessage = "Css4Min Minifier\nERROR: could not open ".self::$options['wwwroot'] . DS . ltrim($fileName,DS);
			// krz : this is a blocking error and it should be output somehow. 
			/*if (self::$options['filetype']=='js')
				$tmpContent=";alert('".$errorMessage."');";
			else 
				$tmpContent = "\n body:first-child {content:'".$errorMessage."'}\n";*/
			$tmpContent = $errorMessage;
			return false;
		}
		
		if (self::isCss()) {
			self::removeCharset($tmpContent);
		}
		
		// remove comments can be done with several methods, all very partial or failing but still so much faster 
		// than using a parser.
		if (self::$options['removeComments']) {
			$tmpContent = self::removeComments($tmpContent);
			 
		} else {
			$tmpContent = "\n/*   Begin inline by Css4Min from $fileName ****/\n".
				$tmpContent . "\n/*   End inline $fileName */ \n";
		}
			
		if (self::isCss()) {
			self::rewriteUrls($tmpContent, $fileName);
		}

		// krz inline base 64 images if img < 10Kb ?
		return true;
	}
	/**
	 * http://razorsharpcode.blogspot.it/2010/02/lightweight-javascript-and-css.html 
	 * by rockjock
	 * 
	 * */
	static function removeComments_rockjock_minify($_src) {
		 // Buffer output
		 ob_start();
		 $_time=microtime(TRUE);
		 $_ptr=0;
		 while ($_ptr<=strlen($_src)) {
		  if ($_src[$_ptr]=='/') {
		   // Let's presume it's a regex pattern
		   $_regex=TRUE;
		   if ($_ptr>0) {
		    // Backtrack and validate
		    $_ofs=$_ptr;
		    while ($_ofs>0) {
		     $_ofs--;
		     // Regex pattern should be preceded by parenthesis, colon or assignment operator
		     if ($_src[$_ofs]=='(' || $_src[$_ofs]==':' || $_src[$_ofs]=='=') {
		       while ($_ptr<=strlen($_src)) {
		       $_str=strstr(substr($_src,$_ptr+1),'/',TRUE);
		       if (!strlen($_str) && $_src[$_ptr-1]!='/' || strpos($_str,"\n")) {
		        // Not a regex pattern
		        $_regex=FALSE;
		        break;
		       }
		       echo '/'.$_str;
		       $_ptr+=strlen($_str)+1;
		       // Continue pattern matching if / is preceded by a \
		       if ($_src[$_ptr-1]!='\\' || $_src[$_ptr-2]=='\\') {
		         echo '/';
		         $_ptr++;
		         break;
		       }
		      }
		      break;
		     }
		     elseif ($_src[$_ofs]!="\t" && $_src[$_ofs]!=' ') {
		      // Not a regex pattern
		      $_regex=FALSE;
		      break;
		     }
		    }
		    if ($_regex && _ofs<1)
		     $_regex=FALSE;
		   }
		   if (!$_regex || $_ptr<1) {
		    if (substr($_src,$_ptr+1,2)=='*@') {
		     // JS conditional block statement
		     $_str=strstr(substr($_src,$_ptr+3),'@*/',TRUE);
		     echo '/*@'.$_str.$_src[$_ptr].'@*/';
		     $_ptr+=strlen($_str)+6;
		    }
		    elseif ($_src[$_ptr+1]=='*') {
		     // Multiline comment
		     $_str=strstr(substr($_src,$_ptr+2),'*/',TRUE);
		     $_ptr+=strlen($_str)+4;
		    }
		    elseif ($_src[$_ptr+1]=='/') {
		     // Multiline comment
		     $_str=strstr(substr($_src,$_ptr+2),"\n",TRUE);
		     $_ptr+=strlen($_str)+2;
		    }
		    else {
		     // Division operator
		     echo $_src[$_ptr];
		     $_ptr++;
		    }
		   }
		   continue;
		  }
		  elseif ($_src[$_ptr]=='\'' || $_src[$_ptr]=='"') {
		   $_match=$_src[$_ptr];
		   // String literal
		   while ($_ptr<=strlen($_src)) {
		    $_str=strstr(substr($_src,$_ptr+1),$_src[$_ptr],TRUE);
		    echo $_match.$_str;
		    $_ptr+=strlen($_str)+1;
		    if ($_src[$_ptr-1]!='\\' || $_src[$_ptr-2]=='\\') {
		     echo $_match;
		     $_ptr++;
		     break;
		    }
		   }
		   continue;
		  }
		  if ($_src[$_ptr]!="\r" && $_src[$_ptr]!="\n" && ($_src[$_ptr]!="\t" && $_src[$_ptr]!=' ' ||
		   preg_match('/[\w\$]/',$_src[$_ptr-1]) && preg_match('/[\w\$]/',$_src[$_ptr+1])))
		    // Ignore whitespaces
		    echo str_replace("\t",' ',$_src[$_ptr]);
		  $_ptr++;
		 }
		 echo '/* Compressed in '.round(microtime(TRUE)-$_time,4).' secs */';
		 $_out=ob_get_contents();
		 ob_end_clean();
		 return $_out;
	} 
	
	/**
	 * Remove comments by 
	 * http://james.padolsey.com/javascript/javascript-comment-removal-revisted/
	 * @param unknown_type $buffer
	 */
	static function removeComments_padolsey(&$buffer) {
		
		$buffer = preg_replace('/\/\/.*?\/?\*.+?(?=\n|\r|$)|\/\*[\s\S]*?\/\/[\s\S]*?\*\//','',$buffer);
		$buffer = preg_replace('/\/\/.+?(?=\n|\r|$)|\/\*[\s\S]+?\*\//','',$buffer);
		return $buffer;
	}
	
	/**
	 * It is indeed quite difficult to strip comments from js.
	 * This is a very limited approach, which deliberately skips some comments
	 * (those that contain "/") = 80% of them,
	 * but still better than nothing. Try with krz: preg_replace_callback
	 */ 
	static function removeComments_safe(&$buffer) {
		// spaces at the beginning of a line
		$buffer = preg_replace('/^[ \t]*/m','',$buffer);
		// multiline doc comments /** */		
		$buffer = preg_replace('!/\*\*.*?\*/!s','',$buffer);
		// multiline standard /* */ 
		$buffer = preg_replace('!/\*[^/]*?\*/!s','',$buffer);
		// CAUTION: I'm still keeping all comments with "/" inside, i.e. all which contain urls
		return $buffer;
		
		/*
		 * The implementation cannot proceed to single-line parsing because I can't get rid of multiline comments properly yet.
		 *  so it turns out I can't remove /* comments yet (it breaks!!!)
		 */
		
		$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
	    		
    	$newBuffer = "";
    	$buffer = str_replace("\n","\r",$buffer);

    	foreach(explode("\r",$buffer) as  $line) {
    		$curline = trim($line," \t");
    		if (empty($curline)) {}
    		else if ( (strpos($curline,'//')===0)) {
    			// if it's 0, it's a comment we wish to strip; 
    			// if it's > 0, the comment starts at mid-line and it's not so easy to remove it (could also be inside a string)
    			
    		} else {
    			// so it's not a comment; let's see if I like the last character of the string:
    			$lastChar = $curline[strlen($curline)-1];
    			//echo "Processing $curline ($lastChar)<br>";
    			/*if ($lastChar==='{')
    			$newBuffer .= $curline."";
    			else
    			$newBuffer .= $curline."\r";*/
    			//krz
    			// this should be strpos(',{};' which could all be followed by another statement;
    			// alas it doesn't work :-( will have to figure out why
    			if (strpos('{',$lastChar)===false) 
    				$newBuffer .= $curline."\r";
    			else
    				$newBuffer .= $curline;
    		}
    	}
    	return $newBuffer;
	}

	/** Yes another options from the plugins settings: use external packer.
	 * @param $buffer
	 */
	static function removeComments_packer(&$buffer) {
		require_once dirname(dirname(__FILE__)) . DS . "packer.php-1.1" . DS . "class.JavaScriptPacker.php";
		$packer = new JavaScriptPacker($buffer, 'Normal', true, false);
		$buffer = $packer->pack();
		return $buffer;
	}
	
	/** Main removeComments:
	 * 
	 * CSS  is handled nicely.
	 * JS is  a bit problematic; there are a bunch of solutions possible, but their effectiveness depends
	 * on the scripts used,   hence we leave it to you to decie.
	 * @param $buffer
	 */
	static function removeComments(&$buffer) {
		$buffer = preg_replace("/[\t ]+/", ' ', $buffer);
			
	    if (self::isCss()) {
			$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
	    	// newline does not mean anything in css, so I can strip it. 
	    	$buffer = str_replace(array("\r\n", "\r", "\n", "\t"), '', $buffer);
	    	return $buffer;
	    }
	    else {
	    	// in scripts, newlines can separate commands if ; are not used consistently.
	    	// so to avoid a parsing nightmare I'll just leave any non-duplicate newlines:
	    	if (!empty(self::$options['processJS'])) {
	    		switch ((int)self::$options['processJS']) {
	    			case 1:
	    				return self::removeComments_safe($buffer);
	    				break;
	    			case 2:
	    				return self::removeComments_packer($buffer);
	    				break;
	    			case 3:
	    				return self::removeComments_rockjock_minify($buffer);
	    				break;
	    			case 4:
	    				return self::removeComments_padolsey($buffer);
	    				break;
	    		}
	    	}
	    }
 	}
 	 
 	/*
 	 * Stylesheets often have a @CHARSET="UTF-8"; header which we do not wish to duplicate; 
 	 * currently, we're just throwing all away. 
 	 * */
 	static function removeCharset(&$buffer) {
 		$buffer = preg_replace('/@CHARSET\\s+.*["\']UTF-8["\']\\s*;/','', $buffer);
 	}
 	
 	/** 
 	 * Wrapper for Css4Min_Minify_CSS_UriRewriter::rewrite
 	 * @param $buffer
 	 * @param $file
 	 */
 	static function rewriteUrls(&$buffer, $file) {
 		$buffer = Css4Min_Minify_CSS_UriRewriter::rewrite($buffer,dirname(self::$options['wwwroot'].DS.$file),self::$options['wwwroot']);
 		if (false) // this will echo debug info from minify_CSS_UriRewriter 
 			echo "<h2>Rewrite results:</h2>".join("<br>",explode("\n",Css4Min_Minify_CSS_UriRewriter::$debugText))."<br>";
 	} 
 	/**
 	 * Wrapper for Css4Min_Minify_CSS_UriRewriter::init
 	 */
 	static function URIRewriterInit() {
 		return Css4Min_Minify_CSS_UriRewriter::init();
 	}	
 	/**
 	 * Wrapper for Css4Min_Minify_CSS_UriRewriter::getImports
 	 */
 	static function URIRewriterGetImports() {
 		return Css4Min_Minify_CSS_UriRewriter::getImports();
 	}
 	/** 
 	 * I'm lazy so I like my functions to be meaningful.
 	 */
	static function isCss() {
		return self::$options['filetype']=='css';
	}
}