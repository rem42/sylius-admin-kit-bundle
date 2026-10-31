---
title: "Active filters are a standalone hookable above the filters panel, not an override of the panel"
author: Rem42
date: 2026-10-31
branch: main
cost: { minutes: 8, tokens: 174486 }
id: l-d79b910d
scope: repo
status: active
src: agent
touches:
  - ".github/workflows/ci.yml"
  - Makefile
  - README.md
  - composer.json
  - docker-compose.yml
  - entrypoint.sh
  - tests/Application/config/packages/framework.yaml
  - tests/Application/importmap.php
---

## Decided
The active filters bar is its own hookable in the grid hook, priority 50 (below the quick filter tabs at 100, above the filters panel). It does not copy/override the filters template of shadcn-admin-ui or bootstrap-admin-ui: a copy would drift from each UI version and the kit supports two UIs plus custom themes.

## Tried and ruled out
- Rendering the chips inside the filters card (as the first app prototype did): needs a full override of the UI filters template, no inner hook exists.
- Rendering between the filters panel and the data table: the UIs register `filters`, `data_table` and `no_data_block` all at priority 0, so no integer priority slots in between (negative goes after the table).

## Watch out
Apps wanting the chips inside their own copy of the filters panel should disable the hookable and call `rem42_admin_kit_active_filters(grid)` from their template.

In the code: src/Rem42SyliusAdminKitBundle.php (prependExtension), templates/*/active_filters/bar.html.twig, docs/active-filters.md
