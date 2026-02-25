<?php

namespace Drupal\nyx_index_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for chat/generate content API endpoints.
 */
class ChatController extends ControllerBase {

  /**
   * The Index Hub API service.
   *
   * @var \Drupal\nyx_index_hub\Service\GeminiApiService
   */
  protected $apiService;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->apiService = $container->get('nyx_index_hub.api_service');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * Generate content using Gemini API with file search.
   *
   * Expected JSON structure:
   * {
   *   "text": "Qual teor da documentacao?"
   * }
   *
   * Required Header:
   * X-Group-Key: abc123xyz
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with generated content.
   */
  public function generate(Request $request) {
    // Parse JSON body
    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    // Validate required fields
    if (empty($data['text'])) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Field "text" is required',
      ], 400);
    }

    // Get group_key from header
    $group_key = $request->headers->get('X-Group-Key');

    if (empty($group_key)) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Header "X-Group-Key" is required',
      ], 400);
    }

    // Get store names by group_key
    $store_names = $this->getStoreNamesByGroupKey($group_key);

    // Validate store names
    if (empty($store_names)) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'No stores found for this group key',
      ], 404);
    }

    $text = $data['text'];

    // Call Gemini API
    $api_response = $this->apiService->generateContent($text, $store_names);

    if ($api_response === NULL) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Failed to generate content',
      ], 500);
    }

    // Extract response text
    $response_text = '';
    if (isset($api_response['candidates'][0]['content']['parts'][0]['text'])) {
      $response_text = $api_response['candidates'][0]['content']['parts'][0]['text'];
    }

    // Simplified response
    return new JsonResponse([
      'status' => 'success',
      'text' => $response_text,
      'store_names' => $store_names,
    ], 200);
  }

  /**
   * Get store names by group key.
   *
   * @param string $group_key
   *   The group key (api_key).
   *
   * @return array
   *   Array of store names.
   */
  protected function getStoreNamesByGroupKey($group_key) {
    // Find group by group_key
    $database = \Drupal::database();
    $query = $database->select('nyx_group', 'g')
      ->fields('g', ['id'])
      ->condition('g.group_key', $group_key)
      ->range(0, 1);
    $group_id = $query->execute()->fetchField();

    if (!$group_id) {
      return [];
    }

    // Get storages for this group
    $storage = \Drupal::entityTypeManager()->getStorage('nyx_storage');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('group_id', $group_id)
      ->condition('status', 1);
    $ids = $query->execute();

    $store_names = [];
    if (!empty($ids)) {
      $storages = $storage->loadMultiple($ids);
      foreach ($storages as $storage_entity) {
        $store_name = $storage_entity->get('store_name')->value;
        if (!empty($store_name)) {
          $store_names[] = $store_name;
        }
      }
    }

    return $store_names;
  }

}
