<?php

namespace Drupal\wri_block\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'related_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "related_field_formatter",
 *   label = @Translation("Deprecated Related Field Formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class RelatedFieldFormatter extends EntityReferenceEntityFormatter { }
