<?php

namespace MatthiasNoback\Tests\MicrosoftTranslator;

use Buzz\Browser;
use MatthiasNoback\MicrosoftOAuth\AccessTokenProvider;
use MatthiasNoback\MicrosoftTranslator\MicrosoftTranslator;
use MatthiasNoback\MicrosoftOAuth\AccessTokenCache;
use Doctrine\Common\Cache\ArrayCache;

class MicrosoftTranslatorFunctionalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MicrosoftTranslator
     */
    private $translator;

    protected function setUp()
    {
        $browser = new Browser();

        $clientId = $this->getEnvironmentVariable('MICROSOFT_OAUTH_CLIENT_ID');
        $clientSecret = $this->getEnvironmentVariable('MICROSOFT_OAUTH_CLIENT_SECRET');

        $cache = new ArrayCache();
        $accessTokenCache = new AccessTokenCache($cache);
        $accessTokenProvider = new AccessTokenProvider($browser, $clientId, $clientSecret);
        $accessTokenProvider->setCache($accessTokenCache);

        $this->translator = new MicrosoftTranslator($browser, $accessTokenProvider);
    }

    public function testTranslate()
    {
        $translated = $this->translator->translate('This is a test', 'nl', 'en');

        $this->assertSame('Dit is een test', $translated);
    }

    public function testTranslateArray()
    {
        $translatedTexts = $this->translator->translateArray(array(
            'This is a test',
            'My name is Matthias',
        ), 'nl', 'en');

        $this->assertSame(array(
            'Dit is een test',
            'Mijn naam is Matthias'
        ), $translatedTexts);
    }

    public function testDetect()
    {
        $text = 'This is a test';

        $detectedLanguage = $this->translator->detect($text);

        $this->assertSame('en', $detectedLanguage);
    }

    public function testDetectArray()
    {
        $texts = array(
            'This is a test',
            'Dit is een test',
        );

        $detectedLanguages = $this->translator->detectArray($texts);

        $this->assertSame(array('en', 'nl'), $detectedLanguages);
    }

    public function testBreakSentences()
    {
        $text = 'This is the first sentence. This is the second sentence. This is the last sentence.';

        $sentences = $this->translator->breakSentences($text, 'en');

        $this->assertSame(array(
            'This is the first sentence. ',
            'This is the second sentence. ',
            'This is the last sentence.',
        ), $sentences);
    }

    public function testSpeak()
    {
        $saveAudioTo = $this->getEnvironmentVariable('MICROSOFT_TRANSLATOR_SAVE_AUDIO_TO');
        if (!is_writable($saveAudioTo)) {
            $this->markTestSkipped(sprintf('Can not save audio file to "%s"', $saveAudioTo));
        }

        $text = 'My name is Matthias';

        $spoken = $this->translator->speak($text, 'en', 'audio/mp3', 'MaxQuality');

        file_put_contents($saveAudioTo.'/speak.mp3', $spoken);
    }

    public function testGetLanguagesForSpeak()
    {
        $languageCodes = $this->translator->getLanguagesForSpeak();
        $this->assertInternalType('array', $languageCodes);
        $this->assertTrue(count($languageCodes) > 30);
    }

    public function testGetLanguagesForTranslate()
    {
        $languageCodes = $this->translator->getLanguagesForSpeak();
        $this->assertInternalType('array', $languageCodes);
        $this->assertTrue(count($languageCodes) > 30);

        return $languageCodes;
    }

    public function testGetLanguageNames()
    {
        $languageCodes = $this->translator->getLanguagesForSpeak();

        $languageNames = $this->translator->getLanguageNames($languageCodes, 'nl');

        foreach ($languageCodes as $languageCode) {
            $this->assertArrayHasKey($languageCode, $languageNames);
        }
    }

    private function getEnvironmentVariable($name)
    {
        if (!isset($_ENV[$name])) {
            $this->markTestSkipped(sprintf('Environment variable "%s" is missing', $name));
        }

        return $_ENV[$name];
    }
}