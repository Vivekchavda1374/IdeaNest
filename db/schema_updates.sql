-- Database Schema Updates for Form Field Standardization

-- Ensure all required columns exist in register table
ALTER TABLE register ADD COLUMN IF NOT EXISTS name VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE register ADD COLUMN IF NOT EXISTS email VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE register ADD COLUMN IF NOT EXISTS enrollment_number VARCHAR(100) NULL;

-- Update projects table for standardized field names
ALTER TABLE projects ADD COLUMN IF NOT EXISTS title VARCHAR(255) NULL;
UPDATE projects SET title = project_name WHERE title IS NULL OR title = '';

-- Update blog/ideas table for standardized field names  
ALTER TABLE blog ADD COLUMN IF NOT EXISTS title VARCHAR(255) NULL;
ALTER TABLE blog ADD COLUMN IF NOT EXISTS content TEXT NULL;
UPDATE blog SET title = project_name WHERE title IS NULL OR title = '';
UPDATE blog SET content = description WHERE content IS NULL OR content = '';

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_register_email ON register(email);
CREATE INDEX IF NOT EXISTS idx_register_enrollment ON register(enrollment_number);
CREATE INDEX IF NOT EXISTS idx_projects_title ON projects(title);
CREATE INDEX IF NOT EXISTS idx_blog_title ON blog(title);

-- Ensure proper constraints
ALTER TABLE register ADD CONSTRAINT IF NOT EXISTS unique_email UNIQUE (email);
