<?php

namespace Kryn\CmsBundle;

class Tools {

    public static function underscore2Camelcase($value)
    {
        return static::char2Camelcase($value, '_');
    }

    public static function char2Camelcase($value, $char = '_')
    {
        $ex = explode($char, $value);
        $return = '';
        foreach ($ex as $str) {
            $return .= ucfirst($str);
        }

        return $return;
    }

    public static function camelcase2Underscore($value)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $value));
    }

    /**
     * Returns a relative path from $path to $current.
     *
     * @param string $path
     * @param string $current relative to this
     *
     * @return string relative path with trailing slash
     */
    public static function resolveRelativePath($path, $current)
    {
        $path    = '/' . trim($path, '/');
        $current = '/' . trim($current, '/');

        if (0 === $pos = strpos($path, $current)) {
            return substr($path, strlen($current));
        }

        $result = '';
        while ($current && false === strpos($path, $current)) {
            $result .= '../';
            $current = substr($current, 0, strrpos($current, '/'));
        }

        return !$current /*we reached root*/ ? $result . substr($path, 1) : $result;
    }

    public static function dbQuote($value, $table = '')
    {
        if (is_array($value)) {
            foreach ($value as &$value) {
                $value = static::dbQuote($value);
            }

            return $value;
        }
        if (strpos($value, ',') !== false) {
            $values = explode(',', str_replace(' ', '', $value));
            $values = static::dbQuote($values);

            return implode(', ', $values);
        }

        if ($table && strpos($value, '.') === false) {
            return static::dbQuote($table) . '.' . static::dbQuote($value);
        }

        return preg_replace('/[^a-zA-Z0-9-_]/', '', $value);;
    }


    public static function urlEncode($string)
    {
        $string = rawurlencode($string);
        $string = str_replace('%2F', '%25252F', $string);
        return $string;
    }

    public static function urlDecode($string)
    {
        $string = str_replace('%25252F', '%2F', $string);
        return rawurldecode($string);
    }

}