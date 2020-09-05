<?php

namespace Drupal\omnipedia_content\Plugin\Omnipedia\Element;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Template\Attribute;
use Drupal\omnipedia_content\OmnipediaElementBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Media element.
 *
 * This essentially acts as a converter from a more human friendly format - a
 * <media> element that looks up the provided media entity name - which then
 * creates a <drupal-media> element pointing to the matched media entity's UUID.
 *
 * @OmnipediaElement(
 *   id = "media",
 *   html_element = "media",
 *   title = @Translation("Media"),
 *   description = @Translation("Media element.")
 * )
 */
class Media extends OmnipediaElementBase {

  /**
   * The Drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    TranslationInterface        $stringTranslation,
    EntityTypeManagerInterface  $entityTypeManager
  ) {
    parent::__construct(
      $configuration, $pluginID, $pluginDefinition, $stringTranslation
    );

    // Save dependencies.
    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('string_translation'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getTheme(): array {
    return [
      'omnipedia_media' => [
        'variables' => [
          'container_attributes'  => null,
          'media_attributes'      => null,
          'align'                 => 'right',
        ],
        'template'  => 'omnipedia-media',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray(): array {
    /** @var string|null */
    $name = $this->elements->attr('name');

    if ($name === null) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup */
      $error = $this->t('Cannot find the <code>name</code> attribute.');

      $this->setError($error);

      return [
        '#theme'    => 'media_embed_error',
        '#message'  => $error,
      ];
    }

    $name = \trim($name);

    /** @var \Drupal\Core\Entity\EntityStorageInterface */
    $mediaStorage = $this->entityTypeManager->getStorage('media');

    // Try to find any media with this name.
    /** @var \Drupal\Core\Entity\EntityInterface[] */
    $foundMedia = $mediaStorage->loadByProperties(['name' => $name]);

    if (count($foundMedia) === 0) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup */
      $error = $this->t(
        'Cannot find any media with the name "@name".',
        ['@name' => $name]
      );

      $this->setError($error);

      return [
        '#theme'    => 'media_embed_error',
        '#message'  => $error,
      ];
    }

    // Grab the first media entity in the array.
    //
    // @todo What if there's more than one?
    /** @var \Drupal\media\MediaInterface */
    $mediaEntity = \reset($foundMedia);

    /** @var Drupal\Core\Template\Attribute */
    $containerAttributes = new Attribute();

    /** @var Drupal\Core\Template\Attribute */
    $mediaAttributes = new Attribute([
      'data-entity-type'  => 'media',
      'data-entity-uuid'  => $mediaEntity->uuid(),
    ]);

    // Copy over attributes for the core MediaEmbed and FilterCaption filters.
    foreach ([
      'size'    => 'view-mode',
      'caption' => 'caption',
    ] as $ourAttribute => $drupalAttribute) {
      /** @var string|null */
      $foundValue = $this->elements->attr($ourAttribute);

      if ($foundValue === null) {
        continue;
      }

      $mediaAttributes->setAttribute('data-' . $drupalAttribute, $foundValue);
    }

    /** @var string|null */
    $align = $this->elements->attr('align');

    // @todo Remove this when we have default options/attributes implemented.
    if ($align === null) {
      $align = self::getTheme()['omnipedia_media']['variables']['align'];
    }

    // @todo Remove this when we have default options/attributes implemented.
    if (!$mediaAttributes->hasAttribute('data-view-mode')) {
      $mediaAttributes->setAttribute('data-view-mode', 'omnipedia_embedded');
    }

    return [
      '#theme' => 'omnipedia_media',

      '#container_attributes' => $containerAttributes,
      '#media_attributes'     => $mediaAttributes,

      '#align'  => $align,

      '#attached' => [
        'library'   => ['omnipedia_content/component.media'],
      ],
    ];
  }

}
