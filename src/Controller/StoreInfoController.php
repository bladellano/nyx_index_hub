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
   * Date format for display.
   */
  const DATE_FORMAT = 'd/m/Y H:i';

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
      // Busca a lista de documentos do store
      $documents_data = $this->apiService->listStoreDocuments($store_name);
      $documents = [];

      if ($documents_data && isset($documents_data['documents'])) {
        foreach ($documents_data['documents'] as $doc) {
          $documents[] = $this->formatDocumentData($doc);
        }
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => array_merge(
          $this->formatStoreData($data),
          ['documents' => $documents]
        ),
      ]);
    }

    return new JsonResponse([
      'success' => FALSE,
      'message' => $this->t('Unable to fetch store information.'),
    ], 500);
  }

  /**
   * Delete a document from a store.
   */
  public function deleteDocument() {
    $request = \Drupal::request();
    $document_name = $request->request->get('document_name');
    $store_name = $request->request->get('store_name');

    if (empty($document_name)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Document name is required.'),
      ], 400);
    }

    $success = $this->apiService->deleteFile($document_name);

    if ($success) {
      return new JsonResponse([
        'success' => TRUE,
        'message' => $this->t('Document deleted successfully.'),
        'store_name' => $store_name,
      ]);
    }

    return new JsonResponse([
      'success' => FALSE,
      'message' => $this->t('Unable to delete document.'),
    ], 500);
  }

  /**
   * Format store data for response.
   *
   * @param array $data
   *   Raw store data from API.
   *
   * @return array
   *   Formatted store data.
   */
  private function formatStoreData(array $data): array {
    return [
      'displayName' => $data['displayName'] ?? '-',
      'activeDocuments' => $data['activeDocumentsCount'] ?? '0',
      'sizeBytes' => ByteSizeMarkup::create($data['sizeBytes'] ?? 0),
      'createTime' => $this->formatDate($data['createTime'] ?? NULL),
      'updateTime' => $this->formatDate($data['updateTime'] ?? NULL),
    ];
  }

  /**
   * Format document data for response.
   *
   * @param array $doc
   *   Raw document data from API.
   *
   * @return array
   *   Formatted document data.
   */
  private function formatDocumentData(array $doc): array {
    return [
      'name' => $doc['name'] ?? '-',
      'displayName' => $doc['displayName'] ?? '-',
      'mimeType' => $doc['mimeType'] ?? '-',
      'sizeBytes' => ByteSizeMarkup::create($doc['sizeBytes'] ?? 0),
      'createTime' => $this->formatDate($doc['createTime'] ?? NULL),
      'updateTime' => $this->formatDate($doc['updateTime'] ?? NULL),
    ];
  }

  /**
   * Format date string.
   *
   * @param string|null $date
   *   ISO date string or NULL.
   *
   * @return string
   *   Formatted date or '-'.
   */
  private function formatDate(?string $date): string {
    return !empty($date) ? date(self::DATE_FORMAT, strtotime($date)) : '-';
  }

}
