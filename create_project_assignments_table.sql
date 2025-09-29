-- Create ProjectAssignments table to track multiple Project In-Charges per project
CREATE TABLE IF NOT EXISTS ProjectAssignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    project_incharge_id INT NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    assigned_by INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (project_id) REFERENCES Projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (project_incharge_id) REFERENCES ssmntUsers(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES ssmntUsers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_incharge (project_id, project_incharge_id)
);

-- Add index for better performance
CREATE INDEX idx_project_assignments_project_id ON ProjectAssignments(project_id);
CREATE INDEX idx_project_assignments_incharge_id ON ProjectAssignments(project_incharge_id);
CREATE INDEX idx_project_assignments_active ON ProjectAssignments(is_active);
