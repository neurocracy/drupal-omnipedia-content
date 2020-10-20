<?php

namespace Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\AmbientImpactMarkdownEventInterface;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\CreateEnvironmentEvent;
use Drupal\omnipedia_content\CommonMark\Block\Element\IndentedContent;
use Drupal\omnipedia_content\CommonMark\Block\Parser\IndentedContentParser;
use Drupal\omnipedia_content\CommonMark\Block\Renderer\IndentedContentRenderer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to add CommonMark indented content parser and renderer.
 *
 * @see \Drupal\omnipedia_content\CommonMark\Block\Element\IndentedContent
 *   Indented content CommonMark element.
 *
 * @see \Drupal\omnipedia_content\CommonMark\Block\Parser\IndentedContentParser
 *   Indented content CommonMark parser.
 *
 * @see \Drupal\omnipedia_content\CommonMark\Block\Renderer\IndentedContentRenderer
 *   Indented content CommonMark renderer.
 */
class IndentedContentEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      AmbientImpactMarkdownEventInterface::COMMONMARK_CREATE_ENVIRONMENT => 'onCommonMarkCreateEnvironment',
    ];
  }

  /**
   * Register the indented content parser.
   *
   * @param \Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\CreateEnvironmentEvent $event
   *   The event object.
   */
  public function onCommonMarkCreateEnvironment(
    CreateEnvironmentEvent $event
  ): void {
    /** @var \League\CommonMark\ConfigurableEnvironmentInterface */
    $environment = $event->getEnvironment();

    // This adds our IndentedContentParser class one weight lighter than
    // \League\CommonMark\Block\Parser\IndentedCodeParser so that we can render
    // indented content before the latter parser gets to it, thus preventing it
    // from matching.
    //
    // @see \League\CommonMark\Extension\CommonMarkCoreExtension::register()
    //   Default CommonMark parsers added here.
    $environment->addBlockParser(new IndentedContentParser(), -99);

    // This adds our IndentedContentRenderer as the renderer for our
    // IndentedContent element.
    //
    // @see \League\CommonMark\Extension\CommonMarkCoreExtension::register()
    //   Default CommonMark parsers added here.
    $environment->addBlockRenderer(
      IndentedContent::class, new IndentedContentRenderer()
    );
  }

}
