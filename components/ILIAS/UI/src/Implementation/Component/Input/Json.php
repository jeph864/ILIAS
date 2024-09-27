<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 */

declare(strict_types=1);

namespace ILIAS\UI\Implementation\Component\Input;

use ILIAS\UI\Component\Input\InputData;
use ILIAS\UI\Implementation\Component\ComponentHelper;

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
trait Json
{
    use ComponentHelper;

    /**
     * @var \ILIAS\UI\Component\Input\Input[]
     */
    protected array $inputs = [];

    /**
     * Get the value that is displayed in the input client side.
     *
     * @return    mixed
     */
    public function getValue()
    {
        return array_map(fn($i) => $i->getValue(), $this->inputs);
    }


    /**
     * Set a value for this group, handling nested structures
     *
     * @return self
     */
    public function withValue($value): self
    {
        $this->checkArg("value", $this->isClientSideValueOk($value), "Provided value does not match the expected JSON structure.");
        $clone = clone $this;

        $clone->setInputs(array_map(function ($input, $key) use ($value) {
            if (!array_key_exists($key, $value)) {
                throw new \InvalidArgumentException("Missing key '{$key}' in provided JSON value.");
            }

            return $input instanceof self
                ? $input->withValue($value[$key]) // Recursive call for nested JsonGroups
                : $input->withValue($value[$key]);
        }, $this->getInputs(), array_keys($this->getInputs())));

        return $clone;
    }

    /**
     * Collects the input, applies trafos and forwards the input to its children and returns
     * a new input group reflecting the inputs with data that was put in.
     *
     * @inheritdoc
     */
    public function withInput(InputData $input): self
    {
        $clone = clone $this;

        $inputs = [];
        $contents = [];
        $error = false;

        foreach ($this->getInputs() as $key => $child) {
            if ($input->has($key)) {
                $child_value = $input->get($key);

                if ($child instanceof self) {
                    // Check if the child is a terminal JSON
                    if (count($child->getInputs()) === 0) {
                        // Terminal JSON: directly assign the value to contents
                        $inputs[$key] = $child;
                        $contents[$key] = $child_value;
                    } else {
                        // Nested JsonGroup: recursively process its inputs
                        $child_input_data = new ArrayInputData($child_value);
                        $inputs[$key] = $child->withInput($child_input_data);

                        $content = $inputs[$key]->getContent();
                        if ($content->isError()) {
                            $error = true;
                        } else {
                            $contents[$key] = $content->value();
                        }
                    }
                } else {
                    // Flat input: process normally
                    $child_input_data = new ArrayInputData([$key => $child_value]);
                    $inputs[$key] = $child->withInput($child_input_data);

                    $content = $inputs[$key]->getContent();
                    if ($content->isError()) {
                        $error = true;
                    } else {
                        $contents[$key] = $content->value();
                    }
                }
            } else {
                // Handle missing input
                if ($child instanceof self && count($child->getInputs()) === 0) {
                    // Terminal JSON: assign an empty structure
                    $inputs[$key] = $child;
                    $contents[$key] = [];
                } else {
                    // Non-terminal input: process as missing
                    $inputs[$key] = $child->withInput(new ArrayInputData([]));
                    $contents[$key] = $inputs[$key]->getContent()->value();
                }
            }
        }

        $clone->inputs = $inputs;

        if ($error) {
            $clone->content = $clone->getDataFactory()->error($this->getLanguage()->txt("ui_error_in_group"));
        } else {
            $clone->content = $this->getDataFactory()->ok($contents);
        }

        if ($clone->content->isError()) {
            $clone->setError($clone->content->error());
        }

        return $clone;
    }
    /**
     * Validate if the provided value matches the expected JSON structure
     *
     * @param mixed $value
     * @return bool
     */
    protected function isClientSideValueOk($value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($this->getInputs() as $key => $input) {
            if (!array_key_exists($key, $value)) {
                return false;
            }

            if (!$input->isClientSideValueOk($value[$key])) {
                return false;
            }
        }

        return true;
    }
}
