<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Captcha;

use DirectoryIterator;
use Zend\Captcha\Image as ImageCaptcha;

/**
 * @group      Zend_Captcha
 */
class ImageTest extends \PHPUnit_Framework_TestCase
{
    protected $tmpDir;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is not available.');
            return;
        }
        if (! function_exists("imagepng")) {
            $this->markTestSkipped("Image CAPTCHA requires PNG support");
        }
        if (! function_exists("imageftbbox")) {
            $this->markTestSkipped("Image CAPTCHA requires FT fonts support");
        }

        if (isset($this->word)) {
            unset($this->word);
        }

        $this->testDir = $this->getTmpDir() . '/ZF_test_images';
        if (! is_dir($this->testDir)) {
            @mkdir($this->testDir);
        }

        $this->captcha = new ImageCaptcha([
            'sessionClass' => 'ZendTest\Captcha\TestAsset\SessionContainer',
            'imgDir'       => $this->testDir,
            'font'         => __DIR__. '/_files/Vera.ttf',
        ]);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    public function tearDown()
    {
        // remove chaptcha images
        foreach (new DirectoryIterator($this->testDir) as $file) {
            if (! $file->isDot() && ! $file->isDir()) {
                unlink($file->getPathname());
            }
        }
    }

    /**
     * Determine system TMP directory
     *
     * @return string
     * @throws \Zend\File\Transfer\Exception\RuntimeException if unable to determine directory
     */
    protected function getTmpDir()
    {
        if (null === $this->tmpDir) {
            $this->tmpDir = sys_get_temp_dir();
        }
        return $this->tmpDir;
    }

    public function testCaptchaSetSuffix()
    {
        $this->captcha->setSuffix(".jpeg");
        $this->assertEquals('.jpeg', $this->captcha->getSuffix());
    }

    public function testCaptchaSetImgURL()
    {
        $this->captcha->setImgUrl("/some/other/url/");
        $this->assertEquals('/some/other/url/', $this->captcha->getImgUrl());
    }

    public function testCaptchaCreatesImage()
    {
        $this->captcha->generate();
        $this->assertFileExists($this->testDir . "/" . $this->captcha->getId() . '.png');
    }

    public function testCaptchaSetExpiration()
    {
        $this->assertEquals($this->captcha->getExpiration(), 600);
        $this->captcha->setExpiration(3600);
        $this->assertEquals($this->captcha->getExpiration(), 3600);
    }

    public function testCaptchaImageCleanup()
    {
        $this->captcha->generate();
        $filename = $this->testDir . "/" . $this->captcha->getId() . ".png";
        $this->assertFileExists($filename);
        $this->captcha->setExpiration(1);
        $this->captcha->setGcFreq(1);
        sleep(2);
        $this->captcha->generate();
        clearstatcache();
        $this->assertFileNotExists($filename, "File $filename was found even after GC");
    }

    /**
     * @group ZF-10006
     */
    public function testCaptchaImageCleanupOnlyCaptchaFilesIdentifiedByTheirSuffix()
    {
        if (! getenv('TESTS_ZEND_CAPTCHA_GC')) {
            $this->markTestSkipped('Enable TESTS_ZEND_CAPTCHA_GC to run this test');
        }
        $this->captcha->generate();
        $filename = $this->testDir . "/" . $this->captcha->getId() . ".png";
        $this->assertFileExists($filename);

        //Create other cache file
        $otherFile = $this->testDir . "/zf10006.cache";
        file_put_contents($otherFile, '');
        $this->assertFileExists($otherFile);
        $this->captcha->setExpiration(1);
        $this->captcha->setGcFreq(1);
        sleep(2);
        $this->captcha->generate();
        clearstatcache();
        $this->assertFileNotExists($filename, "File $filename was found even after GC");
        $this->assertFileExists($otherFile, "File $otherFile was not found after GC");
    }

    public function testGenerateReturnsId()
    {
        $id = $this->captcha->generate();
        $this->assertNotEmpty($id);
        $this->assertInternalType('string', $id);
        $this->id = $id;
    }

    public function testGetWordReturnsWord()
    {
        $this->captcha->generate();
        $word = $this->captcha->getWord();
        $this->assertNotEmpty($word);
        $this->assertInternalType('string', $word);
        $this->assertEquals(8, strlen($word));
        $this->word = $word;
    }

    public function testGetWordLength()
    {
        $this->captcha->setWordLen(4);
        $this->captcha->generate();
        $word = $this->captcha->getWord();
        $this->assertInternalType('string', $word);
        $this->assertEquals(4, strlen($word));
        $this->word = $word;
    }

    public function testGenerateIsRandomised()
    {
        $id1   = $this->captcha->generate();
        $word1 = $this->captcha->getWord();
        $id2   = $this->captcha->generate();
        $word2 = $this->captcha->getWord();

        $this->assertNotEmpty($id1);
        $this->assertNotEmpty($id2);
        $this->assertNotEquals($id1, $id2);
        $this->assertNotEquals($word1, $word2);
    }

    public function testRenderInitializesSessionData()
    {
        $this->captcha->generate();
        $session = $this->captcha->getSession();
        $this->assertEquals($this->captcha->getTimeout(), $session->setExpirationSeconds);
        $this->assertEquals(1, $session->setExpirationHops);
        $this->assertEquals($this->captcha->getWord(), $session->word);
    }

    public function testWordValidates()
    {
        $this->captcha->generate();
        $input = ["id" => $this->captcha->getId(), "input" => $this->captcha->getWord()];
        $this->assertTrue($this->captcha->isValid($input));
    }

    public function testMissingNotValid()
    {
        $this->captcha->generate();
        $this->assertFalse($this->captcha->isValid([]));
        $input = ["input" => "blah"];
        $this->assertFalse($this->captcha->isValid($input));
    }

    public function testWrongWordNotValid()
    {
        $this->captcha->generate();
        $input = ["id" => $this->captcha->getId(), "input" => "blah"];
        $this->assertFalse($this->captcha->isValid($input));
    }

    public function testNoFontProvidedWillThrowException()
    {
        $this->setExpectedException('Zend\Captcha\Exception\NoFontProvidedException');
        $captcha = new ImageCaptcha();
        $captcha->generate();
    }

    public function testImageProvidedNotLoadableWillThrowException()
    {
        $this->setExpectedException('Zend\Captcha\Exception\ImageNotLoadableException');
        $captcha = new ImageCaptcha([
            'font'       => __DIR__. '/../Pdf/_fonts/Vera.ttf',
            'startImage' => 'file_not_found.png',
        ]);
        $captcha->generate();
    }
}
