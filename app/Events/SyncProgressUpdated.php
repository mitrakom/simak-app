<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Progress percentage (0-100)
     */
    public int $progress;

    /**
     * Informative message about current process
     */
    public string $message;

    /**
     * Unique identifier for this sync process
     */
    public string $syncProcessId;

    /**
     * Institusi ID for channel targeting
     */
    public int $institusiId;

    /**
     * Sync type identifier (e.g., 'dosen', 'mahasiswa', etc.)
     */
    public string $syncType;

    /**
     * Additional data (optional)
     */
    public array $data;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $progress,
        string $message,
        string $syncProcessId,
        int $institusiId,
        string $syncType = 'dosen',
        array $data = []
    ) {
        $this->progress = $progress;
        $this->message = $message;
        $this->syncProcessId = $syncProcessId;
        $this->institusiId = $institusiId;
        $this->syncType = $syncType;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sync-process.' . $this->syncProcessId),
            new PrivateChannel('institusi-sync.' . $this->institusiId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'sync.progress.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'progress' => $this->progress,
            'message' => $this->message,
            'sync_process_id' => $this->syncProcessId,
            'institusi_id' => $this->institusiId,
            'sync_type' => $this->syncType,
            'timestamp' => now()->toISOString(),
            'data' => $this->data,
        ];
    }
}
