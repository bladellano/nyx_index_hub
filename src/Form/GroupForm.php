<?php

namespace Drupal\nyx_index_hub\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Uuid\Uuid;

/**
 * Form for Group entity.
 */
class GroupForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Anexar biblioteca JavaScript para geração de UUID.
    $form['#attached']['library'][] = 'nyx_index_hub/group_key_generator';

    // Gerar UUID automaticamente se for um novo grupo.
    if ($this->entity->isNew() && !$this->entity->get('group_key')->value) {
      $uuid = \Drupal::service('uuid')->generate();
      $this->entity->set('group_key', $uuid);
    }

    // Adicionar botão para gerar nova chave ao lado do campo group_key.
    if (isset($form['group_key'])) {
      $form['group_key']['#prefix'] = '<div class="group-key-wrapper">';
      $form['group_key']['#suffix'] = '</div>';

      $form['group_key']['widget'][0]['value']['#attributes']['id'] = 'group-key-input';
      $form['group_key']['widget'][0]['value']['#attributes']['class'][] = 'group-key-input';

      $form['generate_key'] = [
        '#type' => 'button',
        '#value' => $this->t('Generate New Key'),
        '#attributes' => [
          'class' => ['button', 'button--small', 'generate-group-key-btn'],
          'type' => 'button',
        ],
        '#weight' => $form['group_key']['#weight'] ?? 5,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $result = parent::validateForm($form, $form_state);

    $group_key = $form_state->getValue('group_key')[0]['value'] ?? '';

    // Validar formato UUID.
    if (!empty($group_key) && !Uuid::isValid($group_key)) {
      $form_state->setErrorByName('group_key', $this->t('The Group Key must be a valid UUID format (e.g., b758f062-1259-4858-b465-759e948c1pa1).'));
    }

    // Verificar se a chave já existe em outro grupo.
    if (!empty($group_key)) {
      $existing_groups = \Drupal::entityTypeManager()
        ->getStorage('nyx_group')
        ->loadByProperties(['group_key' => $group_key]);

      // Se encontrou grupos e não é o próprio grupo sendo editado.
      if (!empty($existing_groups)) {
        $existing_group = reset($existing_groups);
        if ($existing_group->id() != $this->entity->id()) {
          $form_state->setErrorByName('group_key', $this->t('This Group Key is already in use by another group.'));
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Group %label was created.', $message_args)
      : $this->t('Group %label was updated.', $message_args);
    $this->messenger()->addStatus($message);

    $form_state->setRedirect('entity.nyx_group.collection');

    return $result;
  }

}
