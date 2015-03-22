<?php

namespace Site\CartoBundle\Security;

class CustomCrypto {

    private static $pub = "-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCcMD35gu6ZRORC5fSUTXXXZyGI
67fBjkdSUvDlyLGuAs6ClnVUOb4yXJtFXCSeN5j+0VrFdNVZOpG6lxbdhKixrvTl
YLnKZ6qTdC4gWPOx7nf8VjqCP4VRCLIdvwtMy55vgFDuAQYWXD6dach6LlIt/O4O
vfhOfpzYH4v9xyYjuwIDAQAB
-----END PUBLIC KEY-----";

    private static $priv = "-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQCcMD35gu6ZRORC5fSUTXXXZyGI67fBjkdSUvDlyLGuAs6ClnVU
Ob4yXJtFXCSeN5j+0VrFdNVZOpG6lxbdhKixrvTlYLnKZ6qTdC4gWPOx7nf8VjqC
P4VRCLIdvwtMy55vgFDuAQYWXD6dach6LlIt/O4OvfhOfpzYH4v9xyYjuwIDAQAB
AoGAAj4bFbMQk/jOQjulCGAYWhBsBdhEmi3dzkvMk7APBQ2bQ3q/kocFuRllTVim
WfM4aig9YmpsCczyfLhgpquZ9HVtj686RGqB8iN7Sdm5Pzo5kGrcHdNjuh3wnlAq
NncGRdKI/GoSV4ujbv6pF2Ftlg0sDA5JRqwniUy5ZLpm4EkCQQDJxGCRexnfutxL
9VsgvsS79iNScPUeQXlViF9HtF7PmMZvwjYwET4Pq7ekRz2qE4qpjQ52KpMxsfSq
XH5hTjv3AkEAxiuXXdAPz2MpTWBmRT0Z9RSUnVnS6MzRiIXmhEFEKPtdNvgD+Y7u
EYthQc8IJDhAF8Xeuj8wKk9sgCarQPO9XQJBALBodlY8Xz7xzbLL7sUOhkwgxHlM
McQmUsOp3ESBO3Qei0EjeOVF7hEdfg6wCwYs18uufLpsNw34HYbmH8lL8bkCQCPx
nife6C82jjRBquseFQo17GrJ8w5UsCCyIMiWSfWg+hxRSe9G9HlsLXzRP2nKZh2p
vydK9MKH22c3HFLQouUCQEF7UNyktq3T0B52sz9Je4mpli4GgplIcHC90+zE6+sq
0hlMdGjelZ73Yd8zPuhxVTEW8QgkTUOE61069HpgaVQ=
-----END RSA PRIVATE KEY-----";

    public static function encrypt($data) {
        if (openssl_public_encrypt($data, $encrypted, CustomCrypto::$pub))
            return base64_encode($encrypted);
        else
            return false;
    }

    public static function decrypt($data) {
        if (openssl_private_decrypt(base64_decode($data), $decrypted, CustomCrypto::$priv))
            return $decrypted;
        else
            return false;
    }

}