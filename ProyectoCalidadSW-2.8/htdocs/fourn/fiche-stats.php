<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
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
 *       \file       htdocs/fourn/fiche-stats.php
 *       \ingroup    fournisseur, facture
 *       \brief      Page de fiche fournisseur
 *       \version    $Id$
 */

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

$langs->load('suppliers');
$langs->load('products');
$langs->load('bills');
$langs->load('orders');
$langs->load('companies');
$langs->load('commercial');

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');


/*
 *  Actions
 */



/*
 * View
 */
$societe = new Fournisseur($db);

if ( $societe->fetch($socid) )
{
	llxHeader('',$langs->trans('SupplierCard'));

	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($societe);

	dol_fiche_head($head, 'supplierstat', $langs->trans("ThirdParty"),0,'company');


	print '<table class="border" width="100%">';
	print '<tr><td width="20%">'.$langs->trans("Name").'</td><td width="80%" colspan="3">'.$societe->nom.'</td></tr>';

	print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$societe->prefix_comm.'</td></tr>';

	print '<tr><td nowrap="nowrap">';
	print $langs->trans('SupplierCode').'</td><td colspan="3">';
	print $societe->code_fournisseur;
	if ($societe->check_codefournisseur() <> 0) print ' '.$langs->trans("WrongSupplierCode");
	print '</td></tr>';

	print "</table><br />";

	print '<table class="border" width="100%">';
	print '<tr><td valign="top" width="50%">';

	$file = get_exdir($societe->id, 3) . "ca_genere-".$societe->id.".png";
	if (file_exists($conf->fournisseur->dir_temp.'/'.$file))
	{
		$url=DOL_URL_ROOT.'/viewimage.php?modulepart=graph_fourn&amp;file='.$file;
		print '<img src="'.$url.'" alt="CA genere">';
	}
	else
	{
		print $langs->trans("NoneOrBatchFileNeverRan",'batch_fournisseur_updateturnover.php, batch_fournisseur_buildgraph.php');
	}

	print '</td><td valign="top" width="50%">';

	$file = get_exdir($societe->id, 3) . "ca_achat-".$societe->id.".png";
	if (file_exists($conf->fournisseur->dir_temp.'/'.$file))
	{
		$url=DOL_URL_ROOT.'/viewimage.php?modulepart=graph_fourn&amp;file='.$file;
		print '<img src="'.$url.'" alt="CA">';
	}
	else
	{
		print $langs->trans("NoneOrBatchFileNeverRan",'batch_fournisseur_updateturnover.php, batch_fournisseur_buildgraph.php');
	}

	print '</td></tr>';
	print '</table>' . "\n";
	print '</div>';
}
else
{
	dol_print_error($db);
}
$db->close();

llxFooter('$Date$ - $Revision$');
?>
