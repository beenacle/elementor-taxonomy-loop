(function () {
  'use strict';

  var STUB_SELECTOR = '[data-taxonomy-loop-stub="1"]';
  var WIDGET_SELECTOR = '[data-taxonomy-loop-lazy="1"]';

  function getConfig(stub) {
    var widget = stub.closest(WIDGET_SELECTOR);
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
    var l10n = window.ElementorTaxonomyLoopL10n || {};
    var p = document.createElement('p');
    p.className = 'error-message';
    p.textContent = l10n.errorMessage || 'Unable to load posts for this term.';
    stub.innerHTML = '';
    stub.appendChild(p);
    stub.removeAttribute('aria-busy');
  }

  function triggerElementorHandlers(container) {
    var handler = window.elementorFrontend && window.elementorFrontend.elementsHandler;
    if (!handler || typeof handler.runReadyTrigger !== 'function') {
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
      .then(function (res) { return res.ok ? res.json() : null; })
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
      .catch(function () { renderError(stub); });
  }

  function init() {
    var stubs = document.querySelectorAll(STUB_SELECTOR);
    if (!stubs.length) {
      return;
    }

    var io = new IntersectionObserver(function (entries) {
      for (var j = 0; j < entries.length; j++) {
        var entry = entries[j];
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

    for (var k = 0; k < stubs.length; k++) {
      io.observe(stubs[k]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
