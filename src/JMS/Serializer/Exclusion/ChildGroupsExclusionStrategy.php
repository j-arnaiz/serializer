<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\Serializer\Exclusion;

use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Context;

/**
 * Class: ChildGroupsExclusionStrategy
 *
 * @see ExclusionStrategyInterface
 */
class ChildGroupsExclusionStrategy extends GroupsExclusionStrategy implements ExclusionStrategyInterface
{
    const DEFAULT_CHILDGROUP = 'Default';

    /**
     * __construct
     *
     * @param array $groups
     */
    public function __construct(array $groups)
    {
        parent::__construct($groups);
    }
    /**
     * {@inheritDoc}
     */
    public function shouldSkipClass(ClassMetadata $metadata, Context $navigatorContext)
    {
        return false;
    }

    private function getParentMetadata($context)
    {
        $metaStack = $context->getMetadataStack();
        $metaCount = $metaStack->count();

        for ($i = $metaCount - 1; $i > 0; $i--) {
            if ($metaStack->offsetExists($i)) {
                $metadata = $metaStack->offsetGet($i);
                if ($metadata instanceof PropertyMetadata) {
                    return $metadata;
                }
            }
        }

        return null;
    }

    private function getChildGroups($context)
    {
        $metadata = $this->getParentMetadata($context);
        $childGroups = array();

        if (isset($metadata, $metadata->childGroups)) {
            foreach ($metadata->childGroups as $childGroup) {
                $childGroups[$childGroup] = true;
            }
        }

        return $childGroups;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext)
    {
        // Getting parent childgroups
        $childGroups = $this->getChildGroups($navigatorContext);

        // if no childGroups present, serialize normally
        if (empty($childGroups)) {
            return parent::shouldSkipProperty($property, $navigatorContext);
        }

        if ( ! $property->groups) {
            return ! isset($childGroups[self::DEFAULT_CHILDGROUP]);
        }

        foreach ($property->groups as $group) {
            if (isset($childGroups[$group])) {
                return false;
            }
        }

        return true;
    }
}
