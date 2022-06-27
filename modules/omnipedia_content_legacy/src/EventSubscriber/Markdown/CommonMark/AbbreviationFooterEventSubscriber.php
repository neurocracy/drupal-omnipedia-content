<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content_legacy\EventSubscriber\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\AmbientImpactMarkdownEventInterface;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\CreateEnvironmentEvent;
use Drupal\omnipedia_content_legacy\CommonMark\Block\Element\AbbreviationFooter;
use Drupal\omnipedia_content_legacy\CommonMark\Block\Parser\AbbreviationFooterParser;
use Drupal\omnipedia_content_legacy\CommonMark\Block\Renderer\AbbreviationFooterRenderer;
use Drupal\omnipedia_content\Service\AbbreviationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to add legacy footer abbreviations to CommonMark.
 *
 * This parses any legacy footer abbreviations in the
 * @link https://michelf.ca/projects/php-markdown/extra/#abbr PHP Markdown Extra
 * syntax @endLink, and passes them to the abbreviations service.
 *
 * @see \Drupal\omnipedia_content_legacy\CommonMark\Block\Element\AbbreviationFooter
 *   Legacy footer abbreviation CommonMark element.
 *
 * @see \Drupal\omnipedia_content_legacy\CommonMark\Block\Parser\AbbreviationFooterParser
 *   Legacy footer abbreviation CommonMark parser.
 *
 * @see \Drupal\omnipedia_content_legacy\CommonMark\Block\Renderer\AbbreviationFooterRenderer
 *   Legacy footer abbreviation CommonMark renderer.
 */
class AbbreviationFooterEventSubscriber implements EventSubscriberInterface {

  /**
   * The Omnipedia abbreviation service.
   *
   * @var \Drupal\omnipedia_content\Service\AbbreviationInterface
   */
  protected $abbreviation;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_content\Service\AbbreviationInterface $abbreviation
   *   The Omnipedia abbreviation service.
   */
  public function __construct(AbbreviationInterface $abbreviation) {
    $this->abbreviation = $abbreviation;
  }

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
   * Register the legacy footer abbreviation parser, renderer, and element.
   *
   * @param \Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\CreateEnvironmentEvent $event
   *   The event object.
   */
  public function onCommonMarkCreateEnvironment(
    CreateEnvironmentEvent $event
  ): void {
    /** @var \League\CommonMark\ConfigurableEnvironmentInterface */
    $environment = $event->getEnvironment();

    // This adds our AbbreviationFooterParser. Note that we need to pass it the
    // abbreviation service so that the parser can send any found abbreviations
    // to it.
    //
    // @see \League\CommonMark\Extension\CommonMarkCoreExtension::register()
    //   Default CommonMark parsers added here.
    $environment->addBlockParser(
      new AbbreviationFooterParser($this->abbreviation)
    );

    // This adds our AbbreviationFooterRenderer as the renderer for our
    // AbbreviationFooter element.
    //
    // @see \League\CommonMark\Extension\CommonMarkCoreExtension::register()
    //   Default CommonMark renderers added here.
    $environment->addBlockRenderer(
      AbbreviationFooter::class, new AbbreviationFooterRenderer()
    );
  }

}
