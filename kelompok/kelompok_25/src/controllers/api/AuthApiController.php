<?php

/**
 * Auth API Controller
 */

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Response.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/models/User.php';

class AuthApiController extends Controller
{
    /**
     * Login via API
     */
    public function login()
    {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        // Validate input
        if (empty($input['email']) || empty($input['password'])) {
            Response::error('Email dan password wajib diisi.', [], 400);
        }

        // Attempt login
        if (!Auth::attempt($input['email'], $input['password'])) {
            Response::error('Email atau password salah.', [], 401);
        }

        // Update last login
        $userModel = new User();
        $userModel->updateLastLogin(Auth::id());

        Response::success('Login berhasil.', [
            'user' => Auth::user()
        ]);
    }

    /**
     * Logout via API
     */
    public function logout()
    {
        Auth::logout();
        Response::success('Logout berhasil.');
    }

    /**
     * Get current user
     */
    public function me()
    {
        if (!Auth::check()) {
            Response::unauthorized('Anda belum login.');
        }

        Response::success('User data retrieved.', [
            'user' => Auth::user()
        ]);
    }

    /**
     * Check authentication
     */
    public function check()
    {
        Response::success('Check authentication.', [
            'authenticated' => Auth::check(),
            'user' => Auth::user()
        ]);
    }
}
