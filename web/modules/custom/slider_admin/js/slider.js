/**
 * @file
 * Accessible slider behavior (WAI-ARIA Authoring Practices carousel pattern).
 */

((Drupal, once) => {
  'use strict';

  /**
   * Sets up one slider.
   *
   * @param {HTMLElement} root
   *   The slider wrapper.
   */
  function initSlider(root) {
    const slides = Array.from(root.querySelectorAll('.slider-admin__slide'));
    if (slides.length < 2) {
      return;
    }

    const viewport = root.querySelector('.slider-admin__viewport');
    const dots = Array.from(root.querySelectorAll('.slider-admin__dot'));
    const toggle = root.querySelector('.slider-admin__toggle');
    const prev = root.querySelector('.slider-admin__prev');
    const next = root.querySelector('.slider-admin__next');
    const interval = parseInt(root.dataset.interval, 10) || 6000;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let current = 0;
    let timer = null;
    // People who asked the system to reduce motion start with rotation paused.
    let playing = root.dataset.autoplay === 'true' && !reduceMotion;
    let hovered = false;
    let focused = false;
    // True when the user pressed Play: keep rotating even with focus inside.
    let userStarted = false;

    function show(index) {
      current = (index + slides.length) % slides.length;
      slides.forEach((slide, i) => {
        const active = i === current;
        slide.classList.toggle('is-active', active);
        slide.inert = !active;
      });
      dots.forEach((dot, i) => {
        if (i === current) {
          dot.setAttribute('aria-current', 'true');
        }
        else {
          dot.removeAttribute('aria-current');
        }
      });
    }

    function stop() {
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
      // Announce slide changes only when they are not automatic.
      viewport.setAttribute('aria-live', 'polite');
    }

    function start() {
      stop();
      if (playing && (userStarted || (!hovered && !focused)) && !document.hidden) {
        timer = setInterval(() => show(current + 1), interval);
        viewport.setAttribute('aria-live', 'off');
      }
    }

    function updateToggle() {
      if (!toggle) {
        return;
      }
      toggle.classList.toggle('is-paused', !playing);
      toggle.setAttribute('aria-label', playing ? toggle.dataset.labelPause : toggle.dataset.labelPlay);
    }

    // Choosing a slide by hand stops the "keep playing" override.
    function goTo(index) {
      userStarted = false;
      show(index);
      start();
    }

    prev.addEventListener('click', () => goTo(current - 1));
    next.addEventListener('click', () => goTo(current + 1));
    dots.forEach((dot) => {
      dot.addEventListener('click', () => goTo(parseInt(dot.dataset.index, 10)));
    });

    if (toggle) {
      toggle.addEventListener('click', () => {
        playing = !playing;
        userStarted = playing;
        updateToggle();
        start();
      });
    }

    // Pause while the user is reading or using the slider.
    root.addEventListener('mouseenter', () => {
      hovered = true;
      start();
    });
    root.addEventListener('mouseleave', () => {
      hovered = false;
      start();
    });
    root.addEventListener('focusin', () => {
      focused = true;
      start();
    });
    root.addEventListener('focusout', (event) => {
      if (!root.contains(event.relatedTarget)) {
        focused = false;
        start();
      }
    });
    document.addEventListener('visibilitychange', start);

    // Swipe on touch screens.
    let touchX = null;
    viewport.addEventListener('touchstart', (event) => {
      touchX = event.changedTouches[0].clientX;
    }, { passive: true });
    viewport.addEventListener('touchend', (event) => {
      if (touchX === null) {
        return;
      }
      const delta = event.changedTouches[0].clientX - touchX;
      touchX = null;
      if (Math.abs(delta) > 50) {
        show(delta < 0 ? current + 1 : current - 1);
      }
    }, { passive: true });

    updateToggle();
    show(0);
    start();
  }

  /**
   * Attaches the slider behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.sliderAdmin = {
    attach(context) {
      once('slider-admin', '.slider-admin', context).forEach(initSlider);
    },
  };
})(Drupal, once);
