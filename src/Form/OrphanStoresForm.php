<?php

namespace Drupal\nyx_index_hub\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\nyx_index_hub\Service\GeminiApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for managing orphan stores.
 */
class OrphanStoresForm extends FormBase {

  protected $apiService;
  protected $database;

  public function __construct(GeminiApiService $api_service, Connection $database) {
    $this->apiService = $api_service;
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nyx_index_hub.api_service'),
      $container->get('database')
    );
  }

  public function getFormId() {
    return 'nyx_index_hub_orphan_stores_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $orphans = $this->getOrphans();

    $form['info'] = [
      '#markup' => '<p>' . $this->t('API: @api | Drupal: @drupal | Orphans: @orphans', [
        '@api' => $orphans['total_api'],
        '@drupal' => $orphans['total_drupal'],
        '@orphans' => count($orphans['list']),
      ]) . '</p>',
    ];

    if (empty($orphans['list'])) {
      $form['empty'] = ['#markup' => '<p>' . $this->t('No orphan stores found.') . '</p>'];
      return $form;
    }

    $options = [];
    foreach ($orphans['list'] as $store_name) {
      $options[$store_name] = ['store_name' => $store_name];
    }

    $form['stores'] = [
      '#type' => 'tableselect',
      '#header' => ['store_name' => $this->t('Store Name')],
      '#options' => $options,
      '#empty' => $this->t('No orphan stores found.'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Selected'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected = array_filter($form_state->getValue('stores'));

    if (empty($selected)) {
      $this->messenger()->addWarning($this->t('No stores selected.'));
      return;
    }

    $deleted = 0;
    foreach ($selected as $store_name) {
      try {
        $this->apiService->deleteStore($store_name);
        $deleted++;
      }
      catch (\Exception $e) {
        $this->messenger()->addError($this->t('Error: @msg', ['@msg' => $e->getMessage()]));
      }
    }

    if ($deleted > 0) {
      $this->messenger()->addStatus($this->t('Deleted @count store(s).', ['@count' => $deleted]));
    }
  }

  private function getOrphans() {
    $api_stores = $this->apiService->listStores();
    $api_names = !empty($api_stores['fileSearchStores'])
      ? array_column($api_stores['fileSearchStores'], 'name')
      : [];

    $drupal_names = $this->database->select('nyx_storage', 's')
      ->fields('s', ['store_name'])
      ->isNotNull('store_name')
      ->execute()
      ->fetchCol();

    return [
      'list' => array_diff($api_names, $drupal_names),
      'total_api' => count($api_names),
      'total_drupal' => count($drupal_names),
    ];
  }

}
