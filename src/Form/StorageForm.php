<?php

namespace Drupal\nyx_index_hub\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for File Storage entity.
 */
class StorageForm extends ContentEntityForm {

  /**
   * API service.
   *
   * @var \Drupal\nyx_index_hub\Service\GeminiApiService
   */
  protected $apiService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->apiService = $container->get('nyx_index_hub.api_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Pre-fill group_id from query parameter if creating new storage.
    if ($this->entity->isNew()) {
      $group_id = \Drupal::request()->query->get('group_id');
      if ($group_id && !$this->entity->get('group_id')->value) {
        $this->entity->set('group_id', $group_id);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $is_new = $entity->isNew();

    // If it's a new storage, create the store in the API.
    if ($is_new) {
      $store_name = $this->createStoreForStorage($entity);
      if ($store_name) {
        $entity->set('store_name', $store_name);
      }
    }

    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('File storage %label was created.', $message_args)
      : $this->t('File storage %label was updated.', $message_args);
    $this->messenger()->addStatus($message);

    $form_state->setRedirect('entity.nyx_storage.collection');

    return $result;
  }

  /**
   * Creates a store in the API for the file storage.
   *
   * @param \Drupal\nyx_index_hub\Entity\Storage $storage
   *   The file storage entity.
   *
   * @return string|null
   *   The created store name or NULL.
   */
  protected function createStoreForStorage($storage) {
    $display_name = $this->sanitizeStoreName($storage->get('name')->value);
    $response = $this->apiService->createStore($display_name);

    if ($response && isset($response['name'])) {
      return $response['name'];
    }

    return NULL;
  }

  /**
   * Sanitizes the storage name for use as store name.
   *
   * @param string $name
   *   Original name.
   *
   * @return string
   *   Sanitized name.
   */
  protected function sanitizeStoreName($name) {
    // Remove special characters and spaces.
    $name = preg_replace('/[^a-zA-Z0-9]/', '-', $name);
    $name = strtolower($name);
    return $name;
  }

}
