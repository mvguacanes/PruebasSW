<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/compta/paiement/avalider.php
        \ingroup    compta
        \brief      Page liste des paiements a valider des factures clients
        \version    $Id$
*/

require("./pre.inc.php");

$langs->load("bills");

// S�curit� acc�s client
if (! $user->rights->facture->lire)
  accessforbidden();

$socid=0;
if ($user->societe_id > 0) 
{
    $action = '';
    $socid = $user->societe_id;
}


/*
 * Affichage
 */

llxHeader();

$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.rowid";
if ($page == -1) $page = 0 ;
$limit = $conf->liste_limit;
$offset = $limit * $page ;
  
$sql = "SELECT p.rowid,".$db->pdate("p.datep")." as dp, p.amount, p.statut";
$sql .=", c.libelle as paiement_type, p.num_paiement";
$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."c_paiement as c";
if ($socid)
{
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON pf.fk_facture = f.rowid";
}
$sql .= " WHERE p.fk_paiement = c.id";
if ($socid)
{
    $sql.= " AND f.fk_soc = ".$socid;
}
$sql .= " AND p.statut = 0";
$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit( $limit +1 ,$offset);
$resql = $db->query($sql);

if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    $var=True;

    print_barre_liste($langs->trans("ReceivedCustomersPaymentsToValid"), $page, "avalider.php","",$sortfield,$sortorder,'',$num);

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Ref"),"avalider.php","p.rowid","","",'width="60"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Date"),"avalider.php","dp","","",'width="80" align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Type"),"avalider.php","c.libelle","","","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AmountTTC"),"avalider.php","c.libelle","","",'align="right"',$sortfield,$sortorder);
    print "<td>&nbsp;</td>";
    print "</tr>\n";

    while ($i < min($num,$limit))
    {
        $objp = $db->fetch_object($resql);
        $var=!$var;
        print "<tr $bc[$var]>";
        print '<td>'.'<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowPayment"),"payment").' '.$objp->rowid.'</a></td>';
        print '<td width="80" align="center">'.dol_print_date($objp->dp,'day')."</td>\n";
        print "<td>$objp->paiement_type $objp->num_paiement</td>\n";
        print '<td align="right">'.price($objp->amount).'</td>';
        print '<td align="center">';

        if ($objp->statut == 0)
        {
            print '<a href="fiche.php?id='.$objp->rowid.'&amp;action=valide">'.$langs->trans("PaymentStatusToValidShort").'</a>';
        }
        else
        {
            print "-";
        }

        print '</td>';
        print "</tr>";
        $i++;
    }
    print "</table>";
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
