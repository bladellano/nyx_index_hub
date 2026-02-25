<?php

namespace Drupal\nyx_index_hub\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for File Storage.
 */
class StorageListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['group'] = $this->t('Group');
    $header['status'] = $this->t('Status');
    $header['store_name'] = $this->t('Store');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\nyx_index_hub\Entity\Storage $entity */
    $row['id'] = $entity->id();
    $row['name'] = $entity->toLink($entity->get('name')->value);

    $group = $entity->get('group_id')->entity;
    $row['group'] = $group ? $group->toLink() : '-';

    $row['status'] = $entity->get('status')->value ? $this->t('Active') : $this->t('Inactive');
    $row['store_name'] = $entity->get('store_name')->value ?: '-';

    return $row + parent::buildRow($entity);
  }

}
