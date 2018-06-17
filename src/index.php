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

$siteURLFilter = 'https://eltox.ru/registry/procedure?id=&procedure=&oos_id=&company=&inn=&type=1&price_from=&price_to=&published_from=&published_to=&offer_from=&offer_to=&status=';

$content = file_get_html($siteURLFilter);

$paginator = $content->find('ul[class=pagination]');
$paginationCount = count($paginator[0]->children);
$pagesCounter = (int)($paginator[0]->children[$paginationCount-3]->nodes[0]->innertext);

$procedureArray = array();

for ($page = 1; $page <= $pagesCounter; $page++) {

    $siteURLFilerPage = "https://eltox.ru/registry/procedure/page/$page?id=&procedure=&oos_id=&company=&inn=&type=1&price_from=&price_to=&published_from=&published_to=&offer_from=&offer_to=&status=";

    $content = file_get_html($siteURLFilerPage);

    $procedureList = $content->find('.procedure-list-item');

    foreach ($procedureList as $item) {
        $procedure = new Procedure();

        $link = $item->find('a')[1];

        $procedure->id = explode('/', $link->href)[3];
        $procedure->link = $siteURL . $link->href;
        $procedure->oos = str_replace('№ ООС: ', '', $item->find('span')[2]->innertext);

        $procedurePage = file_get_html($procedure->link);
        $attributesDiv = $procedurePage->find('div[id=tab-basic]')[0];
        $procedure->email = $attributesDiv->nodes[1]->children[11]->children[1]->nodes[0]->innertext;

        $scripts = $procedurePage->find('script');
        $documentsScript = $scripts[25]->nodes[0]->innertext;

        $procedure->document = parseDocumentsFromScript($documentsScript);

        $procedureArray[] = $procedure;
    }
    echo 'page ' . $page;
}
$content->clear();

function  parseDocumentsFromScript(string $src)
{
    $sourceString = $src;

    $downloadRouteString = 'download_route';
    $listString = 'list';

    preg_match("/$downloadRouteString : '(.*?)'/",$sourceString,$m);
    $downLoadRoute = $m[1];

    preg_match("/$listString\b\((.*?)\);/",$sourceString,$m);
    $list = $m[1];

    $docs = json_decode($list);

    $docsArray = array();

    foreach ($docs as $item)
    {
        $document = new Document();
        $document->name = $item->alias;
        $document->link = $downLoadRoute . '/' . $item->path . '/' . $item->name;
        $docsArray[] = $document;
    }

    return $docsArray;
}