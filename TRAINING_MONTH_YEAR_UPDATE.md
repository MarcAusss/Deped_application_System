# Training Month and Year Update

This patch replaces the free-text training details input with a month-and-year
training date.

## Changes

- Public application form uses a native month picker.
- Future months are blocked in the browser and server validation.
- Training month is stored safely in `applicant_trainings.training_date`.
- Admin and Evaluator training relation managers display and edit Month/Year.
- IER training titles include the selected month and year.
- The old `details` database column is retained to avoid deleting historical
  content, but it is no longer used by the training forms.

## Install

Extract this ZIP directly into the Laravel project root and replace matching
files. Then run:

```bash
php artisan migrate
php artisan optimize:clear
npm run build
```

Do not run `migrate:fresh` and do not reseed an existing database.
