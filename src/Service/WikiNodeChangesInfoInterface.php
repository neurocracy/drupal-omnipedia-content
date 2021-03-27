<?php

namespace Drupal\omnipedia_content\Service;

use Drupal\omnipedia_core\Entity\NodeInterface;

/**
 * The Omnipedia wiki node changes info service interface.
 */
interface WikiNodeChangesInfoInterface {

  /**
   * Get all cache IDs for a provided wiki node's changes.
   *
   * This will generate all the cache IDs for a provided wiki node, including
   * for each 'user.permissions' cache context, allowing for preemptive caching.
   *
   * Note that this only generates variations based the node ID (nid),
   * language, negotiated theme, and permissions hashes. A more advanced
   * version of this could replicate what the render cache does, but we don't
   * anticipate requiring that level of granular variation; this is a
   * compromise between having enough variations so that we don't expose any
   * admin stuff (e.g. contextual links) or create security issues, and
   * avoiding caching more variations than we expect to need for the sake of
   * performance.
   *
   * @param \Drupal\omnipedia_core\Entity\NodeInterface $node
   *   The wiki node to get changes cache IDs for.
   *
   * @return array
   *   An array of cache IDs.
   *
   * @see \Drupal\Core\Render\RenderCache::createCacheID()
   *   Drupal core render cache ID generation for reference.
   */
  public function getCacheIds(NodeInterface $node): array;

  /**
   * Get a cache ID for a provided wiki node's changes for the current user.
   *
   * Note that this only generates variations based the node ID (nid), language,
   * negotiated theme, and the current user's permissions hash. A more advanced
   * version of this could replicate what the render cache does, but we don't
   * anticipate requiring that level of granular variation; this is a compromise
   * between having enough variations so that we don't expose any admin stuff
   * (e.g. contextual links) or create security issues, and avoiding caching
   * more variations than we expect to need for the sake of performance.
   *
   * @param \Drupal\omnipedia_core\Entity\NodeInterface $node
   *   The wiki node to get a changes cache ID for.
   *
   * @return string
   *   The cache ID for the current user.
   *
   * @todo Do we need to support fetching for other users?
   *
   * @see \Drupal\Core\Render\RenderCache::createCacheID()
   *   Drupal core render cache ID generation for reference.
   */
  public function getCacheId(NodeInterface $node): string;

}
