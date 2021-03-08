<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 * Copyright (C) 2009      Laurent Destailleur  <eldy@users.sourceforge.net>
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

// Init session. Name of session is specific to Dolibarr instance.
$sessionname='DOLSESSID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
$sessiontimeout='DOLSESSTIMEOUT_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
if (! empty($_COOKIE[$sessiontimeout])) ini_set('session.gc_maxlifetime',$_COOKIE[$sessiontimeout]);
session_name($sessionname);
session_start();
dol_syslog("Start session name=".$sessionname." Session id()=".session_id().", _SESSION['dol_login']=".$_SESSION["dol_login"].", ".ini_get("session.gc_maxlifetime"));


$conf_db_type = $dolibarr_main_db_type;

// Parametres de connexion a la base
$conf_db_host = $dolibarr_main_db_host;
$conf_db_user = $dolibarr_main_db_user;
$conf_db_pass = $dolibarr_main_db_pass;
$conf_db_base = $dolibarr_main_db_name;

// Parametres generaux
$conf_url_racine = $dolibarr_main_url_root.'/cashdesk';

// Identifiant unique correspondant au tiers generique pour la vente
$conf_fksoc = $conf->global->CASHDESK_ID_THIRDPARTY;
// Identifiant unique correspondant au compte caisse / liquide
$conf_fkaccount_cash = $conf->global->CASHDESK_ID_BANKACCOUNT_CASH > 0?$conf->global->CASHDESK_ID_BANKACCOUNT_CASH:$_SESSION["CASHDESK_ID_BANKACCOUNT_CASH"];
// Identifiant unique correspondant au compte cheque
$conf_fkaccount_cheque = $conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE > 0?$conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE:$_SESSION["CASHDESK_ID_BANKACCOUNT_CHEQUE"];
// Identifiant unique correspondant au compte cb
$conf_fkaccount_cb = $conf->global->CASHDESK_ID_BANKACCOUNT_CB > 0?$conf->global->CASHDESK_ID_BANKACCOUNT_CB:$_SESSION["CASHDESK_ID_BANKACCOUNT_CB"];
// Identifiant unique correspondant a l'entrepot a utiliser
$conf_fkentrepot = $conf->global->CASHDESK_ID_WAREHOUSE > 0?$conf->global->CASHDESK_ID_WAREHOUSE:$_SESSION["CASHDESK_ID_WAREHOUSE"];

// Check if setup ok
$error = '';
if (empty($conf_fksoc))
{
	$error.= '<div class="error">Setup of CashDesk module not complete. Third party not defined</div>';
}
if ($conf->banque->enabled && (empty($conf_fkaccount_cash) || empty($conf_fkaccount_cheque) || empty($conf_fkaccount_cb)))
{
	$error.= '<div class="error">Setup of CashDesk module not complete. Bank account not defined</div>';
}
// If we setup stock module to ask movement on invoices, with must not work if required setup not finished.
if ($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_BILL && empty($conf_fkentrepot))
{
	$error.= '<div class="error">Setup of CashDesk module not complete. You ask to decrease stock on invoice creation but warehouse for this is not defined (Change stock module setup, or complete point of sale module setup).</div>';
}

// Parametres d'affichage
$conf_taille_listes = 200;	// Nombre max de lignes a afficher dans les listes
$conf_nbr_car_listes = 60;	// Nombre max de caracteres par ligne dans les listes

?>
