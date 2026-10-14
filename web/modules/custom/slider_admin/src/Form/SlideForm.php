<?php

declare(strict_types=1);

namespace Drupal\slider_admin\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Add and edit form for slides.
 */
class SlideForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $args = ['%title' => $this->entity->label()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('The slide %title has been created.', $args));
    }
    else {
      $this->messenger()->addStatus($this->t('The slide %title has been updated.', $args));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
