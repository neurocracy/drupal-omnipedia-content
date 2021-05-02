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
 * - Now preserves dashes (-).
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

    // If $context is provided and has a getStringContent() method that returns
    // a non-empty() value, replace $text with that return value. This allows us
    // to include the plain string contents of any AbstractStringContainer, such
    // as Abbreviation elements. By default, the CommonMark
    // HeadingPermalinkProcessor only includes Text and Code element content.
    //
    // @see \League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkProcessor::getChildText()
    //
    // @see https://github.com/thephpleague/commonmark/issues/615
    //   Issue opened about this.
    //
    // @todo Test and remove this once we start using CommonMark 2.0, as
    //   HeadingPermalinkProcessor::getChildText() will be removed then.
    if (
      \is_object($context) &&
      \method_exists($context, 'getStringContent') &&
      !empty($context->getStringContent())
    ) {
      $text = $context->getStringContent();
    }

    /** @var string */
    $separator = '_';

    // Trim white-space.
    /** @var string */
    $slug = \trim($text);

    // Try to replace white-space with the separator.
    $slug = \preg_replace('/\s+/u', $separator, $slug) ?? $slug;

    // Try removing characters other than letters, numbers, marks, dashes, and
    // the separator.
    $slug = \preg_replace(
      '/[^\p{L}\p{Nd}\p{Nl}\p{M}\-' . \preg_quote($separator) . ']+/u',
      '', $slug
    ) ?? $slug;

    return $slug;
  }

}
