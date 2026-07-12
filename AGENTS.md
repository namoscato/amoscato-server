# AGENTS.md

This file provides guidance to AI coding agents when working with code in this repository.

## Overview

Symfony 7 (PHP 8.4) application powering the server-side processes behind amoscato.com. It aggregates personal activity from third-party APIs (Goodreads, Untappd, Last.fm, Flickr, GitHub, Vimeo, YouTube) into a Postgres-backed "stream" and a JSON snapshot of "current" activity, both cached to S3. In production, the console commands run on GitHub Actions cron schedules (see `.github/workflows/`).

## Commands

```bash
composer install                 # install dependencies
bin/phpunit                      # run tests (Symfony PHPUnit bridge)
bin/phpunit tests/Source/Stream/GitHubSourceTest.php          # run a single test file
bin/phpunit --filter testMethodName                           # run a single test method
composer cs                      # fix code style (php-cs-fixer: @PSR2 + @Symfony, strict_types)
composer cs -- --dry-run         # check style without fixing (what CI runs)
docker compose up -d             # start local Postgres (db "amoscato", localhost:5432)
```

Console commands (the app's main entry points):

```bash
bin/console amoscato:current:load                # load latest item from each current source → current.json
bin/console amoscato:stream:load [source ...]    # load stream items into Postgres (all sources if none given)
bin/console amoscato:stream:cache [--size=1000]  # aggregate stream from Postgres → stream.json
bin/console amoscato:stream:truncate [--size=1500]  # prune old stream rows
```

Use `-v`/`-vv`/`-vvv` for verbose output (commands write through `OutputDecorator`, which gates debug messages on verbosity).

## Architecture

Two parallel pipelines, both built on tagged Symfony services:

- **Current pipeline** (`src/Source/Current/`): each `CurrentSourceInterface` (`book`, `drink`, `music`, `video`) fetches only the single latest item from its API. `LoadCurrentItemsCommand` collects them into one JSON document and writes `current.json` to storage only if the contents changed.
- **Stream pipeline** (`src/Source/Stream/`): each `StreamSourceInterface` extends `AbstractStreamSource`, which implements a paged extract/transform/load loop into the Postgres `stream` table via raw PDO (`StreamStatementProvider` builds the SQL; there is no ORM). Loading stops when it reaches the most recent `source_id` already in the database. `StreamAggregator` reads rows back and interleaves sources randomly proportional to each source's weight (weights set in `services.yaml`). `CacheStreamCommand` writes the aggregated result to `stream.json`; `StreamController` serves the same aggregation at `GET /stream`.

Supporting layers:

- **Integration clients** (`src/Integration/Client/`): thin wrappers around Guzzle, one per external API, each holding its API key/secret.
- **DI wiring** (`config/services.yaml`): sources are auto-tagged via `_instanceof` on their interfaces and injected as `$currentSources`/`$streamSources` iterables. Non-secret config (API URIs, user IDs, source weights) lives in the `parameters` block; secrets come from `AMOSCATO_*` env vars. New sources are registered just by implementing the interface, plus explicit service config for constructor args.
- **Storage**: `cache.storage` (Flysystem) is S3 in production, local `var/storage/cache/` in dev.

## Testing

Tests use Mockery (`MockeryTestCase`) with mocked clients/PDO — they don't hit real APIs or the database. Third-party API responses are stubbed as `json_decode`d objects. Mirror the existing test structure (`tests/` mirrors `src/`) when adding sources.

## Style

`declare(strict_types=1);` is required in every PHP file (enforced by php-cs-fixer). CI (`ci.yml`) runs PHPUnit and `composer cs -- --dry-run` on every PR.
