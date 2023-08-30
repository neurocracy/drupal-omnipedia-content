<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\EventSubscriber\Form;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\omnipedia_content\PluginManager\OmnipediaElementManagerInterface;
use Drupal\omnipedia_core\Entity\WikiNodeInfo;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to add Omnipedia element validation to wiki node edit form.
 *
 * @todo Can this be applied to all 'processed_text' elements so that we don't
 *   have to target the body field here specifically? Alternatively, what about
 *   using the @link https://www.drupal.org/docs/drupal-apis/entity-api/entity-validation-api/providing-a-custom-validation-constraint#add_constraint_other_entity_base_field Entity Validation API @endLink?
 *
 * @see \Drupal\filter\Element\ProcessedText
 */
class WikiNodeElementValidateEventSubscriber implements EventSubscriberInterface {

  // Note that this is required for Drupal to correctly serialize this when
  // initiating a node prevew. Without this, we would get a WSOD due to using
  // $this in the #element_validate callable value.
  use DependencySerializationTrait;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_content\PluginManager\OmnipediaElementManagerInterface $elementManager
   *   The OmnipediaElement plug-in manager.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal renderer service.
   */
  public function __construct(
    protected readonly OmnipediaElementManagerInterface $elementManager,
    protected readonly RendererInterface                $renderer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      'hook_event_dispatcher.form_node_' . WikiNodeInfo::TYPE . '_edit_form.alter' => 'onFormAlter',
    ];
  }

  /**
   * Alter the 'node_wiki_page_edit_form' form.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   The event object.
   */
  public function onFormAlter(FormIdAlterEvent $event): void {
    /** @var array */
    $form = &$event->getForm();

    // Add our validation method.
    $form['body']['widget'][0]['#element_validate'][] = [
      $this, 'validateBodyElement'
    ];
  }

  /**
   * Validate the body form element.
   *
   * @param array &$element
   *   The element being validated.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   *
   * @param array &$form
   *   The whole form.
   *
   * @see \Drupal\omnipedia_content\Plugin\Filter\OmnipediaElementFilter
   *   Filter plug-in that passes off element rendering to the element manager.
   *
   * @see \Drupal\omnipedia_content\PluginManager\OmnipediaElementManagerInterface::convertElements()
   *   Renders any elements found in the passed filter input.
   *
   * @see \Drupal\omnipedia_content\PluginManager\OmnipediaElementManagerInterface::getElementFormValidationErrors()
   *   Returns any element errors for output in form validation.
   */
  public function validateBodyElement(
    array &$element, FormStateInterface $formState, array &$form,
  ): void {
    if (!isset($element['#needs_validation'])) {
      return;
    }

    /** @var array */
    $renderArray = [
      '#type'   => 'processed_text',
      '#text'   => $element['#value'],
      '#format' => $element['#format'],
    ];

    // Render the 'processed_text' so that the element manager can collect any
    // errors. Since we don't expect to have hundreds or thousands of editors,
    // this potential performance hit should be largely irrelevant once the node
    // is saved and the rendered filter output is cached.
    //
    // If we do later need this to scale, it should be possible to cache this
    // output in a new render context (using render() to correctly attach
    // libraries, etc.) and use that on a successful form submit.
    //
    // We don't need the actual rendered output here, so that's ignored.
    $this->renderer->renderPlain($renderArray);

    /** @var array */
    $errors = $this->elementManager->getElementFormValidationErrors();

    if (count($errors) === 0) {
      return;
    }

    foreach ($errors as $elementName => $elementErrors) {
      foreach ($elementErrors as $error) {
        $formState->setErrorByName('body', $error);
      }
    }
  }

}
