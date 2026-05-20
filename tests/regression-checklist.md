# Ocellaris Regression Checklist

Use this checklist before each production deploy.

## Automated Gates

- [ ] Native suite passes: `bash tests/run-all-tests.sh`
- [ ] Local HTTP smoke passes (against real WP): `SITE_URL='https://your-site.local' bash tests/run-http-smoke.sh`
- [ ] WP runtime checks pass: `WP_PATH='/var/www/html' bash tests/run-wp-runtime-checks.sh`
- [ ] PR CI is green in `Native Theme Tests`

## Critical Business Flows

- [ ] Catalog filter by category returns expected products.
- [ ] Catalog filter by brand returns expected products.
- [ ] Product detail page renders price and add-to-cart button.
- [ ] Featured Products block shows only in-stock products.
- [ ] Cart updates quantity and totals correctly.
- [ ] Checkout can be completed with enabled shipping/payment methods.
- [ ] My Account loads order history without PHP errors.
- [ ] MSI labels appear only on configured products.

## Admin and Configuration

- [ ] Ocellaris menu renders Dashboard, MSI, Text Bar, Documentation, Health Check.
- [ ] Text Bar saves and reflects frontend changes.
- [ ] MSI admin page saves products/months configuration.
- [ ] Health Check does not report regressions.

## Visual and Responsive

- [ ] Header and sidebar menu work on mobile and desktop.
- [ ] Footer payment icons render correctly.
- [ ] Featured Products carousel behaves correctly with varying `productsToShow`.
- [ ] No visible layout breakages in shop, cart, checkout, and account pages.

## Release Notes

- [ ] README updated when behavior/contracts changed.
- [ ] Commit messages clearly describe functional impact.
