/**
 * Fills in Taxonomy Loop terms that were deferred past first paint.
 *
 * Each stub asks the server for its own term as it scrolls into view. The request
 * carries only the document ID, the widget's element ID and the term ID — every
 * other setting is re-read server-side from the saved widget.
 */
(function () {
	'use strict';

	var WIDGET_SELECTOR = '[data-taxonomy-loop-lazy="1"]';
	var STUB_SELECTOR = '[data-taxonomy-loop-stub="1"]';
	var ROOT_MARGIN = '200px 0px';

	var settings = window.ElementorTaxonomyLoop || {};

	function errorMessage() {
		return ( settings.i18n && settings.i18n.error ) || 'Unable to load posts for this term.';
	}

	function renderError( stub ) {
		var paragraph = document.createElement( 'p' );

		paragraph.className = 'error-message';
		paragraph.textContent = errorMessage();

		stub.innerHTML = '';
		stub.appendChild( paragraph );
		stub.removeAttribute( 'data-taxonomy-loop-stub' );
		stub.removeAttribute( 'aria-busy' );
	}

	/**
	 * Boot the Elementor frontend handlers for widgets that were just injected.
	 *
	 * @param {Element} container Element holding the new markup.
	 */
	function initElementorWidgets( container ) {
		var handler = window.elementorFrontend && window.elementorFrontend.elementsHandler;

		if ( ! handler || 'function' !== typeof handler.runReadyTrigger ) {
			return;
		}

		var widgets = container.querySelectorAll( '.elementor-widget' );

		for ( var i = 0; i < widgets.length; i++ ) {
			try {
				handler.runReadyTrigger( widgets[ i ] );
			} catch ( error ) {
				// A single widget handler failing must not stop its siblings mounting.
			}
		}
	}

	function loadStub( stub ) {
		var widget = stub.closest( WIDGET_SELECTOR );
		var termId = stub.getAttribute( 'data-term-id' );

		if ( ! widget || ! termId || ! settings.ajaxUrl || ! settings.action ) {
			renderError( stub );
			return;
		}

		var body = new URLSearchParams();

		body.append( 'action', settings.action );
		body.append( 'post_id', widget.getAttribute( 'data-post-id' ) || '' );
		body.append( 'element_id', widget.getAttribute( 'data-element-id' ) || '' );
		body.append( 'term_id', termId );

		fetch( settings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} )
			.then( function ( response ) {
				return response.ok ? response.json() : null;
			} )
			.then( function ( payload ) {
				if ( ! payload || ! payload.success || ! payload.data || 'string' !== typeof payload.data.html ) {
					renderError( stub );
					return;
				}

				stub.innerHTML = payload.data.html;
				stub.removeAttribute( 'data-taxonomy-loop-stub' );
				stub.removeAttribute( 'aria-busy' );
				initElementorWidgets( stub );
			} )
			.catch( function () {
				renderError( stub );
			} );
	}

	function init() {
		var stubs = document.querySelectorAll( STUB_SELECTOR );

		if ( ! stubs.length ) {
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				for ( var i = 0; i < entries.length; i++ ) {
					if ( ! entries[ i ].isIntersecting ) {
						continue;
					}

					observer.unobserve( entries[ i ].target );
					loadStub( entries[ i ].target );
				}
			},
			{
				root: null,
				rootMargin: ROOT_MARGIN,
				threshold: 0.01
			}
		);

		for ( var i = 0; i < stubs.length; i++ ) {
			observer.observe( stubs[ i ] );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
