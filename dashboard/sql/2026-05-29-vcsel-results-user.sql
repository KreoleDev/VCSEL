ALTER TABLE 2019_prod_7680_vcsel_results
  ADD COLUMN ext_user_id INT NULL AFTER test_id,
  ADD INDEX idx_7680_vcsel_results_ext_user_id (ext_user_id);

-- Optional one-time backfill, only if tester_name values match dashboard usernames:
-- UPDATE 2019_prod_7680_vcsel_results r
-- JOIN core_users u ON LOWER(r.tester_name)=LOWER(u.username)
-- SET r.ext_user_id=u.user_id
-- WHERE r.ext_user_id IS NULL;
