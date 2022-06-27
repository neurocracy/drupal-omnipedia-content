<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content_legacy\Plugin\Omnipedia\ElementLegacy;

use Drupal\Core\Template\Attribute;
use Drupal\omnipedia_content_legacy\Plugin\Omnipedia\ElementLegacy\OmnipediaElementLegacyBase;

/**
 * Media legacy element.
 *
 * @OmnipediaElementLegacy(
 *   id     = "media",
 *   title  = @Translation("Media")
 * )
 */
class Media extends OmnipediaElementLegacyBase {

  /**
   * {@inheritdoc}
   */
  public static function getTheme(): array {
    return [
      'omnipedia_media_legacy' => [
        'variables' => [
          'attributes'  => null,
        ],
        'template'  => 'omnipedia-media-legacy',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray(): array {
    /** @var \Drupal\Core\Template\Attribute */
    $attributes = new Attribute();

    if (isset($this->options[0])) {
      // Split by the first found slash.
      $nameArray = \explode('/', $this->options[0], 2);

      // Use the name following the 'file/', since we no longer use the 'file/'
      // path in Drupal 8.
      if ($nameArray[0] === 'file') {
        $attributes->setAttribute('name', $nameArray[1]);
      }
    }

    // Copy over all non-numeric options.
    foreach ($this->options as $optionName => $optionValue) {
      if (\is_numeric($optionName)) {
        continue;
      }

      $attributes->setAttribute($optionName, $optionValue);
    }

    return [
      '#theme'      => 'omnipedia_media_legacy',
      '#attributes' => $attributes,
    ];
  }

}
