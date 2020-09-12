<?php

namespace Drupal\omnipedia_content_legacy\Plugin\Omnipedia\ElementLegacy;

use Drupal\omnipedia_content_legacy\OmnipediaElementLegacyBase;

/**
 * Infobox legacy element.
 *
 * @OmnipediaElementLegacy(
 *   id = "infobox",
 *   title = @Translation("Infobox")
 * )
 */
class Infobox extends OmnipediaElementLegacyBase {

  /**
   * {@inheritdoc}
   */
  public static function getTheme(): array {
    return [
      'omnipedia_infobox_legacy' => [
        'variables' => [
          'type'  => 'undefined',
          'name'  => 'Undefined',
          'items' => [],
        ],
        'template'  => 'omnipedia-infobox-legacy',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray(): array {
    // Values that correspond to $this->options keys which are not to be passed
    // via '#items' in the render array because the new infobox handles them
    // differently or they need special processing.
    /** @var array */
    $ignoreItemOptions = [0, 'name', 'media', 'caption'];

    /** @var array */
    $renderArray = [
      '#theme'  => 'omnipedia_infobox_legacy',
      '#type'   => $this->options[0],
      '#name'   => $this->options['name'],
      '#items'  => [],
    ];

    if (isset($this->options[0])) {
      $renderArray['#type'] = $this->options[0];
    }

    // Create the media element if one is provided in this infobox.
    if (isset($this->options['media'])) {
      $mediaOptions = [
        0 => $this->options['media'],
      ];

      // Transfer the caption if one is found.
      if (isset($this->options['caption'])) {
        $mediaOptions['caption'] = $this->options['caption'];
      }

      /** @var \Drupal\omnipedia_content_legacy\OmnipediaElementLegacyInterface */
      $mediaInstance = $this->legacyElementManager->createInstance('media', [
        'content' => $mediaOptions,
      ]);

      $renderArray['#items'][] = [
        'label' => 'Media',
        /** @var array */
        'value' => $mediaInstance->getRenderArray(),
      ];
    }

    // Copy over all items whose keys are not listed in $ignoreItemOptions.
    foreach (\array_diff_key(
      $this->options, \array_fill_keys($ignoreItemOptions, '')
    ) as $optionName => $optionValue) {
      $renderArray['#items'][] = [
        // @todo Should we use \mb_convert_case() instead?
        'label' => \ucfirst($optionName),
        'value' => $optionValue,
      ];
    }

    return $renderArray;
  }

}
