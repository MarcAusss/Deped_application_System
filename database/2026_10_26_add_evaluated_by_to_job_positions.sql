-- Who evaluates applications for a job posting.
--   'evaluator' (default): the evaluator portal handles evaluation; admins still see everything.
--   'admin': the admin portal evaluates; these applications are hidden from evaluator accounts.
-- Run once on every database (local and live) before deploying the matching code.
ALTER TABLE `job_positions`
    ADD COLUMN `evaluated_by` ENUM('evaluator','admin') NOT NULL DEFAULT 'evaluator' AFTER `csc_publication_paths`;
