document.querySelectorAll("[data-review]").forEach((section) => {
  if (section.dataset.reviewReady === "true") return;
  section.dataset.reviewReady = "true";

  const tabs = [...section.querySelectorAll('[role="tab"]')];
  const panels = [...section.querySelectorAll('[role="tabpanel"]')];
  const selectTab = (tab) => {
    const panelId = tab?.getAttribute("aria-controls");
    if (!tabs.includes(tab) || !panels.some((panel) => panel.id === panelId))
      return;
    tabs.forEach((item) => {
      const selected = item === tab;
      item.setAttribute("aria-selected", String(selected));
      item.tabIndex = selected ? 0 : -1;
    });
    panels.forEach((panel) => {
      const selected = panel.id === panelId;
      panel.hidden = !selected;
      if (selected) {
        panel.querySelectorAll("[data-horizontal-slider]").forEach((slider) => {
          slider.dispatchEvent(new CustomEvent("horizontal-slider:refresh"));
        });
      }
    });
  };

  tabs.forEach((tab, index) => {
    tab.addEventListener("click", () => selectTab(tab));
    tab.addEventListener("keydown", (event) => {
      let nextIndex = null;
      if (event.key === "Home") nextIndex = 0;
      if (event.key === "End") nextIndex = tabs.length - 1;
      if (event.key === "ArrowRight") nextIndex = (index + 1) % tabs.length;
      if (event.key === "ArrowLeft")
        nextIndex = (index - 1 + tabs.length) % tabs.length;
      if (nextIndex === null) return;
      event.preventDefault();
      selectTab(tabs[nextIndex]);
      tabs[nextIndex].focus();
    });
  });

  const dialog = section.querySelector("[data-review-dialog]");
  if (!dialog) return;
  const video = dialog.querySelector("video");
  const loading = dialog.querySelector("[data-review-loading]");
  const empty = dialog.querySelector("[data-review-empty]");
  const error = dialog.querySelector(
    "[data-review-error], .review-video-error",
  );
  const title = dialog.querySelector("h2");
  const role = dialog.querySelector(
    "[data-review-dialog-role], .review__dialog-role",
  );
  const card = dialog.querySelector('[class$="__dialog-card"]');

  section.querySelectorAll("[data-review-video]").forEach((button) => {
    button.addEventListener("click", () => {
      const article = button.closest("article");
      if (!article) return;
      const source = button.dataset.reviewVideo;
      dialog.dialogOpener = button;
      if (title)
        title.textContent = article.querySelector("h3")?.textContent || "";
      if (role) role.innerHTML = article.querySelector("p")?.innerHTML || "";
      if (card) {
        const image =
          article.style.getPropertyValue("--review-image") ||
          getComputedStyle(article).backgroundImage;
        card.style.setProperty("--review-image", image);
      }
      if (loading) loading.hidden = !source;
      if (empty) empty.hidden = Boolean(source);
      if (error) error.hidden = true;
      dialog.showModal();
      document.documentElement.classList.add("review-dialog-open");
      if (source && video) {
        video.src = source;
        video.load();
        video.play().catch(() => {});
      }
    });
  });

  video?.addEventListener("playing", () => {
    if (loading) loading.hidden = true;
  });
  video?.addEventListener("error", () => {
    if (!dialog.open) return;
    if (loading) loading.hidden = true;
    if (error) error.hidden = false;
  });
  video?.addEventListener("click", () => {
    if (video.paused) video.play().catch(() => {});
    else video.pause();
  });
  dialog
    .querySelector("[data-review-close]")
    ?.addEventListener("click", () => dialog.close());
  dialog.addEventListener("click", (event) => {
    if (event.target === dialog) dialog.close();
  });
  dialog.addEventListener("close", () => {
    document.documentElement.classList.remove("review-dialog-open");
    video?.pause();
    video?.removeAttribute("src");
    video?.load();
    if (loading) loading.hidden = true;
    dialog.dialogOpener?.focus?.();
  });
});
