<?php

namespace Drupal\wri_node\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RequestContext;
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
  public function __construct(ConfigFactoryInterface $config_factory, ModuleExtensionList $module_extension_list, RequestContext $request_context) {
    parent::__construct($config_factory);
    $this->moduleExtensionList = $module_extension_list;
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('extension.list.module'),
      $container->get('router.request_context')
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

    $form['unpublished_person_phrase'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unpublished Person phrase'),
      '#default_value' => $this->config('wri_node.settings')->get('unpublished_person_phrase'),
      '#required' => TRUE,
    ];

    $form['person_listing_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Person listing url'),
      '#default_value' => $this->config('wri_node.settings')->get('person_listing_url'),
      '#size' => 40,
      '#description' => $this->t('If a narrative taxonomy contains a person link that has been unpublished, link to this url instead.'),
      '#field_prefix' => $this->requestContext->getCompleteBaseUrl(),
    ];

    $form['disable_ads_data_redaction'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable google consent mode?'),
      '#description' => $this->t('If checked, the tag for "ads_data_redaction" will be disabled'),
      '#default_value' => $this->config('wri_node.settings')->get('disable_ads_data_redaction'),
    ];

    $form['disable_osano_script'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Osano script from loading in Head?'),
      '#description' => $this->t('If checked, the tag for "osano_script" will be disabled'),
      '#default_value' => $this->config('wri_node.settings')->get('disable_osano_script'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('wri_node.settings')
      ->set('use_fallback_image', $form_state->getValue('use_fallback_image'))
      ->set('unpublished_person_phrase', $form_state->getValue('unpublished_person_phrase'))
      ->set('person_listing_url', $form_state->getValue('person_listing_url'))
      ->set('disable_ads_data_redaction', $form_state->getValue('disable_ads_data_redaction'))
      ->set('disable_osano_script', $form_state->getValue('disable_osano_script'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
