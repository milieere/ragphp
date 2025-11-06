<?php
declare(strict_types=1);

namespace App\Chat\Models;

readonly class Chat {
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt
    ) {}
}
