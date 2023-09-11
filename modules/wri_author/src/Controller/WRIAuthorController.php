<?php

namespace Drupal\wri_author\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\wri_author\Entity\WRIAuthorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WRIAuthorController.
 *
 *  Returns responses for Author routes.
 */
class WRIAuthorController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Author revision.
   *
   * @param int $wri_author_revision
   *   The Author revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($wri_author_revision) {
    $wri_author = $this->entityTypeManager()->getStorage('wri_author')
      ->loadRevision($wri_author_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('wri_author');

    return $view_builder->view($wri_author);
  }

  /**
   * Page title callback for a Author revision.
   *
   * @param int $wri_author_revision
   *   The Author revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($wri_author_revision) {
    $wri_author = $this->entityTypeManager()->getStorage('wri_author')
      ->loadRevision($wri_author_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $wri_author->label(),
      '%date' => $this->dateFormatter->format($wri_author->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Author.
   *
   * @param \Drupal\wri_author\Entity\WRIAuthorInterface $wri_author
   *   A Author object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(WRIAuthorInterface $wri_author) {
    $account = $this->currentUser();
    $wri_author_storage = $this->entityTypeManager()->getStorage('wri_author');

    $langcode = $wri_author->language()->getId();
    $langname = $wri_author->language()->getName();
    $languages = $wri_author->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $wri_author->label(),
    ]) : $this->t('Revisions for %title', ['%title' => $wri_author->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all author revisions") || $account->hasPermission('administer author entities')));
    $delete_permission = (($account->hasPermission("delete all author revisions") || $account->hasPermission('administer author entities')));

    $rows = [];

    $vids = $wri_author_storage->revisionIds($wri_author);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\wri_author\WRIAuthorInterface $revision */
      $revision = $wri_author_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $wri_author->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.wri_author.revision', [
            'wri_author' => $wri_author->id(),
            'wri_author_revision' => $vid,
          ]))->toString();
        }
        else {
          $link = $wri_author->toLink($date)->toString();
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.wri_author.translation_revert', [
                'wri_author' => $wri_author->id(),
                'wri_author_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.wri_author.revision_revert', [
                'wri_author' => $wri_author->id(),
                'wri_author_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.wri_author.revision_delete', [
                'wri_author' => $wri_author->id(),
                'wri_author_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['wri_author_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
