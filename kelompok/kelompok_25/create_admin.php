<?php
/**
 * Script untuk membuat user admin baru
 * Jalankan: php create_admin.php
 */

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== Create Admin User ===\n\n";
    
    // Data admin
    $email = 'admin@inventory.com';
    $password = 'admin123';
    $name = 'Administrator';
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if user already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        echo "⚠️  User dengan email '$email' sudah ada!\n";
        echo "   Mereset password...\n";
        
        $userId = $existingUser['id'];
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, name = ?, is_active = 1 WHERE id = ?");
        $stmt->execute([$passwordHash, $name, $userId]);
        
        echo "   ✓ Password berhasil direset\n\n";
    } else {
        echo "1. Membuat user baru...\n";
        
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password_hash, is_active, created_at, updated_at) 
            VALUES (?, ?, ?, 1, NOW(), NOW())
        ");
        $stmt->execute([$name, $email, $passwordHash]);
        $userId = $db->lastInsertId();
        
        echo "   ✓ User berhasil dibuat (ID: $userId)\n\n";
    }
    
    // Get or ensure admin role exists
    echo "2. Memeriksa role admin...\n";
    
    $stmt = $db->query("SELECT id FROM roles WHERE code = 'admin'");
    $adminRole = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminRole) {
        echo "   ⚠️  Role admin tidak ditemukan, membuat role baru...\n";
        $stmt = $db->prepare("
            INSERT INTO roles (name, code, description, created_at) 
            VALUES ('Administrator', 'admin', 'Full system access', NOW())
        ");
        $stmt->execute();
        $adminRoleId = $db->lastInsertId();
        echo "   ✓ Role admin berhasil dibuat (ID: $adminRoleId)\n\n";
    } else {
        $adminRoleId = $adminRole['id'];
        // Update deskripsi role admin
        $stmt = $db->prepare("UPDATE roles SET description = 'Full system access' WHERE id = ?");
        $stmt->execute([$adminRoleId]);
        echo "   ✓ Role admin ditemukan (ID: $adminRoleId)\n\n";
    }
    
    // Check if user already has admin role
    echo "3. Mengassign role admin ke user...\n";
    
    $stmt = $db->prepare("SELECT id FROM user_roles WHERE user_id = ? AND role_id = ?");
    $stmt->execute([$userId, $adminRoleId]);
    $existingRole = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingRole) {
        echo "   ✓ User sudah memiliki role admin\n\n";
    } else {
        // Delete old roles
        $stmt = $db->prepare("DELETE FROM user_roles WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Assign admin role
        $stmt = $db->prepare("
            INSERT INTO user_roles (user_id, role_id, is_default, created_at) 
            VALUES (?, ?, 1, NOW())
        ");
        $stmt->execute([$userId, $adminRoleId]);
        
        echo "   ✓ Role admin berhasil diassign\n\n";
    }
    
    // Ensure other default roles exist
    echo "4. Memeriksa role lainnya...\n";
    
    $defaultRoles = [
        ['code' => 'manager', 'name' => 'Manager', 'description' => 'Access to data master and reports'],
        ['code' => 'staff', 'name' => 'Staff', 'description' => 'Basic access to materials and transactions']
    ];
    
    foreach ($defaultRoles as $role) {
        $stmt = $db->prepare("SELECT id FROM roles WHERE code = ?");
        $stmt->execute([$role['code']]);
        $existingRole = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingRole) {
            $stmt = $db->prepare("UPDATE roles SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$role['name'], $role['description'], $existingRole['id']]);
            echo "   ✓ Role {$role['name']} sudah ada\n";
        } else {
            $stmt = $db->prepare("
                INSERT INTO roles (name, code, description, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$role['name'], $role['code'], $role['description']]);
            echo "   ✓ Role {$role['name']} dibuat\n";
        }
    }
    
    echo "\n=== Setup Completed! ===\n\n";
    echo "Credentials:\n";
    echo "  Email   : $email\n";
    echo "  Password: $password\n\n";
    echo "📝 PENTING: Jalankan juga 'php setup_permissions.php' untuk setup permissions!\n\n";
    echo "✅ Silakan login menggunakan credentials di atas\n\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
