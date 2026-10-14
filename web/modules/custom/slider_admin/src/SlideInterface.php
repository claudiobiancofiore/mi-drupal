<?php

declare(strict_types=1);

namespace Drupal\slider_admin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Defines a slide shown in the slider.
 */
interface SlideInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Gets the position of the slide inside the slider.
   */
  public function getWeight(): int;

  /**
   * Sets the position of the slide inside the slider.
   */
  public function setWeight(int $weight): static;

}
