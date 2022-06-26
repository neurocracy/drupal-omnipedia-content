This contains the source files for the "*Omnipedia - Content*" Drupal module,
which provides content-related functionality for
[Omnipedia](https://omnipedia.app/).

⚠️⚠️⚠️ ***Here be potential spoilers. Proceed at your own risk.*** ⚠️⚠️⚠️

----

# Why open source?

We're dismayed by how much knowledge and technology is kept under lock and key
in the videogame industry, with years of work often never seeing the light of
day when projects are cancelled. We've gotten to where we are by building upon
the work of countless others, and we want to keep that going. We hope that some
part of this codebase is useful or will inspire someone out there.

----

# Requirements

* [Drupal 9](https://www.drupal.org/download) ([Drupal 8 is end-of-life](https://www.drupal.org/psa-2021-11-30))

* [Composer](https://getcomposer.org/)

----

# Installation

Ensure that you have your Drupal installation set up with the correct Composer
installer types such as those provided by [the ```drupal\recommended-project```
template](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates#s-drupalrecommended-project).
If you're starting from scratch, simply requiring that template and following
[the Drupal.org Composer
documentation](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates)
should get you up and running.

Then, in your root ```composer.json```, add the following to the
```"repositories"``` section:

```
{
  "type": "vcs",
  "url": "https://github.com/neurocracy/drupal-omnipedia-content.git"
}
```

Then, in your project's root, run ```composer require
"drupal/omnipedia_content:3.x-dev@dev"``` to have Composer install the module
and its required dependencies for you.

----

# Description

This contains our content infrastructure that provides custom HTML elements for
wiki content that gets rendered as standard HTML, and many alterations built on
top of Markdown for our specific use-cases.

Note that this module is planned to be broken up into multiple modules that
better encapsulate and separate systems; see [the Planned improvements
section](#planned-improvements).

----

# Planned improvements

* [Move Omnipedia element plug-in infrastructure to new `omnipedia_element` module](https://github.com/neurocracy/drupal-omnipedia-content/issues/3)

* [Move all Markdown functionality to new `omnipedia_markdown` module](https://github.com/neurocracy/drupal-omnipedia-content/issues/4)
