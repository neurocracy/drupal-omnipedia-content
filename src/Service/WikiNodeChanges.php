<?php

namespace Drupal\omnipedia_content\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Template\Attribute;
use Drupal\omnipedia_content\Service\WikiNodeChangesCacheInterface;
use Drupal\omnipedia_content\Service\WikiNodeChangesInterface;
use Drupal\omnipedia_core\Entity\NodeInterface;
use HtmlDiffAdvancedInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The Omnipedia wiki node changes service.
 */
class WikiNodeChanges implements WikiNodeChangesInterface {

  /**
   * Base CSS class for the changes container and child elements.
   *
   * @var string
   */
  protected const CHANGES_BASE_CLASS = 'omnipedia-changes';

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
   * The Omnipedia wiki node changes cache service.
   *
   * @var \Drupal\omnipedia_content\Service\WikiNodeChangesCacheInterface
   */
  protected $wikiNodeChangesCache;

  /**
   * Constructs this service object.
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
   * @param \Drupal\omnipedia_content\Service\WikiNodeChangesCacheInterface $wikiNodeChangesCache
   *   The Omnipedia wiki node changes cache service.
   *
   * @see $this->alterHtmlDiffConfig()
   */
  public function __construct(
    EntityTypeManagerInterface    $entityTypeManager,
    HtmlDiffAdvancedInterface     $htmlDiff,
    RendererInterface             $renderer,
    WikiNodeChangesCacheInterface $wikiNodeChangesCache
  ) {

    // Save dependencies.
    $this->entityTypeManager    = $entityTypeManager;
    $this->htmlDiff             = $htmlDiff;
    $this->renderer             = $renderer;
    $this->wikiNodeChangesCache = $wikiNodeChangesCache;

    $this->alterHtmlDiffConfig();

  }

  /**
   * {@inheritdoc}
   */
  public function getChangesBaseClass(): string {
    return self::CHANGES_BASE_CLASS;
  }

  /**
   * Get the BEM class for all diff elements.
   *
   * @return string
   */
  protected function getDiffElementClass(): string {
    return self::CHANGES_BASE_CLASS . '__diff';
  }

  /**
   * Get the BEM modifier class for added diff elements.
   *
   * @return string
   */
  protected function getDiffAddedModifierClass(): string {
    return $this->getDiffElementClass() . '--added';
  }

  /**
   * Get the BEM modifier class for removed diff elements.
   *
   * @return string
   */
  protected function getDiffRemovedModifierClass(): string {
    return $this->getDiffElementClass() . '--removed';
  }

  /**
   * Get the BEM modifier class for changed (added and removed) diff elements.
   *
   * @return string
   */
  protected function getDiffChangedModifierClass(): string {
    return $this->getDiffElementClass() . '--changed';
  }

  /**
   * Get the BEM class for added elements in changed content.
   *
   * @return string
   */
  protected function getDiffChangedAddedElementClass(): string {
    return $this->getDiffElementClass() . '-changed-added';
  }

  /**
   * Get the BEM class for added elements in changed content.
   *
   * @return string
   */
  protected function getDiffChangedRemovedElementClass(): string {
    return $this->getDiffElementClass() . '-changed-removed';
  }

  /**
   * Get the BEM class for link elements.
   *
   * @return string
   */
  protected function getDiffLinkElementClass(): string {
    return $this->getDiffElementClass() . '-link';
  }

  /**
   * Get the BEM modifier class for changed link elements.
   *
   * @return string
   */
  protected function getDiffLinkChangedModifierClass(): string {
    return $this->getDiffLinkElementClass() . '--changed';
  }

  /**
   * Alter the HTML diff configuration used for diffing.
   *
   * The following changes are made:
   *
   * - Disables the use of HTML Purifier to avoid having to wade through that
   *   configuration nightmare to whitelist attributes (e.g. style) and elements
   *   (such as SVG icons). Drupal's render and filtering systems should take
   *   care of any security stuff for us as we render before passing the markup
   *   to the HTML diff service. This config option requires @link
   *   https://github.com/caxy/php-htmldiff/releases/tag/v0.1.11
   *   caxy/php-htmldiff >= 0.1.11 @endLink which has been specified in this
   *   module's composer.json.
   *
   * - Disables special handling of <a> elements diffing, which would highlight
   *   changed href attributes. The only instance where this currently happens
   *   without the link text changing is when an internal wiki link changes to
   *   point to the new date's revision, which would be irrelevant to highlight.
   */
  protected function alterHtmlDiffConfig(): void {

    /** @var \Caxy\HtmlDiff\HtmlDiffConfig */
    $config = $this->htmlDiff->getConfig();

    $config->setPurifierEnabled(false);

    /** @var array */
    $isolatedDiffElements = $config->getIsolatedDiffTags();

    if (isset($isolatedDiffElements['a'])) {
      unset($isolatedDiffElements['a']);
    }

    $config->setIsolatedDiffTags($isolatedDiffElements);

  }

  /**
   * Alter any links with changed href attributes found in the provided DOM.
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
   */
  protected function alterChangedLinkHrefs(Crawler $crawler): void {

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
   * The following alterations are made:
   *
   * - The default classes are removed from <ins> elements and our own BEM
   *   classes are added. This handles diffed list items as well as standalone
   *   <ins> elements.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The Symfony DomCrawler instance to alter.
   *
   * @todo Should the list item <ins> selectors attempt to avoid potential
   *   nesting?
   */
  protected function alterAddedContent(Crawler $crawler): void {

    /** @var string */
    $changedInsClass = $this->getDiffChangedAddedElementClass();

    foreach ($crawler->filter(\implode(',', [
      'ins.diffins',
      '.diff-list > .replacement ins:not(.' . $changedInsClass . ')',
      '.diff-list > .new ins:not(.' . $changedInsClass . ')',
    ])) as $insElement) {
      $insElement->setAttribute('class', \implode(' ', [
        $this->getDiffElementClass(),
        $this->getDiffAddedModifierClass(),
      ]));
    }

  }

  /**
   * Alter any removed content found in the provided DOM.
   *
   * The following alterations are made:
   *
   * - The default classes are removed from <del> elements and our own BEM
   *   classes are added. This handles diffed list items as well as standalone
   *   <del> elements.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The Symfony DomCrawler instance to alter.
   *
   * @todo Should the list item <ins> selectors attempt to avoid potential
   *   nesting?
   */
  protected function alterRemovedContent(Crawler $crawler): void {

    /** @var string */
    $changedDelClass = $this->getDiffChangedRemovedElementClass();

    foreach ($crawler->filter(\implode(',', [
      'del.diffdel',
      '.diff-list > .removed del:not(.' . $changedDelClass . ')',
    ])) as $delElement) {
      $delElement->setAttribute('class', \implode(' ', [
        $this->getDiffElementClass(),
        $this->getDiffRemovedModifierClass(),
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
        $this->getDiffElementClass(),
        $this->getDiffChangedModifierClass(),
      ]));

      // The <del> element immediately preceding the <ins>.
      /** @var \DOMElement */
      $delElement = $insElement->previousSibling;

      // Insert the wrapper before the <ins>.
      $insElement->parentNode->insertBefore($changedContainer, $delElement);

      $changedContainer->appendChild($delElement);

      $changedContainer->appendChild($insElement);

      $delElement->setAttribute(
        'class', $this->getDiffChangedRemovedElementClass()
      );

      $insElement->setAttribute(
        'class', $this->getDiffChangedAddedElementClass()
      );
    }

  }

  /**
   * Alter any links found in the provided DOM.
   *
   * This removes the .diffmod class from links and adds our own BEM classes.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The Symfony DomCrawler instance to alter.
   */
  protected function alterLinks(Crawler $crawler): void {

    foreach ($crawler->filter('a.diffmod') as $linkElement) {

      // Parse any existing class attribute and create a new Attributes object
      // to make class manipulation easier.
      /** @var \Drupal\Core\Template\Attribute */
      $attributes = new Attribute([
        'class' => \preg_split(
          '/\s+/' , \trim($linkElement->getAttribute('class'))
        ),
      ]);

      $attributes->removeClass('diffmod');

      $attributes->addClass($this->getDiffLinkElementClass());
      $attributes->addClass($this->getDiffLinkChangedModifierClass());

      $linkElement->setAttribute(
        'class', \implode(' ', $attributes->getClass()->value())
      );
    }

  }

  /**
   * Get diff content for a wiki node.
   *
   * @param \Drupal\omnipedia_core\Entity\NodeInterface $node
   *   A wiki node object to get the diff content for.
   *
   * @return array
   *   The diff render array.
   *
   * @see $this->alterChangedContent()
   *   Invoked to alter content that has changed, i.e. which has both removed
   *   and added content.
   *
   * @see $this->alterAddedContent()
   *   Invoked to alter content that was added.
   *
   * @see $this->alterRemovedContent()
   *   Invoked to alter content that was removed.
   *
   * @see $this->alterLinks()
   *   Invoked to alter links.
   */
  protected function getDiff(NodeInterface $node): array {

    // Return a cached render array if one is found in the cache.
    if ($this->wikiNodeChangesCache->isCached($node)) {
      return $this->wikiNodeChangesCache->get($node);
    }

    /** \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $previousNode = $node->getPreviousWikiNodeRevision();

    /** @var \Drupal\Core\Entity\EntityViewBuilderInterface */
    $viewBuilder = $this->entityTypeManager->getViewBuilder(
      $node->getEntityTypeId()
    );

    /** @var array */
    $previousRenderArray = $viewBuilder->view($previousNode, 'full');

    /** @var array */
    $currentRenderArray = $viewBuilder->view($node, 'full');

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

    $this->alterLinks($differenceCrawler);

    /** @var array */
    $renderArray = [
      '#markup'   => $differenceCrawler->html(),

      // Since the parsed diffs have already been run through the renderer and
      // filtering system, we're setting #printed to true to avoid Drupal
      // filtering the output a second time and breaking stuff. For example,
      // this would remove style attributes and strip SVG icons.
      '#printed'  => true,
    ];

    // Save the rendered diff to cache.
    $this->wikiNodeChangesCache->set($node, $renderArray);

    return $renderArray;

  }

  /**
   * {@inheritdoc}
   *
   * @see $this->getDiff()
   *   Gets diff content for a wiki node.
   */
  public function build(NodeInterface $node): array {

    /** \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $previousNode = $node->getPreviousWikiNodeRevision();

    // Bail if not a wiki node or the wiki node does not have a previous
    // revision.
    if (!\is_object($previousNode)) {
      return [];
    }

    /** @var array */
    $renderArray = $this->getDiff($node);

    $renderArray['#markup'] =
      // Note that we can't use '#type' => 'container' or some other wrapper
      // while also setting '#printed' => true as we've told Drupal to do no
      // further rendering.
      //
      // @todo Rework this as a Twig template?
      '<div class="' . $this->getChangesBaseClass() . '">' .
        $renderArray['#markup'] .
      '</div>';

    $renderArray['#attached']['library'][] =
      'omnipedia_content/component.changes';

    // Add both the current and previous wiki nodes as cacheable dependencies of
    // the render array.
    $this->renderer->addCacheableDependency($renderArray, $node);
    $this->renderer->addCacheableDependency($renderArray, $previousNode);

    return $renderArray;

  }

}
