<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BrevoMailService
{
    private string $apiKey;

    private string $senderEmail;

    private string $senderName;

    public function __construct()
    {
        $this->apiKey = (string) config('services.brevo.key');
        $this->senderEmail = (string) config('services.brevo.sender_email');
        $this->senderName = (string) config('services.brevo.sender_name');
    }

    public function send(
        string $toEmail,
        string $subject,
        string $textContent,
        ?string $htmlContent = null,
    ): void {
        if ($this->apiKey === '') {
            throw new RuntimeException('Brevo API key is not configured.');
        }

        $payload = [
            'sender' => [
                'name' => $this->senderName,
                'email' => $this->senderEmail,
            ],
            'to' => [
                ['email' => $toEmail],
            ],
            'subject' => $subject,
            'textContent' => $textContent,
        ];

        if ($htmlContent !== null) {
            $payload['htmlContent'] = $htmlContent;
        }

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', $payload);

        if ($response->failed()) {
            Log::warning('Brevo API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Failed to send email. Please try again later.');
        }

        Log::debug('Brevo email sent', [
            'to' => $toEmail,
            'subject' => $subject,
        ]);
    }

    public function sendOtp(
        string $toEmail,
        string $subject,
        string $code,
        string $purpose,
    ): void {
        $textContent = match ($purpose) {
            'email_verification' => "Your verification code is: {$code}\n\nThis code will expire in 10 minutes.\n\nIf you did not create an account, please ignore this email.",
            'password_reset' => "We received a request to reset your password.\n\nYour verification code is: {$code}\n\nThis code will expire in 10 minutes.\n\nIf you did not request a password reset, please ignore this email.",
            default => "Your verification code is: {$code}\n\nThis code will expire in 10 minutes.",
        };

        $this->send($toEmail, $subject, $textContent);
    }

    public function sendPasswordChangedByAdmin(
        string $toEmail,
        string $userName,
    ): void {
        $textContent = "Hello {$userName},\n\nYour password has been changed by an administrator.\n\nIf you did not authorize this change, please contact support immediately.";

        $this->send($toEmail, 'Your Password Has Been Changed', $textContent);
    }
}
