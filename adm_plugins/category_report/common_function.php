<?php
/**
 ***********************************************************************************************
 * Various common functions for the admidio plugin Category Report
 *
 * @copyright 2004-2021 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');

if(!defined('PLUGIN_FOLDER'))
{
	define('PLUGIN_FOLDER', '/'.substr(__DIR__,strrpos(__DIR__,DIRECTORY_SEPARATOR)+1));
}

if(!defined('ORG_ID'))
{
	define('ORG_ID', (int) $gCurrentOrganization->getValue('org_id'));
}

/**
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin aufzurufen.
 * Zur Prüfung werden die Einstellungen von 'Modulrechte' und 'Sichtbar für' 
 * verwendet, die im Modul Menü für dieses Plugin gesetzt wurden.
 * Zusätzlich muss der Nutzer immer mindestens einer Rolle mit der Berechtigung
 * 'Mitgliederlisten aller Rollen einsehen' angehören.
 * @param   string  $scriptName   Der Scriptname des Plugins
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorized($scriptName)
{
	global $gDb, $gCurrentUser, $gMessage, $gL10n, $gLogger;
	
	$userIsAuthorized = false;
	$menId = 0;
	
	$sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url = ? -- $scriptName ';
	
	$menuStatement = $gDb->queryPrepared($sql, array($scriptName));
	
	if ( $menuStatement->rowCount() === 0 || $menuStatement->rowCount() > 1)
	{
		$gLogger->notice('CategoryReport: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
		$gLogger->notice('CategoryReport: Error with menu entry: ScriptName: '. $scriptName);
		$gMessage->show($gL10n->get('PLG_CATEGORY_REPORT_MENU_URL_ERROR', array($scriptName)), $gL10n->get('SYS_ERROR'));
	}
	else
	{
		while ($row = $menuStatement->fetch())
		{
			$menId = (int) $row['men_id'];
		}
	}
	
	$sql = 'SELECT men_id, men_com_id, com_name_intern
              FROM '.TBL_MENU.'
         LEFT JOIN '.TBL_COMPONENTS.'
                ON com_id = men_com_id
             WHERE men_id = ? -- $menId
          ORDER BY men_men_id_parent DESC, men_order';
	
	$menuStatement = $gDb->queryPrepared($sql, array($menId));
	
	while ($row = $menuStatement->fetch())
	{
		if ((int) $row['men_com_id'] === 0 || Component::isVisible($row['com_name_intern']))
		{
			// Read current roles rights of the menu
			$displayMenu = new RolesRights($gDb, 'menu_view', $row['men_id']);
			$rolesDisplayRight = $displayMenu->getRolesIds();
			
			// check for right to show the menu
			if ((count($rolesDisplayRight) === 0 || $displayMenu->hasRight($gCurrentUser->getRoleMemberships())) && $gCurrentUser->checkRolesRight('rol_all_lists_view'))
			{
				$userIsAuthorized = true;
			}
		}
	}
	return $userIsAuthorized;
}

/**
 * Funktion überprueft den übergebenen Namen, ob er gemaess den Namenskonventionen für
 * Profilfelder und Kategorien zum Uebersetzen durch eine Sprachdatei geeignet ist
 * Bsp: SYS_COMMON --> Rueckgabe true
 * Bsp: Mitgliedsbeitrag --> Rueckgabe false
 *
 * @param   string  $field_name
 * @return  bool
 */
function check_languagePCR($field_name)
{
    $ret = false;
 
    //pruefen, ob die ersten 3 Zeichen von $field_name Grußbuchstaben sind
    //pruefen, ob das vierte Zeichen von $field_name ein _ ist

    //Prüfung entfaellt: pruefen, ob die restlichen Zeichen von $field_name Grußbuchstaben sind
    //if ((ctype_upper(substr($field_name,0,3))) && ((substr($field_name,3,1))=='_')  && (ctype_upper(substr($field_name,4)))   )

    if ((ctype_upper(substr($field_name,0,3))) && ((substr($field_name,3,1)) == '_')   )
    {
      $ret = true;
    }
    return $ret;
}
 

/**
 * Funktion prueft, ob ein User Angehoeriger einer bestimmten Kategorie ist
 *
 * @param   int  $cat_id    ID der zu pruefenden Kategorie
 * @param   int  $user_id   ID des Users, fuer den die Mitgliedschaft geprueft werden soll
 * @return  bool
 */
function isMemberOfCategorie($cat_id, $user_id = 0)
{
    global $gCurrentUser, $gDb;

    if ($user_id == 0)
    {
        $user_id = $gCurrentUser->getValue('usr_id');
    }
    elseif (is_numeric($user_id) == false)
    {
        return -1;
    }

    $sql    = 'SELECT mem_id
                 FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. '
                WHERE mem_usr_id = ? -- $user_id
                  AND mem_begin <= ? -- DATE_NOW
                  AND mem_end    > ? -- DATE_NOW
                  AND mem_rol_id = rol_id
                  AND cat_id   = ? -- $cat_id
                  AND rol_valid  = 1
                  AND rol_cat_id = cat_id
                  AND (  cat_org_id = ? -- ORG_ID
                   OR cat_org_id IS NULL ) ';
    
    $queryParams = array(
        $user_id,
        DATE_NOW,
        DATE_NOW,
        $cat_id,
        ORG_ID
    );
    $statement = $gDb->queryPrepared($sql, $queryParams);
    $user_found = $statement->rowCount();

    if ($user_found == 1)
    {
        return 1;
    }
    else
    {
        return 0;
    }   
}

/**
 * Funktion prüft, ob es eine Konfiguration mit dem übergebenen Namen bereits gibt
 * wenn ja: wird "- Kopie" angehängt und rekursiv überprüft
 * @param   string  $name
 * @return  string  
 */
function createColDescConfig($name)
{
  //  global $pPreferences, $gL10n;
    global $config, $gL10n;
    
    
    while (in_array($name, $config['col_desc']))
    {
        $name .= ' - '.$gL10n->get('MAI_CARBON_COPY');
    }

    return $name;
}

/**
 * Funktion initialisiert das Konfigurationsarray
 * @param   none
 * @return  Array $config  das Konfigurationsarray 
 */
function initConfigArray()
{
    global $gL10n, $gProfileFields;
    
    $config = array(	'col_desc' 		=> array($gL10n->get('PLG_CATEGORY_REPORT_PATTERN')),
                        'col_fields' 	=> array(	'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id').','.
                                                    'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id').','.
                                                    'p'.$gProfileFields->getProperty('STREET', 'usf_id').','.
                                                    'p'.$gProfileFields->getProperty('CITY', 'usf_id')),
                        'col_yes'		=> array('ja'),
                        'col_no'		=> array('nein'),
                        'selection_role'=> array(' '),
                        'selection_cat'	=> array(' '),
                        'number_col'	=> array(0)  );
        
    return $config;
}

/**
 * Funktion liest das Konfigurationsarray ein
 * @param   none
 * @return  Array $config  das Konfigurationsarray
 */
function getConfigArray()
{
    global  $gSettingsManager;
    $dbtoken  = '#_#';  
    
    $config = array();
    $config['col_desc']       = explode($dbtoken, $gSettingsManager->get('category_report_col_desc')); 
    $config['col_fields']     = explode($dbtoken, $gSettingsManager->get('category_report_col_fields')); 
    $config['col_yes']        = explode($dbtoken, $gSettingsManager->get('category_report_col_yes')); 
    $config['col_no']         = explode($dbtoken, $gSettingsManager->get('category_report_col_no')); 
    $config['selection_role'] = explode($dbtoken, $gSettingsManager->get('category_report_selection_role')); 
    $config['selection_cat']  = explode($dbtoken, $gSettingsManager->get('category_report_selection_cat')); 
    $config['number_col']     = explode($dbtoken, $gSettingsManager->get('category_report_number_col')); 
    $config['col_desc']       = explode($dbtoken, $gSettingsManager->get('category_report_col_desc')); 
    
    return $config;
}

/**
 * Funktion speichert das Konfigurationsarray
 * @param   none
 */
function saveConfigArray()
{
    global  $gSettingsManager, $config;
    $dbtoken  = '#_#';
    
    foreach ($config as $name => $value)
    {
        $gSettingsManager->set('category_report_'.$name, implode($dbtoken,$value));
    }
    
    return;
}

