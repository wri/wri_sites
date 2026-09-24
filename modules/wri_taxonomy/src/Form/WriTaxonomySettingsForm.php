<?php

declare(strict_types=1);

namespace Drupal\wri_taxonomy\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pathauto\PathautoState;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure WRI taxonomy settings for this site.
 */
final class WriTaxonomySettingsForm extends ConfigFormBase {

  /**
   * Vocabularies whose terms' canonical urls are affected by this setting.
   */
  const AFFECTED_VOCABULARIES = ['tags', 'regions', 'topics_and_subtopics'];

  /**
   * Number of terms to update per batch operation.
   */
  const BATCH_SIZE = 50;

  /**
   * Constructs a WriTaxonomySettingsForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
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
      $container->get('module_handler'),
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
        fromConfig: [static::class, 'resourcesAnchorFromConfig'],
        toConfig: [static::class, 'resourcesAnchorToConfig'],
      ),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * ConfigTarget fromConfig callback for the resources_anchor field.
   *
   * A static method callable is used instead of a closure because
   * closures cannot be serialized, and the batch API run from submitForm()
   * persists $form_state (including this #config_target) to the session.
   */
  public static function resourcesAnchorFromConfig(?string $value): string {
    return $value ?? 'resources';
  }

  /**
   * ConfigTarget toConfig callback for the resources_anchor field.
   */
  public static function resourcesAnchorToConfig(string $value): string {
    return $value;
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

    if ($this->moduleHandler->moduleExists('pathauto')) {
      $enable_canonical_urls = (bool) $this->config('wri_taxonomy.settings')->get('enable_canonical_urls');
      // Pathauto only derives the taxonomy_term alias type when terms have a
      // canonical link template, so its cached alias types must be rebuilt.
      \Drupal::service('plugin.manager.alias_type')->clearCachedDefinitions();
      $this->batchUpdateTermsPathauto($enable_canonical_urls);
    }
  }

  /**
   * Queues a batch to set the pathauto state on the affected terms.
   */
  protected function batchUpdateTermsPathauto(bool $enabled): void {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $tids = $storage->getQuery()
      ->condition('vid', static::AFFECTED_VOCABULARIES, 'IN')
      ->accessCheck(FALSE)
      ->execute();
    if (!$tids) {
      return;
    }

    $operations = [];
    foreach (array_chunk($tids, static::BATCH_SIZE) as $chunk) {
      $operations[] = [[static::class, 'batchProcessTerms'], [$chunk, $enabled]];
    }
    batch_set([
      'title' => $this->t('Updating term URL alias settings'),
      'operations' => $operations,
      'finished' => [static::class, 'batchFinished'],
    ]);
  }

  /**
   * Batch operation callback: sets pathauto state on a chunk of terms.
   */
  public static function batchProcessTerms(array $tids, bool $enabled, array &$context): void {
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $generator = \Drupal::service('pathauto.generator');
    $pathauto_state = $enabled ? PathautoState::CREATE : PathautoState::SKIP;
    foreach ($storage->loadMultiple($tids) as $term) {
      $term->path->pathauto = $pathauto_state;
      $term->save();
      if ($enabled) {
        // Generate the alias for each translation.
        foreach ($term->getTranslationLanguages() as $langcode => $language) {
          $generator->updateEntityAlias($term->getTranslation($langcode), 'bulkupdate');
        }
      }
    }
    $context['results']['count'] = ($context['results']['count'] ?? 0) + count($tids);
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    if ($success) {
      \Drupal::messenger()->addStatus(\Drupal::translation()->translate('Updated URL alias settings for @count terms.', ['@count' => $results['count'] ?? 0]));
    }
    else {
      \Drupal::messenger()->addError(\Drupal::translation()->translate('An error occurred while updating term URL alias settings.'));
    }
  }

}
