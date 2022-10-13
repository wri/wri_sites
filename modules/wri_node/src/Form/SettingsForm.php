<?php

namespace Drupal\wri_node\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure WRI nodes settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleExtensionList $module_extension_list) {
    parent::__construct($config_factory);
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('extension.list.module')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wri_node_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['wri_node.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $url = '/' . $this->moduleExtensionList->getPath('wri_node') . '/images/default.jpg';
    $form['use_fallback_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a fallback image?'),
      '#description' => $this->t('If checked, a Card for a Node with no existing Main Image will display a default image (based on the title of the Primary topic). If one is not found the default image at <a href="@href" target="_blank">default.jpg</a> will be used.', ['@href' => $url]),
      '#default_value' => $this->config('wri_node.settings')->get('use_fallback_image'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('wri_node.settings')
      ->set('use_fallback_image', $form_state->getValue('use_fallback_image'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
