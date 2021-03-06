<?php
/*
    PmWiki
    Copyright 2001-2004 Patrick R. Michaud
    pmichaud@pobox.com
    http://www.pmichaud.com/

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
error_reporting(E_ALL);
if (ini_get('register_globals')) 
  foreach($_REQUEST as $k=>$v) { unset(${$k}); }
$UnsafeGlobals = array_keys($GLOBALS); $GCount=0; $FmtV=array();
SDV($FarmD,dirname(__FILE__));
SDV($WorkDir,'wiki.d');
define('PmWiki',1);
@include_once('scripts/version.php');
$GroupPattern = '[[:upper:]][\\w]*(?:-\\w+)*';
$NamePattern = '[[:upper:]\\d][\\w]*(?:-\\w+)*';
$WikiWordPattern = '[[:upper:]][[:alnum:]]*(?:[[:upper:]][[:lower:]0-9]|[[:lower:]0-9][[:upper:]])[[:alnum:]]*';
$WikiDir = new PageStore('wiki.d/$PageName');
$WikiLibDirs = array($WikiDir,new PageStore('$FarmD/wikilib.d/$PageName'));
$InterMapFiles = array("$FarmD/scripts/intermap.txt",'local/localmap.txt');
$KeepToken = "\377\377";  
$K0=array('='=>'','@'=>'<code>');  $K1=array('='=>'','@'=>'</code>');
$Now=time();
$TimeFmt = '%B %d, %Y, at %I:%M %p';
$Newline="\262";
$PageEditFmt = "<div id='wikiedit'>
  <a id='top' name='top'></a>
  <h1 class='wikiaction'>$[Editing \$PageName]</h1>
  <form method='post' action='\$PageUrl?action=edit'>
  <input type='hidden' name='action' value='edit' />
  <input type='hidden' name='pagename' value='\$PageName' />
  <input type='hidden' name='basetime' value='\$EditBaseTime' />
  \$EditMessageFmt
  <textarea name='text' rows='25' cols='60'
    onkeydown='if (event.keyCode==27) event.returnValue=false;'
    >\$EditText</textarea><br />
  $[Author]: <input type='text' name='author' value='\$Author' />
  <input type='checkbox' name='diffclass' value='minor' \$DiffClassMinor />
    $[This is a minor edit]<br />
  <input type='submit' name='post' value=' $[Save] ' />
  <input type='submit' name='preview' value=' $[Preview] ' />
  <input type='reset' value=' $[Reset] ' /></form></div>";
$PagePreviewFmt = "<h2 class='wikiaction'>$[Preview \$PageName]</h2>
  <p><b>$[Page is unsaved]</b></p>
  \$PreviewText
  <hr /><p><b>$[End of preview -- remember to save]</b><br />
  <a href='#top'>$[Top]</a></p>";
$EditMessageFmt = '';
$EditFields = array('text');
$EditFunctions = array('RestorePage','ReplaceOnSave','PostPage',
  'PostRecentChanges','PreviewPage');
$RCDelimPattern = '  ';
$RecentChangesFmt = array(
  'Main.AllRecentChanges' => 
    '* [[$Group.$Name]]  . . . $CurrentTime by $AuthorLink',
  '$Group.RecentChanges' =>
    '* [[$Group/$Name]]  . . . $CurrentTime by $AuthorLink');
$DefaultPageTextFmt = 'Describe [[$Name]] here.';
$ScriptUrl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
$PubDirUrl = preg_replace('#/[^/]*$#','/pub',$ScriptUrl,1);
$HTMLVSpace = "<p class='vspace'></p>";
$BlockCS = array(); $BlockVS = array();
$UrlExcludeChars = '<>"{}|\\\\^`()[\\]\'';
$QueryFragPattern = "[?#][^\\s$UrlExcludeChars]*";
$SuffixPattern = '(?:-?[[:alnum:]]+)*';
$LinkPageExistsFmt = "<a class='wikilink' href='\$LinkUrl'>\$LinkText</a>";
$LinkPageCreateFmt = "<a class='createlinktext' href='\$PageUrl?action=edit'>\$LinkText</a><a 
  class='createlink' href='\$PageUrl?action=edit'>?</a>";
$UrlLinkFmt = "<a class='urllink' href='\$LinkUrl'>\$LinkText</a>";
umask(0);
$DefaultGroup = 'Main';
$DefaultName = 'HomePage';
$GroupHeaderFmt = '[:include $Group.GroupHeader:][:nl:]';
$GroupFooterFmt = '[:nl:][:include $Group.GroupFooter:]';
$PagePathFmt = array('$Group.$1','$1.$1','$1.$DefaultName');
$PageAttributes = array(
  'passwdread' => '$[Set new read password:]',
  'passwdedit' => '$[Set new edit password:]',
  'passwdattr' => '$[Set new attribute password:]');
$XLLangs = array('en');
setlocale(LC_CTYPE,'en_US');

$WikiTitle = 'PmWiki';
$HTTPHeaders = array(
  "Expires: Tue, 01 Jan 2002 00:00:00 GMT",
  "Last-Modified: ".gmstrftime('%a, %d %b %Y %H:%M:%S GMT'),
  "Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0",
  "Pragma: no-cache",
  "Content-type: text/html; charset=iso-8859-1;");
$HTMLDoctypeFmt = 
  "<!DOCTYPE html 
    PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
    \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
  <html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'><head>\n";
$HTMLTitleFmt = "  <title>\$WikiTitle - \$PageTitle</title>\n";
$HTMLStylesFmt = array("
  ul, ol, pre, dl, p { margin-top:0px; margin-bottom:0px; }
  code { white-space: nowrap; }
  .vspace { margin-top:1.33em; }
  .indent { margin-left:40px; }
  a.createlinktext { text-decoration:none; border-bottom:1px dotted grey; }
  a.createlink { text-decoration:none; position:relative; top:-0.5em;
    font-weight:bold; font-size:smaller; border-bottom:none; }
  ");
$HTMLHeaderFmt = array(
  "<style type='text/css'><!--",&$HTMLStylesFmt,"\n--></style>");
$HTMLBodyFmt = "</head>\n<body>";
$HTMLStartFmt = array('headers:',&$HTMLDoctypeFmt,&$HTMLHeaderFmt,
  &$HTMLTitleFmt,&$HTMLBodyFmt);
$HTMLEndFmt = "\n</body>\n</html>";
$PageStartFmt = array(&$HTMLStartFmt,"\n<div id='contents'>\n");
$PageEndFmt = array('</div>',&$HTMLEndFmt);

$HandleActions = array(
  'browse' => 'HandleBrowse',
  'edit' => 'HandleEdit', 'source' => 'HandleSource', 
  'attr'=>'HandleAttr', 'postattr' => 'HandlePostAttr');

$Conditions['false'] = 'false';

$MarkupTable['_begin']['seq'] = 'B';
$MarkupTable['_end']['seq'] = 'E';
Markup('fulltext','>_begin');
Markup('split','>fulltext',"\n",
  '$RedoMarkupLine=1; return explode("\n",$x);');
Markup('directives','>split');
Markup('links','>directives');
Markup('block','>links');
Markup('inline','>block');
Markup('style','>inline');

$ImgExtPattern="\\.(?:gif|jpg|jpeg|png)";
$ImgTagFmt="<img src='\$LinkUrl' border='0' alt='\$LinkAlt' />";

$BlockMarkups = array(
  'block' => array('','',''),
  'ul' => array('<ul><li>','</li><li>','</li></ul>'),
  'dl' => array('<dl>','</dd>','</dd></dl>'),
  'ol' => array('<ol><li>','</li><li>','</li></ol>'),
  'p' => array('<p>','','</p>'),
  'indent' => 
     array("<div class='indent'>","</div><div class='indent'>",'</div>'),
  'pre' => array('<pre> ',' ','</pre>'),
  'table' => array("<table width='100%'>",'','</table>'));

$CurrentTime = strftime($TimeFmt,$Now);

foreach(array('http:','https:','mailto:','ftp:','news:','gopher:','nap:') 
  as $m) { $LinkFunctions[$m] = 'LinkIMap';  $IMap[$m]="$m$1"; }
$LinkFunctions['<:page>'] = 'LinkPage';

if (strpos(@$_SERVER['QUERY_STRING'],'?')!==false) {
  unset($_GET);
  parse_str(str_replace('?','&',$_SERVER['QUERY_STRING']),$_GET);
}

foreach(array('pagename','action','text') as $v) {
  if (isset($_GET[$v])) $$v=$_GET[$v];
  elseif (isset($_POST[$v])) $$v=$_POST[$v];
  else $$v='';
}
if ($action=='') $action='browse';

if (!$pagename && 
    preg_match('!^'.preg_quote($_SERVER['SCRIPT_NAME'],'!').'/?([^?]*)!',
      $_SERVER['REQUEST_URI'],$match))
  $pagename = urldecode($match[1]);
if (preg_match('/[\\x80-\\xbf]/',$pagename)) 
  $pagename=utf8_decode($pagename);

if (file_exists("$FarmD/local/farmconfig.php")) 
  include_once("$FarmD/local/farmconfig.php");
if (file_exists('local/config.php')) 
  include_once('local/config.php');

SDV($DefaultPage,"$DefaultGroup.$DefaultName");
SDV($UrlPage,'{$UrlPage}');
if ($pagename&&!preg_match("/^$GroupPattern([\/.])$NamePattern$/",$pagename)) {
  $UrlPage=$pagename;
  $p = MakePageName($DefaultPage,$pagename);
  if (PageExists($p)) { Redirect($p); exit(); }
  SDV($PageNotFound,"$DefaultGroup.PageNotFound");
  $pagename = $PageNotFound;
} else $pagename=MakePageName($DefaultPage,$pagename);

if (IsEnabled($EnableStdConfig,1))
  include_once("$FarmD/scripts/stdconfig.php");


foreach((array)$InterMapFiles as $f) {
  if (@!($mapfd=fopen($f,"r"))) continue;
  while ($mapline=fgets($mapfd,1024)) {
    if (preg_match('/^\\s*$/',$mapline)) continue;
    list($imap,$url) = preg_split('/\\s+/',$mapline);
    if (strpos($url,'$1')===false) $url.='$1';
    $LinkFunctions["$imap:"] = 'LinkIMap';
    $IMap["$imap:"] = $url;
  }
}

$LinkPattern = implode('|',array_keys($LinkFunctions));
SDV($LinkPageCreateSpaceFmt,$LinkPageCreateFmt);

if (!function_exists(@$HandleActions[$action])) $action='browse';
$HandleActions[$action]($pagename);
Lock(0);
exit;

## helper functions
function stripmagic($x) 
  { return get_magic_quotes_gpc() ? stripslashes($x) : $x; }
function PSS($x) 
  { return str_replace('\\"','"',$x); }
function PZZ($x,$y='') { return ''; }
function PRR($x='') { $GLOBALS['RedoMarkupLine']++; return $x; }
function SDV(&$v,$x) { if (!isset($v)) $v=$x; }
function SDVA(&$var,$val) 
  { foreach($val as $k=>$v) if (!isset($var[$k])) $var[$k]=$v; }
function IsEnabled(&$var,$f=0)
  { return (isset($var)) ? $var : $f; }

## Lock is used to make sure only one instance of PmWiki is running when
## files are being written.  It does not "lock pages" for editing.
function Lock($op) { 
  global $WorkDir,$LockFile;
  SDV($LockFile,"$WorkDir/.flock");
  mkdirp(dirname($LockFile));
  static $lockfp,$curop;
    if (!$lockfp) {
    $lockfp=fopen($LockFile,"w") or
      Abort("Cannot acquire lockfile","Lockfile");
    fixperms($LockFile);
  }
  if ($op<0) { flock($lockfp,LOCK_UN); fclose($lockfp); $lockfp=0; $curop=0; }
  elseif ($op==0) { flock($lockfp,LOCK_UN); $curop=0; }
  elseif ($op==1 && $curop<1) { flock($lockfp,LOCK_SH); $curop=1; }
  elseif ($op==2 && $curop<2) { flock($lockfp,LOCK_EX); $curop=2; }
}

## mkdirp creates a directory and its parents as needed, and sets
## permissions accordingly.
function mkdirp($dir) {
  if (file_exists($dir)) return;
  if (!file_exists(dirname($dir))) mkdirp(dirname($dir));
  if (!mkdir($dir,0777)) {
    $parent = realpath(dirname($dir)); 
    $perms=decoct(fileperms($parent) & 03777);
    Abort("PmWiki wants permission to create the <tt>$dir</tt> directory
      in <tt>$parent</tt>.  Try executing <pre>    chmod 2777 $parent</pre>
      or <pre>    chmod 777 $parent</pre> on your server and reloading this
      page.  Afterwards, you can restore the permissions to their current
      setting by executing <pre>    chmod $perms $parent</pre>");
  }
  fixperms($dir);
}

## fixperms attempts to correct permissions on a file or directory
## so that both PmWiki and the account (current dir) owner can manipulate it
function fixperms($fname) {
  clearstatcache();
  if (!file_exists($fname)) Abort('no such file');
  $bp = 0;
  if (fileowner($fname)!=fileowner('.')) $bp = (is_dir($fname)) ? 007 : 006;
  if (filegroup($fname)==filegroup('.')) $bp <<= 3;
  if ($bp && (fileperms($fname) & $bp) != $bp)
    @chmod($fname,fileperms($fname)|$bp);
}

## MakePageName is used to convert a string into a valid pagename.
## If no group is supplied, then it uses $PagePathFmt to look
## for the page in other groups, or else uses the group of the
## pagename passed as an argument.
function MakePageName($basepage,$x) {
  global $MakePageNameFunction,$PageNameChars,$PagePathFmt;
  if (@$MakePageNameFunction) return $MakePageNameFunction($basepage,$x);
  SDV($PageNameChars,'-[:alnum:]');
  if (!preg_match('/(?:([^.\\/]+)[.\\/])?([^.\\/]+)$/',$x,$m)) return '';
  $name=str_replace(' ','',
    preg_replace('/\\b(\\w)/e',"strtoupper('$1')",
      preg_replace("/[^$PageNameChars]+/",' ',$m[2])));
  if ($m[1]) {
    $group = str_replace(' ','',
      preg_replace('/\\b(\\w)/e',"strtoupper('$1')",
        preg_replace("/[^$PageNameChars]+/",' ',$m[1])));
    return "$group.$name";
  }
  foreach((array)$PagePathFmt as $pg) {
    $pn = FmtPageName(str_replace('$1',$name,$pg),$basepage);
    if (PageExists($pn)) return $pn;
  }
  $group=preg_replace('/[\\/.].*$/','',$basepage);
  return "$group.$name";
}
  
## FmtPageName handles $[internationalization] and $Variable 
## substitutions in strings based on the $pagename argument.
function FmtPageName($fmt,$pagename) {
  # Perform $-substitutions on $fmt relative to page given by $pagename
  global $GroupPattern,$NamePattern,$EnablePathInfo,
    $GCount,$UnsafeGlobals,$FmtV;
  if (strpos($fmt,'$')===false) return $fmt;                  
  if (preg_match("/^($GroupPattern)[\\/.]($NamePattern)\$/",$pagename,$match)) {
    $fmt = preg_replace('/\\$([A-Z]\\w*Fmt)\\b/e','$GLOBALS[\'$1\']',$fmt);
    $fmt = preg_replace('/\\$\\[(.+?)\\]/e',"XL(PSS('$1'))",$fmt);
    static $qk = array('$PageUrl','$PageName','$Group','$Name');
    $qv = array('$ScriptUrl/$Group/$Name','$Group.$Name',$match[1],$match[2]);
    $fmt = str_replace($qk,$qv,$fmt);
  }
  if (isset($EnablePathInfo) && !$EnablePathInfo)
    $fmt = preg_replace('!\\$ScriptUrl/([^?#\'"\\s]+)!e',
      "'\$ScriptUrl?pagename='.str_replace('/','.','$1')",$fmt);
  if (strpos($fmt,'$')===false) return $fmt;
  static $g;
  if ($GCount != count($GLOBALS)+count($FmtV)) {
    $g = array();
    foreach($GLOBALS as $n=>$v) {
      if (is_array($v) || is_object($v) ||
         isset($FmtV["\$$n"]) || in_array($n,$UnsafeGlobals)) continue;
      $g["\$$n"] = $v;
    }
    $GCount = count($GLOBALS)+count($FmtV);
    krsort($g); reset($g);
  }
  $fmt = str_replace(array_keys($g),array_values($g),$fmt);
  $fmt = str_replace(array_keys($FmtV),array_values($FmtV),$fmt);
  return $fmt;
}

## The XL functions provide translation tables for $[i18n] strings
## in FmtPageName().
function XL($key) {
  global $XL,$XLLangs;
  foreach($XLLangs as $l) if (isset($XL[$l][$key])) return $XL[$l][$key];
  return $key;
}
function XLSDV($lang,$a) {
  global $XL;
  foreach($a as $k=>$v) { if (!isset($XL[$lang][$k])) $XL[$lang][$k]=$v; }
}
function XLPage($lang,$p) {
  global $TimeFmt,$XLLangs,$FarmD;
  $page = ReadPage($p);
  if (!$page) return;
  $text = preg_replace("/=>\\s*\n/",'=> ',$page['text']);
  foreach(explode("\n",$text) as $l)
    if (preg_match('/^\\s*[\'"](.+?)[\'"]\\s*=>\\s*[\'"](.+)[\'"]/',$l,$match))
      $xl[stripslashes($match[1])] = stripslashes($match[2]);
  if ($xl) {
    if (@$xl['xlpage-i18n']) {
      $i18n = preg_replace('/[^-\\w]/','',$xl['xlpage-i18n']);
      include_once("$FarmD/scripts/xlpage-$i18n.php");
    }
    if ($xl['Locale']) setlocale(LC_ALL,$xl['Locale']);
    if ($xl['TimeFmt']) $TimeFmt=$xl['TimeFmt'];
    array_unshift($XLLangs,$lang);
    XLSDV($lang,$xl);
  }
}

## class PageStore holds objects that store pages via the native
## filesystem.
class PageStore {
  var $dirfmt;
  function PageStore($d='wiki.d') { $this->dirfmt=$d; }
  function read($pagename) {
    $newline = "\262";
    $pagefile = FmtPageName($this->dirfmt,$pagename);
    if ($pagefile && $fp=@fopen($pagefile,"r")) {
      while (!feof($fp)) {
        $line = fgets($fp,4096);
        while (substr($line,-1,1)!="\n" && !feof($fp)) 
          { $line .= fgets($fp,4096); }
        @list($k,$v) = explode('=',rtrim($line),2);
        if ($k=='newline') { $newline=$v; continue; }
        $page[$k] = str_replace($newline,"\n",$v);
      }
      fclose($fp);
    }
    return @$page;
  }
  function write($pagename,$page) {
    global $Now,$Version,$Newline;
    $page['name'] = $pagename;
    $page['time'] = $Now;
    $page['host'] = $_SERVER['REMOTE_ADDR'];
    $page['agent'] = $_SERVER['HTTP_USER_AGENT'];
    $page['rev'] = @$page['rev']+1;
    $s = false;
    $pagefile = FmtPageName($this->dirfmt,$pagename);
    mkdirp(dirname($pagefile));
    if ($pagefile && ($fp=fopen("$pagefile,new","w"))) {
      $s = true && fputs($fp,"version=$Version\nnewline=$Newline\n");
      foreach($page as $k=>$v) 
        if ($k>'') $s = $s&&fputs($fp,str_replace("\n",$Newline,"$k=$v")."\n");
      $s = fclose($fp) && $s;
      if (file_exists($pagefile)) $s = $s && unlink($pagefile);
      $s = $s && rename("$pagefile,new",$pagefile);
    }
    $s && fixperms($pagefile);
    if (!$s)
      Abort("Cannot write page to $pagename ($pagefile)...changes not saved");
  }
  function exists($pagename) {
    $pagefile = FmtPageName($this->dirfmt,$pagename);
    return ($pagefile && file_exists($pagefile));
  }
  function delete($pagename) {
    global $Now;
    $pagefile = FmtPageName($this->dirfmt,$pagename);
    @rename($pagefile,"$pagefile,$Now");
  }
  function ls($pats=NULL) {
    global $GroupPattern,$NamePattern;
    $pats=array_merge("/^$GroupPattern\.$NamePattern$/",(array)$pats);
    $dir = FmtPageName($this->dirfmt,'');
    $dirlist = array(preg_replace('!/?[^/]*\$.*$!','',$dir));
    $out = array();
    while (count($dirlist)>0) {
      $dir = array_shift($dirlist);
      $dfp = opendir($dir); if (!$dfp) continue;
      while (($pagefile=readdir($dfp))!=false) {
        if (substr($pagefile,0,1)=='.') continue;
        if (is_dir("$dir/$pagefile"))
          { array_push($dirlist,"$dir/$pagefile"); continue; }
        if (@$seen[$pagefile]++) continue;
        foreach($pats as $p) {
          if (substr($p,0,1)=='!' && preg_match($p,$pagefile)) continue 2;
          if (!preg_match($p,$pagefile)) continue 2;
        }
        $out[] = $pagefile;
      }
    }
    return $out;
  }
}

function ReadPage($pagename,$defaulttext=NULL) {
  # read a page from the appropriate directories given by $WikiReadDirsFmt.
  global $WikiLibDirs,$DefaultPageTextFmt,$Now;
  if (is_null($defaulttext)) $defaulttext=$DefaultPageTextFmt;
  Lock(1);
  foreach ($WikiLibDirs as $dir) {
    $page = $dir->read($pagename);
    if ($page) break;
  }
  if ($page['text']=='') 
    $page['text']=FmtPageName($defaulttext,$pagename);
  if (@!$page['time']) $page['time']=$Now;
  return $page;
}

function WritePage($pagename,$page) {
  global $WikiDir;
  $WikiDir->write($pagename,$page);
}

function PageExists($pagename) {
  global $WikiLibDirs;
  foreach((array)$WikiLibDirs as $dir)
    if ($dir->exists($pagename)) return true;
  return false;
}

function ListPages($pat=NULL) {
  global $WikiLibDirs;
  foreach((array)$WikiLibDirs as $dir) 
    $out = array_unique(array_merge($dir->ls($pat),(array)@$out));
  return $out;
}

function RetrieveAuthPage($pagename,$level,$authprompt=true,$dtext=NULL) {
  global $AuthFunction;
  SDV($AuthFunction,'BasicAuth');
  if (!function_exists($AuthFunction)) return ReadPage($pagename,$dtext);
  return $AuthFunction($pagename,$level,$authprompt,$dtext);
}

function SetPage($pagename,$page) {
  global $PageTitle,$TimeFmt,$LastModified,$LastModifiedBy,$LastModifiedHost;
  $PageTitle = FmtPageName('$Name',(@$page['name'])?$page['name']:$pagename);
  $LastModified = strftime($TimeFmt,$page['time']);
  $LastModifiedBy = @$page['author'];
  $LastModifiedHost = @$page['host'];
}
  
function Abort($msg) {
  # exit pmwiki with an abort message
  echo "<h3>PmWiki can't process your request</h3>
    <p>$msg</p><p>We are sorry for any inconvenience.</p>";
  exit;
}

function Redirect($pagename,$urlfmt='$PageUrl') {
  # redirect the browser to $pagename
  global $EnableRedirect,$RedirectDelay;
  SDV($RedirectDelay,0);
  clearstatcache();
  #if (!PageExists($pagename)) $pagename=$DefaultPage;
  $pageurl = FmtPageName($urlfmt,$pagename);
  if (IsEnabled($EnableRedirect,1)) {
    header("Location: $pageurl");
    header("Content-type: text/html");
    echo "<html><head>
      <meta http-equiv='Refresh' Content='$RedirectDelay; URL=$pageurl' />
     <title>Redirect</title></head><body></body></html>";
  } else echo "<a href='$pageurl'>Redirect to $pageurl</a>";
  exit;
}

function PrintFmt($pagename,$fmt) {
  global $HTTPHeaders,$FmtV;
  if (is_array($fmt)) 
    { foreach($fmt as $f) PrintFmt($pagename,$f); return; }
  $x = FmtPageName($fmt,$pagename);
  if (preg_match("/^markup:(.*)$/",$x,$match))
    { print MarkupToHTML($pagename,$match[1]); return; }
  if (preg_match("/^headers:/",$x)) {
    foreach($HTTPHeaders as $h) (@$sent++) ? @header($h) : header($h);
    return;
  }
  if (preg_match('/^function:(\S+)\s*(.*)$/s',$x,$match) &&
      function_exists($match[1]))
    { $match[1]($pagename,$match[2]); return; }
  if (preg_match('/^wiki:(.+)$/',$x,$match)) 
    { PrintWikiPage($pagename,$match[1]); return; }
  echo $x;
}

function PrintWikiPage($pagename,$wikilist=NULL) {
  if (is_null($wikilist)) $wikilist=$pagename;
  $pagelist = preg_split('/\s+/',$wikilist,-1,PREG_SPLIT_NO_EMPTY);
  foreach($pagelist as $p) {
    if (PageExists($p)) {
      $page = RetrieveAuthPage($p,'read',false);
      if ($page['text']) 
        echo MarkupToHTML($pagename,$page['text']);
      return;
    }
  }
}

function Keep($x,$level='') {
  # Keep preserves a string from being processed by wiki markups
  global $KeepToken,$KPV,$KPCount;
  $KPCount++; $KPV[$KPCount.$level]=$x;
  return $KeepToken.$KPCount.$level.$KeepToken;
}

function CondText($pagename,$condspec,$condtext) {
  global $Conditions;
  if (!preg_match("/^(\\S+)\\s*(!?)\\s*(\\S+)?\\s*(.*)$/",
    $condspec,$match)) return '';
  @list($condstr,$condtype,$not,$condname,$condparm) = $match;
  if (isset($Conditions[$condname])) {
    $tf = @eval("return ".$Conditions[$condname].";");
    if (!$tf && !$not) $condtext='';
  }
  return $condtext;
}
  
function IncludeText($pagename,$inclspec) {
  global $MaxIncludes,$IncludeBadAnchorFmt,$InclCount,$FmtV;
  SDV($MaxIncludes,10);
  SDV($IncludeBadAnchorFmt,"include:\$PageName - #\$BadAnchor \$[not found]\n");
  $npat = '[[:alpha:]][-\\w]*';
  if ($InclCount++>=$MaxIncludes) return Keep($inclspec);
  if (preg_match("/^include\\s+([^#\\s]+)(.*)$/",$inclspec,$match)) {
    @list($inclstr,$inclname,$opts) = $match;
    $inclname = MakePageName($pagename,$inclname);
    if ($inclname==$pagename) return '';
    $inclpage=RetrieveAuthPage($inclname,'read',false,'');
    $itext=$inclpage['text'];
    foreach(preg_split('/\\s+/',$opts) as $o) {
      if (preg_match("/^#($npat)?(\\.\\.)?(#($npat)?)?$/",$o,$match)) {
        @list($x,$aa,$dots,$b,$bb)=$match;
        if (!$dots && !$b) $bb=$npat;
        if ($b=='#') $bb=$npat;
        if ($aa)
          $itext=preg_replace("/^.*?([^\n]*\\[\\[#$aa\\]\\])/s",'$1',$itext,1);
        if ($bb)
          $itext=preg_replace("/(.)[^\n]*\\[\\[#$bb\\]\\].*$/s",'$1',$itext,1);
        continue;
      } 
      if (preg_match('/^(lines?|paras?)=(\\d*)(\\.\\.(\\d*))?$/',
          $o,$match)) {
        @list($x,$unit,$a,$dots,$b) = $match;
        $upat = (substr($unit,0,1)=='p') ? ".*?(\n\\s*\n|$)" : "[^\n]*\n";
        if (!$dots) { $b=$a; $a=0; }
        if ($a>0) $a--;
        $itext=preg_replace("/^(($upat)\{0,$b}).*$/s",'$1',$itext,1);
        $itext=preg_replace("/^($upat)\{0,$a}/s",'',$itext,1); 
        continue;
      }
    }
    return htmlentities($itext,ENT_QUOTES);
  }
  return Keep($inclspec);
}

function Block($b) {
  global $BlockMarkups,$HTMLVSpace,$BlockCS,$BlockVS;
  $cs = &$BlockCS[0];  $vspaces = &$BlockVS[0];
  $out = '';
  if (!$b) $b='p,1';
  @list($code,$depth) = explode(',',$b);
  if ($code=='vspace') { 
    $vspaces.="\n"; 
    if (@$cs[0]!='p') return; 
  }
  if ($depth==0) $depth=strlen($depth);
  while (count($cs)>$depth) 
    { $c = array_pop($cs); $out .= $BlockMarkups[$c][2]; }
  if ($depth>0 && $depth==count($cs) && $cs[$depth-1]!=$code)
    { $c = array_pop($cs); $out .= $BlockMarkups[$c][2]; }
  if ($vspaces) { 
    $out .= (@$cs[0]=='pre') ? $vspaces : $HTMLVSpace; 
    $vspaces=''; 
  }
  if ($depth==0) { return $out; }
  if ($depth==count($cs)) { return $out.$BlockMarkups[$code][1]; }
  while (count($cs)<$depth-1) 
    { array_push($cs,'dl'); $out .= $BlockMarkups['dl'][0].'<dd>'; }
  if (count($cs)<$depth) {
    array_push($cs,$code);
    $out .= $BlockMarkups[$code][0];
  }
  return $out;
}

function FormatTableRow($x) {
  global $Block,$TableCellAttr;
  $x = preg_replace('/\\|\\|$/','',$x);
  $td = explode('||',$x); $y='';
  for($i=0;$i<count($td);$i++) {
    if ($td[$i]=='') continue;
    $attr = $TableCellAttr;
    if (preg_match('/^\\s+$/',$td[$i])) $td[$i]='&nbsp;';
    if (preg_match('/^!(.*?)!$/',$td[$i],$match))
      { $td[$i]=$match[1]; $t='caption'; $attr=''; }
    elseif (preg_match('/^!(.*)$/',$td[$i],$match)) 
      { $td[$i]=$match[1]; $t='th'; }
    else $t='td';
    if (preg_match('/^\\s.*\\s$/',$td[$i])) { $attr .= " align='center'"; }
    elseif (preg_match('/^\\s/',$td[$i])) { $attr .= " align='right'"; }
    elseif (preg_match('/\\s$/',$td[$i])) { $attr .= " align='left'"; }
    for ($colspan=1;$i+$colspan<count($td);$colspan++) 
      if ($td[$colspan+$i]!='') break;
    if ($colspan>1) { $attr .= " colspan='$colspan'"; }
    $y .= "<$t $attr>".$td[$i]."</$t>";
  }
  if ($t=='caption') return "<:table,1>$y";
  return "<:table,1><tr>$y</tr>";
}

function LinkIMap($pagename,$imap,$path,$title,$txt,$fmt=NULL) {
  global $IMap,$IMapLinkFmt,$UrlLinkFmt;
  $path = str_replace(' ','%20',$path);
  $FmtV['$LinkUrl'] = str_replace('$1',$path,$IMap[$imap]);
  $FmtV['$LinkText'] = $txt;
  $FmtV['$LinkAlt'] = str_replace(array('"',"'"),array('&#34;','&#39;'),$title);
  if (!$fmt) 
    $fmt = (isset($IMapLinkFmt[$imap])) ? $IMapLinkFmt[$imap] : $UrlLinkFmt;
  return str_replace(array_keys($FmtV),array_values($FmtV),$fmt);
}

function LinkPage($pagename,$imap,$path,$title,$txt,$fmt=NULL) {
  global $QueryFragPattern,$LinkPageExistsFmt,
    $LinkPageCreateSpaceFmt,$LinkPageCreateFmt,$FmtV;
  if (substr($path,0,1)=='#' && !$fmt) {
    $path = preg_replace('/[^-.:\\w]/','',$path);
    return "<a href='#$path'>$txt</a>";
  }
  if (!preg_match("/^([^#?]+)($QueryFragPattern)?$/",$path,$match))
    return '';
  $tgtname = MakePageName($pagename,$match[1]); $qf=@$match[2];
  if (!$fmt) {
    if (PageExists($tgtname)) $fmt=$LinkPageExistsFmt;
    elseif (preg_match('/\\s/',$txt)) $fmt=$LinkPageCreateSpaceFmt;
    else $fmt=$LinkPageCreateFmt;
  }
  $FmtV['$LinkUrl'] = FmtPageName("\$PageUrl$qf",$tgtname);
  $FmtV['$LinkText'] = $txt;
  return FmtPageName($fmt,$tgtname);
}

function MakeLink($pagename,$tgt,$txt=NULL,$suffix=NULL,$fmt=NULL) {
  global $LinkPattern,$LinkFunctions,$UrlExcludeChars,$ImgExtPattern,$ImgTagFmt;
  $t = preg_replace('/[()]/','',trim($tgt));
  preg_match("/^($LinkPattern)?(.+?)(\"(.*)\")?$/",$t,$m);
  if (!$m[1]) $m[1]='<:page>';
  if (preg_match("/(($LinkPattern)([^$UrlExcludeChars]+$ImgExtPattern))(\"(.*)\")?$/",$txt,$tm)) 
    $txt = $LinkFunctions[$tm[2]]($pagename,$tm[2],$tm[3],@$tm[5],
      $tm[1],$ImgTagFmt);
  else {
    if (is_null($txt)) {
      $txt = preg_replace('/\\([^)]*\\)/','',$tgt);
      if ($m[1]=='<:page>') $txt = preg_replace('!^.*/!','',$txt);
    }
    $txt .= $suffix;
  }
  $out = $LinkFunctions[$m[1]]($pagename,$m[1],$m[2],@$m[4],$txt,$fmt);
  return $out;
}

function Markup($id,$cmd,$pat=NULL,$rep=NULL) {
  global $MarkupTable,$MarkupRules;
  unset($MarkupRules);
  if (preg_match('/^([<>])?(.+)$/',$cmd,$m)) {
    $MarkupTable[$id]['cmd']=$cmd;
    $MarkupTable[$m[2]]['dep']=array($id=>$m[1]);
    if (!$m[1]) $m[1]='=';
    if (@$MarkupTable[$m[2]]['seq']) {
      $MarkupTable[$id]['seq'] = $MarkupTable[$m[2]]['seq'].$m[1];
      foreach((array)@$MarkupTable[$id]['dep'] as $i=>$m)
        Markup($i,"$m$id");
      unset($MarkupTable[$id]['dep']);
    }
  }
  if ($pat && !isset($MarkupTable[$id]['pat'])) {
    $MarkupTable[$id]['pat']=$pat;
    $MarkupTable[$id]['rep']=$rep;
  }
}

function mpcmp($a,$b) { return @strcmp($a['seq'].'=',$b['seq'].'='); }
function BuildMarkupRules() {
  global $MarkupTable,$MarkupRules,$LinkPattern;
  if (!$MarkupRules) {
    uasort($MarkupTable,'mpcmp');
    foreach($MarkupTable as $id=>$m) 
      if (@$m['pat']) 
        $MarkupRules[str_replace('\\L',$LinkPattern,$m['pat'])]=$m['rep'];
  }
  return $MarkupRules;
}


function MarkupToHTML($pagename,$text) {
  # convert wiki markup text to HTML output
  global $MarkupRules,$BlockCS,$BlockVS,$K0,$K1,$RedoMarkupLine;

  array_unshift($BlockCS,array()); array_unshift($BlockVS,'');
  $markrules = BuildMarkupRules();
  foreach((array)$text as $l) $lines[] = htmlspecialchars($l,ENT_NOQUOTES);
  $out = array();
  while (count($lines)>0) {
    $x = array_shift($lines);
    $RedoMarkupLine=0;
    foreach($markrules as $p=>$r) {
      if (substr($p,0,1)=='/') $x=preg_replace($p,$r,$x); 
      elseif ($p=='' || strstr($x,$p)!==false) $x=eval($r);
      if (isset($php_errormsg)) { echo "pat=$p"; unset($php_errormsg); }
      if ($RedoMarkupLine) { $lines=array_merge((array)$x,$lines); continue 2; }
    }
    if ($x>'') $out[] = "$x\n";
  }
  $x = Block('block');
  if ($x>'') $out[] = "$x\n";
  array_shift($BlockCS); array_shift($BlockVS);
  return implode('',(array)$out);
}
   
function HandleBrowse($pagename) {
  # handle display of a page
  global $FmtV,$HandleBrowseFmt,$PageStartFmt,$PageEndFmt,$PageRedirectFmt;
  Lock(1);
  $page = RetrieveAuthPage($pagename,'read');
  if (!$page) Abort('?cannot read $pagename');
  SetPage($pagename,$page);
  SDV($PageRedirectFmt,"<p><i>($[redirected from] 
    <a href='\$PageUrl?action=edit'>\$PageName</a>)</i></p>\$HTMLVSpace\n");
  $text = $page['text'];
  if (@!$_GET['from']) {
    $PageRedirectFmt = '';
    if (preg_match('/\\[:redirect\\s+(.+?):\\]/',$text,$match)) {
      $rname = MakePageName($pagename,$match[1]);
      if (PageExists($rname)) Redirect($rname,"\$PageUrl?from=$pagename");
    }
  } else $PageRedirectFmt=FmtPageName($PageRedirectFmt,$_GET['from']);
  $text = '[:groupheader:]'.@$text.'[:groupfooter:]';
  $FmtV['$PageText'] = MarkupToHTML($pagename,$text);
  SDV($HandleBrowseFmt,array(&$PageStartFmt,&$PageRedirectFmt,'$PageText',
    &$PageEndFmt));
  PrintFmt($pagename,$HandleBrowseFmt);
}


function RestorePage($pagename,&$page,&$new,$restore=NULL) {
  if (is_null($restore)) $restore=@$_REQUEST['restore'];
  if (!$restore) return;
  $t = $page['text'];
  $nl = (substr($t,-1)=="\n");
  $t = explode("\n",$t);
  if ($nl) array_pop($t);
  krsort($page); reset($page);
  foreach($page as $k=>$v) {
    if ($k<$restore) break;
    foreach(explode("\n",$v) as $x) {
      if (preg_match('/^(\\d+)(,(\\d+))?([adc])(\\d+)/',$x,$match)) {
        $a1 = $a2 = $match[1];
        if ($match[3]) $a2=$match[3];
        $b1 = $match[5];
        if ($match[4]=='d') array_splice($t,$b1,$a2-$a1+1);
        if ($match[4]=='c') array_splice($t,$b1-1,$a2-$a1+1);
        continue;
      }
      if (substr($x,0,2)=='< ') { $nlflag=true; continue; }
      if (preg_match('/^> (.*)$/',$x,$match)) {
        $nlflag=false;
        array_splice($t,$b1-1,0,$match[1]); $b1++;
      }
      if ($x=='\\ No newline at end of file') $nl=$nlflag;
    }
  }
  if ($nl) $t[]='';
  $new['text']=implode("\n",$t);
  return $new['text'];
}

## ReplaceOnSave performs any text replacements (held in $ROSPatterns)
## on the new text prior to saving the page.
function ReplaceOnSave($pagename,&$page,&$new) {
  global $ROSPatterns;
  if (!@$_POST['post']) return;
  foreach((array)$ROSPatterns as $pat=>$repfmt) 
    $new['text'] = 
      preg_replace($pat,FmtPageName($repfmt,$pagename),$new['text']);
}

function Diff($oldtext,$newtext) {
  global $WorkDir,$SysDiffCmd;
  SDV($SysDiffCmd,"/usr/bin/diff");
  if (!$SysDiffCmd) return '';
  $tempold = tempnam($WorkDir,'old');
  if ($oldfp=fopen($tempold,'w')) { fputs($oldfp,$oldtext); fclose($oldfp); }
  $tempnew = tempnam($WorkDir,'new');
  if ($newfp=fopen($tempnew,'w')) { fputs($newfp,$newtext); fclose($newfp); }
  $diff = '';
  $diff_handle = popen("$SysDiffCmd $tempold $tempnew",'r');
  if ($diff_handle) {
    while (!feof($diff_handle)) $diff .= fread($diff_handle,4096);
    pclose($diff_handle);
  }
  @unlink($tempold); @unlink($tempnew);
  return $diff;
} 

function PostPage($pagename,&$page,&$new) {
  global $DiffKeepDays,$DiffFunction,$DeleteKeyPattern,
    $Now,$Author,$WikiDir,$IsPagePosted;
  SDV($DiffKeepDays,3650);
  SDV($DiffFunction,'Diff');
  SDV($DeleteKeyPattern,"^\\s*delete\\s*$");
  if (@$_REQUEST['post']) {
    if ($new['text']==$page['text']) { Redirect($pagename); return; }
    $new["author"]=@$Author;
    $new["author:$Now"] = @$Author;
    $new["host:$Now"] = $_SERVER['REMOTE_ADDR'];
    $diffclass = preg_replace('/\\W/','',@$_POST['diffclass']);
    if ($page["time"]>0) 
      $new["diff:$Now:{$page['time']}:$diffclass"] =
        $DiffFunction($new['text'],$page['text']);
    $keepgmt = $Now-$DiffKeepDays * 86400;
    $keys = array_keys($new);
    foreach($keys as $k)
      if (preg_match("/^\\w+:(\\d+)/",$k,$match) && $match[1]<$keepgmt)
        unset($new[$k]);
    if (preg_match("/$DeleteKeyPattern/",$new['text']))
      $WikiDir->delete($pagename);
    else WritePage($pagename,$new);
    $IsPagePosted=true;
  }
}

function PostRecentChanges($pagename,&$page,&$new) {
  global $IsPagePosted,$RecentChangesFmt,$RCDelimPattern;
  if (!$IsPagePosted) return;
  foreach($RecentChangesFmt as $rcfmt=>$pgfmt) {
    $rcname = FmtPageName($rcfmt,$pagename);  if (!$rcname) continue;
    $pgtext = FmtPageName($pgfmt,$pagename);  if (!$pgtext) continue;
    if (@$seen[$rcname]++) continue;
    $rcpage = ReadPage($rcname,'');
    $rcelim = preg_quote(preg_replace("/$RCDelimPattern.*$/",' ',$pgtext),'/');
    $rcpage['text'] = preg_replace("/[^\n]*$rcelim.*\n/","",$rcpage['text']);
    if (!preg_match("/$RCDelimPattern/",$rcpage['text'])) 
      $rcpage['text'] .= "$pgtext\n";
    else
      $rcpage['text'] = preg_replace("/([^\n]*$RCDelimPattern.*\n)/",
        "$pgtext\n$1",$rcpage['text'],1);
    WritePage($rcname,$rcpage);
  }
}

function PreviewPage($pagename,&$page,&$new) {
  global $IsPageSaved,$FmtV,$PagePreviewFmt;
  if (!$IsPageSaved && @$_REQUEST['preview']) {
    $text = '[:groupheader:]'.$new['text'].'[:groupfooter:]';
    $FmtV['$PreviewText'] = MarkupToHTML($pagename,$text);
  } else $PagePreviewFmt = '';
}
  
function HandleEdit($pagename) {
  global $IsPagePosted,$EditFields,$EditFunctions,$FmtV,$Now,
    $HandleEditFmt,$PageStartFmt,$PageEditFmt,$PagePreviewFmt,$PageEndFmt;
  $IsPagePosted = false;
  Lock(2);
  $page = RetrieveAuthPage($pagename,'edit');
  if (!$page) Abort("?cannot edit $pagename"); 
  SetPage($pagename,$page);
  $new = $page;
  foreach((array)$EditFields as $k) 
    if (isset($_POST[$k])) $new[$k]=str_replace("\r",'',stripmagic($_POST[$k]));
  foreach((array)$EditFunctions as $fn) $fn($pagename,$page,$new);
  if ($IsPagePosted) { Redirect($pagename); return; }
  $FmtV['$EditText'] = 
    str_replace('$','&#036;',htmlspecialchars($new['text'],ENT_NOQUOTES));
  $FmtV['$EditBaseTime'] = $Now;
  SDV($HandleEditFmt,array(&$PageStartFmt,
    &$PageEditFmt,&$PagePreviewFmt,&$PageEndFmt));
  PrintFmt($pagename,$HandleEditFmt);
}

function HandleSource($pagename) {
  Lock(1);
  $page = RetrieveAuthPage($pagename,'read');
  if (!$page) Abort("?cannot source $pagename");
  header("Content-type: text/plain");
  echo $page['text'];
}

## BasicAuth provides password-protection of pages using HTTP Basic
## Authentication.  It is normally called from RetrieveAuthPage.
function BasicAuth($pagename,$level,$authprompt=true,$dtext=NULL) {
  global $AuthRealmFmt,$AuthDeniedFmt,$DefaultPasswords,
    $AllowPassword,$GroupAttributesFmt;
  SDV($GroupAttributesFmt,'$Group/GroupAttributes');
  SDV($AllowPassword,'nopass');
  SDV($AuthRealmFmt,$GLOBALS['WikiTitle']);
  SDV($AuthDeniedFmt,'A valid password is required to access this feature.');
  $page = ReadPage($pagename,$dtext);
  if (!$page) { return false; }
  $passwd = @$page["passwd$level"];
  if ($passwd=="") { 
    $grouppg = ReadPage(FmtPageName($GroupAttributesFmt,$pagename));
    $passwd = @$grouppg["passwd$level"];
    if ($passwd=='') $passwd = @$DefaultPasswords[$level];
    if ($passwd=='') $passwd = @$page["passwdread"];
    if ($passwd=='') $passwd = @$grouppg["passwdread"];
    if ($passwd=='') $passwd = @$DefaultPasswords['read'];
  }
  if ($passwd=='') return $page;
  if (crypt($AllowPassword,$passwd)==$passwd) return $page;
  foreach (array_merge((array)$DefaultPasswords['admin'],(array)$passwd) as $pw)
    if (@crypt($_SERVER['PHP_AUTH_PW'],$pw)==$pw) return $page;
  if (!$authprompt) return false;
  $realm=FmtPageName($AuthRealmFmt,$pagename);
  header("WWW-Authenticate: Basic realm=\"$realm\"");
  header("Status: 401 Unauthorized");
  header("HTTP-Status: 401 Unauthorized");
  PrintFmt($pagename,$AuthDeniedFmt);
  exit;
}

function PrintAttrForm($pagename) {
  global $PageAttributes;
  echo FmtPageName("<form action='\$PageUrl' method='post'>
    <input type='hidden' name='action' value='postattr' />
    <input type='hidden' name='pagename' value='\$PageName' />
    <table>",$pagename);
  foreach($PageAttributes as $attr=>$p) {
    $value = (substr($attr,0,6)=='passwd') ? '' : $page[$k];
    $prompt = FmtPageName($p,$pagename);
    echo "<tr><td>$prompt</td>
      <td><input type='text' name='$attr' value='$value' /></td></tr>";
  }
  echo "</table><input type='submit' /></form>";
}

function HandleAttr($pagename) {
  global $PageAttrFmt;
  $page = RetrieveAuthPage($pagename,'attr');
  if (!$page) { Abort("?unable to read $pagename"); }
  SetPage($pagename,$page);
  SDV($PageAttrFmt,"<h1 class='wikiaction'>$[\$PageName Attributes]</h1>
    <p>Enter new attributes for this page below.  Leaving a field blank
    will leave the attribute unchanged.  To clear an attribute, enter
    'clear'.</p>");
  SDV($HandleAttrFmt,array(&$PageStartFmt,&$PageAttrFmt,
    'function:PrintAttrForm',&$PageEndFmt));
  PrintFmt($pagename,$HandleAttrFmt);
}

function HandlePostAttr($pagename) {
  global $PageAttributes;
  $page = RetrieveAuthPage($pagename,'attr');
  if (!$page) { Abort("?unable to read $pagename"); }
  foreach($PageAttributes as $attr=>$p) {
    $newpw = @$_POST[$attr];
    if ($newpw=='clear') unset($page[$attr]);
    else if ($newpw>'') $page[$attr]=crypt($newpw);
  }
  WritePage($pagename,$page);
  Redirect($pagename);
  exit;
} 

?> 
