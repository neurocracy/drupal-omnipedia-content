<?php

namespace Drupal\omnipedia_content\Plugin\Omnipedia\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Template\Attribute;
use Drupal\omnipedia_content\PluginManager\OmnipediaElementManagerInterface;
use Drupal\omnipedia_content\Plugin\Omnipedia\Element\OmnipediaElementBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Media element.
 *
 * This essentially acts as a converter from a more human friendly format - a
 * <media> element that looks up the provided media entity name - which then
 * renders the found media entity.
 *
 * @OmnipediaElement(
 *   id           = "media",
 *   html_element = "media",
 *   title        = @Translation("Media"),
 *   description  = @Translation("Media element.")
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
          'style'       => 'framed',
          'view_mode'   => 'omnipedia_embedded',
        ],
        'template'  => 'omnipedia-media',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Rather than using the media entity list cache tag on the error render
   *   array, can we instead set a custom cache tag on it containing the name of
   *   the non-existent media and have that specific tag invalidated when media
   *   is edited/added and begins to match that specific media name?
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

      /** @var array */
      $errorRenderArray = [
        '#theme'    => 'media_embed_error',
        '#message'  => $error,
      ];

      // Get the media entity type definition so that we can get the list cache
      // tag for the error message, so that the error message is invalidated
      // when any media is edited/added, in case media will match this. Getting
      // the cache tag this way is is considered a best practice over hard
      // coding it.
      /** @var \Drupal\Core\Entity\EntityTypeInterface|null */
      $mediaEntityType = $this->entityTypeManager->getDefinition('media');

      if ($mediaEntityType instanceof EntityTypeInterface) {
        $errorRenderArray['#cache']['tags'] =
          $mediaEntityType->getListCacheTags();
      }

      return $errorRenderArray;
    }

    // Grab the first media entity in the array.
    //
    // @todo What if there's more than one?
    /** @var \Drupal\media\MediaInterface */
    $mediaEntity = \reset($foundMedia);

    /** @var \Drupal\Core\Template\Attribute */
    $containerAttributes = new Attribute();

    /** @var string|null */
    $align = $this->elements->attr('align');

    // @todo Remove this when we have default options/attributes implemented.
    if ($align === null) {
      $align = self::getTheme()['omnipedia_media']['variables']['align'];
    }

    /** @var string|null */
    $style = $this->elements->attr('style');

    // @todo Remove this when we have default options/attributes implemented.
    if ($style === null) {
      $style = self::getTheme()['omnipedia_media']['variables']['style'];
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
      /** @var \Drupal\Core\Template\Attribute */
      $mediaRenderArray['#attributes'] = new Attribute();
    }

    /** @var string|null */
    $caption = $this->elements->attr('caption');

    if ($caption !== null) {
      $mediaRenderArray['#attributes']->setAttribute('data-caption', $caption);

      // This creates an element containing the caption, with two new lines
      // before and after so that Markdown is parsed and rendered. This is used
      // by MarkdownAlterationsFilter to set the PhotoSwipe caption.
      //
      // @see \Drupal\omnipedia_content\Plugin\Filter\MarkdownAlterationsFilter::alterCaptions()
      $mediaRenderArray['caption'] = [
        '#type'       => 'html_tag',
        '#tag'        => 'div',
        '#attributes' => [
          'class'   => ['omnipedia-media-caption-rendered'],
          'hidden'  => true,
        ],
        'caption_content'   => [
          '#markup'   => "\n\n" . $caption . "\n\n",
        ],
      ];

      // Save a hash of the caption to the media entity render array's cache
      // keys so that Drupal knows to cache multiple instances of this media
      // entity with different captions separately. Without this, the first time
      // this media entity render array would be rendered in this view mode,
      // it'll be cached and used for all subsequent instances, regardless of
      // the caption contents.
      $mediaRenderArray['#cache']['keys'][] =
        'caption-hash:' . Crypt::hashBase64($caption);
    }

    $mediaRenderArray['#embed'] = true;

    return [
      '#theme'      => 'omnipedia_media',

      '#media'      => $mediaRenderArray,
      '#attributes' => $containerAttributes,
      '#align'      => $align,
      '#style'      => $style,
      '#view_mode'  => $viewMode,

      '#attached'   => [
        'library'     => ['omnipedia_content/component.media'],
      ],
    ];
  }

}
