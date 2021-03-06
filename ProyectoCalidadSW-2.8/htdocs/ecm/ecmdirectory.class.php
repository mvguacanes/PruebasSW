<?php
/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 *  \file       htdocs/ecm/ecm_directories.class.php
 *  \ingroup    ecm
 *  \brief      This file is an example for a class file
 *  \version    $Id$
 *  \author		Laurent Destailleur
 *  \remarks	Initialy built by build_class_from_table on 2008-02-24 19:24
 */

/**
 *  \class      EcmDirectory
 *  \brief      Class to manage ECM directories
 *  \remarks	Initialy built by build_class_from_table on 2008-02-24 19:24
 */
class EcmDirectory // extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='ecm_directories';			//!< Id that identify managed objects
	//var $table_element='ecm_directories';	//!< Name of table without prefix where object is stored

	var $id;

	var $label;
	var $fk_parent;
	var $description;
	var $cachenbofdoc;
	var $date_c;
	var $date_m;

	var $cats=array();
	var $motherof=array();


	/**
	 *      \brief      Constructor
	 *      \param      DB      Database handler
	 */
	function EcmDirectory($DB)
	{
		$this->db = $DB;
		return 1;
	}


	/**
	 *      \brief      Create in database
	 *      \param      user        User that create
	 *      \return     int         <0 si ko, >0 si ok
	 */
	function create($user)
	{
		global $conf, $langs;

		$now=time();

		// Clean parameters
		$this->label=dol_string_nospecial(trim($this->label));
		$this->fk_parent=trim($this->fk_parent);
		$this->description=trim($this->description);
		if (! $this->cachenbofdoc) $this->cachenbofdoc=0;
		$this->date_c=$now;
		$this->fk_user_c=$user->id;
		if ($this->fk_parent <= 0) $this->fk_parent=0;

		// Check if same directory does not exists with this name
		$relativepath=$this->label;
		if ($this->fk_parent)
		{
			$parent = new ECMDirectory($this->db);
			$parent->fetch($this->fk_parent);
			$relativepath=$parent->getRelativePath().$relativepath;
		}
		$relativepath=preg_replace('/([\/])+/i','/',$relativepath);	// Avoid duplicate / or \
		//print $relativepath.'<br>';

		$cat = new ECMDirectory($this->db);
		$cate_arbo = $cat->get_full_arbo(1);
		$pathfound=0;
		foreach ($cate_arbo as $key => $categ)
		{
			$path=preg_replace('/([\s-><\/])+/i','/',$categ['fulllabel']);
			//print $path.'<br>';
			if ($path == $relativepath)
			{
				$pathfound=1;
				break;
			}
		}

		if ($pathfound)
		{
			$this->error="ErrorDirAlreadyExists";
			dol_syslog("EcmDirectories::create ".$this->error, LOG_WARNING);
			return -1;
		}
		else
		{
			$this->db->begin();

			// Insert request
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."ecm_directories(";
			$sql.= "label,";
			$sql.= "entity,";
			$sql.= "fk_parent,";
			$sql.= "description,";
			$sql.= "cachenbofdoc,";
			$sql.= "date_c,";
			$sql.= "fk_user_c";
			$sql.= ") VALUES (";
			$sql.= " '".addslashes($this->label)."',";
			$sql.= " '".$conf->entity."',";
			$sql.= " '".$this->fk_parent."',";
			$sql.= " '".addslashes($this->description)."',";
			$sql.= " ".($this->cachenbofdoc).",";
			$sql.= " ".$this->db->idate($this->date_c).",";
			$sql.= " '".$this->fk_user_c."'";
			$sql.= ")";

			dol_syslog("EcmDirectories::create sql=".$sql, LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."ecm_directories");

				$dir=$conf->ecm->dir_output.'/'.$this->getRelativePath();
				$result=create_exdir($dir);

				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('MYECMDIR_CREATE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers

				if (! $error)
				{
					$this->db->commit();
					return $this->id;
				}
				else
				{
					$this->db->rollback();
					return -1;
				}
			}
			else
			{
				$this->error="Error ".$this->db->lasterror();
				dol_syslog("EcmDirectories::create ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		}
	}

	/**
	 *      \brief      Update database
	 *      \param      user        	User that modify
	 *      \param      notrigger	    0=no, 1=yes (no update trigger)
	 *      \return     int         	<0 if KO, >0 if OK
	 */
	function update($user=0, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		// Clean parameters
		$this->label=trim($this->label);
		$this->fk_parent=trim($this->fk_parent);
		$this->description=trim($this->description);

		// Check parameters
		// Put here code to add control on parameters values

		$this->db->begin();

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."ecm_directories SET";
		$sql.= " label='".addslashes($this->label)."',";
		$sql.= " fk_parent='".$this->fk_parent."',";
		$sql.= " description='".addslashes($this->description)."'";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog("EcmDirectories::update sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$error++;
			$this->error="Error ".$this->db->lasterror();
			dol_syslog("EcmDirectories::update ".$this->error, LOG_ERR);
		}

		if (! $error && ! $notrigger)
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('MYECMDIR_MODIFY',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *      \brief      Update database
	 * 		\sign		'+' or '-'
	 *      \return     int         	<0 if KO, >0 if OK
	 */
	function changeNbOfFiles($sign)
	{
		global $conf, $langs;

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."ecm_directories SET";
		$sql.= " cachenbofdoc = cachenbofdoc ".$sign." 1";
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog("EcmDirectories::changeNbOfFiles sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog("EcmDirectories::changeNbOfFiles ".$this->error, LOG_ERR);
			return -1;
		}

		return 1;
	}


	/**
	 *    \brief      Load object in memory from database
	 *    \param      id          id object
	 *    \return     int         <0 if KO, 0 if not found, >0 if OK
	 */
	function fetch($id)
	{
		$sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.label,";
		$sql.= " t.fk_parent,";
		$sql.= " t.description,";
		$sql.= " t.cachenbofdoc,";
		$sql.= " t.fk_user_c,";
		$sql.= " t.fk_user_m,";
		$sql.= " ".$this->db->pdate('t.date_c')." as date_c,";
		$sql.= " ".$this->db->pdate('t.date_m')." as date_m";
		$sql.= " FROM ".MAIN_DB_PREFIX."ecm_directories as t";
		$sql.= " WHERE t.rowid = ".$id;

		dol_syslog("EcmDirectories::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj)
			{
				$this->id    = $obj->rowid;
				$this->ref   = $obj->rowid;

				$this->label = $obj->label;
				$this->fk_parent = $obj->fk_parent;
				$this->description = $obj->description;
				$this->cachenbofdoc = $obj->cachenbofdoc;
				$this->fk_user_m = $obj->fk_user_m;
				$this->fk_user_c = $obj->fk_user_c;
				$this->date_c = $obj->date_c;
				$this->date_m = $obj->date_m;
			}

			$this->db->free($resql);

			return $obj?1:0;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog("EcmDirectories::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 * 		\brief      Delete object on database and on disk
	 *		\param      user        User that delete
	 *		\return		int			<0 if KO, >0 if OK
	 */
	function delete($user)
	{
		global $conf, $langs;

		$relativepath=$this->getRelativePath(1);	// Ex: dir1/dir2/dir3

		dol_syslog("EcmDirectories::delete remove directory ".$relativepath);

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."ecm_directories";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog("EcmDirectories::delete sql=".$sql);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->db->rollback();
			$this->error="Error ".$this->db->lasterror();
			dol_syslog("EcmDirectories::delete ".$this->error, LOG_ERR);
			return -2;
		}

		$file = $conf->ecm->dir_output . "/" . $relativepath;
		$result=@dol_delete_dir($file);

		if ($result || ! @is_dir(dol_osencode($file)))
		{
			$this->db->commit();
		}
		else
		{
			$this->error='ErrorFailedToDeleteDir';
			dol_syslog("EcmDirectories::delete ".$this->error, LOG_ERR);
			$this->db->rollback();
			$error++;
		}

		if (! $error)
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('MYECMDIR_DELETE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers
		}

		if (! $error) return 1;
		else return -1;
	}


	/**
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->label='MyDirectory';
		$this->fk_parent='0';
		$this->description='This is a directory';
	}


	/**
	 *  \brief      	Return directory name you can click (and picto)
	 *  \param			withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *  \param			option			Sur quoi pointe le lien
	 *  \return			string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='',$max=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/ecm/docmine.php?section='.$this->id.'">';
		if ($option == 'index') $lien = '<a href="'.DOL_URL_ROOT.'/ecm/index.php?section='.$this->id.'&amp;sectionexpand=true">';
		if ($option == 'indexexpanded') $lien = '<a href="'.DOL_URL_ROOT.'/ecm/index.php?section='.$this->id.'&amp;sectionexpand=false">';
		if ($option == 'indexnotexpanded') $lien = '<a href="'.DOL_URL_ROOT.'/ecm/index.php?section='.$this->id.'&amp;sectionexpand=true">';
		$lienfin='</a>';

		//$picto=DOL_URL_ROOT.'/theme/common/treemenu/folder.gif';
		$picto='dir';

		$newref=str_replace('_',' ',$this->ref);
		$newlabel=$langs->trans("ShowECMSection").': '.$newref;

		if ($withpicto) $result.=($lien.img_object($newlabel,$picto,'',1).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.($max?dol_trunc($newref,$max,'middle'):$newref).$lienfin;
		return $result;
	}

	/**
	 *  \brief     	Return relative path of a directory on disk
	 * 	\param		force		Force reload of full arbo even if already loaded
	 *	\return		string		Relative physical path
	 */
	function getRelativePath($force=0)
	{
		$this->get_full_arbo($force);

		$ret='';
		$idtosearch=$this->id;
		$i=0;
		do {
			// Get index cursor in this->cats for id_mere
			$cursorindex=-1;
			foreach ($this->cats as $key => $val)
			{
				if ($this->cats[$key]['id'] == $idtosearch)
				{
					$cursorindex=$key;
					break;
				}
			}
			//print "c=".$idtosearch."-".$cursorindex;

			if ($cursorindex >= 0)
			{
				// Path is label sanitized (no space and no special char) and concatenated
				$ret=dol_sanitizeFileName($this->cats[$cursorindex]['label']).'/'.$ret;

				$idtosearch=$this->cats[$cursorindex]['id_mere'];
				$i++;
			}
		}
		while ($cursorindex >= 0 && ! empty($idtosearch) && $i < 100);	// i avoid infinite loop

		return $ret;
	}

	/**
	 * 	\brief		Load this->motherof that is array(id_son=>id_parent, ...)
	 *	\return		int		<0 if KO, >0 if OK
	 */
	function load_motherof()
	{
		global $conf;

		$this->motherof=array();

		// Charge tableau des meres
		$sql = "SELECT fk_parent as id_parent, rowid as id_son";
		$sql.= " FROM ".MAIN_DB_PREFIX."ecm_directories";
		$sql.= " WHERE fk_parent != 0";
		$sql.= " AND entity = ".$conf->entity;

		dol_syslog("EcmDirectory::get_full_arbo sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj= $this->db->fetch_object($resql))
			{
				$this->motherof[$obj->id_son]=$obj->id_parent;
			}
			return 1;
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}


	/**
	 * 	\brief		Reconstruit l'arborescence des categories sous la forme d'un tableau
	 *				Renvoi un tableau de tableau('id','id_mere',...) trie selon
	 *				arbre et avec:
	 *				id = id de la categorie
	 *				id_mere = id de la categorie mere
	 *				id_children = tableau des id enfant
	 *				label = nom de la categorie
	 *				cachenbofdoc = nb of documents
	 *				date_c = date creation
	 * 				fk_user_c = user creation
	 *  			login_c = login creation
	 * 				fullpath	Full path (Added by build_path_from_id_categ call)
	 * 				fulllabel	Full label (Added by build_path_from_id_categ call)
	 * 				level		Level of line (Added by build_path_from_id_categ call)
	 *  \param		force		Force reload of full arbo even if already loaded
	 *	\return		array		Tableau de array
	 */
	function get_full_arbo($force=0)
	{
		global $conf;

		if (empty($force) && $this->full_arbo_loaded)
		{
			return $this->cats;
		}

		// Init this->motherof that is array(id_son=>id_parent, ...)
		$this->load_motherof();

		// Charge tableau des categories
		$sql = "SELECT c.rowid as rowid, c.label as label,";
		$sql.= " c.description as description, c.cachenbofdoc,";
		$sql.= " c.fk_user_c,";
		$sql.= " c.date_c,";
		$sql.= " u.login as login_c,";
		$sql.= " ca.rowid as rowid_fille";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql.= ", ".MAIN_DB_PREFIX."ecm_directories as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."ecm_directories as ca";
		$sql.= " ON c.rowid = ca.fk_parent";
		$sql.= " WHERE c.fk_user_c = u.rowid";
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " ORDER BY c.label, c.rowid";

		dol_syslog("EcmDirectory::get_full_arbo sql=".$sql);
		$resql = $this->db->query ($sql);
		if ($resql)
		{
			$this->cats = array();
			$i=0;
			while ($obj = $this->db->fetch_object($resql))
			{
				$this->cats[$obj->rowid]['id'] = $obj->rowid;
				$this->cats[$obj->rowid]['id_mere'] = $this->motherof[$obj->rowid];
				$this->cats[$obj->rowid]['label'] = $obj->label;
				$this->cats[$obj->rowid]['description'] = $obj->description;
				$this->cats[$obj->rowid]['cachenbofdoc'] = $obj->cachenbofdoc;
				$this->cats[$obj->rowid]['date_c'] = $obj->date_c;
				$this->cats[$obj->rowid]['fk_user_c'] = $obj->fk_user_c;
				$this->cats[$obj->rowid]['login_c'] = $obj->login_c;

				if ($obj->rowid_fille)
				{
					if (is_array($this->cats[$obj->rowid]['id_children']))
					{
						$newelempos=sizeof($this->cats[$obj->rowid]['id_children']);
						//print "this->cats[$i]['id_children'] est deja un tableau de $newelem elements<br>";
						$this->cats[$obj->rowid]['id_children'][$newelempos]=$obj->rowid_fille;
					}
					else
					{
						//print "this->cats[".$obj->rowid."]['id_children'] n'est pas encore un tableau<br>";
						$this->cats[$obj->rowid]['id_children']=array($obj->rowid_fille);
					}
				}
				$i++;

			}
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}

		// On ajoute la propriete fullpath a tous les elements
		foreach($this->cats as $key => $val)
		{
			if (isset($motherof[$key])) continue;
			$this->build_path_from_id_categ($key,0);
		}

		$this->cats=dol_sort_array($this->cats, 'fulllabel', 'asc', true, false);
		$this->full_arbo_loaded=1;

		return $this->cats;
	}

	/**
	 *	\brief		Calcule les proprietes fullpath et fulllabel d'une categorie
	 *				du tableau this->cats et de toutes ces enfants
	 * 	\param		id_categ		id_categ entry to update
	 * 	\param		protection		Deep counter to avoid infinite loop
	 */
	function build_path_from_id_categ($id_categ,$protection=0)
	{
		// Define fullpath
		if (isset($this->cats[$id_categ]['id_mere']))
		{
			$this->cats[$id_categ]['fullpath'] =$this->cats[$this->cats[$id_categ]['id_mere']]['fullpath'];
			$this->cats[$id_categ]['fullpath'].='_'.$id_categ;
			$this->cats[$id_categ]['fulllabel'] =$this->cats[$this->cats[$id_categ]['id_mere']]['fulllabel'];
			$this->cats[$id_categ]['fulllabel'].=' >> '.$this->cats[$id_categ]['label'];
		}
		else
		{
			$this->cats[$id_categ]['fullpath']='_'.$id_categ;
			$this->cats[$id_categ]['fulllabel']=$this->cats[$id_categ]['label'];
		}
		// We count number of _ to have level
		$this->cats[$id_categ]['level']=strlen(preg_replace('/([^_])/i','',$this->cats[$id_categ]['fullpath']));

		// Traite ces enfants
		$protection++;
		if ($protection > 20) return;	// On ne traite pas plus de 20 niveaux
		if (is_array($this->cats[$id_categ]['id_children']))
		{
			foreach($this->cats[$id_categ]['id_children'] as $key => $val)
			{
				$this->build_path_from_id_categ($val,$protection);
			}
		}

		return 1;
	}

	/**
	 *	\brief		Refresh value for cachenboffile
	 * 	\param		directory		Directory to scan
	 *  \param		all         	0=refresh this id , 1=refresh this entity
	 * 	\return		int				<0 if KO, Nb of files in directory if OK
	 */
	function refreshcachenboffile($all=0)
	{
		global $conf;
		include_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');

		$dir=$conf->ecm->dir_output.'/'.$this->getRelativePath();
		$filelist=dol_dir_list($dir,'files',0,'','\.meta$');

		// Test if filelist is in database


		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."ecm_directories SET";
		$sql.= " cachenbofdoc = '".sizeof($filelist)."'";
		if (empty($all))
		{
			$sql.= " WHERE rowid = ".$this->id;
		}
		else
		{
			$sql.= " WHERE entity = ".$conf->entity;
		}

		dol_syslog("EcmDirectories::refreshcachenboffile sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->cachenbofdoc=sizeof($filelist);
			return $this->cachenbofdoc;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog("EcmDirectories::refreshcachenboffile ".$this->error, LOG_ERR);
			return -1;
		}
	}

}
?>
