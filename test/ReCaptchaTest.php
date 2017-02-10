<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Captcha;

use Zend\Captcha\ReCaptcha;
use ZendService\ReCaptcha\ReCaptcha as ReCaptchaService;

/**
 * @group      Zend_Captcha
 */
class ReCaptchaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        if (!getenv('TESTS_ZEND_CAPTCHA_RECAPTCHA_SUPPORT')) {
            $this->markTestSkipped('Enable TESTS_ZEND_CAPTCHA_RECAPTCHA_SUPPORT to test PDF render');
        }

        if (isset($this->word)) {
            unset($this->word);
        }

        $this->captcha = new ReCaptcha([
            'sessionClass' => 'ZendTest\Captcha\TestAsset\SessionContainer'
        ]);
    }

    public function testConstructorShouldSetOptions()
    {
        $options = [
            'secret_key' => 'secretKey',
            'site_key'  => 'siteKey',
            'size' => 'a',
            'theme' => 'b',
            'type' => 'c',
            'tabindex' => 'd',
            'callback' => 'e',
            'expired-callback' => 'f',
            'hl' => 'g',
            'noscript' => 'h',
        ];
        $captcha = new ReCaptcha($options);
        $service = $captcha->getService();

        // have params been stored correctly?
        $test = $service->getParams();
        $compare = ['noscript' => $options['noscript']];
        foreach ($compare as $key => $value) {
            $this->assertArrayHasKey($key, $test);
            $this->assertSame($value, $test[$key]);
        }

        // have options been stored correctly?
        $test = $service->getOptions();
        $compare = [
            'size' => $options['size'],
            'theme' => $options['theme'],
            'type' => $options['type'],
            'tabindex' => $options['tabindex'],
            'callback' => $options['callback'],
            'expired-callback' => $options['expired-callback'],
            'hl' => $options['hl'],
        ];
        $this->assertEquals($compare, $test);
    }

    public function testShouldAllowSpecifyingServiceObject()
    {
        $captcha = new ReCaptcha();
        $try     = new ReCaptchaService();
        $this->assertNotSame($captcha->getService(), $try);
        $captcha->setService($try);
        $this->assertSame($captcha->getService(), $try);
    }

    public function testSetAndGetSiteAndSecretKeys()
    {
        $captcha = new ReCaptcha();
        $siteKey = 'siteKey';
        $secretKey = 'secretKey';
        $captcha->setSiteKey($siteKey)
                ->setSecretKey($secretKey);

        $this->assertSame($siteKey, $captcha->getSiteKey());
        $this->assertSame($secretKey, $captcha->getSecretKey());

        $this->assertSame($siteKey, $captcha->getService()->getSiteKey());
        $this->assertSame($secretKey, $captcha->getService()->getSecretKey());
    }

    public function testSetAndGetRecaptchaServiceSiteAndSecretKeysFromOptions()
    {
        $siteKey = 'siteKey';
        $secretKey = 'secretKey';
        $options = [
            'site_key' => $siteKey,
            'secret_key' => $secretKey
        ];
        $captcha = new ReCaptcha($options);
        $this->assertSame($siteKey, $captcha->getService()->getSiteKey());
        $this->assertSame($secretKey, $captcha->getService()->getSecretKey());
    }

    /** @group ZF-7654 */
    public function testConstructorShouldAllowSettingThemeOptionOnServiceObject()
    {
        $options = ['theme'=>'dark'];
        $captcha = new ReCaptcha($options);
        $this->assertEquals('dark', $captcha->getService()->getOption('theme'));
    }

    /** @group ZF-7654 */
    public function testAllowsSettingThemeOptionOnServiceObject()
    {
        $captcha = new ReCaptcha;
        $captcha->setOption('theme', 'dark');
        $this->assertEquals('dark', $captcha->getService()->getOption('theme'));
    }

    public function testUsesReCaptchaHelper()
    {
        $captcha = new ReCaptcha;
        $this->assertEquals('captcha/recaptcha', $captcha->getHelperName());
    }
}
