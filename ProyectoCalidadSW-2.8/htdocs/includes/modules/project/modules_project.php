<?php
/* Copyright (C) 20010 Regis Houssin  <regis@dolibarr.fr>
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
 *		\file       htdocs/includes/modules/project/modules_project.php
 *      \ingroup    project
 *      \brief      File that contain parent class for projects models
 *                  and parent class for projects numbering models
 *      \version    $Id$
 */
require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');


/**
 *	\class      ModelePDFProjects
 *	\brief      Parent class for projects models
 */

class ModelePDFProjects extends FPDF
{
	var $error='';

	/**
	 *  \brief Renvoi le dernier message d'erreur de creation de PDF du projet
	 */
	function pdferror()
	{
		return $this->error;
	}

	/**
	 *      \brief      Renvoi la liste des modeles actifs
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='project';
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
 *  \class      ModeleNumRefProjects
 *  \brief      Classe mere des modeles de numerotation des references de projets
 */

class ModeleNumRefProjects
{
	var $error='';

	/**
	 *  \brief     	Return if a module can be used or not
	 *  \return		boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**
	 *  \brief      Renvoi la description par defaut du modele de numerotation
	 *  \return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("projects");
		return $langs->trans("NoDescription");
	}

	/**
	 *  \brief      Renvoi un exemple de numerotation
	 *  \return     string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("projects");
		return $langs->trans("NoExample");
	}

	/**
	 *  \brief      Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *                  de conflits qui empechera cette numerotation de fonctionner.
	 *  \return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**
	 *  \brief      Renvoi prochaine valeur attribuee
	 *  \return     string      Valeur
	 */
	function getNextValue()
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *  \brief      Renvoi version du module numerotation
	 *  \return     string      Valeur
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
 *		\brief      Create object on disk
 *		\param	    db  			objet base de donnee
 *		\param	    deliveryid		id object
 *		\param	    modele			force le modele a utiliser ('' to not force)
 *		\param		outputlangs		objet lang a utiliser pour traduction
 *      \return     int         	0 si KO, 1 si OK
 */
function project_pdf_create($db, $comid, $modele,$outputlangs)
{
	global $conf,$langs;
	$langs->load("projects");

	$dir = DOL_DOCUMENT_ROOT."/includes/modules/project/pdf/";

	// Positionne modele sur le nom du modele de projet a utiliser
	if (! strlen($modele))
	{
		if (! empty($conf->global->PROJECT_ADDON_PDF))
		{
			$modele = $conf->global->PROJECT_ADDON_PDF;
		}
		else
		{
			print $langs->trans("Error")." ".$langs->trans("Error_PROJECT_ADDON_PDF_NotDefined");
			return 0;
		}
	}

	// Charge le modele
	$file = "pdf_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($comid,$outputlangs) > 0)
		{
			// on supprime l'image correspondant au preview
			project_delete_preview($db, $comid);

			$outputlangs->charset_output=$sav_charset_output;
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_syslog("Erreur dans project_pdf_create");
			dol_print_error($db,$obj->pdferror());
			return 0;
		}
	}
	else
	{
		print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file);
		return 0;
	}
}
function project_delete_preview($db, $projectid)
{
	global $langs,$conf;

	$project = new Project($db,"",$projectid);
	$project->fetch($projectid);
	$client = new Societe($db);
	$client->fetch($project->socid);

	if ($conf->projet->dir_output.'/commande')
	{
		$projectRef = dol_sanitizeFileName($project->ref);
		$dir = $conf->projet->dir_output . "/" . $projectRef ;
		$file = $dir . "/" . $projectRef . ".pdf.png";

		if ( file_exists( $file ) && is_writable( $file ) )
		{
			if ( ! dol_delete_file($file) )
			{
				$this->error=$langs->trans("ErrorFailedToOpenFile",$file);
				return 0;
			}
		}
	}

	return 1;
}
?>
