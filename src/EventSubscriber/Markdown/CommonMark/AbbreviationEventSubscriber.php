<?php

namespace Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\AmbientImpactMarkdownEventInterface;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\CreateEnvironmentEvent;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentParsedEvent;
use Drupal\omnipedia_content\Service\AbbreviationInterface;
use Eightfold\CommonMarkAbbreviations\Abbreviation;
use Eightfold\CommonMarkAbbreviations\AbbreviationExtension;
use League\CommonMark\Inline\Element\Link;
use League\CommonMark\Inline\Element\Text;
use League\CommonMark\Node\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to enable and output CommonMark abbreviations.
 */
class AbbreviationEventSubscriber implements EventSubscriberInterface {

  /**
   * The Omnipedia abbreviation service.
   *
   * @var \Drupal\omnipedia_content\Service\AbbreviationInterface
   */
  protected $abbreviation;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_content\Service\AbbreviationInterface $abbreviation
   *   The Omnipedia abbreviation service.
   */
  public function __construct(AbbreviationInterface $abbreviation) {
    $this->abbreviation = $abbreviation;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      AmbientImpactMarkdownEventInterface::COMMONMARK_CREATE_ENVIRONMENT =>
        'onCommonMarkCreateEnvironment',
      AmbientImpactMarkdownEventInterface::COMMONMARK_DOCUMENT_PARSED =>
        'onCommonMarkDocumentParsed',
    ];
  }

  /**
   * CreateEnvironmentEvent callback.
   *
   * Register the abbreviations extension.
   *
   * @param \Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\CreateEnvironmentEvent $event
   *   The event object.
   */
  public function onCommonMarkCreateEnvironment(
    CreateEnvironmentEvent $event
  ): void {

    /** @var \League\CommonMark\ConfigurableEnvironmentInterface */
    $environment = $event->getEnvironment();

    $environment->addExtension(new AbbreviationExtension());

  }

  /**
   * Alter a provided Text node if it contains one or more abbreviations.
   *
   * This finds CommonMark Text nodes containing available abbreviations, and
   * splits them into Text and Abbreviation elements as needed.
   *
   * @param \League\CommonMark\Inline\Element\Text $textNode
   *   The CommonMark Text node to scan for abbreviations.
   */
  protected function alterTextNode(Text $textNode): void {

    /** @var boolean */
    $isInAbbreviation = false;

    /** @var \League\CommonMark\Node\Node|null */
    $testNode = $textNode;

    // Loop through descendents, stopping when this returns null because we've
    // hit the root element.
    while ($testNode = $testNode->parent()) {
      if (!($testNode instanceof Abbreviation)) {
        continue;
      }

      $isInAbbreviation = true;

      break;
    }

    // Skip this text node if it's already inside of an Abbreviation element.
    if ($isInAbbreviation === true) {
      return;
    }

    /** @var string */
    $content = $textNode->getContent();

    // Get all matches and reverse their order so that we start from the end of
    // the string content, working towards the start.
    $matches = \array_reverse($this->abbreviation->match($content));

    if (empty($matches)) {
      return;
    }

    /** @var \League\CommonMark\Node\Node[] */
    $newNodes = [];

    foreach ($matches as $match) {

      // Attempt to save any text after the abbreviation. Note the use of
      // \mb_strcut() rather than \mb_substr(), as the offsets are in bytes and
      // not characters. If a string contains multi-byte characters, using
      // \mb_substr() would cause unexpected results.
      $trailing = \mb_strcut(
        $content,
        $match['offset'] + \mb_strlen($match['abbreviation'])
      );

      // Save any found text after the abbreviation as a Text node.
      if (\mb_strlen($trailing) > 0) {
        $newNodes[] = new Text($trailing);
      }

      // Now for the abbreviation.
      $newNodes[] = new Abbreviation($match['abbreviation'], [
        'attributes' => ['title' => $match['description']]
      ]);

      // Remove the abbreviation and trailing content from the original text.
      $content = \mb_substr($content, 0,
        \mb_strlen($content) - \mb_strlen($trailing) -
          \mb_strlen($match['abbreviation'])
      );

    }

    // If there's any text content remaining after the above, insert it as a
    // Text node.
    if (\mb_strlen($content) > 0) {
      $newNodes[] = new Text($content);
    }

    // Insert each new node directly after the original node. Since we're
    // working with the new nodes in reverse order, we can just keep inserting
    // directly after the original node and they'll end up in the correct
    // order.
    foreach ($newNodes as $newNode) {
      $textNode->insertAfter($newNode);
    }

    // Finally, remove the original node.
    $textNode->detach();

    // Run the alter over all the abbreviations we just added, as they won't
    // get picked up by the CommonMark node walker loop.
    foreach ($newNodes as $newNode) {

      if (!($newNode instanceof Abbreviation)) {
        continue;
      }

      $this->alterAbbreviationNode($newNode);

    }

  }

  /**
   * Determine if an abbreviation is marked as 'none'.
   *
   * This allows content editors to disable abbreviation matching on a
   * case-by-case basis.
   *
   * @param \Eightfold\CommonMarkAbbreviations\Abbreviation $abbreviation
   *   The Abbreviation node to check.
   *
   * @return boolean
   */
  protected function isAbbreviationNone(
    Abbreviation $abbreviation
  ): bool {
    return \mb_strtolower(
      $abbreviation->getData('attributes', ['title' => ''])['title']
    ) === 'none';
  }

  /**
   * Determine if a provided CommonMark Node is an open parenthesis.
   *
   * @param \League\CommonMark\Node\Node $node
   *   The node object to check.
   *
   * @return boolean
   *   True if the last text content character is '(', false otherwise.
   */
  protected function isOpenParenthesis(Node $node): bool {
    return \method_exists($node, 'getContent') &&
      \mb_substr($node->getContent(), -1) === '(';
  }

  /**
   * Determine if a provided CommonMark Node is a close parenthesis.
   *
   * @param \League\CommonMark\Node\Node $node
   *   The node object to check.
   *
   * @return boolean
   *   True if the first text content character is ')', false otherwise.
   */
  protected function isCloseParenthesis(Node $node): bool {
    return \method_exists($node, 'getContent') &&
      \mb_substr($node->getContent(), 0, 1) === ')';
  }

  /**
   * Find an adjacent Text node given a starting node.
   *
   * This searches for an adjacent Text node using the following logic and
   * returning the first one that matches:
   *
   * 1. Checks if the immediate sibling in $direction is a Text node.
   *
   * 2. Checks if the immediate sibling in $direction has children, and checks
   *    down that tree for a first or last child (depending on $direction) for a
   *    Text node.
   *
   * 3. Repeats 2. for each ancestor's sibling, starting with the parent and
   *    going up the tree one by one.
   *
   * @param \League\CommonMark\Node\Node $startNode
   *   The Node to check relative to.
   *
   * @param string $direction
   *   Must be one of 'next' or 'previous'.
   *
   * @return \League\CommonMark\Inline\Element\Text|null
   *   A Text node if it can be found, or null if one can't be found.
   *
   * @throws \InvalidArgumentException
   *   If $direction is not one of the expected values.
   */
  protected function findAjacentTextNode(
    Node $startNode, string $direction
  ): ?Text {

    if (!\in_array($direction, ['next', 'previous'])) {
      throw new \InvalidArgumentException(
        '$direction must be one of \'next\' or \'previous\'; was: \'' . $direction . '\''
      );
    }

    // The child method that we have to use on a node depends on the $direction
    // that we're told to search in.
    if ($direction === 'next') {
      $childMethod = 'firstChild';
    } else {
      $childMethod = 'lastChild';
    }

    // Check if there's an immediate sibling Text node and return that if found.

    /** @var \League\CommonMark\Node\Node|null */
    $sibling = $startNode->$direction();

    if (\is_object($sibling) && $sibling instanceof Text) {
      return $sibling;
    }

    // If there's no sibling Text node, go up the tree and check each ancestor
    // if it has an adjacent node in the requested direction.

    /** @var \League\CommonMark\Node\Node|null */
    $ancestor = $startNode;

    // Note that this must be a do...while loop so that we also check
    // $startNode's sibling tree.
    do {

      /** @var \League\CommonMark\Node\Node|null */
      $ancestorSibling = $ancestor->$direction();

      // Skip to the next ancestor if this one doesn't have a sibling in the
      // specified direction.
      if (!\is_object($ancestorSibling)) {
        continue;
      }

      // If this ancestor's sibling is a Text node, return it.
      if ($ancestorSibling instanceof Text) {
        return $ancestorSibling;
      }

      // If the ancestor sibling has children, go down that tree to try and find
      // a Text node.

      /** @var \League\CommonMark\Node\Node|null */
      $child = $ancestorSibling;

      while ($child = $child->$childMethod()) {

        if ($child instanceof Text) {
          return $child;
        }

      }

    } while ($ancestor = $ancestor->parent());

    return null;

  }

  /**
   * Determine if an abbreviation is enclosed in parentheses.
   *
   * This allows us to ignore abbreviations if they're the only text inside of
   * parentheses, e.g '(HTML)', as this is almost always immediately after the
   * full description has been spelled out.
   *
   * While a crude version of this could simply check within a text node if the
   * preceding and following characters are open and close parentheses, this
   * would not catch instances where the parentheses are in different but
   * adjacent text node.
   *
   * @param \Eightfold\CommonMarkAbbreviations\Abbreviation $abbreviation
   *   The Abbreviation node to check.
   *
   * @return boolean
   */
  protected function isAbbreviationParenthesized(
    Abbreviation $abbreviation
  ): bool {

    /** @var \League\CommonMark\Inline\Element\Text|null */
    $previousTextNode = $this->findAjacentTextNode($abbreviation, 'previous');

    /** @var \League\CommonMark\Inline\Element\Text|null */
    $nextTextNode = $this->findAjacentTextNode($abbreviation, 'next');

    if (!\is_object($previousTextNode) || !\is_object($nextTextNode)) {
      return false;
    }

    return
      $this->isOpenParenthesis($previousTextNode) &&
      $this->isCloseParenthesis($nextTextNode);

  }

  /**
   * Determine if an abbreviation is within a Link node.
   *
   * We rarely have abbreviations within a link, and if it is within a link,
   * it's likely an attached data Wikimedia link that explains the term. In any
   * other case, it'll be an internal link that likely also explains the term.
   * Additionally, having an abbreviation in a link isn't always visually clear,
   * and could be missed by users.
   *
   * @param \Eightfold\CommonMarkAbbreviations\Abbreviation $abbreviation
   *   The Abbreviation node to check.
   *
   * @return boolean
   *   True if one of $abbreviation's ancestors is a Link node or false
   *   otherwise.
   */
  protected function isAbbreviationInLink(Abbreviation $abbreviation): bool {

    /** @var \League\CommonMark\Node\Node|null */
    $ancestor = $abbreviation;

    while ($ancestor = $ancestor->parent()) {
      if ($ancestor instanceof Link) {
        return true;
      }
    }

    return false;

  }

  /**
   * Alter a provided Abbreviation node.
   *
   * This replaces Abbreviation nodes with Text nodes if they match one of the
   * following:
   *
   * - If the Abbreviation has a description of "none" (case insensitive).
   *
   * - If the Abbreviation is the only text inside of parentheses.
   *
   * - If the Abbreviation is within a link.
   *
   * @param \Eightfold\CommonMarkAbbreviations\Abbreviation $abbreviation
   *   The Abbreviation node to potentially alter.
   *
   * @see $this->isAbbreviationNone()
   *
   * @see $this->isAbbreviationParenthesized()
   *
   * @see $this->isAbbreviationInLink()
   */
  protected function alterAbbreviationNode(
    Abbreviation $abbreviation
  ): void {

    if (
      !$this->isAbbreviationNone($abbreviation) &&
      !$this->isAbbreviationParenthesized($abbreviation) &&
      !$this->isAbbreviationInLink($abbreviation)
    ) {
      return;
    }

    $textNode = new Text($abbreviation->getContent());

    $abbreviation->insertAfter($textNode);

    $abbreviation->detach();

  }

  /**
   * DocumentParsedEvent callback.
   *
   * @param \Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentParsedEvent $event
   *   The event object.
   */
  public function onCommonMarkDocumentParsed(DocumentParsedEvent $event): void {

    /** @var \League\CommonMark\Block\Element\Document */
    $document = $event->getDocument();

    /** @var \League\CommonMark\Node\NodeWalker */
    $walker = $document->walker();

    while ($event = $walker->next()) {

      /** @var \League\CommonMark\Node\Node */
      $node = $event->getNode();

      if ($node instanceof Text && $event->isEntering()) {
        $this->alterTextNode($node);
      }

    }

    // @todo Determine if the below can be merged into the above loop for
    //   potentially better performance. Currently, doing so results in the
    //   removal of all non-inline abbreviations, i.e. those that would be
    //   matched via $this->alterTextNode().

    /** @var \League\CommonMark\Node\NodeWalker */
    $walker = $document->walker();

    while ($event = $walker->next()) {

      /** @var \League\CommonMark\Node\Node */
      $node = $event->getNode();

      if ($node instanceof Abbreviation && $event->isEntering()) {
        $this->alterAbbreviationNode($node);
      }

    }

  }

}
