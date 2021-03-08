<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/projet/tasks/contact.php
 *	\ingroup    project
 *	\brief      Actors of a task
 *	\version    $Id$
 */

require ("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/html.formcompany.class.php');

$langs->load("projects");
$langs->load("companies");

$taskid = isset($_GET["id"])?$_GET["id"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
//$result = restrictedArea($user, 'projet', $taskid, 'projet_task');
if (!$user->rights->projet->lire) accessforbidden();


/*
 * Actions
 */

// Add new contact
if ($_POST["action"] == 'addcontact' && $user->rights->projet->creer)
{

	$result = 0;
	$task = new Task($db);
	$result = $task->fetch($taskid);

    if ($result > 0 && $taskid > 0)
    {
  		$result = $task->add_contact($_POST["contactid"], $_POST["type"], $_POST["source"]);
    }

	if ($result >= 0)
	{
		Header("Location: contact.php?id=".$task->id);
		exit;
	}
	else
	{
		if ($task->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			$langs->load("errors");
			$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
		}
		else
		{
			$mesg = '<div class="error">'.$task->error.'</div>';
		}
	}
}
// modification d'un contact. On enregistre le type
if ($_POST["action"] == 'updateline' && $user->rights->projet->creer)
{
	$task = new Task($db);
	if ($task->fetch($taskid))
	{
		$contact = $task->detail_contact($_POST["elrowid"]);
		$type = $_POST["type"];
		$statut = $contact->statut;

		$result = $task->update_contact($_POST["elrowid"], $statut, $type);
		if ($result >= 0)
		{
			$db->commit();
		} else
		{
			dol_print_error($db, "result=$result");
			$db->rollback();
		}
	}
	else
	{
		dol_print_error($db);
	}
}

// bascule du statut d'un contact
if ($_GET["action"] == 'swapstatut' && $user->rights->projet->creer)
{
	$task = new Task($db);
	if ($task->fetch($taskid))
	{
		$contact = $task->detail_contact($_GET["ligne"]);
		$id_type_contact = $contact->fk_c_type_contact;
		$statut = ($contact->statut == 4) ? 5 : 4;

		$result = $task->update_contact($_GET["ligne"], $statut, $id_type_contact);
		if ($result >= 0)
		{
			$db->commit();
		} else
		{
			dol_print_error($db, "result=$result");
			$db->rollback();
		}
	}
	else
	{
		dol_print_error($db);
	}
}

// Efface un contact
if ($_GET["action"] == 'deleteline' && $user->rights->projet->creer)
{
	$task = new Task($db);
	$task->fetch($taskid);
	$result = $task->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		Header("Location: contact.php?id=".$task->id);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}


/*
 * View
 */

llxHeader('', $langs->trans("Task"));

$html = new Form($db);
$formcompany   = new FormCompany($db);
$contactstatic = new Contact($db);
$project = new Project($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
if (isset($mesg)) print $mesg;

$id = $_GET['id'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	$task = new Task($db);

	if ( $task->fetch($id,$ref) > 0)
	{
		$result=$project->fetch($task->fk_project);
		if (! empty($project->socid)) $project->societe->fetch($project->socid);

		$head = task_prepare_head($task);
		dol_fiche_head($head, 'contact', $langs->trans("Task"), 0, 'projecttask');


		/*
		 *   Projet synthese pour rappel
		 */
		print '<table class="border" width="100%">';

		//$linkback="<a href=\"".$_SERVER["PHP_SELF"]."?page=$page&socid=$socid&viewstatut=$viewstatut&sortfield=$sortfield&$sortorder\">".$langs->trans("BackToList")."</a>";

		// Ref
		print '<tr><td width="30%">'.$langs->trans('Ref').'</td><td colspan="3">';
		print $html->showrefnav($task,'id','',1,'rowid','ref','','');
		print '</td></tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$task->label.'</td></tr>';

		// Project
		print '<tr><td>'.$langs->trans("Project").'</td><td>';
		print $project->getNomUrl(1);
		print '</td></tr>';

		// Customer
		print "<tr><td>".$langs->trans("Company")."</td>";
		print '<td colspan="3">';
		if ($project->societe->id > 0) print $project->societe->getNomUrl(1);
		else print '&nbsp;';
		print '</td></tr>';

		print "</table>";

		print '</div>';

		/*
		 * Lignes de contacts
		 */
		print '<br><table class="noborder" width="100%">';

		/*
		 * Ajouter une ligne de contact
		 * Non affiche en mode modification de ligne
		 */
		if ($_GET["action"] != 'editline' && $user->rights->projet->creer)
		{
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Source").'</td>';
			print '<td>'.$langs->trans("Company").'</td>';
			print '<td>'.$langs->trans("Contacts").'</td>';
			print '<td>'.$langs->trans("ContactType").'</td>';
			print '<td colspan="3">&nbsp;</td>';
			print "</tr>\n";

			$var = false;

			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="addcontact">';
			print '<input type="hidden" name="source" value="internal">';
			print '<input type="hidden" name="id" value="'.$id.'">';

			// Ligne ajout pour contact interne
			print "<tr $bc[$var]>";

			print '<td nowrap="nowrap">';
			print img_object('','user').' '.$langs->trans("Users");
			print '</td>';

			print '<td colspan="1">';
			print $conf->global->MAIN_INFO_SOCIETE_NOM;
			print '</td>';

			print '<td colspan="1">';
			// On recupere les id des users deja selectionnes
			$html->select_users($user->id,'contactid',0);
			print '</td>';
			print '<td>';
			$formcompany->selectTypeContact($task, '', 'type','internal','rowid');
			print '</td>';
			print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
			print '</tr>';

			print '</form>';

			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="addcontact">';
			print '<input type="hidden" name="source" value="external">';
			print '<input type="hidden" name="id" value="'.$id.'">';

			// Line to add an external contact. Only if project linked to a third party.
			if ($project->socid)
			{
				$var=!$var;
				print "<tr $bc[$var]>";

				print '<td nowrap="nowrap">';
				print img_object('','contact').' '.$langs->trans("ThirdPartyContacts");
				print '</td>';

				print '<td colspan="1">';
				$selectedCompany = isset($_GET["newcompany"])?$_GET["newcompany"]:$project->societe->id;
				$selectedCompany = $formcompany->selectCompaniesForNewContact($task, 'id', $selectedCompany, 'newcompany');
				print '</td>';

				print '<td colspan="1">';
				$nbofcontacts=$html->select_contacts($selectedCompany,'','contactid',0);
				if ($nbofcontacts == 0) print $langs->trans("NoContactDefined");
				print '</td>';
				print '<td>';
				$formcompany->selectTypeContact($task, '', 'type','external','rowid');
				print '</td>';
				print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"';
				if (! $nbofcontacts) print ' disabled="true"';
				print '></td>';
				print '</tr>';
			}

			print "</form>";

			print '<tr><td colspan="6">&nbsp;</td></tr>';
		}

		// Liste des contacts lies
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Source").'</td>';
		print '<td>'.$langs->trans("Company").'</td>';
		print '<td>'.$langs->trans("Contacts").'</td>';
		print '<td>'.$langs->trans("ContactType").'</td>';
		print '<td align="center">'.$langs->trans("Status").'</td>';
		print '<td colspan="2">&nbsp;</td>';
		print "</tr>\n";

		$societe = new Societe($db);
		$var = true;

		foreach(array('internal','external') as $source)
		{
			$tab = $task->liste_contact(-1,$source);
			$num=sizeof($tab);

			$i = 0;
			while ($i < $num)
			{
				$var = !$var;

				print '<tr '.$bc[$var].' valign="top">';

				// Source
				print '<td align="left">';
				if ($tab[$i]['source']=='internal') print $langs->trans("User");
				if ($tab[$i]['source']=='external') print $langs->trans("ThirdPartyContact");
				print '</td>';

				// Societe
				print '<td align="left">';
				if ($tab[$i]['socid'] > 0)
				{
					print '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$tab[$i]['socid'].'">';
					print img_object($langs->trans("ShowCompany"),"company").' '.$societe->get_nom($tab[$i]['socid']);
					print '</a>';
				}
				if ($tab[$i]['socid'] < 0)
				{
					print $conf->global->MAIN_INFO_SOCIETE_NOM;
				}
				if (! $tab[$i]['socid'])
				{
					print '&nbsp;';
				}
				print '</td>';

				// Contact
				print '<td>';
				if ($tab[$i]['source']=='internal')
				{
					print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$tab[$i]['id'].'">';
					print img_object($langs->trans("ShowUser"),"user").' '.$tab[$i]['nom'].'</a>';
				}
				if ($tab[$i]['source']=='external')
				{
					print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$tab[$i]['id'].'">';
					print img_object($langs->trans("ShowContact"),"contact").' '.$tab[$i]['nom'].'</a>';
				}
				print '</td>';

				// Type de contact
				print '<td>'.$tab[$i]['libelle'].'</td>';

				// Statut
				print '<td align="center">';
				// Activation desativation du contact
				if ($task->statut >= 0) print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$task->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">';
				print $contactstatic->LibStatut($tab[$i]['status'],3);
				if ($task->statut >= 0) print '</a>';
				print '</td>';

				// Icon update et delete
				print '<td align="center" nowrap>';
				if ($user->rights->projet->creer)
				{
					print '&nbsp;';
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$task->id.'&amp;action=deleteline&amp;lineid='.$tab[$i]['rowid'].'">';
					print img_delete();
					print '</a>';
				}
				print '</td>';

				print "</tr>\n";

				$i ++;
			}
		}
		print "</table>";
	}
	else
	{
		print "ErrorRecordNotFound";
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
