<?php declare(strict_types=1);

namespace Clarobi\Service;

use Clarobi\Utils\EncodeDecode;

/**
 * Class EncodeResponse
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
     * @param ClarobiConfigService $configService
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
    public function encodeResponse($data, $entityName, $lastId = 0, $type = null)
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
            'license_key' => $this->apiLicense,
//            'rawData' => $originalData,
            'data' => $data,
            'entity' => $entityName,
            'type' => ($type ? $type : 'SYNC')
        ];
        if ($encodedResponse['type'] == 'SYNC') {
            $encodedResponse['lastId'] = $lastId;
        }

        return $encodedResponse;
    }
}
