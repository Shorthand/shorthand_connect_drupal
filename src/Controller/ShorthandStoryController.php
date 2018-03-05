<?php

namespace Drupal\shorthand\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\shorthand\Entity\ShorthandStoryInterface;

/**
 * Class ShorthandStoryController.
 *
 *  Returns responses for Shorthand story routes.
 */
class ShorthandStoryController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Shorthand story  revision.
   *
   * @param int $shorthand_story_revision
   *   The Shorthand story  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($shorthand_story_revision) {
    $shorthand_story = $this->entityManager()->getStorage('shorthand_story')->loadRevision($shorthand_story_revision);
    $view_builder = $this->entityManager()->getViewBuilder('shorthand_story');

    return $view_builder->view($shorthand_story);
  }

  /**
   * Page title callback for a Shorthand story  revision.
   *
   * @param int $shorthand_story_revision
   *   The Shorthand story  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($shorthand_story_revision) {
    $shorthand_story = $this->entityManager()->getStorage('shorthand_story')->loadRevision($shorthand_story_revision);
    return $this->t('Revision of %title from %date', ['%title' => $shorthand_story->label(), '%date' => format_date($shorthand_story->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Shorthand story .
   *
   * @param \Drupal\shorthand\Entity\ShorthandStoryInterface $shorthand_story
   *   A Shorthand story  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ShorthandStoryInterface $shorthand_story) {
    $account = $this->currentUser();
    $langcode = $shorthand_story->language()->getId();
    $langname = $shorthand_story->language()->getName();
    $languages = $shorthand_story->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $shorthand_story_storage = $this->entityManager()->getStorage('shorthand_story');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $shorthand_story->label()]) : $this->t('Revisions for %title', ['%title' => $shorthand_story->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all shorthand story revisions") || $account->hasPermission('administer shorthand story entities')));
    $delete_permission = (($account->hasPermission("delete all shorthand story revisions") || $account->hasPermission('administer shorthand story entities')));

    $rows = [];

    $vids = $shorthand_story_storage->revisionIds($shorthand_story);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\shorthand\ShorthandStoryInterface $revision */
      $revision = $shorthand_story_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $shorthand_story->getRevisionId()) {
          $link = $this->l($date, new Url('entity.shorthand_story.revision', ['shorthand_story' => $shorthand_story->id(), 'shorthand_story_revision' => $vid]));
        }
        else {
          $link = $shorthand_story->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
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
              Url::fromRoute('entity.shorthand_story.translation_revert', ['shorthand_story' => $shorthand_story->id(), 'shorthand_story_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.shorthand_story.revision_revert', ['shorthand_story' => $shorthand_story->id(), 'shorthand_story_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.shorthand_story.revision_delete', ['shorthand_story' => $shorthand_story->id(), 'shorthand_story_revision' => $vid]),
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

    $build['shorthand_story_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
