<?PHP
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *	\file       htdocs/compta/prelevement/config.php
 *	\ingroup    prelevement
 *	\brief      Page configuration des prelevements
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement', '', '', 'bons');


if ($_GET["action"] == "set" && $user->rights->prelevement->bons->configurer)
{
	for ($i = 1 ; $i < 7 ; $i++)
	{
		dolibarr_set_const($db, $_POST["nom$i"], $_POST["value$i"],'chaine',0,'',$conf->entity);
	}

	Header("Location: config.php");
	exit;
}

if ($_GET["action"] == "addnotif" && $user->rights->prelevement->bons->configurer)
{
	$bon = new BonPrelevement($db);
	$bon->AddNotification($_POST["user"],$_POST["action"]);

	Header("Location: config.php");
	exit;
}

if ($_GET["action"] == "deletenotif" && $user->rights->prelevement->bons->configurer)
{
	$bon = new BonPrelevement($db);
	$bon->DeleteNotificationById($_GET["notif"]);

	Header("Location: config.php");
	exit;
}

/*
 *	View
 */

llxHeader('',$langs->trans("WithdrawalsSetup"));

print_fiche_titre($langs->trans("WithdrawalsSetup"));


if ($user->rights->prelevement->bons->configurer)
print '<form method="post" action="config.php?action=set">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<table class="nobordernopadding" width="100%">';
print '<tr class="liste_titre">';
print '<td width="30%">'.$langs->trans("Parameter").'</td>';
if ($user->rights->prelevement->bons->configurer)
print '<td width="40%">'.$langs->trans("Value").'</td>';

print '<td width="30%">'.$langs->trans("CurrentValue").'</td>';
print "</tr>\n";

print '<tr class="pair"><td>'.$langs->trans("NumeroNationalEmetter").'</td>';
if ($user->rights->prelevement->bons->configurer)
{
	print '<td align="left">';
	print '<input type="hidden" name="nom1" value="PRELEVEMENT_NUMERO_NATIONAL_EMETTEUR">';
	print '<input type="text"   name="value1" value="'.PRELEVEMENT_NUMERO_NATIONAL_EMETTEUR.'" size="9" ></td>';
}
print '<td>'.PRELEVEMENT_NUMERO_NATIONAL_EMETTEUR.'</td></tr>';

print '<tr class="impair"><td>'.$langs->trans("Name").'</td>';
if ($user->rights->prelevement->bons->configurer)
{
	print '<td align="left">';
	print '<input type="hidden" name="nom2" value="PRELEVEMENT_RAISON_SOCIALE">';
	print '<input type="text"   name="value2" value="'.PRELEVEMENT_RAISON_SOCIALE.'" size="14" ></td>';
}
print '<td>'.PRELEVEMENT_RAISON_SOCIALE.'</td></tr>';

print '<tr class="pair"><td>'.$langs->trans("BankCode").'</td>';
if ($user->rights->prelevement->bons->configurer)
{
	print '<td align="left">';
	print '<input type="hidden" name="nom3" value="PRELEVEMENT_CODE_BANQUE">';
	print '<input type="text"   name="value3" value="'.PRELEVEMENT_CODE_BANQUE.'" size="6" ></td>';
}
print '<td>'.PRELEVEMENT_CODE_BANQUE.'</td></tr>';

print '<tr class="impair"><td>'.$langs->trans("DeskCode").'</td>';
if ($user->rights->prelevement->bons->configurer)
{
	print '<td align="left">';
	print '<input type="hidden" name="nom4" value="PRELEVEMENT_CODE_GUICHET">';
	print '<input type="text"   name="value4" value="'.PRELEVEMENT_CODE_GUICHET.'" size="6" ></td>';
}
print '<td>'.PRELEVEMENT_CODE_GUICHET.'</td></tr>';

print '<tr class="pair"><td>'.$langs->trans("AccountNumber").'</td>';
if ($user->rights->prelevement->bons->configurer)
{
	print '<td align="left">';
	print '<input type="hidden" name="nom5" value="PRELEVEMENT_NUMERO_COMPTE">';
	print '<input type="text"   name="value5" value="'.PRELEVEMENT_NUMERO_COMPTE.'" size="11" ></td>';
}
print '<td>'.PRELEVEMENT_NUMERO_COMPTE.'</td></tr>';

print '<tr class="impair"><td>'.$langs->trans("ResponsibleUser").'</td>';
if ($user->rights->prelevement->bons->configurer)
{
	print '<td align="left">';
	print '<input type="hidden" name="nom6" value="PRELEVEMENT_USER">';
	print '<select name="value6">';

	$sql = "SELECT u.rowid, u.name, u.firstname";
	$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
	$sql.= " WHERE u.entity IN (0,".$conf->entity.")";

	if ($db->query($sql))
	{
		$num = $db->num_rows();
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object();
			print '<option value="'.$obj->rowid.'">'.$obj->firstname." ".$obj->name;
			$i++;
		}
		$db->free();
	}

	print '</select></td>';
}
print '<td>';
if (defined("PRELEVEMENT_USER") && PRELEVEMENT_USER > 0)
{
	$cuser = new User($db, PRELEVEMENT_USER);
	$cuser->fetch();
	print $cuser->fullname;
}
else
{
	print PRELEVEMENT_USER;
}

print '</td></tr>';

if ($user->rights->prelevement->bons->configurer)
print '<tr><td align="center" colspan="3"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';

print '</table>';

if ($user->rights->prelevement->bons->configurer)
print '</form>';

print '<br>';




/*
 * Notifications
 * TODO Use notification module instead
 */

if ($conf->global->MAIN_MODULE_NOTIFICATION)
{
	print_titre($langs->trans("Notifications"));

	if ($user->rights->prelevement->bons->configurer)
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=addnotif">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("User").'</td>';
	print '<td>'.$langs->trans("Value").'</td>';
	if ($user->rights->prelevement->bons->configurer)
	print '<td align="right">'.$langs->trans("Action").'</td>';
	print "</tr>\n";

	if ($user->rights->prelevement->bons->configurer)
	{
		print '<tr class="impair"><td align="left">';
		print '<input type="hidden" name="nom6" value="PRELEVEMENT_USER">';
		print '<select name="user">';
		$sql = "SELECT u.rowid, u.name, u.firstname";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
		$sql.= " ORDER BY u.name ASC";

		if ($db->query($sql))
		{
			$num = $db->num_rows();
			$i = 0;
			while ($i < $num)
			{
		  $obj = $db->fetch_object();
		  print '<option value="'.$obj->rowid.'">'.$obj->firstname." ".$obj->name;
		  $i++;
			}
			$db->free();
		}

		print '</select></td>';

		print '<td>';
		print '<select name="action">';

		print '<option value="tr">Transmission du bon</option>';
		print '<option value="em">Emission du bon</option>';
		print '<option value="cr">Credit du bon</option>';
		print '</select></td>';

		print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td></tr>';
	}


	$sql = "SELECT u.name, u.firstname";
	$sql.= ", pn.action, pn.rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
	$sql.= ", ".MAIN_DB_PREFIX."prelevement_notifications as pn";
	$sql.= " WHERE u.rowid = pn.fk_user";
	$sql.= " AND u.entity IN (0,".$conf->entity.")";

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		$var = false;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

			$var=!$var;
			print "<tr $bc[$var]>";

			print '<td>'.$obj->firstname." ".$obj->name.'</td>';
			print '<td>'.$obj->action.'</td>';

			if ($user->rights->prelevement->bons->configurer)
			{
				print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=deletenotif&amp;notif='.$obj->rowid.'">'.img_delete().'</a></td></tr>';
			}
			else
			{
				print '</tr>';
			}
			$i++;
		}
		$db->free($resql);
	}
	print '</table>';

	if ($user->rights->prelevement->bons->configurer)
	print '</form>';
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
