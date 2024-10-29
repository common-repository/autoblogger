<?php
class AutoBloggerAPIClient
{
    private $apiKey;
    private $baseUrl = 'https://autoblogger-api.otherweb.com/api/v1';

    public function __construct()
    {
        $this->apiKey = get_option('autoblogger_settings')['api_key'];
    }

    public function fetchPosts()
    {
        return $this->makeRequest('/integrations/wp/posts?page=1&page_size=100');
    }

    public function validateApiKey()
    {
        $response = $this->makeRequest('/integrations/wp/auth');
        if (!is_wp_error($response) && isset($response['is_valid'])) {
            $status = $response['is_valid'] ? 'Valid' : 'Invalid';
            update_option('autoblogger_token_status', $status);
            return $response['is_valid'];
        }
        return false;
    }

    private function makeRequest($endpoint)
    {
        $url = $this->baseUrl . $endpoint;
        $args = [
            'headers' => [
                'Accept' => 'application/json',
                'x-api-key' => $this->apiKey
            ],
            'timeout' => 20
        ];

        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            autoblogger_log('API request error: ' . $response->get_error_message(), 'error');
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code != 200) {
            autoblogger_log("API request failed with status code: $response_code, response: $response_body", 'error');
            return new WP_Error('api_error', "API request failed with status code: $response_code");
        }

        return json_decode($response_body, true);
    }
}
?>
