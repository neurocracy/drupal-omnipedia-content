<?php

namespace Drupal\omnipedia_content_changes\EventSubscriber\Omnipedia\Changes;

use Drupal\ambientimpact_core\Utility\Html;
use Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent;
use Drupal\omnipedia_content_changes\Event\OmnipediaContentChangesEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to clean up any unaltered wiki node changes diff content.
 */
class DiffCleanUpEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      OmnipediaContentChangesEventInterface::DIFF_POST_BUILD => 'onDiffPostBuild',
    ];
  }

  /**
   * Removes any remaining unaltered or invalid elements from the DOM.
   *
   * @param \Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent $event
   *   The event object.
   */
  protected function removeRemainingElements(DiffPostBuildEvent $event): void {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $crawler = $event->getCrawler();

    foreach ($crawler->filter(\implode(',', [
      'ins.mod',
      'del.mod',
      // These cause layout issues and are likely invalid nesting.
      'picture ins',
      'picture del',
    ])) as $element) {

      // This essentially unwraps the element, moving all child elements just
      // before it in the order they appear.
      //
      // @see https://stackoverflow.com/questions/11651365/how-to-insert-node-in-hierarchy-of-dom-between-one-node-and-its-child-nodes/11651813#11651813
      for ($i = 0; $element->childNodes->length > 0; $i++) {
        $element->parentNode->insertBefore(
          // Note that we always specify index "0" as we're basically removing
          // the first child each time, similar to \array_shift(), and the child
          // list updates each time we do this, akin to removing the bottom most
          // card in a deck of cards on each iteration.
          $element->childNodes->item(0),
          $element
        );
      }

      // Remove the now-empty element.
      $element->parentNode->removeChild($element);

    }

  }

  /**
   * Remove any unaltered element classes from the DOM.
   *
   * @param \Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent $event
   *   The event object.
   */
  protected function removeRemainingClasses(DiffPostBuildEvent $event): void {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $crawler = $event->getCrawler();

    foreach ($crawler->filter('.diffmod') as $element) {

      Html::setElementClassAttribute(
        $element,
        Html::getElementClassAttribute($element)->removeClass('diffmod')
      );

    }

  }

  /**
   * DiffPostBuildEvent handler.
   *
   * @param \Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent $event
   *   The event object.
   */
  public function onDiffPostBuild(DiffPostBuildEvent $event): void {

    $this->removeRemainingElements($event);

    $this->removeRemainingClasses($event);

  }

}
