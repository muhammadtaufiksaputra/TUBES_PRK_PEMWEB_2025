<?php

/**
 * Auth Controller (Web)
 */

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/models/User.php';
require_once ROOT_PATH . '/models/Role.php';

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLogin()
    {
        // If already logged in, redirect to dashboard
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }

        $this->view('auth/login', [], 'auth');
    }

    /**
     * Process login
     */
    public function login()
    {
        // Validate CSRF token
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            set_flash('error', 'Invalid CSRF token.');
            $this->back();
        }

        // Validate input
        $validated = $this->validate($_POST, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Attempt login
        if (!Auth::attempt($validated['email'], $validated['password'])) {
            set_flash('error', 'Email atau password salah.');
            set_old($_POST);
            $this->back();
        }

        // Verify session is set
        if (!Auth::check()) {
            set_flash('error', 'Gagal membuat session. Silakan coba lagi.');
            $this->back();
        }

        // Update last login
        $userModel = new User();
        $userModel->updateLastLogin(Auth::id());

        set_flash('success', 'Berhasil login!');
        $this->redirect('/dashboard');
    }

    /**
     * Show register form
     */
    public function showRegister()
    {
        // If already logged in, redirect to dashboard
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }

        $this->view('auth/register', [], 'auth');
    }

    /**
     * Process registration
     */
    public function register()
    {
        // Validate CSRF token
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            set_flash('error', 'Invalid CSRF token.');
            $this->back();
        }

        // Validate input
        $validated = $this->validate($_POST, [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'password_confirmation' => 'required'
        ]);

        // Check password confirmation
        if ($validated['password'] !== $_POST['password_confirmation']) {
            set_flash('error', 'Konfirmasi password tidak sama.');
            set_old($_POST);
            $this->back();
        }

        try {
            // Create user
            $userModel = new User();
            $userId = $userModel->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'is_active' => true
            ]);

            // Assign default role (user/staff)
            $roleModel = new Role();
            $defaultRole = $roleModel->findByCode('staff');
            
            if ($defaultRole) {
                $userModel->assignRole($userId, $defaultRole['id'], true);
            }

            set_flash('success', 'Registrasi berhasil! Silakan login.');
            $this->redirect('/login');

        } catch (Exception $e) {
            set_flash('error', 'Terjadi kesalahan. Silakan coba lagi.');
            set_old($_POST);
            $this->back();
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        // Verify CSRF token
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            set_flash('error', 'Invalid CSRF token.');
            $this->redirect('/login');
        }

        Auth::logout();
        set_flash('success', 'Berhasil logout.');
        $this->redirect('/login');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword()
    {
        $this->view('auth/forgot-password', [], 'auth');
    }

    /**
     * Process forgot password
     */
    public function forgotPassword()
    {
        // Validate CSRF token
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            set_flash('error', 'Invalid CSRF token.');
            $this->back();
        }

        // Validate input
        $validated = $this->validate($_POST, [
            'email' => 'required|email|exists:users,email'
        ]);

        // TODO: Send password reset email
        // For now, just show success message

        set_flash('success', 'Link reset password telah dikirim ke email Anda.');
        $this->redirect('/login');
    }
}
