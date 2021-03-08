<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 * Copyright (C) 2008 Laurent Destailleur   <eldy@uers.sourceforge.net>
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

include('../master.inc.php');
require('include/environnement.php');
require('classes/Auth.class.php');

$username = $_POST['txtUsername'];
$password = $_POST['pwdPassword'];

$auth = new Auth($db);

$retour = $auth->verif ($username, $password);

if ( $retour >= 0 )
{
	$return=array();
	
	$sql = "SELECT rowid, name, firstname";
	$sql.= " FROM ".MAIN_DB_PREFIX."user";
	$sql.= " WHERE login = '".$username."'";
	$sql.= " AND entity IN (0,".$conf->entity.")";

	$result = $db->query($sql);

	if ($result)
	{
		$tab = $db->fetch_array($res);
		
		foreach ( $tab as $key => $value )
		{
			$return[$key] = $value;
		}
		
		$_SESSION['uid'] = $tab['rowid'];
		$_SESSION['uname'] = $username;
		$_SESSION['nom'] = $tab['name'];
		$_SESSION['prenom'] = $tab['firstname'];
		
		header ('Location: '.DOL_URL_ROOT.'/cashdesk/affIndex.php?menu=facturation&id=NOUV');
	}
	else
	{
		dol_print_error($db);
	}

}
else
{
	header ('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.$retour.'&user='.$username);
}

?>