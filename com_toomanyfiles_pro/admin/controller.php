<?php
/**
 * Main controller, will just display the first page; all others are handled by 
 * subcontrollers in the controllers directory. 
 * 
 * @version SVN: $Id$
 * @package    TooManyFiles
 * @author     Riccardo Zorn {@link http://www.fasterjoomla.com/toomanyfiles}
 * @author     Created on 22-Dec-2011
 * @license    GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

/**
 * TooManyFiles Component Controller
 *
 * @package		Joomla.Administrator
 * @subpackage	com_toomanyfiles
 * @since 1.5
 */
class TooManyFilesController extends JControllerLegacy
{

	public function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT.'/helpers/toomanyfiles.php';
	
		// Load the submenu.
		TooManyFilesHelper::init();
		
		parent::display();
	}
}
