(() => {
  const config = window.PlatejkaBlog || {};
  let activeRequest;

  const requestPosts = async (section, page, search = '') => {
    activeRequest?.abort();
    activeRequest = new AbortController();
    const data = new FormData();
    data.set('action', config.action || 'platejka_blog_posts');
    data.set('nonce', config.nonce || '');
    data.set('surface', section.dataset.surface || 'archive');
    const globalLandingSearch = Boolean(search) && section.dataset.surface === 'landing';
    data.set('taxonomy', globalLandingSearch ? '' : (section.dataset.taxonomy || ''));
    data.set('term', globalLandingSearch ? '' : (section.dataset.term || ''));
    data.set('page', String(page));
    data.set('viewport', String(window.innerWidth));
    data.set('search', search);
    const response = await fetch(config.ajaxUrl, { method: 'POST', body: data, signal: activeRequest.signal });
    if (!response.ok) throw new Error('Request failed');
    const payload = await response.json();
    if (!payload.success) throw new Error(payload.data?.message || 'Request failed');
    return payload.data;
  };

  document.querySelectorAll('[data-blog-section]').forEach(section => {
    const grid = section.querySelector('[data-blog-grid]');
    const button = section.querySelector('[data-blog-load-more]');
    const status = section.querySelector('[data-blog-status]');
    button?.addEventListener('click', async () => {
      const nextPage = Number(button.dataset.page || 1) + 1;
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
      status.textContent = 'Загрузка…';
      try {
        const result = await requestPosts(section, nextPage);
        grid.insertAdjacentHTML('beforeend', result.html);
        button.dataset.page = String(nextPage);
        button.hidden = !result.has_more;
        status.textContent = result.has_more ? '' : 'Все статьи загружены';
      } catch (error) {
        if (error.name !== 'AbortError') status.textContent = config.error || 'Не удалось загрузить статьи.';
      } finally {
        button.disabled = false;
        button.removeAttribute('aria-busy');
      }
    });
  });

  const searchForm = document.querySelector('[data-blog-search]');
  const searchInput = searchForm?.querySelector('input[type="search"]');
  const landingSections = [...document.querySelectorAll('[data-blog-section][data-surface="landing"]')];
  landingSections.forEach(section => {
    section.blogOriginal = {
      grid: section.querySelector('[data-blog-grid]')?.innerHTML || '',
      title: section.querySelector('.blog-section__heading h2')?.textContent || '',
      buttonHidden: section.querySelector('[data-blog-load-more]')?.hidden ?? true,
    };
  });
  let searchTimer;
  const runSearch = async () => {
    const section = document.querySelector('[data-blog-section][data-surface="archive"]') || document.querySelector('[data-blog-section]');
    const grid = section?.querySelector('[data-blog-grid]');
    if (!section || !grid) return;
    const term = searchInput.value.trim();
    const url = new URL(window.location.href);
    term ? url.searchParams.set('blog_search', term) : url.searchParams.delete('blog_search');
    history.replaceState({}, '', url);
    if (!term && landingSections.length) {
      landingSections.forEach(sectionItem => {
        sectionItem.hidden = false;
        sectionItem.querySelector('[data-blog-grid]').innerHTML = sectionItem.blogOriginal.grid;
        sectionItem.querySelector('.blog-section__heading h2').textContent = sectionItem.blogOriginal.title;
        const originalButton = sectionItem.querySelector('[data-blog-load-more]');
        if (originalButton) originalButton.hidden = sectionItem.blogOriginal.buttonHidden;
      });
      return;
    }
    if (term && landingSections.length) {
      landingSections.forEach((sectionItem, index) => { sectionItem.hidden = index > 0; });
      section.querySelector('.blog-section__heading h2').textContent = 'Результаты поиска';
    }
    section.setAttribute('aria-busy', 'true');
    try {
      const result = await requestPosts(section, 1, term);
      grid.innerHTML = result.html || '<div class="blog-empty"><h2>Ничего не найдено</h2><p>Попробуйте изменить запрос.</p></div>';
      const button = section.querySelector('[data-blog-load-more]');
      if (button) { button.dataset.page = '1'; button.hidden = !result.has_more; }
    } catch (error) {
      if (error.name !== 'AbortError') section.querySelector('[data-blog-status]').textContent = config.error || 'Не удалось выполнить поиск.';
    } finally { section.removeAttribute('aria-busy'); }
  };
  searchForm?.addEventListener('submit', event => { event.preventDefault(); runSearch(); });
  searchInput?.addEventListener('input', () => { window.clearTimeout(searchTimer); searchTimer = window.setTimeout(runSearch, 350); });
  if (searchInput?.value || (window.innerWidth <= 1024 && document.querySelector('[data-blog-section][data-surface="archive"]'))) runSearch();

  const toc = document.querySelector('[data-blog-toc]');
  toc?.querySelector('.blog-toc__toggle')?.addEventListener('click', event => {
    const expanded = event.currentTarget.getAttribute('aria-expanded') === 'true';
    event.currentTarget.setAttribute('aria-expanded', String(!expanded));
    toc.classList.toggle('is-open', !expanded);
  });

  if (toc) {
    const sections = [...toc.querySelectorAll('a[href^="#"]')]
      .map(link => ({ link, heading: document.getElementById(link.hash.slice(1)) }))
      .filter(section => section.heading);
    let scheduled = false;
    const updateActiveSection = () => {
      scheduled = false;
      if (!sections.length) return;
      const threshold = 160;
      let current = null;
      for (const section of sections) {
        if (section.heading.getBoundingClientRect().top > threshold) break;
        current = section;
      }
      sections.forEach(section => {
        if (section === current) section.link.setAttribute('aria-current', 'location');
        else section.link.removeAttribute('aria-current');
      });
    };
    const scheduleUpdate = () => {
      if (scheduled) return;
      scheduled = true;
      requestAnimationFrame(updateActiveSection);
    };
    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    window.addEventListener('resize', scheduleUpdate);
    scheduleUpdate();
  }

  document.querySelector('[data-blog-share="max"]')?.addEventListener('click', async event => {
    const button = event.currentTarget;
    const shareData = { title: button.dataset.title, url: button.dataset.url };
    const status = document.querySelector('[data-blog-share-status]');
    try {
      if (navigator.share) await navigator.share(shareData);
      else { await navigator.clipboard.writeText(shareData.url); status.textContent = config.copy || 'Ссылка скопирована'; }
    } catch (error) { if (error.name !== 'AbortError') status.textContent = config.error || 'Не удалось поделиться'; }
  });
})();

