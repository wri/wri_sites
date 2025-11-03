<?php

namespace Drupal\wri_block\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\entity_browser\WidgetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Uses upload to create media images.
 *
 * @EntityBrowserWidget(
 *   id = "block_create",
 *   label = @Translation("Creates blocks"),
 *   description = @Translation("Create content blocks."),
 *   auto_select = FALSE
 * )
 */
class BlockCreate extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Current request stack.
   */
  protected RequestStack $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array_merge(parent::defaultConfiguration(), [
      'block_type' => NULL,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $aditional_widget_parameters) {
    // Get current request URI for destination parameter.
    $request = $this->requestStack->getCurrentRequest();
    $current_path = $request ? $request->getRequestUri() : '/';

    $block_types = $this->configuration['block_type'];
    if (empty($block_types)) {
      return ['#markup' => $this->t('No block types selected. Please configure the widget.')];
    }

    $links = [];
    foreach ($block_types as $block_type) {
      if (!empty($block_type)) {
        $url = Url::fromRoute(
          'block_content.add_form',
          ['block_content_type' => $block_type],
          [
            'query' => ['destination' => $current_path],
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => '{"width": "90%"}',
            ],
          ]
        );
        $links[] = Link::fromTextAndUrl($this->t('Create a @type block', ['@type' => $block_type]), $url);
      }
    }

    return [
      '#theme' => 'item_list',
      '#items' => $links,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $block_type_options = [];
    $block_types = $this->entityTypeManager
      ->getStorage('block_content_type')
      ->loadMultiple();

    foreach ($block_types as $block_type) {
      $block_type_options[$block_type->id()] = $block_type->label();
    }

    if (empty($block_type_options)) {
      $url = Url::fromRoute('entity.block_type.add_form')->toString();
      $form['block_type'] = [
        '#markup' => $this->t("You don't have any custom block types. You should <a href=':link'>create one</a>.", [':link' => $url]),
      ];
    }
    else {
      $form['block_type'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Block types'),
        '#default_value' => $this->configuration['block_type'],
        '#options' => $block_type_options,
      ];
    }

    return $form;
  }

}
