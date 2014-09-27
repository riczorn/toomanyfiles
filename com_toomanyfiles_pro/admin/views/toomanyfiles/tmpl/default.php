<?php
/**
 * @version SVN: $Id$
 * @package    TooManyFiles
 * @author     Riccardo Zorn {@link http://www.fasterjoomla.com/toomanyfiles}
 * @author     Created on 22-Dec-2011
 * @license    GNU/GPL
 */

defined('_JEXEC') or die;

?>
<form action="<?php echo JRoute::_('index.php?option=com_toomanyfiles&view=toomanyfiles'); ?>" method="post" name="adminForm" id="adminForm">

<?php if (!empty( $this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;?>
<div class="clearfix"> </div>

<?php // show this conditionally only if no downloads have been done 
/* ?>
<h1><?php echo JText::_("COM_TOOMANYFILES_INTRO_TITLE"); ?></h1>
<img src="components/com_toomanyfiles/assets/images/logo.png" class="rightimage"/>
<?php */ ?>
<table class="table table-striped" id="resourceList">
			<thead>
				<tr>
					<th width="1%" class="hidden-phone">
						<input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
					</th>
                    
				<th class='left'>
					Url
				</th>
                
                <th width="10%" class='right'>
					Download time
				</th>
                    
                <th width="20%" class='right'>
					Analysis
				</th>    
               
				</tr>
			</thead>
			<tfoot>
                <?php 
                if(isset($this->items[0])){
                    $colspan = count(get_object_vars($this->items[0]));
                }
                else{
                    $colspan = 10;
                }
            ?>
			<tr>
				<td colspan="<?php echo $colspan ?>">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
			</tfoot>
			<tbody>
<?php 
$image_uncompressed = '<img style="width:32px;height:32px" src="/administrator/components/com_toomanyfiles/assets/images/uncompressed.png"/>';
$image_compressed =   '<img style="width:32px;height:32px" src="/administrator/components/com_toomanyfiles/assets/images/compressed.png"/>';

	foreach($this->items as $item) {

	$i = $item->id;
	?>
		<tr>
			<td class="center hidden-phone">
					<?php echo JHtml::_('grid.id', $i, $item->id); ?>
			</td>
			<td>

					<?php 
					$uncompressedUrl = $item->url;
					if (strpos($uncompressedUrl,'?'))
						$uncompressedUrl .= '&nocompress=1';
					else 
						$uncompressedUrl .= '?nocompress=1';
					
					echo
					sprintf('<a href="%s" target="_blank" title="%2$s">%3$s</a>',
							$uncompressedUrl,
							JText::_('COM_TOOMANYFILES_VIEW_UNCOMPRESSED'),
							$image_uncompressed
					);
					echo
					sprintf('<a href="%s" target="_blank" title="%s">%s</a> %1$s', 
						$item->url,
						JText::_('COM_TOOMANYFILES_VIEW_COMPRESSED'),
						$image_compressed
					); ?>
			</td>
			<td>
					<?php echo $item->downloaded?
						str_replace("\n","<br>",$item->stats):'<a href="index.php?option=com_toomanyfiles&task=toomanyfiles.download&cid[]='.$item->id.'">Download</a>'; ?>
			</td>
			<td>
					<?php echo $item->analysed?''.$item->note:'<a href="index.php?option=com_toomanyfiles&task=toomanyfiles.analyse&cid[]='.$item->id.'">Analyse</a>'; ?>
			</td>
		</tr>
	<?php 
	}
?>
			</tbody>
		</table>

<?php 
// 	foreach($this->items as $item) {
// 		echo "<b>$item->url</b><br/>";
// 		echo "<pre>";
// 		var_dump($item);
// 		echo "</pre>";
// 	}
?>

<?php 
// echo "<h3>Files</h3>";
// 	foreach($this->files as $fileName=>$fileResources) {
// 		echo "<b>$fileName</b></br>";
// // 		echo "<ul>";
// // 		foreach($fileResources as $file)
// // 		echo "</ul>";
// 		echo "<pre>";
// 		var_dump($fileResources);
// 		echo "</pre>";
// 	}
?>
</div>


		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHtml::_('form.token'); ?>
</form>