This contains the source files for the "*Omnipedia - Content*" Drupal module,
which provides content-related functionality for
[Omnipedia](https://omnipedia.app/).

⚠️ ***[Why open source? / Spoiler warning](https://omnipedia.app/open-source)***

----

# Description

This contains our content infrastructure that provides custom HTML elements for
wiki content which gets rendered as standard HTML, and many alterations built on
top of [CommonMark](https://commonmark.thephpleague.com/) for our specific
use-cases.

----

# Requirements

* [Drupal 10](https://www.drupal.org/download)

* PHP 8.1

* [Composer](https://getcomposer.org/)

## Drupal dependencies

Before attempting to install this, you must add the Composer repositories as
described in the installation instructions for these dependencies:

* The [`ambientimpact_core`](https://github.com/Ambient-Impact/drupal-ambientimpact-core), [`ambientimpact_markdown`](https://github.com/Ambient-Impact/drupal-ambientimpact-markdown), and [`ambientimpact_ux`](https://github.com/Ambient-Impact/drupal-ambientimpact-ux) modules.

* The [`omnipedia_core`](https://github.com/neurocracy/drupal-omnipedia-core) and [`omnipedia_date`](https://github.com/neurocracy/drupal-omnipedia-date) modules.

## Front-end dependencies

To build front-end assets for this project, [Node.js](https://nodejs.org/) and
[Yarn](https://yarnpkg.com/) are required.

----

# Installation

## Composer

### Set up

Ensure that you have your Drupal installation set up with the correct Composer
installer types such as those provided by [the `drupal/recommended-project`
template](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates#s-drupalrecommended-project).
If you're starting from scratch, simply requiring that template and following
[the Drupal.org Composer
documentation](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates)
should get you up and running.

### Repository

In your root `composer.json`, add the following to the `"repositories"` section:

```json
"drupal/omnipedia_content": {
  "type": "vcs",
  "url": "https://github.com/neurocracy/drupal-omnipedia-content.git"
}
```

### Installing

Once you've completed all of the above, run `composer require
"drupal/omnipedia_content:^6.0@dev"` in the root of your project to have
Composer install this and its required dependencies for you.

## Front-end assets

To build front-end assets for this project, you'll need to install
[Node.js](https://nodejs.org/) and [Yarn](https://yarnpkg.com/).

This package makes use of [Yarn
Workspaces](https://yarnpkg.com/features/workspaces) and references other local
workspace dependencies. In the `package.json` in the root of your Drupal
project, you'll need to add the following:

```json
"workspaces": [
  "<web directory>/modules/custom/*"
],
```

where `<web directory>` is your public Drupal directory name, `web` by default.
Once those are defined, add the following to the `"dependencies"` section of
your top-level `package.json`:

```json
"drupal-omnipedia-content": "workspace:^6"
```

Then run `yarn install` and let Yarn do the rest.

### Optional: install yarn.BUILD

While not required, [yarn.BUILD](https://yarn.build/) is recommended to make
building all of the front-end assets even easier.

----

# Building front-end assets

This uses [Webpack](https://webpack.js.org/) and [Symfony Webpack
Encore](https://symfony.com/doc/current/frontend.html) to automate most of the
build process. These will have been installed for you if you followed the Yarn
installation instructions above.

If you have [yarn.BUILD](https://yarn.build/) installed, you can run:

```
yarn build
```

from the root of your Drupal site. If you want to build just this package, run:

```
yarn workspace drupal-omnipedia-content run build
```

----

# Major breaking changes

The following major version bumps indicate breaking changes:

* 4.x - Front-end package manager is now [Yarn](https://yarnpkg.com/); front-end build process ported to [Webpack](https://webpack.js.org/).

* 5.x:

  * Requires Drupal 9.5; includes backward compatible [Drupal 10](https://www.drupal.org/project/drupal/releases/10.0.0) deprecation fixes but is still not fully compatible.

  * Increases minimum version of [Hook Event Dispatcher](https://www.drupal.org/project/hook_event_dispatcher) to 3.1, removes deprecated code, and adds support for 4.0 which supports Drupal 10.

  * Removes the `omnipedia_content_legacy` module; you can still find it in the 4.x branch.

* 6.x:

  * Requires [Drupal 10](https://www.drupal.org/project/drupal/releases/10.0.0) due to non-backwards compatible change to [`\Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher::dispatch()`](https://git.drupalcode.org/project/drupal/-/commit/7b324dd8f18919fc4d728bdb0afbcf27c8c02cb2#6e9d627c11801448b7a793c204471d8f951ae2fb).

  * Removes PHP 7.4 support; Drupal 10 only supports PHP 8.

  * Requires [`drupal/ambientimpact_core` 2.x](https://github.com/Ambient-Impact/drupal-ambientimpact-core/tree/2.x) for Drupal 10 support.

  * Requires [`drupal/ambientimpact_markdown` 2.x](https://github.com/Ambient-Impact/drupal-ambientimpact-markdown/tree/2.x) for Drupal 10 support.
