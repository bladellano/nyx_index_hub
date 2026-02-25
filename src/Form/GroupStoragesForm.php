<?php

namespace Drupal\nyx_index_hub\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\nyx_index_hub\Service\GeminiApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for group storages with AI query.
 */
class GroupStoragesForm extends FormBase {

  protected $apiService;

  public function __construct(GeminiApiService $api_service) {
    $this->apiService = $api_service;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('nyx_index_hub.api_service'));
  }

  public function getFormId() {
    return 'nyx_group_storages_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $nyx_group = NULL) {
    if (!$nyx_group) {
      return $form;
    }

    $group = \Drupal::entityTypeManager()->getStorage('nyx_group')->load($nyx_group);
    if (!$group) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $storages = \Drupal::entityTypeManager()
      ->getStorage('nyx_storage')
      ->loadByProperties(['group_id' => $nyx_group]);

    $form['group_info'] = [
      '#markup' => '<h3>' . $this->t('Group: @name', ['@name' => $group->get('name')->value]) . '</h3>',
    ];

    if (empty($storages)) {
      $form['empty'] = ['#markup' => '<p>' . $this->t('No file storages found.') . '</p>'];
      $form['add_link'] = [
        '#type' => 'link',
        '#title' => $this->t('Add File Storage'),
        '#url' => \Drupal\Core\Url::fromRoute('entity.nyx_storage.add_form', [], ['query' => ['group_id' => $nyx_group]]),
        '#attributes' => ['class' => ['button', 'button--primary']],
      ];
      return $form;
    }

    // AI Query section.
    $form['ai_query'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('AI Query'),
      '#collapsible' => FALSE,
    ];

    $form['ai_query']['prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prompt'),
      '#description' => $this->t('Enter your question about the documents in selected stores.'),
      '#rows' => 3,
      '#required' => FALSE,
    ];

    $form['ai_query']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Query AI'),
      '#button_type' => 'primary',
    ];

    // Response area.
    if ($response = $form_state->get('ai_response')) {
      $form['ai_query']['response'] = [
        '#type' => 'container',
        '#attributes' => ['style' => 'margin-top: 20px; padding: 15px; background: #f5f5f5; border-left: 4px solid #0073aa;'],
        'content' => [
          '#markup' => '<strong>' . $this->t('AI Response:') . '</strong><br>' . nl2br(htmlspecialchars($response)),
        ],
      ];
    }

    // Request payload area.
    if ($payload = $form_state->get('request_payload')) {
      $form['ai_query']['request'] = [
        '#type' => 'details',
        '#title' => $this->t('Request Payload'),
        '#open' => FALSE,
        '#attributes' => ['style' => 'margin-top: 10px;'],
        'content' => [
          '#markup' => '<pre style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; overflow-x: auto;">' .
                       htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) .
                       '</pre>',
        ],
      ];
    }

    // Storages table with checkboxes.
    $form['storages'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Select'),
        $this->t('ID'),
        $this->t('Name'),
        $this->t('Store Name'),
        $this->t('Active'),
      ],
      '#empty' => $this->t('No file storages found.'),
    ];

    foreach ($storages as $storage) {
      $storage_id = $storage->id();
      $store_name = $storage->get('store_name')->value;

      $form['storages'][$storage_id]['select'] = [
        '#type' => 'checkbox',
        '#default_value' => FALSE,
      ];

      $form['storages'][$storage_id]['id'] = [
        '#plain_text' => $storage_id,
      ];

      $form['storages'][$storage_id]['name'] = [
        '#type' => 'link',
        '#title' => $storage->get('name')->value,
        '#url' => $storage->toUrl(),
      ];

      $form['storages'][$storage_id]['store_name'] = [
        '#plain_text' => $store_name ?: '-',
      ];

      $form['storages'][$storage_id]['status'] = [
        '#type' => 'checkbox',
        '#default_value' => $storage->get('status')->value,
        '#ajax' => [
          'callback' => '::toggleStatusCallback',
          'wrapper' => 'storage-status-' . $storage_id,
        ],
        '#storage_id' => $storage_id,
      ];
    }

    $form['actions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-actions']],
      'add' => [
        '#type' => 'link',
        '#title' => $this->t('Add File Storage'),
        '#url' => \Drupal\Core\Url::fromRoute('entity.nyx_storage.add_form', [], ['query' => ['group_id' => $nyx_group]]),
        '#attributes' => ['class' => ['button']],
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback to toggle storage status.
   */
  public function toggleStatusCallback(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $storage_id = $triggering_element['#storage_id'];
    $new_status = $triggering_element['#value'];

    $storage = \Drupal::entityTypeManager()->getStorage('nyx_storage')->load($storage_id);
    if ($storage) {
      $storage->set('status', $new_status);
      $storage->save();
    }

    return $form['storages'][$storage_id]['status'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $prompt = $form_state->getValue('prompt');

    if (empty($prompt)) {
      $this->messenger()->addWarning($this->t('Please enter a prompt.'));
      $form_state->setRebuild();
      return;
    }

    $storages_data = $form_state->getValue('storages');
    $selected_stores = [];

    foreach ($storages_data as $storage_id => $row) {
      if (!empty($row['select'])) {
        $storage = \Drupal::entityTypeManager()->getStorage('nyx_storage')->load($storage_id);
        if ($storage && $storage->get('store_name')->value) {
          $selected_stores[] = $storage->get('store_name')->value;
        }
      }
    }

    if (empty($selected_stores)) {
      $this->messenger()->addWarning($this->t('Please select at least one file storage.'));
      $form_state->setRebuild();
      return;
    }

    // Build request payload.
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
            'fileSearchStoreNames' => $selected_stores,
          ],
        ],
      ],
    ];

    $form_state->set('request_payload', $payload);

    $response = $this->apiService->generateContent($prompt, $selected_stores);

    if ($response && isset($response['candidates'][0]['content']['parts'][0]['text'])) {
      $text = $response['candidates'][0]['content']['parts'][0]['text'];
      $form_state->set('ai_response', $text);
      $this->messenger()->addStatus($this->t('Query completed successfully.'));
    }
    else {
      $this->messenger()->addError($this->t('Failed to get response from AI.'));
    }

    $form_state->setRebuild();
  }

}
