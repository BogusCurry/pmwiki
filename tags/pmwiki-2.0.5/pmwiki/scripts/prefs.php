<?php if (!defined('PmWiki')) exit();
/*  Copyright 2005 Patrick R. Michaud (pmichaud@pobox.com)
    This file is part of PmWiki; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.  See pmwiki.php for full details.

    This script handles per-browser preferences.  Preference settings
    are stored in wiki pages as XLPage translations, and a cookie on
    the browser tells PmWiki where to find the browser's preferred
    settings.

    This script looks for a ?setprefs= request parameter (e.g., in
    a url); when it finds one it sets a 'setprefs' cookie on the browser 
    identifying the page to be used to load browser preferences,
    and loads the associated preferences.

    If there is no ?setprefs= request, then the script uses the
    'setprefs' cookie from the browser to load the preference settings.
*/

SDV($PrefsCookieExpires, $Now + 60 * 60 * 24 * 365);
$sp = '';
if (@$_COOKIE['setprefs']) $sp = $_COOKIE['setprefs'];
if (isset($_GET['setprefs'])) {
  $sp = $_GET['setprefs'];
  setcookie('setprefs', $sp, $PrefsCookieExpires, '/');
}
if ($sp && PageExists($sp)) XLPage('prefs', $sp);

XLSDV('en', array(
  'ak_edit' => 'e',
  'ak_history' => 'h',
  'ak_recentchanges' => 'c',
  'ak_save' => 's',
  'ak_saveedit' => 'u',
  'ak_preview' => 'p'));

