# Filament Dashboard UI Update

This patch modernizes the Admin and Evaluator dashboards without changing the
database, authentication rules, application workflow, or IER export.

## Included

- Shared Filament v5 theme registered through Vite
- New DepEd Recruitment branding for both panels
- Modern light sidebar and formal indigo/teal visual identity
- Responsive Admin recruitment command center
- Responsive Evaluator workspace and personal workload summary
- Integrated evaluator/admin scorecards with icon tiles and compact metric cards
- Redesigned statistics, charts, recent applications, and recent evaluations
- Correct applicant profile fields in the Admin recent-applications widget

## Install

Extract this ZIP directly into the Laravel project root and replace matching
files. Then run:

```bash
npm run build
php artisan optimize:clear
```

No migration or database seeding is required for this dashboard update.
