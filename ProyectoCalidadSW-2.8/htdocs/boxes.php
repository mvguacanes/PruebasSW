<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       htdocs/boxes.php
 \brief      Fichier de la classe boxes
 \author     Rodolphe Qiedeville
 \author	    Laurent Destailleur
 \version    $Id$
 */



/**
 *	\class      InfoBox
 *	\brief      Classe permettant la gestion des boxes sur une page
 */
class InfoBox
{
	var $db;

	/**
	 *      \brief      Constructeur de la classe
	 *      \param      $DB         Handler d'acc�s base
	 */
	function InfoBox($DB)
	{
		$this->db=$DB;
	}


	/**
	 *      \brief      Retourne tableau des boites elligibles pour la zone et le user
	 *      \param      $zone       ID de la zone (0 pour la Homepage, ...)
	 *      \param      $user		Objet user
	 *      \return     array       Tableau d'objet box
	 */
	function listBoxes($zone,$user)
	{
		global $conf;

		$boxes=array();

		$confuserzone='MAIN_BOXES_'.$zone;
		if ($user->id && $user->conf->$confuserzone)
		{
			// Get list of boxes of a particular user (if this one has its own list)
			$sql = "SELECT b.rowid, b.box_id, b.position, b.box_order, b.fk_user,";
			$sql.= " d.file, d.note";
			$sql.= " FROM ".MAIN_DB_PREFIX."boxes as b, ".MAIN_DB_PREFIX."boxes_def as d";
			$sql.= " WHERE b.box_id = d.rowid";
			$sql.= " AND d.entity = ".$conf->entity;
			$sql.= " AND b.position = ".$zone;
			$sql.= " AND b.fk_user = ".$user->id;
			$sql.= " ORDER BY b.box_order";

			dol_syslog("InfoBox::listBoxes get user box list sql=".$sql, LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$j = 0;
				while ($j < $num)
				{
					$obj = $this->db->fetch_object($result);
					$boxname=preg_replace('/\.php$/i','',$obj->file);
					include_once(DOL_DOCUMENT_ROOT."/includes/boxes/".$boxname.".php");
					$box=new $boxname($this->db,$obj->note);
					$box->rowid=$obj->rowid;
					$box->box_id=$obj->box_id;
					$box->position=$obj->position;
					$box->box_order=$obj->box_order;
					$box->fk_user=$obj->fk_user;
					$enabled=true;
					if ($box->depends && sizeof($box->depends) > 0)
					{
						foreach($box->depends as $module)
						{
							//							print $module.'<br>';
							if (empty($conf->$module->enabled)) $enabled=false;
						}
					}
					if ($enabled) $boxes[]=$box;
					$j++;
				}
			}
			else {
				$this->error=$this->db->error();
				dol_syslog("InfoBox::listBoxes Error ".$this->error, LOG_ERR);
				return array();
			}
		}
		else
		{
			// Recupere liste des boites active par defaut pour tous
			$sql = "SELECT b.rowid, b.box_id, b.position, b.box_order, b.fk_user,";
			$sql.= " d.file, d.note";
			$sql.= " FROM ".MAIN_DB_PREFIX."boxes as b, ".MAIN_DB_PREFIX."boxes_def as d";
			$sql.= " WHERE b.box_id = d.rowid";
			$sql.= " AND d.entity = ".$conf->entity;
			$sql.= " AND b.position = ".$zone;
			$sql.= " AND b.fk_user = 0";
			$sql.= " ORDER BY b.box_order";

			dol_syslog("InfoBox::listBoxes get default box list sql=".$sql, LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$j = 0;
				while ($j < $num)
				{
					$obj = $this->db->fetch_object($result);
					$boxname=preg_replace('/\.php$/i','',$obj->file);
					include_once(DOL_DOCUMENT_ROOT."/includes/boxes/".$boxname.".php");
					$box=new $boxname($this->db,$obj->note);
					$box->rowid=$obj->rowid;
					$box->box_id=$obj->box_id;
					$box->position=$obj->position;
					$box->box_order=$obj->box_order;
					if (is_numeric($box->box_order))
					{
						if ($box->box_order % 2 == 1) $box->box_order='A'.$box->box_order;
						elseif ($box->box_order % 2 == 0) $box->box_order='B'.$box->box_order;
					}
					$box->fk_user=$obj->fk_user;
					$enabled=true;
					if ($box->depends && sizeof($box->depends) > 0)
					{
						foreach($box->depends as $module)
						{
							//print $boxname.'-'.$module.'<br>';
							if (empty($conf->$module->enabled)) $enabled=false;
						}
					}
					if ($enabled) $boxes[]=$box;
					$j++;
				}
			}
			else {
				$this->error=$this->db->error();
				dol_syslog("InfoBox::listBoxes Error ".$this->error, LOG_ERR);
				return array();
			}
		}

		return $boxes;
	}


	/**
	 *      \brief      Sauvegarde sequencement des boites pour la zone et le user
	 *      \param      $zone       ID de la zone (0 pour la Homepage, ...)
	 *      \param      $boxorder   Liste des boites dans le bon ordre 'A:123,456,...-B:789,321...'
	 *      \param      $userid     Id du user
	 *      \return     int         <0 si ko, >= 0 si ok
	 */
	function saveboxorder($zone,$boxorder,$userid=0)
	{
		global $conf;

		require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");

		dol_syslog("InfoBoxes::saveboxorder zone=".$zone." user=".$userid);

		if (! $userid || $userid == 0) return 0;

		$user = new User($this->db,$userid);

		$this->db->begin();

		// Sauve parametre indiquant que le user a une
		$confuserzone='MAIN_BOXES_'.$zone;
		$tab[$confuserzone]=1;
		if (dol_set_user_param($this->db, $conf, $user, $tab) < 0)
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -3;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes";
		$sql.= " USING ".MAIN_DB_PREFIX."boxes, ".MAIN_DB_PREFIX."boxes_def";
		$sql.= " WHERE ".MAIN_DB_PREFIX."boxes.box_id = ".MAIN_DB_PREFIX."boxes_def.rowid";
		$sql.= " AND ".MAIN_DB_PREFIX."boxes_def.entity = ".$conf->entity;
		$sql.= " AND ".MAIN_DB_PREFIX."boxes.fk_user = ".$userid;
		$sql.= " AND ".MAIN_DB_PREFIX."boxes.position = ".$zone;

		dol_syslog("InfoBox::saveboxorder sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$colonnes=explode('-',$boxorder);
			foreach ($colonnes as $collist)
			{
				$part=explode(':',$collist);
				$colonne=$part[0];
				$list=$part[1];
				dol_syslog('InfoBox::saveboxorder column='.$colonne.' list='.$list);

				$i=0;
				$listarray=explode(',',$list);
				foreach ($listarray as $id)
				{
					if (is_numeric($id))
					{
						//dol_syslog("aaaaa".sizeof($listarray));
						$i++;
						$ii=sprintf('%02d',$i);
						$sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes";
						$sql.= "(box_id, position, box_order, fk_user)";
						$sql.= " values (";
						$sql.= " ".$id.",";
						$sql.= " ".$zone.",";
						$sql.= " '".$colonne.$ii."',";
						$sql.= " ".$userid;
						$sql.= ")";

						dol_syslog("InfoBox::saveboxorder sql=".$sql);
						$result = $this->db->query($sql);
						if ($result < 0)
						{
							$error++;
							break;
						}
					}
				}
			}
			if ($error)
			{
				$this->error=$this->db->error();
				$this->db->rollback();
				return -2;
			}
			else
			{
				$this->db->commit();
				return 1;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			dol_syslog("InfoBox::saveboxorder ".$this->error);
			return -1;
		}
	}
}
?>
