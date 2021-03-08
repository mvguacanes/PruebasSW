-- ===================================================================
-- Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008-2009 Regis Houssin        <regis@dolibarr.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- ===================================================================

create table llx_livraison
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  ref                   varchar(30)  NOT NULL,         -- delivery number
  entity                integer DEFAULT 1 NOT NULL,    -- multi company id
  ref_client            varchar(30),                   -- customer number
  fk_soc                integer      NOT NULL,
  fk_expedition         integer,                       -- expedition auquel est rattache le bon de livraison
  
  date_creation         datetime,                      -- date de creation
  fk_user_author        integer,                       -- createur du bon de livraison
  date_valid            datetime,                      -- date de validation
  fk_user_valid         integer,                       -- valideur du bon de livraison
  date_livraison 	      date 	       default NULL,     -- date de livraison
  fk_adresse_livraison  integer,                       -- adresse de livraison
  fk_statut             smallint     default 0,
  total_ht              double(24,8) default 0,
  note                  text,
  note_public           text,
  model_pdf             varchar(50)
  
)type=innodb;
