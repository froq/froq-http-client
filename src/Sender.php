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

use froq\http\client\{Client, Response};
use froq\http\client\curl\{Curl, CurlMulti};

/**
 * Sender.
 * @package froq\http\client
 * @object  froq\http\client\Sender
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0, 4.0 Renamed as Sender from MessageEmitter.
 * @static
 */
final class Sender
{
    /**
     * Send.
     * @param  froq\http\client\Client $client
     * @return froq\http\client\Response
     */
    public static function send(Client $client): Response
    {
        $curl = new Curl($client);
        $client->setCurl($curl);

        $runner = $curl;
        $runner->run();

        $response = $client->getResponse();

        return $response;
    }

    /**
     * Send async.
     * @param  array<froq\http\client\Client> $clients
     * @return array<froq\http\client\Response>
     */
    public static function sendAsync(array $clients): array
    {
        foreach ($clients as $client) {
            $curl = new Curl($client);
            $client->setCurl($curl);
        }

        $runner = new CurlMulti($clients);
        $runner->run();

        $responses = [];

        foreach ($runner->getClients() as $client) {
            $responses[] = $client->getResponse();
        }

        return $responses;
    }
}
