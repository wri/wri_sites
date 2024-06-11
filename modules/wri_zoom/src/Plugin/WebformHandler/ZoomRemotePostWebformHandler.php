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
   * {@inheritdoc}
   */
  protected function responseHasError($response) {
    $response_has_error = parent::responseHasError($response);
    // The http response is always 200, so we need to check the body of the
    // response conains "Success".
    $body = $response->getBody()->getContents();
    // Ensure we can get the body again.
    $response->getBody()->rewind();
    $body_has_error = $body !== 'Success';

    return $response_has_error || $body_has_error;
  }


  /**
   * {@inheritdoc}
   */
  protected function getRequestData($state, WebformSubmissionInterface $webform_submission) {
    $data = parent::getRequestData($state, $webform_submission);

    // Clear out any tokens that have no value so we can get an error if the
    // webform id is not set.
    foreach ($data as $key => $value) {
      $data[$key] = $this->replaceTokens($value, $webform_submission, [], ['clear' => TRUE]);
    }

    return $data;
  }


  /**
   * {@inheritdoc}
   */
  protected function handleError($state, $message, $request_url, $request_method, $request_type, $request_options, $response) {
    // Give our logs more information about why the response failed.
    $message .= ' Response body: ' . $response->getBody()->getContents();
    // Ensure we can get the body again.
    $response->getBody()->rewind();
    return parent::handleError($state, $message, $request_url, $request_method, $request_type, $request_options, $response);
  }

}
