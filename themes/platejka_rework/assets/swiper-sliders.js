(() => {
  'use strict';

  if (typeof Swiper !== 'function') return;
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const mobile = window.matchMedia('(max-width: 1024px)');

  const gapOf = element => parseFloat(getComputedStyle(element).columnGap) || 0;
  const motion = () => reducedMotion.matches ? 0 : 450;
  const measureSlides = slides => {
    slides.forEach(slide => {
      slide.classList.remove('swiper-slide');
      slide.style.width = '';
      const style = getComputedStyle(slide);
      const basis = style.flexBasis.endsWith('px') ? parseFloat(style.flexBasis) : 0;
      const width = basis || slide.querySelector('img')?.getBoundingClientRect().width || parseFloat(style.width) || slide.getBoundingClientRect().width;
      slide.style.width = width > 0 ? `${width}px` : '';
      slide.style.boxSizing = 'border-box';
      slide.classList.add('swiper-slide');
    });
  };

  function create(root, viewport, track, slides, options = {}) {
    if (!viewport || !track || slides.length < 2) return null;
    const gap = gapOf(track);
    measureSlides(slides);
    viewport.classList.add('swiper');
    track.classList.add('swiper-wrapper');
    root.dataset.horizontalSliderReady = 'true';
    const swiper = new Swiper(viewport, {
      slidesPerView: 'auto',
      spaceBetween: gap,
      speed: motion(),
      watchOverflow: true,
      resistanceRatio: 0.25,
      threshold: 5,
      preventClicks: true,
      preventClicksPropagation: true,
      loop: !!options.loop && slides.length > 2,
      loopAdditionalSlides: options.loop ? slides.length : 0,
      keyboard: { enabled: true, onlyInViewport: true },
      on: {
        slideChange(instance) {
          root.dispatchEvent(new CustomEvent('horizontal-slider:change', {
            detail: { index: instance.realIndex }
          }));
          options.sync?.(instance);
        },
        reachBeginning: options.sync,
        reachEnd: options.sync,
        resize: options.sync
      }
    });
    options.sync?.(swiper);
    window.addEventListener('resize', () => {
      measureSlides(slides);
      swiper.update();
      options.sync?.(swiper);
    }, { passive: true });
    return swiper;
  }

  document.querySelectorAll('[data-horizontal-slider]').forEach(root => {
    if (root.classList.contains('with-us')) return;
    const viewport = root.querySelector('[data-horizontal-slider-viewport]');
    const track = root.querySelector('[data-horizontal-slider-track]');
    const slides = root.hasAttribute('data-horizontal-slider-infinite')
      ? [...(track?.children || [])]
      : [...root.querySelectorAll('[data-horizontal-slider-slide]')];
    const previous = root.querySelector('[data-horizontal-slider-prev]');
    const next = root.querySelector('[data-horizontal-slider-next]');
    const controls = root.querySelector('[data-horizontal-slider-controls]');
    const indexed = [...root.querySelectorAll('[data-horizontal-slider-go-to]')];
    const infinite = root.hasAttribute('data-horizontal-slider-infinite');
    const sync = swiper => {
      if (!swiper || swiper.destroyed) return;
      const minimum = Number(root.dataset.horizontalSliderMinControls || 0);
      if (controls) controls.hidden = slides.length < Math.max(2, minimum) ||
        (root.hasAttribute('data-horizontal-slider-desktop-controls') && mobile.matches);
      if (previous) previous.disabled = !infinite && swiper.isBeginning;
      if (next) next.disabled = !infinite && swiper.isEnd;
      indexed.forEach(button => {
        const active = Number(button.dataset.horizontalSliderGoTo) === swiper.realIndex;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-current', String(active));
      });
    };
    const swiper = create(root, viewport, track, slides, { loop: infinite, sync });
    if (!swiper) return;
    previous?.addEventListener('click', () => swiper.slidePrev(motion()));
    next?.addEventListener('click', () => swiper.slideNext(motion()));
    indexed.forEach(button => button.addEventListener('click', () => swiper.slideTo(Number(button.dataset.horizontalSliderGoTo), motion())));
    root.addEventListener('horizontal-slider:refresh', () => {
      requestAnimationFrame(() => { swiper.update(); sync(swiper); });
    });
  });

  const responsive = [
    { selector: '[data-problems-slider]', track: '.problems__track', slide: '.problems__card' },
    { selector: '[data-guarantees-slider]', slide: '.guarantee-card' },
    { selector: '[data-shipments-slider]', slide: '.shipment-card' },
    { selector: '[data-documents-slider]', slide: '.document-card' },
    { selector: '.with-us__viewport', track: '.with-us__track', slide: '.with-us__card' },
    { selector: '.about-hero__metrics', slide: '.about-hero__metric' }
  ];

  responsive.forEach(config => document.querySelectorAll(config.selector).forEach(viewport => {
    let swiper = null;
    let wrapper = null;
    const track = config.track ? viewport.querySelector(config.track) : null;
    const slides = [...(track || viewport).querySelectorAll(config.slide)];
    if (slides.length < 2) return;
    const gap = gapOf(track || viewport);
    const update = () => {
      if (mobile.matches && !swiper) {
        viewport.scrollLeft = 0;
        measureSlides(slides);
        wrapper = track || document.createElement('div');
        if (!track) {
          wrapper.className = 'swiper-wrapper';
          slides.forEach(slide => wrapper.appendChild(slide));
          viewport.appendChild(wrapper);
        }
        viewport.classList.add('swiper');
        wrapper.classList.add('swiper-wrapper');
        viewport.scrollLeft = 0;
        swiper = new Swiper(viewport, {
          slidesPerView: 'auto', spaceBetween: gap, speed: motion(),
          threshold: 5, watchOverflow: true, resistanceRatio: 0.25,
          keyboard: { enabled: true, onlyInViewport: true }
        });
        window.addEventListener('resize', () => {
          if (!swiper) return;
          swiper.update();
        }, { passive: true });
      } else if (!mobile.matches && swiper) {
        swiper.destroy(true, true);
        swiper = null;
        slides.forEach(slide => { slide.classList.remove('swiper-slide'); slide.style.width = ''; slide.style.boxSizing = ''; });
        wrapper.classList.remove('swiper-wrapper');
        viewport.classList.remove('swiper');
        if (!track) {
          slides.forEach(slide => viewport.appendChild(slide));
          wrapper.remove();
        }
        wrapper = null;
      }
    };
    mobile.addEventListener('change', update);
    update();
  }));
})();
