<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/user/info.php
 *      \ingroup    core
 *		\brief      Page des informations d'un utilisateur
 *		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/usergroups.lib.php');
require_once(DOL_DOCUMENT_ROOT."/user.class.php");

$langs->load("users");

// Security check
$id = isset($_GET["id"])?$_GET["id"]:'';
$fuser = new User($db);
$fuser->id = $id;
$fuser->fetch();

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2 = (($socid && $user->rights->user->self->creer)?'':'user');
if ($user->id == $_GET["id"])	// A user can always read its own card
{
	$feature2='';
}
$result = restrictedArea($user, 'user', $_GET["id"], '', $feature2);

// If user is not user read and no permission to read other users, we stop
if (($fuser->id != $user->id) && (! $user->rights->user->user->lire))
  accessforbidden();



/*
 * View
 */

llxHeader();

$fuser->info($_GET["id"]);

$head = user_prepare_head($fuser);

$title = $langs->trans("User");
dol_fiche_head($head, 'info', $title, 0, 'user');


print '<table width="100%"><tr><td>';
dol_print_object_info($fuser);
print '</td></tr></table>';

print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
