<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * $Id$
 * $Source$
 */

/**
		\file 		htdocs/admin/boutique-special-all.php
		\ingroup    boutique
		\brief      Page d'administration/configuration du module Boutique
		\version    $Revision$
*/

require("./pre.inc.php");
require("../boutique/livre/livre.class.php");
require("../boutique/editeur/editeur.class.php");
require("../boutique/auteur/auteur.class.php");
require("../product/promotion/promotion.class.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


llxHeader();


print_barre_liste("Mise a jour de tous les livres", $page, "boutique-special-all.php");

$sql = "SELECT l.rowid FROM ".MAIN_DB_PREFIX."livre as l";
  
if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num) {
    $objp = $db->fetch_object();

    $livre = new Livre($db);
    if ($livre->fetch($objp->rowid))
      {
	
	$prom = new Promotion($db);
	$prom->create($user,$livre->oscid, 5);

      }
    else 
      {
	print "Error can't fetch";
      }
    $i++;
  }
}
else
{
  print $db->error();
}



llxFooter();
?>
