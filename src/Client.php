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

use Froq\Http\Client\Agent\Curl;

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
     * @param  callable $callback
     * @return void
     * @throws Froq\Http\Client\ClientException
     */
    public function send(callable $callback = null): void
    {
        $this->reset();

        // could be given in constructor
        $url = $this->getArgument('url');
        if ($url == null) {
            throw new ClientException('No valid url given');
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

        $this->agent = new Curl($this);

        [$result, $resultInfo, $error] = $this->agent->run();
        if ($error) {
            $this->error = $error;
        } else {
            $this->result = $result;
            $this->resultInfo = $resultInfo;

            $this->agent->close();

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
        }

        $callback = $callback ?? $this->getCallback();
        if ($callback != null) {
            $callback($this->request, $this->response, $this->error);
        }
    }
}
