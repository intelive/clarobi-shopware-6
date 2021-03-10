<?php declare(strict_types=1);

namespace ClarobiClarobi\Service;

use ClarobiClarobi\Utils\EncodeDecode;

/**
 * Class EncodeResponseService
 *
 * @package ClarobiClarobi\Service
 * @author Georgiana Camelia Gitan (g.gitan@interlive.ro)
 */
class EncodeResponseService
{
    /** @var ClarobiConfigService $configService */
    protected $configService;
    /** @var string $apiSecret */
    protected $apiSecret;
    /** @var string $apiLicense */
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
     * Encode and compress data.
     *
     * @param array $data
     * @param string $entityName
     * @param $lastId
     * @param null $type
     * @return array
     * @throws \Exception
     */
    public function encodeResponse($data, $entityName, $lastId, $type = null)
    {
        $responseIsEncoded = $responseIsCompressed = false;

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
