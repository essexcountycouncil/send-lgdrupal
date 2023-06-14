(function (Drupal, drupalSettings) {
  Drupal.behaviors.simplei = {
    attach: function (context, settings) {
      // Classic
      let [toolbar] = once('simplei-toolbar', '#toolbar-administration #toolbar-bar', context);
      if (toolbar) {
        toolbar.style.background = drupalSettings.simplei.background;

        let [toolbar_admin] = once('simplei-toolbar-admin', '#toolbar-item-administration', toolbar);
        if (toolbar_admin) {
          toolbar_admin.textContent += ' (' + drupalSettings.simplei.environment + ')';
          toolbar_admin.style.fontWeight = 'bold';
          toolbar_admin.style.color = drupalSettings.simplei.color;
          toolbar_admin.style.backgroundColor = drupalSettings.simplei.background;
          toolbar_admin.style.backgroundImage = 'linear-gradient(rgba(255, 255, 255, 0.25) 20%, transparent 200%)';
        }
      }

      // Gin admin theme
      let [ginToolbar] = once('simplei-gin-toolbar', '#gin-toolbar-bar #toolbar-item-administration-tray .toolbar-icon-admin-toolbar-tools-help.toolbar-icon', context);
      if (ginToolbar) {
        ginToolbar.classList.remove('toolbar-icon');
        ginToolbar.textContent = drupalSettings.simplei.environment;
        ginToolbar.style.height = '56px';
        ginToolbar.style.textIndent = 'unset';
        ginToolbar.style.paddingLeft = '20px';
        ginToolbar.style.paddingTop ='20px';
        ginToolbar.style.paddingBottom = '20px';
        ginToolbar.style.color = drupalSettings.simplei.color;
        ginToolbar.style.backgroundColor = drupalSettings.simplei.background;
        ginToolbar.style.fontWeight = 'bold';
        ginToolbar.style.fontSize = 'small';
      }
    }
  };
})(Drupal, drupalSettings);
