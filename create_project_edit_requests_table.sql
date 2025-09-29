-- Create ProjectEditRequests table to track edit requests from Project In-Charges
CREATE TABLE IF NOT EXISTS ProjectEditRequests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    requested_by INT NOT NULL,
    request_type ENUM('edit', 'deactivate') DEFAULT 'edit',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    
    -- Original project data (for comparison)
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
    FOREIGN KEY (project_id) REFERENCES Projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES ssmntUsers(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES ssmntUsers(id) ON DELETE SET NULL
);

-- Add indexes for better performance
CREATE INDEX idx_project_edit_requests_project_id ON ProjectEditRequests(project_id);
CREATE INDEX idx_project_edit_requests_requested_by ON ProjectEditRequests(requested_by);
CREATE INDEX idx_project_edit_requests_status ON ProjectEditRequests(status);
CREATE INDEX idx_project_edit_requests_requested_at ON ProjectEditRequests(requested_at);
