<?php
/**
 * Setup Permissions Script
 * Script untuk mengatur permissions dan role assignments
 * Jalankan sekali: php setup_permissions.php
 */

// Load config
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Setup Permissions ===\n\n";
    
    // 1. Pastikan roles ada
    echo "1. Checking roles...\n";
    $roles = [
        ['code' => 'admin', 'name' => 'Administrator', 'description' => 'Full system access'],
        ['code' => 'manager', 'name' => 'Manager', 'description' => 'Manage inventory and reports'],
        ['code' => 'staff', 'name' => 'Staff', 'description' => 'Basic operations only']
    ];
    
    foreach ($roles as $role) {
        $stmt = $db->prepare("INSERT INTO roles (code, name, description, is_active) 
                             VALUES (?, ?, ?, 1) 
                             ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), is_active=VALUES(is_active)");
        $stmt->execute([$role['code'], $role['name'], $role['description']]);
        echo "   ✓ Role: {$role['name']}\n";
    }
    
    // 2. Insert semua permissions
    echo "\n2. Setting up permissions...\n";
    $permissions = [
        // Dashboard
        ['view_dashboard', 'View Dashboard', 'Access to dashboard'],
        
        // Materials
        ['view_materials', 'View Materials', 'View materials list'],
        ['create_materials', 'Create Materials', 'Add new materials'],
        ['edit_materials', 'Edit Materials', 'Edit existing materials'],
        ['delete_materials', 'Delete Materials', 'Delete materials'],
        ['export_materials', 'Export Materials', 'Export materials data'],
        
        // Categories
        ['view_categories', 'View Categories', 'View categories'],
        ['create_categories', 'Create Categories', 'Add new categories'],
        ['edit_categories', 'Edit Categories', 'Edit categories'],
        ['delete_categories', 'Delete Categories', 'Delete categories'],
        
        // Suppliers
        ['view_suppliers', 'View Suppliers', 'View suppliers'],
        ['create_suppliers', 'Create Suppliers', 'Add new suppliers'],
        ['edit_suppliers', 'Edit Suppliers', 'Edit suppliers'],
        ['delete_suppliers', 'Delete Suppliers', 'Delete suppliers'],
        
        // Stock In
        ['view_stock_in', 'View Stock In', 'View stock in transactions'],
        ['create_stock_in', 'Create Stock In', 'Record stock in'],
        ['edit_stock_in', 'Edit Stock In', 'Edit stock in transactions'],
        ['delete_stock_in', 'Delete Stock In', 'Delete stock in transactions'],
        
        // Stock Out
        ['view_stock_out', 'View Stock Out', 'View stock out transactions'],
        ['create_stock_out', 'Create Stock Out', 'Record stock out'],
        ['edit_stock_out', 'Edit Stock Out', 'Edit stock out transactions'],
        ['delete_stock_out', 'Delete Stock Out', 'Delete stock out transactions'],
        
        // Stock Adjustments
        ['view_stock_adjustments', 'View Stock Adjustments', 'View stock adjustments'],
        ['create_stock_adjustments', 'Create Stock Adjustments', 'Create adjustments'],
        ['delete_stock_adjustments', 'Delete Stock Adjustments', 'Delete adjustments'],
        
        // Reports
        ['view_reports', 'View Reports', 'Access to reports'],
        ['export_reports', 'Export Reports', 'Export report data'],
        ['view_low_stock', 'View Low Stock', 'View low stock alerts'],
        
        // Users
        ['view_users', 'View Users', 'View users list'],
        ['create_users', 'Create Users', 'Add new users'],
        ['edit_users', 'Edit Users', 'Edit users'],
        ['delete_users', 'Delete Users', 'Delete users'],
    ];
    
    $permissionIds = [];
    foreach ($permissions as $perm) {
        $stmt = $db->prepare("INSERT INTO permissions (code, name, description) 
                             VALUES (?, ?, ?) 
                             ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description)");
        $stmt->execute([$perm[0], $perm[1], $perm[2]]);
        
        // Get permission ID
        $stmt = $db->prepare("SELECT id FROM permissions WHERE code = ?");
        $stmt->execute([$perm[0]]);
        $permissionIds[$perm[0]] = $stmt->fetchColumn();
        
        echo "   ✓ Permission: {$perm[1]}\n";
    }
    
    // 3. Clear existing role_permissions
    echo "\n3. Clearing old role assignments...\n";
    $db->exec("DELETE FROM role_permissions");
    echo "   ✓ Cleared\n";
    
    // 4. Assign permissions to roles
    echo "\n4. Assigning permissions to roles...\n";
    
    // Get role IDs
    $stmt = $db->query("SELECT id, code FROM roles");
    $roleIds = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $roleIds[$row['code']] = $row['id'];
    }
    
    // ADMIN - All permissions
    echo "   → Administrator (ALL PERMISSIONS)\n";
    foreach ($permissionIds as $code => $permId) {
        $stmt = $db->prepare("INSERT INTO role_permissions (role_id, permission_id, is_default) VALUES (?, ?, 1)");
        $stmt->execute([$roleIds['admin'], $permId]);
    }
    echo "      ✓ " . count($permissionIds) . " permissions assigned\n";
    
    // MANAGER - Access to data master and reports ONLY (NO STOCK TRANSACTIONS)
    echo "   → Manager\n";
    $managerPerms = [
        'view_dashboard',
        'view_materials', 'create_materials', 'edit_materials', 'delete_materials', 'export_materials',
        'view_categories', 'create_categories', 'edit_categories', 'delete_categories',
        'view_suppliers', 'create_suppliers', 'edit_suppliers', 'delete_suppliers',
        'view_reports', 'export_reports', 'view_low_stock',
    ];
    foreach ($managerPerms as $code) {
        if (isset($permissionIds[$code])) {
            $stmt = $db->prepare("INSERT INTO role_permissions (role_id, permission_id, is_default) VALUES (?, ?, 1)");
            $stmt->execute([$roleIds['manager'], $permissionIds[$code]]);
        }
    }
    echo "      ✓ " . count($managerPerms) . " permissions assigned\n";
    
    // STAFF - Basic permissions including stock adjustments (NO REPORTS ACCESS)
    echo "   → Staff\n";
    $staffPerms = [
        'view_dashboard',
        'view_materials',
        'view_categories',
        'view_suppliers',
        'view_stock_in', 'create_stock_in',
        'view_stock_out', 'create_stock_out',
        'view_stock_adjustments', 'create_stock_adjustments',
    ];
    foreach ($staffPerms as $code) {
        if (isset($permissionIds[$code])) {
            $stmt = $db->prepare("INSERT INTO role_permissions (role_id, permission_id, is_default) VALUES (?, ?, 1)");
            $stmt->execute([$roleIds['staff'], $permissionIds[$code]]);
        }
    }
    echo "      ✓ " . count($staffPerms) . " permissions assigned\n";
    
    // 5. Update existing users - pastikan semua user punya role
    echo "\n5. Checking user roles...\n";
    $stmt = $db->query("SELECT u.id, u.email, u.name, 
                        (SELECT COUNT(*) FROM user_roles WHERE user_id = u.id) as has_role
                        FROM users u");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        if ($user['has_role'] == 0) {
            // Assign default staff role
            $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id, is_default) VALUES (?, ?, 1)");
            $stmt->execute([$user['id'], $roleIds['staff']]);
            echo "   ✓ Assigned 'Staff' role to: {$user['email']}\n";
        } else {
            echo "   ✓ User already has role: {$user['email']}\n";
        }
    }
    
    // 6. Summary
    echo "\n=== Summary ===\n";
    $stmt = $db->query("SELECT COUNT(*) FROM permissions");
    echo "Total Permissions: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $db->query("SELECT r.name, COUNT(rp.id) as perm_count 
                       FROM roles r 
                       LEFT JOIN role_permissions rp ON r.id = rp.role_id 
                       WHERE r.is_active = 1
                       GROUP BY r.id, r.name");
    echo "\nRole Assignments:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['name']}: {$row['perm_count']} permissions\n";
    }
    
    $stmt = $db->query("SELECT COUNT(DISTINCT u.id) as user_count 
                       FROM users u 
                       INNER JOIN user_roles ur ON u.id = ur.user_id 
                       WHERE u.is_active = 1");
    echo "\nUsers with roles: " . $stmt->fetchColumn() . "\n";
    
    echo "\n✅ Setup completed successfully!\n";
    echo "\n📝 Next steps:\n";
    echo "   1. Logout dari aplikasi\n";
    echo "   2. Login kembali untuk load permissions\n";
    echo "   3. Test akses menu berdasarkan role Anda\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
