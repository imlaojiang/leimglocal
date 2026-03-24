(function () {
  var cfg = window.leimglocalLightbox || {};
  var minSize = parseInt(cfg.min_size, 10);
  var postContentOnly = !!cfg.post_content_only;
  var showIcon = !!cfg.show_icon;
  var contentSelector =
    ".entry-content, .post-content, .article-content, .single-content, .the-content, article .content, article .post-content";
  if (isNaN(minSize) || minSize < 100) {
    minSize = 800;
  }

  function isInPostContent(target) {
    if (!postContentOnly) {
      return true;
    }
    if (!target || !target.closest) {
      return false;
    }
    return !!target.closest(contentSelector);
  }

  function getEligibleImage(target) {
    if (!target || target.tagName !== "IMG") {
      return null;
    }
    if (target.closest(".leimglocal-lightbox-overlay")) {
      return null;
    }
    if (!isInPostContent(target)) {
      return null;
    }
    var width = target.naturalWidth || target.width || 0;
    var height = target.naturalHeight || target.height || 0;
    if (width >= minSize || height >= minSize) {
      return target;
    }
    return null;
  }

  function getIconHost(img) {
    var parent = img.parentElement;
    if (
      parent &&
      (parent.tagName === "A" ||
        parent.tagName === "FIGURE" ||
        parent.classList.contains("wp-block-image"))
    ) {
      return parent;
    }
    if (parent && parent.classList.contains("leimglocal-lightbox-icon-wrap")) {
      return parent;
    }
    var wrap = document.createElement("span");
    wrap.className = "leimglocal-lightbox-icon-wrap";
    img.parentNode.insertBefore(wrap, img);
    wrap.appendChild(img);
    return wrap;
  }

  function applyIconForImage(img) {
    if (!showIcon) {
      return;
    }
    var eligible = !!getEligibleImage(img);
    var existingHost =
      img.closest(".leimglocal-lightbox-icon-wrap") || img.parentElement;
    var hasManagedHost =
      existingHost &&
      (existingHost.classList.contains("leimglocal-lightbox-icon-wrap") ||
        existingHost.classList.contains("leimglocal-lightbox-icon-host"));

    if (!eligible && !hasManagedHost) {
      return;
    }

    var host = eligible ? getIconHost(img) : existingHost;
    if (!host) {
      return;
    }
    var icon = host.querySelector(".leimglocal-lightbox-icon");
    if (!eligible) {
      if (icon) {
        icon.remove();
      }
      host.classList.remove("leimglocal-lightbox-icon-host");
      return;
    }
    host.classList.add("leimglocal-lightbox-icon-host");
    if (!icon) {
      icon = document.createElement("span");
      icon.className = "leimglocal-lightbox-icon";
      icon.setAttribute("aria-hidden", "true");
      icon.setAttribute("title", "可点击放大");
      icon.innerHTML =
        '<svg viewBox="0 0 24 24" width="14" height="14" focusable="false" aria-hidden="true"><path d="M15.5 14h-.8l-.3-.3a6 6 0 1 0-.8.8l.3.3v.8L19 21l2-2-5.5-5zm-5.5 0A4 4 0 1 1 10 6a4 4 0 0 1 0 8z" fill="currentColor"/></svg>';
      host.appendChild(icon);
    }
  }

  function scanAndApplyIcons() {
    if (!showIcon) {
      return;
    }
    var imgs = document.querySelectorAll("img");
    imgs.forEach(function (img) {
      if (!img.complete) {
        img.addEventListener(
          "load",
          function () {
            applyIconForImage(img);
          },
          { once: true }
        );
      }
      applyIconForImage(img);
    });
  }

  function createOverlay(img) {
    var overlay = document.createElement("div");
    overlay.className = "leimglocal-lightbox-overlay";
    overlay.setAttribute("role", "dialog");
    overlay.setAttribute("aria-modal", "true");

    var closeBtn = document.createElement("button");
    closeBtn.className = "leimglocal-lightbox-close";
    closeBtn.setAttribute("type", "button");
    closeBtn.setAttribute("aria-label", cfg.close_text || "Close");
    closeBtn.textContent = "×";

    var lightImg = document.createElement("img");
    lightImg.className = "leimglocal-lightbox-image";
    lightImg.src = img.currentSrc || img.src;
    lightImg.alt = img.alt || "";

    overlay.appendChild(closeBtn);
    overlay.appendChild(lightImg);
    document.body.appendChild(overlay);
    document.body.classList.add("leimglocal-lightbox-open");

    function closeOverlay() {
      document.body.classList.remove("leimglocal-lightbox-open");
      overlay.remove();
      document.removeEventListener("keydown", onEsc, true);
    }

    function onEsc(e) {
      if (e.key === "Escape") {
        closeOverlay();
      }
    }

    closeBtn.addEventListener("click", closeOverlay);
    overlay.addEventListener("click", function (e) {
      if (e.target === overlay) {
        closeOverlay();
      }
    });
    document.addEventListener("keydown", onEsc, true);
  }

  document.addEventListener(
    "click",
    function (e) {
      var img = getEligibleImage(e.target);
      if (!img) {
        return;
      }
      e.preventDefault();
      createOverlay(img);
    },
    true
  );

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", scanAndApplyIcons, {
      once: true,
    });
  } else {
    scanAndApplyIcons();
  }
  window.addEventListener("load", scanAndApplyIcons);
})();
