<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly string $token
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = $this->getResetUrl($notifiable);

        return (new MailMessage)
            ->subject('Reset Password - SIMAK')
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Kami menerima permintaan untuk reset password akun Anda.')
            ->line('Silakan klik tombol di bawah ini untuk reset password:')
            ->action('Reset Password', $resetUrl)
            ->line('Link ini akan expired dalam 24 jam.')
            ->line('Jika Anda tidak meminta reset password, abaikan email ini.')
            ->salutation('Regards, Tim SIMAK');
    }

    /**
     * Get the reset password URL.
     */
    protected function getResetUrl(object $notifiable): string
    {
        // URL ini akan digunakan frontend untuk form reset password
        $frontendUrl = config('app.frontend_url', 'http://localhost:4200');

        return sprintf(
            '%s/auth/reset-password?token=%s&email=%s',
            $frontendUrl,
            $this->token,
            urlencode($notifiable->email)
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'token' => $this->token,
            'email' => $notifiable->email,
            'expires_at' => now()->addHours(24)->toISOString(),
        ];
    }
}
