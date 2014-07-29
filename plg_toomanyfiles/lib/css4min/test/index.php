<?php
/**
 * Toomanyfiles sample test file
 * 
 * @package toomanyfiles.test
 * @author Riccardo Zorn support@fasterjoomla.com
 * @copyright (C) 2012 - 2014 http://www.fasterjoomla.com
 * @license GNU/GPL v2 or greater http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die('Please comment the die() in the php file to allow this test to run'); 
?><!doctype html>
<html><head><title>css4min test</title>
<style>.d50 {width:40%;overflow:scroll;display:inline-block;float:left;max-height:400px}
div.main {padding:0 20px;}
div.main p {font-size:80%;margin:0}
input[type="submit"] {
    border: 0 none;
    display: block;
    float: left;
    padding: 20px 40px;
    margin-right:20px;
    background-color:yellow;
}
label {
	float:left;
	clear:both;
	background-color:yellow;
}
form {width:200px;
	float:right;
	display:block;
	position:fixed;
	right:0;
	margin-right:20px}
h1 {margin:0;padding:0;}
span.smaller {display:inline; padding:0 0 0 15px;margin:0;float:none;background-color:white}
</style>
</head><body>
<form method="post"><input type="submit" name="action" value="refresh"/>
<label for="idcomments">Strip comments <input id="idcomments" type="checkbox" name="stripcomments" value="on"></label>
</form>
<div class='main'><h1>css4min test</h1>
<p>This page displays some functionalities of css4min by combining several css files. 
The expected result is shown above, and below is the actual result. Of course you should see the same.<br>
If you scroll to the end of the page you can see side by side the result of the inclusion for all supported formats</p>
</div>
<?php
define('_JEXEC',42);
require_once(dirname(dirname(__FILE__))."/css4min.php");

echo "Picture of how it should be:<br>";
echo "<img src='images/show.png'><br>";
echo "This is how it actually is:<br><br>";
$css4Min = new Css4Min();
echo "<hr>";

echo "<hr>";
$myRelativePath = str_replace($css4Min->wwwroot,"",dirname(__FILE__)."/");
$files = array(
	$myRelativePath.'images/style.css', 
	'//ajax.googleapis.com/jquery/latest.css',
	'//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.js',  
	$css4Min->siteurl.
	$myRelativePath.'css/style.css',
	$myRelativePath.'js/testjq.js',
	$myRelativePath.'js/testjq2.js'
	);

$css4Min->isdebug = 5;

$output = array();
if (isset($_POST['action'])) {
	if ($_POST['action']=='refresh') {
		exec("rm -rf ".escapeshellarg($css4Min->wwwroot. "/" . $css4Min->cachedir) . " 2>&1");
	}
}
$css4Min->removeComments = false;
if (isset($_POST['stripcomments'])) {
		$css4Min->removeComments = true;
}
//if ($css4Min->removeComments) echo "<h1>rimuovo i commenti</h1>";

echo "<span class='blue'>blue @import(..)</span>
			<span class='red'>red in images folder</span>
			<span class='green'>green in css folder</span>
			<span >just span</span>
			<span class='red' id='testspan1'>test jq span 1</span>
			<span class='red' id='testspan2'>test jq span 2</span>
			<br>";
echo "<p>These are some variables for debugging your script</p>";
echo "<ul class='blue'><li>addFiles: ".$css4Min->addFiles($files).";";
echo "<li>getCacheFilePath: ".$css4Min->getCacheFilePath('styleSheets')."<span class='smaller'> (this is the full path of the css file which will be created</span> ";
echo "<li>getCacheFileURI: <b>".$css4Min->getCacheFileURI('styleSheets')."</b><span class='smaller'>and this is the relative path</span>";
echo "</ul>";
$css4Min->debug();
echo "<h3>Render</h3>";
echo $css4Min->render();
echo "<h3>Result</h3>";
$css4Min->debug();

echo "<hr><div><div class='d50'><h2>Sample substitution</h2>";
$testLines = '
       body {background:#ffffff url("img_tree.png") no-repeat right top;}
       background:url(../images/u.jpg);
       background-image:url(../images/u.jpg);
       list-style:url(\'../images/u.jpg\');
       @font-face: url(fonts/arial.otf) 
       content:url(../../smiley.gif) 
       cursor: url(mycursor.cur)
       /* test multiple import */
       @import url("css1/style.css") screen, print;
       /* test different syntax */
       @import url("css/small.css?1") screen, print;
       @import url("css/small.css?2") screen;
       @import url("css/small.css?3" );
       @import url("css/small.css?4");
       @import url(css/small.css?id=5);
       @import "css/small.css?id=6";
       @import "css/small.css?7" screen;
       @import url(/media/system/css/mootree_rtl.css);
       @import "/templates/system/css/offline_rtl.css";
';
echo "<pre>".join("<br>",explode("\n",$testLines))."</pre></div>";


minifier::rewriteUrls($testLines,str_replace($css4Min->wwwroot."/","",dirname(__FILE__)."/test.css"));
		if ($css4Min->removeComments) {
			minifier::removeComments($testLines);
		}

echo "<div class='d50'><h2>Result</h2><pre>".join("<br>",explode("\n",str_replace("<","&lt;",str_replace(">","&gt;",$testLines))))."</pre></div></div>";

?>
</body></html>