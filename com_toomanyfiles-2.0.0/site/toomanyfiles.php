<?php
/**
 * @version     2.0.0
 * @package     com_toomanyfiles
 * @copyright   Copyright (C) 2014. Tutti i diritti riservati.
 * @license     GNU General Public License versione 2 o successiva; vedi LICENSE.txt
 * @author      Riccardo Zorn <r.zorn@tmg.it> - http://www.fasterjoomla.com
 */

defined('_JEXEC') or die;

// Include dependancies
jimport('joomla.application.component.controller');

// Execute the task.
$controller	= JController::getInstance('Toomanyfiles');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
