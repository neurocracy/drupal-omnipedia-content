<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\AmbientImpactMarkdownEventInterface;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\CreateEnvironmentEvent;
use Drupal\omnipedia_content\CommonMark\Normalizer\WikiSlugNormalizer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to configure CommonMark with our wiki slug normalizer.
 */
class WikiSlugNormalizerEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {

    return [
      AmbientImpactMarkdownEventInterface::COMMONMARK_CREATE_ENVIRONMENT =>
        'onCommonMarkCreateEnvironment',
    ];

  }

  /**
   * CreateEnvironmentEvent callback.
   *
   * @param \Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\CreateEnvironmentEvent $event
   *   The event object.
   */
  public function onCommonMarkCreateEnvironment(
    CreateEnvironmentEvent $event,
  ): void {

    /** @var \League\CommonMark\Environment\EnvironmentBuilderInterface */
    $environment = $event->getEnvironment();

    $environment->mergeConfig([
      'slug_normalizer' => [
        'instance' => new WikiSlugNormalizer(),
      ],
    ]);

  }

}
