<?php

namespace Drupal\omnipedia_content;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\omnipedia_content\Annotation\OmnipediaElement as OmnipediaElementAnnotation;
use Drupal\omnipedia_content\OmnipediaElementInterface;
use Drupal\omnipedia_content\OmnipediaElementManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The OmnipediaElement plug-in manager.
 */
class OmnipediaElementManager extends DefaultPluginManager implements OmnipediaElementManagerInterface {

  use StringTranslationTrait;

  /**
   * An array of element errors, keyed by the plug-in ID.
   *
   * Each plug-in ID key should contain an array of one or more error messages,
   * as \Drupal\Core\StringTranslation\TranslatableMarkup instances.
   *
   * @var array
   */
  protected $elementErrors = [];

  /**
   * The Drupal messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The Drupal renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Generated XPath for any element names that render their own children.
   *
   * This starts off as null to indicate that it has not been built, and if no
   * elements that render their own children are registered, this will be an
   * empty string.
   *
   * @var string|null
   *
   * @see $this->getNonRenderingElementXPath()
   */
  protected $nonRenderingElementXPath = null;

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
      // the 'src/Plugin/Omnipedia/Element' subdirectory of any enabled modules.
      // This also serves to define the PSR-4 subnamespace in which
      // OmnipediaElement plug-ins will live.
      'Plugin/Omnipedia/Element',

      $namespaces,

      $moduleHandler,

      // The name of the interface that plug-ins should adhere to. Drupal will
      // enforce this as a requirement. If a plug-in does not implement this
      // interface, Drupal will throw an error.
      OmnipediaElementInterface::class,

      // The name of the annotation class that contains the plug-in definition.
      OmnipediaElementAnnotation::class
    );

    // This allows the plug-in definitions to be altered by an alter hook. The
    // parameter defines the name of the hook:
    //
    // hook_omnipedia_element_info_alter()
    $this->alterInfo('omnipedia_element_info');

    // This sets the caching method for our plug-in definitions. Plug-in
    // definitions are discovered by examining the directory defined above, for
    // any classes with a OmnipediaElementAnnotation::class. The annotations are
    // read, and then the resulting data is cached using the provided cache
    // backend.
    $this->setCacheBackend($cacheBackend, 'omnipedia_element_info');
  }

  /**
   * {@inheritdoc}
   */
  public function setAddtionalDependencies(
    MessengerInterface    $messenger,
    RendererInterface     $renderer,
    TranslationInterface  $stringTranslation
  ):void {
    $this->messenger          = $messenger;
    $this->renderer           = $renderer;
    $this->stringTranslation  = $stringTranslation;
  }

  /**
   * Get the names of any elements that render their own children.
   *
   * @return string[]
   *   An array of element name strings.
   */
  protected function getNonRenderingElementNames(): array {
    /** @var array */
    $definitions = $this->getDefinitions();

    /** @var array */
    $elements = [];

    foreach ($definitions as $pluginID => $definition) {
      if ($definition['render_children'] === false) {
        $elements[] = $definition['html_element'];
      }
    }

    return $elements;
  }

  /**
   * Get the XPath expression for any elements that render their own children.
   *
   * @return string
   *
   * @see $this->nonRenderingElementXPath
   */
  protected function getNonRenderingElementXPath(): string {
    /** @var string[] */
    $nonRenderingElementNames = $this->getNonRenderingElementNames();

    if ($this->nonRenderingElementXPath === null) {
      if (count($nonRenderingElementNames) === 0) {
        // Set this to an empty stirng if no elements that render their own
        // children are registered so that we don't have to build this again
        // during this request.
        $this->nonRenderingElementXPath = '';

      } else {
        /** @var string[] */
        $nonRenderingElementXPathArray = [];

        foreach ($nonRenderingElementNames as $element) {
          // This evaluates to true if one of the ancestors of whatever element
          // this XPath is applied to is this element that renders its own
          // children.
          /** @var string */
          $nonRenderingElementXPathArray[] =
            'count(ancestor::' . $element . ')=0';
        }

        // Glue the generated XPath strings into an "and" clause. Note that
        // \implode() correctly handles zero or one elements as you would
        // expect, without the need for additional logic.
        $this->nonRenderingElementXPath = \implode(
          ' and ', $nonRenderingElementXPathArray
        );
      }
    }

    return $this->nonRenderingElementXPath;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Rather than rendering the final HTML here, can we create a
   *   placeholder via FilterProcessResult::createPlaceholder()? Since this is
   *   not a filter plug-in, we'd have to return some sort of serlizable format,
   *   such as a render array or similar data, and leave that up to the filter.
   *
   * @see \Drupal\filter\FilterProcessResult::createPlaceholder()
   *
   * @see https://git.drupalcode.org/project/freelinking/-/blob/8.x-3.x/src/Plugin/Filter/Freelinking.php
   *   The Freelinking filter creates placeholders which are rendered by their
   *   service at the end of the rendering process.
   */
  public function convertElements(
    string $html, bool $forceRenderChildren = false
  ): string {
    /** @var array */
    $definitions = $this->getDefinitions();

    if ($forceRenderChildren === false) {
      /** @var string */
      $nonRenderingElementXPath = $this->getNonRenderingElementXPath();

    } else {
      /** @var string */
      $nonRenderingElementXPath = '';
    }

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $rootCrawler = new Crawler(
      // The <div> is to prevent the PHP DOM automatically wrapping any
      // top-level text content in a <p> element.
      '<div id="omnipedia-element-manager-root">' . $html . '</div>'
    );

    // Loop over all plug-in definitions, parsing and rendering any whose
    // matching HTML elements are found in the HTML content.
    foreach ($definitions as $pluginID => $definition) {
      // If there is at least one element plug-in that renders its own children,
      // filter using XPath so that we only render if the current element is not
      // within one of those.
      if (!empty($nonRenderingElementXPath) && $forceRenderChildren === false) {
        /** @var \Symfony\Component\DomCrawler\Crawler */
        $pluginCrawler = $rootCrawler->filterXPath(
          '//' . $definition['html_element'] . '[' .
            $nonRenderingElementXPath .
          ']'
        );

      // Otherwise, just use the cheaper CSS selector filtering.
      } else {
        /** @var \Symfony\Component\DomCrawler\Crawler */
        $pluginCrawler = $rootCrawler->filter($definition['html_element']);
      }

      // If we've found any elements in the HTML matching this plug-in
      // definition, create a plug-in instance for each occurance, so that the
      // plug-in can parse it and build a render array.
      foreach ($pluginCrawler as $element) {
        /** @var \Symfony\Component\DomCrawler\Crawler */
        $elementCrawler = new Crawler($element);

        /** @var \Drupal\omnipedia_content\OmnipediaElementInterface */
        $instance = $this->createInstance($pluginID, [
          'elements' => $elementCrawler,
        ]);

        /** @var array */
        $renderArray = $instance->getRenderArray();

        // Render the new element as an HTML string.
        //
        // @todo Remove this once we have a system in place to return a
        //   serializable format that can be saved as a placeholder for later
        //   rendering.
        $newHtml = (string) $this->renderer->render($renderArray);

        // Parse the new element HTML into a DOM tree.
        /** @var \Symfony\Component\DomCrawler\Crawler */
        $newNodesCrawler = new Crawler(
          // The <div> is to prevent the PHP DOM automatically wrapping any
          // top-level text content in a <p> element.
          '<div id="omnipedia-element-manager-root">' .
            $newHtml .
          '</div>'
        );

        // Attempt to find the first rendered element in the newly parsed DOM.
        // Rather than using \DOMNode::firstChild(), which can return text nodes
        // (including white-space-only nodes), we're using a CSS selector to
        // ensure only an actual element is selected. Also note that if there
        // was an error parsing the DOM, this will be null.
        /** @var \DOMNode|null */
        $newNode = $newNodesCrawler->filter(
          '#omnipedia-element-manager-root > :first-child'
        )->getNode(0);

        // If there was an error in creating the crawler, skip this.
        if (!($newNode instanceof \DOMNode)) {
          $this->messenger->addError($this->t(
            'Could not find the rendered element for this <code>@element</code> element.',
            ['@element' => '<' . $definition['html_element'] . '>']
          ));

          continue;
        }

        // Log any errors this instance reports. Note that we allow processing
        // to go ahead regardless.
        if ($instance->hasErrors()) {
          /** @var \Drupal\Core\StringTranslation\TranslatableMarkup[] */
          $instanceErrors = $instance->getErrors();

          foreach ($instanceErrors as $error) {
            $this->setElementError($pluginID, $error);
          }
        }

        // We need to find the new node's parent to use the replaceChild()
        // method, awkward though it may be. This should be the
        // '#omnipedia-element-manager-root' element.
        /** @var \DOMNode|null */
        $elementParent = $elementCrawler->getNode(0)->parentNode;

        if ($elementParent === null) {
          $this->messenger->addError($this->t(
            'Could not find a valid parent for this <code>@element</code> element.',
            ['@element' => '<' . $definition['html_element'] . '>']
          ));

          continue;
        }

        // Replace the old node (the plug-in's custom HTML element) with the
        // newly rendered node (the standard HTML element structure).
        $elementParent->replaceChild(
          // New node.
          $elementCrawler->getNode(0)->ownerDocument
            ->importNode($newNode, true),
          // Old node.
          $elementCrawler->getNode(0)
        );
      }
    }

    return $rootCrawler->filter('#omnipedia-element-manager-root')->html();
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

  /**
   * Set an error for the given plug-in ID.
   *
   * @param string $pluginID
   *   The plug-in ID to set the error for.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $error
   *   The error message to set.
   */
  protected function setElementError(
    string $pluginID, TranslatableMarkup $error
  ): void {
    $this->elementErrors[$pluginID][] = $error;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementErrors(): array {
    return $this->elementErrors;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementFormValidationErrors(): array {
    /** @var array */
    $errors = $this->getElementErrors();

    /** @var array */
    $formErrors = [];

    if (count($errors) === 0) {
      return $formErrors;
    }

    /** @var array */
    $definitions = $this->getDefinitions();

    foreach ($errors as $pluginID => $pluginErrors) {
      foreach ($pluginErrors as $error) {
        $formErrors[$pluginID][] = $this->t(
          '<code>@element</code> element: @error',
          [
            '@element'  => '<' . $definitions[$pluginID]['html_element'] . '>',
            '@error'    => $error,
          ]
        );
      }
    }

    return $formErrors;
  }

}
