<?php

/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */

declare(strict_types=1);

namespace Tappet\Cypress\Automation\Field;

use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Runner\Action\FieldActionInterface;
use Tappet\Runner\Automation\Field\FieldActionHandlerInterface;
use Tappet\Runner\Standard\Action\Upload;

/**
 * Class FileFieldActionHandler.
 *
 * Handles actions on file input fields.
 *
 * @implements FieldActionHandlerInterface<FieldActionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class FileFieldActionHandler implements FieldActionHandlerInterface
{
    public function __construct(
        private readonly CypressAutomationInterface $automation
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getHandlers(): array
    {
        return [
            Upload::class => function (FieldActionInterface $action): void {
                /** @var Upload $action */
                $this->uploadFile($action);
            },
        ];
    }

    /**
     * Selects the given file on the file input field.
     */
    public function uploadFile(Upload $action): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-field="' . $action->getFieldHandle() . '"]')
            ->selectFile($action->getFilePath());
    }
}
