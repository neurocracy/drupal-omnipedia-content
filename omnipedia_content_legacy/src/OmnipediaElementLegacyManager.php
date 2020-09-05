<?php

namespace Drupal\omnipedia_content_legacy;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Render\RendererInterface;
use Drupal\omnipedia_content_legacy\Annotation\OmnipediaElementLegacy as OmnipediaElementLegacyAnnotation;
use Drupal\omnipedia_content_legacy\OmnipediaElementLegacyInterface;
use Drupal\omnipedia_content_legacy\OmnipediaElementLegacyManagerInterface;
use Mustache_Engine;
use Mustache_LambdaHelper;
use Mustache_Logger_StreamLogger;

/**
 * The OmnipediaElementLegacy plug-in manager.
 */
class OmnipediaElementLegacyManager extends DefaultPluginManager implements OmnipediaElementLegacyManagerInterface {

  /**
   * The Drupal renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Creates the discovery object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plug-in
   *   implementations.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler to invoke the alter hook with.
   *
   * @see \Drupal\plugin_type_example\SandwichPluginManager
   *   This method is based heavily on the sandwich manager from the
   *   'examples' module.
   */
  public function __construct(
    \Traversable            $namespaces,
    CacheBackendInterface   $cacheBackend,
    ModuleHandlerInterface  $moduleHandler
  ) {
    parent::__construct(
      // This tells the plug-in manager to look for OmnipediaElement plug-ins in
      // the 'src/Plugin/Omnipedia/ElementLegacy' subdirectory of any enabled
      // modules. This also serves to define the PSR-4 subnamespace in which
      // OmnipediaElementLegacy plug-ins will live.
      'Plugin/Omnipedia/ElementLegacy',

      $namespaces,

      $moduleHandler,

      // The name of the interface that plug-ins should adhere to. Drupal will
      // enforce this as a requirement. If a plug-in does not implement this
      // interface, Drupal will throw an error.
      OmnipediaElementLegacyInterface::class,

      // The name of the annotation class that contains the plug-in definition.
      OmnipediaElementLegacyAnnotation::class
    );

    // This allows the plug-in definitions to be altered by an alter hook. The
    // parameter defines the name of the hook:
    //
    // hook_omnipedia_element_legacy_info_alter()
    $this->alterInfo('omnipedia_element_legacy_info');

    // This sets the caching method for our plug-in definitions. Plug-in
    // definitions are discovered by examining the directory defined above, for
    // any classes with a OmnipediaElementLegacyAnnotation::class. The
    // annotations are read, and then the resulting data is cached using the
    // provided cache backend.
    $this->setCacheBackend($cacheBackend, 'omnipedia_element_legacy_info');
  }

  /**
   * {@inheritdoc}
   */
  public function setAddtionalDependencies(
    RendererInterface $renderer
  ):void {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function convertElements(string $html): string {
    /** @var array */
    $definitions = $this->getDefinitions();

    if (empty($definitions)) {
      return $html;
    }

    /** @var \Mustache_Engine */
    $mustache = new Mustache_Engine([
      'logger'            => new Mustache_Logger_StreamLogger('php://stderr'),
      'strict_callables'  => true,
    ]);

    /** @var array */
    $variables = [];

    /** @var \Drupal\omnipedia_content_legacy\OmnipediaElementLegacyManagerInterface */
    $manager = $this;

    /** @var \Drupal\Core\Render\RendererInterface */
    $renderer = $this->renderer;

    // Create a callable for each plug-in definition found.
    //
    // @see https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags#lambdas
    foreach ($definitions as $id => $definition) {
      /** @var callable */
      $variables[$id] = function(
        string $content, Mustache_LambdaHelper $helper
      ) use ($manager, $definition, $renderer) {
        /** @var \Drupal\omnipedia_content_legacy\OmnipediaElementLegacyInterface */
        $instance = $manager->createInstance($definition['id'], [
          'content' => $content,
        ]);

        /** @var array */
        $renderArray = $instance->getRenderArray();

        return (string) $renderer->render($renderArray);
      };
    }

    return $mustache->render($html, $variables);
  }

  /**
   * {@inheritdoc}
   */
  public function getTheme(): array {
    /** @var array */
    $definitions = $this->getDefinitions();

    /** @var array */
    $theme = [];

    foreach ($definitions as $id => $definition) {
      $theme[$id] = [
        'provider'  => $definition['provider'],
        'theme'     => $definition['class']::getTheme(),
      ];
    }

    return $theme;
  }

}
