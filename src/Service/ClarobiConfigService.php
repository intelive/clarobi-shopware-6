<?php declare(strict_types=1);

namespace Clarobi\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

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
    }

    public function getConfigs()
    {
        return [
            'apiLicense' => $this->systemConfigService->get('Clarobi.config.apiLicense'),
            'apiKey' => $this->systemConfigService->get('Clarobi.config.apiKey'),
            'apiSecret' => $this->systemConfigService->get('Clarobi.config.apiSecret')
        ];
    }
}
