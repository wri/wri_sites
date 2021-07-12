<?php
namespace Drupal\wri_services_reader\Plugin\rest\resource;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Routing\BcRoute;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * Represents WRI Reader Node Retrieve records as resources.
 *
 * @RestResource (
 *   id = "wri_reader_retrieve_node",
 *   label = @Translation("WRI Reader Node Retrieve"),
 *   uri_paths = {
 *     "canonical" = "/api/reader/node/{id}"
 *   }
 * )
 *
 * @DCG
 * This plugin exposes database records as REST resources. In order to enable it
 * import the resource configuration into active configuration storage. You may
 * find an example of such configuration in the following file:
 * core/modules/rest/config/optional/rest.resource.entity.node.yml.
 * Alternatively you can make use of REST UI module.
 * @see https://www.drupal.org/project/restui
 * For accessing Drupal entities through REST interface use
 * \Drupal\rest\Plugin\rest\resource\EntityResource plugin.
 */
class WriReaderRetrieveNodeResource extends ResourceBase {

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Drupal\rest\Plugin\rest\resource\EntityResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param int $id
   *   The ID of the record.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the record.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get($id) {
    // Validate id.
    $node = $this->entityTypeManager->getStorage('node')->load($id);
    // validate node is node interface.
    $node_array = [];
    $language = $node->langcode->value;
    $special_fields = ['metatag',
      'langcode',
      'path',
      'layout_builder__layout',
      'menu_link',
      'uid',
      'type',
      'revision_uid',
      'revision_log',
      'body',
    ];
    $fields = $node->getFieldDefinitions();
    foreach ($fields as $field_name => $field_object) {
      // Special fields.
      if (in_array($field_name, $special_fields)) {
        switch ($field_name) {
          case 'langcode':
            $node_array['language'] = $language;
            break;

          case 'path':
            // 1. Confusing: the method is called toString, yet passing TRUE
            // for the first param nets you a \Drupal\Core\GeneratedUrl object.
            $url = $node->toUrl()->setAbsolute()->toString(TRUE);
            // 2. The generated URL string, as before.
            $url_string = $url->getGeneratedUrl();
            $node_array['path'] = $url_string;
            break;

          case 'uid':
            $node_array['uid'] = $node->uid->target_id;
            break;

          case 'type':
            $node_array['publication_type']['name'] = $node->get('type')->getValue()[0]['target_id'];
            break;

          case 'revision_uid':
            $node_array['revision_uid'] = $node->revision_uid->target_id;
            break;

          case 'revision_log':
            $node_array['revision_log'] = $node->get('revision_log')->getValue();
            break;

          case 'body':
            $body_array = $node->get('body')->getValue();
            $node_array['body'][$language] = $body_array[0] ?? $body_array;
            break;
        }
      }
      elseif (strpos($field_name, 'field_') !== FALSE) {
        if ($field_name == 'field_files') {
          // Format and load URLs for files
          $files = [];
          $mids = $node->get('field_files')->getValue();
          foreach($mids as $mid) {
            $media = Media::load($mid['target_id']);
            $fid = $media->field_media_file->target_id;
            $file = File::load($fid);
            $files[]['url'] = $file->url();
          }
          $node_array[$field_name] = [
            $language => $files,
          ];

        } else if ($field_name == 'field_authors') {
          // Format and load Person references.
          $authors = $node->get($field_name)->referencedEntities();

          foreach($authors as $author) {
            if (method_exists($author->field_person_link, 'getValue')) {
              $authorData = $author->field_person_link->getValue()[0];

              $node_array['authors'][] = [
                  'non_wri_author' => [$authorData['title']],
                  'non_wri_author_link' => (str_contains($authorData['uri'], 'nolink')) ? [''] : [$authorData['uri']],
                ];
            } else {
              $node_array['authors'][] = [
                $author->get('field_person')->referencedEntities()[0]->toArray(),
              ];
            }

          }
        }
        else {
          if (empty($node->get($field_name)->getValue())) {
            $node_array[$field_name] = [];
          }
          else {
            $node_array[$field_name] = [
              $language => [
                $node->get($field_name)->getValue()[0]
              ],
            ];
          }
        }
      }
      else {
        $node_array[$field_name] = $node->{$field_name}->value;
      }

    }
    $response = new ResourceResponse($node_array);
    // More details in
    // https://www.lullabot.com/articles/early-rendering-a-lesson-in-debugging-drupal-8
    $response->addCacheableDependency($url);
    return $response;
  }

}
