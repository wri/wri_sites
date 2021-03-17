<?php

namespace Drupal\wri_author\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Author revision.
 *
 * @ingroup wri_author
 */
class WRIAuthorRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The Author revision.
   *
   * @var \Drupal\wri_author\Entity\WRIAuthorInterface
   */
  protected $revision;

  /**
   * The Author storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $wRIAuthorStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->wRIAuthorStorage = $container->get('entity_type.manager')->getStorage('wri_author');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wri_author_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.wri_author.version_history', ['wri_author' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $wri_author_revision = NULL) {
    $this->revision = $this->WRIAuthorStorage->loadRevision($wri_author_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->WRIAuthorStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Author: deleted %title revision %revision.', [
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
    ]);
    $this->messenger()->addMessage($this->t('Revision from %revision-date of Author %title has been deleted.', [
      '%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime()),
      '%title' => $this->revision->label(),
    ]));
    $form_state->setRedirect(
      'entity.wri_author.canonical',
       ['wri_author' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {wri_author_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.wri_author.version_history',
         ['wri_author' => $this->revision->id()]
      );
    }
  }

}
