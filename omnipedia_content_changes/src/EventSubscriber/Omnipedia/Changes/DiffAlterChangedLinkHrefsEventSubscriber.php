<?php

namespace Drupal\omnipedia_content_changes\EventSubscriber\Omnipedia\Changes;

use Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent;
use Drupal\omnipedia_content_changes\Event\OmnipediaContentChangesEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to alter link hrefs in the wiki node changes diff output.
 *
 * Note that this is left in the code in case we later need to conditionally
 * remove href highlghting, but is no longer used as we disable the special
 * handling for <a> elements to reduce the amount of DOM alteration we have to
 * do.
 */
class DiffAlterChangedLinkHrefsEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      OmnipediaContentChangesEventInterface::DIFF_POST_BUILD => 'onDiffPostBuild',
    ];
  }

  /**
   * Alter any links with changed href attributes found in the provided DOM.
   *
   * Any links that are marked as changed due to having different href
   * attributes have the old revision removed and the current revision not
   * marked by removing the <ins> element and placing the link back in its
   * place. This is done because there's no benefit from highlighting the
   * change, as this is expected and would just add noise.
   *
   * @param \Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent $event
   *   The event object.
   *
   * @todo Check if links whose href attributes changed are both internal wiki
   *   node paths before removing the changed status?
   */
  protected function alterChangedLinkHrefs(DiffPostBuildEvent $event): void {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $crawler = $event->getCrawler();

    foreach (
      $crawler->filter('del.diffa.diffhref + ins.diffa.diffhref') as $insElement
    ) {
      // Remove the preceding <del> element containing the previous date's wiki
      // page link.
      $insElement->parentNode->removeChild($insElement->previousSibling);

      // This essentially unwraps the <ins> element, moving all child elements
      // just before it in the order they appear. This ensures that if there are
      // any elements or nodes other than the expected <a>, they're preserved.
      //
      // @see https://stackoverflow.com/questions/11651365/how-to-insert-node-in-hierarchy-of-dom-between-one-node-and-its-child-nodes/11651813#11651813
      for ($i = 0; $insElement->childNodes->length > 0; $i++) {
        $insElement->parentNode->insertBefore(
          // Note that we always specify index "0" as we're basically removing
          // the first child each time, similar to \array_shift(), and the child
          // list updates each time we do this, akin to removing the bottom most
          // card in a deck of cards on each iteration.
          $insElement->childNodes->item(0),
          $insElement
        );
      }

      // Remove the now-empty <ins>.
      $insElement->parentNode->removeChild($insElement);
    }

  }

  /**
   * DiffPostBuildEvent handler.
   *
   * @param \Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent $event
   *   The event object.
   */
  public function onDiffPostBuild(DiffPostBuildEvent $event): void {

    $this->alterChangedLinkHrefs($event);

  }

}

