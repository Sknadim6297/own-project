<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomerResetPassword extends ResetPassword
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = url(route('password.reset.form', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $brand = config('yuktione.name', 'YuktiOne');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name') ?: $brand;

        $message = (new MailMessage)
            ->subject('Reset Your Password')
            ->view('emails.password-reset', [
                'brand' => $brand,
                'logoUrl' => asset('frontend/assets/Yuktione_logo.png'),
                'messageIntroLines' => [
                    'Hello,',
                    'We received a request to reset your account password.',
                    'Click the button below to create a new password.',
                ],
                'intro' => 'We received a request to reset your account password. Click the button below to create a new password.',
                'actionText' => 'Reset Password',
                'actionUrl' => $url,
                'expireText' => 'For security reasons, this password reset link will expire in '.config('auth.passwords.users.expire').' minutes.',
                'messageOutroLines' => [
                    'If you did not request a password reset, you can safely ignore this email.',
                    'Regards,',
                    'Support Team',
                ],
                'outro' => 'If you did not request a password reset, you can safely ignore this email.',
            ]);

        if (filled($fromAddress)) {
            $message->from($fromAddress, $fromName);
        }

        return $message;
    }
}
