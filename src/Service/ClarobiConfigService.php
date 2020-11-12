<?php declare(strict_types=1);

namespace ClarobiClarobi\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Class ClarobiConfig
 *
 * @package ClarobiClarobi\Service
 */
class ClarobiConfigService
{
    private static $pluginName = 'ClarobiClarobi';
    private static $licence = 'apiLicense';
    private static $key = 'apiKey';
    private static $secret = 'apiSecret';

    /** @var SystemConfigService $systemConfigService */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * Return plugin configurations.
     *
     * @return array
     */
    public function getConfigs()
    {
        return [
            self::$licence => $this->systemConfigService->get(self::$pluginName . '.config.' . self::$licence),
            self::$key => $this->systemConfigService->get(self::$pluginName . '.config.' . self::$key),
            self::$secret => $this->systemConfigService->get(self::$pluginName . '.config.' . self::$secret)
        ];
    }
}
