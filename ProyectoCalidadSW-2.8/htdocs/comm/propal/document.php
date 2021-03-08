<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005      Regis Houssin         <regis@dolibarr.fr>
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
 *       \file       htdocs/comm/propal/document.php
 *       \ingroup    propale
 *       \brief      Page de gestion des documents attach�es � une proposition commerciale
 *       \version    $Id$
 */

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/propal.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

$langs->load('compta');
$langs->load('other');

$action=empty($_GET['action']) ? (empty($_POST['action']) ? '' : $_POST['action']) : $_GET['action'];

$propalid = isset($_GET["propalid"])?$_GET["propalid"]:'';

// Security check
if ($user->societe_id)
{
	unset($_GET["action"]);
	$action='';
	$socid = $user->societe_id;
}
$result = restrictedArea($user, 'propale', $propalid, 'propal');

// Get parameters
$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 * Actions
 */

// Envoi fichier
if ($_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	$propal = new Propal($db);

	if ($propal->fetch($propalid))
    {
        $upload_dir = $conf->propale->dir_output . "/" . dol_sanitizeFileName($propal->ref);
        if (! is_dir($upload_dir)) create_exdir($upload_dir);

        if (is_dir($upload_dir))
        {
        	$result = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],0);
        	if ($result > 0)
        	{
        		$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
        		//print_r($_FILES);
        	}
        	else if ($result == -99)
        	{
        		// Files infected by a virus
        		$langs->load("errors");
        		$mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWithAVirus").'</div>';
        	}
        	else if ($result < 0)
        	{
        		// Echec transfert (fichier depassant la limite ?)
        		$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
        		// print_r($_FILES);
        	}
        }
    }
}

// Delete
if ($action=='delete')
{
	$propal = new Propal($db);

	$propalid=$_GET["id"];
	if ($propal->fetch($propalid))
    {
        $upload_dir = $conf->propale->dir_output . "/" . dol_sanitizeFileName($propal->ref);
    	$file = $upload_dir . '/' . $_GET['urlfile'];	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
    	dol_delete_file($file);
        $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
    }
}


/*
 * View
 */

llxHeader();

$html = new Form($db);

$id = $_GET['propalid']?$_GET['propalid']:$_GET['id'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	$propal = new Propal($db);
	if ($propal->fetch($id,$ref))
    {
		$upload_dir = $conf->propale->dir_output.'/'.dol_sanitizeFileName($propal->ref);

        $societe = new Societe($db);
        $societe->fetch($propal->socid);

		$head = propal_prepare_head($propal);
		dol_fiche_head($head, 'document', $langs->trans('Proposal'), 0, 'propal');


		// Construit liste des fichiers
		$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
			$totalsize+=$file['size'];
		}


        print '<table class="border"width="100%">';

		$linkback="<a href=\"".$_SERVER["PHP_SELF"]."?page=$page&socid=$socid&viewstatut=$viewstatut&sortfield=$sortfield&$sortorder\">".$langs->trans("BackToList")."</a>";

		// Ref
		print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="3">';
		print $html->showrefnav($propal,'ref',$linkback,1,'ref','ref','');
		print '</td></tr>';

		// Ref client
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
		print $langs->trans('RefCustomer').'</td><td align="left">';
		print '</td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		print $propal->ref_client;
		print '</td>';
		print '</tr>';

		// Customer
		if ( is_null($propal->client) )
			$propal->fetch_client();
		print "<tr><td>".$langs->trans("Company")."</td>";
		print '<td colspan="3">'.$propal->client->getNomUrl(1).'</td></tr>';

        print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
        print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

        print '</table>';

        print '</div>';

        if ($mesg) { print "$mesg<br>"; }

        // Affiche formulaire upload
       	$formfile=new FormFile($db);
		$formfile->form_attach_new_file(DOL_URL_ROOT.'/comm/propal/document.php?propalid='.$propal->id,'',0,0,$user->rights->propale->creer);


		// List of document
		$param='&propalid='.$propal->id;
		$formfile->list_of_documents($filearray,$propal,'propal',$param);

	}
	else
	{
		dol_print_error($db);
	}
}
else
{
	print $langs->trans("UnkownError");
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
