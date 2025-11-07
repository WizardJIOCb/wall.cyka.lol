<?php
/**
 * Wall Social Platform - Location Model
 * 
 * Handles location data for posts
 */

namespace App\Models;

use App\Utils\Database;
use Exception;

class Location
{
    /**
     * Find location by ID
     */
    public static function findById($locationId)
    {
        $sql = "SELECT * FROM locations WHERE location_id = ?";
        return Database::fetchOne($sql, [$locationId]);
    }

    /**
     * Find or create location
     */
    public static function findOrCreate($data)
    {
        // Check if location exists
        $sql = "SELECT * FROM locations WHERE latitude = ? AND longitude = ? LIMIT 1";
        $existing = Database::fetchOne($sql, [$data['latitude'], $data['longitude']]);

        if ($existing) {
            return $existing;
        }

        // Create new location
        return self::create($data);
    }

    /**
     * Create location
     */
    public static function create($data)
    {
        $sql = "INSERT INTO locations (
            latitude, longitude, place_name, place_address,
            place_city, place_country, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $data['latitude'],
            $data['longitude'],
            $data['place_name'] ?? null,
            $data['place_address'] ?? null,
            $data['place_city'] ?? null,
            $data['place_country'] ?? null
        ];

        try {
            Database::query($sql, $params);
            $locationId = Database::lastInsertId();
            return self::findById($locationId);
        } catch (Exception $e) {
            throw new Exception('Failed to create location: ' . $e->getMessage());
        }
    }

    /**
     * Get public location data
     */
    public static function getPublicData($location)
    {
        if (!$location) return null;

        return [
            'location_id' => (int)$location['location_id'],
            'latitude' => (float)$location['latitude'],
            'longitude' => (float)$location['longitude'],
            'place_name' => $location['place_name'],
            'place_address' => $location['place_address'],
            'place_city' => $location['place_city'],
            'place_country' => $location['place_country'],
        ];
    }
}
