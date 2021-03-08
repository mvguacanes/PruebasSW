-- ===================================================================
-- Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009 Regis Houssin        <regis@dolibarr.fr>
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

-- drop table llx_ecm_directories;

create table llx_ecm_directories
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  label           varchar(32) NOT NULL,
  entity          integer DEFAULT 1 NOT NULL,	-- multi company id
  fk_parent       integer,
  description     varchar(255) NOT NULL,
  cachenbofdoc    integer NOT NULL DEFAULT 0,
  date_c		  datetime,
  date_m		  timestamp,
  fk_user_c		  integer,
  fk_user_m		  integer
) type=innodb;
