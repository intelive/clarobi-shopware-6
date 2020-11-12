<?php declare(strict_types=1);

namespace ClarobiClarobi\Service;

use GuzzleHttp\Client;

class ClaroConnectorService
{
    private static $claroLicenseStatusEndpoint = 'https://api.clarobi.com/json_index/check-domain';
    private static $domainQueryParam = 'domain=';

    protected function callClaroApi($url)
    {
        $client = new Client();
        $response = $client->request('GET', $url);
        $stream = $response->getBody();
        $contents = $stream->getContents();

        return json_decode($contents, true);
    }

    public function startClaroCall($domain)
    {
        $url = self::$claroLicenseStatusEndpoint . '?' . self::$domainQueryParam . $domain;

        $result = $this->callClaroApi($url);
        $msg = (isset($result[0]['response']['msg']) ? $result[0]['response']['msg'] : null);
        if ($msg == true) {
            return true;
        }
        return false;
    }
}
