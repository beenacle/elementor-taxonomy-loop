window.addEventListener("elementor/frontend/init", () => {
  // console.log("Elementor is fully loaded!");
  if (typeof elementorFrontendConfig === "undefined") {
    console.error("elementorFrontendConfig is not loaded!");
    return;
  }

  if (typeof elementorModules === "undefined" || typeof elementorModules.frontend === "undefined" || typeof elementorModules.frontend.handlers === "undefined") {
    console.error("Elementor frontend handlers are not loaded!");
    return;
  }
  class EpTaxanomyLoopHandler extends elementorModules.frontend.handlers.Base {
    onInit() {
      super.onInit(); // Ensure the parent class initializes first
      this.initTabs();
    }

    initTabs() {
      // console.log(this);
    }
  }
  elementorFrontend.hooks.addAction("frontend/element_ready/ep_taxanomy_loop.default", function ($element) {
    // Initialize your handler
    elementorFrontend.elementsHandler.addHandler(EpTaxanomyLoopHandler, { $element });

    //Modify buttons in the widget
    elementorFrontend.hooks.addAction("frontend/element_ready/ep-taxanomy-loop-item.default", function ($buttonScope, $) {
      if (!$buttonScope.closest($element).length) return;
      let ep_taxanomy_loop_item = $buttonScope[0];

      const settings = JSON.parse(ep_taxanomy_loop_item.getAttribute("data-settings"));

      let linkContainer = $buttonScope.find(".off-canvas-link").toArray();
      linkContainer.forEach((element) => {
        console.log(element);
        let link = element.querySelector("a");
        link.setAttribute("href", settings.actionlink);
      });

      let canvasCloseButton = $buttonScope.find(".e-off-canvas__content > .off-canvas-link").toArray();
      console.log(canvasCloseButton);

      canvasCloseButton.forEach((element) => {
        let link = element.querySelector("a");
        link.setAttribute("href", settings.actionlink);
      });

      let offcanvasImage = $buttonScope.find(".elementor-widget-image a");
      let offcanvasButton = $buttonScope.find(".elementor-widget-button a");
      let offcanvasWidget = $buttonScope.find(".elementor-widget-off-canvas .e-off-canvas");
      offcanvasImage.attr("href", settings.actionlink);
      offcanvasButton.attr("href", settings.actionlink);
      offcanvasWidget.attr("id", settings.uniqueid);
    });
  });
});

href = "#elementor-action%3Aaction%3Doff_canvas%3Aclose%26settings%3DeyJpZCI6ImNmNTlmZDAiLCJkaXNwbGF5TW9kZSI6ImNsb3NlIn0%3D";
href = "#elementor-action%3Aaction%3Doff_canvas%3Atoggle%26settings%3DeyJpZCI6Im9mZmNhbnZhcy01MTE1IiwiZGlzcGxheU1vZGUiOiJ0b2dnbGUifQ%3D%3D";
