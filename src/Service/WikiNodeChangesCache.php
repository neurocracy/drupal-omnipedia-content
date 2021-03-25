<?php

namespace Drupal\omnipedia_content\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\omnipedia_content\Service\WikiNodeChangesCacheInterface;
use Drupal\omnipedia_core\Entity\NodeInterface;

/**
 * The Omnipedia wiki node changes cache service.
 *
 * Note that this is not the same as the 'cache.omnipedia_wiki_node_changes'
 * cache bin, but an abstraction on top of that cache bin to set and get wiki
 * node changes to/from that cache bin.
 */
class WikiNodeChangesCache implements WikiNodeChangesCacheInterface {

  /**
   * The Drupal cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager
   */
  protected $cacheContextsManager;

  /**
   * The Omnipedia wiki node changes cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $changesCache;

  /**
   * Constructs this service object.
   *
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cacheContextsManager
   *   The Drupal cache contexts manager.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $changesCache
   *   The Omnipedia wiki node changes cache bin.
   */
  public function __construct(
    CacheContextsManager  $cacheContextsManager,
    CacheBackendInterface $changesCache
  ) {

    // Save dependencies.
    $this->cacheContextsManager = $cacheContextsManager;
    $this->changesCache         = $changesCache;

  }

  /**
   * Create the cache ID for a provided wiki node.
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
   *   The wiki node to create the cache ID for.
   *
   * @return string
   *   The cache ID.
   *
   * @see \Drupal\Core\Render\RenderCache::createCacheID()
   *   Drupal core render cache ID generation for reference.
   */
  protected function createCacheID(NodeInterface $node): string {

    // Build the cache keys to vary by based on these hard-coded cache contexts.
    // The cache contexts manager will automatically populate these with the
    // relevant values, e.g. the language code or current user's permissions
    // hash.
    /** @var array */
    $cacheKeys = $this->cacheContextsManager->convertTokensToKeys([
      'languages:language_interface',
      'theme',
      'user.permissions',
    ])->getKeys();

    return $node->nid->getString() . ':' . \implode(':', $cacheKeys);

  }

  /**
   * {@inheritdoc}
   */
  public function isCached(NodeInterface $node): bool {
    return \is_object($this->changesCache->get($this->createCacheID($node)));
  }

  /**
   * {@inheritdoc}
   */
  public function get(NodeInterface $node): ?array {

    if ($this->isCached($node)) {
      return $this->changesCache->get($this->createCacheID($node))->data;
    }

    return null;

  }

  /**
   * {@inheritdoc}
   */
  public function set(NodeInterface $node, array $renderArray): void {

    /** @var \Drupal\Core\Render\BubbleableMetadata */
    $bubbleableMetadata = BubbleableMetadata::createFromRenderArray(
      $renderArray
    );

    $this->changesCache->set(
      $this->createCacheID($node), $renderArray,
      $bubbleableMetadata->getCacheMaxAge(),
      $bubbleableMetadata->getCacheTags()
    );

  }

}
