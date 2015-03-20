<?php
/**
 * fixHead is a class that helps sort and compact your css and js declarations.
 * 
 * it is part of the Joomla! system plugin toomanyfiles
 * 
 * @package toomanyfiles.fixhead
 * @author Riccardo Zorn support@fasterjoomla.com
 * @copyright (C) 2012 - 2014 http://www.fasterjoomla.com
 * @license GNU/GPL v2 or greater http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;
class FixHead {
	var $head;
	var $foot;
	var $document;
	var $scriptLibraries;
	var $usecompressed;
	var $mini;
	var $analytics;
	var $isGuest;
	var $baseurl;
	var $debugmode = 0;
	var $params;
	var $removeScriptRegexp;

	/**
	 * The main task of contructor is to load an array of paths for the common supported libraries
	 * 
	 * @param $plugin
	 */
	function FixHead(&$plugin) {
		$this->document = JFactory::getDocument();
		
		$this->params = $plugin->params;
  		$this->mini = '';
  		$this->removeScriptRegexp = array();
  		
  		if ($this->usecompressed = $this->params->get('scripts_usecompressed')) {
			$this->mini = 'mini';
  		}
  		$this->analytics = $this->params->get('analytics');
  		$this->baseurl = JURI::base();//$this->document->getBase(); will return the SEF path as well

  		$this->head = $this->document->getHeadData();
  		$this->foot = array('scripts'=>array(),'script'=>array(),'styleSheets'=>array());
		
  		// conditional for mootools, which should stay untouched if we're logged in
		$user = JFactory::getUser();
		$this->isGuest = ($user->guest);
		
		$jqversion = '1.11.1';
		$jquiversion = '1.11.2';
		$mootoolsver = '1.4.5';
		$modernizrver = '2.8.3';
		
		$plugindir = "/plugins/system/toomanyfiles";
		
		$this->scriptLibraries = array(
		"jquery"=>array('local'=>$plugindir."/js/jquery-$jqversion.js",
						'localmini'=>$plugindir."/js/jquery-$jqversion.min.js",
						'cdn'=>"//ajax.googleapis.com/ajax/libs/jquery/$jqversion/jquery.js",
						'cdnmini'=>"//ajax.googleapis.com/ajax/libs/jquery/$jqversion/jquery.min.js",
						'regexp'=>"\/jquery[0-9\.\-]*(.min)?\.js",
						'fallback'=>"window.jQuery || document.write('<sc'+'ript src=\"{LOCALPATH}\"><\/sc'+'ript>');\n".
								"window.jQuery && jQuery.noConflict();",		
						'extrascript'=>'jQuery(function() {$=jQuery;});$=jQuery;'
			),
		"jquery_ui"=>array(
			'local'=>$plugindir."/js/jquery-ui-$jquiversion.js",
			'localmini'=>$plugindir."/js/jquery-ui-$jquiversion.min.js",
			'cdn'=>"//ajax.googleapis.com/ajax/libs/jqueryui/$jquiversion/jquery-ui.js",
			'cdnmini'=>"//ajax.googleapis.com/ajax/libs/jqueryui/$jquiversion/jquery-ui.min.js",
			'regexp'=>"jquery-ui[0-9\.\-]*(custom)?(\.min)?\.js"
		),
		"mootools_core"=>array(
			'local'=>"/media/system/js/mootools-core-uncompressed.js",
			'localmini'=>"/media/system/js/mootools-core.js",
			'cdn'=>"//ajax.googleapis.com/ajax/libs/mootools/$mootoolsver/mootools.js",
			'cdnmini'=>"//ajax.googleapis.com/ajax/libs/mootools/$mootoolsver/mootools-yui-compressed.js",
			'dependencies'=>"core,mootools_more,jcaption,jtooltip,validate,keepalive",
		),
		"core"=>array(
			'local'=>"/media/system/js/core-uncompressed.js",
			'localmini'=>"/media/system/js/core.js"
		),
		"jcaption"=>array(
			'local'=>"/media/system/js/caption.js",
			'localmini'=>"/media/system/js/caption.js",
			'removeRegex'=>'%window\.addEvent\(\'load\',\s*function\(\)\s*{\s*new\s*JCaption\(\'img.caption\'\);\s*}\);\s*%',
		),
		"jtooltip"=>array(
				'local'=>"/media/system/js/tooltip.js",
				'localmini'=>"/media/system/js/tooltip.js",
				'removeRegex'=>'%window\.addEvent\(\'domready\',\s*function\(\)\s*{\s*\$\$\(\'\.hasTip\'\)[^}]+}\s*}\);\s*var\s*JTooltips[^}]+}\s*\);\s*}\);%',
		),
		"keepalive"=>array(
				'removeRegex'=>'%\s*function keepAlive\(\)[^0]*[0-9]*\);\s*}\)\s*;\s*%',
		),
		"validate"=>array(
			'local'=>"/media/system/js/validate.js",
			'localmini'=>"/media/system/js/validate.js"
		),
		"mootools_more"=>array(
			'local'=>"/media/system/js/mootools-more.js",
			'localmini'=>"/media/system/js/mootools-more-compressed.js"
		),
		"modernizr"=>array(
			'local'=>$plugindir."/js/modernizr-$modernizrver.min.js",
			'localmini'=>$plugindir."/js/modernizr-$modernizrver.min.js"
		),
		"analytics"=>array(
			'extrascript'=>"  
				var _gaq=[['_setAccount','". htmlspecialchars($this->analytics)."'],['_trackPageview']];
				(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
				g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
				s.parentNode.insertBefore(g,s)}(document,'script'));
			  
			  //<!-- record outbound links in Google Analytics 
			  //Usage: <a href='.......' onclick=\"recordOutboundLink('name', 'category', 'what');\" > ... -->
			  
				function recordOutboundLink(link, category, action) {
					_gat._getTrackerByName()._trackEvent(category, action);
					setTimeout('document.location = \"' + link.href + '\"', 500);
				}
			  "		
			)
		);
    }

	

	
	/**
	 * scan all entries in $this->head, and remove the matching ones.
	 * The syntax allows for dependencies (so one master library can remove the children)
	 * and removeRegex which is a regular expression whose match will be deleted.
	 * Regexprs are collected in $this->removeScriptRegexp and processed in fix()
	 */
	function removeLibrary(&$container, $scriptLibrary) {
		if (isset($this->scriptLibraries[$scriptLibrary])) {
			$lib = $this->scriptLibraries[$scriptLibrary];
		
			foreach($container['scripts'] as $libpath=>$val) {
				if  ( 
						(!empty($lib['regexp']) && preg_match("/".$lib['regexp']."/", $libpath))
						|| 
						(empty($lib['regexp']) && 
							((!empty($lib['localmini']) && strpos($libpath,$lib['localmini'])!==false)
							||
							(!empty($lib['local']) && strpos($libpath,$lib['local'])!==false))
						)
				) {
					unset ($container['scripts'][$libpath]);
					
					// this throws away core.js and caption.js mainly.
					if (!empty($lib['dependencies'])) {
						$deps = explode(",",$lib['dependencies']);
						foreach($deps as $dep) {
							$this->removeLibrary($container, $dep);
						}
					}
				}
				if (!empty($lib['removeRegex'])) {
					$this->removeScriptRegexp[$scriptLibrary]=$lib['removeRegex'];
				}
			}
		}
	}
	
	/**
	 * Simple add style library
	 * @param $scriptLibrary
	 * @param $this->head
	 */
			
	function addStylesheets(&$container,$href,$type,$rel,$title=NULL,$media=NULL,$attribs=NULL) {
		$href = trim($href);
		
		$href = $this->fixResourceUrl($href);
		
		if (empty($href)) return;
		
		//$href = $this->fixResourceUrl($href);
		if (strpos($href,'//')===false) {
			if (strpos($href, $this->baseurl)) {
				if (strpos($href,'/')===0) {
					$href = $this->baseurl.$href;
				}
			}
		}
		$type = $type?$type:'text/css';
		$rel = $rel?$rel:'stylesheet';
		
		$options = array('mime'=>$type, 'rel'=>$rel, 'title'=>$title, 'media'=>$media, 'attribs'=>$attribs);
		
		$container['styleSheets'][$href]=$options;
	}	

	/**
	 * Simple add style rule (inline)
	 * 
	 * @param $container (this could be $this->head or $this->foot)
	 * @param $style
	 */
	function addStyle(&$container, $style) {
		if (empty($container['style'])) {
			$container['style'] = array('text/css'=>'');
		}
		$container['style']['text/css'] .= "\n". $style . "\n";
	}	
	
	/**
	 * Simple add script library
	 * 
	 * @param unknown_type $container   / head or foot
	 * @param unknown_type $scripturl
	 * @param unknown_type $atTop		// place at the top of the list
	 * @param unknown_type $fallback	// some code to run just after the script declaration (i.e. jQuery.noConflict() or smart fallback loader
	 * @param unknown_type $defer		// careful: if false it won't be added; but if it's a string 'no' or 'false' it will be added. 
	 * @param unknown_type $async		// same as above.
	 */
	function addScripts(&$container, $scripturl, $atTop=true, $fallback="", $defer=false, $async=false) {
		$scripturl = trim($scripturl);
		if (empty($scripturl)) {
			return;
		}
		
		$options = array('mime'=>'text/javascript', 'defer'=>$defer, 'async'=>$async);
		
		if (!empty($fallback)) {
			$options['fallback'] = $fallback;
		}
		if ($atTop) {
			$container['scripts'] = array_merge( 
				array("$scripturl"=>$options),
					  $container['scripts']		);
		} else {
			$container['scripts'][$scripturl]=$options;
		}
	}
	
	/**
	 * Urls of resources (javascript, css) can be wrong altogether:
	 * Accepted format: 
	 * 	http(s)://example.com/...
	 *  //example.com/...
	 *  /somepath/...
	 * but could also be:
	 *  somepath (relative to what?) => fix by adding basepath;
	 *  /somerootpath when $basepath is a folder down, these could be sending outside the
	 *    webroot => simply add basepath.
	 * @param unknown $scripturl
	 */
	function fixResourceUrl($scripturl) {
		
		if (strpos($scripturl,'//')===false) {
			// it is a local path;
		
			// is this resource relative? i.e. "modules/mod_name/assets/j.js" ?
			// add a slash:
			if (strpos($scripturl,'/')===0) {
				// fine, starts with a slash;
			} else if (strpos($scripturl,'..')===0) {
				// what? relative to what, the url? are we out of our minds?
				// let's guess here, maybe it's the template?
				// we could go guessing but this is too awkward to deal with.
		
			} else {
				// a relative path, just add a slash:
				$scripturl = '/'.$scripturl;
			}
				
				
			if (strpos($scripturl, $this->baseurl)) {
				if (strpos($scripturl,'/')===0) {
					$scripturl = $this->baseurl.$scripturl;
				}
			}
		}
		return $scripturl;
	}
	
	/** 
	 * Simple add  a script snippet

	 * @param $container
	 * @param $script
	 */
	function addScript(&$container, $script) {
		if (empty($container['script'])) {
			$container['script'] = array('text/javascript'=>'');
		}
		
		$container['script']['text/javascript'] .= "\n". $script . "\n";
	}

	/**
	 * Add a Library from out list of common libraries.
	 * 
	 * - if  $atTop  the added/replaced script goes at the top of the $this->head or $this->foot ($container);
	 * - if !$atTop, the added/replaced script goes at the bottom of the $container array;
	 *	@param $container
	 * @param $scriptLibrary
	 * @param $atTop
	 */
	function addLibrary(&$container,$scriptLibrary,$atTop=true) {
		$lib = $this->scriptLibraries[$scriptLibrary];
		$fallback = "";
		if (!empty($lib['fallback'])) {
			$fallback = str_replace('{LOCALPATH}', $lib['local'.$this->mini], $lib['fallback']);
			// in case we want to remove jQuery/reconflict, it's necessary to remove all
			// instances of jQuery.NoConflict(), including the ones we usually add.
			if (($scriptLibrary=='jquery')
					&& ($this->params->get('jquery_reconflict','0')=='1')) {
						$fallback = '';
					}
			
		}	
		if (!empty($lib['cdn'.$this->mini]))
			$this->addScripts($container, $lib['cdn'.$this->mini],$atTop, $fallback);
		else
		if (!empty($lib['local'.$this->mini]))
			$this->addScripts($container, $lib['local'.$this->mini],$atTop);
		if (!empty($lib['extrascript'])) {
			$extrascript = $lib['extrascript'];
			/* $extrascript may contain the jquery anti-noConflict()
			 * script which needs only be added if the jquery_noconflict
			 * option has been set.
			 */
			if (($scriptLibrary=='jquery') 
					&& ($this->params->get('jquery_reconflict','0')=='0')) {
				$extrascript = '';
			}
			// but in jquery's case, we want to add it always:
			if (isset( $lib['local'.$this->mini])) {
				$this->addScript($container,str_replace('{LOCALPATH}', $lib['local'.$this->mini], $extrascript));
			}
		}
	}				
	
	/** 
	 * This is the helper function which will add cdn and sort js files based on the options set, 
	 * the array $scriptLibraries contains the local and cdn, normal and minified filenames of 
	 * a few selected libraries (jquery+ui, mootools+more)
	 * the params and the index of the $scriptLibraries have the same name but options are prefixed with "script_"
	 * @param $scriptLibrary
	 * @param $this->head
	 */
	function fixHeadLibrary($scriptLibrary) {
		if (!empty($this->scriptLibraries[$scriptLibrary])) {
			$lib = $this->scriptLibraries[$scriptLibrary];
			$option = $this->params->get('script_'.$scriptLibrary);
			switch ($option) {
				case '0': break; // leave as is
				case '-1': {
					// remove it from $this->head;
					$this->removeLibrary($this->head, $scriptLibrary);
					break;
				}
				case '1': {
					// enable it: hence remove it and add it at the top of $this->head or $this->foot as appropriate;
					$this->removeLibrary($this->head,	$scriptLibrary);
					$this->addLibrary($this->head,	$scriptLibrary);
					break;
				}
				default: {
					// new in v.1.6: enable it but use the value of option!
					$this->removeLibrary($this->head, $scriptLibrary);
					// there are several cases for option: absolute path, cdn; 
					// relative path is not expected and should not happen.
					
					$this->scriptLibraries[$scriptLibrary]['cdn'.$this->mini]=$option;
					$this->addLibrary($this->head,	$scriptLibrary);
				}
			}
		}
		return $option;
	}
	
	/**
	 * This is the main function, it iterates through the options and invokes 
	 * fixHeadFoot accordingly.
	 * 
	 * Scripts management.  Force loading some scripts and optionally move them from header to the footer
	 * 
	 * This will change the header for guests, managing mootools and jquery libraries, and - based on options - placing
	 * the scripts at the footer instead of the header.
	 */
	public function fix() {
	    // it is important to have the inverse order for creating the top headers in the right order:
	    // , if all libraries are enabled their order will be
	    // jquery, jqueryui, mootoolscore, mootoolsmore

		
	    //if ($this->isGuest || 
	    //		((int)$this->params->get('script_mootools_enabled_logged_in')==0)) { // let's keep mootools for logged in users
	    	    	
	    	$this->fixHeadLibrary('mootools_more');
		    $this->fixHeadLibrary('mootools_core');
		    
		    
// 		    $this->fixHeadLibrary('jcaption');
// 		    $this->fixHeadLibrary('jtooltip');
// 		    $this->fixHeadLibrary('keepalive');
	    //}
	    
	    $this->fixHeadLibrary('jquery_ui');
	    $this->fixHeadLibrary('jquery');
	     
	    if ($this->params->get('iskip')) { // skip iphone bar 
	    	// hide bar on iPhone
			// requires css: body{ min-height:960px; ...
	    	$this->addStyle($this->head, 'body {min-height:960px}');
	    	$this->addScript($this->head, '/mobile/i.test(navigator.userAgent) && !window.location.hash && setTimeout(function () { if (!pageYOffset) window.scrollTo(0, 0); }, 500);');
	    }
	    
	    // let's fix improper urls
	    foreach($this->head['scripts'] as $key=>$value) {
	    	$newkey =  $this->fixResourceUrl($key);
	    	if ($newkey != $key) {
	    		$this->head['scripts'][$newkey] = $value;
	    		unset($this->head['scripts'][$key]);
	    	}	    	
	    }
	    
	    // now let's move the scripts to the footer
		if ($this->params->get('scripts_position')) {
			
			/* move selected scripts to the bottom; we want to exclude:
			 * Modernizr.js
			 * LazyLoad.js
			 * googleAddAdSenseService
			 * twitter widgets which do document.write
			 */
			$excluded_scripts = array(
				'modernizr.js',
				'lazyload.js',
				'googleadservices',
				'widgets.twimg.com'
				);
			
			foreach($this->head['scripts'] as $key=>$value) {
				$valid = true;
				foreach($excluded_scripts as $excluded_script) {
					if (strpos(strtolower($key),$excluded_script)!==false) {
						$valid = false;
						break;
					}
				}
				if ($valid) {
					$this->foot['scripts'][$key]=$value;
					unset($this->head['scripts'][$key]);
				}
			}
			
			/*
			* Mootools has dependencies everywhere.  If we don't have mootools, we must drop the JCaption code 
			* which would otherwise throw an error.
			* 
			* TODO make sure we enable this automatically when there is a request to remove mootools!
			*
			* This is the html code we're looking for:
			*
			* 1
			
			window.addEvent('load', function() {
					new JCaption('img.caption');
					});
				
			* 2
			
			window.addEvent('domready', function() {
                        $$('.hasTip').each(function(el) {
                                var title = el.get('title');
                                if (title) {
                                        var parts = title.split('::', 2);
                                        el.store('tip:title', parts[0]);
                                        el.store('tip:text', parts[1]);
                                }
                        });
                        var JTooltips = new Tips($$('.hasTip'), { maxTitleChars: 50, fixed: false});
                });
			
			* 3

			function keepAlive() {  var myAjax = new Request({method: "get", url: "index.php"}).send();} window.addEvent("domready", function(){ keepAlive.periodical(3600000); });

				Some additional sources:
				http://www.easy4net.com/remove-jcaption-code-joomla-2-5.html
				http://stackoverflow.com/questions/8721950/how-to-turn-off-mootools-in-joomla-1-5-in-backend
				
									
			*/
// 			$removeScripts = array(
// 			1		'%window\.addEvent\(\'load\',\s*function\(\)\s*{\s*new\s*JCaption\(\'img.caption\'\);\s*}\);\s*%',
// 			2		'%window\.addEvent\(\'domready\',\s*function\(\)\s*{\s*\$\$\(\'\.hasTip\'\)[^}]+}\s*}\);\s*var\s*JTooltips[^}]+}\s*\);\s*}\);%',
// 			3		'%\s*function keepAlive\(\)[^0]*[0-9]*\);\s*}\)\s*;\s*%',
// 					'%window\.addEvent\([\'"]load[\'"],\s*function\(%',
// 					'%window\.addEvent\([\'"]domready[\'"],\s*function\(%',
// 					//'/window\.addEvent[a-zA-Z0-9\(\)\{\}\s*=\.\';:,\[\]\$]+/m',
// 			);
// 			$replaceScripts = array(
// 					'',
// 					'',
// 					'',
// 					'jQuery(function(',
// 					'jQuery(function(',					
// 			);
			
// 			foreach($this->removeScriptRegexp as $regexp) {
// 				array_splice($removeScripts, 0, 0, $regexp);
// 				array_splice($replaceScripts,0,0,'');			
// 			}
			$removeScripts = array();
			foreach($this->removeScriptRegexp as $key=>$rege) {
				$removeScripts[]  = $rege ;
			}
			
			if (count($removeScripts)>0) {
				foreach($this->head['script'] as $key=>$script) { 
					$script =preg_replace($removeScripts,'',$script);
					$this->head['script'][$key] = $script;
				}
			}
	    	$this->foot['script'] = $this->head['script'];
	    	
	    	$this->head['script']=array();
	    	
	    	// since we're moving libraries and scripts to the bottom, all inlined scripts will also have to go down.
	    	// this is accomplished in afterRender so we catch modules output too.
			
	    }
	
	    // now load additional scripts according to options:	    
	    if ($this->params->get('modernizr')) {
	    	$this->addLibrary($this->head, 'modernizr', false);
	    }
	    if ($this->params->get('pluginsjs')) {
	    	$this->addLibrary($this->foot, 'pluginsjs', false);
	    }
	    if ($this->params->get('analytics')) {
	    	$this->addLibrary($this->foot, 'analytics', false);
	    }    
	    
	    $this->dump('Start head',$this->head);
	    $this->dump('Start foot',$this->foot);
	    $this->compress();
	    $this->dump('End head',$this->head);
	    $this->dump('End foot',$this->foot);
	}
	
	/**
	 * Clear the Joomla headers from scripts and styles: fixHead will handle their rendering.
	 */
	function clearDocHead() {
		// to remove scripts it's not enough to empty them JDocumentHtml->setHeadData
		// in fact if they are empty then the original value is retained. See html.php: 
		$this->removeScriptRegexp = array();
		$this->document->_scripts=array(); 
		$this->document->_script=array(); 
		$this->document->_styleSheets=array(); 
		$this->document->_style=array();
	}
	 
	/** 
	 * Invoke Css4Min on head and foot to perform inlining, minify and compression of the scripts and css
	 */
	function compress() {
		require_once(dirname(__FILE__)."/css4min/css4min.php");
		$css4Min = new Css4Min();
		$css4Min->base = JURI::base(true);
		$css4Min->siteurl = JURI::base(false);
		$css4Min->processJS=$this->params->get('compress_js');
		$css4Min->processCSS=$this->params->get('compress_css');
		//Pro version
		$css4Min->pro=$this->params->get('use_pro') && file_exists(JPATH_ADMINISTRATOR.'/components/com_toomanyfiles/controllers/toomanyfiles.php');
		if ($css4Min->pro) {
			$css4Min->resource_package=json_decode($this->params->get('resource_package'));
		}
		
		$css4Min->removeComments=$this->params->get('compress_remove_comments') &&
				!JDEBUG; // always leave comments if we're in Joomla debug mode
		if (JDEBUG) {
			//$css4Min->isdebug=true;
		}
		
		$css4Min->isdebug = $this->debugmode;
		foreach (explode("\n",$this->params->get('exclude_files')) as $exclude_file) {
			$exclude_file = trim($exclude_file);
			if ($exclude_file)
				$css4Min->excludeFiles[] = ltrim($exclude_file,"/");
		}
		if ($this->debugmode>3) echo "<h3>Invoke Joomla HEAD with ".count($this->head['styleSheets'])."/".count($this->head['scripts'])."</h3>";
		
		$this->head = $css4Min->joomla( $this->head);
		if ($this->debugmode>3) echo "<h3>Invoked Joomla HEAD with ".count($this->head['styleSheets'])."/".count($this->head['scripts'])."</h3>";
		$this->foot =$css4Min->joomla( $this->foot);	
		if ($this->debugmode>3) echo "<h3>Invoked Joomla FOOT with ".count($this->foot['styleSheets'])."/".count($this->foot['scripts'])."</h3>";
		}
		
	/**
	 * This will parse the final HTML of the page (which we stripped of all scripts and styles in JDocument head)
	 * but could still contain scripts from the template, or inlined by components, modules and plugins.
	 * 
	 * So we'll search for all script tags, and replace them as appropriate while filling in the last details of our 
	 * $this->head and $this->foot.
	 * @param unknown_type $body
	 */
	function moveScripts(&$body) {
		/**
		 * The lookahead negation of the conditional statements
		 * (?!<!\[endif\]) does not prevent matching of the scripts later.
		 * 
		 * Hence all code for scripts and styles has been wrapped in a ? match.
		 * This way I'm sure a script can only be matched once.
		 * 
		 * '/(<script)\b([^><]*)>(.*?)<\/script>/is'
		 * thus becomes:
		 * '/(<!--\s*\[if [^\]]*\]>\s*)?(<script)\b([^><]*)>(.*?)<\/script>(\s*<!\[endif\]\s*-->)?/ism',
		 * 
		 * And inside the code I simply check if there is a first match, in which case 
		 * I know it's a conditional comment and ignore the line.
		 * 
		 * TODO: It may only match single-scripts. If a conditional comment includes
		 * 			more than one line, it won't be matched.
		 */
		$expressions = array(
			// for conditional comments: we don't need to match them any longer
			//'/(<!--\s*\[if [^\]]*\]>)(.*?)<!\[endif\]\s*-->/is',
			// for scripts: this was the original; 
			//	'/(<script)\b([^><]*)>(.*?)<\/script>/is',
			// upgraded to (see comment above):
			'/(<!--\s*\[if [^\]]*\]>\s*)?(<script)\b([^><]*)>(.*?)<\/script>(\s*<!\[endif\]\s*-->)?/ism',
			// for styles, same logic:
			'/(<!--\s*\[if [^\]]*\]>\s*)?(<link)\b([^>]*)\/?>(\s*<!\[endif\]\s*-->)?/ism'
		);
		// this should be invoked before fix() otherwise fix() could miss duplicates.
		$body = preg_replace_callback($expressions, array(&$this,  'moveItemsCallback'), $body);
	}
	
	/** 
	 * Generic Callback function from preg_replace_callback
	 * @see moveScripts
	 * 
	 * This will manage Internet Explorer conditional comments and - when appropriate - invoke 
	 * the other two callbacks. 
	 * Thank you microsoft again, this code would have taken a few minutes if it weren't for 
	 * the awesome conditional comments. Now I'm past 4 hours in debugging.
	 * 
	 * @see moveStylesCallback
	 * @see moveScriptsCallback
	 * 
	 * @param $matches
	 */
	function moveItemsCallback($matches) {
		$match1 = trim($matches[1]);
		$match2 = trim($matches[2]);
		// @TODO it's not inserting the comment?
		$conditionalComment = $this->debugmode>0?"<!-- fixHead IE conditional comment: untouched style/script -->\n":"";

		if (!empty($match1)) {
			return $conditionalComment.$matches[0];
		} // else, let's drop the empty comments match:
		array_splice($matches, 1, 1);
		
		if (strpos(strtolower($match2),'link')) {
			return $this->moveStylesCallback($matches);
		} else if (strpos(strtolower($match2),'script')) {
			return $this->moveScriptsCallback($matches);
		} else  {
			/*
			 *  this code block relates to an older set of regexpr that matched explicitly comments;
			 * since it didn't prevent scripts from being matched twice,
			 * it's been removed.  Left as a fallback to ensure we don't drop any code we don't handle
			 * properly.
			 */
			return $comment.$matches[0];
		} 
	}
	
	/** 
	 * Styles-related Callback function from preg_replace_callback
	 * @see moveItemsCallback
	 */
	function moveStylesCallback($matches){
		$esclusion_styles = array(
			'gzip.php' // yoothemes' warp 6 styles compressor, it joins all the templates' files into one package and also
						// inlines resources with base 64 encoding (which we don't) because they officially don't support IE6
		);
		$attrs = array('href'=>'','type'=>'','title'=>'','rel'=>'','media'=>'');
		$attr_matches = array();
	    preg_match_all('/(href|type|title|rel|media)=[\'"](.*?)[\'"]/i',$matches[2],$attr_matches);
	    foreach($attr_matches[1] as $key=>$attr_name) {
	    	$attrs[$attr_name]=$attr_matches[2][$key];
	    }
	    
	    if (strtolower($attrs['type'])=='style/css' || strtolower($attrs['type'])=='text/css' || strtolower($attrs['rel']) == 'stylesheet') {
			foreach($esclusion_styles as $exclusion) {
				if (strpos($matches[2],$exclusion)!==false) {
						$comment = $this->debugmode>0?"<!-- TooManyFiles.fixHead: I found this script but excluded it (matching rule $exclusion): -->\n":"";
						return $comment.$matches[0];
				}
			}
			$this->addStylesheets($this->head, $attrs['href'], $attrs['type'], $attrs['rel'], $attrs['title'], $attrs['media']);
			return $this->debugmode>0?"<!-- the file ". $attrs['href']. " was removed for compression -->\n":"";
	    } else {
	    	// it's not a style, let's return it and pretend we were never here:
	    	return $matches[0];
	    }
		$comment = $this->debugmode>0?"<!-- TooManyFiles.fixHead: I found this nice script but didn't touch it: -->\n":"";
		return  $comment . $matches[0];
	}
	
	/** 
	 * Main scripts replacement function, will perform another regepx to examine the attributes of the script
	 * and: add to the right header + delete the script (or replace it with a comment)
	 * @param $matches
	 */
	function moveScriptsCallback($matches){
		$attrs = array('src'=>'','type'=>'','defer'=>'','async'=>'');$attr_matches = array();
	    preg_match_all('/(src|type|defer|async)=[\'"](.*?)[\'"]/i',$matches[2],$attr_matches);
	    foreach($attr_matches[1] as $key=>$attr_name) {
	    	$attrs[$attr_name]=$attr_matches[2][$key];
	    }
	    if ($attrs['src']) { 
	    	// this is a linked library: we'll want to exclude only certain scripts from google, 
	    	// but this is achieved by fix later on.
	    	$this->addScripts($this->head,
	    		$attrs['src'],
	    		false,'',
	    		$attrs['defer'],
	    		$attrs['async']);
	    	return $this->debugmode>0?"\n<!-- ".$attrs['src']." moved by fixHead -->":"";
	    } else {
	    	/* it's an inlined script: we want to make sure that document.write() kind of scripts stay where they are.
	    	 * so if the block contains any of these:
	    	 * 
	    	 * GA_googleAddSlot('ca-pub-XXX', 'ad_position_name');
	    	 * GA_googleFetchAds();
	    	 * GA_googleFillSlot('ad_position_name');
	    	 * 
	    	 * it will not be processed.
	    	 */
	    	$type = strtolower($attrs['type']);
	    	if ($type=='text/javascript' || $type =="") {
		    	$script = trim ($matches[3]);
		    	if ($script) {
			    	$exclusions = array(
			    		'GA_googleAddSlot',
				    	'GA_googleFetchAds',
				    	'GA_googleFillSlot',
				    	'document.write',
				    	'GS_googleAddAdSenseService',
				    	'GS_googleEnableAllServices',
				    	'LazyLoad.js',
				    	'TWTR.Widget',
		    			'SobiProUrl',
		    			'SPLiveSite',
			    	
			    	);
			    	foreach ($exclusions as $exclusion) {
			    		$exclusion = trim($exclusion);
			    		if (!empty($exclusion)) {
					    	if (strpos($script,$exclusion)!==false) {
					    		$comment = $this->debugmode>0?"<!-- fixHead notice: the next script contains forbidden word \"$exclusion\" and was not moved -->\n":"";
					    		return $comment.$matches[0];
					    	}
			    		}
			    	}
					
		    		$this->addScript($this->head,$script);
		    		return  $this->debugmode>0?"<!-- inlined script moved by fixHead -->":"";
		    	} else {
		    		$comment = $this->debugmode>0?"<!-- fixHead ERROR: the next script appears to be empty -->\n":"";
		    		return $comment.$matches[0];
		    	}
		    	
		    } else {
		    	// well I wasn't expecting this, let's return it and pretend we didn't get this wrong
		    	$comment = $this->debugmode>0?"<!-- fixHead ERROR: the next script format is not expected -->\n":"";
		    	return $comment.$matches[0];
		    }
	    }
	    return "<!-- fixHead ERROR: unexpected exit -->";
	}
	/**
	 * This function is ABANDONED but left here because it is interesting in case we wanted to change
	 * the body of the document during template or onBeforeCompileHead, this is what we would get:
	 * the component's output, nothing else. No modules!  This is why it was abandoned
	 * 
	 * Notice how
	 * 		$buffer = $this->document->getBuffer();
	 * 		$arr = $buffer['component'][''];
	 * but
	 * 		$this->document->setBuffer($arr,'component');
	 * 
	 * @deprecated
	 */
	function moveInlinedScripts() {
	    $buffer = $this->document->getBuffer();
	    $arr = $buffer['component'][''];
	    
		// first of all let's save the scripts
		$matches = array();
		preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $arr, $matches);
		
		$newScript = "";
		
		if (!empty($matches)) {
			foreach($matches[1] as $script) {
				$m++;
				$script = trim($script," \n\t"); // there are already extra \n during import
				if (strlen($script)>0) {
					$i++;
					$newScript .= $script;
				}
			}
		}
		$this->removeComments($newScript);
		
		$this->addScript($this->foot,$newScript);
		// then let's remove them from the source.
	    $arr = preg_replace('/(<script\b[^>]*>.*?<\/script>)/is', "", $arr);
	    	    
	    $buffer['component'][''] = $arr;
	    $this->document->setBuffer($arr,'component');
	}
	
	/**
	 * This is invoked by moveScripts to compress directly.
	 * @param $script  The javascript instructions
	 */
	function removeComments(&$script) {
		if (!$this->params->get('compress_remove_comments')) {
			return;
		} 
		require_once(dirname(__FILE__)."/css4min/css4min.php");
		Minifier::$options['filetype']='js';
		Minifier::removeComments($script);
	}
	
	/**
	 * Debug function, will output data verbosely to the page and - usually - break everything while doing so,
	 * but at least it will give you some insight on what's going on internally.
	 * 
	 * Some comments are included in comments when:
	 * - Joomla is in debug mode
	 * - The plugins' options: remove comments is disabled.
	 * 
	 * @param $title 	// printed in the header
	 * @param $obj
	 */
	function dump($title,&$obj) {
		//echo "<!-- 1toomanyfiles $title ".count($obj['styleSheets'])." styles/".count($obj['scripts'])." scripts -->";
		
		if ($this->debugmode<5) 
			return;
		
		echo "<h4> toomanyfiles $title ".count($obj['styleSheets'])." styles/".count($obj['scripts'])." scripts </h4>";
		
		if (! JDEBUG) { 
			return;
		}
		echo "<h4> toomanyfiles $title ".count($obj['styleSheets'])." styles/".count($obj['scripts'])." scripts </h4>";
		echo "<h3>Scripts</h3>";
			echo "<ol>";
		 
		    foreach ($obj['scripts'] as $key=>$value) {
		    	echo "<li>$key =>  ";
		    	if (!empty($value['fallback']))
		    	if ($value['fallback']) echo "<b>HAS FALLBACK</b>";
		    }
		    echo "</ol>";
		echo "<h3>styleSheets</h3>";
			echo "<ol>";
		 
		    foreach ($obj['styleSheets'] as $key=>$value) {
		    	echo "<li>$key =>  ";
		    	var_dump($value);	    	
		    }
		    echo "</ol>";
		    
		echo "<h3>Script</h3>";
			echo "<ol>";
		 
		    foreach ($obj['script'] as $key=>$value) {
		    	echo "<li>$key => ";
		    	var_dump($value);
		    }
	    echo "</ol>";
	    
	}

	/**
	 * Invoke renderTags
	 */
	function renderHead() {
		return $this->renderTags($this->head, 'render head');
	}
	
	/**
	 * Invoke renderTags
	 */
	function renderFoot() {
		return $this->renderTags($this->foot, 'render foot');
	}
	
	/**
	 * This will do no processing, just output the styles, scripts and fallbacks.
	 * 
	 * Taken from and mostly identical to libraries/joomla/document/html/renderer/head.php : fetchHead(
	 * See inline comments in section 'scripts' near 'fallback' for the one change I made.
	 * */
	function renderTags(&$container, $message) {
		if ($message) {
			$this->dump($message,$this->head);
		}
		$lnEnd = $this->document->_getLineEnd();
		$tab = $this->document->_getTab();
		$tagEnd = ' />';
		$buffer =  "<!-- toomanyfiles $message ".count($container['styleSheets'])." styles/".count($container['scripts'])." scripts -->\n";
		
		
		// Generate stylesheet links
		if (isset($container['styleSheets'] )) {
			foreach ($container['styleSheets'] as $strSrc => $strAttr)
			{
				$buffer .= $tab . '<link rel="stylesheet" href="' . $strSrc . '" type="' . $strAttr['mime'] . '"';
				if (!is_null($strAttr['media']))
				{
					$buffer .= ' media="' . $strAttr['media'] . '" ';
				}
				if ($temp = JArrayHelper::toString($strAttr['attribs']))
				{
					$buffer .= ' ' . $temp;
				}
				$buffer .= $tagEnd . $lnEnd;
			}
		}

		// Generate stylesheet declarations
		if (isset($container['style'] )) {
			foreach ($container['style'] as $type => $content)
			{
				$buffer .= $tab . '<style type="' . $type . '">' . $lnEnd;
	
				// This is for full XHTML support.
				if ($this->document->_mime != 'text/html')
				{
					$buffer .= $tab . $tab . '<![CDATA[' . $lnEnd;
				}
	
				$buffer .= $content . $lnEnd;
	
				// See above note
				if ($this->document->_mime != 'text/html')
				{
					$buffer .= $tab . $tab . ']]>' . $lnEnd;
				}
				$buffer .= $tab . '</style>' . $lnEnd;
			}
		}
		
		// Generate script file links
		if (isset($container['scripts'])) {
			foreach ($container['scripts'] as $strSrc => $strAttr)
			{
				// remove jquery-noconflict.js from scripts
				
				if ($this->params->get('jquery_reconflict')) {
					if (preg_match('/jquery.*noconflict/i',$strSrc)) {
						continue;
					}
				}
				
				$buffer .= $tab . '<script src="' . $strSrc . '"';
				if (!is_null($strAttr['mime']))
				{
					$buffer .= ' type="' . $strAttr['mime'] . '"';
				}
				if ($strAttr['defer'])
				{
					$buffer .= ' defer="defer"';
				}
				if ($strAttr['async'])
				{
					$buffer .= ' async="async"';
				}
				$buffer .= '></script>' . $lnEnd;
				
				/** only change to the original Joomla code.
				 * here we manage the fallback code. 
				 * This will load the jQuery library locally if the CDN failed.
				 */
				if (!empty($strAttr['fallback'])) {
					$buffer .= '<script type="text/javascript">'.$lnEnd.
							$strAttr['fallback'].$lnEnd.
							'</script>'.$lnEnd;
				}
			}
		}
		// Generate script declarations
		if (isset($container['script'])) {
			foreach ($container['script'] as $type => $content)
			{
				$buffer .= $tab . '<script type="' . $type . '">' . $lnEnd;
	
				// This is for full XHTML support.
				if ($this->document->_mime != 'text/html')
				{
					$buffer .= $tab . $tab . '<![CDATA[' . $lnEnd;
				}
	
				$buffer .= $content . $lnEnd;
	
				// See above note
				if ($this->document->_mime != 'text/html')
				{
					$buffer .= $tab . $tab . ']]>' . $lnEnd;
				}
				$buffer .= $tab . '</script>' . $lnEnd;
			}
		}
		
		// Re-Conflict additional test: replace noConflict everywhere:
		if ($this->params->get('jquery_reconflict','0')=='1') {
			$buffer = str_replace('jQuery.noConflict()','1',$buffer);
		}
		
		return $buffer;	
	}
	
	/**
	 * TEst a few functions
	 */
	function test() {
		/**
		 * http(s)://example.com/...
		 *  //example.com/...
		 *  /somepath/...
		 * but could also be:
		 *  somepath (relative to what?) => fix by adding basepath;
		 *  /somerootpath when $basepath is a folder down, these could be sending outside the
		 *    webroot => simply add basepath.
		 */
		$fixResourceUrlTests = array(
				'http://mysite.com/media/test.js?params'=>'/media/test.js',
				'http://example.com/media/test.js'=>'http://example.com/media/test.js',
				'//example.com/media/test.js'=>'//example.com/media/test.js',
				'//example.com/media/test.js?params'=>'//example.com/media/test.js?params',
				'/somepath/media/test.js'=>'/somepath/media/test.js',
				'/somepath/media/test.js?params'=>'/somepath/media/test.js',
				'somepath/media/test.js' => '/somepath/media/test.js'
		);
		foreach ($fixResourceUrlTests as $test=>$result) {
			$res = $this->fixResourceUrl($test);
			printf('<div>Test: %s<br>
					&nbsp;Expected Result: %s<br>
					&nbsp;Actual Result: %s<br>
					&nbsp;Pass: %s</div>',
					$test, 
					$result, 
					$res,
					var_export($res==$result,true)
					);
		}
		die('a horrible horrible death.');
	}
}

