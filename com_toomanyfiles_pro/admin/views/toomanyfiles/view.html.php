<?php
/**
 * @version SVN: $Id$
 * @package    TooManyFiles
 * @author     Riccardo Zorn {@link http://www.fasterjoomla.com/toomanyfiles}
 * @author     Created on 22-Dec-2011
 * @license    GNU/GPL
 */

defined('_JEXEC') or die;

class TooManyFilesViewTooManyFiles extends JViewLegacy
{
	protected $files;
	
	protected $items;
	protected $pagination;
	protected $state;
		
	function display($tpl = null)
	{
		$this->state		= $this->get('State');
		$this->items = 		$this->get('Items'); 
		$this->pagination	= $this->get('Pagination');
		JToolBarHelper::title(   JText::_("COM_TOOMANYFILES_PRO"),'toomanyfiles' );	
		JToolBarHelper::preferences('com_toomanyfiles');
		if (count($this->items)) {
			JToolBarHelper:: custom('toomanyfiles.analyse','analyse','analyse','Analyse',true);
			JToolBarHelper:: custom('toomanyfiles.clearFiles','clear','clear','Clear',false);
			
			JToolBarHelper::deleteList('', 'toomanyfiles.download','COM_TOOMANYFILES_URLS_DOWNLOAD');
			JToolBarHelper::deleteList(JText::_("COM_TOOMANYFILES_URLS_DELETE_SURE"), 'toomanyfiles.delete','COM_TOOMANYFILES_URLS_DELETE');
			JToolBarHelper::divider();
				
		} else {
			JToolBarHelper:: custom('toomanyfiles.downloadHome','download','download','Download Homepage',false);
		}
		require_once(JPATH_COMPONENT."/helpers/toomanyfiles.php");
		
		TooManyFilesHelper::addStyles();
		TooManyFilesHelper::addSubmenu(JFactory::getApplication()->input->get('view'));
		$this->sidebar = JHtmlSidebar::render();
		$this->files = '';//$this->getModel()->listFiles();
		parent::display($tpl);
	}
}
