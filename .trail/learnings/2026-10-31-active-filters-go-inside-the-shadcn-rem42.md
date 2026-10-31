---
title: "Active filters go inside the shadcn-admin-ui filters panel through its own hooks, a bar elsewhere"
author: Rem42
date: 2026-10-31
branch: main
cost: { minutes: 4, tokens: 47747 }
id: l-3a477e3d
scope: repo
status: active
replaces: l-d79b910d
src: agent
touches:
  - README.md
  - composer.json
  - config/services/active_filters.php
  - docs/README.md
  - docs/active-filters.md
  - docs/configuration.md
  - docs/themes.md
  - src/ActiveFilters/CriterionFormatter.php
  - src/ActiveFilters/Twig/ActiveFiltersExtension.php
  - src/Rem42SyliusAdminKitBundle.php
---

## Decided
With shadcn-admin-ui the active filters render inside its filters panel, like the first app prototype: the count in the trigger (`sylius_admin.common.index.content.grid.filters#trigger`), the chips between the trigger and the form (`...filters#summary`). shadcn-admin-ui got these two hooks plus `open` / `indicator` configuration on its `filters` hookable for this; the kit sets `open: false, indicator: false`. Other admin UIs (bootstrap) keep a standalone bar in the grid hook, priority 50. The choice depends on whether Rem42ShadcnAdminUiBundle is loaded, not on the kit theme.

## Tried and ruled out
- Copying/overriding the UI filters template from the kit: the UI hooks come from `config/app.php` imported by the app, which loads after the kit's prepended config, so the UI's `template` always wins.
- Disabling the UI `filters` hookable from the kit (`enabled: false`, prepended): twig-hooks computes `type` per config chunk in beforeNormalization, the UI chunk's `type: template` wins and `enabled` is ignored. Keys the UI chunk doesn't set (`configuration`, `condition`) do survive the merge, which is why the kit drives the panel through `configuration`.
- A bar between the filters panel and the table: every UI hookable there is priority 0, nothing slots in between.

## Watch out
The shadcn panel hooks need a shadcn-admin-ui release containing them; with an older one the hookables are never rendered and nothing fails. In functional tests, select the panel with `[data-slot="card"][data-controller="shadcn--collapsible"]`: the sidebar menu is a collapsible too.

In the code: src/Rem42SyliusAdminKitBundle.php (activeFiltersHooks), templates/shadcn/active_filters/{count,summary}.html.twig, templates/bootstrap/active_filters/bar.html.twig, shadcn-admin-ui templates/shared/crud/index/content/grid/filters.html.twig
