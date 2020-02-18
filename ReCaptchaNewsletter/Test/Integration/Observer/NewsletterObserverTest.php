<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaNewsletter\Test\Integration\Observer;

use Magento\TestFramework\App\ReinitableConfig;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;
use Magento\ReCaptcha\Model\CaptchaValidator;
use Magento\ReCaptchaApi\Api\CaptchaValidatorInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\TestFramework\Bootstrap;

/**
 * Test for \Magento\ReCaptchaNewsletter\Observer\NewsletterObserverTest class.
 */
class NewsletterObserverTest extends AbstractController
{
    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var CaptchaValidatorInterface|MockObject
     */
    private $captchaValidatorMock;

    /**
     * @var ReinitableConfig
     */
    private $settingsConfiguration;

    /**
     * @var InterpretationStrategyInterface
     */
    private $interpretationStrategy;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->formKey = $this->_objectManager->get(FormKey::class);
        $this->response = $this->_objectManager->get(ResponseInterface::class);
        $this->captchaValidatorMock = $this->createMock(CaptchaValidatorInterface::class);
        $this->settingsConfiguration = $this->_objectManager->get(ReinitableConfig::class);
        $this->interpretationStrategy = $this->_objectManager->get(InterpretationStrategyInterface::class);
        $this->_objectManager->addSharedInstance($this->captchaValidatorMock, CaptchaValidator::class);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testRecaptchaNotConfigured()
    {
        $this->sendNewsletterPostAction(false, false);

        $this->assertSessionMessages($this->equalTo(['Thank you for your subscription.']));
        $this->assertRedirect($this->anything());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testReCaptchaDisabled()
    {
        $this->sendNewsletterPostAction(true, false);

        $this->assertSessionMessages($this->equalTo(['Thank you for your subscription.']));
        $this->assertRedirect($this->anything());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testCorrectRecaptcha()
    {
        $this->captchaValidatorMock->expects($this->once())->method('validate')->willReturn(true);

        $this->sendNewsletterPostAction(true, true);

        $this->assertSessionMessages($this->equalTo(['Thank you for your subscription.']));
        $this->assertRedirect($this->anything());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testIncorrectRecaptcha()
    {
        $this->captchaValidatorMock->expects($this->once())->method('validate')->willReturn(false);

        $this->sendNewsletterPostAction(true, true);

        $this->assertSessionMessages(
            $this->equalTo(['You cannot proceed with such operation, your reCaptcha reputation is too low.'])
        );
        $this->assertRedirect($this->anything());
    }

    /**
     * Send Newsletter subscribe form
     *
     * @throws LocalizedException
     **/
    private function sendNewsletterPostAction(bool $captchaIsEnabled = true, bool $captchaIsEnabledForNewsletter = true)
    {
        if ($captchaIsEnabled) {
            $this->settingsConfiguration->setValue(
                'recaptcha/frontend/enabled_for_newsletter',
                (int)$captchaIsEnabledForNewsletter,
                ScopeInterface::SCOPE_WEBSITES
            );
            $this->settingsConfiguration->setValue(
                'recaptcha/frontend/public_key',
                'test_public_key',
                ScopeInterface::SCOPE_WEBSITES
            );
            $this->settingsConfiguration->setValue(
                'recaptcha/frontend/private_key',
                'test_private_key',
                ScopeInterface::SCOPE_WEBSITES
            );
        }

        $params = [
            'email' => 'not_used@example.com',
            'form_key' => $this->formKey->getFormKey()
        ];

        if ($captchaIsEnabled && $captchaIsEnabledForNewsletter) {
            $params['g-recaptcha-response'] = 'test_response';
        }

        $this->getRequest()->setPostValue($params)->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('newsletter/subscriber/new');

        $this->settingsConfiguration->reinit();
    }

}
