<?php

namespace Drupal\omnipedia_content\Plugin\Omnipedia\Element;

use Drupal\omnipedia_content\OmnipediaElementBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Infobox element.
 *
 * @OmnipediaElement(
 *   id = "infobox",
 *   html_element = "infobox",
 *   title = @Translation("Infobox"),
 *   description = @Translation("Loosely based on the <a href='https://en.wikipedia.org/wiki/Template:Infobox'>Wikipedia Template:Infobox</a>.")
 * )
 */
class Infobox extends OmnipediaElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getTheme(): array {
    return [
      'omnipedia_infobox' => [
        'variables' => [
          'type'  => 'undefined',
          'name'  => 'Undefined',
          'items' => [],
        ],
        'template'  => 'omnipedia-infobox',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray(): array {
    /** @var string|null */
    $type = $this->elements->attr('type');

    if ($type === null) {
      $this->setError($this->t(
        'Cannot find the <code>type</code> attribute.'
      ));

      $type = self::getTheme()['omnipedia_infobox']['variables']['type'];
    }

    /** @var string|null */
    $name = $this->elements->attr('name');

    if ($name === null) {
      $this->setError($this->t(
        'Cannot find the <code>name</code> attribute.'
      ));

      $name = self::getTheme()['omnipedia_infobox']['variables']['name'];
    }

    /** @var array */
    $items = [];

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $itemElements = $this->elements->filter('item');

    if (\count($itemElements) === 0) {
      $this->setError($this->t(
        'Cannot find any <code>&lt;item&gt;</code> elements.'
      ));
    }

    foreach ($itemElements as $itemElement) {
      $item = [];

      $item['label'] = $itemElement->getAttribute('label');

      /** @var \Symfony\Component\DomCrawler\Crawler */
      $itemCrawler = new Crawler($itemElement);

      $item['value'] = ['#markup' => $itemCrawler->html()];

      // Temporary hard coding until the <media> element is implemented.
      if ($item['label'] === 'Media' || $item['label'] === 'Caption') {
        $item['isMedia'] = true;
      } else {
        $item['isMedia'] = false;
      }

      $items[] = $item;
    }

    return [
      '#theme'  => 'omnipedia_infobox',
      '#type'   => $type,
      '#name'   => $name,
      '#items'  => $items,

      '#attached' => [
        'library'   => ['omnipedia_content/component.infobox'],
      ],
    ];
  }

}
