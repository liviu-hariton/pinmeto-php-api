<?php

declare(strict_types=1);

namespace LHDev\PinMeToAPI;

use Exception;
use stdClass;

class PinMeToAPI {
    const _ENDPOINT_LIVE = 'https://api.pinmeto.com';
    const _ENDPOINT_TEST = 'https://api.test.pinmeto.com';

    private string $endpoint;

    const _API_VERSION_LOCATIONS = '2';
    const _API_VERSION_METRICS = '3';

    const _NETWORKS = [
        'google', 'facebook', 'bing', 'apple'
    ];

    /**
     * The PinMeTo `App ID`
     *
     * @var string
     */
    private string $app_id;

    /**
     * The PinMeTo `App Secret`
     *
     * @var string
     */
    private string $app_secret;

    /**
     * The PinMeTo `Account ID`
     *
     * @var string
     */
    private string $account_id;

    private string $token;

    /**
     * Constructor
     * Initialize it with your `Account ID`, `App ID` and `App Secret` values. You can get them / generate them inside
     * your PinMeTo Account Settings - https://places.pinmeto.com/account-settings/
     *
     * @param array $config_data Should contain the keys:<br>
     * 'app_id' - the PinMeTo `App ID`<br>
     * 'app_secret' - the PinMeTo `App Secret`<br>
     * 'account_id' - the PinMeTo `Account ID`<br>
     * 'mode' - the library working mode: `live` or `test` (defaults to `test`)
     *
     * @throws Exception
     */
    public function __construct(array $config_data)
    {
        $this->saveConfig($config_data);

        $this->token = $this->authenticate();
    }

    /**
     * Validate configuration data
     *
     * @param array $config_data
     * @throws Exception
     */
    private function validateConfig(array $config_data): void
    {
        $required_credentials = ['app_id', 'app_secret', 'account_id'];

        // Check if the required credentials are provided
        foreach($required_credentials as $credential) {
            if(empty($config_data[$credential])) {
                throw new Exception("You need to provide the PinMeTo API credentials [`".implode('`, `', $required_credentials)."`]");
            }
        }

        // Check if the working mode is set
        if(empty($config_data['mode']) || !in_array($config_data['mode'], ['live', 'test'])) {
            throw new Exception("You need to provide the library working mode: `live` or `test`");
        }
    }

    /**
     * @throws Exception
     */
    private function saveConfig($config_data): void
    {
        $this->validateConfig($config_data);

        $this->app_id = $config_data['app_id'];
        $this->app_secret = $config_data['app_secret'];
        $this->account_id = $config_data['account_id'];

        $this->endpoint = $this->setEndpoint($config_data['mode']);
    }

    /**
     * Set the API endpoint based on the library's working mode. Possible values: `live` or `test`
     *
     * @var string $working_mode
     * @return string
     */
    private function setEndpoint(string $working_mode): string
    {
        return $working_mode === 'live' ? self::_ENDPOINT_LIVE : self::_ENDPOINT_TEST;
    }

    /**
     * Get the API Access Token and cache it in the current Session or regenerate it if it has expired
     *
     * @return string|null
     * @throws Exception
     */
    private function authenticate(): string|null
    {
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Check if the token is set or it's not expired
        if(!isset($_SESSION['_pinmeto_token']) || $_SESSION['_pinmeto_token_expire'] < time()) {
            $result = json_decode($this->connect('oauth/token', ['grant_type' => 'client_credentials'], 'POST'));

            if(empty($result->access_token)) {
                throw new Exception("Could not retrieve the token's value");
            }

            $this->token = $result->access_token;

            $_SESSION['_pinmeto_token'] = $this->token;
            $_SESSION['_pinmeto_token_expire'] = time() + $result->expires_in;
        }

        return $_SESSION['_pinmeto_token'];
    }

    /**
     * Perform the actual connection and get the requested data, if available
     *
     * @param string $call The API method to call
     * @param array $parameters Any data that should be transmitted
     * @param string $method The HTTP method
     * @return bool|string|stdClass
     * @throws Exception
     */
    private function connect(string $call, array $parameters = array(), string $method = 'GET'): bool|string|stdClass
    {
        $ch = curl_init();

        // In case of authorization
        if(str_contains($call, "token")) {
            $url = $this->endpoint.'/'.$call;

            $headers = array(
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic '.base64_encode($this->app_id.':'.$this->app_secret)
            );
        } else {
            $headers = array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->token
            );
        }

        // In case of locations V2 API
        if(!str_contains($call, "token") && !str_contains($call, "google") && !str_contains($call, "facebook")) {
            $url = $this->endpoint.'/v'.self::_API_VERSION_LOCATIONS.'/'.$this->account_id.'/'.$call;
        }

        if(str_contains($call, "categories/")) {
            $url = $this->endpoint.'/v'.self::_API_VERSION_LOCATIONS.'/'.$this->account_id.'/'.$call;
        }

        // In case of metrics V3 API
        if((str_contains($call, "google") || str_contains($call, "facebook")) && !str_contains($call, "categories")) {
            $url = $this->endpoint.'/listings/v'.self::_API_VERSION_METRICS.'/'.$this->account_id.'/'.$call;
        }

        if($method === 'GET') {
            if(is_array($parameters) && count($parameters) > 0) {
                $url .= '?'.http_build_query($parameters);
            }
        }

        if(in_array($method, ['POST', 'PUT'])) {
            if(is_array($parameters) && count($parameters) > 0) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
            }
        }

        if($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        }

        if($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $result = curl_exec($ch);

        if($result === false) {
            $curl_errno = curl_errno($ch);
            $curl_error = curl_error($ch);

            throw match($curl_errno) {
                CURLE_COULDNT_CONNECT => new Exception('Could not connect to API (error '.$curl_errno.': '.$curl_error.')'),
                CURLE_OPERATION_TIMEOUTED => new Exception('Operation timed out (error '.$curl_errno.': '.$curl_error.')'),
                CURLE_SSL_CACERT => new Exception('Peer certificate cannot be authenticated (error '.$curl_errno.': '.$curl_error.')'),
                CURLE_TOO_MANY_REDIRECTS => new Exception('The amount of redirections (error '.$curl_errno.': '.$curl_error.')'),
                default => new Exception('cURL Error (error '.$curl_errno.': '.$curl_error.')'),
            };
        }

        curl_close($ch);

        return $result;
    }

    /**
     * Get all available locations
     *
     * @param array (optional) $parameters Possible keys:<br>
     * `pagesize` - Number of locations that should be returned (default: 100, maximum: 250)<br>
     * `next` - Id of starting point to next page<br>
     * `before` - Id of starting point to previous page
     *
     * @return bool|string|stdClass
     * @throws Exception
     */
    public function getLocations(array $parameters = []): bool|string|stdClass
    {
        return $this->connect('locations', $parameters);
    }

    /**
     * Get the details for a specific location
     *
     * @param string $store_id
     * @return bool|stdClass|string
     * @throws Exception
     */
    public function getLocation(string $store_id): bool|string|stdClass
    {
        return $this->connect('locations/'.$store_id);
    }

    /**
     * Create a new location
     *
     * @param array $parameters
     * @param bool $upsert
     * @return bool|string|stdClass
     * @throws Exception
     */
    public function createLocation(array $parameters = [], bool $upsert = false): bool|string|stdClass
    {
        return $this->connect('locations/'.($upsert ? '?upsert=true' : ''), $parameters, 'POST');
    }

    /**
     * Update an existing location
     *
     * @param string $store_id
     * @param array $parameters
     * @return bool|string|stdClass
     * @throws Exception
     */
    public function updateLocation(string $store_id, array $parameters = []): bool|string|stdClass
    {
        return $this->connect('locations/'.$store_id, $parameters, 'PUT');
    }

    /**
     * Get the metrics data for all locations or for a specific location
     *
     * @param string $source The data source (`google` or `facebook`))
     * @param string $from_date The start date of the calendaristic interval, in the YYYY-MM-DD format
     * @param string $to_date The end date of the calendaristic interval, in the YYYY-MM-DD format
     * @param array $fields The specific fields values that should be returned (if none provided, it returns everything)).
     * All available fields are described here https://api.pinmeto.com/documentation/v3/
     *
     * @param string $store_id (optional) The specific Store ID
     * @return bool|stdClass|string
     * @throws Exception
     */
    public function getMetrics(string $source, string $from_date, string $to_date, array $fields = [], string $store_id = ''): bool|string|stdClass
    {
        if(!in_array($source, ['google', 'facebook'])) {
            throw new Exception("You need to provide a valid source - `google` or `facebook`");
        }

        $parameters = [
            'from' => $from_date,
            'to' => $to_date,
        ];

        if(count($fields) > 0) {
            $parameters['fields'] = implode(",", $fields);
        }

        return $this->connect('insights/'.$source.'/'.($store_id !== '' ? $store_id : ''), $parameters);
    }

    /**
     * Get the Google keywords for all locations or for a specific location
     *
     * @param string $from_date The start date of the calendaristic interval, in the YYYY-MM format
     * @param string $to_date The end date of the calendaristic interval, in the YYYY-MM format
     * @param string $store_id (optional) The specific Store ID
     * @return bool|string|stdClass
     * @throws Exception
     */
    public function getKeywords(string $from_date, string $to_date, string $store_id = ''): bool|string|stdClass
    {
        $parameters = [
            'from' => $from_date,
            'to' => $to_date,
        ];

        return $this->connect('insights/google-keywords/'.($store_id !== '' ? $store_id : ''), $parameters);
    }

    /**
     * Get the ratings data for all locations or for a specific location
     *
     * @param string $source The data source (`google` or `facebook`))
     * @param string $from_date The start date of the calendaristic interval, in the YYYY-MM format
     * @param string $to_date The end date of the calendaristic interval, in the YYYY-MM format
     * @param string $store_id (optional) The specific Store ID
     * @return bool|string|stdClass
     * @throws Exception
     */
    public function getRatings(string $source, string $from_date, string $to_date, string $store_id = ''): bool|string|stdClass
    {
        if(!in_array($source, ['google', 'facebook'])) {
            throw new Exception("You need to provide a valid source - `google` or `facebook`");
        }

        $parameters = [
            'from' => $from_date,
            'to' => $to_date,
        ];

        return $this->connect('ratings/'.$source.'/'.($store_id !== '' ? $store_id : ''), $parameters);
    }

    /**
     * Get the categories for a specific network
     *
     * @param string $network The network name (`google` or `apple` or `facebook` or `bing`)
     * @return bool|string|stdClass
     * @throws Exception
     */
    public function getNetworkCategories(string $network): bool|string|stdClass
    {
        if(!in_array($network, self::_NETWORKS)) {
            throw new Exception("The provided network name is not valid. Possible values: `".implode('`, `', self::_NETWORKS)."`");
        }

        return $this->connect('categories/'.$network);
    }
}