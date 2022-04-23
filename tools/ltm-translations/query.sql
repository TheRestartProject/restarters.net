
-- ** FOR EXPORT/REVIEW AND TESTING **
-- DROP TABLE IF EXISTS `ltm_translations_todo`;
-- CREATE TABLE `ltm_translations_todo` (
--   `group` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
--   `key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
--   `en` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
--   `fk` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
--   `lang` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
--   `alias` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
--   `trans` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
-- ) ENGINE=InnoDB;

SELECT t1.*,
'' as trans FROM (
	SELECT
	en.`group`,
	en.`key`,
	en.`value` as en,
	fk.`value` as fk,
	'fr' as 'lang',
	'fr' as 'alias'
	FROM `ltm_translations` en
	LEFT OUTER JOIN `ltm_translations` fk  ON fk.`group` = en.`group` AND fk.`key` = en.`key` AND fk.`locale` = 'fr'
	WHERE en.`locale` = 'en'
	AND en.`value` = fk.`value`
	UNION
	SELECT
	en.`group`,
	en.`key`,
	en.`value` as en,
	fk.`value` as fk,
	'fr-BE' as 'lang',
	'fr' as 'alias'
	FROM `ltm_translations` en
	LEFT OUTER JOIN `ltm_translations` fk  ON fk.`group` = en.`group` AND fk.`key` = en.`key` AND fk.`locale` = 'fr-BE'
	WHERE en.`locale` = 'en'
	AND en.`value` = fk.`value`
	UNION
	SELECT
	en.`group`,
	en.`key`,
	en.`value` as en,
	fk.`value` as fk,
	'de' as 'lang',
	'de' as 'alias'
	FROM `ltm_translations` en
	LEFT OUTER JOIN `ltm_translations` fk  ON fk.`group` = en.`group` AND fk.`key` = en.`key` AND fk.`locale` = 'de'
	WHERE en.`locale` = 'en'
	AND en.`value` = fk.`value`
	UNION
	SELECT
	en.`group`,
	en.`key`,
	en.`value` as en,
	fk.`value` as fk,
	'nl' as 'lang',
	'nl' as 'alias'
	FROM `ltm_translations` en
	LEFT OUTER JOIN `ltm_translations` fk  ON fk.`group` = en.`group` AND fk.`key` = en.`key` AND fk.`locale` = 'nl'
	WHERE en.`locale` = 'en'
	AND en.`value` = fk.`value`
	UNION
	SELECT
	en.`group`,
	en.`key`,
	en.`value` as en,
	fk.`value` as fk,
	'nl-BE' as 'lang',
	'nl' as 'alias'
	FROM `ltm_translations` en
	LEFT OUTER JOIN `ltm_translations` fk  ON fk.`group` = en.`group` AND fk.`key` = en.`key` AND fk.`locale` = 'nl'
	WHERE en.`locale` = 'en'
	AND en.`value` = fk.`value`
	UNION
	SELECT
	en.`group`,
	en.`key`,
	en.`value` as en,
	fk.`value` as fk,
	'it' as 'lang',
	'it' as 'alias'
	FROM `ltm_translations` en
	LEFT OUTER JOIN `ltm_translations` fk  ON fk.`group` = en.`group` AND fk.`key` = en.`key` AND fk.`locale` = 'it'
	WHERE en.`locale` = 'en'
	AND en.`value` = fk.`value`
	UNION
	SELECT
	en.`group`,
	en.`key`,
	en.`value` as en,
	fk.`value` as fk,
	'es' as 'lang',
	'es' as 'alias'
	FROM `ltm_translations` en
	LEFT OUTER JOIN `ltm_translations` fk  ON fk.`group` = en.`group` AND fk.`key` = en.`key` AND fk.`locale` = 'es'
	WHERE en.`locale` = 'en'
	AND en.`value` = fk.`value`
) t1
ORDER BY t1.lang, t1.`group`, t1.`key`;