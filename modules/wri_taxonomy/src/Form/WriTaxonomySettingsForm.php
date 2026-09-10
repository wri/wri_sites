<?php

declare(strict_types=1);

namespace Drupal\wri_taxonomy\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure WRI taxonomy settings for this site.
 */
final class WriTaxonomySettingsForm extends ConfigFormBase {

  /**
   * Constructs a WriTaxonomySettingsForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wri_taxonomy_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['wri_taxonomy.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['enable_canonical_urls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable canonical urls'),
      '#description' => $this->t('Canonical urls for terms on this site are, by default, overridden to point to either the term\'s "Landing page" or to the resource library filtered to this term. Checking this box sets canonical urls back to the Drupal default behavior, pointing links for the terms to /taxonomy/term/XXX.'),
      '#config_target' => 'wri_taxonomy.settings:enable_canonical_urls',
    ];
    $form['resources_anchor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('On-page Resources anchor'),
      '#description' => $this->t('The ID of the on-page anchor for the resources section, without the leading "#". Letters, numbers, and dashes only.'),
      '#config_target' => new ConfigTarget(
        'wri_taxonomy.settings',
        'resources_anchor',
        fromConfig: static fn (?string $value): string => $value ?? 'resources',
        toConfig: static fn (string $value): string => $value,
      ),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    // The enable_canonical_urls setting is read by
    // wri_taxonomy_entity_type_alter(), so the cached taxonomy_term entity
    // type definition must be rebuilt to pick up the change.
    $this->entityTypeManager->clearCachedDefinitions();
  }

}
