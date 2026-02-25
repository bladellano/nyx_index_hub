<?php

namespace Drupal\nyx_index_hub\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the File Storage entity.
 *
 * @ContentEntityType(
 *   id = "nyx_storage",
 *   label = @Translation("File Storage"),
 *   label_collection = @Translation("File Storages"),
 *   label_singular = @Translation("file storage"),
 *   label_plural = @Translation("file storages"),
 *   label_count = @PluralTranslation(
 *     singular = "@count file storage",
 *     plural = "@count file storages",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\nyx_index_hub\Controller\StorageListBuilder",
 *     "form" = {
 *       "add" = "Drupal\nyx_index_hub\Form\StorageForm",
 *       "edit" = "Drupal\nyx_index_hub\Form\StorageForm",
 *       "delete" = "Drupal\nyx_index_hub\Form\StorageDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 *   base_table = "nyx_storage",
 *   admin_permission = "manage nyx storages",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/nyx/storage/{nyx_storage}",
 *     "add-form" = "/admin/nyx/storage/add",
 *     "edit-form" = "/admin/nyx/storage/{nyx_storage}/edit",
 *     "delete-form" = "/admin/nyx/storage/{nyx_storage}/delete",
 *     "collection" = "/admin/nyx/storages",
 *   },
 * )
 */
class Storage extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('File storage name.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['group_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group'))
      ->setDescription(t('Associated group.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'nyx_group')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('File storage description.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 1,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Indicates if the file storage is active.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 2,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 2,
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['store_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Store Name'))
      ->setDescription(t('API store name (fileSearchStores/xxx).'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('File storage creation date.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('Last modification date.'));

    return $fields;
  }

}
