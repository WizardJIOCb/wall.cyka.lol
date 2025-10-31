<?php
/**
 * Wall Social Platform - Ollama AI Service
 * 
 * Handles integration with Ollama API for AI generation
 */

class OllamaService
{
    private static $config = null;
    private static $baseUrl = '';

    /**
     * Initialize service
     */
    private static function init()
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../config/config.php';
            self::$baseUrl = 'http://' . self::$config['ollama']['host'] . ':' . self::$config['ollama']['port'];
        }
    }

    /**
     * Generate AI content using Ollama
     */
    public static function generateContent($prompt, $model = null, $options = [])
    {
        self::init();

        if (!$model) {
            $model = self::$config['ollama']['model'] ?? 'deepseek-coder';
        }

        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => array_merge([
                'temperature' => 0.7,
                'top_p' => 0.9,
                'frequency_penalty' => 0.5,
                'presence_penalty' => 0.5
            ], $options)
        ];

        $url = self::$baseUrl . '/api/generate';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$config['ollama']['timeout'] ?? 300);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Ollama API error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception('Ollama API returned HTTP ' . $httpCode);
        }

        $result = json_decode($response, true);
        
        if (!$result || !isset($result['response'])) {
            throw new Exception('Invalid response from Ollama API');
        }

        return $result['response'];
    }

    /**
     * Generate web application using prompt
     */
    public static function generateWebApp($userPrompt, $userId)
    {
        self::init();

        // Calculate token usage (approximate)
        $tokenCount = self::estimateTokenCount($userPrompt);
        
        // Check user's brick balance
        $user = User::findById($userId);
        $config = self::$config['bricks'];
        $requiredBricks = ceil($tokenCount / $config['tokens_per_brick']);
        
        if ($user['bricks_balance'] < $requiredBricks) {
            throw new Exception('Insufficient bricks balance. Required: ' . $requiredBricks);
        }

        // Deduct bricks
        self::deductBricks($userId, $requiredBricks, $tokenCount);

        // Create prompt for web app generation
        $systemPrompt = self::getWebAppPrompt($userPrompt);
        
        try {
            $generatedContent = self::generateContent($systemPrompt);
            
            // Parse the generated content into HTML, CSS, JS
            $parsedContent = self::parseGeneratedContent($generatedContent);
            
            // Update user statistics
            self::updateUserStats($userId, $tokenCount);
            
            return [
                'html' => $parsedContent['html'] ?? '',
                'css' => $parsedContent['css'] ?? '',
                'js' => $parsedContent['js'] ?? '',
                'preview_image' => $parsedContent['preview'] ?? null,
                'token_count' => $tokenCount,
                'bricks_cost' => $requiredBricks,
                'model_used' => self::$config['ollama']['model']
            ];
        } catch (Exception $e) {
            // Refund bricks on failure
            self::refundBricks($userId, $requiredBricks);
            throw $e;
        }
    }

    /**
     * Get system prompt for web app generation
     */
    private static function getWebAppPrompt($userPrompt)
    {
        return "Create a complete, functional web application based on this request: '$userPrompt'

Requirements:
1. Generate valid HTML5 document with proper DOCTYPE
2. Include embedded CSS in <style> tag
3. Include embedded JavaScript in <script> tag
4. Make it visually appealing and responsive
5. Use modern web standards
6. Include interactive elements where appropriate
7. Add comments explaining key parts
8. Ensure all code is in a single HTML file
9. Do not include any markdown formatting or code blocks
10. Return ONLY the complete HTML document

Example structure:
<!DOCTYPE html>
<html>
<head>
    <title>App Title</title>
    <style>
        /* CSS styles */
    </style>
</head>
<body>
    <!-- HTML content -->
    <script>
        // JavaScript code
    </script>
</body>
</html>";
    }

    /**
     * Parse generated content into components
     */
    private static function parseGeneratedContent($content)
    {
        // Extract HTML, CSS, and JS from the generated content
        $html = '';
        $css = '';
        $js = '';
        $preview = null;

        // Look for CSS in <style> tags
        if (preg_match('/<style[^>]*>(.*?)<\/style>/s', $content, $cssMatches)) {
            $css = trim($cssMatches[1]);
        }

        // Look for JS in <script> tags
        if (preg_match('/<script[^>]*>(.*?)<\/script>/s', $content, $jsMatches)) {
            $js = trim($jsMatches[1]);
        }

        // Extract body content or full HTML
        if (preg_match('/<body[^>]*>(.*?)<\/body>/s', $content, $bodyMatches)) {
            $html = '<!DOCTYPE html>' . "\n" . 
                   preg_replace('/<style[^>]*>.*?<\/style>|<script[^>]*>.*?<\/script>/s', '', $content);
        } else {
            $html = $content;
        }

        return [
            'html' => $html,
            'css' => $css,
            'js' => $js,
            'preview' => $preview
        ];
    }

    /**
     * Estimate token count for prompt
     */
    private static function estimateTokenCount($text)
    {
        // Rough estimation: 1 token ≈ 4 characters
        return strlen($text) / 4;
    }

    /**
     * Deduct bricks from user balance
     */
    private static function deductBricks($userId, $bricks, $tokens)
    {
        try {
            Database::beginTransaction();

            // Update user balance
            $sql = "UPDATE users SET bricks_balance = bricks_balance - ? WHERE user_id = ?";
            Database::query($sql, [$bricks, $userId]);

            // Record transaction
            $transSql = "INSERT INTO bricks_transactions (
                user_id, amount, transaction_type, description, created_at
            ) VALUES (?, ?, 'debit', ?, NOW())";
            
            $description = "AI generation: $tokens tokens ($bricks bricks)";
            Database::query($transSql, [$userId, $bricks, $description]);

            Database::commit();
        } catch (Exception $e) {
            Database::rollback();
            throw new Exception('Failed to deduct bricks: ' . $e->getMessage());
        }
    }

    /**
     * Refund bricks to user
     */
    private static function refundBricks($userId, $bricks)
    {
        try {
            Database::beginTransaction();

            // Update user balance
            $sql = "UPDATE users SET bricks_balance = bricks_balance + ? WHERE user_id = ?";
            Database::query($sql, [$bricks, $userId]);

            // Record transaction
            $transSql = "INSERT INTO bricks_transactions (
                user_id, amount, transaction_type, description, created_at
            ) VALUES (?, ?, 'credit', 'AI generation refund', NOW())";
            
            Database::query($transSql, [$userId, $bricks]);

            Database::commit();
        } catch (Exception $e) {
            Database::rollback();
            // Log error but don't fail
            error_log('Failed to refund bricks: ' . $e->getMessage());
        }
    }

    /**
     * Update user statistics
     */
    private static function updateUserStats($userId, $tokenCount)
    {
        $sql = "UPDATE users SET 
                total_tokens_used = total_tokens_used + ?,
                ai_generations_count = ai_generations_count + 1,
                updated_at = NOW()
                WHERE user_id = ?";
        
        Database::query($sql, [$tokenCount, $userId]);
    }

    /**
     * Check if Ollama service is available
     */
    public static function isAvailable()
    {
        self::init();

        $url = self::$baseUrl . '/api/tags';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Get available models
     */
    public static function getModels()
    {
        self::init();

        $url = self::$baseUrl . '/api/tags';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Failed to fetch models from Ollama');
        }

        $result = json_decode($response, true);
        return $result['models'] ?? [];
    }
}
