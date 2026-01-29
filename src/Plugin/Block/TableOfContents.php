<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\NodeInterface;
use Drupal\omnipedia_core\Entity\WikiNodeInfo;
use Drupal\omnipedia_core\Service\WikiNodeResolverInterface;
use Drupal\omnipedia_core\Service\WikiNodeRouteInterface;
use function is_object;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Provides a block to display a wiki node's table of contents.
 *
 * @see https://www.drupal.org/project/table_of_contents
 *   We initially wanted to use this because it's fairly robust and well
 *   thought out, but it turns out to not render the field before building the
 *   table of contents, i.e. it assumes the field is saved as HTML in the
 *   database; this means it will fail to find any headings generated via
 *   markdown or other input filters.
 */
#[Block(
  id: 'omnipedia_table_of_contents',
  admin_label:  new TranslatableMarkup('Table of Contents'),
  category:     new TranslatableMarkup('Omnipedia'),
)]
class TableOfContents extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal renderer service.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $currentRouteMatch
   *   The Drupal current route match service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeResolverInterface $wikiNodeResolver
   *   The Omnipedia wiki node resolver service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeRouteInterface $wikiNodeRoute
   *   The Omnipedia wiki node route service.
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly RendererInterface $renderer,
    protected readonly RouteMatchInterface $currentRouteMatch,
    TranslationInterface $stringTranslation,
    protected readonly WikiNodeResolverInterface $wikiNodeResolver,
    protected readonly WikiNodeRouteInterface $wikiNodeRoute,
  ) {

    parent::__construct($configuration, $pluginId, $pluginDefinition);

    $this->setStringTranslation($stringTranslation);

  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginId, $pluginDefinition,
  ) {

    return new static(
      $configuration, $pluginId, $pluginDefinition,
      $container->get(EntityTypeManagerInterface::class),
      $container->get(RendererInterface::class),
      $container->get(RouteMatchInterface::class),
      $container->get(TranslationInterface::class),
      $container->get(WikiNodeResolverInterface::class),
      $container->get(WikiNodeRouteInterface::class),
    );

  }

  /**
   * {@inheritdoc}
   */
  public function label() {

    // If a label has been set by the user, defer to that.
    if (!empty($this->configuration['label'])) {
      return $this->configuration['label'];
    }

    // Otherwise we use this.
    return $this->t('Table of Contents');

  }

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    return 'table_of_contents';
  }

  /**
   * Get the current wiki node, if any.
   *
   * @return \Drupal\node\NodeInterface|null
   *   A wiki node if one is being viewed, and null otherwise.
   *
   * @todo Use plug-in context instead of getting the node from the current
   *   route match.
   *
   * @see \Drupal\Core\Plugin\ContextAwarePluginInterface
   *
   * @see https://www.drupal.org/project/table_of_contents
   *   Example of using context-aware plug-in that contains an entity as
   *   context.
   */
  protected function getCurrentWikiNode(): ?NodeInterface {

    // If there's a 'node' route parameter, attempt to resolve it to a wiki
    // node. Note that the 'node' parameter is not upcast into a Node object if
    // viewing a (Drupal) revision other than the currently published one.
    return $this->wikiNodeResolver->resolveWikiNode(
      $this->currentRouteMatch->getParameter('node'),
    );

  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {

    if (!$this->wikiNodeRoute->isWikiNodeViewRouteName(
      $this->currentRouteMatch->getRouteName(),
    )) {

      return AccessResult::forbidden();

    }

    /** @var \Drupal\node\NodeInterface|null */
    $node = $this->getCurrentWikiNode();

    if (!is_object($node)) {

      return AccessResult::forbidden();

    }

    return $node->access('view', $account, true);

  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    /** @var \Drupal\node\NodeInterface|null */
    $node = $this->getCurrentWikiNode();

    if (!is_object($node)) {

      return [];

    }

    /** @var \Drupal\Core\Render\BubbleableMetadata */
    $nodeCacheMetadata = BubbleableMetadata::createFromObject($node);

    /** @var \Drupal\Core\Entity\EntityViewBuilderInterface */
    $viewBuilder = $this->entityTypeManager->getViewBuilder(
      $node->getEntityTypeId(),
    );

    $bodyRenderArray = $viewBuilder->viewField($node->get('body'), 'full');

    // Merge in the body field's cache metadata, if any. This is probably not
    // necessary as the node likely would have this already but it shouldn't
    // hurt to do this here.
    $nodeCacheMetadata->merge(BubbleableMetadata::createFromRenderArray(
      $bodyRenderArray,
    ));

    /** @var \Drupal\Core\Render\RenderContext */
    $renderContext = new RenderContext();

    /** @var string */
    $bodyRendered = (string) $this->renderer->executeInRenderContext(
      $renderContext, function() use (&$bodyRenderArray) {
        return $this->renderer->render($bodyRenderArray);
      }
    );

    $crawlerId = 'omnipedia-table-of-contents-block-root';

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $rootCrawler = new Crawler(
      // The <div> is to prevent the PHP DOM automatically wrapping any
      // top-level text content in a <p> element.
      '<div id="' . $crawlerId . '">' . $bodyRendered . '</div>',
    );

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $tocCrawler = $rootCrawler->filter('.table-of-contents');

    $renderArray = [];

    // Always apply the cache metadata to the render array even if we don't find
    // a table of contents so that cache tags and contexts allow it to be
    // invalidated/vary if/when it does get edited to add one.
    $nodeCacheMetadata->applyTo($renderArray);

    if (count($tocCrawler) === 0) {

      return $renderArray;

    }

    $renderArray['#markup'] = $tocCrawler->outerHtml();

    return $renderArray;

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {

    $contexts = [
      // Note that we don't need to vary by the date, as each date is a
      // different wiki node which is handled by this context.
      'omnipedia_wiki_node',
      // Vary by route, i.e. between 'entity.node.canonical' and
      // 'entity.node.omnipedia_changes'.
      'route',
      'user.permissions',
      'user.node_grants:view',
    ];

    /** @var \Drupal\node\NodeInterface|null */
    $node = $this->getCurrentWikiNode();

    // @todo Also include wiki revisions?
    if (is_object($node)) {

      $contexts = Cache::mergeContexts($node->getCacheContexts(), $contexts);

    }

    return Cache::mergeContexts(parent::getCacheContexts(), $contexts);

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {

    $maxAge = Cache::PERMANENT;

    /** @var \Drupal\node\NodeInterface|null */
    $node = $this->getCurrentWikiNode();

    // @todo Also include wiki revisions?
    if (is_object($node)) {

      $maxAge = Cache::mergeMaxAges($node->getCacheMaxAge(), $maxAge);

    }

    return $maxAge;

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {

    /** @var array */
    $tags = ['block_view:' . $this->getPluginId()];

    /** @var \Drupal\node\NodeInterface|null */
    $node = $this->getCurrentWikiNode();

    // @todo Also include wiki revisions?
    if (is_object($node)) {

      $tags = Cache::mergeTags($node->getCacheTags(), $tags);

    }

    return Cache::mergeTags(parent::getCacheTags(), $tags);

  }

}
