<?php

class Contractor
{
    const TYPE_CUSTOMER = 0;

    private $id;
    private $type;
    private $name;
    private $email;
    private $mobile;

    public function __construct(int $id, int $type, string $name, string $email, bool $mobile)
    {
        $this->id = $id;
        $this->type = $type;
        $this->name = $name;
        $this->email = $email;
        $this->mobile = $mobile;
    }

    public static function getById(int $resellerId): self
    {
        // Здесь ваша реальная логика для получения контрагента по ID
        return new self($resellerId, self::TYPE_CUSTOMER, 'Default Name', 'example@example.com', true);
    }

    public function getFullName(): string
    {
        return $this->name . ' ' . $this->id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function isMobile(): bool
    {
        return $this->mobile;
    }
}
