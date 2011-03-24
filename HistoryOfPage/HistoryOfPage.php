<?php
/**
 *
 * @get the history of a page
 * @return string
 * (c)2011 M. Deri Taufan 
 * version 0.1
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

$wgExtensionCredits['parserhook'][] = array(
        'path' => __FILE__,
        'name' => "HistoryOfPage",
        'description' => "The extension that returns link(s) of non-minor changes to a page",
        'version' => 0.1, 
        'author' => "M. Deri Taufan",
        'url' => "http://wiki.science.ru.nl/mms",
);
# Define a setup function
$wgHooks['ParserFirstCallInit'][] = 'hpParserFunction_Setup';
# Add a hook to initialise the magic word
$wgHooks['LanguageGetMagic'][]       = 'hpParserFunction_Magic';

function hpParserFunction_Setup ( &$parser ) {
    $parser->setFunctionHook( 'hop', 'hpParserFunction_Render' );
    return true;
}

function hpParserFunction_Magic ( &$magicWords, $langCode ) {
    $magicWords['hop'] = array( 0, 'hop' );
    return true;
}

function hpParserFunction_Render ( $parser, $param1='' ) {
    $revPage = '';
    $outputLink = '';
    $pageTitle = '';
    
    $protocol = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
    $url = $protocol.'://'.$_SERVER['HTTP_HOST'];
    
    $dbr = wfGetDB( DB_SLAVE );
    $res = $dbr->selectRow(
        'revision',                                   // $table
        array( 'rev_page' ),            // $vars (columns of the table)
        "rev_id=$param1",                              // &conds
        __METHOD__,                                   // $fname = 'Database::select',
        array( 'ORDER BY' => "rev_id ASC" )        // $options = array()
    );
        $revPage = $res->rev_page;
    
    //query the page title based on page_id
    $res = $dbr->selectRow(
        'page',
        array( 'page_id','page_title' ),
        "page_id=$revPage",
        __METHOD__,
        array( 'ORDER BY' => "page_id ASC" )
    );
    $pageTitle = $res->page_title;
    
    //query timestamp of a non-minor change
    $res = $dbr->select(
        'revision',
        array( 'rev_timestamp','rev_id' ),
        "rev_page=$revPage AND rev_minor_edit=0",
        __METHOD__,
        array( 'ORDER BY' => "rev_id ASC" )
    );
    foreach( $res as $row ) {
        if ($outputLink=='') {
            $outputLink = $url."/index.php?title=$pageTitle&oldid=$row->rev_id (Revision as of ".wfTimestamp(TS_RFC2822,$row->rev_timestamp).")";
        }
        else {
            $outputLink = $outputLink."<br>".$outputLink = $url."/index.php?title=$pageTitle&oldid=$row->rev_id (Revision as of ".wfTimestamp(TS_RFC2822,$row->rev_timestamp).")";
        }
        
    }
    
    return $outputLink;
}