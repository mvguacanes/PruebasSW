<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
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
 *	\file       htdocs/compta/facture/document.php
 *	\ingroup    facture
 *	\brief      Page for attached files on invoices
 *	\version    $Id$
 */

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT.'/discount.class.php');
require_once(DOL_DOCUMENT_ROOT."/lib/invoice.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

$langs->load('propal');
$langs->load('compta');
$langs->load('other');

if (!$user->rights->facture->lire)
accessforbidden();

$facid=empty($_GET['facid']) ? 0 : intVal($_GET['facid']);
$action=empty($_GET['action']) ? (empty($_POST['action']) ? '' : $_POST['action']) : $_GET['action'];

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
 * Actions
 */

// Envoi fichier
if ($_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	$facture = new Facture($db);
	if ($facture->fetch($facid))
	{
		$upload_dir = $conf->facture->dir_output . "/" . dol_sanitizeFileName($facture->ref);
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
	$facture = new Facture($db);

	$facid=$_GET["id"];
	if ($facture->fetch($facid))
	{
		$upload_dir = $conf->facture->dir_output . "/" . dol_sanitizeFileName($facture->ref);
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

$id = $_GET['facid']?$_GET['facid']:$_GET['id'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	$facture = new Facture($db);
	if ($facture->fetch($id,$ref) > 0)
	{
		$upload_dir = $conf->facture->dir_output.'/'.dol_sanitizeFileName($facture->ref);

		$societe = new Societe($db);
		$societe->fetch($facture->socid);

		$head = facture_prepare_head($facture);
		dol_fiche_head($head, 'documents', $langs->trans('InvoiceCustomer'), 0, 'bill');


		// Construit liste des fichiers
		$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
			$totalsize+=$file['size'];
		}



		print '<table class="border"width="100%">';

		// Ref
		print '<tr><td width="30%">'.$langs->trans('Ref').'</td>';
		print '<td colspan="3">';
		$morehtmlref='';
		$discount=new DiscountAbsolute($db);
		$result=$discount->fetch(0,$facture->id);
		if ($result > 0)
		{
			$morehtmlref=' ('.$langs->trans("CreditNoteConvertedIntoDiscount",$discount->getNomUrl(1,'discount')).')';
		}
		if ($result < 0)
		{
			dol_print_error('',$discount->error);
		}
		print $html->showrefnav($facture,'ref','',1,'facnumber','ref',$morehtmlref);
		print '</td></tr>';

		// Company
		print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">'.$societe->getNomUrl(1).'</td></tr>';

		print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
		print "</table>\n";
		print "</div>\n";

		if ($mesg) { print $mesg."<br>"; }


		// Affiche formulaire upload
		$formfile=new FormFile($db);
		$formfile->form_attach_new_file(DOL_URL_ROOT.'/compta/facture/document.php?facid='.$facture->id,'',0,0,$user->rights->facture->creer);


		// List of document
		$param='&facid='.$facture->id;
		$formfile->list_of_documents($filearray,$facture,'facture',$param);

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
