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

use Froq\Util\Interfaces\Loopable;

/**
 * @package    Froq
 * @subpackage Froq\Http\Client
 * @object     Froq\Http\Client\ClientQueue
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
final class ClientQueue extends Collection
{
    /**
     * Constructor.
     * @param Froq\Http\Client\Clients|null $clients
     */
    public function __construct(Clients $clients = null)
    {
        if ($clients != null) {
            foreach ($clients as $client) {
                $this->addClient($client);
            }
        }
    }

    /**
     * Client.
     * @param  int $index
     * @return Froq\Http\Client\Client
     * @throws Froq\Http\Client\ClientQueueException
     */
    public function client(int $index): Client
    {
        if (!isset($this->items[$index])) {
            throw new ClientQueueException("No client exists with index {$index}");
        }

        return $this->item($index);
    }

    /**
     * Clients.
     * @return array
     */
    public function clients(): array
    {
        return $this->items();
    }

    /**
     * Add client.
     * @param  Froq\Http\Client\Client $client
     * @return void
     */
    public function addClient(Client $client): void
    {
        $this->add($client);
    }

    /**
     * Add async client.
     * @param  Froq\Http\Client\Client $client
     * @return void
     */
    public function addAsyncClient(Client $client): void
    {
        $client->async(true);

        $this->add($client);
    }

    /**
     * Perform.
     * @return array
     */
    public function perform(): array
    {
        $clients = new Clients(array_filter($this->items, function ($client) {
            return !$client->async();
        }));
        $clientsAsync = new Clients(array_filter($this->items, function ($client) {
            return $client->async();
        }));

        $ret = [];
        if ($clients->size()) {
            foreach ($clients as $client) {
                $ret[] = MessageEmitter::send($client);
            }
        }
        if ($clientsAsync->size()) {
            $clientsAsync = MessageEmitter::sendAsync($clientsAsync);
            foreach ($clientsAsync as $client) {
                $ret[] = $client;
            }
        }

        return $ret;
    }
}
