<?php
session_start();
require_once '../includes/dbh.inc.php';
require_once '../includes/path_resolver.php';
require_once '../includes/auth_check.php';

// Ensure only 'Councillor' users can access this page
checkRole('Councillor');

$userId = $_GET['id'] ?? null;

if (!$userId) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: user_list.php");
    exit();
}

// Fetch the user's current details
try {
    $stmt = $pdo->prepare("SELECT * FROM ssmntUsers WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header("Location: user_list.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching user details: " . $e->getMessage();
    header("Location: user_list.php");
    exit();
}

// List of predefined communities
$communities = [
    "Ajitsing Nagar", "Arul Colony", "Aurangabad", "Avanigadda", "Beed",
    "Chakan", "Chirala", "Deepthi Bhavan", "Ghodegaon", "Guntupalli",
    "Jaggayyapet", "Jahanuma", "Jyothi Nilayam", "Kapaerkheda", "Kashimira",
    "Kondapalli", "Kuttur", "Mangalagiri", "Mubaraspur", "Niuland",
    "Nunna", "Ponnur", "Prasanth Bhavan", "Rajavaram", "S.A.Peta",
    "Shapura", "Songaon", "St. Ann's Home", "St. Ann's Hospital", "Taylorpet",
    "Tiruvur", "Umwahlang", "Vasai"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Assessment System</title>
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
                
                <!-- Page Content Goes Here -->
                
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Edit User</h1>

        <form action="../Controller/edit_user_process.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">

            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="w-full px-3 py-2 mb-2 border" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-3 py-2 mb-2 border" required>
            </div>

            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" class="w-full px-3 py-2 mb-2 border" required>
                    <option value="Project In-Charge" <?php if ($user['role'] === 'Project In-Charge') echo 'selected'; ?>>Project In-Charge</option>
                    <option value="Councillor" <?php if ($user['role'] === 'Councillor') echo 'selected'; ?>>Councillor</option>
                </select>
            </div>

            <div class="form-group">
                <label for="community">Community:</label>
                <select id="community" name="community" class="w-full px-3 py-2 mb-2 border" required>
                    <option value="" disabled>-- Choose One --</option>
                    <?php foreach ($communities as $community): ?>
                        <option value="<?php echo $community; ?>" <?php if ($user['community'] === $community) echo 'selected'; ?>>
                            <?php echo $community; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Update User</button>
        </form>
    </div>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div></body>
</html>
