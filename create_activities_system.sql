-- Create Activities table for project activity management
CREATE TABLE IF NOT EXISTS Activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    activity_title VARCHAR(255) NOT NULL,
    funding_source ENUM('Project', 'Donations', 'Support from collaborator', 'Center funded', 'Congregation funded', 'Organisation funded', 'Others') NOT NULL,
    funding_source_other VARCHAR(255) NULL,
    organization_id INT NULL,
    project_id INT NULL,
    activity_date DATE NOT NULL,
    place VARCHAR(255) NOT NULL,
    conducted_for VARCHAR(255) NOT NULL,
    number_of_participants INT NOT NULL,
    is_collaboration BOOLEAN DEFAULT FALSE,
    collaborator_organization VARCHAR(255) NULL,
    collaborator_name VARCHAR(255) NULL,
    collaborator_position VARCHAR(255) NULL,
    immediate_outcome TEXT NOT NULL,
    long_term_impact TEXT NOT NULL,
    status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
    created_by INT NOT NULL,
    submitted_at TIMESTAMP NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (organization_id) REFERENCES Organizations(organization_id) ON DELETE SET NULL,
    FOREIGN KEY (project_id) REFERENCES Projects(project_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES ssmntUsers(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES ssmntUsers(id) ON DELETE SET NULL
);

-- Create ActivityAttachments table for photo uploads
CREATE TABLE IF NOT EXISTS ActivityAttachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (activity_id) REFERENCES Activities(activity_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES ssmntUsers(id) ON DELETE CASCADE
);

-- Create ActivityEditRequests table for approval workflow
CREATE TABLE IF NOT EXISTS ActivityEditRequests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    requested_by INT NOT NULL,
    request_type ENUM('edit', 'deactivate') DEFAULT 'edit',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    
    -- Original activity data (for comparison)
    original_data JSON,
    
    -- Requested changes
    requested_changes JSON,
    
    -- Approval details
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    
    -- Timestamps
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (activity_id) REFERENCES Activities(activity_id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES ssmntUsers(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES ssmntUsers(id) ON DELETE SET NULL
);

-- Add indexes for better performance
CREATE INDEX idx_activities_created_by ON Activities(created_by);
CREATE INDEX idx_activities_status ON Activities(status);
CREATE INDEX idx_activities_activity_date ON Activities(activity_date);
CREATE INDEX idx_activities_project_id ON Activities(project_id);
CREATE INDEX idx_activities_organization_id ON Activities(organization_id);

CREATE INDEX idx_activity_attachments_activity_id ON ActivityAttachments(activity_id);
CREATE INDEX idx_activity_attachments_uploaded_by ON ActivityAttachments(uploaded_by);

CREATE INDEX idx_activity_edit_requests_activity_id ON ActivityEditRequests(activity_id);
CREATE INDEX idx_activity_edit_requests_requested_by ON ActivityEditRequests(requested_by);
CREATE INDEX idx_activity_edit_requests_status ON ActivityEditRequests(status);
CREATE INDEX idx_activity_edit_requests_requested_at ON ActivityEditRequests(requested_at);
