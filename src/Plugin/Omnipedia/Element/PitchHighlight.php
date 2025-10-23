<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Plugin\Omnipedia\Element;

use Drupal\omnipedia_content\Plugin\Omnipedia\Element\OmnipediaElementBase;

/**
 * Pitch highlight element.
 *
 * @OmnipediaElement(
 *   id           = "pitch_highlight",
 *   html_element = "pitch-highlight",
 *   title        = @Translation("Pitch highlight"),
 *   description  = @Translation("Pitch highlight element.")
 * )
 */
class PitchHighlight extends OmnipediaElementBase {

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
      '#attributes' => ['class' => ['omnipedia-pitch-highlight']],
      '#value'  => $this->elements->html(),
    ];

  }

}
