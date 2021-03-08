<?php
/* Copyright (C) 2003      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006-2007 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
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
 \file       htdocs/livraison/livraison.class.php
 \ingroup    livraison
 \brief      Fichier de la classe de gestion des bons de livraison
 \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/expedition/expedition.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/mouvementstock.class.php");
if ($conf->propal->enabled)   require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");


/**
 \class      Livraison
 \brief      Classe de gestion des bons de livraison
 */
class Livraison extends CommonObject
{
	var $db;
	var $error;
	var $element="delivery";
	var $fk_element="fk_livraison";
	var $table_element="livraison";

	var $id;
	var $brouillon;
	var $origin;
	var $origin_id;
	var $socid;
	var $ref_client;

	var $expedition_id;

	var $date_livraison;
	var $date_creation;
	var $date_valid;


	/**
	 * Initialisation
	 */
	function Livraison($DB)
	{
		$this->db = $DB;
		$this->lignes = array();
		$this->products = array();

		// List of short language codes for status
		$this->statuts[-1] = 'StatusSendingCanceled';
		$this->statuts[0]  = 'StatusSendingDraft';
		$this->statuts[1]  = 'StatusSendingValidated';
	}

	/**
	 *    \brief      Create delivery receipt in database
	 *    \param      user        Objet du user qui cree
	 *    \return     int         <0 si erreur, id livraison cree si ok
	 */
	function create($user)
	{
		global $conf;

		dol_syslog("Livraison::create");

		$error = 0;

		/* On positionne en mode brouillon le bon de livraison */
		$this->brouillon = 1;

		$this->user = $user;

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."livraison (";
		$sql.= "ref";
		$sql.= ", entity";
		$sql.= ", fk_soc";
		$sql.= ", ref_client";
		$sql.= ", date_creation";
		$sql.= ", fk_user_author";
		$sql.= ", fk_adresse_livraison";
		$sql.= ", fk_expedition";
		$sql.= ")";
		$sql.= " VALUES (";
		$sql.= "'(PROV)'";
		$sql.= ", ".$conf->entity;
		$sql.= ", ".$this->socid;
		$sql.= ", '".$this->ref_client."'";
		$sql.= ", ".$this->db->idate(mktime());
		$sql.= ", ".$user->id;
		$sql.= ", ".($this->fk_delivery_address > 0 ? $this->fk_delivery_address : "null");
		$sql.= ", ".($this->expedition_id ? $this->expedition_id : "null");
		$sql.= ")";

		dol_syslog("Livraison::create sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."livraison");

			$numref = "(PROV".$this->id.")";

			$sql = "UPDATE ".MAIN_DB_PREFIX."livraison ";
			$sql.= "SET ref = '".addslashes($numref)."'";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog("Livraison::create sql=".$sql, LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				if (! $conf->expedition->enabled)
				{
					$commande = new Commande($this->db);
					$commande->id = $this->commande_id;
					$this->lignes = $commande->fetch_lines();
				}


				/*
				 *  Insertion des produits dans la base
				 */
				for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
				{
					$origin_id=$this->lignes[$i]->origin_line_id;
					if (! $origin_id) $origin_id=$this->lignes[$i]->commande_ligne_id;	// For backward compatibility

					if (! $this->create_line(0, $origin_id, $this->lignes[$i]->qty, $this->lignes[$i]->fk_product, $this->lignes[$i]->description))
					{
						$error++;
					}
				}

				if (! $error && $this->id && $this->origin_id)
				{
					$ret = $this->add_object_linked();
					if (!$ret)
					{
						$error++;
					}

					if (! $conf->expedition_bon->enabled)
					{
						if ($conf->commande->enabled)
						{
							$ret = $this->setStatut(2,$this->origin_id,$this->origin);
						}
						else
						{
							// TODO definir un statut
							$ret = $this->setStatut(9,$this->origin_id,$this->origin);
						}
						if (! $ret)
						{
							$error++;
						}
					}
				}

				if (! $error)
				{
					$this->db->commit();
					return $this->id;
				}
				else
				{
					$error++;
					$this->error=$this->db->lasterror()." - sql=".$this->db->lastqueryerror;
					dol_syslog("Livraison::create Error -3 ".$this->error, LOG_ERR);
					$this->db->rollback();
					return -3;
				}
			}
			else
			{
				$error++;
				$this->error=$this->db->lasterror()." - sql=".$this->db->lastqueryerror;
				dol_syslog("Livraison::create Error -2 ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$error++;
			$this->error=$this->db->lasterror()." - sql=".$this->db->lastqueryerror;
			dol_syslog("Livraison::create Error -1 ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *
	 *
	 */
	function create_line($transaction, $commande_ligne_id, $qty, $fk_product=0, $description)
	{
		$error = 0;
		$idprod = $fk_product;
		$j = 0;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."livraisondet (fk_livraison, fk_origin_line,";
		$sql.= " fk_product, description, qty)";
		$sql.= " VALUES (".$this->id.",".$commande_ligne_id.",";
		$sql.= " ".($idprod>0?$idprod:"null").",";
		$sql.= " ".($description?"'".addslashes($description)."'":"null").",";
		$sql.= $qty.")";

		dol_syslog("Livraison::create_line sql=".$sql, LOG_DEBUG);
		if (! $this->db->query($sql) )
		{
			$error++;
		}

		if ($error == 0 )
		{
			return 1;
		}
	}

	/**
	 * 	\brief			Read a delivery receipt
	 */
	function fetch($id)
	{
		global $conf;

		$sql = "SELECT l.rowid, l.fk_soc, l.date_creation, l.date_valid, l.ref, l.ref_client, l.fk_user_author,";
		$sql.=" l.total_ht, l.fk_statut, l.fk_expedition, l.fk_user_valid, l.note, l.note_public";
		$sql.= ", l.date_livraison as date_livraison, l.fk_adresse_livraison, l.model_pdf";
		$sql.= ", el.fk_source as origin_id";
		$sql.= " FROM ".MAIN_DB_PREFIX."livraison as l";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON l.rowid = el.fk_target";
		$sql.= " WHERE l.rowid = ".$id;

		dol_syslog("Livraison::fetch sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql) ;
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id                   = $obj->rowid;
				$this->date_livraison       = $this->db->jdate($obj->date_livraison);
				$this->date_creation        = $this->db->jdate($obj->date_creation);
				$this->date_valid           = $this->db->jdate($obj->date_valid);
				$this->ref                  = $obj->ref;
				$this->ref_client           = $obj->ref_client;
				$this->socid                = $obj->fk_soc;
				$this->statut               = $obj->fk_statut;
				$this->origin_id            = $obj->origin_id;
				$this->expedition_id        = $obj->fk_expedition;
				$this->user_author_id       = $obj->fk_user_author;
				$this->user_valid_id        = $obj->fk_user_valid;
				$this->adresse_livraison_id = $obj->fk_adresse_livraison; // TODO obsolete
				$this->fk_delivery_address  = $obj->fk_adresse_livraison;
				$this->note                 = $obj->note;
				$this->note_public          = $obj->note_public;
				$this->modelpdf             = $obj->model_pdf;

				$this->db->free($result);

				if ($this->origin_id)
				{
					if ($conf->commande->enabled)
					{
						$this->origin = "commande";
					}
					else
					{
						$this->origin = "propal";
					}
				}

				if ($this->statut == 0) $this->brouillon = 1;

				$file = $conf->livraison->dir_output . "/" .get_exdir($livraison->id,2) . "/" . $this->id.".pdf";
				$this->pdf_filename = $file;

				/*
				 * Lignes
				 */
				$result=$this->fetch_lignes();
				if ($result < 0)
				{
					return -3;
				}

				return 1;
			}
			else
			{
				$this->error='Delivery with id '.$rowid.' not found sql='.$sql;
				dol_syslog('Livraison::Fetch Error '.$this->error, LOG_ERR);
				return -2;
			}
		}
		else
		{
			dol_syslog('Livraison::Fetch Error '.$this->error, LOG_ERR);
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *        \brief      Valide l'expedition, et met a jour le stock si stock gere
	 *        \param      user        Objet de l'utilisateur qui valide
	 *        \return     int
	 */
	function valid($user)
	{
		global $conf;

		dol_syslog("livraison.class.php::valid begin");

		$this->db->begin();

		$error = 0;

		if ($user->rights->expedition->livraison->valider)
		{
			if (defined('LIVRAISON_ADDON'))
			{
				if (is_readable(DOL_DOCUMENT_ROOT .'/includes/modules/livraison/'.LIVRAISON_ADDON.'.php'))
				{
					require_once DOL_DOCUMENT_ROOT .'/includes/modules/livraison/'.LIVRAISON_ADDON.'.php';

					// Definition du nom de module de numerotation de commande
					$modName=LIVRAISON_ADDON;

					// Recuperation de la nouvelle reference
					$objMod = new $modName($this->db);
					$soc = new Societe($this->db);
					$soc->fetch($this->socid);

					// on verifie si le bon de livraison est en numerotation provisoire
					$livref = substr($this->ref, 1, 4);
					if ($livref == 'PROV')
					{
						$this->ref = $objMod->livraison_get_num($soc,$this);
					}

					// Tester si non deja au statut valide. Si oui, on arrete afin d'eviter
					// de decrementer 2 fois le stock.
					$sql = "SELECT ref FROM ".MAIN_DB_PREFIX."livraison where ref='".$this->ref."' AND fk_statut <> 0";
					$resql=$this->db->query($sql);
					if ($resql)
					{
						$num = $this->db->num_rows($resql);
						if ($num > 0)
						{
							return 0;
						}
					}

					$sql = "UPDATE ".MAIN_DB_PREFIX."livraison ";
					$sql.= " SET ref='".addslashes($this->ref)."', fk_statut = 1, date_valid = ".$this->db->idate(mktime()).", fk_user_valid = ".$user->id;
					$sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";
					$resql=$this->db->query($sql);
					if ($resql)
					{
						// Si module stock gere et que expedition faite depuis un entrepot
						if (!$conf->expedition->enabled && $conf->stock->enabled && $this->entrepot_id && $conf->global->STOCK_CALCULATE_ON_SHIPMENT == 1)
						{

							//Enregistrement d'un mouvement de stock pour chaque produit de l'expedition


							dol_syslog("livraison.class.php::valid enregistrement des mouvements");

							$sql = "SELECT cd.fk_product, cd.subprice, ld.qty ";
							$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
							$sql.= ", ".MAIN_DB_PREFIX."livraisondet as ld";
							$sql.= " WHERE ld.fk_livraison = ".$this->id;
							$sql.= " AND cd.rowid = ld.fk_commande_ligne";

							$resql=$this->db->query($sql);
							if ($resql)
							{
								$num = $this->db->num_rows($resql);
								$i=0;
								while($i < $num)
								{
									dol_syslog("livraison.class.php::valid movment $i");

									$obj = $this->db->fetch_object($resql);

									$mouvS = new MouvementStock($this->db);
									$result=$mouvS->livraison($user, $obj->fk_product, $this->entrepot_id, $obj->qty, $obj->subprice);
									if ($result < 0)
									{
										$this->db->rollback();
										$this->error=$this->db->error()." - sql=$sql";
										dol_syslog("livraison.class.php::valid ".$this->error, LOG_ERR);
										return -3;
									}
									$i++;
								}

							}
							else
							{
								$this->db->rollback();
								$this->error=$this->db->error()." - sql=$sql";
								dol_syslog("livraison.class.php::valid ".$this->error, LOG_ERR);
								return -2;

							}
						}

						// On efface le repertoire de pdf provisoire
						$livraisonref = dol_sanitizeFileName($this->ref);
						if ($conf->expedition->dir_output)
						{
							$dir = $conf->livraison->dir_output . "/" . $livraisonref ;
							$file = $dir . "/" . $livraisonref . ".pdf";
							if (file_exists($file))
							{
								if (!dol_delete_file($file))
								{
									$this->error=$langs->trans("ErrorCanNotDeleteFile",$file);
									return 0;
								}
							}
							if (file_exists($dir))
							{
								if (!dol_delete_dir($dir))
								{
									$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
									return 0;
								}
							}
						}

						dol_syslog("livraison.class.php::valid ok");
					}
					else
					{
						$this->db->rollback();
						$this->error=$this->db->error()." - sql=$sql";
						dol_syslog("livraison.class.php::valid ".$this->error, LOG_ERR);
						return -1;
					}
				}
			}
		}
		else
		{
			$this->error="Non autorise";
			dol_syslog("livraison.class.php::valid ".$this->error, LOG_ERR);
			return -1;
		}

		$this->db->commit();
		dol_syslog("livraison.class.php::valid commit");
		return 1;
	}

	/**     \brief      Cree le bon de livraison depuis une expedition existante
	 *		\param      user            Utilisateur qui cree
	 *		\param      sending_id      Id de l'expedition qui sert de modele
	 */
	function create_from_sending($user, $sending_id)
	{
		$expedition = new Expedition($this->db);
		$result=$expedition->fetch($sending_id);

		$this->lignes = array();
		$this->date_livraison = time();
		$this->expedition_id = $sending_id;

		for ($i = 0 ; $i < sizeof($expedition->lignes) ; $i++)
		{
			$LivraisonLigne = new LivraisonLigne($this->db);
			$LivraisonLigne->origin_line_id    = $expedition->lignes[$i]->origin_line_id;
			$LivraisonLigne->libelle           = $expedition->lignes[$i]->libelle;
			$LivraisonLigne->description       = $expedition->lignes[$i]->description;
			$LivraisonLigne->qty               = $expedition->lignes[$i]->qty_shipped;
			$LivraisonLigne->fk_product        = $expedition->lignes[$i]->fk_product;
			$LivraisonLigne->ref               = $expedition->lignes[$i]->ref;
			$this->lignes[$i] = $LivraisonLigne;
		}

		$this->origin               = $expedition->origin;
		$this->origin_id            = $expedition->origin_id;
		$this->note                 = $expedition->note;
		$this->projetid             = $expedition->projetidp;
		$this->date_livraison       = $expedition->date_livraison;
		$this->adresse_livraison_id = $expedition->adresse_livraison_id; // TODO obsolete
		$this->fk_delivery_address  = $expedition->adresse_livraison_id;
		$this->socid                = $expedition->socid;
		$this->ref_client			= $expedition->ref_client;

		return $this->create($user);
	}


	/**
	 * Ajoute une ligne
	 *
	 */
	function addline( $id, $qty )
	{
		$num = sizeof($this->lignes);
		$ligne = new LivraisonLigne($this->db);

		$ligne->commande_ligne_id = $id;
		$ligne->qty = $qty;

		$this->lignes[$num] = $ligne;
	}

	/**
	 *
	 *
	 */
	function delete_line($idligne)
	{
		if ($this->statut == 0)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."commandedet";
			$sql.= " WHERE rowid = ".$idligne;

			if ($this->db->query($sql) )
			{
				$this->update_price();

				return 1;
			}
			else
			{
				return 0;
			}
		}
	}

	/**
	 * Supprime la fiche
	 *
	 */
	function delete()
	{
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."livraisondet";
		$sql.= " WHERE fk_livraison = ".$this->id;
		if ( $this->db->query($sql) )
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."livraison";
			$sql.= " WHERE rowid = ".$this->id;
			if ( $this->db->query($sql) )
			{
				$this->db->commit();

				// On efface le repertoire de pdf provisoire
				$livref = dol_sanitizeFileName($this->ref);
				if ($conf->livraison->dir_output)
				{
					$dir = $conf->livraison->dir_output . "/" . $livref ;
					$file = $conf->livraison->dir_output . "/" . $livref . "/" . $livref . ".pdf";
					if (file_exists($file))
					{
						if (!dol_delete_file($file))
						{
							$this->error=$langs->trans("ErrorCanNotDeleteFile",$file);
							return 0;
						}
					}
					if (file_exists($dir))
					{
						if (!dol_delete_dir($dir))
						{
							$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
							return 0;
						}
					}
				}

				return 1;
			}
			else
			{
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *
	 *
	 */
	function fetch_lignes()
	{
		$this->lignes = array();

		$sql = "SELECT ld.rowid, ld.fk_product, ld.description, ld.subprice, ld.total_ht, ld.qty as qty_shipped,";
		$sql.= " cd.qty as qty_asked,";
		$sql.= " p.ref, p.fk_product_type as fk_product_type, p.label as label, p.description as product_desc";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd, ".MAIN_DB_PREFIX."livraisondet as ld";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on p.rowid = ld.fk_product";
		$sql.= " WHERE ld.fk_origin_line = cd.rowid";
		$sql.= " AND ld.fk_livraison = ".$this->id;

		dol_syslog("Livraison::fetch_lignes sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$ligne = new LivraisonLigne($this->db);

				$obj = $this->db->fetch_object($resql);

				$ligne->description    = $obj->description;
				$ligne->fk_product     = $obj->fk_product;
				$ligne->qty_asked      = $obj->qty_asked;
				$ligne->qty_shipped    = $obj->qty_shipped;

				$ligne->ref            = $obj->ref;
				$ligne->libelle        = $obj->label;           // Label produit
				$ligne->label          = $obj->label;
				$ligne->product_desc   = $obj->product_desc;    // Description produit
				$ligne->product_type   = $obj->fk_product_type;

				$ligne->price          = $obj->price;
				$ligne->total_ht       = $obj->total_ht;

				$this->lignes[$i] = $ligne;
				$i++;
			}
			$this->db->free($resql);
		}

		return $this->lignes;
	}


	/**
	 *    \brief      Retourne le libelle du statut d'une expedition
	 *    \return     string      Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *		\brief      Renvoi le libelle d'un statut donne
	 *    	\param      statut      Id statut
	 *    	\param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    	\return     string		Libelle
	 */
	function LibStatut($statut,$mode)
	{
		global $langs;

		if ($mode==0)
		{
			if ($statut==-1) return $langs->trans('StatusSendingCanceled');
			if ($statut==0)  return $langs->trans('StatusSendingDraft');
			if ($statut==1)  return $langs->trans('StatusSendingValidated');
		}
		if ($mode==1)
		{
			if ($statut==-1) return $langs->trans($this->statuts[$statut]);
			if ($statut==0)  return $langs->trans($this->statuts[$statut]);
			if ($statut==1)  return $langs->trans($this->statuts[$statut]);
		}
		if ($mode == 4)
		{
			if ($statut==-1) return img_picto($langs->trans('StatusSendingCanceled'),'statut5').' '.$langs->trans('StatusSendingCanceled');
			if ($statut==0)  return img_picto($langs->trans('StatusSendingDraft'),'statut0').' '.$langs->trans('StatusSendingDraft');
			if ($statut==1)  return img_picto($langs->trans('StatusSendingValidated'),'statut4').' '.$langs->trans('StatusSendingValidated');
		}
	}


	/**
	 *		\brief		Initialise object with default value to be used as example
	 */
	function initAsSpecimen()
	{
		global $user,$langs,$conf;

		// Charge tableau des id de societe socids
		$socids = array();

		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe";
		$sql.= " WHERE client IN (1, 3)";
		$sql.= " AND entity = ".$conf->entity;
		$sql.= " LIMIT 10";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num_socs = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_socs)
			{
				$i++;

				$row = $this->db->fetch_row($resql);
				$socids[$i] = $row[0];
			}
		}

		// Charge tableau des produits prodids
		$prodids = array();

		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."product";
		$sql.= " WHERE envente = 1";
		$sql.= " AND entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num_prods = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_prods)
			{
				$i++;
				$row = $this->db->fetch_row($resql);
				$prodids[$i] = $row[0];
			}
		}

		// Initialise parametres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$socid = rand(1, $num_socs);
		$this->socid = $socids[$socid];
		$this->date_livraison = time();
		$this->note_public='SPECIMEN';

		$i=0;
		$ligne=new LivraisonLigne($this->db);
		$ligne->fk_product     = $prodids[0];
		$ligne->qty_asked      = 10;
		$ligne->qty_shipped    = 9;
		$ligne->ref            = 'REFPROD';
		$ligne->label          = 'Specimen';
		$ligne->description    = 'Description';
		$ligne->price          = 100;
		$ligne->total_ht       = 100;
		$this->lignes[$i] = $ligne;
	}
	
	/**
	 *   \brief      Renvoie la quantite de produit restante a livrer pour une commande
	 *   \return     array		Product remaining to be delivered
	 */
	function getRemainingDelivered()
	{
		global $langs;
		
		// Get the linked object
		$this->load_object_linked(-1,-1,$this->id,$this->element);
				
		// Get the product ref and qty in source
		$sqlSourceLine = "SELECT st.rowid, st.description, st.qty";
		$sqlSourceLine.= ", p.ref, p.label";
		$sqlSourceLine.= " FROM ".MAIN_DB_PREFIX.$this->linked_object[0]['type']."det as st";
		$sqlSourceLine.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON st.fk_product = p.rowid";
		$sqlSourceLine.= " WHERE fk_".$this->linked_object[0]['type']." = ".$this->linked_object[0]['linkid'];

		$resultSourceLine = $this->db->query($sqlSourceLine);
		if ($resultSourceLine)	
		{
			$num_lines = $this->db->num_rows($resultSourceLine);
			$i = 0;
			$resultArray = array();
			while ($i < $num_lines)
			{
				$objSourceLine = $this->db->fetch_object($resultSourceLine);
				
				// Recupere les lignes de la source deja livrees
				$sql = "SELECT ld.fk_origin_line, sum(ld.qty) as qty";
				$sql.= " FROM ".MAIN_DB_PREFIX."livraisondet as ld, ".MAIN_DB_PREFIX."livraison as l,";
				$sql.= " ".MAIN_DB_PREFIX.$this->linked_object[0]['type']." as c";
				$sql.= ", ".MAIN_DB_PREFIX.$this->linked_object[0]['type']."det as cd";
				$sql.= " WHERE ld.fk_livraison = l.rowid";
				$sql.= " AND ld.fk_origin_line = cd.rowid";
				$sql.= " AND cd.fk_".$this->linked_object[0]['type']." = c.rowid";
				$sql.= " AND cd.fk_".$this->linked_object[0]['type']." = ".$this->linked_object[0]['linkid'];
				$sql.= " AND ld.fk_origin_line = ".$objSourceLine->rowid;
				$sql.= " GROUP BY ld.fk_origin_line";
	
				$result = $this->db->query($sql);
				$row = $this->db->fetch_row($result);
					
				if ($objSourceLine->qty - $row[1] > 0)	
				{
					if ($row[0] == $objSourceLine->rowid)
					{
						$array[$i]['qty'] = $objSourceLine->qty - $row[1];
					}
					else
					{
						$array[$i]['qty'] = $objSourceLine->qty;
					}
						
					$array[$i]['ref'] = $objSourceLine->ref;
					$array[$i]['label'] = $objSourceLine->label?$objSourceLine->label:$objSourceLine->description;
				}
				elseif($objSourceLine->qty - $row[1] < 0)
				{
					$array[$i]['qty'] = $objSourceLine->qty - $row[1]. " Erreur livraison !";
					$array[$i]['ref'] = $objSourceLine->ref;
					$array[$i]['label'] = $objSourceLine->label?$objSourceLine->label:$objSourceLine->description;					
				}
				
					$i++;
			}
			return $array;
		}
		else
		{
			$this->error=$this->db->error()." - sql=$sqlSourceLine";
			dol_syslog("livraison.class.php::getRemainingDelivered ".$this->error, LOG_ERR);
			return -1;
		}
	}
	
	/**
	 *      \brief      Renvoie un tableau avec les livraisons par ligne
	 *      \param      filtre_statut       Filtre sur statut
	 *      \return     int                 0 si OK, <0 si KO
	 *      \TODO  obsolete
	 */
	function livraison_array($filtre_statut=-1)
	{
		$this->livraisons = array();
		
		$sql = 'SELECT cd.fk_product, SUM(ld.qty)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'livraisondet as ld';
		$sql.= ', '.MAIN_DB_PREFIX.'livraison as l';
		$sql.= ', '.MAIN_DB_PREFIX.'commande as c';
		$sql.= ', '.MAIN_DB_PREFIX.'commandedet as cd';
		$sql.= ' WHERE ld.fk_livraison = l.rowid';
		$sql.= ' AND ld.fk_commande_ligne = cd .rowid';
		$sql.= ' AND cd.fk_commande = c.rowid';
		$sql.= ' AND cd.fk_commande =' .$this->id;
		if ($filtre_statut >= 0) $sql.=' AND l.fk_statut = '.$filtre_statut;
		$sql.= ' GROUP BY cd.fk_product ';

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $this->db->fetch_row($resql);
				$this->livraisons[$row[0]] = $row[1];
				$i++;
			}
			$this->db->free();
		}

		return 0;
	}

}



/**
 *  \class      LivraisonLigne
 *  \brief      Classe de gestion des lignes de bons de livraison
 */
class LivraisonLigne
{
	var $db;

	// From llx_expeditiondet
	var $qty;
	var $qty_asked;
	var $qty_shipped;
	var $price;
	var $fk_product;
	var $commande_ligne_id;
	var $label;       // Label produit
	var $description;  // Description produit
	var $ref;

	function LivraisonLigne($DB)
	{
		$this->db=$DB;
	}

}

?>
