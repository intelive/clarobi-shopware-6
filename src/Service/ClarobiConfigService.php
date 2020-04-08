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

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
        $this->getConfigs();
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
     */
    public function verifyRequestToken(Request $request)
    {
        /**
         * @todo move in dedicated class
         */
        if($request->headers){
            throw new BadRequestHttpException('token missing');
        }
        return;
    }
}
