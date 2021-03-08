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
 *	\file        htdocs/compta/stats/index.php
 *	\brief       Page reporting CA
 *	\version     $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/report.lib.php");


$year_start=isset($_GET["year_start"])?$_GET["year_start"]:$_POST["year_start"];
$year_current = strftime("%Y",time());
if (! $year_start) {
	$year_start = $year_current - 4;
	$year_end = $year_current;
}
else {
	$year_end=$year_start + 4;
}

// Security check
$socid = isset($_REQUEST["socid"])?$_REQUEST["socid"]:'';
if ($user->societe_id > 0) $socid = $user->societe_id;
if (!$user->rights->compta->resultat->lire && !$user->rights->accounting->comptarapport->lire)
accessforbidden();

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->compta->mode;
if ($_GET["modecompta"]) $modecompta=$_GET["modecompta"];


/*
 * View
 */

llxHeader();
$html=new Form($db);

// Affiche en-tete du rapport
if ($modecompta=="CREANCES-DETTES")
{
	$nom=$langs->trans("SalesTurnover");
	$nom.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';
	$period="$year_start - $year_end";
	$periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year_start=".($year_start-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year_start=".($year_start+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
	$description=$langs->trans("RulesCADue");
	$builddate=time();
	$exportlink=$langs->trans("NotYetAvailable");
}
else {
	$nom=$langs->trans("SalesTurnover");
	$nom.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';
	$period="$year_start - $year_end";
	$periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year_start=".($year_start-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year_start=".($year_start+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
	$description=$langs->trans("RulesCAIn");
	$builddate=time();
	$exportlink=$langs->trans("NotYetAvailable");
}
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);


if ($modecompta == 'CREANCES-DETTES') {
	$sql  = "SELECT sum(f.total) as amount, sum(f.total_ttc) as amount_ttc, date_format(f.datef,'%Y-%m') as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql.= " WHERE f.fk_statut in (1,2)";
} else {
	/*
	 * Liste des paiements (les anciens paiements ne sont pas vus par cette requete car, sur les
	 * vieilles versions, ils n'etaient pas lies via paiement_facture. On les ajoute plus loin)
	 */
	$sql  = "SELECT sum(pf.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql.= ", ".MAIN_DB_PREFIX."paiement_facture as pf";
	$sql.= ", ".MAIN_DB_PREFIX."paiement as p";
	$sql.= " WHERE p.rowid = pf.fk_paiement";
	$sql.= " AND pf.fk_facture = f.rowid";
}
$sql.= " AND f.entity = ".$conf->entity;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
$sql.= " GROUP BY dm DESC";

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num)
	{
		$obj = $db->fetch_object($result);
		$cum[$obj->dm] = $obj->amount_ttc;
		if ($obj->amount_ttc)
		{
			$minyearmonth=($minyearmonth?min($minyearmonth,$obj->dm):$obj->dm);
			$maxyearmonth=max($maxyearmonth,$obj->dm);
		}
		$i++;
	}
	$db->free($result);
}
else {
	dol_print_error($db);
}

// On ajoute les paiements anciennes version, non lies par paiement_facture
if ($modecompta != 'CREANCES-DETTES') {
	$sql = "SELECT sum(p.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= ", ".MAIN_DB_PREFIX."paiement as p";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
	$sql.= " WHERE pf.rowid IS NULL";
	$sql.= " AND p.fk_bank = b.rowid";
	$sql.= " AND b.fk_account = ba.rowid";
	$sql.= " AND ba.entity = ".$conf->entity;
	$sql.= " GROUP BY dm";
	$sql.= " ORDER BY dm";

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			$cum[$obj->dm] += $obj->amount_ttc;
			if ($obj->amount_ttc)
			{
				$minyearmonth=($minyearmonth?min($minyearmonth,$obj->dm):$obj->dm);
				$maxyearmonth=max($maxyearmonth,$obj->dm);
			}
			$i++;
		}
	}
	else {
		dol_print_error($db);
	}
}


print '<table width="100%" class="noborder">';
print '<tr class="liste_titre"><td rowspan="2">'.$langs->trans("Month").'</td>';

for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
	print '<td align="center" width="10%" colspan="2"><a href="casoc.php?year='.$annee.'">'.$annee.'</a></td>';
	if ($annee != $year_end) print '<td width="15">&nbsp;</td>';
}
print '</tr>';

print '<tr class="liste_titre">';
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
	print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
	print '<td align="right">'.$langs->trans("Delta").'</td>';
	if ($annee != $year_end) print '<td width="15">&nbsp;</td>';
}
print '</tr>';


$total_CA=0;
$now_show_delta=0;
$minyear=substr($minyearmonth,0,4);
$maxyear=substr($maxyearmonth,0,4);
$nowyear=strftime("%Y",mktime());
$nowyearmonth=strftime("%Y-%m",mktime());

for ($mois = 1 ; $mois < 13 ; $mois++)
{
	$var=!$var;
	print "<tr $bc[$var]>";

	print "<td>".dol_print_date(dol_mktime(12,0,0,$mois,1,2000),"%B")."</td>";
	for ($annee = $year_start ; $annee <= $year_end ; $annee++)
	{
		$casenow = dol_print_date(mktime(),"%Y-%m");
		$case = dol_print_date(dol_mktime(1,1,1,$mois,1,$annee),"%Y-%m");
		$caseprev = dol_print_date(dol_mktime(1,1,1,$mois,1,$annee-1),"%Y-%m");

		if ($annee == $year_current) {
			$total_CA += $cum[$case];
		}

		// Valeur CA du mois
		print '<td align="right">';
		if ($cum[$case])
		{
			$now_show_delta=1;  // On a trouve le premier mois de la premiere annee generant du chiffre.
			print '<a href="casoc.php?year='.$annee.'&month='.$mois.'">'.price($cum[$case],1).'</a>';
		}
		else
		{
			if ($minyearmonth < $case && $case <= max($maxyearmonth,$nowyearmonth)) { print '0'; }
			else { print '&nbsp;'; }
		}
		print "</td>";

		// Pourcentage du mois
		if ($annee > $minyear && $case <= $casenow) {
			if ($cum[$caseprev] && $cum[$case])
			{
				$percent=(round(($cum[$case]-$cum[$caseprev])/$cum[$caseprev],4)*100);
				//print "X $cum[$case] - $cum[$caseprev] - $cum[$caseprev] - $percent X";
				print '<td align="right">'.($percent>=0?"+$percent":"$percent").'%</td>';

			}
			if ($cum[$caseprev] && ! $cum[$case])
			{
				print '<td align="right">-100%</td>';
			}
			if (! $cum[$caseprev] && $cum[$case])
			{
				print '<td align="right">+Inf%</td>';
			}
			if (! $cum[$caseprev] && ! $cum[$case])
			{
				print '<td align="right">+0%</td>';
			}
		}
		else
		{
			print '<td align="right">';
			if ($minyearmonth <= $case && $case <= $maxyearmonth) { print '-'; }
			else { print '&nbsp;'; }
			print '</td>';
		}

		$total[$annee]+=$cum[$case];
		if ($annee != $year_end) print '<td width="15">&nbsp;</td>';
	}

	print '</tr>';
}

// Affiche total
print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td>';
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
	// Montant total
	if ($annee >= $minyear && $annee <= max($nowyear,$maxyear))
	{
		print "<td align=\"right\" nowrap>".($total[$annee]?price($total[$annee]):"0")."</td>";
	}
	else
	{
		print '<td>&nbsp;</td>';
	}

	// Pourcentage total
	if ($annee > $minyear && $annee <= max($nowyear,$maxyear)) {
		if ($total[$annee-1] && $total[$annee]) {
			$percent=(round(($total[$annee]-$total[$annee-1])/$total[$annee-1],4)*100);
			print '<td align="right" nowrap>'.($percent>=0?"+$percent":"$percent").'%</td>';
		}
		if ($total[$annee-1] && ! $total[$annee])
		{
			print '<td align="right">-100%</td>';
		}
		if (! $total[$annee-1] && $total[$annee])
		{
			print '<td align="right">+Inf%</td>';
		}
		if (! $total[$annee-1] && ! $total[$annee])
		{
			print '<td align="right">+0%</td>';
		}
	}
	else
	{
		print '<td align="right">';
		if ($minyear <= $annee && $annee <= max($nowyear,$maxyear)) { print '-'; }
		else { print '&nbsp;'; }
		print '</td>';
	}

	if ($annee != $year_end) print '<td width="15">&nbsp;</td>';
}
print "</tr>\n";
print "</table>";


/*
 * En mode recettes/depenses, on complete avec les montants factures non regles
 * et les propales signees mais pas facturees. En effet, en recettes-depenses,
 * on comptabilise lorsque le montant est sur le compte donc il est interessant
 * d'avoir une vision de ce qui va arriver.
 */

/*
 Je commente toute cette partie car les chiffres affichees sont faux - Eldy.
 En attendant correction.

 if ($modecompta != 'CREANCES-DETTES')
 {

 print '<br><table width="100%" class="noborder">';

 // Factures non reglees
 // \todo Y a bug ici. Il faut prendre le reste a payer et non le total des factures non reglees !

 $sql = "SELECT f.facnumber, f.rowid, s.nom, s.rowid as socid, f.total_ttc, sum(pf.amount) as am";
 $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f left join ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
 $sql .= " WHERE s.rowid = f.fk_soc AND f.paye = 0 AND f.fk_statut = 1";
 if ($socid)
 {
 $sql .= " AND f.fk_soc = $socid";
 }
 $sql .= " GROUP BY f.facnumber,f.rowid,s.nom, s.rowid, f.total_ttc";

 if ( $db->query($sql) )
 {
 $num = $db->num_rows();
 $i = 0;

 if ($num)
 {
 $var = True;
 $total_ttc_Rac = $totalam_Rac = $total_Rac = 0;
 while ($i < $num)
 {
 $obj = $db->fetch_object();
 $total_ttc_Rac +=  $obj->total_ttc;
 $totalam_Rac +=  $obj->am;
 $i++;
 }
 $var=!$var;
 print "<tr $bc[$var]><td align=\"right\" colspan=\"5\"><i>Facture a encaisser : </i></td><td align=\"right\"><i>".price($total_ttc_Rac)."</i></td><td colspan=\"5\"><-- bug ici car n'exclut pas le deja r�gl� des factures partiellement r�gl�es</td></tr>";
 $total_CA +=$total_ttc_Rac;
 }
 $db->free();
 }
 else
 {
 dol_print_error($db);
 }
 */

/*
 *
 * Propales signees, et non facturees
 *
 */

/*
 Je commente toute cette partie car les chiffres affichees sont faux - Eldy.
 En attendant correction.

 $sql = "SELECT sum(f.total) as tot_fht,sum(f.total_ttc) as tot_fttc, p.rowid, p.ref, s.nom, s.rowid as socid, p.total_ht, p.total_ttc
 FROM ".MAIN_DB_PREFIX."commande AS p, llx_societe AS s
 LEFT JOIN ".MAIN_DB_PREFIX."co_fa AS co_fa ON co_fa.fk_commande = p.rowid
 LEFT JOIN ".MAIN_DB_PREFIX."facture AS f ON co_fa.fk_facture = f.rowid
 WHERE p.fk_soc = s.rowid
 AND p.fk_statut >=1
 AND p.facture =0";
 if ($socid)
 {
 $sql .= " AND f.fk_soc = ".$socid;
 }
 $sql .= " GROUP BY p.rowid";

 if ( $db->query($sql) )
 {
 $num = $db->num_rows();
 $i = 0;

 if ($num)
 {
 $var = True;
 $total_pr = 0;
 while ($i < $num)
 {
 $obj = $db->fetch_object();
 $total_pr +=  $obj->total_ttc-$obj->tot_fttc;
 $i++;
 }
 $var=!$var;
 print "<tr $bc[$var]><td align=\"right\" colspan=\"5\"><i>Signe et non facture:</i></td><td align=\"right\"><i>".price($total_pr)."</i></td><td colspan=\"5\"><-- bug ici, ca devrait exclure le deja facture</td></tr>";
 $total_CA += $total_pr;
 }
 $db->free();
 }
 else
 {
 dol_print_error($db);
 }
 print "<tr $bc[$var]><td align=\"right\" colspan=\"5\"><i>Total CA previsionnel : </i></td><td align=\"right\"><i>".price($total_CA)."</i></td><td colspan=\"3\"><-- bug ici car bug sur les 2 precedents</td></tr>";
 }
 print "</table>";

 */

$db->close();

llxFooter('$Date$ - $Revision$');

?>
