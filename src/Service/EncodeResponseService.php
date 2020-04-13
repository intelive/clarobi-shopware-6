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

    /**
     * EncodeResponse constructor.
     *
     * @param $secret
     * @param $license
     */
    public function __construct(ClarobiConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * @param array $data
     * @param string $entityName
     * @param null $type
     * @return array
     */
    public function encodeResponse($data, $entityName, $type = null): array
    {
        $responseIsEncoded = $responseIsCompressed = false;

        // Encode and compress the data only if we have it
        if (!empty($data)) {
            $encoded = EncodeDecode::encode($data, $this->configService->apiSecret);

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

        return [
            'isEncoded' => $responseIsEncoded,
            'isCompressed' => $responseIsCompressed,
            'data' => $data,
            'license_key' => $this->configService->apiLicense,
            'entity' => $entityName,
            'type' => ($type ? $type : 'SYNC')
        ];
    }
}
