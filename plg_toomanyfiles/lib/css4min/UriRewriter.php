<?php
/**
 * Class Minify_CSS_UriRewriter  
 * (adapted from Minify for use with Css4Min)
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 * @license GNU/GPL v2 or greater http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die;
/**
 * Rewrite file-relative URIs as root-relative in CSS files
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Css4Min_Minify_CSS_UriRewriter {
    
    /**
     * rewrite() and rewriteRelative() append debugging information here
     *
     * @var string
     */
    public static $debugText = '';
    
    //                             @import     url      (         "    p.css    ?id=1        "           )    screen,print    ;
    //                                        1              1 2      23   34              45      5    6    67          7
    public static $regexp_import='/@import\\s+(url\\s*\\(\\s*)?([\'"]?)(.*?)(\\?[^\'"\\)]*)?([\'"]?)\\s*(\\)?)([\\w,\\s]+)?\\s*;/';
    public static $regexp_url='/url\\(\\s*([^\\)\\s]+)\\s*\\)/';
    
    /**
     * This is used in regexpr callbacks during iteration over several css files to hold 
     * the @imports which need to be collected and inserted at the beginning of the resulting css file
     * @var array
     */
    private static $cssExternalImports = null;
    
    /**
     * In CSS content, rewrite file relative URIs as root relative
     * 
     * @param string $css
     * 
     * @param string $currentDir The directory of the current CSS file.
     * 
     * @param string $docRoot The document root of the web site in which 
     * the CSS file resides (default = $_SERVER['DOCUMENT_ROOT']).
     * 
     * @param array $symlinks (default = array()) If the CSS file is stored in 
     * a symlink-ed directory, provide an array of link paths to
     * target paths, where the link paths are within the document root. Because 
     * paths need to be normalized for this to work, use "//" to substitute 
     * the doc root in the link paths (the array keys). E.g.:
     * <code>
     * array('//symlink' => '/real/target/path') // unix
     * array('//static' => 'D:\\staticStorage')  // Windows
     * </code>
     * 
     * @return string
     */
    public static function rewrite($css, $currentDir, $docRoot = null, $symlinks = array()) 
    {
    	//echo "<h3>Rewrite</h3>";
    	$oldCurrentDir = self::$_currentDir;
        self::$_docRoot = self::_realpath(
            $docRoot ? $docRoot : $_SERVER['DOCUMENT_ROOT']
        );
        self::$_currentDir = self::_realpath($currentDir);
        self::$_symlinks = array();
        
        // normalize symlinks
        foreach ($symlinks as $link => $target) {
            $link = ($link === '//')
                ? self::$_docRoot
                : str_replace('//', self::$_docRoot . '/', $link);
            $link = strtr($link, '/', DIRECTORY_SEPARATOR);
            self::$_symlinks[$link] = self::_realpath($target);
        }
        
        self::$debugText .= "docRoot    : " . self::$_docRoot . "\n"
                          . "currentDir : " . self::$_currentDir . "\n";
        if (self::$_symlinks) {
            self::$debugText .= "symlinks : " . var_export(self::$_symlinks, 1) . "\n";
        }
        self::$debugText .= "\n";
        
        $css = self::_trimUrls($css);
        
        //body {background:#ffffff url('img_tree.png') no-repeat right top;}
        //background, background-image, list-style, @font-face url, content:url(smiley.gif) cursor: url(mycursor.cur)
        
        // Syntax1: @import "[URL]" ([media] ("," [media])+ )? ";" 
    	// Syntax2: @import url("[URL]") ([media] ("," [media])+ )? ";" 
    	// @import url(../../../media/system/css/system.css);
        // @import url("foo.css") screen, print;
        
	    //                                @import     url      (         "   p.css   "          )  screen,print    ;
	    //                                           1              1 2     23   34     4    5    56          6
	    // public static $regexp_import='/@import\\s+(url\\s*\\(\\s*)?([\'"])(.*?)([\'"])\\s*(\\)?)([\\w,\\s]+)\\s*;/';
   
        
 
        $css = preg_replace_callback(self::$regexp_import      
            ,array(self::$className, '_processUriCB_import'), $css);

        // warning: imported files will be matched twice against this rule:
        $css = preg_replace_callback(self::$regexp_url
            ,array(self::$className, '_processUriCB_url'), $css);
        
        self::$_currentDir = $oldCurrentDir;    
        return $css;
    }
    
    /**
     * In CSS content, prepend a path to relative URIs
     * 
     * @param string $css
     * 
     * @param string $path The path to prepend.
     * 
     * @return string
     */
    public static function prepend($css, $path)
    {
        self::$_prependPath = $path;
        
        $css = self::_trimUrls($css);
        
        // append
        /*$css = preg_replace_callback('/@import\\s+([\'"])(.*?)[\'"]/'
            ,array(self::$className, '_processUriCB'), $css);
        $css = preg_replace_callback('/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
            ,array(self::$className, '_processUriCB'), $css);*/
    //                                @import     url      (         "   p.css   "          )  screen,print    ;
    //                                           1              1 2     23   34     4    5    56          6
    // public static $regexp_import='/@import\\s+(url\\s*\\(\\s*)?([\'"])(.*?)([\'"])\\s*(\\)?)([\\w,\\s]+)\\s*;/';
        $css = preg_replace_callback(self::$regexp_import      
            ,array(self::$className, '_processUriCB_import'), $css);
            
        // warning: imported files will be matched twice against this rule:
        $css = preg_replace_callback(self::$regexp_url
            ,array(self::$className, '_processUriCB_url'), $css);
                    
        self::$_prependPath = null;
        return $css;
    }
    
    /**
     * Get a root relative URI from a file relative URI
     *
     * <code>
     * Minify_CSS_UriRewriter::rewriteRelative(
     *       '../img/hello.gif'
     *     , '/home/user/www/css'  // path of CSS file
     *     , '/home/user/www'      // doc root
     * );
     * // returns '/img/hello.gif'
     * 
     * // example where static files are stored in a symlinked directory
     * Minify_CSS_UriRewriter::rewriteRelative(
     *       'hello.gif'
     *     , '/var/staticFiles/theme'
     *     , '/home/user/www'
     *     , array('/home/user/www/static' => '/var/staticFiles')
     * );
     * // returns '/static/theme/hello.gif'
     * </code>
     * 
     * @param string $uri file relative URI
     * 
     * @param string $realCurrentDir realpath of the current file's directory.
     * 
     * @param string $realDocRoot realpath of the site document root.
     * 
     * @param array $symlinks (default = array()) If the file is stored in 
     * a symlink-ed directory, provide an array of link paths to
     * real target paths, where the link paths "appear" to be within the document 
     * root. E.g.:
     * <code>
     * array('/home/foo/www/not/real/path' => '/real/target/path') // unix
     * array('C:\\htdocs\\not\\real' => 'D:\\real\\target\\path')  // Windows
     * </code>
     * 
     * @return string
     */
    public static function rewriteRelative($uri, $realCurrentDir, $realDocRoot, $symlinks = array())
    {
        // prepend path with current dir separator (OS-independent)
        $path = strtr($realCurrentDir, '/', DIRECTORY_SEPARATOR)  
            . DIRECTORY_SEPARATOR . strtr($uri, '/', DIRECTORY_SEPARATOR);
        
        self::$debugText .= "file-relative URI  : {$uri}\n"
                          . "path prepended     : {$path}\n";
        
        // "unresolve" a symlink back to doc root
        foreach ($symlinks as $link => $target) {
            if (0 === strpos($path, $target)) {
                // replace $target with $link
                $path = $link . substr($path, strlen($target));
                
                self::$debugText .= "symlink unresolved : {$path}\n";
                
                break;
            }
        }
        // strip doc root
        $path = substr($path, strlen($realDocRoot));
        
        self::$debugText .= "docroot stripped   : {$path}\n";
        
        // fix to root-relative URI
        $uri = strtr($path, '/\\', '//');
        $uri = self::removeDots($uri);
      
        self::$debugText .= "traversals removed : {$uri}\n\n";
        
        return $uri;
    }

    /**
     * Remove instances of "./" and "../" where possible from a root-relative URI
     *
     * @param string $uri
     *
     * @return string
     */
    public static function removeDots($uri)
    {
        $uri = str_replace('/./', '/', $uri);
        // inspired by patch from Oleg Cherniy
        do {
            $uri = preg_replace('@/[^/]+/\\.\\./@', '/', $uri, 1, $changed);
        } while ($changed);
        return $uri;
    }
    
    /**
     * Defines which class to call as part of callbacks, change this
     * if you extend Minify_CSS_UriRewriter
     *
     * @var string
     */
    protected static $className = 'Css4Min_Minify_CSS_UriRewriter';

    /**
     * Get realpath with any trailing slash removed. If realpath() fails,
     * just remove the trailing slash.
     * 
     * @param string $path
     * 
     * @return mixed path with no trailing slash
     */
    protected static function _realpath($path)
    {
        $realPath = realpath($path);
        if ($realPath !== false) {
            $path = $realPath;
        }
        return rtrim($path, '/\\');
    }

    /**
     * Directory of this stylesheet
     *
     * @var string
     */
    private static $_currentDir = '';

    /**
     * DOC_ROOT
     *
     * @var string
     */
    private static $_docRoot = '';

    /**
     * directory replacements to map symlink targets back to their
     * source (within the document root) E.g. '/var/www/symlink' => '/var/realpath'
     *
     * @var array
     */
    private static $_symlinks = array();

    /**
     * Path to prepend
     *
     * @var string
     */
    private static $_prependPath = null;

    /**
     * @param string $css
     *
     * @return string
     */
    private static function _trimUrls($css)
    {
        return preg_replace('/
            url\\(      # url(
            \\s*
            ([^\\)]+?)  # 1 = URI (assuming it does not contain ")")
            \\s*
            \\)         # )
        /x', 'url($1)', $css);
    }

    /**
     *  Trimmed down version of _processUriCB to manage only the urls
     * 
     * @param $m
     */
    private static function _processUriCB_url($m) 
   {
   		
    	
        // $m matched  '/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
        $isImport = false;
            // $m[1] is either quoted or not
            $quoteChar = ($m[1][0] === "'" || $m[1][0] === '"')
                ? $m[1][0]
                : '';
            $uri = ($quoteChar === '')
                ? $m[1]
                : substr($m[1], 1, strlen($m[1]) - 2);
        
                // analyze URI
        if ('/' !== $uri[0]                  // root-relative
            && false === strpos($uri, '//')  // protocol (non-data)
            && 0 !== strpos($uri, 'data:')   // data protocol
        ) {
            // URI is file-relative: rewrite depending on options
            if (self::$_prependPath === null) {
                $uri = self::rewriteRelative($uri, self::$_currentDir, self::$_docRoot, self::$_symlinks);
            } else {
                $uri = self::$_prependPath . $uri;
                if ($uri[0] === '/') {
                    $root = '';
                    $rootRelative = $uri;
                    $uri = $root . self::removeDots($rootRelative);
                } elseif (preg_match('@^((https?\:)?//([^/]+))/@', $uri, $m) && (false !== strpos($m[3], '.'))) {
                    $root = $m[1];
                    $rootRelative = substr($uri, strlen($root));
                    $uri = $root . self::removeDots($rootRelative);
                }
            }
        }
        return "url({$quoteChar}{$uri}{$quoteChar})";
    }
    
    
    /**
     *  Trimmed down version of _processUriCB to manage only the imports
     * 
     * @param $m
     */
    public static function _processUriCB_import($m) {
    	// $m matched :
        //                             @import     url      (         "   p.css   "          )  screen,print    ;
       //                                          1              1 2     23   34     4    5    56          6
       //$css = preg_replace_callback('/@import\\s+(url\\s*\\(\\s*)?([\'"])(.*?)([\'"])\\s*(\\)?)([\\w,\\s]+)\\s*;/'
    	$uri = $m[3];
        
    	// this would be unnecessary for the "@import url('import.css') since they are managed by the call to _processUri,
    	// but since we have to do it for the @import 'file.css' we just keep it.
        
   	    $quoteChar = $m[2];
        
        // analyze URI
        if ('/' !== $uri[0]                  // root-relative
            && false === strpos($uri, '//')  // protocol (non-data)
            && 0 !== strpos($uri, 'data:')   // data protocol
        ) {
            // URI is file-relative: rewrite depending on options
            if (self::$_prependPath === null) {
                $uri = self::rewriteRelative($uri, self::$_currentDir, self::$_docRoot, self::$_symlinks);
            } else {
                $uri = self::$_prependPath . $uri;
                if ($uri[0] === '/') {
                    $root = '';
                    $rootRelative = $uri;
                    $uri = $root . self::removeDots($rootRelative);
                } elseif (preg_match('@^((https?\:)?//([^/]+))/@', $uri, $m) && (false !== strpos($m[4], '.'))) {
                    $root = $m[2];
                    $rootRelative = substr($uri, strlen($root));
                    $uri = $root . self::removeDots($rootRelative);
                }
            }
        }
        
        /**
         * Warning: this is a change for Css4Min to inline additional resources. 
         * For performance reasons instead of doing it in the main class with another cycle, since we're at it 
         * we're doing it from here:
         */
        if (false === strpos($uri, '//')) {
	        $tmpContent = "";
	        Minifier::renderResource($tmpContent,$uri);
	        
	        return  "\n/* Begin CSS import inlined by Css4Min -  $uri */".
	        		$tmpContent.
	        		"/* End CSS import inlined by Css4Min */\n\n";
        } else {
        	self::$cssExternalImports[] = $m[0];
        	return "\n/* the directive $uri removed and added at the beginning of the compressed css file */\n";}
    }
    
    /**
     * This is the original function from minify, slightly changed to function with my requirements but still without 
     * proper management of the "@import url() screen,media" kind of stuff.
     * 
     * @param array $m
     *
     * @return string
     */
    private static function _processUriCB($m)
    {
    	die('please use the two dedicated functions now'); //this is just here for reference.
        // $m matched either '/@import\\s+([\'"])(.*?)[\'"]/' or '/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
        $isImport = ($m[0][0] === '@');
        
        //if ($isImport) echo "<b>DEBUG</b> _processUriCB $isImport : $m[0] , $m[1], $m[2], $m[3]<br>";
        //else echo "DEBUG url _processUriCB $isImport : $m[0] , $m[1], $m[2]<br>";
        // determine URI and the quote character (if any)
        if ($isImport) {
            $quoteChar = $m[2];
            $uri = $m[3];
        } else {
            // $m[1] is either quoted or not
            $quoteChar = ($m[2][0] === "'" || $m[2][0] === '"')
                ? $m[2][0]
                : '';
            $uri = ($quoteChar === '')
                ? $m[2]
                : substr($m[2], 1, strlen($m[2]) - 2);
        }
        // analyze URI
        if ('/' !== $uri[0]                  // root-relative
            && false === strpos($uri, '//')  // protocol (non-data)
            && 0 !== strpos($uri, 'data:')   // data protocol
        ) {
            // URI is file-relative: rewrite depending on options
            if (self::$_prependPath === null) {
                $uri = self::rewriteRelative($uri, self::$_currentDir, self::$_docRoot, self::$_symlinks);
            } else {
                $uri = self::$_prependPath . $uri;
                if ($uri[0] === '/') {
                    $root = '';
                    $rootRelative = $uri;
                    $uri = $root . self::removeDots($rootRelative);
                } elseif (preg_match('@^((https?\:)?//([^/]+))/@', $uri, $m) && (false !== strpos($m[4], '.'))) {
                    $root = $m[2];
                    $rootRelative = substr($uri, strlen($root));
                    $uri = $root . self::removeDots($rootRelative);
                }
            }
        }
        
        /**
         * Warning: this is a change for Css4Min to inline additional resources. 
         * For performance reasons instead of doing it in the main class with another cycle, since we're at it 
         * we're doing it from here:
         */
        
        if ($isImport) {
        	$tmpContent = "";
        	Minifier::renderResource($tmpContent,$uri);
        	return  $tmpContent;
        } else  {
        	return "$m[1]url({$quoteChar}{$uri}{$quoteChar})";
        }
        /*return $isImport
            ? "@import {$quoteChar}{$uri}{$quoteChar}"
            : "url({$quoteChar}{$uri}{$quoteChar})";
            */
    }
    public static function testURIs($linesArr) {
    	
    }
    
    public static function init() {
    	self::$cssExternalImports  = array();
    }
    
    public static function getImports() {
    	return self::$cssExternalImports;
    }
}
