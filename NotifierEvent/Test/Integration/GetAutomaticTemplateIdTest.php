<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */

declare(strict_types=1);

namespace Magento\NotifierEvent\Test\Integration;

use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\NotifierEvent\Model\GetAutomaticTemplateId;
use Magento\NotifierEvent\Model\Rule;
use Magento\NotifierEvent\Test\Integration\Mock\ConfigureMockTemplateGetter;
use PHPUnit\Framework\TestCase;

class GetAutomaticTemplateIdTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var GetAutomaticTemplateId
     */
    private $subject;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        ConfigureMockTemplateGetter::execute();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->subject = $this->objectManager->get(GetAutomaticTemplateId::class);
    }

    public function testShouldReturnEventTemplate(): void
    {
        $rule = $this->objectManager->create(Rule::class);
        $this->assertSame('event:some_event', $this->subject->execute($rule, 'some_event'));
    }

    public function testShouldReturnDefaultEventTemplate(): void
    {
        $rule = $this->objectManager->create(Rule::class);
        $this->assertSame('event:_default', $this->subject->execute($rule, 'unknown_event'));
    }
}
