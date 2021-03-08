<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
 *	\defgroup   societe     Module societe
 *	\brief      Module to manage third parties (customers, prospects)
 *	\version	$Id$
 */

/**
 *	\file       htdocs/includes/modules/modSociete.class.php
 *	\ingroup    societe
 *	\brief      Fichier de description et activation du module Societe
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 *	\class      modSociete
 *	\brief      Classe de description et activation du module Societe
 */
class modSociete extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modSociete($DB)
	{
		global $conf;

		$this->db = $DB ;
		$this->numero = 1 ;

		$this->family = "crm";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des societes et contacts";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->config_page_url = array("societe.php");
		// Name of image file used for this module.
		$this->picto='company';

		// Data directories to create when module is enabled
		$this->dirs = array("/societe/temp");

		// Dependances
		$this->depends = array();
		$this->requiredby = array("modExpedition","modFacture","modFournisseur","modFicheinter","modPropale","modContrat","modCommande");
		$this->langfiles = array("companies");

		// Constantes
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "SOCIETE_FISCAL_MONTH_START";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = "Mettre le numero du mois du debut d\'annee fiscale, ex: 9 pour septembre";
		$this->const[$r][4] = 1;
		$r++;

		$this->const[$r][0] = "MAIN_SEARCHFORM_SOCIETE";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = "1";
		$this->const[$r][3] = "Affichage formulaire de recherche des Societes dans la barre de gauche";
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_SEARCHFORM_CONTACT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = "1";
		$this->const[$r][3] = "Affichage formulaire de recherche des Contacts dans la barre de gauche";
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r++;

		// Boxes
		$this->boxes = array();
		$r=0;
		$this->boxes[$r][1] = "box_clients.php";
		$r++;
		$this->boxes[$r][1] = "box_prospect.php";
		$r++;

		// Permissions
		$this->rights = array();
		$this->rights_class = 'societe';
		$r=0;

		$r++;
		$this->rights[$r][0] = 121; // id de la permission
		$this->rights[$r][1] = 'Lire les societes'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 122; // id de la permission
		$this->rights[$r][1] = 'Creer modifier les societes'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 125; // id de la permission
		$this->rights[$r][1] = 'Supprimer les societes'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 126; // id de la permission
		$this->rights[$r][1] = 'Exporter les societes'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'export';

		// 262 : Resteindre l'acces des commerciaux
		$r++;
		$this->rights[$r][0] = 262;
		$this->rights[$r][1] = 'Consulter tous les tiers par utilisateurs internes (sinon uniquement si contact commercial). Non effectif pour utilisateurs externes (tjs limités à eux-meme).';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'client';
		$this->rights[$r][5] = 'voir';

		$r++;
		$this->rights[$r][0] = 281; // id de la permission
		$this->rights[$r][1] = 'Lire les contacts'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'contact';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 282; // id de la permission
		$this->rights[$r][1] = 'Creer modifier les contacts'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'contact';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 283; // id de la permission
		$this->rights[$r][1] = 'Supprimer les contacts'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'contact';
		$this->rights[$r][5] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 286; // id de la permission
		$this->rights[$r][1] = 'Exporter les contacts'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'contact';
		$this->rights[$r][5] = 'export';


		// Exports
		//--------
		$r=0;

		// Export list of third parties and attributes
		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='ExportDataset_company_1';
		$this->export_icon[$r]='company';
		$this->export_permission[$r]=array(array("societe","export"));
		$this->export_fields_array[$r]=array('s.rowid'=>"Id",'s.nom'=>"Name",'s.prefix_comm'=>"Prefix",'s.client'=>"Customer",'s.fournisseur'=>"Supplier",'s.datec'=>"DateCreation",'s.tms'=>"DateLastModification",'s.code_client'=>"CustomerCode",'s.code_fournisseur'=>"SupplierCode",'s.address'=>"Address",'s.cp'=>"Zip",'s.ville'=>"Town",'p.libelle'=>"Country",'p.code'=>"CountryCode",'s.tel'=>"Phone",'s.fax'=>"Fax",'s.url'=>"Url",'s.email'=>"Email",'s.siret'=>"IdProf1",'s.siren'=>"IdProf2",'s.ape'=>"IdProf3",'s.idprof4'=>"IdProf4",'s.tva_intra'=>"VATIntraShort",'s.capital'=>"Capital",'s.note'=>"Note",'t.libelle'=>"ThirdPartyType",'ce.code'=>"Effectif","cfj.libelle"=>"JuridicalStatus",'s.fk_prospectlevel'=>'ProspectLevel','fk_stcomm'=>'ProspectStatus');
		$this->export_entities_array[$r]=array();	// We define here only fields that use another picto
		$this->export_alias_array[$r]=array('s.rowid'=>"socid",'s.nom'=>"name",'s.prefix_comm'=>"prefix",'s.client'=>"iscustomer",'s.fournisseur'=>"issupplier",'s.datec'=>"datecreation",'s.tms'=>"datelastmodification",'s.code_client'=>"customercode",'s.code_fournisseur'=>"suppliercode",'s.address'=>"address",'s.cp'=>"zip",'s.ville'=>"town",'p.libelle'=>"country",'p.code'=>"countrycode",'s.tel'=>"phone",'s.fax'=>"fax",'s.url'=>"url",'s.email'=>"email",'s.siret'=>"idprof1",'s.siren'=>"idprof2",'s.ape'=>"idprof3",'s.idprof4'=>"idprof4",'s.tva_intra'=>"vatintra",'s.capital'=>"capital",'s.note'=>"note",'t.libelle'=>"thirdpartytype",'ce.code'=>"effectif","cfj.libelle"=>"juridicalstatus",'s.fk_prospectlevel'=>'prospectlevel','fk_stcomm'=>'prospectstatus');

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'societe as s';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_typent as t ON s.fk_typent = t.id';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_pays as p ON s.fk_pays = p.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_effectif as ce ON s.fk_effectif = ce.id';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_forme_juridique as cfj ON s.fk_forme_juridique = cfj.code';
		$this->export_sql_end[$r] .=' WHERE s.entity = '.$conf->entity;

		// Export list of contacts and attributes
		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='ExportDataset_company_2';
		$this->export_icon[$r]='contact';
		$this->export_permission[$r]=array(array("societe","contact","export"));
		$this->export_fields_array[$r]=array('c.rowid'=>"IdContact",'c.civilite'=>"CivilityCode",'c.name'=>'Lastname','c.firstname'=>'Firstname','c.datec'=>"DateCreation",'c.tms'=>"DateLastModification",'c.priv'=>"ContactPrivate",'c.address'=>"Address",'c.cp'=>"Zip",'c.ville'=>"Town",'c.phone'=>"Phone",'c.fax'=>"Fax",'c.email'=>"EMail",'p.libelle'=>"Country",'p.code'=>"CountryCode",'s.rowid'=>"IdCompany",'s.nom'=>"CompanyName",'s.code_client'=>"CustomerCode",'s.code_fournisseur'=>"SupplierCode");
		$this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>"company",'s.code_client'=>"company",'s.code_fournisseur'=>"company");	// We define here only fields that use another picto
		$this->export_alias_array[$r]=array('c.rowid'=>"contactid",'c.civilite'=>"civilitycode",'c.name'=>'lastname','c.firstname'=>'firstname','c.datec'=>"datecreation",'c.tms'=>"datelastmodification",'c.priv'=>"private",'c.address'=>"address",'c.cp'=>"zip",'c.ville'=>"town",'c.phone'=>"phone",'c.fax'=>"fax",'c.email'=>"email",'p.libelle'=>"country",'p.code'=>"countrycode",'s.rowid'=>"socid",'s.nom'=>"companyname",'s.code_client'=>"customercode",'s.code_fournisseur'=>"suppliercode");

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'c_pays as p, '.MAIN_DB_PREFIX.'socpeople as c';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON c.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=' WHERE c.fk_pays = p.rowid';
		$this->export_sql_end[$r] .=' AND c.entity = '.$conf->entity;


		// Imports
		//--------
		$r=0;

		// Import list of third parties and attributes
		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='ImportDataset_company_1';
		$this->import_icon[$r]='company';
		$this->import_tables_array[$r]=array('s'=>MAIN_DB_PREFIX.'societe');	// List of tables to insert into (insert done in same order)
		$this->import_fields_array[$r]=array('s.nom'=>"Name*",'s.prefix_comm'=>"Prefix",'s.client'=>"Customer*",'s.fournisseur'=>"Supplier*",'s.datec'=>"DateCreation",'s.code_client'=>"CustomerCode",'s.code_fournisseur'=>"SupplierCode",'s.address'=>"Address",'s.cp'=>"Zip",'s.ville'=>"Town",'s.tel'=>"Phone",'s.fax'=>"Fax",'s.url'=>"Url",'s.email'=>"Email",'s.siret'=>"IdProf1",'s.siren'=>"IdProf2",'s.ape'=>"IdProf3",'s.idprof4'=>"IdProf4",'s.tva_intra'=>"VATIntraShort",'s.capital'=>"Capital",'s.note'=>"Note",'s.fk_typent'=>"ThirdPartyType",'s.fk_effectif'=>"Effectif","s.fk_forme_juridique"=>"JuridicalStatus",'s.fk_prospectlevel'=>'ProspectLevel','s.fk_stcomm'=>'ProspectStatus');
		$this->import_entities_array[$r]=array();	// We define here only fields that use another picto
		$this->import_examplevalues_array[$r]=array('s.nom'=>"A company",'s.prefix_comm'=>"comp",'s.client'=>'0 or 1','s.fournisseur'=>'0 or 1','s.datec'=>dol_print_date(mktime(),'YYYY-MM-DD'),'s.code_client'=>"CU01-0001",'s.code_fournisseur'=>"SU01-0001",'s.address'=>"61 jump street",'s.cp'=>"123456",'s.ville'=>"Big town",'s.tel'=>"0101010101",'s.fax'=>"0101010102",'s.url'=>"http://mycompany.com",'s.email'=>"test@mycompany.com",'s.siret'=>"",'s.siren'=>"",'s.ape'=>"",'s.idprof4'=>"",'s.tva_intra'=>"FR0123456789",'s.capital'=>"10000",'s.note'=>"This is an example of note for record",'s.fk_typent'=>"2",'s.fk_effectif'=>"3","s.fk_forme_juridique"=>"1",'s.fk_prospectlevel'=>'AAA','s.fk_stcomm'=>'BBB');

		// Import list of contact and attributes
/*		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='ImportDataset_company_2';
		$this->import_icon[$r]='contact';
		//$this->import_permission[$r]=array(array("societe","export"));
		$this->import_tables_array[$r]=array('s'=>MAIN_DB_PREFIX.'socpeople');	// List of tables to insert into (insert done in same order)
		$this->import_fields_array[$r]=array('s.fk_soc'=>'ThirdPartyName*','s.civilite'=>'Civility','s.name'=>"Name*",'s.firstname'=>"Firstname",'s.address'=>"Address",'s.cp'=>"Zip",'s.ville'=>"Town",'s.fk_pays'=>"CountryCode",'s.birthday'=>"BirthdayDate",'s.poste'=>"Role",'s.phone'=>"Phone",'s.phone_perso'=>"PhonePerso",'s.phone_mobile'=>"PhoneMobile",'s.fax'=>"Fax",'s.email'=>"Email",'s.note'=>"Note");
		$this->import_entities_array[$r]=array('s.fk_soc'=>'company');	// We define here only fields that use another picto
		$this->import_examplevalues_array[$r]=array('s.fk_soc'=>'The Big Company','s.civilite'=>"MR",'s.name'=>"Smith",'s.firstname'=>'John','s.address'=>'61 jump street','s.cp'=>'75000','s.ville'=>'Bigtown','s.fk_pays'=>'0','s.datec'=>'1972-10-10','s.poste'=>"Director",'s.phone'=>"5551122",'s.phone_perso'=>"5551133",'s.phone_mobile'=>"5551144",'s.fax'=>"5551155",'s.email'=>"johnsmith@email.com",'s.note'=>"My comments");
		// If value for some fields are a ref to found the key of parent
		$this->import_convertvalue_array[$r]=array('s.fk_soc'=>array('rule'=>'fetchfromref','file'=>'/societe.class.php','class'=>'Societe','method'=>'fetch'));
		// If value for some fields must be the previous inserted record (lastinsertid)
		//$this->import_convertvalue_array[$r]=array('s.fk_soc'=>array('rule'=>'lastrowid',table='t');
*/
	}


	/**
	 *  \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *              Definit egalement les repertoires de donnees a creer pour ce module.
	 *	\param		options		Options when enabling module
	 */
	function init($options='')
	{

		// Prevent pb of modules not correctly disabled
		//$this->remove($options);

		$sql = array();

		return $this->_init($sql,$options);
	}

	/**
	 *  \brief      Fonction appelee lors de la desactivation d'un module.
	 *              Supprime de la base les constantes, boites et permissions du module.
	 *	\param		options		Options when disabling module
	 */
	function remove($options='')
	{
		$sql = array();

		return $this->_remove($sql,$options);
	}
}
?>
