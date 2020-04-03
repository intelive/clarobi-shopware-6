<?php declare(strict_types=1);

namespace Clarobi\Core\Framework\Routing;

use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\AbstractRouteScope;
use Shopware\Core\Framework\Routing\ApiContextRouteScopeDependant;
use Shopware\Core\PlatformRequest;
use Symfony\Component\HttpFoundation\Request;

class ClarobiRouteScope extends AbstractRouteScope implements ApiContextRouteScopeDependant
{
    public const ID = 'clarobi';

    /**
     * @var string[]
     */
    protected $allowedPaths = ['clarobi'];

    public function isAllowed(Request $request): bool
    {

        if (!$request->attributes->get('auth_required', false)) {
            return true;
        }

        /** @var Context $context */
        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);
        $authRequired = $request->attributes->get('auth_required', false);
        $source = $context->getSource();

        if (!$authRequired) {
            return $source instanceof SystemSource || $source instanceof AdminApiSource;
        }

        return $context->getSource() instanceof AdminApiSource;
    }

    public function getId(): string
    {
        return self::ID;
    }
}
