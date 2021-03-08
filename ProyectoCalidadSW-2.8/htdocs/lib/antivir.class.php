<?php
/* Copyright (C) 2000-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *      \file       htdocs/lib/antivir.class.php
 *      \brief      File of class to scan viruses
 *		\version    $Id$
 *      \author	    Laurent Destailleur.
 */

/**
 *      \class      AntiVir
 *      \brief      Class to scan for virus
 */
class AntiVir
{
	var $error;
	var $errors;
	var $output;
	var $db;

	/**
	 * Constructor
	 *
	 * @param unknown_type $db
	 * @return AntiVir
	 */
	function AntiVir($db)
	{
		$this->db=$db;
	}

	/**
	 *	\brief  	Scan a file with antivirus
	 *	\param	 	file			File to scan
	 *	\return	 	int				<0 if KO (-98 if error, -99 if virus), 0 if OK
	 */
	function dol_avscan_file($file)
	{
		global $conf;

		$return = 0;

		$maxreclevel = 5 ; 			// maximal recursion level
		$maxfiles = 1000; 			// maximal number of files to be scanned within archive
		$maxratio = 200; 			// maximal compression ratio
		$bz2archivememlim = 0; 		// limit memory usage for bzip2 (0/1)
		$maxfilesize = 10485760; 	// archived files larger than this value (in bytes) will not be scanned

		@set_time_limit($cfg['ExecTimeLimit']);
		$outputfile=$conf->admin->dir_temp.'/dol_avscan_file.out.'.session_id();

		$command=$conf->global->MAIN_ANTIVIRUS_COMMAND;
		$param=$conf->global->MAIN_ANTIVIRUS_PARAM;

		$param=preg_replace('/%maxreclevel/',$maxreclevel,$param);
		$param=preg_replace('/%maxfiles/',$maxfiles,$param);
		$param=preg_replace('/%maxratio/',$maxratiod,$param);
		$param=preg_replace('/%bz2archivememlim/',$bz2archivememlim,$param);
		$param=preg_replace('/%maxfilesize/',$maxfilesize,$param);
		$param=preg_replace('/%file/',trim($file),$param);

		if (! preg_match('/%file/',$conf->global->MAIN_ANTIVIRUS_PARAM))
			$param=$param." ".escapeshellarg(trim($file));

		if (preg_match("/\s/",$command)) $command=escapeshellarg($command);	// Use quotes on command

		$output=array();
		$return_var=0;
		// Create a clean fullcommand
		$fullcommand=$command.' '.$param.' 2>&1';
		dol_syslog("AntiVir::dol_avscan_file Run command=".$fullcommand);
		exec($fullcommand, $output, $return_var);

		/*
		$handle = fopen($outputfile, 'w');
		if ($handle)
		{
			$handlein = popen($fullcommand, 'r');
			while (!feof($handlein))
			{
				$read = fgets($handlein);
				fwrite($handle,$read);
			}
			pclose($handlein);

			$errormsg = fgets($handle,2048);
			$this->output=$errormsg;

			fclose($handle);

			if (! empty($conf->global->MAIN_UMASK))
				@chmod($outputfile, octdec($conf->global->MAIN_UMASK));
		}
		else
		{
			$langs->load("errors");
			dol_syslog("Failed to open file ".$outputfile,LOG_ERR);
			$this->error="ErrorFailedToWriteInDir";
			$return=-1;
		}
		*/

		dol_syslog("AntiVir::dol_avscan_file Result return_var=".$return_var." output=".join(',',$output));

		$returncodevirus=1;
		if ($return_var == $returncodevirus)	// Virus found
		{
			$this->errors=$output;
			return -99;
		}

		if ($return_var > 0)					// If other error
		{
			$this->errors=$output;
			return -98;
		}

		// If return code = 0
		return 1;
	}

}

?>