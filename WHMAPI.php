<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class TieredReseller_WHMAPI {
    private $whmHost;
    private $whmUsername;
    private $whmAuthToken;
    private $verifySSL;
    private $connectTimeout = 30;
    private $responseTimeout = 45;
    private $lastError = null;

    public function __construct() {
        $this->loadConfig();
    }

    /**
     * Load WHM API configuration from database
     */
    private function loadConfig() {
        $config = Capsule::table('tbladdonmodules')
            ->where('module', 'tiered_reseller')
            ->pluck('value', 'setting');
        
        $this->whmHost = $config['whm_server_ip'] ?? '127.0.0.1';
        $this->whmUsername = 'root'; // WHM always uses root for API auth
        $this->whmAuthToken = $config['whm_api_token'] ?? '';
        $this->verifySSL = $config['whm_verify_ssl'] ?? true;
    }

    /**
     * Get account count for a specific reseller
     *
     * @param string $resellerUsername
     * @return int
     */
    public function getAccountCount($resellerUsername) {
        try {
            if (empty($resellerUsername)) {
                throw new Exception("No WHM username provided");
            }

            // Sanitize username
            $cleanUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $resellerUsername);
            if ($cleanUsername !== $resellerUsername) {
                throw new Exception("Invalid WHM username format");
            }

            // API endpoint with reseller filtering
            $endpoint = "/json-api/listaccts?api.version=1&searchtype=owner&search=$cleanUsername";

            $response = $this->makeRequest($endpoint);

            if (!$response || !isset($response->metadata->result)) {
                throw new Exception("Invalid API response format");
            }

            if ($response->metadata->result == 0) {
                $error = $response->metadata->reason ?? 'Unknown WHM API error';
                throw new Exception("WHM API error: $error");
            }

            // Count matching accounts
            if (empty($response->data->acct)) {
                return 0;
            }

            return count($response->data->acct);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logError($resellerUsername, $e);
            return 0;
        }
    }

    /**
     * Test API connection
     *
     * @return array
     */
    public function testConnection() {
        try {
            $endpoint = "/json-api/account_user_usage?api.version=1";
            $response = $this->makeRequest($endpoint);

            if (!$response || !isset($response->metadata->result)) {
                throw new Exception("Invalid API response format");
            }

            if ($response->metadata->result == 0) {
                $error = $response->metadata->reason ?? 'Unknown WHM API error';
                throw new Exception("WHM API error: $error");
            }

            $whmVersion = $this->getWHMVersion();

            return [
                'success' => true,
                'version' => $whmVersion,
                'message' => "Successfully connected to WHM $whmVersion"
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get WHM version info
     *
     * @return string
     */
    private function getWHMVersion() {
        $response = $this->makeRequest("/json-api/version?api.version=1");
        return $response->data->version ?? 'unknown';
    }

    /**
     * Make authenticated request to WHM API
     *
     * @param string $endpoint
     * @return stdClass
     * @throws Exception
     */
    private function makeRequest($endpoint) {
        if (empty($this->whmAuthToken)) {
            throw new Exception("WHM API token not configured");
        }

        $ch = curl_init();
        $url = "https://{$this->whmHost}:2087{$endpoint}";

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->responseTimeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_SSL_VERIFYPEER => $this->verifySSL,
            CURLOPT_SSL_VERIFYHOST => $this->verifySSL ? 2 : 0,
            CURLOPT_HTTPHEADER => [
                "Authorization: whm {$this->whmUsername}:{$this->whmAuthToken}",
                "User-Agent: WHMCS-Tiered-Reseller/1.0"
            ],
            CURLOPT_CAINFO => $this->verifySSL ? $this->getCABundlePath() : null
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("CURL Error: $error");
        }

        if ($httpCode == 401) {
            throw new Exception("WHM API authentication failed - check token permissions");
        }

        if ($httpCode != 200) {
            throw new Exception("WHM API returned HTTP $httpCode");
        }

        $decoded = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: " . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Get path to CA bundle for SSL verification
     *
     * @return string
     */
    private function getCABundlePath() {
        // Try system CA bundle first
        $paths = [
            '/etc/ssl/certs/ca-certificates.crt',   // Debian/Ubuntu
            '/etc/pki/tls/certs/ca-bundle.crt',     // RHEL/CentOS
            '/usr/local/share/certs/ca-root-nss.crt', // FreeBSD
            '/etc/ssl/cert.pem',                    // macOS
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Fall back to WHMCS bundled CA cert
        return ROOTDIR . '/includes/cacert.pem';
    }

    /**
     * Log API errors
     *
     * @param string $resellerUsername
     * @param Exception $e
     */
    private function logError($resellerUsername, Exception $e) {
        $errorMessage = "WHM API Error for {$resellerUsername}: " . $e->getMessage();
        logActivity("[Tiered Reseller] $errorMessage");

        Capsule::table('mod_tiered_reseller_logs')->insert([
            'userid' => null,
            'action' => 'api_error',
            'details' => $errorMessage,
            'created_at' => Capsule::raw('NOW()')
        ]);
    }

    /**
     * Get the last error that occurred
     *
     * @return string|null
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Validate WHM username (security filtering)
     *
     * @param string $username
     * @return bool
     */
    public function validateWHMUsername($username) {
        return preg_match('/^[a-zA-Z0-9_]+$/', $username) && strlen($username) <= 16;
    }
}
