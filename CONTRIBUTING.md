# Contributing

## Expectations

Contributions should be intentional, reviewable, and supported by tests or a clear reproduction.

- Open pull requests only when the change is ready for review.
- Include enough context for reviewers to reproduce and validate bug fixes.
- Update tests when behavior changes.
- Update documentation when commands, configuration, or public package behavior changes.

## Local Checks

Run the full package checks before opening or updating a pull request:

```bash
composer ci
```

This runs:

- `composer lint`
- `composer analyse`
- `composer test`

Use the formatter locally when needed:

```bash
composer format
```

## AI-Assisted Changes

AI-assisted changes are acceptable only when they are thoroughly reviewed, understood, and verified by the contributor.

- Do not submit code you have not personally reviewed.
- Do not open bulk AI-generated pull requests.
- Make sure `composer ci` passes before submission.

## Pull Request Checklist

- Tests were added or updated when needed.
- `composer ci` passes locally.
- Documentation was updated when package usage changed.
- Generated output was not committed unintentionally.

## Branching

- Send bug fixes and backward-compatible improvements to the active development branch.
- Keep breaking changes isolated and clearly documented.

## Releases

- Document user-facing changes in [CHANGELOG.md](/Users/padraig/Projects/threadable/threadstate/CHANGELOG.md).
- Keep release notes focused on public behavior, package requirements, and upgrade concerns.
