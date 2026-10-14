<?php

declare(strict_types=1);

namespace Drupal\slider_admin\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\slider_admin\SlideInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Shows the visible slides as an accessible slider.
 */
#[Block(
  id: 'slider_admin_block',
  admin_label: new TranslatableMarkup('Slider'),
  category: new TranslatableMarkup('Slider Admin'),
)]
class SliderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'autoplay' => TRUE,
      'interval' => 6,
      'aria_label' => 'Destacados',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rotate slides automatically'),
      '#description' => $this->t('A pause button is always shown, and rotation stops while the mouse or keyboard focus is on the slider.'),
      '#default_value' => $this->configuration['autoplay'],
    ];
    $form['interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Seconds per slide'),
      '#min' => 3,
      '#max' => 30,
      '#default_value' => $this->configuration['interval'],
      '#states' => [
        'visible' => [':input[name="settings[autoplay]"]' => ['checked' => TRUE]],
      ],
    ];
    $form['aria_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name for screen readers'),
      '#description' => $this->t('Describes the slider for people using assistive technology, for example "Featured news".'),
      '#default_value' => $this->configuration['aria_label'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['autoplay'] = (bool) $form_state->getValue('autoplay');
    $this->configuration['interval'] = (int) $form_state->getValue('interval');
    $this->configuration['aria_label'] = $form_state->getValue('aria_label');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $storage = $this->entityTypeManager->getStorage('slider_slide');
    $cache = (new CacheableMetadata())
      ->addCacheTags(['slider_slide_list', 'config:image.style.slider_admin_full']);

    // Access is checked per slide below, so the query itself can skip it.
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->sort('weight')
      ->sort('id')
      ->execute();

    $slides = [];
    foreach ($storage->loadMultiple($ids) as $slide) {
      /** @var \Drupal\slider_admin\SlideInterface $slide */
      $access = $slide->access('view', NULL, TRUE);
      $cache->addCacheableDependency($access);
      if (!$access->isAllowed()) {
        continue;
      }
      $cache->addCacheableDependency($slide);
      if ($item = $this->buildSlide($slide, count($slides) === 0, $cache)) {
        $slides[] = $item;
      }
    }

    $build = [];
    if ($slides) {
      $build = [
        '#theme' => 'slider_admin',
        '#slides' => $slides,
        '#slider_id' => Html::getUniqueId('slider-admin'),
        '#autoplay' => (bool) $this->configuration['autoplay'] && count($slides) > 1,
        '#interval' => max(3, (int) $this->configuration['interval']) * 1000,
        '#label' => $this->configuration['aria_label'],
        '#attached' => ['library' => ['slider_admin/slider']],
      ];
    }
    $cache->applyTo($build);
    return $build;
  }

  /**
   * Prepares the variables for one slide.
   */
  protected function buildSlide(SlideInterface $slide, bool $is_first, CacheableMetadata $cache): ?array {
    $image = $slide->get('image')->first();
    $file = $image?->entity;
    if (!$file) {
      return NULL;
    }

    $url = NULL;
    $link_text = NULL;
    if (!$slide->get('link')->isEmpty()) {
      $link = $slide->get('link')->first();
      $generated = $link->getUrl()->toString(TRUE);
      $cache->addCacheableDependency($generated);
      $url = $generated->getGeneratedUrl();
      $link_text = $link->title ?: NULL;
    }

    return [
      'title' => $slide->label(),
      'subtitle' => $slide->get('subtitle')->value,
      'url' => $url,
      'link_text' => $link_text,
      'image' => [
        '#theme' => 'image_style',
        '#style_name' => 'slider_admin_full',
        '#uri' => $file->getFileUri(),
        '#alt' => $image->alt ?? '',
        '#width' => $image->width,
        '#height' => $image->height,
        // The first image is visible right away; the rest can wait.
        '#attributes' => [
          'loading' => $is_first ? 'eager' : 'lazy',
          'class' => ['slider-admin__img'],
        ],
      ],
    ];
  }

}
