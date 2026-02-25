<?php

namespace Drupal\nyx_index_hub\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Group.
 */
class GroupListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['username'] = $this->t('Username');
    $header['phone'] = $this->t('Phone');
    $header['email'] = $this->t('Email');
    $header['group_key'] = $this->t('Group Key');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\nyx_index_hub\Entity\Group $entity */
    $row['id'] = $entity->id();
    $row['name'] = $entity->toLink($entity->get('name')->value);
    $row['username'] = $entity->get('username')->value;
    $row['phone'] = $entity->get('phone')->value;
    $row['email'] = $entity->get('email')->value;
    $row['group_key'] = substr($entity->get('group_key')->value, 0, 20) . '...';
    return $row + parent::buildRow($entity);
  }

}
