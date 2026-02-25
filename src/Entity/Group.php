<?php

namespace Drupal\nyx_index_hub\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the Group entity.
 *
 * @ContentEntityType(
 *   id = "nyx_group",
 *   label = @Translation("Group"),
 *   label_collection = @Translation("Groups"),
 *   label_singular = @Translation("group"),
 *   label_plural = @Translation("groups"),
 *   label_count = @PluralTranslation(
 *     singular = "@count group",
 *     plural = "@count groups",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\nyx_index_hub\Controller\GroupListBuilder",
 *     "form" = {
 *       "add" = "Drupal\nyx_index_hub\Form\GroupForm",
 *       "edit" = "Drupal\nyx_index_hub\Form\GroupForm",
 *       "delete" = "Drupal\nyx_index_hub\Form\GroupDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 *   base_table = "nyx_group",
 *   admin_permission = "manage nyx groups",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/nyx/group/{nyx_group}",
 *     "add-form" = "/admin/nyx/group/add",
 *     "edit-form" = "/admin/nyx/group/{nyx_group}/edit",
 *     "delete-form" = "/admin/nyx/group/{nyx_group}/delete",
 *     "collection" = "/admin/nyx/groups",
 *   },
 * )
 */
class Group extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Group name.'))
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

    $fields['username'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Username'))
      ->setDescription(t('Contact username.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Phone'))
      ->setDescription(t('Phone number.'))
      ->setSetting('max_length', 50)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('Email address.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['site'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Website'))
      ->setDescription(t('Group website URL.'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'uri_link',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['webhook'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Webhook'))
      ->setDescription(t('Webhook URL for group notifications.'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'uri_link',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['group_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group Key'))
      ->setDescription(t('Group API key for authentication.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Group creation date.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('Last modification date.'));

    return $fields;
  }

}
