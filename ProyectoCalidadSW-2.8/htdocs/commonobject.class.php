<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/commonobject.class.php
 *	\ingroup    core
 *	\brief      Fichier de la classe mere des classes metiers (facture, contrat, propal, commande, etc...)
 *	\version    $Id$
 */


/**
 *	\class 		CommonObject
 *	\brief 		Classe mere pour heritage des classes metiers
 */

class CommonObject
{
	/**
	 *      \brief      Ajoute un contact associe au l'entite definie dans $this->element
	 *      \param      fk_socpeople        Id du contact a ajouter
	 *   	\param 		type_contact 		Type de contact (code ou id)
	 *      \param      source              external=Contact externe (llx_socpeople), internal=Contact interne (llx_user)
	 *      \return     int                 <0 si erreur, >0 si ok
	 */
	function add_contact($fk_socpeople, $type_contact, $source='external')
	{
		global $langs;

		dol_syslog("CommonObject::add_contact $fk_socpeople, $type_contact, $source");

		// Verification parametres
		if ($fk_socpeople <= 0)
		{
			$this->error=$langs->trans("ErrorWrongValueForParameter","1");
			dol_syslog("CommonObject::add_contact ".$this->error,LOG_ERR);
			return -1;
		}
		if (! $type_contact)
		{
			$this->error=$langs->trans("ErrorWrongValueForParameter","2");
			dol_syslog("CommonObject::add_contact ".$this->error,LOG_ERR);
			return -2;
		}

		$id_type_contact=0;
		if (is_numeric($type_contact))
		{
			$id_type_contact=$type_contact;
		}
		else
		{
			// On recherche id type_contact
			$sql = "SELECT tc.rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc";
			$sql.= " WHERE element='".$this->element."'";
			$sql.= " AND source='".$source."'";
			$sql.= " AND code='".$type_contact."' AND active=1";
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$obj = $this->db->fetch_object($resql);
				$id_type_contact=$obj->rowid;
			}
		}

		$datecreate = time();

		// Insertion dans la base
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_contact";
		$sql.= " (element_id, fk_socpeople, datecreate, statut, fk_c_type_contact) ";
		$sql.= " VALUES (".$this->id.", ".$fk_socpeople." , " ;
		$sql.= $this->db->idate($datecreate);
		$sql.= ", 4, '". $id_type_contact . "' ";
		$sql.= ")";
		dol_syslog("CommonObject::add_contact sql=".$sql);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$this->error=$this->db->errno();
				return -2;
			}
			else
			{
				$this->error=$this->db->error()." - $sql";
				dol_syslog($this->error,LOG_ERR);
				return -1;
			}
		}
	}

	/**
	 *      \brief      Mise a jour du statut d'un contact
	 *      \param      rowid               La reference du lien contact-entite
	 * 		\param		statut	            Le nouveau statut
	 *      \param      type_contact_id     Description du type de contact
	 *      \return     int                 <0 si erreur, =0 si ok
	 */
	function update_contact($rowid, $statut, $type_contact_id)
	{
		// Insertion dans la base
		$sql = "UPDATE ".MAIN_DB_PREFIX."element_contact set";
		$sql.= " statut = ".$statut.",";
		$sql.= " fk_c_type_contact = '".$type_contact_id ."'";
		$sql.= " where rowid = ".$rowid;
		// Retour
		if (  $this->db->query($sql) )
		{
			return 0;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *    \brief      Supprime une ligne de contact
	 *    \param      rowid			La reference du contact
	 *    \return     statur        >0 si ok, <0 si ko
	 */
	function delete_contact($rowid)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_contact";
		$sql.= " WHERE rowid =".$rowid;

		dol_syslog("CommonObject::delete_contact sql=".$sql);
		if ($this->db->query($sql))
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("CommonObject::delete_contact error=".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *    \brief      Recupere les lignes de contact de l'objet
	 *    \param      statut        Statut des lignes detail a recuperer
	 *    \param      source        Source du contact external (llx_socpeople) ou internal (llx_user)
	 *    \return     array         Tableau des rowid des contacts
	 */
	function liste_contact($statut=-1,$source='external')
	{
		global $langs;

		$tab=array();

		$sql = "SELECT ec.rowid, ec.statut, ec.fk_socpeople as id";
		if ($source == 'internal') $sql.=", '-1' as socid";
		if ($source == 'external') $sql.=", t.fk_soc as socid";
		$sql.= ", t.name as nom, t.firstname";
		$sql.= ", tc.source, tc.element, tc.code, tc.libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_type_contact tc";
		$sql.= ", ".MAIN_DB_PREFIX."element_contact ec";
		if ($source == 'internal') $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."user t on ec.fk_socpeople = t.rowid";
		if ($source == 'external') $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."socpeople t on ec.fk_socpeople = t.rowid";
		$sql.= " WHERE ec.element_id =".$this->id;
		$sql.= " AND ec.fk_c_type_contact=tc.rowid";
		$sql.= " AND tc.element='".$this->element."'";
		if ($source == 'internal') $sql.= " AND tc.source = 'internal'";
		if ($source == 'external') $sql.= " AND tc.source = 'external'";
		$sql.= " AND tc.active=1";
		if ($statut >= 0) $sql.= " AND ec.statut = '".$statut."'";
		$sql.=" ORDER BY t.name ASC";

		dol_syslog("CommonObject::liste_contact sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$transkey="TypeContact_".$obj->element."_".$obj->source."_".$obj->code;
				$libelle_type=($langs->trans($transkey)!=$transkey ? $langs->trans($transkey) : $obj->libelle);
				$tab[$i]=array('source'=>$obj->source,'socid'=>$obj->socid,'id'=>$obj->id,'nom'=>$obj->nom, 'firstname'=>$obj->firstname,
                               'rowid'=>$obj->rowid,'code'=>$obj->code,'libelle'=>$libelle_type,'status'=>$obj->statut);
				$i++;
			}
			return $tab;
		}
		else
		{
			$this->error=$this->db->error();
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *    \brief      Le detail d'un contact
	 *    \param      rowid      L'identifiant du contact
	 *    \return     object     L'objet construit par DoliDb.fetch_object
	 */
	function detail_contact($rowid)
	{
		$sql = "SELECT ec.datecreate, ec.statut, ec.fk_socpeople, ec.fk_c_type_contact,";
		$sql.= " tc.code, tc.libelle,";
		$sql.= " s.fk_soc";
		$sql.= " FROM (".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc)";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as s ON ec.fk_socpeople=s.rowid";	// Si contact de type external, alors il est li� � une societe
		$sql.= " WHERE ec.rowid =".$rowid;
		$sql.= " AND ec.fk_c_type_contact=tc.rowid";
		$sql.= " AND tc.element = '".$this->element."'";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			return $obj;
		}
		else
		{
			$this->error=$this->db->error();
			dol_print_error($this->db);
			return null;
		}
	}

	/**
	 *      \brief      La liste des valeurs possibles de type de contacts
	 *      \param      source      internal ou external
	 *      \param		order		Sort order by : code or rowid
	 *      \return     array       La liste des natures
	 */
	function liste_type_contact($source, $order='code')
	{
		global $langs;

		$tab = array();

		$sql = "SELECT DISTINCT tc.rowid, tc.code, tc.libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc";
		$sql.= " WHERE tc.element='".$this->element."'";
		$sql.= " AND tc.source='".$source."'";
		$sql.= " ORDER by tc.".$order;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$transkey="TypeContact_".$this->element."_".$source."_".$obj->code;
				$libelle_type=($langs->trans($transkey)!=$transkey ? $langs->trans($transkey) : $obj->libelle);
				$tab[$obj->rowid]=$libelle_type;
				$i++;
			}
			return $tab;
		}
		else
		{
			$this->error=$this->db->error();
			//            dol_print_error($this->db);
			return null;
		}
	}

	/**
	 *      \brief      Retourne id des contacts d'une source et d'un type actif donne
	 *                  Exemple: contact client de facturation ('external', 'BILLING')
	 *                  Exemple: contact client de livraison ('external', 'SHIPPING')
	 *                  Exemple: contact interne suivi paiement ('internal', 'SALESREPFOLL')
	 *		\param		source		'external' or 'internal'
	 *		\param		code		'BILLING', 'SHIPPING', 'SALESREPFOLL', ...
	 *      \return     array       Liste des id contacts
	 */
	function getIdContact($source,$code)
	{
		$result=array();
		$i=0;

		$sql = "SELECT ec.fk_socpeople";
		$sql.= " FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc";
		$sql.= " WHERE ec.element_id = ".$this->id;
		$sql.= " AND ec.fk_c_type_contact=tc.rowid";
		$sql.= " AND tc.element = '".$this->element."'";
		$sql.= " AND tc.source = '".$source."'";
		$sql.= " AND tc.code = '".$code."'";
		$sql.= " AND tc.active = 1";

		dol_syslog("CommonObject::getIdContact sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
				$result[$i]=$obj->fk_socpeople;
				$i++;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("CommonObject::getIdContact ".$this->error, LOG_ERR);
			return null;
		}

		return $result;
	}

	/**
	 *		\brief      Charge le contact d'id $id dans this->contact
	 *		\param      contactid          Id du contact
	 *		\return		int			<0 if KO, >0 if OK
	 */
	function fetch_contact($contactid)
	{
		require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
		$contact = new Contact($this->db);
		$result=$contact->fetch($contactid);
		$this->contact = $contact;
		return $result;
	}

	/**
	 *    	\brief      Charge le tiers d'id $this->socid dans this->client
	 *		\return		int			<0 if KO, >0 if OK
	 */
	function fetch_client()
	{
		global $conf;

		$client = new Societe($this->db);
		$result=$client->fetch($this->socid);
		$this->client = $client;

		// Use first price level if level not defined for third party
		if ($conf->global->PRODUIT_MULTIPRICES && empty($this->client->price_level)) $this->client->price_level=1;

		return $result;
	}

	/**
	 *		\brief      Charge le projet d'id $this->projet_id dans this->projet
	 *		\return		int			<0 if KO, >0 if OK
	 */
	function fetch_projet()
	{
		$projet = new Project($this->db);
		$result=$projet->fetch($this->projet_id);
		$this->projet = $projet;
		return $result;
	}

	/**
	 *		\brief      Charge le user d'id userid dans this->user
	 *		\param      userid 		Id du contact
	 *		\return		int			<0 if KO, >0 if OK
	 */
	function fetch_user($userid)
	{
		$user = new User($this->db, $userid);
		$result=$user->fetch();
		$this->user = $user;
		return $result;
	}

	/**
	 *		\brief      Charge l'adresse de livraison d'id $this->fk_delivery_address dans this->deliveryaddress
	 *		\param      userid 		Id du contact
	 *		\return		int			<0 if KO, >0 if OK
	 */
	function fetch_adresse_livraison($deliveryaddressid)
	{
		$address = new Societe($this->db);
		$result=$address->fetch_adresse_livraison($deliveryaddressid);
		$this->deliveryaddress = $address;
		$this->adresse = $address; // TODO obsolete
		$this->address = $address;
		return $result;
	}

    /**
	 *		\brief		Read linked document
	 */
	function fetch_object()
	{
		$object = $this->origin;
		$class = ucfirst($object);
		$this->$object = new $class($this->db);
		$this->$object->fetch($this->origin_id);
	}


	/**
	 *      \brief      Load properties id_previous and id_next
	 *      \param      filter		Optional filter
	 *	 	\param      fieldid   	Name of field to use for the select MAX and MIN
	 *      \return     int         <0 if KO, >0 if OK
	 */
	function load_previous_next_ref($filter='',$fieldid)
	{
		global $conf, $user;

		if (! $this->table_element)
		{
			dol_syslog("CommonObject::load_previous_next was called on objet with property table_element not defined", LOG_ERR);
			return -1;
		}

 		// this->ismultientitymanaged contains
		// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
		$alias = 's';
		if ($this->element == 'societe') $alias = 'te';

		$sql = "SELECT MAX(te.".$fieldid.")";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as te";
		if ($this->ismultientitymanaged == 2 || ($this->element != 'societe' && !$this->isnolinkedbythird && !$user->rights->societe->client->voir)) $sql.= ", ".MAIN_DB_PREFIX."societe as s";	// If we need to link to societe to limit select to entity
		if (!$this->isnolinkedbythird && !$user->rights->societe->client->voir) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON ".$alias.".rowid = sc.fk_soc";
		$sql.= " WHERE te.".$fieldid." < '".addslashes($this->ref)."'";
		if (!$this->isnolinkedbythird && !$user->rights->societe->client->voir) $sql.= " AND sc.fk_user = " .$user->id;
		if (isset($filter)) $sql.=" AND ".$filter;
		if ($this->ismultientitymanaged == 2 || ($this->element != 'societe' && !$this->isnolinkedbythird && !$user->rights->societe->client->voir)) $sql.= ' AND te.fk_soc = s.rowid';			// If we need to link to societe to limit select to entity
		if ($this->ismultientitymanaged > 0) $sql.= ' AND te.entity IN (0,'.$conf->entity.')';

		//print $sql."<br>";
		$result = $this->db->query($sql) ;
		if (! $result)
		{
			$this->error=$this->db->error();
			return -1;
		}
		$row = $this->db->fetch_row($result);
		$this->ref_previous = $row[0];


		$sql = "SELECT MIN(te.".$fieldid.")";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as te";
		if ($this->ismultientitymanaged == 2 || ($this->element != 'societe' && !$this->isnolinkedbythird && !$user->rights->societe->client->voir)) $sql.= ", ".MAIN_DB_PREFIX."societe as s";	// If we need to link to societe to limit select to entity
		if (!$this->isnolinkedbythird && !$user->rights->societe->client->voir) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON ".$alias.".rowid = sc.fk_soc";
		$sql.= " WHERE te.".$fieldid." > '".addslashes($this->ref)."'";
		if (!$this->isnolinkedbythird && !$user->rights->societe->client->voir) $sql.= " AND sc.fk_user = " .$user->id;
		if (isset($filter)) $sql.=" AND ".$filter;
		if ($this->ismultientitymanaged == 2 || ($this->element != 'societe' && !$this->isnolinkedbythird && !$user->rights->societe->client->voir)) $sql.= ' AND te.fk_soc = s.rowid';			// If we need to link to societe to limit select to entity
		if ($this->ismultientitymanaged > 0) $sql.= ' AND te.entity IN (0,'.$conf->entity.')';
		// Rem: Bug in some mysql version: SELECT MIN(rowid) FROM llx_socpeople WHERE rowid > 1 when one row in database with rowid=1, returns 1 instead of null

		//print $sql."<br>";
		$result = $this->db->query($sql) ;
		if (! $result)
		{
			$this->error=$this->db->error();
			return -2;
		}
		$row = $this->db->fetch_row($result);
		$this->ref_next = $row[0];

		return 1;
	}


	/**
	 *      \brief      On recupere les id de liste_contact
	 *      \param      source      Source du contact external (llx_socpeople) ou internal (llx_user)
	 *      \return     array
	 */
	function getListContactId($source='external')
	{
		$contactAlreadySelected = array();
		$tab = $this->liste_contact(-1,$source);
		$num=sizeof($tab);
		$i = 0;
		while ($i < $num)
		{
			$contactAlreadySelected[$i] = $tab[$i]['id'];
			$i++;
		}
		return $contactAlreadySelected;
	}


	/**
	 *	\brief     	Link element with a project
	 *	\param     	projid		Project id to link element to
	 *	\return		int			<0 if KO, >0 if OK
	 */
	function setProject($projid)
	{
		if (! $this->table_element)
		{
			dol_syslog("CommonObject::setProject was called on objet with property table_element not defined",LOG_ERR);
			return -1;
		}

		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
		if ($projid) $sql.= ' SET fk_projet = '.$projid;
		else $sql.= ' SET fk_projet = NULL';
		$sql.= ' WHERE rowid = '.$this->id;

		dol_syslog("CommonObject::setProject sql=".$sql);
		if ($this->db->query($sql))
		{
			$this->projet_id=$projid;
			$this->projetidp=$projid;
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *		\brief		Set last model used by doc generator
	 *		\param		user		User object that make change
	 *		\param		modelpdf	Modele name
	 *		\return		int			<0 if KO, >0 if OK
	 */
	function setDocModel($user, $modelpdf)
	{
		if (! $this->table_element)
		{
			dol_syslog("CommonObject::setDocModel was called on objet with property table_element not defined",LOG_ERR);
			return -1;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql.= " SET model_pdf = '".$modelpdf."'";
		$sql.= " WHERE rowid = ".$this->id;
		// if ($this->element == 'facture') $sql.= " AND fk_statut < 2";
		// if ($this->element == 'propal')  $sql.= " AND fk_statut = 0";

		dol_syslog("CommonObject::setDocModel sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->modelpdf=$modelpdf;
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return 0;
		}
	}


	/**
	 *      \brief      Stocke un numero de rang pour toutes les lignes de
	 *                  detail d'une facture qui n'en ont pas.
	 * 		\param		renum		true to renum all already ordered lines, false to renum only not already ordered lines.
	 */
	function line_order($renum=false)
	{
		if (! $this->table_element_line)
		{
			dol_syslog("CommonObject::line_order was called on objet with property table_element_line not defined",LOG_ERR);
			return -1;
		}
		if (! $this->fk_element)
		{
			dol_syslog("CommonObject::line_order was called on objet with property fk_element not defined",LOG_ERR);
			return -1;
		}

		$sql = 'SELECT count(rowid) FROM '.MAIN_DB_PREFIX.$this->table_element_line;
		$sql.= ' WHERE '.$this->fk_element.'='.$this->id;
		if (! $renum) $sql.= ' AND rang = 0';
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$nl = $row[0];
		}
		if ($nl > 0)
		{
			$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.$this->table_element_line;
			$sql.= ' WHERE '.$this->fk_element.' = '.$this->id;
			$sql.= ' ORDER BY rang ASC, rowid ASC';
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$num = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num)
				{
					$row = $this->db->fetch_row($resql);
					$li[$i] = $row[0];
					$i++;
				}
			}
			for ($i = 0 ; $i < sizeof($li) ; $i++)
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element_line.' SET rang = '.($i+1);
				$sql.= ' WHERE rowid = '.$li[$i];
				if (!$this->db->query($sql) )
				{
					dol_syslog($this->db->error());
				}
			}
		}
	}

	function line_up($rowid)
	{
		$this->line_order();

		/* Lecture du rang de la ligne */
		$sql = 'SELECT rang FROM '.MAIN_DB_PREFIX.$this->table_element_line;
		$sql.= ' WHERE rowid ='.$rowid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$rang = $row[0];
		}

		if ($rang > 1 )
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element_line.' SET rang = '.$rang ;
			$sql.= ' WHERE '.$this->fk_element.' = '.$this->id;
			$sql.= ' AND rang = '.($rang - 1);
			if ($this->db->query($sql) )
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element_line.' SET rang  = '.($rang - 1);
				$sql.= ' WHERE rowid = '.$rowid;
				if (! $this->db->query($sql) )
				{
					dol_print_error($this->db);
				}
			}
			else
			{
				dol_print_error($this->db);
			}
		}
	}

	function line_down($rowid)
	{
		$this->line_order();

		/* Lecture du rang de la ligne */
		$sql = 'SELECT rang FROM '.MAIN_DB_PREFIX.$this->table_element_line;
		$sql.= ' WHERE rowid ='.$rowid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$rang = $row[0];
		}

		/* Lecture du rang max de la facture */
		$sql = 'SELECT max(rang) FROM '.MAIN_DB_PREFIX.$this->table_element_line;
		$sql.= ' WHERE '.$this->fk_element.' = '.$this->id;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$max = $row[0];
		}

		if ($rang < $max)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element_line.' SET rang = '.$rang;
			$sql.= ' WHERE '.$this->fk_element.' = '.$this->id;
			$sql.= ' AND rang = '.($rang+1);
			if ($this->db->query($sql) )
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element_line.' SET rang = '.($rang+1);
				$sql.= ' WHERE rowid = '.$rowid;
				if (! $this->db->query($sql) )
				{
					dol_print_error($this->db);
				}
			}
			else
			{
				dol_print_error($this->db);
			}
		}
	}

	/**
	 *    \brief      Update private note of element
	 *    \param      note			New value for note
	 *    \return     int         	<0 if KO, >0 if OK
	 */
	function update_note($note)
	{
		if (! $this->table_element)
		{
			dol_syslog("CommonObject::update_note was called on objet with property table_element not defined", LOG_ERR);
			return -1;
		}

		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
		// TODO uniformize fields note_private
		if ($this->table_element == 'fichinter' || $this->table_element == 'projet' || $this->table_element == 'projet_task')
		{
			$sql.= " SET note_private = '".addslashes($note)."'";
		}
		else
		{
			$sql.= " SET note = '".addslashes($note)."'";
		}
		$sql.= " WHERE rowid =". $this->id;

		dol_syslog("CommonObject::update_note sql=".$sql, LOG_DEBUG);
		if ($this->db->query($sql))
		{
			$this->note = $note;
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("CommonObject::update_note error=".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *    \brief      Update public note of element
	 *    \param      note_public	New value for note
	 *    \return     int         	<0 if KO, >0 if OK
	 */
	function update_note_public($note_public)
	{
		if (! $this->table_element)
		{
			dol_syslog("CommonObject::update_note_public was called on objet with property table_element not defined",LOG_ERR);
			return -1;
		}

		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
		$sql.= " SET note_public = '".addslashes($note_public)."'";
		$sql.= " WHERE rowid =". $this->id;

		dol_syslog("CommonObject::update_note_public sql=".$sql);
		if ($this->db->query($sql))
		{
			$this->note_public = $note_public;
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *	 \brief     	Update total_ht, total_ttc and total_vat for an object (sum of lines)
	 *	 \return		int			<0 si ko, >0 si ok
	 */
	function update_price()
	{
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

		$err=0;

		// List lines to sum
		$fieldtva='total_tva';
		if ($this->element == 'facture_fourn') $fieldtva='tva';

		$sql = 'SELECT qty, total_ht, '.$fieldtva.' as total_tva, total_ttc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element_line;
		$sql.= ' WHERE '.$this->fk_element.' = '.$this->id;

		dol_syslog("CommonObject::update_price sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->total_ht  = 0;
			$this->total_tva = 0;
			$this->total_ttc = 0;

			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$this->total_ht       += $obj->total_ht;
				$this->total_tva      += $obj->total_tva;
				$this->total_ttc      += $obj->total_ttc;

				$i++;
			}

			$this->db->free($resql);

			// Now update field total_ht, total_ttc and tva
			$fieldht='total_ht';
			if ($this->element == 'facture') $fieldht='total';
			if ($this->element == 'facturerec') $fieldht='total';
			$fieldtva='tva';
			if ($this->element == 'facture_fourn') $fieldtva='total_tva';
			$fieldttc='total_ttc';
			if ($this->element == 'propal') $fieldttc='total';

			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET';
			$sql .= " ".$fieldht."='".price2num($this->total_ht)."',";
			$sql .= " ".$fieldtva."='".price2num($this->total_tva)."',";
			$sql .= " ".$fieldttc."='".price2num($this->total_ttc)."'";
			$sql .= ' WHERE rowid = '.$this->id;

			//print "xx".$sql;
			dol_syslog("CommonObject::update_price sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("CommonObject::update_price error=".$this->error,LOG_ERR);
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("CommonObject::update_price error=".$this->error,LOG_ERR);
			return -1;
		}
	}

	/**
	 * 	Add objects linked in llx_element_element.
	 */
	function add_object_linked()
	{
		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
		$sql.= "fk_source";
		$sql.= ", sourcetype";
		$sql.= ", fk_target";
		$sql.= ", targettype";
		$sql.= ") VALUES (";
		$sql.= $this->origin_id;
		$sql.= ", '".$this->origin."'";
		$sql.= ", ".$this->id;
		$sql.= ", '".$this->element."'";
		$sql.= ")";

		if ($this->db->query($sql))
	  	{
	  		$this->db->commit();
	  		return 1;
	  	}
	  	else
	  	{
	  		$this->error=$this->db->lasterror()." - sql=$sql";
	  		$this->db->rollback();
	  		return 0;
	  	}
	}

	/**
	 * 	Load array of objects linked to current object. Links are loaded into this->linked_object array.
	 */
	function load_object_linked($sourceid='',$sourcetype='',$targetid='',$targettype='')
	{
		$this->linked_object=array();

		$sourceid = (!empty($sourceid)?$sourceid:$this->id);
		$targetid = (!empty($targetid)?$targetid:$this->id);
		$sourcetype = (!empty($sourcetype)?$sourcetype:$this->origin);
		$targettype = (!empty($targettype)?$targettype:$this->element);

		// Links beetween objects are stored in this table
		$sql = 'SELECT fk_source, sourcetype, fk_target, targettype';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'element_element';
		$sql.= " WHERE (fk_source = '".$sourceid."' AND sourcetype = '".$sourcetype."')";
		$sql.= " OR    (fk_target = '".$targetid."' AND targettype = '".$targettype."')";

		dol_syslog("CommonObject::load_object_linked sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				if ($obj->fk_source == $sourceid)
				{
					$this->linked_object[]=array('linkid'=>$obj->fk_target, 'type'=>$obj->targettype);
				}
				if ($obj->fk_target == $targetid)
				{
					$this->linked_object[]=array('linkid'=>$obj->fk_source, 'type'=>$obj->sourcetype);
				}
				$i++;
			}
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *      \brief      Set statut of an object
	 *      \param		statut			Statut to set
	 *      \param		elementid		Id of element to force (use this->id by default)
	 *      \param		elementtype		Type of element to force (use ->this->element by default)
	 *      \return     int				<0 if ko, >0 if ok
	 */
	function setStatut($statut,$elementId='',$elementType='')
	{
		$elementId = (!empty($elementId)?$elementId:$this->id);
		$elementTable = (!empty($elementType)?$elementType:$this->element);

		$sql = "UPDATE ".MAIN_DB_PREFIX.$elementTable;
		$sql.= " SET fk_statut = ".$statut;
		$sql.= " WHERE rowid=".$elementId;

		dol_syslog("CommonObject::setStatut sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql)
		{
			$this->error=$this->db->lasterror();
			dol_syslog("CommonObject::setStatut ".$this->error, LOG_ERR);
			return -1;
		}

		return 1;
	}

}

?>
