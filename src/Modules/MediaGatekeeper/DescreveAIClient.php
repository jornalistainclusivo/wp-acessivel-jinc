<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\MediaGatekeeper;

class DescreveAIClient
{
    /**
     * Dispara requisição HTTP POST Multipart para o endpoint da DescreveAI.
     *
     * @param string $file_path Caminho absoluto do arquivo da imagem
     * @param string $endpoint URL da API DescreveAI
     * @param string $api_key Chave de API
     * @param int $timeout Timeout da requisição
     * @return array { success: bool, alt: string|null, error: string|null, status_code: int }
     */
    public function analyze(string $file_path, string $endpoint, string $api_key, int $timeout): array
    {
        // Geração do Boundary - restrição: iniciar com ---JINC
        $boundary = '---JINCBoundary' . md5(uniqid('', true));

        if (!file_exists($file_path)) {
            return [
                'success' => false,
                'error' => 'File not found',
                'status_code' => 500
            ];
        }

        $file_contents = file_get_contents($file_path);
        $filename = basename($file_path);
        
        // Montagem do Payload multipart/form-data conforme RFC
        $body  = "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$filename}\"\r\n";
        $body .= "Content-Type: application/octet-stream\r\n\r\n";
        $body .= $file_contents . "\r\n";
        $body .= "--{$boundary}--\r\n";

        $args = [
            'timeout' => $timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
            ],
            'body' => $body,
        ];

        // Disparo exclusivo usando wp_remote_post
        $response = wp_remote_post($endpoint, $args);

        // Tratamento da Resposta
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
                'status_code' => 500
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return [
                'success' => false,
                'error' => 'API Error',
                'status_code' => $code
            ];
        }

        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if (isset($data['success']) && $data['success'] === true && (!empty($data['data']['alt']) || !empty($data['data']['description']))) {
            return [
                'success' => true,
                'alt' => $data['data']['alt'] ?? $data['data']['description'],
                'description' => $data['data']['description'] ?? '',
                'status_code' => 200
            ];
        }

        return [
            'success' => false,
            'error' => 'Invalid JSON response from AI',
            'status_code' => 500
        ];
    }
}
