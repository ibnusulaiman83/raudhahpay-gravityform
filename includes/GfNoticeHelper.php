<?php

class GfNoticeHelper
{
    public static function humanify(array $errors)
    {
        $formatted = '';

        foreach($errors as $key => $error) {
            $formatted .= self::formatErrorKey($key) . ': ' . implode('<br/>', $error) . '<br/>';
        }

        return $formatted;
    }

    private static function formatErrorKey($key)
    {
        $keyArr = explode('.', $key);
        return ucwords(implode(' ', $keyArr));
    }
}