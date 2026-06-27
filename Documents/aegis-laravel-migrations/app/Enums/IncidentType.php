<?php

declare(strict_types=1);

namespace App\Enums;

/** Stored in critical_incidents.incident_type and plan_incident_configs.incident_type as VARCHAR(64). */
enum IncidentType: string
{
    case Death            = 'death';
    case Incapacitation   = 'incapacitation';
    case ExtendedAbsence  = 'extended_absence';
    case Missing          = 'missing';
    case Detainment       = 'detainment';
    case NaturalDisaster  = 'natural_disaster';
    case Geopolitical     = 'geopolitical';

    public function label(): string
    {
        return match ($this) {
            self::Death           => 'Death',
            self::Incapacitation  => 'Incapacitation',
            self::ExtendedAbsence => 'Extended Absence',
            self::Missing         => 'Missing',
            self::Detainment      => 'Detainment',
            self::NaturalDisaster => 'Natural Disaster',
            self::Geopolitical    => 'Geopolitical',
        };
    }

    public function isOptIn(): bool
    {
        return in_array($this, [
            self::Missing, self::Detainment,
            self::NaturalDisaster, self::Geopolitical,
        ], true);
    }

    public static function defaultEnabled(): array
    {
        return [self::Death, self::Incapacitation, self::ExtendedAbsence];
    }
}
