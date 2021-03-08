<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/contrat/index.php
 *      \ingroup    contrat
 *		\brief      Page liste des contrats
 *		\version    $Revision$
 */

require("./pre.inc.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

$langs->load("products");
$langs->load("companies");

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:(isset($_POST["sortfield"])?$_POST["sortfield"]:'');
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:(isset($_POST["sortorder"])?$_POST["sortorder"]:'');
$page = isset($_GET["page"])?$_GET["page"]:(isset($_POST["page"])?$_POST["page"]:'');

$statut=isset($_GET["statut"])?$_GET["statut"]:1;

// Security check
$socid=0;
$contratid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat',$contratid,'');

$staticcompany=new Societe($db);
$staticcontrat=new Contrat($db);
$staticcontratligne=new ContratLigne($db);


/*
 * View
 */

$now = gmmktime();

llxHeader();

print_fiche_titre($langs->trans("ContractsArea"));


print '<table class="notopnoleftnoright" width="100%">';

print '<tr><td width="30%" valign="top" class="notopnoleft">';

// Search contract
if ($conf->contrat->enabled)
{
	$var=false;
	print '<form method="post" action="'.DOL_URL_ROOT.'/contrat/liste.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAContract").'</td></tr>';
	print '<tr '.$bc[$var].'>';
	print '<td nowrap>'.$langs->trans("Ref").':</td><td><input type="text" class="flat" name="search_contract" size="18"></td>';
	print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print '<tr '.$bc[$var].'><td nowrap>'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td>';
	print '</tr>';
	print "</table></form>\n";
	print "<br>";
}

/*
 * Legends / Status
 */
$nb=array();
// Search by status (except expired)
$sql = "SELECT count(cd.rowid), cd.statut";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE cd.fk_contrat = c.rowid AND c.fk_soc = s.rowid";
$sql.= " AND (cd.statut != 4 OR (cd.statut = 4 AND (cd.date_fin_validite is null or cd.date_fin_validite >= ".$db->idate(dol_now('tzref')).')))';
$sql.= " AND s.entity = ".$conf->entity;
if ($user->societe_id) $sql.=' AND c.fk_soc = '.$user->societe_id;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " GROUP BY cd.statut";
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$row = $db->fetch_row($resql);
		$nb[$row[1]]=$row[0];
		$i++;
	}
	$db->free($resql);
}
else
{
	dol_print_error($db);
}
// Search by status (only expired)
$sql = "SELECT count(cd.rowid), cd.statut";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE cd.fk_contrat = c.rowid AND c.fk_soc = s.rowid";
$sql.= " AND (cd.statut = 4 AND cd.date_fin_validite < ".$db->idate(dol_now('tzref')).')';
$sql.= " AND s.entity = ".$conf->entity;
if ($user->societe_id) $sql.=' AND c.fk_soc = '.$user->societe_id;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " GROUP BY cd.statut";
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$row = $db->fetch_row($resql);
		$nb[$row[1].true]=$row[0];
		$i++;
	}
	$db->free($resql);
}
else
{
	dol_print_error($db);
}
print '<table class="liste" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Status").'</td>';
print '<td align="right">'.$langs->trans("Nb").'</td>';
print "</tr>\n";
$var=true;
$listofstatus=array(0,4,4,5); $bool=false;
foreach($listofstatus as $status)
{
	$var=!$var;
	print "<tr $bc[$var]>";
	print '<td>'.$staticcontratligne->LibStatut($status,0,($bool?1:0)).'</td>';
	print '<td align="right"><a href="services.php?mode='.$status.($bool?'&filter=expired':'').'">'.($nb[$status.$bool]?$nb[$status.$bool]:0).' '.$staticcontratligne->LibStatut($status,3,($bool?1:0)).'</a></td>';
	if ($status==4 && $bool==false) $bool=true;
	else $bool=false;
}
print "</tr>\n";
print "</table><br>";

/**
 * Draft contratcs
 */
if ($conf->contrat->enabled && $user->rights->contrat->lire)
{
	$sql  = "SELECT c.rowid as ref, c.rowid,";
	$sql.= " s.nom, s.rowid as socid";
	$sql .= " FROM ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = c.fk_soc";
	$sql.= " AND s.entity = ".$conf->entity;
	$sql.= " AND c.statut = 0";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND s.fk_soc = ".$socid;

	$resql = $db->query($sql);

	if ( $resql )
	{
		$var = false;
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("DraftContracts").($num?' ('.$num.')':'').'</td></tr>';
		if ($num)
		{
			$companystatic=new Societe($db);

			$i = 0;
			//$tot_ttc = 0;
			while ($i < $num && $i < 20)
			{
				$obj = $db->fetch_object($resql);
				print '<tr '.$bc[$var].'><td nowrap>';
				$staticcontrat->ref=$obj->ref;
				$staticcontrat->id=$obj->rowid;
				print $staticcontrat->getNomUrl(1,'');
				print '</td>';
				print '<td>';
				$companystatic->id=$obj->socid;
				$companystatic->nom=$obj->nom;
				$companystatic->client=1;
				print $companystatic->getNomUrl(1,'',16);
				print '</td>';
				print '</tr>';
				//$tot_ttc+=$obj->total_ttc;
				$i++;
				$var=!$var;
			}
		}
		else
		{
			print '<tr colspan="3" '.$bc[$var].'><td>'.$langs->trans("NoContracts").'</td></tr>';
		}
		print "</table><br>";
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

print '</td><td width="70%" valign="top" class="notopnoleftnoright">';


// Last modified contracts
$max=5;
$sql = 'SELECT ';
$sql.= ' sum('.$db->ifsql("cd.statut=0",1,0).') as nb_initial,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite >= ".$db->idate($now).")",1,0).') as nb_running,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < ".$db->idate($now).")",1,0).') as nb_expired,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < ".$db->idate($now - $conf->contrat->services->expires->warning_delay).")",1,0).') as nb_late,';
$sql.= ' sum('.$db->ifsql("cd.statut=5",1,0).') as nb_closed,';
$sql.= " c.rowid as cid, c.ref, c.datec, c.tms, c.statut, s.nom, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
$sql.= " ".MAIN_DB_PREFIX."contrat as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
$sql.= " WHERE c.fk_soc = s.rowid";
$sql.= " AND s.entity = ".$conf->entity;
$sql.= " AND c.statut > 0";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND s.rowid = ".$socid;
$sql.= " GROUP BY c.rowid, c.datec, c.statut, s.nom, s.rowid";
$sql.= " ORDER BY c.tms DESC";
$sql.= " LIMIT ".$max;

dol_syslog("contrat/index.php sql=".$sql, LOG_DEBUG);
$result=$db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("LastContracts",5).'</td>';
	print '<td align="center">'.$langs->trans("DateModification").'</td>';
	//print '<td align="left">'.$langs->trans("Status").'</td>';
	print '<td align="right" width="80" colspan="4">'.$langs->trans("Services").'</td>';
	print "</tr>\n";

	$var=True;
	while ($i < $num)
	{
		$obj = $db->fetch_object($result);
		$var=!$var;

		print "<tr $bc[$var]>";
		print '<td width="80" nowrap="nowrap">';
		$staticcontrat->ref=($obj->ref?$obj->ref:$obj->cid);
		$staticcontrat->id=$obj->cid;
		print $staticcontrat->getNomUrl(1,16);
		if ($obj->nb_late) print img_warning($langs->trans("Late"));
		print '</td>';
		print '<td>';
		$staticcompany->id=$obj->socid;
		$staticcompany->nom=$obj->nom;
		print $staticcompany->getNomUrl(1,'',20);
		print '</td>';
		print '<td align="center">'.dol_print_date($obj->tms,'dayhour').'</td>';
		//print '<td align="left">'.$staticcontrat->LibStatut($obj->statut,2).'</td>';
		print '<td align="right" width="32">'.($obj->nb_initial>0 ? $obj->nb_initial.$staticcontratligne->LibStatut(0,3):'').'</td>';
		print '<td align="right" width="32">'.($obj->nb_running>0 ? $obj->nb_running.$staticcontratligne->LibStatut(4,3,0):'').'</td>';
		print '<td align="right" width="32">'.($obj->nb_expired>0 ? $obj->nb_expired.$staticcontratligne->LibStatut(4,3,1):'').'</td>';
		print '<td align="right" width="32">'.($obj->nb_closed>0  ? $obj->nb_closed.$staticcontratligne->LibStatut(5,3):'').'</td>';
		print "</tr>\n";
		$i++;
	}
	$db->free($result);

	print "</table>";

}
else
{
	dol_print_error($db);
}

print '<br>';


// Not activated services
$sql = "SELECT c.ref, c.fk_soc, cd.rowid as cid, cd.statut, cd.label, cd.description as note, cd.fk_contrat, s.nom";
$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
$sql.= ", ".MAIN_DB_PREFIX."contrat as c";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE c.statut=1";
$sql.= " AND cd.statut = 0";
$sql.= " AND cd.fk_contrat = c.rowid";
$sql.= " AND c.fk_soc = s.rowid";
$sql.= " AND s.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND s.rowid = ".$socid;
$sql.= " ORDER BY cd.tms DESC";

if ( $db->query($sql) )
{
	$num = $db->num_rows();
	$i = 0;

	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("NotActivatedServices").'</td>';
	print "</tr>\n";

	$var=True;
	while ($i < $num)
	{
		$obj = $db->fetch_object();
		$var=!$var;
		print "<tr $bc[$var]>";

		print '<td width="80" nowrap="nowrap">';
		$staticcontrat->ref=($obj->ref?$obj->ref:$obj->fk_contrat);
		$staticcontrat->id=$obj->fk_contrat;
		print $staticcontrat->getNomUrl(1,16);
		print '</td>';
		print '<td nowrap="1">';
		print '<a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service");
		if ($obj->label) print ' '.dol_trunc($obj->label,20).'</a></td>';
		else print '</a> '.dol_trunc($obj->note,20).'</td>';
		print '<td>';
		$staticcompany->id=$obj->fk_soc;
		$staticcompany->nom=$obj->nom;
		print $staticcompany->getNomUrl(1,'',20);
		print '</td>';
		print '<td width="16" align="right"><a href="ligne.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'">';
		print $staticcontratligne->LibStatut($obj->statut,3);
		print '</a></td>';
		print "</tr>\n";
		$i++;
	}
	$db->free();

	print "</table>";

}
else
{
	dol_print_error($db);
}

print '<br>';

// Last modified services
$max=5;

$sql = "SELECT c.ref, c.fk_soc, ";
$sql.= " cd.rowid as cid, cd.statut, cd.label, cd.description as note, cd.fk_contrat, cd.date_fin_validite,";
$sql.= " s.nom";
$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
$sql.= ", ".MAIN_DB_PREFIX."contrat as c";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE cd.fk_contrat = c.rowid";
$sql.= " AND c.fk_soc = s.rowid";
$sql.= " AND s.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND s.rowid = ".$socid;
$sql.= " ORDER BY cd.tms DESC";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("LastModifiedServices",min($num,$max)).'</td>';
	print "</tr>\n";

	$var=True;
	while ($i < min($num,$max))
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;
		print "<tr $bc[$var]>";
		print '<td width="80" nowrap="nowrap">';
		$staticcontrat->ref=($obj->ref?$obj->ref:$obj->fk_contrat);
		$staticcontrat->id=$obj->fk_contrat;
		print $staticcontrat->getNomUrl(1,16);
		//if (1 == 1) print img_warning($langs->trans("Late"));
		print '</td>';
		print '<td><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service");
		if ($obj->label) print ' '.dol_trunc($obj->label,20).'</a></td>';
		else print '</a> '.dol_trunc($obj->note,20).'</td>';
		print '<td>';
		$staticcompany->id=$obj->fk_soc;
		$staticcompany->nom=$obj->nom;
		print $staticcompany->getNomUrl(1,'',20);
		print '</td>';
		print '<td nowrap="nowrap" align="right"><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'">';
		$dateend=$db->jdate($obj->date_fin_validite);
		print $staticcontratligne->LibStatut($obj->statut, 3, ($dateend && $dateend < $now)?1:0);
		print '</a></td>';
		print "</tr>\n";
		$i++;
	}
	$db->free();

	print "</table>";

}
else
{
	dol_print_error($db);
}

print '</td></tr></table>';

print '<br>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
