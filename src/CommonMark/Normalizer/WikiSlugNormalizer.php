<?php

namespace Drupal\omnipedia_content\CommonMark\Normalizer;

use League\CommonMark\Normalizer\TextNormalizerInterface;

/**
 * MediaWiki-style CommonMark URL slug normalizer.
 *
 * This is a copy of the slug normalizer shipped with CommonMark with the
 * following changes to match MediaWiki's slugs:
 *
 * - No longer lowercased; will use capitalization as authored.
 *
 * - Switched to underscores as white-space replacement separators, from dashes.
 *
 * @see \League\CommonMark\Normalizer\SlugNormalizer
 *   Slug normalizer class shipped with CommonMark that this is copied from.
 */
class WikiSlugNormalizer implements TextNormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize(string $text, $context = null): string {
    /** @var string */
    $separator = '_';

    // Trim white-space.
    /** @var string */
    $slug = \trim($text);

    // Try to replace white-space with the separator.
    $slug = \preg_replace('/\s+/u', $separator, $slug) ?? $slug;

    // Try removing characters other than letters, numbers, marks, and the
    // separator.
    $slug = \preg_replace(
      '/[^\p{L}\p{Nd}\p{Nl}\p{M}' . $separator . ']+/u', '', $slug
    ) ?? $slug;

    return $slug;
  }

}
