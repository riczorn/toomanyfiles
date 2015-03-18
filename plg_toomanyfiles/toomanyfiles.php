<?php
/**
 * System Plugin TooManyFiles organizes, joins and compresses style and script resources for you.
 * 
 * This plugin makes use of two libraries, 
 * - fixHead, which performs the header and footer re-organization
 * - css4Min, which joins and minifies the resources 
 * 
 * @version	$Id: toomanyfiles.php 
 * @package toomanyfiles.fixhead
 *
 * @author Riccardo Zorn support@fasterjoomla.com
 * @copyright (C) 2012 - 2014 http://www.fasterjoomla.com
 * @license GNU/GPL v2 or greater http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
require_once(dirname(__FILE__) . "/lib/fixhead.class.php");

/**
 * This plugin has two events, 
 * onBeforeCompileHead, which is used for copying Joomla Head and emptying it (so nothing will be output)
 * onAfterRender, which finds extra scripts, does all the magic, and updates the body.
 */
class plgSystemTooManyFiles extends JPlugin
{
	/**
	 * Plugin that loads module positions within content
	 */
	var $fixHead; // the class instance which will be used by both events
	
	function onBeforeCompileHead()
	{
		if ($this->isAllowed()) {
			if (!$this->fixHead);
				$this->fixHead = new FixHead($this);
			$this->fixHead->clearDocHead();
		}
	}
	
	/**
	 * This is the last event invoked where I can edit the page.
	 * Since I have removed all scripts from the JDocument Head in the onBeforeCompileHead method,
	 * I will now invoke my custom versions of renderHead and renderFoot (which only manage scripts and styles)
	 * to fill in the blanks.
	 * Insert the footer scripts at the end of the document just before the </body>
	 */
	function onAfterRender() {
		if ($this->isAllowed()) {
			if ($this->fixHead) {
				
	 			$body = JResponse::getBody();
	 			
	 			// Here I have the chance to pick up all leftover resources which never entered the JDocument Headers.
	 			$this->fixHead->moveScripts($body);
	 			
	 			// This loads all scripts and styles in each block (head/foot), joins, compresses and returns the 
	 			// compressed urls
	 			$this->fixHead->fix();
	 			
				$find = array("</title>","</body>");
				$replace = array(
					"</title>\n".$this->fixHead->renderHead(),
					$this->fixHead->renderFoot()."</body>"
				);
	 			
	 			$body = str_replace($find,$replace,$body);
				JResponse::setBody($body);
			}
		}
	}

	/**
	 * We try to determine if it's appropriate for the plugin to modify headers:
	 * Exclude administrator, non-html views, logged in users
	 */
	function isAllowed() {
		if (JPATH_BASE == JPATH_ADMINISTRATOR) {
			// why not optimize for managers and administrators, too?
			// Simple: they have all sorts of relative paths to /administrator which can be handled
			return false;
		}
		$document	= JFactory::getDocument();
		
		if ( $document->getType() != 'html' ) {
			return false;
		}		
		
		$user = JFactory::getUser();
		//if (!$user->guest) return false;
		
		$enabled_users = $this->params->get('enabled_users');
		if ($user->guest) {
			if (strpos($enabled_users,'guests')===false) {return false;}
		} else {
			$groups =$user->getAuthorisedGroups();
			if(in_array('7',$groups)||in_array('8',$groups)) {
				if (strpos($enabled_users,'admin')===false) {return false;}
			}
			if(in_array('2',$groups)) {
				if (strpos($enabled_users,'reg')===false) {return false;}
			}
		}		
		
		// let's check if the current component is in the excluded list (from plugin's params)
		$input = JFactory::getApplication()->input;
	    $option = $input->get('option','');
	    foreach(explode("\n",trim($this->params->get('exclude_components'),"\n \t")) as $excl) {
	    	$excl = trim($excl);
			if (!empty($excl) && $option==$excl) {
					return false;
			}
	    }
	    
	    $Itemid = $input->get('Itemid','');
	    foreach(explode("\n",trim($this->params->get('exclude_pages'),"\n \t")) as $excl) {
	    	$excl = trim($excl);
	    	if (!empty($excl) && $Itemid==$excl) {
	    		return false;
	    	}
	    }

	    // special flag passed by the component toomanyfiles:
	    $noCompress = $input->getInt('nocompress',0);
	    if ($noCompress) {
	    	return false;
	    }
	    return true;
	}
}
