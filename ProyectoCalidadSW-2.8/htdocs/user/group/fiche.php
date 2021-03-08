<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *       \file       htdocs/user/group/fiche.php
 *       \brief      Onglet groupes utilisateurs
 *       \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/usergroup.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/usergroups.lib.php");

// Defini si peux lire/modifier utilisateurs et permisssions
$canreadperms=($user->admin || $user->rights->user->user->lire);
$caneditperms=($user->admin || $user->rights->user->user->creer);
$candisableperms=($user->admin || $user->rights->user->user->supprimer);

$langs->load("users");
$langs->load("other");

// Security check
$result = restrictedArea($user, 'user', $_GET["id"], 'usergroup', 'user');

$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];


/**
 *  Action remove group
 */
if ($_REQUEST["action"] == 'confirm_delete' && $_REQUEST["confirm"] == "yes")
{
	if ($caneditperms)
	{
		$editgroup = new Usergroup($db, $_GET["id"]);
		$editgroup->fetch($_GET["id"]);
		$editgroup->delete();
		Header("Location: index.php");
	}
	else
	{
		$message = '<div class="error">'.$langs->trans('ErrorForbidden').'</div>';
	}
}

/**
 *  Action add group
 */
if ($_POST["action"] == 'add')
{
	if($caneditperms)
	{
		$message="";
		if (! $_POST["nom"])
		{
			$message='<div class="error">'.$langs->trans("NameNotDefined").'</div>';
			$action="create";       // Go back to create page
		}

		if (! $message)
		{
			$editgroup = new UserGroup($db,0);

			$editgroup->nom    = trim($_POST["nom"]);
			$editgroup->note   = trim($_POST["note"]);

			$db->begin();

			$id = $editgroup->create();

			if ($id > 0)
			{
				$db->commit();

				Header("Location: fiche.php?id=".$editgroup->id);
			}
			else
			{
				$langs->load("errors");
				$db->rollback();
				$message='<div class="error">'.$langs->trans("ErrorGroupAlreadyExists",$editgroup->nom).'</div>';
				$action="create";       // Go back to create page
			}
		}
	}else{
		$message = '<div class="error">'.$langs->trans('ErrorForbidden').'</div>';
	}
}

// Add user into group
if ($_POST["action"] == 'adduser')
{
	if($caneditperms)
	{
		if ($_POST["user"])
		{
			$editgroup = new UserGroup($db);
			$editgroup->fetch($_GET["id"]);
			$editgroup->oldcopy=dol_clone($editgroup);

			$edituser = new User($db, $_POST["user"]);
			$result=$edituser->SetInGroup($_GET["id"]);

			// We reload members (list has changed)
			$editgroup->members=$editgroup->listUsersForGroup();

			// We update group to force triggers that update groups content
			$result=$editgroup->update();

			if ($result > 0)
			{
				header("Location: fiche.php?id=".$_GET["id"]);
				exit;
			}
		}
	}else{
		$message = '<div class="error">'.$langs->trans('ErrorForbidden').'</div>';
	}
}

// Remove user from group
if ($_GET["action"] == 'removeuser')
{
	if($caneditperms)
	{
		if ($_GET["user"])
		{
			$editgroup = new UserGroup($db);
			$editgroup->fetch($_GET["id"]);
			$editgroup->oldcopy=dol_clone($editgroup);

			$edituser = new User($db, $_GET["user"]);
			$edituser->RemoveFromGroup($_GET["id"]);

			// We reload members (list has changed)
			$editgroup->members=$editgroup->listUsersForGroup();

			// We update group to force triggers that update groups content
			$result=$editgroup->update();

			if ($result > 0)
			{
				header("Location: fiche.php?id=".$_GET["id"]);
				exit;
			}
		}
	}else{
		$message = '<div class="error">'.$langs->trans('ErrorForbidden').'</div>';
	}
}

if ($_POST["action"] == 'update')
{
	if($caneditperms)
	{
		$message="";

		$db->begin();

		$editgroup = new Usergroup($db, $_GET["id"]);
		$editgroup->fetch($_GET["id"]);

		$editgroup->oldcopy=dol_clone($editgroup);

		$editgroup->nom           = trim($_POST["group"]);
		$editgroup->note          = dol_htmlcleanlastbr($_POST["note"]);

		$ret=$editgroup->update();

		if ($ret >= 0 && ! sizeof($editgroup->errors))
		{
			$message.='<div class="ok">'.$langs->trans("GroupModified").'</div>';
			$db->commit();
		}
		else
		{
			$message.='<div class="error">'.$editgroup->error.'</div>';
			$db->rollback();
		}
	}
	else
	{
		$message = '<div class="error">'.$langs->trans('ErrorForbidden').'</div>';
	}
}



/*
 * View
 */

llxHeader('',$langs->trans("GroupCard"));

$html = new Form($db);


if ($action == 'create')
{
	print_fiche_titre($langs->trans("NewGroup"));

	if ($message) { print $message."<br>"; }

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	print '<table class="border" width="100%">';

	print "<tr>".'<td valign="top">'.$langs->trans("Name").'</td>';
	print '<td class="valeur"><input size="30" type="text" name="nom" value=""></td></tr>';

	print "<tr>".'<td valign="top">'.$langs->trans("Note").'</td><td>';
	if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_USER)
	{
		require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
		$doleditor=new DolEditor('note','',240,'dolibarr_notes','',false);
		$doleditor->Create();
	}
	else
	{
		print '<textarea class="flat" name="note" rows="'.ROWS_8.'" cols="90">';
		print '</textarea>';
	}
	print "</textarea></td></tr>\n";

	print "<tr>".'<td align="center" colspan="2"><input class="button" value="'.$langs->trans("CreateGroup").'" type="submit"></td></tr>';
	print "</table>\n";
	print "</form>";
}


/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */
else
{
	if ($_GET["id"] )
	{
		$group = new UserGroup($db);
		$group->fetch($_GET["id"]);

		/*
		 * Affichage onglets
		 */
		$head = group_prepare_head($group);
		$title = $langs->trans("Group");
		dol_fiche_head($head, 'group', $title);

		/*
		 * Confirmation suppression
		 */
		if ($action == 'delete')
		{
			$ret=$html->form_confirm("fiche.php?id=".$group->id,$langs->trans("DeleteAGroup"),$langs->trans("ConfirmDeleteGroup",$group->name),"confirm_delete", '',0,1);
			if ($ret == 'html') print '<br>';
		}

		/*
		 * Fiche en mode visu
		 */

		if ($action != 'edit')
		{
			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
			print '<td colspan="2">';
			print $html->showrefnav($group,'id','',$user->rights->user->user->lire || $user->admin);
			print '</td>';
			print '</tr>';

			// Name
			print '<tr><td width="25%" valign="top">'.$langs->trans("Name").'</td>';
			print '<td width="75%" class="valeur">'.$group->nom;
			if (!$group->entity)
			{
				print img_redstar($langs->trans("GlobalGroup"));
			}
			print "</td></tr>\n";

			// Note
			print '<tr><td width="25%" valign="top">'.$langs->trans("Note").'</td>';
			print '<td class="valeur">'.nl2br($group->note).'&nbsp;</td>';
			print "</tr>\n";
			print "</table>\n";

			print '</div>';

			/*
			 * Barre d'actions
			 */
			print '<div class="tabsAction">';

			if ($caneditperms)
			{
				print '<a class="butAction" href="fiche.php?id='.$group->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
			}

			if ($candisableperms)
			{
				print '<a class="butActionDelete" href="fiche.php?action=delete&amp;id='.$group->id.'">'.$langs->trans("DeleteGroup").'</a>';
			}

			print "</div>\n";
			print "<br>\n";


			if ($message) { print $message."<br>"; }

            /*
             * Liste des utilisateurs dans le groupe
             */

            print_fiche_titre($langs->trans("ListOfUsersInGroup"),'','');

            // On salectionne les users qui ne sont pas deja dans le groupe
            $userlistid = array();
            $uss = array();

            $sql = "SELECT ug.fk_user";
            $sql.= " FROM ".MAIN_DB_PREFIX."usergroup_user as ug";
            $sql.= ", ".MAIN_DB_PREFIX."user as u";
            $sql.= " WHERE ug.fk_usergroup = ".$group->id;
            $sql.= " AND ug.fk_user = u.rowid";
            $sql.= " AND u.entity IN (0,".$conf->entity.")";

            $result = $db->query($sql);
            if ($result)
            {
            	$num = $db->num_rows($result);
              $i = 0;

              while ($i < $num)
              {
              	$obj = $db->fetch_object($result);

              	$userlistid[]=$obj->fk_user;
              	$i++;
              }
            }
            else {
                dol_print_error($db);
            }

            $idList = implode(",",$userlistid);

            if (!empty($idList))
            {
            	$sql = "SELECT u.rowid, u.login, u.name, u.firstname, u.admin";
            	$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
            	$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
            	$sql.= " AND u.rowid NOT IN (".$idList.")";
            	$sql.= " ORDER BY u.name";

            	$result = $db->query($sql);
            	if ($result)
            	{
            		$num = $db->num_rows($result);
                $i = 0;

                while ($i < $num)
                {
                	$obj = $db->fetch_object($result);

                  $uss[$obj->rowid] = ucfirst(stripslashes($obj->name)).' '.ucfirst(stripslashes($obj->firstname));
                  if ($obj->login) $uss[$obj->rowid].=' ('.$obj->login.')';
                  $i++;
                }
              }
              else {
              	dol_print_error($db);
              }
            }
            else
            {
            	$sql = "SELECT u.rowid, u.login, u.name, u.firstname, u.admin";
            	$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
            	$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
            	$sql.= " ORDER BY u.name";

            	$result = $db->query($sql);
            	if ($result)
            	{
            		$num = $db->num_rows($result);
                $i = 0;

                while ($i < $num)
                {
                	$obj = $db->fetch_object($result);

                  $uss[$obj->rowid] = ucfirst(stripslashes($obj->name)).' '.ucfirst(stripslashes($obj->firstname));
                  if ($obj->login) $uss[$obj->rowid].=' ('.$obj->login.')';
                  $i++;
                }
              }
              else {
              	dol_print_error($db);
              }
            }

            if ($caneditperms)
            {
                $form = new Form($db);
                print '<form action="fiche.php?id='.$group->id.'" method="post">'."\n";
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                print '<input type="hidden" name="action" value="adduser">';
                print '<table class="noborder" width="100%">'."\n";
                print '<tr class="liste_titre"><td class="liste_titre" width="25%">'.$langs->trans("NonAffectedUsers").'</td>'."\n";
                //print '<tr class="liste_titre"><td class="liste_titre" width="25%">'.$langs->trans("UsersToAdd").'</td>'."\n";
                print '<td>';
                print $form->select_array("user",$uss,'',1);
                print ' &nbsp; ';
                print '<input type="submit" class=button value="'.$langs->trans("Add").'">';
                print '</td></tr>'."\n";
                print '</table></form>'."\n";
                print '<br>';
            }

            /*
             * Membres du groupe
             */
            $sql = "SELECT u.rowid, u.login, u.name, u.firstname, u.admin, u.entity";
            $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
            $sql.= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
            $sql.= " WHERE ug.fk_user = u.rowid";
            $sql.= " AND ug.fk_usergroup = ".$group->id;
            $sql.= " ORDER BY u.name";

            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);
                $i = 0;

                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre">';
                print '<td class="liste_titre" width="25%">'.$langs->trans("Login").'</td>';
                print '<td class="liste_titre" width="25%">'.$langs->trans("Lastname").'</td>';
                print '<td class="liste_titre" width="25%">'.$langs->trans("Firstname").'</td>';
                print '<td>&nbsp;</td>';
                print "<td>&nbsp;</td></tr>\n";
                if ($num) {
                    $var=True;
                    while ($i < $num)
                    {
                        $obj = $db->fetch_object($result);
                        $var=!$var;

                        print "<tr $bc[$var]>";
                        print '<td>';
                        print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowUser"),"user").' '.$obj->login.'</a>';
                        if ($obj->admin  && !$obj->entity)
                        {
                        	print img_redstar($langs->trans("SuperAdministrator"));
                        }
                        else if ($obj->admin)
                        {
                        	print img_picto($langs->trans("Administrator"),'star');
                        }
                        print '</td>';
                        print '<td>'.ucfirst(stripslashes($obj->name)).'</td>';
                        print '<td>'.ucfirst(stripslashes($obj->firstname)).'</td>';
                        print '<td>&nbsp;</td><td>';

                        if ($user->admin)
                        {

                            print '<a href="fiche.php?id='.$group->id.'&amp;action=removeuser&amp;user='.$obj->rowid.'">';
                            print img_delete($langs->trans("RemoveFromGroup"));
                        }
                        else
                        {
                            print "-";
                        }
                        print "</td></tr>\n";
                        $i++;
                    }
                }
                else
                {
                    print '<tr><td colspan=2>'.$langs->trans("None").'</td></tr>';
                }
                print "</table>";
                print "<br>";
                $db->free($result);
            }
            else {
                dol_print_error($db);
            }
        }

        /*
         * Fiche en mode edition
         */
        if ($_GET["action"] == 'edit' && $caneditperms)
        {
            print '<form action="fiche.php?id='.$group->id.'" method="post" name="updategroup" enctype="multipart/form-data">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';

            print '<table class="border" width="100%">';
            print '<tr><td width="25%" valign="top">'.$langs->trans("Name").'</td>';
            print '<td width="75%" class="valeur"><input size="15" type="text" name="group" value="'.$group->nom.'">';
            if (!$group->entity)
            {
            	print img_redstar($langs->trans("GlobalGroup"));
            }
            print "</td></tr>\n";
            print '<tr><td width="25%" valign="top">'.$langs->trans("Note").'</td>';
            print '<td class="valeur">';

			if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_USER)
			{
				require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
				$doleditor=new DolEditor('note',$group->note,240,'dolibarr_notes','',true);
				$doleditor->Create();
			}
			else
			{
				print '<textarea class="flat" name="note" rows="'.ROWS_8.'" cols="90">';
				print dol_htmlentitiesbr_decode($group->note);
				print '</textarea>';
			}
			print '</td>';
			print "</tr>\n";
			print '<tr><td align="center" colspan="2"><input class="button" value="'.$langs->trans("Save").'" type="submit"></td></tr>';
			print "</table>\n";
			print '</form>';

			print '</div>';
		}

	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
