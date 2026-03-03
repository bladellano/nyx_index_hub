<?php

namespace Drupal\nyx_index_hub\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Serviço para integração com a API Google Gemini File Search.
 */
class GeminiApiService {

  /**
   * O cliente HTTP.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Construtor.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('nyx_index_hub');
  }

  /**
   * Obtém a URL base da API.
   */
  protected function getBaseUrl() {
    return getenv('INDEX_HUB_URL') ?: 'https://generativelanguage.googleapis.com';
  }

  /**
   * Obtém a API Key.
   */
  protected function getApiKey() {
    return getenv('INDEX_HUB_API_KEY') ?: '';
  }

  /**
   * Obtém o modelo a ser usado.
   */
  protected function getHubModel() {
    return getenv('INDEX_HUB_MODEL') ?: '';
  }

  /**
   * Cria um novo store.
   *
   * @param string $display_name
   *   Nome de exibição do store.
   *
   * @return array|null
   *   Resposta da API ou NULL em caso de erro.
   */
  public function createStore($display_name) {
    $url = $this->getBaseUrl() . '/v1beta/fileSearchStores';
    $api_key = $this->getApiKey();
    try {
      $response = $this->httpClient->request('POST', $url, [
        'headers' => [
          'Content-Type' => 'application/json',
          'x-goog-api-key' => $api_key,
        ],
        'json' => [
          'display_name' => $display_name,
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $this->logger->info('Store criado com sucesso: @name', ['@name' => $display_name]);
      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('Erro ao criar store: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Lista todos os stores.
   *
   * @return array|null
   *   Lista de stores ou NULL em caso de erro.
   */
  public function listStores() {
    $url = $this->getBaseUrl() . '/v1beta/fileSearchStores';
    $api_key = $this->getApiKey();

    try {
      $response = $this->httpClient->request('GET', $url, [
        'query' => [
          'key' => $api_key,
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('Erro ao listar stores: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Deleta um store.
   *
   * @param string $store_name
   *   Nome do store (ex: fileSearchStores/xxx).
   *
   * @return bool
   *   TRUE se deletado com sucesso, FALSE caso contrário.
   */
  public function deleteStore($store_name) {
    $url = $this->getBaseUrl() . '/v1beta/' . $store_name;
    $api_key = $this->getApiKey();

    try {
      $this->httpClient->request('DELETE', $url, [
        'headers' => [
          'x-goog-api-key' => $api_key,
        ],
      ]);

      $this->logger->info('Store deletado com sucesso: @name', ['@name' => $store_name]);
      return TRUE;
    }
    catch (RequestException $e) {
      $this->logger->error('Erro ao deletar store: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Obtém informações de um store específico.
   *
   * @param string $store_name
   *   Nome do store.
   *
   * @return array|null
   *   Dados do store ou NULL em caso de erro.
   */
  public function getStore($store_name) {
    $url = $this->getBaseUrl() . '/v1beta/' . $store_name;
    $api_key = $this->getApiKey();

    try {
      $response = $this->httpClient->request('GET', $url, [
        'query' => [
          'key' => $api_key,
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('Erro ao obter store: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Upload de arquivo para a API.
   *
   * @param string $file_path
   *   Caminho completo do arquivo.
   * @param string $mime_type
   *   Tipo MIME do arquivo.
   * @param string $store_name
   *   Nome do store (ex: fileSearchStores/xxx).
   * @param string|null $display_name
   *   Nome de exibição do arquivo (opcional).
   *
   * @return array|null
   *   Resposta da API ou NULL em caso de erro.
   */
  public function uploadFile($file_path, $mime_type, $store_name, $display_name = NULL) {
    try {
      // Se não foi fornecido display_name, usa o nome do arquivo
      if (empty($display_name)) {
        $display_name = basename($file_path);
      }

      $response = $this->httpClient->request('POST', $this->getBaseUrl() . '/upload/v1beta/' . $store_name . ':uploadToFileSearchStore', [
        'headers' => [
          'X-Goog-Upload-Header-Content-Type' => $mime_type,
          'x-goog-api-key' => $this->getApiKey(),
        ],
        'multipart' => [
          [
            'name' => 'file',
            'contents' => fopen($file_path, 'r'),
            'filename' => $display_name,
          ],
        ],
      ]);

      $result = json_decode($response->getBody()->getContents(), TRUE);

      $this->logger->info('File uploaded: @file to @store', ['@file' => basename($file_path), '@store' => $store_name]);

      // Se retornou uma operação, aguarda ela completar
      if (isset($result['name']) && strpos($result['name'], '/operations/') !== FALSE) {
        // Aguarda até 30 segundos para a operação completar
        $max_attempts = 30;
        $attempt = 0;

        while ($attempt < $max_attempts) {
          sleep(1);
          $attempt++;

          // Consulta status da operação
          $operation_result = $this->getOperation($result['name']);

          if ($operation_result && isset($operation_result['done']) && $operation_result['done'] === TRUE) {
            // Retorna o resultado completo com o file
            if (isset($operation_result['response'])) {
              return $operation_result;
            }

            return $result;
          }
        }
      }

      return $result;
    }
    catch (RequestException $e) {
      $this->logger->error('Upload failed: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Consulta status de uma operação.
   *
   * @param string $operation_name
   *   Nome da operação (ex: fileSearchStores/xxx/operations/yyy).
   *
   * @return array|null
   *   Status da operação ou NULL em caso de erro.
   */
  public function getOperation($operation_name) {
    try {
      $url = $this->getBaseUrl() . '/v1beta/' . $operation_name;

      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'x-goog-api-key' => $this->getApiKey(),
        ],
      ]);

      return json_decode($response->getBody()->getContents(), TRUE);
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to get operation: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Obtém informações de um arquivo.
   *
   * @param string $file_name
   *   Nome do arquivo (ex: files/xxx).
   *
   * @return array|null
   *   Dados do arquivo ou NULL em caso de erro.
   */
  public function getFile($file_name) {
    $url = $this->getBaseUrl() . '/v1beta/' . $file_name;
    $api_key = $this->getApiKey();

    try {
      $response = $this->httpClient->request('GET', $url, [
        'query' => [
          'key' => $api_key,
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('Error getting file: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Deleta um arquivo.
   *
   * @param string $file_name
   *   Nome do arquivo (ex: files/xxx ou fileSearchStores/xxx/documents/yyy).
   *
   * @return bool
   *   TRUE se deletado com sucesso, FALSE caso contrário.
   */
  public function deleteFile($file_name) {
    $url = $this->getBaseUrl() . '/v1beta/' . $file_name;
    $api_key = $this->getApiKey();

    try {
      $this->httpClient->request('DELETE', $url, [
        'headers' => [
          'x-goog-api-key' => $api_key,
        ],
        'query' => [
          'force' => 'true',
        ],
      ]);

      $this->logger->info('File deleted successfully: @name', ['@name' => $file_name]);
      return TRUE;
    }
    catch (RequestException $e) {
      $this->logger->error('Error deleting file: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Lista documentos de um file search store.
   *
   * @param string $store_name
   *   Nome do store (ex: fileSearchStores/xxx).
   *
   * @return array|null
   *   Lista completa de documentos (todas as páginas) ou NULL em caso de erro.
   */
  public function listStoreDocuments($store_name) {
    $url = $this->getBaseUrl() . '/v1beta/' . $store_name . '/documents';
    $api_key = $this->getApiKey();
    $all_documents = [];
    $page_token = NULL;

    try {
      // Loop através de todas as páginas
      do {
        $query = [];

        if ($page_token) {
          $query['pageToken'] = $page_token;
        }

        $response = $this->httpClient->request('GET', $url, [
          'headers' => [
            'x-goog-api-key' => $api_key,
          ],
          'query' => $query,
        ]);

        $data = json_decode($response->getBody()->getContents(), TRUE);

        // Adiciona documentos da página atual
        if (isset($data['documents']) && is_array($data['documents'])) {
          $all_documents = array_merge($all_documents, $data['documents']);
        }

        // Verifica se há próxima página
        $page_token = $data['nextPageToken'] ?? NULL;

        // Log para debug
        if ($page_token) {
          $this->logger->info('Fetching next page of documents for @store', ['@store' => $store_name]);
        }

      } while ($page_token);

      $this->logger->info('Listed @count documents from @store', [
        '@count' => count($all_documents),
        '@store' => $store_name,
      ]);

      return ['documents' => $all_documents];
    }
    catch (RequestException $e) {
      $this->logger->error('Error listing store documents: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Gera conteúdo usando o modelo Gemini com busca em file stores.
   *
   * @param string $prompt
   *   Texto da pergunta/prompt.
   * @param array $store_names
   *   Lista de store names (ex: ['fileSearchStores/xxx', 'fileSearchStores/yyy']).
   * @param string $model
   *   Nome do modelo (padrão: gemini-2.5-flash).
   *
   * @return array|null
   *   Resposta da API ou NULL em caso de erro.
   */
  public function generateContent($prompt, array $store_names) {
    $url = $this->getBaseUrl() . '/v1beta/models/' . $this->getHubModel() . ':generateContent';
    $api_key = $this->getApiKey();

    $payload = [
      'contents' => [
        [
          'parts' => [
            ['text' => $prompt],
          ],
        ],
      ],
      'tools' => [
        [
          'fileSearch' => [
            'fileSearchStoreNames' => $store_names,
          ],
        ],
      ],
    ];

    try {
      $response = $this->httpClient->request('POST', $url, [
        'headers' => [
          'Content-Type' => 'application/json',
          'x-goog-api-key' => $api_key,
        ],
        'json' => $payload,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $this->logger->info('Content generated for stores: @stores', ['@stores' => implode(', ', $store_names)]);
      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('Error generating content: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

}
