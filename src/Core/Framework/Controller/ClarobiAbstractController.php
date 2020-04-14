<?php

namespace Clarobi\Core\Framework\Controller;

use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class ClarobiAbstractController extends AbstractController
{
    public function decToHex($decId)
    {
        return dechex($decId);
    }

    public function hexToDec($hexId)
    {
//        $bytes = Uuid::fromHexToBytes($hexId);
//        var_dump($bytes);
//        var_dump(bindec($bytes));
//        die;

        return hexdec($hexId);
    }

    /**
     * @param Request $request
     * @param $configurations
     * @throws \Exception
     */
    public function verifyRequest(Request $request, $configurations)
    {
        // Verify param request
        $from_id = $request->get('from_id');
        if (is_null($from_id)) {
            throw new \Exception('Param \'from_id\' is missing!');
        }

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
}
