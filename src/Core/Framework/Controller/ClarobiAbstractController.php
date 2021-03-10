<?php declare(strict_types=1);

namespace ClarobiClarobi\Core\Framework\Controller;

use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class ClarobiAbstractController
 *
 * @package ClarobiClarobi\Core\Framework\Controller
 * @author Georgiana Camelia Gitan (g.gitan@interlive.ro)
 */
abstract class ClarobiAbstractController extends AbstractController
{
    /** @var Context $context */
    protected $context;

    protected static $contextKey = 'sw-context';

    /**
     * Verify request params.
     *
     * @param Request $request
     * @throws \Exception
     */
    public function verifyParam(Request $request): void
    {
        $from_id = $request->get('from_id');
        if (is_null($from_id)) {
            throw new \Exception('Param \'from_id\' is missing!');
        }
    }

    /**
     * Verify request token.
     *
     * @param Request $request
     * @param $configurations
     * @throws \Exception
     */
    public function verifyToken(Request $request, $configurations): void
    {
        $headerToken = $request->headers->get('X-Claro-TOKEN');
        if (!$headerToken) {
            throw new BadRequestHttpException('\'X-Claro-TOKEN\' header missing from request!');
        }
        if (!$configurations['apiKey']) {
            throw new \Exception('No API KEY provided in plugin configurations!');
        }
        if ($headerToken !== $configurations['apiKey']) {
            throw new \Exception(
                'Provided token: ' . $headerToken
                . ' not matching the API KEY saved in plugin configurations.'
            );
        }
    }

    /**
     * Remove keys from an assoc array.
     *
     * @param $entityData
     * @param string $entityName
     * @param array $keysToIgnore
     * @return array
     */
    public function ignoreEntityKeys($entityData, $entityName, $keysToIgnore)
    {
        $mappedKeys['entity_name'] = $entityName;
        foreach ($entityData as $key => $value) {
            if (in_array($key, $keysToIgnore)) {
                continue;
            }
            $mappedKeys[$key] = $value;
        }

        return $mappedKeys;
    }
}
