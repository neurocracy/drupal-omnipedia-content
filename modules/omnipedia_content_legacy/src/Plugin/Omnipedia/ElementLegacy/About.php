<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content_legacy\Plugin\Omnipedia\ElementLegacy;

use Drupal\omnipedia_content_legacy\Plugin\Omnipedia\ElementLegacy\OmnipediaElementLegacyBase;

/**
 * About legacy element.
 *
 * @OmnipediaElementLegacy(
 *   id     = "about",
 *   title  = @Translation("About")
 * )
 */
class About extends OmnipediaElementLegacyBase {

  /**
   * {@inheritdoc}
   */
  public static function getTheme(): array {
    return [
      'omnipedia_about_legacy' => [
        'variables' => [
          'about'     => '',
          'uses'      => [],
        ],
        'template'  => 'omnipedia-about-legacy',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray(): array {
    /** @var array */
    $uses = [];

    foreach ($this->options as $optionName => $optionValue) {
      // Determine if this is a numbered 'use' attribute, skipping if not.
      \preg_match('%^use(\d+)$%', $optionName, $optionNameMatches);

      if (!isset($optionNameMatches[1])) {
        continue;
      }

      $optionIndex = $optionNameMatches[1];

      if (!isset($this->options['see' . $optionIndex])) {
        continue;
      }

      $uses[] = [
        'use' => $optionValue,
        'see' => $this->options['see' . $optionIndex],
      ];
    }

    return [
      '#theme'  => 'omnipedia_about_legacy',
      // Render any HTML elements nested inside the <about> element.
      '#about'  => ['#markup' => $this->options['about']],
      '#uses'   => $uses,
    ];
  }

}
