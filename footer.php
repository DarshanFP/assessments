<?php
// Set the timezone to IST
date_default_timezone_set('Asia/Kolkata');
$currentDateTime = date('l, d F Y h:i A'); // Format: Day, DD Month YYYY HH:MM AM/PM
?>
<!-- Footer -->
<footer class="footer-container">
    <div class="footer-content">
        <!-- Left Section: Empty for balance -->
        <div class="footer-left"></div>
        
        <!-- Center Section: Right Info -->
        <div class="footer-center">
            <span class="footer-text">© 2025 Assessment System - All Rights Reserved</span>
        </div>
        
        <!-- Right Section: Date and Time -->
        <div class="footer-right">
            <span class="footer-datetime"><?php echo $currentDateTime; ?> IST</span>
        </div>
    </div>
</footer>

<style>
/* Footer Styles */
.footer-container {
    background-color: #2d3748;
    color: white;
    padding: 15px 20px;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    height: 50px;
    display: flex;
    align-items: center;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

.footer-left {
    flex: 1;
}

.footer-center {
    flex: 2;
    text-align: center;
}

.footer-right {
    flex: 1;
    text-align: right;
}

.footer-text {
    font-size: 0.9rem;
    font-weight: 400;
    color: #e2e8f0;
}

.footer-datetime {
    font-size: 0.9rem;
    font-weight: 400;
    color: #e2e8f0;
    font-style: italic;
}

/* Responsive footer */
@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        gap: 5px;
    }
    
    .footer-left, .footer-center, .footer-right {
        flex: none;
        text-align: center;
    }
}
</style>
