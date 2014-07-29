<?php
/**
 * @version     2.0.0
 * @package     com_toomanyfiles
 * @copyright   Copyright (C) 2014. Tutti i diritti riservati.
 * @license     GNU General Public License versione 2 o successiva; vedi LICENSE.txt
 * @author      Riccardo Zorn <r.zorn@tmg.it> - http://www.fasterjoomla.com
 */


// no direct access
defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_toomanyfiles')) 
{
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependancies
jimport('joomla.application.component.controller');

$controller	= JController::getInstance('Toomanyfiles');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
