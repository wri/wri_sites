<?php

namespace Drupal\wri_zoom\Plugin\WebformHandler;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Plugin\WebformElement\BooleanBase;
use Drupal\webform\Plugin\WebformElement\NumericBase;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Drupal\webform\Plugin\WebformElementInterface;
use Drupal\webform\Plugin\WebformHandler\RemotePostWebformHandler;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformMessageManagerInterface;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submission remote Zoom post handler.
 *
 * @WebformHandler(
 *   id = "remote_zoom_post",
 *   label = @Translation("Remote Zoom post"),
 *   category = @Translation("External"),
 *   description = @Translation("Posts webform submissions to a Zoom URL."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class ZoomRemotePostWebformHandler extends RemotePostWebformHandler {

  /**
   * Determine if response has an error status code.
   *
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response returned by the remote server.
   *
   * @return bool
   *   TRUE if response status code reflects an unsuccessful value.
   */
  protected function responseHasError($response) {
    $status_code = $response->getStatusCode();
    $body = $response->getBody()->getContents();

    return ($status_code < 200 || $status_code >= 300) && $body !== 'Success';
  }

}
