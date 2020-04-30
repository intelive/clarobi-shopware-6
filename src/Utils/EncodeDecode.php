<?php declare(strict_types=1);

namespace Clarobi\Utils;

/**
 * Class EncodeDecode
 * @package Clarobi\Utils
 */
class EncodeDecode
{
    /**
     * Compress encoded data if lib and functions exist.
     *
     * @param $data
     * @return bool|string
     */
    public static function compress($data)
    {
        if (extension_loaded('zlib') &&
            function_exists('gzcompress') &&
            function_exists('base64_encode')
        ) {
            return base64_encode(gzcompress(serialize(($data))));
        } else {
            // todo: log error
            // log error (__METHOD__ . ' : ' . 'Extensions zlib or gzcompress or base64_encode do not exist');
        }

        return false;
    }

    /**
     * Encode data with API_SECRET from configuration.
     *
     * @param $payload
     * @param $secret
     * @return string
     */
    public static function encode($payload, $secret)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt(json_encode($payload), 'aes-256-cbc', $secret, 0, $iv);

        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decode data with API_SECRET from configuration.
     *
     * @param $payload
     * @param $secret
     * @return mixed
     */
    public function decode($payload, $secret)
    {
        list($encryptedData, $iv) = explode('::', base64_decode($payload), 2);

        return json_decode(openssl_decrypt($encryptedData, 'aes-256-cbc', $secret, 0, $iv));
    }
}
