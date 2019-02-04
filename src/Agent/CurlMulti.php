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

namespace Froq\Http\Client\Agent;

use Froq\Http\Client\{Client, ClientError};

/**
 * @package    Froq
 * @subpackage Froq\Http\Client\Agent
 * @object     Froq\Http\Client\Agent\CurlMulti
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
final class CurlMulti extends Agent
{
    /**
     * Clients.
     * @var Froq\Http\Client\Client[]
     */
    protected $clients;

    // @param Client[] $clients
    public function __construct(array $clients = null)
    {
        if (!extension_loaded('curl')) {
            throw new AgentException('curl module not found');
        }

        parent::__construct(curl_multi_init(), 'curlmulti');

        $clients && $this->setClients($clients);
    }

    public function setClients(array $clients): self
    {
        foreach ($clients as $client) {
            if (!$client instanceof Client) {
                throw new AgentException('Each client must be an instance of '. Client::class);
            }
            $this->clients[] = $client;
        }

        return $this;
    }
    public function getClients(): ?array
    {
        return $this->clients;
    }

    public function run(): void
    {
        $clients = [];

        foreach ((array) $this->getclients() as $client) {
            $client->reset();
            $client->processPreSend();

            $agent = $client->getAgent();
            // prs($agent);
            $agent->applyCurlOptions();

            $handle = $agent->getHandle();

            $error = curl_multi_add_handle($this->handle, $handle);
            if ($error) {
                throw new AgentException(curl_multi_strerror($error), $error);
            }

            // tick
            $clients[(int) $handle] = $client;
        }

        // exec wrapper (http://php.net/manual/en/function.curl-multi-select.php#108928)
        $exec = function ($handle, &$running) {
            do {
                $code = curl_multi_exec($handle, $running);
            } while ($code == CURLM_CALL_MULTI_PERFORM);

            return $code;
        };

        // start requests
        $exec($this->handle, $running);

        do {
            // fail?
            if (curl_multi_select($this->handle) == -1) {
                usleep(1);
            }

            // get new state
            $exec($this->handle, $running);

            while ($info = curl_multi_info_read($this->handle)) {
                $client = $clients[(int) $info['handle']] ?? null;
                if ($client == null) {
                    continue;
                }
                $handle = $info['handle'];
                if ($handle != $client->getAgent()->getHandle()) {
                    continue;
                }

                $ok = ($info['result'] == CURLE_OK && $info['msg'] == CURLMSG_DONE);

                $error = null;
                $result = $ok ? curl_multi_getcontent($handle) : false;
                $resultInfo = null;
                if ($result !== false) {
                    $resultInfo = curl_getinfo($handle);
                } else {
                    $error = new ClientError(curl_error($handle), $info['result']);
                }

                $client->processPostSend([$result, $resultInfo, $error]);

                $callback = $client->getCallback();
                if ($callback != null) {
                    $callback($client->getRequest(), $client->getResponse(), $client->getError());
                }
            }
        } while ($running);

        foreach ($clients as $client) {
            curl_close($client->getAgent()->getHandle());
        }
        curl_multi_close($this->handle);
    }
}
