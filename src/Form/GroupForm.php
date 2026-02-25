<?php

namespace Drupal\nyx_index_hub\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for Group entity.
 */
class GroupForm extends ContentEntityForm {

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
