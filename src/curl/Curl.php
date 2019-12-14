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

use froq\http\client\Client;
use froq\http\client\curl\{CurlError, CurlException};

/**
 * Curl.
 * @package froq\http\client\curl
 * @object  froq\http\client\curl\Curl
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
final class Curl
{
    /**
     * Client.
     * @var froq\http\client\Client
     */
    private Client $client;

    /**
     * Handle.
     * @var resource
     */
    private $handle;

    /**
     * Not allowed options.
     * @var array<string, int|null>
     */
    private static array $notAllowedOptions = [
        'CURLOPT_CUSTOMREQUEST'  => null,
        'CURLOPT_URL'            => null,
        'CURLOPT_HEADER'         => null,
        'CURLOPT_RETURNTRANSFER' => null,
        'CURLINFO_HEADER_OUT'    => null,
    ];

    /**
     * Constructor.
     * @param froq\http\client\Client $client
     * @throws froq\http\client\CurlException
     */
    public function __construct(Client $client)
    {
        if (!extension_loaded('curl')) {
            throw new CurlException('curl module not loaded');
        }

        $handle = curl_init();
        if (!$handle) {
            throw new CurlException(sprintf('Failed to initialize curl session, error[%s]',
                error_get_last()['message'] ?? 'unknown'));
        }

        $this->client = $client;
        $this->handle = $handle;
    }

    /**
     * Set client.
     * @param  froq\http\client\Client $client
     * @return self
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client.
     * @return froq\http\client\Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Get handle.
     * @return resource
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * Run.
     * @return void
     */
    public function run(): void
    {
        $client = $this->client;
        $client->prepare();

        $curl = $this;
        $curl->applyOptions();

        $handle = $curl->getHandle();

        $result = curl_exec($handle);
        if ($result !== false) {
            $client->end($result, curl_getinfo($handle), null);
        } else {
            $client->end(null, null, new CurlError(curl_errno($handle), curl_error($handle)));
        }

        curl_close($handle);
    }

    /**
     * Apply options.
     * @return void
     * @throws froq\http\client\CurlException
     */
    public function applyOptions(): void
    {
        $client = $this->client;
        $clientOptions = $client->getOptions();

        $request = $client->getRequest();

        [$method, $url, $headers, $body] = [
            $request->getMethod(), $request->getUrl(),
            $request->getHeaders(), $request->getBody()];

        $options = [
            // Immutable (internal) options.
            CURLOPT_CUSTOMREQUEST     => $method, // Prepared, set by request object.
            CURLOPT_URL               => $url,    // Prepared, set by request object.
            CURLOPT_HEADER            => true,    // For proper response headers & body split.
            CURLOPT_RETURNTRANSFER    => true,    // For proper response headers & body split.
            CURLINFO_HEADER_OUT       => true,    // For proper request headers split.
            // Mutable (client) options.
            CURLOPT_AUTOREFERER       => true,
            CURLOPT_FOLLOWLOCATION    => (int) $clientOptions['redir'],
            CURLOPT_MAXREDIRS         => (int) $clientOptions['redirMax'],
            CURLOPT_SSL_VERIFYHOST    => false,
            CURLOPT_SSL_VERIFYPEER    => false,
            CURLOPT_DEFAULT_PROTOCOL  => 'http',
            CURLOPT_DNS_CACHE_TIMEOUT => 3600, // 1 hour.
            CURLOPT_TIMEOUT           => (int) $clientOptions['timeout'],
            CURLOPT_CONNECTTIMEOUT    => (int) $clientOptions['timeoutConnect'],
        ];

        // Request headers.
        $options[CURLOPT_HTTPHEADER][] = 'Expect:';
        foreach ($headers as $name => $value) {
            $options[CURLOPT_HTTPHEADER][] = $name .': '. $value;
        }

        // If body provided, Content-Type & Content-Length added automatically by curl.
        // Else we add them manually, if method is suitable for this.
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
            $options[CURLOPT_HTTPHEADER][] = 'Content-Length: '. strlen((string) $body);
        }

        // Somehow HEAD method is freezing requests and causing timeouts.
        if ($method == 'HEAD') {
            $clientOptions['curl'][CURLOPT_NOBODY] = true;
        }

        // Apply user-provided options.
        if (isset($clientOptions['curl'])) {
            $clientOptions['curl'] = (array) $clientOptions['curl'];

            if (isset($clientOptions['curl'][CURLOPT_HTTP_VERSION])
                   && $clientOptions['curl'][CURLOPT_HTTP_VERSION] == CURL_HTTP_VERSION_2_0) {
                // HTTP/2 requires a https scheme.
                if (strpos($url, 'https') === false) {
                    throw new CurlException('URL scheme must be "https" for HTTP/2 requests');
                }
            }

            foreach ($clientOptions['curl'] as $name => $value) {
                // Check for internal options.
                if (self::optionCheck($name, $foundName)) {
                    throw new CurlException(sprintf(
                        'Not allowed curl option %s given (some options are set internally and '.
                        'not allowed for a proper request/response process, not allowed options'.
                        ': %s', $foundName, join(', ', array_keys(self::$notAllowedOptions))));
                }

                if (is_array($value)) {
                    foreach ($value as $value) {
                        $options[$name][] = $value;
                    }
                } else {
                    $options[$name] = $value;
                }
            }
        }

        curl_setopt_array($this->handle, $options);
    }

    /**
     * Option check.
     * @param  any          $searchValue
     * @param  string|null &$foundName
     * @return bool
     */
    private static function optionCheck($searchValue, string &$foundName = null): bool
    {
        // Cache options for once.
        if (!isset(self::$notAllowedOptions['CURLOPT_CUSTOMREQUEST'])) {
            $names = array_keys(self::$notAllowedOptions);
            foreach (get_defined_constants(true)['curl'] as $name => $value) {
                if (in_array($name, $names)) {
                    self::$notAllowedOptions[$name] = $value;
                }
            }
        }

        // Check options if contain search value.
        foreach (self::$notAllowedOptions as $name => $value) {
            if ($value === $searchValue) {
                $foundName = $name;
                break;
            }
        }

        return $foundName !== null;
    }
}
