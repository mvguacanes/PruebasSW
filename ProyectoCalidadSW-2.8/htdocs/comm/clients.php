<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/comm/clients.php
 *	\ingroup    commercial, societe
 *	\brief      List of customers
 *	\version    $Id$
 */

require("./pre.inc.php");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page=isset($_GET["page"])?$_GET["page"]:$_POST["page"];
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";

$search_nom=isset($_GET["search_nom"])?$_GET["search_nom"]:$_POST["search_nom"];
$search_ville=isset($_GET["search_ville"])?$_GET["search_ville"]:$_POST["search_ville"];
$search_code=isset($_GET["search_code"])?$_GET["search_code"]:$_POST["search_code"];


/*
 * view
 */

llxHeader();


$sql = "SELECT s.rowid, s.nom, s.ville, st.libelle as stcomm, s.prefix_comm, s.code_client";
$sql.= ", ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
if (!$user->rights->societe->client->voir) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.client IN (1, 3)";
$sql.= " AND s.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND s.rowid = ".$socid;

if ($search_nom)   $sql.= " AND s.nom like '%".addslashes(strtolower($search_nom))."%'";
if ($search_ville) $sql.= " AND s.ville like '%".addslashes(strtolower($search_ville))."%'";
if ($search_code)  $sql.= " AND s.code_client like '%".addslashes(strtolower($search_code))."%'";

if ($socname)
{
	$sql.= " AND s.nom like '%".addslashes(strtolower($socname))."%'";
	$sortfield = "s.nom";
	$sortorder = "ASC";
}

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit +1, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$param = "&amp;search_nom=".$search_nom."&amp;search_code=".$search_code."&amp;search_ville=".$search_ville;

	print_barre_liste($langs->trans("ListOfCustomers"), $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

	$i = 0;

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<table class="liste">'."\n";
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"),"clients.php","s.nom","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),"clients.php","s.ville","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("CustomerCode"),"clients.php","s.code_client","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),"clients.php","datec","",$param,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_nom" value="'.$search_nom.'">';
	print '</td><td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ville" value="'.$search_ville.'" size="10">';
	print '</td><td class="liste_titre">';
	print '<input type="text" class="flat" name="search_code" value="'.$search_code.'" size="10">';
	print '</td>';
	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'"></td>';
	print "</tr>\n";

	$var=True;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($result);

		$var=!$var;

		print "<tr $bc[$var]>";
		print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->rowid.'">';
		print img_object($langs->trans("ShowCustomer"),"company");
		print '</a>&nbsp;<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->rowid.'">'.stripslashes($obj->nom).'</a></td>';
		print '<td>'.$obj->ville.'</td>';
		print '<td>'.$obj->code_client.'</td>';
		print '<td align="right">'.dol_print_date($obj->datec).'</td>';
		print "</tr>\n";
		$i++;
	}
	//print_barre_liste($langs->trans("ListOfCustomers"), $page, $_SERVER["PHP_SELF"],'',$sortfield,$sortorder,'',$num);
	print "</table>\n";
	print "</form>\n";
	$db->free($result);
}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
