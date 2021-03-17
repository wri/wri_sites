<?php

namespace Drupal\wri_node\Plugin\DsField\Node;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\ds\Plugin\DsField\Node\NodeLink;

/**
 * Plugin that renders the 'read more' link of a node.
 *
 * @DsField(
 *   id = "node_link_token",
 *   title = @Translation("Read more (with tokens)"),
 *   entity_type = "node",
 *   provider = "node"
 * )
 */
class NodeLinkTokens extends NodeLink {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $result = parent::build();
    $tokenized_output = \Drupal::token()->replace($result['#context']['output'], [$this->entity()->getEntityTypeId() => $this->entity()], ['clear' => TRUE]);
    $result['#context']['output'] = PlainTextOutput::renderFromHtml($tokenized_output);
    return $result;
  }

}
