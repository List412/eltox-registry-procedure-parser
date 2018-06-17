<?php
/**
 * Created by PhpStorm.
 * User: list412
 * Date: 16.06.18
 * Time: 0:20
 */
require 'Models/Procedure.php';
require 'Models/Document.php';
require 'simple_html_dom.php';

$baseURL = "https://eltox.ru/registry/procedure";
$siteURL = "https://eltox.ru";
$documentsURL = '#tab-attachment';

$content = file_get_html($baseURL);

$procedureList = $content->find('.procedure-list-item');

foreach ($procedureList as $item)
{
    $procedure = new Procedure();

    $link = $item->find('a')[1];

    $procedure->id = explode('/', $link->href)[3];
    $procedure->link = $siteURL . $link->href;
    $procedure->oos = str_replace('№ ООС: ', '', $item->find('span')[2]->innertext);

    $procedurePage = file_get_html($procedure->link);
    $attributesDiv = $procedurePage->find('div[id=tab-basic]')[0];
    $procedure->email = $attributesDiv->nodes[1]->children[11]->children[1]->nodes[0]->innertext;

    $document = new Document();
//    $documentsPage = file_get_html();
    $documentsPage = file_get_html('https://eltox.ru/procedure/read/1329#tab-attachment');
    $documentsDiv = $documentsPage->find('div[id=tab-attachment]', 0);

    $scripts = $documentsPage->find('script');
    $documentsScript = $scripts[25]->nodes[0]->innertext;
    echo $documentsScript;
    parseScript($documentsScript);

}

$content->clear();

function  parseScript(string $src)
{
    $sourceString = $src;

    $downloadRoute_string = 'download_route';
    $list_string = 'list';

    preg_match("/$downloadRoute_string : '(.*?)'/",$sourceString,$m);
    $downLoadRoute = $m[1];

    preg_match("/$list_string\b\((.*?)\);/",$sourceString,$m);
    $list = $m[1];

    $docs = json_decode($list);

    $docsArray = array();

    foreach ($docs as $item)
    {
        $document = new Document();
        $document->name = $item->alias;
        $document->link = $downLoadRoute . '/' . $item->path . '/' . $item->name;
        $docsArray = $document;
    }

    return $docsArray;
}