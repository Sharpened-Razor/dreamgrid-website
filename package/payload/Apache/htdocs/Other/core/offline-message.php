<?php

function australiaOfflineMessageText($value)
{
    $value = trim((string)$value);

    if(
        $value === '' ||
        $value[0] !== '<'
    ){
        return $value;
    }

    $previous =
        libxml_use_internal_errors(true);

    $xml =
        simplexml_load_string(
            $value,
            'SimpleXMLElement',
            LIBXML_NOCDATA | LIBXML_NONET
        );

    $text = $value;

    if(
        $xml !== false &&
        isset($xml->message)
    ){
        $text =
            trim(
                (string)$xml->message
            );
    }

    libxml_clear_errors();

    libxml_use_internal_errors(
        $previous
    );

    return $text;
}