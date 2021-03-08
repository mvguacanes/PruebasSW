<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *		\file       htdocs/theme/freelug/freelug.css.php
 *		\brief      Fichier de style CSS du theme Freelug
 *		\version    $Id$
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1'); // We need to use translation files to know direction
if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');

//require_once("../../conf/conf.php");
require_once("../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions.lib.php");

// Define css type
header('Content-type: text/css');
// Important: Avoid page request by browser and dynamic build at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

if (! empty($_GET["lang"])) $langs->setDefaultLang($_GET["lang"]);	// If language was forced on URL by the main.inc.php
$langs->load("main",0,1);
$right=($langs->direction=='rtl'?'left':'right');
$left=($langs->direction=='rtl'?'right':'left');
?>

/* ============================================================================== */
/* Styles par d�faut                                                              */
/* ============================================================================== */

body {
<?php if (! empty($_GET["optioncss"]) && $_GET["optioncss"] == 'print') {  ?>
	background-color: #FFFFFF;
<?php } else { ?>
	background: #F8F8F8 url(<?php echo DOL_URL_ROOT.'/theme/freelug/img/background.png' ?>);
<?php } ?>
	text-decoration: none ;
	color: #101010;
	font-size: <?php print empty($conf->browser->phone)?'12':'9'; ?>px;
	font-family: helvetica, verdana, arial, sans-serif;
    margin-top: 0;
    margin-bottom: 0;
    margin-right: 0;
    margin-left: 0;
}

a:link    { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:visited { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:active  { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:hover   { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: underline; }
input
{
	font-size: <?php print empty($conf->browser->phone)?'12':'9'; ?>px;
    font-family: helvetica, verdana, arial, sans-serif;
    background: #FFFFFF;
    border: 1px solid #8C9C9B;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
input.flat
{
	font-size: <?php print empty($conf->browser->phone)?'12':'9'; ?>px;
    font-family: helvetica, verdana, arial, sans-serif;
    background: #FFFFFF;
    border: 1px solid #8C9C9B;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea  {
	font-size: <?php print empty($conf->browser->phone)?'12':'9'; ?>px;
    font-family: helvetica, verdana, arial, sans-serif;
    background: #FFFFFF;
    border: 1px solid #8C9C9B;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea.flat
{
	font-size: <?php print empty($conf->browser->phone)?'12':'9'; ?>px;
    font-family: helvetica, verdana, arial, sans-serif;
    background: #FFFFFF;
    border: 1px solid #8C9C9B;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
select.flat
{
    font-size: <?php print empty($conf->browser->phone)?'12':'9'; ?>px;
	font-family: helvetica, verdana, arial, sans-serif;
	font-weight: normal;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
.button  {
	font-family: arial,verdana,heletica, sans-serif;
    padding: 0px 2px 0px 2px;
    margin: 0px 0px 0px 0px;
	border-left: 1px solid #cccccc;
	border-right: 1px solid #aaaaaa;
	border-top: 1px solid #dddddd;
	border-bottom: 1px solid #aaaaaa;
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/freelug/img/button_bg.png' ?>);
	background-position: bottom;
	background-repeat: repeat-x;
}
.buttonajax  {
	font-family: arial,verdana,heletica, sans-serif;
    padding: 0px 2px 0px 2px;
    margin: 0px 0px 0px 0px;
	border-left: 1px solid #cccccc;
	border-right: 1px solid #aaaaaa;
	border-top: 1px solid #dddddd;
	border-bottom: 1px solid #aaaaaa;
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/freelug/img/button_bg.png' ?>);
	background-position: bottom;
	background-repeat: repeat-x;
}
form
{
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}


/* ============================================================================== */
/* Styles de positionnement des zones                                             */
/* ============================================================================== */

div.fiche
{
	margin-left: 5px;
}

/* ============================================================================== */
/* Menu superieur et 1ere ligne tableau                                           */
/* ============================================================================== */

div.tmenu
{
<?php if (! empty($_GET["optioncss"]) && $_GET["optioncss"] == 'print') {  ?>
	display:none;
<?php } else { ?>
    position: relative;
    display: block;
    white-space: nowrap;
    border-top: 1px solid #D3E5EC;
    border-left: 0px;
    border-right: 0px solid #555555;
    border-bottom: 1px solid #8B9999;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 2px 0px;
    font-weight: bold;
    height: 20px;
    background: #dddddd;
    color: #000000;
    text-decoration: none;
<?php } ?>
}

a.tmenudisabled
{
	color: #757575;
    padding: 0px 8px;
    margin: 0px 0px 6px 0px;
	cursor: not-allowed;
}
a.tmenudisabled:link
{
	color: #757575;
    font-weight: normal;
}
a.tmenudisabled:visited
{
	color: #757575;
    font-weight: normal;
}
a.tmenudisabled:hover
{
	color: #757575;
    font-weight: normal;
}
a.tmenudisabled:active
{
	color: #757575;
    font-weight: normal;
}

a.tmenu:link
{
  color: #234046;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #dddddd;
  font-weight: bold;
}
a.tmenu:visited
{
  color: #234046;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #dddddd;
  font-weight: bold;
}
a.tmenu:hover
{
  color: #202020;
  background: #bbbbcc;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #eeeedd;
  text-decoration: none;
}
a.tmenu:active
{
  color: #202020;
  background: #bbbbcc;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #eeeedd;
  text-decoration: none;
}

a.tmenusel
{
  color: #202020;
  background: #bbbbcc;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #eeeeff;
}


/* Top menu */

table.tmenu
{
    padding: 0px 0px 10px 0px;
    margin: 0px 0px 0px 6px;
}

* html li.tmenu a
{
	width:40px;
}

ul.tmenu {
	padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
	list-style: none;

}
li.tmenu {
	float: left;
	border-right: solid 1px #000000;
	height: 18px;
	position:relative;
	display: block;
	margin:0;
	padding:0;
}
li.tmenu a{
  	font-size: 13px;
	color:#000000;
	text-decoration:none;
	padding-left:10px;
	padding-right:10px;
	padding-top: 2px;
	height: 18px;
	display: block;
	font-weight: normal;
}
li.tmenu a.tmenusel
{
	background:#FFFFFF;
	color:#000000;
	font-weight: normal;
}
li.tmenu a:visited
{
	color:#000000;
	font-weight: normal;
}
li.tmenu a:hover
{
	background:#FFFFFF;
	color:#000000;
	font-weight: normal;
}
li.tmenu a:active
{
	color:#000000;
	font-weight: normal;
}
li.tmenu a:link
{

	font-weight: normal;
}



/* Login */

a.login
{
  position: absolute;
  <?php print $right; ?>: 30px;
  top: 3px;

  color: #234046;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #dcdcd0;
  font-weight:bold;
}
a.login:hover
{
  color:black;
}

img.login
{
  position: absolute;
  <?php print $right; ?>: 20px;
  top: 3px;

  text-decoration:none;
  color:white;
  font-weight:bold;
}
img.printer
{
  position: absolute;
  <?php print $right; ?>: 4px;
  top: 4px;

  text-decoration: none;
  color: white;
  font-weight: bold;
}


/* ============================================================================== */
/* Menu gauche                                                                    */
/* ============================================================================== */

<?php if (! empty($_GET["optioncss"]) && $_GET["optioncss"] == 'print') {  ?>
.vmenu {
	display:none;
}
<?php } ?>

td.vmenu
{
    padding-right: 2px;
    padding: 0px;
    padding-bottom: 0px;
    width: 180px;
}

a.vmenu:link    { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:visited { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:active  { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:hover   { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
font.vmenudisabled { font-size:<?php print empty($conf->browser->phone)?'12':'9'; ?>px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #a3a590; }

a.vsmenu:link    { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
a.vsmenu:visited { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
a.vsmenu:active  { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
a.vsmenu:hover   { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
font.vsmenudisabled { font-size:<?php print empty($conf->browser->phone)?'12':'9'; ?>px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #a3a590; margin: 1px 1px 1px 6px; }

a.help:link    { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:visited { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:active  { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:hover   { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }


div.blockvmenupair
{
	border-right: 1px solid #555555;
	border-bottom: 1px solid #555555;
	background: #dddddd;
	font-family: helvetica, verdana, arial, sans-serif;
	color: #202020;
	text-align:left;
	text-decoration: none;
    padding: 3px;
    margin: 1px 0px 0px 0px;
}

div.blockvmenuimpair
{
	border-right: 1px solid #555555;
	border-bottom: 1px solid #555555;
	background: #dddddd;
	font-family: helvetica, verdana, arial, sans-serif;
	color: #202020;
	text-align:left;
	text-decoration: none;
    padding: 3px;
    margin: 1px 0px 0px 0px;
}

div.help
{
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	background: #f0f0f0;
	font-family: helvetica, verdana, arial, sans-serif;
	color: #000000;
	text-align:left;
	text-decoration: none;
    padding: 3px;
    margin: 1px 0px 0px 0px;
}

td.barre {
    border-right: 1px solid #000000;
    border-bottom: 1px solid #000000;
    background: #DDDDDD;
    font-family: helvetica, verdana;
    color: #000000;
    text-align:left;
    text-decoration: none
}

td.barre_select {
           background: #b3cccc;
           color: #000000
}

td.photo {
	background: #FCFCFC;
	color: #000000;
    border: 1px solid #b3c5cc;
}


/* ============================================================================== */
/* Onglets                                                                        */
/* ============================================================================== */

div.tabs {
    top: 20px;
    margin: 1px 0px 0px 0px;
    padding: 0px 6px 0px 0px;
    text-align: left;
}

div.tabBar {
    background: #dcdcd0;
    padding-top: 14px;
    padding-left: 14px;
    padding-right: 14px;
    padding-bottom: 14px;
    margin: 0px 0px 10px 0px;
    border: 1px solid #9999BB;
    border-top: 1px solid #9999BB;
}

div.tabsAction {
    margin: 20px 0em 1px 0em;
    padding: 0em 0em;
    text-align: right;
}

a.tabTitle {
    background: #bbbbcc;
    border: 1px solid #9999BB;
    color: #000000;
    font-weight: normal;
    padding: 0em 0.5em;
    margin: 0em 1em;
    text-decoration: none;
    white-space: nowrap;
}

a.tab:link {
  background: white;
  border: 1px solid #9999BB;
  color: #436976;
  padding: 0px 6px;
  margin: 0em 0.2em;
  text-decoration: none;
  white-space: nowrap;
}
a.tab:visited {
  background: white;
  border: 1px solid #9999BB;
  color: #436976;
  padding: 0px 6px;
  margin: 0em 0.2em;
  text-decoration: none;
  white-space: nowrap;
}
a.tab#active {
  background: #dcdcd0;
  border-bottom: #dcdcd0 1px solid;
  text-decoration: none;
}
a.tab:hover {
  background: #ebebe0;
  text-decoration: none;
}

a.tabimage {
    color: #436976;
    text-decoration: none;
    white-space: nowrap;
}


/* ============================================================================== */
/* Boutons actions                                                                */
/* ============================================================================== */

/* Nouvelle syntaxe � utiliser */

a.butAction:link    { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:visited { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:active  { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:hover   { font-family: helvetica, verdana, arial, sans-serif; background: #eeeedd; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }

.butActionRefused         { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #AAAAAA; color: #AAAAAA !important; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none !important; white-space: nowrap; cursor: not-allowed; }

a.butActionDelete:link    { font-family: helvetica, verdana, arial, sans-serif;
                      background: white;
                      border: 1px solid #997777;
                      color: #436976;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butActionDelete:active    { font-family: helvetica, verdana, arial, sans-serif;
                      background: white;
                      border: 1px solid #997777;
                      color: #436976;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butActionDelete:visited    { font-family: helvetica, verdana, arial, sans-serif;
                      background: white;
                      border: 1px solid #997777;
                      color: #436976;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butActionDelete:hover    { font-family: helvetica, verdana, arial, sans-serif;
                      background: #FFe7ec;
                      border: 1px solid #997777;
                      color: #436976;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }


/* ============================================================================== */
/* Tables                                                                         */
/* ============================================================================== */

.nocellnopadd {
list-style-type:none;
margin:0px;
padding:0px;
}

.notopnoleft {
border-collapse: collapse;
border: 0px;
padding-top: 0px;
padding-left: 0px;
padding-right: 4px;
padding-bottom: 4px;
margin: 0px 0px;
}
.notopnoleftnoright {
border-collapse: collapse;
border: 0px;
padding-top: 0px;
padding-left: 0px;
padding-right: 0px;
padding-bottom: 4px;
margin: 0px 0px;
}

table.border {
border-collapse: collapse;
border: 1px white ridge;
}
table.border td {
border: 1px solid #6C7C8B;
padding: 1px 2px;
border-collapse: collapse;
}

table.noborder {
border-collapse: collapse;
border: 0px;
}
table.noborder td {
border: 0px;
padding: 1px 2px;
}

table.nobordernopadding {
border-collapse: collapse;
border: 0px;
}
table.nobordernopadding tr {
border: 0px;
padding: 0px 0px;
}
table.nobordernopadding td {
border: 0px;
padding: 0px 0px;
}

table.liste {
border-collapse: collapse;
border: 0px;
width: 100%;
}


/*
 *  Tableaux
 */






td.border {
            border-top: 1px solid #000000;
            border-right: 1px solid #000000;
            border-bottom: 1px solid #000000;
            border-left: 1px solid #000000;
            }


/*
 *   Ok, Warning, Error
 */
.ok      { color: #114466; }
.warning { color: #777711; }
.error   { color: #550000; font-weight: bold; }

div.ok {
  color: #114466;
}

div.warning {
  color: #777711;
  padding: 0.2em 0.2em 0.2em 0.2em;
  border: 1px solid #ebebd4;
  -moz-border-radius:6px;
  background: #efefd4;
}

div.error {
  color: #550000; font-weight: bold;
  padding: 0.2em 0.2em 0.2em 0.2em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #969090;
}

div.info {
  color: #555555;
  padding: 0.2em 0.2em 0.2em 0.2em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #ACACAB;
}


/*
 *   Liens Payes/Non payes
 */

a.normal:link { font-weight: normal }
a.normal:visited { font-weight: normal }
a.normal:active { font-weight: normal }
a.normal:hover { font-weight: normal }

a.impayee:link { font-weight: bold; color: #550000; }
a.impayee:visited { font-weight: bold; color: #550000; }
a.impayee:active { font-weight: bold; color: #550000; }
a.impayee:hover { font-weight: bold; color: #550000; }





/*
 *  Other
 */

.fieldrequired { font-weight: bold; color: #000055; }

#pictotitle {
	<?php print !empty($conf->browser->phone)?'display: none;':''; ?>
}

div.titre {
    font-family: helvetica, verdana, arial, sans-serif;
    font-weight: normal;
    color: #777799;
    text-decoration: none;
}



input.liste_titre {
    background: #777799;
    border: 0px;
}

tr.liste_titre {
    color: #FFFFFF;
    background: #777799;
    font-family: helvetica, verdana, arial, sans-serif;
    border-left: 1px solid #FFFFFF;
    border-right: 1px solid #FFFFFF;
    border-top: 1px solid #FFFFFF;
    border-bottom: 1px solid #FFFFFF;
    white-space: nowrap;
}

td.liste_titre {
    color: #FFFFFF;
    background: #777799;
    font-family: helvetica, verdana, arial, sans-serif;
    border-top: 1px solid #FFFFFF;
    border-bottom: 1px solid #FFFFFF;
    white-space: nowrap;
}

.liste_titre_sel
{
    color: #DCCCBB;
    background: #777799;
    font-family: helvetica, verdana, arial, sans-serif;
    border-top: 1px solid #FFFFFF;
    border-bottom: 1px solid #FFFFFF;
    white-space: nowrap;
}

tr.liste_total td {
    background: #F0F0F0;
    font-weight: bold;
    white-space: nowrap;
    border-top: 1px solid #888888;
}

th {
    color: #FFFFFF;
    background: #777799;
    font-family: helvetica, verdana, arial, sans-serif;
    border-left: 1px solid #FFFFFF;
    border-right: 1px solid #FFFFFF;
    border-top: 1px solid #FFFFFF;
    border-bottom: 1px solid #FFFFFF;
    white-space: nowrap;
}


.pair {
    background: #dcdcd0;
    }

.impair {
    background: #eeeedd;
    }

/*
 *  Boxes
 */

.box {
	padding-right: 4px;
	padding-bottom: 4px;
}

tr.box_titre {
    color: #FFFFFF;
    background: #777799;
    font-family: Helvetica, Verdana;
}

tr.box_pair {
    background: #dcdcd0;
}

tr.box_impair {
    background: #eeeedd;
    font-family: Helvetica, Verdana;
}

tr.fiche {
    font-family: Helvetica, Verdana;
}



/* ============================================================================== */
/* Formulaire confirmation (HTML)                                                 */
/* ============================================================================== */

table.valid {
    border-top: solid 1px #E6E6E6;
    border-left: solid 1px #E6E6E6;
    border-right: solid 1px #444444;
    border-bottom: solid 1px #555555;
	padding-top: 0px;
	padding-left: 0px;
	padding-right: 0px;
	padding-bottom: 0px;
	margin: 0px 0px;
    background: #DC9999;
}

.validtitre {
    background: #DC9999;
	font-weight: bold;
}

.valid {
}

/* ============================================================================== */
/* Formulaire confirmation (AJAX)                                                 */
/* ============================================================================== */

.overlay_alert {
	background-color: #DDDDDD;
	filter: alpha(opacity=50); /*Does not respect CSS standard */
	-moz-opacity: 0.5;
	opacity: 0.5;
}

.alert_nw {
	width: 5px;
	height: 5px;
	background: transparent url(alert/top_left.gif) no-repeat bottom left;
}

.alert_n {
	height: 5px;
	background: transparent url(alert/top.gif) repeat-x bottom left;
}

.alert_ne {
	width: 5px;
	height: 5px;
	background: transparent url(alert/top_right.gif) no-repeat bottom left
}

.alert_e {
	width: 5px;
	background: transparent url(alert/right.gif) repeat-y 0 0;
}

.alert_w {
	width: 5px;
	background: transparent url(alert/left.gif) repeat-y 0 0;
}

.alert_sw {
	width: 5px;
	height: 5px;
	background: transparent url(alert/bottom_left.gif) no-repeat 0 0;
}

.alert_s {
	height: 5px;
	background: transparent url(alert/bottom.gif) repeat-x 0 0;
}

.alert_se, .alert_sizer {
	width: 5px;
	height: 5px;
	background: transparent url(alert/bottom_right.gif) no-repeat 0 0;
}

.alert_close {
	width:0px;
	height:0px;
	display:none;
}

.alert_minimize {
	width:0px;
	height:0px;
	display:none;
}

.alert_maximize {
	width:0px;
	height:0px;
	display:none;
}

.alert_title {
	float:left;
	height:1px;
	width:100%;
}

.alert_content {
	overflow:visible;
	color: #000;
	font-family: Tahoma, Arial, sans-serif;
  	font: 12px arial;
	background: #FFF;
}

/* For alert/confirm dialog */
.alert_window {
	background: #FFF;
	padding:30px;
	margin-left:auto;
	margin-right:auto;
	width:400px;
}

.alert_message {
  font: 12px arial;
  text-align:left;
	width:100%;
	color:#012;
	padding-top:5px;
	padding-left:5px;
	padding-bottom:5px;
}

.alert_buttons {
	text-align:center;
	width:100%;
}

.alert_buttons input {
	width:20%;
	margin:5px;
}

.alert_progress {
	float:left;
	margin:auto;
	text-align:center;
	width:100%;
	height:16px;
	background: #FFF url('alert/progress.gif') no-repeat center center
}

.dialog {
	display: block;
	position: absolute;
}

.dialog table.table_window  {
  border-collapse: collapse;
  border-spacing: 0;
  width: 100%;
	margin: 0px;
	padding:0px;
}

.dialog table.table_window td , .dialog table.table_window th {
  padding: 0;
}

.dialog .title_window {
  -moz-user-select:none;
}



/* ============================================================================== */
/* Tooltips                                                                       */
/* ============================================================================== */

#dhtmltooltip
{
position: absolute;
width: <?php print dol_size(450,'width'); ?>px;
border-top: solid 1px #BBBBBB;
border-left: solid 1px #BBBBBB;
border-right: solid 1px #444444;
border-bottom: solid 1px #444444;
padding: 2px;
background-color: #FFFFE0;
visibility: hidden;
z-index: 100;
}


/* ============================================================================== */
/* Calendar                                                                       */
/* ============================================================================== */
.bodyline {
	-moz-border-radius:8px;
	border: 1px #E4ECEC outset;
	padding:0px;
	margin-bottom:5px;
}
table.dp {
    width: 180px;
    background-color: #FFFFFF;
    border-top: solid 2px #DDDDDD;
    border-left: solid 2px #DDDDDD;
    border-right: solid 1px #222222;
    border-bottom: solid 1px #222222;
}
.dp td, .tpHour td, .tpMinute td{padding:2px; font-size:10px;}
/* Barre titre */
.dpHead,.tpHead,.tpHour td:Hover .tpHead{
	font-weight:bold;
	background-color:#777799;
	color:white;
	font-size:11px;
	cursor:auto;
}
/* Barre navigation */
.dpButtons,.tpButtons {
	text-align:center;
	background-color:#bbbbcc;
	color:#FFFFFF;
	font-weight:bold;
	border: 1px outset black;
	cursor:pointer;
}
.dpButtons:Active,.tpButtons:Active{border: 1px outset black;}
.dpDayNames td,.dpExplanation {background-color:#D9DBE1; font-weight:bold; text-align:center; font-size:11px;}
.dpExplanation{ font-weight:normal; font-size:11px;}
.dpWeek td{text-align:center}

.dpToday,.dpReg,.dpSelected{
	cursor:pointer;
}
.dpToday{font-weight:bold; color:black; background-color:#DDDDDD;}
.dpReg:Hover,.dpToday:Hover{background-color:black;color:white}

/* Jour courant */
.dpSelected{background-color:#777799;color:white;font-weight:bold; }

.tpHour{border-top:1px solid #DDDDDD; border-right:1px solid #DDDDDD;}
.tpHour td {border-left:1px solid #DDDDDD; border-bottom:1px solid #DDDDDD; cursor:pointer;}
.tpHour td:Hover {background-color:black;color:white;}

.tpMinute {margin-top:5px;}
.tpMinute td:Hover {background-color:black; color:white; }
.tpMinute td {background-color:#D9DBE1; text-align:center; cursor:pointer;}

/* Bouton X fermer */
.dpInvisibleButtons
{
border-style:none;
background-color:transparent;
padding:0px;
font-size:9px;
border-width:0px;
color:#062342;
vertical-align:middle;
cursor: pointer;
}


/* ============================================================================== */
/*  Afficher/cacher                                                               */
/* ============================================================================== */

div.visible {
    display: block;
}

div.hidden {
    display: none;
}

tr.visible {
    display: block;
}

td.hidden {
    display: none;
}



/* ============================================================================== */
/*  Module agenda                                                                 */
/* ============================================================================== */

.cal_other_month   { background: #DDDDDD; border: solid 1px #ACBCBB; padding-left: 2px; padding-right: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_past_month    { background: #EEEEEE; border: solid 1px #ACBCBB; padding-left: 2px; padding-right: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_current_month { background: #FFFFFF; border: solid 1px #ACBCBB; padding-left: 2px; padding-right: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_today         { background: #FFFFFF; border: solid 2px #6C7C7B; padding-left: 2px; padding-right: 1px; padding-top: 0px; padding-bottom: 0px; }
table.cal_event    { border-collapse: collapse; margin-bottom: 1px; }
table.cal_event td { border: 0px; padding-left: 0px; padding-right: 2px; padding-top: 0px; padding-bottom: 0px; } */
.cal_event a:link    { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:visited { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:active  { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:hover   { color: #111111; font-size: 11px; font-weight: normal !important; }



/* ============================================================================== */
/* Admin Menu                                                                     */
/* ============================================================================== */

/* CSS a  appliquer a  l'arbre hierarchique */

/* Lien plier /deplier tout */
.arbre-switch {
    text-align: right;
    padding: 0 5px;
    margin: 0 0 -18px 0;
}

/* Arbre */
ul.arbre {
    padding: 5px 10px;
}
/* strong : A modifier en fonction de la balise choisie */
ul.arbre strong {
    font-weight: normal;
    padding: 0 0 0 20px;
    margin: 0 0 0 -7px;
    background-image: url(<?php echo DOL_URL_ROOT.'/theme/common/treemenu/branch.gif' ?>);
    background-repeat: no-repeat;
    background-position: 1px 50%;
}
ul.arbre strong.arbre-plier {
    background-image: url(<?php echo DOL_URL_ROOT.'/theme/common/treemenu/plus.gif' ?>);
    cursor: pointer;
}
ul.arbre strong.arbre-deplier {
    background-image: url(<?php echo DOL_URL_ROOT.'/theme/common/treemenu/minus.gif' ?>);
    cursor: pointer;
}
ul.arbre ul {
    padding: 0;
    margin: 0;
}
ul.arbre li {
    padding: 0;
    margin: 0;
    list-style: none;
}
ul.arbre li li {
    margin: 0 0 0 16px;
}
/* Classe pour masquer */
.hide {
    display: none;
}

img.menuNew
{
	display:block;
	border:0px;
}

img.menuEdit
{
	border: 0px;
	display: block;
}

img.menuDel
{
	display:none;
	border: 0px;
}

div.menuNew
{
	margin-top:-20px;
	margin-left:270px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;
}

div.menuEdit
{
	margin-top:-15px;
	margin-left:250px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;

}

div.menuDel
{
	margin-top:-20px;
	margin-left:290px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;

}

div.menuFleche
{
	margin-top:-16px;
	margin-left:320px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;

}



/* ============================================================================== */
/*  CSS for color picker                                                          */
/* ============================================================================== */

A.color, A.color:active, A.color:visited {
 position : relative;
 display : block;
 text-decoration : none;
 width : 10px;
 height : 10px;
 line-height : 10px;
 margin : 0px;
 padding : 0px;
 border : 1px inset white;
}
A.color:hover {
 border : 1px outset white;
}
A.none, A.none:active, A.none:visited, A.none:hover {
 position : relative;
 display : block;
 text-decoration : none;
 width : 10px;
 height : 10px;
 line-height : 10px;
 margin : 0px;
 padding : 0px;
 cursor : default;
 border : 1px solid #b3c5cc;
}
.tblColor {
 display : none;
}
.tdColor {
 padding : 1px;
}
.tblContainer {
 background-color : #DDDDDD;
}
.tblGlobal {
 position : absolute;
 top : 0px;
 left : 0px;
 display : none;
 background-color : #DDDDDD;
 border : 2px outset;
}
.tdContainer {
 padding : 5px;
}
.tdDisplay {
 width : 50%;
 height : 20px;
 line-height : 20px;
 border : 1px outset white;
}
.tdDisplayTxt {
 width : 50%;
 height : 24px;
 line-height : 12px;
 font-family: helvetica, verdana, arial, sans-serif;
 font-size : 8pt;
 color : black;
 text-align : center;
}
.btnColor {
 width : 100%;
 font-family: helvetica, verdana, arial, sans-serif;
 font-size : 10pt;
 padding : 0px;
 margin : 0px;
}
.btnPalette {
 width : 100%;
 font-family: helvetica, verdana, arial, sans-serif;
 font-size : 8pt;
 padding : 0px;
 margin : 0px;
}

