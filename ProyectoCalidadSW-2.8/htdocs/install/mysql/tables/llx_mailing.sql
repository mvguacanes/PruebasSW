-- ========================================================================
-- Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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


-- redaction : 0
-- valide    : 1
-- approuv?  : 2
-- envoye    : 3

create table llx_mailing
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  statut             smallint       DEFAULT 0,            --
  titre              varchar(60),                         -- Ref of mailing
  entity             integer DEFAULT 1 NOT NULL,	        -- multi company id
  sujet              varchar(60),                         -- Sujet of mailing
  body               text,
  bgcolor            varchar(8),                          -- Backgroud color of mailing
  bgimage            varchar(255),                        -- Backgroud image of mailing
  cible              varchar(60),
  nbemail            integer,
  email_from         varchar(160),                        -- Email address of sender
  email_replyto      varchar(160),                        -- Email address for reply
  email_errorsto     varchar(160),                        -- Email addresse for errors
  date_creat         datetime,                            -- creation date
  date_valid         datetime,                            -- 
  date_appro         datetime,                            -- 
  date_envoi         datetime,                            -- date d'envoi
  fk_user_creat      integer,                             -- utilisateur qui a cr?? l'info
  fk_user_valid      integer,                             -- utilisateur qui a cr?? l'info
  fk_user_appro      integer                              -- utilisateur qui a cr?? l'info

)type=innodb;

--
-- List of codes for the field entity
--
-- 1 : first company mailing
-- 2 : second company mailing
-- 3 : etc...
--