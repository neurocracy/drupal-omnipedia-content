<?php

namespace Drupal\omnipedia_content\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\omnipedia_core\Entity\NodeInterface;
use HtmlDiffAdvancedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Returns responses for the Omnipedia wiki node changes route.
 */
class OmnipediaWikiNodeChangesController extends ControllerBase {

  /**
   * The Drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The HTML diff service provided by the Diff module.
   *
   * @var \HtmlDiffAdvancedInterface
   */
  protected $htmlDiff;

  /**
   * The Drupal renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The previous revision of this wiki node, if any, or null otherwise.
   *
   * @var \Drupal\omnipedia_core\Entity\NodeInterface|null
   */
  protected $previousNode = null;

  /**
   * Whether we've checked for a previous wiki node revision this request.
   *
   * @var boolean
   */
  protected $hasCheckedPreviousNode = false;

  /**
   * Constructs this controller; saves dependencies.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   *
   * @param \HtmlDiffAdvancedInterface $htmlDiff
   *   The HTML diff service provided by the Diff module.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal renderer service.
   */
  public function __construct(
    EntityTypeManagerInterface  $entityTypeManager,
    HtmlDiffAdvancedInterface   $htmlDiff,
    RendererInterface           $renderer
  ) {
    $this->entityTypeManager  = $entityTypeManager;
    $this->htmlDiff           = $htmlDiff;
    $this->renderer           = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('diff.html_diff'),
      $container->get('renderer')
    );
  }

  /**
   * Get the previous revision of the provided node, if it's a wiki node.
   *
   * @param \Drupal\omnipedia_core\Entity\NodeInterface $node
   *   A node object.
   *
   * @return \Drupal\omnipedia_core\Entity\NodeInterface|null
   *   The previous revision of a wiki node, or null if there is no previous
   *   revision or the provided node is not a wiki node.
   */
  protected function getPreviousNode(NodeInterface $node): ?NodeInterface {

    if ($this->hasCheckedPreviousNode === false) {
      $this->previousNode = $node->getPreviousWikiNodeRevision();

      $this->hasCheckedPreviousNode = true;
    }

    return $this->previousNode;

  }

  /**
   * Checks access for the request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result. Access is granted if the provided node is a wiki node,
   *   the wiki node has a previous revision, and $account has access to both
   *   the provided wiki node and its previous revision.
   */
  public function access(
    AccountInterface $account, NodeInterface $node
  ): AccessResultInterface {

    /** \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $previousNode = $this->getPreviousNode($node);

    return AccessResult::allowedIf(
      $node->access('view', $account) &&
      \is_object($previousNode) &&
      $previousNode->access('view', $account)
    );

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
  public function title(NodeInterface $node) {

    /** \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $previousNode = $this->getPreviousNode($node);

    return [
      '#markup'       => $this->t('@title: changes since @date', [
        '@title'  => $node->getTitle(),
        '@date'   => $previousNode->getWikiNodeDate(),
      ]),
      '#allowed_tags' => Xss::getHtmlTagList(),
    ];

  }

  /**
   * Content callback for the route.
   *
   * This renders the current revision node and the previous revision node,
   * generates the diff via the HTML Diff service that the Diff module provides,
   * and alters/adjust the output as follows before returning it:
   *
   * - The node title is removed from the output, as we already have the page
   *   title.
   *
   * - Any links that are marked as changed due to having different href
   *   attributes have the old revision removed and the current revision not
   *   marked by removing the <ins> element and placing the link back in its
   *   place. This is done because there's no benefit from highlighting the
   *   change, as this is expected and would just add noise.
   *
   * @param \Drupal\omnipedia_core\Entity\NodeInterface $node
   *   A node object.
   *
   * @return array
   *   A render array containing the changes content for this request.
   *
   * @todo Check if links whose href attributes changed are both internal wiki
   *   node paths before removing the changed status?
   */
  public function view(NodeInterface $node) {
    /** \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $previousNode = $this->getPreviousNode($node);

    /** @var \Drupal\Core\Entity\EntityViewBuilderInterface */
    $viewBuilder = $this->entityTypeManager->getViewBuilder(
      $node->getEntityTypeId()
    );

    /** @var array */
    $previousRenderArray = $viewBuilder->view($previousNode, 'full');

    /** @var array */
    $currentRenderArray = $viewBuilder->view($node, 'full');

    // Disable the use of HTML Purifier to avoid having to wade through that
    // configuration nightmare to whitelist attributes (e.g. style) and elements
    // (such as SVG icons). Drupal's render and filtering systems should take
    // care of any security stuff for us as we render before passing the markup
    // to the HTML diff service. This config option requires caxy/php-htmldiff
    // >= 0.1.11 which has been specified in this module's composer.json.
    //
    // @see https://github.com/caxy/php-htmldiff/releases/tag/v0.1.11
    $this->htmlDiff->getConfig()->setPurifierEnabled(false);

    $this->htmlDiff->setOldHtml($this->renderer->render($previousRenderArray));
    $this->htmlDiff->setNewHtml($this->renderer->render($currentRenderArray));

    // Disable PHP libxml errors because we sometimes end up with invalid
    // nesting, e.g. <figure> inside of <dl> elements.
    //
    // @see https://stackoverflow.com/questions/6090667/php-domdocument-errors-warnings-on-html5-tags#6090728
    \libxml_use_internal_errors(true);

    $this->htmlDiff->build();

    \libxml_use_internal_errors(false);

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $differenceCrawler = (new Crawler(
      '<div id="omnipedia-changes-root">' .
        $this->htmlDiff->getDifference() .
      '</div>'
    ))->filter('#omnipedia-changes-root');

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $nodeTitleCrawler = $differenceCrawler->filter('.node__title');

    // Removes the node title element that Drupal generates.
    //
    // @todo Can this be handled in a node entity view mode instead?
    foreach ($nodeTitleCrawler as $element) {
      $element->parentNode->removeChild($element);
    }

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $changedHrefLinksCrawler = $differenceCrawler
      ->filter('del.diffa.diffhref + ins.diffa.diffhref');

    foreach ($changedHrefLinksCrawler as $element) {
      // Remove the preceding <del> element containing the previous date's wiki
      // page link.
      $element->parentNode->removeChild($element->previousSibling);

      // This essentially unwraps the <ins> element, moving all child elements
      // just before it in the order they appear. This ensures that if there are
      // any elements or nodes other than the expected <a>, they're preserved.
      //
      // @see https://stackoverflow.com/questions/11651365/how-to-insert-node-in-hierarchy-of-dom-between-one-node-and-its-child-nodes/11651813#11651813
      for($i = 0; $element->childNodes->length > 0; $i++) {
        $element->parentNode->insertBefore(
          // Note that we always specify index "0" as we're basically removing
          // the first child each time, similar to \array_shift(), and the child
          // list updates each time we do this, akin to removing the bottom most
          // card in a deck of cards on each iteration.
          $element->childNodes->item(0),
          $element
        );
      }

      // Remove the now-empty <ins>.
      $element->parentNode->removeChild($element);
    }

    return [
      '#markup'   => $differenceCrawler->html(),
      // Since the parsed diffs have already been run through the renderer and
      // filtering system, we're setting #printed to true to avoid Drupal
      // filtering the output a second time and breaking stuff. For example,
      // this would remove style attributes and strip SVG icons.
      '#printed'  => true,
    ];

  }

}
