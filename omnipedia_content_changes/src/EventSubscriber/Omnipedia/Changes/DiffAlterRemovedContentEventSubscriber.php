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
   *
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
      '.diff-list > .removed del:not(.' . $changedDelClass . ')',
    ])) as $delElement) {
      $delElement->setAttribute('class', \implode(' ', [
        $this->getDiffElementClass(),
        $this->getDiffRemovedModifierClass(),
      ]));
    }

  }

}
