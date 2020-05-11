<?php declare(strict_types=1);

namespace Clarobi\Core\Framework\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class ClarobiAbstractController
 * @package Clarobi\Core\Framework\Controller
 */
abstract class ClarobiAbstractController extends AbstractController
{
    /**
     * @param Request $request
     * @throws \Exception
     */
    public function verifyParam(Request $request)
    {
        // Verify param request
        $from_id = $request->get('from_id');
        if (is_null($from_id)) {
            throw new \Exception('Param \'from_id\' is missing!');
        }
    }

    /**
     * @param Request $request
     * @param $configurations
     * @throws \Exception
     */
    public function verifyToken(Request $request, $configurations)
    {
        // Verify token request
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
                . ' not matching the API KEY saved in plugin configurations: ' . $configurations['apiKey']
            );
        }
    }

    /**
     * @param $entityData
     * @param string $entityName
     * @param array $keysToIgnore
     * @return mixed
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
