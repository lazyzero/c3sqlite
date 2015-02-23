<?php
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'admin.php');
 
/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_c3sqlite extends DokuWiki_Admin_Plugin {

    function admin_plugin_c3sqlite() {
	global $auth;
	$this->_auth = & $auth;
	$this->setupLocale();
    }
 
    /**
     * return some info
     */
    function getInfo(){
      return array(
        'author' => 'Christian Moll',
        'email'  => 'christian@chrmoll.de',
        'date'   => '2015-02-23',
        'name'   => 'C3sqlite Query Editor',
        'desc'   => 'Admin plugin for editing SQLite querys',
        'url'    => 'http://www.chrmoll.de',
      );
    }
 
    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
      return 111;
    }

    function forAdminOnly() {
      return false;
    }

    /**
     * handle user request
     */
    function handle() {
       if(isset($_REQUEST['fn'])){
	    
	    // extract the command and any specific parameters
	    // submit button name is of the form - fn[cmd][param(s)]
	    $fn   = $_REQUEST['fn'];


	    if (is_array($fn)) {
		$cmd = key($fn);
		$param = is_array($fn[$cmd]) ? key($fn[$cmd]) : null;
		echo($param);
	    } else {
		$cmd = $fn;
		$param = null;
	    }

	    switch($cmd){
	      case "load" : $this->_loadTemplate(); break;
	      case "save" : $this->_saveTemplate(); break;
	      case "reload" : $this->_loadTemplate(); break;
	      case "new" : $this->_newTemplate(); break;
	      
	    }
	}
    }

    function _loadTemplate() {
	global $conf;
	global $lang;
	global $auth;
	$counter = 0;
	
	if (!$auth) return false;

//         if (!is_array($selected) || empty($selected)) return false;
//         $selected = array_keys($selected);

    }

    function _newTemplate() {
	global $conf;
	global $lang;
	global $auth;
	$counter = 0;

	if (!$auth) return false;
 	
	$text = $_REQUEST['text'];
	
	if(!$this->_checkQuery($text)) return;
	
        if (isset($_REQUEST['survey'])) {
	    $queryName = $_REQUEST['newname'];
	    
	    $queryName = str_replace(' ', '', strtolower($queryName));
	    $queryFolder = DOKU_INC.$this->getConf('querypath');
	    $queryFileName = $queryFolder.'/'.$queryName.'.txt';
	    io_saveFile($queryFileName, $text);
	}      
    }
     
    function _saveTemplate() {
	global $conf;
	global $lang;
	global $auth;
	$counter = 0;

	if (!$auth) return false;

	$load = $_REQUEST['load'];
	$text = $_REQUEST['text'];
	
	if(!$this->_checkQuery($text)) return;
	
        if (isset($_REQUEST['survey'])) {
	    $queryName = $_REQUEST['queryname'];
	    $queryName = str_replace(' ', '', strtolower($queryName));
	    $queryFolder = DOKU_INC.$this->getConf('querypath');
	    $queryFileName = $queryFolder.'/'.$queryName.'.txt';
	    io_saveFile($queryFileName, $text);
	}      
    }
    
    function _checkQuery($query) {
	global $conf;
	
	//get the keywords not allowed to be in the query.
	$forbidden = $this->getConf('forbiddenCommands');
	$forbidden = str_replace(' ', '', $forbidden);
	$forbidden = strtoupper($forbidden);
	$forbidden = explode(',', $forbidden);
	
	$query = strtoupper($query);
	$query = explode(' ', $query);
	
	foreach($forbidden as $keyword) {
	    if (in_array($keyword, $query)) {
		msg($this->getLang('forbiddenCommandfound').$this->getConf('forbiddenCommands'),-1);
		return false;
	    }
	}
	return true;
    }

    /**
     * output appropriate html
     */
	function _editor() {
	    global $auth;
	    global $ID;
	    global $lang;
	    global $conf;
	    
	    if (!empty($_REQUEST['newname'])) {
		$queryName = $_REQUEST['newname'];
	    } else {
		$queryName = $_REQUEST['queryname'];
	    }
	    
	    $queryName = str_replace(' ', '', strtolower($queryName));
	    $queryFolder = DOKU_INC.$this->getConf('querypath');
	    $queryFileName = $queryFolder.'/'.$queryName.'.txt';
	    
	    $d = dir($queryFolder);
	    while (false !== ($entry = $d->read())) {
		if (!preg_match('/^\./', $entry)) {
// 			var_dump($entry);
		    $queries[] = str_replace('.txt','',$entry);
		}
	    }
	    $d->close();
	    
	    echo($this->locale_xhtml('list'));
	    
	    $title = $this->getInfo();
	    ptln('<div class="level1"><h1>'.$title['name'].'</h1></div>');
	    
	    ptln(io_readFile(DOKU_PLUGIN.'c3sqlite/lang/'.$conf['lang'].'/intro2.txt', false));
	    
	    ptln('<div class="level2">');
	    ptln("<form action=\"".wl($ID)."\" method=\"post\">");
	    formSecurityToken();
		
	    ptln("Select a query form the list and load it:<br>");
		
	    ptln("<br>".$this->getLang('loadintro')."<br>");
	    ptln("<select name='queryname'>");
	    foreach($queries as $query) {
		if ($query == $queryName) {
		    $selected = " selected";
		} else {
		    $selected = "";
		}
		
		ptln("<option".$selected.">".$query."</option>");
		  
	    }
	    ptln("</select>");
	    ptln("<input name='fn[load]' class='button' value='load' id='usrmgr__notify' type='submit'>");
	    ptln("<input name='fn[save]' class='button' value='save' id='usrmgr__notify' type='submit' onclick=\"return confirm('".$this->getLang('confirmsave')."');\">");
	    ptln("<input name='fn[test]' class='button' value='preview' id='usrmgr__notify' type='submit'>");
	    
	    ptln("<br>".$this->getLang('newintro')."<br>");
	    ptln("<input name='newname' class='textfield' id='usrmgr__notify' type='text'>");
	    ptln("<input name='fn[new]' class='button' value='new' id='usrmgr__notify' type='submit' onclick=\"return confirm('".$this->getLang('confirmsave')."');\">");
	    ptln($this->getLang('newqueryname')."</p>");
	    
	    ptln("<input name='do' value='admin' type='hidden'>");
	    ptln("<input name='page' value='query' type='hidden'>");
	    
	    if (isset($_REQUEST['fn']['test'])) {
		$text = $_REQUEST['text'];
		$replaceBy = $_REQUEST['replaceBy'];
	    } else if (isset($_REQUEST['survey'])){
		$text = io_readFile($queryFileName, false);
	    } else {
		$text = 'Load a query first...';
		$replaceBy = '';
	    }
	    ptln("Query:<br><textarea name='text' cols='40' rows='20' wrap='physical' class='edit' style='width:50%; height: 100%;'>".$text."</textarea><br>");
	    ptln("Replacement parameters:<br><textarea name='replaceBy' cols='40' rows='1' wrap='physical' class='edit' style='width:50%; height: 100%;'>".$replaceBy."</textarea><br>");
	    ptln("</form>");
	    
	    ptln(io_readFile(DOKU_PLUGIN.'query/lang/'.$conf['lang'].'/description.txt' ,false));
	    ptln("<div class=\"sectionedit2\" style=\"padding-left:30px;\">".$this->getConf('forbiddenCommands').'</div>');

	    ptln('</div>');
	}
	
     function _test() {
	global $conf;
	global $lang;
	global $auth;
	$counter = 0;

	if (!$auth) return false;
	
 	$surveyName = $_REQUEST['survey'];
	$text = $_REQUEST['text'];
	
	if(!$this->_checkQuery($text)) return;
	
        if (isset($_REQUEST['survey'])) {
	    $queryName = $_REQUEST['queryname'];
	    $surveyName = $_REQUEST['survey'];
	    $surveyName = str_replace(' ', '', strtolower($surveyName));
	    $result = $this->_connect($text);
	    $this->_table($result);
	}
        
    }
    
    function _table($result) {
	ptln('<br>');
	ptln('<div class="level2"><h2>'.$this->getLang('preview').'</h2></div>');
	ptln("<div><table class=\"inline\">");
	ptln("<tbody><tr class=\"row0\">");
	
	$cols = count(pg_fetch_array($result, 0));
	for ($i = 0; $i < $cols/2; $i++) {
	    ptln("<th class=\"col$i\">".pg_field_name($result, $i)."</th>");
	}
	ptln("</tr>");
	$i=1;
	while ($row = pg_fetch_row($result)) {
	    ptln("<tr class=\"row$i\">\n");
	    $j=0;
	    foreach($row as $value) {
		if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}/i',$value)) {
		    $value = '<a class="mail" title="'.$value.'" href="mailto:'.$value.'">'.$value.'</a>';
		}
		ptln("<td class=\"col$j\">$value </td>\n");
	    }
	    ptln("</tr>\n");
	    $i++;
	}
	
	ptln("</tbody></table></div>");
    }
	 
    function html() {
	global $ID;
	echo($this->locale_xhtml('intro'));

	$this->_editor();
	if (isset($_REQUEST['fn']['test'])) {
	    $this->_test();
	}
	
	
    }
 
}