<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2008-2009 Regis Houssin        <regis@dolibarr.fr>
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

/**     \defgroup   banque     Module bank
 *		\brief      Module pour g�rer la tenue d'un compte bancaire et rapprochements
 *		\version	$Id$
 */

/**
 *		\file       htdocs/includes/modules/modBanque.class.php
 *		\ingroup    banque
 *		\brief      Fichier de description et activation du module Banque
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**     \class      modBanque
 \brief      Classe de description et activation du module Banque
 */

class modBanque extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acc�s base
	 */
	function modBanque($DB)
	{
		global $conf;

		$this->db = $DB ;
		$this->numero = 85 ;

		$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des comptes financiers de type Comptes bancaires ou postaux";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='account';

		// Data directories to create when module is enabled
		$this->dirs = array("/banque/temp");

        // Config pages
        //-------------
        $this->config_page_url = array("bank.php");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array("modComptabilite","modAccounting");
		$this->conflictwith = array();
		$this->langfiles = array("banks","compta","bills","companies");

		// Constants
		$this->const = array();

		// Boites
		$this->boxes = array();
		$this->boxes[0][1] = "box_comptes.php";

		// Permissions
		$this->rights = array();
		$this->rights_class = 'banque';
		$r=0;

		$r++;
		$this->rights[$r][0] = 111; // id de la permission
		$this->rights[$r][1] = 'Lire les comptes bancaires'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (d�pr�ci� � ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par d�faut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 112; // id de la permission
		$this->rights[$r][1] = 'Creer/modifier montant/supprimer ecriture bancaire'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (d�pr�ci� � ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par d�faut
		$this->rights[$r][4] = 'modifier';

		$r++;
		$this->rights[$r][0] = 113; // id de la permission
		$this->rights[$r][1] = 'Configurer les comptes bancaires (creer, gerer categories)'; // libelle de la permission
		$this->rights[$r][2] = 'a'; // type de la permission (d�pr�ci� � ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par d�faut
		$this->rights[$r][4] = 'configurer';

		$r++;
		$this->rights[$r][0] = 114; // id de la permission
		$this->rights[$r][1] = 'Rapprocher les ecritures bancaires'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (d�pr�ci� � ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par d�faut
		$this->rights[$r][4] = 'consolidate';

		$r++;
		$this->rights[$r][0] = 115; // id de la permission
		$this->rights[$r][1] = 'Exporter transactions et releves'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (d�pr�ci� � ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par d�faut
		$this->rights[$r][4] = 'export';

		$r++;
		$this->rights[$r][0] = 116; // id de la permission
		$this->rights[$r][1] = 'Virements entre comptes'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (d�pr�ci� � ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par d�faut
		$this->rights[$r][4] = 'transfer';

		$r++;
		$this->rights[$r][0] = 117; // id de la permission
		$this->rights[$r][1] = 'Gerer les envois de cheques'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (d�pr�ci� � ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par d�faut
		$this->rights[$r][4] = 'cheque';



		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Ecritures bancaires et relev�s';
		$this->export_permission[$r]=array(array("banque","export"));
		$this->export_fields_array[$r]=array('b.rowid'=>'IdTransaction','ba.ref'=>'AccountRef','ba.label'=>'AccountLabel','b.datev'=>'DateValue','b.dateo'=>'DateOperation','b.label'=>'Label','b.num_chq'=>'ChequeOrTransferNumber','-b.amount'=>'Debit','b.amount'=>'Credit','b.num_releve'=>'AccountStatement','b.datec'=>"DateCreation","bu.url_id"=>"IdThirdParty","s.nom"=>"ThirdParty","s.code_compta"=>"CustomerAccountancyCode","s.code_compta_fournisseur"=>"SupplierAccountancyCode");
		$this->export_entities_array[$r]=array('b.rowid'=>'account','ba.ref'=>'account','ba.label'=>'account','b.datev'=>'account','b.dateo'=>'account','b.label'=>'account','b.num_chq'=>'account','-b.amount'=>'account','b.amount'=>'account','b.num_releve'=>'account','b.datec'=>"account","bu.url_id"=>"company","s.nom"=>"company","s.code_compta"=>"company","s.code_compta_fournisseur"=>"company");
		$this->export_alias_array[$r]=array('b.rowid'=>'tran_id','ba.ref'=>'account_ref','ba.label'=>'account_label','b.datev'=>'datev','b.dateo'=>'dateo','b.label'=>'label','b.num_chq'=>'num','-b.amount'=>'debit','b.amount'=>'credit','b.num_releve'=>'numrel','b.datec'=>"datec","bu.url_id"=>"soc_id","s.nom"=>"thirdparty","s.code_compta"=>"customeracccode","s.code_compta_fournisseur"=>"supplieracccode");
		$this->export_special_array[$r]=array('-b.amount'=>'NULLIFNEG','b.amount'=>'NULLIFNEG');

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'bank_account as ba, '.MAIN_DB_PREFIX.'bank as b)';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX."bank_url as bu ON (bu.fk_bank = b.rowid AND bu.type = 'company')";
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON bu.url_id = s.rowid';
		$this->export_sql_end[$r] .=' WHERE ba.rowid = b.fk_account';
		$this->export_sql_end[$r] .=' AND ba.entity = '.$conf->entity;
		$this->export_sql_end[$r] .=' ORDER BY b.datev, b.num_releve';
	}


	/**
	 *   \brief      Fonction appel�e lors de l'activation du module. Ins�re en base les constantes, boites, permissions du module.
	 *               D�finit �galement les r�pertoires de donn�es � cr�er pour ce module.
	 */
	function init()
	{
		global $conf;

		// Permissions
		$this->remove();

		$sql = array();

		return $this->_init($sql);
	}

	/**
	 *    \brief      Fonction appel�e lors de la d�sactivation d'un module.
	 *                Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);
	}
}
?>