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
 * @object     Froq\Http\Client\Message
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
abstract class Message
{
    /**
    * Types.
    * @const int
    */
    const TYPE_REQUEST  = 1,
          TYPE_RESPONSE = 2;

    /**
    * Type.
    * @var int
    */
    protected $type;

    /**
    * Headers.
    * @var array
    */
    protected $headers;

    /**
    * Body.
    * @var any
    */
    protected $body;

    /**
    * Raw body.
    * @var string
    */
    protected $rawBody;

    /**
     * Constructor.
     * @param int $type
     */
    public function __construct(int $type)
    {
        $this->type = $type;
    }

    /**
     * String magic.
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Set headers.
     * @param  array $headers
     * @param  bool  $bool
     * @return self
     */
    public final function setHeaders(array $headers, bool $sort = true): self
    {
        foreach ($headers as $key => $value) {
            $this->setHeader((string) $key, $value);
        }

        // re-order
        if ($sort) {
            $headers = [];
            if (isset($this->headers[0])) {
                $headers[0] = $this->headers[0];
                unset($this->headers[0]);
            }

            ksort($this->headers);
            foreach ($this->headers as $name => $value) {
                $headers[$name] = $value;
            }

            $this->headers = $headers;
        }

        return $this;
    }

    /**
     * Get headers.
     * @return ?array
     */
    public final function getHeaders(): ?array
    {
        return $this->headers;
    }

    /**
     * Has header.
     * @param  string $name
     * @return bool
     */
    public final function hasHeader(string $name): bool
    {
        return $this->getHeader($name) !== null;
    }

    /**
     * Set header.
     * @param   string $name
     * @param   any    $value
     * @return  self
     */
    public final function setHeader(string $name, $value): self
    {
        if ($value === null) { // null means remove
            unset($this->headers[$name]);
        } else {
            if (is_scalar($value)) {
                $value = (string) $value;
            }
            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * Get header.
     * @param  string $name
     * @return any
     */
    public final function getHeader(string $name)
    {
        $value = $this->headers[$name] ?? null;
        if ($value === null) {
            $_name = strtolower($name);
            foreach ($this->headers as $name => $value) {
                if ($_name == strtolower((string) $name)) {
                    break;
                }
            }
        }

        return $value;
    }

    /**
     * Set body.
     * @param  any $body
     * @return self
     */
    public final function setBody($body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body.
     * @return any
     */
    public final function getBody()
    {
        return $this->body;
    }

    /**
     * Set raw body.
     * @param  any $rawBody
     * @return self
     */
    public final function setRawBody($rawBody): self
    {
        $this->rawBody = $rawBody;

        return $this;
    }

    /**
     * Get raw body.
     * @return any
     */
    public final function getRawBody()
    {
        return $this->rawBody;
    }

    /**
     * To string.
     * @param  bool $withBody
     * @return string
     */
    public final function toString(bool $withBody = true): string
    {
        $return = '';

        foreach ($this->headers as $name => $value) {
            if ($name == '0') {
                $return .= "{$value}\r\n";
                continue;
            }
            $return .= "{$name}: {$value}\r\n";
        }

        if ($withBody) {
            $return .= "\r\n";
            $return .= $this->rawBody;
        }

        return $return;
    }
}
