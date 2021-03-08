<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville         <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio          <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier               <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2009 Regis Houssin                <regis@dolibarr.fr>
 * Copyright (C) 2008 	   Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
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
 *	\file       htdocs/admin/fichinter.php
 *	\ingroup    fichinter
 *	\brief      Setup page of module Interventions
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/fichinter/fichinter.class.php');

$langs->load("admin");
$langs->load("bills");
$langs->load("other");
$langs->load("interventions");

if (!$user->admin)
accessforbidden();


/*
 * Actions
 */
if ($_POST["action"] == 'updateMask')
{
	$maskconst=$_POST['maskconst'];
	$maskvalue=$_POST['maskvalue'];
	if ($maskconst) dolibarr_set_const($db,$maskconst,$maskvalue,'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'set_FICHINTER_FREE_TEXT')
{
	dolibarr_set_const($db, "FICHINTER_FREE_TEXT",$_POST["FICHINTER_FREE_TEXT"],'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'set_FICHINTER_DRAFT_WATERMARK')
{
	dolibarr_set_const($db, "FICHINTER_DRAFT_WATERMARK",trim($_POST["FICHINTER_DRAFT_WATERMARK"]),'chaine',0,'',$conf->entity);
}

if ($_GET["action"] == 'specimen')
{
	$modele=$_GET["module"];

	$inter = new Fichinter($db);
	$inter->initAsSpecimen();

	// Charge le modele
	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/fichinter/";
	$file = "pdf_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);

		if ($obj->write_file($inter,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=ficheinter&file=SPECIMEN.pdf");
			return;
		}
		else
		{
			$mesg='<div class="error">'.$obj->error.'</div>';
			dol_syslog($obj->error, LOG_ERR);
		}
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorModuleNotFound").'</div>';
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
}

if ($_GET["action"] == 'set')
{
	$type='ficheinter';
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES ('".$_GET["value"]."','".$type."',".$conf->entity.")";
	if ($db->query($sql))
	{

	}
}

if ($_GET["action"] == 'del')
{
	$type='ficheinter';
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql.= " WHERE nom = '".$_GET["value"]."'";
	$sql.= " AND type = '".$type."'";
	$sql.= " AND entity = ".$conf->entity;

	if ($db->query($sql))
	{

	}
}

if ($_GET["action"] == 'setdoc')
{
	$db->begin();

	if (dolibarr_set_const($db, "FICHEINTER_ADDON_PDF",$_GET["value"],'chaine',0,'',$conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->FICHEINTER_ADDON_PDF = $_GET["value"];
	}

	// On active le modele
	$type='ficheinter';
	$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql_del.= " WHERE nom = '".$_GET["value"]."'";
	$sql_del.= " AND type = '".$type."'";
	$sql_del.= " AND entity = ".$conf->entity;
	dol_syslog("fichinter: sql_del=".$sql_del);
	$result1=$db->query($sql_del);

	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom,type,entity) VALUES ('".$_GET["value"]."','".$type."',".$conf->entity.")";
	dol_syslog("fichinter: sql_del=".$sql_del);
	$result2=$db->query($sql);
	if ($result1 && $result2)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
}

if ($_GET["action"] == 'setmod')
{
	// \todo Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "FICHEINTER_ADDON",$_GET["value"],'chaine',0,'',$conf->entity);
}

// defini les constantes du modele arctic
if ($_POST["action"] == 'updateMatrice') dolibarr_set_const($db, "FICHEINTER_NUM_MATRICE",$_POST["matrice"],'chaine',0,'',$conf->entity);
if ($_POST["action"] == 'updatePrefix') dolibarr_set_const($db, "FICHEINTER_NUM_PREFIX",$_POST["prefix"],'chaine',0,'',$conf->entity);
if ($_POST["action"] == 'setOffset') dolibarr_set_const($db, "FICHEINTER_NUM_DELTA",$_POST["offset"],'chaine',0,'',$conf->entity);
if ($_POST["action"] == 'setNumRestart') dolibarr_set_const($db, "FICHEINTER_NUM_RESTART_BEGIN_YEAR",$_POST["numrestart"],'chaine',0,'',$conf->entity);


/*
 * Affichage page
 */

llxHeader();

$dir=DOL_DOCUMENT_ROOT."/includes/modules/fichinter/";
$html=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("InterventionsSetup"),$linkback,'setup');

print "<br>";


print_titre($langs->trans("FicheinterNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$handle = opendir($dir);
if ($handle)
{
	$var=true;

	while (($file = readdir($handle))!==false)
	{
		if (preg_match('/^(mod_.*)\.php$/i',$file,$reg))
		{
			$file = $reg[1];
			$className = substr($file,4);

			require_once($dir.$file.".php");

			$module = new $file;

			// Show modules according to features level
			if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
			if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

			if ($module->isEnabled())
			{
				$var=!$var;
				print '<tr '.$bc[$var].'><td>'.$module->nom."</td><td>\n";
				print $module->info();
				print '</td>';

				// Examples
				print '<td nowrap="nowrap">'.$module->getExample()."</td>\n";

				print '<td align="center">';
				if ($conf->global->FICHEINTER_ADDON == $className)
				{
					print img_tick($langs->trans("Activated"));
				}
				else
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$className.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
				}
				print '</td>';

				$ficheinter=new Fichinter($db);
				$ficheinter->initAsSpecimen();

				// Info
				$htmltooltip='';
				$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
				$nextval=$module->getNextValue($mysoc,$ficheinter);
				if ($nextval != $langs->trans("NotAvailable"))
				{
					$htmltooltip.=''.$langs->trans("NextValue").': '.$nextval;
				}
				print '<td align="center">';
				print $html->textwithpicto('',$htmltooltip,1,0);
				print '</td>';

				print '</tr>';
			}
		}
	}
	closedir($handle);
}

print '</table><br>';



print_titre($langs->trans("TemplatePDFInterventions"));

// Defini tableau def des modeles
$type='ficheinter';
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$type."'";
$sql.= " AND entity = ".$conf->entity;
$resql=$db->query($sql);
if ($resql)
{
	$i = 0;
	$num_rows=$db->num_rows($resql);
	while ($i < $num_rows)
	{
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
}
else
{
	dol_print_error($db);
}


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$var=true;

$handle=opendir($dir);
while (($file = readdir($handle))!==false)
{
	if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,4) == 'pdf_')
	{
		$name = substr($file, 4, strlen($file) -16);
		$classname = substr($file, 0, strlen($file) -12);

		$var=!$var;

		print '<tr '.$bc[$var].'><td>';
		echo "$name";
		print "</td><td>\n";
		require_once($dir.$file);
		$module = new $classname();
		print $module->description;
		print '</td>';

		// Active
		if (in_array($name, $def))
		{
			print "<td align=\"center\">\n";
			if ($conf->global->FICHEINTER_ADDON_PDF != "$name")
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'">';
				print img_tick($langs->trans("Disable"));
				print '</a>';
			}
			else
			{
				print img_tick($langs->trans("Enabled"));
			}
			print "</td>";
		}
		else
		{
			print "<td align=\"center\">\n";
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';
			print "</td>";
		}

		// Defaut
		print "<td align=\"center\">";
		if ($conf->global->FICHEINTER_ADDON_PDF == "$name")
		{
			print img_tick($langs->trans("Default"));
		}
		else
		{
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
		}
		print '</td>';

		// Info
		$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
		$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
		$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
		$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
		$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
		$htmltooltip.='<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg,1,1);
		$htmltooltip.='<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg,1,1);
		$htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang,1,1);
		$htmltooltip.='<br>'.$langs->trans("WatermarkOnDraftOrders").': '.yn($module->option_draft_watermark,1,1);
		print '<td align="center">';
		print $html->textwithpicto('',$htmltooltip,1,0);
		print '</td>';
		print '<td align="center">';
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'intervention').'</a>';
		print '</td>';

		print '</tr>';
	}
}
closedir($handle);

print '</table>';

//Autres Options
print "<br>";
print_titre($langs->trans("OtherOptions"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print "<td>&nbsp;</td>\n";
print "</tr>\n";
$var=true;

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FICHINTER_FREE_TEXT">';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("FreeLegalTextOnInterventions").' ('.$langs->trans("AddCRIfTooLong").')<br>';
print '<textarea name="FICHINTER_FREE_TEXT" class="flat" cols="120">'.$conf->global->FICHINTER_FREE_TEXT.'</textarea>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

//Use draft Watermark
$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"set_FICHINTER_DRAFT_WATERMARK\">";
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("WatermarkOnDraftInterventionCards").'<br>';
print '<input size="50" class="flat" type="text" name="FICHINTER_DRAFT_WATERMARK" value="'.$conf->global->FICHINTER_DRAFT_WATERMARK.'">';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';


print '</table>';

print '<br>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
