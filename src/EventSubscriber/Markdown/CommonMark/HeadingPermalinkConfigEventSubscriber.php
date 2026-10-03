<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\AmbientImpactMarkdownEventInterface;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\CreateEnvironmentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to configure CommonMark heading permalinks.
 */
class HeadingPermalinkConfigEventSubscriber implements EventSubscriberInterface {

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

    /** @var \League\Config\ConfigurationInterface CommonMark config instance. */
    $config = $environment->getConfiguration();

    if (!$config->exists('heading_permalink/id_prefix')) {
      return;
    }

    // If the id_prefix configuration exists, sync the value to the
    // fragment_prefix value because the Markdown module does not currently
    // expose the latter in the UI.
    $environment->mergeConfig([
      'heading_permalink' => [
        'fragment_prefix' => $config->get('heading_permalink/id_prefix'),
      ],
    ]);

  }

}
