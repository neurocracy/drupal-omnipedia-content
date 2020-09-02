<?php

namespace Drupal\omnipedia_content_legacy\Plugin\Omnipedia\ElementLegacy;

use Drupal\omnipedia_content_legacy\OmnipediaElementLegacyBase;

/**
 * Infobox legacy element.
 *
 * @OmnipediaElementLegacy(
 *   id = "infobox",
 *   theme = "infobox_legacy",
 *   title = @Translation("Infobox")
 * )
 */
class Infobox extends OmnipediaElementLegacyBase {

  /**
   * {@inheritdoc}
   */
  public static function getTheme(): array {
    return [
      'variables' => [
        'type'  => 'undefined',
        'name'  => 'Undefined',
        'items' => [],
      ],
      'template'  => 'infobox-legacy',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray(): array {
    // Values that correspond to $this->options keys which are not to be passed
    // via '#items' in the render array, because the new infobox handles them
    // differently.
    /** @var array */
    $ignoreItemOptions = [0, 'name'];

    /** @var array */
    $renderArray = [
      '#theme'  => 'infobox_legacy',
      '#type'   => $this->options[0],
      '#name'   => $this->options['name'],
      '#items'  => [],
    ];

    if (isset($this->options[0])) {
      $renderArray['#type'] = $this->options[0];
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
