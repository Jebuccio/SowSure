<?php
declare(strict_types=1);

function clean_text(mixed $value, int $maxLength = 255): string
{
    $value = trim((string) $value);
    return mb_substr($value, 0, $maxLength);
}

function validate_required(array &$errors, string $field, string $value, string $label): void
{
    if ($value === '') {
        $errors[$field] = $label . ' is required.';
    }
}

function valid_username(string $username): bool
{
    return (bool) preg_match('/^[a-z0-9][a-z0-9._-]{2,29}$/', $username);
}

function valid_password(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/\d/', $password)
        && preg_match('/[^A-Za-z0-9]/', $password);
}

function valid_iso_date(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

function manila_today(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
}

function expected_farrowing_date(string $breedingDate): string
{
    if (!valid_iso_date($breedingDate)) {
        throw new InvalidArgumentException('Invalid breeding date.');
    }
    return (new DateTimeImmutable($breedingDate, new DateTimeZone('Asia/Manila')))
        ->modify('+114 days')
        ->format('Y-m-d');
}

function is_uuid(string $value): bool
{
    return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value);
}
