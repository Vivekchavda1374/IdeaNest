-- Migration script to update classification fields to use requested_domains
-- Run this script on your existing database to migrate from old structure to new structure

-- Step 1: Add domains column to subadmins table if it doesn't exist
ALTER TABLE subadmins ADD COLUMN IF NOT EXISTS domains TEXT NULL AFTER domain;

-- Step 2: Add requested_domains column to subadmin_classification_requests table if it doesn't exist
ALTER TABLE subadmin_classification_requests ADD COLUMN IF NOT EXISTS requested_domains TEXT NULL AFTER subadmin_id;

-- Step 3: Migrate existing data (optional - only if you have existing classification data to preserve)
-- This will combine software_classification and hardware_classification into domains
UPDATE subadmins 
SET domains = CONCAT_WS(',', 
    CASE WHEN software_classification IS NOT NULL AND software_classification != '' THEN software_classification END,
    CASE WHEN hardware_classification IS NOT NULL AND hardware_classification != '' THEN hardware_classification END
)
WHERE (software_classification IS NOT NULL AND software_classification != '') 
   OR (hardware_classification IS NOT NULL AND hardware_classification != '');

-- Step 4: Migrate existing classification requests (optional)
UPDATE subadmin_classification_requests 
SET requested_domains = CONCAT_WS(',', 
    CASE WHEN requested_software_classification IS NOT NULL AND requested_software_classification != '' THEN requested_software_classification END,
    CASE WHEN requested_hardware_classification IS NOT NULL AND requested_hardware_classification != '' THEN requested_hardware_classification END
)
WHERE (requested_software_classification IS NOT NULL AND requested_software_classification != '') 
   OR (requested_hardware_classification IS NOT NULL AND requested_hardware_classification != '');

-- Step 5: Remove old columns (uncomment these lines after confirming the migration worked correctly)
-- ALTER TABLE subadmins DROP COLUMN software_classification;
-- ALTER TABLE subadmins DROP COLUMN hardware_classification;
-- ALTER TABLE subadmin_classification_requests DROP COLUMN requested_software_classification;
-- ALTER TABLE subadmin_classification_requests DROP COLUMN requested_hardware_classification;

-- Note: Keep the old columns commented out until you've tested the new system thoroughly
-- You can remove them later once you're confident everything is working correctly
