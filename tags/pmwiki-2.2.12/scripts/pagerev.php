<?php if (!defined('PmWiki')) exit();
/*  Copyright 2004-2010 Patrick R. Michaud (pmichaud@pobox.com)
    This file is part of PmWiki; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.  See pmwiki.php for full details.

    This script defines routines for displaying page revisions.  It
    is included by default from the stdconfig.php script.
*/

function LinkSuppress($pagename,$imap,$path,$title,$txt,$fmt=NULL) 
  { return $txt; }

SDV($DiffShow['minor'],(@$_REQUEST['minor']!='n')?'y':'n');
SDV($DiffShow['source'],(@$_REQUEST['source']=='y')?'y':'n');
SDV($DiffMinorFmt, ($DiffShow['minor']=='y') ?
  "<a href='{\$PageUrl}?action=diff&amp;source=".$DiffShow['source']."&amp;minor=n'>$[Hide minor edits]</a>" :
  "<a href='{\$PageUrl}?action=diff&amp;source=".$DiffShow['source']."&amp;minor=y'>$[Show minor edits]</a>" );
SDV($DiffSourceFmt, ($DiffShow['source']=='y') ?
  "<a href='{\$PageUrl}?action=diff&amp;source=n&amp;minor=".$DiffShow['minor']."'>$[Show changes to output]</a>" :
  "<a href='{\$PageUrl}?action=diff&amp;source=y&amp;minor=".$DiffShow['minor']."'>$[Show changes to markup]</a>");
SDV($PageDiffFmt,"<h2 class='wikiaction'>$[{\$FullName} History]</h2>
  <p>$DiffMinorFmt - $DiffSourceFmt</p>
  ");
SDV($DiffStartFmt,"
      <div class='diffbox'><div class='difftime'><a name='diff\$DiffGMT' href='#diff\$DiffGMT'>\$DiffTime</a>
        \$[by] <span class='diffauthor' title='\$DiffHost'>\$DiffAuthor</span> - \$DiffChangeSum</div>");
SDV($DiffDelFmt['a'],"
        <div class='difftype'>\$[Deleted line \$DiffLines:]</div>
        <div class='diffdel'>");
SDV($DiffDelFmt['c'],"
        <div class='difftype'>\$[Changed line \$DiffLines from:]</div>
        <div class='diffdel'>");
SDV($DiffAddFmt['d'],"
        <div class='difftype'>\$[Added line \$DiffLines:]</div>
        <div class='diffadd'>");
SDV($DiffAddFmt['c'],"</div>
        <div class='difftype'>$[to:]</div>
        <div class='diffadd'>");
SDV($DiffEndDelAddFmt,"</div>");
SDV($DiffEndFmt,"</div>");
SDV($DiffRestoreFmt,"
      <div class='diffrestore'><a href='{\$PageUrl}?action=edit&amp;restore=\$DiffId&amp;preview=y'>$[Restore]</a></div>");

SDV($HandleActions['diff'], 'HandleDiff');
SDV($HandleAuth['diff'], 'read');
SDV($ActionTitleFmt['diff'], '| $[History]');
SDV($HTMLStylesFmt['diff'], "
  .diffbox { width:570px; border-left:1px #999999 solid; margin-top:1.33em; }
  .diffauthor { font-weight:bold; }
  .diffchangesum { font-weight:bold; }
  .difftime { font-family:verdana,sans-serif; font-size:66%; 
    background-color:#dddddd; }
  .difftype { clear:both; font-family:verdana,sans-serif; 
    font-size:66%; font-weight:bold; }
  .diffadd { border-left:5px #99ff99 solid; padding-left:5px; }
  .diffdel { border-left:5px #ffff99 solid; padding-left:5px; }
  .diffrestore { clear:both; font-family:verdana,sans-serif; 
    font-size:66%; margin:1.5em 0px; }
  .diffmarkup { font-family:monospace; } ");

function PrintDiff($pagename) {
  global $DiffHTMLFunction,$DiffShow,$DiffStartFmt,$TimeFmt,
    $DiffEndFmt,$DiffRestoreFmt,$FmtV, $LinkFunctions;
  $page = ReadPage($pagename);
  if (!$page) return;
  krsort($page); reset($page);
  $lf = $LinkFunctions;
  $LinkFunctions['http:'] = 'LinkSuppress';
  $LinkFunctions['https:'] = 'LinkSuppress';
  SDV($DiffHTMLFunction, 'DiffHTML');
  foreach($page as $k=>$v) {
    if (!preg_match("/^diff:(\d+):(\d+):?([^:]*)/",$k,$match)) continue;
    $diffclass = $match[3];
    if ($diffclass=='minor' && $DiffShow['minor']!='y') continue;
    $diffgmt = $FmtV['$DiffGMT'] = $match[1];
    $FmtV['$DiffTime'] = strftime($TimeFmt,$diffgmt);
    $diffauthor = @$page["author:$diffgmt"]; 
    if (!$diffauthor) @$diffauthor=$page["host:$diffgmt"];
    if (!$diffauthor) $diffauthor="unknown";
    $FmtV['$DiffChangeSum'] = htmlspecialchars(@$page["csum:$diffgmt"]);
    $FmtV['$DiffHost'] = @$page["host:$diffgmt"];
    $FmtV['$DiffAuthor'] = $diffauthor;
    $FmtV['$DiffId'] = $k;
    $html = $DiffHTMLFunction($pagename, $v);
    if(!$html) continue;
    echo FmtPageName($DiffStartFmt,$pagename);
    echo $html;
    echo FmtPageName($DiffEndFmt,$pagename);
    echo FmtPageName($DiffRestoreFmt,$pagename);
  }
  $LinkFunctions = $lf;
}

# This function converts a single diff entry from the wikipage file
# into HTML, ready for display.
function DiffHTML($pagename, $diff) {
  global $FmtV, $DiffShow, $DiffAddFmt, $DiffDelFmt, $DiffEndDelAddFmt,
  $DiffRenderSourceFunction;
  SDV($DiffRenderSourceFunction, 'DiffRenderSource');
  $difflines = explode("\n",$diff."\n");
  $in=array(); $out=array(); $dtype=''; $html = '';
  foreach($difflines as $d) {
    if ($d>'') {
      if ($d[0]=='-' || $d[0]=='\\') continue;
      if ($d[0]=='<') { $out[]=substr($d,2); continue; }
      if ($d[0]=='>') { $in[]=substr($d,2); continue; }
    }
    if (preg_match("/^(\\d+)(,(\\d+))?([adc])(\\d+)(,(\\d+))?/",
        $dtype,$match)) {
      if (@$match[7]>'') {
        $lines='lines';
        $count=$match[1].'-'.($match[1]+$match[7]-$match[5]);
      } elseif ($match[3]>'') {
        $lines='lines'; $count=$match[1].'-'.$match[3];
      } else { $lines='line'; $count=$match[1]; }
      if ($match[4]=='a' || $match[4]=='c') {
        $txt = str_replace('line',$lines,$DiffDelFmt[$match[4]]);
        $FmtV['$DiffLines'] = $count;
        $html .= FmtPageName($txt,$pagename);
        if ($DiffShow['source']=='y') 
          $html .= "<div class='diffmarkup'>"
            .$DiffRenderSourceFunction($in, $out, 0)
            ."</div>";
        else $html .= MarkupToHTML($pagename,
          preg_replace('/\\(:.*?:\\)/e',"Keep(htmlspecialchars(PSS('$0')))", join("\n",$in)));
      }
      if ($match[4]=='d' || $match[4]=='c') {
        $txt = str_replace('line',$lines,$DiffAddFmt[$match[4]]);
        $FmtV['$DiffLines'] = $count;
        $html .= FmtPageName($txt,$pagename);
        if ($DiffShow['source']=='y') 
          $html .= "<div class='diffmarkup'>"
            .$DiffRenderSourceFunction($in, $out, 1)
            ."</div>";
        else $html .= MarkupToHTML($pagename,
          preg_replace('/\\(:.*?:\\)/e',"Keep(htmlspecialchars(PSS('$0')))",join("\n",$out)));
      }
      $html .= FmtPageName($DiffEndDelAddFmt,$pagename);
    }
    $in=array(); $out=array(); $dtype=$d;
  }
  return $html;
}
# Such parametrizable function allows custom diff rendering (inline...)
function DiffRenderSource($in, $out, $x) {
  $a = $x? $out : $in;
  return str_replace("\n","<br />",htmlspecialchars(join("\n",$a)));
}
function HandleDiff($pagename, $auth='read') {
  global $HandleDiffFmt, $PageStartFmt, $PageDiffFmt, $PageEndFmt;
  $page = RetrieveAuthPage($pagename, $auth, true);
  if (!$page) { Abort("?cannot diff $pagename"); }
  PCache($pagename, $page);
  SDV($HandleDiffFmt,array(&$PageStartFmt,
    &$PageDiffFmt,"<div id='wikidiff'>", 'function:PrintDiff', '</div>',
    &$PageEndFmt));
  PrintFmt($pagename,$HandleDiffFmt);
}

##### Functions for simple word-diff (written by Petko Yotov)
if(IsEnabled($EnableDiffInline, 0)) {
  $DiffRenderSourceFunction = 'DiffRenderInline';
  SDV($HTMLStylesFmt['diffinline'], " 
    .diffmarkup del { background:#fdd; }
    .diffmarkup ins { background:#dfd; }");
}
## Split a line into pieces before passing it through `diff`
function DiffPrepareInline($x) { 
  global $DiffSplitInlineDelims;
  SDV($DiffSplitInlineDelims, "-@!?#$%^&*()=+[]{}.'\"\\:|,<>");
  $y = preg_split("/([".preg_quote($DiffSplitInlineDelims)."\\s])/", 
    $x, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
  return implode("\n", $y);
}
function DiffRenderInline($in, $out, $which) {
  global $DiffFunction;
  $linesx = $linesy = array();
  for($i=0; $i<max(count($in), count($out)); $i++) {
    $x = DiffPrepareInline($in[$i]);
    $y = DiffPrepareInline($out[$i]);
    $z = $DiffFunction($x, $y);

    $x2 = split("\n", htmlspecialchars("\n$x"));
    $y2 = split("\n", htmlspecialchars("\n$y"));
    foreach (split("\n", $z) as $zz) {
      if (preg_match('/^(\\d+)(,(\\d+))?([adc])(\\d+)(,(\\d+))?/',$zz,$m)) {
        $a1 = $a2 = $m[1];
        if ($m[3]) $a2=$m[3];
        $b1 = $b2 = $m[5];
        if ($m[7]) $b2=$m[7];

        if($m[4]=='c'||$m[4]=='d') {
          $x2[$a1] = '<del>'. $x2[$a1];
          $x2[$a2] .= '</del>';
        }
        if($m[4]=='c'||$m[4]=='a') {
          $y2[$b1] = '<ins>'.$y2[$b1];
          $y2[$b2] .= '</ins>';
        }
      }
    }
    $linesx[] = implode('', $x2);
    $linesy[] = implode('', $y2);
  }
  $ret = trim(implode("\n", ($which? $linesy : $linesx)));
  return str_replace("\n","<br />",$ret);
}
