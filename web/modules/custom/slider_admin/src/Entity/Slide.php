<?php

declare(strict_types=1);

namespace Drupal\slider_admin\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\slider_admin\Form\SlideForm;
use Drupal\slider_admin\SlideAccessControlHandler;
use Drupal\slider_admin\SlideInterface;
use Drupal\slider_admin\SlideListBuilder;

/**
 * Defines the Slide entity.
 */
#[ContentEntityType(
  id: 'slider_slide',
  label: new TranslatableMarkup('Slide'),
  label_collection: new TranslatableMarkup('Slider'),
  label_singular: new TranslatableMarkup('slide'),
  label_plural: new TranslatableMarkup('slides'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'title',
    'published' => 'status',
  ],
  handlers: [
    'access' => SlideAccessControlHandler::class,
    'list_builder' => SlideListBuilder::class,
    'form' => [
      'default' => SlideForm::class,
      'add' => SlideForm::class,
      'edit' => SlideForm::class,
      'delete' => ContentEntityDeleteForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'add-form' => '/admin/content/slider/add',
    'edit-form' => '/admin/content/slider/{slider_slide}/edit',
    'delete-form' => '/admin/content/slider/{slider_slide}/delete',
    'collection' => '/admin/content/slider',
  ],
  admin_permission: 'administer slider slides',
  base_table: 'slider_slide',
  label_count: [
    'singular' => '@count slide',
    'plural' => '@count slides',
  ],
)]
class Slide extends ContentEntityBase implements SlideInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return (int) $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight(int $weight): static {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    // New slides go to the end of the slider unless a position was given.
    if ($this->isNew() && $this->get('weight')->isEmpty()) {
      $result = $storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('weight', 'MAX')
        ->execute();
      $max = $result[0]['weight_max'] ?? NULL;
      $this->setWeight($max === NULL ? 0 : ((int) $max) + 1);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setDescription(new TranslatableMarkup('Main text of the slide.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['subtitle'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Text'))
      ->setDescription(new TranslatableMarkup('Optional short text shown under the title.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 1,
        'settings' => ['rows' => 2],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(new TranslatableMarkup('Image'))
      ->setDescription(new TranslatableMarkup('Recommended size: 1600×600 pixels. It will be cropped automatically.'))
      ->setRequired(TRUE)
      ->setSettings([
        'file_directory' => 'slider',
        'file_extensions' => 'png jpg jpeg webp',
        'max_filesize' => '8 MB',
        'alt_field' => TRUE,
        'alt_field_required' => TRUE,
        'title_field' => FALSE,
        'min_resolution' => '1200x400',
        'max_resolution' => '',
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 2,
        'settings' => [
          'preview_image_style' => 'slider_admin_thumbnail',
          'progress_indicator' => 'throbber',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(new TranslatableMarkup('Link'))
      ->setDescription(new TranslatableMarkup('Optional. Internal path (for example /tramites) or full URL. The link text is the button label.'))
      ->setSettings([
        // LinkItemInterface::LINK_GENERIC: internal and external links.
        'link_type' => 17,
        // DRUPAL_OPTIONAL: the link text is optional.
        'title' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => 3,
        'settings' => [
          'placeholder_url' => '/tramites',
          'placeholder_title' => 'Ver más',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status']
      ->setLabel(new TranslatableMarkup('Visible on the site'))
      ->setDescription(new TranslatableMarkup('Uncheck to hide the slide without deleting it.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 4,
        'settings' => ['display_label' => TRUE],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Weight'))
      ->setDescription(new TranslatableMarkup('Position of the slide. Lower values are shown first.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'));

    return $fields;
  }

}
