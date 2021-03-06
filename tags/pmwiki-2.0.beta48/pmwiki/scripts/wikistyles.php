<?php if (!defined('PmWiki')) exit();
/*  Copyright 2004 Patrick R. Michaud (pmichaud@pobox.com)
    This file is part of PmWiki; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.  See pmwiki.php for full details.
*/

## %% markup
Markup('%%','style','%','return ApplyStyles($x);');
## restore links before applying styles
Markup('restorelinks','<%%',"/$KeepToken(\\d+L)$KeepToken/e",
  '$GLOBALS[\'KPV\'][\'$1\']');
## Place a closing %% at the end of any line with a (possible) WikiStyle in it
#Markup('%%$','<block','/([^%]%[^%]+?)$/','$1 %%');

# define PmWiki's standard/default wikistyles
if (IsEnabled($EnableStdWikiStyles,1)) {
  ## standard colors
  foreach(array('black','white','red','yellow','blue','gray',
      'silver','maroon','green','navy','purple') as $c)
    SDV($WikiStyle[$c]['color'],$c);
  ## %newwin% style opens links in a new window
  SDV($WikiStyle['newwin']['target'],'_blank');
  ## %comment% style turns markup into a comment via display:none css
  SDV($WikiStyle['comment']['display'],'none');
  ## display, margin, padding, and border css properties
  $WikiStyleCSS[] = 
    'display|(margin|padding|border)(-(left|right|top|bottom))?';
  ## list-styles
  $WikiStyleCSS[] = 'list-style';
  foreach(array('decimal'=>'decimal', 'roman'=>'lower-roman',
    'ROMAN'=>'upper-roman', 'alpha'=>'lower-alpha', 'ALPHA'=>'upper-alpha')
    as $k=>$v) 
      SDV($WikiStyle[$k],array('apply'=>'list','list-style'=>$v));
  ## apply ranges
  SDVA($WikiStyleApply,array(
    'item' => 'li|dt',
    'list' => 'ul|ol|dl',
    'div' => 'div',
    'block' => 'p(?!\\sclass=)|div|ul|ol|dl|li|dt|pre|h[1-6]',
    'p' => 'p(?!\\sclass=)'));
  foreach(array('item', 'list', 'block', 'p', 'div') as $c)
    SDV($WikiStyle[$c],array('apply'=>$c));
  ## block justifications
  foreach(array('left','right','center') as $c)
    SDV($WikiStyle[$c],array('apply'=>'block','text-align'=>$c));
}

SDV($WikiStylePattern,'%%|%[A-Za-z][-,=:#\\w\\s\'"]*%');

SDVA($WikiStyleAttr,array(
  'height' => 'img',
  'width' => 'img',
  'vspace' => 'img',
  'hspace' => 'img',
  'align' => 'img',
  'value' => 'li',
  'target' => 'a',
  'accesskey' => 'a',
  'rel' => 'a'));

SDVA($WikiStyleRepl,array('/\\bbgcolor([:=])/' => 'background-color$1'));

$WikiStyleCSS[] = 'color|background-color';
$WikiStyleCSS[] = 'text-align|text-decoration';
$WikiStyleCSS[] = 'font-size|font-family|font-weight|font-style';

function ApplyStyles($x) {
  global $UrlExcludeChars, $WikiStylePattern, $WikiStyleRepl, $WikiStyle,
    $WikiStyleAttr, $WikiStyleCSS, $WikiStyleApply, $KeepToken, $KPV;
  $x = preg_replace("/\\bhttps?:[^$UrlExcludeChars]+/e", "Keep('$0')", $x);
  $parts = preg_split("/($WikiStylePattern)/",$x,-1,PREG_SPLIT_DELIM_CAPTURE);
  $parts[] = NULL;
  $out = array();
  $style = array();
  $wikicsspat = '/^('.implode('|',(array)$WikiStyleCSS).')$/';
  while ($parts) {
    $p = array_shift($parts);
    if (preg_match("/^$WikiStylePattern\$/",$p)) {
      $WikiStyle['curr']=$style; $style=array();
      foreach((array)$WikiStyleRepl as $pat=>$rep) 
        $p=preg_replace($pat,$rep,$p);
      preg_match_all('/\\b([a-zA-Z][-\\w]*)([:=]([-#,\\w]+|([\'"]).*?\\4))?/',
        $p,$match,PREG_SET_ORDER);
      while ($match) {
        $m = array_shift($match);
        if (@$m[2]) $style[$m[1]]=preg_replace('/^([\'"])(.*)\\1$/','$2',$m[3]);
        else if (!isset($WikiStyle[$m[1]])) $style['class']=$m[1];
        else $style=array_merge($style,(array)$WikiStyle[$m[1]]);
      }
      if (@$style['define']) {
        $d = $style['define']; unset($style['define']);
        $WikiStyle[$d] = $style;
      }
      if (@$WikiStyleApply[$style['apply']]) {
        $apply[$style['apply']] = 
          array_merge((array)@$apply[$style['apply']],$style);
        $style=array();
      }
      continue;
    }
    if (is_null($p)) 
      { $alist=@$apply; unset($alist['']); $p=implode('',$out); $out=array(); }
    elseif ($p=='') continue;
    else { $alist=array(''=>$style); }
    foreach((array)$alist as $a=>$s) {
      $classv=array(); $stylev=array(); $id='';
      foreach((array)$s as $k=>$v) {
        if (@$WikiStyleAttr[$k]) 
          $p=preg_replace("/<({$WikiStyleAttr[$k]}(?![^>]*\\s$k=))([^>]*)>/s",
            "<$1 $k='$v' $2>",$p);
        elseif ($k=='class') $classv[]=$v;
        elseif ($k=='id') $id = preg_replace('/\W/', '_', $v);
        elseif (preg_match($wikicsspat,$k)) $stylev[]="$k: $v;";
      }
      $spanattr=''; 
      if ($classv) $spanattr="class='".implode(' ',$classv)."' ";
      if ($stylev) $spanattr.="style='".implode(' ',$stylev)."' ";
      if ($id) $spanattr.="id='$id' ";
      if ($spanattr) {
        if (!@$WikiStyleApply[$a]) {
          $p = preg_replace("!^(.*?)($|</?(form|div|table|tr|td|th|p|ul|ol|dl|li|dt|dd|h[1-6]|blockquote|pre|hr))!s", "<span $spanattr>$1</span>$2", $p, 1);
}
        elseif (!preg_match('/^(\\s*<[^>]+>)*$/s',$p)) 
          $p = preg_replace("/<({$WikiStyleApply[$a]})\\b/","<$1 $spanattr",$p);
      }
      if ($s['color'])
        $p = preg_replace('/<a\\b/', "<a style='color: {$s['color']}'", $p);
    }
    $out[] = $p;
  }
  return implode('',$out);
}

