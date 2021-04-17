<?php

namespace Drupal\omnipedia_content\Plugin\Omnipedia\Element;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\omnipedia_content\PluginManager\OmnipediaElementManagerInterface;
use Drupal\omnipedia_content\Plugin\Omnipedia\Element\OmnipediaElementBase;
use Drupal\omnipedia_core\Service\TimelineInterface;
use Drupal\omnipedia_core\Service\WikiNodeRevisionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Main page element.
 *
 * @OmnipediaElement(
 *   id           = "main_page",
 *   html_element = "main-page",
 *   title        = @Translation("Main page"),
 *   description  = @Translation("Main page template, intended to abstract as much of the main page structure as possible.")
 * )
 */
class MainPage extends OmnipediaElementBase {

  /**
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_core\Service\TimelineInterface
   */
  protected $timeline;

  /**
   * The Omnipedia wiki node revision service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeRevisionInterface
   */
  protected $wikiNodeRevision;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\omnipedia_core\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeRevisionInterface $wikiNodeRevision
   *   The Omnipedia wiki node revision service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    OmnipediaElementManagerInterface $elementManager,
    TranslationInterface      $stringTranslation,
    TimelineInterface         $timeline,
    WikiNodeRevisionInterface $wikiNodeRevision
  ) {
    parent::__construct(
      $configuration, $pluginID, $pluginDefinition,
      $elementManager, $stringTranslation
    );

    // Save dependencies.
    $this->timeline         = $timeline;
    $this->wikiNodeRevision = $wikiNodeRevision;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginID, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginID, $pluginDefinition,
      $container->get('plugin.manager.omnipedia_element'),
      $container->get('string_translation'),
      $container->get('omnipedia.timeline'),
      $container->get('omnipedia.wiki_node_revision')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getTheme(): array {
    return [
      'omnipedia_main_page' => [
        'variables' => [
          'featured_article'        => '',
          'featured_article_media'  => '',
          'featured_article_url'    => '',
          'news'        => '',
          'news_media'  => '',
        ],
        'template'  => 'omnipedia-main-page',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray(): array {
    /** @var \Symfony\Component\DomCrawler\Crawler */
    $featuredArticleElement = $this->elements->filter('featured-article');

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $newsElement = $this->elements->filter('news');

    /** @var \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $featuredArticleNode = $this->wikiNodeRevision->getWikiNodeRevision(
      $featuredArticleElement->attr('article'),
      $this->timeline->getDateFormatted('current', 'storage')
    );

    if ($featuredArticleNode === null) {
      $this->setError($this->t(
        'Cannot find the specified featured wiki page titled "@title" for the current date.',
        ['@title' => $featuredArticleElement->attr('article')]
      ));
    }

    // The Url object containing the URL to the featured article, or the front
    // page if the featured article couldn't be resolved.
    /** @var \Drupal\Core\Url */
    $featuredArticleUrl = $featuredArticleNode === null ?
      Url::fromUri('base:<front>') : $featuredArticleNode->toUrl();

    /** @var array */
    $renderArray = [
      '#theme' => 'omnipedia_main_page',
      '#featured_article'       => [
        '#markup' => $featuredArticleElement->html(),
      ],
      '#featured_article_url'   => $featuredArticleUrl,
      '#news'       => [
        '#markup' => $newsElement->html(),
      ],

      '#attached'   => [
        'library'   => ['omnipedia_content/component.main_page'],
      ],
    ];

    // Build the media elements for the featured article and news sections.
    foreach ([
      'featured_article_media'  => [
        'element' => $featuredArticleElement,
        'align'   => 'left',
        'caption' => $featuredArticleElement->attr('media-caption'),
      ],
      'news_media'              => [
        'element' => $newsElement,
        'align'   => 'right',
        'caption' => $newsElement->attr('media-caption'),
      ],
    ] as $variableName => $mediaType) {
      $mediaName = $mediaType['element']->attr('media');

      if ($mediaName === null) {
        continue;
      }

      // Create a new <media> element onto which we set the found options.
      /** @var \Symfony\Component\DomCrawler\Crawler */
      $mediaCrawler = new Crawler('<media></media>');

      /** @var \DOMElement */
      $mediaElement = $mediaCrawler->filter('media')->getNode(0);

      $mediaElement->setAttribute('name',   $mediaName);
      $mediaElement->setAttribute('align',  $mediaType['align']);
      $mediaElement->setAttribute('style', 'frameless');

      // If a caption exists and has been read from the relevant attribute, it
      // will be a string and only null if it doesn't exist.
      if ($mediaType['caption'] !== null) {
        $mediaElement->setAttribute('caption', $mediaType['caption']);
      }

      // Create the plug-in instance.
      $mediaInstance = $this->elementManager->createInstance('media', [
        'elements' => new Crawler($mediaElement),
      ]);

      $renderArray['#' . $variableName] = $mediaInstance->getRenderArray();
    }

    return $renderArray;
  }

}
