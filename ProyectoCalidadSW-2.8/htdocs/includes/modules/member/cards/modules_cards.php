<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
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
 *	\file       htdocs/includes/modules/facture/modules_facture.php
 *	\ingroup    facture
 *	\brief      Fichier contenant la classe mere de generation des factures en PDF
 * 				et la classe mere de numerotation des factures
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');
require_once(DOL_DOCUMENT_ROOT."/product.class.php");


/**
 *	\class      ModelePDFFactures
 *	\brief      Classe mere des modeles de facture
 */
class ModelePDFCards extends FPDF
{
	var $error='';

	/**
	 *       \brief      Renvoi le dernier message d'erreur de creation de facture
	 */
	function pdferror()
	{
		return $this->error;
	}

	/**
	 *      \brief      Renvoi la liste des modeles actifs
	 *      \param      db      Handler de base
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='members_card';
		$liste=array();
		$sql = "SELECT nom as id, nom as lib";
		$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
		$sql.= " WHERE type = '".$type."'";
		$sql.= " AND entity = ".$conf->entity;

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				$liste[$row[0]]=$row[1];
				$i++;
			}
		}
		else
		{
			dol_print_error($db);
			return -1;
		}
		return $liste;
	}

}


/**
 *	\brief   	Cree un fichier de cartes de visites en fonction du modele de ADHERENT_CARDS_ADDON_PDF
 *	\param   	db  			objet base de donnee
 *	\param   	id				id de la facture a creer
 *	\param	    message			message
 *	\param	    modele			force le modele a utiliser ('' to not force)
 *	\param		outputlangs		objet lang a utiliser pour traduction
 *	\return  	int        		<0 if KO, >0 if OK
 */
function members_card_pdf_create($db, $arrayofmembers, $modele, $outputlangs)
{
	global $conf,$langs;
	$langs->load("members");

	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/member/cards/";

	// Positionne modele sur le nom du modele a utiliser
	if (! strlen($modele))
	{
		if ($conf->global->ADHERENT_CARDS_ADDON_PDF)
		{
			$modele = $conf->global->ADHERENT_CARDS_ADDON_PDF;
		}
		else
		{
			$modele = 'standard';
		}
	}


	// Charge le modele
	$file = "pdf_".$modele.".class.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($arrayofmembers, $outputlangs) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"members_card_pdf_create Error: ".$obj->error);
			return -1;
		}
	}

	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file));
		return -1;
	}


}

?>