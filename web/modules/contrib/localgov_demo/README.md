# LocalGov Drupal demonstration module

Example content for demonstrating the LocalGov Drupal distribution and to help
with development.

## Updating and adding content

To update default content already included in the module simply run:

```bash
drush dcem localgov_demo
```

To add new content add entity UUIDs to the `localgov_demo.info.yml` file and
export the content as above. Details on how to find entity UUIDs can be found
here:
<https://www.drupal.org/docs/8/modules/default-content-for-d8/defining-default-content> \
(Hint: use Devel).

Or

Export content and all references with:

```bash
lando drush dcer <entity type> <entity id> --folder=modules/contrib/localgov_demo/content/
```

Notes:

1. The --folder definition is relative to the web root.
2. There is no slash at the start of the path, it is --folder=modules/contrib...
3. You should delete the `localgov_demo/content/user` directory before
committing code if using this method as it will include users.
4. You should also add the new UUIDs to the `localgov_demo.info.yml` file.
