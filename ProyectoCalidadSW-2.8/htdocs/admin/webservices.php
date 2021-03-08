<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.org>
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

/**	    \file       htdocs/admin/webservices.php
 *		\ingroup    webservices
 *		\brief      Page to setup webservices module
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'setvalue' && $user->admin)
{
	//$result=dolibarr_set_const($db, "PAYBOX_IBS_DEVISE",$_POST["PAYBOX_IBS_DEVISE"],'chaine',0,'',$conf->entity);

	if ($result >= 0)
  	{
  		$mesg='<div class="ok">'.$langs->trans("SetupSaved").'</div>';
  	}
  	else
  	{
		dol_print_error($db);
    }
}


/*
 *	View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("WebServicesSetup"),$linkback,'setup');

print $langs->trans("WebServicesDesc")."<br>\n";


if ($mesg) print '<br>'.$mesg;

print '<br><br>';

// Should work with DOL_URL_ROOT='' or DOL_URL_ROOT='/dolibarr'
$firstpart=$dolibarr_main_url_root;
$firstpart=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',$firstpart);

print '<u>'.$langs->trans("WSDLCanBeDownloadedHere").':</u><br>';
$url=$firstpart.DOL_URL_ROOT.'/webservices/server.php?wsdl';
print img_picto('','puce.png').' '.'<a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
print '<br>';

print '<u>'.$langs->trans("EndPointIs").':</u><br>';
$url=$firstpart.DOL_URL_ROOT.'/webservices/server.php';
print img_picto('','puce.png').' '.'<a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
print '<br>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
