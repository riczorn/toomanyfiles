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

jimport('joomla.application.component.modeladmin');

/**
 * Toomanyfiles model.
 */
class ToomanyfilesModelurl extends JModelAdmin
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_TOOMANYFILES2';


	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'url', $prefix = 'ToomanyfilesTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}
	
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk)) {
	
			//Do any procesing on fields here if needed
	
		}
	
		return $item;
	}
	
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');
	
		if (empty($table->id)) {
	
	
		}
	}
	
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app	= JFactory::getApplication();
	
		// Get the form.
		$form = $this->loadForm('com_toomanyfiles.resource', 'resource', array('control' => 'jform', 'load_data' => $loadData));
	
	
		if (empty($form)) {
			return false;
		}
	
		return $form;
	}
		
}