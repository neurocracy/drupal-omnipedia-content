<?php

namespace Drupal\omnipedia_content\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\omnipedia_content\Service\WikiNodeChangesInfoInterface;
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
   * The Omnipedia wiki node changes cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $changesCache;

  /**
   * The Omnipedia wiki node changes info service.
   *
   * @var \Drupal\omnipedia_content\Service\WikiNodeChangesInfoInterface
   */
  protected $wikiNodeChangesInfo;

  /**
   * Constructs this service object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $changesCache
   *   The Omnipedia wiki node changes cache bin.
   *
   * @param \Drupal\omnipedia_content\Service\WikiNodeChangesInfoInterface $wikiNodeChangesInfo
   *   The Omnipedia wiki node changes info service.
   */
  public function __construct(
    CacheBackendInterface         $changesCache,
    WikiNodeChangesInfoInterface  $wikiNodeChangesInfo
  ) {

    // Save dependencies.
    $this->changesCache         = $changesCache;
    $this->wikiNodeChangesInfo  = $wikiNodeChangesInfo;

  }

  /**
   * {@inheritdoc}
   */
  public function isCached(NodeInterface $node): bool {
    return \is_object($this->changesCache->get(
      $this->wikiNodeChangesInfo->getCacheId($node->nid->getString())
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function get(NodeInterface $node): ?array {

    if ($this->isCached($node)) {
      return $this->changesCache->get(
        $this->wikiNodeChangesInfo->getCacheId($node->nid->getString())
      )->data;
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
      $this->wikiNodeChangesInfo->getCacheId($node->nid->getString()),
      $renderArray,
      $bubbleableMetadata->getCacheMaxAge(),
      $bubbleableMetadata->getCacheTags()
    );

  }

}
