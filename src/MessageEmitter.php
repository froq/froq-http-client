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

use Froq\Http\Client\Agent\{Curl, CurlMulti};

/**
 * @package    Froq
 * @subpackage Froq\Http\Client
 * @object     Froq\Http\Client\MessageEmitter
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
final /* static */ class MessageEmitter
{
    /**
     * Send.
     * @param  Froq\Http\Client\Client $client
     * @return Froq\Http\Client\Response
     */
    public static function send(Client $client): Response
    {
        $agent = new Curl($client);
        $agent->run();

        $response = $client->getResponse();

        return $response;
    }

    /**
     * Send async.
     * @param  Froq\Http\Client\Client[]  $clients
     * @return Froq\Http\Client\Response[]
     */
    public static function sendAsync(array $clients): array
    {
        foreach ($clients as $client) {
            $client->setAgent(new Curl($client));
        }

        $agent = new CurlMulti($clients);
        $agent->run();

        $responses = [];
        foreach ($agent->getClients() as $client) {
            $responses[] = $client->getResponse();
        }

        return $responses;
    }
}
