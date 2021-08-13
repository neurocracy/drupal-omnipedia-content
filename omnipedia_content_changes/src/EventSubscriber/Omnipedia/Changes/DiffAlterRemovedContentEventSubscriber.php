<?php

namespace Drupal\omnipedia_content_changes\EventSubscriber\Omnipedia\Changes;

use Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent;
use Drupal\omnipedia_content_changes\Event\OmnipediaContentChangesEventInterface;
use Drupal\omnipedia_content_changes\WikiNodeChangesCssClassesInterface;
use Drupal\omnipedia_content_changes\WikiNodeChangesCssClassesTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to alter any removed wiki node changes diff content.
 */
class DiffAlterRemovedContentEventSubscriber implements EventSubscriberInterface, WikiNodeChangesCssClassesInterface {

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
   * Alter any added content found in the provided DOM.
   *
   * The following alterations are made:
   *
   * - The default classes are removed from <del> elements and our own BEM
   *   classes are added. This handles diffed list items as well as standalone
   *   <del> elements.
   *
   * @param \Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent $event
   *   The event object.
   */
  public function onDiffPostBuild(DiffPostBuildEvent $event): void {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $crawler = $event->getCrawler();

    /** @var string */
    $changedDelClass = $this->getDiffChangedRemovedElementClass();

    foreach ($crawler->filter(\implode(',', [
      'del.diffdel',
      // This catches any changed <del> that aren't handled by the changed
      // event subscriber.
      'del.diffmod',
      '.diff-list > .removed del:not(.' . $changedDelClass . ')',
    ])) as $delElement) {
      $delElement->setAttribute('class', \implode(' ', [
        $this->getDiffElementClass(),
        $this->getDiffRemovedModifierClass(),
      ]));
    }

  }

}
