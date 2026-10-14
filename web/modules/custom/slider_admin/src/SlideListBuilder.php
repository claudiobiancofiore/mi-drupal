<?php

declare(strict_types=1);

namespace Drupal\slider_admin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin listing of slides with drag-and-drop ordering.
 */
class SlideListBuilder extends EntityListBuilder implements FormInterface {

  /**
   * The form builder.
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * Show every slide on one page so they can all be reordered.
   *
   * @var int|false
   */
  protected $limit = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->formBuilder = $container->get('form_builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    return $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort('weight')
      ->sort('id')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'slider_admin_slide_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $slides = $this->load();
    $delta = max(20, count($slides));

    $form['slides'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Image'),
        $this->t('Title'),
        $this->t('Status'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('There are no slides yet. Use the <em>Add slide</em> button to create the first one.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'slide-weight',
        ],
      ],
      '#attributes' => ['id' => 'slider-admin-slides'],
    ];
    $form['#cache'] = [
      'tags' => $this->entityType->getListCacheTags(),
      'contexts' => $this->entityType->getListCacheContexts(),
    ];

    foreach ($slides as $id => $slide) {
      /** @var \Drupal\slider_admin\SlideInterface $slide */
      $form['slides'][$id]['#attributes']['class'][] = 'draggable';
      $form['slides'][$id]['#weight'] = $slide->getWeight();

      $form['slides'][$id]['image'] = $this->buildThumbnail($slide);
      $form['slides'][$id]['title'] = [
        '#plain_text' => $slide->label(),
      ];
      $form['slides'][$id]['status'] = [
        '#markup' => $slide->isPublished() ? $this->t('Visible') : $this->t('Not visible'),
      ];
      $form['slides'][$id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $slide->label()]),
        '#title_display' => 'invisible',
        '#default_value' => $slide->getWeight(),
        '#delta' => $delta,
        '#attributes' => ['class' => ['slide-weight']],
      ];
      $form['slides'][$id]['operations'] = [
        '#type' => 'operations',
        '#links' => $this->getOperations($slide),
      ];
    }

    if ($slides) {
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save order'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * Builds the small preview image shown in the listing.
   */
  protected function buildThumbnail(SlideInterface $slide): array {
    $item = $slide->get('image')->first();
    $file = $item?->entity;
    if (!$file) {
      return ['#markup' => ''];
    }
    return [
      '#theme' => 'image_style',
      '#style_name' => 'slider_admin_thumbnail',
      '#uri' => $file->getFileUri(),
      '#alt' => $item->alt ?? '',
      '#width' => $item->width,
      '#height' => $item->height,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Nothing to validate: weights come from a select list.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValue('slides') ?: [];
    $slides = $this->getStorage()->loadMultiple(array_keys($values));

    foreach ($slides as $id => $slide) {
      /** @var \Drupal\slider_admin\SlideInterface $slide */
      $weight = (int) $values[$id]['weight'];
      if ($slide->getWeight() !== $weight) {
        $slide->setWeight($weight)->save();
      }
    }

    $this->messenger()->addStatus($this->t('The slider order has been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    return $this->formBuilder->getForm($this);
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Rows are built in buildForm() instead.
    return [];
  }

}
