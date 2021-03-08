<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file		htdocs/lib/agenda.lib.php
 *  \brief		Set of function for the agenda module
 *  \version	$Id$
 */


/**
 * Show filter form in agenda view
 *
 * @param unknown_type $canedit
 * @param unknown_type $status
 * @param unknown_type $year
 * @param unknown_type $month
 * @param unknown_type $day
 * @param unknown_type $showborthday
 * @param unknown_type $action
 * @param unknown_type $filtera
 * @param unknown_type $filtert
 * @param unknown_type $filterd
 * @param unknown_type $pid
 * @param unknown_type $socid
 */
function print_actions_filter($form,$canedit,$status,$year,$month,$day,$showborthday,$action,$filtera,$filtert,$filterd,$pid,$socid)
{
	global $conf,$langs;

	// Filters
	if ($canedit || $conf->projet->enabled)
	{
		print '<form name="listactionsfilter" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="status" value="'.$status.'">';
		print '<input type="hidden" name="year" value="'.$year.'">';
		print '<input type="hidden" name="month" value="'.$month.'">';
		print '<input type="hidden" name="day" value="'.$day.'">';
		print '<input type="hidden" name="showbirthday" value="'.$showbirthday.'">';
		print '<input type="hidden" name="action" value="'.$action.'">';
		print '<table class="border" width="100%">';
		if ($canedit || $conf->projet->enabled)
		{
			print '<tr><td nowrap="nowrap">';

			print '<table class="nobordernopadding">';

			if ($canedit)
			{
				print '<tr>';
				print '<td nowrap="nowrap">';
				print $langs->trans("ActionsAskedBy");
				print ' &nbsp;</td><td nowrap="nowrap">';
				print $form->select_users($filtera,'userasked',1,'',!$canedit);
				print '</td>';
				print '</tr>';

				print '<tr>';
				print '<td nowrap="nowrap">';
				print $langs->trans("or").' '.$langs->trans("ActionsToDoBy");
				print ' &nbsp;</td><td nowrap="nowrap">';
				print $form->select_users($filtert,'usertodo',1,'',!$canedit);
				print '</td></tr>';

				print '<tr>';
				print '<td nowrap="nowrap">';
				print $langs->trans("or").' '.$langs->trans("ActionsDoneBy");
				print ' &nbsp;</td><td nowrap="nowrap">';
				print $form->select_users($filterd,'userdone',1,'',!$canedit);
				print '</td></tr>';
			}

			if ($conf->projet->enabled)
			{
				print '<tr>';
				print '<td nowrap="nowrap">';
				print $langs->trans("Project").' &nbsp; ';
				print '</td><td nowrap="nowrap">';
				select_projects($socid,$pid,'projectid');
				print '</td></tr>';
			}

			print '</table>';
			print '</td>';

			// Buttons
			print '<td align="center" valign="middle" nowrap="nowrap">';
			print img_picto($langs->trans("ViewList"),'object_list').' <input type="submit" class="button" name="viewlist" value="'.$langs->trans("ViewList").'">';
			print '<br>';
			print '<br>';
			print img_picto($langs->trans("ViewCal"),'object_calendar').' <input type="submit" class="button" name="viewcal" value="'.$langs->trans("ViewCal").'">';
			print '</td>';
			print '</tr>';
		}
		print '</table>';
		print '</form><br>';
	}
}


/**
 *  \brief     	Show actions to do array
 *  \param		max		Max nb of records
 */
function show_array_actions_to_do($max=5)
{
	global $langs, $conf, $user, $db, $bc, $socid;

	include_once(DOL_DOCUMENT_ROOT.'/actioncomm.class.php');
	include_once(DOL_DOCUMENT_ROOT.'/client.class.php');

	$sql = "SELECT a.id, a.label, ".$db->pdate("a.datep")." as dp, a.fk_user_author, a.percent,";
	$sql.= " c.code, c.libelle,";
	$sql.= " s.nom as sname, s.rowid, s.client";
	$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
	$sql.= ", ".MAIN_DB_PREFIX."c_actioncomm as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.id = a.fk_action";
	$sql.= " AND a.percent < 100";
	$sql.= " AND s.rowid = a.fk_soc";
	$sql.= " AND s.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND s.rowid = ".$socid;
	$sql.= " ORDER BY a.datep DESC, a.id DESC";
	$sql.= $db->plimit($max, 0);

	$resql=$db->query($sql);
	if ($resql)
	{
	    $num = $db->num_rows($resql);
	    if ($num > 0)
	    {
	        print '<table class="noborder" width="100%">';
	        print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("LastActionsToDo",$max).'</td>';
			print '<td colspan="2" align="right"><a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?status=todo">'.$langs->trans("FullList").'</a>';
			print '</tr>';
	        $var = true;
	        $i = 0;

		    $staticaction=new ActionComm($db);
	        $customerstatic=new Client($db);

	        while ($i < $num)
	        {
	            $obj = $db->fetch_object($resql);
	            $var=!$var;

	            print "<tr $bc[$var]>";

	            $staticaction->type_code=$obj->code;
	            $staticaction->libelle=$obj->libelle;
	            $staticaction->id=$obj->id;
	            print '<td>'.$staticaction->getNomUrl(1,12).'</td>';

	            print '<td>'.dol_trunc($obj->label,22).'</td>';

	            $customerstatic->id=$obj->rowid;
	            $customerstatic->nom=$obj->sname;
	            $customerstatic->client=$obj->client;
	            print '<td>'.$customerstatic->getNomUrl(1,'',16).'</td>';

				// Date
				print '<td width="100" alig="right">'.dol_print_date($obj->dp,'day').'&nbsp;';
				$late=0;
				if ($obj->percent == 0 && $obj->dp && date("U",$obj->dp) < time()) $late=1;
				if ($obj->percent == 0 && ! $obj->dp && $obj->dp2 && date("U",$obj->dp) < time()) $late=1;
				if ($obj->percent > 0 && $obj->percent < 100 && $obj->dp2 && date("U",$obj->dp2) < time()) $late=1;
				if ($obj->percent > 0 && $obj->percent < 100 && ! $obj->dp2 && $obj->dp && date("U",$obj->dp) < time()) $late=1;
				if ($late) print img_warning($langs->trans("Late"));
				print "</td>";

				// Statut
				print "<td align=\"center\" width=\"14\">".$staticaction->LibStatut($obj->percent,3)."</td>\n";

				print "</tr>\n";

	            $i++;
	        }
	        print "</table><br>";
	    }
	    $db->free($resql);
	}
	else
	{
	    dol_print_error($db);
	}
}


/**
   \brief      	Show last actions array
   \param		max		Max nb of records
*/
function show_array_last_actions_done($max=5)
{
	global $langs, $conf, $user, $db, $bc, $socid;

	$sql = "SELECT a.id, a.percent, ".$db->pdate("a.datep")." as da, ".$db->pdate("a.datep2")." as da2, a.fk_user_author, a.label,";
	$sql.= " c.code, c.libelle,";
	$sql.= " s.rowid, s.nom as sname, s.client";
	$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
	$sql.= ", ".MAIN_DB_PREFIX."c_actioncomm as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.id = a.fk_action";
	$sql.= " AND a.percent >= 100";
	$sql.= " AND s.rowid = a.fk_soc";
	$sql.= " AND s.entity = ".$conf->entity;
	if ($socid)	$sql.= " AND s.rowid = ".$socid;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	$sql .= " ORDER BY a.datep2 DESC";
	$sql .= $db->plimit($max, 0);

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("LastDoneTasks",$max).'</td>';
		print '<td colspan="2" align="right"><a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?status=done">'.$langs->trans("FullList").'</a>';
		print '</tr>';
		$var = true;
		$i = 0;

	    $staticaction=new ActionComm($db);
	    $customerstatic=new Societe($db);

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			$var=!$var;

			print "<tr $bc[$var]>";

			$staticaction->code=$obj->code;
			$staticaction->libelle=$obj->libelle;
			$staticaction->id=$obj->id;
			print '<td>'.$staticaction->getNomUrl(1,12).'</td>';

            print '<td>'.dol_trunc($obj->label,24).'</td>';

			$customerstatic->id=$obj->rowid;
			$customerstatic->nom=$obj->sname;
			$customerstatic->client=$obj->client;
			print '<td>'.$customerstatic->getNomUrl(1,'',24).'</td>';

			// Date
			print '<td width="100" align="right">'.dol_print_date($obj->da2,'day');
			print "</td>";

			// Statut
			print "<td align=\"center\" width=\"14\">".$staticaction->LibStatut($obj->percent,3)."</td>\n";

			print "</tr>\n";
			$i++;
		}
		// TODO Ajouter rappel pour "il y a des contrats � mettre en service"
		// TODO Ajouter rappel pour "il y a des contrats qui arrivent � expiration"
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}


/**
 *  \brief     	Define head array for tabs of agenda setup pages
 *  \return		Array of head
 */
function agenda_prepare_head()
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/agenda.php";
	$head[$h][1] = $langs->trans("AutoActions");
	$head[$h][2] = 'autoactions';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/agenda_xcal.php";
	$head[$h][1] = $langs->trans("Other");
	$head[$h][2] = 'xcal';
	$h++;

	return $head;
}

/**
 *  \brief     	Define head array for tabs of agenda setup pages
 *  \return		Array of head
 */
function actions_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/fiche.php?id='.$_GET["id"];
	$head[$h][1] = $langs->trans("CardAction");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/document.php?id='.$_GET["id"];
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/info.php?id='.$_GET["id"];
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	return $head;
}

?>