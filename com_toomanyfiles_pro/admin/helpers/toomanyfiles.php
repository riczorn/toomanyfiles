<?php
/**
 * TooManyFiles component helper.
 * Main helper: initialize common styles and submenus, check that folders exist (if assigned)
 * 
 * @version SVN: $Id$
 * @package    TooManyFiles
 * @author     Riccardo Zorn {@link http://www.fasterjoomla.com/toomanyfiles}
 * @author     Created on 22-Dec-2011
 * @license    GNU/GPL
 */

defined('_JEXEC') or die;

class TooManyFilesHelper
{
	public static $extension = 'com_toomanyfiles';
	/**
	 * Performs initialization tasks and checks if folders exist (only if it's assigned in the 
	 * options)
	 */
	public static function init() {
		$params = JComponentHelper::getParams( 'com_toomanyfiles' );
		$params = $params->get('params');
		
	}

	public static function addStyles() {
		$document = JFactory::getDocument();
		$document->addStyleSheet("components/com_toomanyfiles/assets/css/toomanyfiles.css");	
	}
	
	public static function addSubmenu($vName)
	{
		//JSubMenuHelper replaced with JHtmlSidebar for J3
		JHtmlSidebar::addEntry(
		JText::_('COM_TOOMANYFILES_INTRO'),
			'index.php?option=com_toomanyfiles&view=toomanyfiles',
			$vName == 'default' || $vName == 'toomanyfiles' || $vName == '');
			
		JHtmlSidebar::addEntry(
		JText::_('COM_TOOMANYFILES_RESOURCES'),
			'index.php?option=com_toomanyfiles&view=resources',
			$vName == 'resources');
	}	
}