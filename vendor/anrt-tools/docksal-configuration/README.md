# Docksal hosting configuration

## Steps to add docksal config to a project

### Update composer.json

- Add to repositories section
- Post-install-cmd and post-update-cmd

#### Add to project `composer.json` repositories section
```json
"repositories": [
   {
        "type": "composer",
        "url": "https://code.anner.ie/api/v4/group/6/-/packages/composer/packages.json"
    }
]
```

#### Add to project `composer.json` config section
```json
"config": {
    "gitlab-domains": ["code.anner.ie"]
}
```

#### Post install & update commands

Add post-update-cmd to project's `composer.json` scripts section,  
or copy entire snippet if no scripts section already.

```json
"scripts": {
    "post-install-cmd": [
        "Anrt\\Tools\\DocksalConfiguration\\Scripts::postUpdate"
    ],
    "post-update-cmd": [
        "Anrt\\Tools\\DocksalConfiguration\\Scripts::postUpdate"
    ]
}
```

### Install on project

1. Run `(fin) composer require anrt-tools/docksal-configuration:master-dev` (use `7.x-dev` for D7 sites)
2. Update (or create) `docksal.env/yml` using `example.docksal.env/yml` files
3. Check variables defined (e.g. `VIRTUAL_HOST`) & check docksal runs.
4. If project specific Drupal settings/config overrides required:
    * Rename `.docksal/templates/example.settings.project.php` as per file instructions
    * Add any settings or overrides.
    * Run `fin init` or `fin local-config (--no-cache)`
