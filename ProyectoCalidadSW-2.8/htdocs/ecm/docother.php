<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 */

/**
 \file       htdocs/ecm/docother.php
 \ingroup    ecm
 \brief      Main ecm page
 \version    $Id$
 \author		Laurent Destailleur
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

// Load traductions files
$langs->load("ecm");
$langs->load("companies");
$langs->load("other");

// Load permissions
$user->getrights('ecm');

// Get parameters
$socid = isset($_GET["socid"])?$_GET["socid"]:'';

// Permissions
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}

$section=$_GET["section"];
if (! $section) $section='misc';
$upload_dir = $conf->ecm->dir_output.'/'.$section;



/*******************************************************************
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/

// Envoie fichier
if ( $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
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
	    	// File infected by a virus
		    $langs->load("errors");
	    	$mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWithAVirus").'</div>';
	    }
		else if ($result < 0)
		{
			// Echec transfert (fichier d?passant la limite ?)
			$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
			// print_r($_FILES);
		}
	}
}

// Suppression fichier
if ($_POST['action'] == 'confirm_deletefile' && $_POST['confirm'] == 'yes')
{
	$file = $upload_dir . "/" . urldecode($_GET["urlfile"]);
	dol_delete_file($file);
	$mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
}





/*******************************************************************
 * PAGE
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/

llxHeader();

$form=new Form($db);

print_fiche_titre($langs->trans("ECMAutoOrg"));

//$head = societe_prepare_head($societe);


//dol_fiche_head($head, 'document', $societe->nom);


/*
 * Confirmation de la suppression d'une ligne produit
 */
if ($_GET['action'] == 'delete_file')
{
	$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?socid='.$socid.'&amp;urlfile='.urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');
	if ($ret == 'html') print '<br>';
}

// Construit liste des fichiers
clearstatcache();
$totalsize=0;
$filearray=array();
$errorlevel=error_reporting();
error_reporting(0);
$handle=opendir($upload_dir);
error_reporting($errorlevel);
if ($handle)
{
	$i=0;
	while (($file = readdir($handle))!==false)
	{
		if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
		{
			$filearray[$i]->name=$file;
			$filearray[$i]->size=dol_filesize($upload_dir."/".$file);
			$filearray[$i]->date=dol_filemtime($upload_dir."/".$file);
			$totalsize+=$filearray[$i]->size;
			$i++;
		}
	}
	closedir($handle);
}
else
{
	//            print '<div class="error">'.$langs->trans("ErrorCanNotReadDir",$upload_dir).'</div>';
}


/*

print '<table class="border"width="100%">';

// Nbre fichiers
print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';

//Total taille
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

print '</table>';

print '</div>';

*/


if ($mesg) { print $mesg."<br>"; }


print $langs->trans("FeatureNotYetAvailable");

// End of page
$db->close();

llxFooter('$Date$ - $Revision$');
?>
