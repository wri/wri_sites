<?php

namespace Drupal\wri_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field block that renders even when empty.
 *
 * @Block(
 *  id = "related_resources_fallback",
 *  admin_label = @Translation("Related Content with Fallback"),
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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')
    );
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
        $view_properties = [
          'type' => 'related_field_formatter',
          'settings' => ['view_mode' => 'teaser'],
          'label' => 'hidden',
        ];
        $build = $delta->view($view_properties);
      }
      // Respect the title setting.
      if ($this->getConfiguration()['label_display'] == 'visible') {
        $build['#title'] = $this->getConfiguration()['label'];
      }
      else {
        $build['#title'] = '';
      }
      return $build;
    }
    return [];
  }

}
