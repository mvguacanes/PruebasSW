<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2010 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/projet/tasks/index.php
 *	\ingroup    project
 *	\brief      Fiche taches d'un projet
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

$langs->load('projects');

// Security check
$socid=0;
if (!$user->rights->projet->lire) accessforbidden();
if ($user->societe_id > 0) $socid = $user->societe_id;

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])? $_GET["page"]:$_POST["page"];
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;


/*
 * View
 */
$mine = $_REQUEST['mode']=='mine' ? 1 : 0;

$title=$langs->trans("Tasks");
if ($mine) $title=$langs->trans("MyTasks");

llxHeader("",$title,"Projet");

$form=new Form($db);

$projectstatic = new Project($db);
$taskstatic = new Task($db);

if ($_GET["id"])
{
	$projectstatic->fetch($_GET["id"]);
	$projectstatic->societe->fetch($projectstatic->societe->id);
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num);

// Get list of tasks in tasksarray and taskarrayfiltered
// We need all tasks (even not limited to a user because a task to user
// can have a parent that is not affected to him).
$tasksarray=$taskstatic->getTasksArray(0, 0, $projectstatic->id, $socid);
// We load also tasks limited to a particular user
$tasksrole=($mine ? $taskstatic->getUserRolesForProjectsOrTasks(0,$user,$projectstatic->id,0) : '');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="80">'.$langs->trans("RefTask").'</td>';
print '<td>'.$langs->trans("LabelTask").'</td>';
print '<td>'.$langs->trans("Project").'</td>';
print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
print "</tr>\n";
// Show all lines in taskarray (recursive function to go down on tree)
$j=0; $level=0;
$nboftaskshown=PLines($j, 0, $tasksarray, $level, true, 1, $tasksrole);
print "</table>";

print '</div>';

/*
 * Actions
 */
if ($user->rights->projet->creer)
{
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.DOL_URL_ROOT.'/projet/tasks/fiche.php?action=create">'.$langs->trans('AddTask').'</a>';
	print '</div>';
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
