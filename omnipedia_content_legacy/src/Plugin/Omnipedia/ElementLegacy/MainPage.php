<?php

namespace Drupal\omnipedia_content_legacy\Plugin\Omnipedia\ElementLegacy;

use Drupal\omnipedia_content_legacy\OmnipediaElementLegacyBase;

/**
 * Main page legacy element.
 *
 * @OmnipediaElementLegacy(
 *   id     = "main_page",
 *   title  = @Translation("Main page")
 * )
 */
class MainPage extends OmnipediaElementLegacyBase {

  /**
   * {@inheritdoc}
   */
  public static function getTheme(): array {
    return [
      'omnipedia_main_page_legacy' => [
        'variables' => [
          'featured_article'        => '',
          'featured_article_title'  => '',
          'featured_article_media'  => '',
          'news'                => '',
          'news_media'          => '',
          'news_media_caption'  => '',
        ],
        'template'  => 'omnipedia-main-page-legacy',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray(): array {
    // The featured article path exploded into an array. We have to create a
    // variable so that PHP doesn't throw a notice because \end requires an
    // array to be passed by reference.
    /** @var array */
    $featuredArticlePathParts = \explode(
      '/', $this->options['featured_article_path']
    );

    // The featured article title, extracted from the path and with underscores
    // converted to spaces.
    /** @var string */
    $featuredArticleTitle = \str_replace(
      '_', ' ', \end($featuredArticlePathParts)
    );

    // Similar tactic for the media paths, except that we shouldn't replace
    // underscores with spaces.
    /** @var array */
    $mediaPaths = [];

    foreach ([
      'featured_article_media',
      'news_media',
    ] as $mediaOptionName) {
      /** @var array */
      $mediaPathParts = \explode(
        '/', $this->options[$mediaOptionName]
      );

      /** @var string */
      $mediaPaths[$mediaOptionName] = \end($mediaPathParts);
    }

    return [
      '#theme'                  => 'omnipedia_main_page_legacy',
      '#featured_article'       => $this->options['featured_article'],
      '#featured_article_title' => $featuredArticleTitle,
      '#featured_article_media' => $mediaPaths['featured_article_media'],
      '#news'                   => $this->options['news'],
      '#news_media'             => $mediaPaths['news_media'],
      '#news_media_caption'     => $this->options['news_media_caption'],
    ];
  }

}
