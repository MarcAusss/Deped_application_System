-- Lets an admin optionally bring in specific evaluator(s) to help on an
-- Admin-evaluated posting (evaluated_by = 'admin'), without opening it to
-- every evaluator and without promoting that account to Admin. The chosen
-- evaluator(s) keep the plain Evaluator role.
-- Run once on every database (local and live) before deploying the matching code.
CREATE TABLE `job_position_assigned_evaluators` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `job_position_id` BIGINT UNSIGNED NOT NULL,
    `evaluator_user_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `job_position_evaluator_unique` (`job_position_id`, `evaluator_user_id`),
    KEY `job_position_assigned_evaluators_evaluator_user_id_foreign` (`evaluator_user_id`),
    CONSTRAINT `job_position_assigned_evaluators_job_position_id_foreign` FOREIGN KEY (`job_position_id`) REFERENCES `job_positions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `job_position_assigned_evaluators_evaluator_user_id_foreign` FOREIGN KEY (`evaluator_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
