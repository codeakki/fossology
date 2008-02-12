<?php
/***********************************************************
 Copyright (C) 2008 Hewlett-Packard Development Company, L.P.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 version 2 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
***********************************************************/


/*************************************************
 Restrict usage: Every PHP file should have this
 at the very beginning.
 This prevents hacking attempts.
 *************************************************/
global $GlobalReady;
if (!isset($GlobalReady)) { exit; }

/*************************************************************
 Each plugin should focus on one key functionality.
 These definitions identify the key types of functions.
 *************************************************************/
define("PLUGIN_UI",1); // plugin is primarily a UI template
define("PLUGIN_AGENT",2); // agents handle uploads and jobs
define("PLUGIN_ADMIN",3); // plugin manages the system
define("PLUGIN_MISC",4); // plugin does something else

/*************************************************************
 Each plugin has a state to identify if it is invalid.
 For example, if a plugin crashes then it should mark the state
 as invalid.
 *************************************************************/
define("PLUGIN_STATE_INVALID",0);
define("PLUGIN_STATE_VALID",1); // used during install
define("PLUGIN_STATE_READY",2); // used during post-install

/*************************************************************
 This is the Plugin class.  All plugins should:
   1. Use this class or extend this class.
   2. After defining the necessary functions and values, the plugin
      must add the new element to the Plugins array.
      For example:
	$NewPlugin = new Plugin;
	$NewPlugin->Name="Fred";
	if ($NewPlugin->Initialize() != 0) { destroy $NewPlugin; }
 *************************************************************/
class Plugin
  {
  // All public fields can be empty, indicating that it does not apply.

  var $Type=PLUGIN_MISC;
  var $State=PLUGIN_STATE_INVALID;

  /*****
   Name defines the official name of this plugin.  Other plugins may
   call this plugin based on this name.
   *****/
  var $Name="";
  var $Version="1.0";
  var $Title="";  // used for HTML title tags and window menu bars

  /*****
   This array lists plugin dependencies by name and initialization order.
   These are used to call PostInitialize in the correct order.
   PostInitialize will be called when all dependencies are ready.
   InitOrder says "after all dependencies are ready, do higher value
   items first."  For example, this allows for menus to be initialized
   before anything else.  (You probably won't need to change InitOrder.)
   *****/
  var $Dependency = array();
  var $InitOrder=0;

  /*****
   Plugins may define a menu item.
   The menu name defines where it belongs.
   Each menu item belongs in a category (menu list) and could be in
   subcategories (menu sublists).  The MenuList identifies
   the list (and sublists) where this item belongs.  The menu heirarchy
   is defined by a name and a "::" to denote a submenu item.

   The MenuName defines the name for this item in the menu.

   Finally, multiple plugins may place multiple items under the same menu.
   The MenuOrder assigns a numeric ranking for items.  All items
   at the same level are sorted alphabetically by MenuName.

   For example, to define an "About" menu item under the "Help" menu:
     $MenuList = "Help::About";
     $MenuOrder=0;
   And a "delete" agent under the tool, administration menu would be:
     $MenuList = "Tools::Administration::Delete";
     $MenuOrder=0;

   Since menus may link to results that belong in a specific window,
   $MenuTarget can identify the window.  If not defined, the UI will use
   a default results window.

   NOTES:
   1. If the MenuList location does not exist, then it will be created.
   2. If a plugin does not have a menulist item, then it will not appear
      in any menus.
   3. MenuList is case and SPACE sensitive.  "Help :: About" defines
      "Help " and " About".  While "Help::About" defines "Help" and "About".
   *****/
  var $MenuList=NULL;
  var $MenuOrder=0;
  var $MenuTarget=NULL;

  /*****
   These next variables define required functionality.
   If the functions exist, then they are called.  However, plugins are
   not required to define any of these.
   *****/

  /***********************************************************
   Install(): This function (when defined) is only called once,
   when the plugin is first installed.  It should make sure all
   requirements are available and create anything it needs to run.
   It returns 0 on success, non-zero on failure.
   A failed install is not inserted in the system.
   ***********************************************************/
  function Install()
    {
    return(0);
    } // Install()

  /***********************************************************
   Remove(): This function (when defined) is only called once,
   when the plugin is removed.  It should uninstall and remove
   all items that are only used by this plugin.  There should be
   no residues -- if the plugin is ever installed again, it should
   act like a clean install.  Thus, any DB, files, or state variables
   specific to this plugin must be removed.
   This function must always succeed.
   ***********************************************************/
  function Remove()
    {
    return;
    } // Remove()

  /***********************************************************
   Initialize(): This is called before the plugin is used.
   It should assume that Install() was already run one time
   (possibly years ago and not during this object's creation).
   Returns true on success, false on failure.
   A failed initialize is not used by the system.
   NOTE: This function must NOT assume that other plugins are installed.
   ***********************************************************/
  function Initialize()
    {
    if ($this->State != PLUGIN_STATE_INVALID) { return(1); } // don't re-run
    if ($this->Name !== "") // Name must be defined
      {
      global $Plugins;
      $this->State=PLUGIN_STATE_VALID;
      array_push($Plugins,$this);
      }
    return($this->State == PLUGIN_STATE_VALID);
    } // Initialize()

  /***********************************************************
   PostInitialize(): This function is called before the plugin
   is used and after all plugins have been initialized.
   If there is any initialization step that is dependent on other
   plugins, put it here.
   Returns true on success, false on failure.
   NOTE: Do not assume that the plugin exists!  Actually check it!
   ***********************************************************/
  function PostInitialize()
    {
    global $Plugins;
    if ($this->State != PLUGIN_STATE_VALID) { return(0); } // don't run
    // Make sure dependencies are met
    foreach($this->Dependency as $key => $val)
      {
      $id = plugin_find_id($val);
      if ($id < 0) { $this->Destroy(); return(0); }
      }

    // Put your code here!
    // If this fails, set $this->State to PLUGIN_STATE_INVALID.
    // If it succeeds, then set $this->State to PLUGIN_STATE_READY.

    // It worked, so mark this plugin as ready.
    $this->State = PLUGIN_STATE_READY;
    // Add this plugin to the menu
    if ($this->MenuList !== "")
	{
	menu_insert($this->MenuList,$this->MenuOrder,$this->MenuTarget,$this->Name);
	}
    return($this->State == PLUGIN_STATE_READY);
    } // PostInitialize()

  /***********************************************************
   Destroy(): This is a destructor called after the plugin
   is no longer needed.  It should assume that PostInitialize() was
   already run one time (this session) and succeeded.
   This function must always succeed.
   ***********************************************************/
  function Destroy()
    {
    if ($this->State != PLUGIN_STATE_INVALID)
      {
      ; // Put your cleanup here
      }
    $this->State=PLUGIN_STATE_INVALID;
    return;
    } // Destroy()

  /*********************************************************************/
  /*********************************************************************/
  /*********************************************************************
   The output functions generate "output" for use in a text CLI or web page.
   For agents, the outputs generate status information.
   *********************************************************************/
  /*********************************************************************/
  /*********************************************************************/

  /* Possible values: Text, HTML, or XML. */
  var $OutputType="Text";
  var $OutputToStdout=0;

  /***********************************************************
   OutputOpen(): This function is called when user output is
   requested.  This function is responsible for assigning headers.
   If $Type is "HTML" then generate an HTTP header.
   If $Type is "XML" then begin an XML header.
   If $Type is "Text" then generate a text header as needed.
   The $ToStdout flag is "1" if output should go to stdout, and
   0 if it should be returned as a string.  (Strings may be parsed
   and used by other plugins.)
   ***********************************************************/
  function OutputOpen($Type,$ToStdout)
    {
    if ($this->State != PLUGIN_STATE_READY) { return(0); }
    $this->OutputType=$Type;
    $this->OutputToStdout=$ToStdout;
    // Put your code here
    switch($this->OutputType)
      {
      case "XML":
	$V = "<xml>\n";
	break;
      case "HTML":
	header('Content-type: text/html');
	$V = "<html>\n";
	$V .= "<head>\n";
	/* DOCTYPE is required for IE to use styles! (else: css menu breaks) */
	$V .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "xhtml1-frameset.dtd">' . "\n";
	// $V .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n";
	// $V .= '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Loose//EN" "http://www.w3.org/TR/html4/loose.dtd">' . "\n";
	if (!empty($Title)) { $V .= "<title>" . htmlentities($Title) . "</title>\n"; }
	$V .= "<link rel='stylesheet' href='fossology.css'>\n";
	$V .= "</head>\n";
	$V .= "<body>\n";
	break;
      case "Text":
	break;
      default:
	break;
      }
    if (!$this->OutputToStdout) { return($V); }
    print "$V";
    return;
    } // OutputOpen()

  /***********************************************************
   OutputClose(): This function is called when user output is done.
   If $Type is "HTML" then display the HTML footer as needed.
   If $Type is "XML" then end the XML.
   If $Type is "Text" then generate a text footer as needed.
   This uses $OutputType.
   The $ToStdout flag is "1" if output should go to stdout, and
   0 if it should be returned as a string.  (Strings may be parsed
   and used by other plugins.)
   ***********************************************************/
  function OutputClose()
    {
    if ($this->State != PLUGIN_STATE_READY) { return(0); }
    // Put your code here
    switch($this->OutputType)
      {
      case "XML":
	$V = "</xml>\n";
	break;
      case "HTML":
	$V = "</body>\n";
	$V .= "</html>\n";
	break;
      case "Text":
	break;
      default:
	break;
      }
    if (!$this->OutputToStdout) { return($V); }
    print "$V";
    return;
    } // OutputClose()

  /***********************************************************
   OutputSet(): Similar to OutputOpen, this sets the output type
   for this object.  However, this does NOT change any global
   settings.  This is called when this object is a dependency
   for another object.
   ***********************************************************/
  function OutputSet($Type,$ToStdout)
    {
    if ($this->State != PLUGIN_STATE_READY) { return(0); }
    $this->OutputType=$Type;
    $this->OutputToStdout=$ToStdout;
    // Put your code here
    switch($this->OutputType)
      {
      case "XML":
	$V = "<xml>\n";
	break;
      case "HTML":
	break;
      case "Text":
	break;
      default:
	break;
      }
    if (!$this->OutputToStdout) { return($V); }
    print "$V";
    return;
    } // OutputSet()

  /***********************************************************
   OutputUnSet(): Similar to OutputClose, this ends the output type
   for this object.  However, this does NOT change any global
   settings.  This is called when this object is a dependency
   for another object.
   ***********************************************************/
  function OutputUnSet()
    {
    if ($this->State != PLUGIN_STATE_READY) { return(0); }
    // Put your code here
    switch($this->OutputType)
      {
      case "XML":
	$V = "</xml>\n";
	break;
      case "HTML":
	break;
      case "Text":
	break;
      default:
	break;
      }
    if (!$this->OutputToStdout) { return($V); }
    print "$V";
    return;
    } // OutputUnSet()

  /***********************************************************
   Output(): This function is called when user output is
   requested.  This function is responsible for content.
   (OutputOpen and Output are separated so one plugin
   can call another plugin's Output.)
   This uses $OutputType.
   The $ToStdout flag is "1" if output should go to stdout, and
   0 if it should be returned as a string.  (Strings may be parsed
   and used by other plugins.)
   ***********************************************************/
  function Output()
    {
    if ($this->State != PLUGIN_STATE_READY) { return(0); }
    // Put your code here
    $V="";
    switch($this->OutputType)
      {
      case "XML":
	break;
      case "HTML":
	break;
      case "Text":
	break;
      default:
	break;
      }
    if (!$this->OutputToStdout) { return($V); }
    print "$V";
    return;
    } // Output()

  /*********************************************************************/
  /*********************************************************************/
  /*********************************************************************
   General actions.
   These functions will likely be called by other plugins.
   *********************************************************************/
  /*********************************************************************/
  /*********************************************************************/

  /***********************************************************
   Action(): This function is used to perform a specific action.
   The $Command is a string containing the command.
   Unlike ActionArg(), this does not separate the Command from the Target.
   $Command and return value are plugin specific.
   ***********************************************************/
  function Action($Command)
    {
    if ($this->State != PLUGIN_STATE_READY) { return(0); }
    // Put your code here
    return;
    } // Action()

  };
?>
