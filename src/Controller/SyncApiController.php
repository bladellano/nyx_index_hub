<?php

namespace Drupal\nyx_index_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\nyx_index_hub\Service\GeminiApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller para API de sincronização.
 */
class SyncApiController extends ControllerBase {

  /**
   * Gemini API service.
   *
   * @var \Drupal\nyx_index_hub\Service\GeminiApiService
   */
  protected $apiService;

  /**
   * Construtor.
   */
  public function __construct(GeminiApiService $api_service) {
    $this->apiService = $api_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nyx_index_hub.api_service')
    );
  }

  /**
   * Valida se store pertence ao grupo.
   */
  public function validateStore(Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);

      $group_key = $data['group_key'] ?? '';
      $store_name = $data['store_name'] ?? '';

      if (empty($group_key) || empty($store_name)) {
        return new JsonResponse([
          'valid' => FALSE,
          'message' => 'Group key e store name são obrigatórios',
        ], 400);
      }

      // Carrega grupo pelo UUID (group_key)
      $groups = \Drupal::entityTypeManager()
        ->getStorage('nyx_group')
        ->loadByProperties(['group_key' => $group_key]);

      if (empty($groups)) {
        return new JsonResponse([
          'valid' => FALSE,
          'message' => 'Grupo não encontrado',
        ], 404);
      }

      $group = reset($groups);
      $group_id = $group->id();

      // Busca storages desse grupo que tenham o store_name
      $storages = \Drupal::entityTypeManager()
        ->getStorage('nyx_storage')
        ->loadByProperties([
          'group_id' => $group_id,
          'store_name' => $store_name,
        ]);

      $valid = !empty($storages);

      return new JsonResponse([
        'valid' => $valid,
        'message' => $valid ? 'Store válida' : 'Store não pertence ao grupo',
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('nyx_index_hub')->error('Erro em validateStore: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'valid' => FALSE,
        'message' => 'Erro interno: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Recebe upload de conteúdo.
   */
  public function uploadContent(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    $group_key = $data['group_key'] ?? '';
    $store_name = $data['store_name'] ?? '';
    $content_id = $data['content_id'] ?? '';
    $markdown = $data['markdown'] ?? '';

    // Validações básicas
    if (empty($group_key) || empty($store_name) || empty($content_id) || empty($markdown)) {
      return $this->errorResponse('Dados obrigatórios ausentes', 400);
    }

    // Valida store
    $validation = $this->validateStoreAccess($group_key, $store_name);
    if ($validation !== TRUE) {
      return $validation;
    }

    try {
      $state = \Drupal::state();
      $state_key = $this->getStateKey($store_name, $content_id);
      $existing_file = $state->get($state_key);

      // Deleta arquivo antigo se existir
      if ($existing_file && $this->apiService->deleteFile($existing_file)) {
        \Drupal::logger('nyx_index_hub')->info('Arquivo antigo deletado: @file', ['@file' => $existing_file]);
      }

      // Upload novo arquivo
      $result = $this->uploadMarkdownFile($markdown, $store_name);

      if ($result && isset($result['response']['documentName'])) {
        $file_name = $result['response']['documentName'];
        $state->set($state_key, $file_name);

        \Drupal::logger('nyx_index_hub')->info('Arquivo @file salvo para content_id @id no store @store', [
          '@file' => $file_name,
          '@id' => $content_id,
          '@store' => $store_name,
        ]);

        return new JsonResponse([
          'success' => TRUE,
          'message' => 'Conteúdo enviado com sucesso',
          'file_name' => $file_name,
          'replaced_file' => $existing_file ?: NULL,
        ]);
      }

      return $this->errorResponse('Erro ao fazer upload', 500);
    }
    catch (\Exception $e) {
      \Drupal::logger('nyx_index_hub')->error('Erro no upload: @message', ['@message' => $e->getMessage()]);
      return $this->errorResponse('Erro interno no servidor', 500);
    }
  }

  /**
   * Remove conteúdo.
   */
  public function deleteContent(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    $group_key = $data['group_key'] ?? '';
    $store_name = $data['store_name'] ?? '';
    $content_id = $data['content_id'] ?? '';

    if (empty($group_key) || empty($store_name) || empty($content_id)) {
      return $this->errorResponse('Dados obrigatórios ausentes', 400);
    }

    // Valida store
    $validation = $this->validateStoreAccess($group_key, $store_name);
    if ($validation !== TRUE) {
      return $validation;
    }

    try {
      $state = \Drupal::state();
      $state_key = $this->getStateKey($store_name, $content_id);
      $existing_file = $state->get($state_key);

      if (!$existing_file) {
        return $this->errorResponse('Arquivo não encontrado para este content_id', 404);
      }

      if ($this->apiService->deleteFile($existing_file)) {
        $state->delete($state_key);

        \Drupal::logger('nyx_index_hub')->info('Arquivo @file deletado com sucesso para content_id @id', [
          '@file' => $existing_file,
          '@id' => $content_id,
        ]);

        return new JsonResponse([
          'success' => TRUE,
          'message' => 'Conteúdo removido com sucesso',
          'deleted_file' => $existing_file,
        ]);
      }

      return $this->errorResponse('Erro ao deletar arquivo da API Gemini', 500);
    }
    catch (\Exception $e) {
      \Drupal::logger('nyx_index_hub')->error('Erro ao remover conteúdo: @message', ['@message' => $e->getMessage()]);
      return $this->errorResponse('Erro ao remover conteúdo', 500);
    }
  }

  /**
   * Valida se store pertence ao grupo.
   *
   * @param string $group_key
   *   Group key UUID.
   * @param string $store_name
   *   Nome do store.
   *
   * @return true|\Symfony\Component\HttpFoundation\JsonResponse
   *   TRUE se válido, JsonResponse se inválido.
   */
  private function validateStoreAccess(string $group_key, string $store_name) {
    try {
      $groups = $this->entityTypeManager()
        ->getStorage('nyx_group')
        ->loadByProperties(['group_key' => $group_key]);

      if (empty($groups)) {
        return $this->errorResponse('Grupo não encontrado', 404);
      }

      $group = reset($groups);
      $storages = $this->entityTypeManager()
        ->getStorage('nyx_storage')
        ->loadByProperties([
          'group_id' => $group->id(),
          'store_name' => $store_name,
        ]);

      if (empty($storages)) {
        return $this->errorResponse('Store não pertence ao grupo', 403);
      }

      return TRUE;
    }
    catch (\Exception $e) {
      \Drupal::logger('nyx_index_hub')->error('Erro na validação: @message', ['@message' => $e->getMessage()]);
      return $this->errorResponse('Erro ao validar store', 500);
    }
  }

  /**
   * Upload de arquivo markdown.
   *
   * @param string $markdown
   *   Conteúdo markdown.
   * @param string $store_name
   *   Nome do store.
   *
   * @return array|null
   *   Resultado do upload ou NULL.
   */
  private function uploadMarkdownFile(string $markdown, string $store_name): ?array {
    $temp_file = tempnam(sys_get_temp_dir(), 'nyx_');
    if (!$temp_file || file_put_contents($temp_file, $markdown) === FALSE) {
      return NULL;
    }

    try {
      return $this->apiService->uploadFile($temp_file, 'text/markdown', $store_name);
    }
    finally {
      if (file_exists($temp_file)) {
        unlink($temp_file);
      }
    }
  }

  /**
   * Gera chave do State API.
   *
   * @param string $store_name
   *   Nome do store.
   * @param string $content_id
   *   ID do conteúdo.
   *
   * @return string
   *   Chave do state.
   */
  private function getStateKey(string $store_name, string $content_id): string {
    return 'nyx_sync.file_mapping.' . md5($store_name . ':' . $content_id);
  }

  /**
   * Cria resposta de erro.
   *
   * @param string $message
   *   Mensagem de erro.
   * @param int $status_code
   *   Código HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resposta JSON.
   */
  private function errorResponse(string $message, int $status_code): JsonResponse {
    return new JsonResponse([
      'success' => FALSE,
      'message' => $message,
    ], $status_code);
  }

}
