<?php
/* Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/ajaxbox.php
 *       \brief      File to return Ajax response on Box move
 *       \version    $Id$
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

// Retrieve the entity in the cookie
$entityCookieName = "DOLENTITYID_".md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
if (isset($_COOKIE[$entityCookieName])) $_SESSION["dol_entity"] = $_COOKIE[$entityCookieName];

require('master.inc.php');
require_once(DOL_DOCUMENT_ROOT."/boxes.php");

// Enregistrement de la position des boxes
if((isset($_GET['boxorder']) && !empty($_GET['boxorder'])) && (isset($_GET['userid']) && !empty($_GET['userid'])))
{
	dol_syslog("AjaxBox boxorder=".$_GET['boxorder']." userid=".$_GET['userid'], LOG_DEBUG);

	$infobox=new InfoBox($db);
	$result=$infobox->saveboxorder("0",$_GET['boxorder'],$_GET['userid']);
}

?>
