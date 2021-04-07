<?php

namespace Drupal\omnipedia_content_changes\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Session\AccountInterface;
use Drupal\omnipedia_content_changes\Service\WikiNodeChangesBuilderInterface;
use Drupal\omnipedia_content_changes\Service\WikiNodeChangesCacheInterface;
use Drupal\omnipedia_content_changes\Service\WikiNodeChangesInfoInterface;
use Drupal\omnipedia_content_changes\Service\WikiNodeChangesUserInterface;
use Drupal\omnipedia_core\Entity\NodeInterface;
use Drupal\omnipedia_core\Service\TimelineInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for the Omnipedia wiki node changes route.
 */
class OmnipediaWikiNodeChangesController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Our logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $loggerChannel;

  /**
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_core\Service\TimelineInterface
   */
  protected $timeline;

  /**
   * The Omnipedia wiki node changes builder service.
   *
   * @var \Drupal\omnipedia_content_changes\Service\WikiNodeChangesBuilderInterface
   */
  protected $wikiNodeChangesBuilder;

  /**
   * The Omnipedia wiki node changes cache service.
   *
   * @var \Drupal\omnipedia_content_changes\Service\WikiNodeChangesCacheInterface
   */
  protected $wikiNodeChangesCache;

  /**
   * The Omnipedia wiki node changes info service.
   *
   * @var \Drupal\omnipedia_content_changes\Service\WikiNodeChangesInfoInterface
   */
  protected $wikiNodeChangesInfo;

  /**
   * Constructs this controller; saves dependencies.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   *
   * @param \Psr\Log\LoggerInterface $loggerChannel
   *   Our logger channel.
   *
   * @param \Drupal\omnipedia_core\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   *
   * @param \Drupal\omnipedia_content_changes\Service\WikiNodeChangesBuilderInterface $wikiNodeChangesBuilder
   *   The Omnipedia wiki node changes builder service.
   *
   * @param \Drupal\omnipedia_content_changes\Service\WikiNodeChangesCacheInterface $wikiNodeChangesCache
   *   The Omnipedia wiki node changes cache service.
   *
   * @param \Drupal\omnipedia_content_changes\Service\WikiNodeChangesInfoInterface $wikiNodeChangesInfo
   *   The Omnipedia wiki node changes info service.
   *
   * @param \Drupal\omnipedia_content_changes\Service\WikiNodeChangesUserInterface $wikiNodeChangesUser
   *   The Omnipedia wiki node changes user service.
   */
  public function __construct(
    AccountInterface                $currentUser,
    LoggerInterface                 $loggerChannel,
    TimelineInterface               $timeline,
    WikiNodeChangesBuilderInterface $wikiNodeChangesBuilder,
    WikiNodeChangesCacheInterface   $wikiNodeChangesCache,
    WikiNodeChangesInfoInterface    $wikiNodeChangesInfo,
    WikiNodeChangesUserInterface    $wikiNodeChangesUser
  ) {

    $this->currentUser            = $currentUser;
    $this->loggerChannel          = $loggerChannel;
    $this->timeline               = $timeline;
    $this->wikiNodeChangesBuilder = $wikiNodeChangesBuilder;
    $this->wikiNodeChangesCache   = $wikiNodeChangesCache;
    $this->wikiNodeChangesInfo    = $wikiNodeChangesInfo;
    $this->wikiNodeChangesUser    = $wikiNodeChangesUser;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('logger.factory')->get('omnipedia_content_changes'),
      $container->get('omnipedia.timeline'),
      $container->get('omnipedia.wiki_node_changes_builder'),
      $container->get('omnipedia.wiki_node_changes_cache'),
      $container->get('omnipedia.wiki_node_changes_info'),
      $container->get('omnipedia.wiki_node_changes_user')
    );
  }

  /**
   * Checks access for the request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result. Access is granted if the provided node is a wiki node,
   *   the wiki node is not a main page, the wiki node has a previous revision,
   *   and $account has access to both the provided wiki node and its previous
   *   revision.
   */
  public function access(
    AccountInterface $account, NodeInterface $node
  ): AccessResultInterface {

    /** \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $previousNode = $node->getPreviousWikiNodeRevision();

    return AccessResult::allowedIf(
      !$node->isMainPage() &&
      $node->access('view', $account) &&
      \is_object($previousNode) &&
      $previousNode->access('view', $account)
    )
    ->addCacheableDependency($node)
    ->cachePerUser();

  }

  /**
   * Title callback for the route.
   *
   * @param \Drupal\omnipedia_core\Entity\NodeInterface $node
   *   A node object.
   *
   * @return array
   *   A render array containing the changes title for this request.
   */
  public function title(NodeInterface $node): array {

    /** \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $previousNode = $node->getPreviousWikiNodeRevision();

    return [
      '#markup'       => $this->t(
        '<span class="page-title__primary">@title<span class="page-title__glue">: </span></span><span class="page-title__secondary">Changes since @date</span>',
        [
          '@title'  => $node->getTitle(),
          '@date'   => $this->timeline->getDateFormatted(
            $previousNode->getWikiNodeDate(), 'short'
          ),
        ]
      ),
      '#allowed_tags' => Xss::getHtmlTagList(),
    ];

  }

  /**
   * Content callback for the route.
   *
   * @param \Drupal\omnipedia_core\Entity\NodeInterface $node
   *   A node object.
   *
   * @return array
   *   A render array containing the changes content for this request, or a
   *   placeholder render array if the changes have not yet been built.
   */
  public function view(NodeInterface $node): array {

    if (!$this->wikiNodeChangesCache->isCached($node)) {

      // Log this uncached view attempt in case it's useful data for debugging
      // or future optimizations.
      $this->loggerChannel->log(
        RfcLogLevel::DEBUG,
        'Wiki node changes not cached: user <code>%uid</code> requested node <code>%nid</code> with cache ID <code>%cid</code><br>Available cache IDs for this node:<pre>%cids</pre>Current user\'s roles:<pre>%roles</pre>',
        [
          '%uid'    => $this->currentUser->id(),
          '%nid'    => $node->nid->getString(),
          '%cid'    => $this->wikiNodeChangesInfo->getCacheId(
            $node->nid->getString()
          ),
          '%cids'   => \print_r($this->wikiNodeChangesInfo->getCacheIds(
            $node->nid->getString()
          ), true),
          '%roles'  => \print_r($this->currentUser->getRoles(), true),
        ]
      );

      return $this->wikiNodeChangesBuilder->buildPlaceholder($node);

    }

    return $this->wikiNodeChangesBuilder->build($node);

  }

}
