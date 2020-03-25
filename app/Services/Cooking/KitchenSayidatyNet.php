<?php


namespace App\Services\Cooking;

use App\Services\Proxies\Proxy;
use DOMDocument;
use DOMXPath;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Psr\Http\Message\ResponseInterface;

class KitchenSayidatyNet
{
    public $states = [];

    private $client = null;
    /** @var ResponseInterface */
    private $response;
    private $tries = 0;
    public $httpCode;
    /**
     * @var DOMDocument
     */
    private $dom;
    private $link;

    /**
     * EZLocal constructor.
     * https://kitchen.sayidaty.net/node/50
     * @param $link
     * @throws FileNotFoundException
     */
    public function __construct($link)
    {
        $this->tries = (new Proxy())->count() - 1;
        $this->loadLink($link);

        $this->link = $link;
    }

    /**
     * @param $link
     * @return bool
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function loadLink($link)
    {
        try {
            $jar = new CookieJar();
            $this->client = new Client(
                [
                    'headers' => [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                        'Accept-Encoding: gzip, deflate, br',
                        'Accept-Language: en-US,en;q=0.9,ar;q=0.8,de;q=0.7',
                        'Cache-Control: max-age=0',
                        'Connection: keep-alive',
                        'Cookie: SL_GWPT_Show_Hide_tmp=1; SL_wptGlobTipTmp=1; PHPSESSID=r6lrfo7ms2is27bj4t5knrrkp7',
                        'Host: www.cars-data.com',
                        'Sec-Fetch-Dest: document',
                        'Sec-Fetch-Mode: navigate',
                        'Sec-Fetch-Site: none',
                        'Sec-Fetch-User: ?1',
                        'Upgrade-Insecure-Requests: 1',
                        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36'
                    ],
                    'verify' => false,
                    'allow_redirects' => ['max' => 10]
                ]
            );
            $proxy = (new Proxy())->randomProxy($this->tries);

            $this->response = $this->client->get($link, ['verify' => false, 'cookies' => $jar]);
            $this->httpCode = $this->response->getStatusCode();
            return true;
        } catch (RequestException $exception) {

            $response = $exception->getResponse();

            if (method_exists($response, 'getStatusCode')) {
                $code = $response->getStatusCode();
                if ($code == 404 || $code >= 500) {
                    $this->httpCode = $response->getStatusCode();
                    return true;
                }

            }

            $this->tries -= 1;
            if ($this->tries < 0) {
                throw  $exception;
            }

            $this->loadLink($link);

        }
    }

    /**
     * @return object
     */
    public function getLinkContent()
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($this->response->getBody()->getContents());
        libxml_clear_errors();

        return $this->getGeneralData($dom);
    }

    /**
     * @param DOMDocument $dom
     *
     * @return object
     */
    private function getGeneralData(DOMDocument $dom)
    {
        $data = [];
        $DomXpath = new DOMXPath($dom);

        $data['name'] = trim($DomXpath->query('//div[@class="entry-title"]', null)[0]->nodeValue ?? '');
        $data['desc'] = $DomXpath->query('//div[@class="entry-intro"]/p/span', null)[0]->nodeValue ?? '';
        $data['img'] = $DomXpath->query('//div[@class="entry-media-inner"]/img', null)[0]->getAttribute('src')?? '';
        $ingredients = explode('-',$DomXpath->query('//div[@data-name="ingredients"]/div[@class="section-content"]', null)[0]->nodeValue ?? '');

        $categories=explode(',', $DomXpath->query('//div[@class="recipe-more-info"]/div/span', null)[0]->nodeValue ?? '');

        foreach ($categories as $category){
            $data['categories'][] = trim($category);
        }
        foreach ($ingredients as $ingredient){
            if(trim($ingredient)!=''){

                $data['ingredients'][] = preg_replace("/\r|\n/", "", trim($ingredient));

            }
        }

        return (object)$data;
    }
}