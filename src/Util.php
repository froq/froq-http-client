<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace Froq\Http\Client;

use Froq\Dom\Dom;

/**
 * @package    Froq
 * @subpackage Froq\Http\Client
 * @object     Froq\Http\Client\Util
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
final /* static */ class Util
{
    /**
     * Parse url.
     * @param  string $url
     * @return array
     * @throws Froq\Http\Client\UtilException
     */
    public static final function parseUrl(string $url): array
    {
        // ensure scheme
        if (!preg_match('~^(.+)://~', $url)) {
            $url = 'http://'. $url;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new UtilException("No valid url given (url: '{$url}'");
        }

        $parsedUrl = parse_url($url);
        if (empty($parsedUrl['host'])) {
            throw new UtilException("No host found in given url (url: '{$url}'");
        }

        $authority = '';
        $authorityUser = $parsedUrl['user'] ?? null;
        $authorityPass = $parsedUrl['pass'] ?? null;
        if ($authorityUser != null || $authorityPass != null) {
            $authority = $authorityUser;
            if ($authorityPass != null) {
                $authority .= ':'. $authorityPass .'@';
            }
        }

        $host = $parsedUrl['host'];
        $port = $parsedUrl['port'] ?? null;
        if ($port != null) {
            $host .= ':'. $port;
        }

        $query = $parsedUrl['query'] ?? null;
        if ($query != null) {
            parse_str($query, $query);
        }

        $url = sprintf('%s://%s%s%s', $parsedUrl['scheme'], $authority, $host,
            $parsedUrl['path'] ?? '/');
        $urlParams = $query;
        $urlFragment = $parsedUrl['fragment'] ?? null;

        $unparsedUrl = $url;
        if ($urlParams) $unparsedUrl .= '?'. http_build_query($urlParams);
        if ($urlFragment) $unparsedUrl .= '#'. $urlFragment;

        return [$url, $urlParams, $urlFragment, $parsedUrl, $unparsedUrl];
    }

    /**
     * Parse headers.
     * @param  string $headers
     * @param  bool   $allowMultiHeaders
     * @return array
     */
    public static final function parseHeaders(string $headers, bool $allowMultiHeaders = true): array
    {
        $return = [];

        $headers = explode("\r\n", trim($headers));
        if (!empty($headers)) {
            // pick first line
            $return[0] = array_shift($headers);

            foreach ($headers as $header) {
                @ [$name, $value] = explode(':', $header, 2);
                if ($name === null) {
                    continue;
                }

                $name = trim((string) $name);
                $value = trim((string) $value);

                // handle multi-headers as array
                if ($allowMultiHeaders && isset($return[$name])) {
                    $return[$name] = array_merge((array) $return[$name], [$value]);
                } else {
                    $return[$name] = $value;
                }
            }
        }

        return $return;
    }

    /**
     * Parse xml.
     * @param  any  $xml
     * @param  array $options
     * @return any
     */
    public static function parseXml($xml, array $options = null)
    {
        return Dom::parseXml($xml, $options);
    }

    /**
     * Json encode.
     * @param  any        $input
     * @param  array|null $options
     * @return any
     */
    public static function jsonEncode($input, array $options = null)
    {
        return json_encode($input
            , $options['encodeFlags'] ?? 0
            , $options['encodeDepth'] ?? 512
        );
    }

    /**
     * Json decode.
     * @param  any        $input
     * @param  array|null $options
     * @return any
     */
    public static function jsonDecode($input, array $options = null)
    {
        return json_decode($input
            , $options['assoc'] ?? false
            , $options['decodeDepth'] ?? 512
            , $options['decodeFlags'] ?? 0
        );
    }

    /**
     * Build query.
     * @param  array|object $input
     * @param  bool         $normalizeArrays
     * @return string
     * @throws Froq\Http\Client\UtilException
     */
    public static function buildQuery($input, bool $normalizeArrays = true): string
    {
        $inputType = gettype($input);
        if ($inputType != 'array' && $inputType != 'object') {
            throw new UtilException("Only array or object input accepted, '{$inputType}' given");
        }

        // check Froq\Util\Functions\to_query_string
        if (function_exists('to_query_string')) {
            return to_query_string($input, null, false, $normalizeArrays);
        }

        // fix skipped NULL values by http_build_query()
        static $filter;
        if ($filter == null) {
            $filter = function($var) use(&$filter) {
                foreach ($var as $key => $value) {
                    $var[$key] = is_array($value) || is_object($value)
                        ? $filter($value) : strval($value);
                }
                return $var;
            };
        }

        $input = http_build_query($filter($input));

        if ($normalizeArrays) {
            $input = preg_replace('~%5B([\d]+)%5D~simU', '[]', $input);
            $input = preg_replace('~%5B([\w\.-]+)%5D~simU', '[\1]', $input);
        }

        return $input;
    }
}
