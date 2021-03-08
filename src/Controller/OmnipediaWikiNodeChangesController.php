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
use Drupal\omnipedia_core\Service\TimelineInterface;
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
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_core\Service\TimelineInterface
   */
  protected $timeline;

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
   * Base CSS class for the changes container and child elements.
   *
   * @var string
   */
  protected $changesBaseClass = 'omnipedia-changes';

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
   *
   * @param \Drupal\omnipedia_core\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   */
  public function __construct(
    EntityTypeManagerInterface  $entityTypeManager,
    HtmlDiffAdvancedInterface   $htmlDiff,
    RendererInterface           $renderer,
    TimelineInterface           $timeline
  ) {
    $this->entityTypeManager  = $entityTypeManager;
    $this->htmlDiff           = $htmlDiff;
    $this->renderer           = $renderer;
    $this->timeline           = $timeline;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('diff.html_diff'),
      $container->get('renderer'),
      $container->get('omnipedia.timeline')
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
   *   the wiki node is not a main page, the wiki node has a previous revision,
   *   and $account has access to both the provided wiki node and its previous
   *   revision.
   */
  public function access(
    AccountInterface $account, NodeInterface $node
  ): AccessResultInterface {

    /** \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $previousNode = $this->getPreviousNode($node);

    return AccessResult::allowedIf(
      !$node->isMainPage() &&
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
   * Alter any changed links found in the provided DOM.
   *
   * Note that this method is left in the code in case we later need to
   * conditionally remove href highlghting, but is no longer used as we
   * disable the special handling for <a> elements in the view() method to
   * reduce the amount of DOM alteration we have to do.
   *
   * Any links that are marked as changed due to having different href
   * attributes have the old revision removed and the current revision not
   * marked by removing the <ins> element and placing the link back in its
   * place. This is done because there's no benefit from highlighting the
   * change, as this is expected and would just add noise.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The Symfony DomCrawler instance to alter.
   *
   * @todo Check if links whose href attributes changed are both internal wiki
   *   node paths before removing the changed status?
   *
   * @see $this->view()
   */
  protected function alterChangedLinks(Crawler $crawler): void {

    foreach (
      $crawler->filter('del.diffa.diffhref + ins.diffa.diffhref') as $insElement
    ) {
      // Remove the preceding <del> element containing the previous date's wiki
      // page link.
      $insElement->parentNode->removeChild($insElement->previousSibling);

      // This essentially unwraps the <ins> element, moving all child elements
      // just before it in the order they appear. This ensures that if there are
      // any elements or nodes other than the expected <a>, they're preserved.
      //
      // @see https://stackoverflow.com/questions/11651365/how-to-insert-node-in-hierarchy-of-dom-between-one-node-and-its-child-nodes/11651813#11651813
      for($i = 0; $insElement->childNodes->length > 0; $i++) {
        $insElement->parentNode->insertBefore(
          // Note that we always specify index "0" as we're basically removing
          // the first child each time, similar to \array_shift(), and the child
          // list updates each time we do this, akin to removing the bottom most
          // card in a deck of cards on each iteration.
          $insElement->childNodes->item(0),
          $insElement
        );
      }

      // Remove the now-empty <ins>.
      $insElement->parentNode->removeChild($insElement);
    }

  }

  /**
   * Alter any added content found in the provided DOM.
   *
   * The following alterations are made on <ins> elements found via the
   * 'ins.diffins' selector:
   *
   * - The 'diffins' class is removed from the <ins> elements and our own BEM
   *   classes are added.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The Symfony DomCrawler instance to alter.
   */
  protected function alterAddedContent(Crawler $crawler): void {

    foreach ($crawler->filter('ins.diffins') as $insElement) {
      $insElement->setAttribute('class', \implode(' ', [
        $this->changesBaseClass . '__diff',
        $this->changesBaseClass . '__diff--added',
      ]));
    }

  }

  /**
   * Alter any removed content found in the provided DOM.
   *
   * The following alterations are made on <del> elements found via the
   * 'del.diffdel' selector:
   *
   * - The 'diffdel' class is removed from the <del> elements and our own BEM
   *   classes are added.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The Symfony DomCrawler instance to alter.
   */
  protected function alterRemovedContent(Crawler $crawler): void {

    foreach ($crawler->filter('del.diffdel') as $delElement) {
      $delElement->setAttribute('class', \implode(' ', [
        $this->changesBaseClass . '__diff',
        $this->changesBaseClass . '__diff--removed',
      ]));
    }

  }

  /**
   * Alter any changed content found in the provided DOM.
   *
   * The following alterations are made on <del> and <ins> elements found via
   * the 'del.diffmod + ins.diffmod' selector:
   *
   * - Both the <del> and <ins> elements are wrapped in a changes container
   *   <span> for styling.
   *
   * - The 'diffmod' class is removed from both the <del> and <ins> elements and
   *   our own BEM classes are added.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The Symfony DomCrawler instance to alter.
   */
  protected function alterChangedContent(Crawler $crawler): void {

    foreach ($crawler->filter('del.diffmod + ins.diffmod') as $insElement) {
      /** @var \DOMElement|false */
      $changedContainer = $insElement->ownerDocument->createElement('span');

      if (!$changedContainer) {
        continue;
      }

      $changedContainer->setAttribute('class', \implode(' ', [
        $this->changesBaseClass . '__diff',
        $this->changesBaseClass . '__diff--changed',
      ]));

      // The <del> element immediately preceding the <ins>.
      /** @var \DOMElement */
      $delElement = $insElement->previousSibling;

      // Insert the wrapper before the <ins>.
      $insElement->parentNode->insertBefore($changedContainer, $delElement);

      $changedContainer->appendChild($delElement);

      $changedContainer->appendChild($insElement);

      $delElement->setAttribute(
        'class', $this->changesBaseClass . '__diff-changed-removed'
      );

      $insElement->setAttribute(
        'class', $this->changesBaseClass . '__diff-changed-added'
      );
    }

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
   * Additionally, this disables special handling of the diffing of <a>
   * elements, which would diff changed href attributes, as the only instance
   * where this currently happens without the link text changing is when an
   * internal wiki link changes to point to the new date's revision, which would
   * be irrelevant to highlight.
   *
   * @param \Drupal\omnipedia_core\Entity\NodeInterface $node
   *   A node object.
   *
   * @return array
   *   A render array containing the changes content for this request.
   *
   * @see $this->alterChangedContent()
   *
   * @see $this->alterAddedContent()
   *
   * @see $this->alterRemovedContent()
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

    /** @var \Caxy\HtmlDiff\HtmlDiffConfig */
    $htmlDiffConfig = $this->htmlDiff->getConfig();

    // Disable the use of HTML Purifier to avoid having to wade through that
    // configuration nightmare to whitelist attributes (e.g. style) and elements
    // (such as SVG icons). Drupal's render and filtering systems should take
    // care of any security stuff for us as we render before passing the markup
    // to the HTML diff service. This config option requires caxy/php-htmldiff
    // >= 0.1.11 which has been specified in this module's composer.json.
    //
    // @see https://github.com/caxy/php-htmldiff/releases/tag/v0.1.11
    $htmlDiffConfig->setPurifierEnabled(false);

    /** @var array */
    $isolatedDiffElements = $htmlDiffConfig->getIsolatedDiffTags();

    if (isset($isolatedDiffElements['a'])) {
      unset($isolatedDiffElements['a']);
    }

    $htmlDiffConfig->setIsolatedDiffTags($isolatedDiffElements);

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

    // Removes the node title element that Drupal generates.
    //
    // @todo Can this be handled in a node entity view mode instead?
    foreach ($differenceCrawler->filter('.node__title') as $element) {
      $element->parentNode->removeChild($element);
    }

    $this->alterChangedContent($differenceCrawler);

    $this->alterAddedContent($differenceCrawler);

    $this->alterRemovedContent($differenceCrawler);

    return [
      // Note that we can't use '#type' => 'container' or some other wrapper
      // while also setting '#printed' => true as we've told Drupal to do no
      // further rendering.
      //
      // @todo Rework this as a Twig template?
      '#markup'   => '<div class="' . $this->changesBaseClass . '">' .
        $differenceCrawler->html() .
      '</div>',

      // Since the parsed diffs have already been run through the renderer and
      // filtering system, we're setting #printed to true to avoid Drupal
      // filtering the output a second time and breaking stuff. For example,
      // this would remove style attributes and strip SVG icons.
      '#printed'  => true,

      '#attached'   => [
        'library'     => ['omnipedia_content/component.changes'],
      ],
    ];

  }

}
