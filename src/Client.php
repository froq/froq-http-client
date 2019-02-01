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

/**
 * @package    Froq
 * @subpackage Froq\Http\Client
 * @object     Froq\Http\Client\Client
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
final class Client extends AbstractClient
{
    /**
     * Send.
     * @param  string|null $url
     * @param  array|null  $arguments
     * @param  callable    $callback
     * @return void
     * @throws Froq\Http\Client\ClientException
     */
    public function send(string $url = null, array $arguments = null, callable $callback = null): void
    {
        $this->reset();

        // could be given in constructor
        $url = $url ?? $this->getArgument('url');
        if ($url == null) {
            throw new ClientException('No URL given in constructor nor send() method');
        }

        if ($arguments != null) {
            $this->setArguments($arguments);
        }
        if ($callback != null) {
            $this->setCallback($callback);
        }

        [$url, $urlParams] = Util::parseUrl($url);
        $this->request->setUrl($url)
                      ->setUrlParams($urlParams);

        $arguments = $this->getArguments();
        if (!empty($arguments)) {
            $this->request->setMethod($arguments['method']);

            if (isset($arguments['headers'])) {
                $this->request->setHeaders($arguments['headers']);
            }
            if (isset($arguments['urlParams'])) {
                $this->request->setUrlParams($arguments['urlParams']);
            }

            // body accepted for all methods..
            // @see https://stackoverflow.com/questions/978061/http-get-with-request-body
            $body = $arguments['body'] ?? null;
            if ($body !== null) {
                $rawBody = $body;
                $bodyType = gettype($body);
                if ($bodyType == 'array' || $bodyType == 'object') {
                    $contentType = (string) $this->request->getHeader('Content-Type');
                    $rawBody = strpos($contentType, '/json') || strpos($contentType, '+json')
                        ? Util::jsonEncode($rawBody, $arguments['jsonOptions'] ?? [])
                        : Util::buildQuery($rawBody);
                }

                $this->request->setBody($body)
                              ->setRawBody($rawBody);
            }
        }

        $method = $this->request->getMethod();
        $body = $this->request->getRawBody();

        $curlHandle = curl_init();
        $curlOptions = [
            CURLOPT_URL => $this->request->getFullUrl(),
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_FOLLOWLOCATION => $this->options['redir'],
            CURLOPT_MAXREDIRS => $this->options['redirMax'],
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_DEFAULT_PROTOCOL => 'http',
            CURLOPT_DNS_CACHE_TIMEOUT => 3600, // 1 hour
            CURLOPT_TIMEOUT => $this->options['timeout'],
            CURLOPT_CONNECTTIMEOUT => $this->options['timeoutConnect'],
            // CURLOPT_TIMEOUT_MS => $this->options['timeout'], // @debug
            // CURLOPT_CONNECTTIMEOUT_MS => $this->options['timeoutConnect'], // @debug
            CURLINFO_HEADER_OUT => true, // request headers
        ];

        // headers
        $curlOptions[CURLOPT_HTTPHEADER][] = 'Expect:';
        foreach ($this->request->getHeaders() as $name => $value) {
            $curlOptions[CURLOPT_HTTPHEADER][] = sprintf('%s: %s', $name, $value);
        }

        // body
        if ($body !== null) {
            $curlOptions[CURLOPT_POSTFIELDS] = $body;
            // @debug these headers should be added automatically by curl
            // if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            //     $curlOptions[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
            //     $curlOptions[CURLOPT_HTTPHEADER][] = 'Content-Length: '. strlen((string) $body);
            // }
        }

        // user provided options
        if (isset($arguments['curlOptions'])) {
            static $notAllowedOptions = [CURLOPT_URL, CURLOPT_HEADER, CURLOPT_CUSTOMREQUEST,
                CURLINFO_HEADER_OUT];

            foreach ($arguments['curlOptions'] as $name => $value) {
                // these are already set internally
                if (in_array($name, $notAllowedOptions)) {
                    throw new ClientException('Not allowed curl option given (not allowed options: '.
                        'CURLOPT_URL, CURLOPT_HEADER, CURLOPT_CUSTOMREQUEST, CURLINFO_HEADER_OUT)');
                }

                if (is_array($value)) {
                    foreach ($value as $value) {
                        $curlOptions[$name][] = $value;
                    }
                } else { $curlOptions[$name] = $value; }
            }
        }

        curl_setopt_array($curlHandle, $curlOptions);

        $this->result = curl_exec($curlHandle);
        if ($this->result === false) {
            throw new ClientException(curl_error($curlHandle), curl_errno($curlHandle));
        }

        $this->resultInfo = curl_getinfo($curlHandle);

        curl_close($curlHandle);

        // set request headers
        if (isset($this->resultInfo['request_header'])) {
            $this->request->setHeaders(Util::parseHeaders($this->resultInfo['request_header'], false));
        }

        $result = explode("\r\n\r\n", $this->result);
        // drop redirect etc. headers
        while (count($result) > 2) {
            array_shift($result);
        }

        // split headers/body parts
        @ [$headers, $body] = $result;

        if ($headers != null) {
            $headers = Util::parseHeaders($headers);
            if (isset($headers[0])) {
                $this->response->setStatus($headers[0]);
            }

            $this->response->setHeaders($headers);
        }

        if ($body != null) {
            $rawBody = $body;
            $contentEncoding = $this->response->getHeader('Content-Encoding');
            $contentType = (string) $this->response->getHeader('Content-Type');

            // decode gzip (if zipped)
            if ($contentEncoding == 'gzip' || (strpos($contentType, '/octet-stream')
                && substr($this->request->getUrl(), -3) == '.gz')) {
                $body = $rawBody = gzdecode($body);
            }

            // decode json
            if (strpos($contentType, '/json') || strpos($contentType, '+json')) {
                $body = Util::jsonDecode($body, $arguments['jsonOptions'] ?? []);
            } elseif (strpos($contentType, '/xml')) {
                $body = Util::parseXml($body, $arguments['xmlOptions'] ?? []);
            }

            $this->response->setBody($body)
                           ->setRawBody($rawBody);
        }

        $callback = $this->getCallback();
        if ($callback != null) {
            $callback($this->request, $this->response);
        }
    }
}
