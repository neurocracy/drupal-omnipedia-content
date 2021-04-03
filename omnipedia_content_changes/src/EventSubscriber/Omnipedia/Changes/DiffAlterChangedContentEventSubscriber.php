<?php

namespace Drupal\omnipedia_content_changes\EventSubscriber\Omnipedia\Changes;

use Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent;
use Drupal\omnipedia_content_changes\Event\OmnipediaContentChangesEventInterface;
use Drupal\omnipedia_content_changes\WikiNodeChangesCssClassesInterface;
use Drupal\omnipedia_content_changes\WikiNodeChangesCssClassesTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to alter any changed wiki node changes diff content.
 */
class DiffAlterChangedContentEventSubscriber implements EventSubscriberInterface, WikiNodeChangesCssClassesInterface {

  use WikiNodeChangesCssClassesTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      OmnipediaContentChangesEventInterface::DIFF_POST_BUILD => 'onDiffPostBuild',
    ];
  }

  /**
   * Alter any changed content found in the provided DOM.
   *
   * The following alterations are made on <del> and <ins> elements found via
   * the 'del.diffmod + ins.diffmod' selector:
   *
   * - Both the <del> and <ins> elements are wrapped in a changes container
   *   <span> for styling.
   *
   * - The 'diffmod' class is removed from both the <del> and <ins> elements and
   *   our own BEM classes are added.
   *
   * @param \Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent $event
   *   The event object.
   */
  public function onDiffPostBuild(DiffPostBuildEvent $event): void {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $crawler = $event->getCrawler();

    foreach ($crawler->filter('del.diffmod + ins.diffmod') as $insElement) {
      /** @var \DOMElement|false */
      $changedContainer = $insElement->ownerDocument->createElement('span');

      if (!$changedContainer) {
        continue;
      }

      $changedContainer->setAttribute('class', \implode(' ', [
        $this->getDiffElementClass(),
        $this->getDiffChangedModifierClass(),
      ]));

      // The <del> element immediately preceding the <ins>.
      /** @var \DOMElement */
      $delElement = $insElement->previousSibling;

      // Insert the wrapper before the <ins>.
      $insElement->parentNode->insertBefore($changedContainer, $delElement);

      $changedContainer->appendChild($delElement);

      $changedContainer->appendChild($insElement);

      $delElement->setAttribute(
        'class', $this->getDiffChangedRemovedElementClass()
      );

      $insElement->setAttribute(
        'class', $this->getDiffChangedAddedElementClass()
      );
    }

  }

}
