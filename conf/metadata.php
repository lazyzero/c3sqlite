<?php
/**
 * Options for the query plugin
 *
 * @author Christian Moll <christian@chrmoll.de>
 */


$meta['querypath']    = array('string');//,'_pattern' => '/^(|[a-zA-Z\-]+)$/'); // the location of the template file
$meta['xlsfolder']    = array('string');

$meta['forbiddenCommands']   = array('string');

$meta['url_yaml'] = array('string', '_pattern' => '#^(?:(?:(?:https?:)?/)?/)?(?:[\w.][\w./]*/)?js-yaml(?:\.min)?\.js$#');
$meta['url_d3'] = array('string', '_pattern' => '#^(?:(?:(?:https?:)?/)?/)?(?:[\w.][\w./]*/)?d3(?:\.min)?\.js$#');
$meta['url_c3'] = array('string', '_pattern' => '#^(?:(?:(?:https?:)?/)?/)?(?:[\w.][\w./]*/)?c3(?:\.min)?\.js$#');
$meta['url_c3_css'] = array('string', '_pattern' => '#^(?:(?:(?:https?:)?/)?/)?(?:[\w.][\w./]*/)?c3\.css$#');
$meta['width'] = array('string', '_pattern' => '/^(?:\d+(px|%))?$/');
$meta['height'] = array('string', '_pattern' => '/^(?:\d+(px|%))?$/');
$meta['align'] = array('multichoice', '_choices' => array('none','left','center','right'));
