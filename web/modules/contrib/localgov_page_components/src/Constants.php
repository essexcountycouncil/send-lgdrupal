<?php

declare(strict_types = 1);

namespace Drupal\localgov_page_components;

/**
 * Constants for this module.
 */
class Constants {

  const PARAGRAPHS_LIB_LIST_MENU_LINK = 'paragraphs_library.paragraphs_library_item.collection';

  const PAGE_COMPONENT_LABEL      = 'Page component';
  const PAGE_COMPONENT_LIST_LABEL = 'Page components';

  const PARAGRAPHS_LIB_ENTITY_TYPE_ID = 'paragraphs_library_item';

  const PARAGRAPHS_LIB_ADD_ACTION = 'entity.paragraphs_library_item.add_form';
  const PAGE_COMPONENT_ADD_ACTION_LABEL = 'Add Page component';

  const PARAGRAPHS_LIB_LISTING_ROUTE = 'entity.paragraphs_library_item.collection';

  const PARAGRAPHS_LIB_LIST_VIEW_ID = 'paragraphs_library';
  const PARAGRAPHS_LIB_LIST_VIEW_DISPLAY_ID = 'page_1';

  // @see Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReferenceBrowserWidget::formElement().
  const ENTITY_BROWSER_LAUNCH_BUTTON = 'entity_browser';
  const PAGE_COMPONENT_FIELD_NAME = 'localgov_page_components';

}
