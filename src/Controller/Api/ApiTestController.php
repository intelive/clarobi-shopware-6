<?php declare(strict_types=1);

namespace ClarobiClarobi\Controller\Api;

use ClarobiClarobi\Service\ClaroConnectorService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ApiTestController
 *
 * @package ClarobiClarobi\Controller\Api
 */
class ApiTestController
{
    /** @var ClaroConnectorService $connectorService */
    protected $connectorService;

    /**
     * ApiTestController constructor.
     *
     * @param ClaroConnectorService $connectorService
     */
    public function __construct(ClaroConnectorService $connectorService)
    {
        $this->connectorService = $connectorService;
    }

    /**
     * @RouteScope(scopes={"administration"})
     * @Route(path="/api/v{version}/_action/clarobi-api-test/verify")
     */
    public function check(Request $request): JsonResponse
    {
        $currentDomain = $request->getHttpHost();
        $success = false;
        if (!empty($currentDomain)) {
            $success = $this->connectorService->startClaroCall($currentDomain);
        }

        return new JsonResponse(['success' => $success]);
    }
}
