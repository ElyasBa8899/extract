ALTER TABLE `general_events`
ADD COLUMN `event_year` INT(4) NULL AFTER `event_name`,
ADD COLUMN `proposal` TEXT COLLATE utf8mb4_persian_ci NULL AFTER `description`,
ADD COLUMN `required_workforce` TEXT COLLATE utf8mb4_persian_ci NULL AFTER `proposal`,
ADD COLUMN `required_budget` DECIMAL(15, 2) NULL AFTER `required_workforce`;
