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

/**
 * Response.
 * @package froq\http\client
 * @object  froq\http\client\Response
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
final class Response extends Message
{
    /**
     * Status.
     * @var string
     */
    private $status;

    /**
     * Status code.
     * @var int
     */
    private $statusCode;

    /**
     * Status text.
     * @var string
     */
    private $statusText;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Message::TYPE_RESPONSE);
    }

    /**
     * Set status.
     * @param  string $status
     * @return self
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        // extract code & text
        if (preg_match('~^HTTP/(?:.+)?\s+(\d+)(?:\s+(.*))?~i', $status, $matches)) {
            $this->statusCode = (int) $matches[1];
            $this->statusText = isset($matches[2]) ? trim($matches[2]) : null;
        }

        return $this;
    }

    /**
     * Get status.
     * @return ?string
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Get status code.
     * @return ?int
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Get status text.
     * @return ?string
     */
    public function getStatusText(): ?string
    {
        return $this->statusText;
    }
}

