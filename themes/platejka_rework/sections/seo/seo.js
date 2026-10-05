(() => {
  "use strict";

  const initSeoSection = (section) => {
    const content = section.querySelector("[data-seo-content]");
    const toggle = section.querySelector("[data-seo-toggle]");

    if (!content || !toggle) {
      return false;
    }

    const children = [...content.children];
    const leadIndex = children.findIndex((child) => child.tagName === "P");

    if (leadIndex < 0 || leadIndex === children.length - 1) {
      return false;
    }

    const collapsible = content.ownerDocument.createElement("div");
    const reducedMotion = window.matchMedia(
      "(prefers-reduced-motion: reduce)",
    );
    const showLabel = toggle.dataset.seoShowLabel || "Показать ещё";
    const hideLabel = toggle.dataset.seoHideLabel || "Скрыть";
    let expanded = false;

    collapsible.dataset.seoCollapsible = "";
    content.insertBefore(collapsible, children[leadIndex + 1]);
    children
      .slice(leadIndex + 1)
      .forEach((child) => collapsible.appendChild(child));

    Object.assign(collapsible.style, {
      height: "0px",
      overflow: "hidden",
      transition: reducedMotion.matches
        ? "none"
        : "height 450ms cubic-bezier(0.22, 1, 0.36, 1)",
    });
    collapsible.hidden = true;

    const updateToggle = () => {
      toggle.setAttribute("aria-expanded", String(expanded));
      toggle.textContent = expanded ? hideLabel : showLabel;
    };

    collapsible.addEventListener("transitionend", (event) => {
      if (event.propertyName !== "height") {
        return;
      }

      if (expanded) {
        collapsible.style.height = "auto";
      } else {
        collapsible.hidden = true;
      }
    });

    toggle.addEventListener("click", () => {
      if (expanded) {
        expanded = false;
        updateToggle();

        if (reducedMotion.matches) {
          collapsible.style.height = "0px";
          collapsible.hidden = true;
          return;
        }

        collapsible.style.height = `${collapsible.scrollHeight}px`;
        collapsible.offsetHeight;
        collapsible.style.height = "0px";
        return;
      }

      expanded = true;
      collapsible.hidden = false;
      updateToggle();

      if (reducedMotion.matches) {
        collapsible.style.height = "auto";
        return;
      }

      requestAnimationFrame(() => {
        collapsible.style.height = `${collapsible.scrollHeight}px`;
      });
    });

    updateToggle();
    toggle.hidden = false;
    return true;
  };

  document.querySelectorAll("[data-seo]").forEach(initSeoSection);
})();
