<?php

/**
 * @version     2.0.0
 * @package     com_toomanyfiles
 * @copyright   Copyright (C) 2014. Tutti i diritti riservati.
 * @license     GNU General Public License versione 2 o successiva; vedi LICENSE.txt
 * @author      Riccardo Zorn <r.zorn@tmg.it> - http://www.fasterjoomla.com
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

class ToomanyfilesController extends JController {

    /**
     * Method to display a view.
     *
     * @param	boolean			$cachable	If true, the view output will be cached
     * @param	array			$urlparams	An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return	JController		This object to support chaining.
     * @since	1.5
     */
    public function display($cachable = false, $urlparams = false) {
        require_once JPATH_COMPONENT . '/helpers/toomanyfiles.php';

        parent::display($cachable, $urlparams);

        return $this;
    }

}
