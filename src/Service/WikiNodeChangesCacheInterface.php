<?php

namespace Drupal\omnipedia_content\Service;

use Drupal\omnipedia_core\Entity\NodeInterface;

/**
 * The Omnipedia wiki node changes cache service interface.
 */
interface WikiNodeChangesCacheInterface {

  /**
   * Determine whether rendered changes for a provided wiki node are cached.
   *
   * @param \Drupal\omnipedia_core\Entity\NodeInterface $node
   *   The wiki node to check.
   *
   * @return boolean
   *   True if cached changes are found, false otherwise.
   */
  public function isCached(NodeInterface $node): bool;

  /**
   * Get cached rendered changes for a provided wiki node, if any.
   *
   * @param \Drupal\omnipedia_core\Entity\NodeInterface $node
   *   The wiki node to attempt to get cached changes for.
   *
   * @return array|null
   *   A render array if rendered changes are found for the wiki node, or null
   *   otherwise.
   */
  public function get(NodeInterface $node): ?array;

  /**
   * Set rendered changes to the changes cache.
   *
   * @param \Drupal\omnipedia_core\Entity\NodeInterface $node
   *   The wiki node to set cached changes for.
   *
   * @param array $renderArray
   *   The render array to cache. This should contain cacheability metadata.
   */
  public function set(NodeInterface $node, array $renderArray): void;

}
