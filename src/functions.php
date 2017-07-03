<?php

namespace Phantasy\Types;

use Exception;

function product(string $name, array $fields)
{
    return new class($name, $fields) {
        public function __construct($name, $fields)
        {
            $this->name = $name;
            $this->fields = $fields;
        }

        public function __invoke(...$fieldValues)
        {
            if (count($this->fields) !== count($fieldValues)) {
                throw new Exception(
                    'There are '
                    . count($this->fields)
                    . ' fields, but '
                    . count($fieldValues)
                    . ' were passed in!'
                );
            }

            $name = $this->name;
            $this->$name = function (...$fieldValues) {
                $b = clone $this;

                foreach ($b->fields as $i => $field) {
                    $b->$field = $fieldValues[$i];
                }
                $b->fieldValues = $fieldValues;

                return $b;
            };

            foreach ($this->fields as $i => $field) {
                $this->$field = $fieldValues[$i];
            }

            $this->fieldValues = $fieldValues;

            return $this;
        }

        public function __call($method, $args)
        {
            if (isset($this->$method) && is_callable($this->$method)) {
                return $this->$method->call($this, ...$args);
            }
        }

        public function __toString()
        {
            $vals = array_map(function ($x) {
                return var_export($x, true);
            }, $this->fieldValues);
            return $this->name .'(' . implode(', ', $vals) . ')';
        }
    };
}

function sum(string $name, array $constructors)
{
    return new class($name, $constructors) {
        public function __construct($name, $constructors)
        {
            $this->name = $name;
            $this->constructors = $constructors;

            // Create the new classes off of the constructors
            foreach ($constructors as $key => $fields) {
                $self = $this;
                $this->$key = function ($fieldValues) use ($key, $fields, $self) {
                    return new class($fields, $fieldValues, $key, $self)
                    {
                        public function __construct($fields, $fieldValues, $key, $parentCtx)
                        {
                            $this->fields = $fields;
                            $this->fieldValues = $fieldValues;
                            $this->parentCtx = $parentCtx;
                            $this->tag = $key;
                        }

                        public function __call($method, $arguments)
                        {
                            if (in_array($method, array_keys($this->parentCtx->constructors))) {
                                return $this->parentCtx->$method->call($this->parentCtx, $arguments);
                            }

                            if (isset($this->parentCtx->$method) && is_callable($this->parentCtx->$method)) {
                                return $this->parentCtx->$method->call($this, ...$arguments);
                            }
                        }

                        public function __toString()
                        {
                            $vals = array_map(function ($x) {
                                return var_export($x, true);
                            }, $this->fieldValues);
                            return $this->parentCtx->name
                                . '.' . $this->tag
                                .'(' . implode(', ', $vals) . ')';
                        }

                        public function cata($cases)
                        {
                            if (count($cases) !== count($this->parentCtx->constructors) && !isset($cases["_"])) {
                                throw new Exception('You didn\'t cover all of the cases!');
                            }

                            if (isset($cases[$this->tag]) && is_callable($cases[$this->tag])) {
                                return $cases[$this->tag](...$this->fieldValues);
                            } elseif (isset($cases["_"]) && is_callable($cases["_"])) {
                                return $cases["_"](...$this->fieldValues);
                            }
                            throw new Exception('You didn\'t define a method for ' . $this->tag . '.');
                        }

                    };
                };
            }
        }

        public function __call($method, $arguments)
        {
            if (isset($this->$method) && is_callable($this->$method)) {
                return $this->$method->call($this, $arguments);
            }
        }

        public function __toString()
        {
            return $this->name;
        }
    };
}
