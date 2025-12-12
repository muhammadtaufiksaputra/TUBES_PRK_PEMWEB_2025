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
