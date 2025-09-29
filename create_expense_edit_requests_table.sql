-- Create ExpenseEditRequests table to track expense edit requests from Project In-Charges
CREATE TABLE IF NOT EXISTS ExpenseEditRequests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    expense_id INT NOT NULL,
    requested_by INT NOT NULL,
    request_type ENUM('edit', 'deactivate') DEFAULT 'edit',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    
    -- Original expense data (for comparison)
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
    FOREIGN KEY (expense_id) REFERENCES ExpenseEntries(expense_id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES ssmntUsers(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES ssmntUsers(id) ON DELETE SET NULL
);

-- Add indexes for better performance
CREATE INDEX idx_expense_edit_requests_expense_id ON ExpenseEditRequests(expense_id);
CREATE INDEX idx_expense_edit_requests_requested_by ON ExpenseEditRequests(requested_by);
CREATE INDEX idx_expense_edit_requests_status ON ExpenseEditRequests(status);
CREATE INDEX idx_expense_edit_requests_requested_at ON ExpenseEditRequests(requested_at);

-- Add is_active field to ExpenseEntries table for soft deletion
ALTER TABLE ExpenseEntries ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER created_by;
