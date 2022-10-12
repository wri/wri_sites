<?php

namespace Drupal\wri_block\Plugin\Block;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\layout_builder\Plugin\Block\FieldBlock;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field block that renders even when empty.
 *
 * @Block(
 *  id = "related_resources_fallback",
 *  admin_label = @Translation("Always Visible Field"),
 * )
 */
class RelatedResourcesFallback extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        // Implement default settings.
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
        // Implement settings form.
      ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->requestStack->getCurrentRequest()->attributes->get('node');
    if ($node) {
      $delta = $node->field_related_resources;
      if (isset($delta)) {
        return \Drupal::service('renderer')->render($delta->view(array('type' => 'related_field_formatter')));
      }
      else {
        $view = Views::getView('related_content');
        $view->setDisplay('block_1');
        $view->setArguments([$node->id()]);
        $build = $view->render();
        return $build;
      }
    }
    return [];
  }

}
