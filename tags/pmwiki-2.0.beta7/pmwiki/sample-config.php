<?php if (!defined('PmWiki')) exit();
##  This is a sample config.php file.  To use this file, copy it to
##  local/config.php, then edit it for whatever customizations you want.
##  Also, be sure to take a look at http://www.pmichaud.com/wiki/Cookbook
##  for more details on the types of customizations that can be added
##  to PmWiki.

##  $WikiTitle is the name that appears in the browser's title bar.
$WikiTitle = 'PmWiki';

##  $ScriptUrl is your preferred URL for accessing wiki pages
##  $PubDirUrl is the URL for the pub directory.
# $ScriptUrl = 'http://www.mydomain.com/path/to/pmwiki.php';
# $PubDirUrl = 'http://www.mydomain.com/path/to/pub';

##  If your system is able to display the home page but gives
##  "404 Not Found" errors for all others, try setting the following:
# $EnablePathInfo = 0;

## $PageLogoUrl is the URL for a logo image -- you can change this
## to your own logo if you wish.
# $PageLogoUrl = "$PubDirUrl/skins/pmwiki/pmwiki-32.gif";

## If you want to have a custom skin, then set $Skin to the name
## of the directory (in pub/skins/) that contains your skin files.
## See PmWiki.LayoutBasics and Cookbook.Skins.
# $Skin = 'pmwiki';

##  If you want uploads enabled on your system, set $EnableUpload=1.
##  You'll also need to set a default upload password, or else set
##  passwords on individual groups and pages.  For more information
##  see PmWiki.UploadsAdmin and PmWiki.PasswordsAdmin.
# $EnableUpload = 1;                       
# $DefaultPasswords['upload'] = crypt('secret');

##  Setting $EnableDiag turns on the ?action=diag and ?action=phpinfo
##  actions, which often helps the PmWiki authors to troubleshoot 
##  various configuration and execution problems.
# $EnableDiag = 1;                         # enable remote diagnostics

##  By default, PmWiki doesn't allow browsers to cache pages.  Setting
##  $EnableIMSCaching=1; will re-enable browser caches in a somewhat
##  smart manner.  Note that you may want to have caching disabled while
##  adjusting configuration files or layout templates.
# $EnableIMSCaching = 1;                   # allow browser caching

##  Set $SpaceWikiWords if you want WikiWords to automatically 
##  have spaces before each sequence of capital letters.
# $SpaceWikiWords = 1;                     # turn on WikiWord spacing

##  Set $LinkWikiWords to zero if you don't want WikiWord links (i.e.,
##  all links are made using [[...]].
# $LinkWikiWords = 0;                      # disable WikiWord links

##  If you want only the first occurrence of a WikiWord to be converted
##  to a link, set $WikiWordCountMax=1.
# $WikiWordCountMax = 1;                   # converts only first WikiWord
# $WikiWordCountMax = 0;                   # another way to disable WikiWords

##  The $WikiWordCount array can be used to control the number of times
##  a WikiWord is converted to a link.  This is useful for disabling
##  or limiting specific WikiWords.
# $WikiWordCount['PhD'] = 0;               # disables 'PhD'
# $WikiWordCount['PmWiki'] = 1;            # convert only first 'PmWiki'

##  By default, PmWiki is configured such that only the first occurrence
##  of 'PmWiki' in a page is treated as a WikiWord.  If you want to 
##  restore 'PmWiki' to be treated like other WikiWords, uncomment the
##  line below.
# unset($WikiWordCount['PmWiki']);

##  If you want to disable WikiWords matching a pattern, you can use 
##  something like the following.  Note that the first argument has to 
##  be different for each call to Markup().  The example below disables
##  WikiWord links like COM1, COM2, COM1234, etc.
# Markup('COM\d+', '<wikilink', '/\\bCOM\\d+/', "Keep('$0')");

##  $DiffKeepDays specifies the minimum number of days to keep a page's
##  revision history.  The default is 3650 (approximately 10 years).
# $DiffKeepDays=30;                        # keep page history at least 30 days

##  The refcount.php script enables ?action=refcount, which helps to
##  find missing and orphaned pages.  See PmWiki.RefCount.
# include_once('scripts/refcount.php');

##  The rss.php script enables ?action=rss and ?action=rdf, which
##  provides RSS feeds for a site based on WikiTrails.  See PmWiki.RSS.
# include_once('scripts/rss.php');

##  PmWiki allows a great deal of flexibility for creating custom markup.
##  To add support for '*bold*' and '~italic~' markup (the single quotes
##  are part of the markup), uncomment the following lines. 
##  (See PmWiki.CustomMarkup and the Cookbook for details and examples.)
# Markup("'~", "inline", "/'~(.*?)~'/", "<i>$1</i>");        # '~italic~'
# Markup("'*", "inline", "/'\\*(.*?)\\*'/", "<b>$1</b>");    # '*bold*'

?>
