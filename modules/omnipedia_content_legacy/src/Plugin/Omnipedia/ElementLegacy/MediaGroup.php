<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content_legacy\Plugin\Omnipedia\ElementLegacy;

use Drupal\Core\Template\Attribute;
use Drupal\omnipedia_content_legacy\Plugin\Omnipedia\ElementLegacy\OmnipediaElementLegacyBase;

/**
 * Media group legacy element.
 *
 * @OmnipediaElementLegacy(
 *   id     = "media_group",
 *   title  = @Translation("Media group")
 * )
 */
class MediaGroup extends OmnipediaElementLegacyBase {

  /**
   * {@inheritdoc}
   */
  public static function getTheme(): array {
    return [
      'omnipedia_media_group_legacy' => [
        'variables' => [
          'attributes'  => null,
          'items'       => [],
        ],
        'template'  => 'omnipedia-media-group-legacy',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray(): array {
    /** @var \Drupal\Core\Template\Attribute */
    $attributes = new Attribute();

    /** @var array[] */
    $items = [];

    foreach ($this->options as $optionName => $optionValue) {
      // Determine if this is a numbered 'media' option, skipping if not.
      \preg_match('%^media(\d+)$%', $optionName, $optionNameMatches);

      if (!isset($optionNameMatches[1])) {
        continue;
      }

      $optionIndex = $optionNameMatches[1];

      /** @var array */
      $mediaOptions = [
        0 => $optionValue,
      ];

      if (isset($this->options['media' . $optionIndex . '_caption'])) {
        $mediaOptions['caption'] = $this->options[
          'media' . $optionIndex . '_caption'
        ];
      }

      /** @var \Drupal\omnipedia_content_legacy\Plugin\Omnipedia\ElementLegacy\OmnipediaElementLegacyInterface */
      $mediaInstance = $this->legacyElementManager->createInstance('media', [
        'content' => $mediaOptions,
      ]);

      /** @var array */
      $items[] = $mediaInstance->getRenderArray();
    }

    if (isset($this->options['caption'])) {
      $attributes->setAttribute('caption', $this->options['caption']);
    }

    return [
      '#theme'      => 'omnipedia_media_group_legacy',
      '#attributes' => $attributes,
      '#items'      => $items,
    ];
  }

}
