/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function launchModalAlertBanner(Drupal, drupalSettings, cookieMonster) {
  Drupal.behaviors.launchModalAlertBanner = {
    attach: function attach() {
      var alertId = drupalSettings.localgov_alert_banner_full_page.localgov_full_page_alert_banner_id;
      var lgAlert = document.getElementById(alertId);

      if (lgAlert === null) {
        return;
      }

      if (this.isHiddenAlert(lgAlert)) {
        return;
      }

      if (window.dialogPolyfill) {
        window.dialogPolyfill.registerDialog(lgAlert);
      }

      var cancelButton = document.getElementById("".concat(alertId, "-canceloverlay"));
      cancelButton.addEventListener("click", function closeAlert() {
        lgAlert.close();
      });
      lgAlert.showModal();
    },
    isHiddenAlert: function isHiddenAlert(lgAlert) {
      var cookie = cookieMonster.get("hide-alert-banner-token");
      var cookieTokens = typeof cookie !== "undefined" ? cookie.split("+") : [];
      var dismissToken = lgAlert.getAttribute("data-dismiss-alert-token");
      var isHidden = cookieTokens.includes(dismissToken);
      return isHidden;
    }
  };
})(Drupal, drupalSettings, window.Cookies);