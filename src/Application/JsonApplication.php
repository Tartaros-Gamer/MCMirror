<?php declare(strict_types=1);

namespace App\Application;

class JsonApplication implements ApplicationInterface
{
    /**
     * @var array
     */
    private $jsonData;

    /**
     * JsonApplication constructor.
     *
     * @param array $jsonData
     */
    public function __construct(array $jsonData)
    {
        $this->jsonData = $jsonData;
    }

    public function isRecommended(): bool
    {
        return $this->jsonData['recommended'] ?? false;
    }

    public function isAbandoned(): bool
    {
        return $this->jsonData['abandoned'] ?? true;
    }

    public function isExternal(): bool
    {
        return $this->jsonData['external'] ?? false;
    }

    public function getName(): string
    {
        return $this->jsonData['name'];
    }

    public function getCategory(): string
    {
        return $this->jsonData['category'] ?? 'Other';
    }

    public function getOfficialLinks(): array
    {
        return $this->jsonData['officialLinks'] ?? [];
    }
}
