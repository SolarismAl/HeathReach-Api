<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TestAuthController extends Controller
{
    /**
     * Test registration without Firebase (for debugging)
     */
    public function testRegister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Simulate successful registration without Firebase
        $userData = [
            'uid' => 'test-' . uniqid(),
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'patient',
            'contact_number' => $request->contact_number,
            'address' => $request->address,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Test registration successful',
            'data' => $userData
        ], 201);
    }

    /**
     * Test login without Firebase (for debugging)
     */
    public function testLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Simulate successful login
        $userData = [
            'uid' => 'test-user-123',
            'name' => 'Test User',
            'email' => $request->email,
            'role' => 'patient',
            'contact_number' => '+1234567890',
            'address' => 'Test Address',
        ];

        return response()->json([
            'success' => true,
            'message' => 'Test login successful',
            'data' => [
                'user' => $userData,
                'token' => 'test-token-' . time()
            ]
        ]);
    }
}
