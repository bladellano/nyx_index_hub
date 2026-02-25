<?php

namespace Drupal\nyx_index_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\nyx_index_hub\Service\GeminiApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for Store Info API.
 */
class StoreInfoController extends ControllerBase {

  /**
   * API service.
   *
   * @var \Drupal\nyx_index_hub\Service\GeminiApiService
   */
  protected $apiService;

  /**
   * Constructor.
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
   * Get store information.
   */
  public function getStoreInfo() {
    $store_name = \Drupal::request()->query->get('store_name');
    if (empty($store_name)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Store name is required.'),
      ], 400);
    }

    $data = $this->apiService->getStore($store_name);

    if ($data) {
      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'displayName' => $data['displayName'] ?? '-',
          'activeDocuments' => $data['activeDocumentsCount'] ?? '0',
          'sizeBytes' => ByteSizeMarkup::create($data['sizeBytes'] ?? 0),
          'createTime' => !empty($data['createTime']) ? date('d/m/Y H:i', strtotime($data['createTime'])) : '-',
          'updateTime' => !empty($data['updateTime']) ? date('d/m/Y H:i', strtotime($data['updateTime'])) : '-',
        ],
      ]);
    }

    return new JsonResponse([
      'success' => FALSE,
      'message' => $this->t('Unable to fetch store information.'),
    ], 500);
  }

}
