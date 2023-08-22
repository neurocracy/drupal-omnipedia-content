<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Hooks;

use Drupal\hux\Attribute\Alter;
use Drupal\omnipedia_content\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use Drupal\omnipedia_content\Plugin\Markdown\CommonMark\Extension\FootnoteExtension;

/**
 * Markdown hook implementations.
 */
class Markdown {

  #[Alter('markdown_extension_info')]
  /**
   * Implements hook_markdown_extension_info_alter().
   *
   * This performs the following:
   *
   * - Replaces the heading permalink CommonMark extension with our own.
   *
   * - Replaces the footnotes Markdown module plug-in with our own.
   *
   * @see \Drupal\omnipedia_content\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension
   *   Our heading permalink CommonMark extension.
   *
   * @see \Drupal\omnipedia_content\Plugin\Markdown\CommonMark\Extension\FootnoteExtension
   *   Our footnotes Markdown plug-in class.
   */
  public function extensionInfoAlter(array &$info): void {

    $info['commonmark-heading-permalink']['object'] =
      HeadingPermalinkExtension::class;

    $info['commonmark-footnotes']['class'] = FootnoteExtension::class;

  }

}
