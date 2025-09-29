<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class PasswordResetService
{
    private FirestoreService $firestoreService;
    private const COLLECTION_NAME = 'password_resets';
    private const TOKEN_EXPIRY_MINUTES = 60; // 1 hour

    public function __construct(FirestoreService $firestoreService)
    {
        $this->firestoreService = $firestoreService;
    }

    /**
     * Create a password reset token and send email
     */
    public function createPasswordResetToken(array $user, string $email): array
    {
        try {
            // Generate secure token
            $token = Str::random(64);
            $expiresAt = Carbon::now()->addMinutes(self::TOKEN_EXPIRY_MINUTES);

            // Store token in Firestore
            $resetData = [
                'email' => $email,
                'token' => hash('sha256', $token), // Store hashed version
                'user_id' => $user['user_id'],
                'expires_at' => $expiresAt->toISOString(),
                'created_at' => Carbon::now()->toISOString(),
                'used' => false
            ];

            // Delete any existing tokens for this email
            $this->deleteExistingTokens($email);

            // Create new token document
            $documentId = $this->firestoreService->createDocument(
                self::COLLECTION_NAME,
                $resetData,
                'reset-' . Str::uuid()
            );

            if (!$documentId) {
                return [
                    'success' => false,
                    'message' => 'Failed to create password reset token'
                ];
            }

            // Send email
            $emailSent = $this->sendPasswordResetEmail($user, $token);

            if (!$emailSent) {
                return [
                    'success' => false,
                    'message' => 'Failed to send password reset email'
                ];
            }

            return [
                'success' => true,
                'message' => 'Password reset email sent successfully'
            ];

        } catch (Exception $e) {
            \Log::error('Password reset token creation failed:', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process password reset request'
            ];
        }
    }

    /**
     * Verify password reset token
     */
    public function verifyResetToken(string $token): array
    {
        try {
            $hashedToken = hash('sha256', $token);
            
            // Find token in Firestore
            $resetTokens = $this->firestoreService->queryCollection(
                self::COLLECTION_NAME,
                [
                    ['token', '=', $hashedToken],
                    ['used', '=', false]
                ]
            );

            if (empty($resetTokens)) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired reset token'
                ];
            }

            $resetToken = $resetTokens[0];

            // Check if token has expired
            $expiresAt = Carbon::parse($resetToken['expires_at']);
            if (Carbon::now()->isAfter($expiresAt)) {
                return [
                    'success' => false,
                    'message' => 'Reset token has expired'
                ];
            }

            return [
                'success' => true,
                'data' => $resetToken
            ];

        } catch (Exception $e) {
            \Log::error('Token verification failed:', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Token verification failed'
            ];
        }
    }

    /**
     * Mark token as used
     */
    public function markTokenAsUsed(string $documentId): bool
    {
        try {
            return $this->firestoreService->updateDocument(
                self::COLLECTION_NAME,
                $documentId,
                [
                    'used' => true,
                    'used_at' => Carbon::now()->toISOString()
                ]
            );
        } catch (Exception $e) {
            \Log::error('Failed to mark token as used:', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete existing tokens for email
     */
    private function deleteExistingTokens(string $email): void
    {
        try {
            $existingTokens = $this->firestoreService->queryCollection(
                self::COLLECTION_NAME,
                [['email', '=', $email]]
            );

            foreach ($existingTokens as $token) {
                if (isset($token['document_id'])) {
                    $this->firestoreService->deleteDocument(
                        self::COLLECTION_NAME,
                        $token['document_id']
                    );
                }
            }
        } catch (Exception $e) {
            \Log::error('Failed to delete existing tokens:', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail(array $user, string $token): bool
    {
        try {
            // Create reset URL
            $resetUrl = env('APP_FRONTEND_URL', 'http://localhost:8081') . '/reset-password?token=' . $token;

            // Send email
            Mail::send('emails.password-reset', [
                'user' => $user,
                'resetUrl' => $resetUrl,
                'expirationMinutes' => self::TOKEN_EXPIRY_MINUTES
            ], function ($message) use ($user) {
                $message->to($user['email'], $user['name'])
                        ->subject('HealthReach - Password Reset Request');
            });

            return true;

        } catch (Exception $e) {
            \Log::error('Failed to send password reset email:', [
                'email' => $user['email'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clean up expired tokens (should be run periodically)
     */
    public function cleanupExpiredTokens(): int
    {
        try {
            $expiredTokens = $this->firestoreService->queryCollection(
                self::COLLECTION_NAME,
                [['expires_at', '<', Carbon::now()->toISOString()]]
            );

            $deletedCount = 0;
            foreach ($expiredTokens as $token) {
                if (isset($token['document_id'])) {
                    if ($this->firestoreService->deleteDocument(self::COLLECTION_NAME, $token['document_id'])) {
                        $deletedCount++;
                    }
                }
            }

            \Log::info('Cleaned up expired password reset tokens', ['count' => $deletedCount]);
            return $deletedCount;

        } catch (Exception $e) {
            \Log::error('Failed to cleanup expired tokens:', ['error' => $e->getMessage()]);
            return 0;
        }
    }
}
