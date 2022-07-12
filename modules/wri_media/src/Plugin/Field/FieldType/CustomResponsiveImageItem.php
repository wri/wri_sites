<?php

namespace Drupal\wri_media\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'wri_media_custom_responsive_image' field type.
 *
 * @FieldType(
 *   id = "wri_media_custom_responsive_image",
 *   label = @Translation("Custom Responsive Image"),
 *   category = @Translation("General"),
 *   default_widget = "wri_media_custom_responsive_image_widget",
 *   default_formatter = "wri_media_custom_responsive_image_formatter"
 * )
 *
 * @DCG
 * If you are implementing a single value field type you may want to inherit
 * this class form some of the field type classes provided by Drupal core.
 * Check out /core/lib/Drupal/Core/Field/Plugin/Field/FieldType directory for a
 * list of available field type implementations.
 */
class CustomResponsiveImageItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['target_id_sm'] = DataDefinition::create('integer')
      ->setLabel(t('Small'))
      ->setDescription(t('Small Image'));
    $properties['target_id_md'] = DataDefinition::create('integer')
      ->setLabel(t('Medium'))
      ->setDescription(t('Medium Image'));
    $properties['target_id_lg'] = DataDefinition::create('integer')
      ->setLabel(t('Large'))
      ->setDescription(t('Large Image'));
    $properties['target_id_xl'] = DataDefinition::create('integer')
      ->setLabel(t('XL'))
      ->setDescription(t('XL Image'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'target_id_sm' => [
          'description' => 'The ID of the Small file entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'target_id_md' => [
          'description' => 'The ID of the Medium file entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'target_id_lg' => [
          'description' => 'The ID of the Large file entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'target_id_xl' => [
          'description' => 'The ID of the X-large file entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
      ],
    ];
  }
}
