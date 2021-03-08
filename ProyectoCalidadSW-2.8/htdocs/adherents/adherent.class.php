<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/adherents/adherent.class.php
 *	\ingroup    member
 *	\brief      Fichier de la classe permettant la gestion d'un adherent
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/cotisation.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");


/**
 *      \class      Adherent
 *		\brief      Classe permettant la gestion d'un adherent
 */
class Adherent extends CommonObject
{
	var $db;
	var $error;
	var $errors=array();
	var $element='member';
	var $table_element='adherent';

	var $id;
	var $ref;
	var $prenom;
	var $nom;
	var $fullname;
	var $login;
	var $pass;
	var $societe;
	var $adresse;
	var $cp;
	var $ville;
	var $pays_id;
	var $pays_code;
	var $pays;

	var $email;
	var $phone;
	var $phone_perso;
	var $phone_mobile;

	var $morphy;
	var $public;
	var $note;				// Note
	var $statut;			// -1:brouillon, 0:resilie, >=1:valide,paye
	var $photo;

	var $datec;
	var $datem;
	var $datefin;
	var $datevalid;
	var $naiss;

	var $typeid;			// Id type adherent
	var $type;				// Libelle type adherent
	var $need_subscription;

	var $user_id;
	var $user_login;

	var $fk_soc;

	// Fields loaded by fetch_subscriptions()
	var $first_subscription_date;
	var $first_subscription_amount;
	var $last_subscription_date;
	var $last_subscription_amount;
	var $subscriptions=array();

	//  var $public;
	var $array_options;

	var $oldcopy;		// To contains a clone of this when we need to save old properties of object


	/**
	 *	\brief Adherent
	 *	\param DB		base de donnees
	 */
	function Adherent($DB)
	{
		$this->db = $DB ;
		$this->statut = -1;
		// l'adherent n'est pas public par defaut
		$this->public = 0;
		// les champs optionnels sont vides
		$this->array_options=array();
	}


	/**
		\brief	    Fonction envoyant un email a l'adherent avec le texte fourni en parametre.
		\param	    text				contenu du message
		\param	    subject				sujet du message
		\param 		filename_list       tableau de fichiers attaches
		\param 		mimetype_list       tableau des types des fichiers attaches
		\param 		mimefilename_list   tableau des noms des fichiers attaches
		\param 		addr_cc             email cc
		\param 		addr_bcc            email bcc
		\param 		deliveryreceipt		demande accuse reception
		\param		msgishtml			1=String IS already html, 0=String IS NOT html, -1=Unknown need autodetection
		\return		int					<0 si ko, >0 si ok
		\remarks		La particularite de cette fonction est de remplacer certains champs
		\remarks		par leur valeur pour l'adherent en l'occurrence :
		\remarks		%PRENOM% : est remplace par le prenom
		\remarks		%NOM% : est remplace par nom
		\remarks		%INFOS% : l'ensemble des attributs de cet adherent
		\remarks		%SERVEUR% : URL du serveur web
		\remarks		etc..
	*/
	function send_an_email($text,$subject,
	$filename_list=array(),$mimetype_list=array(),$mimefilename_list=array(),
	$addr_cc="",$addr_bcc="",$deliveryreceipt=0,$msgishtml=-1, $errors_to='')
	{
		global $conf,$langs;

		// Detect if message is HTML
		if ($msgishtml == -1)
		{
			$msgishtml = 0;
			if (dol_textishtml($text,1)) $msgishtml = 1;
		}

		$infos='';
		$infos.= $langs->trans("Lastname").": $this->nom\n";
		$infos.= $langs->trans("Firstname").": $this->prenom\n";
		$infos.= $langs->trans("Company").": $this->societe\n";
		$infos.= $langs->trans("Address").": $this->adresse\n";
		$infos.= $langs->trans("Zip").": $this->cp\n";
		$infos.= $langs->trans("Town").": $this->ville\n";
		$infos.= $langs->trans("Country").": $this->pays\n";
		$infos.= $langs->trans("EMail").": $this->email\n";
		$infos.= $langs->trans("Login").": $this->login\n";
		$infos.= $langs->trans("Password").": $this->pass\n";
		$infos.= $langs->trans("Birthday").": $this->naiss\n";
		$infos.= $langs->trans("Photo").": $this->photo\n";
		$infos.= $langs->trans("Public").": ".yn($this->public)."\n";
		if ($msgishtml) $infos = dol_htmlentitiesbr($infos);

		// Substitutions
		$patterns = array (
		       '/%PRENOM%/',
		       '/%NOM%/',
		       '/%INFOS%/',
		       '/%DOL_MAIN_URL_ROOT%/',
		       '/%SOCIETE%/',
		       '/%ADRESSE%/',
		       '/%CP%/',
		       '/%VILLE%/',
		       '/%PAYS%/',
		       '/%EMAIL%/',
		       '/%NAISS%/',
		       '/%PHOTO%/',
		       '/%LOGIN%/',
		       '/%PASSWORD%/'
		       );
		       $replace = array (
		       $this->prenom,
		       $this->nom,
		       $infos,
		       DOL_MAIN_URL_ROOT,
		       $this->societe,
		       $this->adresse,
		       $this->cp,
		       $this->ville,
		       $this->pays,
		       $this->email,
		       $this->naiss,
		       $this->photo,
		       $this->login,
		       $this->pass
		       );
		       $texttosend = preg_replace ($patterns, $replace, $text);
		       $subjectosend = preg_replace ($patterns, $replace, $subject);

		       // Envoi mail confirmation
		       $from=$conf->email_from;
		       if ($conf->global->ADHERENT_MAIL_FROM) $from=$conf->global->ADHERENT_MAIL_FROM;

		       include_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");
		       $mailfile = new CMailFile($subjectosend,$this->email,$from,$texttosend,
									$filename_list,$mimetype_list,$mimefilename_list,
									$addr_cc, $addr_bcc, $deliveryreceipt, $msgishtml);
									if ($mailfile->sendfile())
									{
										return 1;
									}
									else
									{
										$this->error=$langs->trans("ErrorFailedToSendMail",$from,$this->email).'. '.$mailfile->error;
										return -1;
									}

	}


	/**
	 *	\brief	imprime une liste d'erreur.
	 */
	function print_error_list()
	{
		$num = sizeof($this->error);
		for ($i = 0 ; $i < $num ; $i++)
		{
			print "<li>" . $this->error[$i];
		}
	}


	/**
	 *	\brief      Renvoie le libelle traduit de la nature d'un adherent (physique ou morale)
	 *	\param	    morphy		Nature physique ou morale de l'adherent
	 */
	function getmorphylib($morphy='')
	{
		global $langs;
		if (! $morphy) { $morphy=$this->morphy; }
		if ($morphy == 'phy') { return $langs->trans("Physical"); }
		if ($morphy == 'mor') { return $langs->trans("Moral"); }
		return $morphy;
	}

	/**
	 *	\brief  	Fonction qui cree l'adherent
	 *	\param      user        	Objet user qui demande la creation
	 *	\param      notrigger		1 ne declenche pas les triggers, 0 sinon
	 *	\return		int				<0 si ko, >0 si ok
	 */
	function create($user,$notrigger=0)
	{
		global $conf,$langs;

		// Check parameters
		if (! empty($conf->global->ADHERENT_MAIL_REQUIRED) && ! isValidEMail($this->email))
		{
			$langs->load("errors");
			$this->error = $langs->trans("ErrorBadEMail",$this->email);
			return -1;
		}
		if (! $this->datec) $this->datec=gmmktime();
		if (empty($this->login))
		{
			$this->error = $langs->trans("ErrorWrongValueForParameterX","Login");
			return -1;
		}

		$this->db->begin();

		// Insert member
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."adherent";
		$sql.= " (datec,login,fk_user_author,fk_user_mod,fk_user_valid,morphy,fk_adherent_type,entity)";
		$sql.= " VALUES (";
		$sql.= " '".$this->db->idate($this->datec)."'";
		$sql.= ", '".$this->login."'";
		$sql.= ", ".($user->id>0?$user->id:"null");	// Can be null because member can be create by a guest or a script
		$sql.= ", null, null, '".$this->morphy."'";
		$sql.= ", '".$this->typeid."'";
		$sql.= ", ".$conf->entity;
		$sql.= ")";

		dol_syslog("Adherent::create sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."adherent");
			if ($id > 0)
			{
				$this->id=$id;

				// Update minor fields
				$result=$this->update($user,1,1); // nosync is 1 to avoid update data of user
				if ($result < 0)
				{
					$this->db->rollback();
					return -1;
				}

				// Add link to user
				if ($this->user_id)
				{
					// Add link to user
					$sql = "UPDATE ".MAIN_DB_PREFIX."user SET";
					$sql.= " fk_member = '".$this->id."'";
					$sql.= " WHERE rowid = ".$this->user_id;
					dol_syslog("Adherent::create sql=".$sql);
					$resql = $this->db->query($sql);
					if (! $resql)
					{
						$this->error='Failed to update user to make link with member';
						$this->db->rollback();
						return -4;
					}
				}

				$this->use_webcal=($conf->global->PHPWEBCALENDAR_MEMBERSTATUS=='always'?1:0);

				if (! $notrigger)
				{
					// Appel des triggers
					include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('MEMBER_CREATE',$this,$user,$langs,$conf);
					if ($result < 0) { $error++; $this->errors=$interface->errors; }
					// Fin appel triggers
				}

				if (sizeof($this->errors))
				{
					dol_syslog("Adherent::create ".join(',',$this->errors), LOG_ERR);
					$this->db->rollback();
					return -3;
				}
				else
				{
					$this->db->commit();
					return $this->id;
				}
			}
			else
			{
				$this->error='Failed to get last insert id';
				dol_syslog("Adherent::create ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("Adherent::create ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	\brief 		Update a member in database (standard information and password)
	 *	\param		user			User making update
	 *	\param		notrigger		1=disable trigger UPDATE (when called by create)
	 *	\param		nosyncuser		0=Synchronize linked user (standard info), 1=Do not synchronize linked user
	 *	\param		nosyncuserpass	0=Synchronize linked user (password), 1=Do not synchronize linked user
	 * 	\return		int				<0 si KO, >0 si OK
	 */
	function update($user,$notrigger=0,$nosyncuser=0,$nosyncuserpass=0)
	{
		global $conf, $langs;

		$nbrowsaffected=0;
		$error=0;

		dol_syslog("Adherent::update notrigger=".$notrigger.", nosyncuser=".$nosyncuser.", nosyncuserpass=".$nosyncuserpass.", email=".$this->email);

		// Check parameters
		if (! empty($conf->global->ADHERENT_MAIL_REQUIRED) && ! isValidEMail($this->email))
		{
			$langs->load("errors");
			$this->error = $langs->trans("ErrorBadEMail",$this->email);
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
		$sql.= " prenom = ".($this->prenom?"'".addslashes($this->prenom)."'":"null");
		$sql.= ", nom="     .($this->nom?"'".addslashes($this->nom)."'":"null");
		$sql.= ", login="   .($this->login?"'".addslashes($this->login)."'":"null");
		$sql.= ", societe=" .($this->societe?"'".addslashes($this->societe)."'":"null");
		$sql.= ", fk_soc="  .($this->fk_soc > 0?"'".$this->fk_soc."'":"null");
		$sql.= ", adresse=" .($this->adresse?"'".addslashes($this->adresse)."'":"null");
		$sql.= ", cp="      .($this->cp?"'".addslashes($this->cp)."'":"null");
		$sql.= ", ville="   .($this->ville?"'".addslashes($this->ville)."'":"null");
		$sql.= ", pays="    ."'".$this->pays_id."'";
		$sql.= ", email="   ."'".$this->email."'";
		$sql.= ", phone="   .($this->phone?"'".addslashes($this->phone)."'":"null");
		$sql.= ", phone_perso="  .($this->phone_perso?"'".addslashes($this->phone_perso)."'":"null");
		$sql.= ", phone_mobile=" .($this->phone_mobile?"'".addslashes($this->phone_mobile)."'":"null");
		$sql.= ", note="    .($this->note?"'".addslashes($this->note)."'":"null");
		$sql.= ", photo="   .($this->photo?"'".$this->photo."'":"null");
		$sql.= ", public="  ."'".$this->public."'";
		$sql.= ", statut="  .$this->statut;
		$sql.= ", fk_adherent_type=".$this->typeid;
		$sql.= ", morphy="  ."'".$this->morphy."'";
		$sql.= ", naiss="   .($this->naiss?"'".$this->db->idate($this->naiss)."'":"null");
		if ($this->datefin)   $sql.= ", datefin='".$this->db->idate($this->datefin)."'";		// Ne doit etre modifie que par effacement cotisation
		if ($this->datevalid) $sql.= ", datevalid='".$this->db->idate($this->datevalid)."'";	// Ne doit etre modifie que par validation adherent
		$sql.= ", fk_user_mod=".($user->id>0?$user->id:'null');	// Can be null because member can be create by a guest
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog("Adherent::update update member sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$nbrowsaffected+=$this->db->affected_rows($resql);

			if (sizeof($this->array_options) > 0)
			{
				$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."adherent_options WHERE fk_member = ".$this->id;
				dol_syslog("Adherent::update sql=".$sql_del);
				$this->db->query($sql_del);

				$sql = "INSERT INTO ".MAIN_DB_PREFIX."adherent_options (fk_member";
				foreach($this->array_options as $key => $value)
				{
					// Add field of attribut
					$sql.=",".substr($key,8);	// Remove 'options_' prefix
				}
				$sql .= ") VALUES (".$this->id;
				foreach($this->array_options as $key => $value)
				{
					// Add field o fattribut
					if ($this->array_options[$key] != '')
					{
						$sql.=",'".$this->array_options[$key]."'";
					}
					else
					{
						$sql.=",null";
					}
				}
				$sql.=")";

				dol_syslog("Adherent::update update option sql=".$sql);
				$resql = $this->db->query($sql);
				if ($resql)
				{
					$nbrowsaffected+=1;
				}
				else
				{
					$this->error=$this->db->error();
					dol_syslog("Adherent::update ".$this->error,LOG_ERR);
					$this->db->rollback();
					return -2;
				}
			}

			// Update password
			if ($this->pass)
			{
				dol_syslog("Adherent::update update password");
				if ($this->pass != $this->pass_indatabase && $this->pass != $this->pass_indatabase_crypted)
				{
					// Si mot de passe saisi et different de celui en base
					$result=$this->setPassword($user,$this->pass,0,$notrigger,$nosyncuserpass);
					if (! $nbrowsaffected) $nbrowsaffected++;
				}
			}

			// Remove link to user
			dol_syslog("Adherent::update update link to user");
			$sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = NULL WHERE fk_member = ".$this->id;
			dol_syslog("Adherent::update sql=".$sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -5; }
			// If there is a user linked to this member
			if ($this->user_id > 0)
			{
				$sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = ".$this->id." WHERE rowid = ".$this->user_id;
				dol_syslog("Adherent::update sql=".$sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -5; }
			}

			if ($nbrowsaffected)	// If something has change in data
			{
				if ($this->user_id > 0 && ! $nosyncuser)
				{
					require_once(DOL_DOCUMENT_ROOT."/user.class.php");

					dol_syslog("Adherent::update update linked user");

					// This member is linked with a user, so we also update users informations
					// if this is an update.
					$luser=new User($this->db);
					$luser->id=$this->user_id;
					$result=$luser->fetch();

					if ($result >= 0)
					{
						$luser->prenom=$this->prenom;
						$luser->nom=$this->nom;
						$luser->login=$this->user_login;
						$luser->pass=$this->pass;
						$luser->societe_id=$this->societe;

						$luser->email=$this->email;
						$luser->office_phone=$this->phone;
						$luser->user_mobile=$this->phone_mobile;

						$luser->note=$this->note;

						$luser->fk_member=$this->id;

						$result=$luser->update($user,0,1,1);	// Use nosync to 1 to avoid cyclic updates
						if ($result < 0)
						{
							$this->error=$luser->error;
							dol_syslog("Adherent::update ".$this->error,LOG_ERR);
							$error++;
						}
					}
					else
					{
						$this->error=$luser->error;
						$error++;
					}
				}

				$this->fullname=trim($this->nom.' '.$this->prenom);

				if (! $error && ! $notrigger)
				{
					$this->use_webcal=($conf->global->PHPWEBCALENDAR_MEMBERSTATUS=='always'?1:0);

					// Appel des triggers
					include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('MEMBER_MODIFY',$this,$user,$langs,$conf);
					if ($result < 0) { $error++; $this->errors=$interface->errors; }
					// Fin appel triggers
				}
			}

			if (! $error)
			{
				$this->db->commit();
			}
			else
			{
				$this->db->rollback();
			}

			return $nbrowsaffected;
		}
		else
		{
			$this->db->rollback();

			$this->error=$this->db->lasterror();
			dol_syslog("Adherent::update ".$this->error,LOG_ERR);
			return -1;
		}
	}


	/**
	 *	\brief 		Update denormalized last subscription date.
	 * 				This function is called when we delete a subscription for example.
	 *	\param		user			Utilisateur qui realise la mise a jour
	 *	\return		int				<0 if KO, >0 if OK
	 */
	function update_end_date($user)
	{
		global $conf, $langs;

		$error=0;

		$this->db->begin();

		// Search for last subscription id and end date
		$sql = "SELECT rowid, datec as dateop, dateadh as datedeb, datef as datefin";
		$sql.= " FROM ".MAIN_DB_PREFIX."cotisation";
		$sql.= " WHERE fk_adherent='".$this->id."'";
		$sql.= " ORDER by dateadh DESC";	// Sort by start subscription date

		dol_syslog("Adherent::update_end_date sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj=$this->db->fetch_object($resql);
			$dateop=$this->db->jdate($obj->dateop);
			$datedeb=$this->db->jdate($obj->datedeb);
			$datefin=$this->db->jdate($obj->datefin);

			$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
			$sql.= " datefin=".($datefin != '' ? "'".$this->db->idate($datefin)."'" : "null");
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog("Adherent::update_end_date sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->last_subscription_date=$dateop;
				$this->last_subscription_date_start=$datedeb;
				$this->last_subscription_date_end=$datefin;
				$this->datefin=$datefin;
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Adherent::update_end_date ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}

	}

	/**
	 \brief 		Fonction qui supprime l'adherent et les donnees associees
	 \param		rowid		Id de l'adherent a effacer
	 \return		int			<0 si KO, 0=rien a effacer, >0 si OK
	 */
	function delete($rowid)
	{
		global $conf, $langs, $user;
		$result = 0;

		$this->db->begin();

		// Suppression options
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent_options WHERE fk_member = ".$rowid;

		dol_syslog("Adherent::delete sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."cotisation WHERE fk_adherent = ".$rowid;
			dol_syslog("Adherent::delete sql=".$sql);
			$resql=$this->db->query( $sql);
			if ($resql)
			{
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent WHERE rowid = ".$rowid;
				dol_syslog("Adherent::delete sql=".$sql);
				$resql=$this->db->query($sql);
				if ($resql)
				{
					if ($this->db->affected_rows($resql))
					{
						$this->use_webcal=($conf->global->PHPWEBCALENDAR_MEMBERSTATUS=='always'?1:0);

						// Appel des triggers
						include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
						$interface=new Interfaces($this->db);
						$result=$interface->run_triggers('MEMBER_DELETE',$this,$user,$langs,$conf);
						if ($result < 0) { $error++; $this->errors=$interface->errors; }
						// Fin appel triggers

						$this->db->commit();
						return 1;
					}
					else
					{
						// Rien a effacer
						$this->db->rollback();
						return 0;
					}
				}
				else
				{
					$this->error=$this->db->error();
					$this->db->rollback();
					return -3;
				}
			}
			else
			{
				$this->error=$this->db->error();
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}

		return $result;

	}


	/**
	 *    \brief     Change password of a user
	 *    \param     user             Object user de l'utilisateur qui fait la modification
	 *    \param     password         Nouveau mot de passe (e generer si non communique)
	 *    \param     isencrypted      0 ou 1 si il faut crypter le mot de passe en base (0 par defaut)
	 *	  \param	 notrigger		  1=Ne declenche pas les triggers
	 *    \param	 nosyncuser		  Do not synchronize linked user
	 *    \return    string           If OK return clear password, 0 if no change, < 0 if error
	 */
	function setPassword($user, $password='', $isencrypted=0, $notrigger=0, $nosyncuser=0)
	{
		global $conf, $langs;

		$error=0;

		dol_syslog("Adherent::Password user=".$user->id." password=".preg_replace('/./i','*',$password)." isencrypted=".$isencrypted);

		// Si nouveau mot de passe non communique, on genere par module
		if (! $password)
		{
			// TODO Mettre appel au module de generation de mot de passe
			$password=creer_pass_aleatoire_1('');
			//$password=creer_pass_aleatoire_2('');
		}

		// Cryptage mot de passe
		if ($isencrypted)
		{
			// Crypte avec systeme encodage par defaut du PHP
			//$sqlpass = crypt($password, makesalt());
			$password_indatabase = md5($password);
		}
		else
		{
			$password_indatabase = $password;
		}

		// Mise a jour
		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET pass = '".addslashes($password_indatabase)."'";
		$sql.= " WHERE rowid = ".$this->id;

		//dol_syslog("Adherent::Password sql=hidden");
		dol_syslog("Adherent::Password sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$nbaffectedrows=$this->db->affected_rows($result);

			if ($nbaffectedrows)
			{
				$this->pass=$password;
				$this->pass_indatabase=$password_indatabase;

				if ($this->user_id && ! $nosyncuser)
				{
					require_once(DOL_DOCUMENT_ROOT."/user.class.php");

					// This member is linked with a user, so we also update users informations
					// if this is an update.
					$luser=new User($this->db);
					$luser->id=$this->user_id;
					$result=$luser->fetch();

					if ($result >= 0)
					{
						$result=$luser->setPassword($user,$this->pass,0,0,1);
						if ($result < 0)
						{
							$this->error=$luser->error;
							dol_syslog("Adherent::password ".$this->error,LOG_ERR);
							$error++;
						}
					}
					else
					{
						$this->error=$luser->error;
						$error++;
					}
				}

				if (! $error && ! $notrigger)
				{
					// Appel des triggers
					include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('MEMBER_NEW_PASSWORD',$this,$user,$langs,$conf);
					if ($result < 0) { $error++; $this->errors=$interface->errors; }
					// Fin appel triggers
				}

				return $this->pass;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *    \brief     Set link to a user
	 *    \param     userid           	Id of user to link to
	 *    \return    int				1=OK, -1=KO
	 */
	function setUserId($userid)
	{
		global $conf, $langs;

		$this->db->begin();

		// If user is linked to this member, remove old link to this member
		$sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = NULL WHERE fk_member = ".$this->id;
		dol_syslog("Adherent::setUserId sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -1; }

		// Set link to user
		if ($userid > 0)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = ".$this->id;
			$sql.= " WHERE rowid = ".$userid;
			dol_syslog("Adherent::setUserId sql=".$sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -2; }
		}

		$this->db->commit();

		return 1;
	}


	/**
	 *    \brief     Set link to a third party
	 *    \param     userid           	Id of user to link to
	 *    \return    int				1=OK, -1=KO
	 */
	function setThirdPartyId($thirdpartyid)
	{
		global $conf, $langs;

		$this->db->begin();

		// Update link to third party
		if ($thirdpartyid > 0)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET fk_soc = null";
			$sql.= " WHERE fk_soc = '".$thirdpartyid."'";
			$sql.= " AND entity = ".$conf->entity;
			dol_syslog("Adherent::setThirdPartyId sql=".$sql);
			$resql = $this->db->query($sql);
		}

		// Update link to third party
		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET fk_soc = ".($thirdpartyid>0 ? $thirdpartyid : 'null');
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog("Adherent::setThirdPartyId sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("Adherent::setThirdPartyId ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *		\brief      Fonction qui recupere l'adherent depuis son login
	 *		\param	    login		login de l'adherent
	 */
	function fetch_login($login)
	{
		global $conf;

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."adherent";
		$sql.= " WHERE login='".$login."'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$this->db->query( $sql);

		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->fetch($obj->rowid);
			}
		}
		else
		{
			dol_print_error($this->db);
		}
	}


	/**
	 *	\brief 		Load member from database
	 *	\param      rowid       Id of object to load
	 * 	\param		ref			To load member from its ref
	 * 	\param		fk_soc		To load member from its link to third party
	 *	\return     int         >0 if OK, 0 if not found, <0 if KO
	 */
	function fetch($rowid,$ref='',$fk_soc='')
	{
		global $conf, $langs;

		$sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, d.fk_soc, d.statut, d.public, d.adresse, d.cp, d.ville, d.note,";
		$sql.= " d.email, d.phone, d.phone_perso, d.phone_mobile, d.login, d.pass,";
		$sql.= " d.photo, d.fk_adherent_type, d.morphy,";
		$sql.= " d.datec as datec,";
		$sql.= " d.tms as datem,";
		$sql.= " d.datefin as datefin,";
		$sql.= " d.naiss as datenaiss,";
		$sql.= " d.datevalid as datev,";
		$sql.= " d.pays,";
		$sql.= " p.rowid as pays_id, p.code as pays_code, p.libelle as pays_lib,";
		$sql.= " t.libelle as type, t.cotisation as cotisation,";
		$sql.= " u.rowid as user_id, u.login as user_login";
		$sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as t, ".MAIN_DB_PREFIX."adherent as d";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p ON d.pays = p.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON d.rowid = u.fk_member";
		$sql.= " WHERE d.fk_adherent_type = t.rowid";
		$sql.= " AND d.entity = ".$conf->entity;
		if ($ref) $sql.= " AND d.rowid='".$ref."'";
		elseif ($fk_soc) $sql.= " AND d.fk_soc='".$fk_soc."'";
		else $sql.= " AND d.rowid=".$rowid;
		dol_syslog("Adherent::fetch sql=".$sql);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->ref            = $obj->rowid;
				$this->id             = $obj->rowid;
				$this->prenom         = $obj->prenom;
				$this->nom            = $obj->nom;
				$this->fullname       = trim($obj->nom.' '.$obj->prenom);
				$this->login          = $obj->login;
				$this->pass           = $obj->pass;
				$this->societe        = $obj->societe;
				$this->fk_soc         = $obj->fk_soc;
				$this->adresse        = $obj->adresse;
				$this->cp             = $obj->cp;
				$this->ville          = $obj->ville;
				$this->pays_id        = $obj->pays_id;
				$this->pays_code      = $obj->pays_code;
				if ($langs->trans("Country".$obj->pays_code) != "Country".$obj->pays_code) $this->pays = $langs->trans("Country".$obj->pays_code);
				elseif ($obj->pays_lib) $this->pays=$obj->pays_lib;
				else $this->pays=$obj->pays;
				$this->phone          = $obj->phone;
				$this->phone_perso    = $obj->phone_perso;
				$this->phone_mobile   = $obj->phone_mobile;
				$this->email          = $obj->email;

				$this->photo          = $obj->photo;
				$this->statut         = $obj->statut;
				$this->public         = $obj->public;

				$this->datec          = $this->db->jdate($obj->datec);
				$this->datem          = $this->db->jdate($obj->datem);
				$this->datefin        = $this->db->jdate($obj->datefin);
				$this->datevalid      = $this->db->jdate($obj->datev);
				$this->naiss          = $this->db->jdate($obj->datenaiss);

				$this->note           = $obj->note;
				$this->morphy         = $obj->morphy;

				$this->typeid         = $obj->fk_adherent_type;
				$this->type           = $obj->type;
				$this->need_subscription = ($obj->cotisation=='yes'?1:0);

				$this->user_id        = $obj->user_id;
				$this->user_login     = $obj->user_login;

				// Charge autres proprietes
				$result=$this->fetch_subscriptions();

				return $result;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 *	\brief 		Fonction qui recupere pour un adherent les parametres
	 *				first_subscription_date
	 *				first_subscription_amount
	 *				last_subscription_date
	 *				last_subscription_amount
	 *	\return		int			<0 si KO, >0 si OK
	 */
	function fetch_subscriptions()
	{
		global $langs;

		$sql = "SELECT c.rowid, c.fk_adherent, c.cotisation, c.note, c.fk_bank,";
		$sql.= " c.tms as datem,";
		$sql.= " c.datec as datec,";
		$sql.= " c.dateadh as dateadh,";
		$sql.= " c.datef as datef";
		$sql.= " FROM ".MAIN_DB_PREFIX."cotisation as c";
		$sql.= " WHERE c.fk_adherent = ".$this->id;
		$sql.= " ORDER BY c.dateadh";
		dol_syslog("Adherent::fetch_subscriptions sql=".$sql);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->subscriptions=array();

			$i=0;
			while ($obj = $this->db->fetch_object($resql))
			{
				if ($i==0)
				{
					$this->first_subscription_date=$obj->dateadh;
					$this->first_subscription_amount=$obj->cotisation;
				}
				$this->last_subscription_date=$obj->dateadh;
				$this->last_subscription_amount=$obj->cotisation;

				$subscription=new Cotisation($this->db);
				$subscription->id=$obj->rowid;
				$subscription->fk_adherent=$obj->fk_adherent;
				$subscription->amount=$obj->cotisation;
				$subscription->note=$obj->note;
				$subscription->fk_bank=$obj->fk_bank;
				$subscription->datem=$this->db->jdate($obj->datem);
				$subscription->datec=$this->db->jdate($obj->datec);
				$subscription->dateadh=$this->db->jdate($obj->dateadh);
				$subscription->datef=$this->db->jdate($obj->datef);

				$this->subscriptions[]=$subscription;

				$i++;
			}
			return 1;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}
	}


	/**
	 *	\brief      Fonction qui recupere les donnees optionelles de l'adherent
	 *	\param	    rowid
	 */
	function fetch_optionals($rowid)
	{
		$options = new AdherentOptions($this->db);
		$optionsArray = $options->fetch_name_optionals_label();

		$tab=array();

		$sql = "SELECT rowid";

		foreach ($optionsArray as $name => $label)
		{
			$sql.= ", ".$name;
		}

		$sql.= " FROM ".MAIN_DB_PREFIX."adherent_options";
		$sql.= " WHERE fk_member=".$rowid;

		dol_syslog("Adherent::fetch_optionals sql=".$sql, LOG_DEBUG);
		$result=$this->db->query( $sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$tab = $this->db->fetch_array($result);

				foreach ($tab as $key => $value)
				{
					if ($key != 'rowid' && $key != 'tms' && $key != 'fk_member')
					{
						// we can add this attribute to adherent object
						$this->array_options["options_$key"]=$value;
					}
				}
			}
		}
		else
		{
			dol_print_error($this->db);
		}

	}


	/**
	 *	\brief      Fonction qui insere la cotisation dans la base de donnees
	 *				et eventuellement liens dans banques, mailman, etc...
	 *	\param	    date        	Date d'effet de la cotisation
	 *	\param	    montant     	Montant cotisation (accepte 0 pour les adherents non soumis e cotisation)
	 *	\param		account_id		Id compte bancaire
	 *	\param		operation		Type operation (si Id compte bancaire fourni)
	 *	\param		label			Label operation (si Id compte bancaire fourni)
	 *	\param		num_chq			Numero cheque (si Id compte bancaire fourni)
	 *	\param		emetteur_nom	Nom emetteur cheque
	 *	\param		emetteur_banque	Nom banque emetteur cheque
	 *	\param		datesubend		Date fin adhesion
	 *	\return     int         	rowid de l'entree ajoutee, <0 si erreur
	 */
	function cotisation($date, $montant, $accountid=0, $operation='', $label='', $num_chq='', $emetteur_nom='', $emetteur_banque='', $datesubend=0)
	{
		global $conf,$langs,$user;

		// Clean parameters
		if (! $montant) $montant=0;

		$this->db->begin();

		if ($datesubend)
		{
			$datefin=$datesubend;
		}
		else
		{
			// If no end date, end date = date + 1 year - 1 day
			$datefin = dol_time_plus_duree($date,1,'y');
			$datefin = dol_time_plus_duree($datefin,-1,'d');
		}

		// Create subscription
		$cotisation=new Cotisation($this->db);
		$cotisation->fk_adherent=$this->id;
		$cotisation->dateh=$date;		// Date of new subscription
		$cotisation->datef=$datefin;	// End data of new subscription
		$cotisation->amount=$montant;
		$cotisation->note=$label;

		$rowid=$cotisation->create($user);

		if ($rowid > 0)
		{
			// Update denormalized subscription end date (read database subscription to find values)
			// This will also update this->datefin
			$result=$this->update_end_date($user);
			if ($result > 0)
			{
				// Rajout du nouveau cotisant dans les listes qui vont bien
				if ($conf->global->ADHERENT_MAILMAN_LISTS_COTISANT && ! $adh->datefin)
				{
					$result=$adh->add_to_mailman($conf->global->ADHERENT_MAILMAN_LISTS_COTISANT);
				}

				// Insertion dans la gestion bancaire si configure pour
				if ($conf->global->ADHERENT_BANK_USE && $accountid)
				{
					$acct=new Account($this->db);
					$result=$acct->fetch($accountid);

					$dateop=time();

					$insertid=$acct->addline($dateop, $operation, $label, $montant, $num_chq, '', $user, $emetteur_nom, $emetteur_banque);
					if ($insertid > 0)
					{
						$inserturlid=$acct->add_url_line($insertid, $this->id, DOL_URL_ROOT.'/adherents/fiche.php?rowid=', $this->getFullname(), 'member');
						if ($inserturlid > 0)
						{
							// Met a jour la table cotisation
							$sql="UPDATE ".MAIN_DB_PREFIX."cotisation SET fk_bank=".$insertid." WHERE rowid=".$rowid;

							dol_syslog("Adherent::cotisation sql=".$sql);
							$resql = $this->db->query($sql);
							if (! $resql)
							{
								$this->error=$this->db->error();
								$this->db->rollback();
								return -5;
							}
						}
						else
						{
							$this->error=$acct->error();
							$this->db->rollback();
							return -4;
						}
					}
					else
					{
						$this->error=$acct->error;
						$this->db->rollback();
						return -3;
					}
				}

				// Change properties of object (used by triggers)
				$this->last_subscription_date=$dateop;
				$this->last_subscription_amount=$montant;
				$this->last_subscription_date_start=$date;
				$this->last_subscription_date_end=$datefin;
				$this->use_webcal=($conf->global->PHPWEBCALENDAR_MEMBERSTATUS=='always'?1:0);

				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('MEMBER_SUBSCRIPTION',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers

				$this->db->commit();
				return $rowid;
			}
			else
			{
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$cotisation->error;
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *		\brief 		Function that validate a member
	 *		\param		user		user adherent qui valide
	 *		\return		int			<0 if KO, 0 if nothing done, >0 if OK
	 */
	function validate($user)
	{
		global $langs,$conf;

		if ($this->statut == 1)
		{
			dol_syslog("Adherent::validate statut of member does not allow this", LOG_WARNING);
			return 0;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
		$sql.= " statut = 1";
		$sql.= ", datevalid = ".$this->db->idate(mktime());
		$sql.= ", fk_user_valid=".$user->id;
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog("Adherent::validate sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->statut=1;

			$this->use_webcal=($conf->global->PHPWEBCALENDAR_MEMBERSTATUS=='always'?1:0);

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('MEMBER_VALIDATE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *		\brief 		Fonction qui resilie un adherent
	 *		\param		user		user adherent qui resilie
	 *		\return		int			<0 si ko, >0 si ok
	 */
	function resiliate($user)
	{
		global $langs,$conf;

		if ($this->statut == 0)
		{
			dol_syslog("Adherent::resiliate statut of member does not allow this", LOG_WARNING);
			return 0;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
		$sql.= " statut = 0";
		$sql.= ", fk_user_valid=".$user->id;
		$sql.= " WHERE rowid = ".$this->id;

		$result = $this->db->query($sql);
		if ($result)
		{
			$this->statut=0;

			$this->use_webcal=($conf->global->PHPWEBCALENDAR_MEMBERSTATUS=='always'?1:0);

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('MEMBER_RESILIATE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 \brief 		Fonction qui ajoute l'adherent au abonnements automatiques
	 \param		adht
	 \remarks	mailing-list, spip, etc...
	 \return		int		<0 si KO, >=0 si OK
	 */
	function add_to_abo($adht)
	{
		$err=0;

		// mailman
		if (defined("ADHERENT_USE_MAILMAN") && ADHERENT_USE_MAILMAN == 1)
		{
			$result=$this->add_to_mailman();
			if ($result < 0)
			{
				$err+=1;
			}
		}

		// spip
		if (
		defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
		defined("ADHERENT_USE_SPIP_AUTO") && ADHERENT_USE_SPIP_AUTO ==1
		)
		{
			$result=$this->add_to_spip();
			if(!$result)
			{
				$err+=1;
			}
		}
		if ($err)
		{
			// error
			return -$err;
		}
		else
		{
			return 1;
		}
	}


	/**
	 \brief      fonction qui supprime l'adherent des abonnements automatiques
	 \param	    adht
	 \remarks	mailing-list, spip, etc...
	 */
	function del_to_abo($adht)
	{
		$err=0;
		// mailman
		if (defined("ADHERENT_USE_MAILMAN") && ADHERENT_USE_MAILMAN == 1)
		{
			if(!$this->del_to_mailman()){
				$err+=1;
			}
		}

		if (
		defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
		defined("ADHERENT_USE_SPIP_AUTO") && ADHERENT_USE_SPIP_AUTO ==1
		)
		{
			if(!$this->del_to_spip()){
				$err+=1;
			}
		}
		if ($err>0){
			// error
			return 0;
		}else{
			return 1;
		}
	}


	/**
	 \brief fonction qui donne les droits redacteurs dans spip
	 \return		int		=0 si KO, >0 si OK
	 */
	function add_to_spip()
	{
		require_once(DOL_DOCUMENT_ROOT.'/lib/security.lib.php');

		dol_syslog("Adherent::add_to_spip");

		if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
		defined('ADHERENT_SPIP_SERVEUR') && ADHERENT_SPIP_SERVEUR != '' &&
		defined('ADHERENT_SPIP_USER') && ADHERENT_SPIP_USER != '' &&
		defined('ADHERENT_SPIP_PASS') && ADHERENT_SPIP_PASS != '' &&
		defined('ADHERENT_SPIP_DB') && ADHERENT_SPIP_DB != ''
		){
			$mdpass=md5($this->pass);
			$htpass=crypt($this->pass,makesalt());
			$query = "INSERT INTO spip_auteurs (nom, email, login, pass, htpass, alea_futur, statut) VALUES(\"".$this->prenom." ".$this->nom."\",\"".$this->email."\",\"".$this->login."\",\"$mdpass\",\"$htpass\",FLOOR(32000*RAND()),\"1comite\")";
			//      $mydb=new Db('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB);
			$mydb=new DoliDb('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB,ADHERENT_SPIP_PORT);
			$result = $mydb->query($query);

			if ($result)
			{
				$mydb->close();
				return 1;
			}
			else
			{
				$this->error=$mydb->error();
				return 0;
			}
		}
	}

	/**
	 \brief fonction qui enleve les droits redacteurs dans spip
	 */
	function del_to_spip()
	{
		if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
		defined('ADHERENT_SPIP_SERVEUR') && ADHERENT_SPIP_SERVEUR != '' &&
		defined('ADHERENT_SPIP_USER') && ADHERENT_SPIP_USER != '' &&
		defined('ADHERENT_SPIP_PASS') && ADHERENT_SPIP_PASS != '' &&
		defined('ADHERENT_SPIP_DB') && ADHERENT_SPIP_DB != ''
		){
			$query = "DELETE FROM spip_auteurs WHERE login='".$this->login."'";
			$mydb=new DoliDb('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB,ADHERENT_SPIP_PORT);
			$result = $mydb->query($query);

			if ($result)
	  {
	  	$mydb->close();
	  	return 1;
	  }
	  else
	  {
	  	$this->error=$mydb->error();
	  	return 0;
	  }
		}
	}

	/**
	 \brief      Fonction qui dit si cet utilisateur est un redacteur existant dans spip
	 \return     int     1=existe, 0=n'existe pas, -1=erreur
	 */
	function is_in_spip()
	{
		if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
		defined('ADHERENT_SPIP_SERVEUR') && ADHERENT_SPIP_SERVEUR != '' &&
		defined('ADHERENT_SPIP_USER') && ADHERENT_SPIP_USER != '' &&
		defined('ADHERENT_SPIP_PASS') && ADHERENT_SPIP_PASS != '' &&
		defined('ADHERENT_SPIP_DB') && ADHERENT_SPIP_DB != '')
		{

			$query = "SELECT login FROM spip_auteurs WHERE login='".$this->login."'";
			$mydb=new DoliDb('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB,ADHERENT_SPIP_PORT);

			if ($mydb->ok) {

				$result = $mydb->query($query);

				if ($result)
				{
					if ($mydb->num_rows())
					{
						# nous avons au moins une reponse
						$mydb->close();
						return 1;
					}
					else
					{
						# nous n'avons pas de reponse => n'existe pas
						$mydb->close();
						return 0;
					}
				}
				else
				{
					# error
					$this->error=$mydb->error();
					return -1;
				}
			} else {
				$this->error="Echec de connexion avec les identifiants ".ADHERENT_SPIP_SERVEUR." ".ADHERENT_SPIP_USER." ".ADHERENT_SPIP_PASS." ".ADHERENT_SPIP_DB;
				return -1;
			}
		}
	}

	/**
	 \brief 		Fonction qui rajoute l'utilisateur dans mailman
	 \return		int		<0 si KO, >0 si OK
	 */
	function add_to_mailman($listes='')
	{
		global $conf,$langs;

		dol_syslog("Adherent::add_to_mailman");

		if (! function_exists("curl_init"))
		{
			$langs->load("errors");
			$this->error=$langs->trans("ErrorFunctionNotAvailableInPHP","curl_init");
			return -1;
		}

		if (defined("ADHERENT_MAILMAN_URL") && ADHERENT_MAILMAN_URL != '' && defined("ADHERENT_MAILMAN_LISTS") && ADHERENT_MAILMAN_LISTS != '')
		{
			if ($listes =='')
			{
				$lists=explode(',',ADHERENT_MAILMAN_LISTS);
			}
			else
			{
				$lists=explode(',',$listes);
			}
			foreach ($lists as $list)
			{
				// on remplace dans l'url le nom de la liste ainsi
				// que l'email et le mot de passe
				$patterns = array (
				'/%LISTE%/',
				'/%EMAIL%/',
				'/%PASSWORD%/',
				'/%MAILMAN_ADMINPW%/'
				);
				$replace = array (
				$list,
				$this->email,
				$this->pass,
				$conf->global->ADHERENT_MAILMAN_ADMINPW
				);
				$curl_url = preg_replace ($patterns, $replace, $conf->global->ADHERENT_MAILMAN_URL);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,"$curl_url");
				//curl_setopt($ch, CURLOPT_URL,"http://www.j1b.org/");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch, CURLOPT_FAILONERROR, 1);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 5);
				//curl_setopt($ch, CURLOPT_POST, 0);
				//curl_setopt($ch, CURLOPT_POSTFIELDS, "a=3&b=5");
				//--- Start buffering
				//ob_start();
				$result=curl_exec ($ch);
				dol_syslog($result);
				//--- End buffering and clean output
				//ob_end_clean();
				if (curl_error($ch) > 0)
				{
					// error
					return 0;
				}
				curl_close ($ch);

			}
			return 1;
		}
		else
		{
			$this->error="Constantes de connexion non definies";
			return -1;
		}
	}

	/**
		\brief 		Fonction qui desinscrit l'utilisateur de toutes les mailing list mailman
		\remarks	Utilise lors de la resiliation d'adhesion
		*/
	function del_to_mailman($listes='')
	{
		global $conf;

		if (defined("ADHERENT_MAILMAN_UNSUB_URL") && ADHERENT_MAILMAN_UNSUB_URL != '' && defined("ADHERENT_MAILMAN_LISTS") && ADHERENT_MAILMAN_LISTS != '')
		{
			if ($listes==''){
				$lists=explode(',',ADHERENT_MAILMAN_LISTS);
				if (defined("ADHERENT_MAILMAN_LISTS_COTISANT") && ADHERENT_MAILMAN_LISTS_COTISANT !=''){
					$lists=array_merge ($lists,explode(',',ADHERENT_MAILMAN_LISTS_COTISANT));
				}
			}else{
				$lists=explode(',',$listes);
			}
			foreach ($lists as $list)
			{
				// on remplace dans l'url le nom de la liste ainsi
				// que l'email et le mot de passe
				$patterns = array (
				'/%LISTE%/',
				'/%EMAIL%/',
				'/%PASSWORD%/',
				'/%MAILMAN_ADMINPW%/'
				);
				$replace = array (
				$list,
				$this->email,
				$this->pass,
				$conf->global->ADHERENT_MAILMAN_ADMINPW
				);
				$curl_url = preg_replace ($patterns, $replace, $conf->global->ADHERENT_MAILMAN_UNSUB_URL);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,"$curl_url");
				//curl_setopt($ch, CURLOPT_URL,"http://www.j1b.org/");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch, CURLOPT_FAILONERROR, 1);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 5);
				//curl_setopt($ch, CURLOPT_POST, 0);
				//curl_setopt($ch, CURLOPT_POSTFIELDS, "a=3&b=5");
				//--- Start buffering
				//ob_start();
				$result=curl_exec ($ch);
				dol_syslog($result);
				//--- End buffering and clean output
				//ob_end_clean();
				if (curl_error($ch) > 0)
				{
					// error
					return 0;
				}
				curl_close ($ch);

			}
			return 1;
		}
		else
		{
			$this->error="Constantes de connexion non definies";
			return 0;
		}
	}

	/**
	 *    \brief      Retourne le nom complet de l'adherent
	 *    \return     string      	Nom complet
	 */
	function getFullname()
	{
		if ($this->nom && $this->prenom) return $this->nom.' '.$this->prenom;
		if ($this->nom)    return $this->nom;
		if ($this->prenom) return $this->prenom;
		return '';
	}


	/**
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *		\param		maxlen			Longueur max libelle
	 *		\param		option			Page lien
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$maxlen=0,$option='card')
	{
		global $langs;

		$result='';

		if ($option == 'card')
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$this->id.'">';
			$lienfin='</a>';
		}
		if ($option == 'subscription')
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/adherents/card_subscriptions.php?rowid='.$this->id.'">';
			$lienfin='</a>';
		}

		$picto='user';
		$label=$langs->trans("ShowMember");

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$lien.($maxlen?dol_trunc($this->ref,$maxlen):$this->ref).$lienfin;
		return $result;
	}


	/**
	 *    	\brief      Retourne le libelle du statut d'un adherent (brouillon, valide, resilie)
	 *    	\param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    	\return     string		Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$this->need_subscription,$this->datefin,$mode);
	}

	/**
	 *    	\brief      Renvoi le libelle d'un statut donne
	 *    	\param      statut      			Id statut
	 *		\param		need_subscription		1 si type adherent avec cotisation, 0 sinon
	 *		\param		date_end_subscription	Date fin adhesion
	 *    	\param      mode        			0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    	\return     string      			Libelle
	 */
	function LibStatut($statut,$need_subscription,$date_end_subscription,$mode=0)
	{
		global $langs;
		$langs->load("members");
		if ($mode == 0)
		{
			if ($statut == -1) return $langs->trans("MemberStatusDraft");
			if ($statut >= 1)
			{
				if (! $date_end_subscription)            return $langs->trans("MemberStatusActive");
				elseif ($date_end_subscription < time()) return $langs->trans("MemberStatusActiveLate");
				else                                     return $langs->trans("MemberStatusPaid");
			}
			if ($statut == 0)  return $langs->trans("MemberStatusResiliated");
		}
		if ($mode == 1)
		{
			if ($statut == -1) return $langs->trans("MemberStatusDraftShort");
			if ($statut >= 1)
			{
				if (! $date_end_subscription)            return $langs->trans("MemberStatusActiveShort");
				elseif ($date_end_subscription < time()) return $langs->trans("MemberStatusActiveLateShort");
				else                                     return $langs->trans("MemberStatusPaidShort");
			}
			if ($statut == 0)  return $langs->trans("MemberStatusResiliatedShort");
		}
		if ($mode == 2)
		{
			if ($statut == -1) return img_picto($langs->trans('MemberStatusDraft'),'statut0').' '.$langs->trans("MemberStatusDraftShort");
			if ($statut >= 1)
			{
				if (! $date_end_subscription)            return img_picto($langs->trans('MemberStatusActive'),'statut1').' '.$langs->trans("MemberStatusActiveShort");
				elseif ($date_end_subscription < time()) return img_picto($langs->trans('MemberStatusActiveLate'),'statut3').' '.$langs->trans("MemberStatusActiveLateShort");
				else                                     return img_picto($langs->trans('MemberStatusPaid'),'statut4').' '.$langs->trans("MemberStatusPaidShort");
			}
			if ($statut == 0)  return img_picto($langs->trans('MemberStatusResiliated'),'statut5').' '.$langs->trans("MemberStatusResiliatedShort");
		}
		if ($mode == 3)
		{
			if ($statut == -1) return img_picto($langs->trans('MemberStatusDraft'),'statut0');
			if ($statut >= 1)
			{
				if (! $date_end_subscription)            return img_picto($langs->trans('MemberStatusActive'),'statut1');
				elseif ($date_end_subscription < time()) return img_picto($langs->trans('MemberStatusActiveLate'),'statut3');
				else                                     return img_picto($langs->trans('MemberStatusPaid'),'statut4');
			}
			if ($statut == 0)  return img_picto($langs->trans('MemberStatusResiliated'),'statut5');
		}
		if ($mode == 4)
		{
			if ($statut == -1) return img_picto($langs->trans('MemberStatusDraft'),'statut0').' '.$langs->trans("MemberStatusDraft");
			if ($statut >= 1)
			{
				if (! $date_end_subscription)            return img_picto($langs->trans('MemberStatusActive'),'statut1').' '.$langs->trans("MemberStatusActive");
				elseif ($date_end_subscription < time()) return img_picto($langs->trans('MemberStatusActiveLate'),'statut3').' '.$langs->trans("MemberStatusActiveLate");
				else                                     return img_picto($langs->trans('MemberStatusPaid'),'statut4').' '.$langs->trans("MemberStatusPaid");
			}
			if ($statut == 0)  return img_picto($langs->trans('MemberStatusResiliated'),'statut5').' '.$langs->trans("MemberStatusResiliated");
		}
		if ($mode == 5)
		{
			if ($statut == -1) return $langs->trans("MemberStatusDraft").' '.img_picto($langs->trans('MemberStatusDraft'),'statut0');
			if ($statut >= 1)
			{
				if (! $date_end_subscription)            return $langs->trans("MemberStatusActive").' '.img_picto($langs->trans('MemberStatusActive'),'statut1');
				elseif ($date_end_subscription < time()) return $langs->trans("MemberStatusActiveLate").' '.img_picto($langs->trans('MemberStatusActiveLate'),'statut3');
				else                                     return $langs->trans("MemberStatusPaid").' '.img_picto($langs->trans('MemberStatusPaid'),'statut4');
			}
			if ($statut == 0)  return $langs->trans("MemberStatusResiliated").' '.img_picto($langs->trans('MemberStatusResiliated'),'statut5');
		}
	}


	/**
	 *      \brief      Charge indicateurs this->nb de tableau de bord
	 *      \return     int         <0 si ko, >0 si ok
	 */
	function load_state_board()
	{
		global $conf;

		$this->nb=array();

		$sql = "SELECT count(a.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."adherent as a";
		$sql.= " WHERE a.statut > 0";
		$sql.= " AND a.entity = ".$conf->entity;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["members"]=$obj->nb;
			}
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}

	}

	/**
	 *      \brief      Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
	 *      \param      user        Objet user
	 *      \return     int         <0 si ko, >0 si ok
	 */
	function load_board($user)
	{
		global $conf;

		$now=gmmktime();

		if ($user->societe_id) return -1;   // protection pour eviter appel par utilisateur externe

		$this->nbtodo=$this->nbtodolate=0;

		$sql = "SELECT a.rowid, a.datefin";
		$sql.= " FROM ".MAIN_DB_PREFIX."adherent as a";
		$sql.= " WHERE a.statut = 1";
		$sql.= " AND a.entity = ".$conf->entity;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nbtodo++;
				if ($this->db->jdate($obj->datefin) < ($now - $conf->adherent->cotisation->warning_delay)) $this->nbtodolate++;
			}
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 *		\brief		Initialise le membre avec valeurs fictives aleatoire
	 */
	function initAsSpecimen()
	{
		global $user,$langs;

		// Initialise parametres
		$this->id=0;
		$this->specimen=1;
		$this->nom = 'DOLIBARR';
		$this->prenom = 'SPECIMEN';
		$this->fullname=trim($this->nom.' '.$this->prenom);
		$this->login='dolibspec';
		$this->pass='dolibspec';
		$this->societe = 'Societe ABC';
		$this->adresse = '61 jump street';
		$this->cp = '75000';
		$this->ville = 'Paris';
		$this->pays_id = 1;
		$this->pays_code = 'FR';
		$this->pays = 'France';
		$this->morphy = 1;
		$this->email = 'specimen@specimen.com';
		$this->phone        = '0999999999';
		$this->phone_perso  = '0999999998';
		$this->phone_mobile = '0999999997';
		$this->note='No comment';
		$this->naiss=time();
		$this->photo='';
		$this->public=1;
		$this->statut=1;

		$this->datefin=time();
		$this->datevalid=time();

		$this->typeid=1;				// Id type adherent
		$this->type='Type adherent';	// Libelle type adherent
		$this->need_subscription=0;

		$this->first_subscription_date=time();
		$this->first_subscription_amount=10;
		$this->last_subscription_date=time();
		$this->last_subscription_amount=10;
	}


	/**
	 *	\brief		Retourne chaine DN complete dans l'annuaire LDAP pour l'objet
	 *	\param		info		Info string loaded by _load_ldap_info
	 *	\param		mode		0=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
	 *							1=Return DN without key inside (ou=xxx,dc=aaa,dc=bbb)
	 *							2=Return key only (uid=qqq)
	 *	\return		string		DN
	 */
	function _load_ldap_dn($info,$mode=0)
	{
		global $conf;
		$dn='';
		if ($mode==0) $dn=$conf->global->LDAP_KEY_MEMBERS."=".$info[$conf->global->LDAP_KEY_MEMBERS].",".$conf->global->LDAP_MEMBER_DN;
		if ($mode==1) $dn=$conf->global->LDAP_MEMBER_DN;
		if ($mode==2) $dn=$conf->global->LDAP_KEY_MEMBERS."=".$info[$conf->global->LDAP_KEY_MEMBERS];
		return $dn;
	}


	/**
	 *	\brief		Initialise tableau info (tableau des attributs LDAP)
	 *	\return		array		Tableau info des attributs
	 */
	function _load_ldap_info()
	{
		global $conf,$langs;

		$info=array();

		// Object classes
		$info["objectclass"]=explode(',',$conf->global->LDAP_MEMBER_OBJECT_CLASS);

		// Member
		if ($this->fullname  && $conf->global->LDAP_MEMBER_FIELD_FULLNAME) $info[$conf->global->LDAP_MEMBER_FIELD_FULLNAME] = $this->fullname;
		if ($this->nom && $conf->global->LDAP_MEMBER_FIELD_NAME)         $info[$conf->global->LDAP_MEMBER_FIELD_NAME] = $this->nom;
		if ($this->prenom && $conf->global->LDAP_MEMBER_FIELD_FIRSTNAME) $info[$conf->global->LDAP_MEMBER_FIELD_FIRSTNAME] = $this->prenom;
		if ($this->login && $conf->global->LDAP_MEMBER_FIELD_LOGIN)      $info[$conf->global->LDAP_MEMBER_FIELD_LOGIN] = $this->login;
		if ($this->pass && $conf->global->LDAP_MEMBER_FIELD_PASSWORD)    $info[$conf->global->LDAP_MEMBER_FIELD_PASSWORD] = $this->pass;	// this->pass = mot de passe non crypte
		if ($this->poste && $conf->global->LDAP_MEMBER_FIELD_TITLE)      $info[$conf->global->LDAP_MEMBER_FIELD_TITLE] = $this->poste;
		if ($this->adresse && $conf->global->LDAP_MEMBER_FIELD_ADDRESS)  $info[$conf->global->LDAP_MEMBER_FIELD_ADDRESS] = $this->adresse;
		if ($this->cp && $conf->global->LDAP_MEMBER_FIELD_ZIP)           $info[$conf->global->LDAP_MEMBER_FIELD_ZIP] = $this->cp;
		if ($this->ville && $conf->global->LDAP_MEMBER_FIELD_TOWN)       $info[$conf->global->LDAP_MEMBER_FIELD_TOWN] = $this->ville;
		if ($this->pays_code && $conf->global->LDAP_MEMBER_FIELD_COUNTRY)     $info[$conf->global->LDAP_MEMBER_FIELD_COUNTRY] = $this->pays_code;
		if ($this->email && $conf->global->LDAP_MEMBER_FIELD_MAIL)       $info[$conf->global->LDAP_MEMBER_FIELD_MAIL] = $this->email;
		if ($this->phone && $conf->global->LDAP_MEMBER_FIELD_PHONE)      $info[$conf->global->LDAP_MEMBER_FIELD_PHONE] = $this->phone;
		if ($this->phone_perso && $conf->global->LDAP_MEMBER_FIELD_PHONE_PERSO) $info[$conf->global->LDAP_MEMBER_FIELD_PHONE_PERSO] = $this->phone_perso;
		if ($this->phone_mobile && $conf->global->LDAP_MEMBER_FIELD_MOBILE) $info[$conf->global->LDAP_MEMBER_FIELD_MOBILE] = $this->phone_mobile;
		if ($this->fax && $conf->global->LDAP_MEMBER_FIELD_FAX)	      $info[$conf->global->LDAP_MEMBER_FIELD_FAX] = $this->fax;
		if ($this->note && $conf->global->LDAP_MEMBER_FIELD_DESCRIPTION) $info[$conf->global->LDAP_MEMBER_FIELD_DESCRIPTION] = $this->note;
		if ($this->naiss && $conf->global->LDAP_MEMBER_FIELD_BIRTHDATE)  $info[$conf->global->LDAP_MEMBER_FIELD_BIRTHDATE] = dol_print_date($this->naiss,'dayhourldap');
		if (isset($this->statut) && $conf->global->LDAP_FIELD_MEMBER_STATUS)  $info[$conf->global->LDAP_FIELD_MEMBER_STATUS] = $this->statut;
		if ($this->datefin && $conf->global->LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION)  $info[$conf->global->LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION] = dol_print_date($this->datefin,'dayhourldap');

		// Subscriptions
		if ($this->first_subscription_date && $conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE)     $info[$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE]  = dol_print_date($this->first_subscription_date,'dayhourldap');
		if (isset($this->first_subscription_amount) && $conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT) $info[$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT] = $this->first_subscription_amount;
		if ($this->last_subscription_date && $conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE)       $info[$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE]   = dol_print_date($this->last_subscription_date,'dayhourldap');
		if (isset($this->last_subscription_amount) && $conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT)   $info[$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT] = $this->last_subscription_amount;

		return $info;
	}


	/**
	 *      \brief     Charge les informations d'ordre info dans l'objet adherent
	 *      \param     id       Id du membre a charger
	 */
	function info($id)
	{
		$sql = 'SELECT a.rowid, a.datec as datec,';
		$sql.= ' a.datevalid as datev,';
		$sql.= ' a.tms as datem,';
		$sql.= ' a.fk_user_author, a.fk_user_valid, a.fk_user_mod';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'adherent as a';
		$sql.= ' WHERE a.rowid = '.$id;

		dol_syslog("Adherent::info sql=".$sql, LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db, $obj->fk_user_author);
					$cuser->fetch();
					$this->user_creation   = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db, $obj->fk_user_valid);
					$vuser->fetch();
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_mod)
				{
					$muser = new User($this->db, $obj->fk_user_mod);
					$muser->fetch();
					$this->user_modification = $muser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_validation   = $this->db->jdate($obj->datev);
				$this->date_modification = $this->db->jdate($obj->datem);
			}

			$this->db->free($result);

		}
		else
		{
			dol_print_error($this->db);
		}
	}

}
?>
