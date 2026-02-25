<?php

namespace Drupal\nyx_index_hub\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for document upload.
 */
class DocumentoUploadForm extends FormBase {

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
    $instance = new static();
    $instance->apiService = $container->get('nyx_index_hub.api_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nyx_index_hub_document_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nyx_storage = NULL) {
    if (!$nyx_storage) {
      $this->messenger()->addError($this->t('File storage not found.'));
      return $form;
    }

    $form_state->set('storage', $nyx_storage);

    $form['description'] = [
      '#markup' => '<p>' . $this->t('Uploads data to a FileSearchStore, preprocesses and splits into chunks before storing in a FileSearchStore document.') . ' <a href="https://ai.google.dev/api/file-search/file-search-stores?hl=pt-br" target="_blank">' . $this->t('Documentation') . '</a></p>',
    ];

    $form['info'] = [
      '#markup' => '<p>' . $this->t('Document upload for file storage: <strong>@name</strong>', [
        '@name' => $nyx_storage->get('name')->value,
      ]) . '</p>',
    ];

    $form['documento'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Select document'),
      '#description' => $this->t('Accepted formats: .md, .txt, .pdf (max. 10MB)'),
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'md txt pdf',
        ],
        'FileSizeLimit' => [
          'fileLimit' => 10485760,
        ],
      ],
      '#upload_location' => 'public://nyx_documents/',
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send for Indexing'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->get('storage');
    $fid = $form_state->getValue('documento')[0] ?? NULL;

    if (!$fid || !($file = File::load($fid))) {
      $this->messenger()->addError($this->t('Invalid file.'));
      return;
    }

    $file->setPermanent();
    $file->save();

    $store_name = $storage->get('store_name')->value;
    if (empty($store_name)) {
      $this->messenger()->addError($this->t('File storage does not have a store configured.'));
      return;
    }

    $file_path = \Drupal::service('file_system')->realpath($file->getFileUri());
    $result = $this->apiService->uploadFile($file_path, $file->getMimeType(), $store_name);

    if ($result) {
      $this->messenger()->addStatus($this->t('Document @filename was sent successfully to indexing!', [
        '@filename' => $file->getFilename(),
      ]));
    }
    else {
      $this->messenger()->addError($this->t('Error sending document to API. Check logs.'));
    }

    $form_state->setRedirect('entity.nyx_storage.canonical', ['nyx_storage' => $storage->id()]);
  }

}
