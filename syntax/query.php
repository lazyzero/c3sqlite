<?php
/**
 * DokuWiki Plugin query (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Christian Moll <christian.moll@tudor.lu>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_query_query extends DokuWiki_Syntax_Plugin {
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
        $this->Lexer->addSpecialPattern('<QUERY.+?</QUERY>',$mode,'plugin_query_query');
//        $this->Lexer->addEntryPattern('<FIXME>',$mode,'plugin_query_query');
    }

//    public function postConnect() {
      // $this->Lexer->addExitPattern('</QUERY>','plugin_query_query');
//    }

    /**
     * Handle matches of the query syntax
     *
     * @param string $match The match of the syntax
     * @param int    $state The state of the handler
     * @param int    $pos The position in the document
     * @param Doku_Handler    $handler The handler
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler &$handler){
        $match = substr(trim($match), 6, -8);
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
		$data['study'] = $opt[0]; 
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
	    } else if (preg_match("/^CHART_SWAP/", $renderItem)) {
		$c3Data = $this->_c3DataSwap($result);    
		$this->_c3Chart($c3Data, $data, $renderer);
	    } else if (preg_match("/^CHART_CATEGORY/", $renderItem)) {
		$c3Data = $this->_c3Data_category($result);    
		$this->_c3Chart($c3Data, $data, $renderer);
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
    
    function _c3DataSwap($result) {
	$c3Data = array();
	$columns = array();
	
	$cols = count(pg_fetch_array($result, 0));
	for ($i = 0; $i < $cols/2; $i++) {
	    $columns[$i][] = "'".pg_field_name($result, $i)."'";
	}
	
	while ($row = pg_fetch_row($result)) {
	    for ($i = 0; $i < $cols/2; $i++) {
	    	$columns[$i][] = $row[$i];
	    }
	}
	$c3Data['data']['columns'] = $columns;
	
	return $c3Data;
    }
    
    function _c3Data($result) {
	$c3Data = array();
	$rows = array();
	
	while ($row = pg_fetch_row($result)) {
	    $row[0] = "'".$row[0]."'";
	    $rows[] = $row;
	}
	
	$c3Data['data']['columns'] = $rows;
	return $c3Data;
    }
    
    function _c3Data_category($result) {
	$c3Data = array();
	$rows = array();
	$category =array();
	
	while ($row = pg_fetch_row($result)) {
	    list($rows, $categories) = $this->categorisedData($rows, $row, $categories);
	}

	$c3Data['axis']['x']['type'] = "'category'";
	$c3Data['axis']['x']['categories'] = $categories;
	
	$c3Data['data']['columns'] = $rows;
	return $c3Data;
    }
    
    function categorisedData($rows, $row, $categories) {
	$isIn = 0;
	//if first columns already in categories array
	if (!empty($categories)) {
	   if (!in_array("'".$row[0]."'", $categories)) {
	    $categories[] = "'".$row[0]."'";
	    }
	} else {
	    $categories[] = "'".$row[0]."'";
	}
	//remove first column
	for ($i = 1; $i < sizeof($row); $i++) {
	    $row_new[$i-1] = $row[$i];
	}
	//convert first column to string
	$row_new[0] = "'".$row_new[0]."'";
	
	//check if first column already as lable in data
	if (empty($rows)) {
	    $rows[] = $row_new;
	} else {
	    $isIn = 0;
	    $index = 0;
	    foreach ($rows as $line) {
		if ($line[0] == $row_new[0]) {
 		    $line[] = $row_new[1];
		    $rows[$index] = $line;
		    //var_dump($rows[$index]);var_dump($line[0]);echo '<br>';
		    $isIn = 1;
		}
		$index++;
	    }
	    
	    if ($isIn == 0) {
		//var_dump( $row_new); echo '<br>';
		$rows[] = $row_new;
	    }
	}
	
	
        
	return array($rows, $categories);
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
	if (pg_num_rows($result) > 0) {
	    $renderer->doc .= "<table class=\"inline\">";
	    $renderer->doc .= "<tbody><tr class=\"row0\">";
	    
	    $cols = count(pg_fetch_array($result, 0));
	    for ($i = 0; $i < $cols/2; $i++) {
		$renderer->doc .= "<th class=\"col$i\">".pg_field_name($result, $i)."</th>";
	    }
	    $renderer->doc .= "</tr>";
	    $i=1;
	    while ($row = pg_fetch_row($result)) {
		$renderer->doc .= "<tr class=\"row$i\">\n";
		$j=0;
		foreach($row as $value) {
		    if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}/i',$value)) {
			$value = '<a class="mail" title="'.$value.'" href="mailto:'.$value.'">'.$value.'</a>';
		    }
		    $renderer->doc .= "<td class=\"col$j\">$value </td>\n";
		}
		$renderer->doc .= "</tr>\n";
		$i++;
	    }
	    
	    $renderer->doc .= "</tbody></table>";
	} else {
	    $renderer->doc .= "<b>".$this->getLang('nodata')."</b>";
	}
	
    }
    
    function _xls($result, $data, $renderer, $altLabel=false) {
	global $conf;
	$text ='';
	if (pg_num_rows($result) > 0) {
	    $cols = count(pg_fetch_array($result, 0));
	    for ($i = 0; $i < $cols/2; $i++) {
		$text .= "\"".pg_field_name($result, $i)."\""."\t";
	    }
	    $text .= "\n";
	    while ($row = pg_fetch_row($result)) {
		foreach($row as $value) {
		    $text .= "\"$value\"\t";
		}
		$text .= "\n";
	    }
	    
	    $saveto = strtolower(DOKU_INC.$conf['savedir'].'/media/'.$this->getConf('xlsfolder').'/'.$data['study'].'/'.$data['query'].'.xls');
	    //var_dump($saveto);
	    
	    io_saveFile($saveto, $text);
	    
	    $link['target'] = $conf['target']['extern'];
	    $link['style']  = '';
	    $link['pre']    = '';
	    $link['suf']    = '';
	    $link['more']   = '';
	    $link['class']  = 'media';
	    $link['url'] = ml(str_replace('/',':',$this->getConf('xlsfolder').':'.$data['study'].':'.$data['query'].'.xls'));
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
	$host = $this->getConf('host');
	$port = $this->getConf('port');
	$dbname = $this->getConf('dbname');
	$dbuser = $this->getConf('dbuser');
	$dbpassword = $this->getConf('dbpassword');
		
	$queryFile = DOKU_INC.$this->getConf('querypath').'/'.$data['study'].'/'.$data['query'].'.txt';;
	$query = io_readFile($queryFile);
	
	if (!empty($data['replace'])) {
	    foreach($data['replace'] as $replace => $by) {
		$query = str_replace($replace, $by, $query);
	    }
	}
	//var_dump($query);
	
	$dbconnection = pg_connect("host=".$host." port=".$port." dbname=".$dbname." user=".$dbuser." password=".$dbpassword);
	if (!$dbconnection) {
	    msg($this->getLang('connectionfail'),-1);
	    exit;
	}
	
	$result = pg_query($dbconnection, $query);
	
	if (!$result) {
	    msg($this->getLang('queryfail'),-1);
	    exit;
	}
	
	return $result;
    }
}

// vim:ts=4:sw=4:et:
