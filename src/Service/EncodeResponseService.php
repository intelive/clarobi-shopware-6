<?php declare(strict_types=1);

namespace Clarobi\Service;

use Clarobi\Utils\EncodeDecode;

/**
 * Class EncodeResponse
 *
 * @package Clarobi\Utils
 */
class EncodeResponseService
{
    /**
     * @var ClarobiConfigService
     */
    protected $configService;

    protected $apiSecret;
    protected $apiLicense;

    /**
     * EncodeResponse constructor.
     *
     * @param $secret
     * @param $license
     */
    public function __construct(ClarobiConfigService $configService)
    {
        $this->configService = $configService;

        $configs = $this->configService->getConfigs();
        $this->apiSecret = $configs['apiSecret'];
        $this->apiLicense = $configs['apiLicense'];
    }

    /**
     * @param array $data
     * @param string $entityName
     * @param null $type
     * @return array
     */
    public function encodeResponse($data, $entityName, $lastId = 0, $type = null): array
    {
        $responseIsEncoded = $responseIsCompressed = false;

        $originalData = $data;

        // Encode and compress the data only if we have it
        if (!empty($data)) {
            $encoded = EncodeDecode::encode($data, $this->apiSecret);

            if (is_string($encoded)) {
                $responseIsEncoded = true;
                $data = $encoded;
            }

            $compressed = EncodeDecode::compress($encoded);
            if ($compressed) {
                $responseIsCompressed = true;
                $data = $compressed;
            }
        }
        $encodedResponse = [
            'isEncoded' => $responseIsEncoded,
            'isCompressed' => $responseIsCompressed,
            'rawData' => $originalData,
//            'data' => $data,
            'license_key' => $this->apiLicense,
            'entity' => $entityName,
            'type' => ($type ? $type : 'SYNC')
        ];
        if($lastId){
            $encodedResponse['lastId'] = $lastId;
        }

        return $encodedResponse;
    }
}
