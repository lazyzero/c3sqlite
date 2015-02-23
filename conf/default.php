<?php
/**
 * Default settings for the query plugin
 *
 * @author Christian Moll <christian@chrmoll.de>
 */

$conf['querypath']         = 'lib/plugins/c3sqlite/query';   // the location of the template file
$conf['xlsfolder']         = 'xlsexport';

$conf['forbiddenCommands'] = 'DROP, CREATE, UPDATE, DELETE, TRUNCATE, INSERT, ALTER';

$conf['url_yaml']    = 'js-yaml.min.js';
$conf['url_d3']    = 'd3.min.js';
$conf['url_c3']    = 'c3.min.js';
$conf['url_c3_css']    = 'c3.css';
$conf['width']    = '';
$conf['height']    = '';
$conf['align']    = 'center';
