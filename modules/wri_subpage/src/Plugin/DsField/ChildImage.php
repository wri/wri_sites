<?php

namespace Drupal\wri_subpage\Plugin\DsField;

use Drupal\wri_node\General;
use Drupal\node\NodeInterface;
use Drupal\ds\Plugin\DsField\DsFieldBase;
use Drupal\node\Entity\Node;

/**
 * Plugin that renders the terms from a chosen taxonomy vocabulary.
 *
 * Template path: /web/themes/custom/ts_wrin/templates/fields/field-
 * -childimg.html.twig
 * Template name: field--childimg.html.twig.
 *
 * @DsField(
 *   id = "childimg",
 *   title = @Translation("Add Image"),
 *   entity_type = "node",
 *   ui_limit = {"project_detail|*","microsite|*"}
 * )
 */
class ChildImage extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->entity();
    $build = [];
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node) {
      $node_route = \Drupal::routeMatch()->getRawParameter('section_storage');
      $node_id = str_replace('node.', '', $node_route);
      $node = Node::load($node_id);
    }
    $uri = FALSE;
    $alt = '';
    if (($node instanceof NodeInterface) && isset($node->field_main_image->entity->field_media_image->entity->uri->value)) {
      $uri = $node->field_main_image->entity->field_media_image->entity->uri->value;
      $alt = $node->field_main_image->entity->field_media_image->alt;
    }
    elseif (isset($entity->field_main_image->entity->field_media_image->entity->uri->value)) {
      $uri = $entity->field_main_image->entity->field_media_image->entity->uri->value;
      $alt = $entity->field_main_image->entity->field_media_image->alt;
    }

    if ($uri) {
      $build = [
        '#type' => 'responsive_image',
        '#responsive_image_style_id' => 'article_hero_large_cta',
        '#uri' => $uri,
        '#attributes' => [
          'alt' => $alt,
        ],
      ];
    }

    General::$htmlClasses[] = 'transparent-header white';
    return $build;
  }

}
