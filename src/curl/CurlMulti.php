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

namespace froq\http\client\curl;

use froq\http\client\{Client, CurlException};

/**
 * Curl Multi.
 * @package froq\http\client\curl
 * @object  froq\http\client\curl\CurlMulti
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
final class CurlMulti
{
    /**
     * Clients.
     * @var array<froq\http\client\Client>
     */
    protected array $clients;

    /**
     * Constructor.
     * @param  array<froq\http\client\Client>|null $clients
     * @throws froq\http\client\CurlException
     */
    public function __construct(array $clients = null)
    {
        if (!extension_loaded('curl')) {
            throw new CurlException('curl module not loaded');
        }

        $clients && $this->setClients($clients);
    }

    /**
     * Set clients.
     * @param  array<froq\http\client\Client> $clients
     * @return self
     * @throws froq\http\client\CurlException
     */
    public function setClients(array $clients): self
    {
        foreach ($clients as $client) {
            if (!$client instanceof Client) {
                throw new CurlException(sprintf(
                    'Each client must be instance of %s, %s given',
                    Client::class, is_object($client) ? get_class($client) : gettype($client)));
            }

            $this->clients[] = $client;
        }

        return $this;
    }

    /**
     * Get clients.
     * @return ?array<froq\http\client\Client>
     */
    public function getClients(): ?array
    {
        return $this->clients ?? null;
    }

    /**
     * Run.
     * @return void
     */
    public function run(): void
    {
        $clients = $this->clients;
        if (empty($clients)) {
            throw new CurlException('No clients initiated yet to process');
        }

        $multiHandle = curl_multi_init();
        if (!$multiHandle) {
            throw new CurlException(sprintf('Failed to initialize multi-curl session, error[%s]',
                error_get_last()['message'] ?? 'unknown'));
        }

        $clientStack = [];

        foreach ($clients as $client) {
            $client->prepare();

            $curl = $client->getCurl();
            $curl->applyOptions();

            $handle = $curl->getHandle();

            $error = curl_multi_add_handle($multiHandle, $handle);
            if ($error) {
                throw new CurlException(curl_multi_strerror($error), $error);
            }

            // Tick.
            $clientStack[(int) $handle] = $client;
        }

        // Exec wrapper (http://php.net/curl_multi_select#108928).
        $exec = function ($multiHandle, &$running) {
            while (curl_multi_exec($multiHandle, $running) == CURLM_CALL_MULTI_PERFORM);
        };

        // Start requests.
        $exec($multiHandle, $running);

        do {
            // Wait a while if fail. Note: This must be here to achieve the winner (fastest) response
            // first in a right way, not in $exec loop like http://php.net/curl_multi_exec#113002.
            if (curl_multi_select($multiHandle) == -1) {
                usleep(1);
            }

            // Get new state.
            $exec($multiHandle, $running);

            while ($info = curl_multi_info_read($multiHandle)) {
                // Check tick.
                $client = $clientStack[(int) $info['handle']];
                if ($client == null) {
                    continue;
                }

                $handle = $info['handle'];
                if ($handle != $client->getCurl()->getHandle()) {
                    continue;
                }

                // Check status.
                $ok = ($info['result'] == CURLE_OK && $info['msg'] == CURLMSG_DONE);

                $result = $ok ? curl_multi_getcontent($handle) : false;
                if ($result !== false) {
                    $client->end($result, curl_getinfo($handle), null);
                } else {
                    $client->end(null, null, new CurlError($info['result'], curl_error($handle)));
                }

                curl_multi_remove_handle($multiHandle, $handle);
                curl_close($handle);

                // This can be set true to break the queue.
                if ($client->aborted) {
                    $client->fireEvent('abort');

                    break 2; // Break parent loop.
                }
            }
        } while ($running);

        // Close handles if exist any more (which might be not closed due to client abort).
        foreach ($clientStack as $client) {
            $handle = $client->getCurl()->getHandle();
            if (is_resource($handle)) {
                curl_multi_remove_handle($multiHandle, $handle);
                curl_close($handle);
            }
        }

        curl_multi_close($multiHandle);
    }
}
