<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/includes/modules/expedition/pdf/ModelePdfExpedition.class.php
 *  \ingroup    shipping
 *  \brief      Fichier contenant la classe mere de generation des expeditions
 *  \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');


/**
 *  \class      ModelePdfExpedition
 *  \brief      Parent class of ssending receipts models
 */

class ModelePdfExpedition extends FPDF
{
    var $error='';


   /**
        \brief Renvoi le dernier message d'erreur de creation de PDF de commande
    */
    function pdferror()
    {
        return $this->error;
    }


    /**
     *      \brief      Renvoi la liste des mod�les actifs
     *      \return    array        Tableau des modeles (cle=id, valeur=libelle)
     */
    function liste_modeles($db)
    {
    	global $conf;
    	
    	$type='shipping';
    	$liste=array();
    	$sql ="SELECT nom as id, nom as lib";
    	$sql.=" FROM ".MAIN_DB_PREFIX."document_model";
    	$sql.=" WHERE type = '".$type."'";
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
		\brief      Cree un bon d'expedition sur disque
		\param	    db  			objet base de donnee
		\param	    id				id de la expedition a creer
		\param	    modele			force le modele a utiliser ('' to not force)
		\param		outputlangs		objet lang a utiliser pour traduction
*/
function expedition_pdf_create($db, $id, $modele, $outputlangs)
{
	global $conf,$langs;
	$langs->load("sendings");

	$dir = DOL_DOCUMENT_ROOT."/includes/modules/expedition/pdf/";
	$modelisok=0;

	// Positionne modele sur le nom du modele de commande a utiliser
	$file = "pdf_expedition_".$modele.".modules.php";
	if ($modele && file_exists($dir.$file)) $modelisok=1;

    // Si model pas encore bon
	if (! $modelisok)
	{
		if ($conf->global->EXPEDITION_ADDON_PDF) $modele = $conf->global->EXPEDITION_ADDON_PDF;
      	$file = "pdf_expedition_".$modele.".modules.php";
    	if (file_exists($dir.$file)) $modelisok=1;
    }

    // Si model pas encore bon
	if (! $modelisok)
	{
	    $liste=array();
		$model=new ModelePDFExpedition();
		$liste=$model->liste_modeles($db);
        $modele=key($liste);        // Renvoie premiere valeur de cle trouve dans le tableau
      	$file = "pdf_expedition_".$modele.".modules.php";
    	if (file_exists($dir.$file)) $modelisok=1;
	}

	// Charge le modele
    if ($modelisok)
	{
		$classname = "pdf_expedition_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);

		$expedition = new Expedition($db);
		$result=$expedition->fetch($id);
		$result=$expedition->fetch_object($expedition->origin);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($expedition, $langs) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;
			// on supprime l'image correspondant au preview
			//expedition_delete_preview($db, $id);
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_syslog("Erreur dans expedition_pdf_create");
			dol_print_error($db,$obj->pdferror());
			return 0;
		}
	}
	else
	{
        if (! $conf->global->EXPEDITION_ADDON_PDF)
        {
			print $langs->trans("Error")." ".$langs->trans("Error_EXPEDITION_ADDON_PDF_NotDefined");
        }
        else
        {
    		print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file);
        }
		return 0;
   }
}

?>
