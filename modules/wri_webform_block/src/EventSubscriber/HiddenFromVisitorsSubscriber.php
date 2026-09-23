<?php

declare(strict_types=1);

namespace Drupal\wri_webform_block\EventSubscriber;

use Drupal\block_content\BlockContentInterface;
use Drupal\block_content\Plugin\Block\BlockContentBlock;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\layout_builder\Plugin\Block\InlineBlock;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Hides "Hidden from visitors" webform blocks outside the Layout Builder UI.
 */
final class HiddenFromVisitorsSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a HiddenFromVisitorsSubscriber object.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run after core's BlockComponentRenderArray (priority 100) builds it.
    return [LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY => ['onBuildRender', 50]];
  }

  /**
   * Empties the build when the block is hidden and this isn't a preview.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event): void {
    if ($event->inPreview()) {
      return;
    }
    $block = $this->getBlockContent($event->getPlugin());
    if (!$block || $block->bundle() !== 'webform_with_background_color' || !$block->hasField('field_hidden_from_visitors')) {
      return;
    }
    // Vary on the block so unchecking the box shows it again.
    $event->addCacheableDependency($block);
    if ($block->get('field_hidden_from_visitors')->value) {
      $event->setBuild([]);
    }
  }

  /**
   * Loads the block_content entity behind an inline or reusable block plugin.
   */
  protected function getBlockContent(object $plugin): ?BlockContentInterface {
    $storage = $this->entityTypeManager->getStorage('block_content');
    if ($plugin instanceof InlineBlock) {
      $revision_id = $plugin->getConfiguration()['block_revision_id'] ?? NULL;
      $block = $revision_id ? $storage->loadRevision($revision_id) : NULL;
    }
    elseif ($plugin instanceof BlockContentBlock) {
      $blocks = $storage->loadByProperties(['uuid' => $plugin->getDerivativeId()]);
      $block = $blocks ? reset($blocks) : NULL;
    }
    return ($block ?? NULL) instanceof BlockContentInterface ? $block : NULL;
  }

}
