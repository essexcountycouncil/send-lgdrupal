# Condition Field

Condition field allows the user to add a visibility conditions field to any
entity. These conditions can be used for visibility or other purposes.

- For a full description of the module visit:
  [Project Page](https://www.drupal.org/project/condition_field).

- To submit bug reports and feature suggestions, or to track changes visit:
  [Issue Queue](https://www.drupal.org/project/issues/condition_field).


## Contents of this file

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install the Condition Field module as you would normally install a
contributed Drupal module.
Visit [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules) for further information.


## Configuration

- Enable the module
- Add a Conditions field to an entity
- Use a piece of custom code in a hook to evaluate the conditions

```
Example:

function hook_entity_view(array &$build, EntityInterface $entity,
EntityViewDisplayInterface $display, $view_mode) {
  if ($entity->get('CONDITION_FIELDNAME')->isEmpty()) {
    return TRUE;
  }

  $conditions = [];
  // Single value field.
  $conditions_config = $entity->get('CONDITION_FIELDNAME')->getValue()[0]['conditions'];

  $manager = \Drupal::service('plugin.manager.condition');
  foreach ($conditions_config as $condition_id => $values) {
    /** @var \Drupal\Core\Condition\ConditionInterface $condition */
    $conditions[] = $manager->createInstance($condition_id, $values);
  }

  $isVisible = ConditionAccessResolver::checkAccess($conditions, 'or');

  if (!$isVisible) {
    $build = [];
  }
}
```


## Maintainers

- Antal Ludescher-Tyukodi - [aludescher](https://www.drupal.org/u/aludescher)
- Tamás Pintér - [mr.york](https://www.drupal.org/u/mryork)
- Bálint Nagy - [nagy.balint](https://www.drupal.org/u/nagybalint)
- Lucian Hangea - [lhangea](https://www.drupal.org/u/lhangea)
- olivier.br - [olivier.br](https://www.drupal.org/u/olivierbr)
