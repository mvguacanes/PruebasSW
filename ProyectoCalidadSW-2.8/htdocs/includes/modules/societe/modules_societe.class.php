<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
	    \file       htdocs/includes/modules/societe/modules_societe.class.php
		\ingroup    societe
		\brief      Fichier contenant la classe m�re de module de generation societes
		\version    $Id$
*/


/**
	    \class      ModeleThirdPartyCode
		\brief  	Classe m�re des mod�les de num�rotation des codes tiers
*/

class ModeleThirdPartyCode
{
    var $error='';

    /**     \brief      Renvoi la description par defaut du modele de num�rotation
     *      \return     string      Texte descripif
     */
    function info($langs)
    {
        $langs->load("bills");
        return $langs->trans("NoDescription");
    }

    /**     \brief      Renvoi nom module
     *      \return     string      Nom du module
     */
    function getNom($langs)
    {
        return $this->nom;
    }


    /**     \brief      Renvoi un exemple de num�rotation
     *      \return     string      Example
     */
    function getExample($langs)
    {
        $langs->load("bills");
        return $langs->trans("NoExample");
    }

    /**     \brief      Test si les num�ros d�j� en vigueur dans la base ne provoquent pas de
     *                  de conflits qui empechera cette num�rotation de fonctionner.
     *      \return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
        return true;
    }

    /**     \brief      Return next value available
     *      \return     string      Value
     */
    function getNextValue($objsoc=0,$type=-1)
    {
    	global $langs;
        return $langs->trans("Function_getNextValue_InModuleNotWorking");
    }


	/**     \brief      Renvoi version du module numerotation
	*      	\return     string      Valeur
	*/
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		return $langs->trans("NotAvailable");
	}

	/**
     *      \brief      Renvoi la liste des mod�les actifs
     *      \param      db      Handler de base
     */
    function liste_modeles($db)
    {
        $liste=array();
        $sql ="";

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $db->fetch_row($resql);
                $liste[$row[0]]=$row[1];
                $i++;
            }
        }
        else
        {
            return -1;
        }
        return $liste;
    }

    /**
     *      \brief      Return description of module parameters
     *      \param      langs      	Output language
	 *		\param		soc			Third party object
	 *		\param		type		-1=Nothing, 0=Customer, 1=Supplier
	 *		\return		string		HTML translated description
     */
    function getToolTip($langs,$soc,$type)
    {
    	global $conf;

    	$langs->load("admin");

		$s='';
		if ($type == -1) $s.=$langs->trans("Name").': <b>'.$this->nom.'</b><br>';
		if ($type == -1) $s.=$langs->trans("Version").': <b>'.$this->getVersion().'</b><br>';
		if ($type == 0)  $s.=$langs->trans("CustomerCodeDesc").'<br>';
		if ($type == 1)  $s.=$langs->trans("SupplierCodeDesc").'<br>';
		if ($type != -1) $s.=$langs->trans("ValidityControledByModule").': <b>'.$this->getNom($langs).'</b><br>';
		$s.='<br>';
		$s.='<u>'.$langs->trans("ThisIsModuleRules").':</u><br>';
		if ($type == 0)
		{
			$s.=$langs->trans("RequiredIfCustomer").': ';
			if ($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED && !empty($this->code_null)) $s.='<strike>';
			$s.=yn(!$this->code_null,1,2);
			if ($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED && !empty($this->code_null)) $s.='</strike> '.yn(1,1,2).' ('.$langs->trans("ForcedToByAModule",$langs->transnoentities("yes")).')';
			$s.='<br>';
		}
		if ($type == 1)
		{
			$s.=$langs->trans("RequiredIfSupplier").': ';
			if ($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED && !empty($this->code_null)) $s.='<strike>';
			$s.=yn(!$this->code_null,1,2);
			if ($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED && !empty($this->code_null)) $s.='</strike> '.yn(1,1,2).' ('.$langs->trans("ForcedToByAModule",$langs->transnoentities("yes")).')';
			$s.='<br>';
		}
		if ($type == -1)
		{
			$s.=$langs->trans("Required").': ';
			if ($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED && !empty($this->code_null)) $s.='<strike>';
			$s.=yn(!$this->code_null,1,2);
			if ($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED && !empty($this->code_null)) $s.='</strike> '.yn(1,1,2).' ('.$langs->trans("ForcedToByAModule",$langs->transnoentities("yes")).')';
			$s.='<br>';
		}
		$s.=$langs->trans("CanBeModifiedIfOk").': ';
		$s.=yn($this->code_modifiable,1,2);
		$s.='<br>';
		$s.=$langs->trans("CanBeModifiedIfKo").': '.yn($this->code_modifiable_invalide,1,2).'<br>';
		$s.=$langs->trans("AutomaticCode").': '.yn($this->code_auto,1,2).'<br>';
		$s.='<br>';
		if ($type == 0 || $type == -1)
		{
			$nextval=$this->getNextValue($soc,0);
			if (empty($nextval)) $nextval=$langs->trans("Undefined");
			$s.=$langs->trans("NextValue").($type == -1?' ('.$langs->trans("Customer").')':'').': <b>'.$nextval.'</b><br>';
		}
		if ($type == 1 || $type == -1)
		{
			$nextval=$this->getNextValue($soc,1);
			if (empty($nextval)) $nextval=$langs->trans("Undefined");
			$s.=$langs->trans("NextValue").($type == -1?' ('.$langs->trans("Supplier").')':'').': <b>'.$nextval.'</b>';
		}
		return $s;
	}

	function verif_prefixIsUsed()
	{
		return false;
	}

}


/**
		\class		ModeleAccountancyCode
		\brief  	Classe m�re des mod�les de num�rotation des codes compta
*/

class ModeleAccountancyCode
{
    var $error='';

    /**     \brief      Renvoi la description par defaut du modele de num�rotation
     *      \return     string      Texte descripif
     */
    function info($langs)
    {
        $langs->load("bills");
        return $langs->trans("NoDescription");
    }

    /**     \brief      Renvoi un exemple de num�rotation
     *      \return     string      Example
     */
    function getExample($langs)
    {
        $langs->load("bills");
        return $langs->trans("NoExample");
    }

    /**     \brief      Test si les num�ros d�j� en vigueur dans la base ne provoquent pas de
     *                  de conflits qui empechera cette num�rotation de fonctionner.
     *      \return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
        return true;
    }

    /**     \brief      Renvoi prochaine valeur attribu�e
     *      \return     string      Valeur
     */
    function getNextValue($langs)
    {
        return $langs->trans("NotAvailable");
    }
}

?>
