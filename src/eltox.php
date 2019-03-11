<?php
/**
 * Created by PhpStorm.
 * User: erest
 * Date: 11.03.2019
 * Time: 22:05
 */

class eltox
{
    const PREFIX    = 'eltox';
    const URL       = "https://eltox.ru/registry/procedure/page/";
    const STORAGE   = "http://storage.eltox.ru/";
    const SITE      = "https://eltox.ru";

    private $web;
    private $options;

    function __construct($options = null) {
        $this->web = new \GuzzleHttp\Client([
            'base_uri' => self::SITE,
        ]);
        $this->options = $options;
    }


    /**
     * @param $qp QueryPath\DOMQuery part of page
     * @param $name string
     * @param string $type default string, can be date, int, no-number, float
     * @return string $text field text
     */
    private function get_field($qp, $name, $type = 'string') {
        $a = $qp->find('tr')->filterPreg("#$name#usi")->text();
        $text = $qp->find('tr')->filterPreg("#$name#umi")->find('td')->last()->text();
        $text = trim($text);

        switch ($type) {
            case 'int':
                $text = preg_replace('#[^\d]+#usi', '', $text);
                $text = intval($text);
                break;
            case 'no-number':
                $text = preg_replace('#[\d]+#usi', '', $text);
                break;
            case 'date':
                $text = preg_replace('#[^\d\.\:\s]+#usi', '', $text);
                $text =  date(DateTime::ATOM,strtotime($text));
                break;
            case 'float':
                $text = preg_replace('#[^\d\.\,]+#usi', '', $text);
                $text = floatval($text);
                break;
            default: break;
        }

        return $text;
    }


    /**
     * @param $href string link
     * @return \QueryPath\DOMQuery querypath
     */
    private function get_content($href) {
        print_r("Get href $href \n");
        $response = $this->web->get($href);
        $html = (string)$response->getBody();
        return html5qp($html);
    }


    /**
     * @param $href string link
     * @return string make relative links full again
     */
    private function prefix_href($href) {
        if (empty(str_replace('#', '', $href))) return '';
        if (preg_match('/mailto/i', $href)) return '';

        $parsed_url = parse_url(self::SITE);

        $host_prefix = $parsed_url['scheme'].'://' . $parsed_url['host'];

        $path_prefix = $host_prefix . @$parsed_url['path'];
        $path_prefix = preg_replace('/\/$/', '', $path_prefix);

        if (substr($href, 0, 2) == '//')
            $href = 'http:' . $href;

        if (substr($href, 0, 4) != 'http')
            if ($href[0] == '/')
                $href = $host_prefix . $href;
            else {
                $href = preg_replace('/^\.\//', '', $href);
                $href = $path_prefix . '/' . $href;
            }

        return $href;
    }


    /**
     * @param $qp QueryPath\DOMQuery page querypath
     * @return mixed array of parsed data
     */
    private function parse_content($qp) {

        $htmlTender = $qp->html5();
        $data['canceled'] = preg_match('#Дата отмены\:?\s*\d{2}\.\d{2}\.\d{4}#usi', $htmlTender);

        $table = $qp->find('#tab-basic .detail-view')->first();
        $organizer_table = $table->find('table.table-condensed')->first();
        $customer_table = $table->find('table.table-condensed')->last();

        $tender = [
            'name' => $this->get_field($table, 'Наименован.*?процедур'),
            'number' => self::PREFIX . $this->get_field($table, '№ извещения\s?$'),
            'number_gos' => $this->get_field($table, '№ извещения ООС'),
            'contact' => $this->get_field($table, 'Контактное лицо'),
            'phone' => $this->get_field($table, 'Телефон'),
            'email' => $this->get_field($table, 'Почта'),
            'publication_date' => $this->get_field($table, 'Размещено на ООС', 'date'),
            'finish_date' => $this->get_field($table, 'Дата и время окончания подачи заявок', 'date'),
            'consideration_date' => $this->get_field($table, 'Дата вскрытия конвертов', 'date'),
            'completed_date' => $this->get_field($table, 'Дата и время подведения итогов', 'date')
        ];

        $organizer = [
            'name' => $this->get_field($organizer_table, 'Наименование'),
            'inn' => $this->get_field($organizer_table, 'ИНН'),
            'postal_address' => $this->get_field($organizer_table, 'Адрес регистрации'),
            'factual_address' => $this->get_field($organizer_table, 'Фактический адрес')
        ];

        $customer = [
            'name' => $this->get_field($customer_table, 'Наименование'),
            'inn' => $this->get_field($customer_table, 'ИНН'),
            'postal_address' => $this->get_field($customer_table, 'Адрес регистрации'),
            'factual_address' => $this->get_field($customer_table, 'Фактический адрес')
        ];

        $lot_table = $qp->find('#tab-lot .detail-view')->first();

        $lot = [
            'price' => $this->get_field($lot_table, 'Начальная цена', 'float'),
            'name' => $this->get_field($lot_table, 'Предмет договора'),
            'place' => $this->get_field($lot_table, 'Место поставки'),
            'okpd2' => $this->get_field($lot_table, 'ОКПД2'),
            'term' => $this->get_field($lot_table, 'Сроки.период поставки')
        ];

        $documents = [];
        $documents_list = preg_match("/\.list\( ?([^\]]+\])/usi", $htmlTender, $match)?$match[1]:null;
        if (!empty($documents_list)){
            $documents_list = json_decode($documents_list, true);
            foreach($documents_list as $document){
                $doc = [];
                $doc['title']       =
                $doc['description'] = preg_match('#(^.+)\.#usi', $document['alias'], $m)? $m[1]: $document['alias'];
                $doc['href']        = self::STORAGE . $document['path'] . '/' . $document['name'];
                $documents[] = $doc;
            }
        }

        return ['tender' => $tender, 'organizer' =>  $organizer, 'customer' =>  $customer,'lot' =>  $lot, 'docs' =>  $documents];
    }


    /**
     * @param $href string tender card link
     * @return array all parsed data
     */
    protected function parse_tender($href) {
        $qp = $this->get_content($href);
        $data = $this->parse_content($qp);
        $url = $this->prefix_href($href);

        return compact('url') + $data;
    }


    /**
     * @param $filter
     * @param int $page_number
     * @return QueryPath\DOMQuery qp querypath of page
     */
    private function get_filtered_list($filter, $page_number = 1) {

        $query = http_build_query([
            'id'              => @$filter['id']?:'',
            'procedure'       => '',
            'oos_id'          => '',
            'company'         => '',
            'inn'             => '',
            'type'            => 0,
            'price_from'      => '',
            'price_to'        => '',
            'published_from'  => (string) @$filter['date_from'],
            'published_to'    => (string) @$filter['date_to'],
            'offer_from'      => '',
            'offer_to'        => '',
            'status'          => '',
        ]);
        $query = preg_replace('/%5B[0-9]+%5D/i', '', $query);

        return htmlqp($this->get_content(self::URL .$page_number. '?' . $query));
    }


    /**
     * @param $qp QueryPath\DOMQuery one page with tender's links
     * @return array array of tenders links
     */
    private function parse_list($qp) {
        $hrefs = [];
        $rows = $qp->find('div.registerBox.procedure-list-item');
        foreach ($rows as $row) {
            $href = $row->find('a[href*=procedure]')->first()->attr('href');
            $hrefs []= $href;
        }

        return $hrefs;
    }


    /**
     * @param int $limit limit pages
     * @param array $filter
     * @return Generator array of tenders urls from page
     */
    private function walk_through_list($limit, $filter = null) {
        print_r('Filter: ');
        print_r($filter);
        $page_number = 1;
        $page_count = 1;
        do {
            $qp = $this->get_filtered_list($filter, $page_number);
            if ($page_number == 1)
                $page_count = preg_match('#page\/(\d+)\?#usi', $qp->find('ul.pagination a[rel="last"]')->first()->attr('href'), $m)?$m[1]:1;

            $page_hrefs = $this->parse_list($qp);

            yield $page_hrefs;

            $page_number++;
            $next_page = ($page_number <= $page_count);
            $next_page &= ($page_number <= $limit) || ($limit == 0);
        } while ($next_page);
    }


    /**
     * @param $hrefs array array with tender's urls
     * @return array parsed data
     */
    private function parse_hrefs($hrefs) {
        $all = [];
        foreach ($hrefs as $href) {
            $data = $this->parse_tender($href);

            if (!empty($data))
                $all[] = $data;
            print_r($data);
        }
        return $all;
    }


    /**
     * entry point
     */
    public function parse() {

        if (isset($this->options->href)) {
            $data = $this->parse_hrefs([$this->options->href]);
            print_r($data);
            // TODO check if exist and add to mongodb or update
            return;
        }

        $filter = [
            'date_from' => date('d.m.Y', strtotime('yesterday')),
            'date_to' => date('d.m.Y', strtotime('now')),
        ];
        $limit = 3;

        if (isset($this->options->all)) {
            $limit = 0;
            $filter = [];
        }

        foreach ($this->walk_through_list($limit, $filter) as $hrefs) {
            $data = $this->parse_hrefs($hrefs);
            // TODO check if exist and add to mongodb or update
        }
    }
}
