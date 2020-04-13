<?php declare(strict_types=1);

namespace Clarobi\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class ClarobiConfig
 * @package Clarobi\Service
 */
class ClarobiConfigService
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;
    public $apiLicense = '';
    public $apiKey = '';
    public $apiSecret = '';

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
//        $this->getConfigs();
        $this->apiLicense = $this->systemConfigService->get('Clarobi.config.apiLicense');
        $this->apiKey = $this->systemConfigService->get('Clarobi.config.apiKey');
        $this->apiSecret = $this->systemConfigService->get('Clarobi.config.apiSecret');
    }

    public function getConfigs()
    {
        return [
            'apiLicense' => $this->systemConfigService->get('Clarobi.config.apiLicense'),
            'apiKey' => $this->systemConfigService->get('Clarobi.config.apiKey'),
            'apiSecret' => $this->systemConfigService->get('Clarobi.config.apiSecret')
        ];
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function verifyRequestToken(Request $request)
    {
        $headerToken = $request->headers->get('X-Claro-TOKEN');
        if (!$headerToken) {
            throw new BadRequestHttpException('\'X-Claro-TOKEN\' header missing from request!');
        }

        if (!$this->apiKey) {
            throw new \Exception('No API KEY provided in plugin configurations!');
        }

        if ($headerToken !== $this->apiKey) {
            throw new \Exception(
                'Provided token: ' . $headerToken
                . ' not matching the API KEY saved in plugin configurations: ' . $this->apiKey
            );
        }
    }
}
