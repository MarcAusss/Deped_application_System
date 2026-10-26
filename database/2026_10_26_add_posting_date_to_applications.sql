-- The posting an application was submitted to: its JP number and posting
-- date at the time of applying. Lists show one table per JP number, so
-- re-opening a posting (same JP number) keeps its applications together,
-- while a new posting of the same position (new JP number) gets a new table.
-- Run once on every database (local and live) before deploying the matching code.
ALTER TABLE `applications`
    ADD COLUMN `jp_number` VARCHAR(255) NULL DEFAULT NULL AFTER `job_position_id`,
    ADD COLUMN `posting_date` DATE NULL DEFAULT NULL AFTER `jp_number`;

-- Backfill: until now a position kept a single JP number forever, so every
-- existing application belongs to its position's current posting.
UPDATE `applications` a
JOIN `job_positions` jp ON jp.id = a.job_position_id
SET a.jp_number = jp.jp_number,
    a.posting_date = jp.posted_at;
