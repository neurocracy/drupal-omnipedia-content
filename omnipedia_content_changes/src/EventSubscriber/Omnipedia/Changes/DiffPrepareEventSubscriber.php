<?php

namespace Drupal\omnipedia_content_changes\EventSubscriber\Omnipedia\Changes;

use Drupal\ambientimpact_core\Utility\Html;
use Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent;
use Drupal\omnipedia_content_changes\Event\OmnipediaContentChangesEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Event subscriber to prepare wiki node changes diff content for alterations.
 */
class DiffPrepareEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      OmnipediaContentChangesEventInterface::DIFF_POST_BUILD => 'onDiffPostBuild',
    ];
  }

  /**
   * Unwraps any ins.mod or del.mod elements containing <ins> or <del> elements.
   *
   * @param \Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent $event
   *   The event object.
   */
  protected function unwrapNestedDiffElements(DiffPostBuildEvent $event): void {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $crawler = $event->getCrawler();

    foreach ($crawler->filter(\implode(',', [
      'ins.mod',
      'del.mod',
    ])) as $element) {

      if (count(
        (new Crawler($element))->children()->filter('ins, del')
      ) === 0) {
        continue;
      }

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
   * Fixes any invalid nesting of a list inside of an <ins> or <del>.
   *
   * @param \Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent $event
   *   The event object.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/Content_categories#transparent_content_model
   *   <ins> and <del> elements only permit transparent content, which does not
   *   include lists, and so results in browsers interrupting the <ins> and
   *   <del> to render the list and then continuing the <ins> or <del> after the
   *   list.
   */
  protected function fixDiffListNesting(DiffPostBuildEvent $event): void {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $crawler = $event->getCrawler();

    $listXpath = '[(.//ol or .//ul or .//dl)]';

    // Find all <ins> and <del> elements that contain a list.
    foreach ($crawler->evaluate(
      '//ins' . $listXpath . '|//del' . $listXpath
    ) as $element) {

      /** @var \Symfony\Component\DomCrawler\Crawler */
      $textCrawler = (new Crawler($element))->evaluate('.//text()');

      foreach ($textCrawler as $textNode) {

        // Skip text nodes that contain only white-space. This avoids adding
        // unnecessary highlighting and also skips text nodes that are direct
        // children of a list, which shouldn't be wrapped in an <ins> or <del>
        // as it would be invalid nesting.
        if (\mb_strlen(\trim($textNode->textContent)) === 0) {
          continue;
        }

        /** @var \DOMElement|false */
        $cloned = $element->cloneNode();

        // Skip this text node if we couldn't clone the descendent <ins>/<del>,
        // or if the cloned <ins>/<del> could not be inserted after the text
        // node.
        if (
          !\is_object($cloned) ||
          !\is_object(
            $textNode->parentNode->insertBefore($cloned, $textNode)
          )
        ) {
          continue;
        }

        // Finally, append the text node to the cloned <ins>/<del>.
        $cloned->appendChild($textNode);

      }

      // Once the text nodes have been wrapped in clones of $element, unwrap
      // $element.
      Html::unwrapNode($element);

    }

  }

  /**
   * DiffPostBuildEvent handler.
   *
   * @param \Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\DiffPostBuildEvent $event
   *   The event object.
   */
  public function onDiffPostBuild(DiffPostBuildEvent $event): void {

    $this->unwrapNestedDiffElements($event);

    $this->fixDiffListNesting($event);

  }

}
