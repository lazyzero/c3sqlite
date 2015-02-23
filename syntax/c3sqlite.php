<?php
/**
 * DokuWiki Plugin C3SQLite (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Christian Moll <christian@chrmoll.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_c3sqlite_c3sqlite extends DokuWiki_Syntax_Plugin {
    /**
     * @return string Syntax mode type
     */
    public function getType() {
        return 'substition';
    }
    /**
     * @return string Paragraph type
     */
    public function getPType() {
        return 'block';
    }
    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort() {
        return 200;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<C3SQLITE.+?</C3SQLITE>',$mode,'plugin_c3sqlite_c3sqlite');
        $this->Lexer->addSpecialPattern('<c3sqlite.+?</c3sqlite>',$mode,'plugin_c3sqlite_c3sqlite');
//        $this->Lexer->addEntryPattern('<FIXME>',$mode,'plugin_c3sqlite_c3sqlite');
    }

//    public function postConnect() {
      // $this->Lexer->addExitPattern('</C3SQLITE>','plugin_c3sqlite_c3sqlite');
//    }

    /**
     * Handle matches of the c3sqlite syntax
     *
     * @param string $match The match of the syntax
     * @param int    $state The state of the handler
     * @param int    $pos The position in the document
     * @param Doku_Handler    $handler The handler
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler &$handler){
        $match = substr(trim($match), 9, -11);
        $data = array();
        
        list($opts, $c3data) = explode('>', $match);
        
        $c3data = explode("\n", $c3data);
	$c3data = implode("", array_map(trim, $c3data));
        $data['c3opts']['data'] = $c3data;
        
	$data['c3opts']['width'] = $this->getConf('width');
        $data['c3opts']['height'] = $this->getConf('height');
        $data['c3opts']['align'] = $this->getConf('align');
        
        $opts = str_replace('>', '', $opts);
        $opts = trim($opts);
        $opts = explode('&', $opts);
        foreach($opts as $opt) {
	    if (preg_match('/^data/', $opt)) {
		$opt = explode('=', $opt);
		$opt = explode(':', $opt[1]);
		$data['dbname'] = $opt[0]; 
		$data['query'] = $opt[1]; 
	    } else if (preg_match('/^renderAs/', $opt)) {
		$rend = explode('=', $opt);
		$data['render'] =$rend[1];
	    } else if (preg_match('/^replace/', $opt)) {
		if (preg_match('/^replace{(.*)}/', $opt, $matches)) {
		    $opt = explode('|', $matches[1]);
		    foreach ($opt as $replace) {
			$replace = explode('=', $replace);
			$data['replace'][$replace[0]] = $replace[1];
		    }
		}
	    } else if (preg_match('/^width/', $opt)) {
		$rend = explode('=', $opt);
		$data['c3opts']['width'] = $rend[1];
	    } else if (preg_match('/^height/', $opt)) {
		$rend = explode('=', $opt);
		$data['c3opts']['height'] = $rend[1];
	    } else if (preg_match('/^align/', $opt)) {
		$rend = explode('=', $opt);
		$data['c3opts']['align'] = $rend[1];
	    } 
        }
        
        return $data;
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer &$renderer, $data) {
        if($mode != 'xhtml') return false;
        //var_dump($data);
        $result = $this->_connect($data);
        $rend = explode('|',$data['render']);
        
	$renderer->doc .= "<div class=\"table sectionedit1\">";
	
	foreach ($rend as $renderItem) {
	    if (preg_match("/^TABLE/", $renderItem)) {
		$this->_table($result, $renderer);
	    } else if (preg_match("/^XLS/", $renderItem)) {
		$this->_xls($result, $data, $renderer);
	    } else if (preg_match("/^CHART/", $renderItem)) {
		$c3Data = $this->_c3Data($result);
		$this->_c3Chart($c3Data, $data, $renderer);
	    } else {
		$this->_table($result, $renderer);
		$this->_xls($result, $data, $renderer, true);
	    }
	}
	$renderer->doc .= "</div>";
        return true;
    }
    
   
    function _c3Data($result) {
	$c3Data = array();
	$rows = array();
	$data = array();
	
	foreach($result as $row) {
	    foreach($row as $key => $value) {
		$rows[$key][]= $value; 
	    }
	}
	
	foreach($rows as $key => $row) {
	    $d = array();
	    $d[0] = "'".$key."'";
	    foreach($rows[$key] as $col) {
		$d[] = $col;
	    }
	    $data[] = $d;
	}
	
	$c3Data['data']['columns'] = $data;
	return $c3Data;
    }  
    
    function _c3Chart($c3data, $data, $renderer) {
	$opts = $data['c3opts'];
	
// 	$c3data = explode("\n", $c3data); //looks like not needed any more it is already json.
//      $c3data = implode("", array_map(trim, $c3data));
        
        $c3data = json_encode($c3data);        
        if($c3data[0]=='{') $c3data = substr($c3data, 1, -2);
        
        $data['c3opts']['data'] = preg_replace('/}$/', '', $data['c3opts']['data']);
        
        $c3data .= ','.$data['c3opts']['data'];
        $c3data .= '}';
        $c3data = str_replace('"','', $c3data);
	$c3data = str_replace(':',' : ', $c3data);
//$renderer->doc .= $c3data;
        $chartid = uniqid('__c3chart_');

        $c3data = '{bindto: "#'.$chartid.'",'.$c3data.'}';
        $c3data = str_replace('"',"'", $c3data);

        $s = '';
        $c = '';
        foreach($opts as $n => $v) {
            if(in_array($n, array('width','height')) && $v) {
                $s .= $n.':'.hsc($v).';';
            } elseif($n=='align' && in_array($v, array('left','right','center'))) {
                $c = 'media'.$v;
            }
        }
        if($s) $s = ' style="'.$s.'"';
        if($c) $c = ' class="'.$c.'"';
        
        $renderer->doc .= '<div class ="test" id="'.$chartid.'"'.$c.$s.'"></div>'."\n";
        $renderer->doc .= '<script type="text/javascript">var chart = c3.generate('.$c3data.');</script>'."\n";
    }
    
    function _table($result, $renderer) {
	
	if (!empty($result)) {
	    $renderer->doc .= "<table class=\"inline\">";
	    
	    $ths = array_keys($result[0]);
	    $renderer->doc .= "<tr>";
	    foreach($ths as $th) {
		$renderer->doc .= "<th>".hsc($th)."</th>";
	    }
	    $renderer->doc .= "</tr>";
	   
	    foreach($result as $row) {
		$renderer->doc .= "<tr>";
		$tds = array_values($row);
		foreach($tds as $td) {
		    $renderer->doc .= "<td>".hsc($td)."</td>";
		}
		$renderer->doc .= "</tr>";
	    }
	    $renderer->doc .= "</table>";
	
	} else {
	    $renderer->doc .= "<b>".$this->getLang('nodata')."</b>";
	}
	
    }
    
    function _xls($result, $data, $renderer, $altLabel=false) {
	global $conf;
	$text ='';
	if (!empty($result)) {
	    $ths = array_keys($result[0]);
	    foreach($ths as $th) {
		$text .= "\"".$th."\""."\t";
	    }
	    
	    $text .= "\n";
	    
	    foreach($result as $row) {
		$tds = array_values($row);
		foreach($tds as $td) {
		    $text .= "\"$td\"\t";
		}
		$text .= "\n";
	    }
	    
	    $saveto = strtolower(DOKU_INC.$conf['savedir'].'/media/'.$this->getConf('xlsfolder').'/'.$data['dbname'].'/'.$data['query'].'.xls');
	    //var_dump($saveto);
	    
	    io_saveFile($saveto, $text);
	    
	    $link['target'] = $conf['target']['extern'];
	    $link['style']  = '';
	    $link['pre']    = '';
	    $link['suf']    = '';
	    $link['more']   = '';
	    $link['class']  = 'media';
	    $link['url'] = ml(str_replace('/',':',$this->getConf('xlsfolder').':'.$data['dbname'].':'.$data['query'].'.xls'));
	    $link['name']   = $altLabel?$this->getLang("alternativeLabel"):strtolower($data['query'].'.xls');
	    $link['title']  = $renderer->_xmlEntities($link['url']);
	    if($conf['relnofollow']) $link['more'] .= ' rel="nofollow"';

	    list($ext,$mime) = mimetype(basename($saveto));
	    $link['class'] .= ' mediafile mf_'.$ext;

	    //output formatted
	    $renderer->doc .= $renderer->_formatLink($link);
        } 
    }
    
    function _connect($data) {
	
	$queryFile = DOKU_INC.$this->getConf('querypath').'/'.$data['dbname'].'/'.$data['query'].'.txt';;
	$query = io_readFile($queryFile);
	
	if (!empty($data['replace'])) {
	    foreach($data['replace'] as $replace => $by) {
		$query = str_replace($replace, $by, $query);
	    }
	}
	//var_dump($query);
	
	$DBI = plugin_load('helper', 'sqlite');
	if(!$DBI->init($data['dbname'], '')) return;
	$res = $DBI->query("$query;");
        if($res === false) return;
        $result = $DBI->res2arr($res);
	
	if (!$result) {
	    msg($this->getLang('queryfail'),-1);
	    exit;
	}
	
	return $result;
    }
}

// vim:ts=4:sw=4:et:
