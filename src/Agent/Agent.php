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

use Froq\Http\Client\Client;

/**
 * @package    Froq
 * @subpackage Froq\Http\Client\Agent
 * @object     Froq\Http\Client\Agent\Agent
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
abstract class Agent
{
    protected $handle;
    protected $handleType;

    /**
     * Constructor.
     * @param resource $handle
     * @param string   $handleType
     */
    public function __construct($handle, string $handleType)
    {
        $this->handle = $handle;
        $this->handleType = $handleType;
    }

    public final function getHandle()
    {
        return $this->handle;
    }
    public final function getHandleType()
    {
        return $this->handleType;
    }

    public static final function init(string $type, Client $client): Agent
    {
        switch ($type) {
            case 'curl': return new Curl($client);
            case 'curlmulti': return new CurlMulti($client);
            case 'fsock': return new FSock($client);
            default:
                throw new AgentException("Unknown type '{$type}' given");
        }
    }

    public final function applyCurlOptions(): void
    {
        if ($this->handle == null) {
            throw new AgentException('No curl handle yet to apply options');
        }

        $request = $this->client->getRequest();
        // prd($this->client,1);

        [$method, $url, $headers, $body, $options, $arguments] = [
            $request->getMethod(), $request->getFullUrl(), $request->getHeaders(),
            $request->getRawBody(), $this->client->getOptions(), $this->client->getArguments()
        ];

        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_URL => $url,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_FOLLOWLOCATION => $options['redir'],
            CURLOPT_MAXREDIRS => $options['redirMax'],
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_DEFAULT_PROTOCOL => 'http',
            CURLOPT_DNS_CACHE_TIMEOUT => 3600, // 1 hour
            CURLOPT_TIMEOUT => $options['timeout'],
            CURLOPT_CONNECTTIMEOUT => $options['timeoutConnect'],
            // CURLOPT_TIMEOUT_MS => $options['timeout'], // @debug
            // CURLOPT_CONNECTTIMEOUT_MS => $options['timeoutConnect'], // @debug
            CURLINFO_HEADER_OUT => true, // request headers
        ];

        // headers
        $options[CURLOPT_HTTPHEADER][] = 'Expect:';
        foreach ($headers as $name => $value) {
            $options[CURLOPT_HTTPHEADER][] = sprintf('%s: %s', $name, $value);
        }

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
            // @debug these headers should be added automatically by curl
            // if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            //     $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
            //     $options[CURLOPT_HTTPHEADER][] = 'Content-Length: '. strlen((string) $body);
            // }
        }

        // user provided options
        if (isset($arguments['curlOptions'])) {
            static $notAllowedOptions = [CURLOPT_URL, CURLOPT_HEADER, CURLOPT_CUSTOMREQUEST,
                CURLINFO_HEADER_OUT];

            foreach ($arguments['curlOptions'] as $name => $value) {
                // these are already set internally
                if (in_array($name, $notAllowedOptions)) {
                    throw new AgentException('Not allowed curl option given (not allowed options: '.
                        'CURLOPT_URL, CURLOPT_HEADER, CURLOPT_CUSTOMREQUEST, CURLINFO_HEADER_OUT)');
                }

                if (is_array($value)) {
                    foreach ($value as $value) {
                        $options[$name][] = $value;
                    }
                } else { $options[$name] = $value; }
            }
        }

        curl_setopt_array($this->handle, $options);
    }

    abstract public function run(): void;
}
