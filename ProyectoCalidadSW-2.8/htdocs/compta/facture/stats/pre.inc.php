<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/**
        \file         htdocs/compta/facture/stats/pre.inc.php
        \ingroup      facture
        \brief        Fichier de gestion du menu gauche des stats facture
        \version      $Revision$
*/

require("../../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/stats/facturestats.class.php");


function llxHeader($head = "", $urlp = "")
{
  global $langs;
  $langs->load("bills");
  $langs->load("propal");
  
  top_menu($head);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/compta/facture.php", $langs->trans("Bills"));

  $menu->add("index.php", $langs->trans("Statistics"));

  left_menu($menu->liste);
}
?>
