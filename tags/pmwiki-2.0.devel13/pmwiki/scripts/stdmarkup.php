<?php if (!defined('PmWiki')) exit();
/*  Copyright 2004 Patrick R. Michaud (pmichaud@pobox.com)
    This file is part of PmWiki; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.  See pmwiki.php for full details.

    This script defines PmWiki's standard markup.  It is automatically
    included from stdconfig.php unless $EnableStdMarkup==0.

    Each call to Markup() below adds a new rule to PmWiki's translation
    engine (unless a rule with the same name has already been defined).  
    The form of the call is Markup($id,$where,$pat,$rep); 
    $id is a unique name for the rule, $where is the position of the rule
    relative to another rule, $pat is the pattern to look for, and
    $rep is the string to replace it with.
*/

## first we preserve text in [=...=] and [@...@]
Markup('[=','_begin','/\\[([=@])(.*?)\\1\\]/se',
    "Keep(\$K0['$1'].PSS('$2').\$K1['$1'])");
Markup('restore','<_end',"/$KeepToken(\\d.*?)$KeepToken/e",
    '$GLOBALS[\'KPV\'][\'$1\']');

## remove carriage returns before preserving text
Markup('\\r','<[=','/\\r/','');

# ${var} substitutions
Markup('${fmt}','>[=',
  '/{\\$((Group|Name)(spaced)?|LastModified(By|Host)?)}/e',
  "FmtPageName('$$1',\$pagename)");
Markup('${var}','>${fmt}',
  '/{\\$(Version|Author|UrlPage|DefaultName|DefaultGroup)}/e',
  "\$GLOBALS['$1']");
Markup('if','fulltext',"\\[:(if[^\n]*?):\\](.*?)(?=\\[:if[^\n]*:\\]|$)/se",
  "CondText(\$pagename,PSS('$1'),PSS('$2'))");

## [:include:]
Markup('include','>if',"/\\[:(include\\s+.+?):\\]/e",
  "PRR().IncludeText(\$pagename,'$1')");

## GroupHeader/GroupFooter handling
Markup('nogroupheader','>include','/\\[:nogroupheader:\\]/e',
  "PZZ(\$GLOBALS['GroupHeaderFmt']='')");
Markup('nogroupfooter','>include','/\\[:nogroupfooter:\\]/e',
  "PZZ(\$GLOBALS['GroupFooterFmt']='')");
Markup('groupheader','>nogroupheader','/\\[:groupheader:\\]/e',
  "PRR().FmtPageName(\$GLOBALS['GroupHeaderFmt'],\$pagename)");
Markup('groupfooter','>nogroupfooter','/\\[:groupfooter:\\]/e',
  "PRR().FmtPageName(\$GLOBALS['GroupFooterFmt'],\$pagename)");

## [:nl:]
Markup('nl0','<split',"/(?!\n)\\[:nl:\\](?!\n)/","\n");
Markup('nl1','>nl0',"/\\[:nl:\\]/",'');

## \\$  (end of line joins)
Markup('\\$','>nl1',"/(\\\\*)\\\\\n/e",
  "Keep(' '.str_repeat('<br />',strlen('$1')))");

## [:noheader:],[:nofooter:],[:notitle:]...
Markup('noheader','directives','/\\[:noheader:\\]/e',
  "PZZ(\$GLOBALS['PageHeaderFmt']='')");
Markup('nofooter','directives','/\\[:nofooter:\\]/e',
  "PZZ(\$GLOBALS['PageFooterFmt']='')");
Markup('notitle','directives','/\\[:notitle:\\]/e',
  "PZZ(\$GLOBALS['PageTitleFmt']='')");

## [:title:]
Markup('title','directives','/\\[:title\\s(.*?):\\]/e',
  "PZZ(\$GLOBALS['PCache'][\$pagename]['title']=PSS('$1'))");

## [:comment:]
Markup('comment','directives','/\\[:comment .*?:\\]/','');

## [:spacewikiwords:]
Markup('spacewikiwords','directives','/\\[:(no)?spacewikiwords:\\]/e',
  "PZZ(\$GLOBALS['SpaceWikiWords']=('$1'!='no'))");

## [:linkwikiwords:]
Markup('linkwikiwords','directives','/\\[:(no)?linkwikiwords:\\]/e',
  "PZZ(\$GLOBALS['LinkWikiWords']=('$1'!='no'))");

#### inline markups ####
## character entities
Markup('&','directives','/&amp;([A-Za-z0-9]+;|#\\d+;|#[xX][A-Fa-f0-9]+;)/',
  '&$1');

## ''emphasis''
Markup("''",'inline',"/''(.*?)''/",'<em>$1</em>');

## '''strong'''
Markup("'''","<''","/'''(.*?)'''/",'<strong>$1</strong>');

## '''''strong emphasis'''''
Markup("'''''","<'''","/'''''(.*?)'''''/",'<strong><em>$1</em></strong>');

## @@code@@
Markup('@@','inline','/@@(.*?)@@/','<code>$1</code>');

## '+big+', '-small-'
Markup("'+",'inline',"/'\\+(.*?)\\+'/",'<big>$1</big>');
Markup("'-",'inline',"/'\\-(.*?)\\-'/",'<small>$1</small>');

## '^superscript^', '_subscript_'
Markup("'^",'inline',"/'\\^(.*?)\\^'/",'<sup>$1</sup>');
Markup("'_",'inline',"/'_(.*?)_'/",'<sub>$1</sub>');

## [+big+], [-small-]
Markup('[+','inline','/\\[(([-+])+)(.*?)\\1\\]/e',
  "'<span style=\'font-size:'.(round(pow(1.2,$2strlen('$1'))*100,0)).'%\'>'.
    PSS('$3</span>')");

## {+ins+}, {-del-}
Markup('{+','inline','/\\{\\+(.*?)\\+\\}/','<ins>$1</ins>');
Markup('{-','inline','/\\{-(.*?)-\\}/','<del>$1</del>');

## [[<<]] (break)
Markup('[[<<]]','inline','/\\[\\[&lt;&lt;\\]\\]/',"<br clear='all' />");

###### Links ######
## [[free links]]
Markup('[[','links',"/\\[\\[(.+?)\\]\\]($SuffixPattern)/e",
  "Keep(MakeLink(\$pagename,PSS('$1'),NULL,'$2'),'L')");

## [[target | text]]
Markup('[[|','<[[',"/\\[\\[([^|\\]]+)\\|(.*?)\\s*\\]\\]($SuffixPattern)/e",
  "Keep(MakeLink(\$pagename,PSS('$1'),PSS('$2'),'$3'),'L')");

## [[text -> target ]]
Markup('[[->',
  '>[[|',"/\\[\\[([^\\]]+?)\\s*-+&gt;\\s*(.*?)\\]\\]($SuffixPattern)/e",
  "Keep(MakeLink(\$pagename,PSS('$2'),PSS('$1'),'$3'),'L')");

## [[#anchor]]
Markup('[[#','<[[','/\\[\\[#([A-Za-z][-.:\\w]*)\\]\\]/e',
  "Keep(\"<a name='$1' id='$1'></a>\",'L')");

## bare urllinks 
Markup('urllink','>[[',
  "/\\b(\\L)[^\\s$UrlExcludeChars]*[^\\s.,?!$UrlExcludeChars]/e",
  "Keep(MakeLink(\$pagename,'$0','$0'),'L')");

## mailto: links 
Markup('mailto','<urllink',
  "/\\bmailto:([^\\s$UrlExcludeChars]*[^\\s.,?!$UrlExcludeChars])/e",
  "Keep(MakeLink(\$pagename,'$0','$1'),'L')");

## inline images
Markup('img','<urllink',
  "/\\b(\\L)([^\\s$UrlExcludeChars]+$ImgExtPattern)(\"([^\"]*)\")?/e",
  "Keep(\$GLOBALS['LinkFunctions']['$1'](\$pagename,'$1','$2','$4','$1$2',
    \$GLOBALS['ImgTagFmt']),'L')");

## bare wikilinks
Markup('wikilink','>urllink',"/\\b($GroupPattern([\\/.]))?($WikiWordPattern)/e",
  "Keep(WikiLink(\$pagename,'$0'),'L')");

## escaped `WikiWords
Markup('`wikiword','<wikilink',"/`($WikiWordPattern)/e","Keep('$1')");

#### Block markups ####
## process any <:...> markup
Markup('^<:','>block','/^(<:([^>]+)>)?/e',"Block('$2')");

## bullet lists
Markup('^*','block','/^(\\*+)/','<:ul,$1>');

## numbered lists
Markup('^#','block','/^(#+)/','<:ol,$1>');

## indented (->) /hanging indent (-<) text
Markup('^->','block','/^(-+)&gt;/','<:indent,$1>');
Markup('^-<','block','/^(-+)&lt;/','<:outdent,$1>');

## definition lists
Markup('^::','block','/^(:+)([^:]+):/','<:dl,$1><dt>$2</dt><dd>');

## preformatted text
Markup('^ ','block','/^(\\s)/','<:pre,1>');

## blank lines
Markup('blank','<^ ','/^\\s*$/','<:vspace>');

## tables
## ||cell||, ||!header cell||, ||!caption!||
Markup('^||||','block','/^\\|\\|.*\\|\\|.*$/e',"FormatTableRow(PSS('$0'))");
## ||table attributes
Markup('^||','>^||||','/^\\|\\|(.*)$/e',
  "PZZ(\$GLOBALS['BlockMarkups']['table'][0] = PSS('<table $1>'))");

## headers
Markup('^!','block','/^(!{1,6})(.*)$/e',
  "'<:block><h'.strlen('$1').PSS('>$2</h').strlen('$1').'>'");

## horiz rule
Markup('^----','>^->','/^----+/','<:block><hr />');

#### [:table:] markup (AdvancedTables)

function Cells($name,$attr) {
  global $MarkupFrame;
  $attr = preg_replace('/([a-zA-Z]=)([^\'"]\\S*)/',"\$1'\$2'",$attr);
  $tattr = @$MarkupFrame[0]['tattr'];
  if ($name == 'cell' || $name == 'cellnr') {
    if (!@$MarkupFrame[0]['posteval']['cells']) {
      $MarkupFrame[0]['posteval']['cells'] = "return Cells('','');";
      return "<:block><table $tattr><tr><td $attr>";
    } else if ($name == 'cellnr') return "<:block></td></tr><tr><td $attr>";
    return "<:block></td><td $attr>";
  }
  $MarkupFrame[0]['tattr'] = $attr;
  if (@$MarkupFrame[0]['posteval']['cells']) {
    unset($MarkupFrame[0]['posteval']['cells']);
    return '<:block></td></tr></table>';
  }
  return '<:block>';
}

Markup('^table','<block','/^\\[:(table|cell|cellnr|tableend)(\\s.*?)?:\\]/e',
  "Cells('$1',PSS('$2'))");


#### special stuff ####
## [:markup:] for displaying markup examples
Markup('markup','<[=',"/\n\\[:markup:\\]\\s*\\[=(.*?)=\\]/se",
  "'\n'.Keep('<div class=\"markup\" <pre>'.wordwrap(PSS('$1'),60).
    '</pre>').PSS('\n$1\n<:block,0></div>\n')");
$HTMLStylesFmt['markup'] = "
  div.markup { border:2px dotted #ccf; 
    margin-left:30px; margin-right:30px; 
    padding-left:10px; padding-right:10px; }
  div.markup pre { border-bottom:1px solid #ccf; 
    padding-top:10px; padding-bottom:10px; }
  ";

?>
