<?php

namespace App\Services\Glinche;

final class SyncReport
{
    public int $created = 0;

    public int $updated = 0;

    public int $unchanged = 0;

    public int $deleted = 0;

    public int $skipped = 0;

    /**
     * @return array{created: int, updated: int, unchanged: int, deleted: int, skipped: int}
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'unchanged' => $this->unchanged,
            'deleted' => $this->deleted,
            'skipped' => $this->skipped,
        ];
    }
}
