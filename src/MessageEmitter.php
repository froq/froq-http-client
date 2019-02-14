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

use froq\http\client\agent\{Curl, CurlMulti};

/**
 * Message emitter.
 * @package froq\http\client
 * @object  froq\http\client\MessageEmitter
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
final /* static */ class MessageEmitter
{
    /**
     * Send.
     * @param  froq\http\client\Client $client
     * @return froq\http\client\Response
     */
    public static function send(Client $client): Response
    {
        $agent = new Curl($client);
        $agent->run();

        $response = $agent->getClient()->getResponse();

        return $response;
    }

    /**
     * Send async.
     * @param  froq\http\client\Clients $clients
     * @return froq\http\client\Responses
     */
    public static function sendAsync(Clients $clients): Responses
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

        return new Responses($responses);
    }
}
