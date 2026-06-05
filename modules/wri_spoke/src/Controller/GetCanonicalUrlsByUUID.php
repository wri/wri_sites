<?php

declare(strict_types=1);

namespace Drupal\wri_spoke\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for WRI Spoke routes.
 */
final class GetCanonicalUrlsByUUID extends ControllerBase {

  public function __construct(
    private readonly EntityRepositoryInterface $entityRepository,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $repository = $container->get('entity.repository');
    assert($repository instanceof EntityRepositoryInterface);
    return new self($repository);
  }

  /**
   * Builds the response.
   */
  public function __invoke(string $uuid): CacheableJsonResponse {
    $term = $this->entityRepository->loadEntityByUuid('taxonomy_term', $uuid);

    if (!$term) {
      throw new NotFoundHttpException();
    }

    $response = new CacheableJsonResponse([]);
    $response->addCacheableDependency($term);

    if ($term->bundle() !== 'hub_terms') {
      $response->setData($this->canonicalUrls($term));
      return $response;
    }

    // For hub terms, resolve canonical URLs via the "Also Known As" local term.
    $referenced = $term->get('field_also_known_as')->referencedEntities();
    if (empty($referenced)) {
      return $response;
    }

    $local_term = reset($referenced);
    $response->addCacheableDependency($local_term);
    $response->setData($this->canonicalUrls($local_term));

    return $response;
  }

  /**
   * Returns absolute canonical URLs keyed by langcode for the given term.
   */
  private function canonicalUrls(EntityInterface $term): array {
    $urls = [];
    foreach ($term->getTranslationLanguages() as $language) {
      $translation = $term->getTranslation($language->getId());
      $urls[$language->getId()] = $translation->toUrl('canonical', ['absolute' => TRUE])->toString();
    }
    return $urls;
  }

}
