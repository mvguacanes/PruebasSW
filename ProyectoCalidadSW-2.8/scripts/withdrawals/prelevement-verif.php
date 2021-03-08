<?PHP
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       scripts/prelevement/prelevement-verif.php
 *      \ingroup    prelevement
 *      \brief      Verifie que les societes qui doivent etre prelevees ont bien un RIB correct
 *		\version	$Id$
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=str_replace($script_file,'',$_SERVER["PHP_SELF"]);
$path=preg_replace('@[\\\/]+$@','',$path).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You ar usingr PH for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

// Recupere env dolibarr
$version='$Revision$';

require_once($path."../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe.class.php");

$error = 0;

$puser = new user($db, PRELEVEMENT_USER);
$puser->fetch();
dol_syslog("Prelevements effectues par ".$puser->fullname." [".PRELEVEMENT_USER."]");

dol_syslog("Raison sociale : ".PRELEVEMENT_RAISON_SOCIALE);
dol_syslog("Numero Nation Emetteur : ".PRELEVEMENT_NUMERO_NATIONAL_EMETTEUR);

dol_syslog("Code etablissement : ".PRELEVEMENT_CODE_BANQUE);
dol_syslog("Code guichet       : ". PRELEVEMENT_CODE_GUICHET);
dol_syslog("Numero compte      : ".PRELEVEMENT_NUMERO_COMPTE);

/*
 *
 * Lectures des factures a prelever
 *
 */

$factures = array();
$factures_prev = array();

if (!$error)
{

  $sql = "SELECT f.rowid, pfd.rowid as pfdrowid, f.fk_soc";
  $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
  $sql .= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";

  $sql .= " WHERE f.fk_statut = 1";
  $sql .= " AND f.rowid = pfd.fk_facture";
  $sql .= " AND f.paye = 0";
  $sql .= " AND pfd.traite = 0";
  $sql .= " AND f.total_ttc > 0";
  $sql .= " AND f.fk_mode_reglement = 3";

  if ( $db->query($sql) )
    {
      $num = $db->num_rows();

      $i = 0;

      while ($i < $num)
	{
	  $row = $db->fetch_row();

	  $factures[$i] = $row;

	  $i++;
	}
      $db->free();
      dol_syslog("$i factures a prelever");
    }
  else
    {
      $error = 1;
      dol_syslog("Erreur -1");
      dol_syslog($db->error());
    }
}

/*
 *
 * Verification des clients
 *
 */

if (!$error)
{
  /*
   * Verification des RIB
   *
   */
  $i = 0;
  dol_syslog("Debut verification des RIB");

  if (sizeof($factures) > 0)
    {
      foreach ($factures as $fac)
	{
	  $fact = new Facture($db);

	  if ($fact->fetch($fac[0]) == 1)
	    {
	      $soc = new Societe($db);
	      if ($soc->fetch($fact->socid) == 1)
		{

		  if ($soc->verif_rib() == 1)
		    {

		      $factures_prev[$i] = $fac;

		      $i++;
		    }
		  else
		    {
		      dol_syslog("Erreur de RIB societe $fact->socid $soc->nom");
		    }
		}
	      else
		{
		  dol_syslog("Impossible de lire la societe");
		}
	    }
	  else
	    {
	      dol_syslog("Impossible de lire la facture");
	    }
	}
    }
  else
    {
      dol_syslog("Aucune factures a traiter");
    }
}

dol_syslog(sizeof($factures_prev)." factures sur ".sizeof($factures)." seront prelevees");

$db->close();


?>
