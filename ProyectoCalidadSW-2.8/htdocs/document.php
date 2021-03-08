<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/document.php
 *  \brief      Wrapper to download data files
 *  \version    $Id$
 *  \remarks    Call of this wrapper is mad with URL:
 * 				document.php?modulepart=repfichierconcerne&file=pathrelatifdufichier
 */

define('NOTOKENRENEWAL',1); // Disables token renewal

// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
$action = isset($_GET["action"])?$_GET["action"]:'';
$original_file = isset($_GET["file"])?$_GET["file"]:'';
$modulepart = isset($_GET["modulepart"])?$_GET["modulepart"]:'';
$urlsource = isset($_GET["urlsource"])?$_GET["urlsource"]:'';

// Pour autre que bittorrent, on charge environnement + info issus de logon (comme le user)
if (($modulepart == 'bittorrent') && ! defined("NOLOGIN")) define("NOLOGIN",1);

if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX','1');

require("./main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');


// C'est un wrapper, donc header vierge
function llxHeader() { }


// Define mime type
$type = 'application/octet-stream';
if (! empty($_GET["type"])) $type=$_GET["type"];
else $type=dol_mimetype($original_file);
//print 'X'.$type.'-'.$original_file;exit;

// Define attachment (attachment=true to force choice popup 'open'/'save as')
$attachment = true;
// Text files
if (preg_match('/\.txt$/i',$original_file))  	{ $attachment = false; }
if (preg_match('/\.csv$/i',$original_file))  	{ $attachment = true; }
if (preg_match('/\.tsv$/i',$original_file))  	{ $attachment = true; }
// Documents MS office
if (preg_match('/\.doc(x)?$/i',$original_file)) { $attachment = true; }
if (preg_match('/\.dot(x)?$/i',$original_file)) { $attachment = true; }
if (preg_match('/\.mdb$/i',$original_file))     { $attachment = true; }
if (preg_match('/\.ppt(x)?$/i',$original_file)) { $attachment = true; }
if (preg_match('/\.xls(x)?$/i',$original_file)) { $attachment = true; }
// Documents Open office
if (preg_match('/\.odp$/i',$original_file))     { $attachment = true; }
if (preg_match('/\.ods$/i',$original_file))     { $attachment = true; }
if (preg_match('/\.odt$/i',$original_file))     { $attachment = true; }
// Misc
if (preg_match('/\.(html|htm)$/i',$original_file)) 	{ $attachment = false; }
if (preg_match('/\.pdf$/i',$original_file))  	{ $attachment = true; }
if (preg_match('/\.sql$/i',$original_file))     { $attachment = true; }
// Images
if (preg_match('/\.jpg$/i',$original_file)) 	{ $attachment = true; }
if (preg_match('/\.jpeg$/i',$original_file)) 	{ $attachment = true; }
if (preg_match('/\.png$/i',$original_file)) 	{ $attachment = true; }
if (preg_match('/\.gif$/i',$original_file)) 	{ $attachment = true; }
if (preg_match('/\.bmp$/i',$original_file)) 	{ $attachment = true; }
if (preg_match('/\.tiff$/i',$original_file)) 	{ $attachment = true; }
// Calendar
if (preg_match('/\.vcs$/i',$original_file))  	{ $attachment = true; }
if (preg_match('/\.ics$/i',$original_file))  	{ $attachment = true; }
if ($_REQUEST["attachment"])            { $attachment = true; }
if (! empty($conf->global->MAIN_DISABLE_FORCE_SAVEAS)) $attachment=false;
//print "XX".$attachment;exit;

// Suppression de la chaine de caractere ../ dans $original_file
$original_file = str_replace("../","/", $original_file);

// find the subdirectory name as the reference
$refname=basename(dirname($original_file)."/");

$accessallowed=0;
$sqlprotectagainstexternals='';
if ($modulepart)
{
	// On fait une verification des droits et on definit le repertoire concerne

	// Wrapping pour les factures
	if ($modulepart == 'facture')
	{
		$user->getrights('facture');
		if ($user->rights->facture->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->facture->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."facture WHERE ref='$refname'";
	}

	if ($modulepart == 'unpaid')
	{
		$user->getrights('facture');
		if ($user->rights->facture->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->facture->dir_output.'/unpaid/temp/'.$original_file;
	}

	// Wrapping pour les fiches intervention
	if ($modulepart == 'ficheinter')
	{
		$user->getrights('ficheinter');
		if ($user->rights->ficheinter->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->ficheinter->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
	}

	// Wrapping pour les prelevements
	if ($modulepart == 'prelevement')
	{
		$user->getrights('prelevement');
		if ($user->rights->prelevement->bons->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->prelevement->dir_output.'/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."$modulepart WHERE ref='$refname'";
	}

	// Wrapping pour les propales
	if ($modulepart == 'propal')
	{
		$user->getrights('propale');
		if ($user->rights->propale->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}

		$original_file=$conf->propale->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."propal WHERE ref='$refname'";
	}

	// Wrapping pour les commandes
	if ($modulepart == 'commande')
	{
		$user->getrights('commande');
		if ($user->rights->commande->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->commande->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."commande WHERE ref='$refname'";
	}

	// Wrapping pour les projets
	if ($modulepart == 'project')
	{
		$user->getrights('projet');
		if ($user->rights->projet->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->projet->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."projet WHERE ref='$refname'";
	}

	// Wrapping pour les commandes fournisseurs
	if ($modulepart == 'commande_fournisseur')
	{
		$user->getrights('fournisseur');
		if ($user->rights->fournisseur->commande->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->fournisseur->dir_output.'/commande/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE ref='$refname'";
	}

	// Wrapping pour les factures fournisseurs
	if ($modulepart == 'facture_fournisseur')
	{
		$user->getrights('fournisseur');
		if ($user->rights->fournisseur->facture->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->fournisseur->dir_output.'/facture/'.get_exdir(dirname($original_file),2,1).$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."facture_fourn WHERE facnumber='$refname'";
	}

	// Wrapping pour les rapport de paiements
	if ($modulepart == 'facture_paiement')
	{
		$user->getrights('facture');
		if ($user->rights->facture->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		if ($user->societe_id > 0) $original_file=$conf->facture->dir_output.'/payments/private/'.$user->id.'/'.$original_file;
		else $original_file=$conf->facture->dir_output.'/payments/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
	}

	// Wrapping pour les exports de compta
	if ($modulepart == 'export_compta')
	{
		$user->getrights('compta');
		if ($user->rights->compta->ventilation->creer || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->compta->dir_output.'/'.$original_file;
	}

	// Wrapping pour les societe
	if ($modulepart == 'societe')
	{
		$user->getrights('societe');
		if ($user->rights->societe->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->societe->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT rowid as fk_soc FROM ".MAIN_DB_PREFIX."societe WHERE rowid = '".$refname."'";
	}

	// Wrapping pour les expedition
	if ($modulepart == 'expedition')
	{
		$user->getrights('expedition');
		if ($user->rights->expedition->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->expedition->dir_output."/sending/".$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
	}

	// Wrapping pour les bons de livraison
	if ($modulepart == 'livraison')
	{
		$user->getrights('expedition');
		if ($user->rights->expedition->livraison->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->expedition->dir_output."/receipt/".$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
	}

	// Wrapping pour la telephonie
	if ($modulepart == 'telephonie')
	{
		$user->getrights('telephonie');
		if ($user->rights->telephonie->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->telephonie->dir_output.'/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
	}

	// Wrapping pour les actions
	if ($modulepart == 'actions')
	{
		$user->getrights('agenda');
		if ($user->rights->agenda->myactions->read || preg_match('/^specimen/i',$original_file))
		{
		$accessallowed=1;
		}
		$original_file=$conf->agenda->dir_output.'/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
	}

	// Wrapping pour les actions
	if ($modulepart == 'actionsreport')
	{
		$user->getrights('agenda');
		if ($user->rights->agenda->allactions->read || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file = $conf->agenda->dir_temp."/".$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
	}

	// Wrapping pour les produits et services
	if ($modulepart == 'produit' || $modulepart == 'service')
	{
		$user->getrights('produit');
		$user->getrights('service');
		if (($user->rights->produit->lire || $user->rights->service->lire) || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		if ($conf->produit->enabled) $original_file=$conf->produit->dir_output.'/'.$original_file;
		elseif ($conf->service->enabled) $original_file=$conf->service->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = '';
	}

	// Wrapping pour les contrats
	if ($modulepart == 'contract')
	{
		$user->getrights('contrat');
		if ($user->rights->contrat->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->contrat->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = '';
	}

	// Wrapping pour les documents generaux
	if ($modulepart == 'ged')
	{
		$user->getrights('document');
		if ($user->rights->document->lire)
		{
			$accessallowed=1;
		}
		$original_file= $conf->ged->dir_output.'/'.$original_file;
	}

	// Wrapping pour les documents generaux
	if ($modulepart == 'ecm')
	{
		$user->getrights('ecm');
		if ($user->rights->ecm->download)
		{
			$accessallowed=1;
		}
		$original_file= $conf->ecm->dir_output.'/'.$original_file;
	}

	// Wrapping pour les dons
	if ($modulepart == 'donation')
	{
		$user->getrights('don');
		if ($user->rights->don->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->don->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = '';
	}

	// Wrapping pour les remises de cheques
	if ($modulepart == 'remisecheque')
	{
		$user->getrights('banque');
		if ($user->rights->banque->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}

		$original_file=$conf->banque->dir_output.'/bordereau/'.get_exdir(basename($original_file,".pdf"),2,1).$original_file;
		$sqlprotectagainstexternals = '';
	}

	// Wrapping for export module
	if ($modulepart == 'export')
	{
		// Aucun test necessaire car on force le rep de doanwload sur
		// le rep export qui est propre a l'utilisateur
		$accessallowed=1;
		$original_file=$conf->export->dir_temp.'/'.$user->id.'/'.$original_file;
		$sqlprotectagainstexternals = '';
	}

	// Wrapping for import module
	if ($modulepart == 'import')
	{
		// Aucun test necessaire car on force le rep de doanwload sur
		// le rep export qui est propre a l'utilisateur
		$accessallowed=1;
		$original_file=$conf->import->dir_temp.'/'.$original_file;
		$sqlprotectagainstexternals = '';
	}

	// Wrapping pour l'editeur wysiwyg
	if ($modulepart == 'editor')
	{
		// Aucun test necessaire car on force le rep de download sur
		// le rep export qui est propre a l'utilisateur
		$accessallowed=1;
		$original_file=$conf->fckeditor->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = '';
	}

	// Wrapping pour les backups
	if ($modulepart == 'systemtools')
	{
		if ($user->admin)
		{
			$accessallowed=1;
		}
		$original_file=$conf->admin->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = '';
	}

	// Wrapping pour BitTorrent
	if ($modulepart == 'bittorrent')
	{
		$accessallowed=1;
		$dir='files';
		if ($type == 'application/x-bittorrent') $dir='torrents';
		$original_file=$conf->bittorrent->dir_output.'/'.$dir.'/'.$original_file;
		$sqlprotectagainstexternals = '';
	}
}

// Basic protection (against external users only)
if ($user->societe_id > 0)
{
	if ($sqlprotectagainstexternals)
	{
		$resql = $db->query($sqlprotectagainstexternals);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			$num=$db->num_rows($resql);
			if ($num>0 && $user->societe_id != $obj->fk_soc)
			$accessallowed=0;
		}
	}
}

// Security:
// Limite acces si droits non corrects
if (! $accessallowed)
{
	accessforbidden();
}

// Security:
// On interdit les remontees de repertoire ainsi que les pipe dans
// les noms de fichiers.
if (preg_match('/\.\./',$original_file) || preg_match('/[<>|]/',$original_file))
{
	dol_syslog("Refused to deliver file ".$original_file);
	// Do no show plain path in shown error message
	dol_print_error(0,$langs->trans("ErrorFileNameInvalid",$_GET["file"]));
	exit;
}


if ($action == 'remove_file')	// Remove a file
{
	clearstatcache();

	dol_syslog("document.php remove $original_file $urlsource", LOG_DEBUG);

	// This test should be useless. We keep it to find bug more easily
	$original_file_osencoded=dol_osencode($original_file);	// New file name encoded in OS encoding charset
	if (! file_exists($original_file_osencoded))
	{
		dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$_GET["file"]));
		exit;
	}

	dol_delete_file($original_file);

	dol_syslog("document.php back to ".urldecode($urlsource), LOG_DEBUG);

	header("Location: ".urldecode($urlsource));

	return;
}
else						// Open and return file
{
	clearstatcache();

	$filename = basename($original_file);

	// Output file on browser
	dol_syslog("document.php download $original_file $filename content-type=$type");
	$original_file_osencoded=dol_osencode($original_file);	// New file name encoded in OS encoding charset

	// This test if file exists should be useless. We keep it to find bug more easily
	if (! file_exists($original_file_osencoded))
	{
		dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$original_file));
		exit;
	}

	// Les drois sont ok et fichier trouve, on l'envoie

	if ($encoding)   header('Content-Encoding: '.$encoding);
	if ($type)       header('Content-Type: '.$type);
	if ($attachment) header('Content-Disposition: attachment; filename="'.$filename.'"');
	else header('Content-Disposition: inline; filename="'.$filename.'"');

	// Ajout directives pour resoudre bug IE
	header('Cache-Control: Public, must-revalidate');
	header('Pragma: public');

	readfile($original_file_osencoded);
}

?>
