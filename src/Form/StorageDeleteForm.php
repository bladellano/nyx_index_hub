<?php

namespace Drupal\nyx_index_hub\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirmation form for deleting File Storage.
 */
class StorageDeleteForm extends ContentEntityDeleteForm {

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
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the file storage %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\nyx_index_hub\Entity\Storage $entity */
    $entity = $this->getEntity();

    // Delete the store in the API if exists.
    if ($store_name = $entity->get('store_name')->value) {
      $this->apiService->deleteStore($store_name);
    }

    parent::submitForm($form, $form_state);
  }

}
