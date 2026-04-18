(function () {
  'use strict';

  if (typeof window === 'undefined' || typeof document === 'undefined') {
    return;
  }

  var STUB_SELECTOR = '[data-taxonomy-loop-stub="1"]';
  var WIDGET_SELECTOR = '[data-taxonomy-loop-lazy="1"]';
  var observed = typeof WeakSet !== 'undefined' ? new WeakSet() : null;

  function alreadyObserved(node) {
    if (observed) {
      return observed.has(node);
    }
    return node.getAttribute('data-taxonomy-loop-seen') === '1';
  }

  function markObserved(node) {
    if (observed) {
      observed.add(node);
    } else {
      node.setAttribute('data-taxonomy-loop-seen', '1');
    }
  }

  function getConfig(stub) {
    var widget = stub.closest ? stub.closest(WIDGET_SELECTOR) : null;
    if (!widget) {
      return null;
    }
    var raw = widget.getAttribute('data-taxonomy-loop-config');
    if (!raw) {
      return null;
    }
    try {
      return JSON.parse(raw);
    } catch (err) {
      return null;
    }
  }

  function renderError(stub) {
    stub.innerHTML = '<p class="error-message">' +
      'Unable to load posts for this term.' +
      '</p>';
    stub.removeAttribute('aria-busy');
  }

  function triggerElementorHandlers(container) {
    if (!window.elementorFrontend || !window.elementorFrontend.elementsHandler) {
      return;
    }
    var handler = window.elementorFrontend.elementsHandler;
    if (typeof handler.runReadyTrigger !== 'function') {
      return;
    }
    var widgets = container.querySelectorAll('.elementor-widget');
    for (var i = 0; i < widgets.length; i++) {
      try {
        handler.runReadyTrigger(widgets[i]);
      } catch (e) {
        // swallow — widget-level handler errors shouldn't break sibling mounts
      }
    }
  }

  function loadStub(stub) {
    var cfg = getConfig(stub);
    var termId = stub.getAttribute('data-term-id');
    if (!cfg || !termId) {
      stub.innerHTML = '';
      return;
    }

    var body = new FormData();
    body.append('action', cfg.action);
    body.append('nonce', cfg.nonce);
    body.append('post_type', cfg.postType);
    body.append('taxonomy', cfg.taxonomy);
    body.append('term_id', termId);
    body.append('skin', cfg.skin);
    body.append('orderby', cfg.orderby);
    body.append('order', cfg.order);
    body.append('per_term', cfg.perTerm);
    body.append('columns', cfg.columns);
    body.append('cols_t', cfg.cols_t);
    body.append('cols_m', cfg.cols_m);
    body.append('equal', cfg.equal);

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: body
    })
      .then(function (res) {
        return res.ok ? res.json() : null;
      })
      .then(function (data) {
        if (data && data.success && data.data && typeof data.data.html === 'string') {
          stub.innerHTML = data.data.html;
          stub.removeAttribute('data-taxonomy-loop-stub');
          stub.removeAttribute('aria-busy');
          triggerElementorHandlers(stub);
        } else {
          renderError(stub);
        }
      })
      .catch(function () {
        renderError(stub);
      });
  }

  var io = null;

  function ensureObserver() {
    if (io) {
      return io;
    }
    if (typeof IntersectionObserver === 'undefined') {
      return null;
    }
    io = new IntersectionObserver(function (entries) {
      for (var i = 0; i < entries.length; i++) {
        var entry = entries[i];
        if (!entry.isIntersecting) {
          continue;
        }
        io.unobserve(entry.target);
        loadStub(entry.target);
      }
    }, {
      root: null,
      rootMargin: '200px 0px',
      threshold: 0.01
    });
    return io;
  }

  function observeStub(stub) {
    if (alreadyObserved(stub)) {
      return;
    }
    markObserved(stub);
    var observer = ensureObserver();
    if (observer) {
      observer.observe(stub);
    } else {
      // No IntersectionObserver (very old browser): load immediately.
      loadStub(stub);
    }
  }

  function observeStubsIn(root) {
    if (!root || typeof root.querySelectorAll !== 'function') {
      return;
    }
    var stubs = root.querySelectorAll(STUB_SELECTOR);
    for (var i = 0; i < stubs.length; i++) {
      observeStub(stubs[i]);
    }
  }

  function init() {
    observeStubsIn(document);

    // MutationObserver picks up stubs added after initial paint —
    // e.g. Elementor popups, offcanvas drawers that detach/reattach
    // their content, tabs/accordions that inject hidden trees.
    if (typeof MutationObserver === 'undefined' || !document.body) {
      return;
    }
    var mo = new MutationObserver(function (mutations) {
      for (var i = 0; i < mutations.length; i++) {
        var added = mutations[i].addedNodes;
        if (!added || !added.length) {
          continue;
        }
        for (var j = 0; j < added.length; j++) {
          var node = added[j];
          if (!node || node.nodeType !== 1) {
            continue;
          }
          if (node.matches && node.matches(STUB_SELECTOR)) {
            observeStub(node);
          } else {
            observeStubsIn(node);
          }
        }
      }
    });
    mo.observe(document.body, { childList: true, subtree: true });

    // Escape hatch for themes/plugins that inject DOM in ways
    // MutationObserver can't catch (rare, but e.g. shadow DOM).
    window.ElementorTaxonomyLoop = window.ElementorTaxonomyLoop || {};
    window.ElementorTaxonomyLoop.rescan = function (scope) {
      observeStubsIn(scope || document);
    };
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
