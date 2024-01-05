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
 *   label = @Translation("Related field formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class RelatedFieldFormatter extends EntityReferenceEntityFormatter {

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
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if (isset($items) && $items->count()) {
      return parent::viewElements($items, $langcode);
    }
    else {
      $node = $this->requestStack->getCurrentRequest()->attributes->get('node');
      if ($node) {
        $view = Views::getView('related_content');
        $view->setDisplay('block_1');
        $view->setArguments([$node->id()]);
        $build = $view->render();
        $build['#field_name'] = $items->getName();
        return $build;
      }
    }
    return [];
  }

}
