<?php

namespace Drupal\omnipedia_content\Plugin\Omnipedia\Element;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Template\Attribute;
use Drupal\omnipedia_content\OmnipediaElementBase;
use Drupal\omnipedia_content\OmnipediaElementManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Media element.
 *
 * This essentially acts as a converter from a more human friendly format - a
 * <media> element that looks up the provided media entity name - which then
 * renders the found media entity.
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
    OmnipediaElementManagerInterface $elementManager,
    TranslationInterface        $stringTranslation,
    EntityTypeManagerInterface  $entityTypeManager
  ) {
    parent::__construct(
      $configuration, $pluginID, $pluginDefinition,
      $elementManager, $stringTranslation
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
      $container->get('plugin.manager.omnipedia_element'),
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
          'media'       => [],
          'attributes'  => null,
          'align'       => 'right',
          'view_mode'   => 'omnipedia_embedded',
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

    /** @var string|null */
    $align = $this->elements->attr('align');

    // @todo Remove this when we have default options/attributes implemented.
    if ($align === null) {
      $align = self::getTheme()['omnipedia_media']['variables']['align'];
    }

    /** @var string|null */
    $viewMode = $this->elements->attr('view-mode');

    // @todo Remove this when we have default options/attributes implemented.
    if ($viewMode === null) {
      $viewMode = self::getTheme()['omnipedia_media']['variables']['view_mode'];
    }

    /** @var array */
    $mediaRenderArray = $this->entityTypeManager
      ->getViewBuilder('media')
      // @todo $langcode?
      ->view($mediaEntity, $viewMode);

    if (!isset($mediaRenderArray['#attributes'])) {
      /** @var Drupal\Core\Template\Attribute */
      $mediaRenderArray['#attributes'] = new Attribute();
    }

    /** @var string|null */
    $caption = $this->elements->attr('caption');

    if ($caption !== null) {
      $mediaRenderArray['#attributes']->setAttribute('data-caption', $caption);
    }

    $mediaRenderArray['#embed'] = true;

    return [
      '#theme'      => 'omnipedia_media',

      '#media'      => $mediaRenderArray,
      '#attributes' => $containerAttributes,
      '#align'      => $align,
      '#view_mode'  => $viewMode,

      '#attached'   => [
        'library'     => ['omnipedia_content/component.media'],
      ],
    ];
  }

}
