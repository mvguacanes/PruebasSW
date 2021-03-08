<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
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
 *  \file			htdocs/includes/modules/commande/modules_commande.php
 *  \ingroup		commande
 *  \brief			Fichier contenant la classe mere de generation des commandes en PDF
 *  				et la classe mere de numerotation des commandes
 *  \version    	$Id$
 */

require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');
require_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");	// requis car utilise par les classes qui heritent
require_once(DOL_DOCUMENT_ROOT.'/discount.class.php');


/**
 *  \class      ModelePDFCommandes
 *  \brief      Classe mere des modeles de commandes
 */
class ModelePDFCommandes extends FPDF
{
	var $error='';

	/**
	 *	\brief 	Renvoi le dernier message d'erreur de creation de PDF de commande
	 */
	function pdferror()
	{
		return $this->error;
	}

	/**
	 *      \brief      Renvoi la liste des modeles actifs
	 *      \return    array        Tableau des modeles (cle=id, valeur=libelle)
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='order';
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
			$this->error=$db->error();
			return -1;
		}
		return $liste;
	}

}



/**
 *  \class      ModeleNumRefCommandes
 *  \brief      Classe mere des modeles de numerotation des references de commandes
 */

class ModeleNumRefCommandes
{
	var $error='';

	/**     \brief     	Return if a module can be used or not
	 *      	\return		boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**     \brief      Renvoi la description par defaut du modele de numerotation
	 *      \return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("orders");
		return $langs->trans("NoDescription");
	}

	/**     \brief      Renvoi un exemple de numerotation
	 *      \return     string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("orders");
		return $langs->trans("NoExample");
	}

	/**     \brief      Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *                  de conflits qui empechera cette numerotation de fonctionner.
	 *      \return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**     \brief      Renvoi prochaine valeur attribuee
	 *      \return     string      Valeur
	 */
	function getNextValue()
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**     \brief      Renvoi version du module numerotation
	 *      	\return     string      Valeur
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		return $langs->trans("NotAvailable");
	}
}


/**
 *  \brief      Cree un bon de commande sur disque en fonction d'un modele
 *  \param	    db  			objet base de donnee
 *  \param	    id				id de la propale a creer
 *  \param	    modele			force le modele a utiliser ('' to not force)
 *  \param		outputlangs		objet lang a utiliser pour traduction
 */
function commande_pdf_create($db, $id, $modele, $outputlangs)
{
	global $conf,$langs;
	$langs->load("orders");

	$dir = DOL_DOCUMENT_ROOT."/includes/modules/commande/";
	$modelisok=0;
	$liste=array();

	// Positionne modele sur le nom du modele de commande a utiliser
	$file = "pdf_".$modele.".modules.php";
	if ($modele && file_exists($dir.$file))   $modelisok=1;

	// Si model pas encore bon
	if (! $modelisok)
	{
		if ($conf->global->COMMANDE_ADDON_PDF) $modele = $conf->global->COMMANDE_ADDON_PDF;
		$file = "pdf_".$modele.".modules.php";
		if (file_exists($dir.$file))   $modelisok=1;
	}

	// Si model pas encore bon
	if (! $modelisok)
	{
		$model=new ModelePDFCommandes();
		$liste=$model->liste_modeles($db);
		$modele=key($liste);        // Renvoie premiere valeur de cle trouve dans le tableau
		$file = "pdf_".$modele.".modules.php";
		if (file_exists($dir.$file))   $modelisok=1;
	}

	// Charge le modele
	if ($modelisok)
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($id, $outputlangs) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;
			// on supprime l'image correspondant au preview
			commande_delete_preview($db, $id);
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_syslog("Erreur dans commande_pdf_create");
			dol_print_error($db,$obj->pdferror());
			return 0;
		}
	}
	else
	{
		if (! $conf->global->COMMANDE_ADDON_PDF)
		{
			print $langs->trans("Error")." ".$langs->trans("Error_COMMANDE_ADDON_PDF_NotDefined");
		}
		else
		{
			print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file);
		}
		return 0;
	}
}

/**
 *  \brief      Supprime l'image de previsualitation, pour le cas de regeneration de commande
 *  \param	    db  		objet base de donnee
 *  \param	    commandeid	id de la commande a effacer
 *  \param      commanderef reference de la commande si besoin
 */
function commande_delete_preview($db, $commandeid, $commanderef='')
{
	global $langs,$conf;

	if (!$commanderef)
	{
		$com = new Commande($db,"",$commandeid);
		$com->fetch($commandeid);
		$commanderef = $com->ref;
	}

	if ($conf->commande->dir_output)
	{
		$comref = dol_sanitizeFileName($commanderef);
		$dir = $conf->commande->dir_output . "/" . $comref ;
		$file = $dir . "/" . $comref . ".pdf.png";
		$multiple = $file . ".";

		if ( file_exists( $file ) && is_writable( $file ) )
		{
			if ( ! dol_delete_file($file,1) )
			{
				$this->error=$langs->trans("ErrorFailedToOpenFile",$file);
				return 0;
			}
		}
		else
		{
			for ($i = 0; $i < 20; $i++)
			{
				$preview = $multiple.$i;

				if ( file_exists( $preview ) && is_writable( $preview ) )
				{
					if ( ! dol_delete_file($preview,1) )
					{
						$this->error=$langs->trans("ErrorFailedToOpenFile",$preview);
						return 0;
					}
				}
			}
		}
	}

	return 1;
}
?>
