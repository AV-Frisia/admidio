<?php
/******************************************************************************
 * Ankuendigungen anlegen und bearbeiten
 *
 * Copyright    : (c) 2004 - 2005 The Admidio Team
 * Homepage     : http://www.admidio.org
 * Module-Owner : Markus Fassbender
 *
 * Uebergaben:
 *
 * aa_id         - ID der Ankuendigung, die bearbeitet werden soll
 * headline      - Ueberschrift, die ueber den Ankuendigungen steht
 *                 (Default) Ankuendigungen
 *
 ******************************************************************************
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 *****************************************************************************/
require("../../../adm_config/config.php");
require("../../system/function.php");
require("../../system/date.php");
require("../../system/string.php");
require("../../system/tbl_user.php");
require("../../system/session_check_login.php");

if(!isModerator())
{
   $location = "location: $g_root_path/adm_program/system/err_msg.php?err_code=norights";
   header($location);
   exit();
}

if(!array_key_exists("headline", $_GET))
   $_GET["headline"] = "Ankündigungen";

$global        = 0;
$headline      = "";
$description   = "";

// Wenn eine Ankuendigungs-ID uebergeben wurde, soll die Ankuendigung geaendert werden
// -> Felder mit Daten der Ankuendigung vorbelegen

if ($_GET["aa_id"] != 0)
{
   $sql    = "SELECT * FROM adm_ankuendigungen WHERE aa_id = {0}";
   $sql    = prepareSQL($sql, array($_GET['aa_id']));
   $result = mysql_query($sql, $g_adm_con);
   db_error($result);

   if (mysql_num_rows($result) > 0)
   {
      $row_ba = mysql_fetch_object($result);

      $global        = $row_ba->aa_global;
      $headline      = $row_ba->aa_ueberschrift;
      $description   = $row_ba->aa_beschreibung;
   }
}

echo "
<!-- (c) 2004 - 2005 The Admidio Team - http://www.admidio.org - Version: ". getVersion(). " -->\n
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
   <title>". $g_orga_property['ag_shortname']. " - ". $_GET["headline"]. "</title>
   <link rel=\"stylesheet\" type=\"text/css\" href=\"$g_root_path/adm_config/main.css\">
   
   <!--[if gte IE 5.5000]>
   <script type=\"text/javascript\" src=\"$g_root_path/adm_program/system/correct_png.js\"></script>
   <![endif]-->";

   require("../../../adm_config/header.php");
echo "</head>";

require("../../../adm_config/body_top.php");
   echo "
   <div style=\"margin-top: 10px; margin-bottom: 10px;\" align=\"center\">

   <form action=\"announcements_function.php?aa_id=". $_GET["aa_id"]. "&amp;headline=". $_GET['headline']. "&amp;mode=";
      if($_GET["aa_id"] > 0)
         echo "3";
      else
         echo "1";
      echo "\" method=\"post\" name=\"AnkuendigungAnlegen\">
      
      <div class=\"formHead\">";
         if($_GET["aa_id"] > 0)
            $formHeadline = $_GET["headline"]. " ändern";
         else
            $formHeadline = $_GET["headline"]. " anlegen";
         echo strspace($formHeadline, 2);
      echo "</div>
      <div class=\"formBody\">
         <div>
            <div style=\"text-align: right; width: 25%; float: left;\">&Uuml;berschrift:</div>
            <div style=\"text-align: left; margin-left: 27%;\">
               <input type=\"text\" name=\"ueberschrift\" size=\"53\" maxlength=\"100\" value=\"". htmlspecialchars($headline, ENT_QUOTES). "\">
            </div>
         </div>

         <div style=\"margin-top: 6px;\">
            <div style=\"text-align: right; width: 25%; float: left;\">Beschreibung:</div>
            <div style=\"text-align: left; margin-left: 27%;\">
               <textarea  name=\"beschreibung\" rows=\"7\" cols=\"40\">". htmlspecialchars($description, ENT_QUOTES). "</textarea>
            </div>
         </div>";

         // bei mehr als einer Gruppierung, Checkbox anzeigen, ob, Termin bei anderen angezeigt werden soll
         $sql = "SELECT COUNT(1) FROM adm_gruppierung
                  WHERE ag_mother IS NOT NULL ";
         $result = mysql_query($sql, $g_adm_con);
         db_error($result);
         $row = mysql_fetch_array($result);

         if($row[0] > 0)
         {
            echo "
            <div style=\"margin-top: 6px;\">
               <div style=\"text-align: right; width: 25%; float: left;\">&nbsp;</div>
               <div style=\"text-align: left; margin-left: 27%;\">
                  <input type=\"checkbox\" id=\"global\" name=\"global\" ";
                  if($global == 1)
                     echo " checked=\"checked\" ";
                  echo " value=\"1\" />
                  <label for=\"global\">". $_GET["headline"]. " f&uuml;r mehrere Gruppierungen sichtbar</label>&nbsp;
                  <img src=\"$g_root_path/adm_program/images/help.png\" style=\"cursor: pointer; vertical-align: top;\" vspace=\"1\" width=\"16\" height=\"16\" border=\"0\" alt=\"Hilfe\" title=\"Hilfe\"
                  onclick=\"window.open('$g_root_path/adm_program/system/msg_window.php?err_code=termin_global','Message','width=400,height=200,left=310,top=200,scrollbars=yes')\">
               </div>
            </div>";
         }

         echo "<hr width=\"85%\" />

         <div style=\"margin-top: 6px;\">
            <button name=\"speichern\" type=\"submit\" value=\"speichern\">
            <img src=\"$g_root_path/adm_program/images/save.png\" style=\"vertical-align: middle;\" align=\"top\" vspace=\"1\" width=\"16\" height=\"16\" border=\"0\" alt=\"Speichern\">
            Speichern</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

            <button name=\"zurueck\" type=\"button\" value=\"zurueck\" onclick=\"history.back()\">
            <img src=\"$g_root_path/adm_program/images/back.png\" style=\"vertical-align: middle;\" align=\"top\" vspace=\"1\" width=\"16\" height=\"16\" border=\"0\" alt=\"Zur&uuml;ck\">
            Zur&uuml;ck</button>
         </div>";
         if($row_ba->aa_last_change_id > 0)
         {
            // Angabe &uuml;ber die letzten Aenderungen
            $sql    = "SELECT au_vorname, au_name
                         FROM adm_user
                        WHERE au_id = $row_ba->aa_last_change_id ";
            $result = mysql_query($sql, $g_adm_con);
            db_error($result);
            $row = mysql_fetch_array($result);

            echo "<div style=\"margin-top: 6px;\">
               <span style=\"font-size: 10pt\">
               Letzte &Auml;nderung am ". mysqldatetime("d.m.y h:i", $row_ba->aa_last_change).
               " durch $row[0] $row[1]
               </span>
            </div>";
         }

      echo "</div>
   </form>
   
   </div>";

   require("../../../adm_config/body_bottom.php");
echo "</body>
</html>";
?>