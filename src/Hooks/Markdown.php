<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Hooks;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\hux\Attribute\Alter;
use Drupal\omnipedia_content\Plugin\Markdown\CommonMark\Extension\FootnoteExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension as CommonMarkFootnoteExtension;

/**
 * Markdown hook implementations.
 */
class Markdown {

  #[Alter('markdown_extension_info')]
  #[Alter('markdown_allowed_html_info')]
  /**
   * Implements hook_markdown_extension_info_alter().
   *
   * This performs the following:
   *
   * - Replaces the footnotes Markdown module plug-in with our own.
   *
   * @see \Drupal\omnipedia_content\Plugin\Markdown\CommonMark\Extension\FootnoteExtension
   *   Our footnotes Markdown plug-in class.
   */
  public function extensionInfoAlter(array &$info): void {

    $info['commonmark-footnotes']['class'] = FootnoteExtension::class;
    $info['commonmark-footnotes']['object'] = CommonMarkFootnoteExtension::class;

    // When we replace this extension with our own, this key ends up being null
    // at the time that
    // Drupal\markdown\PluginManager\AllowedHtmlManager::getSortedDefinitions()
    // is called, presumably only getting populated after that; this results
    // in a deprecation warning because it passes this null value to
    // strnatcasecmp(); we work around this by setting it explicitly here if
    // it's missing.
    if (is_null($info['commonmark-footnotes']['label'])) {

      $info['commonmark-footnotes']['label'] = new TranslatableMarkup(
        'Footnotes',
      );

    }

  }

}
