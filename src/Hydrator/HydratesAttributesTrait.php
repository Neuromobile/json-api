<?php

/**
 * Copyright 2017 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\JsonApi\Hydrator;

use CloudCreativity\JsonApi\Contracts\Object\StandardObjectInterface;
use DateTime;

/**
 * Class HydratesAttributesTrait
 *
 * @package CloudCreativity\JsonApi
 */
trait HydratesAttributesTrait
{

    /**
     * @param object $record
     *      the record to hydrate
     * @param string $attrKey
     *      the record's attribute key
     * @param mixed $value
     *      the deserialized value
     * @return void
     */
    abstract protected function hydrateAttribute($record, $attrKey, $value);

    /**
     * @param StandardObjectInterface $attributes
     * @param object $record
     */
    protected function hydrateAttributes(StandardObjectInterface $attributes, $record)
    {
        foreach ($this->attributeKeys($attributes, $record) as $resourceKey => $attrKey) {
            if (is_numeric($resourceKey)) {
                $resourceKey = $attrKey;
                $attrKey = $this->keyForAttribute($attrKey, $record);
            }

            if (!$attributes->has($resourceKey)) {
                continue;
            }

            $deserialized = $this->deserializeAttribute($attributes->get($resourceKey), $resourceKey);
            $this->hydrateAttribute($record, $attrKey, $deserialized);
        }
    }

    /**
     * Get a list of attributes that are to be hydrated.
     *
     * @param StandardObjectInterface $attributes
     * @param $record
     * @return array
     */
    protected function attributeKeys(StandardObjectInterface $attributes, $record)
    {
        $keys = property_exists($this, 'attributes') ? $this->attributes : null;

        if (is_null($keys)) {
            return $attributes->keys();
        }

        return (array) $keys;
    }

    /**
     * @param $resourceKey
     * @param $record
     * @return mixed
     */
    protected function keyForAttribute($resourceKey, $record)
    {
        return $resourceKey;
    }

    /**
     * Deserialize a value obtained from the resource's attributes.
     *
     * @param $value
     *      the value that the client provided.
     * @param $resourceKey
     *      the attribute key for the value
     * @return mixed
     */
    protected function deserializeAttribute($value, $resourceKey)
    {
        if ($this->isDateAttribute($resourceKey)) {
            return $this->deserializeDate($value);
        }

        return $value;
    }

    /**
     * @param $value
     * @return DateTime|null
     */
    protected function deserializeDate($value)
    {
        return !is_null($value) ? new DateTime($value) : null;
    }

    /**
     * Is this resource key a date attribute?
     *
     * @param $resourceKey
     * @return bool
     */
    protected function isDateAttribute($resourceKey)
    {
        $dates = property_exists($this, 'dates') ? (array) $this->dates : [];

        return in_array($resourceKey, $dates, true);
    }
}
