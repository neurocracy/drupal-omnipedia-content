<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\CommonMark\Extension\HeadingPermalink;

use Drupal\omnipedia_content\CommonMark\Normalizer\WikiSlugNormalizer;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalink;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkProcessor;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkRenderer;

/**
 * Omnipedia heading permalink CommonMark extension.
 *
 * This is a copy of the extension that ships with CommonMark, with our custom
 * slug normalizer passed to HeadingPermalinkProcessor.
 *
 * @see \League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension
 *   The heading permalink that ships with CommonMark.
 *
 * @see \Drupal\omnipedia_content\CommonMark\Normalizer\WikiSlugNormalizer
 *   Our custom slug normalizer passed to HeadingPermalinkProcessor.
 */
class HeadingPermalinkExtension implements ExtensionInterface {

  /**
   * {@inheritdoc}
   */
  public function register(EnvironmentBuilderInterface $environment): void {
    $environment->addEventListener(
      DocumentParsedEvent::class,
      new HeadingPermalinkProcessor(new WikiSlugNormalizer()),
      -100
    );

    $environment->addRenderer(
      HeadingPermalink::class, new HeadingPermalinkRenderer()
    );
  }

}
