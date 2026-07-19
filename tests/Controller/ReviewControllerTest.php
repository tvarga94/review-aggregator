<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReviewControllerTest extends WebTestCase
{
    public function testSixthSubmissionWithinTenMinutesIsRateLimited(): void
    {
        $client = static::createClient();
        static::getContainer()->get('limiter.review_submission')->create('127.0.0.1')->reset();

        for ($i = 1; $i <= 5; ++$i) {
            $this->submitReviewForm($client, "ratelimit{$i}@example.com", 'Ez egy automatizált teszt vélemény szövege.');
            self::assertResponseRedirects();
        }

        $this->submitReviewForm($client, 'ratelimit6@example.com', 'Ez a hatodik próbálkozás, ennek már el kell buknia.');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-danger', 'Túl sok véleményt');
    }

    private function submitReviewForm(KernelBrowser $client, string $authorEmail, string $reviewText): void
    {
        $client->request('GET', '/reviews/new');
        $client->submitForm('Vélemény beküldése', [
            'review[companyName]' => 'Rate Limit Teszt Kft.',
            'review[rating]' => '4',
            'review[reviewText]' => $reviewText,
            'review[authorEmail]' => $authorEmail,
        ]);
    }
}
