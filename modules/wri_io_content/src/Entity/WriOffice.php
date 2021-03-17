<?php

namespace Drupal\wri_io_content\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the WRI office entity.
 *
 * @ingroup wri_io_content
 *
 * @ContentEntityType(
 *   id = "wri_office",
 *   label = @Translation("WRI office"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\wri_io_content\WriOfficeListBuilder",
 *     "views_data" = "Drupal\wri_io_content\Entity\WriOfficeViewsData",
 *     "translation" = "Drupal\wri_io_content\WriOfficeTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\wri_io_content\Form\WriOfficeForm",
 *       "add" = "Drupal\wri_io_content\Form\WriOfficeForm",
 *       "edit" = "Drupal\wri_io_content\Form\WriOfficeForm",
 *       "delete" = "Drupal\wri_io_content\Form\WriOfficeDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\wri_io_content\WriOfficeHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\wri_io_content\WriOfficeAccessControlHandler",
 *   },
 *   base_table = "wri_office",
 *   data_table = "wri_office_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer wri office entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/wri_office/{wri_office}",
 *     "add-form" = "/admin/content/wri_office/add",
 *     "edit-form" = "/admin/content/wri_office/{wri_office}/edit",
 *     "delete-form" = "/admin/content/wri_office/{wri_office}/delete",
 *     "collection" = "/admin/content/wri_office",
 *   },
 *   field_ui_base_route = "wri_office.settings"
 * )
 */
class WriOffice extends ContentEntityBase implements WriOfficeInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the WRI office entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the WRI office is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
