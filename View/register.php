<?php
session_start();
require_once '../includes/path_resolver.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS included -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">
</head>
 
<body>
    <!-- Layout Container -->
    <div class="layout-container">
        
        <!-- Topbar -->
        <?php include '../topbar.php'; ?>
        
        <!-- Sidebar -->
        <?php include '../includes/role_based_sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="main-content">
            
            <!-- Content Container -->
            <div class="content-container">
                
                <!-- Page Header -->
                <div class="page-header mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Add New Member</h1>
                    <p class="text-gray-600">Register a new user in the system</p>
                </div>

                <!-- Display Session Messages -->
                <?php 
                $successMessage = $_SESSION['success'] ?? null;
                $errorMessage = $_SESSION['error'] ?? null;
                
                if ($successMessage): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <?php
                // Clear session messages after displaying them
                unset($_SESSION['success'], $_SESSION['error']);
                ?>

                <!-- Registration Form -->
                <div class="flex justify-center">
                    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-2xl">
                        <form action="../Controller/register_process.php" method="POST" class="space-y-6">
                            <!-- Personal Information Section -->
                            <div class="border-b border-gray-200 pb-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4">Personal Information</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Full Name -->
                                    <div class="form-group">
                                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name:</label>
                                        <input type="text" id="full_name" name="full_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                    </div>
                                    <!-- Username -->
                                    <div class="form-group">
                                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username:</label>
                                        <input type="text" id="username" name="username" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                    </div>
                                    <!-- Email -->
                                    <div class="form-group">
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email:</label>
                                        <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                    </div>
                                    <!-- Phone Number -->
                                    <div class="form-group">
                                        <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">Phone Number:</label>
                                        <input type="text" id="phone_number" name="phone_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                </div>
                            </div>

                            <!-- Role and Community Section -->
                            <div class="border-b border-gray-200 pb-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4">Role & Community</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Role Selection -->
                                    <div class="form-group">
                                        <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role:</label>
                                        <select id="role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="Project In-Charge" selected>Project In-Charge</option>
                                            <option value="Councillor">Councillor</option>
                                        </select>
                                    </div>
                                    <!-- Community -->
                                    <div class="form-group">
                                        <label for="community" class="block text-sm font-medium text-gray-700 mb-2">Community:</label>
                                        <select name="community" id="community" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                            <option disabled selected>-- Choose One --</option>
                                            <option value="Ajitsing Nagar">Ajitsing Nagar</option>
                                            <option value="Arul Colony">Arul Colony</option>
                                            <option value="Aurangabad">Aurangabad</option>
                                            <option value="Avanigadda">Avanigadda</option>
                                            <option value="Beed">Beed</option>
                                            <option value="Chakan">Chakan</option>
                                            <option value="Chirala">Chirala</option>
                                            <option value="Deepthi Bhavan">Deepthi Bhavan</option>
                                            <option value="Ghodegaon">Ghodegaon</option>
                                            <option value="Guntupalli">Guntupalli</option>
                                            <option value="Jaggayyapet">Jaggayyapet</option>
                                            <option value="Jahanuma">Jahanuma</option>
                                            <option value="Jyothi Nilayam">Jyothi Nilayam</option>
                                            <option value="Kapaerkheda">Kapaerkheda</option>
                                            <option value="Kashimira">Kashimira</option>
                                            <option value="Kondapalli">Kondapalli</option>
                                            <option value="Kuttur">Kuttur</option>
                                            <option value="Mangalagiri">Mangalagiri</option>
                                            <option value="Mubaraspur">Mubaraspur</option>
                                            <option value="Niuland">Niuland</option>
                                            <option value="Nunna">Nunna</option>
                                            <option value="Ponnur">Ponnur</option>
                                            <option value="Prasanth Bhavan">Prasanth Bhavan</option>
                                            <option value="Rajavaram">Rajavaram</option>
                                            <option value="S.A.Peta">S.A.Peta</option>
                                            <option value="Shapura">Shapura</option>
                                            <option value="Songaon">Songaon</option>
                                            <option value="St. Ann's Home">St. Ann's Home</option>
                                            <option value="St. Ann's Hospital">St. Ann's Hospital</option>
                                            <option value="Taylorpet">Taylorpet</option>
                                            <option value="Tiruvur">Tiruvur</option>
                                            <option value="Umwahlang">Umwahlang</option>
                                            <option value="Vasai">Vasai</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Password Section -->
                            <div class="pb-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4">Security</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Password -->
                                    <div class="form-group">
                                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password:</label>
                                        <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                    </div>
                                    <!-- Confirm Password -->
                                    <div class="form-group">
                                        <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password:</label>
                                        <input type="password" id="password_confirm" name="password_confirm" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end space-x-4">
                                <a href="user_list.php" class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors">
                                    Cancel
                                </a>
                                <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
                                    Add Member
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div></body>
</html>
