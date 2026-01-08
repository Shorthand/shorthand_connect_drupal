<?php

namespace Drupal\shorthand\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeStorageInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for shorthand.
 */
class ShorthandCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Shorthand story node type.
   */
  const SHORTHAND_STORY_NODE_TYPE = 'shorthand_story';

  const SHORTHAND_STORY_BASE_PATH = 'shorthand/stories';

  /**
   * Entity type manager.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected NodeStorageInterface $nodeStorage;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * ShorthandCommands constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system) {
    parent::__construct();
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->fileSystem = $file_system;
  }

  /**
   * Drush command to clean up not used shorthand stories.
   *
   * @command shorthand:clean-up
   * @aliases shcu
   */
  public function run() {
    $confirm = $this->io()->confirm('Are you sure you want to delete all not used shorthands?', TRUE);
    if (!$confirm) {
      $this->output()->writeln('Command cancelled.');
      return;
    }

    // Shorthand nodes.
    $nodes = $this->nodeStorage->loadByProperties([
      'type' => self::SHORTHAND_STORY_NODE_TYPE,
    ]);

    $usedShorthands = [];
    if ($nodes) {
      foreach ($nodes as $node) {
        $parts = explode('/', $node->field_shorthand->value);
        if (count($parts)) {
          $usedShorthands[$parts[0]] = $parts[1];
        }
      }
    }

    $destination_uri = 'public://' . static::SHORTHAND_STORY_BASE_PATH;
    $storyFolders = $this->fileSystem->scanDirectory($destination_uri, '/.*/', [
      'recurse' => FALSE,
      'key' => 'filename',
    ]);
    $localStories = array_keys($storyFolders);
    foreach ($localStories as $story) {
      $storyVariants = $this->fileSystem->scanDirectory($destination_uri . '/' . $story, '/.*/', [
        'recurse' => FALSE,
        'key' => 'filename',
      ]);
      if (empty($usedShorthands[$story])) {
        // Remove the story folder.
        $folder = $destination_uri . '/' . $story;
        $this->fileSystem->deleteRecursive($folder);
        $this->writeln($this->t('Removed shorthand: @title', [
          '@title' => $story,
        ]));
      }
      else {
        foreach ($storyVariants as $storyVariant => $data) {
          if ($storyVariant != $usedShorthands[$story]) {
            $folder = $destination_uri . '/' . $story . '/' . $storyVariant;
            $this->fileSystem->deleteRecursive($folder);
            $this->writeln($this->t('Removed shorthand: @title @version', [
              '@title' => $story,
              '@version' => $storyVariant,
            ]));
          }
        }
      }
    }

    $this->io()->success('All unused shorthand stories removed.');
  }

}
