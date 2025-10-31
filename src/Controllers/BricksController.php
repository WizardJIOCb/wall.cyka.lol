<?php
/**
 * Wall Social Platform - Bricks Controller
 * 
 * Handles bricks currency operations
 */

class BricksController
{
    /**
     * Get user's brick balance
     * GET /api/v1/bricks/balance
     */
    public static function getBalance()
    {
        $user = AuthMiddleware::requireAuth();

        $balance = BricksService::getUserBalance($user['user_id']);

        self::jsonResponse(true, [
            'balance' => $balance
        ]);
    }

    /**
     * Get user's bricks statistics
     * GET /api/v1/bricks/stats
     */
    public static function getStats()
    {
        $user = AuthMiddleware::requireAuth();

        $stats = BricksService::getUserStats($user['user_id']);

        self::jsonResponse(true, [
            'stats' => $stats
        ]);
    }

    /**
     * Claim daily bricks
     * POST /api/v1/bricks/claim
     */
    public static function claimDaily()
    {
        $user = AuthMiddleware::requireAuth();

        try {
            $result = BricksService::claimDailyBricks($user['user_id']);

            self::jsonResponse(true, [
                'claimed' => $result['claimed'],
                'new_balance' => $result['new_balance'],
                'next_claim' => $result['next_claim'],
                'message' => 'Daily bricks claimed successfully'
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'CLAIM_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Get transaction history
     * GET /api/v1/bricks/transactions
     */
    public static function getTransactions()
    {
        $user = AuthMiddleware::requireAuth();
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $transactions = BricksService::getTransactionHistory($user['user_id'], $limit, $offset);

        self::jsonResponse(true, [
            'transactions' => $transactions,
            'count' => count($transactions),
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Transfer bricks to another user
     * POST /api/v1/bricks/transfer
     */
    public static function transfer()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        if (empty($data['to_user_id']) || empty($data['amount'])) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'to_user_id and amount are required', 400);
        }

        $amount = (int)$data['amount'];
        if ($amount <= 0) {
            self::jsonResponse(false, ['code' => 'INVALID_AMOUNT'], 'Amount must be positive', 400);
        }

        try {
            $result = BricksService::transferBricks(
                $user['user_id'],
                $data['to_user_id'],
                $amount,
                $data['message'] ?? ''
            );

            self::jsonResponse(true, [
                'from_balance' => $result['from_balance'],
                'to_balance' => $result['to_balance'],
                'amount' => $result['amount'],
                'message' => 'Bricks transferred successfully'
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'TRANSFER_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Calculate AI generation cost
     * POST /api/v1/bricks/calculate-cost
     */
    public static function calculateCost()
    {
        $data = self::getRequestData();

        if (empty($data['token_count'])) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'token_count is required', 400);
        }

        $tokenCount = (int)$data['token_count'];
        $cost = BricksService::calculateAICost($tokenCount);

        self::jsonResponse(true, [
            'token_count' => $tokenCount,
            'bricks_cost' => $cost
        ]);
    }

    /**
     * Admin: Add bricks to user
     * POST /api/v1/bricks/admin/add
     */
    public static function adminAdd()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        if (empty($data['user_id']) || empty($data['amount'])) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'user_id and amount are required', 400);
        }

        try {
            $result = BricksService::adminAddBricks(
                $user['user_id'],
                $data['user_id'],
                $data['amount'],
                $data['reason'] ?? ''
            );

            self::jsonResponse(true, [
                'new_balance' => $result['new_balance'],
                'amount' => $result['amount'],
                'message' => 'Bricks added successfully'
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'ADD_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Admin: Remove bricks from user
     * POST /api/v1/bricks/admin/remove
     */
    public static function adminRemove()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        if (empty($data['user_id']) || empty($data['amount'])) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'user_id and amount are required', 400);
        }

        try {
            $result = BricksService::adminRemoveBricks(
                $user['user_id'],
                $data['user_id'],
                $data['amount'],
                $data['reason'] ?? ''
            );

            self::jsonResponse(true, [
                'new_balance' => $result['new_balance'],
                'amount' => $result['amount'],
                'message' => 'Bricks removed successfully'
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'REMOVE_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Get request JSON data
     */
    private static function getRequestData()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
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
