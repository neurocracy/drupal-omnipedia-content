<?php

namespace Drupal\omnipedia_content_changes\EventSubscriber\Omnipedia\Changes;

use Drupal\Core\Template\Attribute;
use Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent;
use Drupal\omnipedia_content_changes\Event\OmnipediaContentChangesEventInterface;
use Drupal\omnipedia_content_changes\WikiNodeChangesCssClassesInterface;
use Drupal\omnipedia_content_changes\WikiNodeChangesCssClassesTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to alter any links in the wiki node changes diff content.
 */
class DiffAlterLinksEventSubscriber implements EventSubscriberInterface, WikiNodeChangesCssClassesInterface {

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
   * Alter any links found in the provided DOM.
   *
   * This removes the .diffmod class from links and adds our own BEM classes.
   *
   * @param \Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent $event
   *   The event object.
   */
  public function onDiffPostBuild(DiffPostBuildEvent $event): void {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $crawler = $event->getCrawler();

    foreach ($crawler->filter('a.diffmod') as $linkElement) {

      // Parse any existing class attribute and create a new Attributes object
      // to make class manipulation easier.
      /** @var \Drupal\Core\Template\Attribute */
      $attributes = new Attribute([
        'class' => \preg_split(
          '/\s+/' , \trim($linkElement->getAttribute('class'))
        ),
      ]);

      $attributes->removeClass('diffmod');

      $attributes->addClass($this->getDiffLinkElementClass());
      $attributes->addClass($this->getDiffLinkChangedModifierClass());

      $linkElement->setAttribute(
        'class', \implode(' ', $attributes->getClass()->value())
      );
    }

  }

}
