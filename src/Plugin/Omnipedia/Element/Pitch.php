<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Plugin\Omnipedia\Element;

use Drupal\omnipedia_content\Plugin\Omnipedia\Element\OmnipediaElementBase;

/**
 * Pitch element.
 *
 * @OmnipediaElement(
 *   id           = "pitch",
 *   html_element = "pitch",
 *   title        = @Translation("Pitch"),
 *   description  = @Translation("Pitch element.")
 * )
 */
class Pitch extends OmnipediaElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getTheme(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray(): array {

    return [
      '#type' => 'html_tag',
      '#tag'  => 'span',
      '#attributes' => ['class' => ['omnipedia-pitch']],
      '#attached' => [
        'library'   => ['omnipedia_content/component.pitch'],
      ],
    ];

  }

}
