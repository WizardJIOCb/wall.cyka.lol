<?php
/**
 * Wall Social Platform - Upload Controller
 * 
 * Handles file upload operations
 */

class UploadController
{
    private const MAX_AVATAR_SIZE = 5242880; // 5MB
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const UPLOAD_DIR = __DIR__ . '/../../public/uploads/avatars/';

    /**
     * Upload user avatar
     * POST /api/v1/upload/avatar
     */
    public static function uploadAvatar()
    {
        $user = AuthMiddleware::requireAuth();

        // Check if file was uploaded
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            self::jsonResponse(false, [
                'code' => 'NO_FILE_UPLOADED'
            ], 'No file uploaded or upload error occurred', 400);
        }

        $file = $_FILES['avatar'];

        // Validate file size
        if ($file['size'] > self::MAX_AVATAR_SIZE) {
            self::jsonResponse(false, [
                'code' => 'FILE_TOO_LARGE'
            ], 'Image must be smaller than 5MB', 400);
        }

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_IMAGE_TYPES)) {
            self::jsonResponse(false, [
                'code' => 'INVALID_FILE_TYPE'
            ], 'Please select a valid image file (JPEG, PNG, or WebP)', 400);
        }

        try {
            // Create upload directory if it doesn't exist
            if (!is_dir(self::UPLOAD_DIR)) {
                mkdir(self::UPLOAD_DIR, 0755, true);
            }

            // Generate unique filename
            $extension = match($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg'
            };

            $filename = 'user_' . $user['user_id'] . '_' . uniqid() . '.' . $extension;
            $targetPath = self::UPLOAD_DIR . $filename;
            $publicUrl = '/uploads/avatars/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Failed to save uploaded file');
            }

            // Delete old avatar if exists and is not default
            if (!empty($user['avatar_url']) && strpos($user['avatar_url'], '/uploads/avatars/') === 0) {
                $oldAvatarPath = __DIR__ . '/../../public' . $user['avatar_url'];
                if (file_exists($oldAvatarPath)) {
                    @unlink($oldAvatarPath);
                }
            }

            // Update user avatar URL in database
            User::update($user['user_id'], ['avatar_url' => $publicUrl]);

            self::jsonResponse(true, [
                'avatar_url' => $publicUrl,
                'message' => 'Avatar uploaded successfully'
            ]);

        } catch (Exception $e) {
            self::jsonResponse(false, [
                'code' => 'UPLOAD_FAILED'
            ], $e->getMessage(), 500);
        }
    }

    /**
     * Send JSON response
     */
    private static function jsonResponse($success, $data = [], $message = '', $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => $success,
            'data' => $data
        ];

        if ($message) {
            $response['message'] = $message;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
