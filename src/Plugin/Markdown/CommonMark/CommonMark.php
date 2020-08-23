<?php

namespace Drupal\omnipedia_content\Plugin\Markdown\CommonMark;

use Drupal\markdown\Plugin\Markdown\CommonMark\CommonMark as MarkdownCommonMark;
use League\CommonMark\Environment;
use Drupal\omnipedia_content\CommonMark\Block\Element\IndentedContent;
use Drupal\omnipedia_content\CommonMark\Block\Parser\IndentedContentParser;
use Drupal\omnipedia_content\CommonMark\Block\Renderer\IndentedContentRenderer;

/**
 * CommonMark Markdown plug-in extended with Omnipedia functionality.
 *
 * This does not have an annotation so as to not be picked up by Drupal's
 * plug-in system, as we override the Markdown module class.
 *
 * @see \omnipedia_content_markdown_parser_info_alter()
 *   Original Markdown module plug-in class is replaced in this hook.
 *
 * @see \Drupal\markdown\Plugin\Markdown\CommonMark\CommonMark
 *   Original Markdown module plug-in that we're extending.
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
class CommonMark extends MarkdownCommonMark {

  /**
   * Creates an environment.
   *
   * @return \League\CommonMark\ConfigurableEnvironmentInterface
   */
  protected function createEnvironment() {
    /** @var \League\CommonMark\ConfigurableEnvironmentInterface */
    $environment = Environment::createCommonMarkEnvironment();

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

    return $environment;
  }
}
