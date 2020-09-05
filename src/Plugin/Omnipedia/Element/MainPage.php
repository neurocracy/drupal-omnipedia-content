<?php

namespace Drupal\omnipedia_content\Plugin\Omnipedia\Element;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\omnipedia_content\OmnipediaElementBase;
use Drupal\omnipedia_core\Service\TimelineInterface;
use Drupal\omnipedia_core\Service\WikiNodeRevisionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Main page element.
 *
 * @OmnipediaElement(
 *   id = "main_page",
 *   html_element = "main-page",
 *   title = @Translation("Main page"),
 *   description = @Translation("Main page template, intended to abstract as much of the main page structure as possible.")
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
    TranslationInterface      $stringTranslation,
    TimelineInterface         $timeline,
    WikiNodeRevisionInterface $wikiNodeRevision
  ) {
    parent::__construct(
      $configuration, $pluginID, $pluginDefinition, $stringTranslation
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
      'main_page' => [
        'variables' => [
          'featured_article'        => '',
          'featured_article_media'  => '',
          'featured_article_url'    => '',
          'news'        => '',
          'news_media'  => '',
        ],
        'template'  => 'main-page',
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

    return [
      '#theme' => 'main_page',
      '#featured_article'       => [
        '#markup' => $featuredArticleElement->html(),
      ],
      '#featured_article_media' => $featuredArticleElement->attr('media'),
      '#featured_article_url'   => $featuredArticleUrl,
      '#news'       => [
        '#markup' => $newsElement->html(),
      ],
      '#news_media' => $newsElement->attr('media'),

      '#attached'   => [
        'library'   => ['omnipedia_content/component.main_page'],
      ],
    ];
  }

}
