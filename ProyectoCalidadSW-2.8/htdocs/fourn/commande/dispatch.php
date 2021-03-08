<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005	     Eric	Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This	program	is free	software; you can redistribute it and/or modify
 * it under the	terms of the GNU General Public	License	as published by
 * the Free Software Foundation; either	version	2 of the License, or
 * (at your option) any later version.
 *
 * This	program	is distributed in the hope that	it will	be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59	Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file      htdocs/fourn/commande/fiche.php
 *	\ingroup   commande
 *	\brief     Fiche de ventilation des commandes fournisseurs
 *	\version   $Id$
 */

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/modules/supplier_order/modules_commandefournisseur.php');
require_once(DOL_DOCUMENT_ROOT."/product/stock/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/fourn.lib.php");
if ($conf->projet->enabled)	require_once(DOL_DOCUMENT_ROOT.'/projet/project.class.php');

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('deliveries');
$langs->load('products');
$langs->load('stocks');

// Security check
$id = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'commande_fournisseur', $id,'');

// R�cup�ration	de l'id	de projet
$projetid =	0;
if ($_GET["projetid"]) $projetid = $_GET["projetid"];

$mesg='';

/*
 * Actions
 */
if ($_POST["action"] ==	'dispatch' && $user->rights->fournisseur->commande->receptionner)
{
	$commande = new CommandeFournisseur($db);
	$commande->fetch($_GET["id"]);

	foreach($_POST as $key => $value)
	{
		if ( preg_match('/^product_([0-9]+)$/i', $key, $reg) )
		{
	  $prod = "product_".$reg[1];
	  $qty = "qty_".$reg[1];
	  $ent = "entrepot_".$reg[1];
	  $pu = "pu_".$reg[1];
	  $result = $commande->DispatchProduct($user, $_POST[$prod], $_POST[$qty], $_POST[$ent], $_POST[$pu]);
		}
	}

	Header("Location: dispatch.php?id=".$_GET["id"]);
	exit;
}



/*
 * View
 */

llxHeader('',$langs->trans("OrderCard"),"CommandeFournisseur");

$html =	new Form($db);

$now=gmmktime();

$id = $_GET['id'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	//if ($mesg) print $mesg.'<br>';

	$commande = new CommandeFournisseur($db);

	$result=$commande->fetch($_GET['id'],$_GET['ref']);
	if ($result >= 0)
	{
		$soc = new Societe($db);
		$soc->fetch($commande->socid);

		$author = new User($db);
		$author->id = $commande->user_author_id;
		$author->fetch();

		$head = ordersupplier_prepare_head($commande);

		$title=$langs->trans("SupplierOrder");
		dol_fiche_head($head, 'dispatch', $title, 0, 'order');

		/*
		 *	Commande
		 */
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td colspan="2">';
		print $html->showrefnav($commande,'ref','',1,'ref','ref');
		print '</td>';
		print '</tr>';

		// Fournisseur
		print '<tr><td>'.$langs->trans("Supplier")."</td>";
		print '<td colspan="2">'.$soc->getNomUrl(1,'supplier').'</td>';
		print '</tr>';

		// Statut
		print '<tr>';
		print '<td>'.$langs->trans("Status").'</td>';
		print '<td colspan="2">';
		print $commande->getLibStatut(4);
		print "</td></tr>";

		// Date
		if ($commande->methode_commande_id > 0)
		{
			print '<tr><td>'.$langs->trans("Date").'</td><td colspan="2">';
			if ($commande->date_commande)
			{
				print dol_print_date($commande->date_commande,"dayhourtext")."\n";
			}
			print "</td></tr>";

			if ($commande->methode_commande)
			{
				print '<tr><td>'.$langs->trans("Method").'</td><td colspan="2">'.$commande->methode_commande.'</td></tr>';
			}
		}

		// Auteur
		print '<tr><td>'.$langs->trans("AuthorRequest").'</td>';
		print '<td colspan="2">'.$author->getNomUrl(1).'</td>';
		print '</tr>';

		print "</table>";

		if ($mesg) print $mesg;
		else print '<br>';

		/*
		 * Lignes de commandes
		 */
		if ($commande->statut == 3 || $commande->statut == 4 || $commande->statut == 5)
		{
			print '<form method="POST" action="dispatch.php?id='.$commande->id.'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="dispatch">';
			print '<table class="noborder" width="100%">';

			$sql = "SELECT cfd.fk_product, sum(cfd.qty) as qty";
			$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as cfd";
			$sql.= " WHERE cfd.fk_commande = ".$commande->id;
			$sql.= " GROUP BY cfd.fk_product";

			$resql = $db->query($sql);
			if ($resql)
			{
				while ( $row = $db->fetch_row($resql) )
				{
					$products_dispatched[$row[0]] = $row[1];
				}
				$db->free($resql);
			}

			$sql = "SELECT l.ref,l.fk_product,l.description, l.subprice, sum(l.qty) as qty";
			$sql.= ", l.rowid";
			$sql.= ", p.label";
			$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as l";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON l.fk_product=p.rowid";
			$sql.= " WHERE l.fk_commande = ".$commande->id;
			$sql.= " GROUP BY l.fk_product";
			$sql.= " ORDER BY l.rowid";

			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				$i = 0;

				if ($num)
				{
					print '<tr class="liste_titre">';
					print '<td>'.$langs->trans("Description").'</td>';

					print '<td align="right">'.$langs->trans("QtyOrdered").'</td>';
					print '<td align="right">'.$langs->trans("QtyDispatched").'</td>';
					print '<td align="right">'.$langs->trans("Warehouse").'</td>';
					print '<td align="right">'.$langs->trans("QtyDelivered").'</td>';
					print "</tr>\n";
				}

				$entrepot = new Entrepot($db);

				$var=true;
				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);
					// On n'affiche pas les produits personnalises
					if ($objp->fk_product)
					{
						$var=!$var;
						print "<tr ".$bc[$var].">";
						print '<td>';
						print '<a href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$objp->fk_product.'">'.img_object($langs->trans("ShowProduct"),'product').' '.$objp->ref.'</a>';
						print ' - '.$objp->label;
						if ($objp->description) print '<br>'.nl2br($objp->description);
						print '<input name="product_'.$i.'" type="hidden" value="'.$objp->fk_product.'">';
						print '<input name="pu_'.$i.'" type="hidden" value="'.$objp->subprice.'">';
						print "</td>\n";

						print '<td align="right">'.$objp->qty.'</td>';
						print '<td align="right">'.$products_dispatched[$objp->fk_product].'</td>';

						print '<td align="right">';

						if (sizeof($user->entrepots) === 1)
						{
							$uentrepot = array();
							$uentrepot[$user->entrepots[0]['id']] = $user->entrepots[0]['label'];
							$html->select_array("entrepot_".$i, $uentrepot);
						}
						else
						{
							$html->select_array("entrepot_".$i, $entrepot->list_array());
						}
						print "</td>\n";
						print '<td align="right"><input name="qty_'.$i.'" type="text" size="8" value="'.($objp->qty-$products_dispatched[$objp->fk_product]).'"></td>';
						print "</tr>\n";
					}
					$i++;
				}
				$db->free($resql);
			}
			else
			{
				dol_print_error($db);
			}

			print "</table>\n";
			print "<br/>\n";
			print '<center><input type="submit" class="button" value="'.$langs->trans("Save").'"></center></form>';
		}

		print "<br/>\n";
		print '<table class="noborder" width="100%">';


		$sql = "SELECT p.ref,cfd.fk_product, cfd.qty";
		$sql.= ", cfd.rowid";
		$sql.= ", p.label, e.label as entrepot";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as cfd";
		$sql.= " , ".MAIN_DB_PREFIX."product as p ";
		$sql.= " , ".MAIN_DB_PREFIX."entrepot as e ";
		$sql.= " WHERE cfd.fk_commande = ".$commande->id;
		$sql.= " AND cfd.fk_product = p.rowid";
		$sql.= " AND cfd.fk_entrepot = e.rowid";
		$sql.= " ORDER BY cfd.rowid ASC";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num)
			{
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Description").'</td>';
				print '<td align="right">'.$langs->trans("QtyDispatched").'</td>';
				print '<td align="right">'.$langs->trans("Warehouse").'</td>';
				print "</tr>\n";
			}
			$var=false;

			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
				print "<tr $bc[$var]>";
				print '<td>';
				print '<a href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$objp->fk_product.'">'.img_object($langs->trans("ShowProduct"),'product').' '.$objp->ref.'</a>';
				print ' - '.$objp->label;
				print "</td>\n";

				print '<td align="right">'.$objp->qty.'</td>';
				print '<td align="right">'.stripslashes($objp->entrepot).'</td>';
				print "</tr>\n";

				$i++;
				$var=!$var;
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}

		print "</table>\n";
		print '</div>';

		/**
		 * Boutons actions
		 */
		if ($user->societe_id == 0 && $commande->statut	< 3	&& ($_GET["action"]	<> 'valid' || $_GET['action'] == 'builddoc'))
		{
			print '<div	class="tabsAction">';

			if ($commande->statut == 0 && $num > 0)
			{
				if ($user->rights->fournisseur->commande->valider)
				{
					print '<a class="butAction"	href="fiche.php?id='.$commande->id.'&amp;action=valid">'.$langs->trans("Valid").'</a>';
				}
			}

			if ($commande->statut == 1)
			{
				if ($user->rights->fournisseur->commande->approuver)
				{
					print '<a class="butAction"	href="fiche.php?id='.$commande->id.'&amp;action=approve">'.$langs->trans("ApproveOrder").'</a>';

					print '<a class="butAction"	href="fiche.php?id='.$commande->id.'&amp;action=refuse">'.$langs->trans("RefuseOrder").'</a>';
				}

				if ($user->rights->fournisseur->commande->annuler)
				{
					print '<a class="butActionDelete" href="fiche.php?id='.$commande->id.'&amp;action=cancel">'.$langs->trans("CancelOrder").'</a>';
				}

			}

			if ($commande->statut == 2)
			{
				if ($user->rights->fournisseur->commande->annuler)
				{
					print '<a class="butActionDelete" href="fiche.php?id='.$commande->id.'&amp;action=cancel">'.$langs->trans("CancelOrder").'</a>';
				}
			}

			if ($commande->statut == 0)
			{
				if ($user->rights->fournisseur->commande->creer)
				{
					print '<a class="butActionDelete" href="fiche.php?id='.$commande->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
				}
			}
			print "</div>";
		}
		/*
		 *
		 *
		 */
	}
	else
	{
		// Commande	non	trouvee
		dol_print_error($db);
	}
}

$db->close();

llxFooter('$Date$	- $Revision$');
?>
