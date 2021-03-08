<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
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
 *       \file       htdocs/comm/action/document.php
 *       \ingroup    agenda
 *       \brief      Page des documents joints sur les actions
 *       \version    $Id$
 */

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/agenda.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/cactioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("other");
$langs->load("bills");

if (isset($_GET["error"])) $error=$_GET["error"];
$objectid = isset($_GET["id"])?$_GET["id"]:'';

// Security check
if ($user->societe_id > 0)
{
	unset($_GET["action"]);
	$action='';
	$socid = $user->societe_id;
}

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
 * Action envoie fichier
 */
if ( $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
    /*
     * Creation repertoire si n'existe pas
     */
	$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($objectid);
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

/*
 * Efface fichier
 */
if ($_GET["action"] == 'delete')
{
	$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($objectid);
	$file = $upload_dir . '/' . $_GET['urlfile'];	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	dol_delete_file($file);
}


/*
 * View
 */

llxHeader();


if ($objectid > 0)
{
	$act = new ActionComm($db);
	if ($act->fetch($objectid))
	{
		$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($objectid);

		$company=new Societe($db);
		$company->fetch($act->societe->id);
		$act->societe=$company;

		$author=new User($db);
		$author->id=$act->author->id;
		$author->fetch();
		$act->author=$author;

		$contact=new Contact($db);
		$contact->fetch($act->contact->id);
		$act->contact=$contact;

		$head=actions_prepare_head();
		dol_fiche_head($head, 'documents', $langs->trans("Action"),0,'task');

		// Affichage fiche action en mode visu
		print '<table class="border" width="100%"';

		// Ref
		print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">'.$act->id.'</td></tr>';

		// Type
		print '<tr><td>'.$langs->trans("Type").'</td><td colspan="3">'.$act->type.'</td></tr>';

		// Libelle
		print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3">'.$act->label.'</td></tr>';

		// Societe - contact
		print '<tr><td>'.$langs->trans("Company").'</td><td>'.$act->societe->getNomUrl(1).'</td>';
		print '<td>'.$langs->trans("Contact").'</td>';
		print '<td>';
		if ($act->contact->id > 0)
		{
			print $act->contact->getNomUrl(1);
		}
		else
		{
			print $langs->trans("None");
		}

		print '</td></tr>';

		// Construit liste des fichiers
		$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
			$totalsize+=$file['size'];
		}


		print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
		print '</table>';

		print '</div>';

		if ($mesg) { print $mesg."<br>"; }


		// Affiche formulaire upload
	   	$formfile=new FormFile($db);
		$formfile->form_attach_new_file(DOL_URL_ROOT.'/comm/action/document.php?id='.$act->id,'',0,0,($user->rights->agenda->myactions->create||$user->rights->agenda->allactions->create));


		// List of document
		$param='&id='.$act->id;
		$formfile->list_of_documents($filearray,$act,'actions',$param,0,'',$user->rights->agenda->myactions->create);
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
