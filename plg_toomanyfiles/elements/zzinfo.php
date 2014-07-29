<?php
/**
 * @package     FasterJoomla Admin components
 *
 * @author Riccardo Zorn support@fasterjoomla.com
 * @copyright (C) 2012 - 2014 http://www.fasterjoomla.com
 * @license GNU/GPL v2 or greater http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

defined('_JEXEC') or die('Error');

jimport('joomla.form.formfield');

class JFormFieldZzinfo extends JFormField
{
	
    protected $type = 'zzinfo';
    protected function getInput() {
    	$title = !empty($this->element['title'])?$this->element['title']:'a tmg component';
		$header = !empty($this->element['header'])?$this->element['header']:'';
		$body = !empty($this->element['body'])?$this->element['body']:'';
		$footer = !empty($this->element['footer'])?$this->element['footer']:'';
		
		$logoURL = !empty($this->element['logo'])?$this->element['logo']:'http://www.fasterjoomla.com/images/extensions/logoj5b.png';
		
		if (strpos($logoURL,"//")===false)
			$logoURL = "/".trim(ltrim($logoURL,"/")," " ); 
		
		$class = !empty($this->element['class'])?$this->element['class']:'';
		
		$title=JText::_($title);
		$header=JText::_($header);
		$body=JText::_($body);
		$footer=JText::_($footer);
    	
    	
		$document = JFactory::getDocument();
		$document->addStyleDeclaration('
			.zzinfobox {border:1px solid gray;
						background:url('.$logoURL.') top left no-repeat #EFEFEF;
    					border-radius:12px;
    					padding:0 10px 10px 128px;
    					min-height:128px;
    					position:relative;
    					margin-bottom:1em;
    					}
    		.zzinfobox > div {
    					font-size:110%
    					}
    		.zzinfobox > h3 {
    					border-bottom:1px solid #ABABAB;
    		}
    		.zzinfobox > div.header {
    					font-weight:bold;
						background:transparent;
    		}
    		.zzinfobox > div.footer {
    					text-align:center;
    					font-size:100%;
    					bottom:10px;
    					position:absolute;
    		}
    		div.current label, div.current span.faux-label {min-width:200px}
    		
		','text/css');
	
		//JPlugin::loadLanguage('com_cleancache', JPATH_ADMINISTRATOR);
  
		return "<br/><div class='zzinfobox $class'>
				<h3>$title</h3>
				<div class='header'>$header</div>
				<div class='body'>$body</div> 
				<div class='footer'>Copyright (c) 2012-2014 <a href='http://www.fasterjoomla.com' target='_blank'>fasterjoomla.com</a> $footer</div> 
			</div>";
	}
}
