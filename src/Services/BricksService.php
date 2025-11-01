<?php
/**
 * Wall Social Platform - Bricks Currency Service
 * 
 * Handles bricks currency system operations
 */

class BricksService
{
    private static $config = null;

    /**
     * Initialize service
     */
    private static function init()
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../config/config.php';
        }
    }

    /**
     * Get user's brick balance
     */
    public static function getUserBalance($userId)
    {
        $user = User::findById($userId);
        return $user ? (int)$user['bricks_balance'] : 0;
    }

    /**
     * Claim daily bricks
     */
    public static function claimDailyBricks($userId)
    {
        self::init();

        try {
            Database::beginTransaction();

            // Get user info
            $user = User::findById($userId);
            if (!$user) {
                throw new Exception('User not found');
            }

            // Check if already claimed today
            $today = date('Y-m-d');
            if ($user['last_daily_claim'] === $today) {
                throw new Exception('Daily bricks already claimed today');
            }

            // Award daily bricks
            $dailyAmount = self::$config['bricks']['daily_claim_amount'] ?? 50;
            
            $sql = "UPDATE users SET 
                    bricks_balance = bricks_balance + ?,
                    last_daily_claim = ?,
                    updated_at = NOW()
                    WHERE user_id = ?";
            
            Database::query($sql, [$dailyAmount, $today, $userId]);

            // Record transaction
            $transSql = "INSERT INTO bricks_transactions (
                user_id, amount, transaction_type, description, created_at
            ) VALUES (?, ?, 'credit', 'Daily claim', NOW())";
            
            Database::query($transSql, [$userId, $dailyAmount]);

            Database::commit();

            return [
                'claimed' => $dailyAmount,
                'new_balance' => self::getUserBalance($userId),
                'next_claim' => date('Y-m-d', strtotime('+1 day'))
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw new Exception('Failed to claim daily bricks: ' . $e->getMessage());
        }
    }

    /**
     * Transfer bricks between users
     */
    public static function transferBricks($fromUserId, $toUserId, $amount, $message = '')
    {
        if ($amount <= 0) {
            throw new Exception('Transfer amount must be positive');
        }

        if ($fromUserId == $toUserId) {
            throw new Exception('Cannot transfer to yourself');
        }

        try {
            Database::beginTransaction();

            // Check sender balance
            $sender = User::findById($fromUserId);
            if (!$sender) {
                throw new Exception('Sender not found');
            }

            if ($sender['bricks_balance'] < $amount) {
                throw new Exception('Insufficient bricks balance');
            }

            // Check recipient exists
            $recipient = User::findById($toUserId);
            if (!$recipient) {
                throw new Exception('Recipient not found');
            }

            // Transfer bricks
            $deductSql = "UPDATE users SET bricks_balance = bricks_balance - ? WHERE user_id = ?";
            Database::query($deductSql, [$amount, $fromUserId]);

            $addSql = "UPDATE users SET bricks_balance = bricks_balance + ? WHERE user_id = ?";
            Database::query($addSql, [$amount, $toUserId]);

            // Record transactions
            $sendTransSql = "INSERT INTO bricks_transactions (
                user_id, amount, transaction_type, description, related_user_id, created_at
            ) VALUES (?, ?, 'debit', ?, ?, NOW())";
            
            $sendDescription = $message ? "Transfer to user $toUserId: $message" : "Transfer to user $toUserId";
            Database::query($sendTransSql, [$fromUserId, $amount, $sendDescription, $toUserId]);

            $receiveTransSql = "INSERT INTO bricks_transactions (
                user_id, amount, transaction_type, description, related_user_id, created_at
            ) VALUES (?, ?, 'credit', ?, ?, NOW())";
            
            $receiveDescription = $message ? "Transfer from user $fromUserId: $message" : "Transfer from user $fromUserId";
            Database::query($receiveTransSql, [$toUserId, $amount, $receiveDescription, $fromUserId]);

            Database::commit();

            return [
                'from_balance' => self::getUserBalance($fromUserId),
                'to_balance' => self::getUserBalance($toUserId),
                'amount' => $amount
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw new Exception('Failed to transfer bricks: ' . $e->getMessage());
        }
    }

    /**
     * Get user transaction history
     */
    public static function getTransactionHistory($userId, $limit = 50, $offset = 0)
    {
        $sql = "SELECT bt.*, u.username as related_username
                FROM bricks_transactions bt
                LEFT JOIN users u ON bt.related_user_id = u.user_id
                WHERE bt.user_id = ?
                ORDER BY bt.created_at DESC
                LIMIT ? OFFSET ?";
        
        $transactions = Database::fetchAll($sql, [$userId, $limit, $offset]);

        return array_map(function($trans) {
            return [
                'transaction_id' => (int)$trans['transaction_id'],
                'user_id' => (int)$trans['user_id'],
                'amount' => (int)$trans['amount'],
                'transaction_type' => $trans['transaction_type'],
                'description' => $trans['description'],
                'related_user_id' => $trans['related_user_id'] ? (int)$trans['related_user_id'] : null,
                'related_username' => $trans['related_username'],
                'created_at' => $trans['created_at']
            ];
        }, $transactions);
    }

    /**
     * Calculate AI generation cost
     */
    public static function calculateAICost($tokenCount)
    {
        self::init();
        
        $tokensPerBrick = self::$config['bricks']['tokens_per_brick'] ?? 100;
        return ceil($tokenCount / $tokensPerBrick);
    }

    /**
     * Get user statistics
     */
    public static function getUserStats($userId)
    {
        $user = User::findById($userId);
        if (!$user) {
            return null;
        }

        return [
            'bricks_balance' => (int)$user['bricks_balance'],
            'last_daily_claim' => $user['last_daily_claim'],
            'next_claim_available' => $user['last_daily_claim'] !== date('Y-m-d'),
            'total_tokens_used' => (int)$user['total_tokens_used'],
            'ai_generations_count' => (int)$user['ai_generations_count'],
            'daily_claim_amount' => self::$config['bricks']['daily_claim_amount'] ?? 50
        ];
    }

    /**
     * Admin: Add bricks to user
     */
    public static function adminAddBricks($adminUserId, $targetUserId, $amount, $reason = '')
    {
        // Check if admin (user_id = 1 for now)
        if ($adminUserId != 1) {
            throw new Exception('Admin access required');
        }

        if ($amount <= 0) {
            throw new Exception('Amount must be positive');
        }

        try {
            Database::beginTransaction();

            // Check recipient exists
            $recipient = User::findById($targetUserId);
            if (!$recipient) {
                throw new Exception('Recipient not found');
            }

            // Add bricks
            $sql = "UPDATE users SET bricks_balance = bricks_balance + ? WHERE user_id = ?";
            Database::query($sql, [$amount, $targetUserId]);

            // Get new balance
            $newBalance = self::getUserBalance($targetUserId);

            // Record transaction
            $transSql = "INSERT INTO bricks_transactions (
                user_id, amount, transaction_type, balance_after, source, description, created_at
            ) VALUES (?, ?, 'bonus', ?, 'admin', ?, NOW())";

            $description = $reason ? "Admin add: $reason" : "Admin add";
            Database::query($transSql, [$targetUserId, $amount, $newBalance, $description]);

            Database::commit();

            return [
                'new_balance' => self::getUserBalance($targetUserId),
                'amount' => $amount
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw new Exception('Failed to add bricks: ' . $e->getMessage());
        }
    }

    /**
     * Admin: Remove bricks from user
     */
    public static function adminRemoveBricks($adminUserId, $targetUserId, $amount, $reason = '')
    {
        // Check if admin (user_id = 1 for now)
        if ($adminUserId != 1) {
            throw new Exception('Admin access required');
        }

        if ($amount <= 0) {
            throw new Exception('Amount must be positive');
        }

        try {
            Database::beginTransaction();

            // Check recipient exists and has enough balance
            $recipient = User::findById($targetUserId);
            if (!$recipient) {
                throw new Exception('Recipient not found');
            }

            if ($recipient['bricks_balance'] < $amount) {
                throw new Exception('Insufficient bricks balance');
            }

            // Remove bricks
            $sql = "UPDATE users SET bricks_balance = bricks_balance - ? WHERE user_id = ?";
            Database::query($sql, [$amount, $targetUserId]);

            // Get new balance
            $newBalance = self::getUserBalance($targetUserId);

            // Record transaction
            $transSql = "INSERT INTO bricks_transactions (
                user_id, amount, transaction_type, balance_after, source, description, created_at
            ) VALUES (?, ?, 'spent', ?, 'admin', ?, NOW())";
            
            $description = $reason ? "Admin remove: $reason" : "Admin remove";
            Database::query($transSql, [$targetUserId, $amount, $newBalance, $description]);

            Database::commit();

            return [
                'new_balance' => self::getUserBalance($targetUserId),
                'amount' => $amount
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw new Exception('Failed to remove bricks: ' . $e->getMessage());
        }
    }
}
