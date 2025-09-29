-- Create Organizations table for dynamic organization management
-- This replaces the hardcoded ENUM approach with a flexible system

CREATE TABLE IF NOT EXISTS Organizations (
    organization_id INT AUTO_INCREMENT PRIMARY KEY,
    organization_code VARCHAR(10) NOT NULL UNIQUE,
    organization_name VARCHAR(255) NOT NULL,
    full_name VARCHAR(500) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    color_theme VARCHAR(20) DEFAULT 'blue', -- For UI theming
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert initial organizations (SSCT and SAES)
INSERT INTO Organizations (organization_code, organization_name, full_name, description, color_theme) VALUES
('SSCT', 'SSCT', 'Sarvajana Sneha Charitable Trust', 'Primary charitable trust organization', 'blue'),
('SAES', 'SAES', 'St. Ann\'s Education Society', 'Educational society organization', 'pink');

-- Update Projects table to reference Organizations table
-- First, add the new organization_id column
ALTER TABLE Projects 
ADD COLUMN organization_id INT AFTER project_center;

-- Add foreign key constraint
ALTER TABLE Projects 
ADD CONSTRAINT fk_projects_organization 
FOREIGN KEY (organization_id) REFERENCES Organizations(organization_id);

-- Migrate existing data (assuming existing projects are SSCT by default)
UPDATE Projects 
SET organization_id = (SELECT organization_id FROM Organizations WHERE organization_code = 'SSCT')
WHERE organization_id IS NULL;

-- Make organization_id NOT NULL after data migration
ALTER TABLE Projects 
MODIFY COLUMN organization_id INT NOT NULL;

-- Drop the old organization ENUM column
ALTER TABLE Projects 
DROP COLUMN organization;

-- Add index for better performance
CREATE INDEX idx_projects_organization_id ON Projects(organization_id);

-- Add comment to document the new structure
ALTER TABLE Organizations 
MODIFY COLUMN organization_code VARCHAR(10) NOT NULL UNIQUE 
COMMENT 'Short code for organization (e.g., SSCT, SAES)';

ALTER TABLE Organizations 
MODIFY COLUMN organization_name VARCHAR(255) NOT NULL 
COMMENT 'Display name for organization';

ALTER TABLE Organizations 
MODIFY COLUMN full_name VARCHAR(500) NOT NULL 
COMMENT 'Full legal name of the organization';

ALTER TABLE Organizations 
MODIFY COLUMN color_theme VARCHAR(20) DEFAULT 'blue' 
COMMENT 'Color theme for UI display (blue, pink, green, etc.)';
