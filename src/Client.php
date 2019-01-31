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

/**
 * @package    Froq
 * @subpackage Froq\Http\Client
 * @object     Froq\Http\Client\Client
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
final class Client
{
    /**
     * Request.
     * @var Froq\Http\Client\Request
     */
    private $request;

    /**
     * Response.
     * @var Froq\Http\Client\Response
     */
    private $response;

    /**
     * Result.
     * @var string
     */
    private $result;

    /**
     * Result info.
     * @var array
     */
    private $resultInfo;

    /**
     * Options.
     * @var array
     */
    private $options = [
        'redir' => true, 'redirMax' => 3,
        'timeout' => 5,  'timeoutConnect' => 3,
    ];

    /**
     * Arguments.
     * @var array
     */
    private $arguments = [
        'method' => 'GET'
    ];

    /**
     * Methods.
     * @var array
     */
    private $methods = [
        'head', 'options', 'get', 'post', 'put', 'patch', 'delete'
    ];

    /**
     * Constructor.
     * @param array|null $options
     */
    public function __construct(array $options = null)
    {
        $this->setOptions($options ?? []);
    }

    /**
     * Call magic.
     * @param  string $method
     * @param  array  $methodArguments
     * @return Froq\Http\Client\Response
     * @throws Froq\Http\Client\ClientException
     */
    public function __call(string $method, array $methodArguments): Response
    {
        if (in_array($method, $this->methods)) {
            $methodArguments[1] = $methodArguments[1] ?? [];
            $methodArguments[1]['method'] = $method; // request method actually
            return call_user_func_array([$this, 'send'], $methodArguments);
        }

        throw new ClientException(sprintf("No method '%s' found (callable methods: '%s')",
            $method, join(',', $this->methods)));
    }

    /**
     * Get request.
     * @return ?Froq\Http\Client\Request
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * Get response.
     * @return ?Froq\Http\Client\Response
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Get result.
     * @return ?string
     */
    public function getResult(): ?string
    {
        return $this->result;
    }

    /**
     * Get result info.
     * @param  string|null $key
     * @return any|null
     */
    public function getResultInfo(string $key = null)
    {
        return ($key === null) ? $this->resultInfo : $this->resultInfo[$key] ?? null;
    }

    /**
     * Set options.
     * @param  array $options
     * @return self
     */
    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Get options.
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set option.
     * @param string $name
     * @param any    $value
     */
    public function setOption(string $name, $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Get option.
     * @param  string $name
     * @return any|null
     */
    public function getOption(string $name)
    {
        return $this->options[$name] ?? null;
    }

    /**
     * Set arguments.
     * @param  array $arguments
     * @return self
     */
    public function setArguments(array $arguments): self
    {
        foreach ($arguments as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $subName => $subValue) {
                    $this->arguments[$name][$subName] = $subValue;
                }
            } else { $this->arguments[$name] = $value; }
        }

        return $this;
    }

    /**
     * Get arguments.
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Set argument.
     * @param  string $name
     * @param  any    $value
     * @return self
     */
    public function setArgument(string $name, $value): self
    {
        $this->setArguments([$name => $value]);

        return $this;
    }

    /**
     * Get argument.
     * @param  string $name
     * @return any
     */
    public function getArgument(string $name)
    {
        return $this->getArguments()[$name] ?? null;
    }

    /**
     * Get methods.
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Send.
     * @param  string     $url
     * @param  array|null $arguments
     * @return self
     * @throws Froq\Http\Client\ClientException
     */
    public function send(string $url, array $arguments = null): self
    {
        $this->reset();

        [$url, $urlParams] = Util::parseUrl($url);
        $this->request->setUrl($url)
                      ->setUrlParams($urlParams);

        $this->setArguments($arguments ?? []);

        $arguments = $this->getArguments();
        if (!empty($arguments)) {
            $this->request->setMethod($arguments['method']);

            if (isset($arguments['headers'])) {
                $this->request->setHeaders($arguments['headers']);
            }
            if (isset($arguments['urlParams'])) {
                $this->request->setUrlParams($arguments['urlParams']);
            }

            // body accepted for all methods..
            // @see https://stackoverflow.com/questions/978061/http-get-with-request-body
            $body = $arguments['body'] ?? null;
            if ($body !== null) {
                $rawBody = $body;
                $bodyType = gettype($body);
                if ($bodyType == 'array' || $bodyType == 'object') {
                    $contentType = (string) $this->request->getHeader('Content-Type');
                    $rawBody = strpos($contentType, '/json') || strpos($contentType, '+json')
                        ? Util::jsonEncode($rawBody, $arguments['jsonOptions'] ?? [])
                        : Util::buildQuery($rawBody);
                }

                $this->request->setBody($body)
                              ->setRawBody($rawBody);
            }
        }

        $method = $this->request->getMethod();
        $body = $this->request->getRawBody();

        $curlHandle = curl_init();
        $curlOptions = [
            CURLOPT_URL => $this->request->getFullUrl(),
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_FOLLOWLOCATION => $this->options['redir'],
            CURLOPT_MAXREDIRS => $this->options['redirMax'],
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_DEFAULT_PROTOCOL => 'http',
            CURLOPT_DNS_CACHE_TIMEOUT => 3600, // 1 hour
            CURLOPT_TIMEOUT => $this->options['timeout'],
            CURLOPT_CONNECTTIMEOUT => $this->options['timeoutConnect'],
            // CURLOPT_TIMEOUT_MS => $this->options['timeout'], // @debug
            // CURLOPT_CONNECTTIMEOUT_MS => $this->options['timeoutConnect'], // @debug
            CURLINFO_HEADER_OUT => true, // request headers as string
        ];

        // headers
        $curlOptions[CURLOPT_HTTPHEADER][] = 'Expect:';
        foreach ($this->request->getHeaders() as $name => $value) {
            $curlOptions[CURLOPT_HTTPHEADER][] = sprintf('%s: %s', $name, $value);
        }

        // body
        if ($body !== null) {
            $curlOptions[CURLOPT_POSTFIELDS] = $body;
            // @debug these headers should be added automatically by curl
            // if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            //     $curlOptions[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
            //     $curlOptions[CURLOPT_HTTPHEADER][] = 'Content-Length: '. strlen((string) $body);
            // }
        }

        // user provided options
        if (isset($arguments['curlOptions'])) {
            static $notAllowedOptions = [CURLOPT_URL, CURLOPT_HEADER, CURLOPT_CUSTOMREQUEST,
                CURLINFO_HEADER_OUT];

            foreach ($arguments['curlOptions'] as $name => $value) {
                // these are already set internally
                if (in_array($name, $notAllowedOptions)) {
                    throw new ClientException('Not allowed curl option given (not allowed options: '.
                        'CURLOPT_URL, CURLOPT_HEADER, CURLOPT_CUSTOMREQUEST, CURLINFO_HEADER_OUT)');
                }

                if (is_array($value)) {
                    foreach ($value as $value) {
                        $curlOptions[$name][] = $value;
                    }
                } else { $curlOptions[$name] = $value; }
            }
        }

        curl_setopt_array($curlHandle, $curlOptions);

        $this->result = curl_exec($curlHandle);
        if ($this->result === false) {
            throw new ClientException(curl_error($curlHandle), curl_errno($curlHandle));
        }

        $this->resultInfo = curl_getinfo($curlHandle);

        curl_close($curlHandle);

        // set request headers
        if (isset($this->resultInfo['request_header'])) {
            $this->request->setHeaders(Util::parseHeaders($this->resultInfo['request_header'], false));
        }

        $result = explode("\r\n\r\n", $this->result);
        // drop redirect etc. headers
        while (count($result) > 2) {
            array_shift($result);
        }

        // split headers/body parts
        @ [$headers, $body] = $result;

        if ($headers != null) {
            $headers = Util::parseHeaders($headers);
            if (isset($headers[0])) {
                $this->response->setStatus($headers[0]);
            }

            $this->response->setHeaders($headers);
        }

        if ($body != null) {
            $rawBody = $body;
            $contentEncoding = $this->response->getHeader('Content-Encoding');
            $contentType = (string) $this->response->getHeader('Content-Type');

            // decode gzip (if zipped)
            if ($contentEncoding == 'gzip' || (strpos($contentType, '/octet-stream')
                && substr($this->request->getUrl(), -3) == '.gz')) {
                $body = $rawBody = gzdecode($body);
            }

            // decode json
            if (strpos($contentType, '/json') || strpos($contentType, '+json')) {
                $body = Util::jsonDecode($body, $arguments['jsonOptions'] ?? []);
            } elseif (strpos($contentType, '/xml')) {
                $body = Util::parseXml($body, $arguments['xmlOptions']);
            }

            $this->response->setBody($body)
                           ->setRawBody($rawBody);
        }

        return $this;
    }

    /**
     * Reset.
     * @return void
     */
    public function reset(): void
    {
        $this->request = new Request();
        $this->response = new Response();

        $this->result = $this->resultInfo = null;
    }

    /**
     * Ok.
     * @return bool
     */
    public function ok(): bool
    {
        return ($this->response->getStatusCode() == 200);
    }

    /**
     * Is success.
     * @return bool
     */
    public function isSucces(): bool
    {
        return ($statusCode = $this->response->getStatusCode())
            && ($statusCode >= 200 && $statusCode <= 299);
    }

    /**
     * Is failure.
     * @return bool
     */
    public function isFailure(): bool
    {
        return ($this->response->getStatusCode() >= 400);
        // return ($statusCode = $this->response->getStatusCode())
        //     && (($statusCode >= 400 && $statusCode <= 499) ||
        //         ($statusCode >= 500 && $statusCode <= 599));
    }

    /**
     * Is redirect.
     * @return bool
     */
    public function isRedirect(): bool
    {
        return ($statusCode = $this->response->getStatusCode())
            && ($statusCode >= 300 && $statusCode <= 399);
    }

    /**
     * Set user agent.
     * @param  ?string $userAgent Null allows to remove User-Agent header.
     * @return self
     */
    public function setUserAgent(?string $userAgent): self
    {
        return $this->setArgument('headers', ['User-Agent' => $userAgent]);
    }

    /**
     * Set authorization.
     * @param ?string $type
     * @param string  $credentials
     * @param bool    $encodeBasicCredentials
     */
    public function setAuthorization(?string $type, string $credentials = '',
        bool $encodeBasicCredentials = true): self
    {
        if ($type != '') {
            // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Authorization#Directives
            $authorization = $type .' '. ($encodeBasicCredentials && strtolower($type) == 'basic'
                ? base64_encode($credentials) : $credentials);
        } elseif ($credentials != '') {
            $authorization = $credentials;
        }

        return $this->setArgument('headers', ['Authorization' => $authorization ?? null]);
    }
}
