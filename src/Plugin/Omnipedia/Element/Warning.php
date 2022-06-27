<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Plugin\Omnipedia\Element;

use Drupal\omnipedia_content\Plugin\Omnipedia\Element\OmnipediaElementBase;

/**
 * Warning element.
 *
 * @OmnipediaElement(
 *   id           = "warning",
 *   html_element = "warning",
 *   title        = @Translation("Warning"),
 *   description  = @Translation("Warning message element.")
 * )
 */
class Warning extends OmnipediaElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getTheme(): array {
    // We use the Drupal core status message template, so return an empty array
    // as implementing this method is required.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray(): array {

    return [

      '#theme'            => 'status_messages',
      '#message_list'     => ['warning' => [
        // Newlines required for Markdown to be parsed.
        ['#markup' => "\n" . $this->elements->html() . "\n"],
      ]],
      '#status_headings'  => [
        'warning' => $this->t('Warning message'),
      ],

    ];

  }

}
