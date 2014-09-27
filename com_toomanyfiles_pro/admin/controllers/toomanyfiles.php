<?php
/**
 * @version     2.0.0
 * @package     com_toomanyfiles
 * @copyright   Copyright (C) 2014. Tutti i diritti riservati.
 * @license     GNU General Public License versione 2 o successiva; vedi LICENSE.txt
 * @author      Riccardo Zorn <r.zorn@tmg.it> - http://www.fasterjoomla.com
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * Resources list controller class.
 */
class ToomanyfilesControllerToomanyfiles extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'url', $prefix = 'ToomanyfilesModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		return $model;
	}
    

	public function downloadHome($cachable = false, $urlparams = false) {
		$model	= $this->getModel('toomanyfiles');
		$message = $model->downloadHome();
		// Preset the redirect
		$this->setRedirect(JRoute::_('index.php?option=com_toomanyfiles&view=toomanyfiles', false),$message);
	}

	public function clearFiles($cachable = false, $urlparams = false) {
		$model	= $this->getModel('toomanyfiles');
		$model->clearFiles();
		$message = "Files and url database cleared";
		$this->setRedirect(JRoute::_('index.php?option=com_toomanyfiles&view=toomanyfiles', false),$message);
	}
	
	public function occurrences($cachable = false, $urlparams = false) {
		error_log('ccurrences');
		$application = JFactory::getApplication();
		$input = $application->input;
		$input->set('tmpl','component');
		$id = $input->get('id');
		echo "Occurrences of $id";
		exit;
	}
	
	
	public function analyse($cachable = false, $urlparams = false) {
		$input = JFactory::getApplication()->input;
		$pks = $input->get('cid', array(), 'array');
		JArrayHelper::toInteger($pks);
		
		$model = $this->getModel('toomanyfiles');
		$message = $model->analyse($pks);
		
		
		$this->setRedirect(JRoute::_('index.php?option=com_toomanyfiles&view=toomanyfiles', false),$message);
	}
	
	public function download($cachable = false, $urlparams = false) {
		$input = JFactory::getApplication()->input;
		$pks = $input->get('cid', array(), 'array');
		JArrayHelper::toInteger($pks);
		//error_log('downloading');
		$model = $this->getModel('toomanyfiles');
	
		$message = $model->download($pks);
		//error_log('downloading 2' . $model);

		$this->setRedirect(JRoute::_('index.php?option=com_toomanyfiles&view=toomanyfiles', false),$message);
	}	
    

    
    
}