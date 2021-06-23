<?php

namespace Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\AmbientImpactMarkdownEventInterface;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\CreateEnvironmentEvent;
use Drupal\omnipedia_content\Utility\TableOfContentsHtmlClassesTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to alter CommonMark table of contents.
 */
class TableOfContentsEventSubscriber implements EventSubscriberInterface {

  use TableOfContentsHtmlClassesTrait;

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
    CreateEnvironmentEvent $event
  ): void {

    /** @var \League\CommonMark\ConfigurableEnvironmentInterface */
    $environment = $event->getEnvironment();

    $environment->mergeConfig([
      'table_of_contents' => [
        'html_class' => $this->getTableOfContentsListClass(),
      ],
    ]);

  }

}
