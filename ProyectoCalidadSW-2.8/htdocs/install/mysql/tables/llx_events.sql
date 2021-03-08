-- ========================================================================
-- Copyright (C) 2008      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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
-- ========================================================================
-- This table logs all dolibarr security events
-- Content of this table is not managed by users but by Dolibarr
-- trigger interface_all_LogEvent.
-- ========================================================================

create table llx_events
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  tms            timestamp,                   -- date creation/modification
  type           varchar(32)  NOT NULL,       -- action type
  entity         integer DEFAULT 1 NOT NULL,	-- multi company id
  dateevent      datetime,                    -- date event
  fk_user        integer,                     -- id user
  description    varchar(250) NOT NULL,       -- full description of action
  ip             varchar(32) NOT NULL,        -- ip
  user_agent     varchar(128) NULL,           -- user agent
  fk_object      integer                      -- id of related object
) type=innodb;

-- 
-- List of codes for the field entity
--
-- 1 : first company events
-- 2 : second company events
-- 3 : etc...
--