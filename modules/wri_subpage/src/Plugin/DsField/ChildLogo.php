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
 *   id = "childlogo",
 *   title = @Translation("Add Logo"),
 *   entity_type = "node",
 *   ui_limit = {"microsite|*"}
 * )
 */
class ChildLogo extends DsFieldBase {

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
    if (($node instanceof NodeInterface) && isset($node->field_logo->entity->field_media_image->entity->uri->value)) {
      $uri = $node->field_logo->entity->field_media_image->entity->uri->value;
    }
    elseif (isset($entity->field_logo->entity->field_media_image->entity->uri->value)) {
      $uri = $entity->field_logo->entity->field_media_image->entity->uri->value;
    }

    if ($uri) {
      $settings = [
        'uri' => $uri,
        'loading' => 'lazy',
        'image_style' => '250_wide',
      ];

      $build = [
        '#theme' => 'thumbnail',
        '#settings' => $settings,
      ];
    }

    General::$htmlClasses[] = 'transparent-header white';
    return $build;
  }

}
