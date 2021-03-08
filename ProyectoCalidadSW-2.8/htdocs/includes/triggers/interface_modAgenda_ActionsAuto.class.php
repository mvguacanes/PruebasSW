<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/includes/triggers/interface_modAgenda_ActionsAuto.class.php
 *  \ingroup    agenda
 *  \brief      Trigger file for agenda module
 *	\version	$Id$
 */


/**
 *	\class      InterfaceActionsAuto
 *  \brief      Class of triggered functions for agenda module
 */
class InterfaceActionsAuto
{
    var $db;
    var $error;

    var $date;
    var $duree;
    var $texte;
    var $desc;

    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    function InterfaceActionsAuto($DB)
    {
        $this->db = $DB ;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "agenda";
        $this->description = "Triggers of this module add actions in agenda according to setup made in agenda setup.";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
    }

    /**
     *   \brief      Renvoi nom du lot de triggers
     *   \return     string      Nom du lot de triggers
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *   \brief      Renvoi descriptif du lot de triggers
     *   \return     string      Descriptif du lot de triggers
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   \brief      Renvoi version du lot de triggers
     *   \return     string      Version du lot de triggers
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      \brief      Fonction appelee lors du declenchement d'un evenement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre presentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concerne
     *      \param      user        Objet user
     *      \param      langs       Objet langs
     *      \param      conf        Objet conf
     *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
     */
    function run_trigger($action,$object,$user,$langs,$conf)
    {
		$key='MAIN_AGENDA_ACTIONAUTO_'.$action;
		//dol_syslog("xxxxxxxxxxx".$key);
		if (empty($conf->global->$key)) return 0;				// Log events not enabled for this action

		// Following properties must be filled:
		// $object->actiontypecode;
		// $object->actionmsg (note, long text)
		// $object->actionmsg2 (label, short text)
		// $object->sendtoid
		// $object->socid
		// Optionnal:
		// $object->facid
		// $object->propalrowid
		// $object->orderrowid

		$ok=0;

		// Actions
		if ($action == 'COMPANY_CREATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("NewCompanyToDolibarr",$object->nom);
            $object->actionmsg=$langs->transnoentities("NewCompanyToDolibarr",$object->nom);
            if ($object->prefix) $object->actionmsg.=" (".$object->prefix.")";
            //$this->desc.="\n".$langs->transnoentities("Customer").': '.yn($object->client);
            //$this->desc.="\n".$langs->transnoentities("Supplier").': '.yn($object->fournisseur);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->socid=$object->id;
			$object->facid=$object->orderrowid=$object->propalrowid=0;
			$ok=1;
        }
        elseif ($action == 'CONTRACT_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("contracts");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("ContractValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("ContractValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->facid=$object->orderrowid=$object->propalrowid=0;
			$ok=1;
		}
		elseif ($action == 'PROPAL_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("propal");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("PropalValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("PropalValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->propalrowid=$object->id;
			$object->facid=$object->orderrowid=0;
			$ok=1;
		}
        elseif ($action == 'PROPAL_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("propal");
            $langs->load("agenda");
			$ok=1;

			// Parameters $object->xxx defined by caller
		}
		elseif ($action == 'PROPAL_CLOSE_SIGNED')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("propal");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("PropalClosedSignedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("PropalClosedSignedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->propalrowid=$object->id;
			$object->facid=$object->orderrowid=0;
			$ok=1;
		}
		elseif ($action == 'PROPAL_CLOSE_REFUSED')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("propal");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("PropalClosedRefusedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("PropalClosedRefusedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->propalrowid=$object->id;
			$object->facid=$object->orderrowid=0;
			$ok=1;
		}
		elseif ($action == 'ORDER_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("orders");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("OrderValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("OrderValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->orderrowid=$object->id;
			$object->propalrowid=$object->facid=0;
			$ok=1;
		}
        elseif ($action == 'ORDER_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("orders");
            $langs->load("agenda");
			$ok=1;

			// Parameters $object->xxx defined by caller
		}
		elseif ($action == 'BILL_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("InvoiceValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->facid=$object->id;
			$object->orderrowid=$object->propalrowid=0;
			$ok=1;
		}
        elseif ($action == 'BILL_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");
			$ok=1;

			// Parameters $object->xxx defined by caller
		}
		elseif ($action == 'BILL_PAYED')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("InvoicePaidInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoicePaidInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->facid=$object->id;
			$object->orderrowid=$object->propalrowid=0;
			$ok=1;
		}
		elseif ($action == 'BILL_CANCELED')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("InvoiceCanceledInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceCanceledInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->facid=$object->id;
			$object->orderrowid=$object->propalrowid=0;
			$ok=1;
		}
		elseif ($action == 'FICHEINTER_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("interventions");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("InterventionValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InterventionValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$ok=1;
		}
		elseif ($action == 'ORDER_SUPPLIER_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("orders");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("OrderValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("OrderValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->orderrowid=0;	// Supplier order not yet supported
			$object->propalrowid=$object->facid=0;
			$ok=1;
		}
		elseif ($action == 'BILL_SUPPLIER_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("InvoiceValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->facid=0;	// Supplier invoice not yet supported
			$object->orderrowid=$object->propalrowid=0;
			$ok=1;
		}

        // Members
        elseif ($action == 'MEMBER_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("members");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("MemberValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("MemberValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->fullname;
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->facid=0;	// Supplier invoice not yet supported
			$object->orderrowid=$object->propalrowid=0;
			$ok=1;
        }
        elseif ($action == 'MEMBER_SUBSCRIPTION')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("members");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("MemberSubscriptionAddedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("MemberSubscriptionAddedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->fullname;
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;
            $object->actionmsg.="\n".$langs->transnoentities("Amount").': '.$object->last_subscription_amount;
            $object->actionmsg.="\n".$langs->transnoentities("Period").': '.dol_print_date($object->last_subscription_date_start,'day').' - '.dol_print_date($object->last_subscription_date_end,'day');
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->facid=$object->orderrowid=$object->propalrowid=0;
			$ok=1;
        }
        elseif ($action == 'MEMBER_MODIFY')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'MEMBER_RESILIATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("members");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("MemberResiliatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("MemberResiliatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->fullname;
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->facid=$object->orderrowid=$object->propalrowid=0;
			$ok=1;
        }
        elseif ($action == 'MEMBER_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("members");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            $object->actionmsg2=$langs->transnoentities("MemberDeletedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("MemberDeletedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->fullname;
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->facid=$object->orderrowid=$object->propalrowid=0;
			$ok=1;
        }

		// If not found
/*
        else
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' was ran by ".__FILE__." but no handler found for this action.");
			return 0;
        }
*/

        // Add entry in event table
        if ($ok)
        {
			$now=dol_now('tzserver');

			// Insertion action
			require_once(DOL_DOCUMENT_ROOT.'/contact.class.php');
			require_once(DOL_DOCUMENT_ROOT.'/actioncomm.class.php');
			$actioncomm = new ActionComm($this->db);
			$actioncomm->type_code   = $object->actiontypecode;
			$actioncomm->label       = $object->actionmsg2;
			$actioncomm->note        = $object->actionmsg;
			$actioncomm->datep       = $now;
			$actioncomm->datef       = $now;
			$actioncomm->durationp   = 0;
			$actioncomm->punctual    = 1;
			$actioncomm->percentage  = 100;
			$actioncomm->contact     = new Contact($this->db,$object->sendtoid);
			$actioncomm->societe     = new Societe($this->db,$object->socid);
			$actioncomm->author      = $user;   // User saving action
			//$actioncomm->usertodo  = $user;	// User affected to action
			$actioncomm->userdone    = $user;	// User doing action
			$actioncomm->facid       = $object->facid;
			$actioncomm->orderrowid  = $object->orderrowid;
			$actioncomm->propalrowid = $object->propalrowid;
			$ret=$actioncomm->add($user);       // User qui saisit l'action
			if ($ret > 0)
			{
				return 1;
			}
			else
			{
                $error ="Failed to insert : ".$actioncomm->error." ";
                $this->error=$error;

                dol_syslog("interface_modAgenda_ActionsAuto.class.php: ".$this->error, LOG_ERR);
                return -1;
			}
		}

		return 0;
    }

}
?>
