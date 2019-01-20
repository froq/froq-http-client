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
 * @object     Froq\Http\Client\Request
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
final class Request extends Message
{
    /**
     * Methods.
     * @var string
     */
    private $method;

    /**
     * Url.
     * @var string
     */
    private $url;

    /**
     * Url.
     * @var array
     */
    private $urlParams;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Message::TYPE_REQUEST);

        // set default headers
        $this->headers['Connection'] = 'close';
        $this->headers['Accept'] = '*/*';
        $this->headers['Accept-Encoding'] = 'gzip';
        $this->headers['User-Agent'] = 'Froq Http Client (+https://github.com/froq/froq-http-client)';
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
     * @return ?string
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Set url.
     * @param  ?string $url
     * @return self
     */
    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     * @return ?string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set url params.
     * @param  ?array $urlParams
     * @return self
     */
    public final function setUrlParams(?array $urlParams): self
    {
        $this->urlParams = $urlParams;

        return $this;
    }

    /**
     * Get url params.
     * @return ?array
     */
    public final function getUrlParams(): ?array
    {
        return $this->urlParams;
    }

    /**
     * Get full url.
     * @return ?string
     */
    public function getFullUrl(): ?string
    {
        $return = null;

        if ($this->url != null) {
            $return .= $this->url;
        }
        if ($this->urlParams != null) {
            $return .= '?'. Util::buildQuery($this->urlParams);
        }

        return $return;
    }
}
