<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    /**
     * Format old properties for display in KeyValueEntry
     */
    protected function oldProperties(): Attribute
    {
        return Attribute::make(
            get: function () {
                $properties = $this->properties;

                if (is_null($properties)) {
                    return [];
                }

                // Convert Collection to array if needed
                if ($properties instanceof \Illuminate\Support\Collection) {
                    $properties = $properties->toArray();
                }

                // Extract old key
                $old = $properties['old'] ?? null;

                if (is_null($old)) {
                    return [];
                }

                // Convert all values to strings
                $formatted = [];
                if (is_array($old)) {
                    foreach ($old as $key => $value) {
                        if (is_array($value) || is_object($value)) {
                            $formatted[$key] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                        } else {
                            $formatted[$key] = (string) $value;
                        }
                    }
                }

                return $formatted;
            }
        );
    }

    /**
     * Format attributes properties for display in KeyValueEntry
     */
    protected function attributesProperties(): Attribute
    {
        return Attribute::make(
            get: function () {
                $properties = $this->properties;

                if (is_null($properties)) {
                    return [];
                }

                // Convert Collection to array if needed
                if ($properties instanceof \Illuminate\Support\Collection) {
                    $properties = $properties->toArray();
                }

                // Extract attributes key
                $attributes = $properties['attributes'] ?? null;

                if (is_null($attributes)) {
                    return [];
                }

                // Convert all values to strings
                $formatted = [];
                if (is_array($attributes)) {
                    foreach ($attributes as $key => $value) {
                        if (is_array($value) || is_object($value)) {
                            $formatted[$key] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                        } else {
                            $formatted[$key] = (string) $value;
                        }
                    }
                }

                return $formatted;
            }
        );
    }
}
