<?php

namespace Drupal\omnipedia_content\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\omnipedia_content\Service\WikimediaLinkInterface;

/**
 * The Omnipedia Wikimedia link service.
 */
class WikimediaLink implements WikimediaLinkInterface {

  use StringTranslationTrait;

  /**
   * The Wikimedia site prefixes we recognize at the start of link URLs.
   *
   * @var array
   */
  protected $wikiPrefixes = [];

  /**
   * Constructs this service object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    TranslationInterface $stringTranslation
  ) {
    // Save dependencies.
    $this->stringTranslation = $stringTranslation;

    // Build prefixes, which requires the translation service.
    $this->wikiPrefixes = $this->buildPrefixes();
  }

  /**
   * Builds the supported Wikimedia link prefixes array.
   *
   * @return array
   *   An array of prefixes as keys and their values containing Wikimedia site
   *   titles as translated strings.
   */
  protected function buildPrefixes(): array {
    return [
      'wikipedia'   => $this->t('Wikipedia'),
      'wikiquote'   => $this->t('Wikiquote'),
      'wiktionary'  => $this->t('Wiktionary'),
      'wikinews'    => $this->t('Wikinews'),
      'wikisource'  => $this->t('Wikisource'),
      'wikibooks'   => $this->t('Wikibooks'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedPrefixes(): array {
    return $this->wikiPrefixes;
  }

  /**
   * {@inheritdoc}
   */
  public function isPrefixUrl(string $url): bool {
    /** @var array */
    $urlSplit = \explode(':', $url, 2);

    return isset($urlSplit[0]) && \in_array(
      $urlSplit[0], \array_keys($this->wikiPrefixes)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildUrl(string $url): string {
    if (!$this->isPrefixUrl($url)) {
      return $url;
    }

    /** @var array */
    $urlSplit = \explode(':', $url, 2);

    // @todo i18n
    /** @var string */
    $langCode = 'en';

    /** @var string */
    $article = \str_replace(' ', '_', $urlSplit[1]);

    return
      'https://' . $langCode . '.' . $urlSplit[0] . '.org/wiki/' . $article;
  }

}
