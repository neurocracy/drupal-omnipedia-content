<?php

namespace Drupal\omnipedia_content_changes\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\omnipedia_content_changes\Service\WikiNodeChangesInterface;
use Drupal\omnipedia_core\Entity\NodeInterface;
use Drupal\omnipedia_core\Service\TimelineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for the Omnipedia wiki node changes route.
 */
class OmnipediaWikiNodeChangesController extends ControllerBase {

  /**
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_core\Service\TimelineInterface
   */
  protected $timeline;

  /**
   * The Omnipedia wiki node changes service.
   *
   * @var \Drupal\omnipedia_content_changes\Service\WikiNodeChangesInterface
   */
  protected $wikiNodeChanges;

  /**
   * Constructs this controller; saves dependencies.
   *
   * @param \Drupal\omnipedia_core\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   *
   * @param $wikiNodeChanges \Drupal\omnipedia_content_changes\Service\WikiNodeChangesInterface
   *   The Omnipedia wiki node changes service.
   */
  public function __construct(
    TimelineInterface         $timeline,
    WikiNodeChangesInterface  $wikiNodeChanges
  ) {
    $this->timeline         = $timeline;
    $this->wikiNodeChanges  = $wikiNodeChanges;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('omnipedia.timeline'),
      $container->get('omnipedia.wiki_node_changes')
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
   *   A render array containing the changes content for this request.
   */
  public function view(NodeInterface $node): array {
    return $this->wikiNodeChanges->build($node);
  }

}
