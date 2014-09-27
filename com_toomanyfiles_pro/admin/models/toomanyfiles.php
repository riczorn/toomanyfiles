<?php

/**
 * com_toomanyfiles main model
 *
 * @version SVN: $Id$
 * @package    TooManyFiles
 * @author     Riccardo Zorn {@link http://www.fasterjoomla.com/toomanyfiles}
 * @author     Created on 22-Dec-2011
 * @license    GNU/GPL
 */

defined('_JEXEC') or die();

class TooManyFilesModelTooManyFiles extends JModelList {
	/**
	 * Recursively download an entire site up to n levels.
	 */
	private $destinationFolder;
	private $lastResourceOrder;
	private $links;
	private $liveUrl;
		
	public function downloadHome() {
		
		$source = JUri::base(false);
		$source = str_replace('/administrator','',$source);
		if ($this->downloadFile($source)) 
			return "$source downloaded";
		else
			return "$source NOT downloaded";
	}
	
	/**
	 * Clear the downloaded files.
	 */
	public function clearFiles() {
		$destinationFolder = $this->getDestinationFolder(true);
		if ($destinationFolder) {
			jimport('joomla.filesystem.folder');
			$files = JFolder::files($destinationFolder,'.',false,false,array('index.html'));
			foreach($files as $file) {
				$fileName = $destinationFolder . $file;
				unlink($fileName);
			}
		}
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->delete('#__toomanyfiles');
		$db->setQuery($query);
		if (!$db->execute()) {
			JError::raiseWarning(504,"Could not clear table ".$db->getErrorMsg());
		}
		
		$query = $db->getQuery(true);
		$query->update('#__toomanyfiles_resources')->set('instances=0');
		$db->setQuery($query);
		if (!$db->execute()) {
			JError::raiseWarning(505,"Could not reset instances count ".$db->getErrorMsg());
		}
	}
	
	public function listFiles($maxLevels = 2) {
		$destinationFolder = $this->getDestinationFolder(false);
		$result = array();
		if ($destinationFolder) {
			jimport('joomla.filesystem.folder');
			$files = JFolder::files($destinationFolder,'.',false,false,array('index.html'));
			foreach($files as $file) {
				$fileName = $destinationFolder . $file;
				$result[$fileName] = $this->getFileResources($fileName); 
			}
		}
		return $result;
	}
	
	/**
	 * Return an array of all the scripts and stylesheets found in the file.
	 * @param unknown $fileName
	 */
	private function getFileResources($fileName) {
		$content = file_get_contents($fileName);
		require_once(JPATH_SITE.'/plugins/system/toomanyfiles/lib/fixhead.class.php');
		$tooManyFilesPlugin = $this->getTooManyFilePlugin(true);
				
		$fixHead = new FixHead($tooManyFilesPlugin);
		$fixHead->moveScripts($content);
		$result = new stdClass;
		$result->scripts = array();
		$result->styleSheets = array();
		
		foreach($fixHead->head['scripts'] as $key=>$data) {
				$result->scripts[] = $key;
			}
		foreach($fixHead->head['styleSheets'] as $key=>$data) {
				$result->styleSheets[] = $key;
			}
			
		$result->links = $this->findLinks($content);
			
		return $result;
	}
	
	private function getTooManyFilePlugin($noCompress=true) {
		$tooManyFilesPlugin = JPluginHelper::getPlugin('system','toomanyfiles');
		$tooManyFilesPlugin->params = new JRegistry($tooManyFilesPlugin->params);
		
		if ($noCompress) {
			$options = array(
					'scripts_position' => 0,
					'scripts_usecompressed' => 0,
					'compress_js' => 0,
					'compress_css' => 0,
					'debug_level' => 0,
					'enabled_users' => 'guests_reg_admin',
					'exclude_components' => '',
					'exclude_pages' => '',
					'exclude_files' => '');
			foreach($options as $optionKey=>$optionValue) {
				$tooManyFilesPlugin->params->set($optionKey,$optionValue);
			}
		}
		return $tooManyFilesPlugin;
	}
	
	private function getDestinationFolder($createFolder=false) {
		if (!isset($this->destinationFolder)) {
			$config = new JConfig();
			$this->destinationFolder = $config->tmp_path . '/2manyfiles/';
			if ($createFolder && !file_exists($this->destinationFolder)) {
				mkdir($this->destinationFolder,0755);
				file_put_contents($this->destinationFolder.'index.html', '<html></html>');
			}
		
		}
		return $this->destinationFolder;
	}
	
	private function downloadFile($url, $uncompressed=true) {
		$destinationFolder = $this->getDestinationFolder(true);
		$start = microtime(true);
		$success = false;
		$destination = $destinationFolder . 'file_' . rand(10000, 99999) . '.html';
		while (file_exists($destination)) {
			$destination = $destinationFolder . 'file_' . rand(10000, 99999) . '.html';
		} 
		$destinationFileHandle = fopen($destination, "w");
		if (!$destinationFileHandle) {
			JError::raiseWarning(502,'Cannot open file '.$destination . ' for writing');
			return false;
		}
		$result = false;
		$originalUrl = $url;
		if ($uncompressed) {
			if (strpos ( $url, '?' ) > 0 ) {
				$url = $url . '&nocompress=1';
			} else {
				$url = $url . '?nocompress=1';
			}
		}
		$options = array(
				CURLOPT_RETURNTRANSFER => true,         // return web page
				CURLOPT_HEADER         => false,        // don't return headers
				CURLOPT_FOLLOWLOCATION => true,         // follow redirects
				CURLOPT_ENCODING       => "",           // handle all encodings
				CURLOPT_USERAGENT      => "toomanyfiles spider",     // who am i
				//CURLOPT_AUTOREFERER    => true,         // set referer on redirect
				CURLOPT_CONNECTTIMEOUT => 30,          // timeout on connect
				CURLOPT_TIMEOUT        => 30,          // timeout on response
				CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
				CURLOPT_URL				=> $url,
				
				//CURLOPT_POST            => 1,            // i am sending post data
				//CURLOPT_POSTFIELDS     => $curl_data,    // this are my post vars
				// 				CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl
				// 				CURLOPT_SSL_VERIFYPEER => false,        //
				// 				CURLOPT_VERBOSE        => 1                //
		);
	
		$ch = curl_init();
		if (curl_setopt_array($ch,$options)) {
			curl_setopt($ch, CURLOPT_FILE, $destinationFileHandle);
			if (strpos($url,'https://')===0) {
				curl_setopt($ch, CURLOPT_SSLVERSION,3);
			}
			$output = curl_exec($ch);
			if (curl_errno($ch)) {
				error_log('ERROR: '. $error = curl_error($ch));
				JError::raiseWarning(503,"Curl error: ".$error);
			}
			
			$end = (microtime(true) - $start);
			
			$this->saveDownload($originalUrl, $destination, $end);
			
			$result = true;
			
		} else {
			JError::raiseWarning(501,'Cannot set curl options.');
		}
		fclose($destinationFileHandle);
		curl_close ($ch);
		return $result;
	}
	private function getUrls($pks) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('localfile,id,url')->from('#__toomanyfiles')->where('id in ('.join(',',$pks).')');
		$db->setQuery($query);
		$results = $db->loadObjectList();
		if ($db->getErrorNum()>0) {
			JError::raiseWarning('getUrls db error: '.$db->getErrorMsg());
			return array();
		}
		return $results;
	}
	
	public function download($pks) {
		$urls = $this->getUrls($pks);
		if (empty($urls)) return false;
		$count = 0;
		foreach($urls as $result) {
			if (!empty($result->url)) {
				if ($this->downloadFile($result->url)) {
					$count += 1;
				}
			}
		}
		return sprintf("Downloaded %s urls", $count);
	}
	/**
	 * Analyse the files.
	 * @param unknown $pks // the ids of the files.
	 */
	public function analyse($pks) {
		$urls = $this->getUrls($pks);
		if (!$urls) return false;
			
		$scriptCount = 0;
		$styleCount = 0;
		$linkCount = 0;
		
		foreach($urls as $result) {
			$resources = $this->getFileResources($this->getDestinationFolder(false).$result->localfile);
			$thisScriptCount = 0;
			$thisStyleCount = 0;
			$thisLinkCount = 0;
			foreach($resources->scripts as $resource) {
				$this->saveResource('script',$resource);
				$thisScriptCount += 1;
			}
		
			foreach($resources->styleSheets as $resource) {
					$this->saveResource('style',$resource);
					$thisStyleCount += 1;
			}
			
			foreach($resources->links as $link) {
					$this->saveDownload($link,null,null);
					$thisLinkCount += 1;
			}
			$this->saveAnalysed($result->id, sprintf("%s scripts, %s styles, %s links",
					$thisScriptCount,
					$thisStyleCount,
					$thisLinkCount
				));
			
			$scriptCount+=$thisScriptCount;
			$styleCount+=$thisStyleCount;
			$linkCount+=$thisLinkCount;
		}
		
		return sprintf("Analysed %s scripts, %s styles, %s links added",
			$scriptCount,
			$styleCount,
			$linkCount
		);
	}
	
	/**
	 * Save scripts and styles to the db
	 * if not found insert, if found inc instances. 
	 * @param unknown $resource
	 */
	private function saveResource($kind,$resource) {
		
		// clean resource:
		//error_log($resource);
		
		if (strpos($resource,'//')!==false) {
			/*
			 * 	 //cdn.jquery.com
			 *   https://cdn.google.com
			 */
			if (strpos($resource,$this->liveUrl)===0) {
				$resource = str_replace($this->liveUrl,'',$resource);
				//error_log('>'.$resource);
			}
		}
		if (strpos($resource,'//')===false) {
			/*
			 * it's local, I don't want to store params.
			 */
			if (strpos($resource,'?')) {
				$resource = preg_replace('/\?.*$/','',$resource);
				//error_log('>>'.$resource);
			}
			$resource = ltrim($resource,'/');
		}
		
		$db = JFactory::getDbo();
		$query=$db->getQuery(true);
		$query->select('id,ordering,instances,'.$db->quoteName('group'))->from('#__toomanyfiles_resources')->where('uri='.$db->quote($resource));
		$db->setQuery($query);
		$current = $db->loadObject();
		$instances = 1;
		
		$group = 'main';
		$id = null;
		if ($current) {
			$id = $current->id;
			$this->lastResourceOrder=$current->ordering;
			$instances = $current->instances+1;
			$group = $current->group;
		}
		$query = $db->getQuery(true);
		$user = JFactory::getUser();
		if (!empty($id)) {
			$query->update('#__toomanyfiles_resources')->where('id='.$id);	
		}
		else {
			$query->insert('#__toomanyfiles_resources')->set('state=1')->set('created_by='.$user->id)->
				set('created=NOW()')->set('kind='.$db->quote($kind))->set('uri='.$db->quote($resource))->
				set('ordering='.$db->quote($this->getLastResourceOrder()+1))->set($db->quoteName('group').'='.$db->quote($group));
			
			$this->shiftOrder(); // move the following items down;
		}
		
		$query->set('instances='.$instances);
		if (!$db->setQuery($query)->execute()) {
		
			error_log('Error saving resource '.$query);
			JError::raiseWarning(505,'Error saving resource '.$db->getErrorMsg());
			return false;
		}
		return true;
		//$query->insert('#__toomanyfiles_resources')->columns('created_by, created,kind,uri,ordering,');
		//->set('created=NOW()');
	}
	
	
	private function shiftOrder() {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->update('#__toomanyfiles_resources')->set('ordering=ordering+1')->
			where('ordering>'.$this->getLastResourceOrder());
		$this->lastResourceOrder += 1;	
		return $db->setQuery($query)->execute();
			
	}
	
	private function getLastResourceOrder() {
		if (empty($this->lastResourceOrder)) {
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('max(ordering)')->from('#__toomanyfiles_resources');
			$res = $db->setQuery($query)->loadResult();
			if ($res)
				$this->lastResourceOrder = $res + 1;
			else  
				$this->lastResourceOrder = 1;
		}
		return $this->lastResourceOrder;
	}
	
	/**
	 * Save to the db for further analysis
	 * @param unknown $url
	 * @param unknown $file
	 * @param unknown $time
	 */
	private function saveDownload($url, $file, $time) {
		$db = JFactory::getDbo();
		// $url is the key; we'll replace it if necessary;
		$dburl = str_replace(JUri::base(false),'',$url);
		$dbfile = str_replace($this->getDestinationFolder(false),'',$file);
		$query = $db->getQuery(true);
		$query->select('id')->from('#__toomanyfiles')->where('url like '.$db->quote($dburl));
		$db->setQuery($query);
		$id = $db->loadResult();
		$query = $db->getQuery(true);
		if ($id) {
			if (empty($file) && empty($time))
				return; // we already added the file, nothing to write now.
			$query->update('#__toomanyfiles')->where('id='.$db->quote($id));
			
		}
		else {
			$query->insert('#__toomanyfiles');
			$query->set('url='.$db->quote($dburl))->set('created=NOW()');
		}
		$sizeKb=0;
		if (!empty($file)) {
			$query->set('localfile='.$db->quote($dbfile))->set('analysed=false');
			$sizeKb = filesize($file) * .0009765625;
		}
		if (!empty($time)) {
			$query->set('stats='.$db->quote(sprintf("time=%.2fs\nsize=%.0fKb",(0+$time),$sizeKb)));
		}
		
		$db->setQuery($query);
		if (!$db->execute()) {
			JError::raiseWarning(503,'Error saving to db '.$db->getErrorMsg());
		}
	}
	
	private function saveAnalysed($id,$note=null) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->update('#__toomanyfiles')->where('id='.$db->quote($id))
			->set('analysed=1')->set('note='.$db->quote($note));
		return $db->setQuery($query)->execute();
	}
	
	public function getItems() {
		$db = JFactory::getDbo();
		// $url is the key; we'll replace it if necessary;
		$query = $db->getQuery(true);
		$query->select('*')->from('#__toomanyfiles');
		$db->setQuery($query);
		$results = $db->loadObjectList();
		foreach($results as $result) {
			$result->downloaded = ($result->localfile && file_exists($this->getDestinationFolder().$result->localfile));
		}
		return $results;
	}
	
	
	private function findLinks($body) {
		$this->links = array(); 
		$expression = '@<a\s[^>]*href\s*=\s*[\'"]([^<\'">]*)[\'"][^>]*>@ism';
		// this should be invoked before fix() otherwise fix() could miss duplicates.
		$body = preg_replace_callback($expression, array(&$this,  'findLinksCallback'), $body);
		return $this->links;
	}
	

	private function findLinksCallback($matches) {
		$match = trim($matches[1]);
		if (empty($this->liveUrl)) {
			$this->liveUrl = JUri::base(false);
			$this->liveUrl = str_replace('/administrator','',$this->liveUrl);
		}
		if (strpos($match,'http')===0) {// only local files;
			// let's exclude files with the url completely typed in 
			if (strpos($match,$this->liveUrl)===false) {
				return; 
			}
		}
		if (strpos($match,'#')!==false) {
			$match = preg_replace('/#.*$/is','',$match);	
		}
		$match = trim($match);
		$match = ltrim($match,'/');
		
		if (strlen($match)>0) {
			if (strpos($match,$this->liveUrl)===false)
				$match = $this->liveUrl . $match;
			$this->links[] = $match;
		}
	} 
}