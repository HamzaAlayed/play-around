<?php


namespace App\Services\Proxies;


use GuzzleHttp\Client;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Storage;

/**
 * Class Proxy
 * @package App\Services\Proxies
 */
class Proxy
{


    /**
     * @var Client
     */
    private $client;
    /**
     * @var string
     */
    private $proxy;

    function __construct()
    {
        if (!Storage::exists('proxies/proxy.json')) {
            $this->refreshList();
        }

    }

    /**
     * @return bool
     */
    function save()
    {
        return Storage::put('proxies/proxy.json', $this->proxy);
    }

    /**
     * @return mixed
     * @throws FileNotFoundException
     */
    private function loadProxies()
    {
        return json_decode(Storage::get('proxies/proxy.json'));
    }

    /**
     * @return mixed
     * @throws FileNotFoundException
     */
    public function count()
    {
        return count($this->loadProxies()->data);
    }
    /**
     * @param $key
     * @return mixed
     * @throws FileNotFoundException
     */
    public function randomProxy($key)
    {
        $proxyList = $this->loadProxies()->data;
        return $proxyList[$key??0]->ip.":".$proxyList[$key??0]->port;
    }

    public function refreshList()
    {
        $this->client = new Client(
            [
                'verify' => false,
                'allow_redirects' => ['max' => 10]
            ]
        );

        $response = $this->client->get('https://proxy11.com/api/proxy.json?key=MjM4.XS2P2w.vyYMwp17M85qF-dtu3ROJz9fY0g');
        $this->proxy = $response->getBody()->getContents();
        $this->save();
    }

}
