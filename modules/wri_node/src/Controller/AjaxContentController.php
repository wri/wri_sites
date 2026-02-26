<?php
namespace Drupal\wri_node\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;

class AjaxContentController extends ControllerBase {

  public function loadContent(Request $request, Node $node) {
    $view_mode = $request->query->get('view_mode') ?: 'main_content_b';

    // Render the node in the specified view mode
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $content = $view_builder->view($node, $view_mode);
    $rendered_content = \Drupal::service('renderer')->render($content);

    // Create an Ajax response and replace a specific container on the page
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('.article-columns', $rendered_content));

    return $response;
  }
}
