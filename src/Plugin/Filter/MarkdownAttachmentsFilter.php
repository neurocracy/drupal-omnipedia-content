<?php

namespace Drupal\omnipedia_content\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Provides a filter to add Markdown attachments (libraries and settings).
 *
 * @Filter(
 *   id           = "omnipedia_markdown_attachments",
 *   title        = @Translation("Omnipedia: Markdown libraries and settings"),
 *   description  = @Translation("This attaches libraries and JavaScript settings to the filter results if they contain Markdown elements, such as table of contents and references (citations, footnotes). This should be placed <strong>after</strong> the Markdown filter in the processing order."),
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

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $crawler = new Crawler((string) $text);

    foreach ([
      [
        'selector'    => 'abbr',
        'attachments' => [
          'library' => ['ambientimpact_ux/component.abbr'],
        ],
      ],
      [
        'selector'    => '.references',
        'attachments' => [
          'library' => ['omnipedia_content/component.reference'],
        ],
      ],
    ] as $group) {
      /** @var \Symfony\Component\DomCrawler\Crawler */
      $searchCrawler = $crawler->filter($group['selector']);

      if (count($searchCrawler) < 1) {
        continue;
      }

      $result->addAttachments($group['attachments']);
    }

    return $result;
  }

}
