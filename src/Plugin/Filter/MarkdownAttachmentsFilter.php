<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to add Markdown attachments (libraries and settings).
 *
 * @Filter(
 *   id           = "omnipedia_markdown_attachments",
 *   title        = @Translation("Omnipedia: Markdown libraries and settings"),
 *   description  = @Translation("This attaches libraries to the filter results for our Markdown elements such as abbreviations and references (citations, footnotes). This should be placed <strong>after</strong> the Markdown filter in the processing order."),
 *   type         = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class MarkdownAttachmentsFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langCode) {

    /** @var \Drupal\filter\FilterProcessResult */
    $result = new FilterProcessResult($text);

    foreach ([
      'abbr' => [
        'library' => ['ambientimpact_ux/component.abbr'],
      ],
      '.references' => [
        'library' => ['omnipedia_content/component.reference'],
      ],
    ] as $selector => $attachments) {

      $result->addAttachments($attachments);

    }

    return $result;

  }

}
