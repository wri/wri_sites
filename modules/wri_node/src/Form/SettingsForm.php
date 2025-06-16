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
      '#config_target' => 'wri_node.settings:use_fallback_image',
      '#description' => $this->t('If checked, a Card for a Node with no existing Main Image will display a default image (based on the title of the Primary topic). If one is not found the default image at <a href="@href" target="_blank">default.jpg</a> will be used.', ['@href' => $url]),
    ];

    $form['unpublished_person_phrase'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unpublished Person phrase'),
      '#config_target' => 'wri_node.settings:unpublished_person_phrase',
      '#required' => TRUE,
    ];

    $form['and_phrase'] = [
      '#type' => 'textfield',
      '#title' => $this->t('And word for listings, ie: 1, 2 "and" 3'),
      '#config_target' => 'wri_node.settings:and_phrase',
      '#required' => TRUE,
    ];

    $form['within_phrase'] = [
      '#type' => 'textfield',
      '#title' => $this->t('And word for "within" in the phrase "is part of X-project within Y-topic"'),
      '#config_target' => 'wri_node.settings:within_phrase',
      '#required' => TRUE,
    ];

    $form['person_listing_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Person listing url'),
      '#config_target' => 'wri_node.settings:person_listing_url',
      '#size' => 40,
      '#description' => $this->t('If a narrative taxonomy contains a person link that has been unpublished, link to this url instead.'),
      '#field_prefix' => $this->requestContext->getCompleteBaseUrl(),
    ];

    $form['disable_ads_data_redaction'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable google consent mode?'),
      '#config_target' => 'wri_node.settings:disable_ads_data_redaction',
      '#description' => $this->t('If checked, the tag for "ads_data_redaction" will be disabled'),
    ];

    $form['disable_osano_script'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Osano script from loading in Head?'),
      '#config_target' => 'wri_node.settings:disable_osano_script',
      '#description' => $this->t('If checked, the tag for "osano_script" will be disabled'),
    ];

    $form['twitter_share_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Share suffix'),
      '#config_target' => 'wri_node.settings:twitter_share_suffix',
      '#size' => 40,
      '#description' => $this->t('On social share dropdown, the text to come after the title of a page in a tweet. Defaults to "via @WorldResources"'),
    ];

    $form['narrative_taxonomy_org_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Narrative taxonomy organization name'),
      '#config_target' => 'wri_node.settings:narrative_taxonomy_org_name',
      '#size' => 40,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

}
