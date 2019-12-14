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

namespace froq\http\client;

use froq\http\client\Message;

/**
 * Request.
 * @package froq\http\client
 * @object  froq\http\client\Request
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
final class Request extends Message
{
    /**
     * Methods.
     * @var string
     */
    private string $method;

    /**
     * Url.
     * @var string
     */
    private string $url;

    /**
     * Url.
     * @var ?array
     */
    private ?array $urlParams = null;

    /**
     * Constructor.
     * @param string      $method
     * @param string      $url
     * @param array|null  $urlParams
     * @param string|null $body
     * @param array|null  $headers
     */
    public function __construct(string $method, string $url, array $urlParams = null,
        string $body = null, array $headers = null)
    {
        $this->setMethod($method);

        $this->setUrl($url);
        if ($urlParams != null) {
            $this->setUrlParams($urlParams);
        }

        // Default headers.
        static $headersDefault = [
            'accept' => '*/*',
            'accept-encoding' => 'gzip',
            'user-agent' => 'Froq Http Client (+https://github.com/froq/froq-http-client)',
        ];

        $headers = array_replace($headersDefault, $headers ?? []);

        parent::__construct(Message::TYPE_REQUEST, null, $headers, $body);
    }

    /**
     * Set method.
     * @param  string $method
     * @return self
     */
    public function setMethod(string $method): self
    {
        $this->method = strtoupper($method);

        return $this;
    }

    /**
     * Get method.
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Set url.
     * @param  string $url
     * @return self
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set url params.
     * @param  array $urlParams
     * @return self
     */
    public function setUrlParams(array $urlParams): self
    {
        $this->urlParams = $urlParams;

        return $this;
    }

    /**
     * Get url params.
     * @return ?array
     */
    public function getUrlParams(): ?array
    {
        return $this->urlParams;
    }

    /**
     * Get uri.
     * @return string
     * @internal
     */
    protected function getUri(): string
    {
        // Extract the only path and query part of URL.
        return preg_replace('~^\w+://[^/]+(/.*)~', '\\1', $this->getUrl());
    }
}
