<?php

namespace Drupal\omnipedia_content\Plugin\Omnipedia\Element;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\omnipedia_content\PluginManager\OmnipediaElementManagerInterface;
use Drupal\omnipedia_content\Plugin\Omnipedia\Element\OmnipediaElementBase;
use Drupal\omnipedia_core\Service\WikiNodeRevisionInterface;
use Drupal\omnipedia_date\Service\TimelineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * About element.
 *
 * @OmnipediaElement(
 *   id           = "about",
 *   html_element = "about",
 *   title        = @Translation("About"),
 *   description  = @Translation("Loosely based on the <a href='https://en.wikipedia.org/wiki/Template:About'>Wikipedia Template:About</a>.")
 * )
 */
class About extends OmnipediaElementBase {

  /**
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_date\Service\TimelineInterface
   */
  protected $timeline;

  /**
   * The Omnipedia wiki node revision service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeRevisionInterface
   */
  protected $wikiNodeRevision;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\omnipedia_date\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeRevisionInterface $wikiNodeRevision
   *   The Omnipedia wiki node revision service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    OmnipediaElementManagerInterface $elementManager,
    TranslationInterface      $stringTranslation,
    TimelineInterface         $timeline,
    WikiNodeRevisionInterface $wikiNodeRevision
  ) {
    parent::__construct(
      $configuration, $pluginID, $pluginDefinition,
      $elementManager, $stringTranslation
    );

    // Save dependencies.
    $this->timeline         = $timeline;
    $this->wikiNodeRevision = $wikiNodeRevision;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginID, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginID, $pluginDefinition,
      $container->get('plugin.manager.omnipedia_element'),
      $container->get('string_translation'),
      $container->get('omnipedia.timeline'),
      $container->get('omnipedia.wiki_node_revision')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getTheme(): array {
    return [
      'omnipedia_about' => [
        'variables' => [
          'about'     => '',
          'uses'      => [],
        ],
        'template'  => 'omnipedia-about',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray(): array {
    /** @var array */
    $uses = [];

    /** @var \DOMElement|null */
    $element = $this->elements->getNode(0);

    /** @var \DOMNamedNodeMap */
    $attributes = $element->attributes;

    foreach ($attributes as $attribute) {
      // Determine if this is a numbered 'use' attribute, skipping if not.
      \preg_match('%use(\d+)%', $attribute->name, $optionNameMatches);

      if (!isset($optionNameMatches[1])) {
        continue;
      }

      $optionIndex = $optionNameMatches[1];

      // Set an error if this numbered 'use' attribute doesn't have a
      // corresponding 'see' attribute.
      if (!$element->hasAttribute('see' . $optionIndex)) {
        $this->setError($this->t(
          'You need to include a <code>@see</code> option along with the <code>@use</code> option.',
          [
            '@see'  => 'see' . $optionIndex,
            '@use'  => 'use' . $optionIndex,
          ]
        ));

        continue;
      }

      $seeNodeTitle = $element->getAttribute('see' . $optionIndex);

      /** @var \Drupal\omnipedia_core\Entity\NodeInterface|null */
      $seeNode = $this->wikiNodeRevision->getWikiNodeRevision(
        $seeNodeTitle,
        $this->timeline->getDateFormatted('current', 'storage')
      );

      if ($seeNode === null) {
        $this->setError($this->t(
          '<code>@attribute</code> attribute: Cannot find the specified wiki page titled "@title" in the current date.',
          [
            '@attribute'  => 'see' . $optionIndex,
            '@title'      => $seeNodeTitle,
          ]
        ));

        continue;
      }

      // If both matching 'use' and 'see' attributes are found, save them to the
      // array to pass to the template.
      $uses[] = [
        'use' => $attribute->value,
        'see' => [
          '#type'   => 'link',
          '#title'  => $seeNodeTitle,
          '#url'    => $seeNode->toUrl(),
        ],
      ];
    }

    return [
      '#theme'  => 'omnipedia_about',
      // Render any HTML elements nested inside the <about> element.
      '#about'  => ['#markup' => $this->elements->html()],
      '#uses'   => $uses,

      '#attached' => [
        'library'   => ['omnipedia_content/component.about'],
      ],
    ];
  }

}
