<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Plugin\Filter;

use Drupal\ambientimpact_core\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\omnipedia_content\Utility\TableOfContentsHtmlClassesTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Provides a filter to alter Markdown filter output.
 *
 * This does the following:
 *
 * - Adds "reference" class to <sup> elements containing .reference__link
 *   elements.
 *
 * - Works around @link https://github.com/thephpleague/commonmark/issues/596
 *   an issue with CommonMark @endLink where our References heading text
 *   isn't being generated into the table of contents, resulting in an empty
 *   link.
 *
 * - This takes the contents of any .omnipedia-media-caption-rendered element
 *   and sets them as a 'data-photoswipe-caption' on the adjacent image field
 *   link, if any. The plain text of the caption is used, without any HTML
 *   elements, e.g. links or abbreviations.
 *
 * - Finds any table of contents lists and wraps them in a container with a
 *   heading.
 *
 * @Filter(
 *   id           = "omnipedia_markdown_alterations",
 *   title        = @Translation("Omnipedia: Markdown output alterations"),
 *   description  = @Translation("This alters the output of the Markdown filter. This should be placed <strong>after</strong> the Markdown filter in the processing order."),
 *   type         = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
 * )
 *
 * @see \Drupal\omnipedia_content\Plugin\Markdown\CommonMark\Extension\FootnoteExtension
 *   Alters the Markdown footnotes output as much as possible in CommonMark
 *   document parsed event.
 *
 * @todo Split the various methods into event subscribers and add an event they
 *   can subscribe to.
 */
class MarkdownAlterationsFilter extends FilterBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  use TableOfContentsHtmlClassesTrait;

  /**
   * Constructs this filter object; saves dependencies.
   *
   * @param array $configuration
   *   A configuration array containing information about the plug-in instance.
   *
   * @param string $pluginId
   *   The plugin_id for the plug-in instance.
   *
   * @param array $pluginDefinition
   *   The plug-in implementation definition. PluginBase defines this as mixed,
   *   but we should always have an array so the type is set.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    protected $stringTranslation,
  ) {

    parent::__construct($configuration, $pluginId, $pluginDefinition);

  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginId, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginId, $pluginDefinition,
      $container->get('string_translation'),
    );
  }

  /**
   * Alter references.
   *
   * - Adds 'references-heading' class to the references heading.
   *
   * - Adds 'references__list' class to the references list.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The Symfony DomCrawler instance to alter.
   */
  protected function alterReferences(Crawler $crawler): void {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $referenceSupCrawler = $crawler
      ->filter('.reference__link')
      ->evaluate('./ancestor::sup');

    foreach ($referenceSupCrawler as $sup) {
      $sup->setAttribute('class', 'reference');
    }

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $referencesCrawler = $crawler->filter('.references');

    /** @var \DOMElement|null */
    $referencesList = $referencesCrawler->filter('ol')->getNode(0);

    if (\is_object($referencesList)) {

      Html::setElementClassAttribute(
        $referencesList,
        Html::getElementClassAttribute($referencesList)
          ->addClass('references__list'),
      );

    }

    // Try to find the first heading preceding the .references container at the
    // end of the document.
    //
    // This selects the nearest preceding heading, regardless of what heading
    // level it is. This assumes that that's the References heading.
    //
    // @see https://stackoverflow.com/questions/30775686/xpath-get-closest-heading-element-h1-h2-h3-etc
    /** @var array */
    $referencesHeadingResult = $referencesCrawler->evaluate(
      './preceding-sibling::*[' . \implode(' or ', [
        'self::h1',
        'self::h2',
        'self::h3',
        'self::h4',
        'self::h5',
        'self::h6',
      ]) . '][1]',
    );

    foreach ($referencesHeadingResult as $heading) {

      Html::setElementClassAttribute(
        $heading,
        Html::getElementClassAttribute($heading)
          ->addClass('references-heading'),
      );

      /** @var \Symfony\Component\DomCrawler\Crawler */
      $permalinkCrawler = new Crawler($heading);

      // Attempt to find the permalink in the heading.
      /** @var \DOMElement|null */
      $permalink = $permalinkCrawler->filter('.heading-permalink')->getNode(0);

      if ($permalink === null) {
        break;
      }

      /** @var string|null */
      $permalinkName  = $permalink->getAttribute('name');
      /** @var string|null */
      $permalinkId    = $permalink->getAttribute('id');

      if (empty($permalinkName) || empty($permalinkId)) {
        break;
      }

      // Try and find the corresponding link in the table of contents.
      /** @var \DOMElement|null */
      $tableOfContentsLink = $crawler
        ->filter('.table-of-contents a[href="#' . $permalinkId . '"]')
        ->getNode(0);

      if ($tableOfContentsLink === null) {
        break;
      }

      $tableOfContentsLink->nodeValue = $permalinkName;

    }

  }

  /**
   * Alter captions.
   *
   * This takes the contents of any .omnipedia-media-caption-rendered element
   * and sets them as a 'data-photoswipe-caption' on the adjacent image field
   * link, if any. The plain text of the caption is used, without any HTML
   * elements, e.g. links or abbreviations.
   *
   * Additionally, this removes the .omnipedia-media-caption-rendered element
   * regardless of whether it has been used to avoid potential layout issues.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The Symfony DomCrawler instance to alter.
   */
  protected function alterCaptions(Crawler $crawler): void {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $captionsCrawler = $crawler->filter('.omnipedia-media-caption-rendered');

    foreach ($captionsCrawler as $caption) {

      /** @var \Symfony\Component\DomCrawler\Crawler The adjacent image field,
          if any. */
      $fieldCrawler = (new Crawler($caption))->nextAll()
        ->filter('.field--type-image')->first();

      if (count($fieldCrawler) === 0) {
        continue;
      }

      /** @var \Symfony\Component\DomCrawler\Crawler The image field link, if
          any. */
      $linkCrawler = $fieldCrawler->filter('a')->first();

      if (count($linkCrawler) === 0) {
        continue;
      }

      /** @var \DOMElement The image field link element. */
      $link = $linkCrawler->getNode(0);

      // Skip any links that already have a caption set by something else.
      if (!empty($link->getAttribute('data-photoswipe-caption'))) {
        continue;
      }

      // Set the caption contents as plain text, i.e. stripped of HTML elements.
      $link->setAttribute(
        'data-photoswipe-caption', \trim($caption->textContent),
      );

    }

    // Remove all captions, regardless of whether they were used, as they can
    // potentially cause layout issues if left in.
    foreach ($captionsCrawler as $caption) {
      $caption->parentNode->removeChild($caption);
    }

  }

  /**
   * Alter lists.
   *
   * This unwraps any <p> elements found inside of list items, both because
   * they're unnecessary for our uses and to fix an issue with the
   * caxy/php-htmldiff library erroneously thinking the list items are always
   * completely different, even when they're identical.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The Symfony DomCrawler instance to alter.
   *
   * @see https://github.com/caxy/php-htmldiff/issues/100
   *   GitHub issue describing the problem this method solves.
   */
  protected function alterLists(Crawler $crawler): void {

    foreach ($crawler->filter('li p') as $paragraph) {

      Html::unwrapNode($paragraph);

    }

  }

  /**
   * Alter table of contents.
   *
   * - Finds any table of contents lists and wraps them in a container with a
   *   heading.
   *
   * - Adds the table of contents list class to all nested lists.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The Symfony DomCrawler instance to alter.
   */
  protected function alterTableOfContents(Crawler $crawler): void {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $listCrawler = $crawler->filter('.' . $this->getTableOfContentsListClass());

    foreach ($listCrawler as $list) {

      /** @var \DOMNode|null */
      $container = (new Crawler(
        '<div class="' . $this->getTableOfContentsBaseClass() . '">' .
          '<h3 class="' . $this->getTableOfContentsHeadingClass() . '">' .
            (string) $this->t('Table of contents') .
          '</h3>' .
        '</div>',
      ))->filter(
        '.' . $this->getTableOfContentsBaseClass(),
      )->getNode(0);

      if (!($container instanceof \DOMNode)) {
        continue;
      }

      $container = $list->parentNode->insertBefore(
        $list->ownerDocument->importNode($container, true), $list,
      );

      $container->appendChild($list);

      foreach ($listCrawler->children()->filter('ol, ul') as $subList) {
        Html::setElementClassAttribute(
          $subList,
          Html::getElementClassAttribute($subList)
            ->addClass($this->getTableOfContentsListClass()),
        );
      }

    }

  }

  /**
   * {@inheritdoc}
   *
   * @see $this->alterReferences()
   *
   * @see $this->alterCaptions()
   *
   * @see $this->alterLists()
   *
   * @see $this->alterTableOfContents()
   */
  public function process($text, $langCode) {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $crawler = new Crawler(
      // The <div> is to prevent the PHP DOM automatically wrapping any
      // top-level text content in a <p> element.
      '<div id="omnipedia-markdown-alterations-filter-root">' .
        (string) $text .
      '</div>',
    );

    $this->alterReferences($crawler);

    $this->alterCaptions($crawler);

    $this->alterLists($crawler);

    $this->alterTableOfContents($crawler);

    return new FilterProcessResult(
      $crawler->filter(
        '#omnipedia-markdown-alterations-filter-root',
      )->html(),
    );

  }

}
