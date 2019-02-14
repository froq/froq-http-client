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

namespace froq\http\client\agent;

use froq\http\client\{Client, ClientError};

/**
 * Curl.
 * @package froq\http\client\agent
 * @object  froq\http\client\agent\Curl
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
final class Curl extends Agent
{
    /**
     * Client.
     * @var froq\http\client\Client
     */
    protected $client;

    /**
     * Constructor.
     * @param froq\http\client\Client|null $client
     */
    public function __construct(Client $client = null)
    {
        if (!extension_loaded('curl')) {
            throw new AgentException('curl module not found');
        }

        $client && $this->setClient($client);

        parent::__construct(curl_init(), 'curl');
    }

    /**
     * Set client.
     * @param  froq\http\client\\Client $client
     * @return self
     */
    public final function setClient(Client $client): self
    {
        $this->client = $client;
        $this->client->setAgent($this);

        return $this;
    }

    /**
     * Get client.
     * @return ?froq\http\client\Client
     */
    public final function getClient(): ?Client
    {
        return $this->client;
    }

    /**
     * @inheritDoc froq\http\client\agent\Agent
     */
    public function run(): void
    {
        $client = $this->getClient();
        if ($client == null) {
            throw new AgentException('No client initiated yet');
        }

        $client->reset();
        $client->processPreSend();

        $agent = $client->getAgent();
        $agent->applyCurlOptions();

        $handle = $agent->getHandle();

        $error = null;
        $result =@ curl_exec($handle);
        $resultInfo = null;
        if ($result !== false) {
            $resultInfo = curl_getinfo($handle);
        } else {
            $error = new ClientError(curl_error($handle), curl_errno($handle));
        }

        $client->processPostSend([$result, $resultInfo, $error]);

        $callback = $client->getCallback();
        if ($callback != null) {
            $callback($client->getRequest(), $client->getResponse(), $client->getError());
        }

        curl_close($handle);
    }
}
