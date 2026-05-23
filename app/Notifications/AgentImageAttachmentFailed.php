<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Fires when the agent finishes generating a post but couldn't attach a
 * featured image after exhausting every fallback (library → Google →
 * Pexels). Sent to admins so they can sub in a manual image before the
 * scheduled publish time.
 */
final class AgentImageAttachmentFailed extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $postId,
        public readonly string $postTitle,
        public readonly string $query,
        public readonly array $attempts,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $editUrl = route('admin.blog.posts.edit', $this->postId);

        $mail = (new MailMessage)
            ->subject('Action needed: featured image missing for post "'.$this->postTitle.'"')
            ->greeting('Hi '.($notifiable->name ?? 'there').',')
            ->line('The content agent generated post #'.$this->postId.' but could not attach a featured image. Every source we tried (your media library, Google Images, Pexels) either returned no usable results or the candidates failed to download.')
            ->line('Search query used: '.$this->query)
            ->line('Attempts: '.count($this->attempts).' candidate URL(s) tried — see the in-app notification for details.')
            ->action('Add an image', $editUrl)
            ->line('The post is still scheduled and will publish on time — please attach an image before then.');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->postId,
            'post_title' => $this->postTitle,
            'query' => $this->query,
            'attempts' => $this->attempts,
            'edit_url' => route('admin.blog.posts.edit', $this->postId),
        ];
    }
}
