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
     * @var int
     */
    private int $status;

    /**
     * Parsed body.
     * @var ?array
     */
    private ?array $parsedBody = null;

    /**
     * Constructor.
     * @param int         $status
     * @param string|null $body
     * @param array|null  $parsedBody
     * @param array|null  $headers
     */
    public function __construct(int $status = 0, string $body = null, array $parsedBody = null,
        array $headers = null)
    {
        $this->setStatus($status);

        isset($parsedBody) && $this->setParsedBody($parsedBody);

        parent::__construct(Message::TYPE_RESPONSE, null, $headers, $body);
    }

    /**
     * Set status.
     * @param  int $status
     * @return self
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set parsed body.
     * @param  array $parsedBody
     * @return self
     */
    public function setParsedBody(array $parsedBody): self
    {
        $this->parsedBody = $parsedBody;

        return $this;
    }

    /**
     * Get parsed body.
     * @return ?array
     */
    public function getParsedBody(): ?array
    {
        return $this->parsedBody;
    }
}

