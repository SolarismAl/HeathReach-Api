<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Firestore;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Http\HttpClientOptions;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Exception;

class FirebaseService
{
    private Auth $auth;
    private Firestore $firestore;
    private Messaging $messaging;

    public function __construct()
    {
        try {
            $credentials = config('firebase.credentials');

            $httpOptions = HttpClientOptions::default([
                'verify' => false, // Disable SSL verification (dev only!)
                'timeout' => 30,
                'connect_timeout' => 10,
            ]);

            $factory = (new Factory)
                ->withServiceAccount($credentials)
                ->withVerifierCache(new NullAdapter())
                ->withHttpClientOptions($httpOptions);
            
            $this->auth = $factory->createAuth();
            $this->firestore = $factory->createFirestore();
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            \Log::error('Firebase initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAuth(): Auth
    {
        return $this->auth;
    }

    public function getFirestore(): Firestore
    {
        return $this->firestore;
    }

    public function getMessaging(): Messaging
    {
        return $this->messaging;
    }

    /**
     * Get user by email
     */
    public function getUserByEmail(string $email): ?array
    {
        try {
            $user = $this->auth->getUserByEmail($email);
            return [
                'uid' => $user->uid,
                'email' => $user->email,
                'displayName' => $user->displayName,
                'emailVerified' => $user->emailVerified
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Create a new user with custom claims
     */
    public function createUser(string $email, string $password, string $name, string $role): array
    {
        try {
            $userProperties = [
                'email' => $email,
                'password' => $password,
                'displayName' => $name,
                'emailVerified' => false,
            ];

            $user = $this->auth->createUser($userProperties);
            
            // Set custom claims for role
            $this->auth->setCustomUserClaims($user->uid, ['role' => $role]);

            return [
                'success' => true,
                'user' => $user,
                'uid' => $user->uid
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify Firebase ID token
     */
    public function verifyIdToken(string $idToken): array
    {
        try {
            \Log::info('FirebaseService: Starting ID token verification');
            \Log::info('FirebaseService: Token length: ' . strlen($idToken));
            
            $verifiedIdToken = $this->auth->verifyIdToken($idToken);
            \Log::info('FirebaseService: Token verified successfully');
            
            $uid = $verifiedIdToken->claims()->get('sub');
            \Log::info('FirebaseService: Extracted UID: ' . $uid);
            
            $user = $this->auth->getUser($uid);
            \Log::info('FirebaseService: Retrieved user data for UID: ' . $uid);

            return [
                'success' => true,
                'uid' => $uid,
                'user' => $user,
                'token' => $verifiedIdToken
            ];
        } catch (Exception $e) {
            \Log::error('FirebaseService: Token verification failed: ' . $e->getMessage());
            \Log::error('FirebaseService: Exception trace: ' . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send push notification via FCM
     */
    public function sendNotification(string $token, string $title, string $body, ?array $data = null): array
    {
        try {
            $notification = Notification::create($title, $body);
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification);

            if ($data) {
                $message = $message->withData($data);
            }

            $result = $this->messaging->send($message);

            return [
                'success' => true,
                'result' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send notification to multiple tokens
     */
    public function sendMulticastNotification(array $tokens, string $title, string $body, ?array $data = null): array
    {
        try {
            $notification = Notification::create($title, $body);
            $message = CloudMessage::new()->withNotification($notification);

            if ($data) {
                $message = $message->withData($data);
            }

            $result = $this->messaging->sendMulticast($message, $tokens);

            return [
                'success' => true,
                'result' => $result,
                'success_count' => $result->successes()->count(),
                'failure_count' => $result->failures()->count()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sign in with email and password (simulate Firebase Auth REST API)
     */
    public function signInWithEmailAndPassword(string $email, string $password): array
    {
        try {
            // This would normally call Firebase Auth REST API
            // For now, we'll simulate it by checking if user exists
            $users = $this->firestore->database()->collection('users');
            $query = $users->where('email', '=', $email);
            $documents = $query->documents();
            
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $userData = $document->data();
                    // In real implementation, verify password hash
                    return [
                        'success' => true,
                        'localId' => $userData['firebase_uid'] ?? $document->id(),
                        'idToken' => 'simulated_token_' . time(),
                        'email' => $email
                    ];
                }
            }
            
            return ['success' => false, 'error' => 'Invalid credentials'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create custom token
     */
    public function createCustomToken(string $uid, array $claims = []): string
    {
        try {
            $token = $this->auth->createCustomToken($uid, $claims);
            // Convert token object to string using toString method
            if (method_exists($token, 'toString')) {
                return $token->toString();
            } elseif (method_exists($token, '__toString')) {
                return $token->__toString();
            } else {
                // Fallback: try to get the token value directly
                return (string) $token;
            }
        } catch (Exception $e) {
            \Log::error('Failed to create custom token: ' . $e->getMessage());
            return 'error_token_' . time();
        }
    }

    /**
     * Revoke user tokens (logout)
     */
    public function revokeUserTokens(string $uid): array
    {
        try {
            $this->auth->revokeRefreshTokens($uid);
            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Revoke refresh tokens (alias for revokeUserTokens)
     */
    public function revokeRefreshTokens(string $uid): array
    {
        return $this->revokeUserTokens($uid);
    }

    /**
     * Delete user
     */
    public function deleteUser(string $uid): array
    {
        try {
            $this->auth->deleteUser($uid);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update user
     */
    public function updateUser(string $uid, array $properties): array
    {
        try {
            $this->auth->updateUser($uid, $properties);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify user password by attempting to sign in
     */
    public function verifyUserPassword(string $email, string $password): array
    {
        try {
            // Use Firebase Auth REST API to verify password
            $apiKey = config('firebase.api_key');
            $url = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={$apiKey}";
            
            $data = [
                'email' => $email,
                'password' => $password,
                'returnSecureToken' => true
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if (isset($result['localId'])) {
                    return ['success' => true, 'uid' => $result['localId']];
                }
            }

            return ['success' => false, 'error' => 'Invalid password'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update user password
     */
    public function updateUserPassword(string $uid, string $newPassword): array
    {
        try {
            $this->auth->updateUser($uid, ['password' => $newPassword]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if user has a password set
     */
    public function userHasPassword(string $uid): bool
    {
        try {
            $user = $this->auth->getUser($uid);
            
            // Check if user has password provider
            foreach ($user->providerData as $provider) {
                if ($provider->providerId === 'password') {
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            \Log::error('Error checking if user has password: ' . $e->getMessage());
            return false;
        }
    }
}
