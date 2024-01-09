<?php

namespace Drupal\wri_maps\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a region map block.
 *
 * @Block(
 *   id = "wri_region_map_block",
 *   admin_label = @Translation("WRI Region Map"),
 *   category = @Translation("WRI block"),
 * )
 */
class RegionMapBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The redirect destination helper.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->redirectDestination = $container->get('redirect.destination');
    $instance->configFactory = $container->get('config.factory');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    return $instance;
  }

  /**
   * Get a link to the settings page for region map file upload.
   *
   * @return \Drupal\Core\Link
   *   The upload link.
   */
  private function getRegionMapUploadLink() {
    // $options = $this->redirectDestination->getAsArray();
    return Link::createFromRoute($this->t('Upload region map &raquo;'), 'wri_maps.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();
    $intro_text = $config['intro_text'] ?? [
      'value' => '',
      'format' => 'basic_html',
    ];
    $map_style = $config['map_style'] ?? '';

    $form['upload_link'] = $this->getRegionMapUploadLink()->toRenderable();

    $form['intro_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Intro text'),
      '#default_value' => $intro_text['value'],
      '#format' => $intro_text['format'],
      '#allowed_formats' => ['basic_html'],
    ];

    $form['map_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Map style'),
      '#options' => [
        '' => $this->t('- Select a style -'),
        'has-white-background-color' => $this->t('White background'),
        'has-light-gray-background-color' => $this->t('Light gray background'),
      ],
      '#default_value' => $map_style,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $maps_config = $this->configFactory->get('wri_maps.settings');
    if (!$maps_config->get('region_map_file')) {
      $form_state->setError($form, $this->t('No region map file found. @upload_link', [
        '@upload_link' => $this->getRegionMapUploadLink()->toString(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();
    $this->configuration['intro_text'] = $values['intro_text'];
    $this->configuration['map_style'] = $values['map_style'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $intro_text = $config['intro_text'] ?? [
      'value' => '',
      'format' => 'basic_html',
    ];
    $map_style = $config['map_style'] ?? '';

    $maps_config = $this->configFactory->get('wri_maps.settings');
    $region_map_file = $maps_config->get('region_map_file');

    $attributes = new Attribute();
    $attributes->addClass('wri-region-map');
    $attributes->addClass($map_style);

    $build['#attributes']['class'][] = $map_style;
    $build['#title_attributes']['class'][] = 'h3';
    $build['#title_attributes']['class'][] = 'top-border-thick';

    if ($intro_text['value']) {
      $build['intro'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['intro', 'margin-bottom-md']],
      ];
      $build['intro']['text'] = [
        '#type' => 'processed_text',
        '#text' => $intro_text['value'],
        '#format' => $intro_text['format'],
      ];
    }

    $build['wri_region_map'] = [
      '#theme' => 'wri_region_map',
      '#attributes' => $attributes,
    ];

    if ($region_map_file) {
      $svg_file = $this->entityTypeManager->getStorage('file')->load($region_map_file);
      if ($svg_file) {
        $build['wri_region_map']['#svg_url'] = $this->fileUrlGenerator->generateString($svg_file->getFileUri());
      }
      else {
        $build['wri_region_map']['#svg_url'] = '';
      }
    }

    return $build;
  }

}
