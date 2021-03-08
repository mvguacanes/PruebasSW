<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
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
 *       \file       htdocs/adherents/card_subscriptions.php
 *       \ingroup    member
 *       \brief      Onglet d'ajout, edition, suppression des adh�sions d'un adh�rent
 *       \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/member.lib.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_options.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/cotisation.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");

$langs->load("companies");
$langs->load("bills");
$langs->load("members");
$langs->load("users");
$langs->load("mails");

$adh = new Adherent($db);
$adho = new AdherentOptions($db);
$adht = new AdherentType($db);
$errmsg='';

$defaultdelay=1;
$defaultdelayunit='y';

$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];
$rowid=isset($_GET["rowid"])?$_GET["rowid"]:$_POST["rowid"];
$typeid=isset($_GET["typeid"])?$_GET["typeid"]:$_POST["typeid"];

if (! $user->rights->adherent->cotisation->lire)
accessforbidden();


/*
 * 	Actions
 */

if ($user->rights->adherent->cotisation->creer && $_POST["action"] == 'cotisation' && ! $_POST["cancel"])
{
	$langs->load("banks");

	$adh->id = $rowid;
	$result=$adh->fetch($rowid);

	$adht->fetch($adh->typeid);

	// Subscription informations
	$datecotisation=0;
	$datesubend=0;
	if ($_POST["reyear"] && $_POST["remonth"] && $_POST["reday"])
	{
		$datecotisation=dol_mktime(0, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
	}
	if ($_POST["endyear"] && $_POST["endmonth"] && $_POST["endday"])
	{
		$datesubend=dol_mktime(0, 0, 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
	}
	$cotisation=$_POST["cotisation"];	// Amount of subscription
	$label=$_POST["label"];

	if (! $datecotisation)
	{
		$errmsg=$langs->trans("BadDateFormat");
		$action='addsubscription';
	}
	if (! $datesubend)
	{
		$datesubend=dol_time_plus_duree(dol_time_plus_duree($datecotisation,$defaultdelay,$defaultdelayunit),-1,'d');
	}

	// Payment informations
	$accountid=$_POST["accountid"];
	$operation=$_POST["operation"];	// Payment mode
	$num_chq=$_POST["num_chq"];
	$emetteur_nom=$_POST["chqemetteur"];
	$emetteur_banque=$_POST["chqbank"];


	if ($adht->cotisation)	// Type adherent soumis a cotisation
	{
		if (! is_numeric($_POST["cotisation"]))
		{
			// If field is '' or not a numeric value
			$errmsg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount"));
			$action='addsubscription';
		}
		else
		{
			if ($conf->banque->enabled && $conf->global->ADHERENT_BANK_USE)
			{
				if ($_POST["cotisation"])
				{
					if (! $_POST["label"])     $errmsg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
					if (! $_POST["operation"]) $errmsg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("PaymentMode"));
					if (! $_POST["accountid"]) $errmsg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("FinancialAccount"));
				}
				else
				{
					if ($_POST["accountid"])   $errmsg=$langs->trans("ErrorDoNotProvideAccountsIfNullAmount");
				}
				if ($errmsg) $action='addsubscription';
			}
		}
	}

	if ($action=='cotisation')
	{
		$db->begin();

		$crowid=$adh->cotisation($datecotisation, $cotisation, $accountid, $operation, $label, $num_chq, $emetteur_nom, $emetteur_banque, $datesubend);

		if ($crowid > 0)
		{
			$db->commit();

			// Envoi mail
			if ($_POST["sendmail"])
			{
				$result=$adh->send_an_email($conf->global->ADHERENT_MAIL_COTIS,$conf->global->ADHERENT_MAIL_COTIS_SUBJECT,array(),array(),array(),"","",0,-1);
				if ($result < 0) $errmsg=$adh->error;
			}

			$_POST["cotisation"]='';
			$_POST["accountid"]='';
			$_POST["operation"]='';
			$_POST["label"]='';
			$_POST["num_chq"]='';
		}
		else
		{
			$db->rollback();
			$errmsg=$adh->error;
			$action = 'addsubscription';
		}
	}
}


/*
 * View
 */

llxHeader('',$langs->trans("Subscriptions"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$html = new Form($db);

$adh->id = $rowid;
$result=$adh->fetch($rowid);
$result=$adh->fetch_optionals($rowid);

$adht->fetch($adh->typeid);

// fetch optionals attributes and labels
$adho->fetch_optionals();


/*
 * Affichage onglets
 */
$head = member_prepare_head($adh);

dol_fiche_head($head, 'subscription', $langs->trans("Member"), 0, 'user');

print '<form action="fiche.php" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="border" width="100%">';

// Ref
print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
print '<td class="valeur">';
print $html->showrefnav($adh,'rowid');
print '</td></tr>';

// Nom
print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur">'.$adh->nom.'&nbsp;</td>';
print '</tr>';

// Prenom
print '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur">'.$adh->prenom.'&nbsp;</td>';
print '</tr>';

// Login
print '<tr><td>'.$langs->trans("Login").'</td><td class="valeur">'.$adh->login.'&nbsp;</td></tr>';

// Type
print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";

// Status
print '<tr><td>'.$langs->trans("Status").'</td><td class="valeur">'.$adh->getLibStatut(4).'</td></tr>';


print "</table>\n";
print '</form>';

print "</div>\n";


if ($errmsg)
{
	if (preg_match('/^Error/i',$errmsg))
	{
		$langs->load("errors");
		$errmsg=$langs->trans($errmsg);
	}
	print '<div class="error">'.$errmsg.'</div>';
	print "\n";
}


/*
 * Barre d'actions
 *
 */
print '<div class="tabsAction">';

// Lien nouvelle cotisation si non brouillon et non resilie
if ($user->rights->adherent->cotisation->creer)
{
	if ($action != 'addsubscription' && $adh->statut > 0)
	{
		print "<a class=\"butAction\" href=\"card_subscriptions.php?rowid=$rowid&action=addsubscription\">".$langs->trans("AddSubscription")."</a>";
	}
}
print '</div>';
print "<br>\n";



/*
 * Bandeau des cotisations
 *
 */

print '<table border=0 width="100%">';

print '<tr>';
print '<td valign="top" width="50%">';


/*
 * Liste des cotisations
 *
 */
$sql = "SELECT d.rowid, d.prenom, d.nom, d.societe,";
$sql.= " c.rowid as crowid, c.cotisation,";
$sql.= " c.dateadh,";
$sql.= " c.datef,";
$sql.= " c.fk_bank,";
$sql.= " b.rowid as bid,";
$sql.= " ba.rowid as baid, ba.label, ba.bank";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."cotisation as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON c.fk_bank = b.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
$sql.= " WHERE d.rowid = c.fk_adherent AND d.rowid=".$rowid;

$result = $db->query($sql);
if ($result)
{
	$cotisationstatic=new Cotisation($db);
	$accountstatic=new Account($db);

	$num = $db->num_rows($result);
	$i = 0;

	print "<table class=\"noborder\" width=\"100%\">\n";

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Ref").'</td>';
	print '<td align="center">'.$langs->trans("DateSubscription").'</td>';
	print '<td align="center">'.$langs->trans("DateEnd").'</td>';
	print '<td align="right">'.$langs->trans("Amount").'</td>';
	if ($conf->banque->enabled && $conf->global->ADHERENT_BANK_USE)
	{
		print '<td align="right">'.$langs->trans("Account").'</td>';
	}
	print "</tr>\n";

	$var=True;
	while ($i < $num)
	{
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr $bc[$var]>";
		$cotisationstatic->ref=$objp->crowid;
		$cotisationstatic->id=$objp->crowid;
		print '<td>'.$cotisationstatic->getNomUrl(1).'</td>';
		print '<td align="center">'.dol_print_date($db->jdate($objp->dateadh),'day')."</td>\n";
		print '<td align="center">'.dol_print_date($db->jdate($objp->datef),'day')."</td>\n";
		print '<td align="right">'.price($objp->cotisation).'</td>';
		if ($conf->banque->enabled && $conf->global->ADHERENT_BANK_USE)
		{
			print '<td align="right">';
			if ($objp->bid)
			{
				$accountstatic->label=$objp->label;
				$accountstatic->id=$objp->baid;
				print $accountstatic->getNomUrl(1);
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';
		}
		print "</tr>";
		$i++;
	}
	print "</table>";
}
else
{
	dol_print_error($db);
}

print '</td><td valign="top">';


// Date fin cotisation
print "<table class=\"border\" width=\"100%\">\n";
print '<tr><td>'.$langs->trans("SubscriptionEndDate");
print '</td>';
print '<td>';
if ($adh->datefin)
{
	if ($adh->datefin < time())
	{
		print dol_print_date($adh->datefin,'day');
		if ($adh->statut > 0) print " ".img_warning($langs->trans("Late"));	// Affiche picto retard uniquement si non brouillon et non resilie
	}
	else
	{
		print dol_print_date($adh->datefin,'day');
	}
}
else
{
	print $langs->trans("SubscriptionNotReceived");
	if ($adh->statut > 0) print " ".img_warning($langs->trans("Late"));	// Affiche picto retard uniquement si non brouillon et non resilie
}
print '</td>';
print '</tr>';
print '</table>';


/*
 * Ajout d'une nouvelle cotisation
 */
if ($action == 'addsubscription' && $user->rights->adherent->cotisation->creer)
{
	print '<br>';
	print "\n\n<!-- Form add subscription -->\n";

	print '<form name="cotisation" method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="cotisation">';
	print '<input type="hidden" name="rowid" value="'.$rowid.'">';
	print "<table class=\"border\" width=\"100%\">\n";

	// Title subscription
	print '<tr><td colspan="2"><b>'.$langs->trans("NewCotisation").'</b></td></tr>';

	$today=mktime();
	$datefrom=0;
	$dateto=0;

	// Date start subscription
	print '<tr><td>'.$langs->trans("DateSubscription").'</td><td>';
	if ($_POST["reday"])
	{
		$datefrom=dol_mktime(0,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);
	}
	if (! $datefrom)
	{
		if ($adh->datefin > 0)
		{
			$datefrom=dol_time_plus_duree($adh->datefin,1,'d');
		}
		else
		{
			$datefrom=mktime();
		}
	}
	$html->select_date($datefrom,'','','','',"cotisation");
	print "</td></tr>";

	// Date end subscription
	if ($_POST["endday"])
	{
		$dateto=dol_mktime(0,0,0,$_POST["endmonth"],$_POST["endday"],$_POST["endyear"]);
	}
	if (! $dateto)
	{
		//$dateto=dol_time_plus_duree(dol_time_plus_duree($datefrom,$defaultdelay,$defaultdelayunit),-1,'d');
		$dateto=-1;		// By default, no date is suggested
	}
	print '<tr><td>'.$langs->trans("DateEndSubscription").'</td><td>';
	$html->select_date($dateto,'end','','','',"cotisation");
	print "</td></tr>";

	if ($adht->cotisation)
	{
		// Amount
		print '<tr><td>'.$langs->trans("Amount").'</td><td><input type="text" name="cotisation" size="6" value="'.$_POST["cotisation"].'"> '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td>';
		print '<td><input name="label" type="text" size="32" value="'.$langs->trans("Subscription").' ';
		print dol_print_date(($datefrom?$datefrom:time()),"%Y").'" ></td></tr>';

		// Bank account
		if ($conf->banque->enabled && $conf->global->ADHERENT_BANK_USE)
		{
			// Title payments
			print '<tr><td colspan="2"><b>'.$langs->trans("Payment").'</b></td></tr>';

			// Bank account
			print '<tr><td>'.$langs->trans("FinancialAccount").'</td><td>';
			$html->select_comptes($_POST["accountid"],'accountid',0,'',1);
			print "</td></tr>\n";

			// Payment mode
			print '<tr><td>'.$langs->trans("PaymentMode").'</td><td>';
			$html->select_types_paiements($_POST["operation"],'operation');
			print "</td></tr>\n";

			print '<tr><td>'.$langs->trans('Numero');
			print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
			print '</td>';
			print '<td><input name="num_chq" type="text" size="8" value="'.(empty($_POST['num_chq'])?'':$_POST['num_chq']).'"></td></tr>';

			print '<tr><td>'.$langs->trans('CheckTransmitter');
			print ' <em>('.$langs->trans("ChequeMaker").')</em>';
			print '</td>';
			print '<td><input name="chqemetteur" size="32" type="text" value="'.(empty($_POST['chqemetteur'])?$facture->client->nom:$_POST['chqemetteur']).'"></td></tr>';

			print '<tr><td>'.$langs->trans('Bank');
			print ' <em>('.$langs->trans("ChequeBank").')</em>';
			print '</td>';
			print '<td><input name="chqbank" size="32" type="text" value="'.(empty($_POST['chqbank'])?'':$_POST['chqbank']).'"></td></tr>';

		}
	}

	print '<tr><td>'.$langs->trans("SendAcknowledgementByMail").'</td>';
	print '<td>';
	if (! $adh->email)
	{
		print $langs->trans("NoEMail");
	}
	else
	{
		$s1='<input name="sendmail" type="checkbox"'.($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL?' checked="true"':'').'>';
		$s2=$langs->trans("MailFrom").': <b>'.$conf->global->ADHERENT_MAIL_FROM.'</b><br>';
		$s2.=$langs->trans("MailRecipient").': <b>'.$adh->email.'</b>';
		//$s2.='<br>'.$langs->trans("Content").': '.nl2br($conf->global->ADHERENT_MAIL_COTIS);
		print $html->textwithpicto($s1,$s2,1);
	}
	print '</td></tr>';


	print '<tr><td colspan="2" align="center">';
	print '<input type="submit" class="button" name="add" value="'.$langs->trans("AddSubscription").'">';
	print ' &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';

	print '</table>';
	print '</form>';

	print "\n<!-- End form subscription -->\n\n";
}

print '</td></tr>';
print '</table>';



$db->close();

llxFooter('$Date$ - $Revision$');
?>
