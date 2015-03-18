<?php
/**
 * @package     FasterJoomla Admin components - Toomanyfiles
 * 
 * This package shows an editable drop-down to the user with content like:
 *  - No
 *  - CDN
 *      - http://code.jquery.com/blablalba.10.7.min.js
 *      - http://cdn.google.com/blablabla
 *  - File
 *  	- js/jquery.1.10.3.min.js
 *  	- js/jquery.2.1.0.min.js
 *  - a custom element if appropriate;
 *  
 *  The user may specify an alternate local or remote version for each library.
 *
 * @author Riccardo Zorn support@fasterjoomla.com
 * @copyright (C) 2012 - 2014 http://www.fasterjoomla.com
 * @license GNU/GPL v2 or greater http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

defined('_JEXEC') or die('Error');

//2.5? jimport('joomla.form.formfield');
JFormHelper::loadFieldClass('groupedlist');

class JFormFieldZzlibrary extends JFormFieldGroupedList
{
	
    protected $type = 'zzlibrary';
    
    protected $directory;
    protected $cssClass;
    
    protected function getInput() {
    	// Including fallback code for HTML5 non supported browsers.
    	JHtml::_('jquery.framework');
    	JHtml::_('script', 'system/html5fallback.js', false, true);
    	JHtml::_('behavior.modal', 'a.modal');
    	
    	$class="libclass";
    	$title = "title";
    	$body = parent::getInput();
    	$document = JFactory::getDocument();
    	$inputid = $this->type . '_field_'.$this->element["name"];
    	$selectid = 'jform_params_'.$this->element["name"];
    	
    	$document->addScriptDeclaration('
    			/**
    			 * This will update the href of the test button:
    			 */
    			function '.$inputid.'_changed(newUrl) {
    				var url = zzLibrary_getUrl(newUrl,"' . $this->directory . '");
    				jQuery("#test_'.$inputid.'").attr("href",url);
    			}
    			jQuery(function($) {
    				// initialize:
    				'.$inputid.'_changed($("#'.$inputid.'").val());
    			
    				// when typing in a new uri
    				$("#'.$inputid.'").change(function() {
    					'.$inputid.'_changed(this.value);
    			
    					// now let\'s update the select\'s custom option or create a new custom option;
    					customSelector = "#'.$selectid.' .' .$this->cssClass . '";
    					$customOption = jQuery(customSelector);
    					if ($customOption.length==0) { 
    						// create a custom option to hold the edited uri
    						jQuery("#'.$selectid.'").append("<option value=\"1\" class=\"' . 
    							$this->cssClass . '\">loading...</option>");
    						$customOption = jQuery(customSelector);
    					}
    					$customOption.val(this.value);
    					jQuery("#'.$selectid.'").val(this.value);
    					$customOption.text(this.value);
    					$customOption.attr("selected","selected");
    					// now update the chosen markup for the nice dropdown
    					jQuery("#'.$selectid.'").trigger("liszt:updated");			
    				});
    			
    				// when selecting a different option from the dropdown
    			    $("#'.$selectid.'").change(function() {
    					if (this.value=="0" || this.value=="-1" || this.value=="1") {
    						jQuery("#container_'.$inputid.'").addClass("hidden");
    					} else {
    						jQuery("#container_'.$inputid.'").removeClass("hidden");
    						jQuery("#'.$inputid.'").val(this.value);
    						'.$inputid.'_changed(this.value);
    					}
    				}); 			
    			});    			
    			');
    	$href=$this->value;
    	$hidden = in_array($href, array("-1","0","1"))?'hidden':'';
		return "<div class='zzlibrary $class'>
				<div class='body'>$body</div> 
				<div class='test $hidden' id='container_$inputid'>
					<input type='text' id='$inputid' value='$href' />
					<a class='btn btn-small btn-success modal' rel='{handler: \"iframe\"}' id='test_$inputid' >Test</a>
				</div> 
			</div>";
	}
	
	/**
	 * Method to edit the SimpleXMLElement from the configuration xml 
	 * adding the extra values for the local files and - if necessary - the selected item.
	 *
	 * @return  array  The field option objects as a nested array in groups.
	 *
	 * @since   11.1
	 * @throws  UnexpectedValueException
	 */
	protected function getGroups()
	{
		$groups = array();
		$label = 0;
		$foundCurrentValue = false;
		$value = in_array($this->value, array("-1","0","1")) ? false : $this->value;
		foreach ($this->element->children() as $element)
		{
			if ($element->getName()=='group')
			{
				switch($element['value']) {
					case 'cdn':
						// nothing to do here, it's hopefully coming from the xml configuration
						// let's just test if it contains the selected element:
						if ($value) {
							foreach ($element->children() as $child) {
								if ($child->value == $value) {
									$foundCurrentValue = true;
								}
							}
						}
						break;
					case 'local':
						if (!$foundCurrentValue && $value){ 
							$foundCurrentValue = $this->addLocalFiles($element);
						} else {
							$this->addLocalFiles($element);
						}
						break;
				}
			}			
		}
		if (!$foundCurrentValue && $value) {
			
			$el = $this->element->addChild('option',$value);
			$el->addAttribute('value',$value);
			
			$el->addAttribute('class',$this->cssClass);
			$el->addAttribute('label','Custom source: '.$value);
		}
		return parent::getGroups();
	}
	
	/**
	 * Browse folder for libraries.
	 * @param SimpleXMLElement $element
	 */
	private function addLocalFiles($element) {
		$directory = $this->directory;
		$filefilter = $this->element['filefilter'];
		$value = $this->value;
		$foundCurrentValue = false;
		
		if (empty($directory))
			return $foundCurrentValue;
		if (strpos($directory,'/')===0) {
			$path = JPATH_SITE.''.$directory . '/';
		} else {
			$path = JPATH_SITE.'/plugins/system/toomanyfiles/'.$directory . '/';
		}
		
		JLoader::import('joomla.filesystem.folder');
		
		$files = JFolder::files($path,$filefilter,false,false,	array(),	array(),true);
		if (is_array($files)) {
			$rel_path = str_replace(JPATH_SITE,'',$path);
			foreach($files as $file) {
				$el = $element->addChild('option',$file);
				$el->addAttribute('value',$rel_path.$file);
				if ($rel_path.$file == $value) {
					$foundCurrentValue = true;
				}
			}
		}
		
		return $foundCurrentValue;
	}
	
	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     JFormField::setup()
	 * @since   3.2
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$this->addCommonStylesAndScripts();
		
		$return = parent::setup($element, $value, $group);
	
		if ($return)
		{
			$this->directory = rtrim( (string) $this->element['directory'],'/');
		}
		
		$this->cssClass = $this->type . '_class_'.$this->element["name"];
		
		return $return;
	}
	
	protected function addCommonStylesAndScripts() {
		if (!defined('_toomanyfiles_zzlibrary')) {
			define('_toomanyfiles_zzlibrary','1');
			$document = JFactory::getDocument();
			$document->addStyleDeclaration('
					.zzlibrary>* {
						display:inline-block;
					}
					.zzlibrary>.test.hidden {
						display:none;
					}
			');
			$document->addScriptDeclaration('
				/**
				 * Pad the partial url with a meaningful segment
				 */
				function zzLibrary_getUrl(urlSegment, directory) {
						var url = urlSegment;
    					if (url.indexOf("//")===0) {
    						// it is a url! use as is. i.e. //code.google.com/something...
    					} else if (url.indexOf("/")===0) {
    						// it is a relative url! use as is. i.e. /media/libraries/jquery/jquery.min.js
    					} else {
							// There should be no relative urls.
							// let\'s assume the user just forgot a leading "/"
							url = "/"+url;
    					}
					return url;
				}
			');
		}
	} 
}
