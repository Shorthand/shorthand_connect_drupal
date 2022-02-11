<?php

namespace Drupal\shorthand\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\shorthand\ShorthandApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Configure shorthand settings for this site.
 */
class RemoteCollectionController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Shorthand Api service.
   *
   * @var \Drupal\shorthand\ShorthandApiInterface
   */
  protected $shorthandApi;

  /**
   * The constructor method.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\shorthand\ShorthandApiInterface $shorthandApi
   *   The shorthand api connector.
   */
  public function __construct(
    AccountInterface $currentUser,
    ShorthandApiInterface $shorthandApi) {
    $this->currentUser = $currentUser;
    $this->shorthandApi = $shorthandApi;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    // Load the service required to construct this class.
      $container->get('current_user'),
      $container->get('shorthand_api')
    );
  }

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function list() {
    $stories = $this->shorthandApi->getStories();
    $rows = [];
    foreach ($stories as $story) {
      unset($story['metadata']);
      unset($story['image']);

      $story['actions'] = [
        'data' => [
          'label' => [
            'data' => [
              'link' => [
                '#title' => $this->t('Create new story'),
                '#type' => 'link',
                '#url' => Url::fromRoute('shorthand.create.story', [
                  'storyid' => $story['id'],
                ]),
              ],
            ],
          ],
        ],
      ];
      $rows[] = $story;
    }

    $header = [
      //'Image',
      'ID',
      //'Metadata',
      'Title',
      'external_url',
      'Story version',
      'Action',
    ];
    //dump($stories);

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['my-table'],
      ],
      '#header_columns' => 4,
    ];
  }

  public static function downloadStory($sids, &$context) {
    $message = 'Downloading story...';
    $apiservice = 'shorthand_api';

    $results = [];
    foreach ($sids as $sid) {
      $file = \Drupal::service($apiservice)
        ->getStory($sid, []);
      //->getStory($sid, ['without_assets' => TRUE]);

      /*$external_file = 'https://www.example.com/test.png';
      $destination = 'sites/default/files/a-directory/test.png';
      $response = \Drupal::httpClient()->get($external_file, ['sink' => $destination]);*/

      $file_system = \Drupal::service('file_system');
      $filepath = $file_system->realpath($file);
      $archiver = \Drupal::service('plugin.manager.archiver')
        ->getInstance(['filepath' => $filepath]);

      $destination_uri = 'public://shorthand/stories/' . $sid;
      $file_system->prepareDirectory($destination_uri, FileSystemInterface::CREATE_DIRECTORY);
      $destination_path = $file_system->realpath($destination_uri);
      $result = $archiver->extract($destination_path);;

      $public = PublicStream::basePath();
      $html = str_replace('./assets/', './' . $public . '/shorthand/stories/' . $sid . '/assets/', file_get_contents($destination_path . '/index.html'));
      $html_dom = Html::load($html);

      // Title tag.
      $title_dom = $html_dom->getElementsByTagName('title')->item(0)->firstChild;
      $title = $title_dom->ownerDocument->saveXML($title_dom);
      // Body tag.
      $body_dom = $html_dom->getElementsByTagName('article')->item(0);
      $body = $body_dom->ownerDocument->saveXML($body_dom);
      // Meta tags.
      /** @var DOMElement $element */
      $metas = [];
      foreach ($html_dom->getElementsByTagName('meta') as $meta) {
        $metas[] = $meta->ownerDocument->saveXML($meta);
      }
      // Scripts.
      /** @var DOMElement $element */
      $scripts = [];
      foreach ($html_dom->getElementsByTagName('script') as $script) {
        $scripts[] = $script->ownerDocument->saveXML($script);
      }

      // Create story node.
      $node = Node::create([
        'type' => 'shorthand',
        'status' => 1,
        'title' => $title,
        'body' => [
          'value' => $body,
          'format' => 'full_html',
        ],
      ]);
      $node->set('field_shstory_id', $sid);
      $node->set('field_shstory_metatags', implode("\n", $metas));

      $node->save();

      $results[] = $result;
    }

    $context['message'] = $message;
    $context['results'] = $results;
  }

  public static function downloadStoryComplete($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    $message = "";

    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results), 'One story downloaded.', '@count stories downloaded.'
      );
    }
    else {
      $message = static::t('Finished with an error.');
    }

    \Drupal::messenger()->addStatus($message);
  }

  /**
   * Download and safe story.
   *
   * @param string $storyid
   *   Shorthand story ID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function createNewStory($storyid = NULL) {
    if (empty($storyid)) {
      throw new AccessDeniedHttpException();
    }

    $batch = [
      'title' => t('Downloading story...'),
      'operations' => [
        [
          'Drupal\shorthand\Controller\RemoteCollectionController::downloadStory',
          [[$storyid]],
        ],
      ],
      'finished' => 'Drupal\shorthand\Controller\RemoteCollectionController::downloadStoryComplete',
    ];

    batch_set($batch);
    return batch_process('/admin/content');
  }

}
